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
    _init: function() {
        var self        = this;
        var opts        = self.options;

        self.$form      = self.element.find('form:first');
        self.$reset     = self.element.find(':reset');
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
        self.element
                .find('.ui-optionGroups')
                    .optionGroups({
                        namespace:  opts.namespace,
                        form:       self.$form
                    });

        self.$form.hide();

        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self        = this;
        var opts        = self.options;
        

        // Handle a click outside of the display options form.
        var _body_click     = function(e) {
            /* Ignore this click if:
             *  - our form is currently hidden;
             *  - the target is one of the controls in OUR widget;
             */
            if (self.$form.is(':visible') &&
                (! $.contains(self.element[0], e.target)) )
            {
                /* Hide the form by triggering self.$control.click and then
                 * mouseleave
                 */
                self.$control.triggerHandler('click');
                self.$control.trigger('mouseleave', e);
            }
        };

        // Opacity hover effects
        var _mouse_enter    = function(e) {
            self.$control.fadeTo(100, 1.0);
        };

        var _mouse_leave    = function(e) {
            if ((e && e.type === 'mouseleave') && self.$form.is(':visible'))
            {
                // Don't fade if the form is currently visible
                return;
            }

            self.$control.fadeTo(100, 0.5);
        };

        var _control_click  = function(e) {
            // Toggle the displayOptions pane
            //e.preventDefault();
            //e.stopPropagation();

            self.$form.toggle();
            self.$button.toggleClass('ui-state-active');

            //return false;
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
            self.enableSubmit();
        };

        var _form_reset         = function(e) {
            // Serialize all form values to an array...
            var settings    = self.$form.serializeArray();
            var cookieOpts  = {};
            var cookiePath  = $.registry('cookiePath');

            if (cookiePath)
            {
                cookieOpts.path = cookiePath;
            }

            /* ...and UNSET any cookie related to each
             *      namespace +'SortBy'
             *      namespace +'SortOrder'
             *      namespace +'PerPage'
             *      namespace +'Style'
             *      and possibly
             *          namespace +'StyleCustom[ ... ]'
             */
            $(settings).each(function() {
                // /*
                $.log("connexions.dropdownForm: Delete Cookie: "
                      + "name[%s]",
                      this.name);
                // */
                $.cookie(this.name, null, cookieOpts);
            });

            if (! self._trigger('apply', e))
            {
                e.stopImmediatePropagation();
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        };

        var _form_submit        = function(e) {
            // Serialize all form values to an array...
            var settings    = self.$form.serializeArray();
            var cookieOpts  = {};
            var cookiePath  = $.registry('cookiePath');

            if (cookiePath)
            {
                cookieOpts.path = cookiePath;
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
                $.log("connexions.dropdownForm: Add Cookie: "
                      + "name[%s], value[%s]",
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
        };

        var _form_clickSubmit   = function(e) {
            e.preventDefault();

            // Trigger the 'submit' event on the form
            self.$form.trigger('submit');
        };

        var _form_enable        = function(){ self.enable(); };
        var _form_disable       = function(){ self.disable(); };
        var _form_enableSubmit  = function(){ self.enableSubmit(); };
        var _form_disableSubmit = function(){ self.disableSubmit(); };

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
                .bind('change.uidropdownform',        _form_change)
                .bind('submit.uidropdownform',        _form_submit)
                .bind('enable.uidropdownform',        _form_enable)
                .bind('disable.uidropdownform',       _form_disable)
                .bind('enableSubmit.uidropdownform',  _form_enableSubmit)
                .bind('disableSubmit.uidropdownform', _form_disableSubmit)

        self.$submit
                .bind('click.uidropdownform', _form_clickSubmit);

        self.$reset
                .bind('click.uidropdownform', _form_reset);
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

    enableSubmit: function() {
        var self    = this;

        self.$submit
                .removeClass('ui-state-disabled')
                .removeAttr('disabled')
                .addClass('ui-state-default');
    },

    disableSubmit: function() {
        var self    = this;

        self.$submit
                .removeClass('ui-state-default ui-state-highlight')
                .addClass('ui-state-disabled')
                .attr('disabled', true);
    },

    enable: function(enableSubmit) {
        var self    = this;

        self.$form.find('input,select').removeAttr('disabled');

        if (enableSubmit !== true)
        {
            self.disableSubmit();
        }
        else
        {
            self.enableSubmit();
        }
    },

    disable: function() {
        var self    = this;

        self.$form.find('input,select').attr('disabled', true);

        self.disableSubmit();
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

        self.$submit.unbind('.uidropdownform');
        self.$reset.unbind( '.uidropdownform');

        // Remove added elements
        self.$button.button('destroy');
        self.$control.remove();

        self.element.find('.displayStyle').optionGroups( 'destroy' );
    }
});


}(jQuery));
