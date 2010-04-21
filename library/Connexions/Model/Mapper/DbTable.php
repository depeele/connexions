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

    protected   $_keyName   = null; // MAY be an array for multi-field keys
    protected   $_modelName = null;

    /** @brief  Create a new mapper.
     *  @param  config  Configuration, supports additional options due to the
     *                  additional get/set methods in this class:
     *                      keyName     The name of the primary key
     *                                  [ $this->_keyName ];
     *                      modelName   The name of the domain model
     *                                  [ $this->_modelName ];
    public function __construct(array $config = array())
    {
        parent::__construct($config);
    }
     */

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
             *      (.*Model)_Mapper_<Class> => (.*Model)_DbTable_<Class>
             */
            $accessor = preg_replace('/(.*?Model)_Mapper_(.*?)/',
                                     '$1_DbTable_$2', get_class($this));

            /*
            Connexions::log("Connexions_Model_Mapper_DbTable[%s]::"
                            . "setAccessor(%s)",
                            $accessor);
            // */
        }

        return parent::setAccessor($accessor);
    }

    /** @brief  Set the name of the primary key.
     *  @param  name    The name of the primary key.
     *
     *  @return $this for a fluent interface.
     */
    public function setKeyName($name)
    {
        $this->_keyName = $name;
        return $this;
    }

    /** @brief  Get the name of the primary key.
     *
     *  @return The name of the primary key.
     */
    public function getKeyName()
    {
        return $this->_keyName;
    }

    /** @brief  Set the name of the domain model.
     *  @param  name    The name of the domain model.
     *
     *  @return $this for a fluent interface.
     */
    public function setModelName($name)
    {
        $this->_modelName = $name;
        return $this;
    }

    /** @brief  Get the name of the domain model.
     *
     *  @return The name of the domain model.
     */
    public function getModelName()
    {
        if ($this->_modelName === null)
        {
            /* Use the name of the current class to construct a Domain Model
             * class name:
             *      (.*Model)_Mapper_<Class> => (.*Model)_<Class>
             */
            $this->_modelName = preg_replace('/(.*?Model)_Mapper_(.*?)/',
                                             '$1_$2', get_class($this));
        }

        return $this->_modelName;
    }

    /*************************************************************************
     * Generic Zend_Db_Table / Zend_Db_Select based operations.
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
        $id       = $domainModel->getId();

        $data     = $this->filter( $domainModel->toArray( false ) );
        if (! $id)
        {
            // /*
            Connexions::log("Connexions_Model_Mapper_DbTable[%s]::save() "
                            . "insert new model[ %s ]",
                            get_class($this),
                            Connexions::varExport($data));
            // */

            // Insert new record
            $id = $accessor->insert( $data );
            $operation = 'insert';
        }
        else
        {
            // Update
            $where = $this->_where($id);

            $accessor->update( $data, $where );
            $operation = 'update';
        }

        /* Ensure that the new instance replaces anything current in the
         * identity map.
         */
        $this->_unsetIdentity($id);

        $newModel = $this->find( $id );

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::save() "
                        . "%s new model[ %s ]",
                        get_class($this),
                        $operation,
                        ($newModel
                            ? $newModel->debugDump()
                            : 'null'));
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
        $accessor = $this->getAccessor();
        $id       = $domainModel->getId();
        if ($id)
        {
            // Locate the Zend_Db_Table_Row instance matching the incoming model
            $row = $this->_find( $id );
            $row->delete();

            /* Using the database adapter directly
            $where = array();
            $db    = $accessor->getAdapter();
            foreach ($this->_where($id) as $condition => $bindValue)
            {
                array_push($where, $db->quoteInto($condition, $bindValue));
            }
            $where = '('. implode(' AND ', $where) .')';

            // Delete
            $accessor->delete( $where );
            */

            $this->_unsetIdentity( $id );

            $domainModel->invalidate();
        }

        return $this;
    }

    /** @brief  Retrieve the model instance with the given id.
     *  @param  id      An entry id (integer, string, array).
     *
     *  Note: If 'id' is a simple array of values or a single value, this
     *        method will only match those id(s) against primary key(s).
     *
     *        To match against another field, 'id' MUST be an associative array
     *        of condition/value pairs.
     *
     *  @return The matching domain model instance (null if no match).
     */
    public function find($id)
    {
        if ($this->_hasIdentity($id))
            return $this->_getIdentity($id);

        $accessorModel = $this->_find($id);
        if ($accessorModel === null)
            return null;

        $domainModel = $this->makeModel($accessorModel);

        $this->_setIdentity($id, $domainModel);

        return $domainModel;
    }


    /** @brief  Fetch all matching model instances.
     *  @param  where   Optional WHERE clause (string, array, Zend_Db_Select)
     *  @param  order   Optional ORDER clause (string, array)
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  Note: If 'where' is a simple array of values or a single value, this
     *        method (via _where) will only match the value(s) against primary
     *        key(s).
     *
     *        To match against another field, 'where' MUST be an associative
     *        array of condition/value pairs.
     *
     *  @return A Connexions_Model_Set instance that provides access to all
     *          matching Domain Model instances.
     */
    public function fetch($where   = null,
                          $order   = null,
                          $count   = null,
                          $offset  = null)
    {
        if ($where !== null)
        {
            $where = $this->_where($where);
        }

        if ( $where instanceof Zend_Db_Select )
        {
            $select =& $where;
        }
        else
        {
            $totalCount = null;
            $accessor   = $this->getAccessor();
            $select     = $accessor->select();

            if (is_array($where))
            {
                foreach ($where as $condition => $bindValue)
                {
                    $select->where($condition, $bindValue);
                }
            }
        }

        if ($order !== null)
            $select->order($order);
        if (($count !== null) || ($offset !== null))
        {
            $select->limit($count, $offset);
            $totalCount = $this->_getTotalCount($select);
        }

        $accessorModels = $select->query()->fetchAll();

        if ( $totalCount === null )
        {
            // There was no limit so the count is simple the number of returned
            // records.
            $totalCount = count($accessorModels);
        }

        // /*
        Connexions::log("Connexions_Model_Mapper_DbTable::fetch(): "
                        .   "sql[ %s ], %d of %d rows",
                        $select->assemble(),
                        count($accessorModels), $totalCount);
        // */

        // Create a Connexions_Model_Set to contain these results
        $setName = $this->getModelSetName();
        $set     = new $setName( array('mapper'     => $this,
                                      #'modelName'  => $this->getModelName(),
                                       'totalCount' => $totalCount,
                                       'results'    => $accessorModels) );

        return $set;

        /*
        $domainModels  = array();
        $modelName     = $this->getModelName();

        foreach ($accessorModels as $accessorModel)
        {
            $domainModel = $this->makeModel($accessorModel);

            $this->_setIdentity($domainModel->getId(), $domainModel);

            array_push($domainModels, $domainModel);
        }

        return $domainModels;
        */
    }

    /** @brief  Fetch all matching model instances by a specific field.
     *  @param  field   The field to match on;
     *  @param  value   A single value or array of values to match;
     *  @param  order   Optional ORDER clause (string, array)
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A Connexions_Model_Set instance that provides access to all
     *          matching Domain Model instances.
     */
    public function fetchBy($field,
                            $value,
                            $order   = null,
                            $count   = null,
                            $offset  = null)
    {
        if (! is_array($value))
        {
            $where = array( $field .'=?' => $value );
        }
        else
        {
            $where = array( $field .' IN (?)' => $value );
        }

        Connexions::log("Connexions_Model_Mapper_DbTable::fetchBy(%s, %s): "
                        .   "[ %s ]",
                        $field,
                        Connexions::varExport($value),
                        Connexions::varExport($where) );

        return $this->fetch($where, $order, $count, $offset);
    }

    /** @brief  Create a new instance of the Domain Model given a raw record.
     *  @param  record  The raw record (array or Zend_Db_Table_Row).
     *
     *  This allows our Domain Model to contain instances of other Domain
     *  Models if desired.  Over-ride this protected method to
     *  locate/instantiate associated models and/or establish fields to be used
     *  to locate/instantiate on-demand.
     *
     *  @return The matching Domain Model (null if no match).
     */
    public function makeModel($record)
    {
        /*
        Connexions::log("Connexions_Model_Mapper_DbTable::makeModel: %s",
                        (is_object($record)
                            ? get_class($record)
                            : gettype($record)) );
        // */
                        
        return parent::makeModel( $record instanceof Zend_Db_Table_Row_Abstract
                                    ? $record->toArray()
                                    : $record );
    }

    /*********************************************************************
     * Protected helpers
     *
     */

    /** @brief  Retrieve the Accessor Model for the given id.
     *  @param  id      An entry id (integer, string, array).
     *
     *  Note: If 'id' is a simple array of values or a single value, this
     *        method will only match those id(s) against primary key(s).
     *
     *        To match against another field, 'id' MUST be an associative array
     *        of condition/value pairs.
     *
     *  @return The matching Accessor Model (null if no match).
     */
    public function _find($id)
    {
        $where = $this->_where($id);

        $accessor = $this->getAccessor();
        $select   = $accessor->select();

        foreach ($where as $condition => $bindValue)
        {
            $select->where($condition, $bindValue);
        }

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::_find(%s): "
                        .   "sql[ %s ]",
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

            // Re-throw the exception
            throw $e;
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

    /** @brief  Given an entry 'id', generate a matching WHERE clause.
     *  @param  id      An entry id (integer, string, array).
     *
     *  Note: If 'id' is a simple array of values or a single value, this
     *        method will only match those id(s) against primary key(s).
     *
     *        To match against another field, 'id' MUST be an associative array
     *        of condition/value pairs.
     *
     *  @return An array contining one or more WHERE clauses.
     */
    protected function _where($id)
    {
        if ($id instanceof Zend_Db_Select)
            return $id;

        if (is_array($id))
        {
            // Ensure that each condition is bindable (i.e. has '?').
            $where = array();
            foreach ($id as $condition => $bindValue)
            {
                if (is_int($condition))
                {
                    if ($condition > count($this->_keyName))
                    {
                        throw new Exception("Connexions_Model_Mapper_DbTable::"
                                            . "_where(): Too many conditions "
                                            . "for the number of keys "
                                            . "( ". $condition ." > "
                                            . count($this->_keyName) ." )");
                    }

                    $condition = $this->_keyName[$condition];
                }

                if (strpos($condition, '?') === false)
                {
                    if (is_array($bindValue))
                        $condition .= ' IN (?)';
                    else
                        $condition .= '=?';
                }

                $where[ $condition] = $bindValue;

                /*
                Connexions::log("Connexions_Model_Mapper_DbTable[%s]::"
                                . "_where(): add where [ %s, %s ]",
                                get_class($this),
                                $condition, $bindValue);
                // */
            }
        }
        else
        {
            if (is_array($this->_keyName))
            {
                throw new Exception(
                            "Connexions_Model_Mapper_DbTable::_where(): "
                            . "model [ ". get_class($this) ." ]: "
                            . "mismatch between key count and id count: "
                            . count($this->_keyName) ." != 1");
            }

            $where[ $this->_keyName .'=?' ] = $id;
        }

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::"
                        . "_where(): where [ %s ]",
                        get_class($this),
                        Connexions::varExport($where));
        // */

        return $where;
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
        $countColumn = $db->quoteIdentifier(self::ROW_COUNT_COLUMN);
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
                  (count($groups) > 1)                   ||
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

                /* Grouping can consist of multiple group identifiers, which
                 * MUST all be included here in order to properly count unique
                 * items.
                $parts = array();
                foreach ($groups as $group)
                {
                    array_push($parts, $db->quoteIdentifier($group, true));
                }

                $groupPart = implode(',', $parts);
                 */

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

    /*********************************************************************
     * Abstract methods
     *
     */
}
