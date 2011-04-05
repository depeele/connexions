<?php
/** @file
 *
 *  An abstract, Connexions View helper.
 *
 */
abstract class View_Helper_Abstract
                            extends Zend_View_Helper_Abstract
{
    /* An array of key/value 'defaults' should be contained in any concreted
     * class
     */
    protected   $_defaults      = array();

    /** @brief  Set/Get-able parameters -- initialized from self::$defaults in
     *          __construct().
     */
    protected   $_params        = array();

    /** @brief  Construct a new Pagination helper.
     *  @param  config  A configuration array (see populate());
     */
    public function __construct(array $config = array())
    {
        // Include defaults for any option that isn't directly set
        foreach ($this->_defaults as $key => $value)
        {
            if (! isset($config[$key]))
            {
                $config[$key] = $value;
            }
        }

        $this->populate($config);

        return $this;
    }

    /** @brief  Given an array of configuration data, populate the parameter of
     *          this instance.
     *  @param  config  A key/value configuration array.
     *                                          values;
     *
     *  @return $this for a fluent interface.
     */
    public function populate(array $config)
    {
        foreach ($config as $key => $value)
        {
            $this->__set($key, $value);
        }

        return $this;
    }

    /** @brief  Member Setter - checks if the class has a 'set' method for the
     *          given key.  If so, invoke the method, otherwise directly set
     *          the '_params[$key]' value.
     *  @param  key     The key to be set;
     *  @param  value   The value to assign the named key;
     *
     */
    public function __set($key, $value)
    {
        /*
        Connexions::log("View_Helper_Abstract::__set(%s, %s)",
                        $key, Connexions::varExport($value));
        // */

        $method = 'set'. ucfirst($key);
        if (method_exists($this, $method))
        {
            $this->{$method}($value);
        }
        else
        {
            $this->_params[$key] = $value;
        }
    }

    /** @brief  Member Getter - checks if the class has a 'get' method for the
     *          given key.  If so, invoke the method, otherwise retrieve the
     *          value directly from '_params[$key]'.
     *  @param  key     The key to be set;
     *
     *  @return The value of the member (null if not set).
     */
    public function __get($key)
    {
        /*
        Connexions::log("View_Helper_Abstract::__get(%s)",
                        $key);
        // */

        $method = 'get'. ucfirst($key);
        if (method_exists($this, $method))
        {
            $val = $this->{$method}();
        }
        else
        {
            $val = (isset($this->_params[$key])
                        ? $this->_params[$key]
                        : null);
        }

        return $val;
    }
}

