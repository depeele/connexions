/** @file
 *
 *  Provide a sprite-based checkbox.
 *
 *  Requires:
 *      ui.core.js
 */
(function($) {

$.widget("ui.checkbox", {
    /** @brief  Initialize a new instance.
     *
     *  Valid options are:
     *      css         General space-separated CSS class(es) for the checkbox
     *                  [ 'checkbox' ];
     *      cssOn       Space-separated CSS class(es) when checked
     *                  [ 'on' ];
     *      cssOff      Space-separated CSS class(es) when un-checked
     *                  [ 'off' ];
     *      titleOn     Title when checked
     *                  [ 'click to turn off' ];
     *      titleOff    Title when un-checked
     *                  [ 'click to turn on' ];
     *
     *      useElTitle  Include the title of the source element (or it's
     *                  associated label) in the title of this checkbox (as a
     *                  prefix to 'titleOn' or 'titleOff')
     *                  [ true ];
     *
     *      hideLabel   Hide the associated label?  If not, clicking on the
     *                  title will be the same as clicking on the checkbox
     *                  [ false ].
     */
    _init: function() {
        var self    = this;
        var opts    = this.options;

        opts.enabled = self.element.attr('disabled') ? false : true;
        opts.checked = self.element.attr('checked')  ? true  : false;
        opts.title   = '';

        var name     = self.element.attr('name')
        var id       = self.element.attr('id')

        // Try to locate the associated label
        self.$label  = false;

        if (id)
            self.$label = $('label[for='+ id +']');
        if ((! self.$label) && name)
            self.$label = $('label[for='+ name +']');

        if (opts.useElTitle === true)
        {
            opts.title += ': ';

            title = self.element.attr('title');
            if ( ((! opts.title) || (opts.title.length < 1)) &&
                 (self.$label.length > 0) )
            {
                opts.title = self.$label.text();
            }
        }

        var title   = opts.title
                    + (opts.checked
                            ? opts.titleOn
                            : opts.titleOff);

        // Create a new element that will be place just after the current
        self.$orig   = self.element;
        self.element = $(  '<span class="checkbox">'
                          + '<div '
                          +    'class="'+ opts.css
                          +      (opts.enabled ? ' '   : ' diabled ')
                          +      (opts.checked
                                    ? opts.cssOn
                                    : opts.cssOff) +'"'
                          +     (title && title.length > 0
                                    ? ' title="'+ title +'"'
                                    : '')
                          +   '>&nbsp;</div>'
                          +'</span>');
        self.img      = self.element.find('div');

        // Insert the new element after the existing and remove the existing.
        self.element.insertAfter(self.$orig);

        /* Removing the original element will trigger a call to our destroy()
         * method.  Since we're not really destroying this widget, we need to
         * protect against pre-mature destruction with '_isInit'.
         */
        self._isInit = true;
            self.$orig.remove();
        self._isInit = false;

        // Create a new hidden input to represent the final value.
        self.$value = $('<input type="hidden" '
                    +               (id ? 'id="'+ id +'" '
                                        : '')
                    +          'name="'+ name +'" />');
        self.$value.attr('value', opts.checked);
        self.$value.insertBefore(self.element);


        if (self.$label && (self.$label.length > 0))
        {
            // We have a label for this field.
            if (opts.hideLabel === true)
                // Hide it.
                self.$label.hide();
            else
            {
                // Treat a click on the label as a click on the item.
                self.$label.click(function(e) {
                                self.element.trigger('click',[e]);
                                return stopEvent(e);
                });
            }
        }

        // Interaction events
        self.element.hover(function(e) { // Hover in
                        self.element.addClass(   'hover');
                        //return stopEvent(e)
                      },
                      function(e) { // Hover out
                        self.element.removeClass('hover');
                        //return stopEvent(e)}
                      })
               .click(function(e) {
                        self.toggle();
                        //return stopEvent(e);
               });
    },

    /************************
     * Private methods
     *
     */

    /************************
     * Public methods
     *
     */
    isChecked: function() {
        return this.options.checked;
    },
    isEnabled: function() {
        return this.options.enabled;
    },

    enable: function()
    {
        if (! this.options.enabled)
        {
            this.options.enabled = true;
            this.element.trigger('enable');
        }
    },

    disable: function()
    {
        if (this.options.enabled)
        {
            this.options.enabled = false;
            this.element.trigger('disable');
        }
    },

    toggle: function()
    {
        if (! this.options.enabled)
            return;

        if (this.options.checked)
            this.uncheck();
        else
            this.check();
    },

    check: function()
    {
        if (this.options.enabled && (! this.options.checked))
        {
            this.options.checked = true;

            this.$value.attr('value', this.options.checked);

            this.img.removeClass(this.options.cssOff)
                    .addClass(this.options.cssOn)
                    .attr('title', this.options.title + this.options.titleOn);

            this.element.trigger('check');
        }
    },

    uncheck: function()
    {
        if (this.options.enabled && this.options.checked)
        {
            this.options.checked = false;

            this.$value.attr('value', this.options.checked);

            this.img.removeClass(this.options.cssOn)
                    .addClass(this.options.cssOff)
                    .attr('title', this.options.title + this.options.titleOff);

            this.element.trigger('uncheck');
        }
    },

    destroy: function() {
        /* Since we replace the original element, destroy() will be invoked
         * during initialization.  When that happens, simply return.
         */
        if (this._isInit === true)
            return;

        this.$orig.insertAfter(this.element);

        if (this.$label)
            this.$label.show();

        this.$value.remove();
    }
});

$.extend($.ui.checkbox, {
    version:    '0.1.1',
    getter:     'isChecked isEnabled',
    defaults: {
        css:        'checkbox',             // General CSS class
        cssOn:      'on',                   // CSS class when    checked
        cssOff:     'off',                  // CSS class when un-checked
        titleOn:    'click to turn off',    // Title when    checked
        titleOff:   'click to turn on',     // Title when un-checked

        useElTitle: true,                   // Include the title of the source
                                            // element (or it's associated
                                            // label) in the title of this
                                            // checkbox.

        hideLabel:  false                   // Hide the associated label?  If
                                            // not, clicking on the title will
                                            // be the same as clicking on the
                                            // checkbox.
    }
});


})(jQuery);
