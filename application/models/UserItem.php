<?php
/** @file
 *
 *  Model for the UserItem table.
 *
 *  This is also provided aggregate access to the references Model_User and
 *  Model_Item.
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

    protected   $_user  = null;
    protected   $_item  = null;

    /** @brief  Create a new instance.
     *  @param  id      The record identifier.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     *
     */
    public function __construct($id, $db = null)
    {
        parent::__construct($id, $db);

        if (@isset($this->_record['userId']))
        {
            // Include the matching user.
            $this->_user =
                new Model_User(array('userId' => $this->_record['userId']));
        }
        if (@isset($this->_record['itemId']))
        {
            // Locate the matching item.
            $this->_item =
                new Model_Item(array('itemId' => $this->_record['itemId']));
        }
    }

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

    /** @brief  Get a value of the given field.
     *  @param  name    The field name.
     *
     *  @return The field value (or null if invalid field).
     */
    public function __get($name)
    {
        $res = null;
        if (preg_match('/^(user|item)\_(.*?)$/', $name, $matches))
        {
            switch ($matches[1])
            {
            case 'user':
                if ($this->_user instanceof Connexions_Model)
                    $res = $this->_user->__get($matches[2]);
                break;

            case 'item':
                if ($this->_item instanceof Connexions_Model)
                    $res = $this->_item->__get($matches[2]);
                break;
            }
        }
        else
        {
            $res = parent::__get($name);
        }

        return $res;
    }

    /** @brief  Set a value in this record and mark it dirty.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  @return true | false
     */
    public function __set($name, $value)
    {
        $res = parent::__set($name, $value);
        if (($res === null) && ($this->_user instanceof Connexions_Model))
            $res = $this->_user->__set($name, $value);
        if (($res === null) && ($this->_item instanceof Connexions_Model))
            $res = $this->_item->__set($name, $value);

        return $res;
    }

    public function user()
    {
        return $this->_user;
    }

    public function item()
    {
        return $this->_item;
    }

    public function toArray($deep = false)
    {
        $ret = $this->_record;
        if ($deep)
        {
            $ret['user'] = ($this->_user instanceof Connexions_Model
                                ? $this->_user->toArray()
                                : array());
            $ret['item'] = ($this->_item instanceof Connexions_Model
                                ? $this->_item->toArray()
                                : array());
        }

        return $ret;
    }

    public function debugDump()
    {
        $str = substr(parent::debugDump(), 0, -3);

        $userStr = ($this->_user instanceof Connexions_Model
                        ? $this->_user->debugDump()
                        : '[];');
        $userStr = preg_replace('/^/ms', '   ', substr($userStr, 0, -1));

        $itemStr = ($this->_item instanceof Connexions_Model
                        ? $this->_item->debugDump()
                        : '[];');
        $itemStr = preg_replace('/^/ms', '   ', substr($itemStr, 0, -1));

        $str .= sprintf ("  %-15s == User_Model%s\n".
                         "  %-15s == Item_Model%s\n".
                         "];",
                            'user', $userStr,
                            'item', $itemStr);

        return $str;
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
