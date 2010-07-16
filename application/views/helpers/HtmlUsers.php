<?php
/** @file
 *
 *  View helper to render a paginated set of Users in HTML.
 */
class View_Helper_HtmlUsers extends View_Helper_Users
{
    static public   $defaults               = array(
        'displayStyle'      => self::STYLE_REGULAR,
        'includeScript'     => true,
    );


    const STYLE_REGULAR                 = 'regular';
    const STYLE_FULL                    = 'full';
    const STYLE_CUSTOM                  = 'custom';

    /** @brief  Display style definition */
    static protected $displayStyles     = array(
        'user:stats'                        => array(
            'containerCss'  => 'ui-corner-bottom'
        ),
        'user:stats:countItems'             => 'item count',
        'user:stats:countTags'              => 'tag count',
        'user:data:avatar'                  => array(
            'label'         => 'avatar',
            'containerTitle'=> 'avatar',
            'extraPre'      => "<div class='img icon-highlight'><div class='ui-icon ui-icon-person'>&nbsp;</div></div>"
        ),
        'user:data:userId'                  => array(
            'label'         => 'User Id',
            'containerEl'   => 'h4'
        ),
        'user:data:fullName'                => 'Full Name',
        'user:data:email'                   => array(
            'label'         => 'email@ddress',
            'extraPre'      => "<div class='img icon-highlight'><div class='ui-icon ui-icon-mail-closed'>&nbsp;</div></div>"
        ),
        'user:data:email'                   => 'email@',
        'user:data:tags'                    => array(
            'label'         => 'Top tags',
            'extraPost'     => "<label class='tag'>...</label><label class='tag'>...</label><label class='tag'>...</label><label class='tag'>...</label>"
        ),
        'user:data:dates'                   => array(
            'containerPost' => "<br class='clear' />"
        ),
        'user:data:dates:lastVisit'         => 'date:Last Visited'
    );


    /** @brief  Pre-defined style groups. */
    static public   $styleGroups    = array(
        self::STYLE_REGULAR => array(
            'label'     => 'Regular',
            'options'   => array('user:stats:countItems',
                                 'user:stats:countTags',
                                 'user:data:avatar',
                                 'user:data:userId',
                                 'user:data:fullName',
                                 'user:data:email'
            )
        ),
        self::STYLE_FULL    => array(
            'label'     => 'Full',
            'options'   => array('user:stats:countItems',
                                 'user:stats:countTags',
                                 'user:data:avatar',
                                 'user:data:userId',
                                 'user:data:fullName',
                                 'user:data:email',
                                 'user:data:tags',
                                 'user:data:dates:lastVisit'
            )
        ),
        self::STYLE_CUSTOM  => array(
            'label'     => 'Custom',
            'isCustom'  => true,
            'options'   => array('user:stats:countItems',
                                 'user:stats:countTags',
                                 'user:data:avatar',
                                 'user:data:userId',
                                 'user:data:fullName',
                                 'user:data:email',
                                 'user:data:tags'
            )
        )
    );

    static protected $_initialized  = array();

    /** @brief  Set-able parameters. */
    protected       $_displayOptions    = null;

    /** @brief  Construct a new HTML Users helper.
     *  @param  config  A configuration array that may include, in addition to
     *                  what our parent accepts:
     *                      - displayStyle      Desired display style
     *                                          (if an array, STYLE_CUSTOM)
     *                                          [ STYLE_REGULAR ];
     *                      - includeScript     Should Javascript related to
     *                                          bookmark presentation be
     *                                          included?  [ true ];
     */
    public function __construct(array $config = array())
    {
        // Over-ride the default namespace
        parent::$defaults['namespace'] = 'users';

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
     *          users.
     */
    public function htmlUsers(array $config = array())
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
     *  @return View_Helper_HtmlUsers for a fluent interface.
     */
    public function setNamespace($namespace)
    {
        /*
        Connexions::log("View_Helper_HtmlUsers::"
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
                            /* Rely on the CSS class of rendered items
                             * (see View_Helper_HtmlUsersUser)
                             * to determine their Javascript objClass
                            'uiOpts'            => array(
                                'objClass'      => 'user',
                            ),
                             */
                      );

            $call   = "$('#{$namespace}List').itemsPane("
                    .               Zend_Json::encode($config) .");";
            $view->jQuery()->addOnLoad($call);
        }

