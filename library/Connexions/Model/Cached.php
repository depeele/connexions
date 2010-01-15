<?php
/** @file
 *
 *  The base class for Connexions Database Table Model that supports instance
 *  caching.
 *
 *  Requires:
 *      LATE_STATIC_BINDING     to be defined (Connexions.php)
 */
abstract class Connexions_Model_Cached extends Connexions_Model
{
    // Model Instance cache
    protected static $_cache    = array();

    /*************************************************************************
     * Abstract static methods
     *
     */

    /** @brief  Given a record identifier, generate an unique instance
     *          identifier.
     *  @param  id      The record identifier.
     *
     *  @return A unique instance identifier string.
     */
    abstract protected static function _instanceId($id);
    /*************************************************************************/


    /*************************************************************************
     * Static methods
     *
     */

    /** @brief  Locate the record for the identified user and return a new User
     *          instance.
     *  @param  className   The name of the concrete sub-class.
     *  @param  id          The record identifier.
     *  @param  db          An optional database instance (Zend_Db_Abstract).
     *
     *  @return An instance, possibly from our instance cache.
     */
    public static function find($className, $id, $db = null)
    {
        $instanceId = (LATE_STATIC_BINDING
                        ? $className::_instanceId($id)
                        : call_user_func(array($className, '_instanceId'),
                                         $id));

        /*
        Connexions::log("Connexions_Model_Cached::find: "
                          . "className[ {$className} ], "
                          . "id[ ". print_r($id, true) ." ], "
                          . "instanceId[ {$instanceId} ]");
        // */

        if ( ($instanceId !== null) && @isset(self::$_cache[$instanceId]))
        {
            // Return the cached instance
            return self::$_cache[$instanceId];
        }

        // Find/Create a new instance.
        $instance = parent::find($className, $id, $db);
        if ($instance instanceof $className)
        {
            if ($instanceId !== null)
            {
                /* Cache this new instance using the instanceId generated using
                 * the incoming 'id'
                 */
                self::$_cache[$instanceId] =& $instance;
            }

            $newInstId = (LATE_STATIC_BINDING
                            ? $className::_instanceId($instance->_record)
                            : call_user_func(array($className, '_instanceId'),
                                             $instance->_record));

            if ($newInstId !== $instanceId)
            {
                /* Cache this instance using the instanceId generated using the
                 * actual record data.
                 */
                self::$_cache[$newInstId] =& $instance;
            }
        }

        return $instance;
    }
}
