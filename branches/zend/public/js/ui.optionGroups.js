/** @file
 *
 *  Provide option groups for a set of checkbox options.
 *
 *  Requires:
 *      ui.core.js
 */
(function($) {

$.widget("ui.optionGroups", {
    version: "0.1.1",
    options: {
        // Defaults
        namespace:  null,   // Form/cookie namespace
        form:       null,   // Our parent/controlling form
        groups:     null    // Display style groups.
    },

    /** @brief  Initialize a new instance.
     *
     *  Valid options are:
     *      namespace   The form / cookie namespace [ '' ];
     *      groups      An object of style-name => CSS selector;
     *
     *  @triggers:
     *      'enabled.uicheckbox'    when element is enabled;
     *      'disabled.uicheckbox'   when element is disabled;
     *      'checked.uicheckbox'    when element is checked;
     *      'unchecked.uicheckbox'  when element is unchecked.
     */
    _create: function() {
        var self    = this;
        var opts    = this.options;

        if (opts.namespace === null)
        {
            // See if the DOM element has a 'namespace' data item
            var ns  = self.element.data('namespace');
            if (ns !== undefined)
                opts.namespace = ns;
        }
        if (opts.form === null)
        {
            // See if the DOM element has a 'form' data item
            var fm  = self.element.data('form');
            if (fm !== undefined)
                opts.form = fm;
            else
                // Choose the closest form
                opts.form = self.element.closest('form');
        }
        if (opts.groups === null)
        {
            // See if the DOM element has a 'groups' data item
            var sg  = self.element.data('groups');
            if (sg !== undefined)
                opts.groups = sg;
        }


        /* Attach a data item to each display option identifying the display
         * style.  In the process, identify the currently selected group, the
         * LAST one with  CSS class 'option-selected'.
         *
         * Note: The style name is pulled from the CSS class:
         *          namespace+'Style-<type>'
         *
         *       The currently active group has the CSS class:
         *          'option-selected'
         *
         *  :XXX: Should we use 'groups' here?
         */
        self.element.find('a.option,div.option a:first').each(function() {
            // Retrieve the new style value
            var css     = $(this).attr('class');
            var pos     = css.indexOf(opts.namespace +'Style-')
                        + opts.namespace.length + 6 /* Style- len */;
            var group   = null;

            group = css.substr(pos);
            pos   = group.indexOf(' ');
            if (pos > 0)
                group = group.substr(0, pos);

            // Save the group a data item
            $(this).data('group',  group);
        });

        // Interaction events
        self._bindEvents();


        /* If the currently selected group is NOT the fieldset control, toggle
         * the fieldset control closed.
         */
        if (! this.element.find('a.option-selected')
                                .parent().hasClass('control'))
        {
            self.toggleFieldset();
        }
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self            = this;
        var $styleControl   = self.element.find('.control:first');
        var $styleFieldset  = self.element.find('fieldset:first');
        var $itemsStyle     = self.element.find('input[name='
                            +                       self.options.namespace
                            +                                   'Style]');


        var _prevent_default        = function(e) {
            e.preventDefault();
        };

        var _styleControl_click     = function(e) {
            e.preventDefault();
            e.stopPropagation();

            self.toggleFieldset();
        };

        var _styleFieldset_change   = function(e) {
            var $opt    = $styleControl.find('a:first');

            // Activate this style
            self.setStyle( $opt.data('group') );
        };

        var _group_click     = function(e) {
            // Allow only one display style to be selected at a time
            e.preventDefault();
            e.stopPropagation();

            var $opt        = $(this);

            // Activate this style
            self.setStyle( $opt.data('group') );
        };

        // Bind to submit.
        var _form_submit        = function(e) {
            /* Remove all cookies related to 'custom' style.  This is
             * because, when an option is NOT selected, it is not
             * included so, to remove a previously selected options, we
             * must first remove them all and then add in the ones that
             * are explicitly selected.
             */
            $styleFieldset.find('input').each(function() {
                $.cookie( $(this).attr('name'), null );
            });

            /* If the selected display style is NOT 'custom', disable
             * all the 'display custom' pane/field-set inputs so they
             * will not be included in the serialization of form
             * values.
             */
            if ($itemsStyle.val() !== 'custom')
            {
                // Disable all custom field values
                $styleFieldset.find('input').attr('disabled', true);
            }

            // let the form be submitted
        };


        /**********************************************************************
         * bind events
         *
         */

        /* Toggle the display style area.
         * the display style to 'custom', de-selecting the others.
         */
        $styleControl
                .bind('click.uioptiongroups', _styleControl_click);

        /* For all anchors within the control button, disable the default
         * browser action but allow the event to bubble up to any parent click
         * handlers (e.g. _styleControl_click).
         */
        $styleControl.find('> a, .control > a, .control > .ui-icon')
                .bind('click.uioptiongroups', _prevent_default);

        /* When something in the style fieldset changes, set the display style
         * to 'custom', de-selecting the others.
         */
        $styleFieldset
                .bind('change.uioptiongroups', _styleFieldset_change);

        // Allow only one display style to be selected at a time
        self.element.find('a.option')
                .bind('click.uioptiongroups', _group_click);

        // Bind to submit.
        self.options.form
                .bind('submit.uioptiongroups', _form_submit);
    },

    /************************
     * Public methods
     *
     */
    getStyle: function() {
        return this.element.find('a.option-selected')
                                    .data('group');
    },

    setStyle: function(style) {
        // Save the style in our hidden input
        var self            = this;
        var $itemsStyle     = self.element.find('input[name='
                            +                       self.options.namespace
                            +                                   'Style]');
        var $styleFieldset  = self.element.find('fieldset:first');


        $itemsStyle.val( style );

        // Remove the 'option-selected' class from the current selection
        self.element.find('a.option-selected')
                                    .removeClass('option-selected');

        // Add the 'option-selected' class to the new selection
        self.element.find('a.'+ self.options.namespace +'Style-'+ style)
                                    .addClass('option-selected');

        if (style !== 'custom')
        {
            // Turn OFF all items in the style fieldset...
            $styleFieldset.find('input').removeAttr('checked');

            // Turn ON  the items for this new display style.
            $styleFieldset.find( self.options.groups[ style ])
                           .attr('checked', true);
        }

        // Trigger a change event on our form
        self.options.form.change();
    },

    toggleFieldset: function()
    {
        this.element.find('fieldset:first')
                                .toggleClass('ui-state-active')
                                .toggle();
    },

    destroy: function() {
        var self    = this;

        // Remove data
        self.element.find('a.option,div.option a:first')
                .removeData('group');

        // Unbind events
        var $styleControl   = self.element.find('.control:first');
        var $itemsStyle     = self.element.find('input[name='
                            +                       self.options.namespace
                            +                                   'Style]');

        /* Toggle the display style area.
         * the display style to 'custom', de-selecting the others.
         */
        $styleControl
                .unbind('.uioptiongroups');

        /* For all anchors within the control button, disable the default
         * browser action but allow the event to bubble up to any parent click
         * handlers (e.g. _styleControl_click).
         */
        $styleControl.find('> a, .control > a, .control > .ui-icon')
                .unbind('.uioptiongroups');

        /* When something in the style fieldset changes, set the display style
         * to 'custom', de-selecting the others.
         */
        self.element.find('fieldset:first')
                .unbind('.uioptiongroups');

        // Allow only one display style to be selected at a time
        self.element.find('a.option')
                .unbind('.uioptiongroups');

        // Bind to submit.
        self.options.form
                .unbind('.uioptiongroups');
    }
});


})(jQuery);
