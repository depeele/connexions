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
     * JSON-RPC helper.
     *
     */

    var _jsonRpcId  = 0;

    /** @brief  Perform a JSON-RPC call.
     *  @param  def     The JSON-RPC description object:
     *                      { version:, target:, transport: }
     *  @param  method  The desired RPC method string;
     *  @param  params  An object containing the RPC parameters to pass;
     *  @param  options $.ajax-compatible options object;
     */
    $.jsonRpc = function(def, method, params, options) {
        var rpc = {
            version:    def.version,
            id:         _jsonRpcId++,
            method:     method,
            params:     params
        };

        options = $.extend({}, options, {
                            url:        def.target,
                            type:       def.transport,
                            dataType:   'json',
                            data:       JSON.stringify(rpc)
                           });

        $.ajax(options);
    };

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
