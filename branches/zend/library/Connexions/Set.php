<?php
/** @file
 *
 *  The base class for a set of Connexions Database Table Model instances.
 *
 *  This can be directly used as a Zend_Paginator adapter:
 *      $set       = new Connexions_Set('Model_UserItem', $select);
 *      $paginator = new Zend_Paginator($set);
 *
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

    protected   $_error     = null;     /* If there has been an error, this
                                         * will contain the error message
                                         * string.
                                         */
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
        $model = Connexions_Model::__sget($this->_memberClass, 'model');
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

        /*
        Connexions::log("Connexions_Set::setOrder: "
                        . "order[ ". print_r($newOrder, true) ." ]");
        // */

        $this->_select->reset(Zend_Db_Select::ORDER)
                      ->order( $newOrder );

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

        // /*
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

            /*
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

        // Note that this is a backed record.
        $rec = $rows[0];
        $rec['@isBacked'] = true;

        // Create a new instance of the member class using the retrieved data
        $inst = new $this->_memberClass($rec);
        $end2 = microtime(true);

        /*
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

        /*
        Connexions::log(sprintf("Connexions_Set::getItems(%d, %d):%s: "
                                . "sql[ %s ], ",
                                    $offset, $itemCountPerPage,
                                    $this->_memberClass,
                                    $this->_select->assemble()));
        // */

        $rows = $this->_select->query()->fetchAll();
        $end1 = microtime(true);

        $inst = new $this->_iterClass($this, $rows);
        $end2 = microtime(true);

        /*
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
