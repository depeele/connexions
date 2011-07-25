<?php
/** @file
 *
 *  View helper to render a paginated set of User Items / Bookmarks in HTML.
 *
 *  REQUIRES:
 *      application/view/scripts/paginator.phtml
 */
class View_Helper_HtmlPaginationControl
                                    extends View_Helper_Abstract
{
    public static   $marker             = array(
                        'first' => '&laquo;',   //'&#x00ab',    //'&laquo;',
                        'prev'  => '&lsaquo;',  //'&#x2039',    //'&lsaquo;',
                        'next'  => '&rsaquo;',  //'&#x203a',    //'&rsaquo;',
                        'last'  => '&raquo;'    //'&#x00bb',    //'&raquo;',
    );
    public static   $cssClassForm       = 'paginator';
    public static   $cssClassButton     = 'ui-corner-all';

    protected       $_defaults          = array(
        'namespace'         => '',          /* The namespace to use for all
                                             * cookies/parameters/settings;
                                             */
        'paginator'         => null,        /* The Zend_Paginator representing
                                             * the items to be presented;
                                             */
        'perPageChoices'    => array(10, 25, 50, 100, 250, 500),
    );

    /** @brief  Set the array of valid 'perPageChoices'.
     *  @param  choices     The new set of valid choices.  SHOULD be an array.
     *
     *  @return this for a fluent interface.
     */
    public function setPerPageChoices($choices)
    {
        if (@is_array($choices))
            $this->_params['perPageChoices'] = $choices;

        return $this;
    }

    /** @brief  Set the namespace, primarily for forms and cookies.
     *  @param  namespace   A string prefix.
     *
     *  @return this for a fluent interface.
     */
    public function setNamespace($namespace)
    {
        $this->_params['namespace'] = $namespace;

        return $this;
    }

    /** @brief  Get the current namespace.
     *
     *  @return The namespace string.
     */
    public function getNamespace()
    {
        return $this->_params['namespace'];
    }

    /** @brief  Configure and retrive this helper instance OR, if no
     *          configuration is provided, perform a render.
     *  @param  config  A configuration array (see populate());
     *
     *  @return A (partially) configured instance of $this.
     */
    public function htmlPaginationControl(array $config = array())
    {
        if (! empty($config))
        {
            $this->populate($config);
        }

        return $this;
    }

    /** @brief  Render an HTML representation of the pagination control.
     *  @param  cssClassExtra   Additional CSS class(es) [ null ];
     *  @param  excludeInfo     Should paging information be excluded [ false ].
     *
     *  @return The HTML representation of the pagination control.
     */
    public function render($cssClassExtra  = null,
                           $excludeInfo    = false)
    {
        /*
        Connexions::log('View_Helper_HtmlPaginationControl::render(): '
                        . 'paginator: page count[ %d ]',
                        count($this->paginator));
        // */

        return $this->view->partial('paginator.phtml', array(
                    'namespace'      => $this->namespace,
                    'paginator'      => $this->paginator,
                    'perPageChoices' => $this->perPageChoices,
                    'cssForm'        => $cssClassExtra,
                    'excludeInfo'    => $excludeInfo,
               ));
    }
}
