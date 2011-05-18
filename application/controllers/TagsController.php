<?php
/** @file
 *
 *  This controller controls access to Tags and is accessed via the url/routes:
 *      /tags[/<user>]
 */

class TagsController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public  $dependencies = array('db','layout');

    protected   $_url       = null;
    protected   $_users     = null;

    public      $contexts   = array(
                                'index' => array('partial', 'json',
                                                 'rss',     'atom'),
                              );

    public function init()
    {
        parent::init();

        $this->_baseUrl .= 'tags/';
    }

    /** @brief  Index/Get/Read/View action.
     *
     *  Retrieve a set of Tags based upon the requested 'users'.
     *
     *  Once retrieved, perform further setup based upon the current
     *  context/format.
     */
    public function indexAction()
    {
        //Connexions::log("TagsController::indexAction(): - start");

        $request  =& $this->_request;

        /***************************************************************
         * Process the requested 'users'/'owners'
         *
         */
        $reqUsers = $request->getParam('owners', null);

        // Parse the incoming request users / owners
        $this->_users = $this->service('User')->csList2set($reqUsers);

        /***************************************************************
         * We now have a set of valid 'users' ($this->_users).
         *
         * Adjust the URL to reflect the validated 'users'
         */
        $this->_url = $this->baseUrl
                    . (count($this->_users) > 0
                            ? $this->_users .'/'
                            : '');

        /***************************************************************
         * Set the view variables required for all views/layouts.
         *
         */
        $this->view->headTitle('Tags');

        $this->view->url    = $this->_url;

        $this->view->users  = $this->_users;

        // HTML form/cookie namespace
        $this->_namespace = 'tags';
    }

    /** @brief Redirect all other actions to 'index'
     *  @param  method      The target method.
     *  @param  args        Incoming arguments.
     *
     */
    public function __call($method, $args)
    {
        if (substr($method, -6) == 'Action')
        {
            $owner = substr($method, 0, -6);

            return $this->_forward('index', 'tags', null,
                                   array('owner' => $owner));
        }

        throw new Exception('Invalid method "'. $method .'" called', 500);
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

        $itemBaseUrl = (count($this->_users) === 1
                        ? $this->view->baseUrl('/'. $this->_users .'/')
                        : $this->view->baseUrl('/bookmarks/'));

        $extra = array(
            'users'         => $this->_users,

            'showRelation'  => false,

            'itemType'      => View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM,
            'itemBaseUrl'   => $itemBaseUrl,

            'weightName'    => 'userItemCount',
            'weightTitle'   => 'Bookmarks with this tag',
            'titleTitle'    => 'Tag',
        );
        $config = array_merge($this->view->main, $extra);

        /*
        Connexions::log("TagsController::_prepare_main(): config[ %s ]",
                        Connexions::varExport($config));
        // */

        // Defaults
        if ( ($config['perPage'] = (int)$config['perPage']) < 1)
            $config['perPage'] = 250;

        if ( ($config['page'] = (int)$config['page']) < 1)
            $config['page'] = 1;

        if ((empty($config['sortBy'])) || ($config['sortBy'] === 'title'))
            $config['sortBy'] = 'tag';

        if (empty($config['sortOrder']))
            $config['sortOrder'] = Connexions_Service::SORT_DIR_ASC;

        if (empty($config['displayStyle']))
            $config['displayStyle'] = View_Helper_HtmlItemCloud::STYLE_CLOUD;

        if (empty($config['highlightCount']))
            $config['highlightCount'] = 0;

        // Retrieve the set of tags to be presented.
        $count      = $config['perPage'];
        $offset     = ($config['page'] - 1) * $count;
        $fetchOrder = $config['sortBy'] .' '. $config['sortOrder'];

        /*
        Connexions::log("TagsController::_prepare_main(): "
                        . "offset[ %d ], count[ %d ], order[ %s ]",
                        $offset, $count, $fetchOrder);
        // */

        $config['items'] = Connexions_Service::factory('Model_Tag')
                                    ->fetchByUsers($this->_users,
                                                   $fetchOrder,
                                                   $count,
                                                   $offset,
                                                   true);   // exact users

        $paginator   =  new Zend_Paginator($config['items']
                                                ->getPaginatorAdapter());
        $paginator->setItemCountPerPage( $config['perPage'] );
        $paginator->setCurrentPageNumber($config['page'] );

        $config['paginator']        = $paginator;
        $config['currentSortBy']    = $config['sortBy'];
        $config['currentSortOrder'] = $config['sortOrder'];

        $this->view->main = $config;

        /*
        Connexions::log("TagsController::_prepare_main(): "
                        .   "main[ %s ]",
                        Connexions::varExport($this->view->main));
        // */
    }

    /** @brief  Prepare for rendering the sidebar view.
     *
     *  This will collect the variables needed to render the sidebar view,
     *  placing them in $view->sidebar as a configuration array.
     *
     *  Note: The main index view script
     *        (application/views/scripts/tags/index.phtml) will also add
     *        sidebar-related rendering information to the sidbar helper.  In
     *        particular, it will notify the sidbar helper of the items that
     *        are being presented in the main view.
     */
    protected function _prepare_sidebar()
    {
        parent::_prepare_sidebar();

        $async   = ($this->_format === 'partial'
                        ? false
                        : true);

        $extra = array(
            'users' => $this->_users,
        );
        $this->view->sidebar = array_merge($this->view->sidebar, $extra);

        /**************************************
         * Adjust the default style for the
         * tags pane to be "list", sorted
         * by "weight" ordered "descending".
         *
         */
        $paramNs  =  $this->view->sidebar['panes']['tags']['namespace'];
        $paneTags =& $this->view->sidebar['panes']['tags'];

        $paneTags['showRelation']   = false;
        $paneTags['displayStyle']   = $this->_getDisplayStyle($paramNs,
                                        View_Helper_HtmlItemCloud::STYLE_LIST);
        $paneTags['sortBy']         = $this->_getParam('sortBy', $paramNs,
                                    View_Helper_HtmlItemCloud::SORT_BY_WEIGHT);
        $paneTags['sortOrder']      = $this->_getParam('sortOrder', $paramNs,
                                    Connexions_Service::SORT_DIR_DESC);

        /* Include the information required to determine whether or not to show
         * tag-edit controls.
         */
        $paneTags['viewer']         = $this->_viewer;
        $paneTags['users']          = $this->_users;

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
            if (! isset($this->view->main))
            {
                $this->_prepare_main();
            }

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
        Connexions::log("TagsController::_prepare_sidebar(): "
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

        $config['viewer'] =& $this->_viewer;

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
            // Gather Tag statistics depending on any selected users.
            $tSvc   = $this->service('Tag');
            $params = array(
                'users'     => $this->_users,
                'aggregate' => true,
            );
            $config['stats'] = $tSvc->getStatistics( $params );

            // Construct the timeline
            $params = array(
                'users'     => $this->_users,
            );
            $config['timeline'] = $this->_getTimeline( $params );
            break;

        /*************************************************************
         * Sidebar::People
         *
         */
        case 'people':
            $service    = $this->service('User');
            $fetchOrder = array('totalItems DESC',
                                'totalTags  DESC',
                                'tagCount   DESC',
                                'name       ASC');

            if (count($this->_users) < 1)
            {
                /* There were no requested users that limit the tag 
                 * retrieval so, for the sidebar, retrieve ALL users.
                 */

                /*
                Connexions::log("TagsController::_prepare_sidebarPane( %s ): "
                                .   "Fetch all people %d-%d",
                                $pane,
                                $offset, $offset + $count);
                // */

                $tags = null;   // ALL users
            }
            else
            {
                // Users related to the tags used by the given set of user.

                /*
                Connexions::log("TagsController::_prepare_sidebarPane( %s ): "
                                .   "Fetch users %d-%d related to tags "
                                .   "used by users[ %s ]",
                                $pane,
                                $offset, $offset + $count,
                                Connexions::varExport($this->_users));
                // */

                /* In order to prepare the sidebar, we need to know the set
                 * of users presented in the main view.  If we're rendering 
                 * the main view and sidebar syncrhonously, this MAY have been 
                 * communicated to the sidebar helper via 
                 *      application/view/scripts/people/main.phtml.
                 */
                if (! isset($this->view->main))
                {
                    $this->_prepare_main();
                }

                $tags = $this->view->main['items'];

                /*
                Connexions::log("TagsController::_prepare_sidebarPane( %s ): "
                                .   "tags[ %s ]",
                                $pane,
                                Connexions::varExport($tags));
                // */

                $config['selected'] =& $this->_users;
            }

            /* Retrieve the set of users that are related to the presented 
             * tags.
             */
            if ( ($tags !== null) && (count($tags) < 1) )
            {
                /*
                Connexions::log("TagsController::_prepare_sidebarPane( %s ): "
                                .   "EMPTY tags [ %s ] == empty users",
                                $pane,
                                Connexions::varExport($tags));
                // */

                $users = $this->_owner->getMapper()->makeEmptySet();
            }
            else
            {
                /*
                Connexions::log("TagsController::_prepare_sidebarPane( %s ): "
                                .   "Locate users related to tags[ %s ]",
                                $pane,
                                Connexions::varExport($tags));
                // */

                $users = $service->fetchByTags($tags,
                                               false,   // ANY tag
                                               $fetchOrder,
                                               $count,
                                               $offset);
            }

            /*
            Connexions::log("TagsController::_prepare_sidebarPane( %s ): "
                            .   "users[ %s ]",
                            $pane,
                            Connexions::varExport($users));
            // */



            $config['items']            =& $users;
            //$config['itemBaseUrl']      =  $this->_helper->url(null, 'tags');
            $config['itemBaseUrl']      =  $this->view->baseUrl('/tags/');
            $config['weightName']       =  'totalItems';
            $config['weightTitle']      =  'Total Bookmarks';
            $config['titleTitle']       =  'User';
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
            $service    = $this->service('Item');
            $fetchOrder = array('i.userCount   DESC',
                                'ratingCount   DESC',
                                'url           ASC');
            $items      = $service->fetchByUsers($this->_users,
                                                 $fetchOrder,
                                                 $count,
                                                 $offset);

            $config['items']            =& $items;
            //$config['itemBaseUrl']      =  $this->_helper->url(null, 'url');
            $config['itemBaseUrl']      =  $this->view->baseUrl('/url/');
            $config['weightName']       =  'userCount';
            $config['weightTitle']      =  'Taggers';
            $config['titleTitle']       =  'Item Url';
            $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
            $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;
            break;
        }
    }
}
