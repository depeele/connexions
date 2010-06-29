<?php
/** @file
 *
 *  View helper to render a paginated set of User Items / Bookmarks in HTML.
 */
class View_Helper_HtmlBookmarks extends View_Helper_Bookmarks
{
    static public   $defaults           = array(
        'displayStyle'      => self::STYLE_REGULAR,
        'numericGrouping'   => 10,
        'includeScript'     => true,
    );

    const STYLE_TITLE                   = 'title';
    const STYLE_REGULAR                 = 'regular';
    const STYLE_FULL                    = 'full';
    const STYLE_CUSTOM                  = 'custom';

    /** @brief  Display style definition */
    static protected $displayStyles     = array(
        'item:stats:count'                  => array(
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
            'labelCss'      => 'tag',
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
            'options'   => array('item:stats:count',
                                 'item:data:itemName',
                                 'item:data:description:summary',
                                 'item:data:userId:avatar',
                                 'item:data:userId:id'
            )
        ),
        self::STYLE_REGULAR => array(
            'label'     => 'Regular',
            'options'   => array('item:stats:count',
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
            'options'   => array('item:stats:count',
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
            'options'   => array('item:stats:count',
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

    static protected $_initialized  = array();

    /** @brief  Construct a new HTML Bookmarks helper.
     *  @param  config  A configuration array that may include, in addition to
     *                  what our parent accepts:
     *                      - displayStyle      Desired display style
     *                                          (if an array, STYLE_CUSTOM)
     *                                          [ STYLE_REGULAR ];
     *                      - numericGrouping   When sorting numerically, the
     *                                          number of items per group
     *                                          [ 10 ];
     *                      - includeScript     Should Javascript related to
     *                                          bookmark presentation be
     *                                          included?  [ true ];
     */
    public function __construct(array $config = array())
    {
        // Over-ride the default _namespace
        parent::$defaults['namespace'] = 'items';

        // Add extra class-specific defaults
        foreach (self::$defaults as $key => $value)
        {
            $this->_params[$key] = $value;
        }

        parent::__construct($config);
    }

    /** @brief  Configure and retrive this helper instance OR, if no
     *          configuration is provided, perform a render.
     *  @param  config  A configuration array (see populate());
     *
     *  @return A (partially) configured instance of $this OR, if no
     *          configuration is provided, the HTML rendering of the configured
     *          bookmarks.
     */
    public function htmlBookmarks(array $config = array())
    {
        if (! empty($config))
        {
            return $this->populate($config);
        }

        return $this->render();
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

        if ($this->includeScript !== true)
            return $this;

        if (! @isset(self::$_initialized[$namespace]))
        {
            $view       = $this->view;
            $dsConfig   = array(
                                'namespace'     => $namespace,
                                'definition'    => self::$displayStyles,
                                'groups'        => self::$styleGroups
                          );


            // Set / Update our displayOptions namespace.
            if ($this->_displayOptions === null)
            {
                $this->_displayOptions = $view->htmlDisplayOptions($dsConfig);
            }
            else
            {
                $this->_displayOptions->setNamespace($namespace);
            }

            // Include required jQuery
            $config = array('namespace'         => $namespace,
                            'partial'           => 'main',
                            'displayOptions'    => $dsConfig,
                      );

            $call   = "$('#{$namespace}List').bookmarksPane("
                    .               Zend_Json::encode($config) .");";
            $view->jQuery()->addOnLoad($call);
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
    public function setDisplayStyle($style, array $values = null)
    {
        if (is_array($style))
        {
            $values = $style;
            $style  = self::STYLE_CUSTOM;
        }

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

        /*
        Connexions::log('View_Helper_HtmlBookmarks::'
                            . "setDisplayStyle({$style}) == [ "
                            .   $this->_displayOptions->getGroup() ." ]");
        // */
    
        return $this;
    }

    /** @brief  Get the current display style value.
     *
     *  @return The style value (self::STYLE_*).
     */
    public function getDisplayStyle()
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
     *
     *  @return The HTML representation of the user items.
     */
    public function render()
    {
        $paginator    = $this->paginator;
        $viewer       = $this->viewer;

        $html         = "";
        $showMeta     = $this->getShowMeta();

        $uiPagination = $this->view->htmlPaginationControl();
        $uiPagination->setNamespace($this->namespace)
                     ->setPerPageChoices(self::$perPageChoices);

        $html .= "<div id='{$this->namespace}List' class='pane'>"   // List {
              .   $uiPagination->render($paginator, 'paginator-top', true)
              .   $this->_renderDisplayOptions($paginator);

        $nPages = count($paginator);
        if ($nPages > 0)
        {
            /*
            Connexions::log("View_Helper_HtmlBookmarks: "
                            . "render page %d",
                            $paginator->getCurrentPageNumber());
            // */

            //$html .= "<ul class='{$this->namespace}'>";
            $html .= "<ul class='bookmarks'>";

            /* Group by the field identified in $this->sortBy
             *
             * This grouping MAY be a "special field", indicated by the
             * presence of one (or potentially more) ':' characters
             *  (see Model_Mapper_Base::_getSpecialFields())
             *
             * If so, we ASSUME that the final field has been promoted to a
             * pseudo-field of Bookmark.
             */
            $lastGroup  = null;
            $groupBy    = explode(':', $this->sortBy);
            $groupByCnt = count($groupBy);
            $groupByLst = $groupBy[ $groupByCnt - 1];

            /*
            Connexions::log("View_Helper_HtmlBookmarks::render(): "
                            . "sortBy[ %s ], groupBy[ %s ]",
                            $this->sortBy, implode(', ', $groupBy));
            // */

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

                // Retrieve the indicated grouping field value
                $groupVal = $bookmark->{$groupByLst};

                /*
                Connexions::log("View_Helper_HtmlBookmarks::render(): "
                                . "groupBy[ %s ], bookmark[ %s ]",
                                implode(', ', $groupBy),
                                $bookmark->debugDump());
                // */

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
        case self::SORT_BY_RATING_AVERAGE:    // 'ratingAvg'
            $value = floor($value);
            break;

        case self::SORT_BY_RATING_COUNT:      // 'ratingCount'
        case self::SORT_BY_USER_COUNT:        // 'userCount'
            /* We'll do numeric grouping in groups of:
             *      $this->numericGrouping [ 10 ]
             */
            $value = floor($value / $this->numericGrouping) *
                                                    $this->numericGrouping;
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
        case self::SORT_BY_RATING_AVERAGE:    // 'ratingAvg'
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

        /*
        Connexions::log('View_Helper_HtmlBookmarks::_renderDisplayOptions(): '
                        .   '_sortBy[ %s ], sortOrder[ %s ]',
                        $this->sortBy, $this->sortOrder);
        // */

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
