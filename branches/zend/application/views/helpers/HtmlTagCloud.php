<?php
/** @file
 *
 *  View helper to render a Tag Cloud in HTML.
 *
 *  Note: Within a view, use EITHER this helper:
 *          $view->htmlTagCloud();
 *
 *        OR the 'tagCloud.phtml' partial:
 *          $view->partial('tagCloud.phtml',
 *                         array('tagSet'   => &$tagSet,
 *                               'tagInfo'  => &$tagInfo));
 *
 *        Both make use of the Connexions_View_Helper_HtmlTagItem helper
 *        to render individual tag items.
 */
class Connexions_View_Helper_HtmlTagCloud extends Zend_View_Helper_Abstract
{
    static public   $perPageChoices         = array(50, 100, 250, 500);
    static public   $highlightCountChoices  = array(0,  5,   10);

    static public   $defaults               = array(
        'displayStyle'      => self::STYLE_CLOUD,
        'sortBy'            => self::SORT_BY_TITLE,
        'sortOrder'         => Model_TagSet::SORT_ORDER_ASC,

        'perPage'           => 100,
        'highlightCount'    => 5
    );


    const STYLE_LIST                        = 'list';
    const STYLE_CLOUD                       = 'cloud';

    static public   $styleTitles            = array(
        self::STYLE_LIST    => 'List',
        self::STYLE_CLOUD   => 'Cloud'
    );

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


    static protected $_initialized  = array();

    /** @brief  Set-able parameters . */
    protected       $_prefix            = 'tags';
    protected       $_showRelation      = true;

    protected       $_displayStyle      = null;
    protected       $_sortBy            = null;
    protected       $_sortOrder         = null;

    protected       $_perPage           = null;
    protected       $_highlightCount    = null;


    /** @brief  Set the View object.
     *  @param  view    The Zend_View_Interface
     *
     *  Override Zend_View_Helper_Abstract::setView() in order to initialize.
     *
     *  @return Zend_View_Helper_Abstract
     */
    public function setView(Zend_View_Interface $view)
    {
        parent::setView($view);

        if (! @isset(self::$_initialized[ $this->_prefix ]) )
        {
            $this->setPrefix($this->_prefix);
        }

        return $this;
    }

    /** @brief  Render an HTML version of a tag cloud.
     *  @param  itemList        A Connexions_Set_ItemList instance representing
     *                          the items to be presented;
     *  @param  style           The display style ( ['cloud'] | 'list' );
     *  @param  sortBy          The field to sort by ( ['title'] | 'count' );
     *  @param  sortOrder       Sort order ( ['ASC'] | 'DESC').
     *  @param  highlightCount  How many of items to highlight [ 5 ].
     *
     *  @param  hideOptions Should display options be hidden?
     *
     *  @return The HTML representation of a tag cloud.
     */
    public function htmlTagCloud($itemList          = null,
                                 $style             = null,
                                 $sortBy            = null,
                                 $sortOrder         = null,
                                 $highlightCount    = null,
                                 $hideOptions       = false)
    {
        if ( ! $itemList instanceof Connexions_Set_ItemList)
        {
            return $this;
        }

        return $this->render($itemList, $sortBy, $sortOrder,
                             $highlightCount, $hideOptions);
    }


