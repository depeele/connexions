<?php
/** @file
 *
 *  View helper to render a paginated set of Users in HTML.
 */
class Connexions_View_Helper_HtmlUsers extends Zend_View_Helper_Abstract
{
    static public   $numericGrouping    = 10;
    static public   $perPageChoices     = array(10, 25, 50, 100);

    static public   $defaults               = array(
        'displayStyle'      => self::STYLE_REGULAR,

        'showMeta'          => null,

        'sortBy'            => self::SORT_BY_DATE_TAGGED,
        'sortOrder'         => Model_UserSet::SORT_ORDER_DESC,

        'perPage'           => 50,
        'scopeItems'        => null
    );


    const STYLE_REGULAR                 = 'regular';
    const STYLE_FULL                    = 'full';
    const STYLE_CUSTOM                  = 'custom';

    static public   $styleTitles        = array(
        self::STYLE_REGULAR => 'Regular',
        self::STYLE_FULL    => 'Full',
        self::STYLE_CUSTOM  => 'Custom'
    );

    /** @brief  User-settable (via display options) style parts / show-meta */
    static protected $userStyleParts    = array(
            'meta:relation'             => true,

            'meta:count:items'          => true,
            'meta:count:tags'           => true,
            'meta:count'                => array(
                // implied sub-values
                'meta:count:items'      => true,
                'meta:count:tags'       => true
            ),

            'avatar'                    => true,
            'userId'                    => true,
            'fullName'                  => true,
            'email'                     => true,
            'tags'                      => true,

            'dates:visited'             => true,
            'dates'                     => array(
                // implied sub-values
                'dates:visited'         => true
            ),

    );


    static public   $styleParts     = array(
        self::STYLE_REGULAR => array(
            'minimized'                 => false,   // show-meta

            'meta'                      => true,    // show-meta
            'meta:relation'             => true,
            'meta:count'                => true,    // show-meta
            'meta:count:items'          => true,
            'meta:count:tags'           => true,

            'avatar'                    => true,
            'userId'                    => true,
            'fullName'                  => true,
            'email'                     => true,
            'tags'                      => false,

            'dates'                     => false,   // show-meta
            'dates:visited'             => false,
        ),
        self::STYLE_FULL    => array(
            'minimized'                 => false,   // show-meta

            'meta'                      => true,    // show-meta
            'meta:relation'             => true,
            'meta:count'                => true,    // show-meta
            'meta:count:items'          => true,
            'meta:count:tags'           => true,

            'avatar'                    => true,
            'userId'                    => true,
            'fullName'                  => true,
            'email'                     => true,
            'tags'                      => true,

            'dates'                     => true,    // show-meta
            'dates:visited'             => true,
        ),
        self::STYLE_CUSTOM  => array(
            'minimized'                 => false,   // show-meta

            'meta'                      => true,    // show-meta
            'meta:relation'             => true,
            'meta:count'                => true,    // show-meta
            'meta:count:items'          => true,
            'meta:count:tags'           => true,

            'avatar'                    => true,
            'userId'                    => true,
            'fullName'                  => true,
            'email'                     => true,
            'tags'                      => true,

            'dates'                     => true,    // show-meta
            'dates:visited'             => true,
        )
    );

    const SORT_BY_NAME              = 'name';
    const SORT_BY_FULLNAME          = 'fullName';
    const SORT_BY_EMAIL             = 'email';

    const SORT_BY_DATE_VISITED      = 'lastVisit';

    const SORT_BY_TAG_COUNT         = 'totalTags';
    const SORT_BY_ITEM_COUNT        = 'totalItems';

    static public   $sortTitles     = array(
                    self::SORT_BY_NAME          => 'User Name',
                    self::SORT_BY_FULLNAME      => 'Full Name',
                    self::SORT_BY_EMAIL         => 'Email Address',

                    self::SORT_BY_DATE_VISITED  => 'Last Visit Date',

                    self::SORT_BY_TAG_COUNT     => 'Tag Count',
                    self::SORT_BY_ITEM_COUNT    => 'Item Count',
                );