        return $this;
    }

    /** @brief  Set the current style.
     *  @param  style   A style value (self::STYLE_*)
     *  @param  values  If provided, an array of field values for this style.
     *
     *  @return View_Helper_HtmlUsers for a fluent interface.
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
        Connexions::log('View_Helper_HtmlUsers::'
                            . "setDisplayStyle({$style}) == [ "
                            .   $this->_displayOptions->getGroup() ." ]");
        // */
    
        return $this;
    }

    /** @brief  Get the current style value.
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
        $val = $this->_displayOptions->getGroupValues();

        if (! @is_bool($val['minimized']))
        {
            /* Include additional meta information:
             *      minimized
             */
            $val['minimized'] =
                   (($val['user:data:dates:lastVisit'] === false) );
        }

        /*
        Connexions::log('View_Helper_HtmlUsers::'
                            . 'getShowMeta(): return[ '
                            .       print_r($val, true) .' ]');
        // */
    
        return $val;
    }

    /** @brief  Render an HTML version of a paginated set of Users.
     *
     *  @return The HTML representation of the users.
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
            //$html .= "<ul class='items {$this->namespace}'>";
            $html .= "<ul class='items users'>";

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

            foreach ($paginator as $idex => $user)
            {
                if ($user === null)
                {
                    /* Paginator items that aren't avaialble (i.e. beyond the
                     * end of the paginated set) are returned as null.
                     * Therefore, the first null item indicates end-of-set.
                     */
                    break;
                }

                // Retrieve the indicated grouping field value
                $groupVal = $user->{$this->sortBy};
                $newGroup = $this->_groupValue($this->sortBy, $groupVal);

                if ($newGroup !== $lastGroup)
                {
                    $html      .= $this->_renderGroupHeader($this->sortBy,
                                                            $newGroup);
                    $lastGroup  = $newGroup;
                }

                $html .= $this->view->htmlUsersUser($user,
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
        case self::SORT_BY_DATE_VISITED:      // 'lastVisit'
            /* Dates are strings of the form YYYY-MM-DD HH:MM:SS
             *
             * Grouping should be by year:month:day, so strip off the time.
             */
            $value = substr($value, 0, 10);
            break;
            
        case self::SORT_BY_NAME:              // 'name'
        case self::SORT_BY_FULLNAME:          // 'fullName'
        case self::SORT_BY_EMAIL:             // 'email'
            $value = strtoupper(substr($value, 0, 1));
            break;

        case self::SORT_BY_TAG_COUNT:         // 'totalTags'
        case self::SORT_BY_ITEM_COUNT:        // 'totalItems'
            /* We'll do numeric grouping in groups of:
             *      $this->numericGrouping [ 10 ]
             */
            $value = floor($value / $this->numericGrouping) *
                                                    $this->numericGrouping;
            break;
        }

        /*
        Connexions::log("HtmlUsers::_groupValue(%s, %s:%s) == [ %s ]",
                        $groupBy, $orig, gettype($orig),
                        $value);
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
        case self::SORT_BY_DATE_VISITED:      // 'lastVisit'
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
        case self::SORT_BY_FULLNAME:          // 'fullName'
        case self::SORT_BY_EMAIL:             // 'email'
            $html .= "<div class='groupType alpha'>"
                  .   $value
                  .  "</div>";
            break;

        case self::SORT_BY_TAG_COUNT:         // 'totalTags'
        case self::SORT_BY_ITEM_COUNT:        // 'totalItems'
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
