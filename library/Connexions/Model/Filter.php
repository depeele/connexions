<?php
/** @file
 *
 *  An abstract base class for Domain Model input filter/validators.
 *
 */
abstract class Connexions_Model_Filter extends Zend_Filter_Input
{
    /** @brief  Returned from a factory if no instance can be located or 
     *          generated.
     */
    const       NO_INSTANCE             = -1;

    // A cache of Data Accessor instances, by class name
    static protected    $_instCache     = array();


    /** @brief  Create a new User Domain Model Filter. */
    public function __construct(array $data = null)
    {
        $this->setOptions(array(
            self::ALLOW_EMPTY       => false,
            self::MISSING_MESSAGE   => "'%field%' is missing",
            self::NOT_EMPTY_MESSAGE => "'%field%' must not be empty",
        ));

        if ($data)
        {
            $this->setData($data);
        }
    }

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
    public static function factory($filter)
    {
        if ($filter instanceof Connexions_Filter)
        {
            $filterName = get_class($filter);
        }
        else if (is_string($filter))
        {
            // See if we have a Mapper instance with this name in our cache
            $filterName = $filter;
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

                    /*
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
            throw new Exception("Connexions_Model_Filter::factory(): "
                                . "requires a Connexions_Model_Filter "
                                . "instance or filter name string");
        }

        if (! isset(self::$_instCache[ $filterName ]))
        {
            self::$_instCache[ $filterName ] = $filter;

            /*
            Connexions::log("Connexions_Model_Filter::factory( %s ): "
                            . "cache this Filter instance",
                            $filterName);
            // */
        }

        return $filter;
    }
}

