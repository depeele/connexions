/** @file
 *
 *  Provide option groups for a set of checkbox options.
 *
 *  Requires:
 *      ui.core.js
 */
(function($) {

$.widget("ui.dropdownForm", {
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
     *      'apply.uidropdownform'  when the form is submitted;
     */
    _create: function() {
        var self        = this;
        var opts        = self.options;
        var $form       = self.element.find('form:first');
        var $submit     = self.element.find(':submit');

        // Add a toggle control button
        var $control    = 
                $(  "<div class='control ui-corner-all ui-state-default'>"
                  +  "<span>Display Options</span>"
                  +  "<div class='ui-icon ui-icon-triangle-1-s'>"
                  +   "&nbsp;"
                  +  "</div>"
                  + "</div>");

        $control.prependTo(self.element);

        $control.fadeTo(100, 0.5);

        /* Activate a ui.optionGroups handler for any container/div in this
         * form with a CSS class of 'ui-optionGroups'.
         * ui.optionGroups handler for them.
         */
        self.element.find('.ui-optionGroups').optionGroups();


        $form.hide();

        self._bindEvents();

    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self        = this;
        var $control    = self.element.find('.control:first');
        var $form       = self.element.find('form:first');
        var $submit     = self.element.find(':submit');
        

        // Handle a click outside of the display options form.
        var _body_click     = function(e) {
            if ($form.is(':visible') && (! $.contains($form[0], e.target)) )
            {
                /* Hide the form by triggering $control.click and then
                 * mouseleave
                 */
                $control.trigger('click');
                self.element.trigger('mouseleave');
            }
        };

        // Opacity hover effects
        var _mouse_enter    = function(e) {
            $control.fadeTo(100, 1.0);
        };

        var _mouse_leave    = function(e) {
            if ($form.is(':visible'))
                // Don't fade if the form is currently visible
                return;

            $control.fadeTo(100, 0.5);
        };

        var _control_click  = function(e) {
            // Toggle the displayOptions pane
            e.preventDefault();
            e.stopPropagation();

            $form.toggle();
            $control.toggleClass('ui-state-active');

            return false;
        };

        var _prevent_default    = function(e) {
            // Prevent the browser default, but let the event bubble up
            e.preventDefault();
        };

        var _form_change        = function(e) {
            /*
            // Remember which fields have changed
            var changed = self.element.data('changed.uidropdownform');

            if (! $.isArray(changed))
            {
                changed = [];
            }
            changed.push(e.target);

            self.element.data('changed.uidropdownform', changed);
            */

            // Any change within the form should enable the submit button
            $submit.removeClass('ui-state-disabled')
                   .removeAttr('disabled')
                   .addClass('ui-state-default');
        };

        var _form_submit        = function(e) {
            // Serialize all form values to an array...
            var settings    = $form.serializeArray();

            /* ...and set a cookie for each
             *      namespace +'SortBy'
             *      namespace +'SortOrder'
             *      namespace +'PerPage'
             *      namespace +'Style'
             *      and possibly
             *          namespace +'StyleCustom[ ... ]'
             */
            $(settings).each(function() {
                $.log("Add Cookie: name[%s], value[%s]",
                      this.name, this.value);
                $.cookie(this.name, this.value);
            });

            /* Finally, disable ALL inputs so our URL will have no
             * parameters since we've stored them all in cookies.
             */
            self.disable();
            //$form.find('input,select').attr('disabled', true);

            //self.element.trigger('apply.uidropdownform');
            //$form.trigger('apply.uidropdownform');

            // let the form be submitted
        };


        /**********************************************************************
         * bind events
         *
         */

        // Handle a click outside of the display options form.
        $('body')
                .bind('click.uidropdownform', _body_click);

        // Add an opacity hover effect to the displayOptions
        $control.bind('mouseenter.uidroppdownform', _mouse_enter)
                .bind('mouseleave.uidroppdownform', _mouse_leave)
                .bind('click.uidropdownform', _control_click);

        $form.bind('change.uidropdownform', _form_change)
             .bind('submit.uidropdownform', _form_submit);
    },

    /************************
     * Public methods
     *
     */
    getGroup: function() {
        return this.element.find('.displayStyle')
                            .optionGroups( 'getGroup' );
    },

    setGroup: function(style) {
        return this.element.find('.displayStyle')
                            .optionGroups( 'setGroup', style );
    },

    getGroupInfo: function() {
        return this.element.find('.displayStyle')
                            .optionGroups( 'getGroupInfo' );
    },

    open: function() {
        if (this.element.find('form:first').is(':visible'))
            // Already opened
            return;

        this.element.find('.control:first').click();
    },

    close: function() {
        if (! this.element.find('form:first').is(':visible'))
            // Already closed
            return;

        this.element.find('.control:first').click();
    },

    enable: function(enableSubmit) {
        var $form       = this.element.find('form:first');
        var $submit     = this.element.find(':submit');

        $form.find('input,select').removeAttr('disabled');

        if (enableSubmit !== true)
        {
            // Any change within the form should enable the submit button
            $submit.removeClass('ui-state-default ui-state-highlight')
                   .addClass('ui-state-disabled')
                   .attr('disabled', true);
        }
        else
        {
            $submit.removeClass('ui-state-disabled')
                   .removeAttr('disabled')
                   .addClass('ui-state-default');
        }
    },

    disable: function() {
        var $form       = this.element.find('form:first');
        var $submit     = this.element.find(':submit');

        $form.find('input,select').attr('disabled', true);

        // Any change within the form should enable the submit button
        $submit.removeClass('ui-state-default ui-state-highlight')
               .addClass('ui-state-disabled')
               .attr('disabled', true);
    },

    destroy: function() {
        var self        = this;
        var $control    = self.element.find('.control:first');
        var $form       = self.element.find('form:first');
        var $submit     = self.element.find(':submit');

        // Unbind events
        $('body')
                .unbind('.uidropdownform');

        $control.unbind('.uidropdownform');
        $control.find('a:first, .ui-icon:first')
                .unbind('.uidropdownform');

        $form.unbind('.uidropdownform');

        // Remove added elements
        $control.remove();

        self.element.find('.displayStyle').optionGroups( 'destroy' );
    }
});


})(jQuery);
