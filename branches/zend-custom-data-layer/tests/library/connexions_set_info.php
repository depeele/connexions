<?php
require_once('./bootstrap.php');

/*****************************************************************************/
$items = 'dep,sab,cag';
$info  = new Connexions_Set_Info($items, 'Model_User');
echo "<pre>ID information for '{$items}':\n";
printf (" itemClass   : '%s'\n", $info->itemClass);
printf (" reqStr      : '%s'\n", $info->reqStr);
printf (" valid       : { %s }\n", var_export($info->valid, true));
printf (" validIds    : [ %s ]\n", implode(', ', $info->validIds));
printf (" validList   : [ %s ]\n", implode(', ', $info->validList));
printf (" validItems  : [ %s ]\n", $info->validItems);

printf (" invalid     : { %s }\n", var_export($info->invalid, true));
printf (" invalidList : [ %s ]\n", implode(', ', $info->invalidList));
printf (" invalidItems: [ %s ]\n", $info->invalidItems);

echo "</pre><hr />";
unset($info);

/*****************************************************************************/
$items = 'security,identity,abc,def';
$info  = new Connexions_Set_Info($items, 'Model_Tag');
echo "<pre>ID information for '{$items}':\n";
printf (" itemClass   : '%s'\n", $info->itemClass);
printf (" reqStr      : '%s'\n", $info->reqStr);
printf (" valid       : { %s }\n", var_export($info->valid, true));
printf (" validIds    : [ %s ]\n", implode(', ', $info->validIds));
printf (" validList   : [ %s ]\n", implode(', ', $info->validList));
printf (" validItems  : [ %s ]\n", $info->validItems);

printf (" invalid     : { %s }\n", var_export($info->invalid, true));
printf (" invalidList : [ %s ]\n", implode(', ', $info->invalidList));
printf (" invalidItems: [ %s ]\n", $info->invalidItems);

echo "</pre><hr />";
unset($info);
