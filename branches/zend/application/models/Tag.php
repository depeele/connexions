<?php
/** @file
 *
 *  Model for the Tag table.
 *
 */

class Model_Tag extends Connexions_Model
{
    protected static    $table  = 'tag';
                                  // order 'keys' by most used
    protected static    $keys   = array('tagId', 'tag');
    protected static    $model  = array('tagId'         => 'auto',
                                        'tag'           => 'string'
    );
    public static function getTable()  { return self::$table; }
    public static function getKeys()   { return self::$keys; }
    public static function getModel()  { return self::$model; }

    /** @brief  Set a value in this record and mark it dirty.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  Override to ensure that 'tag' is normalized.
     *
     *  @return true | false
     */
    public function __set($name, $value)
    {
        if ($name === 'tag')
            $value = strtolower($value);

        return parent::__set($name, $value);
    }

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        if ($this->isValid() && (! @empty($this->_record['tag'])))
            return $this->_record['tag'];

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
