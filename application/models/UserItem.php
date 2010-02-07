<?php
/** @file
 *
 *  Model for the UserItem table.
 *
 *  This also provides aggregate access to the referenced Model_User and
 *  Model_Item instances.
 *
 */

class Model_UserItem extends Connexions_Model
{
    /*************************************************************************
     * Connexions_Model - static, identity members
     *
     */
    public static   $table  = 'userItem';
                              // order 'keys' by most used
    public static   $keys   = array('userId', 'itemId',
                                    'rating', 'isPrivate','taggedOn');
    public static   $model  = array('userId'        => 'integer',
                                    'itemId'        => 'integer',
                                    'name'          => 'string',
                                    'description'   => 'string',

                                    'rating'        => 'integer',
                                    'isFavorite'    => 'boolean',
                                    'isPrivate'     => 'boolean',
                                    'taggedOn'      => 'datetime',
                                    'updatedOn'     => 'datetime'
    );
    /*************************************************************************/

    protected static    $_foreignFields = null;

    protected           $_user          = null;
    protected           $_item          = null;
    protected           $_tags          = null;


    /** @brief  Create a new instance.
     *  @param  id      The record identifier.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     *  Note: 'id' may include the following special fields:
     *      '@fetch' => array containing 'user', 'item', and/or 'tags' to
     *                  indicate which sub-items should be filled immediately;
     *      'item_*' => item sub-instance initialization fields;
     *      'user_*' => user sub-instance initialization fields.
     */
    public function __construct($id, $db = null)
    {
        $fetch = null;
        if (@is_array($id))
        {
            if (@isset($id['@fetch']))
            {
                $fetch = $id['@fetch'];

                try{
                    unset($id['@fetch']);
                    unset($id['@fetch']);
                } catch(Exception $e) {}
            }

            /* Pull out item-related fields ('item_')
             *      and user-related fields ('user_')
             *
             * for initialization of the sub-instances.
             */
            $item = array();
            $user = array();
            foreach ($id as $key => $val)
            {
                $info  = explode('_', $key);
                $unset = true;
                switch ($info[0])
                {
                case 'item':    $item[$info[1]] = $val;   break;
                case 'user':    $user[$info[1]] = $val;   break;
                default:
                    // Skip this field.
                    $unset = false;
                    break;
                }

                if ($unset)
                {
                    try{
                        unset($id[$key]);
                        unset($id[$key]);
                    } catch(Exception $e) {}
                }
            }

            if (! empty($item))
            {
                if (@isset($id['@isBacked']))
                    $item['@isBacked'] = true;
                $this->_item = $item;
            }

            if (! empty($user))
            {
                if (@isset($id['@isBacked']))
                    $user['@isBacked'] = true;
                $this->_user = $user;
            }

            /*
            echo "<pre>UserItem::__construct: initialization data:\n";
            echo "-- id: "; print_r($id); echo "\n";
            echo "-- item: "; print_r($this->_item); echo "\n";
            echo "-- user: "; print_r($this->_user); echo "\n";
            echo "</pre>\n";
            // */
        }

        parent::__construct($id, $db);

        if (@is_array($fetch))
        {
            // Force an immediate fetch of the specified items.
            if (@in_array('item', $fetch))
                $this->_item();

            if (@in_array('user', $fetch))
                $this->_user();

            if (@in_array('tags', $fetch))
                $this->_tags();
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
     *  Note: Sub-instances or their fields are addressed by pre-pending the
     *        sub-instance name:
     *          user[_<field>]
     *          item[_<field>]
     *          tags[_<indexNum>[_<field>]]
     *
     *  @return The field value (or null if invalid field).
     */
    public function __get($name)
    {
        list($sub, $field) = explode('_', $name);
        if ( (! empty($sub)) && (! empty($field)) )
            $name = $sub;

        switch ($name)
        {
        case 'user':    $res =& $this->_user();         break;
        case 'item':    $res =& $this->_item();         break;
        case 'tags':    $res =& $this->_tags();         break;
        default:        $res =  parent::__get($name);   break;
        }

        if (! empty($field))
            $res = $res->{$field};

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
        $res = false;
        switch ($name)
        {
        case 'user':
        case 'item':
        case 'tags':
            // Do NOT allow external replacement of sub-instances.
            break;

        default:
            $res =  parent::__set($name, $value);
            break;
        }

        return $res;
    }

    public function toArray($deep = false)
    {
        $ret = $this->_record;
        if ($deep)
        {
            $user =& $this->_user();
            $item =& $this->_item();
            $tags =& $this->_tags();

            $ret['user'] = ($user instanceof Connexions_Model
                                ? $user->toArray()
                                : array());
            $ret['item'] = ($item instanceof Connexions_Model
                                ? $item->toArray()
                                : array());
            $ret['tags'] = array();
            foreach ($tags as $tag)
            {
                if ($tag instanceof Connexions_Model)
                    array_push($ret['tags'], $tag->toArray());
            }
        }

        return $ret;
    }

    /** @brief  Generate a string representation of this record.
     *  @param  skipValidation  Skip validation of each field [false]?
     *
     *  @return A string.
     */
    public function debugDump($skipValidation = false)
    {
        $str = substr(parent::debugDump($skipValidation), 0, -3);

        $user =& $this->_user();
        $item =& $this->_item();
        $tags =& $this->_tags();

        $userStr = ($user instanceof Connexions_Model
                        ? $user->debugDump($skipValidation)
                        : '[];');
        $userStr = preg_replace('/^/ms', '   ', substr($userStr, 0, -1));

        $itemStr = ($item instanceof Connexions_Model
                        ? $item->debugDump($skipValidation)
                        : '[];');
        $itemStr = preg_replace('/^/ms', '   ', substr($itemStr, 0, -1));

        $tagStr  = "[\n    ";
        if ($tags->count() > 0)
        {
            $tagStrs = array();
            foreach ($tags as $tag)
            {
                if ($tag instanceof Connexions_Model)
                {
                    array_push($tagStrs,
                               sprintf("%6d: %15s", //: %4d / %4d / %4d",
                                       $tag->tagId, $tag->tag   /*,
                                       $tag->userItemCount,
                                       $tag->itemCount,
                                       $tag->userCount*/));
                }
            }
            $tagStr .= implode(",\n    ", $tagStrs);
        }
        $tagStr .= "\n  ]";

        $str .= sprintf ("  %-15s == User_Model%s\n".
                         "  %-15s == Item_Model%s\n".
                         "  %-15s == %s\n".
                         "];",
                            'user', $userStr,
                            'item', $itemStr,
                            'tags', $tagStr);

        return $str;
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    protected function _user()
    {
        if (! $this->_user instanceof Model_User)
        {
            // _user is NOT a Model_User
            if (@is_array($this->_user))
            {
                /* _user IS an array -- attempt to create a Model_User instance
                 * with the data.
                 */
                $this->_user =
                    Model_User::find($this->_user, $this->_db);
            }

            if ((! $this->_user instanceof Model_User) &&
                (@isset($this->_record['userId'])) )
            {
                /* Attempt to retrieve a Model_User instance using the 'userId'
                 * from our record.
                 */
                $this->_user =
                    Model_User::find(
                                    array('userId' => $this->_record['userId']),
                                    $this->_db);
            }
        }

        return $this->_user;
    }

    protected function _item()
    {
        if (! $this->_item instanceof Model_Item)
        {
            // _item is NOT a Model_Item
            if (@is_array($this->_item))
            {
                /* _item IS an array -- attempt to create a Model_Item instance
                 * with the data.
                 */
                $this->_item =
                    Model_Item::find($this->_item, $this->_db);
            }

            if ((! $this->_item instanceof Model_Item) &&
                (@isset($this->_record['itemId'])) )
            {
                /* Attempt to retrieve a Model_Item instance using the 'itemId'
                 * from our record.
                 */
                $this->_item =
                    Model_Item::find(
                                    array('itemId' => $this->_record['itemId']),
                                    $this->_db);
            }
        }

        return $this->_item;
    }

    protected function _tags()
    {
        if ($this->_tags === null)
        {
            if (@isset($this->_record['userId']) &&
                @isset($this->_record['itemId']))
            {
                $this->_tags =
                    new Model_TagSet(array($this->_record['userId']),
                                     array($this->_record['itemId']));
            }
        }

        return $this->_tags;
    }

    /*************************************************************************
     * Connexions_Model - abstract static method implementations
     *
     */

    /** @brief  Locate the identified record.
     *  @param  id          The record identifier.
     *  @param  db          An optional database instance (Zend_Db_Abstract).
     *
     *  @return A new instance (check isBacked(), isValid(), getError()).
     */
    public static function find($id, $db = null)
    {
        return parent::find(__CLASS__, $id, $db);
    }
}
