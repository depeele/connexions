<?php
/** @file
 *
 *  A set of Model_Tag instances.
 *
 */

class Model_TagSet extends Connexions_Set
{
    const       MEMBER_CLASS    = 'Model_Tag';

    protected   $_userIds       = null;
    protected   $_itemIds       = null;
    protected   $_tagIds        = null;

    protected   $_nonTrivial    = false;

    /** @brief  Create a new instance.
     *  @param  order       The maximum number of tags.
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
            $order = Zend_Registry::get('orderBy').
                     Zend_Registry::get('orderDir');

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
        $select = $db->select()
                     ->from(array('t' => $memberClass::$table))
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
            $select->where('uti.userId IN (?)', $userIds);
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

        //return parent::__construct($select, self::MEMBER_CLASS);
        return parent::__construct($select,
                                   $memberClass);
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

    /** @brief  Create a Zend_Tag_ItemList adapter for the top 'limit' tags.
     *  @param  limit   The maximum number of tags [100].
     *  @param  curTags The current set of selected tags (either a
     *                  comma-separated list or an associative array keyed by
     *                  tag name).
     *
     *
     *  Note: Since the default order is by 'userItemCount DESC', the top tags
     *        will be the tags with the highest count.
     *
     *  @return A Model_TagSet_ItemList instance
     *              (subclass of Zend_Tag_ItemList)
     *
     */
    public function get_Tag_ItemList($limit = null, $curTags = null)
    {
        if ($limit === null)
            $limit = 100;

        return new Model_TagSet_ItemList($this->getItems(0, $limit), $curTags);
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

        $select->reset(Zend_Db_Select::COLUMNS)
               ->reset(Zend_Db_Select::ORDER)
               ->reset(Zend_Db_Select::GROUP)
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

        $select->reset(Zend_Db_Select::COLUMNS)
               ->reset(Zend_Db_Select::ORDER)
               ->reset(Zend_Db_Select::GROUP)
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

        $select->reset(Zend_Db_Select::COLUMNS)
               ->reset(Zend_Db_Select::ORDER)
               ->reset(Zend_Db_Select::GROUP)
               ->columns('uti.tagId')
               ->distinct();

        return $select;
    }
}

/** @brief  An adapter to translate between Connexions_Set and
 *          Zend_Tag_ItemList
 *
 *  Note: This ASSUMES that the current set of tags are held within a 'tags'
 *        parameter within the current request.
 *
 *  Zend_Tag_ItemList implements
 *      Countable, SeekableIterator, ArrayAccess
 */
class Model_TagSet_ItemList extends Zend_Tag_ItemList
{
    protected   $_iterator      = null;
    protected   $_reqUrl        = null;
    protected   $_selectedTags  = array();

    /** @brief  Constructor
     *  @param  iterator    The Connexions_Set_Iterator instance that we will
     *                      adapt.
     *  @param  tags        The current set of selected tags
     *                      (either a comma-separated list or an associative
     *                       array keyed by tag name).
     */
    public function __construct(Connexions_Set_Iterator $iterator,
                                $tags = null)
    {
        $this->_iterator =& $iterator;

        /* Cache the request URL, urldecoding, collapsing spaces, and trimming
         * the right '/'
         */
        $this->_reqUrl = rtrim(preg_replace('/\s\s+/', ' ',
                                            urldecode(
                                                Connexions::getRequest()->
                                                            getRequestUri())
                                            ),
                               ' \t/');

        if (@is_string($tags))
        {
            // Collapse spaces, also removing any space around ','
            $tags = preg_replace('/\s*,\s*/', ',',
                                 preg_replace('/\s+/', ' ', urldecode($tags)));

            if (! @empty($tags))
            {
                $tags = explode(',', $tags);
            }
        }

        if (@is_array($tags))
        {
            /* Do a dirty check to see if 'tags' appears to be an associative
             * array with string keys.
             */
            $keys = array_keys($tags);
            if (! @is_string($keys[0]))
                // Appears to be a normal array with non-string / integer keys
                $tags = array_flip($tags);

            $this->_selectedTags = $tags;
        }
    }

