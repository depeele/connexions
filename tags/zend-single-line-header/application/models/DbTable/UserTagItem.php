<?php
class Model_DbTable_UserTagItem extends Zend_Db_Table_Abstract
{
    protected   $_name              = 'userTagItem';
    protected   $_referenceMap  = array(
            'Users' => array(
                'columns'       => 'userId',
                'refTableClass' => 'Model_DbTable_User',
                'refColumns'    => 'userId',
                'onDelete'      => 'cascade',
            ),
            'Items' => array(
                'columns'       => 'itemId',
                'refTableClass' => 'Model_DbTable_Item',
                'refColumns'    => 'itemId',
                'onDelete'      => 'cascade',
            ),
            'Tags'  => array(
                'columns'       => 'tagId',
                'refTableClass' => 'Model_DbTable_Tag',
                'refColumns'    => 'tagId',
                'onDelete'      => 'cascade',
            ),
            'UserItems' => array(
                'columns'       => array('userId', 'itemId'),
                'refTableClass' => 'Model_DbTable_UserItem',
                'refColumns'    => array('userId', 'itemId'),
                'onDelete'      => 'cascade',
            ),
    );
}


