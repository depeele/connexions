<?php
/** @file
 *
 *  This controller controls access to a Users Network and is accessed via the
 *  url/routes:
 *      /network[/<user>]
 */

class NetworkController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public      $dependencies   = array('db','layout');

    protected   $_owner         = null;
    protected   $_network       = null;
    protected   $_networkUsers  = null;
    protected   $_tags          = null;

    public      $contexts       = array(
                                    'index' => array('partial', 'json',
                                                     'rss',     'atom'),
                                  );

    public function init()
    {
        parent::init();

        $this->_baseUrl .= 'network/';
    }

    /** @brief  Index/Get/Read/View action.
     *
     *  Retrieve a set of Users that are in the 'user' group of the identified
     *  user (defaults to the currently authenticated user) limited by based
     *  upon the requested 'tags'.
     *
     *  Once retrieved, perform further setup based upon the current
     *  context/format.
     */
    public function indexAction()
    {
        $request =& $this->_request;

        /*
        Connexions::log('NetworkController::indexAction(): '
                        .   'params[ %s ]',
                        print_r($request->getParams(), true));
        // */

        /***************************************************************
         * Process the requested 'owner' and 'tags'
         *
         */
        $reqOwner = $request->getParam('owner', null);
        $reqTags  = $request->getParam('tags',  null);

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
            return $this->_helper->redirector($this->_viewer->name);
        }

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
             * 'owner' to the current viewer.
             */
            $this->_owner =& $this->_viewer;
            $reqTags      =  $reqOwner;
        }
        else
        {
            /* 'tags' have already been specified.  Set 'owner' to the current
             * viewer and report that the provided 'owner' is NOT a valid user.
             */
            $this->_owner      =& $this->_viewer;
            $this->view->error =  "Unknown user [ {$reqOwner} ]";
        }

        /*
        Connexions::log("NetworkController::indexAction: reqTags[ %s ]",
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
        $this->_baseUrl .= $this->_owner->name
                        .  '/';

        $this->_url      = $this->_baseUrl
                         . (count($this->_tags) > 0
                                ? $this->_tags .'/'
                                : '');

        /***************************************************************
         * Retrieve the Network of the requested user.
         *
         */
        $this->_network = $this->_owner->getNetwork();

        /*
        Connexions::log("NetworkController::indexAction(): "
                        .   "owner[ %s ], network[ %s ], people[ %s ]",
                        $this->_owner, $this->_network, $this->_network->items);
        // */

        /***************************************************************
         * Set the view variables required for all views/layouts.
         *
         */
        $this->view->headTitle($this->_owner ."'s Network");

        $this->view->url       = $this->_url;
        $this->view->owner     = $this->_owner;

        //$this->view->users     = $this->_network->items;
        $this->view->tags      = $this->_tags;


        // HTML form/cookie namespace
        $this->_namespace = 'network';
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

        if (! $this->_networkUsers)
        {
            // groupType === 'user' thus Model_Set_User
            try
            {
                $this->_networkUsers = &$this->_network->items;
            }
            catch (Exception $e)
            {
                // No access
                $this->_networkUsers = $this->service('User')->makeEmptySet();
                $this->view->error   =
                                "You do not have access to this network.";
            }
        }

        $extra = array(
            //'users'     => $this->_owner,
            //'network'   => &$this->_network,
            'users'     => &$this->_networkUsers,
            'tags'      => &$this->_tags,
        );

        if (count($this->_networkUsers) < 1)
        {
            $extra['bookmarks'] = $this->service('Bookmark')->makeEmptySet();
        }

        $this->view->main = array_merge($this->view->main, $extra);

        /*
        Connexions::log("NetworkController::_prepare_main(): "
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
        Connexions::log("NetworkController::_prepare_sidebar( %s ), "
                        . "partial[ %s ]",
                        ($async === false ? "sync" : "async"),
                        Connexions::varExport($this->_partials));
        // */
        parent::_prepare_sidebar();

        if (! $this->_networkUsers)
        {
            // groupType === 'user' thus Model_Set_User
            try
            {
                $this->_networkUsers = &$this->_network->items;
            }
            catch (Exception $e)
            {
                // No access
                $this->_networkUsers = $this->service('User')->makeEmptySet();
                $this->view->error   =
                                "You do not have access to this network.";
            }
        }

        // /*
        Connexions::log("NetworkController::_prepare_sidebar( %s ), "
                        . "network users[ %s ]",
                        ($async === false ? "sync" : "async"),
                        Connexions::varExport($this->_networkUsers));
        // */

        $extra = array(
            //'users'     => $this->_owner,
            //'network'   => &$this->_network,
            'users'     => &$this->_networkUsers,
            'tags'      => &$this->_tags,
        );

        if (count($this->_networkUsers) < 1)
        {
            $extra['bookmarks'] = $this->service('Bookmark')->makeEmptySet();
        }

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
            Connexions::log("NetworkController::_prepare_sidebar(): "
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
        Connexions::log("NetworkController::_prepare_sidebar(): "
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

            if (count($this->_networkUsers) < 1)
            {
                /* This user has no network, so the tags list should also be
                 * empty
                 */
                $tags = $service->makeEmptySet();
            }
            else if (count($this->_tags) < 1)
            {
                /* There were no requested tags that limit the bookmark 
                 * retrieval so, for the sidebar, retrieve ALL tags limited 
                 * only by the current owner (if any).
                 */

                /*
                Connexions::log("NetworkController::"
                                .   "_prepare_sidebarPane( %s ): "
                                .   "Fetch tags %d-%d by user [ %s ]",
                                $pane,
                                $offset, $offset + $count,
                                Connexions::varExport($this->_owner));
                // */

                $tags = $service->fetchByUsers($this->_networkUsers,
                                               $fetchOrder,
                                               $count,
                                               $offset);
            }
            else
            {
                // Tags related to the bookmarks with the given set of tags.

                /*
                Connexions::log("NetworkController::"
                                .   "_prepare_sidebarPane( %s ): "
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
                 *      application/view/scripts/network/main.phtml.
                 */
                if (! isset($this->view->main))
                {
                    $this->_prepare_main();
                }

                $bookmarks = (isset($this->view->main['items'])
                                ? $this->view->main['items']
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
                    $bookmarks = $helper->getBookmarks();   //bookmarks;
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
            break;

        /*************************************************************
         * Sidebar::People
         *
         */
        case 'people':
            /*
            Connexions::log("NetworkController::_prepare_sidebarPane( %s ): "
                            .   "tags [ %s ]",
                            $pane,
                            $this->_tags);
            // */

            if (count($this->_networkUsers) < 1)
            {
                /* This user has no network, so the tags list should also be
                 * empty
                 */
                $config['items'] = $this->service('User')->makeEmptySet();
            }
            else if (count($this->_tags) < 1)
            {
                /* There were no requested tags that limit the users so, for
                 * the sidebar, present ALL people in this user's network.
                 */
                $config['items'] =& $this->_networkUsers;
            }
            else
            {
                // Users related to the bookmarks with the given set of tags.
                $service = $this->service('User');

                /* In order to prepare the sidebar, we need to know the set of
                 * bookmarks presented in the main view, as well as have
                 * '$this->view->main' established.  If we're rendering the
                 * main view and sidebar syncrhonously, this MAY have been
                 * communicated to the sidebar helper via 
                 *      application/view/scripts/network/main.phtml.
                 */
                if (! isset($this->view->main))
                {
                    /*
                    Connexions::log("NetworkController::"
                                    .   "_prepare_sidebarPane( %s ): "
                                    .   "invoke _prepare_main() to try and "
                                    .   "gather the set of bookmarks being "
                                    .   "presented.",
                                    $pane);
                    // */

                    $this->_prepare_main();
                }

                $bookmarks = (isset($this->view->main['items'])
                                ? $this->view->main['items']
                                : null);
                if ($bookmarks === null)
                {
                    /*
                    Connexions::log("NetworkController::"
                                    .   "_prepare_sidebarPane( %s ): "
                                    .   "_prepare_main() did NOT provide the "
                                    .   "bookmarks.  "
                                    .   "Retrieve them ourselves...",
                                    $pane);
                    // */

                    $overRides = array_merge($this->view->main,
                                             array('perPage' => -1));

                    $helper    = $this->view->bookmarks( $overRides );
                    $bookmarks = $helper->bookmarks;
                }

                /*
                Connexions::log("NetworkController::"
                                .   "_prepare_sidebarPane( %s ): "
                                .   "presented bookmarks[ %s ]",
                                $pane, $bookmarks);
                // */

                /* Retrieve the set of users that are related to the presented 
                 * bookmarks.
                 */
                $config['items'] = $service->fetchByBookmarks($bookmarks,
                                                              $fetchOrder,
                                                              $count,
                                                              $offset);

                /*
                Connexions::log("NetworkController::"
                                .   "_prepare_sidebarPane( %s ): "
                                .   "retrieved people[ %s ]",
                                $pane, Connexions::varExport($config['items']));
                // */

            }

            $config['showControls']     = true;
            $config['itemsType']        =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_USER;
            $config['itemBaseUrl']      =  $this->_helper->url(null, 'network');
                                            //$this->view->baseUrl('/url/');
            $config['weightName']       = 'totalItems';
            $config['weightTitle']      = 'Bookmarks';
            /*
            $config['displayStyle']     =
                                 View_Helper_HtmlItemCloud::STYLE_LIST;
            // */
            $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
            $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;
            /*
            Connexions::log("NetworkController::_prepare_sidebarPane( %s ): "
                            .   "People [ %s ]",
                            $pane,
                            Connexions::varExport($config['items']));
            // */
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

            /*
            Connexions::log("NetworkController::_prepare_sidebarPane( %s ): "
                            .   "Fetch items %d-%d for all users "
                            .   "related to tags [ %s ]",
                            $pane,
                            $offset, $offset + $count,
                            Connexions::varExport($this->_tags));
            // */


            $service = $this->service('Item');
            if (count($this->_networkUsers) < 1)
            {
                /* This user has no network, so the tags list should also be
                 * empty
                 */
                $items = $service->makeEmptySet();
            }
            else
            {
                $items = $service->fetchByUsersAndTags($this->_networkUsers,
                                                       $this->_tags,
                                                       false,   // exact Users
                                                       true,    // exact Tags
                                                       $fetchOrder,
                                                       $count,
                                                       $offset);
            }

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
