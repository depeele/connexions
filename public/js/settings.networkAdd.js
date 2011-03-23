/** @file
 *
 *  Javascript interface/wrapper for the autocompletion of user names as well
 *  as adding a new user to the viewing user's network.
 *
 *  The pre-rendered HTML must have a form similar to:
 *   <div class='networkAdd ui-form'>
 *    <input type='text' />
 *    <button name='submit'>add</button>
 *   </div>
 *   <div class='pane'>         // As a connexions.pane instance
 *    --- item presentation ---
 *   </div>
 *
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.input.js  OR ui.autocomplete.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, setTimeout:false, clearTimeout:false, document:false */
(function($) {

$.widget("settings.networkAdd", {
    version: "0.0.1",
    options: {
        // Defaults
        namespace:  '',
        pane:       null,   // The target pane.

        /* General Json-RPC information:
         *  {version:   Json-RPC version,
         *   target:    URL of the Json-RPC endpoint,
         *   transport: 'POST' | 'GET'
         *  }
         *
         * If not provided, 'version', 'target', and 'transport' are
         * initialized from:
         *      $.registry('api').jsonRpc
         *
         * (which is initialized from
         *      application/configs/application.ini:api
         *  via
         *      application/layout/header.phtml
         *
         */
        jsonRpc:    null,

        /* If the JSON-RPC method is GET, the apiKey for the authenticated user
         * is required for any methods that modify data.
         */
        apiKey:     null,

        minLength:  2       // Minimum term length
    },

    /** @brief  Initialize a new instance.
     */
    _create: function() {
        var self        = this;
        var opts        = self.options;

        /********************************
         * Initialize jsonRpc
         *
         */
        if ($.isFunction($.registry))
        {
            var api = $.registry('api');
            if (api && api.jsonRpc)
            {
                opts.jsonRpc = $.extend({}, api.jsonRpc, opts.jsonRpc);
            }
        }
        if (opts.jsonRpc.params === undefined)
        {
            opts.jsonRpc.params = {};
        }
        if (opts.apiKey !== null)
        {
            opts.jsonRpc.params.apiKey = opts.apiKey;
        }

        if (opts.jsonRpc.params.limit === undefined)
        {
            opts.jsonRpc.params.limit = 25;
        }

        /********************************
         * Locate the pieces
         *
         */
        self.$input    = self.element.find(':text');
        self.$submit   = self.element.find(':button[name=submit]');
        self.$pane     = self.element.siblings('.pane');

        /********************************
         * Instantiate our sub-widgets
         *
         */
        self.$input.input();
        if (opts.jsonRpc !== null)
        {
            // Setup autocompletion via Json-RPC
            self.$input.autocomplete({
                source:     function(request, response) {
                    return self._autocomplete(request,response);
                },
                minLength:  opts.minLength
            });
        }
        self.$submit.button({disabled:true});

        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self    = this;
        var opts    = self.options;

        self.$input.bind('validation_change.networkAdd '
                         + 'autocompletesearch.networkAdd',
                         function(e) {
            var val = self.$input.val();
            if (val.length >= opts.minLength)
            {
                self.$submit.button('enable');
            }
            else
            {
                self.$submit.button('disable');
            }
        });
        self.$input.bind('keydown.networkAdd', function(e) {
            if (e.keyCode === 13)   // return
            {
                self.$input.blur();
                self.$submit.click();
            }
        });

        self.$submit.bind('click.networkAdd', function(e) {
            // Attempt to add the identified user
            var user    = self.$input.val();

            // ... and then request a 'reload' of our presentation pane
            self.$pane.pane('reload', function() {
                self.$pane = self.element.siblings('.pane');
            });
        });
    },

    _autocomplete: function(request, response) {
        var self    = this;
        var opts    = self.options;
                      // { term: %str%, limit: %num%, apiKey: %str% }
        var params  = opts.jsonRpc.params;
        
        params.term  = self.$input.autocomplete('option', 'term');

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.autocomplete', params, {
            success:    function(ret, txtStatus, req){
                if (ret.error !== null)
                {
                    self.element.trigger('error', [txtStatus, req, ret.error]);
                    return;
                }

                var re  = new RegExp(params.term, 'gi');
                response(
                    $.map(ret.result,
                          function(user) {
                            var str = user.name
                                    + ', '+ user.fullName
                                    + ' ('+ user.email +')';
                            str = str.replace(re, '<b>'+ params.term +'</b>' );

                            return {
                                label:   '<span class="name">'
                                       +  str
                                       + '</span>',
                                value: user.name
                            };
                          }));
                self.element.trigger('success', [ret, txtStatus, req]);
            },
            error:      function(req, txtStatus, e) {
                self.element.trigger('error', [txtStatus, req]);
            }
        });
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;

        // Destroy widgets
        if (opts.jsonRpc !== null)
        {
            self.$input.autocomplete('destroy');
        }
        self.$input.input('destroy');
        self.$submit.button('destroy');

        // Unbind events
        self.$input.unbind('.networkAdd');
        self.$submit.unbind('.networkAdd');
    }
});


}(jQuery));

