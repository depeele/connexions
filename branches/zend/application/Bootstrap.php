<?php

require_once('Connexions.php');
require_once('Connexions/Autoloader.php');

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initAutoload()
    {
        $autoLoader = new Connexions_Autoloader();
        return $autoLoader;


        $autoLoader = Zend_Loader_Autoloader::getInstance();

        $connexionsLoader = new Connexions_Autoloader();
        $autoloader->unshiftAutoloader($connexionsLoader);

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

    protected function _initDb()
    {
        $config = $this->getPluginResource('db');
        $db     = $config->getDbAdapter();

        Zend_Registry::set('db', $db);
        return $db;
    }

    protected function _initUser()
    {
        $auth = Zend_Auth::getInstance();
        $auth->setStorage(new Zend_Auth_Storage_Session('connexions', 'user'));
        $id   = $auth->getIdentity();
        if ($id !== null)
        {
            printf ("Initially Authenticated as [ %s ]<br />\n",
                    print_r($id,true));
            Zend_Registry::set('user', $id);
            return $id;
        }

        // Do we have identity information?
        $id      = false;
        $request = new Zend_Controller_Request_Http();
        $user    = $request->getParam('user');
        $pass    = $request->getParam('password');

        //printf ("_initUser: user[ %s ], pass[ %s ]<br />\n", $user, $pass);

        if ( (! @empty($user)) && (! @empty($pass)) )
        {
            // Is the identity information valid?
            $authAdapter = new Zend_Auth_Adapter_DbTable(
                                    Zend_Registry::get('db'),
                                    'user',
                                    'name', 'password', 'MD5(?)');

            $authAdapter->setIdentity($user);
            $authAdapter->setCredential($pass);

            $res = $authAdapter->authenticate();
            if ($res->isValid())
            {
                // Valid Identity -- authenticated
                echo "Authenticated<br />\n";
                $id = $res->getIdentity();
            }
            else
            {
                // INVALID Identity -- NOT authenticated
                echo "<pre>NOT authenticated\n";
                foreach ($res->getMessages() as $msg)
                {
                    echo $msg ,"\n";
                }
                echo "</pre>\n";
            }
        }

        Zend_Registry::set('user', $id);
        return $id;
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

        if (Zend_Registry::get('user') !== false)
        {
            Zend_View_Helper_Navigation_HelperAbstract::setRole('member');
        }
    }

    /**************************************************************************
     * View-specific Bootstrapping -- the following Bootstrap methods can be
     * skipped via:
     *      $_GLOBALS['gNoView'] = true;
     */
    protected function _initDoctype()
    {
        if ($_GLOBALS['gNoView'] === true)
            return;

        $this->bootstrap('view');

        $view = $this->getResource('view');
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

        Zend_Registry::set('Zend_Navigation', $nav);
    }
}

