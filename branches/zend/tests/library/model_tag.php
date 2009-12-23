<?php
require_once('./bootstrap.php');

$rec = Model_Tag::find(1);
echo "<pre>Record for 1:\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";

$tag = 'security';
$rec = Model_Tag::find($tag);
printf ("<pre>Record for '%s':\n", $tag);
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";

$recs = Model_Tag::fetchAll(); //array('userId' => 1));
printf ("<pre>All %d Records:\n", count($recs));
foreach ($recs as $idex => $rec)
{
    printf ("%d: %s\n", $idex, $rec->debugDump());
}
echo "</pre><hr />";


