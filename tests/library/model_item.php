<?php
require_once('./bootstrap.php');

$rec = Model_Item::find(1);
echo "<pre>Record for item 1:\n";
print_r($rec->toArray());
echo "</pre>";

$urlHash = '383cb614a2cc9247b86cad9a315d02e3';
$rec = Model_Item::find($urlHash);
printf ("<pre>Record for urlHash '%s':\n", $urlHash);
print_r($rec->toArray());
echo "</pre>";

/*
$recs = Model_Item::fetchAll(); //array('userId' => 1));
printf ("<pre>All %d Records:\n", count($recs));
foreach ($recs as $idex => $rec)
{
    printf ("%d: ", $idex);
    print_r($rec->toArray());
}
//print_r($recs);
echo "</pre>";
*/

