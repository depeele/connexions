/** @file
 *
 *  Provide a sprite-based checkbox.
 *
 */
(function($) {
    $.Checkbox = $.Checkbox || {};

    $.fn.Checkbox = function(options) {
        var config  = $.extend({}, $.Checkbox.defaults, options || {});

        return this.each(function() {
            var $el = $(this);

            if ($el.data('Checkbox'))
                return;

            $el.data('Checkbox', new $.Checkbox($el, config));
        });

    };

    /* Remove event bubbling. */
    function stopEvent(e)
    {
        if (!e) var e = window.event;
        e.cancelBubble = true;
        if (e.stopPropagation)  e.stopPropagation();

        return false;
    }

    $.Checkbox = Class.extend({
        /** @brief  Initialize a new instance.
         *  @param  el      The jQuery/DOM element
         *  @param  config  A configuration object:
         *                      css         General space-separated CSS
         *                                  class(es) for the checkbox
         *                                  [ 'checkbox' ];
         *                      cssOn       Space-separated CSS class(es)
         *                                  when checked
         *                                  [ 'on' ];
         *                      cssOff      Space-separated CSS class(es)
         *                                  when un-checked
         *                                  [ 'off' ];
         *                      titleOn     Title when checked
         *                                  [ 'click to turn off' ];
         *                      titleOff    Title when un-checked
         *                                  [ 'click to turn on' ];
         *
         *                      useElTitle  Include the title of the source
         *                                  element (or it's associated label)
         *                                  in the title of this checkbox (as a
         *                                  prefix to 'titleOn' or 'titleOff')
         *                                  [ true ];
         *
         *                      hideLabel   Hide the associated label?  If not,
         *                                  clicking on the title will be the
         *                                  same as clicking on the checkbox
         *                                  [ false ].
         */
        init: function(el, config)
        {
            var scope   = this;
            el          = (el.jquery ? el : $(el));

            scope.origEl  = el;
            scope.config  = config;
            scope.enabled = el.attr('disabled') ? false : true;
            scope.checked = el.attr('checked')  ? true  : false;
            scope.title   = '';

            // Try to locate the associated label
            var id      = el.attr('id');
            var name    = el.attr('name');
            var $label  = false;

            if (id)                 $label = $('label[for='+ id +']');
            if ((! $label) && name) $label = $('label[for='+ name +']');

            if (config.useElTitle === true)
            {
                scope.title += ': ';

                title = el.attr('title');
                if ( ((! scope.title) || (scope.title.length < 1)) &&
                     ($label.length > 0) )
                {
                    scope.title = $label.text();
                }
            }

            var title   = scope.title
                        + (scope.checked ? config.titleOn : config.titleOff);

            scope.el      = $( '<span class="checkbox">'
                              + '<div '
                              +    'class="'+ config.css
                              +      (scope.enabled ? ' '   : ' diabled ')
                              +      (scope.checked
                                        ? config.cssOn
                                        : config.cssOff) +'"'
                              +     (title && title.length > 0
                                        ? ' title="'+ title +'"'
                                        : '')
                              +   '>&nbsp;</div>'
                              +'</div>');
            scope.img      = scope.el.find('div');

            scope.el.hover(function(e) { // Hover in
                            scope.el.addClass(   'hover');
                            return stopEvent(e)
                          },
                          function(e) { // Hover out
                            scope.el.removeClass('hover');
                            return stopEvent(e)}
                          )
                   .click(function(e) {
                            scope.toggle();
                            return stopEvent(e);
                   });

            // Hide the original and show the new
            el.hide().after(scope.el);

            // Handle any label associated with this field
            if ($label && ($label.length > 0))
            {
                if (config.hideLabel === true)
                    $label.hide();
                else
                {
                    // Treat a click on the label as a click on the item.
                    $label.click(function(e) {
                                    scope.el.trigger('click',[e]);
                                    return stopEvent(e);
                    });
                }
            }
        },

        enable: function()
        {
            if (! this.enabled)
            {
                this.enabled = true;
                this.el.trigger('enable');
            }
        },

        disable: function()
        {
            if (this.enabled)
            {
                this.enabled = false;
                this.el.trigger('disable');
            }
        },

        toggle: function()
        {
            if (! this.enabled)
                return;

            if (this.checked)
                this.uncheck();
            else
                this.check();
        },

        check: function()
        {
            if (this.enabled && (! this.checked))
            {
                this.checked = true;
                this.origEl.attr('checked', true);
                this.img.removeClass(this.config.cssOff)
                        .addClass(this.config.cssOn)
                        .attr('title', this.title + this.config.titleOn);

                this.el.trigger('check');
            }
        },

        uncheck: function()
        {
            if (this.enabled && this.checked)
            {
                this.checked = false;
                this.origEl.removeAttr('checked');
                this.img.removeClass(this.config.cssOn)
                        .addClass(this.config.cssOff)
                        .attr('title', this.title + this.config.titleOff);

                this.el.trigger('uncheck');
            }
        }
    });

    $.Checkbox.defaults = {
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
    };

 })(jQuery);
