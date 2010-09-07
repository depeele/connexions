<?php
/** @file
 *
 *  The base Connexions Controller.
 *
 *  Provides easy access to Services as well as the Model_User instance
 *  representing the current viewer.
 */
class Connexions_Controller_Action extends Zend_Controller_Action
{
    protected   $_noSidebar = false;
    protected   $_request   = null;
    protected   $_viewer    = null;

    protected   $_format    = 'html';   // Render format
    protected   $_partials  = null;     /* If '_format' is 'partial', this MAY 
                                         * be an array of partial rendering 
                                         * information
                                         * (e.g. 'main-tags-recommended' would 
                                         *       have '_partials' of
                                         *       [ 'tags', 'recommended' ] )
                                         */
    protected   $_namespace = null;     /* The cookie namespace for HTML 
                                         * rendering.
                                         */


    protected   $_baseUrl   = null;     /* The page's base URL minus any
                                         * differentiating parameters
                                         * (e.g. tag restrictions).
                                         *
                                         * Initially, this will be the site's
                                         * root URL but should be changed by
                                         * the controller.
                                         */
    protected   $_url       = null;     /* The page's URL with
                                         * differentiating parameters but
                                         * minus any query.
                                         *
                                         * This MAY be changed by the
                                         * controller based upon validation of
                                         * restrictions (e.g. tags, users).
                                         */


    public function init()
    {
        // Initialize action controller here
        $this->_viewer  =& Zend_Registry::get('user');
        $this->_request =& $this->getRequest();
        $this->_baseUrl =  $this->_request->getBasePath();
        $this->_url     =  $this->_baseUrl
                        .  $this->_request->getPathInfo();

        if (! preg_match('#/\s*$#', $this->_url))
        {
            $this->_url .= '/';
        }

        if (! preg_match('#/\s*$#', $this->_baseUrl))
        {
            $this->_baseUrl .= '/';
        }


        // Default view variables that we can set early
        $this->view->baseUrl       = $this->_baseUrl;
        $this->view->url           = $this->_url;
        $this->view->viewer        = $this->_viewer;
        $this->view->searchContext = $this->_request->getParam('searchContext',
                                                               null);

        Connexions::log("Connexions_Controller_Action::init(): "
                        .   "baseUrl[ %s ], url[ %s ], viewer[ %s ]",
                        $this->_baseUrl,
                        $this->_url,
                        $this->_viewer);

        /*********************************************************************
         * If the concrete controller has defined contexts, initialize context
         * switching.
         *
         */
        if (isset($this->contexts))
        {
            $cs = $this->_helper->contextSwitch();
            $cs->initContext();

            $format =  $cs->getCurrentContext();
            if (empty($format))
                $format = $this->_request->getParam('format', 'html');

            $this->_format = $format;

            /*
            Connexions::log("Connexions_Controller_Action::init(): "
                            . "format[ %s ]",
                            $format);
            // */
        }
    }

    /*************************************************************************
     * Protected Helpers
     *
     */

    /** @brief  Retrieve a Connexions_Service instance.
     *  @param  name    The name of the desired service.
     *
     *  @return The Connexions_Service instance (null on failure).
     */
    protected function service($name)
    {
        if (strpos($name, 'Service_') === false)
            $name = 'Service_'. $name;

        return Connexions_Service::factory($name);
    }

    /** @brief  For an action that requires authentication and the current user
     *          is unauthenticated, this method will redirect to signIn with a
     *          flash indicating that it should return to this same action upon
     *          succssful authentication.
     *  @param  urlParams       An array or string of additional URL parameters
     *                          to be added to the end of the redirection URL;
     */
    protected function _redirectToSignIn($urlParams = null)
    {
        $flash = $this->_helper->getHelper('FlashMessenger');

        /* Note: Since the redirection back to here will be via
         *       Redirector->gotoUrl(), which pre-pends the base URL,
         *       we need to remove the base URL from the return string.
         */
        $url = str_replace($this->_request->getBaseUrl(), '',
                           $this->_request->getRequestUri());

        if (! empty($urlParams))
        {
            $params = $urlParams;
            if (is_array($urlParams))
            {
                $params = array();
                foreach ($urlParams as $key => $val)
                {
                    if (is_int($key))
                        array_push($params, $val);
                    else
                        $params[$key] = $val;
                }

                $params = implode('&', $params);
            }

            if (strpos($url, '?') === false)
                $url .= '?';

            $url .= $params;
        }

        $flash->addMessage('onSuccess:'. $url);

        /*
        Connexions::log("Connexions_Controller_Action::_redirectToSignIn() "
                        . "Redirect to signIn with a flash to return to "
                        . "url [ %s ].",
                        $url);
        // */

        return $this->_helper->Redirector('signIn','auth');
    }

