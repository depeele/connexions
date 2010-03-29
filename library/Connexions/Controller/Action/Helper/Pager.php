<?php
/** @file
 *
 *  An action helper to generate a standard Zend_Pagination instance.
 *
 */

class Connexions_Controller_Action_Helper_Pager
            extends Zend_Controller_Action_Helper_Abstract
{
    protected static   $_defaultPageRange  = 10;

    public static function setDefaultPageRange($range)
    {
        self::$_defaultPageRange = $range;
    }
    public static function getDefaultPageRange($range)
    {
        return self::$_defaultPageRange;
    }

    /** @brief  Initialize defaults from an associative array.
     *  @param  config      The defaults to set:
     *                          pageRange:  the default page range
     *
     *                          via Zend_Paginator:
     *                              scrollingStyle:     default scroll style
     *                              itemCountPerPage:   number of items / page
     */
    public static function setDefaults($config)
    {
        $class = 'Connexions_Controller_Action_Helper_Pager';

        foreach ($config as $key => $val)
        {
            if ($val === null)
                continue;

            $method = 'setDefault'. ucfirst($key);

            if (method_exists($class, $method))
            {
                //Connexions::log("{$class}::{$method}({$val})");
                call_user_func(array($class, $method), $val);
            }
            else if (method_exists('Zend_Paginator', $method))
            {
                //Connexions::log("{$class}::{$method}({$val}) -- Paginator");
                call_user_func(array('Zend_Paginator', $method), $val);
            }
            else
            {
                throw new Exception(
                                "Connexions_Controller_Action_Helper_Pager: "
                                . "invalid method '{$method}'");
            }
        }
    }

    // Proxy methods for Zend_Paginator
    public static function setDefaultItemCountPerPage($perPage)
    {
        Zend_Paginator::setDefaultItemCountPerPage($perPage);
    }
    public static function getDefaultItemCountPerPage()
    {
        return Zend_Paginator::getDefaultItemCountPerPage();
    }
    public static function setsDefaultScrollingStyle($style = 'Sliding')
    {
        Zend_Paginator::setDefaultScrollingStyle($style);
    }
    public static function getDefaultScrollingStyle()
    {
        return Zend_Paginator::getDefaultScrollingStyle();
    }

    /** @brief  Create a standardized Zend_Paginator.
     *  @param  data        The data to page.
     *  @param  curPage     The current page number.
     *  @param  perPage     The number of items per page.
     *
     *  @return A Zend_Paginator instance.
     */
    public function make(Zend_Paginator_Adapter_Interface   $data,
                         $curPage   = null,
                         $perPage   = null)
    {
        $pager = new Zend_Paginator( $data );
        $pager->setPageRange(self::$_defaultPageRange);

        if ($perPage > 0)
            $pager->setItemCountPerPage($perPage);

        if ($curPage > 0)
            $pager->setCurrentPageNumber($curPage);

        return $pager;
    }

    /** @brief  Allow calling this helper as a broker method.
     *  @param  data        The data to page.
     *  @param  curPage     The current page number.
     *  @param  perPage     The number of items per page.
     *
     *  @return A Zend_Paginator instance.
     */
    public function direct(Zend_Paginator_Adapter_Interface $data,
                           $curPage = null,
                           $perPage = null)
    {
        /*
        $mid = 'Connexions_Controller_Action_Helper_Pager::direct';
        Connexions::log($mid .": curPage[ {$curPage} ], perPage[ {$perPage} ]");
        // */

        return $this->make($data, $curPage, $perPage);
    }
}

