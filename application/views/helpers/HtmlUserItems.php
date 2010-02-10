<?php
/** @file
 *
 *  View helper to render a paginated set of User Items / Bookmarks in HTML.
 */
class Connexions_View_Helper_HtmlUserItems extends Zend_View_Helper_Abstract
{
    static public   $numericGrouping    = 10;
    static public   $perPageChoices     = array(10, 25, 50, 100);


    const STYLE_TITLE                   = 'title';
    const STYLE_REGULAR                 = 'regular';
    const STYLE_FULL                    = 'full';
    const STYLE_CUSTOM                  = 'custom';

    static public   $styleTitles        = array(
        self::STYLE_TITLE   => 'Title',
        self::STYLE_REGULAR => 'Regular',
        self::STYLE_FULL    => 'Full',
        self::STYLE_CUSTOM  => 'Custom'
    );

    /** @brief  User-settable (via display options) style parts / show-meta */
    static protected $userStyleParts    = array(
            'meta:countTaggers'         => true,
            'meta:rating:stars:average' => true,
            'meta:rating:stars:owner'   => true,
            'meta:rating:stars'         => array(
                // implied sub-values
                'meta:rating:stars:average' => true,
                'meta:rating:stars:owner'   => true
            ),
            'meta:rating:meta'          => true,

            'itemName'                  => true,
            'url'                       => true,
            'descriptionSummary'        => array(
                // one or the other
                'description'           => false
            ),
            'description'               => array(
                // one or the other
                'descriptionSummary'    => false
            ),
            'userId'                    => true,
            'userId:avatar'             => true,
            'tags'                      => true,

            'dates:tagged'              => true,
            'dates:updated'             => true,

            'dates'                     => array(
                // implied sub-values
                'meta:rating:stars:average' => true,
                'meta:rating:stars:owner'   => true
            ),

    );


    static public   $styleParts     = array(
        self::STYLE_TITLE   => array(
            'minimized'                 => true,    // show-meta

            'meta'                      => true,    // show-meta
            'meta:countTaggers'         => true,
            'meta:rating'               => false,   // show-meta
            'meta:rating:stars'         => false,   // show-meta
            'meta:rating:stars:average' => false,
            'meta:rating:stars:owner'   => false,
            'meta:rating:meta'          => false,

            'itemName'                  => true,
            'url'                       => false,
            'descriptionSummary'        => true,    // constructed data
            'description'               => false,
            'userId'                    => true,
            'userId:avatar'             => true,
            'tags'                      => false,

            'dates'                     => false,   // show-meta
            'dates:tagged'              => false,
            'dates:updated'             => false
        ),
        self::STYLE_REGULAR => array(
            'minimized'                 => false,   // show-meta

            'meta'                      => true,    // show-meta
            'meta:countTaggers'         => true,
            'meta:rating'               => true,    // show-meta
            'meta:rating:stars'         => true,    // show-meta
            'meta:rating:stars:average' => true,
            'meta:rating:stars:owner'   => true,
            'meta:rating:meta'          => false,

            'itemName'                  => true,
            'url'                       => false,
            'descriptionSummary'        => false,   // constructed data
            'description'               => true,
            'userId'                    => true,
            'userId:avatar'             => true,
            'tags'                      => true,

            'dates'                     => false,   // show-meta
            'dates:tagged'              => false,
            'dates:updated'             => false
        ),
        self::STYLE_FULL    => array(
            'minimized'                 => false,   // show-meta

            'meta'                      => true,    // show-meta
            'meta:countTaggers'         => true,
            'meta:rating'               => true,    // show-meta
            'meta:rating:stars'         => true,    // show-meta
            'meta:rating:stars:average' => true,
            'meta:rating:stars:owner'   => true,
            'meta:rating:meta'          => true,

            'itemName'                  => true,
            'url'                       => true,
            'descriptionSummary'        => false,   // constructed data
            'description'               => true,
            'userId'                    => true,
            'userId:avatar'             => true,
            'tags'                      => true,

            'dates'                     => true,    // show-meta
            'dates:tagged'              => true,
            'dates:updated'             => true
        ),
        self::STYLE_CUSTOM  => array(
            'minimized'                 => false,   // show-meta

            'meta'                      => true,    // show-meta
            'meta:countTaggers'         => true,
            'meta:rating'               => true,    // show-meta
            'meta:rating:stars'         => true,    // show-meta
            'meta:rating:stars:average' => true,
            'meta:rating:stars:owner'   => true,
            'meta:rating:meta'          => true,

            'itemName'                  => true,
            'url'                       => true,
            'descriptionSummary'        => false,   // constructed data
            'description'               => true,
            'userId'                    => true,
            'userId:avatar'             => true,
            'tags'                      => true,

            'dates'                     => true,    // show-meta
            'dates:tagged'              => true,
            'dates:updated'             => true
        )
    );

