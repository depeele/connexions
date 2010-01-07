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
     *                  indicate which sub-items should be filled immediately;
     *      'item_*' => item sub-instance initialization fields;
     *      'user_*' => user sub-instance initialization fields.
     */
    public function __construct($id, $db = null)
    {
        $fetch = null;
        if (@is_array($id))
        {
            /* Note: Use '(unset) var;' vs 'unset(var);' to eliminate
             *          'Fatal error: Cannot unset string offsets'
             */
            if (@isset($id['@fetch']))
            {
                $fetch = $id['@fetch'];
                (unset) $id['@fetch'];
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
                    (unset) $id[$key];
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
            */
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

    /*************************************************************************
     * Static methods
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

    /** @brief  Retrieve all records and return an array of instances.
     *  @param  where   A string or associative array of restrictions.
     *
     *  @return An array of instances.
     */
    public static function fetchAll($where = null)
    {
        return parent::fetchAll(__CLASS__, $where);
    }

    /** @brief  Construct a Zend_Db_Select instance representing all userItems
     *          that match the given set of tag, user, and/or item identifiers.
     *  @param  tagIds      An array of tag identifiers.
     *  @param  userIds     An array of user identifiers.
     *  @param  itemIds     An array of item identifiers.
     *
     *  @return A Zend_Db_Select instance representing all userItems
     *          that match the given set of tag, user, and/or item identifiers.
     */
    public static function select($tagIds   = null,
                                  $userIds  = null,
                                  $itemIds  = null)
    {
        /* :TODO: Determine the current, authenticated user
         *        and the proper order.
         */
        try {
            $curUserId = Zend_Registry::get('user')->userId;

        } catch (Exception $e) {
            // Treat the current user as Unauthenticated.
            $curUserId = null;
        }

        try {
            $order = Zend_Registry::get('orderBy').
                     Zend_Registry::get('orderDir');

        } catch (Exception $e) {
            // Treat the current user as Unauthenticated.
            $order = 'ui.taggedOn ASC';
        }

        if ( (! @empty($tagIds)) && (! @is_array($tagIds)) )
            $tagIds = array($tagIds);
        if ( (! @empty($userIds)) && (! @is_array($userIds)) )
            $userIds = array($userIds);
        if ( (! @empty($itemIds)) && (! @is_array($itemIds)) )
            $itemIds = array($itemIds);

        // Include all columns/fields from Item and User, prefixed.
        $itemColumns = array();
        foreach (Model_Item::$model as $field => $type)
        {
            $itemColumns['item_'.$field] = 'i.'.$field;
        }
        $userColumns = array();
        foreach (Model_User::$model as $field => $type)
        {
            $userColumns['user_'.$field] = 'u.'.$field;
        }

        $db      = Connexions::getDb();

        $select = $db->select()
                     ->from(array('ui' => self::$table))
                     ->join(array('i'  => 'item'),      // table / as
                            '(i.itemId=ui.itemId)',     // condition
                            $itemColumns)               // columns
                     ->join(array('u'  => 'user'),      // table / as
                            '(u.userId=ui.userId)',     // condition
                            $userColumns)               // columns
                     ->where('((ui.isPrivate=false) '.
                                 ($curUserId !== null
                                    ? 'OR (ui.userId='.$curUserId.')'
                                    : '') . ')')
                     ->order($order);

        /* Include a special custom property in the Zend_Db_Select instance
         * to help later determine whether or not getItemSelect() will return
         * anything other than ALL items.
         */
        $select->_nonTrivial = false;
        $select->_tagIds     =& $tagIds;
        $select->_userIds    =& $userIds;
        $select->_itemIds    =& $itemIds;

        if (! @empty($tagIds))
        {
            // Tag Restrictions -- required 'userTagItem'
            $select->join(array('uti'   => 'userTagItem'),  // table / as
                          '(i.itemId=uti.itemId) AND '.
                          '(u.userId=uti.userId)',          // condition
                          '')                               // columns (none)
                   ->where('uti.tagId IN (?)', $tagIds)
                   ->group(array('uti.userId', 'uti.itemId'))
                   ->having('COUNT(DISTINCT uti.tagId)='.count($tagIds));
            $select->_nonTrivial = true;
        }

        if (! @empty($userIds))
        {
            // User Restrictions
            $select->where('u.userId IN (?)', $userIds);
            $select->_nonTrivial = true;
        }

        if (! @empty($itemIds))
        {
            // Item Restrictions
            $select->where('i.itemId IN (?)', $itemIds);
            $select->_nonTrivial = true;
        }

        /*
        printf ("Model_UserItem::select: [ %s ], _nonTrivial %s<br />\n",
                $select->assemble(),
                ($select->_nonTrivial ? 'true' : 'false'));
        // */

        return $select;
    }

    /** @brief  Given a Zend_Db_Select instance from User_Item::select() (or an
     *          array of tagIds and/or array of userIds) to retrieve a desired
     *          set of userItems, generate a new Zend_Db_Select instance to
     *          retrieve the item identifiers of the matched userItems.
     *  @param  tagIds      A Zend_Db_Select instance OR array of tag
     *                      identifiers.
     *  @param  userIds     Iff 'tagIds' is an array, this may be an array of
     *                      user identifiers.
     *  @param  itemIds     Iff 'tagIds' is an array, this may be an array of
     *                      item identifiers (i.e. limits what MAY be selected).
     *
     *  @return A Zend_Db_Select instance capable of retrieving the item
     *          identifiers of all matched userItems.
     */
    public static function getItemSelect($tagIds   = null,
                                         $userIds  = null,
                                         $itemIds  = null)
    {
        if ($tagIds instanceof Zend_Db_Select)
        {
            $select = clone $tagIds;
        }
        else
        {
            $select = self::select($tagIds, $userIds, $itemIds);
        }

        $select->reset(Zend_Db_Select::COLUMNS)
               ->reset(Zend_Db_Select::ORDER)
               ->columns('ui.itemId')
               ->distinct();

        /*
        printf ("Model_UserItem::getItemSelect: [ %s ], _nonTrivial %s<br />\n",
                $select->assemble(),
                ($select->_nonTrivial ? 'true' : 'false'));
        // */

        return $select;
    }

    /** @brief  Given a Zend_Db_Select instance from User_Item::select() (or an
     *          array of tagIds and/or array of userIds) to retrieve a desired
     *          set of userItems, retrieve the item identifiers.
     *  @param  tagIds      A Zend_Db_Select instance OR array of tag
     *                      identifiers.
     *  @param  userIds     Iff 'tagIds' is an array, this may be an array of
     *                      user identifiers.
     *  @param  itemIds     Iff 'tagIds' is an array, this may be an array of
     *                      item identifiers (i.e. limits what MAY be selected).
     *
     *  @return An array of item identifiers.
     */
    public static function itemIds($tagIds   = null,
                                   $userIds  = null,
                                   $itemIds  = null)
    {
        $select = self::getItemSelect($tagIds, $userIds, $itemIds);
        if ($select->_nonTrivial !== true)
            return array();

        $stmt   = $select->query(); //Zend_Db::FETCH_NUM);
        $recs   = $stmt->fetchAll();

        // Convert the returned array of records to a simple array of ids
        $ids    = array();
        foreach ($recs as $idex => $row)
        {
            $ids[] = $row['itemId']; // $row[0];
        }

        return $ids;
    }

    /** @brief  Construct a Zend_Paginator for all userItems that match the
     *          given set of tag, user, and/or item identifiers.
     *  @param  tagIds      An array of tag identifiers.
     *  @param  userIds     An array of user identifiers.
     *  @param  itemIds     An array of item identifiers.
     *
     *  @return A Zend_Paginator
     */
    public static function paginator($tagIds    = null,
                                     $userIds   = null,
                                     $itemIds   = null)
    {
        $select  = self::select($tagIds, $userIds, $itemIds);
        $adapter = new Zend_Paginator_Adapter_DbSelect( $select );

        return new Zend_Paginator($adapter);
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
        $select = self::select($tagIds, $userIds, $itemIds);
        $stmt   = $select->query();
        $recs   = $stmt->fetchAll();

        if ($asArray === true)
        {
            $set =& $recs;
        }
        else
        {
            // Create instances for each retrieved record
            $set     = array();
            foreach ($recs as $row)
            {
                /* Create an new instance using backed record data.
                 *  Note: Use self::find() instead of new self() to
                 *        allow for instance caching.
                 */
                $row['@isBacked'] = true;
                $inst             = self::find($row, $db);

                array_push($set, $inst);
            }
        }

        return $set;
    }
}
