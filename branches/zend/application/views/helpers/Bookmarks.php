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
    const SORT_BY_RATING_COUNT      = 'item_ratingCount';
    const SORT_BY_USER_COUNT        = 'item_userCount';

    static public   $perPageChoices = array(10, 25, 50, 100);

    static public   $defaults       = array(
        'namespace'                 => '',
        'sortBy'                    => self::SORT_BY_DATE_TAGGED,
        'sortOrder'                 => Connexions_Service::SORT_DIR_DESC,
        'perPage'                   => 50,
        'multipleUsers'             => true,
    );

    static public   $sortTitles     = array(
        self::SORT_BY_DATE_TAGGED   => 'Tag Date',
        self::SORT_BY_DATE_UPDATED  => 'Update Date',
        self::SORT_BY_NAME          => 'Title',
        self::SORT_BY_RATING        => 'Rating',
        self::SORT_BY_RATING_COUNT  => 'Rating Count',
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

    public function __construct()
    {
        //Connexions::log("View_Helper_Bookmarks::__construct()");

        foreach (self::$defaults as $key => $val)
        {
            $this->_params[$key] = $val;
        }
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
        case self::SORT_BY_USER_COUNT:
            break;

        default:
            $value = self::$defaults['sortBy'];
            break;
        }

        $this->_params['sortBy'] = $value;

        Connexions::log("View_Helper_Bookmarks::setSortBy( %s ) == '%s'",
                        $orig, $value);

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

        Connexions::log("View_Helper_Bookmarks::setSortOrder( %s ) == '%s'",
                        $orig, $value);

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
        if (! isset($this->_params[$key]))
            throw new Exception("Unknown parameter key [{$key}]");

        switch ($key)
        {
        case 'sortBy':
            $this->setSortBy($value);
            break;

        case 'sortOrder':
            $this->setSortOrder($value);
            break;

        case 'perPage':
            $this->setPerPage($value);
            break;
        }
    }

    public function __get($key)
    {
        return (isset($this->_params[$key])
                    ? $this->_params[$key]
                    : null);
    }
}
