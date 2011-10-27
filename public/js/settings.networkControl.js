/** @file
 *
 *  Javascript interface/wrapper for the autocompletion of user names as well
 *  as adding a new user to the viewing user's network.
 *
 *  The pre-rendered HTML must have a form similar to:
 *   <div class='networkControl'>
 *    <div class='networkAdd ui-form'>
 *     <input type='text' />
 *     <button name='submit'>add</button>
 *    </div>
 *    <div class='networkVisibility'>
 *     <label for='visibility'>Visibility</label>
 *     <div class='options'>
 *       <input id='visibility-private' type='radio' name='visibility'
 *              value='private' />
 *       <label for='visibility-private'>Private</label>
 *       <input id='visibility-public' type='radio' name='visibility'
 *              value='public' />
 *       <label for='visibility-public'>Public</label>
 *       <input id='visibility-group' type='radio' name='visibility'
 *              value='group' />
 *       <label for='visibility-group'>These People</label>
 *     </div>
 *    </div>
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

$.widget("settings.networkControl", {
    version: "0.0.2",
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

        visibility: null,

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
        self.$input      = self.element.find('.networkAdd :text');
        self.$submit     = self.element.find(':button[name=submit]');
        self.$visibility = self.element.find('.networkVisibility .options');
        self.$parent     = self.element.parent();
        self.$pane       = self.element.siblings('.pane');

        if (opts.visibility !== null)
        {
            self.$visibility.find(':radio[value='+ opts.visibility +']')
                    .attr('checked', true);
        }

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
        self.$visibility.buttonset();

        opts.visibility = self.$visibility.find(':radio:checked').val();

        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self    = this;
        var opts    = self.options;

        self.$input.bind('validation_change.networkControl '
                         + 'autocompletesearch.networkControl',
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
        self.$input.bind('keypress.networkControl', function(e) {
            if (e.keyCode === 13)   // return
            {
                self.$input.blur();
                self.$submit.click();
            }
        });

        self.$submit.bind('click.networkControl', function(e) {
            self._add_users();
        });

        self.$visibility.bind('change.networkControl', function(e) {
            self._changeVisibility( $(e.target).val() );
        });

        self.$parent.delegate('.users', 'itemDeleted', function() {
            // On item delete, reload the pane
            self.reload();
        });
    },

    /** @brief  Change the visibility to the given value.
     *  @param  val     The (new) visibility value.
     */
    _changeVisibility: function(val) {
        var self    = this;
        var opts    = self.options;

        if (opts.visibility === val)    { return; }

        // { term: %str%, limit: %num%, apiKey: %str% }
        var params  = opts.jsonRpc.params;
        params.visibility = val;

        $.jsonRpc(opts.jsonRpc, 'user.changeNetworkVisibility', params, {
            success:    function(ret, txtStatus, req) {
                if (ret.error !== null)
                {
                    self.$visibility
                        .find(':radio[value='+ opts.visibility +']')
                            .click();

                    $.notify({title: 'Cannot change visibility',
                              text:  ret.error.message});
                    self.element.trigger('error', [txtStatus, req, ret.error]);
                    return;
                }

                opts.visibility = val;
            },
            error:      function(req, txtStatus, e) {
                $.notify({title: 'Cannot change visiblity',
                          text:  txtStatus});
                self.element.trigger('error', [txtStatus, req]);
            }
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
                    $.notify({title: 'Autocompletion error',
                              text:  ret.error.message});
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
                $.notify({title: 'Autocompletion error',
                          text:  txtStatus});
                self.element.trigger('error', [txtStatus, req]);
            }
        });
    },

    _add_users: function() {
        var self    = this;
        var opts    = self.options;
        var users   = self.$input.val();
        var params  = opts.jsonRpc.params;
        
        params.users  = self.$input.val().replace(/\s*,\s*$/, '');

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.addToNetwork', params, {
            success:    function(data, txtStatus, req){
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title:  'User addition failed',
                        text:   '<p class="error">'
                              +  (data ? data.error.message : '')
                              + '</p>'
                    });
                    return;
                }

                // Consolidate all errors and successes
                var errors      = [];
                var successes   = [];
                $.each(data.result, function( user, val ) {
                    if (val !== true)
                    {
                        errors.push( user +': '+ val );
                    }
                    else
                    {
                        successes.push( user );
                    }
                });

                if (errors.length > 0)
                {
                    // Report any deletion failures
                    $.notify({
                        title: 'User addition failed',
                        text:  $.map(errors, function(val, idex) {
                                    return '<p class="error">'+ val +'</p>';
                               }).join('')
                    });
                }

                if (successes.length > 0)
                {
                    // Report any add successes
                    $.notify({
                        title: 'User'+ (successes.length > 1 ? 's' :'')
                                    +' added',
                        text:  successes.join(', ')
                    });

                    self.$input.val('');

                    // Ask the pane to reload to present the new users
                    self.reload();
                }
            },
            error:      function(req, txtStatus, e) {
                $.notify({
                    title: 'User addition failed',
                    text:  '<p class="error">'
                         +   txtStatus
                         + '</p>'
                });
            }
        });
    },

    /************************
     * Public methods
     *
     */
    reload: function() {
        var self    = this;
        var opts    = self.options;

        // Request a reload of our associated presentation pane.
        self.$pane.itemsPane('reload', function() {
            self.$pane = self.element.siblings('.pane');
        });
    },

    destroy: function() {
        var self    = this;

        // Destroy widgets
        if (opts.jsonRpc !== null)
        {
            self.$input.autocomplete('destroy');
        }
        self.$input.input('destroy');
        self.$submit.button('destroy');
        self.$visibility.buttonset('destroy');

        // Unbind events
        self.$input.unbind('.networkControl');
        self.$submit.unbind('.networkControl');
        self.$visibility.unbind('.networkControl');
        self.$parent.undelegate('.users', '.networkControl');
    }
});


}(jQuery));

