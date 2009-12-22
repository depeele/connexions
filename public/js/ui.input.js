/** @file
 *
 *  Provide a ui-styled input / text input area that supports validation.
 *
 *  Requires:
 *      ui.core.js
 */
(function($) {

$.widget("ui.input", {
    /** @brief  Initialize a new instance.
     *
     *  Valid options:
     *      priority        The priority of this field
     *                      ( ['normal'], 'primary', 'secondary');
     *      validationEl:   The element to present validation information in
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
     *      'validationChanged.uiinput' when the validaton state has changed;
     *      'enabled.uiinput'           when element is enabled;
     *      'disabled.uiinput'          when element is disabled.
     */
    _init: function() {
        var self    = this;
        var opts    = this.options;

        opts.enabled = self.element.attr('disabled') ? false : true;

        if (opts.validationEl)
        {
            if (opts.validationEl.jquery === undefined)
                opts.validationEl = $(opts.validationEl);
        }
        else
        {
            /* We ASSUME that the form element is contained within a div along
             * with any  associated validation status element.
             *
             * Use the first child of our parent that has the CSS class
             *  'ui-field-status'
             */
            opts.validationEl = self.element
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
            self.element.addClass('ui-priority-primary');
        else if (opts.priority === 'secondary')
            self.element.addClass('ui-priority-secondary');

        if (opts.enabled)
            self.element.addClass('ui-state-default');
        else
            self.element.addClass('ui-state-disabled');

        // Interaction events
        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self    = this;

        var _mouseenter = function(e) {
            /*
            var el  = $(this);
            if (el.input('option', 'enabled') === true)
                el.addClass('ui-state-hover');
            // */

            if (self.options.enabled === true);
                self.element.addClass('ui-state-hover');
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
                return;

            if (self.keyTimer !== null)
                clearTimeout(self.keyTimer);

            if (e.keyCode === 9)    // tab
                // let '_blur' handle leaving this field.
                return;

            // Clear the current validation information
            self.setValidationState(undefined);

            /* Set a timer that needs to expire BEFORE we fire the validation
             * check
             */
            self.keyTimer = setTimeout(function(){self.validate();}, 1000);
        };

        var _focus      = function(e) {
            /*
            var el  = $(this);
            if (el.input('option', 'enabled') === true)
                el.addClass('ui-state-focus');
            // */
            if (self.options.enabled === true)
                self.element.addClass('ui-state-focus');
        };

        var _blur       = function(e) {
            self.element.removeClass('ui-state-focus');
            if (! self.element.hasClass('ui-state-valid'))
                self.validate();
        };

        self.element
                .bind('mouseenter.uiinput', _mouseenter)
                .bind('mouseleave.uiinput', _mouseleave)
                .bind('keydown.uiinput',    _keydown)
                .bind('focus.uiinput',      _focus)
                .bind('blur.uiinput',       _blur);
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
                        .addClass(   'ui-state-default')
                        .removeAttr('disabled');

            this.element.trigger('enabled.uiinput');
        }
    },

    disable: function()
    {
        if (this.options.enabled)
        {
            this.options.enabled = false;
            this.element.attr('disabled', true)
                        .removeClass('ui-state-default')
                        .addClass(   'ui-state-disabled');

            this.element.trigger('disabled.uiinput');
        }
    },

    getValidationState: function()
    {
        return this.options.valid;
    },

    /** @brief  Set the current validation state.
     *  @param  state   The new state:
     *                      undefined   undetermined
     *                      true        valid
     *                      false       invalid
     *                      string      invalid, error message
     */
    setValidationState: function(state)
    {
        if (state === this.options.valid)
            return;

        // Clear out validation information
        this.element
                .removeClass('ui-state-error ui-state-valid');

        this.options.validationEl
                .empty()
                .removeClass('ui-state-invalid ui-state-valid');

        if (state === true)
        {
            // Valid
            this.element.addClass(   'ui-state-valid');

            this.options.validationEl
                        .addClass(   'ui-state-valid');
        }
        else if (state !== undefined)
        {
            // Invalid, possibly with an error message
            this.element.addClass(   'ui-state-error');

            this.options.validationEl
                        .addClass(   'ui-state-invalid');

            if (typeof state === 'string')
            {
                this.options.validationEl
                            .html(state);
            }
        }

        this.options.valid = state;

        // Let everyone know that the validation state has changed.
        this.element.trigger('validationChanged.uiinput');
    },

    validate: function()
    {
        var msg         = [];
        var newState    = undefined;

        if ($.isFunction(this.options.validation))
        {
            var ret = this.options.validation.apply(this.element,
                                                    [this.element.val()]);
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
            newState = ((this.element.val().length > 0)
                                    ? true
                                    : false);
            msg.push('Cannot be empty');
        }

        // Set the new state
        this.setValidationState( ((newState === false) && (msg.length > 0)
                                    ? msg.join('<br />')
                                    : newState) );
    },

    destroy: function() {
        this.options.validationEl
                .removeClass( 'ui-state-valid '
                             +'ui-state-invalid ');

        this.element
                .removeClass( 'ui-state-default '
                             +'ui-state-disabled '
                             +'ui-state-hover '
                             +'ui-state-valid '
                             +'ui-state-error '
                             +'ui-state-focus '
                             +'ui-priority-primary '
                             +'ui-priority-secondary ')
                .unbind('.uiinput');
    }
});

$.extend($.ui.input, {
    version:    '0.1.1',
    getter:     'isEnabled isValid getValidationState',
    defaults: {
        priority:       'normal',
        validationEl:   null,       // The element to present validation
                                    // information in [:sibling
                                    // .ui-field-status]
        validation:     null        /* The validation criteria
                                     *      '!empty'
                                     *      function(value)
                                     *          returns {isValid:  true|false,
                                     *                   message: string};
                                     */
    }
});


})(jQuery);
