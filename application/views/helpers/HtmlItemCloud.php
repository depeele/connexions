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
                        'itemType'          => self::ITEM_TYPE_TAG,

                        'sortBy'            => self::SORT_BY_TITLE,
                        'sortOrder'         => Connexions_Set::SORT_ORDER_ASC,

                        'perPage'           => 100,
                        'highlightCount'    => 5,

                        'displayStyle'      => self::STYLE_CLOUD
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

    protected       $_itemSet           = null;
    protected       $_itemSetInfo       = null;
    protected       $_itemBaseUrl       = null;

    protected       $_itemType          = null;
    protected       $_sortBy            = null;
    protected       $_sortOrder         = null;

    protected       $_perPage           = null;
    protected       $_highlightCount    = null;

    protected       $_hiddenItems       = array();


    /** @brief  Variable Namespace/Prefix initialization indicators. */
    static protected $_initialized      = array();


    /** @brief  Render an HTML version of an item cloud.
     *  @param  itemSet     A Connexions_Set | Zend_Paginator instance
     *                      representing the items to be presented;
     *
     *  @return The HTML representation of an item cloud.
     */
    public function htmlItemCloud($itemSet = null)
    {
        if ( $itemSet === null )
        {
            return $this;
        }

        return $this->render($itemSet);
    }


    /** @brief  Render an HTML version of an item cloud.
     *  @param  itemSet     A Connexions_Set | Zend_Paginator instance
     *                      representing the items to be presented;
     *
     *  @return The HTML representation of an item cloud.
     */
    public function render($itemSet = null)
    {
        if ($itemSet !== null)
        {
            $this->setItemSet($itemSet);
        }

        $html = "<div id='{$this->_itemType}Items'>";  // <itemType>Items {

        if ($this->_showRelation)
        {
            $html .= "<div class='cloudRelation "
                  .              "connexions_sprites relation_ltr'>"
                  .   "&nbsp;"
                  .  "</div>";
        }

        $uiPagination = null;
        if ($this->_itemSet instanceof Zend_Paginator)
        {
            /* Present the top pagination control.
             * Default values are established via
             *      Bootstrap.php::_initViewGlobal().
             */
            $uiPagination = $this->view->htmlPaginationControl();
            $uiPagination->setNamespace($this->_namespace);

            $html .= $uiPagination->render($this->_itemSet,
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
        if ($this->_itemSet instanceof Connexions_Set)
        {
            $itemList = $this->_itemSet
                                ->get_Tag_ItemList($this->_itemSetInfo,
                                                   $this->_itemBaseUrl);
        }
        else if ($this->_itemSet instanceof Zend_Paginator)
        {
            $itemList = new Connexions_Set_ItemList($this->_itemSet,
                                                    $this->_itemSetInfo,
                                                    $this->_itemBaseUrl);
        }

        if (! empty($this->_hiddenItems))
        {
            // Remove any tags that are to be hidden
            /*
            Connexions::log("Connexions_View_Helper_HtmlItemCloud:: "
                                . "filter out tags [ "
                                .   implode(", ", $this->_hiddenItems) ." ]");
            // */

            foreach ($itemList as $key => $item)
            {
                if (in_array($item->getTitle(), $this->_hiddenItems))
                {
                    /*
                    Connexions::log("Connexions_View_Helper_HtmlItemCloud:: "
                                    . "remove tag [{$item->getTitle()}]");
                    // */

                    unset($itemList[$key]);
                }
            }
        }


        $sortedList = $this->_sort($itemList, $this->_sortBy,
                                              $this->_sortOrder);

        if ($this->getStyle() === self::STYLE_CLOUD)
        {
            // Zend_Tag_Cloud configuration
            $cloudConfig = array(
                // Make helpers in 'application/views/helpers' available.
                'prefixPath'            => array(
                    'prefix'    => 'Connexions_View_Helper',
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
                  .   ($this->_highlightCount > 0
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
            $html .= $uiPagination->render($this->_itemSet);
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

            $jQuery->addJavascriptFile($view->baseUrl('js/jquery.cookie.min.js'))
                   ->addJavascriptFile($view->baseUrl('js/ui.button.min.js'))
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

    /** @brief  Establish the item set to present.
     *  @param  itemSet     A Connexions_Set | Zend_Paginator instance
     *                      representing the items to be presented;
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function setItemSet($itemSet)
    {
        $perPage = $this->getPerPage();
        if ($itemSet instanceof Connexions_Set)
        {
            if ($this->_displayOptions->getGroup() === self::STYLE_CLOUD)
            {
                /* Since we're rendering a cloud, but likely only part of one,
                 * if the selected sort order is NOT by weight, set the order
                 * to 'weight DESC' now so we'll present the highest weighted
                 * items in the sub-set that we can then sort according to the
                 * desired order.
                 */
                if ( $this->_sortBy !== self::SORT_BY_WEIGHT)
                {
                    /* Note: Connexions_Set::setOrder() will parse the order
                     *       specification using
                     *       Connexions_Set::_parse_order().  This will cause
                     *       calls to the mapField() method of $itemSet for
                     *       each referenced field.  This will, for 'weight',
                     *       invoke $itemSet->weightBy() if no weight has yet
                     *       been set.
                     *
                     *       Thus, simply specifying a sort order of 'weight'
                     *       will also ensure the computed weight field is
                     *       included.
                     */
                    $itemSet->setOrder( 'weight DESC' );
                }
            }
            else
            {
                // Always apply the sort order for self::STYLE_LIST
                $itemSet->setOrder( $this->_sortBy .' '.
                                    $this->_sortOrder );
            }

            /* Reflect the perPage value that has been established.
             *
             * This perPage limit is what will cause us to only present a
             * sub-set...
             */
            $itemSet->limit( $perPage );

            $this->_itemSet = $itemSet;
        }
        else if ($itemSet instanceof Zend_Paginator)
        {
            /* We've been given a partially initiallzed paginator.
             *
             * Make sure the 'perPage' setting matches ours.
             */
            $itemSet->setItemCountPerPage( $perPage );

            $this->_itemSet = $itemSet;
        }
        else
        {
            Connexions::log("Connexions_View_Helper_HtmlItemCloud::setItemSet: "
                                . "Invalid class [ "
                                .       get_class($itemSet) ." ]");
        }

        return $this;
    }

    /** @brief  Get the current item list.
     *
     *  @return The Connexions_Set | Zend_Paginator instance representing the
     *          items to be presented (null if none set);
     */
    public function getItemSet()
    {
        return $this->_itemSet;
    }

    /** @brief  Set the request information about items.
     *  @param  setInfo     A Connexions_Set_Info instance containing
     *                      information about any items specified in the
     *                      request.
     *  @param  url         The base url for items
     *                      (defaults to the request URL).
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function setItemSetInfo(Connexions_Set_Info $itemSetInfo)
    {
        $this->_itemSetInfo = $itemSetInfo;

        return $this;
    }

    /** @brief  Get the current item request information.
     *
     *  @return The Connexions_Set_Info (null if none set).
     */
    public function getItemSetInfo()
    {
        return $this->_itemSetInfo;
    }

    /** @brief  Establish the baseUrl for items.
     *  @param  url         The base url for items.
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function setItemBaseUrl($url)
    {
        $this->_itemBaseUrl = $url;

        return $this;
    }

    /** @brief  Get the current item baseUrl.
     *
     *  @return The current baseUrl (null if none set);
     */
    public function getItemBaseUrl()
    {
        return $this->_itemBaseUrl;
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

        /*
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

    /** @brief  Set the number of items per-page.
     *  @param  perPage The number of items per page
                                (self::$perPageChoices).
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function setPerPage($perPage)
    {
        if (($perPage !== null) &&
            in_array($perPage, self::$perPageChoices))
        {
            $this->_perPage = $perPage;
        }
        else
        {
            // Default
            $this->_perPage = self::$defaults['perPage'];
        }

        return $this;
    }

    /** @brief  Get the current per-page value.
     *
     *  @return The per-page value.
     */
    public function getPerPage()
    {
        return $this->_perPage;
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

    /** @brief  Add a tag that should NOT be included in the context URL.
     *  @param  str     The tag (name).
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
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
     *  @param  itemList    A Connexions_Set_ItemList instance;
     *  @param  sortBy      The field to sort by ( self::SORT_BY_* );
     *  @param  sortOrder   Sort order ( Connexions_Set::SORT_ORDER_* );
     *
     *  @return The sorted Connexions_Set_ItemList.
     */
    protected function _sort(Connexions_Set_ItemList $itemList,
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
         *          - if ((this->_itemSet instanceof  Connexions_Set) &&
         *                (displayStyle     === self::STYLE_CLOUD)    &&
         *                (this->_sortBy    !== self::SORT_BY_WEIGHT))
         *          - OR ((this->_sortBy    === self::SORT_BY_WEIGHT) &&
         *                (this->_sortOrder === 
         *                              Model_UserItemSet::SORT_ORDER_DESC))
         *
         * We ALSO don't need to sort if the requested order is 'weight DESC'
         * Now, we need to apply the chosen sort on the current sub-set of
         * items.
         */
        if (($this->_itemSet instanceof Connexions_Set)                &&
            ($this->_displayOptions->getGroup() === self::STYLE_CLOUD) &&
            ($this->_sortBy                     !== self::SORT_BY_WEIGHT) )
        {
            $curSortBy    = self::SORT_BY_WEIGHT;
            $curSortOrder = Model_UserItemSet::SORT_ORDER_DESC;
        }
        else
        {
            $curSortBy    = $this->_sortBy;
            $curSortOrder = $this->_sortOrder;
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

            if ($sortOrder === Model_UserItemSet::SORT_ORDER_DESC)
                // Reverse sort
                $cmp = '(0 - '. $cmp .')';
        }
        else
        {
            $val = 'getWeight()';

            if ($sortOrder === Model_UserItemSet::SORT_ORDER_DESC)
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
     *  @param  itemList    A Connexions_Set_ItemList instance;
     *
     *  Note: itemList SHOULD currently be sorted according to the selected
     *        sort order ($this->_sortBy / _sortOrder).
     *
     *  @return A string of HTML.
     */
    protected function _renderHighlights(Connexions_Set_ItemList $itemList)
    {
        // Re-sort by weight and walk forward from the beginning
        $itemList = $this->_sort($itemList,
                                 'weight',
                                 Connexions_Set::SORT_ORDER_DESC);

        /******************************************************************
         * Render the top items.
         *
         */
        $html .= "<div class='highlights ui-corner-all'>"
              .   "<h4>Top {$this->_highlightCount}</h4>"
              .   "<ul>";

        $idex = 0;
        foreach ($itemList as $item)
        {
            if ($idex++ > $this->_highlightCount)
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
