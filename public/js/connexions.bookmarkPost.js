/** @file
 *
 *  Javascript interface/wrapper for the posting of a bookmark.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-renderd bookmark post from
 *      (application/views/scripts/post/index-partial.phtml)
 *
 *      - conversion of markup for suggestions to ui.tabs instance(s) 
 *        possibly containing connexions.collapsible instance(s);
 *
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
 *
 *   <div class='suggestions' style='display:none;'>
 *    <ul>
 *     <li><a href='#suggestions-tags'><span>Tags</span></a></li>
 *     <li><a href='#suggestions-people'><span>People</span></a></li>
 *    </ul>
 *
 *    <ul id='suggestions-tags'>
 *     <li class='collapsable'>
 *      <h3 class='tooggle'><span>Recommended</span></h3>
 *      <div class='cloud'>
 *      </div>
 *     </li>
 *
 *     <li class='collapsable'>
 *      <h3 class='tooggle'><span>Your Top 100</span></h3>
 *      <div class='cloud'>
 *      </div>
 *     </li>
 *    </ul>
 *
 *    <ul id='suggestions-people'>
 *     <li class='collapsable'>
 *      <h3 class='tooggle'><span>Network</span></h3>
 *      <div class='cloud'>
 *      </div>
 *     </li>
 *    </ul>
 *   </div>
 *
 *  </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      connexions.collapsable
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
        $status:    null,   //'.status',

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
         */
        jsonRpc:    null,

        /* If the JSON-RPC method is GET, the apiKey for the authenticated user
         * is required for any methods that modify data.
         */
        apiKey:     null,

        /* Is this an edit of an existing user bookmark (true) or a user saving
         * the bookmark of another user (false)?
         *
         * If 'isEdit' is false, changes are NOT required to data fields before
         * saving AND ALL fields will be included in the update regardless of
         * whether they've changed.
         */
        isEdit:     true,

        // Widget state
        enabled:    true
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'enabled'
     *      'disabled'
     *      'urlChanged'        -- new URL/bookmark data
     *      'isEditChanged'     -- new URL/bookmark data
     *      'saved'
     *      'canceled'
     *      'complete'
     */
    _init: function()
    {
        var self        = this;
        var opts        = self.options;

        // Hide the form while we prepare it...
        self.element.hide();

        self.element.addClass('ui-form ui-bookmarkPost');

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

        if ((opts.$status !== null) && (opts.$status.jquery === undefined))
        {
            opts.$status = $(opts.$status);
        }

        /********************************
         * Locate the pieces
         *
         */
        opts.$required    = self.element.find('.required');

        // Hidden fields
        opts.$userId      = self.element.find('input[name=userId]');
        opts.$itemId      = self.element.find('input[name=itemId]');

        // Text fields
        opts.$name        = self.element.find('input[name=name]');
        opts.$url         = self.element.find('input[name=url]');
        opts.$description = self.element.find('textarea[name=description]');
        opts.$tags        = self.element.find('textarea[name=tags]');

        // Non-text fields
        opts.$favorite    = self.element.find('input[name=isFavorite]');
        opts.$private     = self.element.find('input[name=isPrivate]');
        opts.$rating      = self.element.find('.userRating .stars-wrapper');

        // Buttons
        opts.$save        = self.element.find('button[name=submit]');
        opts.$cancel      = self.element.find('button[name=cancel]');
        opts.$reset       = self.element.find('button[name=reset]');

        // All input[text/password] and textarea elements
        opts.$inputs      = self.element.find(  'input[type=text],'
                                              + 'input[type=password],'
                                              + 'textarea');

        // click-to-edit elements
        opts.$cte         = self.element.find('.click-to-edit');

        // 'suggestions' div -- to be converted to ui.tabs
        opts.$suggestions = self.element.find('.suggestions');

        // 'collapsable' elements -- to be converted to connexions.collapsable
        opts.$collapsable = self.element.find('.collapsable');

        /********************************
         * Instantiate our sub-widgets
         *
         */

        // Tag autocompletion
        opts.$tags.tagInput({
            noHeight:       true,
            change:     function() {
                /*
                $.log('connexions.bookmarkPost::'
                       + '$tags.change( "'+ opts.$tags.val() +'" )');
                // */

                // Highlight the "new" tags and validate
                self._highlightTags();
                self.validate();
            },
            autocomplete:   {
                source:     function(req, rsp) {
                    /*
                    $.log('connexions.bookmarkPost::'
                           + '$tags.source('+ req.term +')');
                    // */
                    return self._autocomplete(req, rsp);
                },
                change:     function(e, ui) {
                    /*
                    $.log('connexions.bookmarkPost::'
                           + '$tags.change( "'+ opts.$tags.val() +'" )');
                    // */
                    self._highlightTags();
                },
                close:  function(e, ui) {
                    // A tag has been completed.  Perform highlighting.
                    /*
                    $.log('connexions.bookmarkPost::$tags.close()');
                    // */

                    self._highlightTags();
                }
            }
        });

        // Status - Favorite
        opts.$favorite.checkbox({
            css:        'connexions_sprites',
            cssOn:      'star_fill',
            cssOff:     'star_empty',
            titleOn:    'Favorite: click to remove from Favorites',
            titleOff:   'Click to add to Favorites',
            useElTitle: false,
            hideLabel:  true
        });

        // Status - Private
        opts.$private.checkbox({
            css:        'connexions_sprites',
            cssOn:      'lock_fill',
            cssOff:     'lock_empty',
            titleOn:    'Private: click to share',
            titleOff:   'Public: click to mark as private',
            useElTitle: false,
            hideLabel:  true
        });

        // Rating - average and user
        opts.$rating.stars({
            //split:    2
        });

        opts.$save.addClass('ui-priority-primary')
                  .button({disabled: true});

        opts.$cancel.addClass('ui-priority-secondary')
                    .button({disabled: false});
        opts.$reset.addClass('ui-priority-secondary')
                    .button({disabled: false});

        opts.$suggestions.tabs();
        opts.$collapsable.collapsable();

        /* Style all remaining input[type=text|password] / textarea controls
         * with ui.input
         */
        opts.$inputs.input();

        // Add 'ui-field-info' for all required fields
        opts.$required.after(  '<div class="ui-field-info">'
                             +  '<div class="ui-field-status"></div>'
                             +  '<div class="ui-field-requirements">'
                             +   'required'
                             +  '</div>'
                             + '</div>');

        opts.$required
                .filter('[name=tags]')
                    .next('.ui-field-info')
                        .find('.ui-field-requirements')
                            .text('comma-separated, 30 characters per tag - '
                                  + 'required');

        /* (Re)size all 'ui-field-info' elements to match their corresponding
         * input field
         */
        opts.$required.each(function() {
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

        opts.name        = opts.$name.val();
        opts.description = opts.$description.val();
        opts.tags        = opts.$tags.val();

        opts.isFavorite  = opts.$favorite.checkbox('isChecked');
        opts.isPrivate   = opts.$private.checkbox('isChecked');

        opts.url         = opts.$url.val();

        if (opts.$userId.length > 0)
        {
            opts.userId  = opts.$userId.val();
        }

        if (opts.$userId.length > 0)
        {
            opts.itemId  = opts.$itemId.val();
        }

        if (opts.$rating.length > 0)
        {
            opts.rating  = opts.$rating.stars('value');
        }

        if (opts.tags.length > 0)
        {
            self._highlightTags();
        }

        /* If the value of 'isEdit' is changing, trigger 'isEditChanged' making
         * sure this.options.isEdit reflects the new  value BEFORE triggering.
         */
        var oldIsEdit   = opts.isEdit;
        opts.isEdit     = (opts.userId === null ? false : true);

        if (oldIsEdit !== opts.isEdit)
        {
            self.element.trigger('isEditChanged', opts.isEdit);
        }
    },

    _setFormFromState: function()
    {
        // Set the current widget state to the values of it's sub-components
        var self    = this;
        var opts    = self.options;

        /* Set the value of the underlying controls as well as notifying the
         * ui.input widget of the new value
         */
        opts.$name.val(opts.name).input('val', opts.name);
        opts.$description.val(opts.description).input('val', opts.description);
        opts.$tags.val(opts.tags).input('val', opts.tags);

        opts.$favorite.checkbox( opts.isFavorite ? 'check' : 'uncheck' );
        opts.$private.checkbox(  opts.isPrivate  ? 'check' : 'uncheck' );

        /* Do NOT use opts.$url.input('val', opts.url) since this will fire a
         * 'change' event, causing _url_changed() to be invoked, resulting in
         * another call to this method, ...
         */
        opts.$url.val(opts.url);

        if (opts.$userId.length > 0)
        {
            opts.$userId.val(opts.userId);
        }

        if (opts.$userId.length > 0)
        {
            opts.$itemId.val(opts.itemId);
        }

        if (opts.$rating.length > 0)
        {
            //opts.$rating.stars('value', opts.rating);
            opts.$rating.stars('option', 'value', opts.rating)
                        .stars('select', opts.rating);
        }

        /* If the value of 'isEdit' is changing, trigger 'isEditChanged' making
         * sure this.options.isEdit reflects the new  value BEFORE triggering.
         */
        var oldIsEdit   = opts.isEdit;
        opts.isEdit     = (opts.userId === null ? false : true);

        if (oldIsEdit !== opts.isEdit)
        {
            self.element.trigger('isEditChanged', opts.isEdit);
        }
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function()
    {
        var self            = this;
        var opts            = self.options;
        var tagsTabIndex    = Math.floor(opts.$tags.attr('tabIndex'));

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

        var _reset_click   = function(e, data) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            self.reset();
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

        var _url_change = function(e, data) {
            var $el = $(this);
            if ($el.hasClass('ui-state-valid'))
            {
                /* We have a new, valid URL.  See if there is a bookmark
                 * that matches.
                 */
                self._url_changed();
            }
        };

        var _validate_form  = function() {
            self.validate();
        };

        /* Context bind this function in 'self/this' so we can use it
         * outside of this routine.
         */
        self._tagClick = function( event ) {
            event.preventDefault();
            event.stopPropagation();

            var $li     = $(this);
            var $item   = $li.find('a:first');
            var tag     = $item.data('id');
            var tags    = opts.$tags.val();

            if ($li.hasClass('user'))
            {
                // This is a 'user' item, likely in the 'People' tab.
                tag = 'for:'+ tag;
            }

            if ($item.hasClass('selected'))
            {
                // De-select / remove
                opts.$tags.tagInput('deleteTag', tag);
            }
            else
            {
                opts.$tags.tagInput('addTag', tag);
            }

            self._highlightTags();
        };

        /** @brief  Handle tab/backtab for input focus changes.  This is
         *          primarily to ensure that opts.$tags receives focus events.
         */
        var keyCode         = $.ui.keyCode;
        var _form_tabFocus  = function(e) {
            var key     = e.keyCode || e.which;

            if (key === keyCode.TAB)
            {
                var $target     = $(e.target);
                var tabIndex    = Math.floor($target.attr('tabIndex'));

                $.log('connexions.bookmarkPost::_form_tabFocus(): '
                      + 'target[ '+ $target.attr('name') +' ], '
                      + 'tabIndex[ '+ tabIndex +' ], '
                      + 'tagsTabIndex[ '+ tagsTabIndex +' ], '
                      + 'key[ '+ key +' ], '
                      + 'shiftKey[ '+ e.shiftKey +' ]');

                /* Tab       == forward  one field
                 * Shift-Tab == backward one field
                 */
                if ( (   e.shiftKey  && ((tabIndex - 1) === tagsTabIndex)) ||
                     ((! e.shiftKey) && ((tabIndex + 1) === tagsTabIndex)) )
                {
                    // opts.$tags is to be the newly focused field
                    $.log('connexions.bookmarkPost::_form_tabFocus(): '
                          + 'trigger focus for opts.$tags');
                    opts.$tags.focus();

                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                }
            }
        };

        /**********************************************************************
         * bind events
         *
         */
        opts.$inputs.bind('validation_change.bookmarkPost',
                                                _validate_form);
        opts.$favorite.bind('change.bookmarkPost',
                                                _validate_form);
        opts.$private.bind('change.bookmarkPost',
                                                _validate_form);
        opts.$rating.bind('change.bookmarkPost',
                                                _validate_form);

        opts.$cte.bind('validation_change.bookmarkPost',
                                                _validation_change);

        opts.$save.bind('click.bookmarkPost',   _save_click);
        opts.$cancel.bind('click.bookmarkPost', _cancel_click);
        opts.$reset.bind('click.bookmarkPost',  _reset_click);

        opts.$url.bind('validation_change.bookmarkPost',
                                                _url_change);

        opts.$suggestions.delegate('.cloud .cloudItem,'
                                   + '.cloud .Item_List li:not(.header)',
                                   'click.bookmarkPost',
                                                self._tagClick);

        self.element.bind('keydown.bookmarkPost', _form_tabFocus);

        _validate_form();
    },

    /** @brief  Perform a Json-RPC call to "update" (possibly save) the
     *          bookmark represented by this dialog.
     */
    _performUpdate: function()
    {
        var self    = this;
        var opts    = self.options;

        if (opts.enabled !== true)
        {
            return;
        }


        // Gather the current data about this item.
        var nonEmpty    = false;
        var params      = {
            /* id is required: For 'Edit' is should be the userId/itemId of
             * this bookmark
             */
            id: {
                userId: opts.userId,
                itemId: opts.itemId
            }
        };

        if (opts.isEdit !== true)
        {
            /* For 'Save', userId MUST be empty/null to notify Service_Bookmark
             * to use the authenticated user's id.
             */
            params.id.userId = null;
        }

        // Include all fields that have changed.
        if ( (opts.isEdit !== true) ||
             (opts.$name.val() !== opts.name) )
        {
            params.name = opts.$name.val();
            nonEmpty    = true;
        }

        if ( (opts.isEdit !== true) ||
             (opts.$description.val() !== opts.description) )
        {
            params.description = opts.$description.val();
            nonEmpty           = true;
        }

        if ( (opts.isEdit !== true) ||
             ((opts.$tags.length > 0) &&
              (opts.$tags.val() !== opts.tags)) )
        {
            params.tags = opts.$tags.val();
            nonEmpty    = true;
        }

        if ( (opts.isEdit !== true) ||
             (opts.$favorite.checkbox('isChecked') !== opts.isFavorite) )
        {
            params.isFavorite = opts.$favorite.checkbox('isChecked');
            nonEmpty          = true;
        }

        if ( (opts.isEdit !== true) ||
             (opts.$private.checkbox('isChecked') !== opts.isPrivate) )
        {
            params.isPrivate = opts.$private.checkbox('isChecked');
            nonEmpty         = true;
        }

        if ( (opts.isEdit !== true) ||
             ((opts.$rating.length > 0) &&
              (opts.$rating.stars('value') !== opts.rating)) )
        {
            params.rating = opts.$rating.stars('value');
            nonEmpty      = true;
        }

        if ( (opts.isEdit !== true) ||
             (opts.$url.val() !== opts.url) )
        {
            // The URL has changed -- pass it in
            params.url = opts.$url.val();
            nonEmpty   = true;
        }

        if (nonEmpty !== true)
        {
            // Nothing to save.
            self._trigger('complete');
            return;
        }

        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        self.element.mask();

        var verb    = (opts.isEdit === true
                        ? 'update'
                        : 'save');

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'bookmark.update', params, {
            success:    function(data, textStatus, req) {
                if (data.error !== null)
                {
                    self._status(false,
                                 'Bookmark '+ verb +' failed',
                                 data.error.message);

                    return;
                }

                self._status(true,
                             'Bookmark '+ verb +' succeeded',
                             'Bookmark '+ verb +'d'
                             /*
                                          (opts.itemId === null
                                            ? 'created'
                                            : 'updated')
                             */
                );

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
                if ($.isPlainObject(opts.item))
                {
                    opts.url = opts.item.url;
                }

                // "Save" notification
                self._trigger('saved',    null, data.result);

                /* Finally, update the form state
                 *
                 * :XXX: We doe this AFTER triggering 'saved' so any
                 *       'isEditChanged' event won't confuse anyone listeing to
                 *       both 'saved' and 'isEventChanged' events since
                 *       technically, the 'saved' event should reflect the
                 *       'isEdit' value BEFORE the new form data is applied.
                 */
                self._setFormFromState();

                self._trigger('complete');
            },
            error:      function(req, textStatus, err) {
                self._status(false,
                             'Bookmark '+ verb +' failed',
                             textStatus);

                // :TODO: "Error" notification??
            },
            complete:   function(req, textStatus) {
                self.element.unmask();
            }
         });
    },

    /** @brief  After a change to the item's URL, first check to see if there
     *          is a matching bookmark.  If not, perform a HEAD request to
     *          retrieve information.
     */
    _url_changed: function()
    {
        var self    = this;
        var opts    = self.options;

        if (opts.enabled !== true)
        {
            return;
        }


        // Gather the current data about this item.
        var url     = opts.$url.val();
        var params  = {
            /* id is required: For 'Edit' is should be the userId/itemId of
             * this bookmark
             */
            id: { userId: opts.userId, itemId: url }
        };

        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        self.element.mask();

        /* Perform a JSON-RPC call to attempt to retrieve new bookmark
         * information.
         */
        var bookmarkFound   = false;
        $.jsonRpc(opts.jsonRpc, 'bookmark.find', params, {
            success:    function(data, textStatus, req) {
                if (data.error !== null)
                {
                    // Let 'bookmarkFound' remain false
                    $.notify({title: 'Cannot retrieve bookmark',
                              text:  data.error.message});
                    /*
                    self._status(false,
                                 'Cannot retrieve bookmark',
                                 data.error.message);
                    // */
                    return;
                }
                if (data.result === null)
                {
                    // Let 'bookmarkFound' remain false
                    return;
                }

                bookmarkFound = true;

                // Update the presentation with the new bookmark data.
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
                if ($.isPlainObject(opts.item))
                {
                    opts.url = opts.item.url;
                }

                self._setFormFromState();
                self.validate();

                self.element.trigger('urlChanged');
            },
            error:      function(req, textStatus, err) {
                $.notify({title: 'Cannot retrieve bookmark',
                          text:  textStatus});
                /*
                self._status(false,
                             'Cannot retrieve bookmark',
                             textStatus);
                // */

                // :TODO: "Error" notification??
            },
            complete:   function(req, textStatus) {
                self.element.unmask();

                // If a matching bookmark wasn't found, perform a HEAD request.
                if (bookmarkFound === false)
                {
                    $.notify({title: 'Pulling information',
                              text:  'Performing a HEAD request on URL<br />'
                                     +  "<tt>'"+ url +"'</tt>"});
                    self._headers( url );
                }
                else
                {
                    /********************************************************
                     * Also, update the recommended tags section in the
                     * suggestions area.
                     *
                     */
                    self._update_recommendedTags( url );
                }
            }
         });
    },

    /** @brief  Callback for _headers() to process retrieved site headers.
     *  @param  headers     An object containing title and meta items from
     *                      the sites <head> section.
     *
     */
    _headers_success: function(headers)
    {
        var self    = this;
        var opts    = self.options;

        if ( ! opts.$name.input('hasChanged') )
        {
            // See if we can find the title
            if (headers.title.length > 0)
            {
                /* Do NOT use input('val') here since we don't want to 
                 * alter the field's default value.
                 */
                opts.$name.val(headers.title );
                opts.$name.blur();
            }
        }

        if ( ! opts.$name.input('hasChanged') )
        {
            // See if there is a '<meta name="description">'
            var $desc   = headers.meta.filter('meta[name=description]');
            if ($desc.length > 0)
            {
                opts.$description.val($desc.attr('content') );
                opts.$description.blur();
            }
        }

        if ( ! opts.$tags.input('hasChanged') )
        {
            // See if there is a '<meta name="keywords">'
            var $keywords   = headers.meta.filter('meta[name=keywords]');
            if ($keywords.length > 0)
            {
                opts.$tags.val($keywords.attr('content') );
                opts.$tags.blur();
            }
        }
    },

    /** @brief  Make a request to our server for the retrieval of 'title' and
     *          'meta' items from within the <head> element of the web page at
     *          the given URL.
     *  @param  url     The desired URL.
     *  @param  callback    The callback to invoke upon successful retrieval:
     *                          callback( headers )
     */
    _headers: function(url, callback)
    {
        var self    = this;
        var opts    = self.options;

        if (self.headersUrl === url)
        {
            // We've already done a check for this URL.
            return;
        }
        self.headersUrl = url;


        /********************************************************
         * Generate a JSON-RPC to perform the header retrieval.
         *
         */
        var params  = {
            url:        url,
            keepTags:   'title,meta'
        };

        $.jsonRpc(opts.jsonRpc, 'util.getHead', params, {
            success:    function(data, textStatus, req) {
                if (data.error !== null)
                {
                    /*
                    self._status(false,
                                 'URL header retrieval',
                                 data.error.message);
                    // */

                    return;
                }

                if (data.result === null)
                {
                    return;
                }

                var $head   = $('<div />');
                $head.html( data.result.html );

                // Now, pull out all title and meta items
                var headers = {
                    title:  $head.find('title').text(),
                    meta:   $head.find('meta')
                };

		        if ($.isFunction(callback))
                {
			        callback(headers);
		        }
                else
                {
                    self._headers_success(headers);
                }
            },
            error:      function(req, textStatus, err) {
                // :TODO: "Error" notification / invalid URL??
                //self.headersUrl = null;
            },
            complete:   function(req, textStatus) {
                // :TODO: Some indication of completion?
            }
         });

        /********************************************************
         * Also, update the recommended tags section in the
         * suggestions area.
         *
         */
        self._update_recommendedTags(url);
    },

    /** @brief  Make a request to our server for the retrieval of recommended
     *          tags for the given 'url'.
     *  @param  url     The desired URL.
     */
    _update_recommendedTags: function(url)
    {
        var self    = this;
        var opts    = self.options;

        $.ajax({
            url:    ($.registry('urls')).base +'/post/',
            data:   {
                format: 'partial',
                part:   'main-tags-recommended',
                url:    url
            },
            success: function(data) {
                var $content    = opts.$suggestions
                                        .find('#suggestions-tags '
                                                +'.tags-recommended .content');

                $content.html( data );

                self._highlightTags();
            }
        });
    },

    _highlightTags: function()
    {
        var self    = this;
        var opts    = self.options;

        if (opts.$suggestions.length < 1)
        {
            // No suggestions area so no tags to highlight
            return;
        }

        // Find all tags in the suggestions area
        var $cloudTags  = opts.$suggestions.find('.cloud .cloudItem a,'
                                                 + '.cloud .Item_List li a');

        // Remove any existing highlights
        $cloudTags.filter('.selected').removeClass('selected');

        // Highlight any currently selected tags.
        var tags    = opts.$tags.tagInput('option', 'tags');  //val();
        var nTags   = tags.length;
        var tag     = null;

        if (nTags < 1)
        {
            return;
        }

        //tags  = tags.split(/\s*,\s*/);
        //nTags = tags.length;

        var forRe   = /^for:/;
        for (var idex = 0; idex < nTags; idex++)
        {
            tag = tags[idex];
            if (tag.length < 1)
            {
                continue;
            }

            tag = tag.replace('"', '\"');
            if (forRe.test(tag))
            {
                // 'for:' user sharing tag
                tag = tag.replace(forRe, '');
            }
            else
            {
                tag = tag.toLowerCase();
            }
            //$.log('connexions.bookmarkPost::_highlightTags('+ tag +')');

            $cloudTags.filter('.item[data-id="'+ tag +'"]')
                      .addClass('selected');

            /*
            $cloudTags.filter(':contains("'+ tag +'")')
                      .addClass('selected');
            // */
        }
    },

    _autocomplete: function(request, response)
    {
        var self    = this;
        var opts    = self.options;
        var term    = opts.$tags.tagInput('option', 'term');
        var re      = new RegExp(term, 'gi');
        var params  = {
            id:     { userId: opts.userId, itemId: opts.itemId },
            term:   term
        };


        /* If no itemId was provided (or the URL has changed), use the current
         * URL value.
         */
        if ( (params.id.itemId === null) ||
             (opts.$url.val()  !== opts.url) )
        {
            // The URL has changed -- pass it in
            params.id.itemId = opts.$url.val();
        }

        $.jsonRpc(opts.jsonRpc, 'bookmark.autocompleteTag', params, {
            success:    function(ret, txtStatus, req){
                if (ret.error !== null)
                {
                    self.element.trigger('error', [txtStatus, req, ret.error]);
                    return;
                }

                var res = $.map(ret.result, function(item) {
                    var str     = item.tag.replace(re, '<b>'+ term +'</b>');
                    var weight  = item.userItemCount;

                    return {
                        label:   '<span class="name">'+ str +'</span>'
                                +'<span class="count">'+ weight +'</span>',
                        value:  item.tag
                    };
                });

                response( res );
                self.element.trigger('success', [ret, txtStatus, req]);
            },
            error:      function(req, txtStatus, e) {
                self.element.trigger('error', [txtStatus, req]);
            }
        });
    },

    _status: function(isSuccess, title, text)
    {
        var self    = this;
        var opts    = self.options;

        if (opts.$status === null)
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

            opts.$status.html(msg);

            if (isSuccess)
            {
                opts.$status.removeClass('error').addClass('success');
            }
            else
            {
                opts.$status.removeClass('success').addClass('error');
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

            opts.$favorite.checkbox('enable');
            opts.$private.checkbox('enable');
            opts.$rating.stars('enable');
            opts.$inputs.input('enable');

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

            opts.$favorite.checkbox('disable');
            opts.$private.checkbox('disable');
            opts.$rating.stars('disable');
            opts.$inputs.input('disable');

            self._trigger('disabled', null, true);
        }
    },

    /** @brief  Reset any ui.input fields to their original
     *          (creation or direct set) values.
     */
    reset: function()
    {
        var self        = this;
        var opts        = self.options;

        opts.$favorite.checkbox('reset');
        opts.$private.checkbox('reset');
        opts.$rating.stars('reset');
        opts.$inputs.input('reset');

        self._trigger('reset');
        self.headersUrl = undefined;

        self.validate();
    },

    validate: function()
    {
        var self        = this;
        var opts        = self.options;
        var isValid     = true;
        var hasChanged  = self.hasChanged();

        if (hasChanged)
        {
            opts.$required.each(function() {
                if (! $(this).hasClass('ui-state-valid'))
                {
                    isValid = false;
                    return false;
                }
            });

            if (isValid)
            {
                self._status(true);
            }
            else
            {
                self._status(false);
            }
        }

        if ( isValid && ((opts.isEdit !== true) || hasChanged) )
        {
            opts.$save.button('enable');
        }
        else
        {
            opts.$save.button('disable');
        }
    },

    /** @brief  Have any of the ui.input fields changed from their original
     *          values?
     *
     *  @return true | false
     */
    hasChanged: function()
    {
        var self        = this;
        var opts        = self.options;
        var hasChanged  = false;

        // Has anything changed from the forms initial values?
        opts.$inputs.each(function() {
            if ($(this).input('hasChanged'))
            {
                hasChanged = true;
                return false;
            }
        });

        if ((! hasChanged) &&
            (opts.$favorite.checkbox('hasChanged') ||
             opts.$private.checkbox('hasChanged')  ||
             opts.$rating.stars('hasChanged')) )
        {
            hasChanged = true;
        }

        return hasChanged;
    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Cleanup
        opts.$save.removeClass('ui-priority-primary');
        opts.$cancel.removeClass('ui-priority-secondary');
        opts.$reset.removeClass('ui-priority-secondary');
        opts.$required.next('.ui-field-info').remove();

        self.element.removeClass('ui-form');

        // Unbind events
        self.element.unbind('.bookmarkPost');

        opts.$inputs.unbind('.bookmarkPost');
        opts.$favorite.unbind('.bookmarkPost');
        opts.$private.unbind('.bookmarkPost');
        opts.$rating.unbind('.bookmarkPost');
        opts.$cte.unbind('.bookmarkPost');
        opts.$save.unbind('.bookmarkPost');
        opts.$cancel.unbind('.bookmarkPost');
        opts.$reset.unbind('.bookmarkPost');

        opts.$url.unbind('.bookmarkPost');
        opts.$tags.unbind('.bookmarkPost');

        opts.$suggestions.undelegate('.cloud .cloudItem,'
                                     + '.cloud .Item_List li:not(.header)',
                                     '.bookmarkPost');

        // Remove added elements
        opts.$favorite.checkbox('destroy');
        opts.$private.checkbox('destroy');
        opts.$rating.stars('destroy');
        opts.$inputs.input('destroy');
        opts.$save.button('destroy');
        opts.$cancel.button('destroy');
        opts.$reset.button('destroy');

        opts.$suggestions.tabs('destroy');
        opts.$collapsable.collapsable('destroy');
    }
});

}(jQuery));
