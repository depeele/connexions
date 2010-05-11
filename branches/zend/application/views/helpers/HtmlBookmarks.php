<?php
/** @file
 *
 *  View helper to render a paginated set of User Items / Bookmarks in HTML.
 */
class View_Helper_HtmlBookmarks extends View_Helper_Bookmarks
{
    static public   $numericGrouping    = 10;

    static public   $defaults           = array(
        'displayStyle'      => self::STYLE_REGULAR
    );


    const STYLE_TITLE                   = 'title';
    const STYLE_REGULAR                 = 'regular';
    const STYLE_FULL                    = 'full';
    const STYLE_CUSTOM                  = 'custom';

    /** @brief  Display style definition */
    static protected $displayStyles     = array(
        'item:stats:countTaggers'           => array(
            'label'         => 'user count',
            'containerCss'  => 'ui-corner-bottom'
        ),
        'item:stats:rating:stars'           => 'rating stars',
        'item:stats:rating:info'            => 'rating info',
        'item:data:itemName'                => array(
            'label'         => 'Title',
            'containerEl'   => 'h4'
        ),
        'item:data:url'                     => 'url',
        'item:data:description:full'        => 'description',
        'item:data:description:summary'     => array(
            'label'         => 'summarized description',
            'containerPost' => "<br class='clear' />"
        ),
        'item:data:userId:avatar'           => array(
            'label'         => 'avatar',
            'extraPre'      => "<div class='img icon-highlight'><div class='ui-icon ui-icon-person'>&nbsp;</div></div>"
        ),
        'item:data:userId:id'               => 'User Id',
        'item:data:tags'                    => array(
            'label'         => 'tags',
            'extraPost'     => "<label class='tag'>...</label><label class='tag'>...</label><label class='tag'>...</label><label class='tag'>...</label>",
            'containerPost' => "<br class='clear' />"
        ),
        'item:data:dates'                   => array(
            'containerPost' => "<br class='clear' />"
        ),
        'item:data:dates:tagged'            => 'date:Tagged',
        'item:data:dates:updated'           => 'date:Updated'
    );

    /** @brief  Pre-defined style groups. */
    static public   $styleGroups        = array(
        self::STYLE_TITLE   => array(
            'label'     => 'Title',
            'options'   => array('item:stats:countTaggers',
                                 'item:data:itemName',
                                 'item:data:description:summary',
                                 'item:data:userId:avatar',
                                 'item:data:userId:id'
            )
        ),
        self::STYLE_REGULAR => array(
            'label'     => 'Regular',
            'options'   => array('item:stats:countTaggers',
                                 'item:stats:rating:stars',
                                 'item:data:itemName',
                                 'item:data:description:summary',
                                 'item:data:userId:avatar',
                                 'item:data:userId:id',
                                 'item:data:tags',
                                 'item:data:dates:tagged'
            )
        ),
        self::STYLE_FULL    => array(
            'label'     => 'Full',
            'options'   => array('item:stats:countTaggers',
                                 'item:stats:rating:stars',
                                 'item:stats:rating:info',
                                 'item:data:itemName',
                                 'item:data:url',
                                 'item:data:description:full',
                                 'item:data:userId:avatar',
                                 'item:data:userId:id',
                                 'item:data:tags',
                                 'item:data:dates:tagged',
                                 'item:data:dates:updated'
            )
        ),
        self::STYLE_CUSTOM  => array(
            'label'     => 'Custom',
            'isCustom'  => true,
            'options'   => array('item:stats:countTaggers',
                                 'item:data:itemName',
                                 'item:data:description:summary',
                                 'item:data:userId:avatar',
                                 'item:data:userId:id',
                                 'item:data:tags',
            )
        )
    );


    /** @brief  Set-able parameters. */
    protected       $_displayOptions    = null;

    // Over-ride the default _namespace
    protected       $_namespace         ='items';

    static protected $_initialized  = array();

    public function __construct()
    {
        // Add extra class-specific defaults
        self::$defaults['displayStyle'] = self::STYLE_REGULAR;
    }

