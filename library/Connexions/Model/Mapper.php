<?php
/** @file
 *
 *  Abstract base class for a Data Access Object to Model mapper.
 *
 */
abstract class Connexions_Model_Mapper
{
    /** @brief  Returned from a factory if no instance can be located or 
     *          generated.
     */
    const       NO_INSTANCE             = -1;


    // A cache of Data Accessor instances, by class name
    static protected    $_instCache     = array();

    /** @brief  An identity map holding Domain Model instances generated by
     *          this mapper.
     */
    protected           $_identityMap   = array();


    // An array of one or more key names.
    protected   $_keyNames  = null;

    /* Data Accessor (e.g. Table Data Gateway / Zend_Db_Table_Abstract) for
     * this mapper.
     */
    protected           $_accessor      = null;

    // The name of the Model class this mapper is associated with.
    protected           $_modelName     = null;

    // The name of the Model Set class to use when retrieving multiple items.
    protected           $_modelSetName  = null;

    /** @brief  Create a new mapper.
     *  @param  config  Configuration:
     *                      accessor        The name of a Data Accessor class,
     *                                      or the Data Accessor Object
     *                                      instance to use
     *                                      (e.g.  Model_Mapper_*)
     *                      modelName       The name of the domain model
     *                                      [ $this->_modelName ];
     *                      modelSetName    The name of the Data Set class to
     *                                      use when retrieving multiple items
     *                                      (by default, this will be
     *                                       constructed from the name of the
     *                                       concrete class of this Mapper).
     */
    public function __construct($config = array())
    {
        $config = (array)$config;

        foreach ($config as $key => $val)
        {
            $method = 'set'. ucfirst($key);
            if (! method_exists( $this, $method ))
            {
                throw new Exception(get_class($this)
                                    . ": Unknown property '{$key}'");
            }

            $this->{$method}($val);
        }

        // Register this instance with the factory
        self::factory($this);
    }

    /** @brief  Set the current Data Access Object instance.
     *  @param  accessor    The new Data Access Object instance.
     *
     *  Note: If a concrete sub-class is able to establish a default accessor,
     *        it should do so in this method when the incoming accessor is null.
     *
     *  @return $this for a fluent interface.
     */
    public function setAccessor($accessor)
    {
        $this->_accessor = self::accessorFactory($accessor);

        return $this;
    }

    /** @brief  Get the current Data Access Object instance.
     *  @param  name    The name of the specific Accessor to retrieve.
     *
     *  @return The current Data Access Object instance.
     */
    public function getAccessor($name = null)
    {
        if ($name !== null)
        {
            // Retrieve a specific Accessor (by class name).
            return self::accessorFactory($name);
        }

        if ( (! is_object($this->_accessor)) &&
             ($this->_accessor !== self::NO_INSTANCE) )
        {
            /* No accessor has been set.  Rely on any concrete sub-class to
             * identify a default accessor and return it.
             */
            $this->setAccessor($this->_accessor);
        }

        if (! is_object($this->_accessor))
        {
            throw new Exception("Connexions_Model_Mapper::getAccessor: "
                                . "No accessor located for this mapper "
                                . '( '. get_class($this) .' )');
        }

        return $this->_accessor;
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
             *      Model_Mapper_<Class> => Model_<Class>
             */
            $this->_modelName = str_replace('Model_Mapper_', 'Model_',
                                            get_class($this));
        }

