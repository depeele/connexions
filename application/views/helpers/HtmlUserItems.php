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

    static public $styleTitles      = array(
                    self::STYLE_TITLE   => 'Title',
                    self::STYLE_REGULAR => 'Regular',
                    self::STYLE_FULL    => 'Full'
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
               ->addOnLoad('init_displayOptions();')
               ->addOnLoad('init_userItems();')
               ->javascriptCaptureStart();

        ?>

/************************************************
 * Initialize display options.
 *
 */
function init_displayOptions()
{
    var $displayOptions = $('.displayOptions,'+
                            ':not(.displayOptions) .pagination');
    var $pForm          = $displayOptions.parent('form');
    if ($pForm.length < 1)
    {
        // Wrap our displayOptions in a form
        $pForm = $displayOptions.wrap('<form />');
    }

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

    // All form items will set a cookie
    $pForm.find('input,select').change(function() {
        var name    = $(this).attr('name');
        if (name.length < 0)
            return;

        $.cookie(name, $(this).val());

        // Force a 'submit'
        $pForm.submit();
    });

    $pForm.find(':submit').click(function() {
        var page    = $(this).val();

        $.cookie('page', page);
    });

    $pForm.submit(function(e) {
        /* Disable all displayOption form elements -- this removes these items
         * from form serialization.
         *
         * We've already set any settings as cookies and don't want these
         * showing up in the URL as well.
         */
        $pForm.find('input,button,select').attr('disabled', true);

        var ser = $pForm.serialize();
        var a   = 1;
    });
}

/************************************************
 * Initialize ui elements.
 *
 */
function init_userItems()
{
    var $form   = $('#userItems');

    $form.hide();   // hide while we prepare...

    $form.addClass('ui-form');

    $form.find('.status,.control')
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
    $form.find('input[name=isFavorite]').checkbox({
        css:        'connexions_sprites',
        cssOn:      'star_fill',
        cssOff:     'star_empty',
        titleOn:    'Favorite: click to remove from Favorites',
        titleOff:   'Click to add to Favorites',
        useElTitle: false,
        hideLabel:  true
    });

    // Privacy
    $form.find('input[name=isPrivate]').checkbox({
        css:        'connexions_sprites',
        cssOn:      'lock_fill',
        cssOff:     'lock_empty',
        titleOn:    'Private: click to share',
        titleOff:   'Public: click to mark as private',
        useElTitle: false,
        hideLabel:  true
    });

    // Rating - average and user
    $form.find('.rating .stars .average').stars({split:2});
    $form.find('.rating .stars .owner').stars();

    $form.show();
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

        // Include the current page number
        $html = sprintf("<input type='hidden' name='page' value='%s' />",
                            $paginator->getCurrentPageNumber());

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
                                            array($ownerStr => $ownerUrl));

        $html .="<div class='displayOptions'>"  // displayOptions {
              .  "<div class='displayStyle'>";  // displayStyle {

        $idex       = 0;
        $titleCount = count(self::$styleTitles);
        foreach (self::$styleTitles as $key => $title)
        {
            $html .= $this->renderOption('itemsStyle',
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
        }

        $html .= "</div>"                       // displayStyle }
              .  $this->view->paginationControl($paginator,
                                                null,        // style
                                                'paginationControl.phtml',
                                                array('excludeInfo' => true))
              .  "<div class='displaySort'>"    // displaySort {
              .   "<label   for='itemsSortBy'>Sorted by</label>"
              .   "<select name='itemsSortBy' "
              .             "id='itemsSortBy' "
              .          "class='sort-by sort-by-{$sortBy} "
              .                  "ui-input ui-state-default ui-corner-all'>";

        foreach (self::$sortTitles as $key => $title)
        {
            $html .= $this->renderOption('sortBy',
                                         $key,
                                         $title,
                                         $key == $sortBy);
        }

        $html .=  "</select>"
              .   "<div class='sort-order sort-order-{$sortOrder}'>";

        $idex = 0;
        foreach (self::$orderTitles as $key => $title)
        {
            $html .= $this->renderOption('itemsSortOrder',
                                         $key,
                                         $title,
                                         $key == $sortOrder,
                                         'radio',
                                         'itemsSortOrder-'. $idex,
                                         'ui-icon ui-icon-triangle-1-'
                                          . ($key ===
                                              Model_UserItemSet::SORT_ORDER_ASC
                                                ? 'n'
                                                : 's'),
                                          ($key ===
                                              Model_UserItemSet::SORT_ORDER_ASC
                                                ? 'ui-corner-top'
                                                : 'ui-corner-bottom'));

            $idex++;
        }

        $html .=  "</div>"  // sort-order
              .  "</div>"   // displaySort }
              . "</div>";   // displayOptions }

        if (count($paginator))
        {
            $html .= "<ul class='items'>";

            foreach ($paginator as $idex => $userItem)
            {
                $html .= $this->view->partial('userItem.phtml',
                                              array(
                                                  'index'    =>  $idex,
                                                  'userItem' => &$userItem,
                                                  'viewer'   => &$viewer));
            }

            $html .= "</ul>";
        }


        $html .= $paginator
              .  "<br class='clear' />\n";

        // Return the rendered HTML
        return $html;
    }

    protected function renderOption($name,
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
