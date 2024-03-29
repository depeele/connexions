<?php
/** @file
 *
 *  This controller controls access to the "inbox" for users or user-groups.
 *  It may be access via the url/routes:
 *      /inbox/<user>[/<tag list>]]
 */

class InboxController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public  $dependencies = array('db','layout');

    protected   $_url           = null;
    protected   $_owner         = null;
    protected   $_tags          = null;

    protected   $_forTag        = null;
    protected   $_allTags       = null; // _tags + _forTag


    public      $contexts       = array(
                                    'index' => array('partial', 'json',
                                                     'rss',     'atom'),
                                );

    /** @brief  Index/Get/Read/View action.
     *
     *  Retrieve a set of Bookmarks based upon the requested
     *  'owner' and/or 'tags', including the 'for:%user%' tag.
     *
     *  Once retrieved, perform further setup based upon the current
     *  context/format.
     */
    public function indexAction()
    {
        $request       =& $this->_request;

        /***************************************************************
         * Process the requested 'owner' and 'tags'
         *
         */
        $reqOwner = $request->getParam('owner', null);
        $reqTags  = $request->getParam('tags', null);

        // See if the requested user is one of the special 'self' indicators.
        if ( ($reqOwner === null)    ||
             ($reqOwner === '@mine') ||
             ($reqOwner === '@self') ||
             ($reqOwner === 'mine')  ||
             ($reqOwner === 'me')    ||
             ($reqOwner === 'self') )
        {
            // 'mine' == the currently authenticated user (viewer)
            if ( ( ! $this->_viewer instanceof Model_User) ||
                 (! $this->_viewer->isAuthenticated()) )
            {
                // Unauthenticated user -- Redirect to signIn
                return $this->_redirectToSignIn();
            }

            // Redirect to the viewer's network
            $url = $this->_viewer->name .'/'. $reqTags;
            return $this->_helper->redirector( $url );
        }

        // Does the name match an existing user?
        if ($reqOwner === $this->_viewer->name)
        {
            // 'name' matches the current viewer...
            $this->_owner =& $this->_viewer;
        }
        else
        {
            //$ownerInst = Model_User::find(array('name' => $name));
            $this->_owner = $this->service('User')
                                ->find(array('name' => $reqOwner));
        }

        if ($this->_owner === null)
        {
            // Unknown User
            $this->view->error = "Unknown user [ {$reqOwner} ]";
            return;
        }

        /* :TODO: Is 'viewer' allowed to see the inbox of 'owner'??
         *        - For a user       inbox,
         *          or  a user-group inbox with visibility:private
         *                              ONLY the owner may view
         *        - For a user-group inbox with visibility:group
         *                              ONLY members of the group may view
         *        - For a user-group inbox with visibility:public
         *                              anyone can view
         *
         * For now, only the owner may view.
         */

        // Parse the incoming request tags
        $tService      = $this->service('Tag');

        $this->_tags   = $tService->csList2set($reqTags);

        // And retrieve a Domain Model instance representing the for tag.
        $this->_forTag = $tService->get('for:'. $this->_owner->name);

        $this->_allTags = clone $this->_tags;
        $this->_allTags->append( $this->_forTag );

        /*
        Connexions::log("InboxController::indexAction(): "
                        . "tags[ %s ], forTag[ %s ], allTags[ %s ]",
                        Connexions::varExport($this->_tags),
                        Connexions::varExport($this->_forTag),
                        Connexions::varExport($this->_allTags));
        // */

        /***************************************************************
         * We now have a valid 'owner' ($this->_owner) and
         * 'tags' ($this->_tags)
         *
         * Adjust the URL to reflect the validated 'owner' and 'tags'
         */
        $this->_baseUrl .= 'inbox/'
                        .  $this->_owner->name
                        .  '/';

        $this->_url      = $this->_baseUrl
                         . (count($this->_tags) > 0
                                ? $this->_tags .'/'
                                : '');

        /***************************************************************
         * Set the view variables required for all views/layouts.
         *
         */
        $this->view->headTitle($this->_owner ."'s Inbox");

        $this->view->baseUrl   = $this->_baseUrl;
        $this->view->url       = $this->_url;

        $this->view->owner     = $this->_owner;
        $this->view->allTags   = $this->_allTags;

        $this->view->tags      = $this->_tags;

        /* Finally, IF 'owner' === 'viewer' AND 'viewer' is authenticated, save
         * the current 'lastVisitFor' value for later use by
         * View_Helper_InitNavMenu, and then update it.
         */
        if ( ($this->_viewer->getId() == $this->_owner->getId()) &&
             ($this->_viewer->isAuthenticated()) )
        {
            /* Record the initial 'lastVisitFor' value so
             *  View_Helper_InitNavMenu has access to the value
             *  BEFORE we reset it here
             */
            $this->view->lastVisitFor = $this->_viewer->lastVisitFor;

            // /*
            Connexions::log("InboxController::indexAction(): "
                            . "update lastVisitFor for owner '%s'",
                            $this->_viewer);
            // */

            $this->_viewer->updateLastVisitFor();
            $this->_viewer->save();
        }


        /* HTML form/cookie namespace
         * (same as IndexController since we're presenting bookmarks)
         *
         * Set this to a different value, or change 'cookiePath', to allow
         * independent settings for paging and displayOptions.
         */
        $this->_namespace = 'bookmarks';
    }

    /*************************************************************************
     * Protected Helpers
     *
     */

    /** @brief  Prepare for rendering the main view, regardless of format.
     *
     *  This will collect the variables needed to render the main view, placing
     *  them in $view->main as a configuration array.
     */
    protected function _prepare_main()
    {
        parent::_prepare_main();

        $extra = array(
            'tags'   => &$this->_allTags,
        );
        $this->view->main = array_merge($this->view->main, $extra);

        /*
        Connexions::log("InboxController::_prepare_main(): "
                        .   "main[ %s ]",
                        Connexions::varExport($this->view->main));
        // */
    }

    /** @brief  Prepare for rendering the sidebar view.
     *
     *  This will collect the variables needed to render the sidebar view,
     *  placing them in $view->sidebar as a configuration array.
     */
    protected function _prepare_sidebar()
    {
        $async = ($this->_format === 'partial'
                        ? false
                        : true);

        /*
        Connexions::log("InboxController::_prepare_sidebar( %s )",
                        ($async ? "async" : "sync"));
        // */
        parent::_prepare_sidebar($async);

        $extra = array(
            'tags'  => &$this->_allTags,
        );
        $this->view->sidebar = array_merge($this->view->sidebar, $extra);


        /******************************************************************
         * Create a Sidebar Helper using the configuration information
         * that we've gathered thus far.
         *
         */
        if ($async === false)
        {
            /* Finialize sidebar preparations by retrieving the necessary model 
             * data for those portions of the sidebar that are to be rendered.
             *
             * The fact that actual sidebar rendering will occur is indicated 
             * by 'async' == false.  The value of 'pane' will indicate which 
             * sidebar pane is to be rendered with null meaning that they will 
             * all be rendered.
             */

            /*
            Connexions::log("InboxController::_prepare_sidebar(): "
                            . "!async, partials %sarray [ %s ]",
                            (is_array($this->_partials) ? "" : "!"),
                            Connexions::varExport($this->_partials));
            // */

            $part = (is_array($this->_partials)
                        ? $this->_partials[1]
                        : null);

            if ( ($part === null) || ($part === 'tags') )
            {
                $this->_prepare_sidebarPane('tags');
            }

            if ( ($part === null) || ($part === 'people') )
            {
                $this->_prepare_sidebarPane('people');
            }

            if ( ($part === null) || ($part === 'items') )
            {
                $this->_prepare_sidebarPane('items');
            }
        }

        /*
        Connexions::log("InboxController::_prepare_sidebar(): "
                        .   "sidebar[ %s ]",
                        Connexions::varExport($this->view->sidebar));
        // */
    }

    /** @brief  Given the portion of the sidebar to prepare, along with an
     *          instance of the sidebar helper, finish preparations for the 
     *          sidebar portion by retrieving the model data that will be 
     *          presented.
     *  @param  pane    The portion of the sidebar to render
     *                  (tags | people | items);
     *
     */
    protected function _prepare_sidebarPane($pane)
    {
        $config =& $this->view->sidebar['panes'][$pane];

        $perPage = ((int)$config['perPage'] > 0
                        ? (int)$config['perPage']
                        : 100);
        $page    = ((int)$config['page'] > 0
                        ? (int)$config['page']
                        : 1);

        $count   = $perPage;
        $offset  = ($page - 1) * $perPage;

        switch ($pane)
        {
        /*************************************************************
         * Sidebar::Tags
         *
         */
        case 'tags':
            $service    = $this->service('Tag');
            $fetchOrder = array('userItemCount DESC',
                                'userCount     DESC',
                                'itemCount     DESC',
                                'tag           ASC');

            // Tags related to the bookmarks with the given set of tags.

            /*
            Connexions::log("InboxController::_prepare_sidebarPane( %s ): "
                            .   "Fetch tags %d-%d related to bookmarks "
                            .   "with tags[ %s ]",
                            $pane,
                            $offset, $offset + $count,
                            Connexions::varExport($this->_allTags));
            // */

            /* In order to prepare the sidebar, we need to know the set
             * of bookmarks presented in the main view.  If we're rendering 
             * the main view and sidebar syncrhonously, this MAY have been 
             * communicated to the sidebar helper via 
             *      application/view/scripts/index/main.phtml.
             */
            if (! isset($this->view->main))
            {
                $this->_prepare_main();
            }

            $bookmarks = (isset($main['items'])
                            ? $main['items']
                            : null);
            if ($bookmarks === null)
            {
                /* The set of bookmarks presented in the main view has not 
                 * been communicated to the sidebar helper.  We need to 
                 * generate them now using the non-format related 
                 * View_Helper_Bookmarks to generate the appropriate set of 
                 * bookmarks, telling the helper to return ALL bookmarks by 
                 * setting 'perPage' to -1.
                 */
                $overRides = array_merge($this->view->main,
                                         array('perPage' => -1));

                $helper    = $this->view->bookmarks( $overRides );
                $bookmarks = $helper->bookmarks;


                /*
                Connexions::log("InboxController::_prepare_sidebarPane( %s ): "
                                .   "%d bookmarks in main view",
                                $pane,
                                count($bookmarks));
                // */
            }

            /*
            Connexions::log("InboxController::_prepare_sidebarPane( %s ): "
                            .   "bookmarks are %sempty, ",
                            $pane,
                            (empty($bookmarks) ? '' : 'NOT '));
            // */

            if (! empty($bookmarks))
            {
                /* Retrieve the set of tags that are related to the presented 
                 * bookmarks.
                 */
                $config['items'] =
                    $service->fetchByBookmarks($bookmarks,
                                               $fetchOrder,
                                               $count,
                                               $offset);
            }

            $config['selected']         =& $this->_tags;
            $config['hiddenItems']      = array( $this->_forTag->tag );

            $config['itemType']         =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
            $config['itemBaseUrl']      =  $this->_url;

            $config['weightName']       =  'userItemCount';
            $config['weightTitle']      =  'Bookmarks with this tag';
            $config['titleTitle']       =  'Tag';
            $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
            $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;
            break;

        /*************************************************************
         * Sidebar::People
         *
         */
        case 'people':
            /* Order by userItem/Bookmark count here so the most used items 
             * will be in the limited set.  User-requested sorting will be 
             * performed later (via View_Helper_HtmlItemCloud) before the 
             * cloud or list is rendered.
             */

            /*
            Connexions::log("InboxController::_prepare_sidebarPane( %s ): "
                            .   "Fetch people %d-%d related to tags[ %s ]",
                            $pane,
                            $offset, $offset + $count,
                            Connexions::varExport($this->_tags));
            // */

            $fetchOrder = array('userItemCount DESC',
                                'userCount     DESC',
                                'itemCount     DESC',
                                'name          ASC');

            // Fetch related users by tag
            $service = $this->service('User');
            $users   = $service->fetchByTags($this->_allTags,
                                             true,    // exact
                                             $fetchOrder,
                                             $count,
                                             $offset);


            $config['items']            =& $users;
            $config['itemType']         =
                             View_Helper_HtmlItemCloud::ITEM_TYPE_USER;
            $config['weightName']       =  'userItemCount';
            $config['weightTitle']      =  'Bookmarks';
            $config['currentSortBy']    =
                             View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
            $config['currentSortOrder'] =
                             Connexions_Service::SORT_DIR_DESC;
            break;

        /*************************************************************
         * Sidebar::Items
         *
         */
        case 'items':
            // ALL users - sort by userItemCount
            $fetchOrder = array('userItemCount DESC',
                                'ratingCount   DESC',
                                'url           ASC');

            $config['weightName']  = 'userItemCount';
            $config['weightTitle'] = 'Bookmarks';

            $service = $this->service('Item');
            $items   = $service->fetchByTags( $this->_allTags,
                                              true,    // exact
                                              $fetchOrder,
                                              $count,
                                              $offset);

            $config['items']            =& $items;
            $config['itemType']         =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
            $config['itemBaseUrl']      =  $this->_helper->url(null, 'url');
                                            //$this->view->baseUrl('/url/');
            $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
            $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;
            break;
        }
    }

}
