<?php
// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH',
                realpath(dirname(__FILE__) . '/../../../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV',
              (getenv('APPLICATION_ENV')
                ? getenv('APPLICATION_ENV')
                : 'testing'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    realpath(APPLICATION_PATH . '/models'),
    get_include_path(),
)));

// Make the application configuration generally avaialble
require_once('Zend/Config/Ini.php');
require_once('Zend/Registry.php');

$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini',
                              APPLICATION_ENV);

Zend_Registry::set('config', $config);

/***************************************************************************
 * Setup the Timezone.
 *
 */
$zone = $config->get('timezone', 'PST');

date_default_timezone_set($zone);

/***************************************************************************
 * Setup the Autoloader.
 *
 */
require_once('Zend/Loader/Autoloader.php');
require_once('Connexions.php');
require_once('Connexions/Autoloader.php');

$autoLoader = Zend_Loader_Autoloader::getInstance();

$connexionsLoader = new Connexions_Autoloader();
$autoLoader->unshiftAutoloader($connexionsLoader);

// Load ANY namespace
$autoLoader->setFallbackAutoloader(true);

Zend_Session::start();

/***************************************************************************
 * Setup logging.
 *
 */

$logConfig = $config->resources->log;
$logger    = Zend_Log::factory($logConfig);

Zend_Registry::set('log', $logger);

Connexions::log("Test Logging initialized");


/***************************************************************************
 * Setup a Database connection.
 *
 */
$db  = Zend_Db::factory($config->resources->db);

try
{
    $db->getConnection();
}
catch (Zend_Db_Adapter_Exception $e)
{
    /* perhaps a failed login credential, or perhaps the RDBMS is not
     * running
     */
    die("*** Database error: Failed to login or DB not accessible");
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
