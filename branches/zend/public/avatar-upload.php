<?php

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
define('APPLICATION_ENV', 'development');
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV')
                                    ? getenv('APPLICATION_ENV')
                                    : 'production'));

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

/*
echo "<pre>";
echo "_SERVER[ "; print_r($_SERVER); echo " ]\n\n";
echo "config[ "; print_r($config); echo " ]\n\n";
echo "</pre>\n";
exit;
// */

/*****************************************************************************
 * Handle the upload
 *
 */
$public       = dirname($_SERVER['SCRIPT_FILENAME']);
$urlBase      = $config->urls->base;
$urlAvatar    = $config->urls->avatar;
$urlAvatarTmp = $config->urls->avatarTmp;

$uploadDir    = realpath($public .'/'.
                            preg_replace("#^{$urlBase}#", '', $urlAvatarTmp) );
$avatarFile   = basename( $_FILES['avatarFile']['name']) ;
//if (empty($avatarFile)) $avatarFile = 'img.jpg';
$uploadFile   = $uploadDir .'/'. $avatarFile;
$uploadUrl    = $urlAvatarTmp .'/'. $avatarFile;

/*
echo "<ul>";
printf(  "<li>public:     %s</li>\n"
       . "<li>urlBase:    %s</li>\n"
       . "<li>uploadDir:  %s</li>\n"
       . "<li>avatarFile: %s</li>\n"
       . "<li>uploadFile: %s</li>\n"
       . "<li>uploadUrl:  %s</li>\n",
       $public,
       $urlBase,
       $uploadDir,
       $avatarFile,
       $uploadFile,
       $uploadUrl);
echo "</ul>\n";
exit;
// */

if(move_uploaded_file($_FILES['avatarFile']['tmp_name'], $uploadFile))
{
    echo '<div id="status">success</div>';
    echo '<div id="url">'. $uploadUrl .'</div>';
    echo '<div id="message">Avatar uploaded<div>';
} else {
    echo '<div id="status">failed</div>';
    echo '<div id="message">Avatar upload failed</div>';
}

