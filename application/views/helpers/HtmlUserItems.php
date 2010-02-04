<?php
/** @file
 *
 *  View helper to render a paginated set of User Items / Bookmarks in HTML.
 */
class Connexions_View_Helper_HtmlUserItems extends Zend_View_Helper_Abstract
{
    static protected    $_initialized   = false;

    const STYLE_TITLE               = 'title';
    const STYLE_REGULAR             = 'regular';
    const STYLE_FULL                = 'full';
    const STYLE_CUSTOM              = 'custom';

    static public $styleTitles      = array(
        self::STYLE_TITLE   => 'Title',
        self::STYLE_REGULAR => 'Regular',
        self::STYLE_FULL    => 'Full',
        self::STYLE_CUSTOM  => 'Custom'
    );

    static public $styleParts       = array(
        self::STYLE_TITLE   => array(
            'meta:countTaggers'         => true,
            'meta:rating:stars:average' => false,
            'meta:rating:stars:owner'   => false,
            'meta:rating:meta'          => false,
            'itemName'                  => true,
            'url'                       => false,
            'description'               => false,
            'userName'                  => false,
            'tags'                      => false,
            'dates:tagged'              => false,
            'dates:updated'             => false
        ),
        self::STYLE_REGULAR => array(
            'meta:countTaggers'         => true,
            'meta:rating:stars:average' => true,
            'meta:rating:stars:owner'   => true,
            'meta:rating:meta'          => false,
            'itemName'                  => true,
            'url'                       => false,
            'description'               => true,
            'userName'                  => true,
            'tags'                      => true,
            'dates:tagged'              => false,
            'dates:updated'             => false
        ),
        self::STYLE_FULL    => array(
            'meta:countTaggers'         => true,
            'meta:rating:stars:average' => true,
            'meta:rating:stars:owner'   => true,
            'meta:rating:meta'          => true,
            'itemName'                  => true,
            'url'                       => true,
            'description'               => true,
            'userName'                  => true,
            'tags'                      => true,
            'dates:tagged'              => true,
            'dates:updated'             => true
        ),
        self::STYLE_CUSTOM  => array(
            'meta:countTaggers'         => true,
            'meta:rating:stars:average' => true,
            'meta:rating:stars:owner'   => true,
            'meta:rating:meta'          => true,
            'itemName'                  => true,
            'url'                       => true,
            'description'               => true,
            'userName'                  => true,
            'tags'                      => true,
            'dates:tagged'              => true,
            'dates:updated'             => true
        )
    );

    const SORT_BY_DATE_TAGGED       = 'taggedOn';
    const SORT_BY_DATE_UPDATED      = 'dateUpdated';
    const SORT_BY_TITLE             = 'name';
    const SORT_BY_RATING            = 'rating';
    const SORT_BY_USER_COUNT        = 'userCount';

    static public $sortTitles       = array(
                    self::SORT_BY_DATE_TAGGED   => 'Tag Date',
                    self::SORT_BY_DATE_UPDATED  => 'Update Date',
                    self::SORT_BY_TITLE         => 'Title',
                    self::SORT_BY_RATING        => 'Rating',
                    self::SORT_BY_USER_COUNT    => 'User Count'
                );

    static public $orderTitles      = array(
                    Model_UserItemSet::SORT_ORDER_ASC   => 'Ascending',
                    Model_UserItemSet::SORT_ORDER_DESC  => 'Descending'
                );

