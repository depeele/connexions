<?php
/** @file
 *
 *  View helper to render an Item Cloud, possibly paginated, in HTML.
 *
 */
class Connexions_View_Helper_HtmlItemCloud extends Zend_View_Helper_Abstract
{
    static public   $perPageChoices         = array(50, 100, 250, 500);
    static public   $highlightCountChoices  = array(0,  5,   10);

    static public   $defaults               = array(
        'displayStyle'      => self::STYLE_CLOUD,
        'itemType'          => self::ITEM_TYPE_TAG,
        'sortBy'            => self::SORT_BY_TITLE,
        'sortOrder'         => Connexions_Set::SORT_ORDER_ASC,

        'perPage'           => 100,
        'highlightCount'    => 5
    );

    /** @brief  Cloud Item type -- determines the item decorator
     */
    const ITEM_TYPE_TAG                     = 'tag';
    const ITEM_TYPE_USER                    = 'user';

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
                    Model_UserItemSet::SORT_ORDER_ASC   => 'Ascending',
                    Model_UserItemSet::SORT_ORDER_DESC  => 'Descending'
                );


    /** @brief  Set-able parameters . */
    protected       $_namespace         = 'tags';

    protected       $_displayOptions    = null;
    protected       $_showRelation      = true;
    protected       $_itemList          = null;
    protected       $_paginator         = null;

    protected       $_itemType          = null;
    protected       $_sortBy            = null;
    protected       $_sortOrder         = null;

    protected       $_perPage           = null;
    protected       $_highlightCount    = null;


    /** @brief  Variable Namespace/Prefix initialization indicators. */
    static protected $_initialized  = array();


    /** @brief  Render an HTML version of an item cloud.
     *  @param  itemList        A Connexions_Set_ItemList instance representing
     *                          the items to be presented;
     *  @param  style           The display style    ( self::STYLE_* );
     *  @param  itemType        The item type        ( self::TYPE_* );
     *  @param  sortBy          The field to sort by ( self::SORT_BY_* );
     *  @param  sortOrder       Sort order ( Connexions_Set::SORT_ORDER_* );
     *  @param  highlightCount  How many of items to highlight [ 5 ].
     *
     *  @param  hideOptions Should display options be hidden?
     *
     *  @return The HTML representation of an item cloud.
     */
    public function htmlItemCloud($itemList         = null,
                                  $style            = null,
                                  $itemType         = null,
                                  $sortBy           = null,
                                  $sortOrder        = null,
                                  $highlightCount   = null,
                                  $hideOptions      = false)
    {
        if ( ! $itemList instanceof Connexions_Set_ItemList)
        {
            return $this;
        }

        return $this->render($itemList, $style, $itemType,
                              $sortBy, $sortOrder,
                             $highlightCount, $hideOptions);
    }


    /** @brief  Render an HTML version of an item cloud.
     *  @param  itemList        A Connexions_Set_ItemList instance representing
     *                          the items to be presented;
     *  @param  style           The display style    ( self::STYLE_* );
     *  @param  itemType        The item type        ( self::TYPE_* );
     *  @param  sortBy          The field to sort by ( self::SORT_BY_* );
     *  @param  sortOrder       Sort order ( Connexions_Set::SORT_ORDER_* );
     *  @param  highlightCount  How many of items to highlight [ 5 ].
     *  @param  hideOptions     Should display options be hidden?
     *
     *  @return The HTML representation of an item cloud.
     */
    public function render(Connexions_Set_ItemList  $itemList       = null,
                                                    $style          = null,
                                                    $itemType       = null,
                                                    $sortBy         = null,
                                                    $sortOrder      = null,
                                                    $highlightCount = null,
                                                    $hideOptions    = false)
    {
        if ($itemList       !== null)   $this->setItemList($itemList);
        if (! empty($style))            $this->setStyle($style);
        if (! empty($itemType))         $this->setItemType($itemType);
        if (! empty($sortBy))           $this->setSortBy($sortBy);
        if (! empty($sortOrder))        $this->setSortOrder($sortOrder);
        if ($highlightCount !== null)   $this->setHighlightCount(
                                                            $highlightCount);


        $html = "<div id='{$this->_itemType}Items'>";  // <itemType>Items {

        if ($this->_showRelation)
        {
            $html .= "<div class='cloudRelation "
                  .              "connexions_sprites relation_ltr'>"
                  .   "&nbsp;"
                  .  "</div>";
        }

        $uiPagination = null;
        if ($this->_paginator instanceof Zend_Paginator)
        {
            /* Present the top pagination control.
             * Default values are established via
             *      Bootstrap.php::_initViewGlobal().
             */
            $uiPagination = $this->view->htmlPaginationControl();
            $uiPagination->setNamespace($this->_namespace);

            $html .= $uiPagination->render($this->_paginator,
                                           'pagination-top', true);
        }

        if ($hideOptions !== true)
        {
            $html .= $this->_renderDisplayOptions()
                  .  "<br class='clear' />";
        }

        if ($this->getStyle() === self::STYLE_CLOUD)
        {
            // Zend_Tag_Cloud configuration
            $cloudConfig = array(
                // Make helpers in 'application/views/helpers' available.
                'prefixPath'            => array(
                    'prefix'    => 'Connexions_View_Helper',
                    'path'      => APPLICATION_PATH .'/views/helpers/'
                 ),
                'ItemList'              => &$this->_itemList,
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

            switch ($this->_itemType)
            {
            case self::ITEM_TYPE_TAG:
                /* Use our cloud item decorator:
                 *      Connexions_View_Helper_HtmlItemCloudTag
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
                 *      Connexions_View_Helper_HtmlItemCloudUser
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
            }


            // Create a Zend_Tag_Cloud renderer (by default, renders HTML)
            $cloud = new Zend_Tag_Cloud( $cloudConfig );

            // Render the HTML for a cloud
            $html .= "<div class='cloud'>"
                  .   ($this->_highlightCount > 0
                            ? $this->_renderHighlights( )
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
                  .   ($this->_highlightCount > 0
                            ? $this->_renderHighlights( )
                            : '')
                  */
                  .   $this->_renderList($this->_itemList)
                  .   "<br class='clear' />"
                  .  "</div>";
        }

        if ($uiPagination !== null)
        {
            // Present the bottom pagination control.
            $html .= $uiPagination->render($this->_paginator);
        }

        $html .= "</div>";  // <itemType>Items }

        // Return the rendered HTML
        return $html;
    }

    /** @brief  Set the namespace, primarily for forms and cookies.
     *  @param  namespace   A string prefix.
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function setNamespace($namespace)
    {
        // /*
        Connexions::log("Connexions_View_Helper_HtmlItemCloud::"
                            .   "setNamespace( {$namespace} )");
        // */

        $this->_namespace = $namespace;

        if (! @isset(self::$_initialized['__global__']))
        {
            $view   = $this->view;
            $jQuery = $view->jQuery();

            $jQuery->addJavascriptFile($view->baseUrl('js/jquery.cookie.js'))
                   ->addJavascriptFile($view->baseUrl('js/ui.button.js'))
                   ->javascriptCaptureStart();

            ?>

/************************************************
 * Initialize ui elements.
 *
 */
function init_ItemCloud(namespace)
{
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
                                'groups'        => self::$styleGroups
                            );

                $this->_displayOptions = $view->htmlDisplayOptions($dsConfig);
            }
            else
            {
                $this->_displayOptions->setNamespace($namespace);
            }

            // Include required jQuery
            $view->jQuery()->addOnLoad("init_ItemCloud('{$namespace}');");
        }

        return $this;
    }

    /** @brief  Get the current namespace.
     *
     *  @return The string namespace.
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /** @brief  Set whether or not the "relation" indicator is presented.
     *  @param  show    A boolean.
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function setShowRelation($show)
    {
        $this->_showRelation = ($show ? true : false);

        return $this;
    }

    /** @brief  Get the "show relation" indicator.
     *
     *  @return The boolean.
     */
    public function getShowRelation()
    {
        return $this->_showRelation;
    }

    /** @brief  Set the item list to present.
     *  @param  itemList    A Connexions_Set_ItemList instance representing the
     *                      items to be presented;
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function setItemList(Connexions_Set_ItemList $itemList = null)
    {
        if ($itemList !== null)
        {
            $this->_itemList = $itemList;
            $this->_perPage = count($itemList);
        }

        return $this;
    }

    /** @brief  Get the current item list.
     *
     *  @return The Connexions_Set_ItemList instance representing the
     *          items to be presented (null if none set);
     */
    public function getItemList()
    {
        return $this->_itemList;
    }

    /** @brief  Set the paginator to present.
     *  @param  paginator   The Zend_Paginator instance.
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function setPaginator(Zend_Paginator $paginator)
    {
        $this->_paginator = $paginator;

        return $this;
    }

    /** @brief  Unset the paginator.
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function unsetPaginator()
    {
        $this->_paginator = null;

        return $this;
    }

    /** @brief  Get the current paginator.
     *
     *  @return The Zend_Paginator instance (or null if none).
     */
    public function getPaginator()
    {
        return $this->_paginator;
    }

    /** @brief  Set the cloud item type.
     *  @param  itemType    A item type value (self::ITEM_TYPE_*)
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function setItemType($itemType)
    {
        $orig = $itemType;

        switch ($itemType)
        {
        case self::ITEM_TYPE_TAG:
        case self::ITEM_TYPE_USER:
            break;

        default:
            $itemType = self::$defaults['itemType'];
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_HtmlItemCloud::'
                            . "setType({$orig}) == [ {$itemType} ]");
        // */
    
        $this->_itemType = $itemType;

        return $this;
    }

    /** @brief  Get the current item type.
     *
     *  @return The item type value (self::ITEM_TYPE_*).
     */
    public function getItemType()
    {
        return $this->_itemType;
    }

    /** @brief  Set the current style.
     *  @param  style   A style value (self::STYLE_*)
     *  @param  values  If provided, an array of field values for this style.
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function setStyle($style, array $values = null)
    {
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
        Connexions::log('Connexions_View_Helper_HtmlItemCloud::'
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

    /** @brief  Set the current sortBy.
     *  @param  sortBy  A sortBy value (self::SORT_BY_*)
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
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
        Connexions::log('Connexions_View_Helper_HtmlItemCloud::'
                            . "setSortBy({$orig}) == [ {$sortBy} ]");
        // */

        $this->_sortBy = $sortBy;

        return $this;
    }

    /** @brief  Get the current sortBy value.
     *
     *  @return The sortBy value (self::SORT_BY_*).
     */
    public function getSortBy()
    {
        return $this->_sortBy;
    }

    /** @brief  Set the current sortOrder.
     *  @param  sortOrder   A sortOrder value (Connexions_Set::SORT_ORDER_*)
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function setSortOrder($sortOrder)
    {
        $orig = $sortOrder;

        $sortOrder = strtoupper($sortOrder);
        switch ($sortOrder)
        {
        case Connexions_Set::SORT_ORDER_ASC:
        case Connexions_Set::SORT_ORDER_DESC:
            break;

        default:
            $sortOrder = self::$defaults['sortOrder'];
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_HtmlItemCloud::'
                            . "setSortOrder({$orig}) == [ {$sortOrder} ]");
        // */
    
        $this->_sortOrder = $sortOrder;

        return $this;
    }

    /** @brief  Get the current sortOrder value.
     *
     *  @return The sortOrder value (Connexions_Set::SORT_ORDER_*).
     */
    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    /** @brief  Set the number of items to highlight
     *  @param  highlightCount  The number of items to highlight
     *                          (self::$highlightCountChoices).
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function setHighlightCount($highlightCount)
    {
        if (($highlightCount !== null) &&
            in_array($highlightCount, self::$highlightCountChoices))
        {
            $this->_highlightCount = $highlightCount;
        }
        else
        {
            // Default
            $this->_highlightCount = self::$defaults['highlightCount'];
        }

        return $this;
    }

    /** @brief  Get the current highlightCount value.
     *
     *  @return The highlightCount value.
     */
    public function getHighlightCount()
    {
        return $this->_highlightCount;
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Render an item list.
     *  @param  itemList    A Connexions_Set_ItemList instance representing the
     *                      items to be presented;
     *
     *
     *  @return A string of HTML.
     */
    protected function _renderList(Connexions_Set_ItemList   $itemList)
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
     *
     *  @return A string of HTML.
     */
    protected function _renderHighlights()
    {
        /*
        Connexions::log("HtmlItemCloud: _renderHighlights: "
                            . "sort[ {$this->_sortBy}, {$this->_sortOrder} ]");
        // */

        /* If our list is already sorted by order, simply take the:
         *  - first if order is 'DESC';
         *  - last  if order is 'ASC'.
         *
         * Otherwise, we'll need to re-sort...
         */
        $topItems = array();
        if ($this->_sortBy === 'weight')
        {
            switch ($this->_sortOrder)
            {
            case Connexions_Set::SORT_ORDER_ASC:
                // Take the last
                $topItems = $this->_itemList->slice(
                                        (count($this->_itemList) -
                                            $this->_highlightCount),
                                        $this->_highlightCount);
                break;

            case Connexions_Set::SORT_ORDER_DESC:
                // Take the first
                $topItems = $this->_itemList->slice(
                                        0,
                                        $this->_highlightCount);
                break;
            }
        }

        if (empty($topItems))
        {
            // Re-sort...
            /*
            $count = count($this->_itemList);
            Connexions::log("HtmlItemCloud: _renderHighlights: "
                            . "re-sort {$count} items");
            // */

            $sortList = clone $this->_itemList;

            // Create function to reverse sort by weight.
            $sortFn = create_function('$a,$b',   '$aVal = $a->getWeight();'
                                               . '$bVal = $b->getWeight();'
                                               . '$cmp  = ($bVal - $aVal);'
                                               . 'return  $cmp;');

            // Sort the item list
            $sortList->uasort($sortFn);

            $topItems = $sortList->slice( 0, $this->_highlightCount );
        }


        /******************************************************************
         * Render the top items.
         *
         */
        $html .= "<div class='highlights ui-corner-all'>"
              .   "<h4>Top {$this->_highlightCount}</h4>"
              .   "<ul>";

        foreach ($topItems as $idex => $item)
        {
            if ($idex > $this->_highlightCount)
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
        $namespace = $this->_namespace;

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
              .         "class='sort-by sort-by-{$this->_sortBy} "
              .                 "ui-input ui-state-default ui-corner-all'>";

        foreach (self::$sortTitles as $key => $title)
        {
            $isOn = ($key == $this->_sortBy);
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
                  .          ($key == $this->_sortOrder
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
              .      "<!-- {$namespace}PerPage: {$this->_perPage} -->";

        foreach (self::$perPageChoices as $countOption)
        {
            $html .= "<option value='{$countOption}'"
                  .           ($countOption == $this->_perPage
                                 ? ' selected'
                                 : '')
                  .                     ">{$countOption}</option>";
        }
    
        $html .=    "</select>"
              .     "<span class='label'>highlighting the</span>"
              .    "</div>"                             // perPage }
              .    "<div class='field highlightCount'>" // highlightCount {
              .     "<label for='{$namespace}HighlightCount'>top</label>"
              .     "<select class='ui-input ui-state-default ui-corner-all "
              .                   "count' name='{$namespace}HighlightCount'>";

        foreach (self::$highlightCountChoices as $countOption)
        {
            $html .= "<option value='{$countOption}'"
                  .           ($countOption == $this->_highlightCount
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