    /** @brief  Determine the proper rendering format.  The only ones we deal
     *          with directly are:
     *              partial       - render a single part of this page
     *                                  (main, sidebar, sidebar-tags,
     *                                   sidebar-people, sidebar-items);
     *              html          - normal HTML rendering including 'index'
     *                              (and thus 'main') as well as the 'sidebar';
     *              json/rss/atom - alternate format rendering of JUST the main
     *                              content via 'index-%format%';
     *
     *  @param  htmlNamespace   The namespace for this rendering if HTML;
     *
     *  All others are handled by the 'contextSwitch' established in
     *  this controller's init method.
     */
    protected function _handleFormat($htmlNamespace = '')
    {
        /*
        Connexions::log("Connexions_Controller_Action::_handleFormat(%s): "
                        . "_format[ %s ]",
                        $htmlNamespace, $this->_format);
        // */

        /* All actual rendering is via one of the scripts in:
         *      application / views / scripts / %controller% /
         *
         * along with a layout from:
         *      application / layouts / [ layout.phtml ]
         */
        switch ($this->_format)
        {
        case 'partial':
            /* Render just PART of the page and MAY not require the item
             * paginator.
             *
             *  part=(content                                           |
             *        main   ([.:-](tags  ([.:-](recommended | top))? |
             *                      people([.:-](network))? )? )        |
             *        sidebar([.:-](tags | people | items))? )
             *
             * Note: The separation for 'main' is primarily to support
             *       the PostController.
             *
             * Change the layout script to:
             *      partial.phtml
             */
            $this->layout->setLayout('partial');


            /* Notify view scripts that we are rendering a partial
             * (asynchronously loaded portion of a full page).
             */
            $this->view->isPartial = true;

            $part  = $this->_request->getParam('part', 'content');
            $parts = preg_split('/\s*[\.:\-]\s*/', $part);

            $primePart       = array_shift($parts);
            $this->_partials = $parts;

            /*
            Connexions::log("Connexions_Controller_Action::_handleFormat(): "
                            . "part[ %s ] == primePart[ %s ], partials[ %s ]",
                            $part,
                            $primePart,
                            Connexions::varExport($this->_partials));
            // */


            switch ($primePart)
            {
            case 'sidebar':
                if ($this->_noSidebar !== true)
                {
                    /* Render JUST the sidebar:
                     *      sidebar.phtml
                     *
                     * OR a single pane of the sidebar:
                     *      sidebar-(implode('-', _partials)).phtml
                     */
                    $this->_renderSidebar(false);
                    break;
                }

            case 'main':
                // Render JUST the main pane.
                $this->_renderMain('main', $htmlNamespace);
                break;

            case 'content':
            default:
                /* Render JUST the main content section, that includes
                 * the main pane:
                 *      index.phtml
                 */
                break;
            }
            break;

        case 'html':
            /* Normal HTML rendering - both the main pane (via 'index') and the
             * sidebar.
             */
            $this->_renderMain('index', $htmlNamespace);
            if ($this->_noSidebar !== true)
            {
                $this->_renderSidebar();
            }
            break;

        case 'json':
        case 'rss':
        case 'atom':
        default:
            /* Render a non-HTML format, based upon $this->_format.
             *
             * RSS and Atom are consolidated to:
             *      index-feed.pthml  with 'feedType' set appropriately.
             *
             * JSON is rendered via:
             *      index-json.phtml
             */
            if ($this->_format === 'rss')
            {
                $this->view->main['feedType'] =
                                    View_Helper_FeedBookmarks::TYPE_RSS;
                $this->_format = 'feed';
            }
            else if ($this->_format === 'atom')
            {
                $this->view->main['feedType'] =
                                    View_Helper_FeedBookmarks::TYPE_ATOM;
                $this->_format = 'feed';
            }

            $this->_renderMain('index-'. $this->_format);

            break;
        }
    }

