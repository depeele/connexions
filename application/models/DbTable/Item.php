<?php
class Model_DbTable_Item extends Zend_Db_Table_Abstract
{
    protected   $_name              = 'item';
    protected   $_dependentTables   = array('Model_DbTable_UserItem',
                                            'Model_DbTable_UserTagItem');
}
