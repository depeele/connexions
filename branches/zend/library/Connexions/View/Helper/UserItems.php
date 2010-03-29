<?php
/** @file
 *
 *  View helper to aid in the rendering of a paginated set of User Items /
 *  BookmarksHTML.
 */
class Connexions_View_Helper_UserItems extends Zend_View_Helper_Abstract
{
    static public   $perPageChoices     = array(10, 25, 50, 100);

    static public   $defaults               = array(
        'sortBy'            => self::SORT_BY_DATE_TAGGED,
        'sortOrder'         => Model_UserItemSet::SORT_ORDER_DESC,

        'perPage'           => 50,
        'multipleUsers'     => true,
    );

    const SORT_BY_DATE_TAGGED       = 'taggedOn';
    const SORT_BY_DATE_UPDATED      = 'updatedOn';
    const SORT_BY_NAME              = 'name';
    const SORT_BY_RATING            = 'rating';
    const SORT_BY_RATING_COUNT      = 'item_ratingCount';
    const SORT_BY_USER_COUNT        = 'item_userCount';

    static public   $sortTitles     = array(
                    self::SORT_BY_DATE_TAGGED   => 'Tag Date',
                    self::SORT_BY_DATE_UPDATED  => 'Update Date',
                    self::SORT_BY_NAME          => 'Title',
                    self::SORT_BY_RATING        => 'Rating',
                    self::SORT_BY_RATING_COUNT  => 'Rating Count',
                    self::SORT_BY_USER_COUNT    => 'User Count'
                );

    static public   $orderTitles    = array(
                    Model_UserItemSet::SORT_ORDER_ASC   => 'Ascending',
                    Model_UserItemSet::SORT_ORDER_DESC  => 'Descending'
                );



    /** @brief  Set-able parameters. */
    protected       $_namespace         = '';

    protected       $_sortBy            = null;
    protected       $_sortOrder         = null;
    protected       $_perPage           = null;
    protected       $_multipleUsers     = null;

    /** @brief  Set the namespace, primarily for forms and cookies.
     *  @param  namespace   A string namespace.
     *
     *  @return Connexions_View_Helper_UserItems for a fluent interface.
     */
    public function setNamespace($namespace)
    {
        // /*
        Connexions::log("Connexions_View_Helper_UserItems::"
                            .   "setNamespace( {$namespace} )");
        // */

        $this->_namespace = $namespace;

        return $this;
    }

    /** @brief  Get the current namespace.
     *
     *  @return The string namespace.
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /** @brief  Set the current sortBy.
     *  @param  sortBy  A sortBy value (self::SORT_BY_*)
     *
     *  @return Connexions_View_Helper_UserItems for a fluent interface.
     */
    public function setSortBy($sortBy)
    {
        $orig = $sortBy;

        switch ($sortBy)
        {
        case self::SORT_BY_DATE_TAGGED:
        case self::SORT_BY_DATE_UPDATED:
        case self::SORT_BY_NAME:
        case self::SORT_BY_RATING:
        case self::SORT_BY_RATING_COUNT:
        case self::SORT_BY_USER_COUNT:
            break;

        default:
            $sortBy = self::$defaults['sortBy'];
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_UserItems::'
                            . "setSortBy({$orig}) == [ {$sortBy} ]");
        // */

        $this->_sortBy = $sortBy;

        return $this;
    }

    /** @brief  Get the current sortBy value.
     *
     *  @return The sortBy value (self::SORT_BY_*).
     */
    public function getSortBy()
    {
        return $this->_sortBy;
    }

    /** @brief  Set the current sortOrder.
     *  @param  sortOrder   A sortOrder value (Model_UserItemSet::SORT_ORDER_*)
     *
     *  @return Connexions_View_Helper_UserItems for a fluent interface.
     */
    public function setSortOrder($sortOrder)
    {
        $orig = $sortOrder;

        $sortOrder = strtoupper($sortOrder);
        switch ($sortOrder)
        {
        case Model_UserItemSet::SORT_ORDER_ASC:
        case Model_UserItemSet::SORT_ORDER_DESC:
            break;

        default:
            $sortOrder = self::$defaults['sortOrder'];
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_UserItems::'
                            . "setSortOrder({$orig}) == [ {$sortOrder} ]");
        // */
    
        $this->_sortOrder = $sortOrder;

        return $this;
    }

    /** @brief  Get the current sortOrder value.
     *
     *  @return The sortOrder value (Model_UserItemSet::SORT_ORDER_*).
     */
    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    /** @brief  Set number of items per page.
     *  @param  perPage     The number of items per page
     *                          [ self::$defaults['perPage'] ];
     *
     *  @return Connexions_View_Helper_UserItems for a fluent interface.
     */
    public function setPerPage($perPage)
    {
        if ($perPage < 1)
            $perPage = self::$defaults['perPage'];

        /*
        Connexions::log('Connexions_View_Helper_UserItems::'
                            . "setPerPage() == [ {$perPage} ]");
        // */
    
        $this->_perPage = $perPage;

        return $this;
    }

    /** @brief  Get the current per-page value.
     *
     *  @return The perPage value [ self::$defaults['perPage'] ].
     */
    public function getPerPage()
    {
        return ($this->_perPage === null
                    ? self::$defaults['perPage']
                    : $this->_perPage);
    }

    /** @brief  Set the current multipleUsers.
     *  @param  multipleUsers   A multipleUsers boolean [ true ];
     *
     *  @return Connexions_View_Helper_UserItems for a fluent interface.
     */
    public function setMultipleUsers($multipleUsers = true)
    {
        $this->_multipleUsers = ($multipleUsers ? true : false);

        /*
        Connexions::log('Connexions_View_Helper_UserItems::'
                            . 'setMultipleUsers('
                            .   ($multipleUsers ? 'true' : 'false') .')');
        // */
    
        return $this;
    }

    /** @brief  Set the current multipleUsers to false.
     *
     *  @return Connexions_View_Helper_UserItems for a fluent interface.
     */
    public function setSingleUser()
    {
        $this->_multipleUsers = false;

        /*
        Connexions::log('Connexions_View_Helper_UserItems::'
                            . 'setSingleUser()');
        // */
    

        return $this;
    }

    /** @brief  Get the current multipleUsers value.
     *
     *  @return The multipleUsers boolean.
     */
    public function getMultipleUsers()
    {
        return $this->_multipleUsers;
    }

    /*************************************************************************
     * Protected helpers
     *
     */
}
