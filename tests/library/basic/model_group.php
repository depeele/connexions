<?php
require_once('./bootstrap.php');

$rec = Model_Group::find(1);
echo "<pre>Record for 1:\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");

if ($rec instanceof Connexions_Model)
{
    echo "\n";
    $owner = $rec->owner;
    echo "Owner:\n", $owner->debugDump();

    /*
    echo "\n";
    $members = $rec->members;
    echo "Members:\n", $members->debugDump();

    echo "\n";
    $items = $rec->items;
    echo "Items:\n", $items->debugDump();
    */
}

echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_Group::find(array('groupId' => 1));
echo "<pre>Record for groupId==1:\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_Group::find(array('groupId' => 1, 'name' => 'dep'));
echo "<pre>Record for groupId==1, name=='dep':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_Group::find(array('groupId' => 1, 'name' => 'abcdef'));
echo "<pre>Record for groupId==1, name=='abcdef':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_Group::find('dep');
echo "<pre>Record for 'dep':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_Group::find('abcdef');
echo "<pre>Record for 'abcdef':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_Group::find(array('invalidKey' => 123));
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
echo "<h3>Creation</h3>\n";
$rec = new Model_Group(array('name'  => 'Test Group', 'ownerId' => 1));
echo "<pre>Record for name=='Test Group':\n";
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
    echo "** FAILURE: ", $rec->getError() ,"\n";
echo "\n\n";

$rec->groupType = 'item';
echo "Modify 'groupType':\n";
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
    echo "** FAILURE: ", $rec->getError() ,"\n";
echo "\n\n";

// Invalid group type
$rec->lastVisit = 'invalid-type';
echo "Try to modify 'groupType' with an invalid value:\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "\n\n";


echo "<h3>Deletion</h3>\n";
$res = $rec->delete();
if ($res === true)
    echo "-- success\n";
else
    echo "** FAILURE: ", $rec->getError() ,"\n";

echo "</pre><hr />";
unset($rec);

/*****************************************************************************/
$groups = 'dep,sab,cag';
$data  = Model_Group::ids('dep,sab,cag');
echo "<pre>ID information for '{$groups}':\n";
print_r($data);
echo "</pre><hr />";
unset($data);
