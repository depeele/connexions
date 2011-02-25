<?php
require_once('./bootstrap.php');


/** Zend_Application */
require_once('Zend/Application.php');

// Create application, bootstrap, and run
$application = new Zend_Application(APPLICATION_ENV, $config);

$application->bootstrap()
            ->run();
