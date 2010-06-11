<?php
/** @file
 *
 *  The abstract base class for a service that provides access to and
 *  operations on Connexions Domain Models and Model Sets.
 *
 *  This provides logical separation between the application and the Data
 *  Persistence Layer.  Users of a Service only have to deal with Domain Model
 *  abstractions.
 */
abstract class Connexions_Service
{
    protected   $_modelName = null;
    protected   $_mapper    = null;

    /** @brief  Returned from a factory if no instance can be located or 
     *          generated.
     */
    const       NO_INSTANCE             = -1;

    // A cache of Data Accessor instances, by class name
    static protected    $_instCache     = array();

    const   SORT_DIR_ASC    = 'ASC';
    const   SORT_DIR_DESC   = 'DESC';

    /** @brief  Find an existing Domain Model instance, or Create a new Domain
     *          Model instance, initializing it with the provided data.
     *  @param  data    An array of name/value pairs used to initialize the
     *                  Domain Model.  All 'name's MUST be valid for the target
     *                  Domain Model.
     *
     *  @return A new Domain Model instance.
     *          Note: If the caller wishes this new instance to persist, they
     *                must invoke either:
     *                    $model = $model->save()
     *                or
     *                    $model = $this->update($model)
     */
    public function get(array $data)
    {
        return $this->_getMapper()->getModel($data);
    }

    /** @brief  Retrieve a single, existing Domain Model instance.
     *  @param  criteria    An array of name/value pairs that represent the
     *                      desired properties of the target Domain Model.  All
     *                      'name's MUST be valid for the target Domain Model.
     *
     *  @return A new Connexions_Model instance.
     */
    public function find($criteria = array())
    {
        return $this->_getMapper()->find( $criteria );
    }

    /** @brief  Retrieve a set of Domain Model instances.
     *  @param  criteria    An array of name/value pairs that represent the
     *                      desired properties of the target Domain Model.  All
     *                      'name's MUST be valid for the target Domain Model;
     *  @param  order       An array of name/direction pairs representing the
     *                      desired sorting order.  The 'name's MUST be valid
     *                      for the target Domain Model and the directions a
     *                      Connexions_Service::SORT_DIR_* constant.  If an
     *                      order is omitted, Connexions_Service::SORT_DIR_ASC
     *                      will be used [ no specified order ];
     *  @param  count       The maximum number of items from the full set of
     *                      matching items that should be returned
     *                      [ null == all ];
     *  @param  offset      The starting offset in the full set of matching
     *                      items [ null == 0 ].
     *
     *  @return A new Connexions_Model_Set.
     */
    public function fetch($criteria  = null,
                          $order     = null,
                          $count     = null,
                          $offset    = null)
    {
        if ($order !== null)
            $order = (array)$order;

        if (is_array($order))
        {
            // Ensure that we have all name/direction pairs
            $newOrder = array();
            foreach ($newOrder as $name => $direction)
            {
                if (is_int($name))
                {
                    $name      = $direction;
                    $direction = self::SORT_DIR_ASC;
                }
                else
                {
                    if ($direction !== self::SORT_DIR_DESC)
                        $direction = self::SORT_DIR_ASC;
                }

                $newOrder[$name] = $direction;
            }

            $order = $newOrder;
        }

        return $this->_getMapper()->fetch( $criteria, $order,
                                           $count,    $offset );
    }

    /** @brief  Retrieve a paginated set of Domain Model instances.
     *  @param  criteria    An array of name/value pairs that represent the
     *                      desired properties of the target Domain Model.  All
     *                      'name's MUST be valid for the target Domain Model;
     *  @param  order       An array of name/direction pairs representing the
     *                      desired sorting order.  The 'name's MUST be valid
     *                      for the target Domain Model and the directions a
     *                      Connexions_Service::SORT_DIR_* constant.  If an
     *                      order is omitted, Connexions_Service::SORT_DIR_ASC
     *                      will be used [ no specified order ];
     *
     *  @return A new Connexions_Model_Set.
     */
    public function fetchPaginated($criteria  = array(),
                                   $order     = null)
    {
        $set = $this->_getMapper()->fetch( $criteria, $order );
        return new Zend_Paginator( $set->getPaginatorAdapter() );
    }
                                      
