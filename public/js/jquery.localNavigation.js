/** @file
 *
 *  A local navigation helper for sections that use local urls to identify and
 *  navigate between collapsable portions of a single page/area (e.g. help).
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.autocomplete.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, document:false */
(function($) {

    /** @brief  If the browser supports 'window.history.pushState', establish
     *          local navigation so the page doesn't have to be completely
     *          reloaded for local links between sections.
     *  @param  $body   The DOM element containing the target anchor elements
     *                  to be handled for local navigation;
     *  @param  url     The base URL that will be handled.
     */
    function setup_localNavigation($body, url)
    {
        if (! window.history.pushState)
        {
            /* HTML5 pushState/popState is NOT supported.  Local navigation
             * cannot be implemented.
             */
            return;
        }

        $body.data('localNavigation.url', url);

        // The current local history stack
        var localHistory    = [];
        $(window).bind('popstate', function(e) {
            if (localHistory.length < 1)
            {
                // No local history to use -- let the browser handle this
                return;
            }

            var state   = localHistory.pop();
            var $a      = state.context;
            var $el     = $a.parents('.collapsable:first');

            // Scroll back to the previous element
            $.scrollTo( $el, {
                duration:   800,
                onAfter:    function() {
                    $el.effect('highlight', null, 2000);
                }
            });
        });

        // Bind clicks for any local href
        $body.delegate('a[href^="'+ url +'"]', 'click', function(e) {
            var a       = this;
            var $a      = $(a);
            var href    = $a.attr('href');

            /* Doesn't work on Chrome if 'state' is non-null
            var state   = { context: a };
            var title   = $('head title').text();
            window.history.pushState(state, title, href);
            // */
            localHistory.push({ context: $a });
            window.history.pushState(null, null, href);

            href = '#' + href.replace(url +'/', '')
                             .replace(/\//g, '_')
                             .toLowerCase()

            var $el = $( href );
            if ($el.length < 1)
            {
                // Don't stop this one since we don't know where it goes...
                return;
            }

            // Ensure that all collapsable parents are expanded
            $el.parents('.collapsable').trigger('expand');
            $el.trigger('expand');

            // Give the target item time to become visible
            var timer   = window.setInterval(function() {
                if (! $el.is(':visible'))
                {
                    return;
                }

                window.clearInterval(timer);
                $.scrollTo( $el, {
                    duration:   800,
                    onAfter:    function() {
                        $el.parent().effect('highlight', null, 2000);
                    }
                });
            }, 250);

            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    }

$.fn.localNavigation = function(url) {
    this.each(function() {
        setup_localNavigation($(this), url);
    });

    // Return this for a fluent interface
    return this;
};

 }(jQuery));