    /** @brief  Render an HTML version of a tag cloud.
     *  @param  itemList        A Connexions_Set_ItemList instance representing
     *                          the items to be presented;
     *  @param  style           The display style ( ['cloud'] | 'list' );
     *  @param  sortBy          The field to sort by ( ['title'] | 'count' );
     *  @param  sortOrder       Sort order ( ['ASC'] | 'DESC').
     *  @param  highlightCount  How many of items to highlight [ 5 ].
     *  @param  hideOptions     Should display options be hidden?
     *
     *  @return The HTML representation of a tag cloud.
     */
    public function render(Connexions_Set_ItemList    $itemList,
                           $style           = null,
                           $sortBy          = null,
                           $sortOrder       = null,
                           $highlightCount  = null,
                           $hideOptions     = false)
    {
        $this->_perPage = count($itemList);

        $this->setStyle($style)
             ->setSortBy($sortBy)
             ->setSortOrder($sortOrder)
             ->setHighlightCount($highlightCount);


        $html = '';

        if ($this->_showRelation)
        {
            $html .= "<div class='tagRelation "
                  .              "connexions_sprites relation_ltr'>"
                  .   "&nbsp;"
                  .  "</div>";
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
                             $item->tag,
                             $item->userItemCount);
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

        Connexions::log("Connexions_View_Helper_HtmlTagCloud:: "
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
                    . 'Connexions::log("HtmlTagCloud:cmp: '
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
            // Create a Zend_Tag_Cloud renderer (by default, renders HTML)
            $cloud = new Zend_Tag_Cloud(
                    array(
                        /* Make the Connexions_View_Helper_HtmlTagItem helper
                         * available.
                         */
                        'prefixPath'            => array(
                            'prefix'    => 'Connexions_View_Helper',
                            'path'      => APPLICATION_PATH .'/views/helpers/'
                         ),
                        'ItemList'              => &$itemList,
                        'CloudDecorator'        => array(
                            'decorator'         => 'htmlCloud',
                            'options'           => array(
                                'HtmlTags'      => array(
                                    'ul'        => array(
                                        'class' =>'Tag_Cloud'
                                    )
                                )
                            )
                        ),
                        'TagDecorator'          => array(
                            /* Use the Connexions_View_Helper_HtmlTagItem helper
                             * to render tag items.
                             */
                            'decorator'         => 'htmlTagItem',   //'htmlTag',
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
                        )
                    ));

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

        // Return the rendered HTML
        return $html;
    }

    /** @brief  Set the cookie-name prefix.
     *  @param  prefix  A string prefix.
     *
     *  @return Connexions_View_Helper_HtmlTagCloud for a fluent interface.
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;

        if (! @isset(self::$_initialized[$prefix]))
        {
            $view   = $this->view;
            $jQuery = $view->jQuery();

            $jQuery->addJavascriptFile($view->baseUrl('js/jquery.cookie.js'))
                   ->addJavascriptFile($view->baseUrl('js/ui.button.js'))
                   ->addOnLoad("init_{$prefix}Cloud();")
                   ->javascriptCaptureStart();

            ?>

/************************************************
 * Initialize display options.
 *
 */
function init_<?= $prefix ?>CloudDisplayOptions()
{
    var $displayOptions = $('.<?= $prefix ?>-displayOptions');
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
    var $style          = $displayStyle.find('input[name=<?= $prefix ?>Style]');

    /* Attach a data item to each display option identifying the display type
     * (pulled from the CSS class (<?= $this->_prefix ?>Style-<type>)
     */
    $displayStyle.find('a.option,div.option a:first').each(function() {
                // Retrieve the new style value from the
                // '<?= $prefix ?>Style-*' class
                var style   = $(this).attr('class');
                var pos     = style.indexOf('<?= $prefix ?>Style-') + 6 +
                                                    <?= strlen($prefix) ?>;

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
                 *  <?= $prefix ?>SortBy
                 *  <?= $prefix ?>SortOrder
                 *  <?= $prefix ?>PerPage
                 *  <?= $prefix ?>Count
                 *  <?= $prefix ?>Style
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
function init_<?= $prefix ?>Cloud()
{
    // Initialize display options
    init_<?= $prefix ?>CloudDisplayOptions();

    //var $tagCloud   = $('form.tagCloud');
}

            <?php
            $jQuery->javascriptCaptureEnd();

            self::$_initialized[$prefix] = true;
        }

        return $this;
    }

    /** @brief  Get the current prefix.
     *
     *  @return The string prefix.
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /** @brief  Set whether or not the "relation" indicator is presented.
     *  @param  show    A boolean.
     *
     *  @return Connexions_View_Helper_HtmlTagCloud for a fluent interface.
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

    /** @brief  Set the current style.
     *  @param  style   A style value (self::STYLE_*)
     *
     *  @return Connexions_View_Helper_HtmlTagCloud for a fluent interface.
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
        Connexions::log('Connexions_View_Helper_HtmlTagCloud::'
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
     *  @return Connexions_View_Helper_HtmlTagCloud for a fluent interface.
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
        Connexions::log('Connexions_View_Helper_HtmlTagCloud::'
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
     *  @param  sortOrder   A sortOrder value (Model_TagSet::SORT_ORDER_*)
     *
     *  @return Connexions_View_Helper_HtmlTagCloud for a fluent interface.
     */
    public function setSortOrder($sortOrder)
    {
        $orig = $sortOrder;

        $sortOrder = strtoupper($sortOrder);
        switch ($sortOrder)
        {
        case Model_TagSet::SORT_ORDER_ASC:
        case Model_TagSet::SORT_ORDER_DESC:
            break;

        default:
            $sortOrder = self::$defaults['sortOrder'];
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_HtmlTagCloud::'
                            . "setSortOrder({$orig}) == [ {$sortOrder} ]");
        // */
    
        $this->_sortOrder = $sortOrder;

        return $this;
    }

    /** @brief  Get the current sortOrder value.
     *
     *  @return The sortOrder value (Model_TagSet::SORT_ORDER_*).
     */
    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    /** @brief  Set the number of items to highlight
     *  @param  highlightCount  The number of items to highlight
     *                          (self::$highlightCountChoices).
     *
     *  @return Connexions_View_Helper_HtmlTagCloud for a fluent interface.
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

    /** @brief  Render a tag list.
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
        $prefix = $this->_prefix;

        $html .= "<div class='displayOptions {$prefix}-displayOptions'>"
              .   "<div class='control ui-corner-all ui-state-default'>"
              .    "<a>Display Options</a>"
              .    "<div class='ui-icon ui-icon-triangle-1-s'>&nbsp;</div>"
              .   "</div>"
              .   "<form class='ui-state-active ui-corner-all' "
              .         "style='display:none;'>";

        $html .=  "<div class='field sortBy'>"          // sortBy {
              .    "<label   for='{$prefix}SortBy'>Sorted by</label>"
              .    "<select name='{$prefix}SortBy' "
              .              "id='{$prefix}SortBy' "
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
              .    "<label for='{$prefix}SortOrder'>Sort order</label>";

        foreach (self::$orderTitles as $key => $title)
        {
            $html .= "<div class='field'>"
                  .   "<input type='radio' name='{$prefix}SortOrder' "
                  .                         "id='{$prefix}SortOrder-{$key}' "
                  .                      "value='{$key}'"
                  .          ($key == $this->_sortOrder
                                 ? " checked='true'" : "" ). " />"
                  .   "<label for='{$prefix}SortOrder-{$key}'>{$title}</label>"
                  .  "</div>";
        }

        $html .=   "<br class='clear' />"
              .   "</div>"                              // sortOrder }
              .   "<div class='field itemCounts'>"      // itemCounts {
              .    "<div class='field perPage'>"        // perPage {
              .     "<label for='{$prefix}PerPage'>Show</label>"
              .     "<select class='ui-input ui-state-default ui-corner-all "
              .                   "count' name='{$prefix}PerPage'>"
              .      "<!-- {$prefix}PerPage: {$this->_perPage} -->";

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
              .     "<label for='{$prefix}HighlightCount'>top</label>"
              .     "<select class='ui-input ui-state-default ui-corner-all "
              .                   "count' name='{$prefix}HighlightCount'>"
              .      "<!-- {$prefix}HighlightCount: {$this->_highlightCount} -->";

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
              .    "<label for='{$prefix}Style'>Display</label>"
              .    "<input type='hidden' name='{$prefix}Style' "
              .          "value='{$this->_displayStyle}' />";

        $idex       = 0;
        $titleCount = count(self::$styleTitles);
        $parts      = array();
        foreach (self::$styleTitles as $key => $title)
        {
            $itemHtml = '';
            $cssClass = "option {$prefix}Style-{$key}";
            if ($key == $this->_displayStyle)
                $cssClass .= ' option-selected';

            $itemHtml .= "<a class='{$cssClass}' "
                      .      "href='?{$prefix}Style={$key}'>{$title}</a>";

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
