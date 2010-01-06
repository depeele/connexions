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
                                    'taggedOn'      => 'datetime'
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
     *                 indicate which sub-items should NOT be filled
     *                 immediately.
     */
    public function __construct($id, $db = null)
    {
        if (@isset($id['@fetch']))
        {
            $fetch = $id['@fetch'];
            unset($id['@fetch']);
        }
        else
        {
            $fetch = array();
        }

        parent::__construct($id, $db);

        if (@in_array('user', $fetch) && @isset($this->_record['userId']))
        {
            // Include the matching user.
            $this->_user =
                Model_User::find(array('userId' => $this->_record['userId']),
                                 $db);
        }

        if (@in_array('item', $fetch) && @isset($this->_record['itemId']))
        {
            // Locate the matching item.
            $this->_item =
                Model_Item::find(array('itemId' => $this->_record['itemId']),
                                 $db);
        }

        if (@in_array('tags', $fetch))
        {
            if (@isset($this->_record['userId']) &&
                @isset($this->_record['itemId']))
            {
                $this->_tags =
                    Model_Tag::fetch(array($this->_record['userId']),
                                     array($this->_record['itemId']));
            }
            else if (($this->_user !== null) && ($this->_item !== null))
            {
                $this->_tags =
                    Model_Tag::fetch(array($this->_user->userId),
                                     array($this->_item->itemId));
            }
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
        switch ($name)
        {
        case 'user':    $res =& $this->_user();         break;
        case 'item':    $res =& $this->_item();         break;
        case 'tags':    $res =& $this->_tags();         break;
        default:        $res =  parent::__get($name);   break;
        }

        /*
        $res = null;
        if (preg_match('/^(user|item|tags)(?:_(.*?))?$/', $name, $matches))
        {
            $type  = $matches[1];
            $field = $matches[2];

            switch ($type)
            {
            case 'user':
                // Just 'user' with no field name returns the user instance
                $res =& $this->_user();
                if ( (! @empty($field)) && ($res instanceof Connexions_Model))
                    $res = $res->__get($field);
                break;

            case 'item':
                // Just 'item' with no field name returns the item instance
                $res =& $this->_item();
                if ( (! @empty($field)) && ($res instanceof Connexions_Model))
                    $res = $res->__get($field);
                break;

            case 'tags':
                // Just 'tags' with no field name returns the tags array.
                $res =& $this->_tags();

                // The 'tags' "field" is an index followed by an optional
                // field name.
                if ( @is_array($res) &&
                     preg_match('/^([0-9]+)(?:_(.*?))?$/', $field, $subMatches))
                {
                    $index = $subMatches[1];
                    $field = $subMatches[2];

                    if (@isset($res[$index]))
                    {
                        // Just an index will return the tag instance.
                        $res = $res[$index];

                        if ( ($res instanceof Connexions_Model) &&
                             (! @empty($field)) )
                        {
                            // Index with a field name will return the field
                            // value for the indexed tag
                            $res = $res->__get($field);
                        }
                    }
                }
                break;
            }
        }
        else
        {
            $res = parent::__get($name);
        }
        */

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

        /*
        if (preg_match('/^(user|item|tags)(?:_(.*?))?$/', $name, $matches))
        {
            $type  = $matches[1];
            $field = $matches[2];

            switch ($type)
            {
            case 'user':
                $user =& $this->_user();
                if ( (! @empty($field)) && ($user instanceof Connexions_Model))
                    $res = $user->__set($field, $value);
                break;

            case 'item':
                $item =& $this->_item();
                if ( (! @empty($field)) && ($item instanceof Connexions_Model))
                    $res = $item->__set($field, $value);
                break;

            case 'tags':
                // Just 'tags' with no field name returns the tags array.
                $tags =& $this->_tags();

                // The 'tags' "field" is an index followed by an optional
                // field name.
                if ( @is_array($tags) &&
                     preg_match('/^([0-9]+)(?:_(.*?))?$/', $field, $subMatches))
                {
                    $index = $subMatches[1];
                    $field = $subMatches[2];

                    if (@isset($tags[$index]))
                    {
                        $tag = $tags[$index];

                        if ( ($tag instanceof Connexions_Model) &&
                             (! @empty($field)) )
                        {
                            // Index with a field name will return the field
                            // value for the indexed tag
                            $res = $tag->__set($field, $value);
                        }
                    }
                }
                break;
            }
        }
        else
        {
            $res = parent::__set($name, $value);
        }
        */

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
        if (@is_array($tags))
        {
            $tagStrs = array();
            foreach ($tags as $tag)
            {
                if ($tag instanceof Connexions_Model)
                {
                    array_push($tagStrs,
                               sprintf("%6d: %s", $tag->tagId, $tag->tag));
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

    /** @brief  Retrieve all records and return an array of instances.
     *  @param  id      The record identifier.
     *
     *  @return A new instance (false if no matching user).
     */
    public static function find($id)
    {
        return parent::find(__CLASS__, $id);
    }

    /** @brief  Retrieve all records and return an array of instances.
     *  @param  where   A string or associative array of restrictions.
     *
     *  @return An array of instances.
     */
    public static function fetchAll($where = null)
    {
        return parent::fetchAll(__CLASS__, $where);
    }

    /** @brief  Retrieve a set of userItems that match the given set of tag,
     *          user, and/or item identifiers.
     *  @param  tagIds      An array of tag identifiers.
     *  @param  userIds     An array of user identifiers.
     *  @param  itemIds     An array of item identifiers.
     *  @param  asArray     Return as array records instead of instances?
     *
     *  @return An array of instances (or record arrays if 'asArray' == true).
     */
    public static function fetch($tagIds,
                                 $userIds = null,
                                 $itemIds = null,
                                 $asArray = false)
    {
        $db   = Connexions::getDb();

        /* :TODO: Determine the current, authenticated user
         *        and the proper order.
         */
        $curUserId = 1;
        $sortOrder = 'ui.taggedOn ASC';

        // Include all fields from Item and User
        if (! @is_array(self::$_foreignFields))
        {
            self::$_foreignFields = array();
            foreach (Model_Item::$model as $field => $type)
            {
                array_push(self::$_foreignFields,
                            'i.'. $field .' AS item_'. $field);
            }
            foreach (Model_User::$model as $field => $type)
            {
                array_push(self::$_foreignFields,
                            'u.'. $field .' AS user_'. $field);
            }
        }


        // Generate the SQL
        $sql = 'SELECT ui.*,'
             .          implode(',', self::$_foreignFields)
             .  ' FROM item i,user u,userItem ui'
             .          (! @empty($tagIds) ? ',userTagItem uti' : '')
             .  ' WHERE (i.itemId=ui.itemId)'
             .    ' AND (u.userId=ui.userId)'

                        // Tag restrictions
             .          (! @empty($tagIds)
                            ?  ' AND (i.itemId=uti.itemId)'
                              .' AND (u.userId=uti.userId)'
                              .' AND (uti.tagId IN ('.
                                                implode(',',$tagIds).'))'
                             : '')

                        // User restrictions
             .          (! @empty($userIds)
                             ? ' AND (u.userId IN ('.
                                                implode(',',$userIds).'))'
                             : '')

                        // Item restrictions
             .          (! @empty($itemIds)
                             ? ' AND (i.itemId IN ('.
                                                implode(',',$itemIds).'))'
                             : '')

                        // Public item OR owned by the current user
             .   ' AND ((ui.isPrivate=false) OR (ui.userId='. $curUserId .'))'

                        // Require ALL tags for an item to be selected
             .          (! @empty($tagIds)
                            ? ' GROUP BY uti.userId,uti.itemId'.
                              ' HAVING (COUNT(DISTINCT uti.tagId)='.
                                                        count($tagIds) .')'
                             : '')

             .  ' ORDER BY '. $sortOrder ;

        // Retrieve all records
        $recs = $db->fetchAll($sql);

        if ($asArray === true)
        {
            $set =& $recs;
        }
        else
        {
            // Create instances for each retrieved record
            $set     = array();
            $itemMap = array();
            $userMap = array();
            foreach ($recs as $row)
            {
                /* Pull out item-related fields ('item_')
                 *      and user-related fields ('user_')
                 *
                 * for initialization of the sub-instances.
                 */
                $item = array('@isBacked'=>true);
                $user = array('@isBacked'=>true);
                foreach ($row as $key => $val)
                {
                    $id = explode('_', $key);
                    if (count($id) < 2)
                        continue;

                    switch ($id[0])
                    {
                    case 'item':    $item[$id[1]] = $val;   break;
                    case 'user':    $user[$id[1]] = $val;   break;
                    default:
                        throw (new Exception('Invalid sub-field ['. $key .']'));
                    }
                    unset($row[$key]);
                }

                // Create an new instance using backed record data.
                $row['@isBacked'] = true;
                //$row['@fetch']    = array('user','item','tags');
                $inst = self::find($row, $db);  //new self($row, $db);

                // Remember the item and user information we retrieved.
                $inst->_item = $item;
                $inst->_user = $user;

                array_push($set, $inst);
            }
        }

        return $set;
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
                    Model_Tag::fetch(array($this->_record['userId']),
                                     array($this->_record['itemId']));
            }
        }

        return $this->_tags;
    }

}