    /** @brief  Render an HTML version of a paginated set of User Items or,
     *          if no arguments, this helper instance.
     *  @param  paginator       The Zend_Paginator representing the items to
     *                          be presented.
     *  @param  viewer          A Model_User instance representing the
     *                          current viewer;
     *  @param  tags            A Connexions_Model_Set instance containing
     *                          information about the requested tags;
     *  @param  style           The style to use for each item
     *                          (View_Helper_HtmlBookmarks::STYLE_*);
     *  @param  sortBy          The field used to sort the items
     *                          (View_Helper_HtmlBookmarks::SORT_BY_*);
     *  @param  sortOrder       The sort order
     *                          (Connexions_Service::SORT_DIR_ASC |
     *                           Connexions_Service::SORT_DIR_DESC)
     *
     *  @return The HTML representation of the user items.
     */
    public function htmlBookmarks($paginator    = null,
                                  $viewer       = null,
                                  $tags         = null,
                                  $style        = null,
                                  $sortBy       = null,
                                  $sortOrder    = null)
    {
        if ((! $paginator instanceof Zend_Paginator)                         ||
            (! $viewer    instanceof Model_User)                             ||
            (! $tags      instanceof Connexions_Model_Set))
        {
            return $this;
        }

        return $this->render($paginator, $viewer, $tags,
                             $style, $sortBy, $sortOrder);
    }

    /** @brief  Set the namespace, primarily for forms and cookies.
     *  @param  namespace   A string namespace.
     *
     *  @return View_Helper_HtmlBookmarks for a fluent interface.
     */
    public function setNamespace($namespace)
    {
        /*
        Connexions::log("View_Helper_HtmlBookmarks::"
                            .   "setNamespace( {$namespace} )");
        // */

        parent::setNamespace($namespace);

        if (! @isset(self::$_initialized['__global__']))
        {
            // Include required jQuery
            $view   = $this->view;
            $jQuery =  $view->jQuery();

            $jQuery->addJavascriptFile($view->baseUrl('js/ui.stars.min.js'))
                   ->addJavascriptFile($view->baseUrl('js/ui.checkbox.min.js'))
                   ->addJavascriptFile($view->baseUrl('js/ui.button.min.js'))
                   ->addJavascriptFile($view->baseUrl('js/ui.input.min.js'))
                   ->javascriptCaptureStart();
            ?>

/************************************************
 * Initialize group header options.
 *
 */
function init_GroupHeader(namespace)
{
    var $headers    = $('#'+ namespace +'List .groupHeader .groupType');
    var dimOpacity  = 0.5;

    $headers
        .fadeTo(100, dimOpacity)
        .hover(
            // in
            function() {
                $(this).fadeTo(100, 1.0);
            },

            // out
            function() {
                $(this).fadeTo(100, dimOpacity);
            }
        );
}

/************************************************
 * Initialize ui elements.
 *
 */
function init_Bookmarks(namespace)
{
    var $list       = $('#'+ namespace +'List');
    var $bookmarks  = $list.find('form.bookmark');

    // Favorite
    $bookmarks.find('input[name=isFavorite]').checkbox({
        css:        'connexions_sprites',
        cssOn:      'star_fill',
        cssOff:     'star_empty',
        titleOn:    'Favorite: click to remove from Favorites',
        titleOff:   'Click to add to Favorites',
        useElTitle: false,
        hideLabel:  true
    });

    // Privacy
    $bookmarks.find('input[name=isPrivate]').checkbox({
        css:        'connexions_sprites',
        cssOn:      'lock_fill',
        cssOff:     'lock_empty',
        titleOn:    'Private: click to share',
        titleOff:   'Public: click to mark as private',
        useElTitle: false,
        hideLabel:  true
    });

    // Rating - average and user
    //$bookmarks.find('.rating .stars .average').stars({split:2});
    $bookmarks.find('.rating .stars .owner').stars();

    // Initialize any group headers
    init_GroupHeader(namespace);
}

            <?php
            $jQuery->javascriptCaptureEnd();

            self::$_initialized['__global__'] = true;
        }


        if (! @isset(self::$_initialized[$namespace]))
        {
            $view   = $this->view;

            // Set / Update our displayOptions namespace.
            if ($this->_displayOptions === null)
            {
                $dsConfig = array(
                                'namespace'     => $namespace,
                                'definition'    => self::$displayStyles,
                                'groups'        => self::$styleGroups
                            );

                $this->_displayOptions = $view->htmlDisplayOptions($dsConfig);
            }
            else
            {
                $this->_displayOptions->setNamespace($namespace);
            }

            // Include required jQuery
            $view->jQuery()->addOnLoad("init_Bookmarks('{$namespace}');");
        }

        return $this;
    }

