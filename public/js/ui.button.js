/** @file
 *
 *  Provide a ui-styled button.
 *
 *  Requires:
 *      ui.core.js
 */
(function($) {

$.widget("ui.button", {
    version: "0.1.1",
    options: {
        // Defaults
        priority:   'normal'
    },

    /** @brief  Initialize a new instance.
     *
     *  Valid options:
     *      priority        The priority of this field
     *                      ( ['normal'], 'primary', 'secondary');
     *
     *  @triggers:
     *      'enabled.uibutton'  when element is enabled;
     *      'disabled.uibutton' when element is disabled.
     */
    _create: function() {
        var self    = this;
        var opts    = this.options;

        if (opts.enabled !== false)
        {
            opts.enabled = self.element.attr('disabled')
                                ? false
                                : true;
        }

        self.element.addClass( 'ui-button '
                              +'ui-corner-all ');

        if (opts.priority === 'primary')
            self.element.addClass('ui-priority-primary');
        else if (opts.priority === 'secondary')
            self.element.addClass('ui-priority-secondary');

        if (opts.enabled)
            self.enable();
        else
            self.disable();

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
            /*
            var el  = $(this);
            el.removeClass('ui-state-hover');
            // */

            self.element.removeClass('ui-state-hover');
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
        };

        self.element
                .bind('mouseenter.uibutton', _mouseenter)
                .bind('mouseleave.uibutton', _mouseleave)
                .bind('focus.uibutton',      _focus)
                .bind('blur.uibutton',       _blur);
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
        var wasEnabled  = (this.options.enabled === true);

        this.element.removeClass('ui-state-disabled')
                    .addClass(   'ui-state-default')
                    .removeAttr('disabled');

        if (! wasEnabled)
        {
            this.element.trigger('enabled.uibutton');
        }

        this.options.enabled = true;
    },

    disable: function()
    {
        var wasEnabled  = (this.options.enabled === true);

        this.options.enabled = false;
        this.element.attr('disabled', true)
                    .removeClass('ui-state-default')
                    .addClass(   'ui-state-disabled');

        if (wasEnabled)
        {
            this.element.trigger('disabled.uibutton');
        }
    },

    destroy: function() {
        this.element
                .removeClass( 'ui-state-default '
                             +'ui-state-disabled '
                             +'ui-state-hover '
                             +'ui-state-focus '
                             +'ui-priority-primary '
                             +'ui-priority-secondary '
                             +'ui-corner-all ')
                .unbind('.uibutton');
    }
});

})(jQuery);

