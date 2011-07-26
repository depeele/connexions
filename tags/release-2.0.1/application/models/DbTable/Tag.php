<?php
class Model_DbTable_Tag extends Zend_Db_Table_Abstract
{
    protected   $_name              = 'tag';
    protected   $_dependentTables   = array('Model_DbTable_UserTagItem');
}
