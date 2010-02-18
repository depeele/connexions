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

    static public   $styleTitles            = array(
        self::STYLE_LIST    => 'List',
        self::STYLE_CLOUD   => 'Cloud'
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
    protected       $_showRelation      = true;
    protected       $_paginator         = null;

    protected       $_displayStyle      = null;
    protected       $_itemType          = null;
    protected       $_sortBy            = null;
    protected       $_sortOrder         = null;

    protected       $_perPage           = null;
    protected       $_highlightCount    = null;


    /** @brief  Variable Namespace/Prefix initialization indicators. */
    static protected $_initialized  = array();


    /** @brief  Set the View object.
     *  @param  view    The Zend_View_Interface
     *
     *  Override Zend_View_Helper_Abstract::setView() in order to initialize.
     *
     *  Note: if '$view->viewNamespace' is defined, it will override any
     *        namespace previously set for this instance.
     *
     *  @return Zend_View_Helper_Abstract
     */
    public function setView(Zend_View_Interface $view)
    {
        parent::setView($view);

        $namespace = null;
        if ( (! @empty($view->viewNamespace)) &&
             ($this->_namespace != $view->viewNamespace) )
            // Pull the namespace from the view
            $namespace = $view->viewNamespace;

        if ( ($namespace !== null) &&
             (! @isset(self::$_initialized[ $namespace ])) )
        {
            /*
            Connexions::log("Connexions_View_Helper_HtmlItemCloud:: "
                                . "set namespace from view [ {$namespace}]");
            // */

            $this->setNamespace($namespace);
        }

        return $this;
    }

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
    public function render(Connexions_Set_ItemList    $itemList,
                           $style           = null,
                           $itemType        = null,
                           $sortBy          = null,
                           $sortOrder       = null,
                           $highlightCount  = null,
                           $hideOptions     = false)
    {
        $this->_perPage = count($itemList);

        $this->setStyle($style)
             ->setItemType($itemType)
             ->setSortBy($sortBy)
             ->setSortOrder($sortOrder)
             ->setHighlightCount($highlightCount);


        $html = "<div id='{$itemType}Items'>";  // <itemType>Items {

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

            $html .= $uiPagination->render($this->_paginator,
                                           'pagination-top', true);
        }

        if ($hideOptions !== true)
        {
            $html .= $this->_renderDisplayOptions()
                  .  "<br class='clear' />";
        }

        $origItemList = clone $itemList;
        /*
        $html .= "\n<!-- First {$this->_highlightCount} items:\n";
        foreach ($itemList as $idex => $item)
        {
            $html .= sprintf("    '%s' [%d]\n",
                             $item->getTitle(),
                             $item->getWeight());
        }
        $html .= "\n -->\n";
         */

        /*
        $itemListParts = array();
        foreach ($itemList as $idex => $item)
        {
            array_push($itemListParts,
                       sprintf("%s[%d]",
                               $item->getTitle(),
                               $item->getWeight()) );
        }
        $itemListStr = implode(', ', $itemListParts);

        Connexions::log("Connexions_View_Helper_HtmlItemCloud:: "
                          . "sortBy[ {$this->_sortBy} ], "
                          . "sortOrder[ {$this->_sortOrder} ], "
                          . count($itemList) . " items "
                          . "in list[ {$itemListStr} ]");
        // */


        $uSortBy = ucfirst($this->_sortBy);

        // Create a sort function
        $sortFn = create_function('$a,$b',
                      '$aVal = $a->get'. $uSortBy .'();'
                    . '$bVal = $b->get'. $uSortBy .'();'
                    . '$cmp = '. ($this->_sortBy === 'title'
                                    ? 'strcasecmp($aVal, $bVal)'
                                    : '($aVal - $bVal)') .';'
                    .  ( (($this->_sortOrder === 'ASC') ||
                          ($this->_sortOrder === 'asc'))
                            ? ''
                              // Reverse the comparison to reverse the ASC sort
                            : '$cmp = ($cmp < 0 '
                              .         '? 1 '
                              .         ': ($cmp > 0 '
                              .             '? -1 '
                              .             ': 0));')
                    /*
                    . 'Connexions::log("HtmlItemCloud:cmp: '
                    .                   'a[{$aVal}], '
                    .                   'b[{$bVal}]: "'
                    .                   '.($cmp < 0'
                    .                       '? "&lt;"'
                    .                       ': ($cmp > 0'
                    .                           '? "&gt;"'
                    .                           ':"="))'
                    .                 ');'
                    */
                    . 'return  $cmp;');

        // Sort the item list
        $itemList->uasort($sortFn);

        if ($this->_displayStyle === self::STYLE_CLOUD)
        {
            // Zend_Tag_Cloud configuration
            $cloudConfig = array(
                // Make helpers in 'application/views/helpers' available.
                'prefixPath'            => array(
                    'prefix'    => 'Connexions_View_Helper',
                    'path'      => APPLICATION_PATH .'/views/helpers/'
                 ),
                'ItemList'              => &$itemList,
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
                            ? $this->_renderHighlights( $origItemList )
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
                            ? $this->_renderHighlights( $origItemList )
                            : '')
                  */
                  .   $this->_renderList($itemList)
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

        if ($this->view !== null)
            // Pass this new namespace into our view
            $this->view->viewNamespace = $namespace;

        $this->_namespace = $namespace;

        if (! @isset(self::$_initialized[$namespace]))
        {
            $view   = $this->view;
            $jQuery = $view->jQuery();

            $jQuery->addJavascriptFile($view->baseUrl('js/jquery.cookie.js'))
                   ->addJavascriptFile($view->baseUrl('js/ui.button.js'))
                   ->addOnLoad("init_{$namespace}Cloud();")
                   ->javascriptCaptureStart();

            ?>

/************************************************
 * Initialize display options.
 *
 */
function init_<?= $namespace ?>CloudDisplayOptions()
{
    var $displayOptions = $('.<?= $namespace ?>-displayOptions');
    var $form           = $displayOptions.find('form:first');
    var $submit         = $displayOptions.find(':submit');
    var $control        = $displayOptions.find('.control:first');

    // Add an opacity hover effect to the displayOptions
    $displayOptions.fadeTo(100, 0.5)
                   .hover(  function() {    // in
                                $displayOptions.fadeTo(100, 1.0);
                            },
                            function(e) {   // out
                                /* For at least Mac Firefox 3.5, for <select>
                                 * when we move into the options we receive a
                                 * 'moustout' event on the select box with a
                                 * related target of 'html'.  The wreaks havoc
                                 * by de-selecting the select box and it's
                                 * parent(s), causing the displayOptions to
                                 * disappear.  NOT what we want, so IGNORE the
                                 * event.
                                 */
                                if ((e.relatedTarget === undefined) ||
                                    (e.relatedTarget === null)      ||
                                    (e.relatedTarget.localName === 'html'))
                                {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    return false;
                                }

                                $displayOptions.fadeTo(100, 0.5);

                                // Also "close" the form
                                if ($form.is(':visible'))
                                    $control.click();
                            }
                         );

    // Click the 'Display Options' button to toggle the displayOptions pane
    $control.click(function(e) {
                e.preventDefault();
                e.stopPropagation();

                $form.toggle();
                $control.toggleClass('ui-state-active');
            });

    var $displayStyle   = $displayOptions.find('.displayStyle');
    var $style          = $displayStyle.find('input[name=<?= $namespace ?>Style]');

    /* Attach a data item to each display option identifying the display type
     * (pulled from the CSS class (<?= $namespace ?>Style-<type>)
     */
    $displayStyle.find('a.option,div.option a:first').each(function() {
                // Retrieve the new style value from the
                // '<?= $namespace ?>Style-*' class
                var style   = $(this).attr('class');
                var pos     = style.indexOf('<?= $namespace ?>Style-') + 6 +
                                                    <?= strlen($namespace) ?>;

                style = style.substr(pos);
                pos   = style.indexOf(' ');
                if (pos > 0)
                    style = style.substr(0, pos);

                // Save the style in a data item
                $(this).data('displayStyle', style);
            });

    // Allow only one display style to be selected at a time
    $displayStyle.find('a.option').click(function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $opt    = $(this);

                // Save the style in our hidden input
                $style.val( $opt.data('displayStyle') );

                $displayStyle.find('a.option-selected')
                                            .removeClass('option-selected');
                $opt.addClass('option-selected');

                // Trigger a change event on our form
                $form.change();
            });

    // Any change within the form should enable the submit button
    $form.change(function() {
                $submit.removeClass('ui-state-disabled')
                       .removeAttr('disabled')
                       .addClass('ui-state-default,ui-state-highlight');
            });

    // Bind to submit.
    $form.submit(function() {
                // Serialize all form values to an array...
                var settings    = $form.serializeArray();

                /* ...and set a cookie for each
                 *  <?= $namespace ?>SortBy
                 *  <?= $namespace ?>SortOrder
                 *  <?= $namespace ?>PerPage
                 *  <?= $namespace ?>Count
                 *  <?= $namespace ?>Style
                 */
                $(settings).each(function() {
                    $.log("Add Cookie: name[%s], value[%s]",
                          this.name, this.value);
                    $.cookie(this.name, this.value);
                });

                /* Finally, disable ALL inputs so our URL will have no
                 * parameters since we've stored them all in cookies.
                 */
                $form.find('input,select').attr('disabled', true);

                // let the form be submitted
            });

    return;
}

/************************************************
 * Initialize ui elements.
 *
 */
function init_<?= $namespace ?>Cloud()
{
    // Initialize display options
    init_<?= $namespace ?>CloudDisplayOptions();
}

            <?php
            $jQuery->javascriptCaptureEnd();

            self::$_initialized[$namespace] = true;
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
     *
     *  @return Connexions_View_Helper_HtmlItemCloud for a fluent interface.
     */
    public function setStyle($style)
    {
        $orig = $style;

        switch ($style)
        {
        case self::STYLE_LIST:
        case self::STYLE_CLOUD:
            break;

        default:
            $style = self::$defaults['displayStyle'];
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_HtmlItemCloud::'
                            . "setStyle({$orig}) == [ {$style} ]");
        // */
    
        $this->_displayStyle = $style;

        return $this;
    }

    /** @brief  Get the current style value.
     *
     *  @return The style value (self::STYLE_*).
     */
    public function getStyle()
    {
        return $this->_displayStyle;
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
     *  @param  itemList    A Connexions_Set_ItemList instance representing the
     *                      items to be presented;
     *
     *
     *  @return A string of HTML.
     */
    protected function _renderHighlights(Connexions_Set_ItemList   $itemList)
    {
        $html .= "<div class='highlights ui-corner-all'>"
              .   "<h4>Top {$this->_highlightCount}</h4>"
              .   "<ul>";

        foreach ($itemList as $idex => $item)
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

        $html .=  "<div class='field sortBy'>"          // sortBy {
              .    "<label   for='{$namespace}SortBy'>Sorted by</label>"
              .    "<select name='{$namespace}SortBy' "
              .              "id='{$namespace}SortBy' "
              .           "class='sort-by sort-by-{$this->_sortBy} "
              .                   "ui-input ui-state-default ui-corner-all'>";

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

        $html .=   "</select>"
              .   "</div>";                             // sortBy }


        $html .=  "<div class='field sortOrder'>"   // sortOrder {
              .    "<label for='{$namespace}SortOrder'>Sort order</label>";

        foreach (self::$orderTitles as $key => $title)
        {
            $html .= "<div class='field'>"
                  .   "<input type='radio' name='{$namespace}SortOrder' "
                  .                         "id='{$namespace}SortOrder-{$key}' "
                  .                      "value='{$key}'"
                  .          ($key == $this->_sortOrder
                                 ? " checked='true'" : "" ). " />"
                  .   "<label for='{$namespace}SortOrder-{$key}'>{$title}</label>"
                  .  "</div>";
        }

        $html .=   "<br class='clear' />"
              .   "</div>"                              // sortOrder }
              .   "<div class='field itemCounts'>"      // itemCounts {
              .    "<div class='field perPage'>"        // perPage {
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
              .                   "count' name='{$namespace}HighlightCount'>"
              .      "<!-- {$namespace}HighlightCount: {$this->_highlightCount} -->";

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
              .    "<br class='clear' />"
              .   "</div>";                             // itemCounts }

        $html .=  "<div class='field displayStyle'>"    // displayStyle {
              .    "<label for='{$namespace}Style'>Display</label>"
              .    "<input type='hidden' name='{$namespace}Style' "
              .          "value='{$this->_displayStyle}' />";

        $idex       = 0;
        $titleCount = count(self::$styleTitles);
        $parts      = array();
        foreach (self::$styleTitles as $key => $title)
        {
            $itemHtml = '';
            $cssClass = "option {$namespace}Style-{$key}";
            if ($key == $this->_displayStyle)
                $cssClass .= ' option-selected';

            $itemHtml .= "<a class='{$cssClass}' "
                      .      "href='?{$namespace}Style={$key}'>{$title}</a>";

            array_push($parts, $itemHtml);
        }
        $html .= implode("<span class='comma'>, </span>", $parts)
              .   "</div>";                             // displayStyle }

        $html .=   "<div id='buttons-global' class='buttons'>"
              .     "<button type='submit' "
              .            "class='ui-button ui-corner-all "
              .                  "ui-state-default ui-state-disabled' "
              .            "value='custom'"
              .         "disabled='true'>apply</button>"
              .    "</div>"
              .   "</form>"
              .  "</div>";

        return $html;
    }

}
