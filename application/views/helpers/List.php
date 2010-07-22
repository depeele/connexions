<?php
/** @file
 *
 *  View helper to render a paginated list.
 */
abstract class View_Helper_List extends Zend_View_Helper_Abstract
{
    static public   $perPageChoices = array(10, 25, 50, 100);

    static public   $defaults       = array(
        'namespace'                 => '',
        'viewer'                    => null,

        // The name of the primary set of items to present
        'listName'                  => 'items',

        // Pagination values
        'page'                      => 1,
        'perPage'                   => 50,

        // Valid sort values are defined by the concrete instance
        'sortBy'                    => null,
        'sortOrder'                 => Connexions_Service::SORT_DIR_ASC,

        // For sort conditions that are numeric, the default grouping.
        'numericGrouping'           => 10,
    );

    static public   $orderTitles    = array(
        Connexions_Service::SORT_DIR_ASC    => 'Ascending',
        Connexions_Service::SORT_DIR_DESC   => 'Descending'
    );

    /** @brief  Set-able parameters -- initialized from self::$defaults in
     *          __construct().
     */
    protected   $_params            = array();

    /** @brief  Construct a new Bookmarks helper.
     *  @param  config  A configuration array (see populate());
     */
    public function __construct(array $config = array())
    {
        foreach (self::$defaults as $key => $value)
        {
            $this->_params[$key] = $value;
        }

        if (! empty($config))
            $this->populate($config);

        return $this;
    }

    /** @brief  Given an array of configuration data, populate the parameter of
     *          this instance.
     *  @param  config  A configuration array that may include:
     *                      - namespace     The namespace to use for all
     *                                      cookies/parameters/settings
     *                                      [ '' ];
     *                      - viewer        A Model_User instance identifying
     *                                      the current viewer;
     *                      - items         A Connexions_Model_Set instance
     *                                      representing the primary set of
     *                                      items being presented [ null ];
     *                      - page          The page to present [ 1 ];
     *                      - perPage       The number of items per page
     *                                      [ 50 ];
     *                      - sortBy        The field to sort by
     *                                      (extablished by the concerte
     *                                       classes);
     *                      - sortOrder     The sort order
     *                                      [Connexions_Service::SORT_DIR_DESC]
     *                      - numericGrouping
     *                                      When sorting numerically, the
     *                                      number of items per group [ 10 ];
     *
     *  @return $this for a fluent interface.
     */
    public function populate(array $config)
    {
        foreach ($config as $key => $value)
        {
            $this->__set($key, $value);
            //$this->_params[$key] = $value;
        }

        return $this;
    }

    public function setNamespace($namespace)
    {
        $this->_params['namespace'] = $namespace;

        return $this;
    }

    public function setSortOrder($value)
    {
        $orig = $value;

        switch (strtoupper($value))
        {
        case Connexions_Service::SORT_DIR_ASC:
        case Connexions_Service::SORT_DIR_DESC:
            break;

        default:
            $value = self::$defaults['sortOrder'];
            break;
        }
        $this->_params['sortOrder'] = $value;

        /*
        Connexions::log("View_Helper_Bookmarks::setSortOrder( %s ) == '%s'",
                        $orig, $value);
        // */

        return $this;
    }

    public function setPage($value)
    {
        if ($value < 1)
            $value = self::$defaults['page'];

        $this->_params['page'] = $value;

        return $this;
    }

    public function setPerPage($value)
    {
        if (empty($value))
            $value = self::$defaults['perPage'];

        $this->_params['perPage'] = $value;

        return $this;
    }

    public function __set($key, $value)
    {
        /*
        Connexions::log("View_Helper_Bookmarks::__set(%s, %s)",
                        $key, $value);
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

    public function __get($key)
    {
        switch ($key)
        {
        case 'paginator':
            if (! isset($this->_params[$key]))
            {
                $items = $this->__get($this->listName);
                if ( (! is_object($items)) ||
                     (! method_exists($items, 'getPaginatorAdapter')) )
                {
                    throw new Exception("Invalid 'items' - "
                                        .   "MUST be an instance of a class "
                                        .   "that implements "
                                        .   "getPaginatorAdapter()");
                }

                /*
                Connexions::log("View_Helper_List::__get( %s ): "
                                . "%d items [ %s ]",
                                $key,
                                count($items),
                                (is_object($items)
                                    ? get_class($items)
                                    : gettype($items)) );
                // */

                $paginator = new Zend_Paginator($items->getPaginatorAdapter());

                /*
                Connexions::log("View_Helper_List::__get( %s ): "
                                . "Retrieve paginator: "
                                . "perPage[ %d ], page[ %d ]",
                                $key, $this->perPage, $this->page);
                // */

                $paginator->setItemCountPerPage(  $this->perPage );
                $paginator->setCurrentPageNumber( $this->page );

                $this->_params[$key] = $paginator;
            }
            break;
        }

        return (isset($this->_params[$key])
                    ? $this->_params[$key]
                    : null);
    }
}

