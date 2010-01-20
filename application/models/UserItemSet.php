<?php
/** @file
 *
 *  A set of Model_UserItem instances.
 *
 */

class Model_UserItemSet extends Connexions_Set
{
    const       MEMBER_CLASS    = 'Model_UserItem';

    protected   $_tagIds        = null;
    protected   $_userIds       = null;
    protected   $_itemIds       = null;

    protected   $_nonTrivial    = false;

    protected   $_select_items  = null;
    protected   $_select_users  = null;

    /** @brief  Create a new instance.
     *  @param  tagIds      An array of tag identifiers.
     *  @param  userIds     An array of user identifiers.
     *  @param  itemIds     An array of item identifiers.
     *
     */
    public function __construct($tagIds   = null,
                                $userIds  = null,
                                $itemIds  = null)
    {
        $memberClass = self::MEMBER_CLASS;

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


        /* Include all columns/fields from Item and User, prefixed.
         * Note: These will be used by Model_UserItem (self::MEMBER_CLASS) to
         *       provide access to the referenced User and Item.
         */
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

        // Generate a Zend_Db_Select instance
        $db     = Connexions::getDb();
        $table  = Connexions_Model::__sget($memberClass, 'table');

        $select = $db->select()
                     ->from(array('ui' => $table))
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
            $this->_nonTrivial = true;
        }

        if (! @empty($userIds))
        {
            // User Restrictions
            $select->where('u.userId IN (?)', $userIds);
            $this->_nonTrivial = true;
        }

        if (! @empty($itemIds))
        {
            // Item Restrictions
            $select->where('i.itemId IN (?)', $itemIds);
            $this->_nonTrivial = true;
        }

        /*
        Connexions::log("Model_UserItemSet: "
                            . "select[ ". $select->assemble() ." ]");
        // */

        // Include '_memberClass' in $select so we can use 'Connexions_Set'
        $select->_memberClass = $memberClass;

        $this->_tagIds  = $tagIds;
        $this->_userIds = $userIds;
        $this->_itemIds = $itemIds;

        return parent::__construct($select, $memberClass);
    }

    /** @brief  Retrieve the array of item identifiers for all items in this
     *          set.
     *
     *  @return An array of item identifiers.
     */
    public function itemIds()
    {
        if ($this->_nonTrivial !== true)
        {
            /*
            Connexions::log("UserItemSet::itemIds: "
                                . "trivial [ "
                                .       implode(', ', $this->_itemIds) ." ]");
            // */

            return $this->_itemIds;
        }

        $select = $this->_select_items();

        /*
        Connexions::log("UserItemSet::itemIds: "
                            . "non-trivial, sql [ ". $select->assemble() ." ]");
        // */

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
               ->group('ui.itemId')
               ->columns('ui.itemId')
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
               ->group('ui.userId')
               ->columns('ui.userId')
               ->distinct();

        return $select;
    }

}
