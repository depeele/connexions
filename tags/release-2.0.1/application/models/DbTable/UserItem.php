<?php
class Model_DbTable_UserItem extends Zend_Db_Table_Abstract
{
    protected   $_name          = 'userItem';
    protected   $_referenceMap  = array(
            'User'  => array(
                'columns'       => 'userId',
                'refTableClass' => 'Model_DbTable_User',
                'refColumns'    => 'userId',
                'onDelete'      => 'cascade',
            ),
            'Item'  => array(
                'columns'       => 'itemId',
                'refTableClass' => 'Model_DbTable_Item',
                'refColumns'    => 'itemId',
                'onDelete'      => 'cascade',
            ),
            'ByUser' => array(
                'columns'       => 'userId',
                'refTableClass' => 'Model_DbTable_UserItem',
                'refColumns'    => 'userId',
            ),
    );
}
