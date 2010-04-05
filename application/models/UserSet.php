<?php
/** @file
 *
 *  A set of Model_User instances.
 *
 */

class Model_UserSet extends Connexions_Set
{
    const       MEMBER_CLASS    = 'Model_User';

    protected   $_userIds       = null;
    protected   $_itemIds       = null;
    protected   $_tagIds        = null;

    protected   $_weightSet     = false;

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
        if ($tagIds instanceof Zend_Db_Select)
        {
            return parent::__construct($tagIds, self::MEMBER_CLASS);
        }

        $select = $this->_commonSelect(self::MEMBER_CLASS,
                                       $userIds, $itemIds, $tagIds,
                                       false);  // NOT exact tags

        // Use a default order.
        $select->order('u.lastVisit DESC');

        $this->_userIds = $userIds;
        $this->_itemIds = $itemIds;
        $this->_tagIds  = $tagIds;

        // /*
        Connexions::log(
                sprintf("Model_UserSet: select[ %s ]<br />\n",
                        $select->assemble()) );
        // */

        return parent::__construct($select, self::MEMBER_CLASS);
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
        case 'title':
            $name  = 'name';
            break;

        case 'weight':
            if (! $this->_weightSet)
            {
                /* No weight has yet been set.
                 *
                 * Set it to the default (but don't reorder).
                 */
                $this->weightBy(null, false);
            }
            break;

        default:
            $name = parent::mapField($name);
            break;
        }

        return $name;
    }

    /** @brief  Modify the tag restrictions to allow a match if a user has used
     *          ANY of the specified tags (vs all).
     *
     *  @return $this
     */
    public function withAnyTag()
    {
        $this->_select->reset(Zend_Db_Select::HAVING);

        return $this;
    }

    /** @brief  Set the weighting.
     *  @param  by      Weight by ('tag', 'item', [ 'userItem' ]).
     *  @param  reorder Should ordering be changed? [ true ]
     *
     *  @return $this
     */
    public function weightBy($by = null, $reorder = true)
    {
        $cols = array();

        // /*
        Connexions::log("Model_UserSet::weightBy(%s): init sql[ %s ]",
                         $by, $this->_select->assemble());
        // */

        switch ($by)
        {
        case 'tag':
            $cols['weight'] = 'uti.tagCount';
            break;

        case 'item':
            $cols['weight'] = 'uti.itemCount';
            break;

        case 'userItem':
        default:            // Default to 'userItem'
            $cols['weight'] = 'uti.userItemCount';
            break;
        }

        $this->_select->columns($cols);

        if ($reorder === true)
        {
            $this->_select->reset(Zend_Db_Select::ORDER)
                          ->order('weight DESC');
        }

        $this->_weightSet = true;

        /*
        Connexions::log("Model_UserSet::weightBy({$by}): "
                            . "sql[ ". $this->_select->assemble() ." ]");
        // */

        return $this;
    }

    /** @brief  Retrieve a set of items that are related to this set.
     *  @param  type    The type of item (Connexions_Set::RELATED_*).
     *
     *  @return The new Connexions_Set instance.
     */
    public function getRelatedSet($type)
    {
        return parent::getRelatedSet($type,
                                     $this->userIds(),  // userIds
                                     null,              // itemIds
                                     null);             // tagIds
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
        $recs   = $select->query()->fetchAll();

        // Convert the returned array of records to a simple array of ids
        $ids    = array();
        foreach ($recs as $idex => $row)
        {
            $ids[] = $row['userId']; // $row[0];
        }

        /*
        Connexions::log("Model_UserSet::userIds:  [ ".
                            implode(', ', $ids) ." ]");
        // */

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

        // Convert the returned array of records to a simple array of ids
        $ids    = array();
        foreach ($recs as $idex => $row)
        {
            $ids[] = $row['itemId']; // $row[0];
        }

        return $ids;
    }

    /** @brief  Retrieve the array of all tag identifiers used by this set of
     *          users.
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

        // Convert the returned array of records to a simple array of ids
        $ids    = array();
        foreach ($recs as $idex => $row)
        {
            $ids[] = $row['tagId'];
        }

        return $ids;
    }

    /*************************************************************************
     * Protected helpers methods
     *
     */

    /** @brief  Return a Zend_Db_Select instance capable of retrieving the user
     *          identifiers of the users represented by this set.
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
               ->columns('userId', 'uti')
               ->group('uti.userId');

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
               ->columns('itemId', 'uti')
               ->group('uti.itemId');

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

        $select->reset(Zend_Db_Select::COLUMNS)
               ->reset(Zend_Db_Select::ORDER)
               ->reset(Zend_Db_Select::GROUP)
               ->columns('tagId', 'uti')
               ->group('uti.tagId');

        return $select;
    }
}
