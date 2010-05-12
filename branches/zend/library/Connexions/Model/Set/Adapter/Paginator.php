<?php
/** @file
 *
 *  A Zend_Paginator adapter for a Connexiosn_Model_Set instance.
 *
 *  Usage:
 *      $set       = new Connexions_Model_Set(
 *                          array('modelName' => 'Model_UserItem',
 *                                'results'   => $results));
 *      $paginator = new Zend_Paginator( $set->getPaginatorAdapter() );
 *
 */
class Connexions_Model_Set_Adapter_Paginator
                            implements Zend_Paginator_Adapter_Interface
{
    /** @brief  The underlying Connexions_Model_Set instance. */
    protected   $_set               = null;
    protected   $_offset            = 0;

    public function __construct(Connexions_Model_Set    $set,
                                                        $offset = 0)
    {
        $this->_set    = $set;
        $this->_offset = $set->getOffset();
    }

    /** @brief  Returns an iterator for the items of a page.
     *  @param  offset              The page offset.
     *  @param  itemCountPerPage    The number of items per page.
     *
     *  @return A Connexions_Model_Set for the records in the given range.
     */
    public function getItems($offset, $itemCountPerPage)
    {
        /*
        Connexions::log("Connexions_Model_Set_Adapter_Paginator::"
                        .   "getItems(%d, %d): _offset[ %d ]",
                        $offset, $itemCountPerPage, $this->_offset);
        // */

        return $this->_set->getItems($offset - $this->_offset,
                                     $itemCountPerPage);
    }

    /** @brief  Returns the total number of items in the set.
     *
     *  @return integer
     */
    public function count()
    {
        return $this->_set->getTotalCount();
    }
}

