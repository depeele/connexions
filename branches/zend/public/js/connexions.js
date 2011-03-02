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
     * Simple password validation.
     *
     */

    /** @brief  Given two ui.input widgets and the one which cause the
     *          validation routine to trigger, area, check if the passwords are
     *          equivalent and more than 1 and mark BOTH passwords either valid
     *          or invalid.
     *  @param  $active     The ui.input widget (either $pass1 or $pass2) that
     *                      triggered the validation check.
     *  @param  $pass1      The ui.input widget representing password #1.
     *  @param  $pass2      The ui.input widget representing password #2.
     *
     *  @return
     */
    $.validatePasswords = function($active, $pass1, $pass2) {
        var pass1       = $pass1.val();
        var pass2       = $pass2.val();
        var res         = true;

        if ((pass1.length < 1) || (pass2.length < 1))
        {
            // Neither valid nor ivnalid
            res = undefined;

            // Also clear the validation status for the other field
            if ($active[0] === $pass1[0])
                $pass2.input('valid');  //, undefined);
            else
                $pass1.input('valid');  //, undefined);
        }
        else if (pass1 !== pass2)
        {
            // Invalid -- with message
            res = 'Passwords do not match.';

            // Only report errors on 'password2'
            if ($active[0] === $pass1[0])
            {
                $pass2.input('valid', res);
                res = undefined;
            }
            else
            {
                /* But we still  want to clear the validation status for
                 * password1
                 */
                $pass1.input('valid');  //, undefined);
            }
        }
        else
        {
            // Also report success for the other field.
            if ($active[0] === $pass1[0])
                $pass2.input('valid', true);
            else
                $pass1.input('valid', true);
        }

        return res;
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
                                    .css({width:    $el.outerWidth(),
                                          height:   $el.outerHeight(),
                                          'z-index':zIndex});

            if ($spin.length > 0)
            {
                var url = $spin.attr('src');
                $spin.attr('src', url.replace('.gif', '-spinner.gif') );
            }

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

            if ($spin.length > 0)
            {
                var url = $spin.attr('src');
                $spin.attr('src', url.replace('-spinner.gif', '.gif') );
            }
        });
    };

 }(jQuery));
