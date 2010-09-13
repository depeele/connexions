/** @file
 *
 *  Javascript interface/wrapper for the presentation of an item scope
 *  display/input area.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered area generate by View_Helper_HtmlItemScope:
 *      - conversion of the input area to either a ui.input or ui.autocomplete
 *        instance;
 *
 *  The pre-rendered HTML must have a form similar to:
 *      <form class='itemScope'>
 *        <input type='hidden' name='scopeCurrent' ... />
 *        <ul>
 *          <li class='root'>
 *            <a href='%url with no items%'> %Root Label% </a>
 *          </li>
 *
 *          <!-- For each item currently defining the scope -->
 *          <li class='scopeItem deletable'>
 *            <a href='%url with item%'> %Scope Label% </a>
 *            <a href='%url w/o  item%' class='delete'>x</a>
 *          </li>
 *
 *          <li class='scopeEntry'>
 *            <input name=' %inputName% ' value=' %inputLabel ' /> 
 *            <button type='submit'>&gt;</button>
 *          </li>
 *
 *          <li class='itemCount'> %item Count% </li>
 *        </ul>
 *      </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.input.js  OR ui.autocomplete.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false, document:false */
(function($){

$.widget("connexions.itemScope", {
    options: {
        namespace:          '',     // Cookie/parameter namespace

        /* General Json-RPC information:
         *  {version:   Json-RPC version,
         *   target:    URL of the Json-RPC endpoint,
         *   transport: 'POST' | 'GET'
         *   method:    RPC method name,
         *   params:    {
         *      key/value parameter pairs
         *   }
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
        jsonRpc:            null,

        separator:          ',',    // The term separator
        minLength:          2       // Minimum term length
    },
    _create: function(){
        var self    = this;
        var opts    = self.options;

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

        /********************************
         * Locate the pieces
         *
         */
        self.$input    = self.element.find('.scopeEntry :text');
        self.$curItems = self.element.find('.scopeItem');
        self.$submit   = self.element.find('.scopeEntry :submit');

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

        self._bindEvents();
    },

    _autocomplete: function(request, response) {
        var self    = this;
        var opts    = self.options;
        var params  = opts.jsonRpc.params;
        
        params.str  = self.$input.autocomplete('option', 'term');

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, opts.jsonRpc.method, params, {
            success:    function(ret, txtStatus, req){
                if (ret.error !== null)
                {
                    self.element.trigger('error', [txtStatus, req, ret.error]);
                    return;
                }

                response(
                    $.map(ret.result,
                          function(item) {
                            return {
                                label:   '<span class="name">'
                                       +  item.tag
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

    _bindEvents: function() {
        var self    = this;
        var opts    = self.options;

        // Attach a hover effect for deletables
        var $deletables = self.element.find('.deletable a.delete');
        $deletables
                .bind('mouseenter.itemScope', function(e) {
                    $(this).css('opacity', 1.0)
                           .addClass('ui-icon-circle-close')
                           .removeClass('ui-icon-close');
                })
                .bind('mouseleave.itemScope', function(e) {
                    $(this).css('opacity', 0.25)
                           .addClass('ui-icon-close')
                           .removeClass('ui-icon-circle-close');
                })
                .trigger('mouseleave');

        // Attach a click handler to the submit button
        self.$submit
                .bind('click.itemScope', function(e) {
                    // Force the 'submit' event on our form
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    self.element.submit();
                });

        // Attach a 'submit' handler to the itemScope form item
        self.element
                .bind('submit.itemScope', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    // Changing scope -- adjust the form's action
                    var loc     = window.location;
                    var url     = loc.toString();
                    var scope   = self.$input.val().replace(/\s*,\s*/g, ',')
                                                   .replace(/,$/, '');
                    if (url[url.length-1] !== '/')
                    {
                        url += '/';
                    }

                    if (scope.length > 0)
                    {
                        // Include the new scope item(s)
                        if (self.$curItems.length > 0)
                        {
                            url += ',';
                        }
                        url += scope;
                    }

                    // Simply change the browsers URL
                    window.location.assign(url);

                    // Allow form submission to continue
                });
    },

    /*************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;
        var opts    = self.options;

        // Destroy widgets
        if (opts.jsonRpc !== null)
        {
            self.$input.autocomplete('destroy');
        }
        self.$input.input('destroy');

        // Unbind events
        self.element.find('.deletable a.delete').unbind('.itemScope');
        self.$submit.unbind('.itemScope');
        self.element.unbind('.itemScope');
    }
});

}(jQuery));
