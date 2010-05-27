<?php
/** @file
 *
 *  View helper to render an Item Cloud, possibly paginated, in HTML.
 *
 */
class View_Helper_HtmlItemCloud extends Zend_View_Helper_Abstract
{
    static public   $perPageChoices         = array(50, 100, 250, 500);
    static public   $highlightCountChoices  = array(0,  5,   10);

    static public   $defaults               = array(
        'namespace'         => 'tags',
        'showRelation'      => true,
        'itemType'          => self::ITEM_TYPE_TAG,
        'items'             => null,        /* A Connexions_Model_Set
                                             * containing the items to present
                                             */
        'selected'          => null,        /* A Connexions_Model_Set, that
                                             * SHOULD be a sub-set of 'items',
                                             * containing those items that are
                                             * currently selected.
                                             */

        'itemBaseUrl'       => null,        /* The base url to use for
                                             * completed items
                                             */

        'weightName'        => null,        /* The name of the field/member
                                             * to use for weight.
                                             */


        'sortBy'            => self::SORT_BY_TITLE,
        'sortOrder'         => Connexions_Service::SORT_DIR_ASC,

        'page'              => 1,
        'perPage'           => 100,
        'highlightCount'    => 5,

        'displayStyle'      => self::STYLE_CLOUD,
    );

    /** @brief  Cloud Item type -- determines the item decorator
     */
    const ITEM_TYPE_TAG                     = 'tag';
    const ITEM_TYPE_USER                    = 'user';
    const ITEM_TYPE_ITEM                    = 'item';

    /** @brief  Cloud Presentation style. */
    const STYLE_LIST                        = 'list';
    const STYLE_CLOUD                       = 'cloud';

    /** @brief  Pre-defined style "groups". */
    static protected $styleGroups       = array(
                        self::STYLE_LIST    => array(
                            'label'     => 'List',
                            'options'   => array()
                        ),
                        self::STYLE_CLOUD   => array(
                            'label'     => 'Cloud',
                            'options'   => array()
                        )
                    );

    /** @brief  Cloud item sorting. */
    const SORT_BY_TITLE     = 'title';
    const SORT_BY_WEIGHT    = 'weight';

    static public   $sortTitles     = array(
                        self::SORT_BY_TITLE     => 'Name',
                        self::SORT_BY_WEIGHT    => 'Weight'
                    );

    static public   $orderTitles    = array(
                        Connexions_Service::SORT_DIR_ASC    => 'Ascending',
                        Connexions_Service::SORT_DIR_DESC   => 'Descending',
                );


    /** @brief  Set-able parameters -- initialized from self::$defaults in
     *          __construct().
     */
    protected       $_params            = array();

    protected       $_displayOptions    = null;
    protected       $_hiddenItems       = array();

    static protected $_initialized      = array();

    /** @brief  Construct a new Bookmarks helper.
     *  @param  config  A configuration array (see populate());
     */
    public function __construct(array $config = array())
    {
        //Connexions::log("View_Helper_Bookmarks::__construct()");

        foreach (self::$defaults as $key => $value)
        {
            $this->_params[$key] = $value;
        }

        if (! empty($config))
            $this->populate($config);
    }


    /** @brief  Configure and retrive this helper instance.
     *  @param  config  A configuration array (see populate());
     *
     *  @return A (partially) configured instance of $this.
     */
    public function htmlItemCloud(array $config = array())
    {
        if (! empty($config))
        {
            $this->populate($config);
        }

        return $this;
    }

    /** @brief  Given an array of configuration data, populate the parameter of
     *          this instance.
     *  @param  config  A configuration array that may include:
     *                      namespace       [tags];
     *                      showRelation    [true];
     *                      itemType        [self::ITEM_TYPE_TAG];
     *                      items           The Connexions_Model_Set containing
     *                                      the items to present;
     *                      selected        The Connexions_Model_Set containing
     *                                      the items that are currently
     *                                      selected;
     *                      itemBaseUrl     [null];
     *                      sortBy          [self::SORT_BY_TITLE];
     *                      sortOrder       [Connexions_Service::SORT_DIR_ASC];
     *                      page            [1];
     *                      perPage         [50];
     *                      highlightCount  [5];
     *                      displayStyle    [self::STYLE_CLOUD];
     *
     *  @return $this for a fluent interface.
     */
    public function populate(array $config)
    {
        foreach ($config as $key => $value)
        {
            $this->__set($key, $value);
            //$this->_params[$key] = $value;
        }

        /*
        Connexions::log("View_Helper_HtmlSidebar::populate(): params[ %s ]",
                        print_r($this->_params, true));

        // */

        return $this;
    }

