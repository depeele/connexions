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

    /** @brief  Index/Get/Read/View action.
     *
     *  Retrieve a set of Tags based upon the requested 'users'.
     *
     *  Once retrieved, perform further setup based upon the current
     *  context/format.
     */
    public function indexAction()
    {
        Connexions::log("TagsController::indexAction(): - start");

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
        $this->_url = $request->getBasePath()
                    . '/tags'
                    . '/' .(count($this->_users) > 0
                            ? $this->_users .'/'
                            : '');

        /***************************************************************
         * Set the view variables required for all views/layouts.
         *
         */
        $this->view->headTitle('Tags');

        $this->view->url    = $this->_url;
        $this->view->viewer = $this->_viewer;

        $this->view->users  = $this->_users;

        // Handle this request based on the current context / format
        $this->_handleFormat('tags');

        Connexions::log("TagsController::indexAction(): - complete");
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
    protected function _prepareMain($htmlNamespace  = '')
    {
        parent::_prepareMain($htmlNamespace);

        $extra = array(
            'users' => $this->_users,
        );
        $config = array_merge($this->view->main, $extra);

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

        Connexions::log("TagsController::_prepareMain(): "
                        . "offset[ %d ], count[ %d ], order[ %s ]",
                        $count, $offset, $fetchOrder);
        $config['tags'] = Connexions_Service::factory('Model_Tag')
                                    ->fetchByUsers($this->_users,
                                                   $fetchOrder,
                                                   $count,
                                                   $offset,
                                                   true);   // exact users

        $paginator   =  new Zend_Paginator($config['tags']
                                                ->getPaginatorAdapter());
        $paginator->setItemCountPerPage( $config['perPage'] );
        $paginator->setCurrentPageNumber($config['page'] );

        $config['paginator'] = $paginator;

        $this->view->main = $config;

        /*
        Connexions::log("TagsController::_prepareMain(): "
                        .   "main[ %s ]",
                        Connexions::varExport($this->view->main));
        // */
    }

    /** @brief  Prepare for rendering the sidebar view.
     *  @param  part    The part/pane of the sidebar to be rendered
     *                  (tags | people | items) [ null == all ]
     *  @param  async   Should we setup to do an asynchronous render
     *                  (i.e. tab callbacks will request tab pane contents when 
     *                        needed)?
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
    protected function _prepareSidebar($part    = null,
                                       $async   = false)
    {
        parent::_prepareSidebar($part, $async);

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
        $paramNs = $this->view->sidebar['panes']['tags']['namespace'];

        $this->view->sidebar['panes']['tags']['displayStyle'] =
                        $this->_request->getParam($paramNs .'OptionGroup',
                                    View_Helper_HtmlItemCloud::STYLE_LIST);
        $this->view->sidebar['panes']['tags']['sortBy'] =
                        $this->_request->getParam($paramNs .'SortBy',
                                    View_Helper_HtmlItemCloud::SORT_BY_WEIGHT);
        $this->view->sidebar['panes']['tags']['sortOrder'] =
                        $this->_request->getParam($paramNs .'SortOrder',
                                    Connexions_Service::SORT_DIR_DESC);

        /******************************************************************
         * Create a Sidebar Helper using the configuration information
         * that we've gathered thus far.
         *
         */
        $sidebar = $this->view->htmlSidebar( $this->view->sidebar );
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
                $this->_prepareMain();
            }

            if ( ($part === null) || ($part === 'tags') )
            {
                $this->_prepareSidebarPane('tags', $sidebar);
            }

            if ( ($part === null) || ($part === 'people') )
            {
                $this->_prepareSidebarPane('people', $sidebar);
            }

            if ( ($part === null) || ($part === 'items') )
            {
                $this->_prepareSidebarPane('items', $sidebar);
            }
        }

        // Pass the configured instance of the sidebar helper to the views
        $this->view->sidebarHelper = $sidebar;
        /*
        Connexions::log("TagsController::_prepareSidebar(): "
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
     *  @param  sidebar The View_Helper_HtmlSidebar instance;
     *
     */
    protected function _prepareSidebarPane(                        $pane,
                                           View_Helper_HtmlSidebar &$sidebar)
    {
        $config  = $sidebar->getPane($pane);

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
            // Tags related to the given set of tags (i.e. used by them all).
            $service    = $this->service('Tag');
            $fetchOrder = array('userItemCount DESC',
                                 'userCount     DESC',
                                'itemCount     DESC',
                                'tag           ASC');

            /* Retrieve the set of tags that are related to the presented
             * bookmarks.
             */
            $tags = $service->fetchByUsers($this->_users,
                                           $fetchOrder,
                                           $count,
                                           $offset,
                                           true);   // used by ALL users

            //$config['selected']         =& $this->_users;

            $config['items']            =& $tags;
            $config['itemsType']        =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
            $config['itemBaseUrl']      =  $this->_helper->url(null,
                                                               'bookmarks');

            /* :NOTE: In this context, userItemCount can also represent
             *        the total user count.
             */
            $config['weightName']       =  'userItemCount';
            $config['weightTitle']      =  'Users of this tag';
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

                // /*
                Connexions::log("TagsController::_prepareSidebarPane( %s ): "
                                .   "Fetch all people %d-%d",
                                $pane,
                                $offset, $offset + $count);
                // */

                $tags = null;   // ALL users
            }
            else
            {
                // Users related to the tags used by the given set of user.

                // /*
                Connexions::log("TagsController::_prepareSidebarPane( %s ): "
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
                    $this->_prepareMain();
                }

                $tags = $this->view->main['tags'];

                $config['selected'] =& $this->_users;
            }

            /* Retrieve the set of users that are related to the presented 
             * tags.
             */
            $users = $service->fetchByTags($tags,
                                           false,   // ANY tag
                                           $fetchOrder,
                                           $count,
                                           $offset);


            $config['items']            =& $users;
            $config['itemsType']        =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_USER;
            $config['itemBaseUrl']      =  $this->_helper->url(null, 'tags');
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
            $config['itemsType']        =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
            $config['itemBaseUrl']      =  $this->_helper->url(null, 'url');
            $config['weightName']       =  'userCount';
            $config['weightTitle']      =  'Taggers';
            $config['titleTitle']       =  'Item Url';
            $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
            $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;
            break;
        }

        $sidebar->setPane($pane, $config);
    }
}