    protected function _initialize()
    {
        if (self::$_initialized)
            return;

        $view   =& $this->view;
        $jQuery = $view->jQuery();

        $jQuery->addJavascriptFile($view->baseUrl('js/jquery.cookie.js'))
               ->addJavascriptFile($view->baseUrl('js/ui.stars.js'))
               ->addJavascriptFile($view->baseUrl('js/ui.checkbox.js'))
               ->addJavascriptFile($view->baseUrl('js/ui.button.js'))
               ->addJavascriptFile($view->baseUrl('js/ui.input.js'))
               ->addOnLoad('init_userItems();')
               ->addOnLoad('init_displayOptions();')
               ->javascriptCaptureStart();

        ?>

/************************************************
 * Initialize display options.
 *
 */
function init_displayOptions()
{
    var $displayOptions = $('.displayOptions');
    var $control        = $displayOptions.find('.control:first');

    $control/*.hover( // in
                    function(e) {
                        $control.addClass('ui-state-hover');
                    },
                    // out
                    function(e) {
                        $control.removeClass('ui-state-hover');
                    })*/
            .click(function(e) {
                e.preventDefault();
                e.stopPropagation();

                $displayOptions.find('form:first').toggle();
                $control.toggleClass('ui-state-active');
            });

    $control.find('a:first, .ui-icon:first')
                                         // Let it bubble up
                    .click(function(e) {e.preventDefault(); });

    var $displayStyle   = $displayOptions.find('.displayStyle');
    var $cControl       = $displayStyle.find('.control:first');

    $cControl/*.hover(// in
                    function(e) {
                        $cControl.addClass('ui-state-hover');
                    },
                    // out
                    function(e) {
                        $cControl.removeClass('ui-state-hover');
                    })*/
            .click(function(e) {
                e.preventDefault();
                e.stopPropagation();

                $displayStyle.find('.custom.items').toggle();
                $cControl.toggleClass('ui-state-active');
            });
    $cControl.find('a:first,b:first,.ui-icon:first')
                                         // Let it bubble up
                    .click(function(e) { e.preventDefault(); });

    // Hover effects
    $('.displayOptions .control, .displayOptions select, '+
      '.displayOptions button,'+
      '.pagination button,       .pagination select').hover(
                    // in
                    function(e) {
                        $(this).addClass('ui-state-hover');
                    },
                    // out
                    function(e) {
                        $(this).removeClass('ui-state-hover');
                    });

    return;

    /* Attach ui.input to the input field with defined 'emptyText' and a
     * validation callback to enable/disable the submit button based upon
     * whether or not there is text in the search box.
    $displayOptions.find('input[emptyText]').input();
     */

    $displayOptions.fadeTo(100, 0.5)
                   .hover(  // In
                            function(event) {
                                $(this).fadeTo(100, 1.0);
                            },
                            // Out
                            function(event) {
                                $(this).fadeTo(100, 0.5);
                            }
                   )
                   // Resize the sort-order button widths
                   .find('.sort-order .ui-radio-button')
                                .width('16px');

    /* Add logic to mirror the state of the underlying radio button to the
     * container.  Also, when the container is clicked, toggle the underlying
     * radio button.
     */
    $displayOptions.find('.ui-radio-button').each(function() {
        var $item   = $(this);
        var $radio  = $item.find('input:radio');

        /* For the underlysing radio button, add a change handler to mirror
         * state, and hide the button.
         */
        $radio.change(function() {
                        if ($radio.is(':checked'))
                            $item.parent().removeClass('ui-state-default')
                                          .addClass('ui-radio-on '+
                                                    'ui-state-highlight');
                        else
                            $item.parent().addClass('ui-state-default')
                                          .removeClass('ui-radio-on '+
                                                       'ui-state-highlight');
                      })
              .hide();

        /* Proxy clicks on the container through to the underlying radio button
         * (this should cause a 'change' event that we handle above.
         */
        $item.click(function(e) {
            if (! $radio.is(':checked'))
            {
                $item.parent().parent().find('input:radio')
                        .removeAttr('checked')
                        .trigger('change');

                $radio.attr('checked', true)
                      .trigger('change');
            }
        });
    });


    /*
    // All form items will set a cookie
    $displayOptions.find('input,select').change(function() {
        var name    = $(this).attr('name');
        if (name.length < 0)
            return;

        $.cookie(name, $(this).val());

        // Force a 'submit'
        $displayOptions.submit();
    });

    $displayOptions.find(':submit').click(function() {
        var page    = $(this).val();
        //$.cookie('page', page);
    });

    $displayOptions.submit(function(e) {
        // Disable all displayOption form elements -- this removes these items
        // from form serialization.
        //
        // We've already set any settings as cookies and don't want these
        // showing up in the URL as well.
        $displayOptions.find('input,:not(input:submit),button,select')
                .attr('disabled', true);

        var ser = $displayOptions.serialize();
        var a   = 1;
    });
    */
}

/************************************************
 * Initialize ui elements.
 *
 */
function init_userItems()
{
    var $userItems  = $('form.userItem');

    $userItems.find('.status,.control')
            .fadeTo(100, 0.5)
            .hover( // In
                    function() {
                        $(this).fadeTo(100, 1.0);
                    },
                    // Out
                    function() {
                        $(this).fadeTo(100, 0.5);
                    });
    // Favorite
    $userItems.find('input[name=isFavorite]').checkbox({
        css:        'connexions_sprites',
        cssOn:      'star_fill',
        cssOff:     'star_empty',
        titleOn:    'Favorite: click to remove from Favorites',
        titleOff:   'Click to add to Favorites',
        useElTitle: false,
        hideLabel:  true
    });

    // Privacy
    $userItems.find('input[name=isPrivate]').checkbox({
        css:        'connexions_sprites',
        cssOn:      'lock_fill',
        cssOff:     'lock_empty',
        titleOn:    'Private: click to share',
        titleOff:   'Public: click to mark as private',
        useElTitle: false,
        hideLabel:  true
    });

    // Rating - average and user
    //$userItems.find('.rating .stars .average').stars({split:2});
    $userItems.find('.rating .stars .owner').stars();
}

        <?php
        $jQuery->javascriptCaptureEnd();

        self::$_initialized = true;
    }

