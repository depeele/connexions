<?php
class Model_DbTable_Activity extends Zend_Db_Table_Abstract
{
    protected   $_name              = 'activity';
    protected   $_referenceMap      = array(
            'User'    => array(
                'columns'       => 'userId',
                'refTableClass' => 'Model_DbTable_User',
                'refColumns'    => 'userId',
                'onDelete'      => 'cascade',
            ),
    );
}
