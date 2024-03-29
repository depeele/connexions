<?php
/** @file
 *
 *  The abstract base class for Connexions Domain Models.
 *
 *  The Domain Models are responsible for representing the data and operations 
 *  on that data with no direct connection to the underlying persistence layer 
 *  (e.g. no SQL).
 */
abstract class Connexions_Model
{
    /** @brief  Returned from a factory if no instance can be located or 
     *          generated.
     */
    const       NO_INSTANCE         = -1;

    /** @brief  A cache of instances, by class name */
    static protected    $_instCache = array();

    /** @brief  The Data Mapper for this instance. */
    protected           $_mapper    = null;

    /** @brief  The validation filter for this instance
     *          (e.g. Zend_Form, Zend_Filter_Input)
     */
    protected           $_filter    = null;

    /** @brief  The data of this instance. */
    protected           $_data      = array();
    protected           $_valid     = array();
    protected           $_dirty     = array();

    /** @brief  Is the contained data directly from / saved in a persistent 
     *          backing store?
     */
    protected           $_isBacked  = false;

    /** @brief  Is the contained data valid / has it been validated?
     */
    protected           $_isValid   = false;

    /** @brief  A flag allowing populate() to influence _set() so we can use
     *          __set() but still delay validation until populate() is
     *          complete.
     */
    protected           $_delayValidation   = false;

    /*************************************************************************/

