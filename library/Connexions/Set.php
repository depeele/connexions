<?php
/** @file
 *
 *  The abstract base class for a set of Connexions Database Table Model
 *  instances.
 *
 *  This can be directly used as a Zend_Paginator adapter:
 *      $set       = new Connexions_Set('Model_UserItem', $select);
 *      $paginator = new Zend_Paginator($set);
 *
 *
 *  This provides functionality similar to Zend_Db_Table_Rowset_Abstract but
 *  provides Lazy-loading and a Direct Model connection, making it similar in
 *  many respects to ActiveRecord.
 *
 *      Lazy-loading:   no database interactions nor Model instantiations occur
 *                      until a caller attempts to access a record.
 *      Direct Model:   when an offset is requested from this set, a
 *                      Connexions_Model instance is returned.
 *
 *
 *  Connexions_Set accepts a Zend_Db_Select that represents the desired set of
 *  items along with the name of the Connexions_Model class that represents the
 *  members of this set.
 *
 *  Concrete classes include a MEMBER_CLASS constant that identifies the name
 *  Connexions_Model class to be used to represent individual rows.
 *
 *  Records are retrieved either one at a time, or in a group:
 *      getItems(offset, count) - Ensures that all records in the specified
 *                                range have been retrieved, and wraps them in
 *                                an instance of the iterator class
 *                                (Connexions_Set_Iterator), which further
 *                                delays instantiation of the Connexions_Model
 *                                for a record until a specific offset is
 *                                requested;
 *
 *      offsetGet(offset),      - Retrieves the single requested row, wrapping
 *      current(),                it in a Connexions_Model instance;
 *      getItem(offset)           (the primary difference with getItem() and
 *                                 the other two methods is that getItem() does
 *                                 NOT alter the current iteration
 *                                 position/key)
 *
 *
 *
 *
 * old....
 *      getIterator()           - Uses getItems() to retrieve an iterator for
 *                                all records represented by the select
 *                                specified on instantiation;
 *
 *  Interface Soup
 *  --------------------------------------------------------------------------
 *  Countable           count
 *  ArrayAccess         offsetExists/Get/Set/Unset
 *  SeekableIterator    seek                                : Iterator
 *  Iterator            current, key, next, rewind, valid   : Traversable
 *  IteratorAggregate   getIterator                         : Traversable
 *
 *  ArrayIterator                                           : Countable,
 *                                                            Iterator,
 *                                                            Traversable,
 *                                                            ArrayAccess,
 *                                                            SeekableIterator
 *
 *
 *  Zend_Db_Table_Rowset_Abstract                           : Countable,
 *                                                            ArrayAccess,
 *                                                            SeekableIterator
 *
 *  Zend_Paginator_Adapter_Interface                        : Countable
 *                      getItems
 *
 */
