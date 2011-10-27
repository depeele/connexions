/** @file
 *
 *  Javascript interface/wrapper for the presentation of an item scope
 *  display/input area.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered area generate by View_Helper_HtmlItemScope:
 *      - conversion of the input area to a ui.input, ui.autocomplete, or
 *        ui.tagInput instance;
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
 *      ui.input.js, ui.autocomplete.js, or ui.tagInput.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false, document:false */
(function($){

$.widget("connexions.itemScope", {
    options: {
        namespace:          '',                 // Cookie/parameter namespace

        termName:           'tag',              /* The propert(ies)
                                                 * representing the
                                                 * autocompletion match string.
                                                 *
                                                 * MAY be an array if multiple
                                                 * properties were used in the
                                                 * autocomplete and should be
                                                 * presented.  In this case,
                                                 * the first SHOULD be the
                                                 * property to use as the
                                                 * autocompletion value.
                                                 */
        weightName:         'userItemCount',    /* The property to present
                                                 * as the autocompletion
                                                 * match weight.
                                                 */

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

        refocusCookie:      'itemScope-refocus',    /* The name of the cookie
                                                     * indicating the need to
                                                     * refocus on the input
                                                     * area upon
                                                     * initialization.
                                                     */

        separator:          ',',    // The term separator
        minLength:          2       // Minimum term length
    },
    _create: function(){
        var self    = this,
            opts    = self.options;

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
            if (self.$input.tagInput)
            {
                self.$input.tagInput({
                    height:         'none',
                    autocomplete:   {
                        source:     function(request, response) {
                            return self._autocomplete(request,response);
                        },
                        minLength:  opts.minLength,
                        position:   {
                            offset: '0 5'
                        }
                    }
                });

                // Make it easier for _autocomplete() and destroy()
                self.autocompleteWidget = self.$input.data('tagInput');
            }
            else if (self.$input.autocomplete)
            {
                self.$input.autocomplete({
                    separator:  ',',
                    source:     function(request, response) {
                        return self._autocomplete(request,response);
                    },
                    minLength:  opts.minLength
                });

                // Make it easier for _autocomplete() and destroy()
                self.autocompleteWidget = self.$input.data('autocomplete');
            }
        }

        self._bindEvents();

        // See if we should refocus on our input area upon initialization
        var cookieOpts  = {},
            cookiePath  = $.registry('cookiePath');
        if (cookiePath)
        {
            cookieOpts.path = cookiePath;
        }

        if ($.cookie(opts.refocusCookie))
        {
            // Delete the cookie
            $.cookie(opts.refocusCookie, null, cookieOpts);

            self.$input.focus();
        }
    },

    _autocomplete: function(request, response) {
        var self    = this;
        var opts    = self.options;
        var params  = opts.jsonRpc.params;
        
        params.term = self.autocompleteWidget.option('term');

        var re      = new RegExp(params.term, 'gi');

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
                            var str     = '';
                            var value   = null;
                            var weight  = (item[opts.weightName] === undefined
                                            ? ''
                                            : item[opts.weightName]);

                            if ($.isArray(opts.termName))
                            {
                                // Multiple match keys
                                var parts   = [];
                                $.each(opts.termName, function() {
                                    if (item[ this ] === undefined)
                                    {
                                        return;
                                    }

                                    if (value === null)
                                    {
                                        value = item[this];
                                    }

                                    str = item[this]
                                            .replace(re,
                                                     '<b>'+params.term+'</b>');

                                    parts.push( str );
                                });

                                str = parts.join(', ');
                            }
                            else
                            {
                                value = item[opts.termName];
                                str   = value
                                        .replace(re, '<b>'+params.term+'</b>');
                            }

                            return {
                                label:   '<span class="name">'
                                       +  str
                                       + '</span>'
                                       +' <span class="count">'
                                       +  weight
                                       + '</span>',
                                value: value
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
                .trigger('mouseleave')
                .bind('click.itemScope', function(e) {
                    $.spinner();
                });

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
                                                   .replace(/(^,|,$)/g, '');

                    if (self.$curItems.length > 0)
                    {
                        if (scope.length > 0)
                        {
                            url += ',';
                        }
                    }
                    else if (url[url.length-1] !== '/')
                    {
                        url += '/';
                    }
                    url += scope;

                    // Simply change the browsers URL
                    $.spinner();
                    window.location.assign(url);

                    // Allow form submission to continue
                });

        /* Attach a 'keypress' handler to the itemScope input item.  On ENTER,
         * trigger 'submit' on the form item.
         */
        self.$input
                .bind('keypress.itemScope', function(e) {
                    if (e.keyCode === $.ui.keyCode.ENTER)
                    {
                        // Set the itemScope-refocus cookie
                        var cookieOpts  = {},
                            cookiePath  = $.registry('cookiePath');
                        if (cookiePath)
                        {
                            cookieOpts.path = cookiePath;
                        }

                        $.cookie(opts.refocusCookie, true, cookieOpts);
                                
                        self.element.submit();
                    }
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
            self.autocompleteWidget.destroy();
        }
        self.$input.input('destroy');

        // Unbind events
        self.element.find('.deletable a.delete').unbind('.itemScope');
        self.$submit.unbind('.itemScope');
        self.$input.unbind('.itemScope');
        self.element.unbind('.itemScope');
    }
});

}(jQuery));
