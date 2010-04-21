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
    public static   $keys   = array(array('userId', 'itemId'),
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

    // Associated model caches (e.g. userItem's user, item, tags).
    protected           $_user          = null; // Model_User
    protected           $_item          = null; // Model_Item
    protected           $_tags          = null; // Model_TagSet


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
        if ($this->isValid() && (! @empty($this->_record['name'])))
            return $this->_record['name'];

        return parent::__toString();
    }

    /** @brief  Get a value of the given field.
     *  @param  name    The field name.
     *
     *  Note: Sub-instances or their fields may be addressed by pre-pending the
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

    /** @brief  Modify the set of tags associated with this userItem / 
     *          Bookmark.
     *  @param  tags    The new, full set of tags for this userItem / Bookmark.
     *                  This may be an array of tagIds or a comma-separated 
     *                  string of tags.
     *
     *  @return This Model_UserItem for a fluent interface.
     */
    public function tagsUpdate($tags)
    {
        if (! is_array($tags))
        {
            /* Parse the tags into an array of 'valid' / existing tagIds and
             * a second array of 'invalid' / non-existing tag names.
             */
            $tagInfo = self::ids($tags);

            /* Create all tags that do not yet exist, adding the new tagId to 
             * the list of 'valid' tagIds.
             */
            foreach ($tagInfo['invalid'] as $tagStr)
            {
                $tag = new Model_Tag($tagStr);
                $tag->save();

                array_push($tagInfo['valid'], $tag->tagId);
            }

            $tags = $tagInfo['valid'];
        }

        /* Change the set of tags for this userItem / Bookmark.
         *
         * This involves:
         *  1) Determine which of the current tags should be deleted, and which 
         *     of the new tags are being added;
         *          e.g.    new     = (a,    c, d,    f, g, h, i)
         *                  current = (a, b, c, d, e, f         )
         *                  -------------------------------------
         *                  delete  = (   b,       e            )
         *                  keep    = (a,    c, d,    f         )
         *
         *                  add     = diff(new, keep)
         *                            (                  g, h, i)
         *
         *  2) Add any new tags;
         *  3) Delete any tags that are no longer needed.
         */
        $userId  = $this->userId;
        $itemId  = $this->itemId;

        $tagsDelete = array();
        $tagsKeep   = array();
        foreach ($this->tags as $tag)
        {
            if (! in_array($tag->tagId, $tags))
            {
                // This tag is to be deleted
                array_push($tagsDelete, $tag->tagId);
            }
            else
            {
                // This tag is to be kept
                array_push($tagsKeep,   $tag->tagId);
            }
        }

        /* The tags that will be added will be the difference between the 
         * incoming set of tags and those we will be keeping.
         */
        $tagsAdd = array_diff($tags, $tagsKeep);

        // 2) Add any new tags (tagsAdd);
        if (! empty($tagsAdd))
        {
            //                             vv don't notify related models here
            $this->tagsAdd($tagsAdd,       false);
        }

        // 3) Delete any tags that are no longer needed (tagsDelete).
        if (! empty($tagsDelete))
        {
            //                             vv don't notify related models here
            $this->tagsDelete($tagsDelete, false);
        }

        // Invalidate our tag cache.
        $this->_tags = null;

        // Notify related models of this update to tags
        $this->user->tagsUpdated();
        $this->item->tagsUpdated();

        return $this;
    }

    /** @brief  Given a set of tags (either an array of tagIds or a 
     *          comma-separated string of tags), associate all tags with this 
     *          userItem.  If tags do not exist, they will be created.
     *  @param  tags    The set of tags -- as an array of tagIds or a 
     *                  comma-separated string of tags.
     *
     *  Note: This method will also update the appropriate join tables
     *        ( userTagItem ).
     *
     *  @return This Model_UserItem for a fluent interface.
     */
    public function tagsAdd($tags, $notify = true)
    {
        // Retrieve the count of existing tags for this userItem
        $curTagCount = count($this->tags);

        if (! is_array($tags))
        {
            /* Parse the tags into an array of 'valid' / existing tagIds and
             * a second array of 'invalid' / non-existing tag names.
             */
            $tagInfo = self::ids($tags);

            /* Create all tags that do not yet exist, adding the new tagId to 
             * the list of 'valid' tagIds.
             */
            foreach ($tagInfo['invalid'] as $tagStr)
            {
                $tag = new Model_Tag($tagStr);
                $tag->save();

                array_push($tagInfo['valid'], $tag->tagId);
            }

            $tags = $tagInfo['valid'];
        }

        // Create the appropriate join table entries for each new tag
        $userId = $this->userId;
        $itemId = $this->itemId;
        foreach ($tags as $tagId)
        {
            // Create the userTagItem entry
            try
            {
                $this->_db->insert('userTagItem',
                                   array('userId' => $userId,
                                         'tagId'  => $tagId,
                                         'itemId' => $itemId));
            }
            catch (Exception $e) { /* IGNORE -- likely a duplicate entry */ }
        }

        // Invalidate our tag cache.
        $this->_tags = null;

        if ($notify)
        {
            // Notify related models of this update to tags
            $this->user->tagsUpdated();
            $this->item->tagsUpdated();
        }

        return $this;
    }

    /** @brief  Remove the given set of tags associated with this userItem / 
     *          Bookmark.
     *  @param  tags    An array of tagIds to delete
     *                  (empty to delete all tags);
     *
     *  Note: This method will also update the appropriate join tables
     *        ( userTagItem ).
     *
     *  @return This Model_UserItem for a fluent interface.
     */
    public function tagsDelete(array $tags = array(), $notify = true)
    {
        if (empty($tags))
        {
            // Retrieve the set of tagIds for this userItem
            $tags = array();
            foreach ($this->tags as $tag)
            {
                array_push($tags, $tag->tagId);
            }
        }

        // Delete the join table entries for each existing tag
        $userId = $this->userId;
        $itemId = $this->itemId;

        /* Delete all userTagItem entries matching this userItem and any of the 
         * provided tags.
         */
        $this->_db->delete('userTagItem', array('userId=?'     => $userId,
                                                'tagId IN (?)' => $tags,
                                                'itemId=?'     => $itemId));

        // Invalidate our tag cache.
        $this->_tags = null;

        if ($notify)
        {
            // Notify related models of this update to tags
            $this->user->tagsUpdated();
            $this->item->tagsUpdated();
        }

        return $this;
    }

    /** @brief  Return an associative array representing this item.
     *  @param  deep    Include details about sub-instances (user, item, tags)?
     *  @param  public  Include only "public" information?
     *
     *  @return An associaitve array.
     */
    public function toArray($deep = false, $public = true)
    {
        $ret = $this->_record;

        if ($public)
        {

            // Remove non-public information
            unset($ret['userId']);
            unset($ret['itemId']);
        }

        if ($deep)
        {
            $user =& $this->_user();
            $item =& $this->_item();
            $tags =& $this->_tags();

            $ret['user'] = ($user instanceof Connexions_Model
                                ? $user->toArray($public)
                                : array());
            $ret['item'] = ($item instanceof Connexions_Model
                                ? $item->toArray($public)
                                : array());
            $ret['tags'] = array();
            foreach ($tags as $tag)
            {
                if ($tag instanceof Connexions_Model)
                    array_push($ret['tags'], $tag->toArray($public));
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

    /** @brief  Invalidate any cache we have of sub-instances
     *          (i.e. _user, _item, _tags).
     *
     *  @return This Model_UserItem for a fluent interface.
     */
    public function invalidateCache()
    {
        $this->_user = null;
        $this->_item = null;
        $this->_tags = null;

        return $this;
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
        return parent::find($id, $db, __CLASS__);
    }
}
