<?php
require_once('./bootstrap.php');

echo "<pre>";
echo "<h3>Model_UserSet tests</h3>";

$set        = new Model_UserSet();

printf ("%s records:\n",
        number_format( count($set) ));
if (! $set instanceof Connexions_Set)
    echo " *** ERROR: Wrong Set class (". get_class($set) .")\n";
else
{
    if ($set->hasError())
    {
        echo "*** Set ERROR:\n";
        print_r($set->getError());
    }

    echo "Record #1:\n";

    $rec = $set[0];

    echo ($rec instanceof Connexions_Model
            ? $rec->debugDump()
            : "*** ERROR: Wrong Record class (". get_class($rec) .")\n");
}
echo "\n\n";

/*****************************************************************************/
$order = array('lastVisit', 'totalItems DESC');
echo "New ordering: '", implode(', ', $order) ,"':\n";
$set->setOrder($order);

echo "SQL: ", $set->select()->assemble() ,"\n";

printf ("%s records:\n",
        number_format( count($set) ));
if (! $set instanceof Connexions_Set)
    echo " *** ERROR: Wrong Set class (". get_class($set) .")\n";
else
{
    if ($set->hasError())
    {
        echo "*** Set ERROR:\n";
        print_r($set->getError());
    }

    echo "Record #1:\n";

    $rec = $set[0];

    echo ($rec instanceof Connexions_Model
            ? $rec->debugDump()
            : "*** ERROR: Wrong Record class (". get_class($rec) .")\n");
}
echo "\n\n";

/*****************************************************************************/
$users    = 'dep';
$userInfo = new Connexions_Set_Info($users, 'Model_User');

$order    = array('weight DESC');
$weightBy = 'totalItems';
echo "New ordering '", implode(', ', $order) ,"', weightBy '{$weightBy}':\n";
$set->weightBy($weightBy);
$set->setOrder($order);

printf ("Select SQL[ %s ]\n", $set->select()->assemble());

$userList = $set->get_Tag_ItemList($userInfo, '/', 0, 100);
foreach ($userList as $user)
{
    printf ("%4d: '%s'\n", $user->weight, $user->name);
}