    public function __set($key, $value)
    {
        $method = 'set'. ucfirst($key);
        if (method_exists($this, $method))
        {
            $this->{$method}($value);
        }
        else
        {
            $this->_params[$key] = $value;
        }
    }

    public function __get($key)
    {
        return (isset($this->_params[$key])
                    ? $this->_params[$key]
                    : null);
    }

    /** @brief  A sort callback for ordering the items to be rendered.
     *  @param  item1   A Connexions_Model instance
     *  @param  item2   A Connexions_Model instance
     *
     *  @return A comparison value ( -1 < ; 0 == ; +1 > )
     */
    public function sortCb($item1, $item2)
    {
        $field = $this->sortBy;
        $dir   = $this->sortOrder;

        if ($field === 'weight')
            $res = $item1->getWeight() - $item2->getWeight();
        else
            $res = strcasecmp($item1->getTitle(), $item2->getTitle());

        if ($dir !== Connexions_Service::SORT_DIR_ASC)
            // Reverse the order
            $res = -$res;

        return $res;
    }

    /** @brief  Render an HTML version of an item cloud.
     *
     *  @return The HTML representation of an item cloud.
     */
    public function render()
    {
        /*
        Connexions::log("View_Helper_HtmlItemCloud::render(): "
                        .   "namespace[ %s ], %d items, "
                        .   "sortBy[ %s ], sortOrder[ %s ], style[ %s ]",
                        $this->namespace,
                        $this->items->count(),
                        $this->sortBy, $this->sortOrder,
                        $this->getDisplayStyle());
        // */


        // (Re)sort the items according to the requested ordering
        $this->items->usort( array($this, 'sortCb') );

        /*
        Connexions::log("View_Helper_HtmlItemCloud::render(): sort[ %s, %s ]",
                        $this->sortBy, $this->sortOrder);
        foreach ($this->items as $idex => $item)
        {
            Connexions::log("  %2d: %4d '%s'",
                            $idex, $item->getWeight(), $item->getTitle());
        }
        // */


        $html = "<div id='{$this->itemType}Items'>";  // <itemType>Items {

        if ($this->showRelation)
        {
            $html .= "<div class='cloudRelation "
                  .              "connexions_sprites relation_ltr'>"
                  .   "&nbsp;"
                  .  "</div>";
        }

        $uiPagination = null;
        if ($this->items instanceof Zend_Paginator)
        {
            /* Present the top pagination control.
             * Default values are established via
             *      Bootstrap.php::_initViewGlobal().
             */
            $uiPagination = $this->view->htmlPaginationControl();
            $uiPagination->setNamespace($this->namespace);

            $html .= $uiPagination->render($this->items,
                                           'pagination-top', true);
        }

        if ($hideOptions !== true)
        {
            $html .= $this->_renderDisplayOptions()
                  .  "<br class='clear' />";
        }

        /*****************************************************************
         * We now need a Zend_Tag_ItemList adapter for our item set
         *
         */
        $itemList = new Connexions_Model_Set_Adapter_ItemList(
                                    $this->items,
                                    $this->selected,
                                    $this->itemBaseUrl,
                                    $this->weightName);

        if (! empty($this->_hiddenItems))
        {
            // Remove any tags that are to be hidden
            /*
            Connexions::log("View_Helper_HtmlItemCloud:: "
                                . "filter out tags [ "
                                .   implode(", ", $this->_hiddenItems) ." ]");
            // */

            foreach ($itemList as $key => $item)
            {
                if (in_array($item->getTitle(), $this->_hiddenItems))
                {
                    /*
                    Connexions::log("View_Helper_HtmlItemCloud:: "
                                    . "remove tag [{$item->getTitle()}]");
                    // */

                    unset($itemList[$key]);
                }
            }
        }

        $sortedList = $this->_sort($itemList, $this->sortBy,
                                              $this->sortOrder);

        if ($this->getDisplayStyle() === self::STYLE_CLOUD)
        {
            // Zend_Tag_Cloud configuration
            $cloudConfig = array(
                // Make helpers in 'application/views/helpers' available.
                'prefixPath'            => array(
                    'prefix'    => 'View_Helper',
                    'path'      => APPLICATION_PATH .'/views/helpers/'
                 ),
                'ItemList'              => $sortedList,
                /* Use the default cloud decorator:
                 *      Zend_Tag_Cloud_Decorator_HtmlItemCloud
                 */
                'CloudDecorator'        => array(
                    'decorator'         => 'htmlCloud',
                    'options'           => array(
                        'HtmlTags'      => array(
                            'ul'        => array(
                                'class' =>'Tag_Cloud'
                            )
                        )
                    )
                )
            );

            switch ($this->itemType)
            {
            case self::ITEM_TYPE_TAG:
                /* Use our cloud item decorator:
                 *      View_Helper_HtmlItemCloudTag
                 */
                $cloudConfig['TagDecorator'] = array(
                    'decorator'         => 'htmlItemCloudTag',
                    'options'           => array(
                        'HtmlTags'      => array(
                            'li'        => array(
                                'class'=>'tag'
                            )
                        ),
                        'ClassList'     => array(
                            'size0', 'size1', 'size2', 'size3',
                            'size4', 'size5', 'size6'
                        )
                    )
                );
                break;

            case self::ITEM_TYPE_USER:
                /* Use our cloud item decorator:
                 *      View_Helper_HtmlItemCloudUser
                 */
                $cloudConfig['TagDecorator'] = array(
                    'decorator'         => 'htmlItemCloudUser',
                    'options'           => array(
                        'HtmlTags'      => array(
                            'li'        => array(
                                'class'=>'user'
                            )
                        ),
                        'ClassList'     => array(
                            'size0', 'size1', 'size2', 'size3',
                            'size4', 'size5', 'size6'
                        )
                    )
                );
                break;

            case self::ITEM_TYPE_ITEM:
                /* Use our cloud item decorator:
                 *      View_Helper_HtmlItemCloudItem
                 */
                $cloudConfig['TagDecorator'] = array(
                    'decorator'         => 'htmlItemCloudItem',
                    'options'           => array(
                        'HtmlTags'      => array(
                            'li'        => array(
                                'class'=>'user'
                            )
                        ),
                        'ClassList'     => array(
                            'size0', 'size1', 'size2', 'size3',
                            'size4', 'size5', 'size6'
                        )
                    )
                );
                break;
            }


            // Create a Zend_Tag_Cloud renderer (by default, renders HTML)
            $cloud = new Zend_Tag_Cloud( $cloudConfig );

            // Render the HTML for a cloud
            $html .= "<div class='cloud'>"
                  .   ($this->highlightCount > 0
                            ? $this->_renderHighlights( $itemList )
                            : '')
                  .   $cloud->render()
                  .  "<br class='clear' />"
                  .  "</div>";
        }
        else
        {
            // Render the HTML for a list
            $html .= "<div class='cloud'>"
                  /* Showing top doesn't make sense when presenting a list...
                  .   ($this->highlightCount > 0
                            ? $this->_renderHighlights( $itemList )
                            : '')
                  */
                  .   $this->_renderList( $sortedList )
                  .   "<br class='clear' />"
                  .  "</div>";
        }

        if ($uiPagination !== null)
        {
            // Present the bottom pagination control.
            $html .= $uiPagination->render($this->items);
        }

        $html .= "</div>";  // <itemType>Items }

        // Return the rendered HTML
        return $html;
    }