    /** @brief  Render an HTML version of a paginated set of User Items.
     *  @param  paginator       The Zend_Paginator representing the items to
     *                          be presented.
     *  @param  owner           A Model_User instance representing the
     *                          owner of the current area OR
     *                          a String '*' indicating ALL users;
     *  @param  viewer          A Model_User instance representing the
     *                          current viewer;
     *  @param  tagInfo         A Connexions_Set_Info instance containing
     *                          information about the requested tags;
     *  @param  style           The style to use for each item
     *                          (Connexions_View_Helper_HtmlUserItems::
     *                                                          STYLE_*);
     *  @param  sortBy          The field used to sort the items
     *                          (Connexions_View_Helper_HtmlUserItems::
     *                                                      SORT_BY_*);
     *  @param  sortOrder       The sort order
     *                          (Model_UserItemSet::SORT_ORDER_ASC |
     *                           Model_UserItemSet::SORT_ORDER_DESC)
     *
     *  @return The HTML representation of the user items.
     */
    public function htmlUserItems(Zend_Paginator            $paginator,
                                  /* Model_User | String */ $owner,
                                  Model_User                $viewer,
                                  Connexions_Set_info       $tagInfo,
                                  $style        = null,
                                  $sortBy       = null,
                                  $sortOrder    = null)
    {
        $this->_initialize();

        /*
        Connexions::log("Connexions_View_Helper_HtmlUserItems: "
                            . "style[ {$style} ], "
                            . "sortBy[ {$sortBy} ], "
                            . "sortOrder[ {$sortOrder} ]");
        // */

        switch ($style)
        {
        case self::STYLE_TITLE:
        case self::STYLE_FULL:
        case self::STYLE_REGULAR:
        case self::STYLE_CUSTOM:
            break;

        default:
            $style = self::STYLE_REGULAR;
            break;
        }

        switch ($sortBy)
        {
        case self::SORT_BY_DATE_TAGGED:
        case self::SORT_BY_DATE_UPDATED:
        case self::SORT_BY_TITLE:
        case self::SORT_BY_RATING:
        case self::SORT_BY_USER_COUNT:
            break;

        default:
            $sortBy = self::SORT_BY_DATE_TAGGED;
            break;
        }

        switch (strtoupper($sortOrder))
        {
        case Model_UserItemSet::SORT_ORDER_ASC:
        case Model_UserItemSet::SORT_ORDER_DESC:
            break;

        default:
            $sortOrder = Model_UserItemSet::SORT_ORDER_DESC;
            break;
        }


        /*
        Connexions::log("Connexions_View_Helper_HtmlUserItems: "
                            . "validated to: "
                            . "style[ {$style} ], "
                            . "styleTitle[ ".self::$styleTitles[$style]." ], "
                            . "sortBy[ {$sortBy} ], "
                            . "sortByTitle[ ".self::$sortTitles[$sortBy]." ], "
                            . "sortOrder[ {$sortOrder} ], "
                            . "sortOrderTitle[ ".
                                        self::$sortTitles[$sortBy]." ]");
        // */

        $html = '';

        $ownerStr = (String)$owner;
        if ($ownerStr === '*')
        {
            $ownerStr = 'Bookmarks';
            $ownerUrl = $this->view->baseUrl('/tagged');
        }
        else
        {
            $ownerUrl = $this->view->baseUrl($ownerStr);
        }

        $html .= $this->view->htmlItemScope($paginator,
                                            $tagInfo,
                                            'Tags',
                                            'tags',
                                            array($ownerStr => $ownerUrl))
              .  $this->view->paginationControl($paginator,
                                                null,        // style
                                                'paginationControl.phtml',
                                                array('excludeInfo' => true,
                                                      'cssClass'    =>
                                                            'pagination-top'))
              .  $this->_renderDisplayOptions($style, $sortBy, $sortOrder);

        if (count($paginator))
        {
            $html .= "<ul class='items'>";

            $show = self::$styleParts[$style];
            foreach ($paginator as $idex => $userItem)
            {
                $html .= $this->view->partial('userItem.phtml',
                                              array(
                                                  'index'    =>  $idex,
                                                  'userItem' => &$userItem,
                                                  'viewer'   => &$viewer,
                                                  'showParts'=> &$show));
            }

            $html .= "</ul>";
        }


        $html .= $paginator
              .  "<br class='clear' />\n";

        // Return the rendered HTML
        return $html;
    }

