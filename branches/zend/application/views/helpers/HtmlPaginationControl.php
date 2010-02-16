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
    protected           $_prefix            = '';
    protected           $_perPageChoices    = array(10, 25, 50, 100, 250, 500);

    /** @brief  Set the View object.
     *  @param  view    The Zend_View_Interface
     *
     *  Override Zend_View_Helper_Abstract::setView() in order to initialize.
     *
     *  @return Zend_View_Helper_Abstract
     */
    public function setView(Zend_View_Interface $view)
    {
        parent::setView($view);

        if (! self::$_initialized)
        {
            $jQuery =  $view->jQuery();

            $jQuery->addOnLoad('init_paginationControls();')
                   ->javascriptCaptureStart();

            ?>

/************************************************
 * Initialize display options, as well as the
 * PerPage selector in the bottom paginator.
 *
 */
function init_paginationControls()
{
    var $controls = $('form.pagination');

    // Add an opacity hover effect to the pagination controls
    $controls.filter(':first')
             .fadeTo(100, 0.5)
             .hover(    function() {    // in
                            $controls.fadeTo(100, 1.0);
                        },
                        function() {    // out
                            $controls.fadeTo(100, 0.5);
                        }
             );

    // Attach to any perPage selection box in pagination forms.
    $controls.filter(':has(select[name=<?= $this->_prefix ?>perPage])')
                .each(function() {
                        var $cForm  = $(this);

                        $cForm.submit(function() {
                            // Serialize all form values to an array...
                            var settings    = $cForm.serializeArray();

                            /* ...and set a cookie for each:
                             *      <?= $this->_prefix ?>perPage
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

        $cForm.find('select[name=<?= $this->_prefix ?>perPage]')
                .change(function() {
                    // On change of the select item, submit the pagination form.
                    $cForm.submit();
                });
    });

    return;
}

            <?php
            $jQuery->javascriptCaptureEnd();

            self::$_initialized = true;
        }

        return $this;
    }

    public function setPerPageChoices($choices)
    {
        if (@is_array($choices))
            $this->_perPageChoices = $choices;

        return $this;
    }

    /** @brief  Set the cookie-name prefix.
     *  @param  prefix  A string prefix.
     *
     *  @return Connexions_View_Helper_HtmlPaginationControl
     *              for a fluent interface.
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;

        return $this;
    }

    /** @brief  Get the cookie-name prefix.
     *
     *  @return The prefix string.
     */
    public function getPrefix()
    {
        return $this->_prefix;
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
    public function htmlPaginationControl($paginator        = null,
                                          $cssClassExtra    = null,
                                          $excludeInfo      = false)
    {
        if (! $paginator instanceof Zend_Paginator )
        {
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

        $html .= sprintf(  "<form class='%s'>"      // { form
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
                    $this->_prefix);
            
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
