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
        'pageBaseUrl'       => null,        /* The base URL of the containing
                                             * page used to set the cookie path
                                             * for the attached Javascript
                                             * 'cloudPane' which, in turn,
                                             * effects the cookie path passed
                                             * to the contained 'dropdownForm'
                                             * presneting Display Options.
                                             */

        'displayStyle'      => self::STYLE_REGULAR,
        'includeScript'     => true,
        'ulCss'             => 'bookmarks', // view/scripts/list.phtml
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

    static protected $_initialized  = array();

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
     *                      - includeScript     Should Javascript related to
     *                                          bookmark presentation be
     *                                          included?  [ true ];
     */
    public function __construct(array $config = array())
    {
        // Over-ride the default _namespace
        parent::$defaults['namespace'] = 'bookmarks';   //'items';

        // Add extra class-specific defaults
        foreach (self::$defaults as $key => $value)
        {
            $this->_params[$key] = $value;
        }

        parent::__construct($config);
    }

    /** @brief  Over-ride to ensure that if incoming configuration has BOTH
     *          'namespace' AND 'pageBaseUrl', the 'pageBaseUrl' is set first
     *          (since setNamespace() makes use of it).
     *  @param  config  A configuration array that may include:
     *
     *  @return $this for a fluent interface.
     */
    public function populate(array $config)
    {
        // Ensure that 'pageBaseUrl' is set FIRST
        foreach (array('pageBaseUrl') as $key)
        {
            if (isset($config[$key]))
            {
                /*
                Connexions::log("View_Helper_HtmlBookmarks::populate(): "
                                . "set 'pageBaseUrl' first");
                // */

                $this->__set($key, $config[$key]);
            }
        }

        return parent::populate($config);
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
        /*
        Connexions::log("View_Helper_HtmlBookmarks::htmlBookmarks( %s )",
                        Connexions::varExport($config));
        // */

        if (! empty($config))
        {
            $rc = $this->populate($config);

            return $rc;
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
                        .   "setNamespace( %s ): includeScript[ %s ]",
                        $namespace,
                        Connexions::varExport($this->includeScript));
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

            if ($this->pageBaseUrl !== null)
            {
                $dsConfig['cookiePath'] = rtrim($this->pageBaseUrl, '/');
            }


            /*
            Connexions::log("View_Helper_HtmlBookmarks::setNamespace(): "
                            . "new namespace: config[ %s ]",
                            Connexions::varExport($dsConfig));
            // */

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
                             * (see View_Helper_HtmlBookmark)
                             * to determine their Javascript objClass
                            'uiOpts'            => array(
                                'objClass'      => 'bookmark',
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
     *  @param  style   A style value (self::STYLE_*) -- if an array if
     *                  provided, it will be used as 'values' and the style
     *                  will be set to self::STYLE_CUSTOM;
     *  @param  values  If provided, an array of field values for this style.
     *
     *  @return View_Helper_HtmlBookmarks for a fluent interface.
     */
    public function setDisplayStyle($style, array $values = null)
    {
        /*
        Connexions::log("View_Helper_HtmlBookmarks::setDisplayStyle(): "
                        . "_displayOptions is %snull, "
                        . "style[ %s ], values[ %s ]",
                        ($this->_displayOptions === null ? '' : 'NOT '),
                        print_r($style, true), print_r($values, true));
        // */

        if ($this->_displayOptions !== null)
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
    
        }

        return $this;
    }

    /** @brief  Get the current display style value.
     *
     *  @return The style value (self::STYLE_*).
     */
    public function getDisplayStyle()
    {
        return ($this->_displayOptions
                    ? $this->_displayOptions->getGroup()
                    : self::$defaults['displayStyle']);
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
                    ->setGroupValue('item:data:avatar',    'hide')
                    ->setGroupValue('item:data:userId:id', 'hide');
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

    /** @brief  Render an HTML version of a paginated set of bookmarks.
     *
     *  @return The HTML representation of the bookmarks.
     */
    public function render()
    {
        $this->_showParts = $this->getShowMeta();

        return parent::render();
    }

    /** @brief  Render HTML to represent a Bookmark within this list.
     *  @param  item        The Model_Bookmark instance to render.
     *
     *  @return The HTML of the rendered bookmark.
     */
    public function renderItem($item)
    {
        /*
        Connexions::log("View_Helper_HtmlBookmarks::renderItem(): "
                        . "item[ %s ], showParts[ %s ]",
                        $item, Connexions::varExport($this->_showParts));
        // */

        return parent::renderItem($item,
                                  array(
                                    'namespace'  => $this->namespace,
                                    'bookmark'   => $item,
                                    'viewer'     => $this->viewer,
                                    'showParts'  => $this->_showParts,
                                    'sortBy'     => $this->sortBy,
                                    'tags'       => $this->tags,
                                  ));
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