    /** @brief  Set the namespace, primarily for forms and cookies.
     *  @param  namespace   The new namespace.
     *
     *  @return $this for a fluent interface.
     */
    public function setNamespace($namespace)
    {
        $this->_params['namespace'] = $namespace;

        Connexions::log("View_Helper_HtmlItemCloud::setNamespace( %s )",
                        $namespace);

        $view   = $this->view;
        $jQuery = $view->jQuery();

        if (! @isset(self::$_initialized['__global__']))
        {
            Connexions::log("View_Helper_HtmlItemCloud::setNamespace( %s ): "
                            .   "include init_CloudOptions()",
                            $namespace);

            $jQuery->javascriptCaptureStart();
                // {
            ?>

/************************************************
 * Initialize cloud display options
 *
 */
function init_CloudOptions(namespace)
{
    var $cloudOptions = $('.'+ namespace +'-displayOptions');
    if ( $cloudOptions.length > 0 )
    {
        /* On Display Style change, toggle the state of 'highlightCount'
         *
         * Note: The ui.optionsGroup widget is established, and attached to the
         *       ui-optionsGroup DOM element,  by ui.dropdownForm when it is
         *       initialized.  When the options change, ui.optionsGroup will
         *       trigger the 'change' event on the displayOptions form with
         *       information about the selected display group.
         */
        var form    = $cloudOptions.find('form');

        form.bind('change.optionGroup',
                  function(e, info) {
                    var $field  = $(this).find('.field.highlightCount');

                    /*
                    $.log("change.optionGroup: group[ "+ info.group +" ], "
                          + $field.length +' fields');
                    // */

                    if (info.group === 'cloud')
                    {
                        // enable the 'highlightCount'
                        $field.removeClass('ui-state-disabled');
                        $field.find('select').removeAttr('disabled');
                    }
                    else
                    {
                        // disable the 'highlightCount'
                        $field.addClass('ui-state-disabled');
                        $field.find('select').attr('disabled', true);
                    }

                  });
    }

    return;
}

            <?php
                // }
            $jQuery->javascriptCaptureEnd();

            self::$_initialized['__global__'] = true;
        }

        if (! @isset(self::$_initialized[$namespace]))
        {
            // Set / Update our displayOptions namespace.
            if ($this->_displayOptions === null)
            {
                $dsConfig = array(
                                'namespace' => $namespace,
                                'groups'    => self::$styleGroups,
                            );

                $this->_displayOptions = $view->htmlDisplayOptions($dsConfig);
            }
            else
            {
                $this->_displayOptions->setNamespace($namespace);
            }

            $jQuery->addOnLoad("init_CloudOptions('{$namespace}');");

            self::$_initialized[$namespace] = true;
        }

        return $this;
    }

