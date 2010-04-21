<?php
require_once('./bootstrap.php');

echo "<pre>";
echo "<h3>DB Query tests</h3>";

$db = Connexions::getDb();

$select1 = $db->select()
              ->from('userTagItem',
                     array('tagId',
                           'userItemCount'  => 'COUNT(DISTINCT itemId,userId)',
                           'itemCount'      => 'COUNT(DISTINCT itemId)',
                           'userCount'      => 'COUNT(DISTINCT userId)'))
              ->group('tagId');
printf ("select1 sql[ %s ]\n", $select1->assemble());
echo "<hr />\n\n";

$select2 = $db->select()
                ->from(array('t'  => 'tag'),
                       array('t.*',
                             'uti.userItemCount',
                             'uti.itemCount',
                             'uti.userCount'))
                ->join(array('uti' => $select1),
                       't.tagId=uti.tagId',
                       null)
                ->order('uti.userItemCount DESC')
                ->limit(50);
printf ("select2 sql[ %s ]\n", $select2->assemble());
echo "<hr />\n\n";

/*****************************************************************************/
$as     = 'ui';
$table  = Model_UserItem::metaData('table');
$keys   = Model_UserItem::metaData('keys');

$groupBy = $keys[0];
$key     = (is_array($keys[0]) ? $keys[0] : array( $keys[0] ));

$select1 = $db->select()
              ->from('userTagItem',
                     array_merge($key,
                                 array('userItemCount'  =>
                                            'COUNT(DISTINCT userId,itemId)',
                                       'userCount'      =>
                                            'COUNT(DISTINCT userId)',
                                       'itemCount'      =>
                                            'COUNT(DISTINCT itemId)',
                                       'tagCount'       =>
                                            'COUNT(DISTINCT tagId)')))
              ->group($groupBy);
printf ("select1 sql[ %s ]\n", $select1->assemble());
echo "<hr />\n\n";

$joinCond = array();
foreach ($key as $name)
{
    array_push($joinCond, "{$as}.{$name}=uti.{$name}");
}

$select2 = $db->select()
                ->from(array($as  => $table),
                       array("{$as}.*",
                             'uti.userItemCount',
                             'uti.userCount',
                             'uti.itemCount',
                             'uti.tagCount'))
                ->join(array('uti' => $select1),
                       implode(' AND ', $joinCond),
                       null)
                ->order('uti.userItemCount DESC')
                ->limit(50);
printf ("select2 sql[ %s ]\n", $select2->assemble());
echo "<hr />\n\n";

/*****************************************************************************/
