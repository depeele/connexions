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

    protected   $_noSidebar = false;    /* Should the sidebar be ignored? */
    protected   $_noFormatHandling
                            = false;    /* Should format handling in render()
                                         * be ignored?
                                         */

    protected   $_format    = 'html';   // Render format
    protected   $_partials  = null;     /* If '_format' is 'partial', this MAY 
                                         * be an array of partial rendering 
                                         * information
                                         * (e.g. 'main-tags-recommended' would 
                                         *       have '_partials' of
                                         *       [ 'tags', 'recommended' ] )
                                         */
    protected   $_namespace = '';       /* The cookie namespace for HTML 
                                         * rendering.
                                         */


    protected   $_rootUrl   = null;     // The root connexions URL.
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
    protected   $_streaming = false;    /* Did the current request include
                                         * 'streaming=true'?
                                         *
                                         * If so, Bootstrap will have disabled
                                         * output buffering and layouts.
                                         */
    public function init()
    {
        // Initialize common members
        $this->_viewer  =  Zend_Registry::get('user');
        $this->_request =  $this->getRequest();
        $this->_rootUrl =  $this->_request->getBasePath();
        $this->_baseUrl =  $this->_rootUrl;
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

        /*
        Connexions::log("Connexions_Controller_Action::init(): "
                        .   "request params[ %s ]",
                        Connexions::varExport($this->_request->getParams()));
        // */

        $this->_streaming = Connexions::to_bool(
                                $this->_request->getParam('streaming', false));


        // Default view variables that can be set early
        $this->view->controller    = $this->_request->getParam('controller');
        $this->view->action        = $this->_request->getParam('action');
        $this->view->rootUrl       = $this->_rootUrl;
        $this->view->baseUrl       = $this->_baseUrl;
        $this->view->url           = $this->_url;
        $this->view->viewer        = $this->_viewer;
        $this->view->searchContext = $this->_request->getParam('searchContext',
                                                               null);

        /*
        Connexions::log("Connexions_Controller_Action::init(): "
                        .   "baseUrl[ %s ], url[ %s ], viewer[ %s ]",
                        $this->_baseUrl,
                        $this->_url,
                        $this->_viewer);
        // */

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

            /*
            Connexions::log("Connexions_Controller_Action::init(): "
                            . "ContextSwitch format[ %s ]",
                            $format);
            // */

            if (empty($format))
            {
                $format = $this->_request->getParam('format', 'html');

                /*
                Connexions::log("Connexions_Controller_Action::init(): "
                                . "request format[ %s ]",
                                $format);
                // */

            }

            $this->_format = $format;

            /*
            Connexions::log("Connexions_Controller_Action::init(): "
                            . "format[ %s ]",
                            $format);
            // */
        }
    }

    /** @brief  Override Zend_Controller_Action::render so we can invoke
     *          _handleFormat() consistently across controllers.
     *  @param  action          The desired action
     *                          [ null == the action registered in
     *                                    the request object ];
     *  @param  name            Response object named path segment to use
     *                          [ null ];
     *  @param  noController    Use controller name as subdirectory [ false ];
     *
     *  @return void
     */
    public function postDispatch()
    {
        /*
        Connexions::log("Connexions_Controller_Action(%s)::postDispatch(): "
                        .   "url[ %s ], action[ %s ], "
                        .   "is %sdispatched, is %sredirect",
                        get_class($this),
                        $this->_request->getRequestUri(),
                        $this->_request->getActionName(),
                        ($this->_request->isDispatched()    ? '' : 'NOT '),
                        ($this->getResponse()->isRedirect() ? '' : 'NOT '));
        // */

        /* Only perform format handling if:
         *  - '_noFormatHandling' is NOT explicitly set to true;
         *  - the request is marked as "dispatched"
         *    (i.e. the controller/action is NOT forwarding to another
         *          controller/action);
         *  - the response is NOT marked as "redirect"
         *    (i.e. the controller/action is redirecting to another
         *          controller/action);
         */
        if ( (  $this->_noFormatHandling !== true) &&
             (  $this->_request->isDispatched())   &&
             (! $this->getResponse()->isRedirect()) )
        {
            $this->_handleFormat();
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

    /** @brief  Given a string that is supposed to represent a user, see if it
     *          represents a valid user.
     *  @param  name    The user name.
     *
     *  @return A Model_User instance matching 'name', null if no match.
     */
    protected function _resolveUserName($name)
    {
        $res = null;

        if ((! @empty($name)) && ($name !== '*'))
        {
            // Does the name match an existing user?
            if ($name === $this->_viewer->name)
            {
                // 'name' matches the current viewer...
                $ownerInst =& $this->_viewer;
            }
            else
            {
                //$ownerInst = Model_User::find(array('name' => $name));
                $ownerInst = $this->service('User')
                                    ->find(array('name' => $name));
            }

            // Have we located a valid, backed user?
            if ($ownerInst !== null)
            {
                // YES -- we've located an existing user.

                $res = $ownerInst;
            }
        }

        return $res;
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
     *
     *  All others are handled by the 'contextSwitch' established in
     *  this controller's init method.
     */
    protected function _handleFormat()
    {
        /*
        Connexions::log("Connexions_Controller_Action(%s)::_handleFormat(): "
                        . "_format[ %s ], _namespace[ %s ], "
                        . "url[ %s ], action[ %s ]",
                        get_class($this),
                        $this->_format, $this->_namespace,
                        $this->_request->getRequestUri(),
                        $this->_request->getActionName());
        // */


        /* By default, render the view script associated with the current
         * action
         */
        $this->_partials = array( $this->_request->getActionName() );


        /* All actual rendering is via one of the scripts in:
         *      application / views / scripts / %controller% /
         *
         * along with a layout from:
         *      application / layouts / [ layout.phtml ]
         */
        switch ($this->_format)
        {
        case 'partial':
            /* Partial page rendering defined by 'part':
             *      format=partial&part=%part%
             *
             * The desired %part% is a delimiter separated (.:-) string
             * that identifies the specific part of a page that is to be
             * rendered.
             *
             * The part typically MUST have an associated view script and MAY
             * have methods in the concrete class for view preparation.
             *
             * Example parts and associated view scripts and preparation
             * methods (all view scripts have a starting path of
             *          'application/view/scripts'):
             *      main
             *        view script:  %controller%/main.phtml
             *        prep methods: %Controller%::_prepare_main()
             *
             *      sidebar-items
             *        view script:  %controller%/sidebar-items.phtml
             *        prep methods: %Controller%::_prepare_sidebar()
             *                      %Controller%::_prepare_sidebar_items()
             *
             *      main-tags-manage
             *        view script:  %controller%/main-tags-manage.phtml
             *        prep methods: %Controller%::_prepare_main()
             *                      %Controller%::_prepare_main_tags()
             *                      %Controller%::_prepare_main_tags_manage()
             *
             *      post-account-avatar
             *        view script:  %controller%/post-account-avatar.phtml
             *        prep methods: %Controller%::_prepare_post()
             *                      %Controller%::_prepare_post_account()
             *                      %Controller%::_prepare_post_account_avatar()
             *
             *
             * Change the layout script to:
             *      partial.phtml
             */
            $this->layout->setLayout('partial');


            /* Notify view scripts that we are rendering a partial
             * (asynchronously loaded portion of a full page).
             */
            $this->view->isPartial = true;

            $part   = $this->_request->getParam('part', 'content');
            $parts  = preg_split('/\s*[\.:\-]\s*/', $part);

            if ($parts[0] !== 'content')
            {
                $this->_partials = $parts;
            }
            $this->_renderPartial();
            break;

        case 'json':
        case 'rss':
        case 'atom':
            /* Render a (possibly) non-HTML format, based upon $this->_format.
             *
             * RSS and Atom are consolidated to:
             *      %action%-feed.pthml  with 'feedType' set appropriately.
             *
             * JSON is rendered via:
             *      %action%-json.phtml
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

            $this->_partials = array( $this->_request->getActionName(),
                                      $this->_format);

            $this->_renderPartial();
            break;

        case 'html':
        default:
            /* Normal HTML rendering - both the main pane and the sidebar.
             */
            $this->_renderPartial();

            if ($this->_noSidebar !== true)
            {
                /* Directly include preparation and rendering of the sidebar to
                 * a placeholder
                 */
                $this->_prepare_sidebar();
                $this->_renderSidebar();
            }
            break;
        }
    }

    /** @brief  Prepare for rendering a partial view, regardless of format.
     *
     *  This will attempt to invoke '_prepare_*' methods in the concrete class
     *  based upon $this->_partials.  If any one returns false, terminate.
     *
     *  @return true (prepared) or false (NOT prepared / cancel).
     */
    protected function _preparePartial()
    {
        /* See if the concrete class has a method to prepare this partial
         *  Check '_prepare_%part0%'
         *        '_prepare_%part0%_%part1%'
         *        ...
         */
        $res    = true;
        $method = "_prepare";
        foreach ($this->_partials as $idex => $part)
        {
            // If the first part is 'index', replace it with 'main'
            $method .= '_'. (($idex === 0) && ($part === 'index')
                                ? 'main'
                                : $part);

            /*
            Connexions::log("Connexions_Controller_Action(%s)::"
                            . "_preparePartial(): "
                            . "Check method [ %s ]",
                            get_class($this),
                            $method);
            // */

            if (method_exists( $this, $method ))
            {
                /*
                Connexions::log("Connexions_Controller_Action(%s)::"
                                . "_preparePartial(): "
                                . "Invoke [ %s ]",
                                get_class($this),
                                $method);
                // */

                $res = $this->{$method}();
                if ($res === false)
                {
                    break;
                }
            }
        }

        return $res;
    }

    /** @brief  Prepare and render a partial view.
     *
     *  The final, rendered view script is based upon '_partials'.
     *
     */
    protected function _renderPartial()
    {
        if ($this->_preparePartial() === false)
        {
            return;
        }

        // Perform direct rendering of the script indicated by '_partials'.
        $script = implode('-', $this->_partials);

        /*
        Connexions::log("Connexions_Controller_Action(%s)::"
                        . "_renderPartial(): "
                        . "render view script [ %s ]",
                        get_class($this),
                        $script);
        // */

        $this->render($script);
    }

    /** @brief  Render the sidebar based upon the incoming request.
     *
     */
    protected function _renderSidebar()
    {
        if ($this->_noSidebar === true)
        {
            return;
        }

        /* Use the 'sidebar.phtml' script associated with the current
         * controller:
         *      application/views/scripts/%controller%/sidebar.html
         */
        $controller = '';   //$this->_request->getParam('controller');
        $script     = $controller .'/sidebar.phtml';

        /*
        Connexions::log("Connexions_Controller_Action(%s)::"
                        . "_renderSidebar(): entire sidebar via "
                        . "controller script [ %s ], view.sidebar[ %s ]",
                        get_class($this),
                        $script,
                        Connexions::varExport($this->view->sidebar));
        // */

        // Render the sidebar into the 'right' placeholder
        $this->view->renderToPlaceholder($script, 'right');
    }

    /** @brief  Prepare for rendering the main view, regardless of format.
     *
     *  This will collect the variables needed to render the main view, placing
     *  them in $view->main as a configuration array.
     */
    protected function _prepare_main()
    {
        $request    =& $this->_request;
        $namespace  =  $this->_namespace;

        /*
        Connexions::log("Connexions_Controller_Action::_prepare_main(): "
                        . "namespace[ %s ], format[ %s ], all params[ %s ]",
                        $namespace, $this->_format,
                        Connexions::varExport($request->getParams()));
        // */

        $config = array(
            'namespace'     => $namespace,
            'cookieUrl'     => $this->_rootUrl,
            'viewer'        => &$this->_viewer,
            'displayStyle'  => null,
        );

        if (($this->_format === 'html') || ($this->_format === 'partial'))
        {
            /* HTML and Partial will typically be requested via click on a
             * pre-defined URL.
             *
             * "displayStyle" is indicated by a combination of
             *  '%namespace%OptionGroup' and '%namespace%OptionsGroups_option'
             */
            $displayStyle = $request->getParam($namespace ."OptionGroup");
            $dsCustom     = $request->getParam($namespace
                                                    ."OptionGroups_option");

            if ( ($displayStyle === 'custom') && (is_array($dsCustom)) )
                $displayStyle = $dsCustom;

            $config['displayStyle'] = $displayStyle;

            /* Include ALL namespaced parameters
             *  (i.e. ALL parameters with a prefix of %namespace%)
             */
            $params   = $request->getParams();
            $nsLen    = strlen($namespace);
            foreach ($params as $key => $val)
            {
                if (substr($key, 0, $nsLen) == $namespace)
                {
                    $nsKey = substr($key, $nsLen);
                    $nsKey[0] = strtolower($nsKey[0]);

                    $config[ $nsKey ] = $val;
                }
            }
        }
        else
        {
            /* All the rest are more subject to variability since they are
             * likely added by a user.
             */
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


            $config['perPage']    = $perPage;
            $config['page']       = $page;
            $config['sortBy']     = $sortBy;
            $config['sortOrder']  = $sortOrder;
        }

        /*
        Connexions::log("Connexions_Controller_Action::_prepare_main(): "
                        . "namespace[ %s ], final config[ %s ]",
                        $namespace,
                        Connexions::varExport($config));
        // */

        // Additional view variables for the HTML view.
        $this->view->main = $config;
    }

    /** @brief  Prepare for rendering the sidebar view.
     *
     *  This will collect the variables needed to render the sidebar view,
     *  placing them in $view->sidebar as a configuration array.
     */
    protected function _prepare_sidebar()
    {
        $request =& $this->_request;

        /* If this is being rendered as a partial, treat all pans as
         * synchronous, otherwise make then asynchronous.
         */
        $async = ($this->_format === 'partial'
                    ? false
                    : true);

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
                    'viewer'        => &$this->_viewer,
                    'cookieUrl'     => $this->_rootUrl,
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
                    'viewer'        => &$this->_viewer,
                    'cookieUrl'     => $this->_rootUrl,
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
                    'viewer'        => &$this->_viewer,
                    'cookieUrl'     => $this->_rootUrl,
                    'namespace'     => 'sbItems',
                    'title'         => 'Items',
                    'weightName'    => 'userCount',

                    // 'related' will be set by the main view renderer
                    // 'selected'      => $this->_owner,
                    'itemType'      =>
                                View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM,
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

    /** @brief  Prepare for rendering the post view, regardless of format.
     *
     *  This will verify that this is a POST request.
     *
     *  @return true (is POST) or false (is NOT POST).
     */
    protected function _prepare_post()
    {
        $request   =& $this->_request;
        $namespace =  $this->_namespace;

        if (! $request->isPost())
        {
            $this->view->error = 'Invalid Post';
            return false;
        }

        return true;
    }
}
