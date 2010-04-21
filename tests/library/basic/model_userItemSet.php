<?php
require_once('./bootstrap.php');

$time_start = microtime(true);
$mem_start  = memory_get_usage();
$mem_first  = $mem_start;
$set        = new Model_UserItemSet();

number_format($mem_end    = memory_get_usage());
$time_end   = microtime(true);
printf ("<pre>%d records, %f seconds, %s bytes:\n",
        number_format( count($set) ),
        $time_end - $time_start,
        number_format($mem_end  - $mem_start));
if (! $set instanceof Connexions_Set)
    echo " *** ERROR: Wrong Set class (". get_class($set) .")\n";
else
{
    echo "Record #1:\n";

    $rec = $set[0];

    echo ($rec instanceof Connexions_Model
            ? $rec->debugDump()
            : "*** ERROR: Wrong Record class (". get_class($rec) .")\n");
}
echo "\n\n";

/*****************************************************************************/
$order = 'item_userCount DESC';
echo "New ordering: '{$order}':\n";
$set->setOrder($order);

printf ("<pre>%d records:\n",
        number_format( count($set) ));
if (! $set instanceof Connexions_Set)
    echo " *** ERROR: Wrong Set class (". get_class($set) .")\n";
else
{
    echo "Record #1:\n";

    $rec = $set[0];

    echo ($rec instanceof Connexions_Model
            ? $rec->debugDump()
            : "*** ERROR: Wrong Record class (". get_class($rec) .")\n");
}
echo "\n\n";

/*****************************************************************************/
