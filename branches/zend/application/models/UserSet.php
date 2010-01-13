<?php
/** @file
 *
 *  A set of Model_User instances.
 *
 */

class Model_UserSet extends Connexions_Set
{
    const       MEMBER_CLASS    = 'Model_User';

    protected   $_tagIds        = null;
    protected   $_itemIds       = null;
    protected   $_userIds       = null;

    protected   $_nonTrivial    = false;

    /** @brief  Create a new instance.
     *  @param  tagIds      An array of tag identifiers.
     *  @param  itemIds     An array of item identifiers.
     *  @param  userIds     An array of user identifiers.
     *
     */
    public function __construct($tagIds   = null,
                                $itemIds  = null,
                                $userIds  = null)
    {
        $memberClass  = self::MEMBER_CLASS;

        // :TODO: Determine the proper order.
        try {
            $order = Zend_Registry::get('orderBy').
                     Zend_Registry::get('orderDir');

        } catch (Exception $e) {
            $order = 'u.name ASC';
        }

        if ( (! @empty($tagIds)) && (! @is_array($tagIds)) )
            $tagIds = array($tagIds);
        if ( (! @empty($itemIds)) && (! @is_array($itemIds)) )
            $itemIds = array($itemIds);
        if ( (! @empty($userIds)) && (! @is_array($userIds)) )
            $userIds = array($userIds);

        // Generate a Zend_Db_Select instance
        $db     = Connexions::getDb();
        $select = $db->select()
                     ->from(array('u' => $memberClass::$table))
                     ->join(array('uti'   => 'userTagItem'),  // table / as
                                  '(u.userId=uti.userId)',    // condition
                                  '')                         // columns (none)
                     ->group('u.userId')
                     ->order($order);

        if (! @empty($tagIds))
        {
            // Tag Restrictions -- required 'userTagItem'
            $select->where('uti.tagId IN (?)', $tagIds)
                   ->having('COUNT(DISTINCT uti.tagId)='.count($tagIds));
            $this->_nonTrivial = true;
        }

        if (! @empty($itemIds))
        {
            // Item Restrictions
            $select->where('uti.itemId IN (?)', $itemIds);
            $this->_nonTrivial = true;
        }

        if (! @empty($userIds))
        {
            // User Restrictions
            $select->where('u.userId IN (?)', $userIds);
            $this->_nonTrivial = true;
        }


        // Include '_memberClass' in $select so we can use 'Connexions_Set'
        $select->_memberClass = $memberClass;   //self::MEMBER_CLASS;

        $this->_tagIds  = $tagIds;
        $this->_userIds = $userIds;
        $this->_itemIds = $itemIds;

        /*
        printf ("Model_UserSet: select[ %s ]<br />\n", $select->assemble());
        // */

        return parent::__construct($select, $memberClass);
    }

    /** @brief  Retrieve the array of all tag identifiers used by this set of
     *          users.
     *
     *  @return An array of tag identifiers.
     */
    public function tagIds()
    {
        if ($this->_nonTrivial !== true)
            return $this->_tagIds;

        $select = $this->_select_tags();
        $recs   = $select->query()->fetchAll();

        // Convert the returned array of records to a simple array of ids
        $ids    = array();
        foreach ($recs as $idex => $row)
        {
            $ids[] = $row['tagId'];
        }

        return $ids;
    }

    /** @brief  Retrieve the array of item identifiers for all items in this
     *          set.
     *
     *  @return An array of item identifiers.
     */
    public function itemIds()
    {
        if ($this->_nonTrivial !== true)
            return $this->_itemIds;

        $select = $this->_select_items();
        $recs   = $select->query()->fetchAll();

        // Convert the returned array of records to a simple array of ids
        $ids    = array();
        foreach ($recs as $idex => $row)
        {
            $ids[] = $row['itemId']; // $row[0];
        }

        return $ids;
    }

    /** @brief  Retrieve the array of user identifiers for all users in this
     *          set.
     *
     *  @return An array of user identifiers.
     */
    public function userIds()
    {
        if ($this->_nonTrivial !== true)
            return $this->_userIds;

        $select = $this->_select_users();
        $recs   = $select->query()->fetchAll();

        // Convert the returned array of records to a simple array of ids
        $ids    = array();
        foreach ($recs as $idex => $row)
        {
            $ids[] = $row['userId']; // $row[0];
        }

        return $ids;
    }

    /*************************************************************************
     * Protected helpers methods
     *
     */

    /** @brief  Return a Zend_Db_Select instance capable of retrieving the tag
     *          identifiers of all userItems included in this set.
     *
     *  @return A Zend_Db_Select instance capable of retrieving the tag
     *          identifiers of all userItems included in this set.
     *
     *          Note: This MAY be different than $this->_tagIds
     */
    protected function _select_tags()
    {
        $select = clone $this->_select;

        $select->reset(Zend_Db_Select::COLUMNS)
               ->reset(Zend_Db_Select::ORDER)
               ->reset(Zend_Db_Select::GROUP)
               ->columns('uti.tagId')
               ->distinct();

        return $select;
    }

    /** @brief  Return a Zend_Db_Select instance capable of retrieving the item
     *          identifiers of the userItems represented by this set.
     *
     *  @return A Zend_Db_Select instance capable of retrieving the item
     *          identifiers of the userItems represented by this set.
     *
     *          Note: This MAY be different than $this->_itemIds
     */
    protected function _select_items()
    {
        $select = clone $this->_select;

        $select->reset(Zend_Db_Select::COLUMNS)
               ->reset(Zend_Db_Select::ORDER)
               ->reset(Zend_Db_Select::GROUP)
               ->columns('uti.itemId')
               ->distinct();

        return $select;
    }

    /** @brief  Return a Zend_Db_Select instance capable of retrieving the user
     *          identifiers of the userItems represented by this set.
     *
     *  @return A Zend_Db_Select instance capable of retrieving the item
     *          identifiers of the userItems represented by this set.
     *
     *          Note: This MAY be different than $this->_userIds
     */
    protected function _select_users()
    {
        $select = clone $this->_select;

        $select->reset(Zend_Db_Select::COLUMNS)
               ->reset(Zend_Db_Select::ORDER)
               ->reset(Zend_Db_Select::GROUP)
               ->columns('u.userId')
               ->distinct();

        return $select;
    }
}
