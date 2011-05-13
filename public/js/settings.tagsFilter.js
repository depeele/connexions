/** @file
 *
 *  Javascript interface/wrapper for the presentation of a configurable pane
 *  which contains a bookmark list.
 *
 *  This is class extends connexions.pane to include unobtrusive activation of
 *  any contained, pre-rendered ul.cloud generated via
 *      view/scripts/settings/main-tags-manage.phtml
 *      - conversion of the input area to either a ui.input or ui.autocomplete
 *        instance;
 *
 *  The pre-rendered HTML must have a form similar to:
 *   <div class='tagFilter ui-form'>
 *    <input type='text' />
 *    <button name='submit'>filter</button>
 *    <button name='reset' >clear</button>
 *   </div>
 *   <div class='pane'>         // As a connexions.tagsManagePane()
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

$.widget("settings.tagsFilter", {
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

        separator:  ',',    // The term separator
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

        /********************************
         * Locate the pieces
         *
         */
        self.$input    = self.element.find(':text');
        self.$submit   = self.element.find(':button[name=submit]');
        self.$reset    = self.element.find(':button[name=reset]');
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
                separator:  ',',
                source:     function(request, response) {
                    return self._autocomplete(request,response);
                },
                minLength:  opts.minLength
            });
        }
        self.$submit.button({disabled:true});
        self.$reset.button()
                   .hide();

        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self    = this;
        var opts    = self.options;

        self.$input.bind('validation_change.tagsFilter '
                         + 'autocompletesearch.tagsFilter',
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
        self.$input.bind('keydown.tagsFilter', function(e) {
            if (e.keyCode === 13)   // return
            {
                self.$input.blur();
                self.$submit.click();
            }
        });

        self.$submit.bind('click.tagsFilter', function(e) {
            // Notify the pane of the new filter, and request a 'reload'
            self.$pane.tagsManagePane('filter', self.$input.val());
            self.$pane.tagsManagePane('reload', function() {
                self.$pane = self.element.siblings('.pane');
            });
            self.$reset.show();
        });

        self.$reset.bind('click.tagsFilter', function(e) {
            // Notify the pane of the new filter, and request a 'reload'
            self.$input.val('');
            self.$pane.tagsManagePane('removeFilter');
            self.$pane.tagsManagePane('reload', function() {
                self.$pane = self.element.siblings('.pane');
            });
            self.$reset.hide();
        });
    },

    _autocomplete: function(request, response) {
        var self    = this;
        var opts    = self.options;
                      // { term: %str%, limit: %num%, apiKey: %str% }
        var params  = opts.jsonRpc.params;
        
        params.term  = self.$input.autocomplete('option', 'term');

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.autocompleteMyTags', params, {
            success:    function(ret, txtStatus, req){
                if (ret.error !== null)
                {
                    self.element.trigger('error', [txtStatus, req, ret.error]);
                    return;
                }

                response(
                    $.map(ret.result,
                          function(item) {
                            var str = item.tag.replace(
                                                params.term,
                                                '<b>'+ params.term +'</b>' );
                            return {
                                label:   '<span class="name">'
                                       +  str
                                       + '</span>'
                                       +' <span class="count">'
                                       +  item.userItemCount
                                       + '</span>',
                                value: item.tag
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
        self.$reset.button('destroy');

        // Unbind events
        self.$input.unbind('.tagsFilter');
        self.$submit.unbind('.tagsFilter');
        self.$reset.unbind('.tagsFilter');
    }
});


}(jQuery));
