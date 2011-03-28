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

        /* Connexions_Model_Set instances that the retrieved bookmarks should 
         * be related to
         */
        'users'                     => null,
        'tags'                      => null,
        'items'                     => null,

        // Additional 'where' conditions for the retrieved bookmarks
        'where'                     => null,

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
        // To allow parent::setSortOrder() to use the default we're overriding
        parent::$defaults['sortOrder'] = self::$defaults['sortOrder'];

        foreach (self::$defaults as $key => $value)
        {
            if (! isset($this->_params[$key]))
            {
                /*
                Connexions::log("View_Helper_Bookmarks::__construct(): "
                                . "'%s', default value '%s'",
                                $key, $value);
                // */

                $this->_params[$key] = $value;
            }
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

    /** @brief  Retrieve the bookmarks to be presented.
     *
     *  @return The Model_Set_Bookmark instance representing the bookmarks.
     */
    public function getBookmarks()
    {
        $key = $this->listName;
        if ( (! @isset($this->_params[$key])) ||
             ($this->_params[$key] === null) )
        {
            /* This is here in a view helper and not in the controller
             * primarily to allow centralized, contextual default values
             * for things like sortBy, sortOrder and perPage.
             */
            $fetchOrder = $this->sortBy .' '. $this->sortOrder;
            $perPage    = $this->perPage;
            $page       = ($this->page > 0
                            ? $this->page
                            : 1);

            $count      = $perPage;
            $offset     = ($page - 1) * $perPage;


            $to = array();
            if ( ($where = $this->where) !== null)
            {
                $to['where'] = $where;
            }

            if ( ($users = $this->users) !== null)
            {
                if ($users instanceof Model_User)
                    $to['users'] = $users->userId;
                else
                    $to['users'] =& $users;
            }

            if ( ($tags = $this->tags) !== null)
            {
                $to['tags']      =& $tags;
                //$to['exactTags'] =  true;
            }

            if ( ($items = $this->items) !== null)
            {
                $to['items'] =& $items;
            }


            /*
            Connexions::log("View_Helper_Bookmarks::getBookmarks(): "
                            . "Retrieve bookmarks: "
                            . "to[ %s ], "
                            . "order[ %s ], count[ %d ], offset[ %d ]",
                            Connexions::varExport($to),
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

    /** @brief  Render a Bookmark within this list.
     *  @param  item    The Model_Bookmark instance to render;
     *  @param  params  If provided, parameters to pass to the partial
     *                  [ {namespace, bookmark, viewer} ];
     *
     *  Typically invoked from within a list-rendering view script.
     *
     *  @return The rendered bookmark.
     */
    public function renderItem($item, $params = array())
    {
        if (empty($params))
        {
            $params = array('namespace' => $this->namespace,
                            'bookmark'  => $item,
                            'viewer'    => $this->viewer,
                            'sortBy'    => $this->sortBy,
                      );
        }

        return parent::renderItem($item, $params);
    }

    /** @brief  Given a value, return the group (accoroding to 'groupBy') into
     *          which the value falls.
     *  @param  value       The value;
     *  @param  groupBy     The field by which to group [ $this->sortBy ];
     *
     *  Typically invoked from within a list-rendering view script.
     *
     *  @return  The value of the group into which the value falls.
     */
    public function groupValue($value, $groupBy = null)
    {
        if ($groupBy === null)
        {
            $groupBy = $this->sortBy;
        }

        $orig    = $value;
        switch ($groupBy)
        {
        case self::SORT_BY_DATE_TAGGED:       // 'taggedOn'
        case self::SORT_BY_DATE_UPDATED:      // 'dateUpdated'
            /* Dates are strings of the form YYYY-MM-DD HH:MM:SS
             *
             * Grouping should be by year:month:day, so strip off the time.
             */
            $value = substr($value, 0, 10);
            break;
            
        case self::SORT_BY_NAME:              // 'name'
            $value = strtoupper(substr($value, 0, 1));

            break;

        case self::SORT_BY_RATING:            // 'rating'
        case self::SORT_BY_RATING_AVERAGE:    // 'ratingAvg'
            $value = floor($value);
            break;

        case self::SORT_BY_RATING_COUNT:      // 'ratingCount'
        case self::SORT_BY_USER_COUNT:        // 'userCount'
            /* We'll do numeric grouping in groups of:
             *      $this->numericGrouping [ 10 ]
             */
            $value = floor($value / $this->numericGrouping) *
                                                    $this->numericGrouping;
            break;
        }

        /*
        Connexions::log(
            sprintf("Bookmarks::_groupValue(%s, %s:%s) == [ %s ]",
                    $groupBy, $orig, gettype($orig),
                    $value));
        // */

        return $value;
    }
}