    const SORT_BY_DATE_TAGGED       = 'taggedOn';
    const SORT_BY_DATE_UPDATED      = 'dateUpdated';
    const SORT_BY_NAME              = 'name';
    const SORT_BY_RATING            = 'rating';
    const SORT_BY_RATING_COUNT      = 'item_ratingCount';
    const SORT_BY_USER_COUNT        = 'item_userCount';

    static public   $sortTitles     = array(
                    self::SORT_BY_DATE_TAGGED   => 'Tag Date',
                    self::SORT_BY_DATE_UPDATED  => 'Update Date',
                    self::SORT_BY_NAME          => 'Title',
                    self::SORT_BY_RATING        => 'Rating',
                    self::SORT_BY_RATING_COUNT  => 'Rating Count',
                    self::SORT_BY_USER_COUNT    => 'User Count'
                );

    static public   $orderTitles    = array(
                    Model_UserItemSet::SORT_ORDER_ASC   => 'Ascending',
                    Model_UserItemSet::SORT_ORDER_DESC  => 'Descending'
                );


    /** @brief  Set-able parameters with default values. */
    protected       $_style         = self::STYLE_REGULAR;
    protected       $_showMeta      = null;
    protected       $_sortBy        = self::SORT_BY_DATE_TAGGED;
    protected       $_sortOrder     = Model_UserItemSet::SORT_ORDER_DESC;
    protected       $_multipleUsers = true;
    protected       $_scopeItems    = null;

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
               ->addJavascriptFile($view->baseUrl('js/ui.stars.js'))
               ->addJavascriptFile($view->baseUrl('js/ui.checkbox.js'))
               ->addJavascriptFile($view->baseUrl('js/ui.button.js'))
               ->addJavascriptFile($view->baseUrl('js/ui.input.js'))
               ->addOnLoad('init_userItems();')
               ->javascriptCaptureStart();

