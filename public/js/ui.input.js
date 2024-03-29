/** @file
 *
 *  Provide a ui-styled input / text input area that supports validation.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false, clearTimeout:false, setTimeout:false */
(function($) {

$.widget("ui.input", {
    version: "0.1.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Defaults
        priority:       'normal',
        $label:         null,       // The field label element.
        hideLabel:      true,       /* Should the label be hidden / used to
                                     * present a default value for the field
                                     * [ true ];
                                     */

        handleAutofill: false,      /* Should we attempt to handle issues with
                                     * browser auto-fill where input values
                                     * are automatically filled but no
                                     * 'change' or 'update' events are fired?
                                     */

        $validation:    null,       /* The element to present validation
                                     * information in [:sibling
                                     *                  .ui-field-status]
                                     */
        validation:     null        /* The validation criteria
                                     *      '!empty'
                                     *      function(value)
                                     *          returns {isValid:  true|false,
                                     *                   message: string};
                                     */
    },

    /** @brief  Initialize a new instance.
     *
     *  Valid options:
     *      priority        The priority of this field
     *                      ( ['normal'], 'primary', 'secondary');
     *      $label:         The field label element.
     *      hideLabel:      Should the label be hidden / used to present a
     *                      default value for the field [ true ];
     *      $validation:    The element to present validation information in
     *                      [ parent().find('.ui-field-status:first) ]
     *      validation:     The validation criteria:
     *                          '!empty'
     *                          function(value) that returns:
     *                              undefined   undetermined
     *                              true        valid
     *                              false       invalid
     *                              string      invalid, error message
     *
     *  @triggers:
     *      'validation_change' when the validaton state has changed;
     *      'enabled'           when element is enabled;
     *      'disabled'          when element is disabled.
     */
    _init: function()
    {
        var self    = this;
        var opts    = this.options;

        // Remember the original value (no validation)
        self.saved( true );

        opts.enabled = self.element.attr('disabled') ? false : true;

        if (opts.$validation)
        {
            if (opts.$validation.jquery === undefined)
            {
                opts.$validation = $(opts.$validation);
            }
        }
        else
        {
            /* We ASSUME that the form element is contained within a div along
             * with any  associated validation status element.
             *
             * Use the first child of our parent that has the CSS class
             *  'ui-field-status'
             */
            opts.$validation = self.element
                                        .parent()
                                            .find('.ui-field-status:first');
        }

        if ( (! opts.validation) && (self.element.hasClass('required')) )
        {
            // Use a default validation of '!empty'
            opts.validation = '!empty';
        }

        self.element.addClass( 'ui-input '
                              +'ui-corner-all ');
        self.keyTimer = null;

        if (opts.priority === 'primary')
        {
            self.element.addClass('ui-priority-primary');
        }
        else if (opts.priority === 'secondary')
        {
            self.element.addClass('ui-priority-secondary');
        }

        self.element.addClass('ui-state-default');
        if (! opts.enabled)
        {
            self.element.addClass('ui-state-disabled');
        }

        var id  = self.element.attr('id');
        if ((id === undefined) || (id.length < 1))
        {
            id = self.element.attr('name');
        }

        if ((id !== undefined) && (id.length > 0))
        {
            opts.$label  = self.element
                                .parent()
                                    .find('label[for='+ id +']');
        }
        else
        {
            opts.$label = self.element.closest('label');
        }

        if (opts.hideLabel === true)
        {
            opts.$label.addClass('ui-input-over')
                       .hide();
        }
        else
        {
            opts.$label.addClass('ui-input-over')
                       .show();
        }
        this._bindEvents();
    },

    _bindEvents: function()
    {
        var self    = this;
        var opts    = self.options;

        var _mouseenter = function(e) {
            /*
            var el  = $(this);
            if (el.input('option', 'enabled') === true)
                el.addClass('ui-state-hover');
            // */

            if (self.options.enabled === true)
            {
                self.element.addClass('ui-state-hover');
            }
        };

        var _mouseleave = function(e) {
            var el  = $(this);
            el.removeClass('ui-state-hover');
        };

        var _keydown   = function(e) {
            /*
            var el  = $(this);
            if (el.input('option', 'enabled') === true)
                el.input('validate');
            // */
            if (self.options.enabled !== true)
            {
                return;
            }

            if (self.keyTimer !== null)
            {
                clearTimeout(self.keyTimer);
            }

            if (e.keyCode === 9)    // tab
            {
                // let '_blur' handle leaving this field.
                return;
            }

            // Clear the current validation information
            self.valid(undefined);

            /* Set a timer that needs to expire BEFORE we fire the validation
             * check
             */
            self.keyTimer = setTimeout(function(){self.validate();}, 1000);
        };

        var _focus      = function(e) {
            if (self.options.enabled === true)
            {
                if (opts.hideLabel === true)
                {
                    opts.$label.hide();
                }

                self.element.removeClass('ui-state-empty')
                            .addClass('ui-state-focus ui-state-active');
            }
        };

        var _blur       = function(e) {
            self._blur();
        };

        var _validate   = function(e) {
            self.validate();
        };

        self.element
                .bind('mouseenter.uiinput', _mouseenter)
                .bind('mouseleave.uiinput', _mouseleave)
                .bind('keydown.uiinput',    _keydown)
                .bind('focus.uiinput',      _focus)
                .bind('blur.uiinput',       _blur)
                .bind('change.uiinput',     _blur)
                .bind('validate.uiinput',   _validate);

        opts.$label
                .bind('click.uiinput', function() { self.element.focus(); });

        if (self.val() !== '')
        {
            // Perform an initial validation
            self.validate();
        }
        else if (opts.hideLabel === true)
        {
            opts.$label.show();
        }

        if (opts.handleAutofill === true)
        {
            self._handleAutofill();
        }
    },

    /** @brief  Hack to try and deal with browser autofill issues when the
     *          browser doesn't fire any 'change' event on autofill.
     */
    _handleAutofill: function() {
        /* Handle some browser autocompletion issues by doing a small bit of
         * polling at 10, 100, 1000, and 10000 microseconds to see if the
         * browser makes an unannounced change to the value.
         */
        var self            = this;
        var timeout         = 1;
        var autofillCheck   = function() {
            if (self.hasChanged())
            {
                $.log('ui.input: id[ %s ] unannounced change after %sms!',
                      self.element.attr('name'), timeout);
                self._blur();
            }
            else if (timeout < 10000)
            {
                // Wait a bit longer
                timeout *= 10;

                $.log('ui.input: id[ %s ] wait for %sms for a change...',
                      self.element.attr('name'), timeout);
                window.setTimeout(function() { autofillCheck(); }, timeout);
            }
            else
            {
                $.log('ui.input: id[ %s ] NO change after %sms',
                      self.element.attr('name'), timeout);
            }
        };

        autofillCheck();
    },

    _blur: function()
    {
        var self    = this;
        var opts    = self.options;

        self.element.removeClass('ui-state-focus ui-state-active');
        if (! self.element.hasClass('ui-state-valid'))
        {
            self.validate();
        }

        if (self.val() === '')
        {
            self.element.addClass('ui-state-empty');

            if (opts.hideLabel === true)
            {
                opts.$label.show();
            }
        }
        else
        {
            if (opts.hideLabel === true)
            {
                opts.$label.hide();
            }

            self.element.removeClass('ui-state-empty');
        }
    },

    /************************
     * Public methods
     *
     */
    isEnabled: function() {
        return this.options.enabled;
    },

    isValid: function() {
        return this.options.valid;
    },

    enable: function()
    {
        if (! this.options.enabled)
        {
            this.options.enabled = true;
            this.element.removeClass('ui-state-disabled')
                        .removeAttr('disabled');
            this.options.$label
                        .removeClass('ui-state-disabled')
                        .removeAttr('disabled');

            try {
                //this.element.trigger('enabled.uiinput');
                this._trigger('enabled');
            } catch(e) {}
        }
    },

    disable: function()
    {
        if (this.options.enabled)
        {
            this.options.enabled = false;
            this.element.attr('disabled', true)
                        .addClass('ui-state-disabled');
            this.options.$label
                        .attr('disabled', true)
                        .addClass('ui-state-disabled');

            try {
                //this.element.trigger('disabled.uiinput');
                this._trigger('disabled');
            } catch(e) {}
        }
    },

    /** @brief  Reset the input to its original (creation or last direct set)
     *          value.
     */
    reset: function()
    {
        // Restore the original value
        this.val( this.element.data('value.uiinput') );

        this.element
                .removeClass('ui-state-error ui-state-valid ui-state-changed');

        // Invoke '_blur' which will cause a re-validation.
        this._blur();

        // On reset, don't leave anything marked error, valid OR changed.
        this.element
                .removeClass('ui-state-error ui-state-valid ui-state-changed');
    },

    /** @brief  Has the value of this input changed from its original?
     *
     *  @return true | false
     */
    hasChanged: function()
    {
        return (this.val() !== this.element.data('value.uiinput'));
    },

    /** @brief  Set the current validation state.
     *  @param  state   The new state:
     *                      undefined   undetermined
     *                      true        valid
     *                      false       invalid
     *                      string      invalid, error message
     */
    valid: function(state)
    {
        var self    = this;
        var opts    = this.options;
        if (state === opts.valid)
        {
            return;
        }

        // Clear out validation information
        self.element
                .removeClass('ui-state-error ui-state-valid ui-state-changed');

        var hasVal  = ( opts.$validation.length > 0);

        if ( hasVal )
        {
            opts.$validation
                .html('&nbsp;')
                .removeClass('ui-state-invalid ui-state-valid');
        }

        if (state === true)
        {
            // Valid
            self.element.addClass(   'ui-state-valid');

            if ( hasVal )
            {
                opts.$validation
                            .addClass(   'ui-state-valid');
            }
        }
        else if (state !== undefined)
        {
            // Invalid, possibly with an error message
            self.element.addClass(   'ui-state-error');

            if ( hasVal )
            {
                opts.$validation.addClass(   'ui-state-invalid');

                if (typeof state === 'string')
                {
                    opts.$validation.html(state);
                }
            }
        }

        if (self.hasChanged())
        {
            self.element.addClass('ui-state-changed');
        }

        opts.valid = state;

        // Let everyone know that the validation state has changed.
        //self.element.trigger('validation_change.uiinput');

        if (state !== undefined)
        {
            try {
                self._trigger('validation_change', null, [state]);
            } catch(e) {}
        }
    },

    getLabel: function()
    {
        return this.options.$label.text();
    },

    setLabel: function(str)
    {
        this.options.$label.text(str);
    },

    getOrigValue: function()
    {
        return this.element.data('value.uiinput');
    },

    /** @brief  This field has been successfully saved.  Update the "original"
     *          value to the current value so changes can be properly
     *          reflected.
     *  @param  noValidation    (Internal use) do NOT perform validation
     *                          [ false ];
     */
    saved: function(noValidation)
    {
        this.element.data('value.uiinput', this.val() );
        if (noValidation !== true)
        {
            // Force valid() to reset any CSS classes
            this.options.valid = undefined;
            this.validate();
        }
    },

    val: function(newVal)
    {
        if (newVal !== undefined)
        {
            newVal = $.trim(newVal);

            // Unset the current validation status
            this.element.removeClass('ui-state-valid');
            delete this.options.valid;

            var ret = this.element.val( newVal );

            // Invoke _blur() to validate
            this._blur();

            /* Do NOT set 'value.uiinput' here.  It's supposed to represent the
             * original value of the input for change purposes.
             */

            return ret;
        }

        return $.trim( this.element.val() );
    },

    validate: function()
    {
        var msg         = [];
        var newState;

        if (this.options.validation === null)
        {
            this.valid( true );
            return;
        }

        if ($.isFunction(this.options.validation))
        {
            var ret = this.options.validation.apply(this.element,
                                                    [this.val()]);
            if (typeof ret === 'string')
            {
                // Invalid with a message
                newState = false;
                msg.push(ret);
            }
            else
            {
                // true | false | undefined
                newState = ret;
            }
        }
        else if (this.options.validation === '!empty')
        {
            newState = ((this.val().length > 0)
                                    ? true
                                    : false);

            if (! newState)
            {
                msg.push('Cannot be empty');
            }
        }

        // Set the new state
        this.valid( ((newState === false) && (msg.length > 0)
                                    ? msg.join('<br />')
                                    : newState) );
    },

    destroy: function() {
        this.options.$validation
                .removeClass( 'ui-state-valid '
                             +'ui-state-invalid ');
        this.options.$label
                .unbind('.uiinput');

        this.element
                .removeClass( 'ui-state-default '
                             +'ui-state-disabled '
                             +'ui-state-hover '
                             +'ui-state-valid '
                             +'ui-state-error '
                             +'ui-state-focus '
                             +'ui-state-active '
                             +'ui-priority-primary '
                             +'ui-priority-secondary ')
                .unbind('.uiinput')
                .removeData('.uiinput');
    }
});


}(jQuery));
