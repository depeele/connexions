<?php
/** @file
 *
 *  View helper to render an Item Cloud, possibly paginated, in HTML.
 *
 */
class View_Helper_HtmlItemCloud extends Zend_View_Helper_Abstract
{
    static public   $perPageChoices         = array(10, 25, 50, 100, 250, 500);
    static public   $highlightCountChoices  = array(0,  5,   10);

    static public   $defaults               = array(
        'namespace'         => 'tags',
        'pageBaseUrl'       => null,        /* The base URL of the containing
                                             * page used to set the cookie path
                                             * for the attached Javascript
                                             * 'cloudPane' which, in turn,
                                             * effects the cookie path passed
                                             * to the contained 'dropdownForm'
                                             * presneting Display Options.
                                             */
        'panePartial'       => 'main',

        'showRelation'      => true,
        'showOptions'       => true,
        'itemType'          => self::ITEM_TYPE_ITEM,

        'items'             => null,        /* A Connexions_Model_Set
                                             * containing the items to present
                                             */
        'paginator'         => null,        /* A paginated version of 'items'.
                                             * Provide if a paginator control
                                             * should be rendered.
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
        'weightTitle'       => null,        /* The title describing the item's
                                             * weight.
                                             */
        'titleTitle'        => null,        /* The title describing the item's
                                             * title.
                                             */


        // The desired sort order
        'sortBy'            => self::SORT_BY_TITLE,
        'sortOrder'         => Connexions_Service::SORT_DIR_ASC,

        // The current sort order
        'currentSortBy'     => null,
        'currentSortOrder'  => null,

        'page'              => 1,
        'perPage'           => 100,
        'highlightCount'    => 5,

        'displayStyle'      => self::STYLE_CLOUD,
    );

    /** @brief  Cloud Item type -- determines the item decorator
     */
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

    protected       $_currentSortBy     = null;
    protected       $_currentSortOrder  = null;

    static protected $_initialized      = array();

