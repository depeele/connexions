<?php
/** @file
 *
 *  View helper to render a paginated set of User Items / Bookmarks in HTML.
 */
class View_Helper_Bookmarks extends Zend_View_Helper_Abstract
{
    const SORT_BY_DATE_TAGGED       = 'taggedOn';
    const SORT_BY_DATE_UPDATED      = 'updatedOn';
    const SORT_BY_NAME              = 'name';
    const SORT_BY_RATING            = 'rating';
    const SORT_BY_RATING_COUNT      = 'item:ratingCount';
    const SORT_BY_RATING_AVERAGE    = 'item:ratingAvg';
    const SORT_BY_USER_COUNT        = 'userCount';

    static public   $perPageChoices = array(10, 25, 50, 100);

    static public   $defaults       = array(
        'namespace'                 => '',
        'viewer'                    => null,
        'users'                     => null,
        'tags'                      => null,

        'page'                      => 1,
        'perPage'                   => 50,

        'sortBy'                    => self::SORT_BY_DATE_TAGGED,
        'sortOrder'                 => Connexions_Service::SORT_DIR_DESC,

        'multipleUsers'             => true,
    );

    static public   $sortTitles     = array(
        self::SORT_BY_DATE_TAGGED   => 'Tag Date',
        self::SORT_BY_DATE_UPDATED  => 'Update Date',
        self::SORT_BY_NAME          => 'Title',
        self::SORT_BY_RATING        => 'Rating',
        self::SORT_BY_RATING_COUNT  => 'Rating Count',
        self::SORT_BY_RATING_AVERAGE=> 'Average Rating',
        self::SORT_BY_USER_COUNT    => 'User Count'
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
        //Connexions::log("View_Helper_Bookmarks::__construct()");
        foreach (self::$defaults as $key => $value)
        {
            $this->_params[$key] = $value;
        }

        if (! empty($config))
            $this->populate($config);
    }

    /** @brief  Given an array of configuration data, populate the parameter of
     *          this instance.
     *  @param  config  A configuration array that may include:
     *                      - namespace     The namespace to use for all
     *                                      cookies/parameters/settings
     *                                      [ '' ];
     *                      - viewer        A Model_User instance identifying
     *                                      the current viewer;
     *                      - users         A Model_Set_User or Model_User
     *                                      instance, or null for all users
     *                                      [ null ];
     *                      - tags          A Model_Set_Tag instance or null
     *                                      for all tags
     *                                      [ null ];
     *                      - page          The page to present [ 1 ];
     *                      - perPage       The number of items per page
     *                                      [ 50 ];
     *                      - sortBy        The Bookmark field to sort by
     *                                      [ self::SORT_BY_DATE_TAGGED ];
     *                      - sortOrder     The sort order
     *                                      [Connexions_Service::SORT_DIR_DESC]
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

        /*
        $viewer = $this->_params['viewer']; unset($this->_params['viewer']);
        $users  = $this->_params['users'];  unset($this->_params['users']);
        $tags   = $this->_params['tags'];   unset($this->_params['tags']);

        $this->_params['viewer'] = $viewer->name;
        $this->_params['users']  = ($this->_params['users'] === null
                                        ? ''
                                        : $this->_params['users']
                                                            ->__toString());
        $this->_params['tags']   = ($this->_params['tags'] === null
                                        ? ''
                                        : $this->_params['tags']
                                                            ->__toString());

        Connexions::log("View_Helper_Bookmarks::populate(): params[ %s ]",
                        print_r($this->_params, true));

        $this->_params['viewer'] = $viewer;
        $this->_params['users']  = $users;
        $this->_params['tags']   = $tags;
        // */

        return $this;
    }

    public function setNamespace($namespace)
    {
        $this->_params['namespace'] = $namespace;

        return $this;
    }

    public function setSortBy($value)
    {
        $orig = $value;

        switch ($value)
        {
        case self::SORT_BY_DATE_TAGGED:
        case self::SORT_BY_DATE_TAGGED:
        case self::SORT_BY_DATE_UPDATED:
        case self::SORT_BY_NAME:
        case self::SORT_BY_RATING:
        case self::SORT_BY_RATING_COUNT:
        case self::SORT_BY_RATING_AVERAGE:
        case self::SORT_BY_USER_COUNT:
            break;

        default:
            $value = self::$defaults['sortBy'];
            break;
        }

        $this->_params['sortBy'] = $value;

        /*
        Connexions::log("View_Helper_Bookmarks::setSortBy( %s ) == '%s'",
                        $orig, $value);
        // */

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
        if ($value < 1)
            $value = self::$defaults['perPage'];

        $this->_params['perPage'] = $value;

        return $this;
    }

    public function setMultipleUsers($value = true)
    {
        $this->_params['multipleUsers'] = ($value ? true : false);

        return $this;
    }

    public function setSingleUser($value = true)
    {
        $this->_params['multipleUsers'] = ($value ? false : true);

        return $this;
    }

    public function __set($key, $value)
    {
        /*
        if (! isset($this->_params[$key]))
            throw new Exception("Unknown parameter key [{$key}]");
        */
        /*
        Connexions::log("View_Helper_Bookmarks::__set(%s, %s)",
                        $key, $value);
        // */

        if ($key === 'users')
        {
            // Also set 'multipleUsers' based upon the value of 'users'
            if (($value !== null) &&
                ( (($value instanceof Model_Set_User) &&
                  (count($value) == 1)) ||
                  ($value instanceof Model_User) ) )
            {
                $this->_params['multipleUsers'] = false;
            }
            else
            {
                $this->_params['multipleUsers'] = true;
            }

            /*
            Connexions::log("View_Helper_Bookmarks::__set(%s): "
                            . "users set [ %s ], multipleUsers:%s",
                            $key,
                            ($value === null
                                ? 'null'
                                : ($value instanceof Model_Set_User
                                    ? 'Model_Set_User:'. count($value)
                                    : '! Model_Set_User')),
                            ($this->_params['multipleUsers']
                                ? 'true'
                                : 'false'));
            // */
        }

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
        case 'bookmarks':
            if (! @isset($this->_params[$key]))
            {
                $fetchOrder = $this->sortBy .' '. $this->sortOrder;
                $perPage    = $this->perPage;
                $page       = $this->page;
                if ($page < 1)
                    $page = 1;

                $count      = $perPage;
                $offset     = ($page - 1) * $perPage;

                if ($this->users instanceof Model_User)
                    $users = $this->users->userId;
                else
                    $users =& $this->users;

                /*
                Connexions::log("View_Helper_Bookmarks::__get( %s ): "
                                . "Retrieve bookmarks: "
                                . "order[ %s ], count[ %d ], offset[ %d ]",
                                $key, $fetchOrder, $count, $offset);
                // */

                $bookmarks = Connexions_Service::factory('Model_Bookmark')
                                    ->fetchByUsersAndTags($users,
                                                          $this->tags,
                                                          true,
                                                          $fetchOrder,
                                                          $count,
                                                          $offset);
                $this->_params[$key] = $bookmarks;
            }
            break;

        case 'paginator':
            if (! isset($this->_params[$key]))
            {
                $paginator = new Zend_Paginator(
                                    $this->bookmarks->getPaginatorAdapter() );

                /*
                Connexions::log("View_Helper_Bookmarks::__get( %s ): "
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
