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
defined('APPLICATION_SERVICE_PATH')
    || define('APPLICATION_SERVICE_PATH',
              realpath(APPLICATION_PATH . '/services'));
defined('APPLICATION_VIEW_HELPER_PATH')
    || define('APPLICATION_VIEW_HELPER_PATH',
              realpath(APPLICATION_PATH . '/views/helpers'));

/** @brief  This is the primary autoloader for Connexions.  It is capable of
 *          handling the 'Zend_', 'ZendX_', 'Connexions_', 'Model_', and
 *          'Service_' namespaces.
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
                'View_'         => array('path' =>
                                            APPLICATION_VIEW_HELPER_PATH,
                                         'shift'=> 2),
                'Model_'        => array('path' => APPLICATION_MODEL_PATH,
                                         'shift'=> 1),
                'Service_'      => array('path' => APPLICATION_SERVICE_PATH,
                                         'shift'=> 1),
                /* Force the Zend_Loader to call us for any class that has
                 * no prefix -- we could use this to remove any prefix from our
                 *              model classes.
                 */
                ''              => array('path' => APPLICATION_PATH,
                                         'shift'=> 1),
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
            $filePath = APPLICATION_MODEL_PATH; //APPLICATION_LIBRARY_PATH;
        }
        else
        {
            $filePath = $mapInfo['path'];
            if ((@isset($mapInfo['shift'])) && $mapInfo['shift'])
            {
                // Shift off the first mapInfo['shift'] parts of classParts
                for ($idex = 0; $idex < $mapInfo['shift']; $idex++)
                {
                    array_shift($classParts);
                }
            }
        }

        $filePath .= $ds . implode($ds, $classParts) .'.php';

        $res = @include_once $filePath;

        if ( (! $res) ||
             ( (! class_exists($class)) && (! interface_exists($class)) ) )
        {
            $msg = sprintf("Connexions_Autoloader::autoload: class[ %s ], "
                            .   "filePath[ %s ] - FAILED: "
                            .   "res[ %s ], "
                            .   "class_exists[ %s ], "
                            .   "interface_exists[ %s ]",
                            $class, $filePath,
                            var_export($res, true),
                            (class_exists($class)     ? 'true' : 'false'),
                            (interface_exists($class) ? 'true' : 'false'));

            throw new Exception( $msg );
        }

        return true;
    }
}
