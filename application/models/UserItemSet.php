<?php
/** @file
 *
 *  A set of Model_UserItem instances.
 *
 */

class Model_UserItemSet extends Connexions_Set
{
    const       MEMBER_CLASS    = 'Model_UserItem';

    const       SORT_ORDER_ASC  = Zend_Db_Select::SQL_ASC;
    const       SORT_ORDER_DESC = Zend_Db_Select::SQL_DESC;

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

    /** @brief  Establish sorting for this set.
     *  @param  by          Any field of the memberClass.
     *  @param  order       Sort order
     *                      (Model_UserItemSet::SORT_ORDER_ASC|SORT_ORDER_DESC
     *                              ==
     *                      Zend_Db_Select::SQL_ASC           | SQL_DESC)
     *  @param  smartLimit  For fields that are part of item, should we limit 
     *                      the retrieved user items to only the earliest 
     *                      representative in order to reduce the amount of 
     *                      redundant data? [ true ].
     *
     *  Override in order to support order aliases, forcing sort by:
     *      'date'               => 'taggedOn'
     *      'item_url'           => 'item_url'
     *      'item_userCount'     => 'item_userCount'
     *      'item_ratingCount'   => 'item_ratingCount'
     *      'item_ratingSum'     => 'item_ratingSum'
     *      'user_name'          => 'user_name'
     *      'user_email'         => 'user_email'
     *      'user_lastVisit'     => 'user_lastVisit'
     *      'user_totalTags'     => 'user_totalTags' 
     *      'user_totalItems'    => 'user_totalItems'
     *
     *  @return $this
     */
    public function setOrder($by, $order, $smartLimit = true)
    {
        $orig   = $by;
        $force  = false;
        $group  = null;
        switch ($by)
        {
        case 'date':
            $by    = 'taggedOn';
            $force = true;
            break;

        case 'name':
        case 'item_url':
        case 'item_userCount':
        case 'item_ratingCount':
        case 'item_ratingSum':
            // Sorting by item informamtion.
            if ($smartLimit)
            {
                /* Also group by itemId and include 'taggedOn ASC' in the 
                 * order-by so we'll only retrieve the earliest, representative 
                 * userItem.
                 *
                 * Otherwise, we'll retrieve all user items for each item type, 
                 * which, when presented, will show a large amount of redundant 
                 * data (i.e. all item information will be identical).
                 */
                $by    = array("{$by} {$order}", 'taggedOn ASC');
                $group = 'item_itemId';
            }
            $force = true;
            break;

        case 'user_name':
        case 'user_email':
        case 'user_lastVisit':
        case 'user_totalTags':
        case 'user_totalItems':
            $force = true;
            break;
        }

        // /*
        if ($force || ($orig != $by))
        {
            if (is_array($by))
                $byStr = var_export($by, true);
            else
                $byStr = $by;

            Connexions::log("Model_UserItem::setOrder: "
                                . "Alias '{$orig}' to '{$byStr}', '{$order}'");
        }
        else
        {
            Connexions::log("Model_UserItem::setOrder: "
                                . "Pass on '{$by}'");
        }
        // */


        parent::setOrder($by, $order, $force);

        if ($group !== null)
            $this->_select->group('ui.itemId');

        // /*
        Connexions::log("Model_UserItem::setOrder: "
                            . "sql[ {$this->_select->assemble()} ]");
        // */

        return $this;
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
