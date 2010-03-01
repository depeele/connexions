<?php
require_once('./bootstrap.php');

$time_start = microtime(true);
$mem_start  = memory_get_usage();
$mem_first  = $mem_start;
$set        = new Model_UserSet();

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

printf ("<pre>%d records:\n",
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
