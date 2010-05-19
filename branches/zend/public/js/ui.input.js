/** @file
 *
 *  Provide a ui-styled input / text input area that supports validation.
 *
 *  Requires:
 *      ui.core.js
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
        emptyText:      null,
        validationEl:   null,       // The element to present validation
                                    // information in [:sibling
                                    //                  .ui-field-status]
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
     *      emptyText       Text to present when the field is empty;
     *      validationEl:   The element to present validation information in
     *                      [ parent().find('.ui-field-status:first) ]
     *      hideLabel:      Hide any label associated with this input?
     *                          [ true if 'emptyText' is provided,
     *                            false otherwise ];
     *      validation:     The validation criteria:
     *                          '!empty'
     *                          function(value) that returns:
     *                              undefined   undetermined
     *                              true        valid
     *                              false       invalid
     *                              string      invalid, error message
     *
     *  @triggers:
     *      'validationChanged' when the validaton state has changed;
     *      'enabled'           when element is enabled;
     *      'disabled'          when element is disabled.
     */
    _create: function() {
        var self    = this;
        var opts    = this.options;

        opts.enabled = self.element.attr('disabled') ? false : true;

        if (opts.validationEl)
        {
            if (opts.validationEl.jquery === undefined)
            {
                opts.validationEl = $(opts.validationEl);
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

        if (opts.emptyText === null)
        {
            // See if there is an 'emptyText' attribute
            var empty   = self.element.attr('emptyText');
            if ((empty !== undefined) && (empty.length > 0))
            {
                opts.emptyText = empty;
            }

        }

        if ((opts.emptyText === null) && (! self.element.is(':password')) )
        {
            // See if there is a label associated with this field
            if (opts.hideLabel !== false)
            {
                // Attempt to locate the label associated with this field...
                var id      = self.element.attr('id');
                var $label  = null;
                if ((id === undefined) || (id.length < 1))
                {
                    id = self.element.attr('name');
                }

                if ((id !== undefined) && (id.length > 0))
                {
                    $label  = self.element
                                        .parent()
                                            .find('label[for='+ id +']');
                }

                if (($label !== null) && ($label.length > 0))
                {
                    /* We've found the label!  Use it's text as the 'emptyText'
                     * and set 'hideLabel' to true.
                     */
                
                    opts.emptyText = $label.text();
                    opts.hideLabel = true;
                }
            }
        }

        self.setEmptyText(opts.emptyText, true);

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
                if ((self.options.emptyText !== null) &&
                    (self.val().length < 1))
                {
                    self.element.val('');
                }

                self.element.removeClass('ui-state-empty')
                            .addClass('ui-state-focus ui-state-active');
            }
        };

        var _blur       = function(e) {
            self.element.removeClass('ui-state-focus ui-state-active');
            if (! self.element.hasClass('ui-state-valid'))
            {
                self.validate();
            }

            if (self.val().length < 1)
            {
                self.element.addClass('ui-state-empty');

                if (self.options.emptyText !== null)
                {
                    self.element.val(self.options.emptyText);
                }
            }
        };

        self.element
                .bind('mouseenter.uiinput', _mouseenter)
                .bind('mouseleave.uiinput', _mouseleave)
                .bind('keydown.uiinput',    _keydown)
                .bind('focus.uiinput',      _focus)
                .bind('blur.uiinput',       _blur);

        if (this.val().length > 0)
        {
            // Perform an initial validation
            self.validate();
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

            //this.element.trigger('enabled.uiinput');
            this._trigger('enabled');
        }
    },

    disable: function()
    {
        if (this.options.enabled)
        {
            this.options.enabled = false;
            this.element.attr('disabled', true)
                        .addClass('ui-state-disabled');

            //this.element.trigger('disabled.uiinput');
            this._trigger('disabled');
        }
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
        if (state === this.options.valid)
        {
            return;
        }

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
        //this.element.trigger('validationChanged.uiinput');
        this._trigger('validationChanged');
    },

    getEmptyText: function()
    {
        return this.options.emptyText;
    },

    setEmptyText: function(str, force)
    {
        if (this.element.is(':password'))
        {
            this.options.hideLabel = false;
            this.options.emptyText = null;
            return;
        }

        if ((this.options.emptyText !== null) &&
            (this.val() === this.options.emptyText))
        {
            this.element.val('');
        }

        this.options.emptyText = str;

        if (this.options.emptyText !== null)
        {
            if (this.options.hideLabel !== false)
            {
                this.options.hideLabel = true;

                // Attempt to locate the label associated with this field...
                var id      = this.element.attr('id');
                if ((id === undefined) || (id.length < 1))
                {
                    id = this.element.attr('name');
                }

                if ((id !== undefined) && (id.length > 0))
                {
                    var $label  = this.element
                                        .parent()
                                            .find('label[for='+ id +']');
                
                    $label.hide();
                }
            }

            //if ( (force === true) || (this.val().length < 1) )
            if ( ((force === true) || (! this.element.is(':focus')) ) &&
                (this.val().length < 1) )
            {
                this.element.val(this.options.emptyText);
            }
        }
    },

    val: function(newVal)
    {
        var self    = this;
        var ret     = null;

        if (newVal === undefined)
        {
            // Value retrieval
            ret = self.element.val().trim();

            if ((self.options.emptyText !== null) &&
                (ret === self.options.emptyText))
            {
                ret = '';
            }
        }
        else
        {
            ret = self.element.val(newVal);
            self.validate();
        }

        return ret;
    },

    validate: function()
    {
        var msg         = [];
        var newState;

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
            msg.push('Cannot be empty');
        }

        // Set the new state
        this.valid( ((newState === false) && (msg.length > 0)
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
                             +'ui-state-active '
                             +'ui-priority-primary '
                             +'ui-priority-secondary ')
                .unbind('.uiinput');
    }
});


}(jQuery));