    /** @brief  Prepare for rendering the main view, regardless of format.
     *  @param  namespace   The namespace for this rendering;
     *
     *  This will collect the variables needed to render the main view, placing
     *  them in $view->main as a configuration array.
     */
    protected function _prepareMain($namespace  = '')
    {
        $request          =& $this->_request;

        if (($this->_format === 'html') || ($this->_format === 'partial'))
        {
            /* HTML and Partial will typically be requested via click on a
             * pre-defined URL.
             */
            $displayStyle = $request->getParam($namespace ."OptionGroup");
            $dsCustom     = $request->getParam($namespace
                                                    ."OptionGroups_option");

            $perPage      = $request->getParam($namespace ."PerPage");
            $page         = $request->getParam($namespace ."Page");
            $sortBy       = $request->getParam($namespace ."SortBy");
            $sortOrder    = $request->getParam($namespace ."SortOrder");

            if ( ($displayStyle === 'custom') && (is_array($dsCustom)) )
                $displayStyle = $dsCustom;
        }
        else
        {
            /* All the rest are more subject to variability since they are
             * likely added by a user.
             */
            $displayStyle = null;
            $perPage      = $request->getParam("perPage");
            $page         = $request->getParam("page");
            $sortBy       = $request->getParam("sortBy");
            $sortOrder    = $request->getParam("sortOrder");

            // Alternative names
            if (empty($perPage))    $perPage   = $request->getParam("perpage");
            if (empty($sortBy))     $sortBy    = $request->getParam("sortby");
            if (empty($sortOrder))  $sortOrder =
                                            $request->getParam("sortorder");

            if (empty($perPage))    $perPage   = $request->getParam("limit");
            if (empty($page))       $page      = $request->getParam("offset");
        }

        // Additional view variables for the HTML view.
        $this->view->main = array(
            'pageBaseUrl'   => $this->_baseUrl,
            'namespace'     => $namespace,
            'viewer'        => &$this->_viewer,

            'perPage'       => $perPage,
            'page'          => $page,
            'sortBy'        => $sortBy,
            'sortOrder'     => $sortOrder,

            'displayStyle'  => $displayStyle,
        );
    }

