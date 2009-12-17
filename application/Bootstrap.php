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
        $front  = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();

        $route = new Connexions_Controller_Route();
        $router->addRoute('default', $route);

        return $route;
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

        /* Put the 'view' in the registry so it will be accessible to
         *      Connexions_Controller_Plugin_Auth
         *
         * to set the ACL role used by view navigation.
         */
        Zend_Registry::set('view', $view);

        return $view;
    }

    protected function _initDoctype()
    {
        if ($_GLOBALS['gNoView'] === true)
            return;

        $view = $this->getResource('view'); //Zend_Registry::get('view');
        $view->doctype('XHTML1_STRICT');
    }

    protected function _initJQueryViewHelpers()
    {
        if ($_GLOBALS['gNoView'] === true)
            return;

        /*
        Zend_Controller_Action_HelperBroker::addPrefix(
                                        'ZendX_JQuery_View_Helper');
        */

        $view = $this->getResource('view'); //Zend_Registry::get('view');
        $view->addHelperPath("ZendX/JQuery/View/Helper",
                             "ZendX_JQuery_View_Helper");
    }

    protected function _initNavigation()
    {
        if ($_GLOBALS['gNoView'] === true)
            return;

        /* Use our router as the navigation provider
        $route = $this->getResource('route');
        $view  = $this->getResource('view');

        $view->navigation($nav);
        */

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
        $view = $this->getResource('view'); //Zend_Registry::get('view');
        $view->navigation($nav);
    }
}
