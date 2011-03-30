<?php
/** @file
 *
 *  This controller controls access to Url and is accessed via the url/routes:
 *      /url[/<md5 hash of url>]
 */

class UrlController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public  $dependencies = array('db','layout');

    protected   $_url       = null;
    protected   $_tags      = null;

    protected   $_item      = null;

    protected   $_urlHash   = null;

    public      $contexts   = array(
                                'index' => array('partial', 'json',
                                                 'rss',     'atom'),
                              );

    public function init()
    {
        parent::init();

        $this->_baseUrl .= 'url/';
    }

    /** @brief  Index/Get/Read/View action.
     *
     *  Retrieve a set of Bookmarks for the given url.
     */
    public function indexAction()
    {
        $request =& $this->_request;
        $url     =  $request->getParam('url',  null);

        if (empty($url))
            return $this->_forward('choose');

        /* If the incoming URL is NOT an MD5 hash (32 hex characters), convert
         * it to a normalzed hash now
         */
        $this->_urlHash = Connexions::md5Url($url);
        if ($this->_urlHash !== $url)
        {
            // Redirect using the URL hash
            return $this->_helper->redirector
                                    ->setGotoRoute(array('url',
                                                         $this->_urlHash));
        }

        /***************************************************************
         * Process the requested 'tags'
         *
         */
        $reqTags     = $request->getParam('tags', null);
        $this->_tags = $this->service('Tag')->csList2set($reqTags);

        /***************************************************************
         * We now have a valid 'owner' ($this->_owner) and
         * 'tags' ($this->_tags)
         *
         * Adjust the URL to reflect the validated 'owner' and 'tags'
         */
        $this->_url = $this->_baseUrl
                    . $this->_urlHash .'/'
                    . (count($this->_tags) > 0
                            ? $this->_tags .'/'
                            : '');

        // Locate the item with the requested URL (if there is one).
        $this->_item = $this->service('Item')->find($this->_urlHash);

        /*
        Connexions::log("UrlController:: item[ %s ]",
                        ($this->_item ? $this->_item->debugDump()
                                      : 'null'));
        // */
        if ( (! $this->_item) || (! $this->_item->isValid()) )
        {
            // This URL has not been bookmarked here.
            $this->view->url   = $url;
            $this->view->error = "There are no bookarks for the provided URL.";

            return $this->_forward('choose');
        }

        /***************************************************************
         * Set the view variables required for all views/layouts.
         *
         */
        $this->view->headTitle('Url');
        /*
        if ($this->_item  !== null)
            $this->view->headTitle($this->_item->urlHash);
        // */

        $this->view->url       = $this->_url;
        $this->view->tags      = $this->_tags;
        $this->view->item      = $this->_item;

        // HTML form/cookie namespace
        $this->_namespace = 'bookmarks';
    }

    public function chooseAction()
    {
        // Nothing much to do -- let the view script render...
        Connexions::log('UrlController::chooseAction');
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
            $url = substr($method, 0, -6);

            return $this->_forward('index', 'url', null,
                                   array('url' => $url));
        }

        throw new Exception('Invalid method "'. $method .'" called', 500);
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
            'items' => &$this->_item,
            'tags'  => &$this->_tags,
        );
        $this->view->main = array_merge($this->view->main, $extra);

        /*
        Connexions::log("UrlController::_prepare_main(): "
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
     *        (application/views/scripts/url/index.phtml) will also add
     *        sidebar-related rendering information to the sidbar helper.  In
     *        particular, it will notify the sidbar helper of the items that
     *        are being presented in the main view.
     */
    protected function _prepare_sidebar()
    {
        // Our tags sidebar MAY need main-view variables set...
        if (! isset($this->view->main))
        {
            $this->_prepare_main();
        }

        parent::_prepare_sidebar();

        $async   = ($this->_format === 'partial'
                        ? false
                        : true);

        $extra = array(
            'tags'  => &$this->_tags,
        );
        $this->view->sidebar = array_merge($this->view->sidebar, $extra);

        /**************************************
         * Adjust the default style for the
         * people pane to be "list", sorted
         * by "weight" ordered "descending".
         *
         */
        $paramNs = $this->view->sidebar['panes']['people']['namespace'];

        $this->view->sidebar['panes']['people']['displayStyle'] =
                        $this->_request->getParam($paramNs .'OptionGroup',
                                    View_Helper_HtmlItemCloud::STYLE_LIST);
        $this->view->sidebar['panes']['people']['sortBy'] =
                        $this->_request->getParam($paramNs .'SortBy',
                                    View_Helper_HtmlItemCloud::SORT_BY_WEIGHT);
        $this->view->sidebar['panes']['people']['sortOrder'] =
                        $this->_request->getParam($paramNs .'SortOrder',
                                    Connexions_Service::SORT_DIR_DESC);


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
                $this->_prepare_sidebarPane('items');
            }
        }

        /*
        Connexions::log("UrlController::_prepare_sidebar(): "
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
        $config  =& $this->view->sidebar['panes'][$pane];

        $perPage = ((int)$config['perPage'] > 0
                        ? (int)$config['perPage']
                        : 100);
        $page    = ((int)$config['page'] > 0
                        ? (int)$config['page']
                        : 1);

        $count   = $perPage;
        $offset  = ($page - 1) * $perPage;

        // Related to:
        $to = array('items' => $this->_item);
        if (count($this->_tags) > 0)
        {
            $to['tags']      = $this->_tags;
            //$to['exactTags'] = true;
        }


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
                /* There were no requested tags that limit bookmark
                 * retrieval so, for the sidebar, retrieve ALL tags.
                 */
                $to = array('items' => $this->_item);

                $tags = $service->fetchRelated($to,
                                               $fetchOrder,
                                               $count,
                                               $offset);
            }
            else
            {
                // Tags related to the bookmarks with the given set of tags.

                // /*
                Connexions::log("UrlController::_prepare_sidebarPane( %s ): "
                                .   "Fetch tags %d-%d related to users "
                                .   "with tags[ %s ]",
                                $pane,
                                $offset, $offset + $count,
                                Connexions::varExport($this->_tags));
                // */

                /* In order to prepare the sidebar, we need to know the set
                 * of bookmarks presented in the main view.  If we're rendering
                 * the main view and sidebar syncrhonously, this MAY have been 
                 * set in $this->view->main['items'] via
                 *      application/view/scripts/people/main.phtml.
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
                    /* The set of users presented in the main view has not 
                     * been communicated to the sidebar helper.  We need to 
                     * generate them now using the non-format related 
                     * View_Helper_Users to generate the appropriate set of 
                     * users, telling the helper to return ALL users by 
                     * setting 'perPage' to -1.
                     */
                    $overRides = array_merge($this->view->main,
                                             array('perPage' => -1));

                    $helper    = $this->view->bookmarks( $overRides );
                    $bookmarks = $helper->bookmarks;
                }

                /* Retrieve the set of tags that are related to the presented 
                 * users.
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
            $config['weightTitle']      =  'Item bookmarks';
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

            $users = $service->fetchRelated($to,
                                            $fetchOrder,
                                            $count,
                                            $offset);

            // /*
            Connexions::log("UrlController::_prepare_sidebarPane( %s ): "
                            .   "Fetched %d users",
                            $pane,
                            count($users));
            // */


            $config['items']            =& $users;
            $config['itemsType']        =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_USER;
            $config['weightName']       =  'totalItems';
            $config['weightTitle']      =  'Total Bookmarks';
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
            $fetchOrder = array('userItemCount DESC',
                                'ratingCount   DESC',
                                'url           ASC');

            // Retrieve items similar to the item being presented.
            $items = $service->fetchSimilar($this->_item,
                                            $fetchOrder,
                                            $count,
                                            $offset);

            // /*
            Connexions::log("UrlController::_prepare_sidebarPane( %s ): "
                            .   "Fetched %d items",
                            $pane,
                            count($items));
            // */


            $config['items']            =& $items;
            $config['itemsType']        =
                                 View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
            $config['itemBaseUrl']      =  $this->_helper->url(null, 'url');
            $config['weightName']       =  'userItemCount';
            $config['weightTitle']      =  'Bookmarks';
            $config['titleTitle']       =  'Item Url';
            $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
            $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;
            break;
        }
    }
}
