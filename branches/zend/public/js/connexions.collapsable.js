/** @file
 *
 *  Javascript interface/wrapper for the presentation of a collapsable area.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered area.
 *
 *  The pre-rendered HTML must have a form similar to:
 *      < dom container, 'element' for this class (e.g. <div>, <ul>, <li>) >
 *        <h3 class='toggle'><span>Area Title</span></h3>
 *        <div > ... </div>
 *      </ dom container >
 *
 *         <a href='/settings/account'
 *            data-panel.tabs='#account'
 *            data-load.tabs='/settings?format=partial&section=account'>
 *           <span>Account</span>
 *         </a>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($) {

var collapsableId   = 0;

$.widget("connexions.collapsable", {
    version: "0.0.1",
    options: {
        // Defaults
        cache:          true,
        ajaxOptions:    null,
        cookie:         null,
        idPrefix:       'connexions-collapsable-',
        panelTemplate:  '<div></div>',
        spinner:        "<em>Loading&#8230;</em>"
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'collapse', 'expand', 'toggle'
     */
    _init: function() {
        var self    = this;
        var opts    = self.options;

        self.$toggle  = self.element.find('.toggle:first');
        self.$a       = self.$toggle.find('a:first');

        if (self.$a.length > 0)
        {
            var href    = self.$a.attr('href');
            if (! href.match(/^#.+/))
            {
                // remote tab -- save the original URL
                self.$a.data('href.collapsable', href);
                var loadUrl = self.$a.data('load.collapsable');
                if (loadUrl === undefined)
                {
                    self.$a.data('load.collapsable', href.replace(/#.*$/, ''));
                }


                var contentId   = self.$a.data('cache.collapsable');
                if (contentId === undefined)
                {
                    contentId = self.$a.data('content.collapsable');
                }

                if (contentId === undefined)
                {
                    // Generate a contentId
                    contentId   = ((self.$a.title &&
                                    self.$a.title.replace(/\s/g, '_')
                                       .replace(/[^A-Za-z0-9\-_:\.]/g, '')) ||
                                   opts.idPrefix + (++collapsableId));
                    self.$a.attr('href', '#'+ contentId);
                }

                self.$content = $('#'+ contentId);
                if (self.$content.length < 1)
                {
                    self.$content = $(opts.panelTemplate)
                                        .attr('id', contentId)
                                        .addClass('ui-corner-bottom')
                                        .insertAfter(self.$toggle);
                    self.$content.data('destroy.collapsable', true);
                }
            }
        }

        if ( (! self.$content) || (self.$content.length < 1) )
        {
            self.$content = self.$toggle.next();
        }

        // Add styling to the toggle and content
        self.$toggle.addClass('ui-corner-top');
        self.$content.addClass('content ui-corner-bottom');

        // Add an open/close indicator
        self.$toggle.prepend( '<div class="ui-icon">&nbsp;</div>');
        self.$indicator = self.$toggle.find('.ui-icon:first');

        if (self.$toggle.hasClass('collapsed'))
        {
            // Change the indicator to "closed" and hide the content
            self.$indicator.addClass('ui-icon-triangle-1-e');
            self.$content.hide();
        }
        else
        {
            // Change the indicator to "open" and hide the content
            self.$indicator.addClass('ui-icon-triangle-1-s');
            self.$content.show();

            if (! self.$toggle.hasClass('expanded'))
            {
                self.$toggle.addClass('expanded');
            }

            self._load();
        }

        self._bindEvents();
    },

    _bindEvents: function() {
        var self    = this;

        self.$toggle.bind('click.collapsable', function(e) {
            e.preventDefault();

            if (self.$content.is(":hidden"))
            {
                // Show the content / open
                self.$toggle.removeClass('collapsed')
                            .addClass(   'expanded');
                self.$indicator.removeClass('ui-icon-triangle-1-e')
                               .addClass(   'ui-icon-triangle-1-s');
                self.$content.slideDown();
                    
                self.element.trigger('expand');
                self._load();
            }
            else
            {
                // Hide the content / close
                self.$toggle.removeClass('expanded')
                            .addClass(   'collapsed');
                self.$indicator.removeClass('ui-icon-triangle-1-s')
                               .addClass(   'ui-icon-triangle-1-e');
                self.$content.slideUp();

                self.element.trigger('collapse');
            }

            // Trigger 'toggle'
            self.element.trigger('toggle');
        });
    },

    _load: function() {
        var self    = this;
        var opts    = self.options;
        var url     = self.$a.data('load.collapsable');

        self._abort();

        if ((! url) || self.$a.data('cache.collapsable'))
        {
            return;
        }

        $.log('connexions.collapsable: load url[ '+ url +' ]');

        // Load remote content.
        self.xhr = $.ajax($.extend({}, opts.ajaxOptions, {
            url:     url,
            beforeSend: function(xhr, textStatus) {
                self.element.addClass('ui-state-processing');
                if ( opts.spinner )
                {
                    var $span = self.$a.find('span:first');
                    $span.data( "label.collapsable", $span.html() )
                                    .html( opts.spinner );
                }

                if (opts.ajaxOptions &&
                    $.isFunction(opts.ajaxOptions.beforeSend))
                {
                    opts.ajaxOptions.beforeSend.call(self.element,
                                                     xhr, textStatus);
                }
            },
            complete: function(xhr, textStatus) {
                if (opts.ajaxOptions &&
                    $.isFunction(opts.ajaxOptions.complete))
                {
                    opts.ajaxOptions.complete.call(self.element,
                                                   xhr, textStatus);
                }

                if ( opts.spinner )
                {
                    var $span = self.$a.find('span:first');
                    $span.html( $span.data( "label.collapsable" ) )
                         .removeData( 'label.collapsable' );
                }

                self.element.removeClass('ui-state-processing');
            },
            success: function(res, stat) {
                self.$content.html(res);

                if (opts.cache)
                {
                    self.$a.data('cache.collapsable', true);
                }

                self._trigger('load', null, self.element);

                try {
                    opts.ajaxOptions.success(res, stat);
                }
                catch (e) {}
            },
            error:   function(xhr, stat, err) {
                self.$content.html(  "<div class='error'>"
                                   +  "Cannot load: "
                                   +  xhr.statusText
                                   + "</div>");

                self._trigger('load', null, self.element);

                try {
                    opts.ajaxOptions.error(xhr, stat, self.element, self.$a);
                }
                catch (e) {}
            }
        }));

        return this;
    },

    _abort: function() {
        var self    = this;

        if (self.xhr)
        {
            self.xhr.abort();
            delete self.xhr;
        }

        return self;
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;
        var opts    = self.options;

        // Restore the href and remove data.
        var href    = self.$a.data('href.collapsable');
        if (href)
        {
            self.$a.attr('href', href);
        }
        $.each(['href', 'load', 'cache'], function(i, prefix) {
            self.$a.removeData(prefix +'.collapsable');
        });

        if (self.$content.data('destroy.collapsable'))
        {
            self.$content.remove();
        }
        else
        {
            self.$content.removeClass('ui-corner-bottom content');
        }

        // Remove styling
        self.$toggle.removeClass('ui-corner-top');
        self.$toggle.removeClass('collapsed,expanded');

        // Remove event bindings
        self.$toggle.unbind('.collapsable');

        // Ensure that the content is visible
        self.$content.show();
    }
});


}(jQuery));



