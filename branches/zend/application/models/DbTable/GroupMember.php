<?php
class Model_DbTable_GroupMember extends Zend_Db_Table_Abstract
{
    protected   $_name          = 'groupMember';
    protected   $_referenceMap  = array(
            'User'  => array(
                'columns'       => 'userId',
                'refTableClass' => 'Model_DbTable_User',
                'refColumns'    => 'userId',
            ),
            'Group' => array(
                'columns'       => 'groupId',
                'refTableClass' => 'Model_DbTable_Group',
                'refColumns'    => 'groupId',
            ),
    );
}

