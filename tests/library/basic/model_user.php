<?php
require_once('./bootstrap.php');

echo "<pre>";
echo "<h3>Model_User tests</h3>";

/*****************************************************************************/
$user = new Model_User(array('name'     => 'test_user',
                             'fullName' => 'Test User',
                             /*'tags'     => 'abc,def,hij'*/));
echo $user->debugDump(), "\n";

$mapper = $user->getMapper();
printf ("Model_User: mapper[ %s ]\n", get_class($mapper));
echo "<hr />";

/*****************************************************************************/
try
{
    $user = new Model_User(array('name'     => 'test_user',
                                 'fullName' => 'Test User',
                                 'tags'     => 'abc,def,hij'));

    echo "*** ERROR: Created a new Model_User with invalid 'tags' object\n";
}
catch (Exception $e)
{
    echo "=== SUCCESS: Cannot create a Model_User with invalid 'tags' object\n";
}
echo "<hr />";

/*****************************************************************************/
//$mapper = new Model_UserMapper( array('dao' => new Model_DbTable_User()) );
//$mapper = new Model_Mapper_User();  //UserMapper( );

$id   = 1;
$user = $mapper->find( $id );

printf ("User matching id '%s': ", $id);
if ($user instanceof Connexions_Model)
{
    echo $user->debugDump();

    /*
    $tags = $user->tags;
    $idex = 0;
    printf ("\n%d tags; First 5: ", count($tags));
    foreach ($tags as $tag)
    {
        if ($idex++ >= 5)
            break;

        printf ("%d: id[%d] '%s', ", $idex, $tag->tagId, $tag);
    }
    echo "\n";

    $userItems = $user->userItems;
    $idex = 0;
    printf ("\n%d user-items; First 5: ", count($userItems));
    foreach ($userItems as $item)
    {
        if ($idex++ >= 5)
            break;

        printf ("%d: id[%d, %d] '%s', ",
                $idex, $item->userId, $item->itemId, $item);
    }
    echo "\n";
    // */       
}
else
{
    echo " *** ERROR\n";
}
echo "<hr />";
unset($user);

/*****************************************************************************/
$id   = array('userId' => 1);
$user = $mapper->find( $id );
printf ("User matching [ %s ]: %s\n",
        Connexions::varExport($id), $user->debugDump());
echo "<hr />";
unset($rec);

/*****************************************************************************/
$id   = array('userId' => 1, 'name' => 'dep');
$user = $mapper->find( $id );
printf ("User matching [ %s ]: %s\n",
        Connexions::varExport($id), $user->debugDump());
echo "<hr />";
unset($rec);

/*****************************************************************************/
$id   = array('userId' => 1, 'name' => 'abcdef');
$user = $mapper->find( $id );
printf ("User matching [ %s ]: %s\n",
        Connexions::varExport($id), $user->debugDump());
echo "<hr />";
unset($rec);

/*****************************************************************************/
$id    = array('userId' => array(1,2,4,8,16));
$users = $mapper->fetch( $id );
printf ("%d Users matching [ %s ]:\n",
        count($users), Connexions::varExport($id));

foreach ($users as $idex => $user)
{
    printf (" #%2d: %s\n------------------\n",
            $idex, $user->debugDump());
}

echo "<hr />";
unset($rec);

die;

/*****************************************************************************/
$rec = Model_User::find('dep');
echo "Record for 'dep':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "<hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_User::find('abcdef');
echo "Record for 'abcdef':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "<hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_User::find(91827371);
echo "Record for '91827371':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "<hr />";
unset($rec);

/*****************************************************************************/
$rec = Model_User::find(array('invalidKey' => 123));
echo "Record for invalidKey==123:\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "\n\n";

$rec->name = 'abc';
echo "Set 'name':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "\n\n";

$rec->invalidField = 'def';
echo "Set 'invalidField':\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");

echo "<hr />";
unset($rec);

/*****************************************************************************/
$rec = new Model_User(array('name'  => 'Test User'));
echo "Record for name=='Test User':\n";
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

echo "<hr />";
unset($rec);

/*****************************************************************************/
$users = 'dep,sab,cag';
$data  = Model_User::ids('dep,sab,cag');
echo "ID information for '{$users}':\n";
print_r($data);
echo "<hr />";
unset($data);
