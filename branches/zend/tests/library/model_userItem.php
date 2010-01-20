<?php
require_once('./bootstrap.php');

$time_start = microtime(true);
$mem_start  = memory_get_usage();
$mem_first  = $mem_start;
$rec        = Model_UserItem::find(array('userId'  => 1,
                                         'itemId'  => 1));
number_format($mem_end    = memory_get_usage());
$time_end   = microtime(true);
printf ("<pre>Record for userId==1, itemId==1, %f seconds, %s bytes:\n",
        $time_end - $time_start,
        number_format($mem_end  - $mem_start));
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "\n\n";

/*****************************************************************************/
$rec->taggedOn  = date('Y-m-d H:i:s');
$rec->isPrivate = (! $rec->isPrivate);
echo 'Save (should be an update with new "taggedOn" and toggled "isPrivate"):',
     "\n";
$time_start = microtime(true);
$res        = $rec->save();
$mem_end    = memory_get_usage();
$time_end   = microtime(true);
if ($res === true)
{
    printf ("-- success, %f seconds, %s bytes\n",
            $time_end - $time_start,
            number_format($mem_end  - $mem_start));
    echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
}
else
    echo "** FAILURE\n";
echo "\n\n";

printf ("taggedOn:     %s\n", $rec->taggedOn);
printf ("isPrivate:    %s\n", ($rec->isPrivate ? 'true' : 'false'));
printf ("userId:       %d\n", $rec->userId);
printf ("user->userId: %d\n", $rec->user->userId);
printf ("user->name:   %s\n", $rec->user->name);
printf ("item->url:    %s\n", $rec->item->url);
printf ("tags[0]->tag: %s\n", $rec->tags[0]->tag);

$mem_end    = memory_get_usage();
$time_end   = microtime(true);
printf ("-- Retrieval + Access: %f seconds, %s bytes:\n",
            $time_end - $time_start,
            number_format($mem_end  - $mem_start));

unset($rec);
echo "</pre><hr />";

/*****************************************************************************/
$tagIds  = array(5);    //array(1,2,5);
$userIds = array(1,2,441);
$itemIds = array();

$time_start = microtime(true);
$mem_start  = memory_get_usage();
$recs       = new Model_UserItemSet($tagIds, $userIds, $itemIds);
$mem_end    = memory_get_usage();
$time_end   = microtime(true);

printf ("<pre>%d Set Record(s) from users (%s) with tags (%s), %f seconds, %s bytes:\n",
            $recs->count(),
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
$mem_last = $mem_end;

printf ("Total memory: %s bytes<br />\n",
        number_format($mem_last - $mem_first));
