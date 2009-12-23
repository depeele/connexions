<?php
/** @file
 *
 *  Model for the UserItem table.
 *
 */

class Model_UserItem extends Connexions_Model
{
    protected static    $table  = 'userItem';
                                  // order 'keys' by most used
    protected static    $keys   = array('userId',   'itemId',  'rating',
                                        'isPrivate','taggedOn');
    protected static    $model  = array('userId'        => 'integer',
                                        'itemId'        => 'integer',
                                        'name'          => 'string',
                                        'description'   => 'string',

                                        'rating'        => 'integer',
                                        'isFavorite'    => 'boolean',
                                        'isPrivate'     => 'boolean',
                                        'taggedOn'      => 'datetime'
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
        if ($this->isValid() && (! @empty($this->_record['url'])))
            return $this->_record['url'];

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
