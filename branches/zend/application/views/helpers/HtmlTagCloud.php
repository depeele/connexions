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
    static public   $tagsMaxChoices     = array(50, 100, 250, 500);
    static public   $tagsTopChoices     = array(0,  5,   10);

    const STYLE_LIST                    = 'list';
    const STYLE_CLOUD                   = 'cloud';

    static public   $styleTitles        = array(
        self::STYLE_LIST    => 'List',
        self::STYLE_CLOUD   => 'Cloud'
    );

    const SORT_BY_TAG               = 'tag';
    const SORT_BY_USER_ITEM_COUNT   = 'userItemCount';

    static public   $sortTitles     = array(
                    self::SORT_BY_TAG                   => 'Tag',
                    self::SORT_BY_USER_ITEM_COUNT       => 'Item Count'
                );

    static public   $orderTitles    = array(
                    Model_UserItemSet::SORT_ORDER_ASC   => 'Ascending',
                    Model_UserItemSet::SORT_ORDER_DESC  => 'Descending'
                );


    /** @brief  Set-able parameters with default values. */
    protected       $_tagsStyle     = self::STYLE_CLOUD;
    protected       $_tagsSortBy    = self::SORT_BY_TAG;
    protected       $_tagsSortOrder = Model_TagSet::SORT_ORDER_ASC;

    protected       $_tagsMax       = 100;
    protected       $_tagsTop       = 5;


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

        $jQuery =  $view->jQuery();

        $jQuery->addJavascriptFile($view->baseUrl('js/jquery.cookie.js'))
               ->addJavascriptFile($view->baseUrl('js/ui.button.js'))
               ->addOnLoad('init_tagItems();')
               ->javascriptCaptureStart();

        ?>

/************************************************
 * Initialize display options.
 *
 */
