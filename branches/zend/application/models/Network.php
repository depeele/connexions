<?php
/** @file
 *
 *  Model for the Network table.
 *
 */

class Model_Network extends Connexions_Model
{
    protected static    $table  = 'network';
                                  // order 'keys' by most used
    protected static    $keys   = array('userId', 'memberId');
    protected static    $model  = array('userId'            => 'integer',
                                        'memberId'          => 'integer'
                                        'rating'            => 'integer'
    );
    public static function getTable()  { return self::$table; }
    public static function getKeys()   { return self::$keys; }
    public static function getModel()  { return self::$model; }

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

    /** @brief  Retrieve all records an return an array of instances.
     *  @param  id          The user identifier
     *                      (integrer userId or string name).
     *
     *  @return A new instance (false if no matching user).
     */
    public static function find($id)
    {
        return parent::find(__CLASS__, $id);
    }

    /** @brief  Retrieve all records an return an array of instances.
     *  @param  where   A string or associative array of restrictions.
     *
     *  @return An array of instances.
     */
    public static function fetchAll($where = null)
    {
        return parent::fetchAll(__CLASS__, $where);
    }
}
