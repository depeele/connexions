<?php
require_once('./bootstrap.php');

echo "<pre>";
echo "<h3>Model_Bookmark tests</h3>";

$mapper = new Model_BookmarkMapper( );

$id       = array('userId' => 1, 'itemId' => 1);
$bookmark = $mapper->find( $id );

printf ("Bookmark for id[ %s ]: ",
        Connexions::varExport($id));
if ($bookmark instanceof Connexions_Model)
{
    echo $bookmark->debugDump(), "\n";

    $user = $bookmark->user;
    if ($user)
        echo "User: ", $user->debugDump(), "\n";
    else
        echo " *** ERROR retrieving User\n";

    $item = $bookmark->item;
    if ($item)
        echo "Item: ", $item->debugDump(), "\n";
    else
        echo " *** ERROR retrieving Item\n";

    $tags = $bookmark->tags;
    if ($tags)
        echo "Tags: ", $tags->debugDump(), "\n";
    else
        echo " *** ERROR retrieving Tags\n";
}
else
{
    echo " *** ERROR\n";
}
echo "<hr />\n";

die;

/*****************************************************************************/
$bookmark->taggedOn  = date('Y-m-d H:i:s');
$bookmark->isPrivate = (! $bookmark->isPrivate);
echo 'Save (should be an update with new "taggedOn" and toggled "isPrivate"):',
     "\n";
$res        = $bookmark->save();
if ($res === true)
{
    echo "-- success\n";
    $bookmark->debugDump();
}
else
{
    echo "** FAILURE\n";
}
echo "\n\n\n";

printf ("taggedOn:     %s\n", $bookmark->taggedOn);
printf ("isPrivate:    %s\n", ($bookmark->isPrivate ? 'true' : 'false'));
printf ("userId:       %d\n", $bookmark->userId);
printf ("user->userId: %d : %d\n", $bookmark->user->userId,
                                   $bookmark->user_userId);
printf ("user->name:   %s : %s\n", $bookmark->user->name,
                                   $bookmark->user_name);
printf ("item->url:    %s : %s\n", $bookmark->item->url,
                                   $bookmark->item_url);
printf ("tags[0]->tag: %s\n", $bookmark->tags[0]->tag);

unset($bookmark);
echo "<hr />\n";

/*****************************************************************************/
