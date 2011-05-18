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
    protected   $_modelName         = null;
    protected   $_mapper            = null;

    /** @brief  Any default ordering that should be be merged into a specified 
     *          order.
     *
     *  Merged via _extraOrder(), this should be an associative array of
     *  field/sort_direction pairs.
     */
    protected   $_defaultOrdering   = array();

    /** @brief  Returned from a factory if no instance can be located or 
     *          generated.
     */
    const       NO_INSTANCE             = -1;

    // A cache of Data Accessor instances, by class name
    static protected    $_instCache     = array();

    const   SORT_DIR_ASC    = 'ASC';
    const   SORT_DIR_DESC   = 'DESC';

    public function __construct()
    {
        // Resolve our model name
        if (empty($this->_modelName))
        {
            /* Use the class name of this instance to construct a Model
             * class name:
             *      Service_<Class> => Model_<Class>
             */
            $this->_modelName = str_replace('Service_', 'Model_',
                                            get_class($this));
        }

        // Resolve our mapper
        $this->_mapper = $this->_getMapper();
    }

    /** @brief  Find an existing Domain Model instance, or Create a new Domain
     *          Model instance, initializing it with the provided
     *          identification data.
     *  @param  id      Identification value(s) (string, integer, array).
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value pairs.
     *
     *  @return A new Domain Model instance.
     *          Note: If the caller wishes this new instance to persist, they
     *                must invoke either:
     *                    $model = $model->save()
     *                or
     *                    $model = $this->update($model)
     */
    public function get($id)
    {
        $res = $this->_mapper->getModel( $this->_mapper->normalizeId($id) );

        /*
        Connexions::log("Connexions_Service[%s]::get( %s ): [ %s ]",
                        get_class($tis),
                        Connexions::varExport($id),
                        ($res ? $res->debugDump()
                              : 'null'));
        // */
        return $res;
    }

    /** @brief  Retrieve a single, existing Domain Model instance.
     *  @param  id      Identification value(s) (string, integer, array).
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value pairs.
     *
     *  @return A new Connexions_Model instance.
     */
    public function find($id)
    {
        $normId = $this->_mapper->normalizeId($id);

        /*
        Connexions::log("Connexions_Service[%s]::find( %s ): "
                        .   "normalized[ %s ]",
                        get_class($this),
                        Connexions::varExport($id),
                        Connexions::varExport($normId));
        // */

        return $this->_mapper->find( $normId );
    }

    /** @brief  Retrieve a set of Domain Model instances.
     *  @param  id      Identification value(s), null to retrieve all.
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value(s) pairs.
     *  @param  order   An array of name/direction pairs representing the
     *                  desired sorting order.  The 'name's MUST be valid for
     *                  the target Domain Model and the directions a
     *                  Connexions_Service::SORT_DIR_* constant.  If an order
     *                  is omitted, Connexions_Service::SORT_DIR_ASC will be
     *                  used [ no specified order ];
     *  @param  count   The maximum number of items from the full set of
     *                  matching items that should be returned
     *                  [ null == all ];
     *  @param  offset  The starting offset in the full set of matching items
     *                  [ null == 0 ].
     *
     *  @return A new Connexions_Model_Set.
     */
    public function fetch($id       = null,
                          $order    = null,
                          $count    = null,
                          $offset   = null)
    {
        $ids     = $this->_csList2array($id);
        $normIds = $this->_mapper->normalizeIds($ids);
        $order   = $this->_csOrder2array($order);

        /*
        Connexions::log("Connexions_Service::fetch() "
                        . "id[ %s ], ids[ %s ], normIds[ %s ]",
                        Connexions::varExport($id),
                        Connexions::varExport($ids),
                        Connexions::varExport($normIds));
        // */

        return $this->_mapper->fetch( $normIds,
                                      $order,
                                      $count,
                                      $offset );

    }

    /** @brief  Retrieve a paginated set of Domain Model instances.
     *  @param  id      An array of 'property/value' pairs identifying the
     *                  desired model(s), or null to retrieve all.
     *  @param  order   An array of name/direction pairs representing the
     *                  desired sorting order.  The 'name's MUST be valid for
     *                  the target Domain Model and the directions a
     *                  Connexions_Service::SORT_DIR_* constant.  If an order
     *                  is omitted, Connexions_Service::SORT_DIR_ASC will be
     *                  used [ no specified order ];
     *
     *  @return A new Connexions_Model_Set.
     */
    public function fetchPaginated($id      = null,
                                   $order   = null)
    {
        $ids     = $this->_csList2array($id);
        $normIds = $this->_mapper->normalizeIds($ids);
        $order   = $this->_csOrder2array($order);

        $set = $this->_mapper->fetch( $normIds, $order );
        return new Zend_Paginator( $set->getPaginatorAdapter() );
    }
                                      
    /** @brief  Create an empty set.
     *
     *  @return Connexions_Model_Set
     */
    public function makeEmptySet()
    {
        return $this->_mapper->makeEmptySet();
    }

    /** @brief  Convert a comma-separated list of item identifiers to a
     *          Connexions_Model_Set instance.
     *  @param  csList  The comma-separated list of identifiers
     *                  (MUST ALL target the same model field);
     *  @param  order   An ordering string/array.
     *  @param  create  Should any non-existing items be created? [ false ];
     *
     *  @return Connexions_Model_Set
     */
    public function csList2set($csList, $order = null, $create = false)
    {
        if (is_object($csList))
        {
            /* Handle the case where we're passed a Connexions_Model_Set or
             * Connexions_Model
             */
            if ($csList instanceof Connexions_Model_Set)
                return $csList;

            if ($csList instanceof Connexions_Model)
            {
                /* Create an empty set and add this Connexions_Model instance
                 * as its only member.
                 */
                $mapper = $csList->getMapper();
                $set    = $mapper->makeEmptySet();
                $set->setResults(array($csList));

                return $set;
            }
        }


        // Parse the comma-separated-list into a simple array.
        $ids = $this->_csList2array($csList);
        if ( (! is_array($ids)) || empty($ids) )
        {
            $normIds = $ids;
            $set     = $this->_mapper->makeEmptySet();
        }
        else
        {
            $normIds = $this->_mapper->normalizeIds($ids);
            $order   = $this->_csOrder2array($order);

            /*
            Connexions::log("Connexions_Service[%s]::csList2set(): "
                            . "csList[ %s ]",
                            get_class($this),
                            Connexions::varExport($csList));
            Connexions::log("Connexions_Service[%s]::csList2set(): "
                            . "ids[ %s ]",
                            get_class($this),
                            Connexions::varExport($ids));
            Connexions::log("Connexions_Service[%s]::csList2set(): "
                            . "normIds[ %s ]",
                            get_class($this),
                            Connexions::varExport($normIds));
            Connexions::log("Connexions_Service[%s]::csList2set(): "
                            . "order[ %s ]",
                            get_class($this),
                            Connexions::varExport($order));
            // */

            $set     = $this->_mapper->fetch($normIds, $order);
        }
        $set->setSource($csList);


        /* Now, if the size of 'set' is less than the size of 'ids', locate the
         * missing entries and create a representative model instance for each.
         *
         * If 'create' is true, ensure that the representative model instances
         * have been saved.
         */
        if ( count($set) < count($ids) )
        {
            /*
            Connexions::log("Connexions_Service[%s]::csList2set(): "
                                . "%d item(s) seem to be missing...",
                            get_class($this),
                            count($ids) - count($set));
            Connexions::log("Connexions_Service[%s]::csList2set(): "
                                . "ids[ %s ], normIds[ %s ]",
                            get_class($this),
                            Connexions::varExport($ids),
                            Connexions::varExport($normIds));
            Connexions::log("Connexions_Service[%s]::csList2set(): "
                                . "set[ %s ]",
                            get_class($this),
                            Connexions::varExport($set));
            // */

            // Ensure we have entries for all items in 'normIds'
            foreach( $normIds as $field => $vals )
            {
                $vals = (is_array($vals) ? $vals : array( $vals ));

                /*
                Connexions::log("Connexions_Service[%s]::csList2set(): "
                                    . "field[ %s ], values[ %s ]",
                                get_class($this),
                                $field,
                                Connexions::varExport($vals));
                // */

                foreach( $vals as $id)
                {
                    $data = array();
                    $data[$field] = $id;

                    $model = $this->_mapper->getModel( $data );

                    /*
                    Connexions::log("Connexions_Service[%s]::csList2set(): "
                                        . "getModel for item '%s': %sbacked",
                                    get_class($this),
                                    $item,
                                    ($model->isBacked() ? '' : 'NOT '));
                    // */

                    // See if the normalized item already exists in the set.
                    if (! $set->contains($model))
                    {
                        /* The normalized item does NOT exist in the set.
                         *
                         * If we've been asked to create, save this model now.
                         *
                         * Regardless, append it to the set.
                         */
                        if (($create === true) && (! $model->isBacked()) )
                        {
                            $model = $model->save();
                        }
                        $set->append( $model );

                        /*
                        Connexions::log("Connexions_Service[%s]::csList2set(): "
                                            . "append[ %s ]",
                                        get_class($this),
                                        $model->debugDump());
                        // */
                    }
                }
            }

            /*
            Connexions::log("Connexions_Service[%s]::csList2set(): "
                                . "ids[ %s ] == final set[ %s ]",
                            get_class($this),
                            Connexions::varExport($ids),
                            Connexions::varExport($set));
            // */

        }

        /*
        Connexions::log("Connexions_Service[%s]::csList2set( %s ): "
                        .   "[ %s ] == [ %s ] %s:%s",
                        get_class($this),
                        $csList,
                        Connexions::varExport($ids),
                        $set,
                        gettype($set),
                        (is_object($set)
                            ? get_class($set)
                            : ''));
        // */

        return $set;
    }

    /*********************************************************************
     * Protected methods
     *
     */

    /** @brief  Convert a comma-separated string into an array.
     *  @param  str     The comma-separated string.
     *
     *  @return A matching array.
     */
    protected function _csList2array($str)
    {
        if (! is_string($str))
        {
            if ($str === null)
            {
                return null;
            }

            if (is_object($str))
            {
                if (method_exists($str, 'getIds'))
                    return $str->getIds();
                else if (method_exists($str, 'toArray'))
                    return $str->toArray();
            }

            return (array)$str;
        }

        // Remove any empty items (e.g. ",,")
        $str  = preg_replace('/,\s*,/', ',', $str);

        // Include ',' in the trim
        $str  = trim($str, " \t\n\r\0\x0B,");

        // Split out the items
        $list = (empty($str)
                    ? array()
                    : preg_split('/\s*,\s*/', $str));

        /*
        Connexions::log("Connexions_Service::_csList2array( %s ): "
                        . "[ %s ]",
                        $str,
                        Connexions::varExport($list));
        // */

        return $list;
    }

    /** @brief  Convert a comma-separated string or order criterian into an
     *          order array acceptable to Connexions_Service::fetch().
     *  @param  order       The order value (comma-separated string or array).
     *  @param  noExtras    If true, do NOT include extra, default ordering
     *                      [ false ];
     *
     *  @return A matching array.
     */
    protected function _csOrder2array($order, $noExtras = false)
    {
        if (! is_array($order))
        {
            // Convert any comma-separated string into an array.
            $orderAr = $this->_csList2array($order);
            if (! is_array($orderAr))
                return $order;
        }
        else
        {
            $orderAr = $order;
        }

        /*
        Connexions::log("Connexions_Service::_csOrder2array( %s ): "
                        . "array[ %s ]",
                        $order,
                        Connexions::varExport($orderAr));
        // */


        // Ensure that we have all name/direction pairs
        $newOrder = array();
        foreach ($orderAr as $name => $dir)
        {
            if (is_int($name))
            {
                list($name, $dir) = preg_split('/\s+/', $dir, 2);
            }
            $dir = strtoupper($dir);

            if ($dir !== self::SORT_DIR_DESC)
                $dir = self::SORT_DIR_ASC;

            array_push($newOrder, $name .' '. $dir);
        }

        if ((! empty($newOrder)) && ($noExtras !== true) )
        {
            $newOrder = $this->_extraOrder($newOrder);
        }

        /*
        Connexions::log("Connexions_Service::_csOrder2array( %s ): "
                        . "order[ %s ]",
                        $order,
                        Connexions::varExport($newOrder));
        // */

        return $newOrder;
    }

    /** @brief  Retrieve an instance of the named filter.
     *  @param  filterName  The specific filter to retrieve
     *                      (MAY be the name of the Model handled by the
     *                       desired filter, null == _modelName).
     *
     *  @return The Connexions_Model_Filter instance.
     */
    protected function _getFilter($filterName = null)
    {
        if ($filterName === null)   $filterName = $this->_modelName;

        // Locate a specific filter
        $name = ( (strpos($filterName, 'Model_Filter') === false)
                    ? str_replace('Model_', 'Model_Filter_', $filterName)
                    : $filterName );

        $filter = Connexions_Model_Filter::factory( $name );

        /*
        Connexions::log("Connexions_Service::_getFilter(): "
                        .   "name[ %s ], filter[ %s ]",
                        $name, get_class($filter));
        // */

        return $filter;
    }

    /** @brief  Retrieve an instance of the named mapper.
     *  @param  mapperName  The specific mapper to retrieve
     *                      (MAY be the name of the Model handled by the
     *                       desired mapper, null == _modelName).
     *
     *  @return The Connexions_Model_Mapper instance.
     */
    protected function _getMapper($mapperName = null)
    {
        if ($mapperName === null)
        {
            if ($this->_mapper !== null)    return $this->_mapper;

            $mapperName = $this->_modelName;
        }

        // Locate a specific mapper
        $name = ( (strpos($mapperName, 'Model_Mapper_') === false)
                    ? str_replace('Model_', 'Model_Mapper_', $mapperName)
                    : $mapperName );

        $mapper = Connexions_Model_Mapper::factory( $name );

        /*
        Connexions::log("Connexions_Service::_getMapper(): "
                        .   "name[ %s ], mapper[ %s ]",
                        $name, get_class($mapper));
        // */

        return $mapper;
    }

    /** @brief  Given an ordering, include additional ordering criteria that
     *          will help make result sets consistent.
     *  @param  order   The incoming order criteria.
     *
     *  @return A new order criteria array.
     */
    protected function _extraOrder($order)
    {
        if (! isset($this->_defaultOrdering))
            return $order;

        /* Include any of the default ordering values that haven't been
         * overridden.
         */
        $newOrder = (is_array($order)
                        ? $order
                        : (is_string($order)
                            ? array($order)
                            : array()));

        /* First, split apart the current orderings into 'field' and 
         * 'direction'
         */
        $orderMap = array();
        foreach ($newOrder as $ord)
        {
            list($by, $dir) = preg_split('/\s+/', $ord);
            $orderMap[$by] = $dir;
        }

        /* Now, walk through '_defaultOrdering' and add any that haven't been 
         * overridden.
         */
        foreach ($this->_defaultOrdering as $by => $dir)
        {
            if (! isset($orderMap[ $by ]))
            {
                array_push($newOrder, $by .' '. $dir);
            }

        }

        return $newOrder;
    }

    /** @brief  Retrieve the currently identified user.
     *
     *  @return A Model_User instance or null if none.
     */
    protected function _curUser()
    {
        $user = Connexions::getUser();
        if ($user === false)
            $user = null;

        return $user;
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
                /*
                Connexions::log("Connexions_Service::factory(): "
                                . "autoload '%s'",
                                $serviceName);
                // */

                // NO - create a new instance
                try
                {
                    @Zend_Loader_Autoloader::autoload($serviceName);
                    $service  = new $serviceName();

                    /*
                    Connexions::log("Connexions_Service::factory(): "
                                    . "service '%s' autoloaded...",
                                    $serviceName);
                    // */
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
