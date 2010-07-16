<?php
/** @file
 *
 *  View helper to render a paginated set of Users.
 */
class View_Helper_Users extends View_Helper_Items
{
    const SORT_BY_NAME              = 'name';
    const SORT_BY_FULLNAME          = 'fullName';
    const SORT_BY_EMAIL             = 'email';

    const SORT_BY_DATE_VISITED      = 'lastVisit';

    const SORT_BY_TAG_COUNT         = 'totalTags';
    const SORT_BY_ITEM_COUNT        = 'totalItems';

    static public   $defaults       = array(
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

    /** @brief  Set the current sortBy.
     *  @param  sortBy  A sortBy value (self::SORT_BY_*)
     *
     *  @return View_Helper_HtmlUsers for a fluent interface.
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

        /*
        Connexions::log('View_Helper_HtmlUsers::'
                            . "setSortBy({$orig}) == [ {$sortBy} ]");
        // */

        $this->_sortBy = $sortBy;

        return $this;
    }

    public function __set($key, $val)
    {
        if ($key === 'users')
            $key = 'items';

        return parent::__set($key, $val);
    }

    public function __get($key)
    {
        $val = null;

        switch ($key)
        {
        case 'users':
            $key = 'items';

            // Fall through
        case 'items':
            if (! @isset($this->_params[$key]))
            {
                $fetchOrder = $this->sortBy .' '. $this->sortOrder;
                $perPage    = $this->perPage;
                $page       = $this->page;
                if ($page < 1)
                    $page = 1;

                $count      = $perPage;
                $offset     = ($page - 1) * $perPage;

                /*
                Connexions::log("View_Helper_Users::__get( %s ): "
                                . "Retrieve users: "
                                . "order[ %s ], count[ %d ], offset[ %d ]",
                                $key, $fetchOrder, $count, $offset);
                // */

                $users = Connexions_Service::factory('Model_User')
                                    ->fetchByTags($this->tags,
                                                  true,
                                                  $fetchOrder,
                                                  $count,
                                                  $offset);

                /*
                Connexions::log("View_Helper_Users::__get( %s ): "
                                . "Retrieved %d users: "
                                . "order[ %s ], count[ %d ], offset[ %d ]",
                                $key,
                                count($users),
                                $fetchOrder, $count, $offset);
                // */

                $this->_params[$key] = $users;
            }
            $val = $this->_params[$key];

            break;

        default:
            $val = parent::__get($key);
            break;
        }

        return $val;
    }
}
