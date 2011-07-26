<?php
// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH',
                realpath(dirname(__FILE__) . '/../../application'));

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

$user = $pass = null;
if ( $argc > 1)
{
    /*
    echo "<pre>argv:\n";
    print_r($argv);
    echo "</pre>\n";
    // */

    // See if '-u=<users>' or '-p=<password>' are in argv
    for ($idex = 1; $idex < $argc; $idex++)
    {
        if (preg_match('/^\s*-(u|p)=(.*?)\s*$/', $argv[$idex], $matches))
        {
            switch ($matches[1])
            {
            case 'u':
                $user = $matches[2];
                break;

            case 'p':
                $pass = $matches[2];
                break;
            }
        }
    }
}

/*
echo "<pre>_GET:\n";
print_r($_GET);
echo "</pre>\n";
die;
// */

/** Zend_Application */
require_once('Zend/Application.php');

// Create application and perform non-view-related bootstrapping.
$application = new Zend_Application(APPLICATION_ENV, $config);

$application->bootstrap('common');

if (($user !== null) && ($pass !== null))
{
    // Attempt to authenticate
    $_POST['username'] = $user;
    $_POST['password'] = $pass;

    $auth        = Zend_Auth::getInstance();
    $authAdapter = new Connexions_Auth_UserPassword();
    $authResult  = $auth->authenticate($authAdapter);

    if (! $authResult->isValid())
    {
        printf ("*** Invalid user/pass [ %s / %s ]\n", $user, $pass);
    }
    else
    {
        $user = $authResult->getUser();

        printf ("--- Authenticated as:\n%s\n\n", $user->debugDump());
        Zend_Registry::set('user', $user);
    }
}

function db_profile_output()
{
    $db       = Zend_Registry::get('db');
    $profiler = $db->getProfiler();

    if ((! $profiler instanceof Zend_Db_Profiler) ||
        ($profiler->getEnabled() !== true) )
    {
        //return ("Profiler disabled");
        return ('');
    }

    $totalTime    = $profiler->getTotalElapsedSecs();
    $totalQueries = $profiler->getTotalNumQueries();
    $longest      = null;

    $profiles = $profiler->getQueryProfiles();

    if (! $profiles)
        return ('');

    $times    = array();
    $queries  = array();
    foreach ($profiles as $query)
    {
        $time = $query->getElapsedSecs();

        array_push($queries, array('time'   => $time,
                                   'query'  => $query));
        array_push($times, $time);
    }

    array_multisort($times, SORT_DESC, $queries);

    $topCnt = min($totalQueries, 5);

    $html =  "<div class='db-profile'>"
          .   "<h3>Database Profile</h3>"
          .   "<ul>"
          .    sprintf ("<li>Executed %d queries in %f seconds, "
                        .    "average %f seconds/query, "
                        .    "%f queries/second</li>",
                        $totalQueries, $totalTime,
                        $totalTime    / $totalQueries,
                        $totalQueries / $totalTime)
          .    sprintf ("<li>Longest %d queries:<dl>",  $topCnt);
    
    for ($idex = 0; $idex < $topCnt; $idex++)
    {
        $query =& $queries[$idex]['query'];
        $html .= sprintf (  "<dt>%d: %10f seconds</dt>"
                          . "<dd>%s</dd>",
                          $idex + 1,
                          $queries[$idex]['time'],
                          $query->getQuery());

    }
    $html .=    "</dl>"
          .    "</li>"
          .   "</ul>"
          .  "</div>";
    
    echo $html;
}
