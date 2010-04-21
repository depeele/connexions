<?php
class Model_DbTable_GroupItem extends Zend_Db_Table_Abstract
{
    protected   $_name          = 'groupItem';
    protected   $_referenceMap  = array(
            'User'  => array(
                'columns'       => 'itemId',
                'refTableClass' => 'Model_DbTable_User',
                'refColumns'    => 'userId',
            ),
            'Item'  => array(
                'columns'       => 'itemId',
                'refTableClass' => 'Model_DbTable_Item',
                'refColumns'    => 'itemId',
            ),
            'Tag'   => array(
                'columns'       => 'itemId',
                'refTableClass' => 'Model_DbTable_Tag',
                'refColumns'    => 'tagId',
            ),

            'Group' => array(
                'columns'       => 'groupId',
                'refTableClass' => 'Model_DbTable_Group',
                'refColumns'    => 'groupId',
            ),
    );
}

