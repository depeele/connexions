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
    protected   $_request   = null;
    protected   $_viewer    = null;
    protected   $_format    = 'html';

    public function init()
    {
        /* Initialize action controller here */
        $this->_viewer  =& Zend_Registry::get('user');
        $this->_request =& $this->getRequest();

        if (isset($this->contexts))
        {
            // Initialize context switching (via $this->contexts)
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

        // /*
        Connexions::log("Connexions_Controller_Action::_redirectToSignIn() "
                        . "Redirect to signIn with a flash to return to "
                        . "url [ %s ].",
                        $url);
        // */

        return $this->_helper->Redirector('signIn','auth');
    }

    /** @brief  Determine the proper rendering format.  The only ones we deal
     *          with directly are:
     *              partial - render a single part of this page
     *              html    - normal HTML rendering
     *
     *  All others are handled by the 'contextSwitch' established in
     *  this controller's init method.
     */
    protected function _handleFormat()
    {
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
             *  part=(content | sidebar([.:-](tags | people))? )
             *
             * Change the layout script to:
             *      partial.phtml
             */
            $this->layout->setLayout('partial');

            /* Notify view scripts that we are rendering a partial
             * (asynchronously loaded portion of a full page).
             */
            $this->view->isPartial = true;

            $parts = preg_split('/\s*[\.:\-]\s*/',
                                $this->_request->getParam('part', 'content'));
            switch ($parts[0])
            {
            case 'sidebar':
                /* Render JUST the sidebar:
                 *      sidebar.phtml
                 *
                 * OR a single pane of the sidebar:
                 *      sidebar-%part%.phtml
                 */
                $this->_htmlSidebar(false, (count($parts) > 1
                                                ? $parts[1]
                                                : null));
                break;

            case 'main':
                /* Render JUST the main pane:
                 *      main.phtml
                 */
                $this->render('main');
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
            /* Normal HTML rendering, including the sidebar:
             *      index.phtml
             *      sidebar.phtml
             */
            $this->render('index');
            $this->_htmlSidebar();
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

            $this->render('index-'. $this->_format);

            break;
        }
    }

    /** @brief  Prepare for rendering the main view, regardless of format.
     *
     *  This will collect the variables needed to render the main view, placing
     *  them in $view->main as a configuration array.
     */
    protected function _prepareMain($htmlNamespace  = '')
    {
        $request          =& $this->_request;

        if (($this->_format === 'html') || ($this->_format === 'partial'))
        {
            /* HTML and Partial will typically be requested via click on a
             * pre-defined URL.
             */
            $namespace    = $htmlNamespace;
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
            $namespace    = '';
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
     *
     *  Note: The main index view script MAY also add sidebar-related rendering
     *        information to the sidbar helper.  In particular, it will notify
     *        the sidbar helper of the items that are being presented in the
     *        main view.
     */
    protected function _prepareSidebar($async = false)
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
                    'namespace'     => 'sbItems',
                    'title'         => 'Items',

                    // 'related' will be set by the main view renderer
                    // 'selected'      => $this->_owner,
                    'itemBaseUrl'   => $this->_url,

                    'sortBy'        => $request->getParam("sbItemsSortBy"),
                    'sortOrder'     => $request->getParam("sbItemsSortOrder"),

                    'page'          => $request->getParam("sbItemsPage"),
                    'perPage'       => $request->getParam("sbItemsPerPage"),
                    'highlightCount'=> $request->getParam(
                                                    "sbItemsHighlightCount"),

                    'displayStyle'  => $request->getParam(
                                                    "sbItemsOptionGroup"),
                ),
            ),
        );

        $this->view->sidebar = $sidebar;
    }

    /** @brief  Generate HTML for the sidebar based upon the incoming request.
     *  @param  usePlaceholder      Should the rendering be performed
     *                              immediately into a placeholder?
     *                              [ true, into the 'right' placeholder ]
     *  @param  part                The portion of the sidebar to render
     *                                  (tags | people | items)
     *                              [ null == all ]
     *
     */
    protected function _htmlSidebar($usePlaceholder = true,
                                    $part           = null)
    {
        $this->_prepareSidebar( $usePlaceholder );

        if ($part !== null)
        {
            // Render just the requested part
            $this->render('sidebar-'. $part);
        }
        else
        {
            // Render the entire sidebar
            if ($usePlaceholder === true)
            {
                $controller = $this->_request->getParam('controller');

                /*
                Connexions::log("Connexions_Controller_Action::_htmlSidebar(): "
                                . "render sidebar for controller [ %s ]",
                                $controller);
                // */

                // Render the sidebar into the 'right' placeholder
                $this->view->renderToPlaceholder($controller .'/sidebar.phtml',
                                                 'right');
            }
            else
            {
                $this->render('sidebar');
            }
        }
    }
}