    /** @brief  Prepare for rendering the sidebar view.
     *  @param  async   Should we setup to do an asynchronous render
     *                  (i.e. tab callbacks will request tab pane contents when 
     *                        needed)?
     *
     *  This will collect the variables needed to render the sidebar view,
     *  placing them in $view->sidebar as a configuration array.
     */
    protected function _prepareSidebar($async   = false)
    {
        $request =& $this->_request;

        $sidebar = array(
            'namespace' => 'sidebar-tab',
            'async'     => $async,
            'viewer'    => &$this->_viewer,
            'users'     => null,
            'tags'      => &$this->_tags,

            /* 'items'  SHOULD be set by the main view script to a
             *          Connexions_Model_Set instance representing the set of
             *          items presented in the main view.
             */

            // Sidebar tab pane definitions
            'panes'     => array(
                'tags'    => array(
                    /*****************************************************
                     * For sidebar-tags.phtml
                     *
                     * which then invokes 'View_Helper_HtmlItemCloud' to
                     * render the tag cloud or list.
                     *
                     */
                    'pageBaseUrl'   => $this->_baseUrl,
                    'namespace'     => 'sbTags',
                    'title'         => 'Tags',
                    'weightName'    => 'userItemCount',

                    // 'related' will be set by the main view renderer
                    // 'selected'      => $this->_tags,
                    'itemType'      =>
                                View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM,
                    'itemBaseUrl'   => $this->_url,

                    'sortBy'        => $request->getParam("sbTagsSortBy"),
                    'sortOrder'     => $request->getParam("sbTagsSortOrder"),

                    'page'          => $request->getParam("sbTagsPage"),
                    'perPage'       => $request->getParam("sbTagsPerPage"),
                    'highlightCount'=> $request->getParam(
                                                    "sbTagsHighlightCount"),

                    'displayStyle'  => $request->getParam("sbTagsOptionGroup"),
                ),

                'people'  => array(
                    /*****************************************************
                     * For sidebar-people.phtml
                     *
                     * which then invokes 'View_Helper_HtmlItemCloud' to
                     * render the people cloud or list.
                     *
                     */
                    'pageBaseUrl'   => $this->_baseUrl,
                    'namespace'     => 'sbPeople',
                    'title'         => 'People',
                    'weightName'    => 'userItemCount',

                    // 'related' will be set by the main view renderer
                    // 'selected'      => $this->_owner,
                    'itemType'      =>
                                View_Helper_HtmlItemCloud::ITEM_TYPE_USER,
                    'itemBaseUrl'   => Connexions::url('/'),    // $this->_url,

                    'sortBy'        => $request->getParam("sbPeopleSortBy"),
                    'sortOrder'     => $request->getParam("sbPeopleSortOrder"),

                    'page'          => $request->getParam("sbPeoplePage"),
                    'perPage'       => $request->getParam("sbPeoplePerPage"),
                    'highlightCount'=> $request->getParam(
                                                    "sbPeopleHighlightCount"),

                    'displayStyle'  => $request->getParam(
                                                    "sbPeopleOptionGroup"),
                ),

                'items'   => array(
                    /*****************************************************
                     * For sidebar-items.phtml
                     *
                     * which then invokes 'View_Helper_HtmlItemCloud' to
                     * render the item cloud or list.
                     *
                     */
                    'pageBaseUrl'   => $this->_baseUrl,
                    'namespace'     => 'sbItems',
                    'title'         => 'Items',

                    // 'related' will be set by the main view renderer
                    // 'selected'      => $this->_owner,
                    'itemBaseUrl'   => $this->_url,

                    'page'          => $request->getParam("sbItemsPage"),
                    'perPage'       => $request->getParam("sbItemsPerPage"),
                    'highlightCount'=> $request->getParam(
                                                    "sbItemsHighlightCount"),

                    /************************************
                     * Adjust the default for this pane
                     *      list style,
                     *      sorted in descending order
                     *      by weight
                     *
                     */
                    'sortBy'        =>
                        $request->getParam("sbItemsSortBy",
                                    View_Helper_HtmlItemCloud::SORT_BY_WEIGHT),

                    'sortOrder'     =>
                        $request->getParam("sbItemsSortOrder",
                                    Connexions_Service::SORT_DIR_DESC),

                    'displayStyle'  =>
                        $request->getParam("sbItemsOptionGroup",
                                    View_Helper_HtmlItemCloud::STYLE_LIST),
                ),
            ),
        );

        $this->view->sidebar = $sidebar;
    }

    /** @brief  Prepare and render the main view using the provided view script.
     *  @param  script      The view script to use for rendering;
     *  @param  namespace   The namespace for this rendering;
     *
     */
    protected function _renderMain($script, $namespace = '')
    {
        $this->_namespace = $namespace;

        $this->_prepareMain($namespace);

        if ( count($this->_partials) > 0)
        {
            $script .= '-' . implode('-', $this->_partials);
        }

        $this->render($script);

    }

    /** @brief  Render the sidebar based upon the incoming request.
     *  @param  usePlaceholder      Should the rendering be performed
     *                              immediately into a placeholder?
     *                              [ true, into the 'right' placeholder ]
     *
     */
    protected function _renderSidebar($usePlaceholder = true)
    {
        if ($this->_noSidebar === true)
        {
            return;
        }

        /*
        Connexions::log("Connexions_Controller_Action::_renderSidebar(): "
                        . "usePlaceholder[ %s ]",
                        Connexions::varExport($usePlaceholder));
        // */

        $this->_prepareSidebar( $usePlaceholder );

        if ($this->_partials !== null)
        {
            // Render just the requested part
            $script = 'sidebar-'. implode('-', $this->_partials);

            /*
            Connexions::log("Connexions_Controller_Action::_renderSidebar(): "
                            . "script [ %s ]",
                            $script);
            // */


            $this->render($script);
        }
        else
        {
            // Render the entire sidebar
            if ($usePlaceholder === true)
            {
                $controller = $this->_request->getParam('controller');

                /*
                Connexions::log("Connexions_Controller_Action::"
                                . "_renderSidebar(): "
                                . "render sidebar for controller [ %s ]",
                                $controller);
                // */

                // Render the sidebar into the 'right' placeholder
                $script = $controller .'/sidebar.phtml';
                $this->view->renderToPlaceholder($script, 'right');
            }
            else
            {
                $script = 'sidebar';
                $this->render($script);
            }
        }
    }
}