    /** @brief  Create a new Domain Model instance.
     *  @param  config  Model configuration:
     *                      mapper      The name of a Data Mapper class, or a
     *                                  Mapper instance to use
     *                                  (e.g. Connexions_Model_Mapper)
     *
     *                                  If not provided, a default will be
     *                                  located when needed.  This will be
     *                                  based upon the name of this class
     *
     *                      filter      The name of a Validation Filter class,
     *                                  or a Filter instance to use
     *                                  (e.g. Zend_Form, Zend_Filter_Input)
     *
     *                                  If not provided, a default will be
     *                                  located when needed.  This will be
     *                                  based upon the name of this class
     *
     *                      data        The raw data for this model instance.
     *                                  Note: The raw data MAY also be directly
     *                                        contained within 'config' as
     *                                        key/value pairs.
     *
     *                      isBacked    Is the data backed by persistent
     *                                  storage?
     *                      isValid     Has this data been validated?
     *
     */
    public function __construct($config = array())
    {
        if ($config instanceof Zend_Db_Table_Row_Abstract)
        {
            $config = $config->toArray();
        }
        else
        {
            $config  = (array)$config;
        }

        if (isset($config['data']))
        {
            if (isset($config['isValid']) && ($config['isValid'] === true))
            {
                /* We have an assertion that this data is valid so do NOT
                 * perform validation in populate()
                 */
                $this->_delayValidation = true;
            }

            $this->populate($config['data']);

            $this->_delayValidation = false;

            // Include any meta-settings
            foreach ($config as $key => $val)
            {
                if ($key === 'data')
                {
                    continue;
                }

                /*
                Connexions::log("Connexions_Model[%s]: config [ %s, %s ]",
                                get_class($this),
                                $key, (is_object($val)
                                        ? get_class($val)
                                        : Connexions::varExport($val)));
                // */

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
        else
        {
            // ASSUME the 'config' data IS record data and simply set it.
            $this->populate( $config );
        }

        if ($this->isBacked())
        {
            // This is a backed model so no fields are dirty
            $this->_dirty = array();
        }
    }

    /** @brief  Given incoming record data, populate this model instance.
     *  @param  data    Incoming key/value record data.
     *
     *  @return $this for a fluent interface.
     */
    public function populate($data)
    {
        if ($data instanceof Zend_Db_Table_Row_Abstract)
        {
            $data = $data->toArray();
        }
        else if (is_object($data))
        {
            $data = (array)$data;
        }
        if (! is_array($data))
        {
            throw new Exception("Connexions_Model::populate(): "
                                . "data MUST be an array or object");
        }

        /*
        Connexions::log("Connexions_Model[%s]::populate(): data[ %s ]",
                        get_class($this),
                        Connexions::varExport($data));
        // */

        $skipValidation = $this->_delayValidation;
        $this->_delayValidation = true;
        foreach ($data as $key => $val)
        {
            /*
            Connexions::log("Connexions_Model[%s]::populate() [ %s, %s ]",
                            get_class($this),
                            $key, (is_object($val)
                                    ? get_class($val)
                                    : Connexions::varExport($val)));
            // */

            $this->__set($key, $val);
        }
        $this->_delayValidation = $skipValidation;

        if ($skipValidation !== true)
        {
            // Perform full validation of the populated data
            $this->validate();
        }

        /*
        Connexions::log("Connexions_Model[%s]::populate(): complete[ %s ]",
                        get_class($this),
                        $this->debugDump());
        // */

        return $this;
    }

    /** @brief  Get a value of the given field.
     *  @param  name    The field name.
     *
     *  @return The field value (null if invalid).
     */
    public function __get($name)
    {
        /*
        Connexions::log("Connexions_Model[%s]::__get( %s ): "
                        .   "val[ %s ]",
                        get_class($this),
                        $name,
                        (isset($this->_data[$name])
                            ? Connexions::varExport($this->_data[$name])
                            : 'undefined'));
        // */

        if (array_key_exists($name, $this->_data))
        {
            return $this->_data[$name];
        }

        //return null;
    }

    /** @brief  Set the value of the given field.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  @return $this for a fluent interface.
     */
    public function __set($name, $value)
    {
        return $this->_set($name, $value,
                           ($this->_delayValidation ? false : true));
    }

    /** @brief  Is the given field set?
     *  @param  name    The field name.
     *
     *  @return true | false
     */
    public function __isset($name)
    {
        return (isset($this->_data[$name]));
    }

    /** @brief  Unset a field
     *  @param  name    The field name.
     *
     *  @return void
     */
    public function __unset($name)
    {
        if (isset($this->$name))
        {
            unset($this->_data[$name]);
        }
    }

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        $id = $this->getId();
        if (is_array($id))
            $str = implode(',', $id);
        else
            $str = (String)$id;

        return $str;
    }

    /** @brief  Return an array version of this instance.
     *  @param  props   Generation properties:
     *                      - deep      Deep traversal (true)
     *                                    or   shallow (false)
     *                                    [true];
     *                      - public    Include only public fields (true)
     *                                    or  also include private (false)
     *                                    [true];
     *                      - dirty     Include only dirty fields (true)
     *                                    or           all fields (false);
     *                                    [false];
     *
     *  @return An array representation of this Domain Model.
     */
    public function toArray(array $props    = array())
    {
        /*
        Connexions::log("Connexions_Model(%s)::toArray(): props[ %s ]",
                        get_class($this),
                        Connexions::varExport($props));
        // */

        if ( isset($props['dirty']) && ($props['dirty'] === true) )
        {
            $ret = array();
            foreach ($this->_data as $key => $val)
            {
                if ( isset($this->_dirty[$key]))
                {
                    $ret[$key] = $val;
                }
            }
        }
        else
        {
            $ret = $this->_data;
        }

        /*
        Connexions::log("Connexions_Model::toArray(): return[ %s ]",
                        Connexions::varExport($ret));
        // */

        return $ret;
    }

    /** @brief  Return a JSON-encoded version of this instance.
     *  @param  props   Generation properties:
     *                      - deep      Deep traversal (true)
     *                                    or   shallow (false)
     *                                    [false];
     *                      - public    Include only public fields (true)
     *                                    or  also include private (false)
     *                                    [true];
     *                      - dirty     Include only dirty fields (true)
     *                                    or           all fields (false);
     *                                    [false];
     *
     *  @return An array representation of this Domain Model.
     */
    public function toJson(array $props    = array())
    {
        if (! isset($props['deep']))    $props['deep'] = false;

        /*
        Connexions::log("Connexions_Model(%s)::toJson(): props[ %s ]",
                        get_class($this),
                        Connexions::varExport($props));
        // */

        $json = json_encode( $this->toArray($props) );
        return $json;
    }

    /** @brief  Invalidate the data contained in this model instance.
     *
     *  @return $this for a fluent interface.
     */
    public function invalidate()
    {
        // Clear any Mapper-based identity map
        $this->getMapper()->unsetIdentity( $this );

        foreach ($this->_data as $key => &$val)
        {
            $val = null;
            //$this->__set($key, null);
        }

        $this->setIsBacked(false);
        $this->setIsValid( false);

        return $this;
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
             *      Model_<Class> => Model_Mapper_<Class>
             */
            $mapper = str_replace('Model_', 'Model_Mapper_',
                                  get_class($this));

            /*
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
             ($this->_mapper !== self::NO_INSTANCE) )
        {
            // Establish a default mapper and return it.
            $this->setMapper($this->_mapper);
        }
        //if (! $this->_mapper instanceof Connexions_Model_Mapper)

        return $this->_mapper;
    }

    /** @brief  Set the data validation filter.
     *  @param  filter      The data validation filter.
     *
     *  @return $this for a fluent interface.
     */
    public function setFilter($filter = null)
    {
        if ( (! is_object($filter)) && ($filter !== self::NO_INSTANCE) )
        {
            /* Use the name of the current class to construct a Filter
             * class name:
             *      Model_<Class> => Model_Filter_<Class>
             */
            $filter = str_replace('Model_', 'Model_Filter_',
                                  get_class($this));
        }

        /* Invoke the filterFactory.  If 'filter' is an incoming Filter 
         * instance, this will ensure that we have a cached version.  
         * Otherwise, look in the cache for an existing instance, if not found, 
         * create and cache a new Filter instance.
         */
        $this->_filter = Connexions_Model_Filter::factory($filter);

        return $this;
    }

    /** @brief  Retrieve the data validation filter.
     *
     *  @return A validation filter instance.
     */
    public function getFilter()
    {
        if ( (! is_object($this->_filter)) &&
             ($this->_filter !== self::NO_INSTANCE) )
        {
            // Establish a validation filter
            $this->setFilter($this->_filter);
        }
        /*
        if ((! $this->_filter instanceof Zend_Form) &&
            (! $this->_filter instanceof Zend_Filter_Input))
        */

        return $this->_filter;
    }

    /** @brief  Set the 'isBacked' indicator
     *  @param  value   The value (will be cast to boolean).
     *
     *  @return $this for a fluent interface.
     */
    public function setIsBacked($value = true)
    {
        /*
        Connexions::log("Connexions_Model[%s]::setIsBacked(): "
                        .   "id[ %s ], val[ %s ]",
                        get_class($this),
                        Connexions::varExport( $this->getId() ),
                        Connexions::varExport($value));
        // */

        $this->_isBacked = (bool)$value;

        return $this;
    }

    /** @brief  Retrieve the 'isBacked' indicator
     *
     *  Should we name this getIsBacked() instead??
     */
    public function isBacked()
    {
        return $this->_isBacked;
    }

    /** @brief  Set the 'isValid' indicator
     *  @param  value   The value (will be cast to boolean).
     *
     *  @return $this for a fluent interface.
     */
    public function setIsValid($value = true)
    {
        $this->_isValid = (bool)$value;

        // Mark all fields with the validation status of the model
        $this->_valid   = array();
        foreach ($this->_data as $fieldName => $fieldValue)
        {
            $this->_valid[$fieldName] = $value;
        }

        return $this;
    }

    /** @brief  Retrieve the 'isValid' indicator
     *
     *  Should we name this getIsValid() instead??
     */
    public function isValid()
    {
        return $this->_isValid;
    }

    /** @brief  Perform validation over the current model data.
     *
     *  Note: This will also update '_data' to contain only valid data and
     *        update the parallel   '_valid' array to indicate which fields
     *        are valid (true) and which are not ( validation array or unset ).
     *
     *  @return true (valid) or false (invalid).
     */
    public function validate()
    {
        $filter = $this->getFilter();
        if ( is_object($filter) )
        {
            $filter->setData( $this->_data );

            // Reset the validation status based upon the filter
            $this->setIsValid( $filter->isValid() );

            /* Now, '_data' MAY have fields that are considered 'unknown' by
             * the filter.  If so, the filter will call the data 'invalid'.
             *
             * Retrieve validation information for all fields.  If there
             * are no fields marked 'invalid' call the model valid.
             */
            $messages = $filter->getMessages();

            $this->setIsValid();
            foreach ($this->_data as $fieldName => $value)
            {
                if ($filter->isValid($fieldName))
                {
                    $this->_valid[$fieldName] = true;
                    $this->_data[$fieldName]  =
                        $filter->getUnescaped($fieldName);
                }
                else if (array_key_exists($fieldName, $messages))
                {
                    $this->_valid[$fieldName] = $messages[$fieldName];

                    $this->_isValid = false;
                }
                else
                {
                    // There are no validation messages for this field
                    // so it is likely an "unknown" field.  Remove any
                    // validity information about it.
                    unset($this->_valid[$fieldName]);
                }
            }

            /*
            Connexions::log("Connexions_Model::validate(): "
                            .   "%svalid [ %s ], _valid[ %s ]",
                            ($this->isValid() ? '' : "NOT "),
                            $this->debugDump(),
                            Connexions::varExport($this->_valid));
            // */

            return $this->isValid();
        }
        else
        {
            // No filter -- call it valid.
            $this->setIsValid( true );
        }

        return $this->isValid();
    }

    /** @brief  If isValid() returns false, there SHOULD BE validation messages
     *          available indicating why the model is not valid.  These
     *          messages may be retrieved using this method.
     *
     *  Note: Validation is performed during __set() for individual fields,
     *        populate() after all fields have been set, or on demand via
     *        validate() for the entire model.
     *
     *  @return An array of validation messages
     *          (empty if there are no validation messages)
     */
    public function getValidationMessages()
    {
        $res    = array();
        foreach ($this->_valid as $fieldName => $validation)
        {
            if ($validation === true)
                continue;

            $res[$fieldName] = $validation;
        }

        return $res;
    }

    /** @brief  Retrieve the 'isDirty' indicator
     *
     *  Should we name this getIsDirty() instead??
     */
    public function isDirty()
    {
        return (! empty($this->_dirty));
    }

    /** @brief  Generate a string representation of this record.
     *  @param  indent      The number of spaces to indent [ 0 ];
     *  @param  leaveOpen   Should the terminating '];\n' be excluded [ false ];
     *
     *  @return A string.
     */
    public function debugDump($indent       = 0,
                              $leaveOpen    = false)
    {
        $str = str_repeat(' ', $indent)
             . get_class($this) .": is "
             .      ($this->isBacked() ? '' : 'NOT '). 'backed, '
             .      ($this->isValid()  ? '' : 'NOT '). 'valid, '
             .      ($this->isDirty()  ? '' : 'NOT '). 'dirty '
             .      "[\n";

        foreach ($this->_data as  $key => $val)
        {
            $type = gettype($val);
            if ($type === 'object')
                $type = get_class($val);
            else if ($type === 'boolean')
                $val = ($val ? 'true' : 'false');

            $str .= sprintf ("%s%-15s == %-15s %s%s [ %s ]%s\n",
                             str_repeat(' ', $indent + 1),
                             $key, $type,
                             (isset($this->_dirty[$key])
                                ? ($this->_dirty[$key] === true
                                    ? "*"
                                    : " ")
                                : " "),
                             (isset($this->_valid[$key])
                                ? ($this->_valid[$key] !== true
                                    ? "!"
                                    : " ")
                                : "?"),
                             $val,
                             (isset($this->_valid[$key])
                                ? ($this->_valid[$key] !== true
                                    ? (is_array($this->_valid[$key])
                                        ? " : ".
                                            implode(', ', $this->_valid[$key])
                                        : $this->_valid[$key])
                                    : '')
                                : ''));
        }

        if ($leaveOpen !== true)
            $str .= str_repeat(' ', $indent) .'];';

        return $str;
    }

    /*************************************************************************
     * Generic serialization operations.
     *
     */

    /** @brief  Invoked after save() with the new, now-backed instance to allow
     *          concrete models to copy over any non-backed meta-propeties
     *          (e.g.  authentication state).
     *  @param  model   The model instance being cloned.
     *
     *  @return $this for a fluent interface.
     */
    public function cloneOf(Connexions_Model $model)
    {
        return $this;
    }

    /** @brief  Save this instancne.
     *  @param  noLog   Should the activity log be bypassed?
     *
     *  @return The (updated) instance.
     */
    public function save($noLog = false)
    {
        /*
        Connexions::log("Connexions_Model[%s]::save(): noLog[ %s ]: %s",
                        get_class($this),
                        Connexions::varExport($noLog),
                        $this->debugDump());
        // */

        $res = $this;
        if (! empty($this->_dirty))
        {
            if ($noLog !== true)
            {
                if ( $this->isBacked())
                {
                    $operation  = 'update';
                    $properties = $this->toArray(array('deep'  => false,
                                                       'dirty' => true));
                }
                else
                {
                    $operation  = 'save';
                }
            }

            $res = $this->getMapper()->save( $this );

            if (($noLog !== true) && $res->isBacked())
            {
                if ($operation === 'save')
                {
                    $properties = $res->toArray(array('deep'  => false,
                                                      'dirty' => false));
                }
                $this->_logActivity($operation, $properties);
            }
        }

        return $res;
    }

    /** @brief  Delete this instance.
     *
     *  @return void
     */
    public function delete()
    {
        /*
        Connexions::log("Connexions_Model::delete(): [ %s ]",
                        $this->debugDump());
        // */
        if ( $this->isBacked())
        {
            // Use toArray() so we get a public version of field values
            $fullProperties = $this->toArray(array('deep'  => false,
                                                   'dirty' => false));
            $keys           = $this->getMapper()->getKeyNames();
            $properties     = array();
            foreach ($keys as $key)
            {
                $properties[$key] = $fullProperties[$key];
            }

            $this->_logActivity('delete', $properties);
        }

        return $this->getMapper()->delete( $this );
    }

    /** @brief  Simple comparision to see if the current model instance is the
     *          "same as" the provided model instance.
     *  @param  as      The model instance to compare against.
     *
     *  This simple comparison is completely based upon the equivalence of the
     *  primary ids of the two instances.
     *
     *  @return true | false
     */
    public function isSame(Connexions_Model $as)
    {
        $id1 = $this->getId();
        if (is_array($id1)) $str1 = implode(',', $id1);
        else                $str1 = (String)$id1;

        $id2 = $as->getId();
        if (is_array($id2)) $str2 = implode(',', $id2);
        else                $str2 = (String)$id2;

        return ($id1 == $id2);
    }

    /*********************************************************************
     * Abstract methods
     *
     */

    /** @brief  Retrieve the unique identifier for this instance.  This MAY 
     *          return an array of identifiers as key/value pairs.
     *
     *  @return The unique identifier.
     */
    abstract public function getId();


    /*********************************************************************
     * Protected methods
     *
     */

    /** @brief  Log activity
     *  @param  operation   The activity/operation
     *                      ('save', 'update', 'delete');
     *  @param  properties  An array of key/value pairs representing the object
     *                      properties to include;
     *
     *  @return this for a fluent interface.
     */
    protected function _logActivity(      $operation,
                                    array $properties = array())
    {
        /**************************************************
         * Store this operation in the activities table
         *
         */
        $objectType = strtolower(str_replace('Model_', '',
                                             get_class($this)));
        if ($objectType === 'activity')
        {
            // Don't go recursive by logging the activity of logging activity!!
            return $this;
        }

        $objectId   = $this->getId();

        if (is_array($objectId))    $objectId = implode(':', $objectId);
        else                        $objectId = (String)$objectId;

        $activity   = array(
            'userId'        => null,
            'objectId'      => $objectId,
            'operation'     => $operation,
            'objectType'    => $objectType,
            'properties'    =>  Zend_Json::encode($properties),
        );

        $actor = Connexions::getUser();
        if ($actor)
        {
            $activity['userId'] = $actor->getId();
        }

        // If 'userId' is STILL null, set it to 0.
        if ($activity['userId'] === null)   $activity['userId'] = 0;

        /*
        Connexions::log("Connexions_Model::_logActivity(): "
                        . "[ %s ]",
                        Connexions::varExport($activity));
        // */

        // Create a new instance and save.
        $activityInst = new Model_Activity($activity);
        $activityInst = $activityInst->save();

        /*
        Connexions::log("Connexions_Model::_logActivity(): "
                        . "[ %s ]",
                        $activityInst->debugDump());
        // */


        return $this;
    }

    /** @brief  Set the value of the given field.
     *  @param  name        The field name.
     *  @param  value       The new value.
     *  @param  validate    Should immediate validation be performed? [ true ]
     *
     *  @return $this for a fluent interface.
     */
    protected function _set($name, $value, $validate = true)
    {
        if (! array_key_exists($name, $this->_data))
        {
            /*
            Connexions::log("Connexions_Model[%s]::__set(%s, %s): "
                            . "Invalid property",
                            get_class($this),
                            $name,
                            (is_object($value)
                                ? get_class($value)
                                : Connexions::varExport($value)) );
            // */

            throw new Exception("Connexions_Model::__set(): "
                                . "Invalid property '{$name}'");
        }

        if ($this->_data[$name] !== $value)
        {
            $this->_dirty[$name] = true;
        }

        /*
        Connexions::log("Connexions_Model[%s]::__set(%s, %s, %s): dirty[ %s ]",
                        get_class($this),
                        $name,
                        Connexions::varExport($value),
                        Connexions::varExport($validate),
                        (isset($this->_dirty[$name]) ? 'true' : 'false') );
        // */


        // Assign the new value
        $this->_data[$name] = $value;

        if ($validate === true)
        {
            // Validate the incoming value
            $this->validate();
        }
                    
        return $this;
    }
}