    /** @brief  Establish the item set to present.
     *  @param  items       A Connexions_Model_Set | Zend_Paginator instance
     *                      representing the items to be presented;
     *
     *  @return $this for a fluent interface.
     */
    public function setItems($items)
    {
        if ($items instanceof Zend_Paginator)
        {
            /* We've been given a partially initiallzed paginator.
             *
             * Make sure the 'perPage' setting matches ours.
             */
            $items->setItemCountPerPage( $this->perPage );
            $items->setCurrentPageNumber($this->page );
        }
        else if (! $items instanceof Connexions_Model_Set)
        {
            throw new Exception("Invalid class[ ". get_class($items) ." ]");
        }

        $this->_params['items'] = $items;

        return $this;
    }

    /** @brief  Set the cloud item type.
     *  @param  itemType    A item type value (self::ITEM_TYPE_*)
     *
     *  @return $this for a fluent interface.
     */
    public function setItemType($itemType)
    {
        $orig = $itemType;

        switch ($itemType)
        {
        case self::ITEM_TYPE_TAG:
        case self::ITEM_TYPE_USER:
        case self::ITEM_TYPE_ITEM:
            break;

        default:
            $itemType = self::$defaults['itemType'];
            break;
        }

        /*
        Connexions::log('View_Helper_HtmlItemCloud::'
                            . "setType({$orig}) == [ {$itemType} ]");
        // */
    
        $this->_params['itemType'] = $itemType;

        return $this;
    }

