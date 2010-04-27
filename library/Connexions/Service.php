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

    const   ORDER_ASC       = 'ASC';
    const   ORDER_DESC      = 'DESC';

    /** @brief  Create a new, unbacked Domain Model instance.
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
    public function create(array $data)
    {
        $modelName = $this->_getModelName();    //_modelName;

        // Unset any special parameters
        if (is_array($data['data']))
            $data = $data['data'];

        unset($data['mapper']);
        unset($data['filtier']);
        unset($data['isBacked']);
        unset($data['isValid']);

        return new $modelName( $data );
    }

    /** @brief  Retrieve a single, existing Domain Model instance.
     *  @param  criteria    An array of name/value pairs that represent the
     *                      desired properties of the target Domain Model.  All
     *                      'name's MUST be valid for the target Domain Model.
     *
     *  @return A new Connexions_Model instance.
     */
    public function retrieve($criteria = array())
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
     *                      Connexions_Service::ORDER_* constant.  If an order
     *                      is omitted, Connexions_Service::ORDER_ASC will be
     *                      used [ no specified order ];
     *  @param  count       The maximum number of items from the full set of
     *                      matching items that should be returned
     *                      [ null == all ];
     *  @param  offset      The starting offset in the full set of matching
     *                      items [ null == 0 ].
     *
     *  @return A new Connexions_Model_Set.
     */
    public function retrieveSet($criteria  = array(),
                                $order     = null,
                                $count     = null,
                                $offset    = null)
    {
        if ($order !== null)
            $order = (array)$order;

        if (is_array($order))
        {
            // Ensure that we have all nave/direction pairs
            $newOrder = array();
            foreach ($newOrder as $name => $direction)
            {
                if (is_int($name))
                {
                    $name      = $direction;
                    $direction = self::ORDER_ASC;
                }
                else
                {
                    if ($direction !== self::ORDER_DESC)
                        $direction = self::ORDER_ASC;
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
     *                      Connexions_Service::ORDER_* constant.  If an order
     *                      is omitted, Connexions_Service::ORDER_ASC will be
     *                      used [ no specified order ];
     *
     *  @return A new Connexions_Model_Set.
     */
    public function retrievePaginated($criteria  = array(),
                                      $order     = null)
    {
        $set = $this->_getMapper()->fetch( $criteria, $order );
        return new Zend_Paginator( $set );
    }
                                      
    /** @brief  Initiate an update of the provided Domain Model instance.
     *  @param  model   The Domain Model instance to update.
     *
     *  Note: For simple Domain Models, this can also be accomplished
     *        directly via the Domain Model (e.g. $model->save() ).
     *
     *  @return The updated Domain Model instance.
     */
    public function update(Connexions_Model $model)
    {
        return $model->save();
    }

    /** @brief  Initiate the deletion of the provided Domain Model instance.
     *  @param  model   The Domain Model instance to delete.
     *
     *  Note: For simple Domain Models, this can also be accomplished
     *        directly via the Domain Model (e.g. $model->delete() ).
     *
     *  @return void
     */
    public function delete(Connexions_Model $model)
    {
        $model->delete();
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
     *
     *  @return The Connexions_Model_Mapper instance.
     */
    protected function _getMapper()
    {
        if ( ! $this->_mapper instanceof Connexions_Model_Mapper )
        {
            $mapperName = $this->_mapper;
            if (empty($mapperName))
            {
                /* Use the model name to construct a Model Mapper
                 * class name:
                 *      Model_<Class> => Model_Mapper_<Class>
                 */
                $mapperName = str_replace('Model_', 'Model_Mapper_',
                                          $this->_getModelName());
            }

            $this->_mapper = Connexions_Model_Mapper::factory( $mapperName );

            /*
            Connexions::log("Connexions_Service::_getMapper(): "
                            .   "name[ %s ], mapper[ %s ]",
                            $mapperName, get_class($this->_mapper));
            // */
        }

        return $this->_mapper;
    }

    /*********************************************************************
     * Static methods
     *
     */

    /** @brief  Given a Service Class name, retrieve the associated Service
     *          instance.
     *  @param  service     The Service Class instance, Service Class name, or
     *                      Domain Model name.
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
            // See if we have a Service instance with this name in our cache
            if (is_object($service))
                $serviceName = get_class($service);
            else
                $serviceName = $service;

            // Allow the incoming name to identify the target Domain Model
            if (strpos($serviceName, 'Model_') !== false)
            {
                $serviceName = str_replace('Model_', 'Service_', $serviceName);
            }


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
