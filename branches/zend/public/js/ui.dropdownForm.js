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
     *      'enabled.uicheckbox'    when element is enabled;
     *      'disabled.uicheckbox'   when element is disabled;
     *      'checked.uicheckbox'    when element is checked;
     *      'unchecked.uicheckbox'  when element is unchecked.
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


        $form.hide();

        self._bindEvents();

        /* If there is a '.displayStyle' div in this form, activate a
         * ui.optionGroups handler for it.
         */
        var $displayStyle   = self.element.find('.displayStyle');
        if ( (opts.groups !== undefined) && ($displayStyle.length > 0) )
        {
            // Initialize the display style control
            opts.form = $form;
            $displayStyle.optionGroups( opts );
        }

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
                return;

            // For at least Mac Firefox 3.5, for <select>
            // when we move into the options we receive a
            // 'moustout' event on the select box with a
            // related target of 'html'.  The wreaks havoc
            // by de-selecting the select box and it's
            // parent(s), causing the displayOptions to
            // disappear.  NOT what we want, so IGNORE the
            // event.
            if ((e.relatedTarget === undefined) ||
                (e.relatedTarget === null)      ||
                (e.relatedTarget.localName === 'html'))
            {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }

            $control.fadeTo(100, 0.5);
        };

        var _control_click  = function(e) {
            // Toggle the displayOptions pane
            e.preventDefault();
            e.stopPropagation();

            $form.toggle();
            $control.toggleClass('ui-state-active');
        };

        var _prevent_default    = function(e) {
            // Prevent the browser default, but let the event bubble up
            e.preventDefault();
        };

        var _form_change        = function(e) {
            // Any change within the form should enable the submit button
            $submit.removeClass('ui-state-disabled')
                   .removeAttr('disabled')
                   .addClass('ui-state-default,ui-state-highlight');
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
            $form.find('input,select').attr('disabled', true);

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

        /* For the anchor within the 'Display Options' button, disable the
         * default browser action but allow the event to bubble up to the click
         * handler on the 'Display Options' button.
         */
        $control.find('a:first, .ui-icon:first')
                    .bind('click.uidropdownform', _prevent_default);

        $form.bind('change.uidropdownform', _form_change)
             .bind('submit.uidropdownform', _form_submit);
    },

    /************************
     * Public methods
     *
     */
    getStyle: function() {
        return this.element.find('.displayStyle')
                            .optionGroups( 'getStyle' );
    },

    setStyle: function(style) {
        return this.element.find('.displayStyle')
                            .optionGroups( 'setStyle', style );
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
