<?php
/** @file
 *
 *  A set of Model_UserItem instances.
 *
 */

class Model_UserItemSet extends Connexions_Set
{
    const       MEMBER_CLASS    = 'Model_UserItem';

    protected   $_userIds       = null;
    protected   $_itemIds       = null;
    protected   $_tagIds        = null;

    protected   $_select_items  = null;
    protected   $_select_users  = null;

    // Have we added a join to the Item table yet?
    protected   $_itemJoined    = false;

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
        /*
        Connexions::log("Model_UserItemSet: "
                            . "tagIds [ ". print_r($tagIds, true)  ." ], "
                            . "userIds[ ". print_r($userIds, true) ." ], "
                            . "itemIds[ ". print_r($itemIds, true) ." ]");
        // */


        // Determine the current, authenticated user.
        try {
            $curUserId = Zend_Registry::get('user')->userId;

        } catch (Exception $e) {
            // Treat the current user as Unauthenticated.
            $curUserId = null;
        }

        if ($tagIds instanceof Zend_Db_Select)
        {
            return parent::__construct($tagIds, self::MEMBER_CLASS);
        }

        $select = $this->_commonSelect(self::MEMBER_CLASS,
                                       $userIds, $itemIds, $tagIds);

        // Use a default order.
        $select->order('ui.taggedOn ASC');

        /*
        Connexions::log("Model_UserItemSet: "
                            . "select[ ". $select->assemble() ." ]");
        // */

        $this->_userIds = $userIds;
        $this->_itemIds = $itemIds;
        $this->_tagIds  = $tagIds;

        $res = parent::__construct($select, self::MEMBER_CLASS);

