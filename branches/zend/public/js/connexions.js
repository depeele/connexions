/** @file
 *
 *  Provide global Javascript functionality for Connexions.
 *
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false, document:false, setTimeout:false */
(function($) {
    function init_log()
    {
        $.log = function(fmt) {
            if ((window.console !== undefined) &&
                $.isFunction(window.console.log))
            {
                var msg = fmt;
                for (var idex = 1; idex < arguments.length; idex++)
                {
                    msg = msg.replace(/%s/, arguments[idex]);
                }
                window.console.log(msg);
            }
        };

        /*
        $.log = ((window.console !== undefined) &&
                 $.isFunction(window.console.log)
                    ?  window.console.log
                    : function() {});
        */

        $.log("Logging enabled");
    }

    if ( (window.console === undefined) || (! $.isFunction(window.console.log)))
    {
        $(document).ready(init_log);
    }
    else
    {
        init_log();
    }

    /* IE6 Background Image Fix
     *  Thanks to http://www.visualjquery.com/rating/rating_redux.html
     */
    if ($.browser.msie)
    {
        try { document.execCommand("BackgroundImageCache", false, true); }
        catch(e) { }
    }

    /*************************************************************************
     * Dynamic script inclusion -- Based upon jquery-include.js
     *
     * Note: This modifies jQuery.ready to wait for any scripts that have been
     *       queued for dynamic loading.
     */

    if (false) {
    /* Overload jQuery's onDomReady
     *
     * Note: This MUST be BEFORE we redefine $.ready() so we can remove the
     *       current jQuery.ready event listener.
     */
    if ($.browser.mozilla || $.browser.opera)
    {
        document.removeEventListener('DOMContentLoaded', $.ready, false);
        document.addEventListener('DOMContentLoaded', function(){ $.ready(); },
                                  false);
    }
    $.event.remove(window, 'load', $.ready);
    $.event.add(window, 'load', function(){ $.ready(); });

    function scriptLoaded(script, url)
    {
        $.includeScripts[url] = true;

        // Invoke all callbacks that we have queued for this script
        $.each($.includeCallbacks[url], function(idex, onload) {
            onload.call(script);
        });
    }

    $.extend({
        includeScripts:     {}, // by url: false | $(script)
        includeCallbacks:   {}, // by url: array( onload callbacks )
        includeTimer:       null,
        include: function(url, onload) {
            if ($.includeScripts[url] !== undefined)
            {
                if (typeof onload === 'function')
                {
                    if ($.includeScripts[url] !== false)
                    {
                        // Already loaded, invoke the callback immediately
                        onload($.includeScripts[url]);
                    }
                    else
                    {
                        // Not yet loaded, push the callback on our list
                        $.includeCallbacks[url].push(onload);
                    }
                }
                return;
            }

            var script                = document.createElement('script');
            script.type               = 'text/javascript';
            script.onload             = function() {
                scriptLoaded(script, url);
            };
            script.onreadystatechange = function() {
                if ( (script.readyState !== 'complete') &&
                     (script.readyState !== 'loaded') )
                {
                    return;
                }

                scriptLoaded(script, url);
            };
            script.src                = url;

            // Mark this script as not-yet-loaded
            $.includeScripts[url]     = false;
            $.includeCallbacks[url] = [];
            if (typeof onload === 'function')
            {
                $.includeCallbacks[url].push(onload);
            }

            // Put the script into the DOM -- loading begins now
            document.getElementsByTagName('head')[0].appendChild(script);
        },

        /* Replace jQuery.ready to wait for included scripts to be loaded */
        _ready: $.ready,
        ready: function() {
            var isReady = true;

            // See if all included scripts have loaded
            $.each($.includeScripts, function(url, state) {
                if (state === false)
                {
                    return (isReady = false); // Stop traversal
                }
            });

            if (isReady)
            {
                /* All included scripts have loaded, invoke the original
                 * jQuery.ready()
                 */
                $._ready.apply($, arguments);
            }
            else
            {
                // NOT all included script are loaded, wait a bit...
                setTimeout($.ready, 10);
            }
        }
    });

    }
    /*************************************************************************/

    /*************************************************************************
     * Overlay any element.
     *
     */
    $.fn.mask = function() {
        return this.each(function() {
            var $spin       = $('#pageHeader h1 a img');
            var $el         = $(this);
            var zIndex      = $el.css('z-index');
            if (zIndex === 'auto')
            {
                zIndex = 99999;
            }
            else
            {
                zIndex++;
            }

            var $overlay    = $('<div></div>')
                                    .addClass('ui-widget-overlay')
                                    .appendTo($el)
                                    .css({width:    $el.width(),
                                          height:   $el.height(),
                                          'z-index':zIndex});

            var url = $spin.attr('src');
            $spin.attr('src', url.replace('.gif', '-spinner.gif') );

            if ($.fn.bgiframe)
            {
                $overlay.bgiframe();
            }
        });
    };

    $.fn.unmask = function() {
        return this.each(function() {
            var $spin       = $('#pageHeader h1 a img');
            var $el         = $(this);
            var $overlay    = $el.find('.ui-widget-overlay');

            $overlay.remove();

            var url = $spin.attr('src');
            $spin.attr('src', url.replace('-spinner.gif', '.gif') );
        });
    };

 }(jQuery));