        return $this->_modelName;
    }

    /** @brief  Set the name of the Model Set class to use when retrieving
     *          multiple items.
     *  @param  modelSetName    The name of the Model Set.
     *
     *  @return $this for a fluent interface.
     */
    public function setModelSetName($modelSetName = null)
    {
        if ($modelSetName === null)
        {
            /* Use the name of the current class to construct a Model Set
             * class name:
             *      Model_Mapper_<Class> => Model_Set_<Class>
             */
            $modelSetName = str_replace('Model_Mapper_', 'Model_Set_',
                                        get_class($this));

            /*
            Connexions::log("Connexions_Model_Mapper::setModelSetName(%s)",
                            $modelSetName);
            // */
        }

        $this->_modelSetName = $modelSetName;

        return $this;
    }

    /** @brief  Get the name of the Model Set class for this mapper.
     *
     *  @return The name of the Model Set class.
     */
    public function getModelSetName()
    {
        if ($this->_modelSetName === null)
        {
            /* No name has been set.  Invoke 'setModelSetName()' to construct
             * the name from the name of the class of this instance.
             */
            $this->setModelSetName();
        }

        return $this->_modelSetName;
    }

    /** @brief  Find a matching Domain Model or create a new one given raw
     *          data. 
     *  @param  data        The raw data.
     *
     *  @return A matching Domain Model
     *          (MAY be backed if a matching instance already exists).
     */
    public function getModel($data)
    {
        $model = $this->find($data);
        if ($model !== null)
        {
            return $model;
        }

        // Create a new, un-backed Domain Model.
        return $this->makeModel($data, false);
    }

    /** @brief  Create a new instance of the Domain Model given raw data, 
     *          typically from a persistent store.
     *  @param  data        The raw data.
     *  @param  isBacked    Is the incoming data backed by persistent store?
     *                      [ true ];
     *
     *  @return A matching Domain Model
     *          (MAY be backed if a matching instance already exists).
     */
    public function makeModel($data, $isBacked = true)
    {
        /* First, see if there is already an Identity Map entry matching the 
         * incoming data.
         */
        $id = $this->getId($data);
        if ($this->_hasIdentity($id))
        {
            $domainModel = $this->_getIdentity($id);

            /* Refresh this instance with the incoming data.  This is to ensure 
             * that any updates are reflected, primarily for things like 
             * statistics and counts.
             */
            return $domainModel->populate($data);
        }

        // No existing entry.  Create a new instance.
        $modelName   = $this->getModelName();
        $domainModel = new $modelName(array('mapper'    => $this,
                                            'isBacked'  => $isBacked,
                                            //'isValid'   => $isBacked,
                                            'data'      => $data,
                                            ));

        /*
        Connexions::log("Connexions_Model_Mapper::makeModel(): "
                        .   "isBacked[ %s ], data[ %s ], model[ %s ]",
                        ($isBacked === true ? 'true' : 'false'),
                        Connexions::varExport($data),
                        $domainModel->debugDump());
        // */

        /*
        $dId = $this->getId($domainModel);
        if ($id != $dId)
        {
            throw new Exception(  'Raw ID    [ '. implode(':', $id) .' ] != '
                                . 'Domain Id [ '. implode(':', $dId).' ]');
        }
         */


        // Add this new instance to the Identity Map
        $this->_setIdentity($id, $domainModel);

        return $domainModel;
    }

    /** @brief  Convert the incoming model into an array containing only 
     *          data that should be directly persisted.  This method may also
     *          be used to update dynamic values
     *          (e.g. update date/time, last visit date/time).
     *  @param  model   The Domain Model to reduce to an array.
     *
     *  @return A filtered associative array containing data that should 
     *          be directly persisted.
     */
    public function reduceModel(Connexions_Model $model)
    {
        return $model->toArray( array('deep'    => false,
                                      'public'  => false,
                                      'dirty'   => true) );
    }

    /** @brief  Given identification value(s) that will be used for retrieval,
     *          normalize them to an array of attribute/value(s) pairs.
     *  @param  id      Identification value(s) (string, integer, array).
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value pairs.
     *
     *  Note: This a support method for Services and
     *        Connexions_Model_Mapper::normalizeIds()
     *
     *  @return An array containing attribute/value(s) pairs suitable for
     *          retrieval.
     */
    public function normalizeId($id)
    {
        if (is_array($id))
        {
            $nKeys      = count($this->_keyNames);
            $nKeysUsed  = 0;
            $normalized = array();

            // Walk through all specified values...
            foreach ($id as $key => $val)
            {
                /* If this array entry has an integer index/key:
                 *      ASSUME a 1-to-1 match with the valid keys for this
                 *      Model;
                 * Else ASSUME it is a valid field for this model.
                 *
                 *      Note: Connexions_Model_Mapper_DbTable COULD override 
                 *      this method and validate the field via:
                 *              $accessor = $this->getAccessor();
                 *              $fields   = $accessor->info(
                 *                              Zend_Db_Table_Abstract::COLS);
                 *
                 *              // and use $fields to validate, remembering to
                 *              // normalize field names between the two...
                 */
                if (is_int($key))
                {
                    if ($nKeysUsed >= $nKeys)
                    {
                        throw new Exception("Too many unnamed values for "
                                            . "the number of keys "
                                            . '('. $nKeys .')');
                    }

                    // Use the next available key name.
                    $key = $this->_keyNames[ $nKeysUsed++ ];
                }

                /* ASSUME the caller will NOT mix indexed fields, which map to
                 * key names, with named fields that map to the same key names.
                 * If they do, only the last one will survive.
                 */
                $normalized[$key] = $val;

                /* If we want to support mixing indexed fields with named
                 * fields that map to the same key names...
                 *
                if (isset($normalized[$key]))
                {
                    $normalized[$key] = (array)$normalized[$key];
                    array_push($normalized[$key], $val);
                }
                else
                {
                    $normalized[$key] = $val;
                }
                */
            }
            $id = $normalized;
        }
        else if (! is_null($id))
        {
            // Use the first key to construct the normalized id.
            $id = array($this->_keyNames[0] => $id);
        }

        return $id;
    }

    /** @brief  Given either a Domain Model instance or data that will be used 
     *          to construct a Domain Model instance, return the unique 
     *          identifier representing the instance.
     *
     *  @param  model   Connexions_Model instance or an array of data to be 
     *                  used in constructing a new Connexions_Model instance.
     *
     *  Note: There MUST be a 1-to-1 mapping between _keyNames and the array of
     *        values returned.
     *
     *  @return An array containing unique identifier values.
     */
    public function getId($model)
    {
        if ($model instanceof Connexions_Model)
        {
            return (array)$model->getId();
        }

        /*****************************************************
         * March through the keys for this class and
         * generate an array containing the values of those
         * keys from 'model'.  For missing items, use 0.
         */
        $uid      = array();
        foreach ($this->_keyNames as $key)
        {
            if (isset($model[$key]))
            {
                array_push($uid, $model[$key]);
            }
            else
            {
                array_push($uid, 0);
            }
        }

        /*
        Connexions::log("Connexions_Model_Mapper[%s]::getId(): "
                        .   "keyNames[ %s ], uid[ %s ]",
                        get_class($this),
                        Connexions::varExport($this->_keyNames),
                        Connexions::varExport($uid));
        // */

        return $uid;
    }


    /** @brief  Remove the given model instance from the identity map.
     *  @param  model   The model instance.
     *
     *  @return $this for a fluent interface.
     */
    public function unsetIdentity(Connexions_Model $model)
    {
        $this->_unsetIdentity( $this->getId($model) /*$model->getId()*/,
                               $model);

        return $this;
    }

    /** @brief  Remove all entries from the identity map.
     *
     *  @return $this for a fluent interface.
     */
    public function flushIdentityMap()
    {
        foreach ($this->_identityMap as $key => $item)
        {
            $item->invalidate();
        }

        $this->_identityMap = array();

        return $this;
    }

    /******** DEBUG vvvv { **********
    public function dumpIdentityMap()
    {
        printf ("Identity map for '%s'.  %d items:\n",
                get_class($this), count($this->_identityMap));

        $idex = 0;
        foreach ($this->_identityMap as $key => $item)
        {
            printf (" %2d: %-15s, [ %s ]\n",
                    ++$idex, $key, $item->__toString());
        }
    }
     ******** DEBUG ^^^^ } **********/

    /************************************
     * Support for Connexions_Model_Set
     *
     */

    /** @brief  Construct an empty Model_Set_* instance. */
    public function makeEmptySet()
    {
        $setName = $this->getModelSetName();
        $set     = new $setName(array('mapper'      => $this,
                                      'modelName'   => $this->getModelName(),
                                      'totalCount'  => 0));

        return $set;
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
        $id = array( $field => $value );

        /*
        Connexions::log("Connexions_Model_Mapper::fetchBy(%s, %s): "
                        .   "[ %s ]",
                        $field, Connexions::varExport($value),
                        Connexions::varExport($id) );
        // */

        return $this->fetch($id, $order, $count, $offset);
    }

    /** @brief  Given an array of identification value(s) that will be used to
     *          retrieve a set of model instances (via fetch()), normalize them 
     *          to an array of attribute/value(s) pairs.
     *  @param  ids     An array of identification value(s) (string, integer, 
     *                  array).  Each identification value MAY be an 
     *                  associative array that specifically identifies 
     *                  attribute/value pairs.
     *
     *  @return An array containing arrays of attribute/value(s) pairs suitable 
     *          for retrieval.
     */
    public function normalizeIds($ids)
    {
        /*
        Connexions::log("Connexions_Model_Mapper::normalizeIds( %s )",
                        Connexions::varExport($ids));
        // */

        if ( (! is_array($ids)) ||
             ( ($keys = array_keys($ids)) && (! is_int($keys[0]))) )
        {
            /* Non-integer keys == named keys.  Treat this as an identifier
             * that can be handled by normalizeId() -- of the form:
             *
             *      { 'key' => id value(s), ... }
             */
            return $this->normalizeId($ids);
        }

        /* Numeric keys indicate an array of identifiers.
         *
         * The key mapping is performed via normalizeId() on the first item
         * and then applied to all items.
         *
         * Simple transformations:
         *  [1,2,3,4]           => { 'key1':        [1,2,3,4] }
         *
         *  ['a','b','c','d']   => { 'key3':        ['a','b','c','d'] }
         *
         *  {'key1': [1,2,3,4],
         *   'key3': ['a','b']} => { 'key1':        [1,2,3,4],
         *                           'key3':        ['a','b'] }
         *
         *
         * More Complex transformations:
         *  [ [1,2], [3,4] ]    => { '(key1,key2)': [ [1,2], [3,4] ] }
         *
         *  [ { 'key1': 1,
         *      'key2': 2,
         *    },
         *    { 'key1': 3,
         *      'key2': 4,
         *    }
         *  ]                   => { '(key1,key2)': [ [1,2], [3,4] ] }
         *
         */
        $newIds     = array();
        $isMultiKey = -1;
        foreach ($ids as $id)
        {
            $norm = $this->normalizeId($id);
            if ($isMultiKey < 0)
            {
                $keys = array_keys($norm);
                if (count($keys) === 1)
                {
                    // Simple { 'key': [1,2,3,4, ...] }
                    $newIds = array( $keys[0] => $ids );
                    break;
                }

                // Complex { '(key1,key2)': [ [1,2], [3,4], ... ] }
                $isMultiKey = true;
            }

            foreach ($keys as $key)
            {
                if (! is_array($newIds[$key]))
                    $newIds[$key] = array();

                if (isset($norm[$key]))
                    array_push($newIds[$key], $norm[$key]);
                else
                    throw new Exception("Mixed keys");
            }
        }

        /*
        Connexions::log("Connexions_Model_Mapper::normalizeIds( %s ) "
                        .   "== [ %s ]",
                        Connexions::varExport($ids),
                        Connexions::varExport($newIds));
        // */

        return $newIds;
    }

    /*********************************************************************
     * Abstract methods
     *
     */

    /** @brief  Save the given model instance.
     *  @param  model   The model instance to save.
     *
     *  Note: This should invoke reduceModel() on Model Data before it is
     *        persisted.
     *
     *  @return The updated model instance.
     */
    abstract public function save(Connexions_Model $model);

    /** @brief  Delete the data for the given model instance.
     *  @param  model   The model instance to delete.
     *
     *  @return $this for a fluent interface.
     */
    abstract public function delete(Connexions_Model $model);

    /** @brief  Retrieve the model instance with the given id.
     *  @param  id      An array of 'property/value' pairs identifying the
     *                  desired model.
     *
     *  @return The matching model instance (null if no match).
     */
    abstract public function find($id);

    /************************************
     * Support for Connexions_Model_Set
     *
     */

    /** @brief  Fetch all matching model instances.
     *  @param  id      An array of 'property/value' pairs identifying the
     *                  desired models, or null to retrieve all.
     *  @param  order   Optional ORDER clause (string, array)
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A Connexions_Model_Set instance that provides access to all
     *          matching Domain Model instances.
     */
    abstract public function fetch($id      = null,
                                   $order   = null,
                                   $count   = null,
                                   $offset  = null);

    /** @brief  In support of lazy-evaluation, this method retrieves the
     *          specified range of values for an existing set.
     *  @param  set     Connexions_Model_Set instance.
     *  @param  offset  The beginning offset.
     *  @param  count   The number of items.
     *
     *  @return An array containing Connexions_Model instances for each item in
     *          the specified range.
     */
    abstract public function fillItems(Connexions_Model_Set  $set,
                                       $offset, $count);

    /** @brief  Return an array of the Identifiers of all items in this set,
     *          regardless of offset or limit restrictions.
     *
     *  @return An array of all Identifiers.
     */
    abstract public function getIds(Connexions_Model_Set   $set);

    /** @brief  Get the total count for the given Model Set.
     *  @param  set     Connexions_Model_Set instance.
     *
     *  @return The total count.
     */
    abstract public function getTotalCount(Connexions_Model_Set $set);

    /** @brief  Get the starting offset of the given Model Set.
     *  @param  set     Connexions_Model_Set instance.
     *
     *  @return The starting offset.
     */
    abstract public function getOffset(Connexions_Model_Set $set);

    /*********************************************************************
     * Protected methods
     *
     */

    /** @brief  See if we have a model instance for the given 'id'
     *  @param  id      The model instance identifier.
     *
     *  @return true | false
     */
    protected function _hasIdentity($id)
    {
        if (is_array($id))
            $id = implode(':', array_values($id));

        return array_key_exists($id, $this->_identityMap);
    }

    /** @brief  See if we've already instantiated a model instance for the
     *          given 'id'
     *  @param  id      The model instance identifier.
     *
     *  @return The Model instance (null if not found).
     */
    protected function _getIdentity($id)
    {
        if (is_array($id))
            $id = implode(':', array_values($id));

        if (array_key_exists($id, $this->_identityMap))
        {
            /*
            Connexions::log("Connexions_Model_Mapper::_getIdentity( %s ): %s: "
                            .   "return identity instance",
                             $id, get_class($this));
            // */

            return $this->_identityMap[$id];
        }

        return null;
    }

    /** @brief  Save a new Model instance in our identity map.
     *  @param  id      The model instance identifier.
     *  $param  model   The model instance.
     *
     */
    protected function _setIdentity($id, Connexions_Model $model)
    {
        if (is_array($id))
            $id = implode(':', array_values($id));

        if (empty($id))
            // An entry with no unique id should NOT be in the map!
            return;

        /*
        if ($this->_hasIdentity($id))
        {
            Connexions::log("Connexions_Model_Mapper::_setIdentity( %s ): %s: "
                            .   "replacing existing instance",
                             $id, get_class($this));
        }
        // */

        /*
        Connexions::log("Connexions_Model_Mapper::_setIdentity( %s ): %s",
                         $id, get_class($this));
        // */

        $this->_identityMap[$id] = $model;
    }

    /** @brief  Remove an identity map entry.
     *  @param  id      The model instance identifier.
     *  $param  model   The model instance currently mapped.
     */
    protected function _unsetIdentity($id, Connexions_Model $model)
    {
        if (is_array($id))
            $id = implode(':', array_values($id));

        /*
        Connexions::log("Connexions_Model_Mapper::_unsetIdentity( %s ): %s",
                        $id, get_class($this));
        // */

        unset($this->_identityMap[$id]);
    }

    /*********************************************************************
     * Static methods
     *
     */

    /** @brief  Given a Mapper Class name, retrieve the associated Mapper 
     *          instance.
     *  @param  mapper The Mapper Class name or instance.
     *
     *  @return The Model_Mapper instance.
     */
    public static function factory($mapper)
    {
        if ($mapper instanceof Connexions_Model_Mapper)
        {
            $mapperName = get_class($mapper);
        }
        else if (is_string($mapper))
        {
            // See if we have a Mapper instance with this name in our cache
            $mapperName = $mapper;
            if ( isset(self::$_instCache[ $mapperName ]))
            {
                // YES - use the existing instance
                $mapper =& self::$_instCache[ $mapperName ];
            }
            else
            {
                // NO - create a new instance
                try
                {
                    @Zend_Loader_Autoloader::autoload($mapperName);
                    $mapper  = new $mapperName();
                }
                catch (Exception $e)
                {
                    // Simply return null
                    $mapper = self::NO_INSTANCE;

                    // /*
                    Connexions::log("Connexions_Model_Mapper::factory: "
                                    . "CANNOT locate class '%s'",
                                    $mapperName);
                    // */
                }
            }
        }
        else
        {
            throw new Exception("Connexions_Model_Mapper::factory(): "
                                . "requires a Connexions_Model_Mapper "
                                . "instance or mapper name string");
        }

        if (! isset(self::$_instCache[ $mapperName ]))
        {
            self::$_instCache[ $mapperName ] = $mapper;

            /*
            Connexions::log("Connexions_Model_Mapper::factory( %s ): "
                            . "cache this Mapper instance",
                            $mapperName);
            // */
        }

        return $mapper;
    }

    /** @brief  Given a Data Accessor Class name, retrieve the associated
     *          Accessor instance.
     *  @param  accessor    The Data Accessor Class name or instance.
     *
     *  @return The Model_Mapper_* instance.
     */
    public static function accessorFactory($accessor)
    {
        if ( is_object($accessor) )
        {
            $accessorName = get_class($accessor);
        }
        else
        {
            // See if we have a Accessor instance with this name in our cache
            $accessorName = $accessor;
            if ( isset(self::$_instCache[ $accessorName ]))
            {
                // YES - use the existing instance
                $accessor =& self::$_instCache[ $accessorName ];
            }
            else
            {
                // NO - create a new instance
                try
                {
                    @Zend_Loader_Autoloader::autoload($accessorName);
                    $accessor  = new $accessorName();
                }
                catch (Exception $e)
                {
                    // Simply return null
                    $accessor = self::NO_INSTANCE;

                    // /*
                    Connexions::log("Connexions_Model_Mapper::accessorFactory: "
                                    . "CANNOT locate class '%s'",
                                    $accessorName);
                    // */
                }
            }
        }

        if (! isset(self::$_instCache[ $accessorName ]))
        {
            self::$_instCache[ $accessorName ] = $accessor;

            /*
            Connexions::log("Connexions_Model::accessorFactory( %s ): "
                            . "cache this Accessor instance ( %s )",
                            $accessorName,
                            (is_object($accessor)
                                ? 'class: '. get_class($accessor)
                                : $accessor));
            // */
        }

        return $accessor;
    }
}
