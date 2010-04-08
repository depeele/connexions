<?php
/** @file
 *
 *  The base class for Connexions Database Table Model that supports instance
 *  caching.
 *
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
     *  @param  id          The record identifier.
     *  @param  db          An optional database instance (Zend_Db_Abstract).
     *  @param  className   The name of the concrete sub-class.
     *
     *  @return An instance, possibly from our instance cache.
     */
    public static function find($id, $db = null, $className = null)
    {
        // PHP < 5.3, comment out this test, requiring callers to supply ALL
        //            parameters.
        if ($className === null)
            $className = get_called_class();

        /* PHP < 5.3:
         *  $instanceId = call_user_func(array($className, '_instanceId'),
         *                               $id);
         */
        $instanceId = $className::_instanceId($id);

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
        $instance = parent::find($id, $db, $className);
        if ($instance instanceof $className)
        {
            if ($instanceId !== null)
            {
                /* Cache this new instance using the instanceId generated using
                 * the incoming 'id'
                 */
                self::$_cache[$instanceId] =& $instance;
            }

            /* PHP < 5.3:
             *  $newInstId = call_user_func(array($className, '_instanceId'),
             *                              $instance->_record);
             */
            $newInstId = $className::_instanceId($instance->_record);

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