        ?>

/************************************************
 * Initialize display options, as well as the
 * perPage selector in the bottom paginator.
 *
 */
function init_userItemDisplayOptions()
{
    var $displayOptions = $('.displayOptions');
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

    var $displayStyle   = $displayOptions.find('.displayStyle');
    var $itemsStyle     = $displayStyle.find('input[name=itemsStyle]');
    var $cControl       = $displayStyle.find('.control:first');
    var $customFieldset = $displayStyle.find('fieldset:first');

    /* Attach a data item to each display option identifying the display type
     * (pulled from the CSS class (itemsStyle-<type>)
     */
    $displayStyle.find('a.option,div.option a:first').each(function() {
                // Retrieve the new style value from the 'itemsStyle-*' class
                var style   = $(this).attr('class');
                var pos     = style.indexOf('itemsStyle-') + 11;

                style = style.substr(pos);
                pos   = style.indexOf(' ');
                if (pos > 0)
                    style = style.substr(0, pos);

                // Save the style in a data item
                $(this).data('itemsStyle', style);
            });

    // Click the 'Custom' button to toggle the 'display custom' pane/field-set
    $cControl.click(function(e) {
                e.preventDefault();
                e.stopPropagation();

                $displayOptions.find('#buttons-global')
                                    .toggleClass('buttons-custom');

                $displayStyle.find('.custom.items').toggle();
                $cControl.toggleClass('ui-state-active');
            });
    /* For the anchors within the 'Custom' button, disable the default browser
     * action but allow the event to bubble up to the click handler on the
     * 'Custom' button.
     */
    $cControl.find('> a, .control > a, .control > .ui-icon')
                                         // Let it bubble up
                    .click(function(e) { e.preventDefault(); });

    /* When something in the 'display custom' pane/field-set changes, set the
     * display style to 'custom', de-selecting the others.
     */
    $customFieldset.change(function() {
                var $opt    = $cControl.find('a:first');

                // Save the style in our hidden input
                $itemsStyle.val( $opt.data('itemsStyle' ) );

                // Turn "off" all other display options...
                $displayStyle.find('a.option-selected')
                                            .removeClass('option-selected');
                // And turn 'custom' on.
                $opt.addClass('option-selected');
            });

    // Allow only one display style to be selected at a time
    $displayStyle.find('a.option').click(function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $opt    = $(this);

                // Save the style in our hidden input
                $itemsStyle.val( $opt.data('itemsStyle') );

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
                /* Remove all cookies related to 'custom' style.  This is 
                 * because, when an option is NOT selected, it is not included 
                 * so, to remove a previously selected options, we must first 
                 * remove them all and then add in the ones that are explicitly 
                 * selected.
                 */
                $customFieldset.find('input').each(function() {
                    $.cookie( $(this).attr('name'), null );
                });

                /* If the selected display style is NOT 'custom', disable all
                 * the 'display custom' pane/field-set inputs so they will not
                 * be included in the serialization of form values.
                 */
                if ($itemsStyle.val() !== 'custom')
                {
                    // Disable all custom field values
                    $customFieldset.find('input').attr('disabled', true);
                }

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

    /* Finally, attach to the perPage selection box in the bottom pagination
     * form.
     */
    var $pForm  = $('form.pagination:has(select[name=perPage])');

    $pForm.submit(function() {
                // Serialize all form values to an array...
                var settings    = $pForm.serializeArray();

                /* ...and set a cookie for each
                 *  perPage
                 */
                $(settings).each(function() {
                    $.log("Add Cookie: name[%s], value[%s]",
                          this.name, this.value);
                    $.cookie(this.name, this.value);
                });

                /* Finally, disable ALL inputs so our URL will have no
                 * parameters since we've stored them all in cookies.
                 */
                $pForm.find('input,select').attr('disabled', true);
            });

    $pForm.find('select[name=perPage]').change(function() {
                // On change, submit the form.
                $pForm.submit();
            });
    return;
}

/************************************************
 * Initialize group header options.
 *
 */
function init_userItemsGroupHeader()
{
    var $headers    = $('#userItems .groupHeader .groupType');
    var dimOpacity  = 0.5;

    $headers
        .fadeTo(100, dimOpacity)
        .hover(
            // in
            function() {
                $(this).fadeTo(100, 1.0);
            },

            // out
            function() {
                $(this).fadeTo(100, dimOpacity);
            }
        );
}

/************************************************
 * Initialize ui elements.
 *
 */
function init_userItems()
{
    // Initialize display options
    init_userItemDisplayOptions();

    var $userItems  = $('form.userItem');

    /*
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
    */

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

    // Initialize display options
    init_userItemsGroupHeader();
}

        <?php
        $jQuery->javascriptCaptureEnd();

        return $this;
    }


    /** @brief  Render an HTML version of a paginated set of User Items or,
     *          if no arguments, this helper instance.
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
    public function htmlUserItems($paginator    = null,
                                  $owner        = null,
                                  $viewer       = null,
                                  $tagInfo      = null,
                                  $style        = null,
                                  $sortBy       = null,
                                  $sortOrder    = null)
    {
        if ((! $paginator instanceof Zend_Paginator)                         ||
            ( (! $owner   instanceof Model_User) && (! @is_string($owner)) ) ||
            (! $viewer    instanceof Model_User)                             ||
            (! $tagInfo   instanceof Connexions_Set_Info))
        {
            return $this;
        }

        return $this->render($paginator, $owner, $viewer, $tagInfo,
                             $style, $sortBy, $sortOrder);
    }

    /** @brief  Set the current style.
     *  @param  style   A style value (self::STYLE_*)
     *
     *  @return Connexions_View_Helper_HtmlUserItems for a fluent interface.
     */
    public function setStyle($style)
    {
        $orig = $style;

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

        /*
        Connexions::log('Connexions_View_Helper_HtmlUserItems::'
                            . "setStyle({$orig}) == [ {$style} ]");
        // */
    
        $this->_style = $style;

        return $this;
    }

    /** @brief  Get the current style value.
     *
     *  @return The style value (self::STYLE_*).
     */
    public function getStyle()
    {
        return $this->_style;
    }


