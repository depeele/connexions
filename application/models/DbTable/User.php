<?php
class Model_DbTable_User extends Zend_Db_Table_Abstract
{
    protected   $_name              = 'user';
    protected   $_dependentTables   = array('Model_DbTable_UserItem',
                                            'Model_DbTable_UserAuth',
                                            'Model_DbTable_GroupMember',
                                            'Model_DbTable_UserTagItem');
}