    /*********************************************************************
     * Protected methods
     *
     */

    protected function _getModelName()
    {
        if (empty($this->_modelName))
        {
            /* Use the class name of this instance to construct a Model
             * class name:
             *      Service_<Class> => Model_<Class>
             */
            $this->_modelName = str_replace('Service_', 'Model_',
                                            get_class($this));
        }

        return $this->_modelName;
    }

    /** @brief  Retrieve the mapper for this Service.
     *  @param  mapperName  The specific mapper to retrieve.  If not provided,
     *                      retrieve the mapper for THIS service [ null ];
     *
     *  @return The Connexions_Model_Mapper instance.
     */
    protected function _getMapper($mapperName = null)
    {
        if ( ($mapperName !== null) ||
             (! $this->_mapper instanceof Connexions_Model_Mapper ) )
        {
            if ($mapperName !== null)
            {
                // Locate a specific mapper
                if (strpos($mapperName, 'Model_Mapper_') === false)
                    $name = str_replace('Model_', 'Model_Mapper_', $mapperName);
                else
                    $name = $mapperName;
            }
            else
            {
                // Locate the mapper for THIS service.
                $name = $this->_mapper;
                if (empty($name))
                {
                    /* Use the model name to construct a Model Mapper
                     * class name:
                     *      Model_<Class> => Model_Mapper_<Class>
                     */
                    $name = str_replace('Model_', 'Model_Mapper_',
                                              $this->_getModelName());
                }
            }

            $mapper = Connexions_Model_Mapper::factory( $name );

            /*
            Connexions::log("Connexions_Service::_getMapper(): "
                            .   "name[ %s ], mapper[ %s ]",
                            $name, get_class($mapper));
            // */

            if ($mapperName === null)
                $this->_mapper = $mapper;
        }
        else
        {
            $mapper = $this->_mapper;
        }

        return $mapper;
    }

    /*********************************************************************
     * Static methods
     *
     */

    /** @brief  Given a Service Class name, retrieve the associated Service
     *          instance.
     *  @param  service     The Service Class instance, Service Class name,
     *                      Domain Model instance, or Domain Model name.
     *
     *  @return The Connexions_Service instance.
     */
    public static function factory($service)
    {
        if ($service instanceof Connexions_Service)
        {
            $serviceName = get_class($service);
        }
        else
        {
            if ($service instanceof Connexions_Model)
                $serviceName = get_class($service);
            else if (is_string($service))
                $serviceName = $service;
            else
            {
                throw new Exception("Connexions_Service::factory(): "
                                    . "requires a "
                                    . "Connexions_Service instance, "
                                    . "Connexions_Model instance, "
                                    . "or a "
                                    . "Service or Domain Model name string");
            }

            // Allow the incoming name to identify the target Domain Model
            if (strpos($serviceName, 'Model_') !== false)
            {
                $serviceName = str_replace('Model_', 'Service_', $serviceName);
            }


            // See if we have a Service instance with this name in our cache
            if ( isset(self::$_instCache[ $serviceName ]))
            {
                // YES - use the existing instance
                $service =& self::$_instCache[ $serviceName ];
            }
            else
            {
                // NO - create a new instance
                try
                {
                    @Zend_Loader_Autoloader::autoload($serviceName);
                    $service  = new $serviceName();
                }
                catch (Exception $e)
                {
                    // Simply return null
                    $service = self::NO_INSTANCE;

                    // /*
                    Connexions::log("Connexions_Service::factory: "
                                    . "CANNOT locate class '%s'",
                                    $serviceName);
                    // */
                }
            }
        }

        if (! isset(self::$_instCache[ $serviceName ]))
        {
            self::$_instCache[ $serviceName ] = $service;

            /*
            Connexions::log("Connexions_Service::factory( %s ): "
                            . "cache this Mapper instance",
                            $serviceName);
            // */
        }

        return $service;
    }
}