    protected function _renderDisplayOptions($style, $sortBy, $sortOrder)
    {
        $html = "<div class='displayOptions'>"      // displayOptions {
              .  "<div class='control ui-state-default'>"
              .   "<a >Display Options</a>"
              .   "<div class='ui-icon ui-icon-triangle-1-s'>"
              .    "&nbsp;"
              .   "</div>"
              .  "</div>"
              .  "<form style='display:none;' "
              .        "class='ui-state-active'>";  // form {

        $html .=  "<div class='field sortBy'>"  // itemsSortBy {
              .    "<label   for='itemsSortBy'>Sorted by</label>"
              .    "<select name='itemsSortBy' "
              .              "id='itemsSortBy' "
              .           "class='sort-by sort-by-{$sortBy} "
              .                   "ui-input ui-state-default ui-corner-all'>";

        foreach (self::$sortTitles as $key => $title)
        {
            $html .= $this->_renderOption('sortBy',
                                          $key,
                                          $title,
                                          $key == $sortBy);
        }

        $html .=   "</select>"
              .   "</div>";                             // itemsSortBy }


        $html .=  "<div class='field sortOrder'>"       // itemsSortOrder {
              .    "<label for='itemSortOrder'>Sort order</label>";

        foreach (self::$orderTitles as $key => $title)
        {
            $html .= "<div class='field'>"
                  .   "<input type='radio' name='itemsSortOrder' "
                  .                         "id='itemsSortOrder-{$key}' "
                  .                      "value='{$key}'"
                  .          ($key == $sortOrder
                                 ? " checked='true'" : "" ). " />"
                  .   "<label for='itemsSortOrder-{$key}'>{$title}</label>"
                  .  "</div>";
        }

        $html .=   "<br class='clear' />"
              .   "</div>";                             // itemsSortOrder }

        $html .=  "<div class='field displayStyle'>"    // itemsStyle {
              .    "<label for='itemsStyle'>Display</label>";

        $idex       = 0;
        $titleCount = count(self::$styleTitles);
        $parts      = array();
        foreach (self::$styleTitles as $key => $title)
        {
            $itemHtml = '';
            $cssClass = 'option';

            if ($key === self::STYLE_CUSTOM)
            {
                $itemHtml .= "<div class='{$cssClass} control "
                          .     ($style !== self::STYLE_CUSTOM
                                    ? "ui-state-default"
                                    : "ui-state-active") ."'>";
                $cssClass  = '';
            }

            if ($key == $style)
                $itemHtml .= "<b class='{$cssClass}'>{$title}</b>";
            else
                $itemHtml .= "<a class='{$cssClass}' "
                          .      "href='?itemsStyle={$key}'>{$title}</a>";

            if ($key === self::STYLE_CUSTOM)
            {
                $itemHtml .=  "<div class='ui-icon ui-icon-triangle-1-s'>"
                          .    "&nbsp;"
                          .   "</div>"
                          .  "</div>";
            }

            array_push($parts, $itemHtml);

            /*
            $html .= $this->_renderOption('itemsStyle',
                                          $key,
                                          $title,
                                          $key == $style,
                                          'radio',
                                          'itemsStyle-'. $idex,
                                          $key,
                                          ($idex === 0
                                            ? 'ui-corner-left'
                                            : ($idex < ($titleCount - 1)
                                                    ? ''
                                                    : 'ui-corner-right')));

            $idex++;
             */
        }
        $html .= implode("<span class='comma'>, </span>", $parts);


        if ($style == self::STYLE_CUSTOM)
        {
            /* The custom style selections should have already been assigned to
             *  Connexions_View_Helper_HtmlUserItems::$styleParts[
             *      Connexions_View_Helper_HtmlUserItems::STYLE_CUSTOM]
             *
             */
            $show = self::$styleParts[$style];
        }
        else
            $show = self::$styleParts[$style];

        $html .=   sprintf("<fieldset class='custom items'%s>",
                            ($style !== self::STYLE_CUSTOM
                                ? " style='display:none;'"
                                : ""),
                            ($style !== self::STYLE_CUSTOM
                                ? " disabled='true'"
                                : ""));
                        
        // Need 'legend' for vertical spacing control
        $html .=    "<legend></legend>"
              .     "<div class='label'>Custom display</div>"
              .     "<div class='item'>"
              .      "<div class='meta'>"
              .       "<div class='field countTaggers'>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[countTaggers]' "
              .                 "id='display-countTaggers'"
              .              ( $show['meta:countTaggers']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-countTaggers'>user count</label>"
              .       "</div>"
              .       "<div class='field rating'>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[rating]' "
              .                 "id='display-rating'"
              .              ( $show['meta:rating:stars:average'] ||
                               $show['meta:rating:stars:owner']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-rating'>Rating stars</label>"
              .        "<div class='meta'>"
              .         "<input type='checkbox' "
              .                "name='itemsStyleCustom[ratingMeta]' "
              .                  "id='display-ratingMeta'"
              .               ( $show['meta:rating:meta']
                                 ? " checked='true'"
                                 : ''). " />"
              .         "<label for='display-ratingMeta'>Rating info</label>"
              .        "</div>"
              .       "</div>"
              .      "</div>"
              .      "<div class='data'>"
              .       "<h4 class='field itemName'>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[itemName]' "
              .                 "id='display-itemName'"
              .              ( $show['itemName']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-itemName'>Title</label>"
              .       "</h4>"
              .       "<div class='field url'>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[url]' "
              .                 "id='display-url'"
              .              ( $show['url']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-url'>url</label>"
              .       "</div>"
              .       "<div class='field description'>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[description]' "
              .                 "id='display-description'"
              .              ( $show['description']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-description'>description</label>"
              .       "</div>"
              .       "<br class='clear' />"
              .       "<div class='field userName'>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[userName]' "
              .                 "id='display-userName'"
              .              ( $show['userName']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-userName'>User Name</label>"
              .       "</div>"
              .       "<div class='field tags'>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[tags]' "
              .                 "id='display-tags' "
              .                              "class='tag' "
              .              ( $show['tags']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-tags' class='tag ui-state-default'>"
              .         "tags"
              .        "</label>"
              .        "<label class='tag ui-state-default'> ... </label>"
              .        "<label class='tag ui-state-default'> ... </label>"
              .        "<label class='tag ui-state-default'> ... </label>"
              .        "<label class='tag ui-state-default'> ... </label>"
              .       "</div>"
              .       "<br class='clear' />"
              .       "<div class='dates'>"
              .        "<div class='field tagged'>"
              .         "<input type='checkbox' "
              .               "name='itemsStyleCustom[dateTagged]' "
              .                 "id='display-dateTagged'"
              .              ( $show['dates:tagged']
                                ? " checked='true'"
                                : ''). " />"
              .         "<label for='display-dateTagged'>date:Tagged</label>"
              .        "</div>"
              .        "<div class='field updated'>"
              .         "<input type='checkbox' "
              .               "name='itemsStyleCustom[dateUpdated]' "
              .                 "id='display-dateUpdated'"
              .              ( $show['dates:updated']
                                ? " checked='true'"
                                : ''). " />"
              .         "<label for='display-dateUpdated'>date:Updated</label>"
              .        "</div>"
              .       "</div>"
              .       "<br class='clear' />"
              .      "</div>"
              .     "</div>"
              .     "<div class='buttons'>"
              .      "<button type='submit' "
              .             "class='ui-button ui-corner-all ui-state-default' "
              .              "name='itemsStyle' "
              .             "value='custom'>save</button>"
              .     "</div>"
              .    "</fieldset>";

        $html .=  "</div>";                     // itemsStyle }

        $html .= "</form>"  // form }
              . "</div>";   // displayOptions }

        return $html;
    }

    protected function _renderOption($name,
                                     $value,
                                     $title,
                                     $isOn       = false,
                                     $type       = 'option',
                                     $id         = null,
                                     $css        = '',
                                     $corner     = 'ui-corner-all')
    {
        $html = '';

        switch ($type)
        {
        case 'toggle-button':
            $html = sprintf(  "<button type='submit' "
                            .         "name='%s' "
                            .           "%s"
                            .        "class='ui-state-%s "
                            .               "ui-toggle-button%s%s' "
                            .        "title='%s' value='%s'>"
                            .  "<span>%s</span>"
                            . "</button>",
                            $name,
                            ($id !== null
                                ? "id='". $id ."' "
                                : ""),
                            ( $isOn
                                ? "highlight"
                                : "default"),
                            ( !@empty($css)
                                ? " ". $css
                                : ""),
                            ( !@empty($corner)
                                ? " ". $corner
                                : ""),
                            $title,
                            $value,
                            $title);
            break;

        case 'radio':
            $html = sprintf(  "<div class='ui-radio%s ui-state-%s%s'>"
                            .  "<div class='ui-radio-button%s' "
                            .          "title='%s'>"
                            .   "<input type='radio' "
                            .          "name='%s' "
                            .            "id='%s' "
                            .          "title='%s' value='%s'%s />"
                            .   "<label for='%s'>%s</label>"
                            .  "</div>"
                            . "</div>",
                            ($isOn ? " ui-radio-on" : ""),
                            ($isOn ? "highlight"    : "default"),
                            ( !@empty($corner)
                                ? " ". $corner
                                : ""),

                            ( !@empty($css)
                                ? " ". $css
                                : ""),
                            $title,

                            $name,
                            ($id === null
                                ? $name
                                : $id),
                            $title,
                            $value,
                            ($isOn ? " checked" : ""),

                            ($id === null
                                ? $name
                                : $id),
                            $title);
            break;

        case 'option':
        default:
            if ($isOn)              $css .= ' option-on';
            if (! empty($corner))   $css .= ' '. $corner;

            $html = sprintf(  "<option%s title='%s' value='%s'%s>"
                            .  "<span>%s</span>"
                            . "</option>",
                            ( !@empty($css)
                                ? " class='". $css ."'"
                                : ""),
                            $title,
                            $value,
                            ($isOn ? " selected" : ""),
                            $title);
        }

        return $html;
    }
}
