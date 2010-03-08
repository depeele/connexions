<?php

//error_reporting(E_ALL);

require_once('Connexions.php');
require_once('Connexions/Autoloader.php');

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    /** @brief  Perform all initialization that will be needed no matter what
     *          the view.
     */
    protected function _initCommon()
    {
        $this->_commonTimezone()
             ->_commonSession()
             ->_commonAutoload()
             ->_commonLogging()
             ->_commonDb()
             ->_commonUser();
    }

    protected function _initView()
    {
        // Initialize common view portions.
        $this->_viewAcl()
             ->_viewRoute()
             ->_viewPlugins();

        // Initialize the view
        $viewResource = $this->getPluginResource('view');
        $view         = $viewResource->init();

        /*******************************************************************
         * Add our view helpers as well as the ZendX JQuery view helper.
         *
         */
        $view->addHelperPath(APPLICATION_PATH .'/views/helpers',
                                'Connexions_View_Helper')
             ->addHelperPath("ZendX/JQuery/View/Helper",
                                "ZendX_JQuery_View_Helper");


        /*******************************************************************
         * Initialize the default ACL and Role for this view.
         *
         */
        $acl  = Zend_Registry::get('acl'); //$this->getResource('acl');

        // Initialize the default ACL and Role for this view
        Zend_View_Helper_Navigation_HelperAbstract::setDefaultAcl($acl);
        Zend_View_Helper_Navigation_HelperAbstract::setDefaultRole('guest');


        /*******************************************************************
         * Establish the base title and separator
         *
         */
        $view->headTitle('connexions')->setSeparator(' > ');


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

        $nav    = new Zend_Navigation($config);
        $view->navigation($nav);

        /* If there is a current, authenticated user, set our ACL role to
         * 'member'
         */
        $user = Zend_Registry::get('user');
        if ($user->isAuthenticated())
        {
            $view->navigation()->setRole('member');
        }

        // /*
        Connexions::log("Bootstrap::_initView: role[ "
                        .   $view->navigation()->getRole()
                        .       " ]");
        // */

        return $view;
    }




    protected function _commonTimezone()    //_initTimezone()
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

        //return $zone;
        return $this;
    }

    protected function _commonSession() //_initSession()
    {
        Zend_Session::start();

        return $this;
    }

    protected function _commonAutoload()    //_initAutoload()
    {
        $autoLoader = Zend_Loader_Autoloader::getInstance();

        $connexionsLoader = new Connexions_Autoloader();
        $autoLoader->unshiftAutoloader($connexionsLoader);

        // Load ANY namespace
        $autoLoader->setFallbackAutoloader(true);

        return $this;   //return $autoLoader;

        /*
        $autoLoader = new Zend_Application_Module_Autoloader(array(
                            'namespace' => 'Default_',
                            'basePath'  => dirname(__FILE__)
                          ));

        return $autoLoader;
        */
    }

    protected function _commonLogging() //_initLogging()
    {
        $config = $this->getPluginResource('log');

        try
        {
            $log = $config->init();
        }
        catch (Exception $e)
        {
            echo "<pre>*** Log Initialization error\n",
                    print_r($e, true),
                 "</pre>\n";
            die;

        }

        Zend_Registry::set('log', $log);

        Connexions::log('Bootstrap::Logging initialized');

        return $this;   //return $log;
    }

    protected function _commonDb()  //_initDb()
    {
        $config = $this->getPluginResource('db');
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

        Zend_Registry::set('db', $db);

        Connexions::log('Bootstrap::Database initialized');

        return $this;   //return $db;
    }

    protected function _commonUser()    //_initUser()
    {
        /* Attempt to identify and authenticate the current user:
         *  1) First, look in the session-based authentication store for an
         *     identity;
         *
         *     a) If an identity is found in the store, make sure it identifies
         *        a valid user;
         *        i)  If the identity represents a valid user, consider the
         *            user authenticated (should there be other checks??);
         *        ii) Otherwise, fall back to looking at the request.
         *
         *  2) If no valid identity was found in the session-based
         *     authentication store, see if our request contains identity and
         *     authentication information;
         *
         *     a) If identity and authentication information were found in the
         *        request, validate the identity and attempt authentication
         *        verification;
         *     b) If the identity is invalid or authentication fails, then we
         *        have no authenticated user and create an "invalid" Model_User
         *        instance to represent this fact;
         *
         *  3) Set the Model_User instance in our local cache and return it
         *     to be stored as the 'User' resource.
         */

        // 1) See if the session-based authentication store has an identity
        $auth = Zend_Auth::getInstance();
        $auth->setStorage(new Zend_Auth_Storage_Session('connexions', 'user'));

        $user   = null;
        $userId = $auth->getIdentity();
        /*
        Connexions::log(sprintf("Bootstrap::_initUser: "
                                . "UserId from session [ %s ]",
                                    print_r($userId, true)) );
        // */

        if ($userId !== null)
        {
            /* 1.a) There appears to be identity information in our
             *      authentication store (session/cookie).  Does it identify a
             *      valid user?
             */
            $user = Model_User::find($userId);

            /*
            Connexions::log("Bootstrap::_initUser: userId[{$userId}], "
                                . "User Model:\n"
                                .   $user->debugDump());
            // */

            if ($user->isBacked())
            {
                // 1.a.i) We have a valid user -- consider them authenticated.
                /*
                Connexions::log(sprintf("Bootstrap::_initUser: "
                                        .   "Initially Authenticated as "
                                        .       "[ %s ]",
                                        $user) );
                // */
                $user->setAuthenticated();
            }
        }

        if ( ($user === null) || (! $user->isBacked()) )
        {
            // 2) Do we have identity and authentication information?
            $userId  = $req->getParam('user', null);
            $pass    = $req->getParam('password');

            /*
            Connexions::log(sprintf("Bootstrap::_initUser: "
                                    .   "User[ %s ], pass[ %s ]...",
                                    $userId, $pass) );
            // */

            /* Generate a Model_User instance based upon the incoming userId.
             *
             * Note: If $userId is null, this will generate a Model_User
             *       instance that is marked as 'invalid'.
             *
             * If we already have a non-null user and $userId is null, then we
             * already have a $user that is marked as invalid.  Otherwise, we
             * need to create a new Model_User instance with the given $userId.
             */
            if (($user === null) || ($userId !== null))
                $user = Model_User::find($userId);

            /*
            Connexions::log("Bootstrap::_initUser: "
                            .   "User[ {$userId} ], pass[ {$pass} ], "
                            .   "User Model:\n"
                            .       $user->debugDump() );
            // */

            if ( $user->isBacked() && (! @empty($pass)) )
            {
                /* Perform authentication verification.
                 *
                 * Note: The Connexions_Auth adapter uses the
                 *       Model_User::authenticate method to verify credentials.
                 *       This ensures that the Model_User instance is properly
                 *       marked as authenticated or NOT authenticated in
                 *       addition to returning a valid Zend_Auth_Result.
                 */
                $adapter = new Connexions_Auth($user, $pass);
                $res     = $auth->authenticate($adapter);

                /*
                Connexions::log("Bootstrap::_initUser: "
                                .   "User authentication is"
                                .       ($res->isValid() ? "" : " NOT")
                                .   "valid/Authenticated, results:\n"
                                .   print_r($res, true));
                // */

                /*
                if (! $res->isValid())
                {
                    // Invalid password.
                    Connexions::log("Bootstrap::_initUser: "
                                    .   "User [ {$userId} ] "
                                    .   "NOT authenticated: [ "
                                    .       $res->getMessages() ." ], ".
                                    .   "user error[ {$user->getError()} ]");
                }
                // */
            }
            /*
            else
            {
                // Invalid user or missing password.
                Connexions::log("Bootstrap::_initUser: "
                                .   "User [ {$userId} ] "
                                .   "NOT authenticated: "
                                .   "User is"
                                .       ($user->isValid()  ? "" : " NOT")
                                .   " valid, is"
                                .       ($user->isBacked() ? "" : " NOT")
                                .   " backed, "
                                .   "password[ {$pass} ]");
            }
            // */
        }

        // /*
        Connexions::log(sprintf("Bootstrap::_initUser: "
                                .  "Final user '%s' is%s authenticated",
                                $user,
                                ($user->isAuthenticated() ? '':' NOT')) );
        // */

        /* Make it easy to retrieive user from anywhere.
         *
         * Without this, we would need to retrieve the current bootstrap and
         * the request the 'user' resource.
         *
         * From a Zend_Controller_Action:
         *      $this->getInvokeArg('bootstrap')->getResource('user');
         *      
         */
        Zend_Registry::set('user', $user);


        return $this;   //return $user;
    }

    protected function _viewAcl() //_initAcl()
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

        Zend_Registry::set('acl', $acl);

        return $this;   //return $acl;
    }

    protected function _viewRoute()   //_initRoute()
    {
        $front  = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();

        $route = new Connexions_Controller_Route();
        $router->addRoute('default', $route);

        Connexions::log('Bootstrap::Route initialized');

        return $this;   //return $route;
    }

    protected function _viewPlugins()
    {
        /*
        $front = Zend_Controller_Front::getInstance();

        // Register our authentication plugin (performs
        // identification/authentication during dispatchLoopStartup().
        $front->registerPlugin(new Connexions_Controller_Plugin_Auth());
        */

        /*
        $viewResource = new Connexions_Application_Resource_View();
        $this->registerPluginResource($viewResource);
        */

        /*
        $loader = $this->getPluginLoader();
        $loader->addPrefixPath('Connexions_Application_Resource_View',
                                    'Connexions/Application/Resource');
        */

        /* Register our Controller Action Helpers Prefix.
         *
         * This will make available all helpers in:
         *  library/Connexions/Controller/Action/Helper
         */
        Zend_Controller_Action_HelperBroker::addPrefix(
                                        'Connexions_Controller_Action_Helper');

        return $this;
    }
}