function init_tagItemsDisplayOptions()
{
    var $displayOptions = $('#tagItems .displayOptions');
    var $form           = $displayOptions.find('form:first');
    var $submit         = $displayOptions.find(':submit');
    var $control        = $displayOptions.find('.control:first');

    // Click the 'Display Options' button to toggle the displayOptions pane
    $control.click(function(e) {
                e.preventDefault();
                e.stopPropagation();

                $form.toggle();
                $control.toggleClass('ui-state-active');
            });

    /* For the anchor within the 'Display Options' button, disable the default
     * browser action but allow the event to bubble up to the click handler on
     * the 'Display Options' button.
     */
    $control.find('a')  // Let it bubble up
              .click(function(e) {e.preventDefault(); });

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
                 *  tagsSortBy
                 *  tagsSortOrder
                 *  tagsMax
                 *  tagsTop
                 *  tagsStyle
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
function init_tagItems()
{
    // Initialize display options
    init_tagItemsDisplayOptions();

    //var $tagItems   = $('form.tagItems');
}

        <?php
        $jQuery->javascriptCaptureEnd();

        return $this;
    }

    /** @brief  Render an HTML version of a tag cloud.
     *  @param  itemList    A Connexions_Set_ItemList instance representing the
     *                      items to be presented;
     *  @param  sortBy      The tag field to sort by ( ['title'] | 'count' );
     *  @param  sortOrder   Sort order ( ['ASC'] | 'DESC').
     *  @param  tagsTop     How many of the top items to specially render [ 5 ].
     *  @param  hideOptions Should display options be hidden?
     *
     *  @return The HTML representation of a tag cloud.
     */
    public function htmlTagCloud($itemList      = null,
                                 $sortBy        = 'title',
                                 $sortOrder     = 'ASC',
                                 $tagsTop       = 5,
                                 $hideOptions   = false)
    {
        if ( ! $itemList instanceof Connexions_Set_ItemList)
        {
            return $this;
        }

        return $this->render($itemList, $sortBy, $sortOrder,
                             $tagsTop, $hideOptions);
    }


    /** @brief  Render an HTML version of a tag cloud.
     *  @param  itemList    A Connexions_Set_ItemList instance representing the
     *                      items to be presented;
     *  @param  sortBy      The tag field to sort by ( ['title'] | 'count' );
     *  @param  sortOrder   Sort order ( ['ASC'] | 'DESC').
     *  @param  tagsTop     How many of the top items to specially render [ 5 ].
     *  @param  hideOptions Should display options be hidden?
     *
     *  @return The HTML representation of a tag cloud.
     */
    public function render(Connexions_Set_ItemList    $itemList,
                           $sortBy        = null,
                           $sortOrder     = null,
                           $tagsTop       = null,
                           $hideOptions   = null)
    {
        $this->_tagsMax = count($itemList);

        if (! empty($sortBy))       $this->setSortBy($sortBy);
        if (! empty($sortOrder))    $this->setSortOrder($sortOrder);
        if (  isset($tagsTop))      $this->setTagsTop($tagsTop);


        $html = '';
        if ($hideOptions !== true)
        {
            /*
            $html .= "<ul class='tagsHeader'>"
                  .   "<li class='active ui-corner-top'>Tags</li>"
                  .   "<br class='clear' />"
                  .  "</ul>";
            */

            $html .= "<div class='tagRelation "
                  .              "connexions_sprites relation_ltr'>"
                  .   "&nbsp;"
                  .  "</div>"
                  .  $this->_renderDisplayOptions()
                  .  "<br class='clear' />";
        }

        $origItemList = clone $itemList;
        /*
        $html .= "\n<!-- First {$this->_tagsTop} tags:\n";
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
                       sprintf("%s[%d, %d]",
                               $item->tag, $item->tagId,
                               $item->userItemCount) );
        }
        $itemListStr = implode(', ', $itemListParts);

        Connexions::log("Connexions_View_Helper_HtmlTagCloud:: "
                          . "tagsSortBy[ {$this->_tagsSortBy} ], "
                          . "tagsSortOrder[ {$this->_tagsSortOrder} ], "
                          . count($itemList) . " items "
                          . "in list[ {$itemListStr} ]");
        // */


        // Create a sort function
        $sortFn = create_function('$a,$b',
                      '$aVal = $a->'. $this->_tagsSortBy .';'
                    . '$bVal = $b->'. $this->_tagsSortBy .';'
                    . '$cmp = '. ($this->_tagsSortBy === 'tag'
                                    ? 'strcasecmp($aVal, $bVal)'
                                    : '($aVal - $bVal)') .';'
                    .  ( (($this->_tagsSortOrder === 'ASC') ||
                          ($this->_tagsSortOrder === 'asc'))
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

        if ($this->_tagsStyle === self::STYLE_CLOUD)
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
                  .   ($this->_tagsTop > 0
                            ? $this->_renderTopCount( $origItemList )
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
                  .   ($this->_tagsTop > 0
                            ? $this->_renderTopCount( $origItemList )
                            : '')
                  */
                  .   $this->_renderList($itemList)
                  .   "<br class='clear' />"
                  .  "</div>";
        }

        // Return the rendered HTML
        return $html;
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
            $style = self::STYLE_CLOUD;
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_HtmlTagCloud::'
                            . "setStyle({$orig}) == [ {$style} ]");
        // */
    
        $this->_tagsStyle = $style;

        return $this;
    }

    /** @brief  Get the current style value.
     *
     *  @return The style value (self::STYLE_*).
     */
    public function getStyle()
    {
        return $this->_tagsStyle;
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
        case self::SORT_BY_TAG:
        case self::SORT_BY_USER_ITEM_COUNT:
            break;

        default:
            $sortBy = self::SORT_BY_TAG;
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_HtmlTagCloud::'
                            . "setSortBy({$orig}) == [ {$sortBy} ]");
        // */

        $this->_tagsSortBy = $sortBy;

        return $this;
    }

    /** @brief  Get the current sortBy value.
     *
     *  @return The sortBy value (self::SORT_BY_*).
     */
    public function getSortBy()
    {
        return $this->_tagsSortBy;
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
            $sortOrder = Model_TagSet::SORT_ORDER_ASC;
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_HtmlTagCloud::'
                            . "setSortOrder({$orig}) == [ {$sortOrder} ]");
        // */
    
        $this->_tagsSortOrder = $sortOrder;

        return $this;
    }

    /** @brief  Get the current sortOrder value.
     *
     *  @return The sortOrder value (Model_TagSet::SORT_ORDER_*).
     */
    public function getSortOrder()
    {
        return $this->_tagsSortOrder;
    }

    /** @brief  Set the number of top tags to present
     *  @param  tagsTop     The number of top tags (self::$tagsTopChoices).
     *
     *  @return Connexions_View_Helper_HtmlTagCloud for a fluent interface.
     */
    public function setTagsTop($tagsTop)
    {
        if (in_array($tagsTop, self::$tagsTopChoices))
        {
            $this->_tagsTop = $tagsTop;
        }

        return $this;
    }

    /** @brief  Get the current tagsTop value.
     *
     *  @return The tagsTop value.
     */
    public function getTagsTop()
    {
        return $this->_tagsTop;
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

    /** @brief  Render the top tags (by count).
     *  @param  itemList    A Connexions_Set_ItemList instance representing the
     *                      items to be presented;
     *
     *
     *  @return A string of HTML.
     */
    protected function _renderTopCount(Connexions_Set_ItemList   $itemList)
    {
        $html .= "<div class='tagsTop ui-corner-all'>"
              .   "<h4>Top {$this->_tagsTop}</h4>"
              .   "<ul>";

        foreach ($itemList as $idex => $item)
        {
            if ($idex > $this->_tagsTop)
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
        $html .= "<div class='displayOptions'>"
              .   "<div class='control ui-corner-all ui-state-default'>"
              .    "<a>Display Options</a>"
              .    "<div class='ui-icon ui-icon-triangle-1-s'>&nbsp;</div>"
              .   "</div>"
              .   "<form class='ui-state-active ui-corner-all' "
              .         "style='display:none;'>";

        $html .=  "<div class='field tagsSortBy'>"  // tagsSortBy {
              .    "<label   for='tagsSortBy'>Sorted by</label>"
              .    "<select name='tagsSortBy' "
              .              "id='tagsSortBy' "
              .           "class='sort-by sort-by-{$this->_tagsSortBy} "
              .                   "ui-input ui-state-default ui-corner-all'>";

        foreach (self::$sortTitles as $key => $title)
        {
            $isOn = ($key == $this->_tagsSortBy);
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
              .   "</div>";                             // tagsSortBy }


        $html .=  "<div class='field tagsSortOrder'>"   // tagsSortOrder {
              .    "<label for='tagsortOrder'>Sort order</label>";

        foreach (self::$orderTitles as $key => $title)
        {
            $html .= "<div class='field'>"
                  .   "<input type='radio' name='tagsSortOrder' "
                  .                         "id='tagsSortOrder-{$key}' "
                  .                      "value='{$key}'"
                  .          ($key == $this->_tagsSortOrder
                                 ? " checked='true'" : "" ). " />"
                  .   "<label for='tagsSortOrder-{$key}'>{$title}</label>"
                  .  "</div>";
        }

        $html .=   "<br class='clear' />"
              .   "</div>"                              // tagsSortOrder }
              .   "<div class='field tagsMax'>"         // tagsMax {
              .    "<label for='tagsMax'>Tag count</label>"
              .    "<select class='ui-input ui-state-default "
              .                  "count' name='tagsMax'>"
              .     "<!-- tagsMax: {$this->_tagsMax} -->";

        foreach (self::$tagsMaxChoices as $countOption)
        {
            $html .= "<option value='{$countOption}'"
                  .           ($countOption == $this->_tagsMax
                                 ? ' selected'
                                 : '')
                  .                     ">{$countOption}</option>";
        }
    
        $html .=   "</select>"
              .    "<br class='clear' />"
              .   "</div>"                              // tagsMax }
              .   "<div class='field tagsTop'>"         // tagsTop {
              .    "<label for='tagsTop'>Show top</label>"
              .    "<select class='ui-input ui-state-default "
              .                  "count' name='tagsTop'>"
              .     "<!-- tagsTop: {$this->_tagsTop} -->";

        foreach (self::$tagsTopChoices as $countOption)
        {
            $label = ($countOption === 0
                            ? 'none'
                            : $countOption);

            $html .= "<option value='{$countOption}'"
                  .           ($countOption == $this->_tagsTop
                                 ? ' selected'
                                 : '')
                  .                     ">{$label}</option>";
        }
    
        $html .=   "</select>"
              .    "<br class='clear' />"
              .   "</div>";                             // tagsTop }

        $html .=  "<div class='field tagsStyle'>"       // tagsStyle {
              .    "<label for='tagsStyle'>Display</label>"
              .    "<input type='hidden' name='tagsStyle' "
              .          "value='{$this->_tagsStyle}' />";

        $idex       = 0;
        $titleCount = count(self::$styleTitles);
        $parts      = array();
        foreach (self::$styleTitles as $key => $title)
        {
            $itemHtml = '';
            $cssClass = "option tagsStyle-{$key}";
            if ($key == $this->_tagsStyle)
                $cssClass .= ' option-selected';

            $itemHtml .= "<a class='{$cssClass}' "
                      .      "href='?tagsStyle={$key}'>{$title}</a>";

            array_push($parts, $itemHtml);
        }
        $html .= implode("<span class='comma'>, </span>", $parts)
              .   "</div>";                             // tagsStyle }

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
