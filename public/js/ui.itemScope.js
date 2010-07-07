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
/*global jQuery:false, window:false */
(function($){

$.widget("ui.itemScope", {
    options: {
        namespace:          '',     // Cookie/parameter namespace
        autocompleteSrc:    null,   // The source of auto-completion data
                                    // (if non-null, passed to ui.autocomplete)
        rpcId:              1       // The initial RPC identifier
    },
    _create: function(){
        var self    = this;
        var opts    = self.options;

        self.$input    = self.element.find('.scopeEntry :text');
        self.$curItems = self.element.find('.scopeItem');
        self.$submit   = self.element.find('.scopeEntry :submit');

        self.$input.input();

        var source  = null;
        if (opts.autocompleteSrc !== null)
        {
            source = opts.autocompleteSrc;
        }
        else if (opts.jsonRpc !== undefined)
        {
            // Default source
            source = self._jsonRpc;
        }

        self.$input.autocomplete({
            source:     source,
            delay:      200,
            minLength:  2
        });

        self._bindEvents();
    },

    _jsonRpc: function(request, response) {
        var self    = this;
        var opts    = self.options;
        var id      = opts.rpcId++;
        var data    = {
            version:    '2.0',
            id:         id,
            method:     opts.jsonRpc.method,
            params:     opts.jsonRpc.params
        };

        data.params.str = request.term;

        $.ajax({
            type:       opts.jsonRpc.transport,
            url:        opts.jsonRpc.target,
            dataType:   "json",
            data:       JSON.stringify(data),
            success:    function(ret, txtStatus, req){
                response(
                    $.map(ret.result,
                          function(item) {
                            return {
                                label: item.tag +': '+
                                       item.itemCount,
                                value: item.tag
                            };
                          }));
                self.element.trigger('success',
                                     [ret,
                                      txtStatus,
                                      req]);
            },
            error:      function(req, txtStatus, e) {
                self.element.trigger('error',
                                     [txtStatus,
                                      req]);
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
                    var scope   = self.$input.val();

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
        if (opts.autocompleteSrc !== null)
        {
            self.$input.autocomplete('destroy');
        }
        else
        {
            self.$input.input('destroy');
        }

        // Unbind events
        self.element.find('.deletable a.delete').unbind('.itemScope');
        self.$submit.unbind('.itemScope');
        self.element.unbind('.itemScope');
    }
});

}(jQuery));