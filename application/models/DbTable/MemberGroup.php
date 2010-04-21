<?php
class Model_DbTable_Group extends Zend_Db_Table_Abstract
{
    protected   $_name              = 'memberGroup';
    protected   $_dependentTables   = array('Model_DbTable_GroupItem',
                                            'Model_DbTable_GroupMember');
}

