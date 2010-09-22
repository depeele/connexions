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
     */
    _create: function()
    {
        var self    = this;
        var opts    = this.options;

        self.element.addClass( 'ui-form');

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
                                    .parent()
                                        .find('.ui-form-status:first');
        }

        opts.$required = self.element.find('.required');
        opts.$inputs   = self.element.find(  'input[type=text],'
                                           + 'input[type=password],'
                                           + 'textarea');
        if (opts.$submit === undefined)
        {
            opts.$submit = self.element.find( opts.submitSelector );
        }
        opts.$cancel   = self.element.find('button[name=cancel]');
        opts.$reset    = self.element.find('button[name=reset]');

        // Instantiate sub-widgets
        opts.$inputs.input({hideLabel: opts.hideLabels});
        opts.$submit.button({priority:'primary', enabled:false});
        opts.$cancel.button({priority:'secondary'});
        opts.$reset.button({priority:'secondary'});

        self._bindEvents();
    },

    _bindEvents: function()
    {
        var self    = this;
        var opts    = self.options;

        var _validate   = function(e) {
            self.validate();
        };

        var _reset      = function(e) {
            e.preventDefault();
            e.stopPropagation();

            self.reset();
        };

        opts.$inputs.bind('validation_change.uivalidationform', _validate);
        opts.$reset.bind('click.uivalidationform', _reset);

        // Perform an initial validation
        self.validate();
    },

    /************************
     * Public methods
     *
     */
    isEnabled: function() {
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

    validate: function()
    {
        var self        = this;
        var opts        = self.options;
        var isValid     = true;
        var hasChanged  = self.hasChanged();

        if (hasChanged)
        {
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

            if (isValid)
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
            }
        }

        if (hasChanged && isValid)
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

        opts.$inputs.unbind('.uivalidationform');
        opts.$reset.unbind('.uivalidationform');

        opts.$inputs.input('destroy');
        opts.$submit.button('destroy');
        opts.$cancel.button('destroy');
        opts.$reset.button('destroy');

        self.element.removeClass('ui-form');
    }
});


}(jQuery));
