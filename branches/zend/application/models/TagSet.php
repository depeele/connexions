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
     *  @param  limit       The maximum number of tags [100].
     *  @param  reqTags     A comma-separated string representing the tags that
     *                      are currently specified in the request;
     *  @param  reqTagInfo  Null or an array of tag information (from
     *                      Model_Tag::ids()) that identifies both valid and
     *                      invalid tags from 'reqTags';
     *
     *  Note: Since the default order is by 'userItemCount DESC', the top tags
     *        will be the tags with the highest count.
     *
     *  @return A Model_TagSet_ItemList instance
     *              (subclass of Zend_Tag_ItemList)
     *
     */
    public function get_Tag_ItemList($limit         = null,
                                     $reqTags       = null,
                                     $reqTagInfo    = null)
    {
        if ($limit === null)
            $limit = 100;

        return new Model_TagSet_ItemList($this->getItems(0, $limit),
                                         $reqTags,
                                         $reqTagInfo);
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
 *  This class provides lazy evaluation of the list items, returning a
 *  Model_Tag instance during iteration that contains additional contextual
 *  information (i.e. whether or not the tag is currently selected and the URL
 *                    to use when presenting the tag).
 *
 *  Zend_Tag_ItemList implements
 *      Countable, SeekableIterator, ArrayAccess
 */
class Model_TagSet_ItemList extends Zend_Tag_ItemList
{
    protected   $_iterator      = null;
    protected   $_reqUrl        = null;
    protected   $_reqTags       = null;
    protected   $_reqTagInfo    = null;

    /** @brief  Constructor
     *  @param  iterator    The Connexions_Set_Iterator instance that we will
     *                      adapt.
     *  @param  reqTags     A comma-separated string representing the tags that
     *                      are currently specified in the request;
     *  @param  reqTagInfo  Null or an array of tag information (from
     *                      Model_Tag::ids()) that identifies both valid and
     *                      invalid tags from 'reqTags';
     */
    public function __construct(Connexions_Set_Iterator $iterator,
                                $reqTags    = null,
                                $reqTagInfo = null)
    {
        $this->_iterator =& $iterator;

        if ( (! @empty($reqTags)) && (! @is_array($reqTagInfo)) )
        {
            // Generate the 'reqTagInfo' information from the provided 'reqTags'
            $reqTagInfo = Model_Tag::ids($reqTags);
        }

        /* Retrieve the current request URL.  Simplify it by removing any
         * query/fragment, urldecoding, collapsing spaces, trimming any
         * right-most white-space and ending '/'
         */
        $uri = Connexions::getRequestUri();
        $uri = preg_replace('/[\?#].*$/', '',  $uri);   // query/fragment
        $uri = urldecode($uri);
        $uri = preg_replace('/\s\s+/',    ' ', $uri);   // white-space collapse
        $uri = rtrim($uri, " \t\n\r\0\x0B/");

        $this->_reqUrl     =  $uri;
        $this->_reqTagInfo =& $reqTagInfo;

        if (@is_string($reqTags))
        {
            /* decode, collapse spaces, and removing any space around ',' from
             * the incoming 'reqUrl'
             */
            $tags = urldecode($reqTags);
            $tags = preg_replace('/\s+/',     ' ', $tags);
            $tags = preg_replace('/\s*,\s*/', ',', $tags);

            $this->_reqTags = $tags;
        }

        /*
        Connexions::log(sprintf("Model_TagSet_ItemList:: ".
                                    "reqUrl[ %s ], ".
                                    "reqTags[ %s ], ".
                                    "reqTagInfo[ %s ]",
                                $this->_reqUrl,
                                $this->_reqTags,
                                print_r($this->_reqTagInfo, true)) );
        // */
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
    /** @brief  Retrieve an instance of the current item.
     *
     *  @return A Model_Tag instance.
     */
    public function current()
    {
        $tag = $this->_iterator->current();
        if (($tag instanceof Model_Tag) &&
            (@is_bool($tag->getParam('selected', true))) )
        {
            // We've already processed this item.
            return $tag;
        }

        /* Include additional parameters for this tag item:
         *      selected    boolean indicating whether this tag is in the
         *                  tag list for the current request / view;
         *      url         The url to visit if this tag is clicked.
         */
        $tagStr  = $tag->tag;
        $tagList = (@is_array($this->_reqTagInfo)
                        ? array_keys($this->_reqTagInfo['valid'])
                        : null);

        $url = $this->_reqUrl;
        if (! @empty($this->_reqTags))
            // Remove the requested tags from the request URL
            $url = str_replace('/'. $this->_reqTags, '', $url);

        if (@is_array($this->_reqTagInfo['valid']) &&
            @isset($this->_reqTagInfo['valid'][$tagStr]))
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
        $url .= '/'. implode(',', $tagList);

        $tag->setParam('url', $url);

        /*
        Connexions::log(
                sprintf("Model_TagSet_ItemList::current: reqUrl[ %s ], ".
                            "tag[ %s ], selected[ %s ]: ".
                            "is %sselected, url[ %s ]",
                        $this->_reqUrl,
                        $tagStr,
                        $this->_reqTags,
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