    /** @brief  Set the current sortBy.
     *  @param  sortBy  A sortBy value (self::SORT_BY_*)
     *
     *  @return Connexions_View_Helper_HtmlUserItems for a fluent interface.
     */
    public function setSortBy($sortBy)
    {
        $orig = $sortBy;

        switch ($sortBy)
        {
        case self::SORT_BY_DATE_TAGGED:
        case self::SORT_BY_DATE_UPDATED:
        case self::SORT_BY_NAME:
        case self::SORT_BY_RATING:
        case self::SORT_BY_RATING_COUNT:
        case self::SORT_BY_USER_COUNT:
            break;

        default:
            $sortBy = self::SORT_BY_DATE_TAGGED;
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_HtmlUserItems::'
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
     *  @param  sortOrder   A sortOrder value (Model_UserItemSet::SORT_ORDER_*)
     *
     *  @return Connexions_View_Helper_HtmlUserItems for a fluent interface.
     */
    public function setSortOrder($sortOrder)
    {
        $orig = $sortOrder;

        $sortOrder = strtoupper($sortOrder);
        switch ($sortOrder)
        {
        case Model_UserItemSet::SORT_ORDER_ASC:
        case Model_UserItemSet::SORT_ORDER_DESC:
            break;

        default:
            $sortOrder = Model_UserItemSet::SORT_ORDER_DESC;
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_HtmlUserItems::'
                            . "setSortOrder({$orig}) == [ {$sortOrder} ]");
        // */
    
        $this->_sortOrder = $sortOrder;

        return $this;
    }

    /** @brief  Get the current sortOrder value.
     *
     *  @return The sortOrder value (Model_UserItemSet::SORT_ORDER_*).
     */
    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    /** @brief  Set the current showMeta.
     *  @param  showMeta    A showMeta value (self::SORT_BY_*)
     *
     *  @return Connexions_View_Helper_HtmlUserItems for a fluent interface.
     */
    public function setShowMeta(Array $showMeta)
    {
        if (@is_array($showMeta))
        {
            /* Leave modifications / mix-ins for retrieval since other relevant 
             * values may change before then.
             */
            $this->_showMeta = array();

            foreach (self::$userStyleParts as $key => $val)
            {
                $newVal = (@isset($showMeta[$key])
                            ? true
                            : false);

                $this->_showMeta[$key] = $newVal;
                if ($newVal && @is_array($val))
                {
                    // Additional settings
                    foreach ($val as $subKey => $subVal)
                    {
                        /* If the main key was set to true, then all sub-keys 
                         * are directly set to the specified value.
                         *
                         * If the main key was set to false, then all sub-keys 
                         u that are specified true must also be set false.  
                         * Those specified false are either-or settings and are 
                         * NOT directly implied when the main key is false.
                         */
                        if (($newVal === true) || ($subVal === true))
                            $this->_showMeta[$subKey] = $subVal;
                    }
                }
            }

            /*
            Connexions::log('Connexions_View_Helper_HtmlUserItems::'
                                . 'setShowMeta( [ '
                                .       print_r($showMeta, true) .' ] ) == [ '
                                .       print_r($this->_showMeta, true) .' ]');
            // */
    
        }

        return $this;
    }

    /** @brief  Get the current showMeta value.
     *
     *  @return The showMeta value (self::SORT_BY_*).
     */
    public function getShowMeta()
    {
        if (@is_array($this->_showMeta))
        {
            $val = $this->_showMeta;
        }
        else
            $val = self::$styleParts[$this->_style];

        if (! $this->_multipleUsers)
        {
            /* If we're only showing information for a single user, mark 
             * 'userId' as 'hide' (not true nor false).
             */
            $val['userId']        = 'hide';
            $val['userId:avatar'] = 'hide';
        }

        if (! @is_bool($val['minimized']))
        {
            /* View meta-info:
             *  Include additional meta-info that is helpful for further view
             *  renderers in determining what to render.
             */
            $val = $this->_includeShowMeta($val);

            $this->_showMeta = $val;
        }

        /*
        Connexions::log('Connexions_View_Helper_HtmlUserItems::'
                            . 'getShowMeta(): return[ '
                            .       print_r($val, true) .' ]');
        // */
    
        return $val;
    }

    /** @brief  Set the current multipleUsers.
     *  @param  multipleUsers   A multipleUsers boolean [ true ];
     *
     *  @return Connexions_View_Helper_HtmlUserItems for a fluent interface.
     */
    public function setMultipleUsers($multipleUsers = true)
    {
        $this->_multipleUsers = ($multipleUsers ? true : false);

        /*
        Connexions::log('Connexions_View_Helper_HtmlUserItems::'
                            . 'setMultipleUsers('
                            .   ($multipleUsers ? 'true' : 'false') .')');
        // */
    
        return $this;
    }

    /** @brief  Set the current multipleUsers to false.
     *
     *  @return Connexions_View_Helper_HtmlUserItems for a fluent interface.
     */
    public function setSingleUser()
    {
        $this->_multipleUsers = false;

        /*
        Connexions::log('Connexions_View_Helper_HtmlUserItems::'
                            . 'setSingleUser()');
        // */
    

        return $this;
    }

    /** @brief  Get the current multipleUsers value.
     *
     *  @return The multipleUsers boolean.
     */
    public function getMultipleUsers()
    {
        return $this->_multipleUsers;
    }

    /** @brief  Set the Connexions_Set that specifies the full set of scope 
     *          items (for auto-suggest).
     *  @param  scopeItems  A Connexions_Set instance.
     *
     *  @return Connexions_View_Helper_HtmlUserItems for a fluent interface.
     */
    public function setScopeItems(Connexions_Set $scopeItems)
    {
        $this->_scopeItems = $scopeItems;

        return $this;
    }

    /** @brief  Get the current scopeItems value.
     *
     *  @return The Connexions_Set instance (null if not set).
     */
    public function getScopeItems()
    {
        return $this->_scopeItems;
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
    public function render(Zend_Paginator            $paginator,
                           /* Model_User | String */ $owner,
                           Model_User                $viewer,
                           Connexions_Set_info       $tagInfo,
                           $style        = null,
                           $sortBy       = null,
                           $sortOrder    = null)
    {
        /*
        Connexions::log("Connexions_View_Helper_HtmlUserItems: "
                            . "style[ {$style} ], "
                            . "sortBy[ {$sortBy} ], "
                            . "sortOrder[ {$sortOrder} ]");
        // */

        if ($style     !== null)    $this->setStyle($style);
        if ($sortBy    !== null)    $this->setSortBy($sortBy);
        if ($sortOrder !== null)    $this->setSortOrder($sortOrder);

        /*
        Connexions::log("Connexions_View_Helper_HtmlUserItems: "
                            . "validated to: "
                            . "style[ {$this->_style} ], "
                            . "styleTitle[ "
                            .       self::$styleTitles[$this->_style]." ], "
                            . "sortBy[ {$this->_sortBy} ], "
                            . "sortByTitle[ "
                            .       self::$sortTitles[$this->_sortBy]." ], "
                            . "sortOrder[ {$this->_sortOrder} ], "
                            . "sortOrderTitle[ "
                            .       self::$orderTitles[$this->_sortOrder]." ]");
        // */

        $html = '';

        $ownerStr = (String)$owner;
        if ($ownerStr === '*')
        {
            $ownerStr      = 'Bookmarks';
            $ownerUrl      = $this->view->baseUrl('/tagged');

            $this->setMultipleUsers();
        }
        else
        {
            $ownerUrl      = $this->view->baseUrl($ownerStr);

            $this->setSingleUser();
        }

        $showMeta   = $this->getShowMeta();


        // Construct the scope auto-completion callback URL
        $scopeParts = array('format=json');
        if ($owner !== '*')
            array_push($scopeParts, 'owner='. $owner->name);

        if ($tagInfo->hasValidItems())
            array_push($scopeParts, 'tags='. $tagInfo->validitems);

        $scopeCbUrl = $this->view->baseUrl('/scopeAutoComplete')
                    . '?'. implode('&', $scopeParts);


        // /*
        Connexions::log("Connexions_View_Helper_HtmlUserItems: "
                        .       "scopeCbUrl[ {$scopeCbUrl} ]");
        // */


        $html     .= $this->view->htmlItemScope($paginator,
                                                $tagInfo,
                                                'Tags',
                                                'tags',
                                                array($ownerStr => $ownerUrl),
                                                $scopeCbUrl)
                  .  $this->view->paginationControl($paginator,
                                                    null,        // style
                                                    'paginationControl.phtml',
                                                    array(
                                                      'excludeInfo'    =>
                                                        true,
                                                      'cssClass'       =>
                                                        'pagination-top',
                                                      'perPageChoices' =>
                                                        self::$perPageChoices))
                  .  $this->_renderDisplayOptions($paginator, $showMeta);

        if (count($paginator))
        {
            $html .= "<ul class='items'>";

            // Group by the field identified in $this->_sortBy
            $lastGroup = null;
            foreach ($paginator as $idex => $userItem)
            {
                $groupVal = $userItem->{$this->_sortBy};
                $newGroup = $this->_groupValue($this->_sortBy, $groupVal);

                if ($newGroup !== $lastGroup)
                {
                    $html      .= $this->_renderGroupHeader($this->_sortBy,
                                                            $newGroup);
                    $lastGroup  = $newGroup;
                }

                $html .= $this->view->htmlUserItem($userItem,
                                                   $viewer,
                                                   $showMeta,
                                                   $idex);
            }

            $html .= "</ul>";
        }


        $html .= $this->view->paginationControl($paginator,
                                                null,        // style
                                                'paginationControl.phtml',
                                                array(
                                                    'perPageChoices' =>
                                                        self::$perPageChoices))
              .  "<br class='clear' />\n";

        // Return the rendered HTML
        return $html;
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Given a grouping identifier and values, return the group into
     *          which the value falls.
     *  @param  groupBy     The grouping identifier / field (self::SORT_BY_*);
     *  @param  value       The value;
     *
     * @return  The value of the group into which the value falls.
     */
    protected function _groupValue($groupBy, $value)
    {
        $orig = $value;
        switch ($groupBy)
        {
        case self::SORT_BY_DATE_TAGGED:       // 'taggedOn'
        case self::SORT_BY_DATE_UPDATED:      // 'dateUpdated'
            /* Dates are strings of the form YYYY-MM-DD HH:MM:SS
             *
             * Grouping should be by year:month:day, so strip off the time.
             */
            $value = substr($value, 0, 10);
            break;
            
        case self::SORT_BY_NAME:              // 'name'
            $value = strtoupper(substr($value, 0, 1));

            break;

        case self::SORT_BY_RATING:            // 'rating'
            $value = floor($value);
            break;

        case self::SORT_BY_RATING_COUNT:      // 'ratingCount'
        case self::SORT_BY_USER_COUNT:        // 'userCount'
            /* We'll do numeric grouping in groups of:
             *      self::$numericGrouping [ 10 ]
             */
            $value = floor($value / self::$numericGrouping) *
                                                    self::$numericGrouping;
            break;
        }

        /*
        Connexions::log(
            sprintf("HtmlUserItems::_groupValue(%s, %s:%s) == [ %s ]",
                    $groupBy, $orig, gettype($orig),
                    $value));
        // */

        return $value;
    }


    protected $_lastYear    = null;

    /** @brief  Render the HTML of a group header.
     *  @param  groupBy     The grouping identifier / field (self::SORT_BY_*);
     *  @param  value       The value of this group;
     *
     *  @return The HTML of the group header.
     */
    protected function _renderGroupHeader($groupBy, $value)
    {
        $html  =  "<div class='groupHeader ui-corner-tl'>" // groupHeader {
               .   "<div class='group group". ucfirst($groupBy)
                                      // groupTaggedOn, groupDateUpdated,
                                      // groupName,     groupRating,
                                      // groupUserCount
               .              " ui-corner-right'>";


        switch ($groupBy)
        {
        case self::SORT_BY_DATE_TAGGED:       // 'taggedOn'
        case self::SORT_BY_DATE_UPDATED:      // 'dateUpdated'
            // The date group value will be of the form YYYY-MM-DD
            list($year, $month, $day) = explode('-', $value);

            // Figure out the day-of-week
            $time      = strtotime($value);
            $month     = date('M', $time);
            $dayOfWeek = date('D', $time);

            $html .= "<div class='groupType date'>";

            if ($year !== $this->_lastYear)
            {
                $html .=  "<span class='year'    >{$year}</span>";
                $this->_lastYear = $year;
            }

            $html .=  "<div class='dateBox'>"
                  .    "<span class='month'      >{$month}</span>"
                  .    "<span class='day'        >{$day}</span>"
                  .    "<span class='day-of-week'>{$dayOfWeek}</span>"
                  .   "</div>"
                  .  "</div>";
            break;
            
        case self::SORT_BY_NAME:              // 'name'
            $html .= "<div class='groupType alpha'>"
                  .   $value
                  .  "</div>";
            break;

        case self::SORT_BY_RATING:            // 'rating'
        case self::SORT_BY_RATING_COUNT:      // 'ratingCount'
        case self::SORT_BY_USER_COUNT:        // 'userCount'
            $html .= "<div class='groupType numeric'>"
                  .   $value
                  .  "</div>";
            break;
        }

        $html  .=  "</div>"
               .  "</div>";                         // groupHeader }

        return $html;
    }

    /** @brief  Given a show style array, include additional show-meta 
     *          information useful for future view renderers in determining
     *          what to render.
     *  @param  show    The show style array.
     *
     *  @return A new, updated show style array.
     */
    protected function _includeShowMeta($show)
    {
        /* View meta-info:
         *  Include additional meta-info that is helpful for further view
         *  renderers in determining what to render.
         */
        if (! @isset($show['minimized']))
        {
            $show['minimized'] =
                   (($show['meta:rating:stars:average'] === false) &&
                    ($show['meta:rating:stars:owner']   === false) &&
                    ($show['meta:rating:meta']          === false) &&
                    ($show['url']                       === false) &&
                    ($show['description']               === false) &&
                    //($show['tags']                      === false) &&
                    ($show['dates:tagged']              === false) &&
                    ($show['dates:updated']             === false));
        }
            
        if (! @isset($show['meta:rating:stars']))
        {
            $show['meta:rating:stars'] =
                    (($show['meta:rating:stars:average'] === true) ||
                     ($show['meta:rating:stars:owner']   === true));
        }

        if (! @isset($show['meta:rating']))
        {
                $show['meta:rating'] =
                    (($show['meta:rating:stars']         === true) ||
                     ($show['meta:rating:stars:owner']   === true));
        }

        if (! @isset($show['meta']))
        {
            $show['meta'] =
                    (($show['meta:rating']               === true) ||
                     ($show['meta:countTaggers']         === true));
        }

        if (! @isset($show['dates']))
        {
            $show['dates'] =
                    (($show['dates:tagged']              === true) ||
                     ($show['dates:updated']             === true));
        }

        return $show;
    }

    protected function _renderDisplayOptions($paginator, $showMeta)
    {
        $itemCountPerPage = $paginator->getItemCountPerPage();

        $html = "<div class='displayOptions'>"      // displayOptions {
              .  "<div class='control ui-corner-all ui-state-default'>"
              .   "<a >Display Options</a>"
              .   "<div class='ui-icon ui-icon-triangle-1-s'>"
              .    "&nbsp;"
              .   "</div>"
              .  "</div>"
              .  "<form style='display:none;' "
              .        "class='ui-state-active ui-corner-all'>";    // form {

        $html .=  "<div class='field sortBy'>"  // itemsSortBy {
              .    "<label   for='itemsSortBy'>Sorted by</label>"
              .    "<select name='itemsSortBy' "
              .              "id='itemsSortBy' "
              .           "class='sort-by sort-by-{$this->_sortBy} "
              .                   "ui-input ui-state-default ui-corner-all'>";

        foreach (self::$sortTitles as $key => $title)
        {
            $html .= $this->_renderOption('sortBy',
                                          $key,
                                          $title,
                                          $key == $this->_sortBy);
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
                  .          ($key == $this->_sortOrder
                                 ? " checked='true'" : "" ). " />"
                  .   "<label for='itemsSortOrder-{$key}'>{$title}</label>"
                  .  "</div>";
        }

        $html .=   "<br class='clear' />"
              .   "</div>"                              // itemsSortOrder }
              .   "<div class='field perPage'>"         // perPage {
              .    "<label for='perPage'>Per page</label>"
              .    "<select class='ui-input ui-state-default "
              .                  "count' name='perPage'>"
              .     "<!-- perPage: {$itemCountPerPage} -->";

        foreach (self::$perPageChoices as $perPage)
        {
            $html .= "<option value='{$perPage}'"
                  .           ($perPage == $itemCountPerPage
                                 ? ' selected'
                                 : '')
                  .                     ">{$perPage}</option>";
        }
    
        $html .=   "</select>"
              .    "<br class='clear' />"
              .   "</div>"                              // perPage }
              .   "<div class='field displayStyle'>"    // itemsStyle {
              .    "<label for='itemsStyle'>Display</label>"
              .    "<input type='hidden' name='itemsStyle' "
              .          "value='{$this->_style}' />";

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
                          .             "ui-corner-all ui-state-default"
                          .     ($this->_style === self::STYLE_CUSTOM
                                    ? " ui-state-active"
                                    : "")
                          .                 "'>";
                $cssClass  = '';
            }

            $cssClass .= " itemsStyle-{$key}";
            if ($key == $this->_style)
                $cssClass .= ' option-selected';

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
                                          $key == $this->_style,
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


        $html .= sprintf("<fieldset class='custom items'%s>",
                          ($this->_style !== self::STYLE_CUSTOM
                                ? " style='display:none;'"
                                : ""));

        // Need 'legend' for vertical spacing control
        $html .=    "<div class='item'>"
              .      "<div class='meta'>"
              .       "<div class='field countTaggers'>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[meta:countTaggers]' "
              .                 "id='display-countTaggers'"
              .              ( $showMeta['meta:countTaggers']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-countTaggers'>user count</label>"
              .       "</div>"
              .       "<div class='field rating'>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[meta:rating:stars]' "
              .                 "id='display-rating'"
              .              ( $showMeta['meta:rating:stars']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-rating'>Rating stars</label>"
              .        "<div class='meta'>"
              .         "<input type='checkbox' "
              .                "name='itemsStyleCustom[meta:rating:meta]' "
              .                  "id='display-ratingMeta'"
              .               ( $showMeta['meta:rating:meta']
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
              .              ( $showMeta['itemName']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-itemName'>Title</label>"
              .       "</h4>"
              .       "<div class='field url'>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[url]' "
              .                 "id='display-url'"
              .              ( $showMeta['url']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-url'>url</label>"
              .       "</div>"
              .       "<div class='field description'>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[description]' "
              .                 "id='display-description'"
              .              ( $showMeta['description']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-description'>description</label>"
              .       "</div>"
              .       "<div class='field descriptionSummary'>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[descriptionSummary]' "
              .                 "id='display-descriptionSummary'"
              .              ( $showMeta['descriptionSummary']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-descriptionSummary'>"
              .         "description-summary"
              .        "</label>"
              .       "</div>"
              .       "<br class='clear' />"
              .       "<div class='field userId'>"
              .        "<div class='field userId-avatar'>"
              .         "<input type='checkbox' "
              .                "name='itemsStyleCustom[userId:avatar]' "
              .                  "id='display-userId-avatar'"
              .               ( $showMeta['userId:avatar']
                                 ? " checked='true'"
                                 : ''). " />"
              .         "<label for='display-userId-avatar'>avatar</label>"
              .        "</div>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[userId]' "
              .                 "id='display-userId'"
              .              ( $showMeta['userId']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-userId'>User Id</label>"
              .       "</div>"
              .       "<div class='field tags'>"
              .        "<input type='checkbox' "
              .               "name='itemsStyleCustom[tags]' "
              .                 "id='display-tags' "
              .              "class='tag' "
              .              ( $showMeta['tags']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-tags' class='tag'>"
              .         "tags"
              .        "</label>"
              .        "<label class='tag'> ... </label>"
              .        "<label class='tag'> ... </label>"
              .        "<label class='tag'> ... </label>"
              .        "<label class='tag'> ... </label>"
              .       "</div>"
              .       "<br class='clear' />"
              .       "<div class='dates'>"
              .        "<div class='field tagged'>"
              .         "<input type='checkbox' "
              .               "name='itemsStyleCustom[dates:tagged]' "
              .                 "id='display-dateTagged'"
              .              ( $showMeta['dates:tagged']
                                ? " checked='true'"
                                : ''). " />"
              .         "<label for='display-dateTagged'>date:Tagged</label>"
              .        "</div>"
              .        "<div class='field updated'>"
              .         "<input type='checkbox' "
              .               "name='itemsStyleCustom[dates:updated]' "
              .                 "id='display-dateUpdated'"
              .              ( $showMeta['dates:updated']
                                ? " checked='true'"
                                : ''). " />"
              .         "<label for='display-dateUpdated'>date:Updated</label>"
              .        "</div>"
              .       "</div>"
              .       "<br class='clear' />"
              .      "</div>"
              .     "</div>"
              /*
              .     "<div class='buttons'>"
              .      "<button type='submit' "
              .             "class='ui-button ui-corner-all ui-state-default' "
              .              "name='itemsStyle' "
              .             "value='custom'>apply custom settings</button>"
              .     "</div>"
              */
              .    "</fieldset>";

        $html .=  "</div>"                      // itemsStyle }
              .   "<div id='buttons-global' class='buttons"
              .           ($this->_style === self::STYLE_CUSTOM
                                ? " buttons-custom"
                                : "")
              .                         "'>"
              .    "<button type='submit' "
              .           "class='ui-button ui-corner-all "
              .                 "ui-state-default ui-state-disabled' "
              .           "value='custom'"
              .        "disabled='true'>apply</button>"
              .   "</div>";

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
