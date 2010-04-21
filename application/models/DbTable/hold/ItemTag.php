<?php
class Model_DbTable_ItemTag extends Zend_Db_Table_Abstract
{
    protected   $_name              = 'itemTag';
    protected   $_referenceMap  = array(
            'Items' => array(
                'columns'       => 'itemId',
                'refTableClass' => 'Model_DbTable_Item',
                'refColumns'    => 'itemId',
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

