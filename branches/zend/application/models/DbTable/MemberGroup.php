<?php
class Model_DbTable_MemberGroup extends Zend_Db_Table_Abstract
{
    protected   $_name              = 'memberGroup';
    protected   $_dependentTables   = array('Model_DbTable_GroupItem',
                                            'Model_DbTable_GroupMember');
    protected   $_referenceMap      = array(
            'Owner'  => array(
                'columns'       => 'ownerId',
                'refTableClass' => 'Model_DbTable_User',
                'refColumns'    => 'ownerId',
                'onDelete'      => 'cascade',
            ),
            'Members' => array(
                'columns'       => 'groupId',
                'refTableClass' => 'Model_DbTable_GroupMember',
                'refColumns'    => 'groupId',
                'onDelete'      => 'cascade',
            ),
    );
}

