<?php
require_once('./bootstrap.php');

$rec = Model_User::find(1);
echo "<pre>Record for 1:\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_User::find(array('userId' => 1));
echo "<pre>Record for userId==1:\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_User::find(array('userId' => 1, 'name' => 'dep'));
echo "<pre>Record for userId==1, name=='dep':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_User::find(array('userId' => 1, 'name' => 'abcdef'));
echo "<pre>Record for userId==1, name=='abcdef':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_User::find('dep');
echo "<pre>Record for 'dep':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_User::find('abcdef');
echo "<pre>Record for 'abcdef':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_User::find(91827371);
echo "<pre>Record for '91827371':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_User::find(array('invalidKey' => 123));
echo "<pre>Record for invalidKey==123:\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "\n\n";

$rec->name = 'abc';
echo "Set 'name':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "\n\n";

$rec->invalidField = 'def';
echo "Set 'invalidField':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");

echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = new Model_User(array('name'  => 'Test User'));
echo "<pre>Record for name=='Test User':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "\n\n";

echo "Save (should be an insert):\n";
$res = $rec->save();
if ($res === true)
{
    echo "-- success\n";
    echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
}
else
    echo "** FAILURE\n";
echo "\n\n";

$rec->lastVisit = date('Y-m-d H:i:s');
echo "Modify 'lastVisit':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "\n\n";

echo "Save (should be an update):\n";
$res = $rec->save();
if ($res === true)
{
    echo "-- success\n";
    echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
}
else
    echo "** FAILURE\n";
echo "\n\n";

// Invalid date
$rec->lastVisit = date('Y-M-D H:i:s');
echo "Try to modify 'lastVisit' with an invalid value:\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "\n\n";


echo "Delete:\n";
$res = $rec->delete();
if ($res === true)
    echo "-- success\n";
else
    echo "** FAILURE\n";

echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$recs = Model_User::fetchAll(); //array('userId' => 1));
printf ("<pre>All %d Records:\n", count($recs));
foreach ($recs as $idex => $rec)
{
    printf ("%d: %s\n", $idex, $rec->debugDump());
}
echo "</pre><hr />";
