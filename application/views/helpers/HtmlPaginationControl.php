<?php
/** @file
 *
 *  View helper to render a paginated set of User Items / Bookmarks in HTML.
 */
class Connexions_View_Helper_HtmlPaginationControl
                                    extends Zend_View_Helper_Abstract
{
    public static   $marker             = array(
                        'first' => '&laquo;',   //'&#x00ab',    //'&laquo;',
                        'prev'  => '&lsaquo;',  //'&#x2039',    //'&lsaquo;',
                        'next'  => '&rsaquo;',  //'&#x203a',    //'&rsaquo;',
                        'last'  => '&raquo;'    //'&#x00bb',    //'&raquo;',
    );
    public static   $cssClassForm       = 'pagination';
    public static   $cssClassButton     = 'ui-corner-all';



    protected static    $_initialized       = false;
    protected           $_namespace         = '';
    protected           $_perPageChoices    = array(10, 25, 50, 100, 250, 500);

    public function setPerPageChoices($choices)
    {
        if (@is_array($choices))
            $this->_perPageChoices = $choices;

        return $this;
    }

    /** @brief  Set the namespace, primarily for forms and cookies.
     *  @param  namespace   A string prefix.
     *
     *  @return Connexions_View_Helper_HtmlPaginationControl
     *              for a fluent interface.
     */
    public function setNamespace($namespace)
    {
        // /*
        Connexions::log("Connexions_View_Helper_HtmlPaginationControl::"
                            . "setNamespace( {$namespace} )");
        // */

        $this->_namespace = $namespace;

        if (! @isset(self::$_initialized[$namespace]))
        {
            $view   = $this->view;
            $jQuery =  $view->jQuery();

            $jQuery->addOnLoad("init_{$namespace}PaginationControls();")
                   ->javascriptCaptureStart();

            ?>

/************************************************
 * Initialize display options, as well as the
 * PerPage selector in the bottom paginator.
 *
 */
function init_<?= $namespace ?>PaginationControls()
{
    var $controls = $('form.pagination');

    // Add an opacity hover effect to the pagination controls
    $controls.filter(':first')
             .fadeTo(100, 0.5)
             .hover(    function() {    // in
                            $(this).fadeTo(100, 1.0);
                        },
                        function() {    // out
                            $(this).fadeTo(100, 0.5);
                        }
             );

    // Attach to any PerPage selection box in pagination forms.
    $controls.filter(':has(select[name=<?= $namespace ?>PerPage])')
                .each(function() {
                        var $cForm  = $(this);

                        $cForm.submit(function() {
                            // Serialize all form values to an array...
                            var settings    = $cForm.serializeArray();

                            /* ...and set a cookie for each:
                             *      <?= $namespace ?>PerPage
                             */
                            $(settings).each(function() {
                                $.log("Add Cookie: name[%s], value[%s]",
                                    this.name, this.value);
                                $.cookie(this.name, this.value);
                            });

                            /* Finally, disable ALL inputs so our URL will have
                             * no parameters since we've stored them all in
                             * cookies.
                             */
                            $cForm.find('input,select').attr('disabled', true);
                        });

        $cForm.find('select[name=<?= $namespace ?>PerPage]')
                .change(function() {
                    // On change of the select item, submit the pagination form.
                    $cForm.submit();
                });
    });

    return;
}

            <?php
            $jQuery->javascriptCaptureEnd();

            self::$_initialized[$namespace] = true;
        }

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
     *          Connexions_View_Helper_HtmlPaginationControl.
     */
    public function htmlPaginationControl(Zend_Paginator    $paginator  = null,
                                          $cssClassExtra    = null,
                                          $excludeInfo      = false)
    {
        if ($paginator === null)    //! $paginator instanceof Zend_Paginator )
        {
            /*
            Connexions::log("Connexions_View_Helper_HtmlPaginationControl:: "
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
            isset($this->view->papginator)    &&
            ($this->view->paginator !== null) &&
            ($this->view->paginator instanceof Zend_Paginator) )
        {
            $paginator = $this->view->paginator;
        }

        /*  Retrieve the public members of the paginator:
         *      pageCount
         *      itemCountPerPage
         *      first
         *      current
         *      last
         *      next
         *      pagesInRange [ 1:1, 2:2, 3:3 ]
         *      firstPageInRange
         *      lastPageInRange
         *      currentItemCount
         *      totalItemCount
         *      firstItemNumber
         *      lastItemNumber
         */
        $pages = get_object_vars($paginator->getPages());

        $html  = sprintf("\n<!-- pages     [ %s ] -->\n",
                         var_export($pages, true));

        $html .= sprintf(  "<form class='%s%s'>"    // { form
                         .  "<div class='pager'>",  // { pager
                         self::$cssClassForm,
                         (! @empty($cssClassExtra)
                            ? " ". $cssClassExtra
                            : ""));


        if ($pages['pageCount'] > 1)
        {
            // Present the pages BEFORE the current range
            if ($pages['firstPageInRange'] > $pages['first'])
            {
                $html .= sprintf(
                          "<button type='submit' "
                        .         "name='page' "
                        .        "class='ends-left ui-state-default %s' "
                        .        "value='%s' "
                        .        "title='Previous Page'>%s</button>"
                        . "<button type='submit' "
                        .         "name='page' "
                        .        "class='ui-state-default %s' "
                        .        "value='%s' "
                        .        "title='Page %s'>%s</button>",
                        self::$cssClassButton,
                        $pages['previous'],
                        self::$marker['prev'],
                        self::$cssClassButton,
                        $pages['first'],
                        $pages['first'], $pages['first']);
            
                if ($pages['firstPageInRange'] > ($pages['first'] + 1))
                    $html .= sprintf(
                              "<button type='submit' "
                            .         "name='page' "
                            .        "class='ui-state-default %s' "
                            .        "value='%s' "
                            .        "title='Page %s'>%s</button>",
                            self::$cssClassButton,
                            $pages['first'] + 1,
                            $pages['first'] + 1, $pages['first'] + 1);
            
                if ($pages['firstPageInRange'] > ($pages['first'] + 2))
                    $html .= sprintf(
                           "<span class='ui-state-skip %s'>...</span>",
                           self::$cssClassButton);
            }
            else
            {
                $html .= sprintf(
                          "<button type='submit' "
                        .         "name='page' "
                        .        "class='ends-left ui-state-%s %s' "
                        .        "%s"
                        .        "value='%s' "
                        .        "title='Previous Page'>%s</button>",
                        (@isset($pages['previous'])
                            ? 'default'
                            : 'disabled'),
                        self::$cssClassButton,
                        (@isset($pages['previous'])
                            ? ''
                            : 'disabled '),
                        (@isset($pages['previous'])
                            ? $pages['previous']
                            : ''),
                        self::$marker['prev']);
                /*
                if (@isset($pages['previous']))
                    $html .= spritnf(
                              "<button type='submit' "
                            .         "name='page' "
                            .        "class='ends-left ui-state-default %s' "
                            .        "value='%s' "
                            .        "title='Previous Page'>%s</button>",
                            self::$cssClassButton,
                            $pages['previous'],
                            self::$marker['prev']);
                else
                    $html .= sprintf(
                            "<span class='ends-left ui-state-disabled %s' "
                            .               "title='Previous Page'>%s</span>",
                            self::$cssClassButton,
                            self::$marker['prev']);
                */
            }
            
            // Present the pages within the current range
            foreach ($pages['pagesInRange'] as $page)
            {
                if ($page == $pages['current'])
                    $html .= sprintf(
                            "<button class='ui-state-active %s'>%s</button>",
                            self::$cssClassButton, ($page));
                else
                    $html .= sprintf(
                              "<button type='submit' "
                            .         "name='page' "
                            .        "class='ui-state-default %s' "
                            .        "value='%s' "
                            .        "title='Page %s'>%s</button>",
                            self::$cssClassButton,
                            $page,
                            $page, $page);
                            //$this->view->url(array('page' => $page)), $page);
            }
            
            
            // Present the pages AFTER the current range
            if ($pages['lastPageInRange'] < $pages['last'])
            {
                if ($pages['lastPageInRange'] < ($pages['last'] - 2))
                    $html .= sprintf(
                            "<span class='ui-state-skip %s'>...</span>",
                            self::$cssClassButton);
            
                if ($pages['lastPageInRange'] < ($pages['last'] - 1))
                    $html .= sprintf(
                              "<button type='submit' "
                            .         "name='page' "
                            .        "class='ui-state-default %s' "
                            .        "value='%s' "
                            .        "title='Page %s'>%s</button>",
                            self::$cssClassButton,
                            $pages['last'] - 1,
                            $pages['last'] - 1, $pages['last'] - 1);
            
                $html .= sprintf(
                          "<button type='submit' "
                        .         "name='page' "
                        .        "class='ui-state-default %s' "
                        .        "value='%s' "
                        .        "title='Page %s'>%s</button>"
                        . "<button type='submit' "
                        .         "name='page' "
                        .        "class='ends-right ui-state-default %s' "
                        .        "value='%s' "
                        .        "title='Next Page'>%s</button>",
                        self::$cssClassButton,
                        $pages['last'],
                        $pages['last'], $pages['last'],
                        self::$cssClassButton,
                        $pages['next'],
                        self::$marker['next']);
            }
            else
            {
                $html .= sprintf(
                          "<button type='submit' "
                        .         "name='page' "
                        .        "class='ends-right ui-state-%s %s' "
                        .        "%s"
                        .        "value='%s' "
                        .        "title='Next Page'>%s</button>",
                        (@isset($pages['next'])
                            ? 'default'
                            : 'disabled'),
                        self::$cssClassButton,
                        (@isset($pages['next'])
                            ? ''
                            : 'disabled '),
                        (@isset($pages['next'])
                            ? $pages['next']
                            : ''),
                        self::$marker['next']);
            }
        }
        else
        {
            $html .= "&nbsp;";
        }
        
        $html .=  "</div>";                  // pager }
        
        if ( ! $excludeInfo )
        {
            $html .= "<div class='info'>";  // { info
        
            $totalItemCount   = $pages['totalItemCount'];
            $pageCount        = $pages['pageCount'];
            $itemCountPerPage = $pages['itemCountPerPage'];
            
            $html .= sprintf(
                      "<div class='perPage'>"
                    .   "<div class='itemCount'>%s</div>"
                    .   "item%s"
                    /*
                    .   " in"
                    .   "<div class='pageCount'>%s</div>"
                    .   "page%s"
                    */
                    .   " with"
                    .   "<select class='ui-input ui-state-default "
                    .                  "count %s' name='%sPerPage'>",
                    number_format($totalItemCount),
                    ($totalItemCount === 1 ? "" : "s"),
                    //number_format($pageCount),
                    //($pageCount      === 1 ? "" : "s"),
                    self::$cssClassButton,
                    $this->_namespace);
            
            foreach ($this->_perPageChoices as $perPage)
            {
                $html .= sprintf(
                         "<option value='%s'%s>%s</option>",
                             $perPage,
                             ($perPage == $itemCountPerPage
                                ? ' selected'
                                : ''),
                             $perPage);
            }
            
            $html .= sprintf(
                        "</select>"
                    .   "item%s per page."
                    . "</div>"
                    . "<div class='itemRange'>"
                    .   "Current viewing"
                    /*
                    .   "page %s,"
                    */
                    .   " items"
                    .   "<div class='count %s'>%s - %s</div>."
                    . "</div>"
                    ."</div>",      // info }
                    ($itemCountPerPage === 1 ? "" : "s"),
                    //number_format($pages['current']),
                    self::$cssClassButton,
                    number_format($pages['firstItemNumber']),
                    number_format($pages['lastItemNumber']));
        }
        
        $html .= "</form>";                 // form }

        return $html;
    }
}
