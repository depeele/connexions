<?php
/** @file
 *
 *  The base class for a set of Connexions Database Table Model instances.
 *
 *  This can be directly used as a Zend_Paginator adapter:
 *      $set       = new Connexions_Set('Model_UserItem', $select);
 *      $paginator = new Zend_Paginator($set);
 *
 *  Requires:
 *      LATE_STATIC_BINDING     to be defined (Connexions.php)
 */
class Connexions_Set implements Countable,
                                IteratorAggregate,
                                ArrayAccess,
                                Zend_Paginator_Adapter_Interface
{
    /** @brief  The name to use as the row count column. */
    const       ROW_COUNT_COLUMN    = 'connexions_set_row_count';

    const       SORT_ORDER_ASC  = Zend_Db_Select::SQL_ASC;
    const       SORT_ORDER_DESC = Zend_Db_Select::SQL_DESC;


    /** @brief  The name of the Iterator class to use for this set. */
    protected   $_iterClass     = 'Connexions_Set_Iterator';

    /** @brief  The name of the Model class for members of this set. */
    protected   $_memberClass   = null;

    /** @brief  Total number of records. */
    protected   $_count         = null;

    /** @brief  Zend_Db_Select instance representing all items of this set. */
    protected   $_select        = null;
    protected   $_select_count  = null;

    /** @brief  Create a new instance.
     *  @param  select      A Zend_Db_Select instance representing the set of
     *                      items.
     *  @param  memberClass The name of the Model class for members of this
     *                      set.
     *  @param  iterClass   The name of the class to create for a set iterator
     *                      [ 'Connexions_Set_Iterator' ].
     */
    public function __construct(Zend_Db_Select $select,
                                $memberClass    = null,
                                $iterClass      = null)
    {
        if (! @is_string($memberClass))
            throw new Exception("Connexions_Set requires 'memberClass' ".
                                    "either directly specified or as ".
                                    "a member of the provided 'select'");

        $this->_select      = $select;
        $this->_memberClass = $memberClass;

        if (! @empty($iterClass))
            $this->_iterClass = $iterClass;
    }

    /** @brief  Return the memberClass for this instance.
     *
     *  @return The string representing the member class.
     */
    public function memberClass()
    {
        return $this->_memberClass;
    }

    /** @brief  Establish sorting for this set.
     *  @param  by          Any field of the memberClass.
     *  @param  order       Sort order
     *                      (Connexions_Set::SORT_ORDER_ASC | SORT_ORDER_DESC
     *                              ==
     *                      Zend_Db_Select::SQL_ASC         | SQL_DESC)
     *  @param  force       Force the use of the provided 'by' value without
     *                      check [false].
     *
     *  @return $this
     */
    public function setOrder($by, $order, $force = false)
    {
        if ($force !== true)
        {
            // Validate 'by'
            $memberClass = $this->_memberClass;
            $model       = Connexions_Model::__sget($memberClass, 'model');
            if (! @isset($model[$by]))
            {
                // Invalid 'by' -- use the first key
                $keys = array_keys(Connexions_Model::__sget($memberClass,
                                                            'keys'));

                /*
                Connexions::log("Connexions_Set::setOrder: "
                                . "Invalid 'by' [{$by}]: using [{$keys[0]}]");
                // */

                $by   = $keys[0];
            }
        }

        // Validate 'order'
        switch ($order)
        {
        case Zend_Db_Select::SQL_ASC:
        case Zend_Db_Select::SQL_DESC:
            break;

        default:
            $order = Zend_Db_Select::SQL_ASC;
            break;
        }

        /*
        Connexions::log("Connexions_Set::setOrder: "
                        . "by[{$by}], order[{$order}]");
        // */

        $this->_select->reset(Zend_Db_Select::ORDER)
                      ->order( (is_array($by) ? $by
                                              : "{$by} {$order}") );

        /*
        Connexions::log("Connexions_Set::setOrder: "
                        . "sql[ {$this->_select->assemble()} ]");
        // */

        return $this;
    }

    /** @brief  Retrieve the sort information for this set.
     *
     *  @return array('by'      => field to sort by,
     *                'order'   =>  sort order
     *                              (Connexions_Set::SORT_ORDER_ASC |
     *                                               SORT_ORDER_DESC
     *                                  ==
     *                               Zend_Db_Select::SQL_ASC | SQL_DESC)
     */
    public function getOrder()
    {
        $order = $this->_select->getPart(Zend_Db_Select::ORDER);
        $parts = $order[0]; //preg_split('/\s+/', $order[0]);

        return array('by'       => $parts[0],
                     'order'    => $parts[1]);
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

    /** @brief  Create a Zend_Tag_ItemList adapter for the top 'limit' items.
     *  @param  offset      The page offset [0];
     *  @param  limit       The number of items per page [100];
     *  @param  setInfo     A Connexions_Set_Info instance containing
     *                      information about any items specified in the
     *                      request.
     *  @param  url         The base url for items
     *                      (defaults to the request URL).
     *
     *  @return A Connexions_Set_ItemList instance
     *              (subclass of Zend_Tag_ItemList)
     *
     */
    public function get_Tag_ItemList(                    $offset    = null,
                                                         $perPage   = null,
                                     Connexions_Set_info $setInfo   = null,
                                                    $url            = null)
    {
        if ($offset  === null)  $offset  = 0;
        if ($perPage === null)  $perPage = 100;

        return new Connexions_Set_ItemList($this->getItems($offset, $perPage),
                                           $setInfo, $url);
    }

    /*************************************************************************
     * Countable Interface
     *
     */
    public function count()
    {
        if ($this->_count === null)
        {
            $start = microtime(true);
            $res = $this->_select_forCount()
                        ->query(Zend_Db::FETCH_ASSOC)
                        ->fetch();
            $end   = microtime(true);

            $this->_count = (@isset($res[self::ROW_COUNT_COLUMN])
                                ? $res[self::ROW_COUNT_COLUMN]
                                : 0);

            // /*
            Connexions::log(sprintf("Connexions_Set::count():%s: "
                                    . "%d: retrieve %f seconds",
                                        $this->_memberClass,
                                        $this->_count,
                                        ($end - $start)) );
            // */
        }

        return $this->_count;
    }

    /*************************************************************************
     * ArrayAccess Interface
     *
     */
    public function offsetExists($offset)
    {
        if ( (! @is_numeric($offset)) ||
             ($offset < 0)            ||
             ($offset >= $this->count()))
            return false;

        return true;
    }

    public function offsetGet($offset)
    {
        $start = microtime(true);

        // Retrieve a single row
        $this->_select->limit(1, $offset);
        $rows = $this->_select->query()->fetchAll();
        $end1 = microtime(true);

        // Create a new instance of the member class using the retrieved data
        $inst = new $this->_memberClass($rows[0]);
        $end2 = microtime(true);

        // /*
        Connexions::log(sprintf("Connexions_Set::offsetGet(%d):%s: "
                                . "retrieve %f sec, instantiate %f secs",
                                    $offset,
                                    $this->_memberClass,
                                    ($end1 - $start),
                                    ($end2 - $end1)) );
        // */

        // Return the new instance
        return $inst;
    }

    public function offsetSet($offset, $value)
    {
        // Disallow...
        return;
    }

    public function offsetUnset($offset)
    {
        // Disallow...
        return;
    }

    /*************************************************************************
     * IteratorAggregate Interface
     *
     */

    /** @brief  Return a foreach-compatible iterator for the current set.
     *
     *  @return Traversable
     */
    public function getIterator()
    {
        return $this->getItems(0, -1);
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
        $start = microtime(true);
        if ($itemCountPerPage <= 0)
        {
            $this->_select->reset(Zend_Db_Select::LIMIT_COUNT);
            $this->_select->reset(Zend_Db_Select::LIMIT_OFFSET);
        }
        else
            $this->_select->limit($itemCountPerPage, $offset);

        $rows = $this->_select->query()->fetchAll();
        $end1 = microtime(true);

        $inst = new $this->_iterClass($this, $rows);
        $end2 = microtime(true);

        // /*
        Connexions::log(sprintf("Connexions_Set::getItems(%d, %d):%s: "
                                //. "sql[ %s ], "
                                . "retrieve %f sec, instantiate %f secs",
                                    $offset, $itemCountPerPage,
                                    $this->_memberClass,
                                    //$this->_select->assemble(),
                                    ($end1 - $start),
                                    ($end2 - $end1)) );
        // */

        return $inst;
    }

    /*************************************************************************
     * Protected helpers
     *
     */

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
                 (count($groups) > 1)                   ||
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
                $groupPart = $db->quoteIdentifier($groups[0], true);
            }

            /* If the original query had a GROUP BY or DISTINCE and only one
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

        return $this->_select_count;
    }
}