    /** @brief  Set the current style.
     *  @param  style   A style value (self::STYLE_*)
     *  @param  values  If provided, an array of field values for this style.
     *
     *  @return $this for a fluent interface.
     */
    public function setDisplayStyle($style, array $values = null)
    {
        $reqStyle = $style;
        if ($values !== null)
        {
            $this->_displayOptions->setGroupValues($values);
        }
        else
        {
            switch ($style)
            {
            case self::STYLE_LIST:
            case self::STYLE_CLOUD:
                break;

            default:
                $style = self::$defaults['displayStyle'];
                break;
            }

            $this->_displayOptions->setGroup($style);
        }

        // /*
        Connexions::log('View_Helper_HtmlItemCloud::'
                            . "setDisplayStyle({$reqStyle}) == [ "
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

    /** @brief  Set the current sortBy.
     *  @param  sortBy  A sortBy value (self::SORT_BY_*)
     *
     *  @return $this for a fluent interface.
     */
    public function setSortBy($sortBy)
    {
        $orig = $sortBy;

        switch ($sortBy)
        {
        case self::SORT_BY_TITLE:
        case self::SORT_BY_WEIGHT:
            break;

        default:
            $sortBy = self::$defaults['sortBy'];
            break;
        }

        /*
        Connexions::log('View_Helper_HtmlItemCloud::'
                            . "setSortBy({$orig}) == [ {$sortBy} ]");
        // */

        $this->_params['sortBy'] = $sortBy;

        return $this;
    }

    /** @brief  Set the current sortOrder.
     *  @param  sortOrder   A sortOrder value (Connexions_Service::SORT_DIR_*)
     *
     *  @return $this for a fluent interface.
     */
    public function setSortOrder($sortOrder)
    {
        $orig = $sortOrder;

        $sortOrder = strtoupper($sortOrder);
        switch ($sortOrder)
        {
        case Connexions_Service::SORT_DIR_ASC:
        case Connexions_Service::SORT_DIR_DESC:
            break;

        default:
            $sortOrder = self::$defaults['sortOrder'];
            break;
        }

        /*
        Connexions::log('View_Helper_HtmlItemCloud::'
                            . "setSortOrder({$orig}) == [ {$sortOrder} ]");
        // */
    
        $this->_params['sortOrder'] = $sortOrder;

        return $this;
    }

    /** @brief  Set the number of items per-page.
     *  @param  perPage The number of items per page
                                (self::$perPageChoices).
     *
     *  @return $this for a fluent interface.
     */
    public function setPerPage($perPage)
    {
        if (($perPage !== null) &&
            in_array($perPage, self::$perPageChoices))
        {
            $this->_params['perPage'] = $perPage;
        }
        else
        {
            // Default
            $this->_params['perPage'] = self::$defaults['perPage'];
        }

        return $this;
    }

    /** @brief  Set the number of items to highlight
     *  @param  highlightCount  The number of items to highlight
     *                          (self::$highlightCountChoices).
     *
     *  @return $this for a fluent interface.
     */
    public function setHighlightCount($highlightCount)
    {
        if (($highlightCount !== null) &&
            in_array($highlightCount, self::$highlightCountChoices))
        {
            $this->_params['highlightCount'] = $highlightCount;
        }
        else
        {
            // Default
            $this->_params['highlightCount'] =
                                self::$defaults['highlightCount'];
        }

        return $this;
    }

    /** @brief  Add a tag that should NOT be included in the context URL.
     *  @param  str     The tag (name).
     *
     *  @return $this for a fluent interface.
     */
    public function addHiddenItem($str)
    {
        array_push($this->_hiddenItems, $str);
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Sort our tags, if needed.
     *  @param  itemList    A Connexions_Model_Set_Adapter_ItemList instance;
     *  @param  sortBy      The field to sort by ( self::SORT_BY_* );
     *  @param  sortOrder   Sort order ( Connexions_Service::SORT_DIR_* );
     *
     *  @return The sorted Connexions_Model_Set_Adapter_ItemList
     */
    protected function _sort(Connexions_Model_Set_Adapter_ItemList  $itemList,
                             $sortBy, $sortOrder)
    {
        /*
        Connexions::log("HtmlItemCloud: _sort: ". count($itemList) ." items, "
                            . "sort[ {$sortBy}, {$sortOrder} ]");

        foreach($itemList as $key => $item)
        {
            Connexions::log("HtmlItemCloud: _sort: %s: "
                            . "(%s) title [ %s ], weight[ %d ]",
                            $key,
                            get_class($item),
                            $item->getTitle(),
                            $item->getWeight() );
        }
        // */

        /* In setItemSet(), the chosen sort order MAY be over-ridden to ensure
         * that we're presenting the most weighty portion of the cloud.
         *
         * This will occur if the incoming set is a Connexions_Set instance AND
         * sortBy !== SORT_BY_WEIGHT.
         *
         * So, here, the sort order of the incoming itemSet is:
         *      - 'weight DESC'
         *          - if ((this->items instanceof  Connexions_Set) &&
         *                (displayStyle     === self::STYLE_CLOUD)    &&
         *                (this->sortBy    !== self::SORT_BY_WEIGHT))
         *          - OR ((this->sortBy    === self::SORT_BY_WEIGHT) &&
         *                (this->sortOrder === 
         *                              Connexions_Service::SORT_DIR_DESC))
         *
         * We ALSO don't need to sort if the requested order is 'weight DESC'
         * Now, we need to apply the chosen sort on the current sub-set of
         * items.
        if (($this->items instanceof Connexions_Set)                &&
            ($this->_displayOptions->getGroup() === self::STYLE_CLOUD) &&
            ($this->sortBy                     !== self::SORT_BY_WEIGHT) )
        {
            $curSortBy    = self::SORT_BY_WEIGHT;
            $curSortOrder = Connexions_Service::SORT_DIR_DESC;
        }
        else
         */
        {
            $curSortBy    = $this->sortBy;
            $curSortOrder = $this->sortOrder;
        }

        if (($sortBy    === $curSortBy) &&
            ($sortOrder === $curSortOrder))
        {
            // The incoming list should ALREADY be properly sorted.
            return $itemList;
        }

        /**********************************************************************
         * Re-sort the list
         *
         */
        if ($sortBy === self::SORT_BY_TITLE)
        {
            $val = 'getTitle()';
            $cmp = 'strcasecmp($aVal, $bVal)';

            if ($sortOrder === Connexions_Service::SORT_DIR_DESC)
                // Reverse sort
                $cmp = '(0 - '. $cmp .')';
        }
        else
        {
            $val = 'getWeight()';

            if ($sortOrder === Connexions_Service::SORT_DIR_DESC)
                // Reverse sort (Descending)
                $cmp = '($bVal - $aVal)';
            else
                $cmp = '($aVal - $bVal)';
        }

        // Create function to reverse sort by weight.
        $sortFn = create_function('$a,$b',   '$aVal = $a->'. $val .';'
                                           . '$bVal = $b->'. $val .';'
                                           . 'return '. $cmp .';');

        // Clone and sort the item list
        $itemList = clone $itemList;

        $itemList->uasort($sortFn);

        /*
        Connexions::log("HtmlItemCloud: _sort: ----------------------- "
                            . count($itemList) ." items, "
                            . "sorted [ {$sortBy}, {$sortOrder} ]");
        foreach($itemList as $key => $item)
        {
            Connexions::log("HtmlItemCloud: _sort: %-3s: "
                            . "title [ %-15s ], weight[ %d ]",
                            $key,
                            $item->getTitle(),
                            $item->getWeight() );
        }
        // */
        return $itemList;
    }

    /** @brief  Render an item list.
     *  @param  itemList    A Connexions_Model_Set_Adapter_ItemList instance
     *                      representing the items to be presented;
     *
     *
     *  @return A string of HTML.
     */
    protected function _renderList(Connexions_Model_Set_Adapter_ItemList
                                                                    $itemList)
    {
        $html .= "<ul>";

        foreach ($itemList as $idex => $item)
        {
            $html .= "<li>";

            $url    = $item->getParam('url');
            $weight = number_format($item->getWeight());
            if (empty($url))
                $html .= sprintf("<span class='item'>%s</span>",
                                    htmlSpecialChars($item->getTitle()));
            else
                $html .= sprintf('<a class="item" href="%s">%s</a>',
                                    htmlSpecialChars($url),
                                    htmlSpecialChars($item->getTitle()));

            $html .=  "<span class='itemCount'>{$weight}</span>"
                  .   "<br class='clear' />"
                  .  "</li>";
        }

        $html .= "</ul>";

        return $html;
    }

    /** @brief  Render the top items (by count).
     *  @param  itemList    A Connexions_Model_Set_Adapter_ItemList instance;
     *
     *  Note: itemList SHOULD currently be sorted according to the selected
     *        sort order ($this->sortBy / sortOrder).
     *
     *  @return A string of HTML.
     */
    protected function _renderHighlights(Connexions_Model_Set_Adapter_ItemList
                                                                    $itemList)
    {
        // Re-sort by weight and walk forward from the beginning
        $itemList = $this->_sort($itemList,
                                 'weight',
                                 Connexions_Service::SORT_DIR_DESC);

        /******************************************************************
         * Render the top items.
         *
         */
        $html .= "<div class='highlights ui-corner-all'>"
              .   "<h4>Top {$this->highlightCount}</h4>"
              .   "<ul>";

        $idex = 0;
        foreach ($itemList as $item)
        {
            if (++$idex > $this->highlightCount)
                break;

            $html .= "<li>";

            $url    = $item->getParam('url');
            $weight = number_format($item->getWeight());
            if (empty($url))
                $html .= sprintf("<span class='item'>%s</span>",
                                    htmlSpecialChars($item->getTitle()));
            else
                $html .= sprintf('<a class="item" href="%s">%s</a>',
                                    htmlSpecialChars($url),
                                    htmlSpecialChars($item->getTitle()));

            $html .=  "<span class='itemCount'>{$weight}</span>"
                  .   "<br class='clear' />"
                  .  "</li>";
        }

        $html .=  "</ul>"
              .  "</div>";

        return $html;
    }

    /** @brief  Render the 'displayOptions' control area.
     *
     *
     *  @return A string of HTML.
     */
    protected function _renderDisplayOptions()
    {
        $namespace = $this->namespace;

        $html .= "<div class='displayOptions {$namespace}-displayOptions'>"
              .   "<div class='control ui-corner-all ui-state-default'>"
              .    "<a>Display Options</a>"
              .    "<div class='ui-icon ui-icon-triangle-1-s'>&nbsp;</div>"
              .   "</div>"
              .   "<form class='ui-state-active ui-corner-all' "
              .         "style='display:none;'>";

        /**************************************************************
         * SortBy
         *
         */
        $html =  "<label   for='{$namespace}SortBy'>Sorted by</label>"
              .  "<select name='{$namespace}SortBy' "
              .            "id='{$namespace}SortBy' "
              .         "class='sort-by sort-by-{$this->sortBy} "
              .                 "ui-input ui-state-default ui-corner-all'>";

        foreach (self::$sortTitles as $key => $title)
        {
            $isOn = ($key == $this->sortBy);
            $css  = 'ui-corner-all'
                  .   ($isOn ? ' option-on' : '');

            $html .= sprintf(  "<option%s title='%s' value='%s'%s>"
                             .  "<span>%s</span>"
                             . "</option>",
                             ( !@empty($css)
                                ? " class='". $css ."'"
                                : ""),
                             $title,
                             $key,
                             ($isOn ? " selected" : ""),
                             $title);
        }

        $html .= "</select>";

        $this->_displayOptions->addFormField('sortBy', $html);


        /**************************************************************
         * SortOrder
         *
         */
        $html =    "<label for='{$namespace}SortOrder'>Sort order</label>";

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

        $html .=   "<br class='clear' />";

        $this->_displayOptions->addFormField('sortOrder', $html);


        /**************************************************************
         * ItemCounts: perPage, highlightCount
         *
         */
        $html =    "<div class='field perPage'>"        // perPage {
              .     "<label for='{$namespace}PerPage'>Show</label>"
              .     "<select class='ui-input ui-state-default ui-corner-all "
              .                   "count' name='{$namespace}PerPage'>"
              .      "<!-- {$namespace}PerPage: {$this->perPage} -->";

        foreach (self::$perPageChoices as $countOption)
        {
            $html .= "<option value='{$countOption}'"
                  .           ($countOption == $this->perPage
                                 ? ' selected'
                                 : '')
                  .                     ">{$countOption}</option>";
        }
    
        $html .=    "</select>"
              .    "</div>";                            // perPage }


        $hlClasses = 'field highlightCount';
        $formState = '';
        if ($this->getDisplayStyle() !== self::STYLE_CLOUD)
        {
            // Disable the highlightCount
            $formState  = ' disabled=true';
            $hlClasses .= ' ui-state-disabled';
        }

        $html .=   "<div class='{$hlClasses}'>" // highlightCount {
              .     "<label for='{$namespace}HighlightCount' class='above'>"
              .       "highlighting the"
              .     "</label>"
              .     "<label for='{$namespace}HighlightCount'>top</label>"
              .     "<select class='ui-input ui-state-default ui-corner-all "
              .                   "count' name='{$namespace}HighlightCount' "
              .                   "{$formState}>";

        foreach (self::$highlightCountChoices as $countOption)
        {
            $html .= "<option value='{$countOption}'"
                  .           ($countOption == $this->highlightCount
                                 ? ' selected'
                                 : '')
                  .                     ">{$countOption}</option>";
        }
    
        $html .=    "</select>"
              .    "</div>"                             // highlightCount }
              .    "<br class='clear' />";

        $this->_displayOptions->addFormField('itemCounts', $html);

        /* _displayOptions->render will use the previously added fields, along
         * with the available display styles to render the complete display
         * options form.
         */
        return $this->_displayOptions->render();
    }

}
