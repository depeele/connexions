<?php
require_once('./bootstrap.php');

$rec = Model_UserItem::find(array('userId'  => 1,
                                  'itemId'  => 1));
echo "<pre>Record for userId==1, itemId==1:\n";
echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
echo "\n\n";

$rec->taggedOn  = date('Y-m-d H:i:s');
$rec->isPrivate = (! $rec->isPrivate);
echo "Save (should be an update with new 'taggedOn' and toggled 'isPrivate'):\n";
$res = $rec->save();
if ($res === true)
{
    echo "-- success\n";
    echo ($rec instanceof Connexions_Model ? $rec->debugDump() : " *** ERROR\n");
}
else
    echo "** FAILURE\n";
echo "\n\n";

printf ("userId:      %d\n", $rec->userId);
printf ("user_userId: %d\n", $rec->user_userId);
printf ("user_name:   %s\n", $rec->user_name);
printf ("item_url:    %s\n", $rec->item_url);

echo "</pre><hr />";

$recs = Model_UserItem::fetchAll(); //array('userId' => 1));
printf ("<pre>All %d Records:\n", count($recs));
foreach ($recs as $idex => $rec)
{
    printf ("%d: %s\n", $idex, $rec->debugDump());
}
echo "</pre><hr />";
