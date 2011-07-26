<?php

require_once("../lib/tagging.php");

$tagdb =& $gTagging->mTagDB;

$users = $tagdb->users(null,        // All users
                       'name ASC',  // orderBy
                       null,        // renderer
                       -1);         // no paging

echo "<pre>\n";
printf ("Updating stats for %u users:\n", count($users));
foreach ($users as $idex => $user)
{
    $counts    = array();

    $counts['totalTags']  = $tagdb->tagsCount($user['userid']);
                 //count($tagdb->tagsOfUsers($user['userid']));
    $counts['totalItems'] = $tagdb->userItemsCount($user['userid'],
                                        null,           // tags
                                        $user['userid']);   // curUser
                 //count($tagdb->itemsOfUsers($user['userid']));

    printf (" %4u: %4u tags and %4u items for user '%s'",
            $idex,
            $counts['totalTags'],
            $counts['totalItems'], $user['name']);

    // /*
    $res = $tagdb->update('user', $user['userid'], $counts);
    if ($res === false)
    {
        echo " *** FAILED";
    }
    // */
    echo "\n";
}

$items = $tagdb->items(null, null);
printf ("Updating stats for %u items:\n", count($items));
foreach ($items as $idex => $itemid)
{
    $counts = array();

    $sql = "SELECT COUNT(DISTINCT userid) AS userCount"
            . " FROM useritem"
            .       " WHERE (itemid = {$itemid})";
    $countRows = $tagdb->db->GetRow($sql);
    $counts['userCount'] = $countRows['userCount'];

    $sql = "SELECT COUNT(rating) AS ratingCount, SUM(rating) AS ratingSum"
            . " FROM useritem"
            .       " WHERE (itemid = {$itemid})"
            .          "AND (rating > 0)";
    $countRows   = $tagdb->db->GetRow($sql);
    $counts['ratingCount'] = $countRows['ratingCount'];
    $counts['ratingSum']   = $countRows['ratingSum'];
                                   
    printf (" %4u: %4u users, %4u ratings, rating sum=%4u for item '%s'",
            $idex,
            $counts['userCount'],
            $counts['ratingCount'],
            $counts['ratingSum'], $itemid);

    // /*
    $res = $tagdb->update('item', $itemid, $counts);
    if ($res === false)
    {
        echo " *** FAILED";
    }
    // */
    echo "\n";
}

echo "</pre>\n";

?>
