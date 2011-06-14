/** @file
 *
 *  Provide a ui-styled validation form.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.input.js
 *      ui.button.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false, clearTimeout:false, setTimeout:false */
(function($) {

$.widget("ui.validationForm", {
    version: "0.1.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Defaults
        submitSelector: 'button[name=submit]',
                                    /* The jQuery selector for the
                                     * primary submit button
                                     *  [ button[name=submit] ];
                                     */
        hideLabels:     true,       /* Should the input labels be hidden / used
                                     * to present a default value for the field
                                     * [ true ];
                                     */
        disableSubmitOnUnchanged:
                        true,       /* Should the submit button be disabled
                                     * if the fields are valid but have not
                                     * changed from the initial values
                                     * [ true ];
                                     */
        handleAutofill: false,      /* Should we attempt to handle issues with
                                     * browser auto-fill where input values
                                     * are automatically filled but no
                                     * 'change' or 'update' events are fired?
                                     */

        $status:        null        /* The element to present validation
                                     * information in [:sibling
                                     *                  .ui-form-status]
                                     */
    },

    /** @brief  Initialize a new instance.
     *
     *  Valid options:
     *      $status:        The element to present validation information in
     *                      [ parent().find('.ui-form-status:first) ]
     *
     *  @triggers:
     *      'validation_change' when the validaton state has changed;
     *      'enabled'           when element is enabled;
     *      'disabled'          when element is disabled.
     *      'submit'            When the form is submitted.
     *      'cancel'            if the form has a 'cancel' button and it is
     *                          clicked.
     */
    _init: function()
    {
        var self    = this;
        var opts    = this.options;

        self.element.addClass( 'ui-form');

        if (! $.isFunction(opts.validate))
        {
            opts.validate = function() {
                return self._validate();
            };
        }

        opts.enabled = self.element.attr('disabled') ? false : true;

        if (opts.$status)
        {
            if (opts.$status.jquery === undefined)
            {
                opts.$status = $(opts.$status);
            }
        }
        else
        {
            /* We ASSUME that the form element is contained within a div along
             * with any  associated validation status element.
             *
             * Use the first child of our parent that has the CSS class
             *  'ui-form-status'
             */
            opts.$status = self.element
                                    .parents('.ui-validation-form:first')
                                        .find('.ui-form-status:first');
        }

        opts.$required = self.element.find('.required');
        opts.$inputs   = self.element.find(  'input[type=text],'
                                           + 'input[type=password],'
                                           + 'textarea');
        opts.$buttons  = self.element.find(  'button');

        if (opts.$submit === undefined)
        {
            opts.$submit = self.element.find( opts.submitSelector );
        }
        opts.$cancel   = self.element.find('button[name=cancel]');
        opts.$reset    = self.element.find('button[name=reset]');

        // Instantiate sub-widgets if they haven't already been instantiated
        opts.$inputs.each(function() {
            var $el = $(this);
            if ($el.data('input'))  return;
            $el.input({
                hideLabel:      opts.hideLabels,
                handleAutofill: opts.handleAutofill
            });
        });

        opts.$buttons.each(function() {
            var $el = $(this);
            if ($el.data('button'))  return;
            $el.button();
        });

        opts.$submit.button('disable');

        /*
        opts.$submit.button({priority:'primary', enabled:false});
        opts.$cancel.button({priority:'secondary'});
        opts.$reset.button({priority:'secondary'});
        // */

        self._bindEvents();

        // Perform an initial validation
        self.validate();
    },

    _bindEvents: function()
    {
        var self    = this;
        var opts    = self.options;

        var _validate   = function(e) {
            self.validate();
        };

        var _cancel_click   = function(e, data) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            // :TODO: "Cancel" notification
            self._trigger('canceled', null, data);
            self._trigger('complete');
        };

        var _reset_click   = function(e, data) {
            e.preventDefault();
            e.stopPropagation();

            self.reset();
        };

        opts.$inputs.bind('validation_change.uivalidationform', _validate);
        opts.$cancel.bind('click.uivalidationform',             _cancel_click);
        opts.$reset.bind('click.uivalidationform',              _reset_click);
    },

    /** @brief  Default callback for _trigger('validate')
     */
    _validate: function()
    {
        var self        = this;
        var opts        = self.options;
        var isValid     = true;

        opts.$required.each(function() {
            /*
            $.log( 'ui.validationForm::validate: '
                  +      'name[ '+ this.name +' ], '
                  +     'class[ '+ this.className +' ]');
            // */

            if (! $(this).hasClass('ui-state-valid'))
            {
                isValid = false;
                return false;
            }
        });

        return isValid;
    },

    /************************
     * Public methods
     *
     */
    isEnabled: function()
    {
        return this.options.enabled;
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
            this.options.$label
                        .attr('disabled', true)
                        .addClass('ui-state-disabled');

            //this.element.trigger('disabled.uiinput');
            this._trigger('disabled');
        }
    },

    /** @brief  Reset any ui.input fields to their original
     *          (creation or direct set) values.
     */
    reset: function()
    {
        var self    = this;
        var opts    = this.options;

        opts.$inputs.input('reset');

        // Perform a validation
        self.validate();
    },

    /** @brief  The form has been successfully submitted/saved so any
     *          "original" values (e.g. in ui.input widgets) should be
     *          updated to allow further edits to properly reflect changes.
     */
    saved: function()
    {
        this.options.$inputs.input('saved');
        this.validate();
    },

    /** @brief  Have any of the ui.input fields changed from their original
     *          values?
     *
     *  @return true | false
     */
    hasChanged: function()
    {
        var self    = this;
        var opts    = this.options;
        var hasChanged  = false;

        // Has anything changed from the forms initial values?
        opts.$inputs.each(function() {
            if ($(this).input('hasChanged'))
            {
                hasChanged = true;
                return false;
            }
        });

        return hasChanged;
    },

    /** @brief  Invoked when additional inputs have been added to the form.
     */
    rebind: function()
    {
        var self    = this;
        var opts    = self.options;

        // Make sure our lists are up-to-date
        opts.$required = self.element.find('.required');
        opts.$inputs   = self.element.find(  'input[type=text],'
                                           + 'input[type=password],'
                                           + 'textarea');

        // Unbind any existing events
        opts.$inputs.unbind('.uivalidationform');

        // and rebind
        self._bindEvents();
    },

    /** @brief  Invoked to perform validation.
     */
    validate: function()
    {
        var self        = this;
        var opts        = self.options;
        var isValid     = opts.validate();

        if (isValid === true)
        {
            opts.$status
                    .removeClass('error')
                    .addClass('success')
                    .text('');
        }
        else
        {
            opts.$status
                    .removeClass('success')
                    .addClass('error');

            if ($.type(isValid) === 'string')
            {
                opts.$status.text( isValid );
            }
        }

        if ((isValid === true) &&
            ( (opts.disableSubmitOnUnchanged === false) || self.hasChanged()) )
        {
            opts.$submit.button('enable');
        }
        else
        {
            opts.$submit.button('disable');
        }
    },

    destroy: function() {
        var self    = this;
        var opts    = self.options;

        self.element.unbind('.uivalidationform');
        opts.$inputs.unbind('.uivalidationform');
        opts.$submit.unbind('.uivalidationform');
        opts.$cancel.unbind('.uivalidationform');
        opts.$reset.unbind('.uivalidationform');

        opts.$inputs.input('destroy');
        opts.$submit.button('destroy');
        opts.$cancel.button('destroy');
        opts.$reset.button('destroy');

        self.element.removeClass('ui-form');
    }
});


}(jQuery));
