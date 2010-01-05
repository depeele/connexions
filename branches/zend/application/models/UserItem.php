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

    protected static    $_foreignFields = null;

    protected   $_user  = null;
    protected   $_item  = null;
    protected   $_tags  = null;

    /** @brief  Create a new instance.
     *  @param  id      The record identifier.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     *  Note: 'id' may include the following special fields:
     *      '@lazy' => array containing 'user', 'item', and/or 'tags' to
     *                 indicate which sub-items should NOT be filled
     *                 immediately.
     */
    public function __construct($id, $db = null)
    {
        if (@isset($id['@lazy']))
        {
            $lazy = $id['@lazy'];
            unset($id['@lazy']);
        }
        else
        {
            $lazy = array();
        }

        parent::__construct($id, $db);

        if ((! @in_array('user', $lazy)) && @isset($this->_record['userId']))
        {
            // Include the matching user.
            $this->_user =
                new Model_User(array('userId' => $this->_record['userId']));
        }

        if ((! @in_array('item', $lazy)) && @isset($this->_record['itemId']))
        {
            // Locate the matching item.
            $this->_item =
                new Model_Item(array('itemId' => $this->_record['itemId']));
        }

        if (! @in_array('tags', $lazy))
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

        $tagStr  = "[\n    ";
        if (@is_array($this->_tags))
        {
            $tags = array();
            foreach ($this->_tags as $tag)
            {
                if ($tag instanceof Connexions_Model)
                {
                    array_push($tags,
                               sprintf("%6d: %s", $tag->tagId, $tag->tag));
                }
            }
            $tagStr .= implode(",\n    ", $tags);
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
     *  @param  id          The user identifier
     *                      (integrer userId or string name).
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
            foreach (Model_Item::getModel() as $field => $type)
            {
                array_push(self::$_foreignFields,
                            'i.'. $field .' AS item_'. $field);
            }
            foreach (Model_User::getModel() as $field => $type)
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
                $item = array('@isRecord'=>true,'@isBacked'=>true);
                $user = array('@isRecord'=>true,'@isBacked'=>true);
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
                $row['@isRecord'] = true;
                $row['@isBacked'] = true;
                $row['@lazy']     = array('user','item');   //,'tags');
                $inst = new self($row, $db);

                // Locate / Create a Model_User instance
                if (@isset($userMap[$user['userId']]))
                {
                    $inst->_user =& $userMap[$user['userId']];
                }
                else
                {
                    $inst->_user = new Model_User($user, $db);
                    $userMap[$user['userId']] = $inst->_user;
                }

                // Locate / Create a Model_Item instance
                if (@isset($itemMap[$item['itemId']]))
                {
                    $inst->_item =& $itemMap[$item['itemId']];
                }
                else
                {
                    $inst->_item = new Model_Item($item, $db);
                    $itemMap[$item['itemId']] = $inst->_item;
                }

                /*
                $inst->_tags = Model_Tag::fetch(array($inst->_user->userId),
                                                array($inst->_item->itemId));
                */

                array_push($set, $inst);
            }
        }

        return $set;
    }
}
