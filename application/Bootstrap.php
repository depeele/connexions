<?php

//error_reporting(E_ALL);

require_once('Zend/Session.php');
require_once('Zend/Loader/Autoloader.php');

require_once('Connexions.php');
require_once('Connexions/Autoloader.php');

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    /** @brief  Allow the direct setting of container resources.
     *  @param  name    The resource name.
     *  @param  val     The resource value.
     *
     *  @return Zend_Application_Bootstrap_Bootstrap
     */
    public function setResource($name, $val)
    {
        $resource  = strtolower($name);
        $container = $this->getContainer();
        $container->{$name} = $val;

        return $this;
    }
    

    /** @brief  Perform all initialization that will be needed no matter what
     *          the view.
     */
    protected function _initCommon()
    {
        $this->_commonTimezone()
             ->_commonSession()
             ->_commonAutoload()
             ->_commonLogging();

        /*
        Connexions::log("Bootstrap::_initCommon: "
                        .   "session save path[ %s ], "
                        .   "session id[ %s ], "
                        .   "options[ %s ]",
                        session_save_path(),
                        Zend_Session::getId(),
                        Connexions::varExport(Zend_Session::getOptions()));
        // */

        $this->_commonPaths()
             ->_commonDb()
             ->_commonConnection()
             ->_commonAuth();

        /*
        Connexions::log("Bootstrap::_initCommon: headers[ %s ]",
                        print_r(getallheaders(), true ));
        // */

        /*
        Connexions_Profile::checkpoint('Connexions',
                                       'Bootstrap::_initCommon complete');
        // */
    }

    protected function _initMinimalView()
    {
        // Ensure that 'common' has been initialized.
        $this->bootstrap('common');

        /************************************************
         * Perform minimal view initialization
         *
         */
        $this->_controllerRequest()
             ->_controllerPlugins()
             ->_viewContext()
             ->_viewAcl()
             ->_viewRoute();

        // Initialize the view
        $viewResource = $this->getPluginResource('view');
        $view         = $viewResource->init();

        // Add our view helpers as well as the ZendX JQuery view helper.
        $view->addHelperPath(APPLICATION_PATH .'/views/helpers',
                                'View_Helper')
             ->addHelperPath("ZendX/JQuery/View/Helper",
                                "ZendX_JQuery_View_Helper");

        /*
        Connexions::log("_initMinimalView: Helper Paths[ %s ]",
                        Connexions::varExport($view->getHelperPaths()) );
        // */

        return $view;
    }

    protected function _initView()
    {
        /* Ensure that 'minimalView' has been initialized and retrieve the
         * generated view.
         */
        $this->bootstrap('minimalView');
        $view = $this->getResource('minimalView');

        /*******************************************************************
         * Set view defaults:
         *  encoding, doctype, head metadata,
         *  base title and title separator
         *
         *  from resources.view
         *          .encoding
         *          .doctype
         *          .contentType
         *          .title
         *          .titleSeparator
         */
        $options = $this->getOptions();
        if (isset($options['resources']['view']))
        {
            $optsView =& $options['resources']['view'];
            if (isset($optsView['doctype']))
                $view->doctype($optsView['doctype']);

            if (isset($optsView['contentType']))
                $view->headMeta()->appendHttpEquiv(
                                'Content-Type',
                                $optsView['contentType']
                           );

            if (isset($optsView['title']))
                $view->headTitle($optsView['title']);

            if (isset($optsView['titleSeparator']))
                $view->headTitle()
                        ->setSeparator(
                            $optsView['titleSeparator'] );
        }

        /*******************************************************************
         * Initialize the default ACL and Role for this view.
         *
         */
        //$acl  = Zend_Registry::get('acl'); //$this->getResource('acl');
        $acl = $this->getResource('acl');

        // Initialize the default ACL and Role for this view
        Zend_View_Helper_Navigation_HelperAbstract::setDefaultAcl($acl);
        Zend_View_Helper_Navigation_HelperAbstract::setDefaultRole('guest');


        /*******************************************************************
         * Initialize paging.
         *
         */
        if ($this->hasOption('paging'))
        {
            /* Set our default pagination:
             *  ScrollingStyle      -- 'Elastic', 'Jumping', 'Sliding'
             *  ItemCountPerPage
             *  PageRange
             *  ...
             */
            $paging = $this->getOption('paging');

            /*
            Connexions::log("Bootstrap::_initView: paging [ ".
                                var_export($paging, true) . " ]");
            // */

            Connexions_Controller_Action_Helper_Pager::setDefaults($paging);
        }

        /*******************************************************************
         * Initialize Navigation
         *
         */
        $config = new Zend_Config_Xml(
                                APPLICATION_PATH .'/configs/navigation.xml',
                                'nav');

        /*
        Connexions::log("_initView: nav config[ %s ]",
                        print_r($config->toArray(), true));
        // */

        $nav    = new Zend_Navigation($config);
        $this->setResource('navigation', $nav);

        $view->navigation($nav);

        /* If there is a current, authenticated user, set our ACL role to
         * 'member'
         */
        $user = $this->getResource('user'); //Zend_Registry::get('user');
        if ($user && $user->isAuthenticated())
        {
            $view->navigation()->setRole('member');
        }

        /*
        //Connexions::log("Bootstrap::_initView: role[ "
        //                .   $view->navigation()->getRole()
        //                .       " ]");

        $it = new RecursiveIteratorIterator(
                        $view->navigation()->getContainer(),    //$nav,
                        RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $idex => $page)
        {
            Connexions::log("_initView: nav page #%d: %s %s [ %s ]",
                            $idex, str_repeat('.', $it->getDepth()),
                            $page->label, $page->getHref());
        }
        // */

        /*
        Connexions_Profile::checkpoint('Connexions',
                                       "Bootstrap::_initView complete: "
                                       .    "role[ %s ]",
                                       $view->navigation()->getRole());
        // */

        return $view;
    }

    /*************************************************************************
     * Batch initialization helpers
     *
     */

    /** @brief  Initialize the default timezone. */
    protected function _commonTimezone()
    {
        if ($this->hasOption('timezone'))
        {
            $zone = $this->getOption('timezone');
        }
        else
        {
            $zone = 'UTC';
        }

        date_default_timezone_set($zone);

        return $this;
    }

    /** @brief  Initialize the PHP session. */
    protected function _commonSession()
    {
        $options    = array(
            /* If the current connection is secure (https), REQUIRE a secure
             * connection before we provide the session cookie.
             */
            'cookie_secure' => (isset($_SERVER['HTTPS']) &&
                                ($_SERVER['HTTPS'] === 'on')
                                    ? true
                                    : ''),
        );

        Zend_Session::start($options);

        return $this;
    }

    /** @brief  Initialize autoloading. */
    protected function _commonAutoload()
    {
        /*
        $autoLoader = Zend_Loader_Autoloader::getInstance();
        $autoLoader->registerNamespace('Connexions_');

        $loader = new Zend_Application_Module_Autoloader(
                array('namespace'   => '',  //'App',    App_Model_
                      'basePath'    => dirname(__FILE__),
                )
        );
        $this->setResource('ResourceLoader', $loader);

        return $this;
        // */

        $autoLoader = Zend_Loader_Autoloader::getInstance();

        $connexionsLoader = new Connexions_Autoloader();
        $autoLoader->unshiftAutoloader($connexionsLoader,
                                       $connexionsLoader->getNamespaces());

        // Tell the loader to load ANY namespace
        $autoLoader->setFallbackAutoloader(true);

        return $this;

        /*
        $autoLoader = new Zend_Application_Module_Autoloader(array(
                            'namespace' => 'Default_',
                            'basePath'  => dirname(__FILE__)
                          ));

        return $autoLoader;
        */
    }

    /** @brief  Initialize logging. */
    protected function _commonLogging()
    {
        $resources = $this->getOption('resources');

        // Do we have 'resources.log' in our options?
        if ( is_array($resources) && isset($resources['log']))
        {
            try
            {
                $config = $this->getPluginResource('log');
                $log    = $config->init();
            }
            catch (Exception $e)
            {
                echo "<pre>*** Log Initialization error\n",
                        print_r($e, true),
                     "</pre>\n";
                die;
            }
        }
        else
        {
            // Logging is NOT enabled.
            $log = -1;
        }

        // Make the log available via the global Registry
        Zend_Registry::set('log', $log);

        if ($log !== -1)
        {
            //Connexions::log('Bootstrap::Logging initialized');
            Connexions_Profile::init($log);
            Connexions_Profile::start('Connexions',
                                      'Bootstrap::Logging initialized');
        }

        return $this;
    }

    /** @brief  Initialize configuration path information.
     *
     *  Establish the Configuration 'paths' entries to support
     *  Connexions::url2path()
     */
    protected function _commonPaths()
    {
        defined('APPLICATION_WEBROOT')
            || define('APPLICATION_WEBROOT',
                      realpath(APPLICATION_PATH .'/../public'));

        /*
        Connexions::log("Bootstrap::_commonPaths: APPLICATION_WEBROOT [ %s ]",
                        APPLICATION_WEBROOT);
        // */

        $config  = Connexions::getConfig();
        $baseUrl = $config->urls->base;
        foreach ($config->urls as $name => $url)
        {
            $path = APPLICATION_WEBROOT
                  . preg_replace('#^'. $baseUrl .'#', '', $url);

            /*
            Connexions::log("Bootstrap::_commonPaths: url.%s [ %s ] == [ %s ]",
                            $name, $url, $path);
            // */

            Connexions::urlPathMap($name, array('url'   => $url,
                                                'path'  => $path) );
        }

        return $this;
    }

    /** @brief  Initialize the databsae. */
    protected function _commonDb()
    {
        /* Database cache configuration is found in:
         *  cache.db
         *      .frontEnd
         *          .adapter    Front-end adapter name
         *          .params     Front-end adapter options
         *      .backEnd
         *          .adapter    Back-end adapter name
         *          .params     Back-end adapter options
         */
        $cache = $this->getOption('cache');
        if (isset($cache['db']))
        {
            // Create a metadata cache to be used with all table objects.
            $dbCache =& $cache['db'];

            /*
            Connexions::log("Bootstrap::_commonDb: dbCache [ ".
                                var_export($dbCache, true) . " ]");
            // */

            $cache = Zend_Cache::factory($dbCache['frontEnd']['adapter'],
                                         $dbCache['backEnd']['adapter'],
                                         $dbCache['frontEnd']['params'],
                                         $dbCache['backEnd']['params']);

            Zend_Db_Table::setDefaultMetadataCache($cache);
        }

        $config = $this->getPluginResource('db');

        /* Zend_Application_Resource_Db
        Connexions::log("bootstrap::_commonDb: config.params[ %s ]",
                        print_r($config->getParams(), true));
        // */

        $db     = $config->getDbAdapter();

        try
        {
            $db->getConnection();
        }
        catch (Zend_Db_Adapter_Exception $e)
        {
            /* perhaps a failed login credential, or perhaps the RDBMS is not
             * running
             */
            echo "<pre>*** Database error: Failed to login or "
                    . "DB not accessible\n",
                    print_r($e, true),
                 "</pre>\n";
            die;

        }
        catch (Zend_Exception $e)
        {
            // perhaps factory() failed to load the specified Adapter class
            die("*** Database error: Cannot load specified adapter class");
        }

        if (! $db->isConnected())
        {
            die("*** Cannot connect to database");
        }

        // Make this the default database adapter for Zend_Db_Table
        Zend_Db_Table::setDefaultAdapter($db);

        /* Make this available via the global Registry and as a Bootstrap
         * Resource
         */
        Zend_Registry::set('db', $db);
        $this->setResource('db', $db);

        //Connexions::log('Bootstrap::Database initialized');

        return $this;
    }

    /** @brief  Initialize connexions information. */
    protected function _commonConnection()
    {
        $connectionInfo = array(
            'domain'    => (isset($_SERVER['SERVER_NAME'])
                                ? $_SERVER['SERVER_NAME']
                                : 'localhost'),
            'clientIp'  => (isset($_SERVER['REMOTE_ADDR'])
                                ? $_SERVER['REMOTE_ADDR']
                                : '127.0.0.1'),
            'referer'   => (isset($_SERVER['HTTP_REFERER'])
                                ? $_SERVER['HTTP_REFERER']
                                : ''),
            'https'     => (isset($_SERVER['HTTPS']) &&
                            ($_SERVER['HTTPS'] === 'on')
                                ? true
                                : false),
            'pki'       => null,
        );

        // Include any PKI information
        if ( isset($_SERVER['SSL_CLIENT_VERIFY']) )
        {
            $connectionInfo['pki'] = array(
                'verified'  => ($_SERVER['SSL_CLIENT_VERIFY'] === 'SUCCESS'),
                'issuer'    => (isset($_SERVER['SSL_CLIENT_I_DN'])
                                    ? $_SERVER['SSL_CLIENT_I_DN']
                                    : null),
                'subject'   => (isset($_SERVER['SSL_CLIENT_S_DN'])
                                    ? $_SERVER['SSL_CLIENT_S_DN']
                                    : null),
            );
        }

        /*
        Connexions::log("Bootstrap::_commonConnection: connectionInfo[ %s ]",
                        Connexions::varExport($connectionInfo));
        // */

        Zend_Registry::set('connectionInfo', $connectionInfo);

        return $this;
    }

    /** @brief  Initialize authentication. */
    protected function _commonAuth()
    {
        $uService = Connexions_Service::factory('Service_User');
        $user     = null;

        // Initialize authentication to use session-based storage
        $auth = Zend_Auth::getInstance();
        $auth->setStorage(new Zend_Auth_Storage_Session('connexions', 'user'));

        /* AuthController::signinAction() is where non-transport-level
         * authentication actually occurs.  This is just checking to see if
         * that has already happend, which would result in the session-based
         * storage containing an Identity that we can retrieve here.
         */
        if  ($auth->hasIdentity())
        {
            /*
            Connexions::log("Bootstrap::_commonAuth: Auth has identity: %s",
                            Connexions::varExport($auth->getIdentity()));
            // */

            // Locate the session-based user
            $user = $uService->find( $auth->getIdentity() );
            if ($user !== null)
            {
                /* We have a session-based authenticated user.
                 *
                 * Do we need to perform a new authentication?
                 */
                $nextAuth = Connexions::date2time($user->lastAuth)
                          + Connexions::getConfig()->api->authTimeout;

                /*
                Connexions::log("Bootstrap::_commonAuth(): "
                                .   "Session based identity: %s : %s, "
                                .   "nextAuth[ %s ], time[ %s ]",
                                Connexions::varExport($auth->getIdentity()),
                                $user,
                                $nextAuth, time());
                // */

                if ($nextAuth <= time())
                {
                    /* The previous authentication for this session-based user
                     * has expired.
                     */

                    /*
                    Connexions::log("Boostrap::_commonAuth(): auth expiration "
                                    .   "for '%s'...",
                                    $user);
                    // */

                    $user->logout();
                    $user = null;
                }
                else
                {
                    /* Create a Zend_Auth_Result that indicates success based
                     * upon the session-based authenticated user.
                     */
                    $result = new Connexions_Auth_Pre($user);
                    $user->setAuthResult($result);
                }
            }
        }

        if ($user === null)
        {
            /* We have no valid session-based authenticated user.
             *
             * See if the connecting user may have specified _autoSignin() for
             * an authentication method and, if so, whether re-authentication
             * succeeds.
             */
            $user = $this->_autoSignin();
        }

        /*
        Connexions::log("Bootstrap::_commonAuth: user is %sNULL",
                        ($user === null ? '' : 'NOT '));
        // */

        if ($user !== null)
        {
            // Update the 'lastVisit' time for this user.
            $user->updateLastVisit();
            $user->save(true);  // noLog
        }
        else
        {
            /*
            Connexions::log("Bootstrap::_commonAuth: create an anonymous user");
            // */

            // Find/Make the 'anonymous', unauthenticated user
            $user = $uService->getAnonymous();
        }

        //Connexions::log("Bootstrap::_commonAuth: Add 'user' to registry");

        /* Make this available via the global Registry and as a Bootstrap
         * Resource.
         *
         * Without making this available via the global Registry, we would need
         * to retrieve the current bootstrap and the request the 'user'
         * resource.
         *
         * From a Zend_Controller_Action:
         *      $this->getInvokeArg('bootstrap')->getResource('user');
         *      
         */
        Zend_Registry::set('user', $user);
        $this->setResource('user', $user);

        /*
        Connexions_Profile::checkpoint('Connexions',
                                       "Bootstrap::_commonAuth complete: "
                                       .    "user[ %s ], %sauthenticated",
                                       $user->name,
                                       ($user->isAuthenticated()
                                            ? '' : 'NOT '));
        // */

        return $this;
    }

    /** @brief  If there is an 'autoSignin' cookie, attempt auto-signin with
     *          all indicated methods until authentication is successful or
     *          we run out of methods.
     *
     *  @return A valid, authenticated Model_User instance or null.
     */
    protected function _autoSignin()
    {
        $autoSigninCookie = Connexions::getConfig()->api->autoSigninCookie;
        $autoSignin       = (isset($_COOKIE[$autoSigninCookie])
                                 ? $_COOKIE[$autoSigninCookie]
                                 : false);

        /*
        Connexions::log("Bootstrap::_autoSignin(): autoSignin cookie: "
                        . "name[ %s ], value[ %s ]",
                        $autoSigninCookie,
                        Connexions::varExport($autoSignin));
        // */

        if (empty($autoSignin))
        {
            return null;
        }

        /********************************************************************
         * Attempt all authentication methods indicated by the autoSignin
         * cookie to see if we can automatically authenticate with any.
         */
        $user     = null;
        $uService = Connexions_Service::factory('Service_User');
        $methods  = preg_split('/\s*,\s*/', $autoSignin);
        foreach ($methods as $method)
        {
            try {
                $user = $uService->authenticate( $method );
            } catch(Exception $e) {
                $user = null;
            }

            /*
            Connexions::log("Bootstrap::_autoSignin(): method[ %s ] user[ %s ]",
                        $method,
                        Connexions::varExport($user));
            // */

             if ($user && $user->isAuthenticated())
             {
                 return $user;
             }
        }

        return null;
    }

    /*******************************************
     * For Views
     *
     */

    /** @brief  Initialize the incoming request, assigning it to the front
     *          controller.
     */
    protected function _controllerRequest()
    {
        $front   = Zend_Controller_Front::getInstance();
        $request = Connexions::getRequest();


        /* DEBUG: Disable output buffering...
        $front->getDispatcher()
                    ->setParam('disableOutputBuffering', true);

        printf ("_controllerRequest(): output buffering disabled<br />\n");
        // */


        /*
        $request = $front->getRequest();
        */
        if ($request === null)
        {
            /* We don't already have a request assigned so create one ASSUMING
             * HTTP.
             */
            $request = new Connexions_Controller_Request_Http();
            $front->setRequest($request);

            Connexions::setRequest($request);
        }

        // Should buffering be disabled?
        $isStreaming = Connexions::to_bool(
                            $request->getParam('streaming', false) );

        /*
        Connexions::log("_controllerRequest: is %sStreaming",
                        ($isStreaming === true ? '' : 'NOT '));
        Connexions::log("_controllerRequest: url[ %s ], params[ %s ]",
                        Connexions::varExport($request->getRequestUri()),
                        Connexions::varExport($request->getParams()));
        // */

        if ($isStreaming === true)
        {
            // /*
            Connexions::log("_controllerRequest: Streaming -- "
                            .   "disable output buffering and layout");
            // */

            $front->setParam('disableOutputBuffering', true);
            $front->getDispatcher()
                    ->setParam('disableOutputBuffering', true);

            $layout       = Zend_Layout::getMvcInstance();
            if ($layout instanceof Zend_Layout)
            {
                // /*
                Connexions::log("_controllerRequest: disable layout");
                // */

                $layout->disableLayout();
            }
        }


        // Make the request available as a Bootstrap Resource
        $this->setResource('request', $request);

        return $this;
    }

    /** @brief  Initialize common, pre-view plugins and helpers. */
    protected function _controllerPlugins()
    {
        /* Register our Controller Action Helpers Prefix.
         *
         * This will make available all helpers in:
         *  library/Connexions/Controller/Action/Helper
         */
        Zend_Controller_Action_HelperBroker::addPrefix(
                                        'Connexions_Controller_Action_Helper');

        // Register a Resource Injector
        Zend_Controller_Action_HelperBroker::addHelper(
                new Connexions_Controller_Action_Helper_ResourceInjector());

        /* Content-Type parameter helper: $view->_helper->params();
        Zend_Controller_Action_HelperBroker::addHelper(
                new Connexions_Controller_Action_Helper_Params());
        // */

        return $this;
    }

    /** @brief  Initialize available render contexts.
     *
     *  The choice of context is handled by the view renderer based upon the
     *  'format' request parameter.
     */
    protected function _viewContext()
    {
        $contextSwitch =
          Zend_Controller_Action_HelperBroker::getStaticHelper('contextSwitch');

        $contextSwitch->setContexts(array(
            'partial'   => array(
                //'suffix'    => 'part',
            ),
            'json'      => array(
                'suffix'    => 'json',
                'headers'   => array('Content-Type'  => 'application/json'),
                'callbacks' => array(
                    'init'  => array($this, 'jsonp_init'),
                    'post'  => array($this, 'jsonp_post'),
                ),
            ),
            'rss'       => array(
                'suffix'    => 'rss',
                'headers'   => array('Content-Type'  => 'application/xml'),
                'callbacks' => array(
                    'init'  => array($this, 'feed_init'),
                    'post'  => array($this, 'feed_post'),
                ),
            ),
            'atom'      => array(
                'suffix'    => 'atom',
                'headers'   => array('Content-Type'  => 'application/xml'),
                'callbacks' => array(
                    'init'  => array($this, 'feed_init'),
                    'post'  => array($this, 'feed_post'),
                ),
            ),
        ));

        // Perform header detection to determine context
        $request = $this->getResource('request');
        $header  = $request->getHeader('Accept');
        if (strstr($header, 'application/json'))
        {
            $request->setParam('format', 'json');
        }
        /*
        else if ( strstr($header, 'application/xml') &&
                  (! strstr($header, 'html')) )
        {
            $request->setParam('format', 'xml');
        }
        */

        return $this;
    }

    /** @brief  Initialize the view-related ACL. */
    protected function _viewAcl()
    {
        // Setup ACL
        $acl = new Zend_Acl();

        $acl->addRole(new Zend_Acl_Role('member'));
        $acl->addRole(new Zend_Acl_Role('guest'));

        $acl->addResource(new Zend_Acl_Resource('member'));
        $acl->addResource(new Zend_Acl_Resource('guest'));

        $acl->deny( 'guest',  'member');
        $acl->allow('member', 'member');

        $acl->allow('guest',  'guest');
        $acl->deny( 'member', 'guest');

        /* Make this available via the global Registry and as a Bootstrap
         * Resource
         */
        Zend_Registry::set('acl', $acl);
        $this->setResource('acl', $acl);

        return $this;
    }

    /** @brief  Initialize view-related routing. */
    protected function _viewRoute()
    {
        $front  = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();

        $route = new Connexions_Controller_Route();
        $router->addRoute('default', $route);

        //Connexions::log('Bootstrap::Route initialized');

        return $this;
    }

    /*******************************************
     * JSON Processing
     *
     */

    public function jsonp_init()
    {
        $viewRenderer =
          Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');

        $view = $viewRenderer->view;
        if ($view instanceof Zend_View_Interface)
        {
            /* Disable rendering -- we'll handle it directly, performing the
             *                      final output in jsonp_post()
             */
            //Connexions::log('Bootstrap::jsonp_init: Disable auto rendering');

            $viewRenderer->setNoRender(true);
        }
    }

    /** @brief  Perform post-processing of a JSON request.
     *
     *  JSONP handling makes use of the following view variables:
     *      rpc         If set, this SHOULD be a JsonRpc instance that SHOULD
     *                  contain the reply data;
     *      callback    If set, this is the JSONP callback name specified by
     *                  the remote caller;
     *      data        REQUIRED, this object defines all data that will be
     *                  JSON encoded and returned to the remote caller.
     *                  If NOT provided, view rendering will be re-enabled,
     *                  causing application/views/scripts/
     *                              <controller>/<action>.json.pthml
     *                  to be rendered.
     */
    public function jsonp_post()
    {
        $front = Zend_Controller_Front::getInstance();

        // See if the current request has been dispatched
        try
        {
            $request      = $front->getRequest();
            if (! $request->isDispatched())
            {
                /* Do NOTHING.  The active request has NOT been dispatched
                 * (i.e. is likely being re-routed).
                 */

                /*
                Connexions::log("jsonp_post: Request NOT dispatched -- return");
                // */
                return;
            }
        } catch (Exception $e) { }

        $viewRenderer =
          Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');

        $view = $viewRenderer->view;

        /*
        Connexions::log("jsonp_post: data %spresent, rpc %spresent",
                        (isset($view->data) ? '' : 'NOT '),
                        (isset($view->rpc)  ? '' : 'NOT '));
        // */

        if ((! $view instanceof Zend_View_Interface) ||
            ( (! isset($view->data)) &&
             ((! isset($view->rpc)) ||
              ( ! $view->rpc instanceof Connexions_JsonRpc)) ))
        {
            // Invalid state for JSONP.  Re-enable view rendering and return.
            Connexions::log("jsonp_post: Missing data/rpc information.  "
                            . "Fallback to normal rendering...");

            $viewRenderer->setNoRender(false);
            return;
        }

        if (isset($view->rpc))
        {
            // The return data is the RPC reply
            $json = $view->rpc->toJson();
        }
        else
        {
            // Grab JSONP information from the view.
            $json = json_encode($view->data);
        }

        if (isset($view->callback) && (! empty($view->callback)))
        {
            $json = "{$view->callback}({$json});";
        }

        if ($front instanceof Zend_Controller_Front)
        {
            /* Set the response body -- this determines what will be returned
             * to the remove caller.
             */
            $front->getResponse()->setBody($json);

            /*
            Connexions::log('Bootstrap::jsonp_post: '
                            .   'place JSON in response body [ %s ]',
                            $json);
            // */
        }
        else
        {
            // Not sure that this can happen...  if it does, punt.
            Connexions::log('Bootstrap::jsonp_post: '
                            .   'echo JSON directly');
            echo $json;
        }

        Connexions_Profile::stop('Connexions',
                                 'JSON rendering COMPLETE');
    }

    /*******************************************
     * Feed Processing
     *
     */

    public function feed_init()
    {
        $viewRenderer =
          Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');

        $view = $viewRenderer->view;
        if ($view instanceof Zend_View_Interface)
        {
            /* Disable rendering -- we'll handle it directly, performing the
             *                      final output in feed_post()
             */
            Connexions::log('Bootstrap::feed_init: Disable auto rendering');

            $viewRenderer->setNoRender(true);
        }
    }

    /** @brief  Perform post-processing of a Feed request.
     *
     *  Feed handling makes use of the following view variables:
     *      feed        SHOULD be a Zend_Feed instance containing the feed data
     *                  to be returned;
     */
    public function feed_post()
    {
        $viewRenderer =
          Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');

        $view = $viewRenderer->view;
        if ((! $view instanceof Zend_View_Interface) ||
            (! isset($view->feed)) ||
            (! $view->feed instanceof Zend_Feed_Abstract))
        {
            // Invalid state for JSONP.  Re-enable view rendering and return.
            Connexions::log("feed_post: Missing feed information.  "
                            . "feed is "
                            .   (! isset($view->feed)
                                    ? 'MISSING'
                                    : get_class($view->feed))
                            . ". Fallback to normal rendering...");

            $viewRenderer->setNoRender(false);
            return;
        }

        $view->feed->send();

        Connexions_Profile::stop('Connexions',
                                 'Feed rendering COMPLETE');
    }
}
