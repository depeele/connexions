<?php

require_once('Connexions.php');
require_once('Connexions/Autoloader.php');

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initSession()
    {
        Zend_Session::start();
    }

    protected function _initAutoload()
    {
        $autoLoader = Zend_Loader_Autoloader::getInstance();

        $connexionsLoader = new Connexions_Autoloader();
        $autoLoader->unshiftAutoloader($connexionsLoader);

        // Load ANY namespace
        $autoLoader->setFallbackAutoloader(true);

        return $autoLoader;

        /*
        $autoLoader = new Zend_Application_Module_Autoloader(array(
                            'namespace' => 'Default_',
                            'basePath'  => dirname(__FILE__)
                          ));

        return $autoLoader;
        */
    }

    protected function _initRoute()
    {
        // /*
        $config = Zend_Registry::get('config');
        $front  = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();

        if (true)
        {
        $route = new Connexions_Controller_Route();
        $router->addRoute('default', $route);
        }
        else if (false)
        {
        $router->addConfig($config, 'routes');

        }
        else if (false)
        {
        $configRouter = new Zend_Controller_Router_Rewrite();
        $configRouter->addConfig($config, 'routes');

        $router->addRoute('default', $configRouter);
        }
        else if (false)
        {
        $route2 = new Zend_Controller_Router_Route(
                        ':owner/*',
                        array('module'      => 'default',
                              'controller'  => 'index',
                              'action'      => 'index',
                              'owner'       => '')
                     );
        $route1 = new Zend_Controller_Router_Route(
                        ':controller/:action/:owner/*',
                        array('module'      => 'default',
                              'controller'  => 'index',
                              'action'      => 'index',
                              'owner'       => '')
                     );

        $route1->chain($route2);

        $router->addRoute('default', $route1);
        }
        else if (false)
        {
        $route = new Zend_Controller_Router_Route_Regex(
                    '(?:!(auth|people|network|tags|subscriptions|help|inbox))'.
                                '/*',
                        array('controller'  => 'index',
                              'action'      => 'index',
                              'owner'       => 'mine'),
                        array('owner'       => 1),
                        '%s'
                     );

        $router->addRoute('bookmarks', $route);
        }
        else if (false)
        {
        $route1 = new Zend_Controller_Router_Route_Static(
                    'people',
                        array('controller'  => 'people',
                              'action'      => 'index')
                     );
        $route2 = new Zend_Controller_Router_Route(
                    ':owner/:controller/*',
                        array('controller'  => 'index',
                              'action'      => 'index',
                              'owner'       => 'mine')
                     );

        $router->addRoute('people',  $route1)
               ->addRoute('route2',  $route2);
        }
        else if (false)
        {
        $route1 = new Zend_Controller_Router_Route(
                        'auth/:action/*',
                        array('controller'  => 'auth',
                              'action'      => 'index',
                              'owner'       => '')
                     );
        $route2 = new Zend_Controller_Router_Route(
                        'people/:action/*',
                        array('controller'  => 'people',
                              'action'      => 'index',
                              'owner'       => '')
                     );
        $route3 = new Zend_Controller_Router_Route(
                        'network/:owner/*',
                        array('controller'  => 'network',
                              'action'      => 'index',
                              'owner'       => 'mine')
                     );
        $route4 = new Zend_Controller_Router_Route(
                        'tags/:owner/*',
                        array('controller'  => 'tags',
                              'action'      => 'index',
                              'owner'       => 'mine')
                     );
        $route5 = new Zend_Controller_Router_Route(
                        'subscriptions/:owner/*',
                        array('controller'  => 'subscriptions',
                              'action'      => 'index',
                              'owner'       => 'mine')
                     );
        $route6 = new Zend_Controller_Router_Route(
                        'inbox/:owner/*',
                        array('controller'  => 'inbox',
                              'action'      => 'index',
                              'owner'       => 'mine')
                     );

        $route7 = new Zend_Controller_Router_Route(
                        'help/:action/*',
                        array('controller'  => 'help',
                              'action'      => 'index')
                     );
        $route8 = new Zend_Controller_Router_Route(
                        ':owner/*',
                        array('controller'  => 'index',
                              'action'      => 'index',
                              'owner'       => 'mine')
                     );

        $router->addRoute('auth',           $route1)
               ->addRoute('people',         $route2)
               ->addRoute('network',        $route3)
               ->addRoute('tags',           $route4)
               ->addRoute('subscriptions',  $route5)
               ->addRoute('inbox',          $route6)
               ->addRoute('help',           $route7);
        }
        // */
    }

    protected function _initDb()
    {
        $config = $this->getPluginResource('db');
        $db     = $config->getDbAdapter();

        Zend_Registry::set('db', $db);
        return $db;
    }

    protected function _initPlugins()
    {
        $front = Zend_Controller_Front::getInstance();

        /* Register our authentication plugin (performs
         * identification/authentication during dispatchLoopStartup.
         */
        $front->registerPlugin(new Connexions_Controller_Plugin_Auth());
    }

    protected function _initActionHelpers()
    {
        /* Register our Controller Action Helpers Prefix.
         *
         * This will make available all helpers in:
         *  library/Connexions/Controller/Action/Helper
         */
        Zend_Controller_Action_HelperBroker::addPrefix(
                                        'Connexions_Controller_Action_Helper');
    }

    protected function _initAcl()
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

        Zend_View_Helper_Navigation_HelperAbstract::setDefaultAcl($acl);
        Zend_View_Helper_Navigation_HelperAbstract::setDefaultRole('guest');

        /* Note: The proper role will be set in
         *       Connexions_Controller_Plugin_Auth during dispatchLoopStartup().
         */
    }

    /**************************************************************************
     * View-specific Bootstrapping -- the following Bootstrap methods can be
     * skipped via:
     *      $_GLOBALS['gNoView'] = true;
     */
    protected function _initViewGlobal()
    {
        if ($_GLOBALS['gNoView'] === true)
            return;

        $this->bootstrap('view');

        $view = $this->getResource('view');
        Zend_Registry::set('view', $view);

        return $view;
    }

    protected function _initDoctype()
    {
        if ($_GLOBALS['gNoView'] === true)
            return;

        $view = Zend_Registry::get('view');
        $view->doctype('XHTML1_STRICT');
    }

    protected function _initNavigation()
    {
        if ($_GLOBALS['gNoView'] === true)
            return;

        $config = new Zend_Config_Xml(
                                APPLICATION_PATH .'/configs/navigation.xml',
                                'nav');

        $nav    = new Zend_Navigation($config);

        /* Set the default navigation container:
         *  $view   = $this->getResource('view');
         *
         *  $view->getHelper('navigation')->setContainer($nav)
         *      OR
         *  $view->navigation($nav);
         *      OR
         *  Zend_Registry::set('Zend_Navigation', $nav);
         *
         *  We've placed the view in the registry for easier access.
         */
        $view = Zend_Registry::get('view');
        $view->navigation($nav);
    }
}
