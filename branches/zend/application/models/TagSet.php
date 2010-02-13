<?php
/** @file
 *
 *  A set of Model_Tag instances.
 *
 */

class Model_TagSet extends Connexions_Set
{
    const       MEMBER_CLASS    = 'Model_Tag';

    const       SORT_ORDER_ASC  = Zend_Db_Select::SQL_ASC;
    const       SORT_ORDER_DESC = Zend_Db_Select::SQL_DESC;

    protected   $_userIds       = null;
    protected   $_itemIds       = null;
    protected   $_tagIds        = null;

    protected   $_nonTrivial    = false;

    /** @brief  Create a new instance.
     *  @param  userIds     An array of user identifiers.
     *  @param  itemIds     An array of item identifiers.
     *  @param  tagIds      An array of tag identifiers.
     *
     */
    public function __construct($userIds    = null,
                                $itemIds    = null,
                                $tagIds     = null)
    {
        $memberClass  = self::MEMBER_CLASS;

        // :TODO: Determine the proper order.
        try {
            $order = Zend_Registry::get('orderBy') .' '
                   . Zend_Registry::get('orderDir');

        } catch (Exception $e) {
            $order = 'userItemCount DESC';
        }

        if ( (! @empty($userIds)) && (! @is_array($userIds)) )
            $userIds = array($userIds);
        if ( (! @empty($itemIds)) && (! @is_array($itemIds)) )
            $itemIds = array($itemIds);
        if ( (! @empty($tagIds)) && (! @is_array($tagIds)) )
            $tagIds = array($tagIds);


        // Generate a Zend_Db_Select instance
        $db     = Connexions::getDb();
        $table  = Connexions_Model::__sget($memberClass, 'table');

        $select = $db->select()
                     ->from(array('t' => $table))
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

        if (! @empty($userIds))
        {
            // User Restrictions
            $select->where('uti.userId IN (?)', $userIds)
                   ->having('userCount='.count($userIds));
            $this->_nonTrivial = true;
        }

        if (! @empty($itemIds))
        {
            // Item Restrictions
            $select->where('uti.itemId IN (?)', $itemIds);
            $this->_nonTrivial = true;
        }

        if (! @empty($tagIds))
        {
            // Tag Restrictions -- required 'userTagItem'
            $select->where('uti.tagId IN (?)', $tagIds);
            $this->_nonTrivial = true;
        }

        // Remember the original user, item, and tag identifiers.
        $this->_userIds = $userIds;
        $this->_itemIds = $itemIds;
        $this->_tagIds  = $tagIds;

        /*
        Connexions::log(
                sprintf("Model_TagSet: select[ %s ]\n",
                        $select->assemble()) );
        // */

        return parent::__construct($select, $memberClass);
    }

    /** @brief  Modify the tag restrictions to allow a match if ANY user has
     *          used any of the specified tags (vs all).
     *
     *  @return $this
     */
    public function withAnyUser()
    {
        $this->_select->reset(Zend_Db_Select::HAVING);

        return $this;
    }

    /** @brief  Limit the selection to tags matching the provided sub-string
     *  @param  substr  The substring.
     *
     *  @return $this
     */
    public function like($substr)
    {
        $this->_select->where('t.tag LIKE ?', '%'.$substr.'%');

        /*
        Connexions::log(
                sprintf("Model_TagSet: like[ %s ]\n",
                        $this->_select->assemble()) );
        // */

        return $this;
    }

    /** @brief  Set the weighting.
     *  @param  by      Weight by ('user', 'item', 'userItem').
     *
     *  @return $this
     */
    public function weightBy($by)
    {
        $cols = array();

        switch (strtolower($by))
        {
        case 'user':
            $cols['weight'] = 'COUNT(DISTINCT uti.userId)';
            break;

        case 'item':
            $cols['weight'] = 'COUNT(DISTINCT uti.itemId)';
            break;

        case 'useritem':
        default:            // Default to 'userItem'
            $cols['weight'] = 'COUNT(DISTINCT uti.userId,uti.itemId)';
            break;
        }

        $this->_select->columns($cols)
                      ->order('weight DESC');

        /*
        Connexions::log("Model_TagSet::weightBy({$by}): "
                            . "sql[ ". $this->_select->assemble() ." ]");
        // */

        return $this;
    }

    /** @brief  Retrieve the array of item identifiers of all userItems that
     *          include any of the tags represented by this set.
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

    /** @brief  Retrieve the array of user identifiers of all userItems that
     *          include any of the tags represented by this set.
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

    /** @brief  Retrieve the array of all tag identifiers.
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

    /** @brief  Construct the comma-separated string representing this set of
     *          tags.
     *
     *  @return An comma-separated string.
     */
    public function __toString()
    {
        $recs = $this->_select->query()->fetchAll();

        /* Convert the returned array of records to a simple, comma-separated
         * string
         */
        $tags = array();
        foreach ($recs as $idex => $row)
        {
            $tags[] = $row['tag'];
        }

        return implode(',', $tags);
    }

    /*************************************************************************
     * Protected helpers methods
     *
     */

    /** @brief  Return a Zend_Db_Select instance capable of retrieving the item
     *          identifiers of all userItems included in this set.
     *
     *  @return A Zend_Db_Select instance capable of retrieving the item
     *          identifiers of all userItems included in this set.
     *
     *          Note: This MAY be different than $this->_itemIds
     */
    protected function _select_items()
    {
        $select = clone $this->_select;

        $select//->reset(Zend_Db_Select::COLUMNS)
               //->reset(Zend_Db_Select::ORDER)
               //->reset(Zend_Db_Select::GROUP)
               ->columns('uti.itemId')
               ->distinct();

        return $select;
    }

    /** @brief  Return a Zend_Db_Select instance capable of retrieving the user
     *          identifiers of all userItems included in this set.
     *
     *  @return A Zend_Db_Select instance capable of retrieving the user
     *          identifiers of all userItems included in this set.
     *
     *          Note: This MAY be different than $this->_userIds
     */
    protected function _select_users()
    {
        $select = clone $this->_select;

        $select//->reset(Zend_Db_Select::COLUMNS)
               //->reset(Zend_Db_Select::ORDER)
               //->reset(Zend_Db_Select::GROUP)
               ->columns('uti.userId')
               ->distinct();

        return $select;
    }

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

        $select//->reset(Zend_Db_Select::COLUMNS)
               //->reset(Zend_Db_Select::ORDER)
               //->reset(Zend_Db_Select::GROUP)
               ->columns('uti.tagId')
               ->distinct();

        return $select;
    }
}
