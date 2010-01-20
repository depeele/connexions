<?php
require_once('./bootstrap.php');

$rec = Model_Item::find(1);
echo "<pre>Record for 1:\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";

/*****************************************************************************/
$urlHash = 'cff6c93515995784e7217e9e3cedfd0d';
$rec = Model_Item::find($urlHash);
printf ("<pre>Record for '%s':\n", $urlHash);
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";