    /** @brief  Set the current style.
     *  @param  style   A style value (self::STYLE_*) -- if an array if
     *                  provided, it will be used as 'values' and the style
     *                  will be set to self::STYLE_CUSTOM;
     *  @param  values  If provided, an array of field values for this style.
     *
     *  @return View_Helper_HtmlBookmarks for a fluent interface.
     */
    public function setStyle($style, array $values = null)
    {
        if (is_array($style))
        {
            $values = $style;
            $style  = self::STYLE_CUSTOM;
        }

        /*
        Connexions::log("View_Helper_HtmlBookmarks::setStyle( %s, { %s } )",
                        $style, var_export($values, true));
        // */


        /*
        if (($values !== null) && (! empty($values)) )
        {
            $this->_displayOptions->setGroupValues($values);
            if ($style === self::STYLE_CUSTOM)
                $this->_displayOptions->setGroup($style);
        }
        else
        */
        {
            switch ($style)
            {
            case self::STYLE_TITLE:
            case self::STYLE_REGULAR:
            case self::STYLE_FULL:
            case self::STYLE_CUSTOM:
                break;

            default:
                $style = self::$defaults['displayStyle'];
                break;
            }

            $this->_displayOptions->setGroup($style, $values);
        }

        // /*
        Connexions::log('View_Helper_HtmlBookmarks::'
                            . "setStyle({$style}) == [ "
                            .   $this->_displayOptions->getGroup() ." ]");
        // */
    
        return $this;
    }

    /** @brief  Get the current style value.
     *
     *  @return The style value (self::STYLE_*).
     */
    public function getStyle()
    {
        return $this->_displayOptions->getGroup();
    }


    /** @brief  Get the current showMeta value.
     *
     *  @return The showMeta value (self::SORT_BY_*).
     */
    public function getShowMeta()
    {
        if (! $this->multipleUsers)
        {
            /* If we're only showing information for a single user, mark 
             * 'userId' as 'hide' (not true nor false).
             */
            $this->_displayOptions
                    ->setGroupValue('item:data:userId:avatar','hide')
                    ->setGroupValue('item:data:userId:id',    'hide');
        }

        $val = $this->_displayOptions->getGroupValues();

        /*
        Connexions::log("View_Helper_HtmlBookmarks::"
                            . "getShowMeta(): "
                            . "[ ". print_r($val, true) ." ]");
        // */

        if (! @is_bool($val['minimized']))
        {
            /* Include additional meta information:
             *      minimized
             */
            $val['minimized'] =
                   (//($val['item:stats:rating']          !== true) &&
                    ($val['item:data:url']              !== true) &&
                    ($val['item:data:description:full'] !== true));
        }

        /*
        Connexions::log('View_Helper_HtmlBookmarks::'
                            . 'getShowMeta(): return[ '
                            .       print_r($val, true) .' ]');
        // */
    
        return $val;
    }

