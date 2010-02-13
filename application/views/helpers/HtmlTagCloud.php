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
    protected $_sortBy      = 'title';
    protected $_sortOrder   = 'ASC';
    protected $_topItems    = 5;


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
    $control.find('a:first, .ui-icon:first')
                                         // Let it bubble up
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
                 *  itemsSortBy
                 *  itemsSortOrder
                 *  perPage
                 *  itemsStyle
                 *      and possibly
                 *      itemsStyleCustom[ ... ]
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

    var $tagItems   = $('form.tagItems');
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
     *  @param  topItems    How many of the top items to specially render [ 5 ].
     *  @param  hideOptions Should display options be hidden?
     *
     *  @return The HTML representation of a tag cloud.
     */
    public function htmlTagCloud($itemList      = null,
                                 $sortBy        = 'title',
                                 $sortOrder     = 'ASC',
                                 $topItems      = 5,
                                 $hideOptions   = false)
    {
        if ( ! $itemList instanceof Connexions_Set_ItemList)
        {
            return $this;
        }

        return $this->render($itemList, $sortBy, $sortOrder,
                             $topItems, $hideOptions);
    }


    /** @brief  Render an HTML version of a tag cloud.
     *  @param  itemList    A Connexions_Set_ItemList instance representing the
     *                      items to be presented;
     *  @param  sortBy      The tag field to sort by ( ['title'] | 'count' );
     *  @param  sortOrder   Sort order ( ['ASC'] | 'DESC').
     *  @param  topItems    How many of the top items to specially render [ 5 ].
     *  @param  hideOptions Should display options be hidden?
     *
     *  @return The HTML representation of a tag cloud.
     */
    public function render(Connexions_Set_ItemList    $itemList,
                           $sortBy        = 'title',
                           $sortOrder     = 'ASC',
                           $topItems      = 5,
                           $hideOptions   = false)
    {
        if ($sortBy === 'count')
            $this->_sortBy = 'count';
        if (! empty($sortOrder))
        {
            switch (strtoupper($sortOrder))
            {
            case 'DESC':
            case 'ASC':
                $this->_sortOrder = $sortOrder;
            }
        }
        $this->_topItems = $topItems;



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
        $html .= "\n<!-- First {$this->_topItems} tags:\n";
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
                          . "sortBy[ {$this->_sortBy} ], "
                          . "sortOrder[ {$this->_sortOrder} ], "
                          . count($itemList) . " items "
                          . "in list[ {$itemListStr} ]");
        // */


        // Create a sort function
        $sortFn = create_function('$a,$b',
                      '$aStr = $a->'. $this->_sortBy .';'
                    . '$bStr = $b->'. $this->_sortBy .';'
                    . '$cmp = strcasecmp($aStr, $bStr);'
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
                    .                   'a[{$aStr}], '
                    .                   'b[{$bStr}]: "'
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

        // Render the HTML
        $html .= "<div class='cloud'>"
              .   ($this->_topItems > 0
                        ? $this->_renderTopTags( $origItemList )    //$itemList)
                        : '')
              .   $cloud->render()
              .  "<br class='clear' />"
              .  "</div>";

        // Return the rendered HTML
        return $html;
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Render the top tags (by count).
     *  @param  itemList    A Connexions_Set_ItemList instance representing the
     *                      items to be presented;
     *
     *
     *  @return A string of HTML.
     */
    protected function _renderTopTags(Connexions_Set_ItemList   $itemList)
    {
        $html .= "<div class='topItems ui-corner-all'>"
              .   "<h4>Top {$this->_topItems}</h4>"
              .   "<ul>";

        foreach ($itemList as $idex => $item)
        {
            if ($idex > $this->_topItems)
                break;

            $html .= "<li>";

            $url    = $item->getParam('url');
            $weight = number_format($item->getWeight());
            if (empty($url))
                $html .= sprintf("<span class='item'>%s</span>",
                                    htmlSpecialChars($item->getTitle()));
            else
                $html .= sprintf('<a class="tag" href="%s">%s</a>',
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
              .         "style='display:none;'>"
              .    "<div id='buttons-global' class='buttons'>"
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