    /** @brief  Construct a new Bookmarks helper.
     *  @param  config  A configuration array (see populate());
     */
    public function __construct(array $config = array())
    {
        //Connexions::log("View_Helper_HtmlItemCloud::__construct()");

        foreach (self::$defaults as $key => $value)
        {
            /* :WARNING: Do NOT invoke __set() here -- it's too early for many
             *           of the set methods...
             */
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
     *                      showOptions     [true];
     *                      itemType        [self::ITEM_TYPE_ITEM];
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
        // Variables that MUST be set BEFORE 'namespace'...
        foreach (array('pageBaseUrl', 'panePartial') as $key)
        {
            if (isset($config[$key]))
            {
                $this->__set($key, $config[$key]);
            }
        }

        foreach ($config as $key => $value)
        {
            if ($key === 'hiddenItems')
            {
                $list = (is_array($value)
                            ? $value
                            : preg_split('/\s*,\s*/', trim($value)) );

                foreach ($list as $value)
                {
                    $this->addHiddenItem($value);
                }

                continue;
            }

            $this->__set($key, $value);
            //$this->_params[$key] = $value;
        }

        /*
        Connexions::log("View_Helper_HtmlItemCloud::populate(): params[ %s ]",
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

    /** @brief  Are there hidden items?
     *
     *  @return true | false
     */
    public function hasHiddenItems()
    {
        return (! empty($this->_hiddenItems));
    }

    /** @brief  Is the given item hidden?
     *  @param  item    A Connexions_Model instance to check.
     *
     *  @return true | false
     */
    public function isHiddenItem(Connexions_Model   $item)
    {
        return (in_array($item->getTitle(), $this->_hiddenItems));
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
        $res = $this->view->partial('itemCloud.phtml',
                                    array('helper' => $this));
        return $res;
    }

    /** @brief  Set the namespace, primarily for forms and cookies.
     *  @param  namespace   The new namespace.
     *
     *  @return $this for a fluent interface.
     */
    public function setNamespace($namespace)
    {
        $this->_params['namespace'] = $namespace;

        /*
        Connexions::log("View_Helper_HtmlItemCloud::setNamespace( %s )",
                        $namespace);
        // */

        if ( ($this->showOptions !== false) &&
             (! @isset(self::$_initialized[$namespace])) )
        {
            // Set / Update our displayOptions namespace.
            $view   = $this->view;
            $jQuery = $view->jQuery();

            $dsConfig = array(
                            'namespace' => $namespace,
                            'groups'    => self::$styleGroups,
                        );

            if ($this->pageBaseUrl !== null)
            {
                $dsConfig['cookiePath'] = rtrim($this->pageBaseUrl, '/');
            }

            /*
            Connexions::log("View_Helper_HtmlItemCloud::setNamespace(): "
                            . "new namespace: config[ %s ]",
                            Connexions::varExport($dsConfig));
            // */

            if ($this->_displayOptions === null)
            {
                $this->_displayOptions = $view->htmlDisplayOptions($dsConfig);

                /* Ensure that the current display style is properly reflected
                 * in the new display options instance.
                 */
                $this->_displayOptions->setGroup($this->displayStyle);
            }
            else
            {
                $this->_displayOptions->setNamespace($namespace);
            }

            $config = array('namespace'         => $namespace,
                            'partial'           => $this->panePartial,
                            'displayOptions'    => $dsConfig,
                      );
            $call   = "$('#{$namespace}Cloud').cloudPane("
                    .               Zend_Json::encode($config) .");";
            $jQuery->addOnLoad($call);

            //$jQuery->addOnLoad("init_CloudOptions('{$namespace}');");

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

        /*
        Connexions::log("View_Helper_HtmlItemCloud::setItems(): "
                        . "%d items:",
                        count($items));

        foreach($items as $key => $item)
        {
            Connexions::log("View_Helper_HtmlItemCloud::setItems(): "
                            . "%s: (%s) title [ %s ], weight[ %d ]",
                            $key,
                            get_class($item),
                            $item->getTitle(),
                            $item->getWeight() );
        }
        // */

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
        case self::ITEM_TYPE_ITEM:
        case self::ITEM_TYPE_USER:
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
        /*
        Connexions::log("View_Helper_HtmlItemCloud:"
                        . "setDisplayStyle(): "
                        .   "style[ %s ], values[ %s ]",
                        $style, print_r($values, true));
        // */

        $reqStyle = $style;
        if ($values !== null)
        {
            if ($this->_displayOptions !== null)
            {
                $this->_displayOptions->setGroupValues($values);
            }
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

            $this->_params['displayStyle'] = $style;
            if ($this->_displayOptions !== null)
            {
                $this->_displayOptions->setGroup($style);
            }
        }

        /*
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
        return ($this->_displayOptions !== null
                    ? $this->_displayOptions->getGroup()
                    : $this->_params['displayStyle']);
    }

    /** @brief  Set the desired sortBy.
     *  @param  sortBy  A sortBy value (self::SORT_BY_*)
     *
     *  @return $this for a fluent interface.
     */
    public function setSortBy($sortBy)
    {
        $this->_params['sortBy'] = $this->_validateSortBy($sortBy);

        /*
        Connexions::log('View_Helper_HtmlItemCloud::'
                            . "setSortBy({$sortBy}) == [ {$this->sortBy} ]");
        // */

        return $this;
    }

    /** @brief  Set the desired sortOrder.
     *  @param  sortOrder   A sortOrder value (Connexions_Service::SORT_DIR_*)
     *
     *  @return $this for a fluent interface.
     */
    public function setSortOrder($sortOrder)
    {
        $this->_params['sortOrder'] = $this->_validateSortOrder($sortOrder);

        /*
        Connexions::log('View_Helper_HtmlItemCloud::'
                            . "setSortOrder({$sortOrder}) == "
                            .            "[ {$this->sortOrder} ]");
        // */

        return $this;
    }

    /** @brief  Set the current sortBy.
     *  @param  sortBy  A sortBy value (self::SORT_BY_*)
     *
     *  @return $this for a fluent interface.
     */
    public function setCurrentSortBy($sortBy)
    {
        $this->_params['currentSortBy'] = $this->_validateSortBy($sortBy);

        /*
        Connexions::log('View_Helper_HtmlItemCloud::'
                            . "setCurrentSortBy({$sortBy}) == "
                            .         "[ {$this->currentSortBy} ]");
        // */

        return $this;
    }

    /** @brief  Set the current sortOrder.
     *  @param  sortOrder   A sortOrder value (Connexions_Service::SORT_DIR_*)
     *
     *  @return $this for a fluent interface.
     */
    public function setCurrentSortOrder($sortOrder)
    {
        $this->_params['currentSortOrder'] =
                            $this->_validateSortOrder($sortOrder);

        /*
        Connexions::log('View_Helper_HtmlItemCloud::'
                            . "setCurrentSortOrder({$sortOrder}) == "
                            .            "[ {$this->currentSortOrder} ]");
        // */

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

    /** @brief  Render the 'displayOptions' control area.
     *
     *
     *  @return A string of HTML.
     */
    public function renderDisplayOptions()
    {
        if ($this->showOptions === false)
        {
            return '';
        }

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
            $countTitle = ($countOption === 0
                            ? 'none'
                            : $countOption);

            $html .= "<option value='{$countOption}'"
                  .           ($countOption == $this->highlightCount
                                 ? ' selected'
                                 : '')
                  .                     ">{$countTitle}</option>";
        }
    
        $html .=    "</select>"
              .    "</div>"                             // highlightCount }
              .    "<br class='clear' />";

        $this->_displayOptions->addFormField('itemCounts', $html);

        /* _displayOptions->render will use the previously added fields, along
         * with the available display styles to render the complete display
         * options form.
         */
        return $this->_displayOptions->render()
               . "<br class='clear' />";
    }

    /** @brief  Sort our tags, if needed.
     *  @param  itemList    A Connexions_Model_Set_Adapter_ItemList instance;
     *  @param  sortBy      The field to sort by ( self::SORT_BY_* )
     *                      [ $this->sortBy ];
     *  @param  sortOrder   Sort order ( Connexions_Service::SORT_DIR_* )
     *                      [ $this->sortOrder ];
     *
     *  @return The sorted Connexions_Model_Set_Adapter_ItemList
     */
    public function sortItemList(
                        Connexions_Model_Set_Adapter_ItemList  $itemList,
                        $sortBy     = null,
                        $sortOrder  = null)
    {
        if ($sortBy    === null)    $sortBy    = $this->sortBy;
        if ($sortOrder === null)    $sortOrder = $this->sortOrder;

        /*
        Connexions::log("View_Helper_HtmlItemCloud::sortItemList(): "
                        . "%d items, sort[ %s => %s, %s => %s ], "
                        . "weightName[ %s ]",
                        count($itemList),
                        $this->currentSortBy,    $sortBy,
                        $this->currentSortOrder, $sortOrder,
                        $this->weightName);

        foreach($itemList as $key => $item)
        {
            $val = ($this->weightName === null
                        ? $item->getWeight()
                        : $item->__get($this->weightName));

            Connexions::log("View_Helper_HtmlItemCloud::sortItemList(): "
                            . "%s: (%s) title [ %s ], weight[ %d / %d ]",
                            $key,
                            get_class($item),
                            $item->getTitle(),
                            $item->getWeight(), $val );
        }
        // */

        /* In setItemSet(), the chosen sort order MAY be over-ridden to ensure
         * that we're presenting the most weighty portion of the cloud.
         */
        if (($sortBy    === $this->currentSortBy) &&
            ($sortOrder === $this->currentSortOrder))
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
            $val = ($this->weightName === null
                        ? 'getWeight()'
                        : $this->weightName);

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
        Connexions::log("View_Helper_HtmlItemCloud:sortItemList(): "
                        .   "----------------------- "
                            . count($itemList) ." items, "
                            . "sorted [ {$sortBy}, {$sortOrder} ]");
        foreach($itemList as $key => $item)
        {
            Connexions::log("View_Helper_HtmlItemCloud:sortItemList(): "
                            . "%-3s: title [ %-15s ], weight[ %d ]",
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
    public function renderList(Connexions_Model_Set_Adapter_ItemList
                                                                    $itemList)
    {
        $html .= "<ul class='Item_List'>";

        $includeWeight = ($this->weightName !== null);

        Connexions::log("View_Helper_HtmlItemCloud::renderList(): "
                        . "%d items, weightName[ %s ], weightTitle[ %s ]",
                        count($itemList), $this->weightName,
                        $this->weightTitle);

        $html .= "<li class='header'>"
              .   "<span class='item'>"
              .    ($this->titleTitle !== null
                      ? $this->titleTitle
                      : ($this->itemType === self::ITEM_TYPE_USER
                            ? "User"
                            : "Item"))
              .   "</span>";
        if ($includeWeight)
        {

            $html .= "<span class='itemCount'>"
                  .   ($this->weightTitle !== null
                        ? $this->weightTitle
                        : 'Weight')
                  .  "</span>";
        }
        $html .= "</li>";

        $idex = 0;
        foreach ($itemList as $item)
        {
            $oddEven = ($idex++ % 2 ? 'odd' : 'even');
            $html   .= "<li class='{$oddEven}'>";

            $url    = $item->getParam('url');
            $weight = number_format(round($item->getWeight()));

            if (empty($url))
                $html .= sprintf("<span class='item'>%s</span>",
                                    $item->getTitle());
            else
                $html .= sprintf('<a class="item" href="%s">%s</a>',
                                    htmlSpecialChars($url),
                                    $item->getTitle());

            if ($includeWeight)
            {
                $title = ($this->weightTitle !== null
                            ? " title='{$this->weightTitle}'"
                            : '');

                $html .=  "<span class='itemCount'{$title}>{$weight}</span>";
            }

            $html .= "</li>";
        }

        $html .= "</ul>";

        return $html;
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Given a requested sortBy value, validate, returning a valid
     *          value.
     *  @param  sortBy  A sortBy value (self::SORT_BY_*)
     *
     *  @return A valid sortBy value.
     */
    protected function _validateSortBy($sortBy)
    {
        switch ($sortBy)
        {
        case self::SORT_BY_TITLE:
        case self::SORT_BY_WEIGHT:
            break;

        default:
            $sortBy = self::$defaults['sortBy'];
            break;
        }

        return $sortBy;
    }

    /** @brief  Given a requested sortOrder value, validate, returning a valid
     *          value.
     *  @param  sortOrder   A sortOrder value (Connexions_Service::SORT_DIR_*)
     *
     *  @return A valid sortOrder value.
     */
    protected function _validateSortOrder($sortOrder)
    {
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

        return $sortOrder;
    }
}