    /** @brief  Render an HTML version of a paginated set of User Items.
     *  @param  paginator       The Zend_Paginator representing the items to
     *                          be presented.
     *  @param  viewer          A Model_User instance representing the
     *                          current viewer;
     *  @param  tags            A Connexions_Model_Set instance containing
     *                          information about the requested tags;
     *  @param  style           The style to use for each item
     *                          (View_Helper_HtmlBookmarks::STYLE_*);
     *  @param  sortBy          The field used to sort the items
     *                          (View_Helper_HtmlBookmarks::SORT_BY_*);
     *  @param  sortOrder       The sort order
     *                          (Connexions_Service::SORT_DIR_ASC |
     *                           Connexions_Service::SORT_DIR_DESC)
     *
     *  @return The HTML representation of the user items.
     */
    public function render(Zend_Paginator            $paginator,
                           Model_User                $viewer,
                           Connexions_Model_Set      $tags,
                           $style        = null,
                           $sortBy       = null,
                           $sortOrder    = null)
    {
        /*
        Connexions::log("View_Helper_HtmlBookmarks: "
                            . "style[ {$style} ], "
                            . "sortBy[ {$sortBy} ], "
                            . "sortOrder[ {$sortOrder} ]");
        // */

        if ($style     !== null)    $this->setStyle($style );
        if ($sortBy    !== null)    $this->setSortBy($sortBy);
        if ($sortOrder !== null)    $this->setSortOrder($sortOrder);

        /*
        Connexions::log("View_Helper_HtmlBookmarks: "
                            . "validated to: "
                            . "style[ {$this->getStyle()} ], "
                            . "sortBy[ {$this->sortBy} ], "
                            . "sortByTitle[ "
                            .       self::$sortTitles[$this->sortBy]." ], "
                            . "sortOrder[ {$this->sortOrder} ], "
                            . "sortOrderTitle[ "
                            .       self::$orderTitles[$this->sortOrder]." ]");
        // */

        $html         = "";
        $showMeta     = $this->getShowMeta();

        $uiPagination = $this->view->htmlPaginationControl();
        $uiPagination->setNamespace($this->namespace)
                     ->setPerPageChoices(self::$perPageChoices);

        $html .= "<div id='{$this->namespace}List'>"   // List {
              .   $uiPagination->render($paginator, 'pagination-top', true)
              .   $this->_renderDisplayOptions($paginator);

        $nPages = count($paginator);
        if ($nPages > 0)
        {
            /*
            Connexions::log("View_Helper_HtmlBookmarks: "
                            . "render page %d",
                            $paginator->getCurrentPageNumber());
            // */

            $html .= "<ul class='{$this->namespace}'>";

            // Group by the field identified in $this->sortBy
            $lastGroup = null;
            foreach ($paginator as $idex => $bookmark)
            {
                if ($bookmark === null)
                {
                    /* Paginator items that aren't avaialble (i.e. beyond the
                     * end of the paginated set) are returned as null.
                     * Therefore, the first null item indicates end-of-set.
                     */
                    break;
                }

                $groupVal = $bookmark->{$this->sortBy};
                $newGroup = $this->_groupValue($this->sortBy, $groupVal);

                if ($newGroup !== $lastGroup)
                {
                    $html      .= $this->_renderGroupHeader($this->sortBy,
                                                            $newGroup);
                    $lastGroup  = $newGroup;
                }

                $html .= $this->view->htmlBookmark($bookmark,
                                                   $viewer,
                                                   $showMeta,
                                                   $idex);
            }

            $html .= "</ul>";
        }


        $html .= $uiPagination->render($paginator)
              .  "<br class='clear' />\n"
              . "</div>\n";                      // List }

        // Return the rendered HTML
        return $html;
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Given a grouping identifier and values, return the group into
     *          which the value falls.
     *  @param  groupBy     The grouping identifier / field (self::SORT_BY_*);
     *  @param  value       The value;
     *
     * @return  The value of the group into which the value falls.
     */
    protected function _groupValue($groupBy, $value)
    {
        $orig = $value;
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
            $value = floor($value);
            break;

        case self::SORT_BY_RATING_COUNT:      // 'ratingCount'
        case self::SORT_BY_USER_COUNT:        // 'userCount'
            /* We'll do numeric grouping in groups of:
             *      self::$numericGrouping [ 10 ]
             */
            $value = floor($value / self::$numericGrouping) *
                                                    self::$numericGrouping;
            break;
        }

        /*
        Connexions::log(
            sprintf("HtmlBookmarks::_groupValue(%s, %s:%s) == [ %s ]",
                    $groupBy, $orig, gettype($orig),
                    $value));
        // */

        return $value;
    }


    protected $_lastYear    = null;

