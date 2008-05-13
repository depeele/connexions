<?php

// We'll be outputting CSS
header('Content-type: text/css');

include("../config.php");
require_once("../lib/ua.php");
$ua = ua_get();

include("tagging.css");

?>
