<?php
class Model_DbTable_UserTag extends Zend_Db_Table_Abstract
{
    protected   $_name              = 'userTag';
    protected   $_referenceMap  = array(
            'Users' => array(
                'columns'       => 'userId',
                'refTableClass' => 'Model_DbTable_User',
                'refColumns'    => 'userId',
                'onDelete'      => 'cascade',
            ),
            'Tags'  => array(
                'columns'       => 'tagId',
                'refTableClass' => 'Model_DbTable_Tag',
                'refColumns'    => 'tagId',
                'onDelete'      => 'cascade',
            ),
    );
}