    /** @brief  Render the HTML of a group header.
     *  @param  groupBy     The grouping identifier / field (self::SORT_BY_*);
     *  @param  value       The value of this group;
     *
     *  @return The HTML of the group header.
     */
    protected function _renderGroupHeader($groupBy, $value)
    {
        $html  =  "<div class='groupHeader ui-corner-tl'>" // groupHeader {
               .   "<div class='group group". ucfirst($groupBy)
                                      // groupTaggedOn, groupDateUpdated,
                                      // groupName,     groupRating,
                                      // groupUserCount
               .              " ui-corner-right'>";


        switch ($groupBy)
        {
        case self::SORT_BY_DATE_TAGGED:       // 'taggedOn'
        case self::SORT_BY_DATE_UPDATED:      // 'dateUpdated'
            // The date group value will be of the form YYYY-MM-DD
            list($year, $month, $day) = explode('-', $value);

            // Figure out the day-of-week
            $time      = strtotime($value);
            $month     = date('M', $time);
            $dayOfWeek = date('D', $time);

            $html .= "<div class='groupType date'>";

            if ($year !== $this->_lastYear)
            {
                $html .=  "<span class='year'    >{$year}</span>";
                $this->_lastYear = $year;
            }

            $html .=  "<div class='dateBox'>"
                  .    "<span class='month'      >{$month}</span>"
                  .    "<span class='day'        >{$day}</span>"
                  .    "<span class='day-of-week'>{$dayOfWeek}</span>"
                  .   "</div>"
                  .  "</div>";
            break;
            
        case self::SORT_BY_NAME:              // 'name'
            $html .= "<div class='groupType alpha'>"
                  .   $value
                  .  "</div>";
            break;

        case self::SORT_BY_RATING:            // 'rating'
        case self::SORT_BY_RATING_COUNT:      // 'ratingCount'
        case self::SORT_BY_USER_COUNT:        // 'userCount'
            $html .= "<div class='groupType numeric'>"
                  .   $value .'<sup>+</sup>'
                  .  "</div>";
            break;
        }

        $html  .=  "</div>"
               .  "</div>";                         // groupHeader }

        return $html;
    }

    /** @brief  Render the 'displayOptions' control area.
     *  @param  paginator   The current paginator (so we know the number of 
     *                                             items per page);
     *
     *
     *  @return A string of HTML.
     */
    protected function _renderDisplayOptions($paginator)
    {
        $namespace        = $this->namespace;
        $itemCountPerPage = $paginator->getItemCountPerPage();


        /**************************************************************
         * SortBy
         *
         */
        $html =  "<label   for='{$namespace}SortBy'>Sorted by</label>"
              .  "<select name='{$namespace}SortBy' "
              .            "id='{$namespace}SortBy' "
              .         "class='sort-by sort-by-{$this->sortBy} "
              .                 "ui-input ui-state-default ui-corner-all'>";

        Connexions::log('View_Helper_HtmlBookmarks::_renderDisplayOptions(): '
                        .   '_sortBy[ %s ], sortOrder[ %s ]',
                        $this->sortBy, $this->sortOrder);

        foreach (self::$sortTitles as $key => $title)
        {
            $isOn = ($key == $this->sortBy);
            $css  = 'ui-corner-all';

            if ($isOn)  $css .= ' option-on';

            $html .= sprintf(  "<option%s title='%s' value='%s'%s>"
                             .  "<span>%s</span>"
                             . "</option>",
                             ( !@empty($css) ? " class='". $css ."'" : ""),
                             $title,
                             $key,
                             ($isOn          ? " selected"           : ""),
                             $title);
        }

        $html .= "</select>";

        $this->_displayOptions->addFormField('sortBy', $html);


        /**************************************************************
         * SortOrder
         *
         */
        $html =  "<label for='{$namespace}SortOrder'>Sort order</label>";

        foreach (self::$orderTitles as $key => $title)
        {
            $html .= "<div class='field'>"
                  .   "<input type='radio' name='{$namespace}SortOrder' "
                  .                         "id='{$namespace}SortOrder-{$key}' "
                  .                      "value='{$key}'"
                  .          ($key == $this->sortOrder
                                 ? " checked='true'" : "" ). " />"
                  .   "<label for='{$namespace}SortOrder-{$key}'>"
                  .    $title
                  .   "</label>"
                  .  "</div>";
        }

        $html .= "<br class='clear' />";

        $this->_displayOptions->addFormField('sortOrder', $html);

        /**************************************************************
         * PerPage
         *
         */
        $html =  "<label for='{$namespace}PerPage'>Per page</label>"
              .  "<select class='ui-input ui-state-default ui-corner-all "
              .                "count' name='{$namespace}PerPage'>"
              .   "<!-- {$namespace}PerPage: {$itemCountPerPage} -->";

        foreach (self::$perPageChoices as $perPage)
        {
            $html .= "<option value='{$perPage}'"
                  .           ($perPage == $itemCountPerPage
                                 ? ' selected'
                                 : '')
                  .                     ">{$perPage}</option>";
        }
    
        $html .= "</select>"
              .  "<br class='clear' />";

        $this->_displayOptions->addFormField('perPage', $html);

        /* _displayOptions->render will use the previously added fields, along
         * with the available display styles to render the complete display
         * options form.
         */
        return $this->_displayOptions->render();
    }
}
