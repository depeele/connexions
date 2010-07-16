<?php
/** @file
 *
 *  View helper to render a paginated set of User Items / Bookmarks.
 */
class View_Helper_Bookmarks extends View_Helper_Items
{
    /* Additional, statistics fields are established via
     *  Model_Mapper_Bookmark::_includeStatistics
     */
    const SORT_BY_DATE_TAGGED       = 'taggedOn';
    const SORT_BY_DATE_UPDATED      = 'updatedOn';
    const SORT_BY_NAME              = 'name';
    const SORT_BY_RATING            = 'rating';
    const SORT_BY_RATING_COUNT      = 'ratingCount';
    const SORT_BY_RATING_AVERAGE    = 'ratingAvg';
    const SORT_BY_USER_COUNT        = 'userCount';

    static public   $defaults       = array(
        'users'                     => null,
        'tags'                      => null,

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

    /** @brief  Construct a new Bookmarks helper.
     *  @param  config  A configuration array (see populate());
     */
    public function __construct(array $config = array())
    {
        // Over-ride the default sortBy/sortOrder
        parent::$defaults['sortBy']    = self::$defaults['sortBy'];
        parent::$defaults['sortOrder'] = self::$defaults['sortOrder'];

        foreach (self::$defaults as $key => $value)
        {
            $this->_params[$key] = $value;
        }

        parent::__construct($config);
    }

    public function bookmarks(array $config = array())
    {
        if (! empty($config))
        {
            $this->populate($config);
            return $this;
        }

        return $this->render();
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

        $this->_params['sortBy'] = trim($value);

        /*
        Connexions::log("View_Helper_Bookmarks::setSortBy( %s ) == '%s'",
                        $orig, $value);
        // */

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

    public function setUsers($value)
    {
        // Also set 'multipleUsers' based upon the value of 'users'
        if (($value !== null) &&
            ( (($value instanceof Model_Set_User) &&
              (count($value) == 1)) ||
              ($value instanceof Model_User) ) )
        {
            $this->setSingleUser();
        }
        else
        {
            $this->setMultipleUsers();
        }

        /*
        Connexions::log("View_Helper_Bookmarks::setUsers(%s): "
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

        return $this;
    }

    public function __set($key, $val)
    {
        if ($key === 'bookmarks')
            $key = 'items';

        return parent::__set($key, $val);
    }

    public function getBookmarks()
    {
        $key = 'items';
        if ( (! @isset($this->_params[$key])) ||
             ($this->_params[$key] === null) )
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

            // /*
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

            /*
            Connexions::log("View_Helper_Bookmarks::__get( %s ): "
                            . "Retrieved %d bookmarks: "
                            . "order[ %s ], count[ %d ], offset[ %d ]",
                            $key,
                            count($bookmarks),
                            $fetchOrder, $count, $offset);
            // */

            $this->_params[$key] = $bookmarks;
        }
        $val = $this->_params[$key];

        return $val;
    }

    public function __get($key)
    {
        $val = null;

        switch ($key)
        {
        case 'bookmarks':
        case 'items':
            $val = $this->getBookmarks();
            break;

        default:
            $val = parent::__get($key);
            break;
        }

        return $val;
    }
}
