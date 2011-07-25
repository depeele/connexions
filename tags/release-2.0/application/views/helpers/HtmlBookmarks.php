<?php
/** @file
 *
 *  View helper to render a paginated set of User Items / Bookmarks in HTML.
 *
 *  REQUIRES:
 *      application/view/scripts/list.phtml
 *      application/view/scripts/bookmark.phtml
 *
 *      application/view/scripts/list_group.phtml
 *      application/view/scripts/list_groupDate.phtml
 *      application/view/scripts/list_groupAlpha.phtml
 *      application/view/scripts/list_groupNumeric.phtml
 */
class View_Helper_HtmlBookmarks extends View_Helper_Bookmarks
{
    static public   $defaults           = array(
        'namespace'         => 'bookmarks',

        'displayStyle'      => self::STYLE_REGULAR,
        'panePartial'       => 'main',
        'ulCss'             => 'bookmarks', // view/scripts/list.phtml

        // HTML to prepend/append to the inner container.
        'html'              => null,
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
        'item:data:avatar'           => array(
            'label'         => 'avatar',
            'extraPre'      => "<div class='img icon-highlight'><div class='ui-icon ui-icon-person'>&nbsp;</div></div>"
        ),
        'item:data:itemName'                => array(
            'label'         => 'Title',
            'containerEl'   => 'h4',
            'containerCss'  => 'itemName',
        ),
        'item:data:url'                     => 'url',
        'item:data:description:full'        => 'description',
        'item:data:description:summary'     => array(
            'label'         => 'summarized description',
            //'containerPost' => "<br class='clear' />"
        ),
        'item:data:tags'                    => array(
            'label'         => 'tags',
            'extraPost'     => "<label class='tag'>...</label><label class='tag'>...</label><label class='tag'>...</label><label class='tag'>...</label>",
            //'containerPost' => "<br class='clear' />"
        ),
        'item:data:userId:id'               => 'User Id',
        'item:data:dates'                   => array(
            //'containerPost' => "<br class='clear' />"
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
                                 'item:data:avatar',
                                 'item:data:userId:id'
            )
        ),
        self::STYLE_REGULAR => array(
            'label'     => 'Regular',
            'options'   => array('item:stats:count',
                                 'item:stats:rating:stars',
                                 'item:data:itemName',
                                 'item:data:description:summary',
                                 'item:data:avatar',
                                 'item:data:userId:id',
                                 'item:data:tags',
                                 'item:data:dates:tagged',
                                 'item:data:dates:updated',
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
                                 'item:data:avatar',
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
                                 'item:data:avatar',
                                 'item:data:userId:id',
                                 'item:data:tags',
            )
        )
    );

    /** @brief  Set-able parameters. */
    protected       $_displayOptions    = null;

    /** @brief  For renderGroupHeader() */
    protected       $_lastYear          = null;
    protected       $_showParts         = null;

    /** @brief  The view script / partial that should be used to render this
     *          list and the items of this list.
     */
    protected       $_listScript        = 'list.phtml';
    protected       $_itemScript        = 'bookmark.phtml';

    /** @brief  Construct a new HTML Bookmarks helper.
     *  @param  config  A configuration array that may include, in addition to
     *                  what our parent accepts:
     *                      - displayStyle      Desired display style
     *                                          (if an array, STYLE_CUSTOM)
     *                                          [ STYLE_REGULAR ];
     */
    public function __construct(array $config = array())
    {
        // Include defaults for any option that isn't directly set
        foreach (self::$defaults as $key => $value)
        {
            if (! isset($config[$key]))
            {
                $config[$key] = $value;
            }
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
            $rc = $this->populate($config);

            return $rc;
        }

        return $this->render();
    }

    /** @brief  Enforce a non-null display style.
     *  @param  value   The new display style (null === default);
     *
     *  @return $this for a fluent interface.
     */
    public function setDisplayStyle( $value )
    {
        $origVal = $value;
        if ($value === null)
        {
            $value = self::$defaults['displayStyle'];
        }

        /*
        Connexions::log("View_Helper_HtmlBookmarks::setDisplayStyle( %s ) "
                        .   "== [ %s ]",
                        Connexions::varExport($origValue),
                        Connexions::varExport($value));
        // */

        $this->_params['displayStyle'] = $value;

        return $this;
    }

    /** @brief  Retrieve the DisplayOptions helper. */
    public function getDisplayOptions()
    {
        if ( ($this->_displayOptions === null) &&
             ($this->view            !== null) &&
             ($this->showOptions     !== false) )
        {
            $style    = $this->getDisplayStyleName();
            $dsConfig = array(
                            'namespace'  => $this->namespace,
                            'definition' => self::$displayStyles,
                            'groups'     => self::$styleGroups,
                        );

            $this->_displayOptions =
                    $this->view->htmlDisplayOptions($dsConfig);

            /*
            Connexions::log("getDisplayOptions(): config[ %s ]",
                            Connexions::varExport($dsConfig));
            Connexions::log("getDisplayOptions(): style[ %s ]",
                            Connexions::varExport($style));
            Connexions::log("getDisplayOptions(): displayStyle[ %s ]",
                            Connexions::varExport($this->displayStyle));
            // */

            /* Ensure that the current display style is properly reflected
             * in the new display options instance.
             */
            $this->_displayOptions->setGroup( $style,
                                              ($style === self::STYLE_CUSTOM
                                                ? $this->displayStyle
                                                : null) );
        }

        return $this->_displayOptions;
    }

    /** @brief  Retrieve the DisplayOptions configiration data. */
    public function getDisplayOptionsConfig()
    {
        $do = $this->getDisplayOptions();

        return ($do === null
                    ? array()
                    : $do->getConfig());
    }

    /** @brief  Return any special configuration information that should be
     *          passed to the Javascript connexions.itemList widget.
     *
     *  @return An array of configuration information.
     */
    public function getItemListConfig()
    {
        return array();
    }

    /** @brief  Get the name of the current display style value.
     *
     *  @return The style name (self::STYLE_*).
     */
    public function getDisplayStyleName()
    {
        $style = ( ($this->_displayOptions !== null)
                    ? $this->_displayOptions->getGroup()
                    : (is_array($this->displayStyle)
                        ? self::STYLE_CUSTOM
                        : $this->displayStyle) );

        /*
        Connexions::log("View_Helper_HtmlBookmarks::getDisplayStyleName(): "
                        . "_displayOptions %snull, "
                        . "displayStyle[ %s ] == [ %s ]",
                        ($this->_displayOptions !== null ? 'NOT ' : ''),
                        Connexions::varExport($this->displayStyle),
                        Connexions::varExport($style));
        // */

        return $style;
    }

    /** @brief  Get the current showMeta value.
     *
     *  @return The showMeta value (self::SORT_BY_*).
     */
    public function getShowMeta()
    {
        $do = $this->getDisplayOptions();

        if (! $this->multipleUsers)
        {
            /* If we're only showing information for a single user, mark 
             * 'userId' as 'hide' (not true nor false).
             */
            $do->setGroupValue('item:data:avatar',    'hide')
               ->setGroupValue('item:data:userId:id', 'hide');
        }

        $val = $do->getGroupValues();

        /*
        Connexions::log("View_Helper_HtmlBookmarks::"
                            . "getShowMeta(): [ %s ]",
                        Connexions::varExport($val));
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
                            . 'getShowMeta(): return[ %s ]',
                         Connexions::varExport($val));
        // */
    
        return $val;
    }

    /** @brief  Render HTML to represent a Bookmark within this list.
     *  @param  item        The Model_Bookmark instance to render.
     *  @param  params  If provided, parameters to pass to the partial
     *                  [ {namespace, bookmark, viewer} ];
     *
     *  @return The HTML of the rendered bookmark.
     */
    public function renderItem($item, $params = array())
    {
        if ($this->_showParts === null)
        {
            $this->_showParts = $this->getShowMeta();
        }

        $defaults = array(
            'namespace'  => $this->namespace,
            'bookmark'   => $item,
            'viewer'     => $this->viewer,
            'showParts'  => $this->_showParts,
            'sortBy'     => $this->sortBy,
            'tags'       => $this->tags,
        );
        if ( isset($this->view->lastVisitFor))
        {
            $params['lastVisitFor'] = $this->view->lastVisitFor;
        }

        $params = array_merge($defaults, $params);

        return parent::renderItem($item, $params);
    }

    /** @brief  Render the HTML of a group header.
     *  @param  value       The value of this group;
     *  @param  groupBy     The grouping identifier / field (self::SORT_BY_*)
     *                      [ $this->sortBy ];
     *
     *  Typically invoked from within a list-rendering view script.
     *
     *  @return The HTML of a group header.
     */
    public function renderGroupHeader($value, $groupBy = null)
    {
        if ($groupBy === null)
            $groupBy = $this->sortBy;

        $detailsScript       = null;
        $detailsScriptParams = array('helper' => $this,
                                     'value'  => $value);

        switch ($groupBy)
        {
        case self::SORT_BY_DATE_TAGGED:       // 'taggedOn'
        case self::SORT_BY_DATE_UPDATED:      // 'dateUpdated'
            $detailsScript = 'list_groupDate.phtml';
            $detailsScriptParams['lastYear'] = $this->_lastYear;

            // Update '_lastYear'
            list($year, $month, $day) = explode('-', $value);
            if ($year !== $this->_lastYear)
            {
                $this->_lastYear = $year;
            }
            break;
            
        case self::SORT_BY_NAME:              // 'name'
            $detailsScript = 'list_groupAlpha.phtml';
            break;

        case self::SORT_BY_RATING:            // 'rating'
        case self::SORT_BY_RATING_AVERAGE:    // 'ratingAvg'
        case self::SORT_BY_RATING_COUNT:      // 'ratingCount'
        case self::SORT_BY_USER_COUNT:        // 'userCount'
            $detailsScript = 'list_groupNumeric.phtml';
            break;
        }

        return $this->view->partial('list_group.phtml',
                                    array(
                                    'helper'       => $this,
                                    'groupBy'      => $groupBy,
                                    'script'       => $detailsScript,
                                    'scriptParams' => $detailsScriptParams,
                                   ));
    }

    /** @brief  Render the 'displayOptions' control area.
     *  @param  paginator   The current paginator (so we know the number of 
     *                                             items per page);
     *
     *
     *  @return A string of HTML.
     */
    public function renderDisplayOptions($paginator = null)
    {
        if ($paginator === null)
            $paginator =& $this->paginator;

        $do               = $this->getDisplayOptions();
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
        Connexions::log('View_Helper_HtmlBookmarks::renderDisplayOptions(): '
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

        $do->addFormField('sortBy', $html);


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

        $do->addFormField('sortOrder', $html);

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

        $do->addFormField('perPage', $html);

        /* _displayOptions->render will use the previously added fields, along
         * with the available display styles to render the complete display
         * options form.
         */
        return $do->render();
    }
}