        return $res;
    }

    /** @brief  Retrieve a set of items that are related to this set.
     *  @param  type    The type of item (Connexions_Set::RELATED_*).
     *  @param  tagIds  Any additional tag restrictions.
     *
     *  @return The new Connexions_Set instance.
     */
    public function getRelatedSet($type, $tagIds = null)
    {
        return parent::getRelatedSet($type,
                                     $this->userIds(),  // userIds
                                     $this->itemIds(),  // itemIds
                                     $tagIds);          // tagIds
    }

    /** @brief  Retrieve the array of user identifiers for all users in this
     *          set.
     *
     *  @return An array of user identifiers.
     */
    public function userIds()
    {
        if ( (! empty($this->_userIds)) ||
               (empty($this->_itemIds) &&
                empty($this->_tagIds)) )
        {
            // Trivially the set of ids we were given
            if ($this->_userIds !== null)
                return $this->_userIds;
            else
                // ALL userIds
                return array();
        }

        $select = $this->_select_users();

        /*
        Connexions::log('Model_UserItemSet::userIds(): '
                        . "sql [ {$select->assemble()} ], retrieved "
                        . count($recs) .' records');
        // */

        $recs   = $select->query()->fetchAll();

        // Convert the returned array of records to a simple array of ids
        $ids    = array();
        foreach ($recs as $idex => $row)
        {
            $ids[] = $row['userId']; // $row[0];
        }

        // Cache the ids
        $this->_userIds = $ids;

        return $ids;
    }

    /** @brief  Retrieve the array of item identifiers for all items in this
     *          set.
     *
     *  @return An array of item identifiers.
     */
    public function itemIds()
    {
        if ( (! empty($this->_itemIds)) ||
               (empty($this->_userIds) &&
                empty($this->_tagIds)) )
        {
            // Trivially the set of ids we were given
            if ($this->_itemIds !== null)
                return $this->_itemIds;
            else
                // ALL userIds
                return array();
        }

        $select = $this->_select_items();
        $recs   = $select->query()->fetchAll();

        /*
        Connexions::log('Model_UserItemSet::itemIds(): '
                        . "sql [ {$select->assemble()} ], retrieved "
                        . count($recs) .' records');
        // */


        // Convert the returned array of records to a simple array of ids
        $ids    = array();
        foreach ($recs as $idex => $row)
        {
            $ids[] = $row['itemId']; // $row[0];
        }

        // Cache the ids
        $this->_itemIds = $ids;

        return $ids;
    }

    /** @brief  Retrieve the array of tag identifiers for all tags related to
     *          userItems in this set.
     *
     *  @return An array of tag identifiers.
     */
    public function tagIds()
    {
        if ( (! empty($this->_tagIds)) ||
               (empty($this->_userIds) &&
                empty($this->_itemIds)) )
        {
            // Trivially the set of ids we were given
            if ($this->_tagIds !== null)
                return $this->_tagIds;
            else
                // ALL userIds
                return array();
        }

        $select = $this->_select_tags();
        $recs   = $select->query()->fetchAll();

        /*
        Connexions::log('Model_UserItemSet::tagIds(): '
                        . "sql [ {$select->assemble()} ], retrieved "
                        . count($recs) .' records');
        // */

        // Convert the returned array of records to a simple array of ids
        $ids    = array();
        foreach ($recs as $idex => $row)
        {
            $ids[] = $row['tagId']; // $row[0];
        }

        // Cache the ids
        $this->_tagIds = $ids;

        return $ids;
    }

    /** @brief  Map a field name.
     *  @param  name    The provided name.
     *
     *  @return The new, mapped name (null if the name is NOT a valid field).
     */
    public function mapField($name)
    {
        switch ($name)
        {
        // Convenience
        case 'date':
            $name  = 'taggedOn';
            break;

        // sub-instance names
        case 'item_url':
        case 'item_userCount':
        case 'item_ratingCount':
        case 'item_ratingSum':
            break;

        default:
            $name = parent::mapField($name);
            break;
        }

        return $name;
    }

    /** @brief  Establish sorting for this set.
     *  @param  order       A string or array of strings identifying the
     *                      field(s) to sort by.  Each may also, optionally
     *                      include a sorting order (ASC, DESC) following the
     *                      field name, separated by a space.
     *  @param  smartLimit  For fields that are part of item, should we limit 
     *                      the retrieved user items to only the earliest 
     *                      representative in order to reduce the amount of 
     *                      redundant data? [ true ].
     *
     *  @return $this
     */
    public function setOrder($order, $smartLimit = true)
    {
        $group = null;
        if ($smartLimit)
        {
            if (! is_array($order)) $order = array($order);

            $newOrder = array();
            foreach ($order as $spec)
            {
                $orderParts = $this->_parse_order($spec);
                if ($orderParts === null)
                {
                    /*
                    Connexions::log("Model_UserItemSet::setOrder: "
                                    . "Invalid specification [{$spec}] --skip");
                    // */
                    continue;
                }

                // Check for special fields
                switch ($orderParts[0])
                {
                case 'name':
                case 'item_url':
                case 'item_userCount':
                case 'item_ratingCount':
                case 'item_ratingSum':
                    /* Sorting by item informamtion.
                     *
                     * Also group by itemId and include 'taggedOn ASC' in the
                     * order-by so we'll only retrieve the earliest,
                     * representative userItem.
                     *
                     * Otherwise, we'll retrieve all user items for each item
                     * type, which, when presented, will show a large amount of
                     * redundant data
                     * (i.e. all item information will be identical).
                     */

                    /* If our query doesn't already include a join with the 
                     * item table, add it now.
                     */
                    $this->_joinItem();

                    $group = 'item_itemId';
                    array_push($newOrder, implode(' ', $orderParts));
                    array_push($newOrder, 'taggedOn ASC');
                    break;

                default:
                    array_push($newOrder, implode(' ', $orderParts));
                    break;
                }
            }
        }

        parent::setOrder($order);

        if ($group !== null)
            $this->_select->group('ui.itemId');

        /*
        Connexions::log("Model_UserItemSet::setOrder: "
                            . "order[ ". print_r($order, true) ." ], "
                            . "sql[ {$this->_select->assemble()} ]");
        // */

        return $this;
    }

    /*************************************************************************
     * Protected helpers methods
     *
     */

    /** @brief  If our current query doesn't already include a join with the 
     *          Item table, add the join now.
     *
     *  @return $this
     */
    protected function _joinItem()
    {
        if ($this->_itemJoined)
            return $this;

        // Include all columns/fields from Item, prefixed by 'item_'.
        $itemColumns = array();
        foreach (Model_Item::$model as $field => $type)
        {
            $itemColumns['item_'.$field] = 'i.'.$field;
        }

        $this->_select->joinLeft(array('i'  => 'item'),     // table / as
                                 '(i.itemId=ui.itemId)',    // condition
                                 $itemColumns);             // columns

        /*
        Connexions::log("Model_UserItemSet::_joinItem: sql[ %s ]",
                        $this->_select->assemble());
        // */

        $this->_itemJoined = true;

        return $this;
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
               ->columns('userId',   'ui')
               ->group('ui.userId');

        /*
        Connexions::log("Model_UserItemSet::_select_users(): sql[ %s ]",
                        $select->assemble());
        // */

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
               ->columns('itemId', 'ui')
               ->group('ui.itemId');

        /*
        Connexions::log("Model_UserItemSet::_select_items(): sql[ %s ]",
                        $select->assemble());
        // */

        return $select;
    }

    /** @brief  Return a Zend_Db_Select instance capable of retrieving the tag
     *          identifiers of all tags relalted to the userItems represented
     *          by this set.
     *
     *  @return A Zend_Db_Select instance capable of retrieving the item
     *          identifiers of the userItems represented by this set.
     */
    protected function _select_tags()
    {
        $select = clone $this->_select;

        $select->reset(Zend_Db_Select::COLUMNS)
               ->reset(Zend_Db_Select::ORDER)
               ->reset(Zend_Db_Select::GROUP)
               ->columns('tagId', 'uti')
               ->group('uti.tagId');

        /*
        Connexions::log("Model_UserItemSet::_select_tags(): sql[ %s ]",
                        $select->assemble());
        // */

        return $select;
    }
}
