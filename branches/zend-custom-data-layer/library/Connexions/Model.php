<?php
/** @file
 *
 *  The abstract base class for Connexions Domain Models.
 *
 */
abstract class Connexions_Model
{
    protected   $_mapper    = null;
    protected   $_data      = array();  // The data of this model instance

    /*************************************************************************/

    /** @brief  Create a new instance.
     *  @param  config  Model configuration / creation data.
     *
     */
    public function __construct(array $config)
    {
        if (is_string($this->_mapper))
            $this->_mapper = new $this->_mapper();

        $this->_data = $config;
    }

    /** @brief  Get a value of the given field.
     *  @param  name    The field name.
     *
     *  @return The field value (or null if invalid field).
     */
    public function __get($name)
    {
        return (isset($this->_data[$name])
                    ? $this->_dtaa[$name]
                    : null);
    }

    /** @brief  Set the value of the given field.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  @return $this for a fluent interface.
     */
    public function __set($name, $value)
    {
        /*
        if (isset($this->_data[$name]))
            $this->_data[$name] = $value;
        // */
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

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        return (String)($this->getId());
    }

    /** @brief  Return an array version of this instance.
     *
     *  @return An array.
     */
    public function toArray()
    {
        return $this->_data;
    }

    /** @brief  Generate a string representation of this record.
     *  @param  skipValidation  Skip validation of each field [false]?
     *
     *  @return A string.
     */
    public function debugDump($skipValidation = false)
    {
        $str = get_class($this) .": [\n";

        foreach ($this->_data as  $key => $val)
        {
            $type = gettype($val);
            if ($type === 'object')
                $type = get_class($val);
            else if ($type === 'boolean')
                $val = ($val ? 'true' : 'false');

            $str .= sprintf (" %-15s == %-15s[ %s ]\n",
                             $key, $type, $val);
        }

        $str .= "\n];";

        return $str;
    }

    /** @brief  Retrieve the data mapper for this model.
     *
     *  @return A Connexions_Model_Mapper instance
     */
    public function getMapper()
    {
        if ($this->_mapper !== null)
            return $this->_mapper;

        $modelClass  = get_class($this);
        $mapperClass = $modelClass .'Mapper';

    }

    /*********************************************************************
     * Abstract methods
     *
     */

    /** @brief  Retrieve the unique identifier for this instance.
     *
     *  @return The unique identifier.
     */
    abstract public function getId();
}
