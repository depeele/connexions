/** @file
 *
 *  Javascript interface/wrapper for the posting of a bookmark.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-renderd bookmark post form
 *      (application/views/scripts/post/index-partial.phtml)
 *
 *  <form>
 *   <div class='item-status'>
 *    <div class='field favorite'>
 *     <label  for='isFavorite'>Favorite</label>
 *     <input name='isFavorite' type='checkbox' />
 *    </div>
 *    <div class='field private'>
 *     <label  for='isPrivate'>Private</label>
 *     <input name='isPrivate' type='checkbox' />
 *    </div>
 *   </div>
 *   <div class='item-data'>
 *    <div class='field userRating'>
 *     <?= View_Helper_HtmlStarRating output ?>
 *    </div>
 *    <div class='field item-name'>
 *     <label  for='name'>Bookmark name / title</label>
 *     <input name='name' type='text' class='required' />
 *    </div>
 *    <div class='field item-url'>
 *     <label  for='url'>URL to bookmark</label>
 *     <input name='url' type='text' class='required' />
 *    </div>
 *    <div class='field item-description'>
 *     <label     for='description'>
 *       Description / Notes for this bookmark
 *     </label>
 *     <textarea name='description'>...</textarea>
 *    </div>
 *    <div class='field item-tags'>
 *     <label     for='tags'>Tags</label>
 *     <textarea name='tags' class='required'>...</textarea>
 *    </div>
 *   </div>
 *   <div class='buttons'>
 *    <button name='submit'>Save</button>
 *    <button name='cancel'>Cancel</button>
 *   </div>
 *  </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.bookmarkPost", {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Widget state (mirrors Model_Bookmark)
        userId:     null,
        itemId:     null,

        name:       null,
        description:null,
        rating:     null,
        isFavorite: null,
        isPrivate:  null,

        tags:       null,
        url:        null,

        // taggedOn and updateOn are not user editable

        /* An element or element selector to be used to present general status
         * information.  If not provided, $.notify will be used.
         */
        statusEl:   null,

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
         * If not provided, 'method' will be:
         *      'bookmarks.update'
         *
         */
        jsonRpc:    null,
        rpcId:      1,      // The initial RPC identifier

        // Widget state
        enabled:    true
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'enabled'
     *      'disabled'
     *      'saved'
     *      'canceled'
     *      'complete'
     */
    _create: function()
    {
        var self        = this;
        var opts        = self.options;

        // Hide the form while we prepare it...
        self.element.hide();

        self.element.addClass('ui-form');

        /********************************
         * Initialize jsonRpc
         *
         */
        if ($.isFunction($.registry))
        {
            var api = $.registry('api');
            if (api && api.jsonRpc)
            {
                opts.jsonRpc = $.extend({method: 'bookmark.update'},
                                        api.jsonRpc, opts.jsonRpc);
            }
        }

        if ((opts.statusEl !== null) && (opts.statusEl.jquery === undefined))
        {
            opts.statusEl = $(opts.statusEl);
        }

        /********************************
         * Locate the pieces
         *
         */
        self.$required    = self.element.find('.required');

        self.$userId      = self.element.find('input[name=userId]');
        self.$itemId      = self.element.find('input[name=itemId]');

        self.$favorite    = self.element.find('input[name=isFavorite]');
        self.$private     = self.element.find('input[name=isPrivate]');
        self.$rating      = self.element.find('.userRating .stars-wrapper');

        self.$name        = self.element.find('input[name=name]');
        self.$url         = self.element.find('input[name=url]');
        self.$description = self.element.find('textarea[name=description]');
        self.$tags        = self.element.find('textarea[name=tags]');

        self.$save        = self.element.find('button[name=submit]');
        self.$cancel      = self.element.find('button[name=cancel]');

        // All input[text/password] and textarea elements
        self.$inputs      = self.element.find(  'input[type=text],'
                                              + 'input[type=password],'
                                              + 'textarea');

        // click-to-edit elements
        self.$cte         = self.element.find('.click-to-edit');

        /********************************
         * Instantiate our sub-widgets
         *
         */

        // Tag autocompletion
        self.$tags.autocomplete({
            source: function(req, rsp) {
                return self._autocomplete(req, rsp);
            }
        });

        // Status - Favorite
        self.$favorite.checkbox({
            css:        'connexions_sprites',
            cssOn:      'star_fill',
            cssOff:     'star_empty',
            titleOn:    'Favorite: click to remove from Favorites',
            titleOff:   'Click to add to Favorites',
            useElTitle: false,
            hideLabel:  true
        });

        // Status - Private
        self.$private.checkbox({
            css:        'connexions_sprites',
            cssOn:      'lock_fill',
            cssOff:     'lock_empty',
            titleOn:    'Private: click to share',
            titleOff:   'Public: click to mark as private',
            useElTitle: false,
            hideLabel:  true
        });

        // Rating - average and user
        self.$rating.stars({
            //split:    2
        });

        self.$save.addClass('ui-priority-primary')
                  .button({disabled: true});

        self.$cancel.addClass('ui-priority-secondary')
                    .button({disabled: false});

        /* Style all remaining input[type=text|password] / textarea controls
         * with ui.input
         */
        self.$inputs.input();

        // Add 'ui-field-info' for all required fields
        self.$required.after(  '<div class="ui-field-info">'
                             +  '<div class="ui-field-status"></div>'
                             +  '<div class="ui-field-requirements">'
                             +   'required'
                             +  '</div>'
                             + '</div>');

        self.$required
                .filter('[name=tags]')
                    .next('.ui-field-info')
                        .find('.ui-field-requirements')
                            .text('comma-separated, 30 characters per tag - '
                                  + 'required');

        /* (Re)size all 'ui-field-info' elements to match their corresponding
         * input field
         */
        self.$required.each(function() {
            var $input = $(this);

            $input.next().css('width', $input.css('width'));
        });

        /********************************
         * Initialize our state and bind
         * to interesting events.
         *
         */
        self._setStateFromForm();
        self._bindEvents();

        self.element.show();
    },

    _setStateFromForm: function()
    {
        // Set the current widget state to the values of it's sub-components
        var self    = this;
        var opts    = self.options;

        opts.name        = self.$name.val();
        opts.description = self.$description.val();
        opts.tags        = self.$tags.val();

        opts.isFavorite  = self.$favorite.checkbox('isChecked');
        opts.isPrivate   = self.$private.checkbox('isChecked');

        opts.url         = self.$url.val();

        if (self.$userId.length > 0)
        {
            opts.userId  = self.$userId.val();
        }

        if (self.$userId.length > 0)
        {
            opts.itemId  = self.$itemId.val();
        }

        if (self.$rating.length > 0)
        {
            opts.rating  = self.$rating.stars('value');
        }
    },

    _setFormFromState: function()
    {
        // Set the current widget state to the values of it's sub-components
        var self    = this;
        var opts    = self.options;

        self.$name.val(opts.name);
        self.$description.val(opts.description);
        self.$tags.val(opts.tags);

        self.$favorite.checkbox( opts.isFavorite ? 'check' : 'uncheck' );
        self.$private.checkbox(  opts.isPrivate  ? 'check' : 'uncheck' );

        self.$url.val(opts.url);

        if (self.$userId.length > 0)
        {
            self.$userId.val(opts.userId);
        }

        if (self.$userId.length > 0)
        {
            self.$itemId.val(opts.itemId);
        }

        if (self.$rating.length > 0)
        {
            self.$rating.stars('value', opts.rating);
        }
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function()
    {
        var self    = this;
        var opts    = self.options;

        // Handle a direct click on one of the status indicators
        var _save_click       = function(e, data) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            $.log('connexions.bookmarkPost::_save_click('+ data +')');

            self._performUpdate();

            return false;
        };

        var _cancel_click   = function(e, data) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            // :TODO: "Cancel" notification
            self._trigger('canceled', null, data);
            self._trigger('complete');
        };

        var _validation_change  = function(e, data) {
            /* On ANY validation change, remove the 'click-to-edit' class and
             * unbind this listener.
             */
            var $el = $(this);
            if ($el.data('validationInitialized') !== true)
            {
                $el.data('validationInitialized', true);
                return;
            }

            $el.removeClass('click-to-edit')
               .unbind('validationChange');
        };

        var _validate_form  = function() {
            var isValid     = true;

            self.$required.each(function() {
                if (! $(this).hasClass('ui-state-valid'))
                {
                    isValid = false;
                    return false;
                }
            });

            if (isValid)
            {
                self.$save.button('enable');
                self._status(true);
            }
            else
            {
                self.$save.button('disable');
                self._status(false);
            }
        };


        /**********************************************************************
         * bind events
         *
         */
        self.$inputs.bind('validation_change.bookmarkPost',
                                                _validate_form);

        self.$cte.bind('validation_change.bookmarkPost',
                                                _validation_change);

        self.$save.bind('click.bookmarkPost',   _save_click);
        self.$cancel.bind('click.bookmarkPost', _cancel_click);

        _validate_form();
    },

    _performUpdate: function() {
        var self    = this;
        var opts    = self.options;

        if (opts.enabled !== true)
        {
            return;
        }


        // Gather the current data about this item.
        var nonEmpty    = false;
        var params      = {
            id: { userId: opts.userId, itemId: opts.itemId }
        };

        // Include all fields that have changed.
        if (self.$name.val() !== opts.name)
        {
            params.name = self.$name.val();
            nonEmpty    = true;
        }

        if (self.$description.val() !== opts.description)
        {
            params.description = self.$description.val();
            nonEmpty           = true;
        }

        if ( (self.$tags.length > 0) &&
             (self.$tags.val() !== opts.tags) )
        {
            params.tags = self.$tags.val();
            nonEmpty    = true;
        }

        if (self.$favorite.checkbox('isChecked') !== opts.isFavorite)
        {
            params.isFavorite = self.$favorite.checkbox('isChecked');
            nonEmpty          = true;
        }

        if (self.$private.checkbox('isChecked') !== opts.isPrivate)
        {
            params.isPrivate = self.$private.checkbox('isChecked');
            nonEmpty         = true;
        }

        if ( (self.$rating.length > 0) &&
             (self.$rating.stars('value') !== opts.rating) )
        {
            params.rating = self.$rating.stars('value');
            nonEmpty      = true;
        }

        if (self.$url.val() !== opts.url)
        {
            // The URL has changed -- pass it in
            params.url = self.$url.val();
            nonEmpty   = true;
        }
        if (nonEmpty !== true)
        {
            // Nothing to save.
            self._trigger('complete');
            return;
        }

        // If no itemId was provided, use the final URL.
        if (params.id.itemId === null)
        {
            params.id.itemId = (params.url !== undefined
                                ? params.url
                                : opts.url);
        }

        // Generate a JSON-RPC to perform the update.
        var rpc = {
            version: opts.jsonRpc.version,
            id:      opts.rpcId++,
            method:  opts.jsonRpc.method,
            params:  params
        };

        self.element.mask();

        // Perform a JSON-RPC call to update this item
        $.ajax({
            url:        opts.jsonRpc.target,
            type:       opts.jsonRpc.transport,
            dataType:   'json',
            data:       JSON.stringify(rpc),
            success:    function(data, textStatus, req) {
                if (data.error !== null)
                {
                    self._status(false,
                                 'Bookmark update failed',
                                 data.error.message);

                    return;
                }

                self._status(true,
                             null,
                             'Bookmark '+ (opts.itemId === null
                                            ? 'created'
                                            : 'updated'));

                if (data.result === null)
                {
                    return;
                }

                self.options = $.extend(self.options, data.result);
                opts = self.options;

                if ($.isArray(opts.tags))
                {
                    var tags    = [];
                    $.each(opts.tags, function() {
                        tags.push(this.tag);
                    });

                    opts.tags = tags.join(',');
                }

                self._setFormFromState();

                // "Save" notification
                self._trigger('saved',    null, data.result);
                self._trigger('complete');
            },
            error:      function(req, textStatus, err) {
                self._status(false,
                             'Bookmark update failed',
                             textStatus);

                // :TODO: "Error" notification??
            },
            complete:   function(req, textStatus) {
                self.element.unmask();
            }
         });
    },

    _autocomplete: function(request, response) {
        var self    = this;
        var opts    = self.options;
        var id      = opts.rpcId++;
        var data    = {
            version:    opts.jsonRpc.version,
            id:         id,
            method:     'bookmark.autocompleteTag',
            params:     { id: { userId: opts.userId, itemId: opts.itemId } }
        };

        // If no itemId was provided, use the final URL.
        if (data.params.id.itemId === null)
        {
            // The URL has changed -- pass it in
            data.params.id.itemId = self.$url.val();
        }

        data.params.str = self.$tags.autocomplete('option', 'term');

        $.ajax({
            type:       opts.jsonRpc.transport,
            url:        opts.jsonRpc.target,
            dataType:   "json",
            data:       JSON.stringify(data),
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

    _status: function(isSuccess, title, text) {
        var self    = this;
        var opts    = self.options;

        if (opts.statusEl === null)
        {
            if ((title !== undefined) && (text !== undefined))
            {
                $.notify({title: title, text: text});
            }
        }
        else
        {
            var msg = '';
            /*
            if (title !== undefined)
            {
                msg += '<h3>'+ title +'</h3>';
            }
            */
            if (text !== undefined)
            {
                msg += text;
            }

            opts.statusEl.html(msg);

            if (isSuccess)
            {
                opts.statusEl.removeClass('error').addClass('success');
            }
            else
            {
                opts.statusEl.removeClass('success').addClass('error');
            }
        }
    },

    /************************
     * Public methods
     *
     */
    isEnabled: function()
    {
        return this.options.enabled;
    },

    enable: function()
    {
        var self    = this;
        var opts    = self.options;

        if (! opts.enabled)
        {
            opts.enabled = true;
            self.element.removeClass('ui-state-disabled');

            self.$favorite.checkbox('enable');
            self.$private.checkbox('enable');
            self.$rating.stars('enable');
            self.$inputs.input('enable');

            self._trigger('enabled', null, true);
        }
    },

    disable: function()
    {
        var self    = this;
        var opts    = self.options;

        if (opts.enabled)
        {
            opts.enabled = false;
            self.element.addClass('ui-state-disabled');

            self.$favorite.checkbox('disable');
            self.$private.checkbox('disable');
            self.$rating.stars('disable');
            self.$inputs.input('disable');

            self._trigger('disabled', null, true);
        }
    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Cleanup
        self.$save.removeClass('ui-priority-primary');
        self.$cancel.removeClass('ui-priority-secondary');
        self.$required.next('.ui-field-info').remove();

        self.element.removeClass('ui-form');

        // Unbind events
        self.$inputs.unbind('.bookmarkPost');
        self.$cte.unbind('.bookmarkPost');
        self.$save.unbind('.bookmarkPost');
        self.$cancel.unbind('.bookmarkPost');

        // Remove added elements
        self.$favorite.checkbox('destroy');
        self.$private.checkbox('destroy');
        self.$rating.stars('destroy');
        self.$inputs.input('destroy');
        self.$save.button('destroy');
        self.$cancel.button('destroy');
    }
});

}(jQuery));
