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
        $db   = Connexions::getDb();

        /* :TODO: Determine the proper order.
         */
        $sortOrder        = 't.tag ASC';
        $restrictUserItem = ( (! @empty($userIds)) || (! @empty($itemIds)) );

        // Generate the SQL
        $sql = 'SELECT t.*'
             .  ' FROM tag t, userTagItem uti'
             .  ' WHERE (t.tagId = uti.tagId)'

                        // Tag restrictions
             .          (! @empty($tagIds)
                            ? ' AND (uti.tagId IN ('.
                                                implode(',',$tagIds).'))'
                             : '')

                        // User restrictions
             .          (! @empty($userIds)
                             ? ' AND (uti.userId IN ('.
                                                implode(',',$userIds).'))'
                             : '')

                        // Item restrictions
             .          (! @empty($itemIds)
                             ? ' AND (uti.itemId IN ('.
                                                implode(',',$itemIds).'))'
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
            foreach ($recs as $idex => $row)
            {
                // Create an instance with this backed record.
                $row['@isBacked'] = true;
                $inst = self::find($row, $db);  //new self($row, $db);

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
