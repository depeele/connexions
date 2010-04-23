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

    /** @brief  Constants For toArray() */
    const       DEPTH_DEEP          = true;
    const       DEPTH_SHALLOW       = false;

    const       FIELDS_PUBLIC       = true;
    const       FIELDS_ALL          = false;


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

    /** @brief  Is the contained data directly from / saved in a persistent 
     *          backing store?
     */
    protected           $_isBacked  = false;

    /** @brief  Is the contained data valid / has it been validated?
     */
    protected           $_isValid   = false;

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

        $populated = false;
        foreach ($config as $key => $val)
        {
            /*
            Connexions::log("Connexions_Model[%s]: config [ %s, %s ]",
                            get_class($this),
                            $key, (is_object($val)
                                    ? get_class($val)
                                    : Connexions::varExport($val)));
            // */

            if ($key === 'data')
            {
                // Record data -- populate
                $this->populate($val);
                $populated = true;
            }
            else
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

        if (! $populated)
        {
            // ASSUME the 'config' data IS record data and simply set it.
            $this->populate( $config );
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

        return $this;
    }

    /** @brief  Get a value of the given field.
     *  @param  name    The field name.
     *
     *  @return The field value (null if invalid).
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->_data))
            return $this->_data[$name];

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

        /*
        Connexions::log("Connexions_Model::__set(%s, %s)",
                        $name, $value);
        // */

        // Validate the incoming value
        $filter = $this->getFilter();
        if ( is_object($filter) )
        {
            $data = $this->_data; $data[$name] = $value;
            $filter->setData( $data );
            $this->_valid[$name] = $filter->isValid($name);

            if (! $this->_valid[$name])
            {
                // Set the entire Model to "invalid"
                $this->setIsValid(false);

                Connexions::log("Connexions_Model::__set(%s, %s): INVALID",
                                $name, $value);
                return $this;

                /*
                throw new Exception("Connexions_Model::__set(): "
                                    . "Invalid value for '{$name}': "
                                    . Connexions::varExport(
                                                $filter->getMessages()) );
                */
            }

            $value = $filter->getUnescaped($name);
        }

        // Assign the new value
        $this->_data[$name] = $value;
                    
        return $this;
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
        return (String)($this->getId());
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
    public function toArray($deep   = self::DEPTH_DEEP,
                            $public = self::FIELDS_PUBLIC)
    {
        return $this->_data;
    }

    /** @brief  Invalidate the data contained in this model instance.
     *
     *  @return $this for a fluent interface.
     */
    public function invalidate()
    {
        // Ensure any Mapper-based identity map has been cleared.
        $this->unsetIdentity();

        foreach ($this->_data as $key => &$val)
        {
            $val = null;
            //$this->__set($key, null);
        }

        $this->_isBacked  = false;
        $this->_isValid   = false;

        return $this;
    }

    /** @brief  Remove the identity map entry for this instance.
     *
     *  @return $this for a fluent interface.
     */
    public function unsetIdentity()
    {
        $this->getMapper()->unsetIdentity( $this );

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
             *      (.*Model)_<Class> => (.*Model)_Mapper_<Class>
             */
            $mapper = preg_replace('/(.*?Model)_(.*?)/',
                                    '$1_Mapper_$2', get_class($this));

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
             *      (.*Model)_<Class> => (.*Model)_Filter_<Class>
             */
            $filter = preg_replace('/(.*?Model)_(.*?)/',
                                   '$1_Filter_$2', get_class($this));
        }

        /* Invoke the filterFactory.  If 'filter' is an incoming Filter 
         * instance, this will ensure that we have a cached version.  
         * Otherwise, look in the cache for an existing instance, if not found, 
         * create and cache a new Filter instance.
         */
        $this->_filter = self::filterFactory($filter);

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

        return $this;
    }

    /** @brief  Retrieve the 'isValid' indicator
     *
     *  Should we name this getIsValid() instead??
     */
    public function isValid()
    {
        if ($this->_isValid !== true)
        {
            // Attempt to validate the data of this Model.
            $filter = $this->getFilter();
            if ( is_object($filter) )
            {
                $filter->setData( $this->_data );

                $this->_isValid = $filter->isValid();
            }
        }

        return $this->_isValid;
    }

    /** @brief  Generate a string representation of this record.
     *  @param  skipValidation  Skip validation of each field [false]?
     *
     *  @return A string.
     */
    public function debugDump($skipValidation = false)
    {
        $str = get_class($this) .": is "
             .      ($this->isBacked() ? '' : 'NOT '). 'backed, '
             .      ($this->isValid()  ? '' : 'NOT '). 'valid '
             .      "[\n";

        foreach ($this->_data as  $key => $val)
        {
            $type = gettype($val);
            if ($type === 'object')
                $type = get_class($val);
            else if ($type === 'boolean')
                $val = ($val ? 'true' : 'false');

            $str .= sprintf (" %-15s == %-15s %s [ %s ]\n",
                             $key, $type,
                             ($this->_valid[$key] === false
                                ? "!" : " "),
                             $val);
        }

        $str .= "\n];";

        return $str;
    }

    /*************************************************************************
     * Generic serialization operations.
     *
     */

    /** @brief  Save this instancne.
     *
     *  @return The (updated) instance.
     */
    public function save()
    {
        return $this->getMapper()->save( $this );
    }

    /** @brief  Delete this instance.
     *
     *  @return void
     */
    public function delete()
    {
        return $this->getMapper()->delete( $this );
    }

    /*********************************************************************
     * Abstract methods
     *
     */

    /** @brief  Retrieve the unique identifier for this instance.  This MAY 
     *          return an array of identifiers as key/value pairs.
     *
     *  This MUST return null if the model is not currently backed.
     *
     *  @return The unique identifier.
     */
    abstract public function getId();


    /*********************************************************************
     * Static methods
     *
     */

    /** @brief  Given a Filter Class name, retrieve the associated Filter 
     *          instance.
     *  @param  filter   The Filter Class name
     *                  (optionally, a new Filter instance to ensure is in
     *                   our instance cache).
     *
     *  @return The Filter instance.
     */
    public static function filterFactory($filter)
    {
        if (is_string($filter))
        {
            // See if we have a Filter instance with this name in our cache
            $filterName = $filter;

            /*
            Connexions::log("Connexions_Model::filterFactory( %s ): "
                            . "set by name...",
                            $filterName);
            // */

            if ( isset(self::$_instCache[ $filterName ]))
            {
                // YES - use the existing instance
                $filter = self::$_instCache[ $filterName ];
            }
            else
            {
                // NO - create a new instance
                try
                {
                    @Zend_Loader_Autoloader::autoload($filterName);
                    $filter  = new $filterName();

                    // /*
                    Connexions::log("Connexions_Model::filterFactory( %s ): "
                                    . "filter loaded",
                                    $filterName);
                    // */
                }
                catch (Exception $e)
                {
                    // /*
                    Connexions::log("Connexions_Model::filterFactory( %s ): "
                                    . "CANNOT load filter",
                                    $filterName);
                    // */

                    // Return self::NO_INSTANCE
                    $filter = self::NO_INSTANCE;
                }
            }
        }
        else
        {
            $filterName = get_class($filter);
        }

        if (! isset(self::$_instCache[ $filterName ]))
        {
            self::$_instCache[ $filterName ] = $filter;

            /*
            Connexions::log("Connexions_Model::filterFactory( %s ): "
                            . "cache this Filter instance",
                            $filterName);
            // */
        }

        return $filter;
    }
}

