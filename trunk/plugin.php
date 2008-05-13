<?php
$gAppDir = dirname(__FILE__);

require_once('config.php');
require_once('lib/tagging.php');
require_once('lib/plugin.php');

define('BASE_URL', $gBaseurl);
define('BASE_DIR', $gAppDir.'/');

$gPluginDispatcher->addRoute($gRoutes);

$gPluginDispatcher->dispatch($gAppDir, BASE_URL,
                             (isset($_GET['__route__'])
                                ? '/'.$_GET['__route__']
                                : '/'));
?>