abstract class Connexions_Set extends    ArrayIterator
                              implements Countable,
                                         ArrayAccess,
                                         SeekableIterator,
                                         Zend_Paginator_Adapter_Interface
{
    /** @brief  The number of records to retrieve on a cache miss. */
    const       FETCH_COUNT         = 100;

    /** @brief  The name to use as the row count column. */
    const       ROW_COUNT_COLUMN    = 'connexions_set_row_count';

    /** @brief  Valid sort orders. */
    const       SORT_ORDER_ASC      = Zend_Db_Select::SQL_ASC;
    const       SORT_ORDER_DESC     = Zend_Db_Select::SQL_DESC;

    /** @brief  Valid related type values. */
    const       RELATED_USERS       = 'users';
    const       RELATED_TAGS        = 'tags';
    const       RELATED_ITEMS       = 'items';
    const       RELATED_USERITEMS   = 'userItems';
    const       RELATED_GROUPS      = 'groups';


    /** @brief  The name of the Iterator class to use for this set. */
    protected   $_iterClass     = 'Connexions_Set_Iterator';

    /** @brief  The name of the Model class for members of this set. */
    protected   $_memberClass   = null;

    /** @brief  Total number of records. */
    protected   $_count         = null;

    /** @brief  The original data for each row. */
    protected   $_data          = array();

    /** @brief  Cache of instantiated Connexions_Model objects that parallels
     *          $_data.
     */
    protected   $_members       = array();

    /** @brief  Iterator pointer (i.e. current iteration offset). */
    protected   $_pointer       = 0;


    /** @brief  Zend_Db_Select instance representing all items of this set. */
    protected   $_select        = null;
    protected   $_select_count  = null;

    protected   $_error         = null; /* If there has been an error, this
                                         * will contain the error message
                                         * string.
                                         */
    /** @brief  Create a new instance.
     *  @param  select      A Zend_Db_Select instance representing the set of
     *                      items;
     *  @param  memberClass The name of the Model class for members of this
     *                      set;
     *  @param  iterClass   The name of the class to create for a set iterator
     *                      [ 'Connexions_Set_Iterator' ].
     */
    public function __construct(Zend_Db_Select $select,
                                $memberClass    = null,
                                $iterClass      = null)
    {
        if ($this->_memberClass === null)
        {
            if (! @is_string($memberClass))
            {
                throw new Exception("Connexions_Set requires 'memberClass' "
                                    . "either directly specified or as "
                                    . "a member of the provided 'select'");
            }

            $this->_memberClass = $memberClass;
        }

        $this->_select = $select;

        if (! @empty($iterClass))
            $this->_iterClass = $iterClass;
    }

    public function getError()
    {
        return $this->_error;
    }

    public function hasError()
    {
        return ($this->_error !== null);;
    }

    /** @brief  Return the memberClass for this instance.
     *
     *  @return The string representing the member class.
     */
    public function memberClass()
    {
        return $this->_memberClass;
    }

    /** @brief  Map a field name.
     *  @param  name    The provided name.
     *
     *  @return The new, mapped name (null if the name is NOT a valid field).
     */
    public function mapField($name)
    {
        $model = Connexions_Model::metaData('model', $this->_memberClass);
        if (! @isset($model[$name]))
        {
            //$this->_addError("Invalid field [ {$name} ]");
            $name = null;
        }

        return $name;
    }

    /** @brief  Establish sorting for this set.
     *  @param  order       A string or array of strings identifying the
     *                      field(s) to sort by.  Each may also, optionally
     *                      include a sorting order (ASC, DESC) following the
     *                      field name, separated by a space.
     *
     *  Note: When the order is changed, any cached data will be flushed.
     *
     *  @return $this
     */
    public function setOrder($order)
    {
        if (! is_array($order))     $order = array($order);

        // Validate the incoming order
        $newOrder    = array();
        foreach ($order as $spec)
        {
            $orderParts = $this->_parse_order($spec);
            if ($orderParts === null)
            {
                $this->_addError("setOrder: Invalid spepcification [{$spec}]");

                /*
                Connexions::log("Connexions_Set::setOrder: "
                                . "Invalid specification [{$spec}] -- skip");
                // */
                continue;
            }

            array_push($newOrder, implode(' ', $orderParts));
        }

        $curOrder = $this->_select->getPart(Zend_Db_Select::ORDER);

        /*
        Connexions::log("Connexions_Set::setOrder: "
                        . "current[ ". print_r($curOrder, true) ." ]");
                        . "order[ ".   print_r($newOrder, true) ." ]");
        // */

        if ($curOrder != $newOrder)
        {
            $this->_select->reset(Zend_Db_Select::ORDER)
                          ->order( $newOrder );

            /*
            Connexions::log("Connexions_Set::setOrder: "
                            . "order change: Flush all data");
            // */

            // Flush all cached data
            $this->_data    = array();
            $this->_members = array();
        }

        /*
        Connexions::log("Connexions_Set::setOrder: "
                        . "sql[ {$this->_select->assemble()} ]");
        // */

        return $this;
    }

    /** @brief  Retrieve the sort information for this set.
     *
     *  @return An order array.
     */
    public function getOrder()
    {
        return $this->_select->getPart(Zend_Db_Select::ORDER);
    }

    /** @brief  Return the current Zend_Db_Select instance representing the
     *          items of this set.
     *
     *  @return Zend_Db_Select
     */
    public function select()
    {
        return $this->_select;
    }

    /** @brief  Limit the number of items returned.
     *  @param  itemCountPerPage    The number of items per page
     *  @param  offset              The page offset.
     *
     *  @return $this
     */
    public function limit($itemCountPerPage, $offset = 0)
    {
        $this->_select->limit($itemCountPerPage, $offset);

        return $this;
    }

    /** @brief  Retrieve the number of items per page.
     *
     *  @return The number of items per page.
     */
    public function getItemCountPerPage()
    {
        return $this->_select->getPart(Zend_Db_Select::LIMIT_COUNT);
    }

    /** @brief  Retrieve the current page number.
     *
     *  @return The current page number.
     */
    public function getItemPageNumber()
    {
        return $this->_select->getPart(Zend_Db_Select::LIMIT_OFFSET);
    }

    /** @brief  Retrieve a set of items that are related to this set.
     *  @param  type    The type of item (Connexions_Set::RELATED_*);
     *  @param  userIds The array of userIds to use in the relation;
     *  @param  itemIds The array of itemIds to use in the relation;
     *  @param  tagIds  The array of tagIds  to use in the relation;
     *
     *  @return The new Connexions_Set instance.
     */
    public function getRelatedSet($type,
                                  $userIds = null,
                                  $itemIds = null,
                                  $tagIds  = null)
    {
        $set = null;
        switch ($type)
        {
        case self::RELATED_USERS:
            $set     = new Model_UserSet($tagIds, $itemIds, $userIds);
            break;

        case self::RELATED_TAGS:
            $set     = new Model_TagSet($userIds, $itemIds, $tagIds);
            break;

        case self::RELATED_ITEMS:
            throw(new Exception("Connexions_Set::getRelatedSet({$type}): "
                                . 'Not-implemented for this type'));
            break;

        case self::RELATED_USERITEMS:
            $set     = new Model_UserItemSet($tagIds, $userIds, $itemIds);
            break;

        case self::RELATED_GROUPS:
            throw(new Exception("Connexions_Set::getRelatedSet({$type}): "
                                . 'Not-implemented for this type'));
            break;

        default:
            throw(new Exception("Connexions_Set::getRelatedSet({$type}): "
                                . 'Unexpected type'));
            break;
        }

        return $set;
    }

    /** @brief  Create a Zend_Tag_ItemList adapter for the top 'limit' items.
     *  @param  setInfo     A Connexions_Set_Info instance containing
     *                      information about any items specified in the
     *                      request.
     *  @param  url         The base url for items
     *                      (defaults to the request URL).
     *  @param  offset      The page offset [0];
     *  @param  limit       The number of items per page [100];
     *
     *  @return A Connexions_Set_ItemList instance
     *              (subclass of Zend_Tag_ItemList)
     *
     */
    public function get_Tag_ItemList(Connexions_Set_Info $setInfo   = null,
                                                         $url       = null,
                                                         $offset    = null,
                                                         $perPage   = null)
    {
        /* If 'offset' and/or 'perPage' are not provided, see if we have any
         * limits already established on the 'select'.
         */
        if ($offset === null)
            $offset  = $this->getItemPageNumber();
        if ($perPage === null)
        {
            $perPage = $this->getItemCountPerPage();
            if ($perPage < 1)
                $perPage = 100;
        }

        /*
        Connexions::log("Connexions_Set::get_Tag_ItemList: "
                            . "offset[ {$offset} ], perPage[ {$perPage} ], "
                            . "sql[ {$this->_select->assemble()} ]");
        // */

        return new Connexions_Set_ItemList($this->getItems($offset, $perPage),
                                           $setInfo, $url);
    }

    /*************************************************************************
     * Countable Interface
     *
     */

    /** @brief  Return the number of elements in this set.
     *
     *  Override Zend_Db_Table_Rowset_Abstract so we can delay until needed.
     *
     *  @return int
     */
    public function count()
    {
        if ($this->_count === null)
        {
            $res = $this->_select_forCount()
                        ->query(Zend_Db::FETCH_ASSOC)
                        ->fetch();

            $this->_count = (@isset($res[self::ROW_COUNT_COLUMN])
                                  ? $res[self::ROW_COUNT_COLUMN]
                                  : 0);

            /*
            Connexions::log(sprintf("Connexions_Set::count():%s: "
                                    . "%d",
                                        $this->_memberClass,
                                        $this->_count) );
            // */
        }

        return $this->_count;
    }

    /*************************************************************************
     * ArrayAccess Interface
     *
     */

    /** @brief  Check if an offset "exists".
     *  @param  offset      The new offset (numeric).
     *
     *  Override Zend_Db_Table_Abstract so we can delay count() and actual
     *  retrieval of row data.
     *
     *  @return boolean
     */
    public function offsetExists($offset)
    {
        if ( (! @is_numeric($offset)) ||
             ($offset < 0)            ||
             ($offset >= $this->count()))
            return false;

        return true;
    }

    /** @brief  Get the member for the given offset
     *  @param  offset  The desired offset.
     *
     *  Required by the ArrayAccess implementation
     *
     *  @return Connexions_Model instance.
     */
    public function offsetGet($offset)
    {
        $this->_pointer = (int) $offset;

        return $this->current();

        /*
        // Retrieve a single row
        $this->_select->limit(1, $offset);
        $rows = $this->_select->query()->fetchAll();

        // Note that this is a backed record.
        $rec = $rows[0];
        $rec['@isBacked'] = true;

        // Create a new instance of the member class using the retrieved data
        $inst = new $this->_memberClass($rec);

        Connexions::log(sprintf("Connexions_Set::offsetGet(%d):%s",
                                    $offset,
                                    $this->_memberClass) );

        // Return the new instance
        return $inst;
        */
    }

    /** @brief  Does nothing
     *  @param  offset
     *  @param  value
     *
     *  Required by the ArrayAccess implementation
     */
    public function offsetSet($offset, $value)
    {
        // Disallow...
    }

    /** @brief  Does nothing
     *  @param  offset
     *
     *  Required by the ArrayAccess implementation
     */
    public function offsetUnset($offset)
    {
        // Disallow...
    }

    /*************************************************************************
     * Iterator Interface
     */

    /** @brief  Return the identifying key of the current element.
     *
     *  Similar to the key() function for arrays in PHP.
     *  Required by interface Iterator.
     *
     *  @return int
     */
    public function key()
    {
        return $this->_pointer;
    }

    /** @brief  Rewind the Iterator to the first element.
     *
     *  Similar to the reset() function for arrays in PHP.
     *  Required by interface Iterator.
     *
     *  @return Connexions_Set for a Fluent interface.
     */
    public function rewind()
    {
        $this->_pointer = 0;
        return $this;
    }

    /** @brief  Move forward to next element.
     *
     *  Similar to the next() function for arrays in PHP.
     *  Required by interface Iterator.
     *
     *  @return void
     */
    public function next()
    {
        ++$this->_pointer;
    }

    /** @brief  Return the current element.
     *
     *  Similar to the current() function for arrays in PHP
     *  Required by interface Iterator.
     *
     *  @return Connexions_Model instance representing the current Item
     */
    public function current()
    {
        return $this->getItem($this->_pointer);
    }

    /** @brief  Check if the current location is valid.
     *
     *  Override Zend_Db_Table_Rowset_Abstract so we can delay count() until
     *  needed.
     *
     *  @return bool    (false if there's nothing more to iterate over).
     */
    public function valid()
    {
        return ( ($this->_pointer >= 0) &&
                 ($this->_pointer <  $this->count()) );
    }

    /*************************************************************************
     * SeekableIterator Interface
     *
     */

    /** @brief  Move our current position.
     *  @param  int position    The new position.
     *
     *  Override Zend_Db_Table_Rowset_Abstract so we can delay count() until
     *  needed.
     *
     *  @throws Zend_Db_Table_Rowset_Exception
     *
     *  @return Connexions_Set
     */
    public function seek($position)
    {
        if ($this->_count === null)
            // Establish the count.
            $this->count();

        return parent::seek($position);
    }

    /*************************************************************************
     * Zend_Paginator_Adapter_Interface (extends Countable)
     *
     */

    /** @brief  Returns an iterator for the items of a page.
     *  @param  offset              The page offset.
     *  @param  itemCountPerPage    The number of items per page.
     *
     *  @return A Connexions_Set_Iterator for the records in the given range.
     */
    public function getItems($offset, $itemCountPerPage)
    {
        if ($itemCountPerPage <= 0)
        {
            $offset           = 0;
            $itemCountPerPage = $this->count();
        }

        // Ensure that the desired records are cached.
        $this->_cacheRecords($offset, $itemCountPerPage);

        $rows = array_slice($this->_data, $offset, $itemCountPerPage);
        $inst = new $this->_iterClass($this, $rows);

        // /*
        Connexions::log(sprintf("Connexions_Set::getItems(%d, %d):%s: ",
                                    $offset, $itemCountPerPage,
                                    $this->_memberClass
                                    ));
        // */

        return $inst;
    }

    /** @brief  Get the member for the given offset
     *  @param  offset  The desired offset.
     *
     *  Note: This differs from offsetGet() in that the current iteration
     *        offset is NOT modified.
     *
     *  @return Connexions_Model instance representing the desired Item
     */
    public function getItem($offset)
    {
        if ( ($offset < 0) || ($offset >= $this->count()) )
        {
            /*
            Connexions::log(sprintf("Connexions_Set(%s)::getItem(%d):%s "
                                    .   "-- INVALID OFFSET",
                                    get_class($this),
                                    $offset,
                                    $this->_memberClass));
            // */

            return null;
        }

        if (! isset($this->_members[$offset]))
        {
            // Ensure that the needed record is cached.
            $this->_cacheRecords($offset, 1);

            /* Access the raw record data, ensuring that it is marked as a
             * database-backed record.
             */
            $rec =& $this->_data[$offset];
            $rec['@isBacked'] = true;

            // Create a new instance of the member class using the record.
            $this->_members[$offset] = new $this->_memberClass( $rec );

            /*
            Connexions::log(sprintf("Connexions_Set(%s)::getItem(%d):%s",
                                        get_class($this),
                                        $offset,
                                        $this->_memberClass));
            // */
        }
        /*
        else
        {
            Connexions::log(sprintf("Connexions_Set(%s)::getItem(%d):%s "
                                    .   "-- return existing instance",
                                    get_class($this),
                                    $offset,
                                    $this->_memberClass));
        }
        // */

        // Return the record instance
        return $this->_members[$offset];
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Ensure that all records in the given range have been cached.
     *  @param  offset  The beginning offset.
     *  @param  count   The number of records to retrieve.
     *
     */
    protected function _cacheRecords($offset, $count)
    {
        /* Find the first missing record -- we'll retrieve everything from that
         * point, even if it's already cached.  We stop with the first to avoid
         * excessive overhead checking to see what's cached.
         */
        $firstMissing = -1;
        for ($idex = 0; $idex < $count; $idex++)
        {
            if (! isset($this->_data[$offset + $idex]))
            {
                $firstMissing = $idex;
                break;
            }
        }

        if ($firstMissing === -1)
            // The entire range is already cached.
            return;


        /* Retrieve the raw record data in the range:
         *      ($offset + $firstMissing) .. ($offset + $count)
         *
         * Remember the current offset/count and adjust to pull
         * a range.
         */
        $origOffset  = $this->_select->getPart(Zend_Db_Select::LIMIT_OFFSET);
        $origCount   = $this->_select->getPart(Zend_Db_Select::LIMIT_COUNT);

        $fetchOffset = $offset + $firstMissing;
        $fetchCount  = ($offset + $count) - $fetchOffset;
        if ($fetchCount < self::FETCH_COUNT)
            $fetchCount = self::FETCH_COUNT;

        $this->_select->limit($fetchCount, $fetchOffset);

        // Retrieve everything in the current range.
        $rows = $this->_select->query()->fetchAll();

        // Reset the limit
        $this->_select->limit($origCount, $origOffset);

        // /*
        Connexions::log(sprintf("Connexions_Set(%s)::_cacheRecords(%d, %d):%s "
                                . "retrieved %d/%d items (%d..%d)",
                                get_class($this),
                                $offset, $count,
                                $this->_memberClass,
                                count($rows), $fetchCount,
                                $fetchOffset, $fetchOffset + count($rows)
                                ));
        // */

        /* Cache the raw row data, splicing it into the proper offset
         * within _data.
         */
        array_splice($this->_data, $offset, count($rows), $rows);
    }

    /** @brief  Add a new error.
     *  @param  err     The error string.
     *
     *  @return $this
     */
    protected function _addError($err)
    {
        if ($this->_error === null)
            $this->_error = array();

        array_push($this->_error, $err);

        return $this;
    }

    /** @brief  Split an 'order by' specification string into the field name
     *          and direction.
     *  @param  spec    The specification string.
     *
     *  @return An array [ field, sort_direction ]
     */
    protected function _parse_order($spec)
    {
        if (empty($spec))
            return null;

        $dir = self::SORT_ORDER_ASC;
        if (preg_match('/(.*\W)('. self::SORT_ORDER_ASC  .'|'
                                 . self::SORT_ORDER_DESC .')\b/si',
                       $spec, $matches))
        {
            $by  = trim($matches[1]);
            $dir = trim($matches[2]);

            // Validate 'dir'
            switch ($dir)
            {
            case self::SORT_ORDER_ASC:
            case self::SORT_ORDER_DESC:
                break;

            default:
                $dir = self::SORT_ORDER_ASC;
                break;
            }
        }
        else
        {
            $by = $spec;
        }

        // Map and validate the field
        $vBy = $this->mapField($by);
        if ($vBy === null)
        {
            // Invalid field.
            
            /*
            Connexions::log("Connexions_Set::_parse_order: "
                            . "Invalid 'by' [{$by}]");
            // */
            return null;
        }

        return array($vBy, $dir);
    }

    /** @brief  Generate a Zend_Db_Select instance representing the COUNT for
     *          the set of items represented by $this->_select.
     *
     *  Modeled after Zend_Paginator_Adapter_DbSelect::getCountSelect()
     *
     *  @return Zend_Db_Select
     */
    protected function _select_forCount()
    {
        if ($this->_select_count !== null)
            return $this->_select_count;

        $count = clone $this->_select;
        $count->__toString();    // ZF-3719 workaround

        /*
        Connexions::log("Connexions_Set::_select_forCount:"
                        .   "[ ". get_class($this) ." ]: "
                        .   "original sql[ {$count->assemble()} ]");
        // */

        $db         = $count->getAdapter();

        // Default count column expression and name
        $countPart   = 'COUNT(1) AS ';
        $countColumn = $db->quoteIdentifier(self::ROW_COUNT_COLUMN);
        $union       = $count->getPart(Zend_Db_Select::UNION);

        /* If we're dealing with a UNION query, execute the UNION as a subquery
         * to the COUNT query.
         */
        if (! @empty($union))
        {
            $expr  = new Zend_Db_Expr($countPart . $countColumn);
            $count = $db->select()->from($count, $expr);
        }
        else
        {
            $columns    = $count->getPart(Zend_Db_Select::COLUMNS);
            $groups     = $count->getPart(Zend_Db_Select::GROUP);
            $having     = $count->getPart(Zend_Db_Select::HAVING);
            $isDistinct = $count->getPart(Zend_Db_Select::DISTINCT);

            $groupPart  = null;

            /* If there is more than one column AND it's a DISTRINCT query,
             * there is more than one group, or if the query has a HAVING
             * clause, then take the original query and use it as a subquery of
             * the COUNT query.
             */
            if ( ($isDistinct && (count($columns) > 1)) ||
                 /* We use grouping for userItem to select a unique item.
                  *
                  * This does NOT require a sub-select to count, it just
                  * requires the grouping change in the final else below...
                  *
                  *(count($groups) > 1)                   ||
                  */
                  (! @empty($having)) )
            {
                $count = $db->select()->from($this->_select);
            }
            else if ($isDistinct)
            {
                $part = $columns[0];

                if (($part[1] !== Zend_Db_Select::SQL_WILDCARD) &&
                    ( ! $part[1] instanceof Zend_Db_Expr))
                {
                    $col = $db->quoteIdentifier($part[1], true);

                    if (! @empty($part[0]))
                        $col = $db->quoteIdentifier($part[0], true) .'.'. $col;
                }

                $groupPart = $col;
            }
            else if ((! @empty($groups))                            &&
                     ($groups[0] !== Zend_Db_Select::SQL_WILDCARD)  &&
                     (! $groups[0] instanceof Zend_Db_Expr))
            {
                /* Grouping can consist of multiple group identifiers, which
                 * MUST all be included here in order to properly count unique
                 * items.
                 */
                //$groupPart = $db->quoteIdentifier($groups[0], true);

                $parts = array();
                foreach ($groups as $group)
                {
                    array_push($parts, $db->quoteIdentifier($group, true));
                }

                $groupPart = implode(',', $parts);
            }

            /* If the original query had a GROUP BY or DISTINCT and only one
             * column was specified, create a COUNT(DISTINCT ) query instead of
             * a regular COUNT query.
             */
            if (! @empty($groupPart))
                $countPart = 'COUNT(DISTINCT '. $groupPart .') AS ';

            // Create the COUNT part of the query
            $expr = new Zend_Db_Expr($countPart . $countColumn);

            $count->reset(Zend_Db_Select::COLUMNS)
                  ->reset(Zend_Db_Select::ORDER)
                  ->reset(Zend_Db_Select::LIMIT_OFFSET)
                  ->reset(Zend_Db_Select::GROUP)
                  ->reset(Zend_Db_Select::DISTINCT)
                  ->reset(Zend_Db_Select::HAVING)
                  ->columns($expr);
        }

        $this->_select_count = $count;

        /*
        Connexions::log("Connexions_Set::_select_forCount:"
                        .   "[ ". get_class($this) ." ]: "
                        .   "sql[ {$count->assemble()} ]");
        // */
        return $this->_select_count;
    }
}
