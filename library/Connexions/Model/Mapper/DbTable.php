<?php
/** @file
 *
 *  Abstract base class for a Data Access Object to Model mapper using a
 *  Zend_Db_Select.
 *
 *  Note: The default Accessor and Domain Model used for any instance of this
 *        class is based upon the class name of the instance.  For example:
 *          Our class         :  (.*Model)_Mapper_<Class>
 *          Domain Model class:     => (.*Model)_<Class>
 *          Accessor class    :     => (.*Model)_DbTable_<Class>
 */
abstract class Connexions_Model_Mapper_DbTable
                extends Connexions_Model_Mapper
{
    /** @brief  The name to use as the row count column. */
    const       ROW_COUNT_COLUMN    = 'connexions_set_row_count';

    /** @brief  Set the current Data Access Object instance.
     *  @param  accessor    The new Data Access Object instance.
     *
     *  Note: Our super class will invoke setAccessor( null ) if getAccessor()
     *        is called before an accessor has been set.
     *
     *  @return $this for a fluent interface.
     */
    public function setAccessor($accessor)
    {
        if ($accessor === null)
        {
            /* Use the name of the current class to construct a Data Accessor
             * class name:
             *      Model_Mapper_<Class> => Model_DbTable_<Class>
             */
            $accessor = str_replace('Model_Mapper_', 'Model_DbTable_',
                                    get_class($this));

            /*
            Connexions::log("Connexions_Model_Mapper_DbTable[%s]::"
                            . "setAccessor(%s)",
                            $accessor);
            // */
        }

        return parent::setAccessor($accessor);
    }

    /*************************************************************************
     * Connexions_Model_Mapper abstract method implementations.
     *
     *      Generic Zend_Db_Table / Zend_Db_Select based operations.
     *
     */

    /** @brief  Save the given model instance.
     *  @param  model   The domain model instance to save.
     *
     *  @return The updated domain model instance.
     */
    public function save(Connexions_Model $domainModel)
    {
        $accessor = $this->getAccessor();
        $data     = $this->reduceModel( $domainModel );

        if (! $domainModel->isBacked())
        {
            // Insert new record
            /*
            Connexions::log("Connexions_Model_Mapper_DbTable[%s]::save() "
                            . "EMPTY id, insert new model[ %s ]",
                            get_class($this),
                            Connexions::varExport($data));
            // */

            $id        = $accessor->insert( $data );
            $operation = 'insert';

            $id        = array_combine($this->_keyNames, (array)$id);

            /*
            Connexions::log("Connexions_Model_Mapper_DbTable[%s]::save() "
                            . "insert returned[ %s ]",
                            get_class($this),
                            Connexions::varExport($id));
            // */
        }
        else
        {
            // Update
            $id    = array_combine($this->_keyNames,
                                   $this->getId($domainModel));
            $where = $this->_where($id);

            /*
            Connexions::log("Connexions_Model_Mapper_DbTable[%s]::save() "
                            . "update model[ %s ], where[ %s ]",
                            get_class($this),
                            Connexions::varExport($data),
                            Connexions::varExport($where));
            // */
            /*
            Connexions::log("Connexions_Model_Mapper_DbTable[%s]::save() "
                            . "update id[ %s ]",
                            get_class($this),
                            Connexions::varExport($id));
            // */

            $accessor->update( $data, $where );
            $operation = 'update';
        }

        /* Ensure that the new instance replaces anything current in the
         * identity map.
         */
        $this->_unsetIdentity( $id, $domainModel );

        $newModel = $this->find( $id );

        if ($newModel !== null)
        {
            /* Give the new concrete instance access to the concrete instance
             * being replace to allow duplication of any non-backed
             * meta-properties (e.g.  authentication state).
             */
            $newModel->cloneOf($domainModel);
        }

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::save() "
                        . "%s 'new' model[ %s ]: id[ %s ]",
                        get_class($this),
                        $operation,
                        ($newModel
                            ? $newModel->debugDump()
                            : 'null'),
                        Connexions::varExport($id));
        // */

        return $newModel;
    }

    /** @brief  Delete the data for the given model instance.
     *  @param  model   The domain model instance to delete.
     *
     *  @return $this for a fluent interface.
     */
    public function delete(Connexions_Model $domainModel)
    {
        if ($domainModel->isBacked())
        {
            $accessor = $this->getAccessor();
            $id       = array_combine($this->_keyNames,
                                      $this->getId($domainModel));

            /*
            Connexions::log("Connexions_Model_Mapper_DbTable::delete(): "
                            .   "id[ %s ]",
                            Connexions::varExport($id));
            // */
        
            // Locate the Zend_Db_Table_Row instance matching the incoming model
            $row = $this->_find( $id );
            $row->delete();
        }

        /* Invalidate this Model Instance, which will also remove it from
         * the Identity Map.
         */
        $domainModel->invalidate();

        return $this;
    }

    /** @brief  Retrieve the model instance with the given id.
     *  @param  id      An array of 'property/value' pairs identifying the
     *                  desired model.
     *
     *  @return The matching domain model instance (null if no match).
     */
    public function find($id)
    {
        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::find( %s )",
                        get_class($this),
                        Connexions::varExport($id));
        // */

        $uid = $this->getId($id);

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::find(): "
                        .   "id[ %s ] == uid[ %s ]",
                        get_class($this),
                        Connexions::varExport($id),
                        Connexions::varExport($uid));
        // */

        if ($this->_hasIdentity($uid))
        {
            /*
            Connexions::log("Connexions_Model_Mapper_DbTable[%s]::find( %s ) "
                            .   "uid[ %s ] --- return identity map entry",
                            get_class($this),
                            Connexions::varExport($id),
                            Connexions::varExport($uid));
            // */

            return $this->_getIdentity($uid);
        }

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::find( %s ) "
                        .   "uid[ %s ]",
                        get_class($this),
                        Connexions::varExport($id),
                        Connexions::varExport($uid));
        // */

        $accessorModel = $this->_find( (is_null($id) ? array() : $id ) );
        if ($accessorModel === null)
            return null;

        return $this->makeModel($accessorModel);
    }

    /************************************
     * Support for Connexions_Model_Set
     *
     */

    /** @brief  Fetch all matching model instances.
     *  @param  id      An array of 'property/value' pairs OR a Zend_Db_Select
     *                  identifying the desired models.
     *  @param  order   Optional ORDER clause (string, array)
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *  @param  raw     Should the raw records be returned instead of a
     *                  Connexions_Model_Set instance? [ false ];
     *
     *  Note: 'id' can contain properties prefixed with '|' to indicate 'OR' 
     *        verses 'AND'.
     *
     *
     *  @return If 'raw' is false, a Connexions_Model_Set instance that
     *          provides access to all matching Domain Model instances,
     *          otherwise, and array of raw records.
     */
    public function fetch($id      = null,
                          $order   = null,
                          $count   = null,
                          $offset  = null,
                          $raw     = false)
    {
        if ( $id instanceof Zend_Db_Select )
        {
            $select =& $id;
        }
        else
        {
            //$accessor = $this->getAccessor();
            //$select   = $accessor->select();
            // Retrieve the Zend_Db_Table_Select (vice Zend_Db_Select).
            $select = $this->select( false );

            if ($id !== null)
            {
                if (! is_array($id))
                    throw(new Exception ("id MUST be Zend_Db_Select, "
                                          .             "null, or array"));

                $where  = $this->_where($id, false);

                $this->_addWhere($select, $where);
            }
        }

        if ($order !== null)
            $select->order($order);
        if (($count !== null) || ($offset !== null))
        {
            $select->limit($count, $offset);
        }

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::fetch() "
                        . "initial sql[ %s ]...",
                        get_class($this),
                        $select->assemble());
        // */

        if (is_array($raw))
        {
            // Force assembly so we can get information about the select
            $select->assemble();

            list($correlationName, $column, $alias) =
                @array_shift($select->getPart(Zend_Db_Select::COLUMNS));

            /*
            Connexions::log("Connexions_Model_Mapper_DbTable[%s]::fetch(): "
                            .   "initial col[ %s:%s:%s ], "
                            .   "columns[ %s ]",
                            get_class($this),
                            $correlationName, $column, $alias,
                            Connexions::varExport(
                                $select->getPart(Zend_Db_Select::COLUMNS)) );
            // */

            /* Clear out the current column selection and include ONLY those
             * specified by 'raw'
             */
            $select->reset(Zend_Db_Select::COLUMNS)
                   ->columns($raw, $correlationName);
        }

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::fetch() "
                        . "sql[ %s ]...",
                        get_class($this),
                        $select->assemble());
        // */

        $accessorModels = $select->query()->fetchAll();

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::fetch() "
                        . "sql[ %s ], %d rows...",
                        get_class($this),
                        $select->assemble(),
                        count($accessorModels));
        // */

        if ($raw !== false)
        {
            $set = $accessorModels;
        }
        else
        {
            // Create a Connexions_Model_Set to contain these results
            $setName = $this->getModelSetName();
            $set     = new $setName( array('mapper'     => $this,
                                           'context'    => $select,
                                           'offset'     => $offset,
                                           'results'    => $accessorModels) );
        }

        return $set;
    }

    /** @brief  In support of lazy-evaluation, this method retrieves the
     *          specified range of values for an existing set.
     *  @param  set     Connexions_Model_Set instance.
     *  @param  offset  The beginning offset.
     *  @param  count   The number of items.
     *
     *  @return An array containing the raw data required to construct
     *          Connexions_Model instances for each item in the specified
     *          range.
     */
    public function fillItems(Connexions_Model_Set  $set,
                              $offset, $count)
    {
        $select = $set->getContext();
        if (! $select instanceof Zend_Db_Select)
            throw new Exception("Invalid context!  Not Zend_Db_Select.");

        $select->limit($count, $offset);

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable::fillItems(%d..%d): "
                        .   "totalCount[ %d ], sql[ %s ]",
                        $offset, $offset+$count,
                        $set->getTotalCount(),
                        $select->assemble());
        // */

        return $select->query()->fetchAll();
    }

    /** @brief  Return an array of the Identifiers of all items in this set,
     *          regardless of offset or limit restrictions.
     *
     *  @return An array of all Identifiers.
     */
    public function getIds(Connexions_Model_Set   $set)
    {
        $select = $set->getContext();
        if (! $select instanceof Zend_Db_Select)
            throw new Exception("Invalid context!  Not Zend_Db_Select.");

        $selectIds = clone $select;
        $selectIds->__toString();    // ZF-3719 workaround

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::getIds(%s): "
                        .   "initial select[ %s ]",
                        get_class($this),
                        get_class($set),
                        $selectIds->assemble());
        // */

        $selectIds->reset(Zend_Db_Select::COLUMNS)
                  //->reset(Zend_Db_Select::ORDER)
                  ->reset(Zend_Db_Select::LIMIT_OFFSET)
                  ->reset(Zend_Db_Select::LIMIT_COUNT)
                  ->reset(Zend_Db_Select::GROUP)
                  ->reset(Zend_Db_Select::DISTINCT)
                  ->reset(Zend_Db_Select::HAVING)
                  ->columns($this->_keyNames);

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::getIds(): "
                        .   "keyNames[ %s ], sql[ %s ]",
                        get_class($this),
                        implode(', ', $this->_keyNames),
                        $selectIds->assemble());
        // */

        $rows  = $selectIds->query(Zend_Db::FETCH_ASSOC)
                           ->fetchAll();

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable::getIds(): "
                        .   "keyNames[ %s ], sql[ %s ]: %d rows, [ %s ]",
                        implode(', ', $this->_keyNames),
                        $selectIds->assemble(),
                        count($rows),
                        Connexions::varExport($rows));
        // */

        $nKeys = count($this->_keyNames);
        $ids   = array();
        foreach ($rows as $row)
        {
            /*
            Connexions::log("Connexions_Model_Mapper_DbTable::getIds(): "
                            .   "row[ %s ]",
                            Connexions::varExport($row));
            // */

            $id = array_values($row);
            if ($nKeys === 1)
                $id = $row[0];

            array_push($ids, $id);
        }

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable::getIds(): "
                        .   "ids[ %s ]",
                        Connexions::varExport($ids));
        // */

        return $ids;
    }

    /** @brief  Get the total count for the given Model Set.
     *  @param  set     Connexions_Model_Set instance.
     *  @param  force   Force a count, event if there are no limits? [ false ];
     *
     *  @return The total count (null if there are no limits in place).
     */
    public function getTotalCount(Connexions_Model_Set $set,
                                                       $force   = false)
    {
        $select = $set->getContext();
        if (! $select instanceof Zend_Db_Select)
            throw new Exception("Invalid context!  Not Zend_Db_Select.");

        $offset = (int) $select->getPart(Zend_Db_Select::LIMIT_OFFSET);
        $count  = (int) $select->getPart(Zend_Db_Select::LIMIT_COUNT);
        if ( $force || ($offset > 0) || ($count > 0) )
        {
            /*
            Connexions::log("Connexions_Model_Mapper_DbTable::getTotalCount(): "
                            .   "Perform a query to establish the total count");
            // */

            return $this->_getTotalCount($select);
        }

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable::getTotalCount(): "
                        .   "No limits are in place: count() === totalCount");
        // */

        return null;
    }

    /** @brief  Get the starting offset of the given Model Set.
     *  @param  set     Connexions_Model_Set instance.
     *
     *  @return The starting offset.
     */
    public function getOffset(Connexions_Model_Set $set)
    {
        $select = $set->getContext();
        if (! $select instanceof Zend_Db_Select)
            throw new Exception("Invalid context!  Not Zend_Db_Select.");

        $offset = (int) $select->getPart(Zend_Db_Select::LIMIT_OFFSET);
        //$count  = (int) $select->getPart(Zend_Db_Select::LIMIT_COUNT);

        return $offset;
    }

    /************************************
     * Overrides
     *
     */

    /** @brief  Convert the incoming model into an array containing only 
     *          data that should be directly persisted.  This method may also
     *          be used to update dynamic values
     *          (e.g. update date/time, last visit date/time).
     *  @param  model       The Domain Model to reduce to an array.
     *  @param  keepKeys    If keys need to be kept, a concrete sub-class can
     *                      override reduceModel() and invoke with 'true'.
     *
     *  @return A filtered associative array containing data that should 
     *          be directly persisted.
     */
    public function reduceModel(Connexions_Model $model,
                                                 $keepKeys = false)
    {
        $data = parent::reduceModel($model);

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable::reduceModel(): "
                        . "is %sbacked, %skeep keys: data[ %s ]",
                        ($model->isBacked()    ? '' : 'NOT '),
                        ( ($keepKeys === true) ? '' : 'DO NOT '),
                        Connexions::varExport($data));
        // */

        // For non-backed data, key values MUST be removed (in most cases)
        if ( (! $keepKeys) && (! $model->isBacked()) )
        {
            /*
            Connexions::log("Connexions_Model_Mapper_DbTable::reduceModel(): "
                            . "unset key values[ %s ]",
                            Connexions::varExport($this->_keyNames));
            // */

            foreach ($this->_keyNames as $keyName)
            {
                unset($data[$keyName]);
            }
        }

        return $data;
    }

    /** @brief  Create a new instance of the Domain Model given raw data, 
     *          typically from a persistent store.
     *  @param  data        The raw data (array or Zend_Db_Table_Row).
     *  @param  isBacked    Is the incoming data backed by persistent store?
     *                      [ true ];
     *
     *  @return A matching Domain Model
     *          (MAY be backed if a matching instance already exists).
     */
    public function makeModel($data, $isBacked = true)
    {
        /*
        Connexions::log("Connexions_Model_Mapper_DbTable::makeModel: %s",
                        (is_object($data)
                            ? get_class($data)
                            : gettype($data)) );
        // */

        return parent::makeModel( ($data instanceof Zend_Db_Table_Row_Abstract
                                    ? $data->toArray()
                                    : $data ),
                                  $isBacked );
    }

    /*********************************************************************
     * Protected helpers
     *
     */

    /** @brief  Retrieve the Accessor Model for the given id.
     *  @param  id      An array of 'property/value' pairs identifying the
     *                  desired model.
     *
     *  @return The matching Accessor Model (null if no match).
     */
    //public function _find(array $id)
    public function _find(array $id)
    {
        $accessor = $this->getAccessor();
        $select   = $accessor->select();
        $where    = $this->_where($id);

        $this->_addWhere($select, $where);

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::_find(): "
                        .   "id[ %s ], sql[ %s ]",
                        get_class($this),
                        Connexions::varExport($id),
                        $select->assemble());
        // */

        try
        {
            $model = $accessor->fetchRow( $select );
        }
        catch (Exception $e)
        {
            Connexions::log("Connexions_Model_Mapper_DbTable[%s]::_find(): "
                            .   "EXCEPTION: %s",
                            get_class($this),
                            $e->getMessage());

            $model = null;

            // Re-throw the exception
            //throw $e;
        }

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::_find(%s): "
                        . "sql[ %s ], data[ %s ]",
                        get_class($this),
                        Connexions::varExport($id),
                        $select->assemble(),
                        ($model === null
                            ? 'null'
                            : Connexions::varExport($model->toArray(false))) );
        // */

        return $model;
    }

    /** @brief  Given a condition string and value, generate an appropriate
     *          'where' condition array entry.
     *  @param  condition   The condition string.
     *  @param  value       The desired value.
     *
     *  'condition' can have the form:
     *      prefix field op
     *
     *  'prefix' may be:
     *      |   - this is an 'OR' condition as opposed to the default 'AND';
     *      +   - combine/group this condition with the previous using 'AND';
     *      +|  - combine/group this condition with the previous using 'OR';
     *
     *  'field' names the target database field
     *  'op' may be:
     *      =   - [default] equivalence match;
     *      !=  - NOT equal;
     *      >   - greater than;
     *      >=  - greater than or equal to;
     *      <   - less than;
     *      <=  - less than or equal to;
     *
     *  For String values, the follow are also valid for 'op':
     *      =*  - contains 'value';
     *      =^  - begins with 'value';
     *      =$  - ends   with 'value;
     *
     *      !=* - does NOT contain 'value';
     *      !=^ - does NOT begin with 'value';
     *      !=$ - does NOT end   with 'value;
     *
     *
     *  If 'value' is an array, it indicates that any of the values is 
     *  acceptable.
     *
     *  For array values, if the operator is '=' or '!=', the condition will be
     *  reduced using 'IN' or 'NOT IN'; otherwise, it will be converted to a 
     *  single, complex 'where' condition with one per value, pre-bound and 
     *  database quoted, all combined via 'OR' ('AND' for NOT conditions).  
     *
     *  Note: This REQUIRES _flattenConditions() to convert a set of conditions 
     *        generate via _whereCondition() to a single, flat, pre-bound, 
     *        database-specific WHERE condition string.
     *
     *  @return An array of { condition: %condition%,
     *                        value:     %value% } or null if invalid.
     */
    protected function _whereCondition($condition, $value)
    {
        if (preg_match(
                //    prefix  field    op
                '/^\s*(\|)?\s*(.*?)\s*(!=[\^*$]?|[<>]=?|=[\^*$]?)?\s*[?]?\s*$/',
                $condition, $match))
        {
            /*
            Connexions::log("Connexions_Model_Mapper_DbTable::_whereCondition()"
                            .   ": condition match [ %s ]",
                            Connexions::varExport($match));
            // */

            /* match[1] == empty or '|'
             * match[2] == field name
             * match[3] == condition operator
             */
            $nMatches  = count($match);
            $prefix    = $match[1];
            $field     = $match[2];
            $op        = ($nMatches > 3 ? $match[3] : '=');

            $condition = $prefix . $field;
            switch ($op)
            {
            case '=':
            case '!=':
                if (is_array($value))
                {
                    // Convert to IN / NOT IN
                    if ($op[0] == '!')
                        $condition .= ' NOT IN ?';
                    else
                        $condition .= ' IN ?';
                }
                else
                {
                    $condition .= ' '. $op .' ?';
                }
                break;

            case '<=':
            case '>=':
            case '<':
            case '>':
                $condition .= ' '. $op .' ?';
                break;

            case '=^':
            case '!=^':
                if ($op[0] == '!')
                    $condition .= ' NOT';

                $condition .= ' LIKE ?';

                // Adjust each value to be a string with '%' suffix
                if (! is_array($value))
                    $value = (array)$value;

                $newValue = array();
                foreach ($value as $val)
                {
                    $pVal = preg_replace('/\*+/', '%', $val);
                    if ($pVal[strlen($pVal)-1] !== '%')
                        $pVal = $pVal .'%';

                    array_push($newValue, $pVal);
                }

                $value = (count($newValue) > 1
                            ? $newValue
                            : array_pop($newValue));
                break;

            case '=*':
            case '!=*':
                if ($op[0] == '!')
                    $condition .= ' NOT';

                $condition .= ' LIKE ?';

                // Adjust each value to be a string surrounded with '%'
                if (! is_array($value))
                    $value = (array)$value;

                $newValue = array();
                foreach ($value as $val)
                {
                    $pVal = preg_replace('/\*+/', '%', $val);
                    if ($pVal[0] !== '%')
                        $pVal = '%'. $pVal;
                    if ($pVal[strlen($pVal)-1] !== '%')
                        $pVal = $pVal .'%';

                    array_push($newValue, $pVal);
                }

                $value = (count($newValue) > 1
                            ? $newValue
                            : array_pop($newValue));
                break;

            case '=$':
            case '!=$':
                if ($op[0] == '!')
                    $condition .= ' NOT';

                $condition .= ' LIKE ?';

                // Adjust each value to be a string with '%' prefix
                if (! is_array($value))
                    $value = (array)$value;

                $newValue = array();
                foreach ($value as $val)
                {
                    $pVal = preg_replace('/\*+/', '%', $val);
                    if ($pVal[0] !== '%')
                        $pVal = '%'. $pVal;

                    array_push($newValue, $pVal);
                }

                $value = (count($newValue) > 1
                            ? $newValue
                            : array_pop($newValue));
                break;

            default:
                $condition .= ' = ?';
                break;
            }

            // Now, handle an array of values depending on the operator.
            if (is_array($value))
            {
                if (($op !== '=') && ($op !== '!='))
                {
                    /* MUST be expanded to a direct query since we need one
                     * statement per value.
                     */
                    $conditions = array();
                    foreach ($value as $idex => $val)
                    {
                        array_push($conditions,
                                   array('condition' => $condition,
                                         'value'     => $val));
                    }

                    /* For multi-value matches, if the operator included a NOT,
                     * we need to combine using 'AND' instead of 'OR'.
                     */
                    $condition = $this->_flattenConditions($conditions,
                                                           ($op[0] === '!'));

                    $value     = null;
                }
            }
        }
        else
        {
            // else, skip it (or throw an error)...

            /*
            Connexions::log("Connexions_Model_Mapper_DbTable::_whereCondition()"
                            .   ": condition NO MATCH");
            // */

            $condition = null;
        }

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable::_whereCondition():"
                        .   "final condition: [ %s ], value[ %s ]",
                        Connexions::varExport($condition),
                        Connexions::varExport($value));
        // */

        return ($condition !== null
                    ? array('condition' => $condition,
                            'value'     => $value)
                    : null);
    }

    /** @brief  Given an array of conditions generated via _whereCondition(),
     *          flatten them into a single condition string, binding and 
     *          quoting all values.
     *  @param  conditions      An array of conditions from _whereCondition();
     *  @param  and             Join via 'AND' (true) or 'OR' (false) [ true ];
     *
     *  @return A flat, string condition
     */
    protected function _flattenConditions($conditions, $and = true)
    {
        $adapter    = $this->getAccessor()->getAdapter();

        /*
        Connexions::log("_flattenConditions: adapter [ %s ]",
                        (is_object($adapter)
                            ? get_class($adapter)
                            : gettype($adapter)) );
        // */

        $quoted = array();
        foreach ($conditions as $cond)
        {
            array_push($quoted,
                       '('.$adapter->quoteInto($cond['condition'],
                                               $cond['value']) .')');
        }

        $res = '('
             .    implode(($and ? ' AND '   // Zend_Db_Select::SQL_AND
                                : ' OR '),  // Zend_Db_Select::SQL_OR
                          $quoted)
             . ')';

        return $res;
    }

    /** @brief  Given a Zend_Db_Select instance along with a 'where' condition 
     *          (generated via _where()), add the appropriate SQL WHERE 
     *          condition(s) to the select.
     *  @param  select      The Zend_Db_Select to count.
     *  @param  where       An associative array of
     *                          { 'condition' => value(s), ...}
     *
     *  @return Zend_Db_Select
     */
    protected function _addWhere(Zend_Db_Select $select,
                                 array          $where)
    {
        foreach ($where as $condition => $bindValue)
        {
            if ($condition[0] === '|')
            {
                // OR
                $condition = substr($condition, 1);
                $select->orWhere($condition, $bindValue);
            }
            else
            {
                // AND
                $select->where($condition, $bindValue);
            }
        }

        return $select;
    }

    /** @brief  Generate a Zend_Db_Select instance representing the COUNT for
     *          the set of items represented by the provided Zend_Db_Select
     *  @param  select      The Zend_Db_Select to count.
     *
     *  Modeled after Zend_Paginator_Adapter_DbSelect::getCountSelect()
     *
     *  @return Zend_Db_Select
     */
    protected function _getTotalCount(Zend_Db_Select $select)
    {
        $selectCount = clone $select;
        $selectCount->__toString();    // ZF-3719 workaround

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable::_select_forCount:"
                        .   "[ ". get_class($this) ." ]: "
                        .   "original sql[ {$selectCount->assemble()} ]");
        // */

        $db          = $selectCount->getAdapter();

        // Default count column expression and name
        $countPart   = 'COUNT(1) AS ';
        $countColumn = $db->quoteIdentifier(
                                $db->foldCase(self::ROW_COUNT_COLUMN));
        $union       = $selectCount->getPart(Zend_Db_Select::UNION);

        /* If we're dealing with a UNION query, execute the UNION as a subquery
         * to the COUNT query.
         */
        if (! @empty($union))
        {
            $expr  = new Zend_Db_Expr($countPart . $countColumn);
            $selectCount = $db->select()->from($selectCount, $expr);
        }
        else
        {
            $columns    = $selectCount->getPart(Zend_Db_Select::COLUMNS);
            $groups     = $selectCount->getPart(Zend_Db_Select::GROUP);
            $having     = $selectCount->getPart(Zend_Db_Select::HAVING);
            $isDistinct = $selectCount->getPart(Zend_Db_Select::DISTINCT);

            $groupPart  = null;

            /* If there is more than one column AND it's a DISTRINCT query,
             * there is more than one group, or if the query has a HAVING
             * clause, then take the original query and use it as a subquery of
             * the COUNT query.
             */
            if ( ($isDistinct && (count($columns) > 1)) ||
                  (count($groups) > 1)                  ||
                  (! @empty($having)) )
            {
                $selectCount = $db->select()->from( $selectCount );
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

                /*
                Connexions::log("Connexions_Model_Mapper_DbTable::"
                                .   "_select_forCount: "
                                .   "*********** DISTINCT, group [ %s ] ",
                                print_r($groupParts, true));
                // */
            }
            else if ((! @empty($groups))                            &&
                     ($groups[0] !== Zend_Db_Select::SQL_WILDCARD)  &&
                     (! $groups[0] instanceof Zend_Db_Expr))
            {
                $groupPart = $db->quoteIdentifier($groups[0], true);

                /*
                Connexions::log("Connexions_Model_Mapper_DbTable::"
                                .   "_select_forCount: "
                                .   "*********** GROUPS, group [ %s ] ",
                                print_r($groupParts, true));
                // */
            }

            /* If the original query had a GROUP BY or DISTINCT and only one
             * column was specified, create a COUNT(DISTINCT ) query instead of
             * a regular COUNT query.
             */
            if (! @empty($groupPart))
                $countPart = 'COUNT(DISTINCT '. $groupPart .') AS ';

            // Create the COUNT part of the query
            $expr = new Zend_Db_Expr($countPart . $countColumn);

            $selectCount->reset(Zend_Db_Select::COLUMNS)
                        ->reset(Zend_Db_Select::ORDER)
                        ->reset(Zend_Db_Select::LIMIT_OFFSET)
                        ->reset(Zend_Db_Select::LIMIT_COUNT)
                        ->reset(Zend_Db_Select::GROUP)
                        ->reset(Zend_Db_Select::DISTINCT)
                        ->reset(Zend_Db_Select::HAVING)
                        ->columns($expr);
        }

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable::_select_forCount:"
                        .   "[ ". get_class($this) ." ]: "
                        .   "FINAL sql[ {$selectCount->assemble()} ]");
        // */

        $res   = $selectCount->query(Zend_Db::FETCH_ASSOC)
                             ->fetch();

        $count = (@isset($res[self::ROW_COUNT_COLUMN])
                    ? $res[self::ROW_COUNT_COLUMN]
                    : 0);

        return $count;
    }

    /**************************************************************************
     * These MAY be generic enough now to move to Connexions_Model_Mapper()
     *
     */

    /** @brief  Given an array of identification/selection information,
     *          construct a database-specific array of selection clauses.
     *  @param  id          The identification/selection information;
     *  @param  nonEmpty    Can the final array of selection clauses be empty?
     *                      [ true ];
     *
     *  Identification/selection information may have the form:
     *      { condition1: value(s), condition2: value(s), ... }
     *      [ condition1, condition2, ... ]
     *      [ {'condition': condition1, 'value': value(s)},
     *         'condition': condition2, 'value': value(s)},
     *         ...} ]
     *
     *  Each 'condition' can have the form:
     *      prefix field op
     *
     *  'prefix' may be:
     *      |   - this is an 'OR' condition as opposed to the default 'AND';
     *      +   - combine/group this condition with the previous using 'AND';
     *      +|  - combine/group this condition with the previous using 'OR';
     *
     *  'field' names the target database field
     *  'op' may be:
     *      =   - [default] equivalence match;
     *      !=  - NOT equal;
     *      >   - greater than;
     *      >=  - greater than or equal to;
     *      <   - less than;
     *      <=  - less than or equal to;
     *
     *  For String values, the follow are also valid for 'op':
     *      =*  - contains 'value';
     *      =^  - begins with 'value';
     *      =$  - ends   with 'value;
     *
     *      !=* - does NOT contain 'value';
     *      !=^ - does NOT begin with 'value';
     *      !=$ - does NOT end   with 'value;
     *
     *
     *  If 'value' is an array, it indicates that any of the values is 
     *  acceptable.
     *
     *  For array values, if the operator is '=' or '!=', the condition will be
     *  reduced using 'IN' or 'NOT IN'; otherwise, it will be converted to a 
     *  single, complex 'where' condition with one per value, pre-bound and 
     *  database quoted, all combined via 'OR' ('AND' for NOT conditions).  
     *
     *  Note: This REQUIRES _whereCondition() to convert a generic 
     *        condition/value pair into a database-specific, bindable 
     *        condition/value pair.
     *
     *        This also REQUIRES _flattenConditions() to convert a set of 
     *        conditions generate via _whereCondition() to a single, flat, 
     *        pre-bound, database-specific WHERE condition string.
     *
     *  @return An array of database-specific selection clauses.
     */
    protected function _where(array $id, $nonEmpty = true)
    {
        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::"
                        . "_where(%s, %sempty):",
                        get_class($this),
                        Connexions::varExport($id),
                        ($nonEmpty ? 'non-' : ''));
        // */

        $tmpWhere = array();
        foreach ($id as $condition => $value)
        {
            /*
            Connexions::log("Connexions_Model_Mapper_DbTable[%s]::_where():"
                            .   "condition[ %s ], value[ %s ]...",
                            get_class($this),
                            Connexions::varExport($condition),
                            Connexions::varExport($value));
            // */

            if (is_int($condition))
            {
                /* 'condition' is an integer, meaning this is a non-associative 
                 * member.
                 *
                 * See if 'value' is an array containing 'condition' and 
                 * 'value'.  If so, we have a condition and value, otherwise, 
                 * 'value' is actually the condition.
                 */
                if ( is_array($value)           &&
                     isset($value['condition']) &&
                     isset($value['value']) )
                {
                    // Special, complex condition(s)
                    $condition = $value['condition'];
                    $value     = $value['value'];
                }
                else
                {
                    /* Simply use 'value' as the condition.  This is for
                     * simple, direct compare/value statements (e.g. 'field=1')
                     */
                    $condition = $value;
                    $value     = null;

                    array_push($tmpWhere,
                               array('condition' => $condition,
                                     'value'     => $value));
                    continue;
                }
            }

            /*******************************************************
             * See if this condition is to be directly joined
             * with the previous condition (i.e. prefixed with '+').
             *
             */
            if (preg_match('/^\s*\+(.*)$/', $condition, $match))
            {
                // YES - remember the condition without the prefix.
                $joinPrevious = true;
                $condition    = $match[1];
            }
            else
            {
                // NO
                $joinPrevious = false;
            }

            /*******************************************************
             * Parse this single condition/value pair, generating
             * a discrete 'condition' and 'value'
             *
             */
            $res = $this->_whereCondition($condition, $value);

            /*
            Connexions::log("Connexions_Model_Mapper_DbTable[%s]::_where(): "
                            . "condition[ %s ], value[ %s ] == [ %s ]",
                            get_class($this),
                            Connexions::varExport($condition),
                            Connexions::varExport($value),
                            Connexions::varExport($res));
            // */

            if ($res === null)
            {
                // INVALID - skip it (or throw an error)...
                continue;
            }

            if ($joinPrevious)
            {
                /* Join the current condition with the previous condition 
                 * pre-binding and quoting all values.
                 */
                $prev      = array_pop($tmpWhere);
                $condition = '';

                if ($prev['condition'][0] === '|')
                {
                    $condition = '|';
                    $prev['condition'] = substr($prev['condition'], 1);
                }

                if ($res['condition'][0] === '|')
                {
                    $and = false;
                    $res['condition'] = substr($res['condition'], 1);
                }
                else
                {
                    $and = true;
                }


                // Generate a flattened, string condition.
                $condition .= $this->_flattenConditions(array($prev,$res),
                                                        $and);

                $res['condition'] = $condition;
                $res['value']     = null;
            }

            array_push($tmpWhere, $res);
        }

        if ( ($nonEmpty !== false) && empty($tmpWhere) )
        {
            throw new Exception(
                        "Cannot generate a non-empty WHERE clause for "
                        . "model [ ". get_class($this) ." ] "
                        . "from data "
                        . "[ ". Connexions::varExport($id) ." ]");
        }

        /***********************************************************
         * Finally, simplify the where to an associative array of
         *  { condition: value(s), ... }
         */
        $where = array();
        foreach ($tmpWhere as $statement)
        {
            if (is_array($statement['condition']))
            {
                // Break it out into multiple statements
                foreach ($statement['condition'] as $idex => $condition)
                {
                    $where[ $condition ] = $statement['value'][$idex];
                }
            }
            else
            {
                $where[ $statement['condition'] ] = $statement['value'];
            }
        }

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::"
                        . "_where(%s, %sempty): where[ %s ]",
                        get_class($this),
                        Connexions::varExport($id),
                        ($nonEmpty ? 'non-' : ''),
                        Connexions::varExport($where));
        // */

        return $where;
    }

    /*********************************************************************
     * Abstract methods
     *
     */
}
