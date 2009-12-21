<?php
require_once('./bootstrap.php');

$rec = Model_Tag::find(1);
echo "<pre>Record for item 1:\n";
print_r($rec->toArray());
echo "</pre>";

$tag = 'security';
$rec = Model_Tag::find($tag);
printf ("<pre>Record for tag '%s':\n", $tag);
print_r($rec->toArray());
echo "</pre>";

/*
$recs = Model_Tag::fetchAll(); //array('userId' => 1));
printf ("<pre>All %d Records:\n", count($recs));
foreach ($recs as $idex => $rec)
{
    printf ("%d: ", $idex);
    print_r($rec->toArray());
}
//print_r($recs);
echo "</pre>";
*/


