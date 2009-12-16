/** @file
 *
 *  Provide global Javascript functionality for Connexions.
 *
 */
;(function($) {
    /* IE6 Background Image Fix
     *  Thanks to http://www.visualjquery.com/rating/rating_redux.html
     */
    if ($.browser.msie)
    {
        try { document.execCommand("BackgroundImageCache", false, true)}
        catch(e) { };
    }

    /*******************************************************************
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
                    return;

                scriptLoaded(script, url);
            };
            script.src                = url;

            // Mark this script as not-yet-loaded
            $.includeScripts[url]     = false;
            $.includeCallbacks[url] = [];
            if (typeof onload === 'function')
                $.includeCallbacks[url].push(onload);

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
                    return (isReady = false); // Stop traversal
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

    function scriptLoaded(script, url)
    {
        $.includeScripts[url] = true;

        // Invoke all callbacks that we have queued for this script
        $.each($.includeCallbacks[url], function(idex, onload) {
            onload.call(script);
        });
    }
    }
    /*******************************************************************/

 })(jQuery);
