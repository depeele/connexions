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

    protected   $_connection= null;     /* Connection information gatherd
                                         * via Bootstrap::_commonConnection()
                                         * that includes:
                                         *      domain      string
                                         *      clientIp    string
                                         *      https       boolean
                                         *      pki         null or object:
                                         *        verified  boolean
                                         *        issuer    null or string DN
                                         *        subject   null or string DN
                                         */
    protected   $_noNav     = false;    /* Should navigate be excluded?   */
    protected   $_noSidebar = false;    /* Should the sidebar be ignored? */
    protected   $_noFormatHandling
                            = false;    /* Should format handling in render()
                                         * be ignored?
                                         */

    protected   $_format    = 'html';   // Render format
    protected   $_feedType  = null;     // For _format == 'feed', type type
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
    protected   $_cookiePath= null;     /* The URL path to use when setting
                                         * cookies.  This is used to set the
                                         * cookie path for the attached
                                         * Javascript 'itemPane' which, in
                                         * turn, effects the cookie path passed
                                         * to the contained 'dropdownForm'
                                         * presneting Display Options.
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
        $this->_viewer      =  Zend_Registry::get('user');
        $this->_request     =  $this->getRequest();

        $this->_rootUrl     =  $this->_request->getBasePath();

        if ($this->_baseUrl === null)
        {
            $this->_baseUrl =  $this->_rootUrl;
        }

        $this->_url         =  $this->_baseUrl
                            .  $this->_request->getPathInfo();

        $this->_connection  = Zend_Registry::get('connectionInfo');

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
        Connexions::log("Connexions_Controller_Action::init(): "
                        .   "_connection[ %s ]",
                        Connexions::varExport($this->_connection));
        // */

        $this->_streaming = Connexions::to_bool(
                                $this->_request->getParam('streaming', false));


        // Default view variables that can be set early
        $this->view->controller    = $this->_request->getParam('controller');
        $this->view->action        = $this->_request->getParam('action');
        $this->view->rootUrl       = $this->_rootUrl;
        $this->view->baseUrl       = $this->_baseUrl;
        $this->view->cookiePath    = $this->_cookiePath;
        $this->view->url           = $this->_url;
        $this->view->viewer        = $this->_viewer;
        $this->view->connection    = $this->_connection;
        $this->view->searchContext = $this->_request->getParam('searchContext',
                                                               null);

        /*
        Connexions::log("Connexions_Controller_Action::init(): "
                        .   "baseUrl[ %s ], url[ %s ], viewer[ %s ]",
                        $this->_baseUrl,
                        $this->_url,
                        $this->_viewer);
        // */

        // Allow request-override of 'noNav'.
        $this->_noNav =
            Connexions::to_bool($this->_request->getParam('noNav',
                                $this->_noNav));

        /*
        Connexions::log("Connexions_Controller_Action::init(): noNav[ %s ]",
                        Connexions::varExport($this->_noNav));
        // */

        if ($this->_noNav === true)
        {
            $this->view->excludeNav = true;
        }
        if ($this->_noSidebar === true)
        {
            $this->view->excludeSidebar = true;
        }

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

            if ($format === 'json')
            {
                $callback = $this->_request->getParam('callback', null);
                if (! empty($callback))
                {
                    /*
                    Connexions::log("Connexions_Controller_Action::init(): "
                                    . "request json format, callback[ %s ]",
                                    Connexions::varExport($callback));
                    // */

                    $this->view->callback = $callback;
                }
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

    /** @brief  Retrieve and normalize timeline information.
     *  @param  params  Timeline retrieval parameters
     *                  ('grouping' will be forced to 'YM' for normalization);
     *
     *  @return Normalized timeline information.
     */
    protected function _getTimeline(array $params)
    {
        // Ensure the expected grouping
        $params['grouping'] = 'YM';

        // Construct the timeline
        $bSvc     = $this->service('Bookmark');
        $timeline = $bSvc->getTimeline( $params );

        $months = 0;
        $last   = null;
        foreach ($timeline['activity'] as $date => $count)
        {
            $month = substr($date, 0, 6);

            if ($month !== $last)   $months++;
            $last = $month;
        }

        /* Reduce to a number of ticks that will hopefully be
         * uncluttered
         */
        $ticks = $months;
        while ($ticks >= 24)
        {
            $ticks = ($ticks / 6) + 1;
        }

        /* For the RPC params, any Model_Set or Model instance should be passed
         * as a simple string.
         */
        $rpcParams = array();
        foreach ($params as $key => $val)
        {
            if (is_object($val) && method_exists($val, '__toString'))
            {
                $val = $val->__toString();
            }
            $rpcParams[$key] = $val;
        }

        $res = array(
            'rpcMethod'     => 'bookmark.getTimeline',
            'rpcParams'     => $rpcParams,
            'xDataHint'     => 'fmt:%Y %b',
            'replaceLegend' => true,
            'height'        => '250px',
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
            'rawData'       => $timeline,
        );

        return $res;
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
            // Does the name match the current viewer?
            if ($name === $this->_viewer->name)
            {
                // 'name' matches the current viewer...
                $ownerInst =& $this->_viewer;
            }
            else
            {
                /* Retieve a model representing the target user
                 * (MAY be unbacked).
                 */
                $ownerInst = $this->service('User')
                                    ->get(array('name' => $name));
            }

            // Have we located a user?
            if ($ownerInst !== null)
            {
                // YES -- we have a user model instance, possibly unbacked.
                $res = $ownerInst;
            }
        }

        return $res;
    }

    /** @brief  Retrieve a request parameter based upon the current namespace.
     *  @param  name        The name of the request parameter;
     *  @param  namespace   Override the current namespace when retrieving
     *                      this parameter [ null == use $this->_namspace ];
     *  @param  default     The default value if the request parameter doesn't
     *                      exist [ null ];
     *
     *  @return The value of the named parameter.
     */
    protected function _getParam($name, $namespace = null, $default = null)
    {
        $origName      = $name;
        $origNamespace = $namespace;
        if ($namespace === null)    $namespace = $this->_namespace;
        if (! empty($namespace))    $name      = $namespace . ucfirst($name);

        $val = $this->_request->getParam( $name, $default );

        /*
        Connexions::log("Connexions_Controller_Action::_getParam(%s, %s, %s): "
                        . "full name[ %s ] == [ %s ]",
                        $origName,
                        Connexions::varExport($origNamespace),
                        Connexions::varExport($default),
                        $name,
                        Connexions::varExport($val));
        // */

        return $val;
    }
    /** @brief  Retrieve all request parameters based upon the current
     *          namespace.
     *  @param  namespace   Override the current namespace when retrieving
     *                      these parameters [ null == use $this->_namspace ];
     *
     *  @return All parameters in the target namespace.
     */
    protected function _getParams($namespace = null)
    {
        $origNamespace = $namespace;
        if ($namespace === null)    $namespace = $this->_namespace;

        /* Include ALL namespaced parameters
         *  (i.e. ALL parameters with a prefix of %namespace%)
         */
        $params   = $this->_request->getParams();

        /*
        Connexions::log("Connexions_Controller_Action::_getParams(%s): "
                        . "namespace[ %s ], ALL parameters[ %s ]",
                        Connexions::varExport($origNamespace),
                        Connexions::varExport($namespace),
                        Connexions::varExport($params));
        // */

        $nsParams = array();
        $nsLen    = strlen($namespace);
        foreach ($params as $key => $val)
        {
            if (substr($key, 0, $nsLen) == $namespace)
            {
                $nsKey = substr($key, $nsLen);
                $nsKey[0] = strtolower($nsKey[0]);

                $nsParams[ $nsKey ] = $val;
            }
        }

        /*
        Connexions::log("Connexions_Controller_Action::_getParams(): "
                        . "namespace[ %s ] parameters[ %s ]",
                        Connexions::varExport($namespace),
                        Connexions::varExport($nsParams));
        // */

        return $nsParams;
    }

    /** @brief  The current display style.  The display style is indicated by a
     *          combination of 'optionGroup' and 'optionGroups_option'.
     *
     *  @param  namespace   Override the current namespace when retrieving
     *                      this parameter [ null == use $this->_namspace ];
     *  @param  default     The default value if the request parameter doesn't
     *                      exist [ null ];
     *
     *  @return The 'displayStyle'.
     */
    protected function _getDisplayStyle($namespace = null, $default = null)
    {
        $origNamespace = $namespace;
        if ($namespace === null)    $namespace = $this->_namespace;

        $group  = $this->_getParam('optionGroup',         $namespace, $default);
        $option = $this->_getParam('optionGroups_option', $namespace);

        /*
        Connexions::log("Connexions_Controller_Action(%s)::_getDisplayStyle(): "
                        . "namespace[ %s ], group[ %s ], option[ %s ]",
                        get_class($this),
                        $namespace,
                        Connexions::varExport($group),
                        Connexions::varExport($option));
        // */

        if ( ($group === 'custom') && (is_array($option)) )
        {
            $group = $option;
        }

        /*
        Connexions::log("Connexions_Controller_Action(%s)::_getDisplayStyle(): "
                        . "namespace[ %s ] return [ %s ]",
                        get_class($this),
                        $namespace,
                        Connexions::varExport($group));
        // */

        return $group;
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
        $actionName = $this->_request->getActionName();

        /*
        Connexions::log("Connexions_Controller_Action(%s)::_handleFormat(): "
                        . "_format[ %s ], _namespace[ %s ], "
                        . "url[ %s ], action[ %s ]",
                        get_class($this),
                        $this->_format, $this->_namespace,
                        $this->_request->getRequestUri(),
                        $actionName);
        // */


        /* By default, render the view script associated with the current
         * action
         */
        $this->_partials = array( $actionName );


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

        case 'rss':
            if ($this->_format !== 'feed')
            {
                $this->_format   = 'feed';
                $this->_feedType = Zend_Feed_Writer::TYPE_RSS_ANY;
            }
            // Fall through

        case 'atom':
            if ($this->_format !== 'feed')
            {
                $this->_format   = 'feed';
                $this->_feedType = Zend_Feed_Writer::TYPE_ATOM_ANY;
            }
            // Fall through
        
        case 'json':
            /* Render a (possibly) non-HTML format, based upon $this->_format.
             *
             * RSS and Atom are consolidated to:
             *      %action%-feed.pthml  with 'feedType' set appropriately.
             *
             * JSON is rendered via:
             *      %action%-json.phtml
             *
            Connexions::log("Connexions_Controller_Action(%s)"
                            . "::_handleFormat(): "
                            . "feedType[ %s ], format[ %s ]",
                            get_class($this),
                            Connexions::varExport($this->_feedType),
                            $this->_format);
             */

            $this->_partials = array( $actionName, $this->_format);

            $this->_renderPartial();
            break;

        case 'html':
        default:
            /* Normal HTML rendering - both the main pane and the sidebar.
             *
             * Tell the view about available contexts
             */
            if (isset($this->contexts) &&
                isset($this->contexts[$actionName ] ))
            {
                $this->view->contexts = $this->contexts[ $actionName ];
            }

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
        $namespace  =  $this->_namespace;

        /*
        Connexions::log("Connexions_Controller_Action::_prepare_main(): "
                        . "namespace[ %s ], format[ %s ], all params[ %s ]",
                        $namespace, $this->_format,
                        Connexions::varExport($this->_request->getParams()));
        // */

        $config = $this->_getParams();

        if (empty($config['namespace']))
            $config['namespace'] = $this->_namespace;
        if (empty($config['viewer']))
            $config['viewer'] = &$this->_viewer;


        /* Allow 'perPage', 'page', 'sortBy', and 'sortOrder' to be specified
         * for all formats.
         */
        $request = &$this->_request;
        if (empty($config['perPage']))
            $config['perPage']   = $request->getParam("perPage");
        if (empty($config['page']))
            $config['page']      = $request->getParam("page");
        if (empty($config['sortBy']))
            $config['sortBy']    = $request->getParam("sortBy");
        if (empty($config['sortOrder']))
            $config['sortOrder'] = $request->getParam("sortOrder");


        if (($this->_format === 'html') || ($this->_format === 'partial'))
        {
            // For HTML rendering, include 'cookiePath' and 'displayStyle'
            $config['cookiePath']   = $this->_cookiePath;
            $config['displayStyle'] = $this->_getDisplayStyle();
        }
        else
        {
            /* All the rest are more subject to variability since they are
             * likely added by a user.
             */

            if ( ($this->_format === 'feed') && (! empty($this->_feedType)) )
            {
                $config['feedType'] = $this->_feedType;
            }

            // Alternative names
            if (empty($config['perPage']))
                $config['perPage']   = $request->getParam("perpage");
            if (empty($config['page']))
                $config['page']      = $request->getParam("Page");
            if (empty($config['sortBy']))
                $config['sortBy']    = $request->getParam("sortby");
            if (empty($config['sortOrder']))
                $config['sortOrder'] = $request->getParam("sortorder");

            if (empty($config['perPage']))
                $config['perPage']   = $request->getParam("limit");
            if (empty($config['page']))
                $config['page']      = $request->getParam("offset");
        }

        /*
        Connexions::log("Connexions_Controller_Action(%s)::_prepare_main(): "
                        . "all params[ %s ]",
                        get_class($this),
                        Connexions::varExport($this->_request->getParams()));

        Connexions::log("Connexions_Controller_Action(%s)::_prepare_main(): "
                        . "namespace[ %s ], final config[ %s ]",
                        get_class($this),
                        $this->_namespace,
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
        /* If this is being rendered as a partial, treat all pans as
         * synchronous, otherwise make then asynchronous.
         */
        $async = ($this->_format === 'partial'
                    ? false
                    : true);

        $sidebar = array(
            'namespace'     => 'sidebar-tab',
            'async'         => $async,
            'viewer'        => &$this->_viewer,
            'users'         => null,
            'tags'          => &$this->_tags,
            'initialRender' => Connexions::to_bool(
                                $this->_request->getParam('initialRender',
                                                          false)),

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
                    'cookiePath'    => $this->_cookiePath,  //$this->_rootUrl,
                    'namespace'     => 'sbTags',
                    'title'         => 'Tags',
                    'weightName'    => 'userItemCount',

                    // 'related' will be set by the main view renderer
                    // 'selected'      => $this->_tags,
                    'itemType'      =>
                                View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM,
                    'itemBaseUrl'   => $this->_url,


                    // Default displayStyle
                    'displayStyle'  => View_Helper_HtmlItemCloud::STYLE_CLOUD,
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
                    'cookiePath'    => $this->_cookiePath,  //$this->_rootUrl,
                    'namespace'     => 'sbPeople',
                    'title'         => 'People',
                    'weightName'    => 'userItemCount',

                    // 'related' will be set by the main view renderer
                    // 'selected'      => $this->_owner,
                    'itemType'      =>
                                View_Helper_HtmlItemCloud::ITEM_TYPE_USER,
                    'itemBaseUrl'   => Connexions::url('/'),    // $this->_url,

                    // Default displayStyle
                    'displayStyle'  => View_Helper_HtmlItemCloud::STYLE_LIST,
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
                    'cookiePath'    => $this->_cookiePath,  //$this->_rootUrl,
                    'namespace'     => 'sbItems',
                    'title'         => 'Items',
                    'weightName'    => 'userCount',

                    // 'related' will be set by the main view renderer
                    // 'selected'      => $this->_owner,
                    'itemType'      =>
                                View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM,
                    'itemBaseUrl'   => $this->_url,

                    // Default displayStyle
                    'displayStyle'  => View_Helper_HtmlItemCloud::STYLE_LIST,
                ),
            ),
        );

        // Include namespaced and displayStyle parameters for each pane
        foreach ($sidebar['panes'] as $name => &$pane)
        {
            $params       = $this->_getParams($pane['namespace']);
            $params['panePartial']  = 'sidebar-'. $name;
            $params['displayStyle'] =
                $this->_getDisplayStyle($pane['namespace'],
                                        $pane['displayStyle']);

            $pane = array_merge($pane, $params);
        }

        /*
        Connexions::log("Connexions_Controller_Action(%s)::_prepare_sidebar(): "
                        . "namespace[ %s ], final config[ %s ]",
                        get_class($this),
                        $this->_namespace,
                        Connexions::varExport($sidebar));
        // */

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
        if (! $this->_request->isPost())
        {
            $this->view->error = 'Invalid Post';
            return false;
        }

        return true;
    }
}
