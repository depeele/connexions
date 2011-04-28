<?php
/** @file
 *
 *  This controller controls access to Users / People and is accessed via the
 *  url/routes:
 *      /people[/:tags]
 */

class PeopleController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public  $dependencies       = array('db','layout');

    protected   $_url           = null;
    protected   $_tags          = null;

    public      $contexts       = array(
                                    'index' => array('partial', 'json',
                                                     'rss',     'atom'),
                                );

    public function init()
    {
        parent::init();

        $this->_baseUrl .= 'people/';
    }

    /** @brief  Index/Get/Read/View action.
     *
     *  Retrieve a set of Users based upon the requested 'tags'.
     *
     *  Once retrieved, perform further setup based upon the current
     *  context/format.
     */
    public function indexAction()
    {
        $request  =& $this->_request;

        /*
        Connexions::log('PeopleController::indexAction(): '
                        .   'params[ %s ]',
                        print_r($request->getParams(), true));
        // */

        /***************************************************************
         * Process the requested 'owner' and 'tags'
         *
         */
        $reqTags   = $request->getParam('tags',      null);

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
        $this->_url         = $this->_baseUrl
                            . (count($this->_tags) > 0
                                    ? $this->_tags .'/'
                                    : '');

        /***************************************************************
         * Set the view variables required for all views/layouts.
         *
         */
        $this->view->headTitle('People');

        $this->view->url       = $this->_url;

        $this->view->tags      = $this->_tags;


        // HTML form/cookie namespace
        $this->_namespace = 'people';
    }

    /*************************************************************************
     * Context-specific view initialization and invocation
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
            'tags'          => &$this->_tags,

            /* Pass down that 'deleted' events should NOT cause the item to be
             * removed from the list:
             *  views/scripts/people/main.phtml
             *      -> views/helpers/HtmlUsers.php
             *          -> JavaScript:connexions.itemsPane
             *              -> JavaScript:connexions.itemList
             */
            'ignoreDeleted' => true,
        );
        $this->view->main = array_merge($this->view->main, $extra);

        /*
        Connexions::log("PeopleController::_prepare_main(): "
                        .   "main[ %s ]",
                        Connexions::varExport($this->view->main));
        // */
    }

    /** @brief  Prepare for rendering the sidebar view.
     *
     *  This will collect the variables needed to render the sidebar view,
     *  placing them in $view->sidebar as a configuration array.
     */
    protected function _prepare_sidebar($async   = false)
    {
        $async   = ($this->_format === 'partial'
                        ? false
                        : true);

        /*
        Connexions::log("PeopleController::_prepare_sidebar( %s )",
                        ($async ? "async" : "sync"));
        // */

        parent::_prepare_sidebar($async);


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
                $this->_prepare_sidebarPane('items', $sidebar);
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
     *  @param  pane    The pane of the sidebar to render
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
            $fetchOrder = array('userCount     DESC',
                                'itemCount     DESC',
                                'tag           ASC');

            if (count($this->_tags) < 1)
            {
                /* There were no requested tags that limit the user 
                 * retrieval so, for the sidebar, retrieve ALL tags.
                 */

                // /*
                Connexions::log("PeopleController::"
                                .   "_prepare_sidebarPane( %s ): "
                                .   "Fetch all tags %d-%d",
                                $pane,
                                $offset, $offset + $count);
                // */

                $tags = $service->fetchByUsers(null,        // ALL users
                                               $fetchOrder,
                                               $count,
                                               $offset);
            }
            else
            {
                // Tags related to the users with the given set of tags.

                // /*
                Connexions::log("PeopleController::"
                                .   "_prepare_sidebarPane( %s ): "
                                .   "Fetch tags %d-%d related to users "
                                .   "with tags[ %s ]",
                                $pane,
                                $offset, $offset + $count,
                                Connexions::varExport($this->_tags));
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

                $users = (isset($this->view->main['items'])
                            ? $this->view->main['items']
                            : null);
                if ($users === null)
                {
                    /* The set of users presented in the main view has not 
                     * been communicated to the sidebar helper.  We need to 
                     * generate them now using the non-format related 
                     * View_Helper_Users to generate the appropriate set of 
                     * users, telling the helper to return ALL users by 
                     * setting 'perPage' to -1.
                     */
                    $overRides = array_merge($this->view->main,
                                             array('perPage' => -1));

                    $helper = $this->view->users( $overRides );
                    $users  = $helper->users;
                }

                /* Retrieve the set of tags that are related to the presented 
                 * users.
                 */
                $tags = $service->fetchByUsers($users,
                                               $fetchOrder,
                                               $count,
                                               $offset);

                $config['selected'] =& $this->_tags;
            }

            $config['items']            =& $tags;
            $config['itemType']         =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
            $config['weightName']       =  'userCount';
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
         *  Retrieve contributor and timeline information
         *
         */
        case 'people':
            // Retrieve contributor information
            $uSvc      = $this->service('User');
            $params    = array(
                'tags'      => $this->_tags,
            );
            $config['counts'] = $uSvc->getContributorCount( $params );

            // Construct the timeline
            $bSvc     = $this->service('Bookmark');
            $params   = array(
                'tags'      => $this->_tags,
                'grouping'  => 'YM',
            );
            $timeline = $bSvc->getTimeline( $params );

            $months = 0;
            $last   = null;
            foreach ($timeline as $date => $count)
            {
                $month = substr($date, 0, 6);

                if ($month !== $last)   $months++;
                $last = $month;
            }

            // Reduce to a number of ticks that will hopefully be uncluttered
            $ticks = $months;
            while ($ticks >= 24)
            {
                $ticks = ($ticks / 6) + 1;
            }

            $config['timeline'] = array(
                'rpcMethod'     => 'bookmark.getTimeline',
                'rpcParams'     => $params,
                'xDataHint'     => 'fmt:%Y %b',
                'replaceLegend' => true,
                'height'        => '200px',
                'flot'          => array(
                    'grid'      => array(
                        'borderWidth'   => 0.75,
                    ),
                    'points'    => array(
                        'radius'        => 1.5,
                        'lineWidth'     => 0.75,
                    ),
                    'lines'     => array(
                        'lineWidth'     => 1,
                    ),
                    'xaxis'     => array(
                        'labelAngle'    => 75,
                        'ticks'         => $ticks,
                    ),
                ),
                'rawData'       => array(
                    'activity'  => $timeline,
                ),
            );

            /*
            Connexions::log("PeopleController::"
                            .   "_prepare_sidebarPane( %s ): "
                            .   "config[ %s ]",
                            $pane,
                            Connexions::varExport($config));
            // */
            break;

        /*************************************************************
         * Sidebar::Items
         *
         */
        case 'items':
            $service    = $this->service('Item');
            $fetchOrder = array('userCount DESC',
                                'ratingSum DESC',
                                'url       ASC');

            if (count($this->_tags) < 1)
            {
                /* There were no requested tags that limit the user 
                 * retrieval so, for the sidebar, retrieve ALL items.
                 */
                $items = $service->fetch(null,   // all
                                         $fetchOrder,
                                         $count,
                                         $offset);
                /*
                $items = $service->fetchByUsers(null,   // all
                                                $fetchOrder,
                                                $count,
                                                $offset);
                // */
            }
            else
            {
                // Items related to the users with the given set of tags.

                /* In order to prepare the sidebar, we need to know the set
                 * of users presented in the main view.  If we're rendering 
                 * the main view and sidebar syncrhonously, this MAY have been 
                 * set in $this->view->main['items'] via
                 *      application/view/scripts/people/main.phtml.
                 */
                if (! isset($this->view->main))
                {
                    $this->_prepare_main();
                }

                $users = (isset($this->view->main['items'])
                            ? $this->view->main['items']
                            : null);
                if ($users === null)
                {
                    /* The set of users presented in the main view has not 
                     * been communicated to the sidebar helper.  We need to 
                     * generate them now using the non-format related 
                     * View_Helper_Users to generate the appropriate set of 
                     * users, telling the helper to return ALL users by 
                     * setting 'perPage' to -1.
                     */
                    $overRides = array_merge($this->view->main,
                                             array('perPage' => -1));

                    $helper = $this->view->users( $overRides );
                    $users  = $helper->getUsers();  //users;

                    /*
                    Connexions::log("IndexController::"
                                    .   "_prepare_sidebarPane( %s ): "
                                    .   "items related to %d users [ %s ] and "
                                    .   "tags[ %s ], overRides[ %s ]",
                                    $pane,
                                    count($users),
                                    Connexions::varExport($users),
                                    Connexions::varExport($this->_tags),
                                    Connexions::varExport($overRides));
                    // */
                }

                /* Retrieve the set of items that are related to the presented 
                 * users.
                 */
                $items = $service->fetchByUsersAndTags($users,
                                                       $this->_tags,
                                                       false,   // exact Users
                                                       true,    // exact Tags
                                                       $fetchOrder,
                                                       $count,
                                                       $offset);
            }

            // /*
            Connexions::log("PeopleController::"
                            .   "_prepare_sidebarPane( %s ): "
                            .   "Fetched %d items",
                            $pane,
                            count($items));
            // */


            $config['items']            =& $items;
            $config['itemType']         =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
            $config['itemBaseUrl']      =  $this->_helper->url(null, 'url');
                                            //$this->view->baseUrl('/url/');
            $config['weightName']       =  'userCount';
            $config['weightTitle']      =  'Bookmarking Users';
            $config['titleTitle']       =  'Item Url';
            $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
            $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;
            break;
        }
    }
}
