<?php
/** @file
 *
 *  Model for the Tag table.
 *
 */

class Model_Tag extends Connexions_Model_Cached
{
    /*************************************************************************
     * Connexions_Model - static, identity members
     *
     */
    public static   $table  = 'tag';
                              // order 'keys' by most used
    public static   $keys   = array('tagId', 'tag');
    public static   $model  = array('tagId' => 'auto',
                                    'tag'   => 'string'
    );
    /*************************************************************************/

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

    /*************************************************************************
     * Static methods
     *
     */

    /** @brief  Given a set of tags, retrieve the tag identifier for each.
     *  @param  tags    The set of tags as a comma-separated string or array.
     *
     *  @return An array of tag identifiers.
     */
    public static function ids($tags)
    {
        if (! @is_array($tags))
            $tags = preg_split('/\s*,\s*/', $tags);

        $db     = Connexions::getDb();
        $select = $db->select()
                     ->from(self::$table,
                            'tagId')
                     ->where('tag IN (?)', $tags);
        $stmt   = $select->query(); //Zend_Db::FETCH_NUM);
        $recs   = $stmt->fetchAll();

        // Convert the returned array of records to a simple array of ids
        $ids    = array();
        foreach ($recs as $idex => $row)
        {
            $ids[] = $row['tagId']; // $row[0];
        }

        return $ids;
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

    /** @brief  Construct a Zend_Db_Select instance representing all tags that
     *          match the given set of tag, user, and/or item identifiers.
     *  @param  userIds     An array of user identifiers.
     *  @param  itemIds     An array of item identifiers.
     *  @param  tagIds      An array of tag identifiers.
     *
     *  @return A Zend_Db_Select instance representing all tags
     *          that match the given set of user, item, and/or tag identifiers.
     */
    public static function select($userIds = null,
                                 $itemIds = null,
                                 $tagIds  = null)
    {
        /* :TODO: Determine the proper order.
         */
        try {
            $order = Zend_Registry::get('orderBy').
                     Zend_Registry::get('orderDir');

        } catch (Exception $e) {
            // Treat the current user as Unauthenticated.
            $order = 't.tag ASC';
        }

        if ( (! @empty($userIds)) && (! @is_array($userIds)) )
            $userIds = array($userIds);
        if ( (! @empty($itemIds)) && (! @is_array($itemIds)) )
            $itemIds = array($itemIds);
        if ( (! @empty($tagIds)) && (! @is_array($tagIds)) )
            $tagIds = array($tagIds);

        $db     = Connexions::getDb();
        $select = $db->select()
                     ->from(array('t' => self::$table))
                     ->join(array('uti'   => 'userTagItem'),  // table / as
                            ' t.tagId=uti.tagId',             // condition
                            '')                               // columns (none)
                     ->columns(array(
                                'userItemCount' =>
                                        'COUNT(DISTINCT uti.itemid,uti.userId)',
                                'itemCount' =>
                                        'COUNT(DISTINCT uti.itemId)',
                                'userCount' =>
                                        'COUNT(DISTINCT uti.userId)'))
                     ->group('t.tagId')
                     ->order($order);

        if (! @empty($tagIds))
        {
            // Tag Restrictions -- required 'userTagItem'
            $select->where('uti.tagId IN (?)', $tagIds);
        }

        if (! @empty($userIds))
        {
            // User Restrictions
            $select->where('uti.userId IN (?)', $userIds);
        }

        if (! @empty($itemIds))
        {
            // Item Restrictions
            $select->where('uti.itemId IN (?)', $itemIds);
        }

        /*
        printf ("Model_Tag::select: [ %s ]<br />\n", $select->assemble());
        // */

        return $select;
    }

    /** @brief  Retrieve a set of Tags that match the given set of tag, user,
     *          and/or item identifiers.
     *  @param  userIds     An array of user identifiers.
     *  @param  itemIds     An array of item identifiers.
     *  @param  tagIds      An array of tag identifiers.
     *  @param  asArray     Return as array records instead of instances?
     *
     *  @return An array of instances (or record arrays if 'asArray' == true).
     */
    public static function fetch($userIds = null,
                                 $itemIds = null,
                                 $tagIds  = null,
                                 $asArray = false)
    {
        $select = self::select($userIds, $itemIds, $tagIds);
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

    /*************************************************************************
     * Connexions_Model_Cached - abstract static method implementations
     *
     */

    /** @brief  Given a record identifier, generate an unique instance
     *          identifier.
     *  @param  id      The record identifier.
     *
     *  @return A unique instance identifier string.
     */
    protected static function _instanceId($id)
    {
        return __CLASS__ .'_'.  (! @empty($id['tagId'])
                                    ?  $id['tagId']
                                    : 'generic');
    }
}
