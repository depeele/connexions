<?php
/** @file
 *
 *  The abstract base class for a set of Connexions Domain Model instances.
 *
 *  This can be directly used as a Zend_Paginator adapter:
 *      $set       = new Connexions_Model_Set(
 *                          array('modelName' => 'Model_UserItem',
 *                                'results'   => $results));
 *      $paginator = new Zend_Paginator($set);
 *
 *  Items are retrieved either one at a time, or in a group:
 *      getItems(offset, count) - Ensures that all records in the specified
 *                                range have been retrieved and returns them.
 *      offsetGet(offset),      - Retrieves the single requested row, wrapping
 *      current(),                it in a Connexions_Model instance;
 *      getItem(offset)           (the primary difference with getItem() and
 *                                 the other two methods is that getItem() does
 *                                 NOT alter the current iteration
 *                                 position/key)
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
abstract class Connexions_Model_Set
                            extends    ArrayIterator
                            implements Countable,
                                       ArrayAccess,
                                       SeekableIterator,
                                       Zend_Paginator_Adapter_Interface
{
    /** @brief  The name of the Model class for members of this set. */
    protected   $_modelName         = null;

    /** @brief  The Data Mapper for member instances. */
    protected   $_mapper            = null;

    /** @brief  Total number of records. */
    protected   $_count             = null;

    /** @brief  Begins as the raw data for each row, when retrieved, intantiate
     *          a Model instance to replace the raw row data entry.
     */
    protected   $_members           = array();

    /** @brief  Iterator pointer (i.e. current iteration offset). */
    protected   $_pointer           = 0;

    /*************************************************************************/

    /** @brief  Create a new Domain Model instance.
     *  @param  config  Model configuration:
     *                      mapper      The name of a Data Mapper class, or
     *                                  a Mapper instance to use
     *                                  (e.g. Connexions_Model_Mapper)
     *
     *                                  If not provided, a default will be 
     *                                  located when needed.  This will be 
     *                                  based upon the name of this class.
     *
     *                      modelName   The name of a Domain Model class to 
     *                                  instantiate for each member of this 
     *                                  set;
     *
     *                                  If not provided, a default will be 
     *                                  generated when needed.  This will be 
     *                                  based upon the name of this class.
     *
     *                      totalCount  The total number of matching records.
     *                                  This may differ from $this->count() if
     *                                  a limit was specified on fetch that
     *                                  retrieve the underlying data;
     *
     *                      results     The array of raw data, one entry per
     *                                  member;
     *
     */
    public function __construct($config = array())
    {
        $config  = (array)$config;

        foreach ($config as $key => $val)
        {
            $method = 'set'. ucfirst($key);
            if (method_exists( $this, $method ))
            {
                /*
                Connexions::log("Connexions_Model:: %s...", $method);
                // */

                $this->{$method}($val);
            }
        }
    }

    /** @brief  Delete all items in the set (and in persistent store) and then
     *          invalidate the set.
     */
    public function delete()
    {
        Connexions::log("Connexions_Model_Set::delete(): %d items",
                        count($this->_members));

        foreach ($this->_members as $key => $item)
        {
            if ($item instanceof Connexions_Model)
                $item->delete();

            $this->_member[$key] = null;
        }

        $this->_members = array();
    }

    /**********************************************************************
     * Setters and Getters
     *
     */

    /** @brief  Set the Data Mapper for this model.
     *  @param  mapper      The mapper class name or instance.
     *
     *  @return $this for a fluent interface.
     */
    public function setMapper($mapper = null)
    {
        if ($mapper === null)
        {
            /* Use the name of the current class to construct a Mapper
             * class name:
             *      Model_Set_<Class> => Model_Mapper_<Class>
             */
            $mapper = str_replace('Model_Set_', 'Model_Mapper_',
                                  get_class($this));

            // /*
            Connexions::log("Connexions_Model::setMapper(%s)",
                            $mapper);
            // */
        }

        if (! $mapper instanceof Connexions_Model_Mapper)
        {
            /* Invoke the mapper Factory.  It will look in the cache for an 
             * existing instance and, if not found, create and cache a new 
             * Mapper instance.
             */
            $this->_mapper = Connexions_Model_Mapper::factory($mapper);
        }

        return $this;
    }

    /** @brief  Retrieve the data mapper for this model.
     *
     *  @return A Connexions_Model_Mapper instance
     */
    public function getMapper()
    {
        if ( (! is_object($this->_mapper)) &&
             ($this->_mapper !== Connexions_Model_Mapper::NO_INSTANCE) )
        {
            // Establish a default mapper and return it.
            $this->setMapper($this->_mapper);
        }
        //if (! $this->_mapper instanceof Connexions_Model_Mapper)

        return $this->_mapper;
    }

    /** @brief  Set the underlying result set for this model set.
     *  @param  results     The underlying result set.
     *
     *  @return $this for a fluent interface.
     */
    public function setResults($results)
    {
        /*
        Connexions::log("Connexions_Model_Set[%s]::setResults(): %d results",
                        get_class($this), count($results));
        // */

        $this->_members = $results;

        return $this;
    }

    /** @brief  Retrieve the underlying result set for this model set.
     *
     *  @return The result set.
     */
    public function getResults()
    {
        return $this->_members;
    }

    /** @brief  Set the total number of matching records.
     *  @param  totalCount  The total count.
     *
     *  Note: This will differ from $this->count() if a limit was specified on
     *        fetch that retrieve the underlying data;
     *
     *  @return $this for a fluent interface.
     */
    public function setTotalCount($totalCount)
    {
        $this->_totalCount = $totalCount;

        return $this;
    }

    /** @brief  Retrieve the total number of matching records.
     *
     *  Note: This will differ from $this->count() if a limit was specified on
     *        fetch that retrieve the underlying data;
     *
     *  @return The result set.
     */
    public function getTotalCount()
    {
        return $this->_totalCount;
    }

    /** @brief  Set the name of the Domain Model class instantiated for each 
     *          returned member of this set.
     *  @param  name    The mane of the Domain Model class.
     *
     *  @return $this for a fluent interface.
     */
    public function setModelName($name)
    {
        if ($name === null)
        {
            /* Use the name of the current class to construct a Domain Model
             * class name:
             *      Model_Set_<Class>   => Model_<Class>
             */
            $name = str_replace('Model_Set_', 'Model_',
                                get_class($this));
        }

        $this->_modelName = $name;

        return $this;
    }

    /** @brief  Retrieve the name of the Domain Model class instantiated for 
     *          each returned member of this set.
     *
     *  @return The name of the Domain Model class.
     */
    public function getModelName()
    {
        if ($this->_modelName === null)
        {
            $this->setModelName( $this->_modelName );
        }

        return $this->_modelName;
    }

    /*************************************************************************
     * Conversions
     *
     */

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        return implode(', ', $this->idArray());
    }

    /** @brief  Return an array version of this instance.
     *  @param  deep    Should any associated models be retrieved?
     *                      [ Connexions_Model::DEPTH_DEEP ] |
     *                        Connexions_Model::DEPTH_SHALLOW
     *  @param  public  Include only "public" information?
     *                      [ Connexions_Model::FIELDS_PUBLIC ] |
     *                        Connexions_Model::FIELDS_ALL
     *
     *  @return An array representation of this Domain Model.
     */
    public function toArray($deep   = Connexions_Model::DEPTH_SHALLOW,
                            $public = Connexions_Model::FIELDS_PUBLIC)
    {
        $res = array();
        foreach ($this->_members as $item)
        {
            if ($item instanceof Connexions_Model)
                array_push($res, $item->toArray($deep, $public));
            else if (is_object($item) && method_exists($item, 'toArray'))
                array_push($res, $item->toArray());
            else if (is_array($item))
                array_push($res, $item);
        }

        return $res;
    }

    /** @brief  Return an array of the Identifiers of all instances.
     *
     *  @return An array of all Identifiers.
     */
    public function idArray()
    {
        $mapper = $this->getMapper();
        $ids    = array();
        foreach ($this->_members as $item)
        {
            /*
            Connexions::Log("Connexions_Model_Set[%s]::idArray(): "
                            .   "[ %s ] == [ %s ]",
                            get_class($this),
                            (is_object($item)
                                ? get_class($item)
                                : gettype($item)),
                            (is_object($item)
                                ? Connexions::varExport($item->getId())
                                : Connexions::varExport($item)) );
            // */

            array_push($ids, $mapper->getId( $item ));
        }

        return $ids;
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
            $this->_count = count( $this->_members );

            /*
            Connexions::log("Connexions_Model_Set::count():%s: %d",
                            $this->_modelName, $this->_count);
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
     *  @return Connexions_Model_Set for a Fluent interface.
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
     *  @return Connexions_Model_Set
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
     *  @return A Connexions_Model_Set for the records in the given range.
     */
    public function getItems($offset, $itemCountPerPage)
    {
        if ($itemCountPerPage <= 0)
        {
            $offset           = 0;
            $itemCountPerPage = $this->count();
        }

        /*
        Connexions::log("Connexions_Model_Set::getItems(%d, %d): %d total",
                        $offset, $itemCountPerPage, count($this->_members));
        // */

        // Ensure that each item in the range is a Model instance
        $mapper = $this->getMapper();
        for ($idex = 0; $idex < $itemCountPerPage; $idex++)
        {
            $item =& $this->_members[$offset + $idex];

            /*
            Connexions::log("Connexions_Model_Set::getItems(%d, %d): "
                            . "item #%d, type[ %s ], class[ %s ]",
                            $offset, $itemCountPerPage, $offset + $idex,
                            gettype($item),
                            (is_object($item) ? get_class($item) : ''));
            // */

            if ( is_array($item) )
            {
                // Create a new instance of the member class using the record.
                $this->_members[$offset + $idex] =
                        $mapper->makeModel( $item );
            }
        }

        return array_slice($this->_members, $offset, $itemCountPerPage);
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
            return null;
        }

        $modelName = $this->getModelName();
        $item      = $this->_members[$offset];

        if (! $item instanceof $modelName)
        {
            /* Access the raw record data, ensuring that it is marked as a
             * database-backed record.
             */

            // Create a new instance of the member class using the record.
            $item = $this->getMapper()->makeModel( $item );

            $this->_members[$offset] = $item;
        }

        // Return the Domain Model instance
        return $item;
    }
}
