<?php
/** @file
 *
 *  View helper to render a paginated set of User Items / Bookmarks.
 */
class View_Helper_Bookmarks extends View_Helper_List
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
        'listName'                  => 'bookmarks',

        'users'                     => null,
        'tags'                      => null,
        'items'                     => null,

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
        parent::$defaults['listName']  = self::$defaults['listName'];
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

        $this->_params['users'] = $value;

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

    public function getBookmarks()
    {
        $key = $this->listName;
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

            $to         = array();

            $users = $this->users;
            if ($users !== null)
            {
                if ($users instanceof Model_User)
                    $to['users'] = $users->userId;
                else
                    $to['users'] =& $users;
            }

            $tags = $this->tags;
            if ($tags !== null)
            {
                $to['tagsExact'] =& $tags;
            }

            $items = $this->items;
            if ($items !== null)
            {
                $to['items'] =& $items;
            }


            // /*
            Connexions::log("View_Helper_Bookmarks::getBookmarks(): "
                            . "Retrieve bookmarks: "
                            . "order[ %s ], count[ %d ], offset[ %d ]",
                            $fetchOrder, $count, $offset);
            // */

            $bookmarks = Connexions_Service::factory('Model_Bookmark')
                                ->fetchRelated($to,
                                               $fetchOrder,
                                               $count,
                                               $offset);

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
            $val = $this->getBookmarks();
            break;

        default:
            $val = parent::__get($key);
            break;
        }

        return $val;
    }
}