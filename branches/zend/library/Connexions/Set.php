<?php
/** @file
 *
 *  The base class for a set of Connexions Database Table Model instances.
 *
 *  This can be directly used as a Zend_Paginator adapter:
 *      $set       = new Connexions_Set('Model_UserItem', $select);
 *      $paginator = new Zend_Paginator($set);
 */
class Connexions_Set implements Countable,
                                IteratorAggregate,
                                Zend_Paginator_Adapter_Interface
{
    /** @brief  The name to use as the row count column. */
    const       ROW_COUNT_COLUMN    = 'connexions_set_row_count';

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
     *
     *  Note: 'memberClass' may be excluded IF 'select' identifies the member
     *        class (i.e. has a '_memberClass' member set as is the case for
     *                    the Zend_Db_Select returned from Model_*::select()).
     */
    public function __construct(Zend_Db_Select $select, $memberClass = null)
    {
        if ($memberClass === null)
            $memberClass = $select->_memberClass;

        if (! @is_string($memberClass))
            throw new Exception("Connexions_Set requires 'memberClass' ".
                                    "either directly specified or as ".
                                    "a member of the provided 'select'");

        $this->_memberClass = $memberClass;
        $this->_select      = $select;
    }

    /** @brief  Return the memberClass for this instance.
     *
     *  @return The stringn representing the member class.
     */
    public function memberClass()
    {
        return $this->_memberClass;
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

    /*************************************************************************
     * Countable Interface
     *
     */
    public function count()
    {
        if ($this->_count === null)
        {
            $res = $this->_select_forCount()->query(Zend_Db::FETCH_ASSOC)
                                               ->fetch();

            /*
printf ("<pre>Connexions_Set::count: count(res): %d, count: %s:\n",
        count($res), $res[self::ROW_COUNT_COLUMN]);
print_r($res, true);
echo "</pre>\n";
            // */

            $this->_count = (@isset($res[self::ROW_COUNT_COLUMN])
                                ? $res[self::ROW_COUNT_COLUMN]
                                : 0);
        }

        return $this->_count;
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

    /** @brief  Returns an array of items for a page.
     *  @param  offset              The page offset.
     *  @param  itemCountPerPage    The number of items per page.
     *
     *  @return A Connexions_Set_Iterator for the records in the given range.
     */
    public function getItems($offset, $itemCountPerPage)
    {
        if ($itemCountPerPage <= 0)
            $this->_select->reset(Zend_Db_Select::LIMIT_OFFSET);
        else
            $this->_select->limit($itemCountPerPage, $offset);

        $rows = $this->_select->query()->fetchAll();

        return new Connexions_Set_Iterator($this, $rows);
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

/** @brief  A lazy iterator.  Allows us to postpone the actual instantiation of
 *          a Model instance until it is actually retrieved.
 */
class Connexions_Set_Iterator extends ArrayIterator
{
    /** @brief  The Connexions_Set that is the source of these items. */
    protected   $_parentSet     = null;

    public function __construct(Connexions_Set $parentSet, $array)
    {
        $this->_parentSet =  $parentSet;

        parent::__construct($array);
    }

    public function current()
    {
        $class  = $this->_parentSet->memberClass();
        $offset = $this->key();
        $row    = parent::current();
        if ($row instanceof $class)
            return $row;

        // Create Model instance for each retrieved record
        $db    = $this->_parentSet->select()->getAdapter();
        $row['@isBacked'] = true;
        return $class::find($row, $db);


        $inst             = $class::find($row, $db);

        // Replace the current offset with this new instance
        $this->offsetSet($offset, $inst);

        /*
        printf ("<pre>Connexions_Set::getItems: %d items:\n", count($items));
        print_r($items);
        echo "</pre>\n";
        // */
        return $inst;
    }
}
