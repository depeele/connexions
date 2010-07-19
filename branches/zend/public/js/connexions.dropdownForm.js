/** @file
 *
 *  Provide option groups for a set of checkbox options.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      connexions.optionGroups.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($) {

$.widget("connexions.dropdownForm", {
    version: "0.1.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

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

        self.$form      = self.element.find('form:first');
        self.$submit    = self.element.find(':submit');

        /* Convert selects to buttons
        self.$form.find('.field select')
                .button();
        */

        // Add a toggle control button
        self.$control   = 
                $(  "<div class='control'>"
                  +  "<button>Display Options</button>"
                  + "</div>");

        self.$control.prependTo(self.element);

        self.$button = self.$control.find('button');
        self.$button.button({
            icons: {
                secondary:  'ui-icon-triangle-1-s'
            }
        });
        self.$control.fadeTo(100, 0.5);

        /* Activate a connexions.optionGroups handler for any container/div in
         * this form with a CSS class of 'ui-optionGroups'.
         * connexions.optionGroups handler for them.
         */
        self.element.find('.ui-optionGroups').optionGroups();

        self.$form.hide();

        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self        = this;
        

        // Handle a click outside of the display options form.
        var _body_click     = function(e) {
            if (self.$form.is(':visible') &&
                (! $.contains(self.$form[0], e.target)) )
            {
                /* Hide the form by triggering self.$control.click and then
                 * mouseleave
                 */
                self.$control.trigger('click');

                self._trigger('mouseleave', e);
                //self.element.trigger('mouseleave');
            }
        };

        // Opacity hover effects
        var _mouse_enter    = function(e) {
            self.$control.fadeTo(100, 1.0);
        };

        var _mouse_leave    = function(e) {
            if (self.$form.is(':visible'))
            {
                // Don't fade if the form is currently visible
                return;
            }

            self.$control.fadeTo(100, 0.5);
        };

        var _control_click  = function(e) {
            // Toggle the displayOptions pane
            e.preventDefault();
            e.stopPropagation();

            self.$form.toggle();
            self.$button.toggleClass('ui-state-active');

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

            //$.log("connexions.dropdownForm::caught 'form:change'");

            // Any change within the form should enable the submit button
            self.$submit
                    .removeClass('ui-state-disabled')
                    .removeAttr('disabled')
                    .addClass('ui-state-default');
        };

        var _form_submit        = function(e) {
            // Serialize all form values to an array...
            var settings    = self.$form.serializeArray();
            var cookieOpts  = {path: window.location.pathname};
            //e.preventDefault();

            if (window.location.protocol === 'https')
            {
                cookieOpts.secure = true;
            }

            /* ...and set a cookie for each
             *      namespace +'SortBy'
             *      namespace +'SortOrder'
             *      namespace +'PerPage'
             *      namespace +'Style'
             *      and possibly
             *          namespace +'StyleCustom[ ... ]'
             */
            $(settings).each(function() {
                /*
                $.log("Add Cookie: name[%s], value[%s]",
                      this.name, this.value);
                // */
                $.cookie(this.name, this.value, cookieOpts);
            });

            if (! self._trigger('apply', e))
            {
                e.stopImmediatePropagation();
                e.preventDefault();
                e.stopPropagation();
                return false;
            }

            /*
            var callback    = self.options.apply;
            if ($.isFunction(callback))
            {
                callback.call( self.element[0], e);
                //self.options.submitCb(e, self);
            }
            else
            {
                // Reload so our URL won't be polluted with form variables that
                // we've just placed into cookies.
                window.location.reload();
            }
            */
        };

        var _form_clickSubmit   = function(e) {
            e.preventDefault();

            // Trigger the 'submit' event on the form
            self.$form.trigger('submit');
        };

        /**********************************************************************
         * bind events
         *
         */

        // Handle a click outside of the display options form.
        $('body')
                .bind('click.uidropdownform', _body_click);

        // Add an opacity hover effect to the displayOptions
        self.$control
                .bind('mouseenter.uidroppdownform', _mouse_enter)
                .bind('mouseleave.uidroppdownform', _mouse_leave)
                .bind('click.uidropdownform',       _control_click);

        self.$form
                .bind('change.uidropdownform', _form_change)
                .bind('submit.uidropdownform', _form_submit);

        self.$submit
                .bind('click.uidropdownform', _form_clickSubmit);

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

    setApplyCb: function(cb) {
        this.options.apply = cb;
    },

    open: function() {
        if (this.element.find('form:first').is(':visible'))
        {
            // Already opened
            return;
        }

        this.element.find('.control:first').click();
    },

    close: function() {
        if (! this.element.find('form:first').is(':visible'))
        {
            // Already closed
            return;
        }

        this.element.find('.control:first').click();
    },

    enable: function(enableSubmit) {
        var self    = this;

        self.$form.find('input,select').removeAttr('disabled');

        if (enableSubmit !== true)
        {
            // Any change within the form should enable the submit button
            self.$submit
                    .removeClass('ui-state-default ui-state-highlight')
                    .addClass('ui-state-disabled')
                    .attr('disabled', true);
        }
        else
        {
            self.$submit
                    .removeClass('ui-state-disabled')
                    .removeAttr('disabled')
                    .addClass('ui-state-default');
        }
    },

    disable: function() {
        var self    = this;

        self.$form.find('input,select').attr('disabled', true);

        // Any change within the form should enable the submit button
        self.$submit
                .removeClass('ui-state-default ui-state-highlight')
                .addClass('ui-state-disabled')
                .attr('disabled', true);
    },

    destroy: function() {
        var self        = this;

        // Unbind events
        $('body')
                .unbind('.uidropdownform');

        self.$control.unbind('.uidropdownform');
        self.$control.find('a:first, .ui-icon:first')
                     .unbind('.uidropdownform');

        self.$form.unbind('.uidropdownform');

        // Remove added elements
        self.$button.button('destroy');
        self.$control.remove();

        self.element.find('.displayStyle').optionGroups( 'destroy' );
    }
});


}(jQuery));
