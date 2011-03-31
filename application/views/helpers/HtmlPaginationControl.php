<?php
/** @file
 *
 *  View helper to render a paginated set of User Items / Bookmarks in HTML.
 *
 *  REQUIRES:
 *      application/view/scripts/paginator.phtml
 */
class View_Helper_HtmlPaginationControl
                                    extends Zend_View_Helper_Abstract
{
    public static   $marker             = array(
                        'first' => '&laquo;',   //'&#x00ab',    //'&laquo;',
                        'prev'  => '&lsaquo;',  //'&#x2039',    //'&lsaquo;',
                        'next'  => '&rsaquo;',  //'&#x203a',    //'&rsaquo;',
                        'last'  => '&raquo;'    //'&#x00bb',    //'&raquo;',
    );
    public static   $cssClassForm       = 'paginator';
    public static   $cssClassButton     = 'ui-corner-all';



    protected       $_namespace         = '';
    protected       $_perPageChoices    = array(10, 25, 50, 100, 250, 500);

    public function setPerPageChoices($choices)
    {
        if (@is_array($choices))
            $this->_perPageChoices = $choices;

        return $this;
    }

    /** @brief  Set the namespace, primarily for forms and cookies.
     *  @param  namespace   A string prefix.
     *
     *  @return View_Helper_HtmlPaginationControl
     *              for a fluent interface.
     */
    public function setNamespace($namespace)
    {
        /*
        Connexions::log("View_Helper_HtmlPaginationControl::"
                            . "setNamespace( {$namespace} )");
        // */

        $this->_namespace = $namespace;

        return $this;
    }

    /** @brief  Get the current namespace.
     *
     *  @return The namespace string.
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /** @brief  Render an HTML version of a paginated set of User Items or,
     *          if no arguments, this helper instance.
     *  @param  paginator       The Zend_Paginator representing the items to
     *                          be presented.
     *  @param  cssClassExtra   Additional CSS class(es) [ null ];
     *  @param  excludeInfo     Should paging information be excluded [false].
     *
     *  @return The HTML representation of the pagination control or
     *          View_Helper_HtmlPaginationControl.
     */
    public function htmlPaginationControl(Zend_Paginator    $paginator  = null,
                                          $cssClassExtra    = null,
                                          $excludeInfo      = false)
    {
        if ($paginator === null)    //! $paginator instanceof Zend_Paginator )
        {
            /*
            Connexions::log("View_Helper_HtmlPaginationControl:: "
                                . "return instance");
            // */

            return $this;
        }

        return $this->render($paginator, $cssClassExtra, $excludeInfo);
    }

    /** @brief  Render an HTML representation of the pagination control.
     *  @param  paginator       The Zend_Paginator representing the items to
     *                          be presented;
     *  @param  cssClassExtra   Additional CSS class(es) [ null ];
     *  @param  excludeInfo     Should paging information be excluded [ false ].
     *
     *  @return The HTML representation of the pagination control.
     */
    public function render(Zend_Paginator   $paginator      = null,
                                            $cssClassExtra  = null,
                                            $excludeInfo    = false)
    {
        if (($paginator             === null) &&
            isset($this->view->paginator)     &&
            ($this->view->paginator !== null) &&
            ($this->view->paginator instanceof Zend_Paginator) )
        {
            $paginator = $this->view->paginator;
        }

        return $this->view->partial('paginator.phtml',
                                     array(
                                        'namespace'   => $this->_namespace,
                                        'paginator'   => $paginator,
                                        'cssForm'     => $cssClassExtra,
                                        'excludeInfo' => $excludeInfo,
                                    ));
    }
}
