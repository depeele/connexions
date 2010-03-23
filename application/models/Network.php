<?php
/** @file
 *
 *  Model for the Network table.
 *
 */

class Model_Network extends Connexions_Model
{
    /*************************************************************************
     * Connexions_Model - static, identity members
     *
     */
    public static   $table  = 'network';
                              // order 'keys' by most used
    public static   $keys   = array('userId', 'memberId');
    public static   $model  = array('userId'    => 'integer',
                                    'memberId'  => 'integer'
                                    'rating'    => 'integer'
    );
    /*************************************************************************/

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        if ($this->isValid())
            return sprintf("%d:%d",
                           $this->_record['userId'],
                           $this->_record['memberId']);

        return parent::__toString();
    }

    /*************************************************************************
     * Connexions_Model - abstract static method implementations
     *
     */

    /** @brief  Retrieve all records and return an array of instances.
     *  @param  id      The record identifier.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     *  @return A new instance (false if no matching user).
     */
    public static function find($id, $db = null)
    {
        return parent::find($id, $db, __CLASS__);
    }
}
