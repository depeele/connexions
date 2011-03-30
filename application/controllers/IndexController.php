<?php
/** @file
 *
 *  This controller controls access to Bookmarks and is accessed
 *  via the url/routes:
 *      /[ (<user> | bookmarks) [/<tag list>]]
 */

class IndexController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public  $dependencies = array('db','layout');

    protected   $_owner         = null;
    protected   $_tags          = null;

    public      $contexts       = array(
                                    'index' => array('partial', 'json',
                                                     'rss',     'atom'),
                                );

    /** @brief  Index/Get/Read/View action.
     *
     *  Retrieve a set of Bookmarks based upon the requested
     *  'owner' and/or 'tags'.
     *
     *  Once retrieved, perform further setup based upon the current
     *  context/format.
     */
    public function indexAction()
    {
        $request  =& $this->_request;

        /*
        Connexions::log('IndexController::indexAction(): '
                        .   'params[ %s ]',
                        print_r($request->getParams(), true));
        // */

        /***************************************************************
         * Process the requested 'owner' and 'tags'
         *
         */
        $reqOwner = $request->getParam('owner', null);
        $reqTags  = $request->getParam('tags', null);

        /* If this is a user/"owned" area (e.g. /<userName> [/ <tags ...>]),
         * verify the validity of the requested user.
         */
        if ( ($reqOwner === 'mine') ||
             ($reqOwner === 'me')   ||
             ($reqOwner === 'self') )
        {
            // 'mine' == the currently authenticated user (viewer)
            if ( ( ! $this->_viewer instanceof Model_User) ||
                 (! $this->_viewer->isAuthenticated()) )
            {
                // Unauthenticated user -- Redirect to signIn
                return $this->_redirectToSignIn();
            }

            // Redirect to the viewer's bookmarks
            $url = $this->_viewer->name .'/'. $reqTags;
            return $this->_helper->redirector( $url );
        }

        if ($reqOwner === '*')
        {
            $this->_owner = $reqOwner;
        }
        else
        {
            // Resolve the incoming 'owner' name.
            $user = $this->_resolveUserName($reqOwner);

            if ($user !== null) // ($user instanceof Model_User)
            {
                // A valid user
                $this->_owner = $user;
            }
            // 'owner' is NOT a valid user.
            else if (empty($reqTags))   // If 'tags' are empty, use 'owner'
            {
                /* No 'tags' were specified.  Use the owner as 'tags' and set
                 * 'owner' to '*'
                 */
                $this->_owner = '*';
                $reqTags      = $reqOwner;
            }
            else
            {
                /* 'tags' have already been specified.  Set 'owner' to '*' and
                 * report that the provided 'owner' is NOT a valid user.
                 */
                $this->_owner      = '*';
                $this->view->error = "Unknown user [ {$reqOwner} ]";
            }
        }

        /*
        Connexions::log("IndexController::indexAction: reqTags[ %s ]",
                        $reqTags);
        // */

        // Parse the incoming request tags
        $this->_tags = $this->service('Tag')->csList2set($reqTags);

        /***************************************************************
         * We now have a valid 'owner' ($this->_owner) and
         * 'tags' ($this->_tags)
         *
         * Adjust the URL to reflect the validated 'owner' and 'tags'
         */
        $this->_baseUrl .= ($this->_owner instanceof Model_User
                                ? $this->_owner->name
                                : 'bookmarks')
                        .  '/';

        $this->_url      = $this->_baseUrl
                         . (count($this->_tags) > 0
                                ? $this->_tags .'/'
                                : '');

        /***************************************************************
         * Set the view variables required for all views/layouts.
         *
         */
        if ($this->_owner !== '*')
            $this->view->headTitle($this->_owner ."'s Bookmarks");
        else
            $this->view->headTitle('Bookmarks');

        $this->view->url       = $this->_url;
        $this->view->owner     = $this->_owner;

        $this->view->tags      = $this->_tags;


        // HTML form/cookie namespace
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
            'users' => ($this->_owner !== '*'
                            ? $this->_owner
                            : null),
            'tags'  => &$this->_tags,
        );
        $this->view->main = array_merge($this->view->main, $extra);

        // /*
        Connexions::log("IndexController::_prepare_main(): "
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
        $async   = ($this->_format === 'partial'
                        ? false
                        : true);

        /*
        Connexions::log("IndexController::_prepare_sidebar(): %s",
                        ($async ? "async" : "sync"));
        // */
        parent::_prepare_sidebar();

        $extra = array(
            'users' => ($this->_owner !== '*'
                            ? $this->_owner
                            : null),
            'tags'  => &$this->_tags,
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
            Connexions::log("IndexController::_prepare_sidebar(): "
                            . "sync, partials %sarray [ %s ]",
                            (is_array($this->_partials) ? "" : "!"),
                            Connexions::varExport($this->_partials));
            // */

            $part = (count($this->_partials) > 1
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
        Connexions::log("IndexController::_prepare_sidebar(): "
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

            if (count($this->_tags) < 1)
            {
                /* There were no requested tags that limit the bookmark 
                 * retrieval so, for the sidebar, retrieve ALL tags limited 
                 * only by the current owner (if any).
                 */

                /*
                Connexions::log("IndexController::_prepare_sidebarPane( %s ): "
                                .   "Fetch tags %d-%d by user [ %s ]",
                                $pane,
                                $offset, $offset + $count,
                                Connexions::varExport($this->_owner));
                // */

                $tags = $service->fetchByUsers(($this->_owner === '*'
                                                ? null            // ALL users
                                                : $this->_owner), // ONE user
                                               $fetchOrder,
                                               $count,
                                               $offset);
            }
            else
            {
                // Tags related to the bookmarks with the given set of tags.

                /*
                Connexions::log("IndexController::_prepare_sidebarPane( %s ): "
                                .   "Fetch tags %d-%d related to bookmarks "
                                .   "with tags[ %s ]",
                                $pane,
                                $offset, $offset + $count,
                                Connexions::varExport($this->_tags));
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

                $bookmarks = (isset($this->view->main['items'])
                                ? $this->view->main['items']
                                : null);
                if ($boomkarks === null)
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
                }

                /* Retrieve the set of tags that are related to the presented 
                 * bookmarks.
                 */
                $tags = $service->fetchByBookmarks($bookmarks,
                                                   $fetchOrder,
                                                   $count,
                                                   $offset);

                $config['selected'] =& $this->_tags;
            }

            $config['items']            =& $tags;
            $config['itemsType']        =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
            $config['weightName']       =  'userItemCount';
            $config['weightTitle']      =  'Bookmarks with this tag';
            $config['titleTitle']       =  'Tag';
            $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
            $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;

            /* Include the information required to determine whether or not to
             * show tag-edit controls.
             */
            $config['viewer']           = $this->_viewer;
            $config['users']            = $this->_owner;
            break;

        /*************************************************************
         * Sidebar::People
         *
         */
        case 'people':
            if ($this->_owner === '*')
            {
                /* Order by userItem/Bookmark count here so the most used items 
                 * will be in the limited set.  User-requested sorting will be 
                 * performed later (via View_Helper_HtmlItemCloud) before the 
                 * cloud or list is rendered.
                 */

                /*
                Connexions::log("IndexController::_prepare_sidebarPane( %s ): "
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
                $users   = $service->fetchByTags($this->_tags,
                                                 true,    // exact
                                                 $fetchOrder,
                                                 $count,
                                                 $offset);


                $config['items']            =& $users;
                $config['itemsType']        =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_USER;
                $config['weightName']       =  'userItemCount';
                $config['weightTitle']      =  'Bookmarks';
                $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
                $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;
            }
            else
            {
                // A single user's bookmarks -- show just the "owner"

                /*
                Connexions::log("IndexController::_prepare_sidebarPane( %s ): "
                                .   "Present JUST the owner [ %s ]",
                                $pane,
                                Connexions::varExport($this->_owner));
                // */

            }
            break;

        /*************************************************************
         * Sidebar::Items
         *
         */
        case 'items':
            if ($this->_owner === '*')
            {
                // ALL users - sort by userItemCount
                $fetchOrder = array('userItemCount DESC',
                                    'ratingCount   DESC',
                                    'url           ASC');

                $users                 = null;
                $config['weightName']  = 'userItemCount';
                $config['weightTitle'] = 'Bookmarks';

                /*
                Connexions::log("IndexController::_prepare_sidebarPane( %s ): "
                                .   "Fetch items %d-%d for all users "
                                .   "related to tags [ %s ]",
                                $pane,
                                $offset, $offset + $count,
                                Connexions::varExport($this->_tags));
                // */

            }
            else
            {
                // A single user's bookmarks -- sort by rating
                $fetchOrder = array('ratingAvg DESC',
                                    'url       ASC');

                $users                 =& $this->_owner;
                $config['weightName']  =  'ratingAvg';
                $config['weightTitle'] =  'Average Rating';

                /*
                Connexions::log("IndexController::_prepare_sidebarPane( %s ): "
                                .   "Fetch items %d-%d for owner[ %s ] "
                                .   "related to tags [ %s ]",
                                $pane,
                                $offset, $offset + $count,
                                Connexions::varExport($this->_owner),
                                Connexions::varExport($this->_tags));
                // */
            }

            $service = $this->service('Item');
            $items   = $service->fetchByUsersAndTags($users,
                                                     $this->_tags,
                                                     true,    // exact Users
                                                     true,    // exact Tags
                                                     $fetchOrder,
                                                     $count,
                                                     $offset);

            $config['items']            =& $items;
            $config['itemsType']        =
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
