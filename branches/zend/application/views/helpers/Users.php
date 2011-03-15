<?php
/** @file
 *
 *  View helper to render a paginated set of Users.
 */
class View_Helper_Users extends View_Helper_List
{
    const SORT_BY_NAME              = 'name';
    const SORT_BY_FULLNAME          = 'fullName';
    const SORT_BY_EMAIL             = 'email';

    const SORT_BY_DATE_VISITED      = 'lastVisit';

    const SORT_BY_TAG_COUNT         = 'totalTags';
    const SORT_BY_ITEM_COUNT        = 'totalItems';

    static public   $defaults       = array(
        'listName'                  => 'users',
        'tags'                      => null,

        'sortBy'                    => self::SORT_BY_NAME,
        'sortOrder'                 => Connexions_Service::SORT_DIR_ASC,
    );


    static public   $sortTitles     = array(
        self::SORT_BY_NAME          => 'User Name',
        self::SORT_BY_FULLNAME      => 'Full Name',
        self::SORT_BY_EMAIL         => 'Email Address',

        self::SORT_BY_DATE_VISITED  => 'Last Visit Date',

        self::SORT_BY_TAG_COUNT     => 'Tag Count',
        self::SORT_BY_ITEM_COUNT    => 'Item Count',
    );

    /** @brief  Construct a new Users helper.
     *  @param  config  A configuration array (see populate());
     */
    public function __construct(array $config = array())
    {
        // To allow parent::setSortOrder() to use the default we're overriding
        parent::$defaults['sortOrder'] = self::$defaults['sortOrder'];

        foreach (self::$defaults as $key => $value)
        {
            if (! isset($this->_params[$key]))
                $this->_params[$key] = $value;
        }

        parent::__construct($config);
    }

    public function users(array $config = array())
    {
        if (! empty($config))
        {
            $this->populate($config);
            return $this;
        }

        return $this->render();
    }

    /** @brief  Set the current sortBy.
     *  @param  sortBy  A sortBy value (self::SORT_BY_*)
     *
     *  @return $this for a fluent interface.
     */
    public function setSortBy($sortBy)
    {
        $orig = $sortBy;

        switch ($sortBy)
        {
        case self::SORT_BY_NAME:
        case self::SORT_BY_FULLNAME:
        case self::SORT_BY_EMAIL:
        case self::SORT_BY_DATE_VISITED:
        case self::SORT_BY_TAG_COUNT:
        case self::SORT_BY_ITEM_COUNT:
            break;

        default:
            $sortBy = self::$defaults['sortBy'];
            break;
        }

        $this->_params['sortBy'] = trim($sortBy);

        /*
        Connexions::log('View_Helper_Users::'
                            . "setSortBy({$orig}) == [ {$sortBy} ]");
        // */

        return $this;
    }

    /** @brief  Retrieve the users to be presented.
     *
     *  @return The Model_Set_User instance representing the users.
     */
    public function getUsers()
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
            if ( $this->where !== null )
            {
                $to['where'] = $this->where;
            }

            if ( ($tags = $this->tags) !== null)
            {
                $to['tags']      =& $tags;
                $to['exactTags'] =  true;
            }

            /*
            Connexions::log("View_Helper_Users::getUsers(): "
                            . "Retrieve users: "
                            . "listname[ %s ], "
                            . "to[ %s ], "
                            . "order[ %s ], count[ %d ], offset[ %d ]",
                            $key,
                            Connexions::varExport($to),
                            $fetchOrder, $count, $offset);
            // */

            $users = Connexions_Service::factory('Model_User')
                                ->fetchRelated($to,
                                               $fetchOrder,
                                               $count,
                                               $offset);

            $this->_params[$key] = $users;
        }
        $val = $this->_params[$key];

        return $val;
    }

    public function __get($key)
    {
        $val = null;

        switch ($key)
        {
        case 'users':
        case 'items':
            $val = $this->getUsers();
            break;

        default:
            $val = parent::__get($key);
            break;
        }

        return $val;
    }

    /** @brief  Render a User within this list.
     *  @param  item    The Model_User instance to render;
     *  @param  params  If provided, parameters to pass to the partial
     *                  [ {namespace, bookmark, viewer} ];
     *
     *  Typically invoked from within a list-rendering view script.
     *
     *  @return The rendered user.
     */
    public function renderItem($item, $params = array())
    {
        if (empty($params))
        {
            $params = array('namespace' => $this->namespace,
                            'user'      => $item,
                            'viewer'    => $this->viewer,
                            'sortBy'    => $this->sortBy,
                      );
        }

        return parent::renderItem($item, $params);
    }

    /** @brief  Given a grouping identifier and values, return the group into
     *          which the value falls.
     *  @param  value       The value;
     *  @param  groupBy     The field by which to group [ $this->sortBy ];
     *
     *  Typically invoked from within a list-rendering view script.
     *
     *  @return The value of the group into which the value falls.
     */
    public function groupValue($value, $groupBy = null)
    {
        if ($groupBy === null)
            $groupBy = $this->sortBy;

        $orig = $value;
        switch ($groupBy)
        {
        case self::SORT_BY_DATE_VISITED:      // 'lastVisit'
            /* Dates are strings of the form YYYY-MM-DD HH:MM:SS
             *
             * Grouping should be by year:month:day, so strip off the time.
             */
            $value = substr($value, 0, 10);
            break;
            
        case self::SORT_BY_NAME:              // 'name'
        case self::SORT_BY_FULLNAME:          // 'fullName'
        case self::SORT_BY_EMAIL:             // 'email'
            $value = strtoupper(substr($value, 0, 1));
            break;

        case self::SORT_BY_TAG_COUNT:         // 'totalTags'
        case self::SORT_BY_ITEM_COUNT:        // 'totalItems'
            /* We'll do numeric grouping in groups of:
             *      $this->numericGrouping [ 10 ]
             */
            $value = floor($value / $this->numericGrouping) *
                                                    $this->numericGrouping;
            break;
        }

        /*
        Connexions::log("View_Helper_Users::groupValue(%s:%s, %s) == [ %s ]",
                        $orig, gettype($orig), $groupBy,
                        $value);
        // */

        return $value;
    }
}
