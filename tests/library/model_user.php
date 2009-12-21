<?php
require_once('./bootstrap.php');

$rec = Model_User::find(1);
echo "<pre>Record for userId 1:\n";
print_r($rec->toArray());
echo "</pre>";

$rec = Model_User::find('elmo');
echo "<pre>Record for userId 'elmo':\n";
print_r($rec->toArray());
echo "</pre>";

$recs = Model_User::fetchAll(); //array('userId' => 1));
printf ("<pre>All %d Records:\n", count($recs));
foreach ($recs as $idex => $rec)
{
    printf ("%d: ", $idex);
    print_r($rec->toArray());
}
//print_r($recs);
echo "</pre>";
