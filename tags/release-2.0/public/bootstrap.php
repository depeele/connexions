<?php
/** @file
 *
 *  The basics required to bootstrap any directly accessible portion of a Zend
 *  application.
 */

// Define application environment
define('APPLICATION_ENV', 'development');
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV')
                                    ? getenv('APPLICATION_ENV')
                                    : 'production'));

// This directory SHOULD be the directly accessible portion of the app.
defined('APPLICATION_WEBROOT')
    || define('APPLICATION_WEBROOT', dirname( __FILE__ ) );

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH',
              realpath(APPLICATION_WEBROOT .'/../application'));

define('LIBRARY_PATH',  realpath(APPLICATION_PATH .'/../library'));
define('MODEL_PATH',    realpath(APPLICATION_PATH .'/models'));

$includePaths = array(LIBRARY_PATH,
                      MODEL_PATH,
                      //APPLICATION_PATH,
                      get_include_path());

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, $includePaths));

// Make the application configuration generally avaialble
require_once('Zend/Config/Ini.php');
require_once('Zend/Registry.php');

$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini',
                              APPLICATION_ENV);

Zend_Registry::set('config', $config);
