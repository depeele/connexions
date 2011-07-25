<?php
require_once('./bootstrap.php');

echo "<pre>";
echo "<h3>Model_Dao_UserItem tests</h3>";

// Our primary
$table_userItem  = new Model_Dao_UserItem();

/*****************************************************************************/
echo "table->find(1, 2)...\n";
$userItems = $table_userItem->find( 1, 3 );

printf ("userItem(s): 1,1, %d rows:\n", count($userItems));
foreach ($userItems as $userItem)
{
    echo   "- Item: ";
    print_r($userItem->toArray());
}
echo "<hr />";

/*****************************************************************************/
echo "table->fetchRow( where )...\n";
$select   = $table_userItem->select()->where('userId=?', 1 )
                                     ->where('itemId=?', 3 );
printf ("sql[ %s ]...\n", $select->assemble());
$userItem = $table_userItem->fetchRow( $select );

printf ("userItem: %d,%d: [ %s ]\n", $userItem->itemId, $userItem->userId,
                                     get_class($userItem));
print_r($userItem->toArray());
echo "<hr />";

echo "row->findParentRow( 'Model_Dao_User' )...\n";
$user =  $userItem->findParentRow('Model_Dao_User');
echo "User Info:\n";
print_r($user->toArray());
echo "<hr />";

$userItems = $user->findDependentRowset('Model_Dao_UserItem');
printf ("%d userItems for user %d: first 10\n", count($userItems),
                                                $user->userId);
foreach ($userItems as $idex => $tmpUserItem)
{
    printf (" UserItem %2d: ", $idex);
    print_r($tmpUserItem->toArray());
    echo "\n";

    if ($idex >= 9)
        break;
}
echo "<hr />";

echo "row->findParentRow( 'Model_Dao_Item' )...\n";
$item =  $userItem->findParentRow('Model_Dao_Item');
echo "Item Info:\n";
print_r($item->toArray());
echo "<hr />";

printf ("Find Tags for userItem %d,%d...\n",
        $userItem->userId, $userItem->itemId);
$tags =  $userItem->findManyToManyRowset('Model_Dao_Tag',
                                         'Model_Dao_UserTagItem');
printf ("%d tags, first 10:\n", count($tags));
foreach ($tags as $idex => $tag)
{
    printf (" Tag %2d: ", $idex);
    print_r($tag->toArray());
    echo "\n";

    if ($idex >= 9)
        break;
}
echo "<hr />";

$users = $item->findManyToManyRowset('Model_Dao_User',
                                     'Model_Dao_UserItem');
printf ("%d users for item %d: first 10\n", count($users), $item->itemId);
foreach ($users as $idex => $user)
{
    printf (" User %2d: ", $idex);
    print_r($user->toArray());
    echo "\n";

    if ($idex >= 9)
        break;
}
echo "<hr />";

/*****************************************************************************/
$select    = $table_userItem->select()
                        ->order('taggedOn DESC')
                        ->limit(10);

printf ("table->fetchAll( %s )...\n", $select->assemble());
$userItems = $table_userItem->fetchAll( $select );

printf ("%d userItems:", count($userItems));
foreach ($userItems as $idex => $userItem)
{
    printf (" Item %2d: ", $idex);
    print_r($userItem->toArray());
    echo "\n";
}
echo "<hr />";

//print_r($userItem);

/*
$userItems = $user->findDependentRowset('App_Model_Dao_UserItem');
$user      = $userItem->findParentRow();

$users     = $item->findManyToManyRowset('App_Model_Dao_User',
                                         'App_Model_Dao_UserItem');
*/