    /** @brief  Spread values in the items relative to their weight.
     *  @param  values  An array of values to spread into.
     *
     *  @throws Zend_Tag_Exception  When value list is empty.
     *  @return void
     */
    public function spreadWeightValues(array $values)
    {
        // Modeled after Zend_Tag_ItemList::spreadWeightValues()
        $numValues = @count($values);

        if ($numValues < 1)
            throw new Zend_Tag_Exception('Value list may not be empty');

        // Re-index the array
        $values = array_values($values);

        // If just a single value is supplied, simply assign it to all tags
        if ($numValues === 1)
        {
            foreach ($this->_iterator as $item)
            {
                $item->setParam('weightValue', $values[0]);
            }
        }
        else
        {
            // Calculate min and max weights
            $minWeight = null;
            $maxWeight = null;

            foreach ($this->_iterator as $item)
            {
                if (($minWeight === null) && ($maxWeight === null))
                {
                    $minWeight = $item->getWeight();
                    $maxWeight = $item->getWeight();
                }
                else
                {
                    $minWeight = min($minWeight, $item->getWeight());
                    $maxWeight = max($maxWeight, $item->getWeight());
                }
            }

            // Calculate the thresholds
            $steps      = count($values);
            $delta      = ($maxWeight - $minWeight) / ($steps - 1);
            $thresholds = array();

            for ($idex = 0; $idex < $steps; $idex++)
            {
                $thresholds[$idex] =
                    floor(100 * log(($minWeight + ($idex * $delta)) + 2));
            }

            // Assign the weight values
            foreach ($this->_iterator as $item)
            {
                $threshold = floor(100 * log($item->getWeight() + 2));

                for ($idex = 0; $idex < $steps; $idex++)
                {
                    if ($threshold <= $thresholds[$idex])
                    {
                        $item->setParam('weightValue', $values[$idex]);
                        break;
                    }
                }
            }
        }
    }

    /*************************************************************************
     * Proxy methods for _iterator.
     *
     * These should cover Interfaces
     *  Countable, SeekableIterator, ArrayAccess, and portions of ArrayIterator
     */
    // Countable
    public function count()
                        { return $this->_iterator->count(); }

    // SeekableIterator
    public function seek($index)
                        { return $this->_iterator->seek($index); }

    // SeekableIterator::Iterator
    public function current()
    {
        $tag = $this->_iterator->current();

        /* Include additional parameters for this tag item:
         *      selected    boolean indicating whether this tag is in the
         *                  tag list for the current request / view;
         *      url         The url to visit if this tag is clicked.
         */
        $tagStr  = $tag->tag;
        $tagList = array_keys($this->_selectedTags);

        // Remove the tag list from the current URL
        $url = str_replace('/'.implode(',', $tagList), '', $this->_reqUrl);


        if (@isset($this->_selectedTags[$tagStr]))
        {
            // Remove this tag from the new tag list.
            $tag->setParam('selected', true);

            $tagList = array_diff($tagList, array($tagStr));
        }
        else
        {
            // Include this tag in the tag list.
            $tag->setParam('selected', false);

            $tagList[] = $tagStr;
        }

        $tag->setParam('url', $url .'/'. implode(',', $tagList) );

        /*
        Connexions::log(
                sprintf("Model_TagSet_ItemList::current: tag[ %s ], ".
                            "selected[ %s ]: is %sselected, url[ %s ]",
                        $tagStr,
                        implode(', ', array_keys($this->_selectedTags)),
                        ($tag->getParam('selected') ? '' : 'NOT '),
                        $tag->getParam('url') ));
        // */

        return $tag;
    }

    public function key()
                        { return $this->_iterator->key(); }
    public function next()
                        { return $this->_iterator->next(); }
    public function rewind()
                        { return $this->_iterator->rewind(); }
    public function valid()
                        { return $this->_iterator->valid(); }

    // ArrayAccess
    public function offsetExists($offset)
                        { return $this->_iterator->offsetExists($offset); }
    public function offsetGet($offset)
                        { return $this->_iterator->offsetGet($offset); }
    public function offsetSet($offset, $value)
                        { return $this->_iterator->offsetSet($offset, $value); }
    public function offsetUnset($offset)
                        { return $this->_iterator->offsetUnset($offset); }

    // ArrayIterator
    public function asort()
                        { return $this->_iterator->asort(); }
    public function ksort()
                        { return $this->_iterator->ksort(); }
    public function uasort($cmp_func)
                        { return $this->_iterator->uasort($cmp_func); }
    public function uksort($cmp_func)
                        { return $this->_iterator->uksort($cmp_func); }
}
