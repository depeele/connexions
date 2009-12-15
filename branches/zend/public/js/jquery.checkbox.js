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
         *                      css         General CSS classes for the
         *                                  checkbox.
         *                      classOn     CSS class to use when the state is
         *                                  on/checked.
         *
         *                      classOff    CSS class to use when the state is
         *                                  off/unchecked.
         */
        init: function(el, config)
        {
            var scope   = this;
            el          = (el.jquery ? el : $(el));

            scope.origEl  = el;
            scope.config  = config;
            scope.enabled = el.attr('disabled') ? false : true;
            scope.checked = el.attr('checked')  ? true  : false;

            var title   = el.attr('title');

            scope.el      = $( '<span class="checkbox">'
                              + '<img src="'+ config.empty +'" '
                              +    'class="'+ config.css
                              +      (scope.enabled ? ' '   : ' diabled ')
                              +      (scope.checked
                                        ? config.cssOn
                                        : config.cssOff) +'"'
                              +     (title && title.length > 0
                                        ? ' title="'+ title +'"'
                                        : '')
                              +   ' />'
                              +'</div>');
            scope.img      = scope.el.find('img');

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
            var id      = el.attr('id');
            var name    = el.attr('name');
            var $label  = false;

            if (id)                 $label = $('label[for='+ id +']');
            if ((! $label) && name) $label = $('label[for='+ name +']');
            if ($label)
            {
                // Treat a click on the label as a click on the item.
                $label.click(function(e) {
                                scope.el.trigger('click',[e]);
                                return stopEvent(e);
                });
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
                        .addClass(this.config.cssOn);

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
                        .addClass(this.config.cssOff);

                this.el.trigger('uncheck');
            }
        }
    });

    $.Checkbox.defaults = {
        empty:  '/images/empty.gif',
        css:    'checkbox',
        cssOn:  'on',
        cssOff: 'off'
    };

 })(jQuery);
