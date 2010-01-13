<?php
/** @file
 *
 *  Primary Connexions Autoloader.
 *
 */
require_once('Zend/Loader/Autoloader/Interface.php');

defined('APPLICATION_LIBRARY_PATH')
    || define('APPLICATION_LIBRARY_PATH',
              realpath(APPLICATION_PATH . '/../library'));
defined('APPLICATION_MODEL_PATH')
    || define('APPLICATION_MODEL_PATH',
              realpath(APPLICATION_PATH . '/models'));
defined('APPLICATION_VIEW_HELPER_PATH')
    || define('APPLICATION_VIEW_HELPER_PATH',
              realpath(APPLICATION_PATH . '/views/helpers'));

/** @brief  This is the primary autoloader for Connexions.  It is capable of
 *          handling the 'Zend_', 'ZendX_', 'Connexions_', and 'Model_'
 *          namespaces.
 *
 *
 *  This Autoloader is currently installed via:
 *      Bootstrap::_initAutoload()  - applications/Bootstrap.php
 */
class Connexions_Autoloader implements Zend_Loader_Autoloader_Interface
{
    // "Namespaces" that we can handle
    private static  $_loaderMap     = array(
                'Zend_'         => array('path' => APPLICATION_LIBRARY_PATH),
                'ZendX_'        => array('path' => APPLICATION_LIBRARY_PATH),
                'Connexions_'   => array('path' => APPLICATION_LIBRARY_PATH),
                'Model_'        => array('path' => APPLICATION_MODEL_PATH,
                                         'shift'=> 1)
    );

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
        return array_keys(self::$_loaderMap);
    }

    /** @brief  Attempt to autoload a class
     *  @param  class   The name of the class to load.
     *
     *  @return result of include operation, false if no match.
     */
    public function autoload($class)
    {
        $ds         = DIRECTORY_SEPARATOR;
        $classParts = explode('_', $class);
        $mapInfo    = self::$_loaderMap[$classParts[0] .'_'];

        if (! $mapInfo)
        {
            // Default to APPLICATION_LIBRARY_PATH
            $filePath = APPLICATION_LIBRARY_PATH;
        }
        else
        {
            $filePath = $mapInfo['path'];
            if ($mapInfo['shift'])
            {
                // Shift off the first mapInfo['shift'] parts of classParts
                for ($idex = 0; $idex < $mapInfo['shift']; $idex++)
                {
                    array_shift($classParts);
                }
            }
        }

        $filePath .= $ds . implode($ds, $classParts) .'.php';

        return include $filePath;
    }
}
