<?php
//error_reporting( E_ALL | E_STRICT );
//ini_set('display_startup_errors', 1);
//ini_set('display_errors',         1);
//date_default_timezone_set('UTC');

//define('APPLICATION_ENV', 'development');
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV')
                                    ? getenv('APPLICATION_ENV')
                                    : 'production'));

define('API_PATH',         realpath(dirname(__FILE__)));
define('APPLICATION_PATH', realpath(API_PATH         .'/../../application'));
define('LIBRARY_PATH',     realpath(APPLICATION_PATH .'/../library'));
define('MODEL_PATH',       realpath(APPLICATION_PATH .'/models'));

#$_SERVER['SERVER_NAME'] = 'http://localhost';

$includePaths = array(LIBRARY_PATH,
                      APPLICATION_PATH,
                      MODEL_PATH,
                      API_PATH,
                      get_include_path());
set_include_path(implode(PATH_SEPARATOR, $includePaths));

// Make the application configuration generally avaialble
require_once('Zend/Config/Ini.php');
require_once('Zend/Registry.php');

$config = new Zend_Config_Ini(
                    APPLICATION_PATH . '/configs/application.ini',
                    APPLICATION_ENV);

Zend_Registry::set('config', $config);


/** Zend_Application */
require_once('Zend/Application.php');

// Create application, bootstrap, and run
$application = new Zend_Application(APPLICATION_ENV, $config);

$application->bootstrap('common');
