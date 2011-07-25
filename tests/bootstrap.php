<?php
//error_reporting( E_ALL | E_STRICT );
ini_set('display_startup_errors', 1);
ini_set('display_errors',         1);
date_default_timezone_set('UTC');

defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', 'unitTests');

define('TESTS_PATH',       realpath(dirname(__FILE__)));
define('APPLICATION_PATH', realpath(TESTS_PATH .'/../application'));
define('LIBRARY_PATH',     realpath(TESTS_PATH .'/../library'));

$_SERVER['SERVER_NAME'] = 'http://localhost';

$includePaths = array(LIBRARY_PATH, APPLICATION_PATH, TESTS_PATH, get_include_path());
set_include_path(implode(PATH_SEPARATOR, $includePaths));

require_once 'Zend/Session.php';
Zend_Session::$_unitTestEnabled = true;
Zend_Session::start();

require_once 'Zend/Config/Ini.php';
require_once 'Zend/Application.php';
require_once 'Zend/Registry.php';
$config = new Zend_Config_Ini(
                    APPLICATION_PATH . '/configs/application.ini',
                    APPLICATION_ENV);
Zend_Registry::set('config', $config);

$application = new Zend_Application(APPLICATION_ENV, $config);
$application->bootstrap('common');

/*
require_once 'Zend/Loader/Autoloader.php';
$autoLoader = Zend_Loader_Autoloader::getInstance();

 */
