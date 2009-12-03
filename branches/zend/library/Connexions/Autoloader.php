<?php
/** @file
 *
 *  Primary Connexions Autoloader.
 *
 */
require_once('Zend/Loader/Autoloader/Interface.php');

/** @brief  This is the primary autoloader for Connexions.  It is capable of
 *          handling the 'Zend_' and 'Connexions_' namespaces.
 *
 *
 *  This Autoloader is currently installed via:
 *      Bootstrap::_initAutoload()  - applications/Bootstrap.php
 */
class Connexions_Autoloader implements Zend_Loader_Autoloader_Interface
{
    // "Namespaces" that we can handle
    private static  $_namespaces    = array('Zend_',
                                            'Connexions_');

    /** @brief  Constructor
     *  @param  options     array | Zend_Config.
     *
     *  @return void
     */
    public function __construct(array $options = array())
    {
    }

    /** @brief  Return the set of supported namespaces.
     *
     *  @return An array of namespaces.
     */
    public static function getNamespaces()
    {
        return self::$_namespaces;
    }

    /** @brief  Attempt to autoload a class
     *  @param  class   The name of the class to load.
     *
     *  @return result of include operation, false if no match.
     */
    public function autoload($class)
    {
        $misses = 0;
        $path   = APPLICATION_PATH . DIRECTORY_SEPARATOR
                . 'library'        . DIRECTORY_SEPARATOR
                . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

        return include $path;
    }
}