    static public   $orderTitles    = array(
                    Model_UserSet::SORT_ORDER_ASC   => 'Ascending',
                    Model_UserSet::SORT_ORDER_DESC  => 'Descending'
                );



    static protected $_initialized  = array();

    /** @brief  Set-able parameters. */
    protected       $_namespace     = 'users';

    protected       $_displayStyle  = null;
    protected       $_showMeta      = null;
    protected       $_sortBy        = null;
    protected       $_sortOrder     = null;
    protected       $_scopeItems    = null;

    /** @brief  Set the View object.
     *  @param  view    The Zend_View_Interface
     *
     *  Override Zend_View_Helper_Abstract::setView() in order to initialize.
     *
     *  Note: If this new view has a 'viewNamespace', change our namespace.
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
            Connexions::log("Connexions_View_Helper_HtmlUsers:: "
                                . "set namespace from view [ {$namespace}]");
            // */

            $this->setNamespace($namespace);
        }

        return $this;
    }

    /** @brief  Render an HTML version of a paginated set of Users or,
     *          if no arguments, this helper instance.
     *  @param  paginator       The Zend_Paginator representing the items to
     *                          be presented.
     *  @param  viewer          A Model_User instance representing the
     *                          current viewer;
     *  @param  tagInfo         A Connexions_Set_Info instance containing
     *                          information about the requested tags;
     *  @param  style           The style to use for each item
     *                          (Connexions_View_Helper_HtmlUsers::
     *                                                          STYLE_*);
     *  @param  sortBy          The field used to sort the items
     *                          (Connexions_View_Helper_HtmlUsers::
     *                                                      SORT_BY_*);
     *  @param  sortOrder       The sort order
     *                          (Model_UserSet::SORT_ORDER_ASC |
     *                           Model_UserSet::SORT_ORDER_DESC)
     *
     *  @return The HTML representation of the users.
     */
    public function htmlUsers($paginator    = null,
                              $viewer       = null,
                              $tagInfo      = null,
                              $style        = null,
                              $sortBy       = null,
                              $sortOrder    = null)
    {
        if ((! $paginator instanceof Zend_Paginator) ||
            (! $viewer    instanceof Model_User)     ||
            (! $tagInfo   instanceof Connexions_Set_Info))
        {
            return $this;
        }

        return $this->render($paginator, $viewer, $tagInfo,
                             $style, $sortBy, $sortOrder);
    }

    /** @brief  Set the namespace, primarily for forms and cookies.
     *  @param  namespace   A string namespace.
     *
     *  @return Connexions_View_Helper_HtmlUsers for a fluent interface.
     */
    public function setNamespace($namespace)
    {
        // /*
        Connexions::log("Connexions_View_Helper_HtmlUsers::"
                            .   "setNamespace( {$namespace} )");
        // */

        if ($this->view !== null)
            // Pass this new namespace into our view
            $this->view->viewNamespace = $namespace;

        $this->_namespace = $namespace;

        if (! @isset(self::$_initialized[$namespace]))
        {
            $view   = $this->view;
            $jQuery =  $view->jQuery();

            $jQuery->addJavascriptFile($view->baseUrl('js/jquery.cookie.js'))
                   ->addJavascriptFile($view->baseUrl('js/ui.stars.js'))
                   ->addJavascriptFile($view->baseUrl('js/ui.checkbox.js'))
                   ->addJavascriptFile($view->baseUrl('js/ui.button.js'))
                   ->addJavascriptFile($view->baseUrl('js/ui.input.js'))
                   ->addOnLoad("init_{$namespace}List();")
                   ->javascriptCaptureStart();

            ?>

/************************************************
 * Initialize display options.
 *
 */
function init_<?= $namespace ?>DisplayOptions()
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

    /* For the anchor within the 'Display Options' button, disable the default
     * browser action but allow the event to bubble up to the click handler on
     * the 'Display Options' button.
     */
    $control.find('a:first, .ui-icon:first')
                                         // Let it bubble up
                    .click(function(e) {e.preventDefault(); });

    var $displayStyle   = $displayOptions.find('.displayStyle');
    var $itemsStyle     = $displayStyle.find('input[name=<?= $namespace ?>Style]');
    var $cControl       = $displayStyle.find('.control:first');
    var $customFieldset = $displayStyle.find('fieldset:first');

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
                $itemsStyle.val( $opt.data('displayStyle' ) );

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
                $itemsStyle.val( $opt.data('displayStyle') );

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
                 *  <?= $namespace ?>SortBy
                 *  <?= $namespace ?>SortOrder
                 *  <?= $namespace ?>PerPage
                 *  <?= $namespace ?>Style
                 *      and possibly
                 *      <?= $namespace ?>StyleCustom[ ... ]
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
 * Initialize group header options.
 *
 */
function init_<?= $namespace ?>GroupHeader()
{
    var $headers    = $('#<?= $namespace ?>List .groupHeader .groupType');
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
function init_<?= $namespace ?>List()
{
    // Initialize display options
    init_<?= $namespace ?>DisplayOptions();

    //var $userItems  = $('#<?= $namespace ?>List form.userItem');

    // Initialize display options
    init_<?= $namespace ?>GroupHeader();
}

            <?php
            $jQuery->javascriptCaptureEnd();
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

    /** @brief  Set the current style.
     *  @param  style   A style value (self::STYLE_*)
     *
     *  @return Connexions_View_Helper_HtmlUsers for a fluent interface.
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
            $style = self::$defaults['displayStyle'];
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_HtmlUsers::'
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
     *  @return Connexions_View_Helper_HtmlUsers for a fluent interface.
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
            $sortBy = self::$defaults['sortBy'];
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_HtmlUsers::'
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
     *  @param  sortOrder   A sortOrder value (Model_UserSet::SORT_ORDER_*)
     *
     *  @return Connexions_View_Helper_HtmlUsers for a fluent interface.
     */
    public function setSortOrder($sortOrder)
    {
        $orig = $sortOrder;

        $sortOrder = strtoupper($sortOrder);
        switch ($sortOrder)
        {
        case Model_UserSet::SORT_ORDER_ASC:
        case Model_UserSet::SORT_ORDER_DESC:
            break;

        default:
            $sortOrder = self::$defaults['sortOrder'];
            break;
        }

        /*
        Connexions::log('Connexions_View_Helper_HtmlUsers::'
                            . "setSortOrder({$orig}) == [ {$sortOrder} ]");
        // */
    
        $this->_sortOrder = $sortOrder;

        return $this;
    }

    /** @brief  Get the current sortOrder value.
     *
     *  @return The sortOrder value (Model_UserSet::SORT_ORDER_*).
     */
    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    /** @brief  Set the current showMeta.
     *  @param  showMeta    A showMeta value (self::SORT_BY_*)
     *
     *  @return Connexions_View_Helper_HtmlUsers for a fluent interface.
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
            Connexions::log('Connexions_View_Helper_HtmlUsers::'
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
            $val = self::$styleParts[$this->_displayStyle];

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
        Connexions::log('Connexions_View_Helper_HtmlUsers::'
                            . 'getShowMeta(): return[ '
                            .       print_r($val, true) .' ]');
        // */
    
        return $val;
    }

    /** @brief  Set the Connexions_Set that specifies the full set of scope 
     *          items (for auto-suggest).
     *  @param  scopeItems  A Connexions_Set instance.
     *
     *  @return Connexions_View_Helper_HtmlUsers for a fluent interface.
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

    /** @brief  Render an HTML version of a paginated set of Users.
     *  @param  paginator       The Zend_Paginator representing the items to
     *                          be presented.
     *  @param  viewer          A Model_User instance representing the
     *                          current viewer;
     *  @param  tagInfo         A Connexions_Set_Info instance containing
     *                          information about the requested tags;
     *  @param  style           The style to use for each item
     *                          (Connexions_View_Helper_HtmlUsers::
     *                                                          STYLE_*);
     *  @param  sortBy          The field used to sort the items
     *                          (Connexions_View_Helper_HtmlUsers::
     *                                                      SORT_BY_*);
     *  @param  sortOrder       The sort order
     *                          (Model_UserSet::SORT_ORDER_ASC |
     *                           Model_UserSet::SORT_ORDER_DESC)
     *
     *  @return The HTML representation of the users.
     */
    public function render(Zend_Paginator            $paginator,
                           Model_User                $viewer,
                           Connexions_Set_info       $tagInfo,
                           $style        = null,
                           $sortBy       = null,
                           $sortOrder    = null)
    {
        /*
        Connexions::log("Connexions_View_Helper_HtmlUsers: "
                            . "style[ {$style} ], "
                            . "sortBy[ {$sortBy} ], "
                            . "sortOrder[ {$sortOrder} ]");
        // */

        if ($style     !== null)    $this->setStyle($style);
        if ($sortBy    !== null)    $this->setSortBy($sortBy);
        if ($sortOrder !== null)    $this->setSortOrder($sortOrder);

        /*
        Connexions::log("Connexions_View_Helper_HtmlUsers: "
                            . "validated to: "
                            . "style[ {$this->_displayStyle} ], "
                            . "styleTitle[ "
                            .   self::$styleTitles[$this->_displayStyle]." ], "
                            . "sortBy[ {$this->_sortBy} ], "
                            . "sortByTitle[ "
                            .       self::$sortTitles[$this->_sortBy]." ], "
                            . "sortOrder[ {$this->_sortOrder} ], "
                            . "sortOrderTitle[ "
                            .       self::$orderTitles[$this->_sortOrder]." ]");
        // */

        $html = "";

        $showMeta   = $this->getShowMeta();


        // Construct the scope auto-completion callback URL
        $scopeParts = array('format=json');
        if ($tagInfo->hasValidItems())
        {
            /*
            Connexions::log(sprintf("Connexions_View_Helper_HtmlUsers: "
                                    .   "reqStr[ %s ], valid[ %s ]",
                                    $tagInfo->reqStr,
                                    var_export($tagInfo->valid, true)) );

            // */

            array_push($scopeParts, 'tags='. $tagInfo->validItems);
        }

        $scopeCbUrl = $this->view->baseUrl('/scopeAutoComplete')
                    . '?'. implode('&', $scopeParts);


        /*
        Connexions::log("Connexions_View_Helper_HtmlUsers: "
                        .       "scopeCbUrl[ {$scopeCbUrl} ]");
        // */

        $uiPagination = $this->view->htmlPaginationControl();
        $uiPagination->setPerPageChoices(self::$perPageChoices);


        $scopeRoot = 'People';
        $scopeUrl  = $this->view->baseUrl('/people');

        $html .= $this->view->htmlItemScope($paginator,
                                            $tagInfo,
                                            'Tags',
                                            'tags',
                                            array($scopeRoot => $scopeUrl),
                                            $scopeCbUrl);

        $html .= "<div id='{$this->_namespace}List'>"   // List {
              .   $uiPagination->render($paginator, 'pagination-top', true)
              .   $this->_renderDisplayOptions($paginator, $showMeta);

        if (count($paginator))
        {
            $html .= "<ul class='people'>";

            // Group by the field identified in $this->_sortBy
            $lastGroup = null;
            foreach ($paginator as $idex => $user)
            {
                $groupVal = $user->{$this->_sortBy};
                $newGroup = $this->_groupValue($this->_sortBy, $groupVal);

                if ($newGroup !== $lastGroup)
                {
                    $html      .= $this->_renderGroupHeader($this->_sortBy,
                                                            $newGroup);
                    $lastGroup  = $newGroup;
                }

                $html .= $this->view->htmlUsersUser($user,
                                                    $viewer,
                                                    $showMeta,
                                                    $idex);
            }

            $html .= "</ul>";
        }


        $html .= $uiPagination->render($paginator)
              .  "<br class='clear' />\n"
              . "</div>\n";                      // List }

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
        case self::SORT_BY_DATE_VISITED:      // 'lastVisit'
            /* Dates are strings of the form YYYY-MM-DD HH:MM:SS
             *
             * Grouping should be by year:month:day, so strip off the time.
             */
            $value = substr($value, 0, 10);
            break;
            
        case self::SORT_BY_NAME:              // 'name'
        case self::SORT_BY_FULLNAME:          // 'fullName'
        case self::SORT_BY_EMAIL:             // 'email'
            $value = strtoupper(substr($value, 0, 1));
            break;

        case self::SORT_BY_TAG_COUNT:         // 'totalTags'
        case self::SORT_BY_ITEM_COUNT:        // 'totalItems'
            /* We'll do numeric grouping in groups of:
             *      self::$numericGrouping [ 10 ]
             */
            $value = floor($value / self::$numericGrouping) *
                                                    self::$numericGrouping;
            break;
        }

        /*
        Connexions::log(
            sprintf("HtmlUsers::_groupValue(%s, %s:%s) == [ %s ]",
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
        case self::SORT_BY_DATE_VISITED:      // 'lastVisit'
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
        case self::SORT_BY_FULLNAME:          // 'fullName'
        case self::SORT_BY_EMAIL:             // 'email'
            $html .= "<div class='groupType alpha'>"
                  .   $value
                  .  "</div>";
            break;

        case self::SORT_BY_TAG_COUNT:         // 'totalTags'
        case self::SORT_BY_ITEM_COUNT:        // 'totalItems'
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
        if (! @isset($show['meta:count']))
        {
            $show['meta:count'] =
                    (($show['meta:count:items'] === true) ||
                     ($show['meta:count:tags']  === true));
        }

        if (! @isset($show['meta']))
        {
            $show['meta'] =
                    (($show['meta:relation']  === true) ||
                     ($show['meta:count']     === true));
        }

        if (! @isset($show['dates']))
        {
            $show['dates'] =
                    (($show['dates:visited'] === true));
        }

        return $show;
    }

    /** @brief  Render the 'displayOptions' control area.
     *  @param  paginator   The current paginator (so we know the number of 
     *                                             items per page);
     *  @param  showMeta    The set of custom options;
     *
     *
     *  @return A string of HTML.
     */
    protected function _renderDisplayOptions($paginator, $showMeta)
    {
        $namespace        = $this->_namespace;
        $itemCountPerPage = $paginator->getItemCountPerPage();

        $html .= "<div class='displayOptions {$namespace}-displayOptions'>"
                                                        // displayOptions {
              .  "<div class='control ui-corner-all ui-state-default'>"
              .   "<a >Display Options</a>"
              .   "<div class='ui-icon ui-icon-triangle-1-s'>"
              .    "&nbsp;"
              .   "</div>"
              .  "</div>"
              .  "<form style='display:none;' "
              .        "class='ui-state-active ui-corner-all'>";    // form {

        $html .=  "<div class='field sortBy'>"  // sortBy {
              .    "<label   for='{$namespace}SortBy'>Sorted by</label>"
              .    "<select name='{$namespace}SortBy' "
              .              "id='{$namespace}SortBy' "
              .           "class='sort-by sort-by-{$this->_sortBy} "
              .                   "ui-input ui-state-default ui-corner-all'>";

        foreach (self::$sortTitles as $key => $title)
        {
            $isOn = ($key == $this->_sortBy);
            $css  = 'ui-corner-all';
            if ($isOn)              $css .= ' option-on';

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


        $html .=  "<div class='field sortOrder'>"       // sortOrder {
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
              .   "<div class='field perPage'>"         // perPage {
              .    "<label for='{$namespace}PerPage'>Per page</label>"
              .    "<select class='ui-input ui-state-default ui-corner-all "
              .                  "count' name='{$namespace}PerPage'>"
              .     "<!-- {$namespace}PerPage: {$itemCountPerPage} -->";

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
              .   "<div class='field displayStyle'>"    // displayStyle {
              .    "<label for='{$namespace}Style'>Display</label>"
              .    "<input type='hidden' name='{$namespace}Style' "
              .          "value='{$this->_displayStyle}' />";

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
                          .     ($this->_displayStyle === self::STYLE_CUSTOM
                                    ? " ui-state-active"
                                    : "")
                          .                 "'>";
                $cssClass  = '';
            }

            $cssClass .= " {$namespace}Style-{$key}";
            if ($key == $this->_displayStyle)
                $cssClass .= ' option-selected';

            $itemHtml .= "<a class='{$cssClass}' "
                      .      "href='?{$namespace}Style={$key}'>{$title}</a>";

            if ($key === self::STYLE_CUSTOM)
            {
                $itemHtml .=  "<div class='ui-icon ui-icon-triangle-1-s'>"
                          .    "&nbsp;"
                          .   "</div>"
                          .  "</div>";
            }

            array_push($parts, $itemHtml);
        }
        $html .= implode("<span class='comma'>, </span>", $parts);


        $html .= sprintf("<fieldset class='custom items'%s>",
                          ($this->_displayStyle !== self::STYLE_CUSTOM
                                ? " style='display:none;'"
                                : ""));

        // Need 'legend' for vertical spacing control
        $html .=    "<div class='item'>"
              .      "<div class='meta'>"
              .       "<div class='field countItems'>"
              .        "<input type='checkbox' "
              .              "name='{$namespace}StyleCustom[meta:count:items]' "
              .                "id='display-countItems'"
              .              ( $showMeta['meta:count:items']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-countItems'>item count</label>"
              .       "</div>"
              .       "<div class='field countTags'>"
              .        "<input type='checkbox' "
              .              "name='{$namespace}StyleCustom[meta:count:tags]' "
              .                "id='display-countTags'"
              .              ( $showMeta['meta:count:tags']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-countTags'>tag count</label>"
              .       "</div>"
              .       "</div>"
              .      "</div>"
              .      "<div class='data'>"
              .       "<div class='field avatar'>"
              .        "<input type='checkbox' "
              .               "name='{$namespace}StyleCustom[avatar]' "
              .                 "id='display-avatar'"
              .              ( $showMeta['avatar']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-avatar'>avatar</label>"
              .       "</div>"
              .       "<h4 class='field userId'>"
              .        "<input type='checkbox' "
              .               "name='{$namespace}StyleCustom[userId]' "
              .                 "id='display-userId'"
              .              ( $showMeta['userId']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-userId'>User Name</label>"
              .       "</h4>"
              .       "<div class='field fullName'>"
              .        "<input type='checkbox' "
              .               "name='{$namespace}StyleCustom[fullName]' "
              .                 "id='display-fullName'"
              .              ( $showMeta['fullName']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-fullName'>full name</label>"
              .       "</div>"
              .       "<div class='field email'>"
              .        "<input type='checkbox' "
              .               "name='{$namespace}StyleCustom[email]' "
              .                 "id='display-email'"
              .              ( $showMeta['email']
                                ? " checked='true'"
                                : ''). " />"
              .        "<label for='display-email'>email address</label>"
              .       "</div>"
              .       "<br class='clear' />"
              .       "<div class='field tags'>"
              .        "<input type='checkbox' "
              .               "name='{$namespace}StyleCustom[tags]' "
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
              .        "<div class='field dateVisited'>"
              .         "<input type='checkbox' "
              .               "name='{$namespace}StyleCustom[dates:visited]' "
              .                 "id='display-dateVisited'"
              .              ( $showMeta['dates:visited']
                                ? " checked='true'"
                                : ''). " />"
              .         "<label for='display-dateVisited'>date:visited</label>"
              .        "</div>"
              .       "</div>"
              .       "<br class='clear' />"
              .      "</div>"
              .     "</div>"
              .    "</fieldset>";

        $html .=  "</div>"                      // displayStyle }
              .   "<div id='buttons-global' class='buttons"
              .           ($this->_displayStyle === self::STYLE_CUSTOM
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
}
