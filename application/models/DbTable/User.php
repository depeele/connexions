<?php
class Model_DbTable_User extends Zend_Db_Table_Abstract
{
    protected   $_name              = 'user';
    protected   $_dependentTables   = array('Model_DbTable_UserItem',
                                            'Model_DbTable_UserAuth',
                                            'Model_DbTable_GroupMember',
                                            'Model_DbTable_MemberGroup',
                                            'Model_DbTable_UserTagItem');
    // /*
    protected   $_referenceMap      = array(
            'Members' => array(
                'columns'       => 'userId',
                'refTableClass' => 'Model_DbTable_GroupMember',
                'refColumns'    => 'userId',
                'onDelete'      => 'cascade',
            ),
    );
    // */
}
