<?php

//error_reporting(E_ALL);

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
             ->_commonLogging()
             ->_commonDb()
             ->_commonAuth()
             ->_commonRequest()
             ->_commonPlugins();
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
        //$acl  = Zend_Registry::get('acl'); //$this->getResource('acl');
        $acl = $this->getResource('acl');

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
        $user = $this->getResource('user'); //Zend_Registry::get('user');
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
        Zend_Session::start();

        return $this;
    }

    /** @brief  Initialize autoloading. */
    protected function _commonAutoload()
    {
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

        Connexions::log('Bootstrap::Logging initialized');

        return $this;
    }

    /** @brief  Initialize the databsae. */
    protected function _commonDb()
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

        /* Make this available via the global Registry and as a Bootstrap
         * Resource
         */
        Zend_Registry::set('db', $db);
        $this->setResource('db', $db);

        //Connexions::log('Bootstrap::Database initialized');

        return $this;
    }

    /** @brief  Initialize authentication. */
    protected function _commonAuth()
    {
        // Initialize authentication to use session-based storage
        $auth = Zend_Auth::getInstance();
        $auth->setStorage(new Zend_Auth_Storage_Session('connexions', 'user'));

        // See if there is a user currently identified
        $userId = $auth->getIdentity();

        /*
        Connexions::log("Bootstrap::_commonAuth: "
                                . "UserId from session [ "
                                .   print_r($userId, true) ." ]");
        // */

        $user = null;
        if ($userId !== null)
        {
            // Does the identity represent a valid user?
            //$user = Model_User::find($userId);
            $user = new Model_User( $userId );

            /*
            Connexions::log("Bootstrap::_commonAuth: userId[{$userId}], "
                                . "User Model:\n"
                                .   $user->debugDump());
            // */

            if ($user->isBacked())
            {
                /* We have a valid user identified in our session-based
                 * authentication store -- consider them authenticated.
                 */

                /*
                Connexions::log(sprintf("Bootstrap::_commonAuth: "
                                        .   "Initially Authenticated as "
                                        .       "[ %s ]",
                                        $user) );
                // */

                $user->setAuthenticated();
            }
            else
            {
                /* Invalid user identified in our session-based authentication
                 * store -- clear the identity.
                 */

                // /*
                Connexions::log("Bootstrap::_commonAuth: "
                                .   "Invalid identity [ {$userId} ] - CLEAR");
                // */

                $auth->clearIdentity();

                unset($user);
                $user = null;
            }
        }

        if ($user === null)
        {
            /* :TODO: Any Transport-level / Atomic
             *          Identification & Authentication should occur here,
             *          storing the identity in the global Auth instance:
             *              Zend_Auth::getInstance()->setIdentity( $id );
             *
             *  Example:
             *      $res = $auth->authenticate(new Connexions_Auth_ApacheSsl());
             *      if ($res->isValid())
             *          $user = $res->getUser();
             *      else
             *          Create an 'anonymous', unauthenticated user
             */

            // Create an 'anonymous', unauthenticated user
            $user = new Model_User(array(
                            '@isBacked' => false,
                            '@isRecord' => true,
                            'name'      => 'anonymous',
                            'fullName'  => 'Anonymous'
                        ));
        }

        // /*
        Connexions::log(sprintf("Bootstrap::_commonAuth: "
                                .  "Final user '%s' is%s authenticated",
                                $user,
                                ($user->isAuthenticated() ? '':' NOT')) );
        // */

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

        return $this;
    }

    /** @brief  Initialize the incoming request, assigning it to the front
     *          controller.
     */
    protected function _commonRequest()
    {
        $front   = Zend_Controller_Front::getInstance();
        $request = $front->getRequest();
        if ($request === null)
        {
            /* We don't already have a request assigned so create one ASSUMING
             * HTTP.
             */
            $request = new Zend_Controller_Request_Http();
            $front->setRequest($request);
        }

        // Make the request available as a Bootstrap Resource
        $this->setResource('request', $request);

        return $this;
    }

    /** @brief  Initialize common, pre-view plugins and helpers. */
    protected function _commonPlugins()
    {
        /*
        $front = Zend_Controller_Front::getInstance();

        // Register our authentication plugin (performs
        // identification/authentication during dispatchLoopStartup().
        $front->registerPlugin(new Connexions_Controller_Plugin_Auth());
        */
    }


    /*******************************************
     * For Views
     *
     */

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

    /** @brief  Initialize view-related plugins and helpers. */
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
