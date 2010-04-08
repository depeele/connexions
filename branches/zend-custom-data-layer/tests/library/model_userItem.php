<?php
require_once('./bootstrap.php');

echo "<pre>";
echo "<h3>Model_UserItem tests</h3>";

$userItem   = Model_UserItem::find(array('userId'  => 1,
                                         'itemId'  => 1));
if ($userItem instanceof Connexions_Model)
{
    echo "Record for userId==1, itemId==1:\n";
    echo $userItem->debugDump();
}
else
{
    echo " *** ERROR\n";
}
echo "<hr />\n";

/*****************************************************************************/
$userItem->taggedOn  = date('Y-m-d H:i:s');
$userItem->isPrivate = (! $userItem->isPrivate);
echo 'Save (should be an update with new "taggedOn" and toggled "isPrivate"):',
     "\n";
$res        = $userItem->save();
if ($res === true)
{
    echo "-- success\n";
    $userItem->debugDump();
}
else
{
    echo "** FAILURE\n";
}
echo "\n\n\n";

printf ("taggedOn:     %s\n", $userItem->taggedOn);
printf ("isPrivate:    %s\n", ($userItem->isPrivate ? 'true' : 'false'));
printf ("userId:       %d\n", $userItem->userId);
printf ("user->userId: %d : %d\n", $userItem->user->userId,
                                   $userItem->user_userId);
printf ("user->name:   %s : %s\n", $userItem->user->name,
                                   $userItem->user_name);
printf ("item->url:    %s : %s\n", $userItem->item->url,
                                   $userItem->item_url);
printf ("tags[0]->tag: %s\n", $userItem->tags[0]->tag);

unset($userItem);
echo "<hr />\n";

/*****************************************************************************/
$tagIds  = array(5,6);
$userIds = array(1,2,441);
$itemIds = array();

$userItems = new Model_UserItemSet($tagIds, $userIds, $itemIds);

printf ("%d Set Record(s) from users (%s) with tags (%s):\n",
            $userItems->count(),
            implode(', ', $userIds),
            //implode(', ', $tagIds));
            implode(', ', $userItems->tagIds()));
foreach ($userItems as $idex => $userItem)
{
    printf ("Record %d, ", $idex);
    if ($userItem instanceof Connexions_Model)
    {
        echo " Instance: ", $userItem->debugDump();
    }
    else
    {
        echo " Array: ";
        print_r($userItem);
    }
    unset($userItem);
    echo "\n------------------------------------\n";
}
unset($userItems);
echo "<hr />";

/*****************************************************************************/
/*
$time_start = microtime(true);
$mem_start  = memory_get_usage();
$recs       = Model_UserItem::fetch($tagIds,          // tagIds
                                    $userIds,         // userIds
                                    null,             // itemIds
                                    false);           // asArray
$mem_end    = memory_get_usage();
$time_end   = microtime(true);

printf ("<pre>%d Record(s) from users (%s) with tags (%s), %f seconds, %s bytes:\n",
            count($recs),
            implode(', ', $userIds),
            implode(', ', $tagIds),
            $time_end - $time_start,
            number_format($mem_end  - $mem_start));
foreach ($recs as $idex => $rec)
{
    printf ("Record %d, ", $idex);
    if ($rec instanceof Connexions_Model)
    {
        echo " Instance: ", $rec->debugDump();
    }
    else
    {
        echo " Array: ";
        print_r($rec);
    }
    unset($rec);
    echo "\n------------------------------------\n";
}

$mem_end    = memory_get_usage();
$time_end   = microtime(true);
printf ("-- Retrieval + Iteration: %f seconds, %s bytes:\n",
            $time_end - $time_start,
            number_format($mem_end  - $mem_start));

unset($recs);
echo "</pre><hr />";
// */

/*****************************************************************************/
/*
$select = Model_UserItem::select();
printf ("Select sql:<blockquote>%s</blockquote>",
        $select->assemble());

$time_start = microtime(true);
$mem_start  = memory_get_usage();
$recs       = new Connexions_Set($select);
$mem_end    = memory_get_usage();
$time_end   = microtime(true);

printf ("<pre>All %d Records retrieved, %f seconds, %s bytes:\n",
        $recs->count(),
        $time_end - $time_start,
        number_format($mem_end  - $mem_start));

set_time_limit(0);
foreach ($recs as $idex => $rec)
{
    printf ("%d: %s\n", $idex, $rec->debugDump(true));
    unset($rec);

    $mem_end    = memory_get_usage();
    printf ("-- %s bytes:\n", number_format($mem_end  - $mem_start));
}

$mem_end    = memory_get_usage();
$time_end   = microtime(true);
printf ("-- Retrieval + Iteration: %f seconds, %s bytes:\n",
            $time_end - $time_start,
            number_format($mem_end  - $mem_start));

unset($recs);
echo "</pre><hr />";
// */

/*****************************************************************************/
/*
$time_start = microtime(true);
$mem_start  = memory_get_usage();
$recs       = Model_UserItem::fetchAll(); //array('userId' => 1));
$mem_end    = memory_get_usage();
$time_end   = microtime(true);

printf ("<pre>All %d Records retrieved, %f seconds, %s bytes:\n",
        count($recs),
        $time_end - $time_start,
        number_format($mem_end  - $mem_start));
foreach ($recs as $idex => $rec)
{
    printf ("%d: %s\n", $idex, $rec->debugDump(true));
    unset($rec);
}

$mem_end    = memory_get_usage();
$time_end   = microtime(true);
printf ("-- Retrieval + Iteration: %f seconds, %s bytes:\n",
            $time_end - $time_start,
            number_format($mem_end  - $mem_start));

unset($recs);
echo "</pre><hr />";
// */

/*****************************************************************************/
echo "</pre>";
db_profile_output();
