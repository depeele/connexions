/** @file
 *
 *  Javascript interface/wrapper for the presentation of a single bookmark.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered bookmark item (View_Helper_HtmlBookmark):
 *      - convert (optional Favorite and Privacy checkboxes into image-based
 *        hover buttons;
 *      - convert any (optional) star rating presentation to an active ui.stars
 *        widget;
 *      - allow in-line, on demand editing of the bookmark if it has a
 *        '.control .item-edit' link;
 *      - allow in-line, on demand deletion of the bookmark if it has a
 *        '.control .item-delete' link;
 *      - allow in-line, on demand saving of the bookmark if it has a
 *        '.control .item-save' link;
 *
 *  View_Helper_HtmlBookmark will generate HTML for a bookmark similar to:
 *     <form class='bookmark'>
 *       <input type='hidden' name='userId' value='...' />
 *       <input type='hidden' name='itemId' value='...' />
 *
 *       <!-- Status -->
 *       <div class='status'>
 *         <div class='favorite'>
 *           <input type='checkbox' name='isFavorite' value='...' />
 *         </div>
 *         <div class='private'>
 *           <input type='checkbox' name='isPrivate' value='...' />
 *         </div>
 *       </div>
 *
 *       <!-- Stats: item:stats -->
 *       <div class='stats'>
 *
 *         <!-- item:stats:count -->
 *         <a class='count' ...> count </a>
 *
 *         <!-- item:stats:rating -->
 *         <div class='rating'>
 *           <div class='stars'>
 *
 *             <!-- item:stats:rating:stars -->
 *             <div class='ui-stars-wrapper'> ... </div>
 *           </div>
 *
 *           <!-- item:stats:rating:info -->
 *           <div class='info'>
 *             <span class='count'> count </span> raters,
 *             <span class='average'> average </span> avg.
 *           </div>
 *         </div>
 *       </div>
 *
 *       <!-- Bookmark Data: item:data -->
 *       <div class='data'>
 *
 *         <!-- User Identification: item:data:userId -->
 *         <div class='userId'>
 *           <a ...>
 *
 *             <!-- item:data:userId:avatar -->
 *             <div class='img'>
 *               <img ... avatar image ... />
 *             </div>
 *
 *             <!-- item:data:userId:id -->
 *             <span class='name'> userName </span>
 *           </a>
 *         </div>
 *
 *         <!-- Owner controls -->
 *         <div class='control'>
 *           <a class='item-edit' ...>EDIT</a> |
 *           <a class='item-delete' ...>DELETE</a>
 *
 *           <a class='item-save' ...>SAVE</a>
 *         </div class='control'>
 *
 *         <!-- Item Name: item:data:itemName -->
 *         <h4 class='itemName'> <a ...> title </a> </h4>
 *
 *         <!-- Item Url: item:data:url -->
 *         <div class='url'><a ..> url </a></div>
 *
 *         <!-- Item Description: item:data:description -->
 *         <div class='description'>
 *
 *           <!-- Item Description: item:data:description:summary -->
 *           <div class='summary'> description summary </div>
 *
 *           <!-- Item Description: item:data:description:full -->
 *           <div class='full'> description full </div>
 *         </div class='description'>
 *
 *         <!-- Item Tags: item:data:tags -->
 *         <ul class='tags'>
 *           <li class='tag'><a ...> tag </a></li>
 *           ...
 *         </ul>
 *
 *         <!-- Item Dates: item:data:dates -->
 *         <div class='dates'>
 *
 *           <!-- item:data:dates:tagged -->
 *           <div class='tagged'> tagged date </div>
 *
 *           <!-- item:data:dates:updated -->
 *           <div class='updated'> updated date </div>
 *         </div>
 *       </div>
 *     </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.bookmark", {
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

        /* A change callback
         *      function(data)
         *          return true  to allow the change
         *          return false to abort the change
         */
        change:     null,

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

        // Widget state
        enabled:    true
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'enabled'
     *      'disabled'
     *      'deleted'
     */
    _create: function()
    {
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

        /********************************
         * Locate the pieces
         *
         */
        self.$userId      = self.element.find('input[name=userId]');
        self.$itemId      = self.element.find('input[name=itemId]');
        self.$name        = self.element.find('.itemName a');
        self.$description = self.element.find('.description');

        self.$rating      = self.element.find('.rating .stars .owner');
        self.$favorite    = self.element.find('input[name=isFavorite]');
        self.$private     = self.element.find('input[name=isPrivate]');

        self.$dates       = self.element.find('.dates');
        self.$dateTagged  = self.$dates.find('.tagged');
        self.$dateUpdated = self.$dates.find('.updated');

        //self.$tags        = self.element.find('input[name=tags]');
        self.$tags        = self.element.find('.tags');
        self.tagTmpl      = self.$tags.find('.tag-template').html();

        self.$edit        = self.element.find('.control .item-edit');
        self.$delete      = self.element.find('.control .item-delete');
        self.$save        = self.element.find('.control .item-save');

        self.$url         = self.element.find('.itemName a,.url a');

        /********************************
         * Localize dates
         *
         */
        self._localizeDates();

        /********************************
         * Instantiate our sub-widgets
         *
         */

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


        /********************************
         * Initialize our state and bind
         * to interesting events.
         *
         */
        self._setState();
        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function()
    {
        var self    = this;
        var opts    = self.options;

        self._squelch = false;

        // Handle a direct click on one of the status indicators
        var _update_item      = function(e, data) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            self._performUpdate();
        };

        // Handle item-edit
        var _edit_click  = function(e) {
            if (self.options.enabled === true)
            {
                // Popup a dialog with a post form for this item.
                var formUrl = self.$edit.attr('href')
                            +   '&format=partial'
                            +   '&part=main';
                            //+   '&excludeSuggestions=true';

                $.get(formUrl,
                      function(data) {
                        self._showBookmarkDialog(data, true /*isEdit*/);
                      });
            }

            e.preventDefault();
            e.stopPropagation();
        };

        // Handle item-delete
        var _delete_click  = function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            if (self.options.enabled !== true)
            {
                return;
            }
            self.disable();

            self.$delete.confirmation({
                question:   'Really delete?',
                confirmed:  function() {
                    self._performDelete();
                },
                closed:     function() {
                    self.enable();
                }
            });
        };

        // Handle item-save
        var _save_click  = function(e) {
            if (self.options.enabled === true)
            {
                // Popup a dialog with a post form for this item.
                var formUrl = self.$save.attr('href')
                            +   '&format=partial'
                            +   '&part=main';

                $.get(formUrl,
                      function(data) {
                        self._showBookmarkDialog(data);
                      });
            }

            e.preventDefault();
            e.stopPropagation();
        };

        /**********************************************************************
         * bind events
         *
         */

        /*
        self.$favorite.bind('click.bookmark', _update_item);
        self.$private.bind('click.bookmark',  _update_item);
        self.$rating.bind('click.bookmark',   _update_item);
        */

        self.element.bind('change.bookmark',    _update_item);

        self.$edit.bind('click.bookmark',       _edit_click);
        self.$delete.bind('click.bookmark',     _delete_click);
        self.$save.bind('click.bookmark',       _save_click);
    },


    /** @brief  Given a date/time string, localize it to the client-side
     *          timezone.
     *  @param  utcStr      The date/time string in UTC and in the form:
     *                          YYYY-MM-DD HH:mm:ss
     *
     *  @return The localized time string.
     */
    _localizeDate: function(utcStr, groupBy)
    {
        var self        = this;
        groupBy         = (groupBy === undefined
                            ? self.$dates.data('groupBy')
                            : groupBy);

        var timeOnly    = ((groupBy === undefined) ||
                           (groupBy.indexOf(utcStr.substr(0,10)) < 0)
                            ? false // NOT timeOnly
                            : true  // timeOnly
        );

        return $.date2str( $.str2date( utcStr ), timeOnly );
    },
    // */

    /** @brief  Update presented dates to the client-side timezone.
     */
    _localizeDates: function()
    {
        var self    = this;
        var groupBy = self.$dates.data('groupBy');
        var utcStr;
        var newStr;
        var timeOnly;

        if (self.$dateTagged.length > 0)
        {
            newStr = self._localizeDate(self.$dateTagged.data('utcdate'),
                                        groupBy);

            self.$dateTagged.html( newStr );
        }

        if (self.$dateUpdated.length > 0)
        {
            newStr = self._localizeDate(self.$dateUpdated.data('utcdate'),
                                        groupBy);

            self.$dateUpdated.html( newStr );
        }
    },

    _performDelete: function( )
    {
        var self    = this;
        var opts    = self.options;
        var params  = {
            id:     { userId: opts.userId, itemId: opts.itemId }
        };
        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'bookmark.delete', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'Bookmark delete failed',
                        text:  '<p class="error">'
                             +   (data ? data.error.message : '')
                             + '</p>'
                    });

                    return;
                }

                // Trigger a deletion event for our parent
                self._trigger('deleted');
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'Bookmark delete failed',
                    text:  '<p class="error">'
                         +   textStatus
                         + '</p>'
                });
            },
            complete:   function(req, textStatus) {
            }
         });
    },

    _performUpdate: function()
    {
        var self    = this;
        var opts    = self.options;

        if ((opts.enabled !== true) || (self._squelch === true))
        {
            return;
        }

        // Gather the current data about this item.
        var nonEmpty    = false;
        var params      = {
            id: {
                userId: opts.userId,
                itemId: opts.itemId
            }
        };

        // Only include those portions that have changed
        if (self.$name.text() !== opts.name)
        {
            params.name = self.$name.text();
            nonEmpty    = true;
        }

        if (self.$description.text() !== opts.description)
        {
            params.description = self.$description.text();
            nonEmpty           = true;
        }

        /* Tags are currently NOT directly editable, so ignore them for now.
        if ( (self.$tags.length > 0) &&
             (self.$tags.text() !== opts.tags) )
        {
            params.tags = self.$tags.text();
            nonEmpty    = true;
        }
        // */

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

        if (self.$url.attr('href') !== opts.url)
        {
            // The URL has changed -- pass it in
            params.url = self.$url.attr('href');
            nonEmpty   = true;
        }

        if (nonEmpty !== true)
        {
            return;
        }

        $.log('connexions.bookmark::_performUpdate()');

        /* If there is a 'change' callback, invoke it.
         *
         * If it returns false, terminate the change.
         */
        if ($.isFunction(self.options.change))
        {
            if (! self.options.change(params))
            {
                // Rollback state.
                self._resetState();

                return;
            }
        }

        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'bookmark.update', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'Bookmark update failed',
                        text:  '<p class="error">'
                             +   (data ? data.error.message : '')
                             + '</p>'
                    });

                    // rollback state
                    self._resetState();
                    return;
                }

                if (data.result === null)
                {
                    return;
                }

                self._refreshBookmark(data.result);
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'Bookmark update failed',
                    text:  '<p class="error">'
                         +   textStatus
                         + '</p>'
                });

                // rollback state
                self._resetState();
            },
            complete:   function(req, textStatus) {
            }
         });
    },

    /** @brief  Given new bookmark data from either an update or an edit,
     *          refresh the bookmark presentation from the provided data.
     *  @param  data    Result data representing the bookmark.
     */
    _refreshBookmark: function(data)
    {
        var self    = this;
        var opts    = self.options;

        self._squelch = true;

        // Include the updated data
        self.$itemId.val( data.itemId );
        self.$name.text(  data.name );

        // Update description (both full and summary if they're presented)
        var $desc_full  = self.$description.find('.full');
        var $desc_sum   = self.$description.find('.summary');
        if ($desc_sum.length > 0)
        {
            // summarize will perform an $.htmlentities() on the result.
            $desc_sum.html( '&mdash; '+ $.summarize( data.description ) );
        }
        if ($desc_full.length > 0)
        {
            $desc_full.html( $.esc(data.description) );
        }

        // Update tags
        if ($.isArray(data.tags) && (self.tagTmpl.length > 0))
        {
            /* Update the tag using the '.tag-template' DOM element that SHOULD
             * have been found in the tags area.
             */
            var tagHtml = '';

            $.each(data.tags, function() {
                tagHtml += self.tagTmpl.replace(/%tag%/g, this);
            });

            // Replace the existing tags with the new.
            self.$tags.html( tagHtml );
        }

        self.$rating.stars('select',data.rating);

        self.$favorite.checkbox((data.isFavorite ? 'check' : 'uncheck') );
        self.$private.checkbox( (data.isPrivate  ? 'check' : 'uncheck') );
        self.$url.attr('href',  data.url);

        // Update and localize the dates
        self.$dateTagged.data( 'utcdate', data.taggedOn  );
        self.$dateUpdated.data('utcdate', data.updatedOn );
        self._localizeDates();

        // Alter our parent to reflect 'isPrivate'
        var parent  = self.element.parent();
        if (data.isPrivate)
        {
            parent.addClass('private');
        }
        else
        {
            parent.removeClass('private');
        }
        self._squelch = false;

        // set state
        self._setState();

        // Animate a highlight of this bookmark
        self.element.effect('highlight', {}, 2000);
    },

    _showBookmarkDialog: function(html, isEdit)
    {
        var self    = this;
        var opts    = self.options;
        var title   = (isEdit === true ? 'Edit' : 'Save')
                    + ' bookmark';
        var dialog  = '<div>'      // dialog {
                    +  '<div class="ui-validation-form">'  // validation-form {
                    +   '<div class="userInput lastUnit">'
                           // bookmarkPost HTML goes here
                    +   '</div>'
                    +  '</div>'                            // validation-form }
                    + '</div>';    // dialog }

        var $pane   = self.element.parents('.pane:first');
        var $html   = $(dialog).hide()
                               .appendTo( 'body' );
        var $dialog = $html.first();

        /* Establish an event delegate for the 'isEditChanged' event BEFORE
         * evaluating the incoming HTML 
         */
        $dialog.delegate('form', 'isEditChanged.bookmark', function() {
            // Update the dialog header
            isEdit = $dialog.find('form:first')
                            .bookmarkPost('option', 'isEdit');
            title  = (isEdit === true ? 'Edit YOUR' : 'Save')
                   + ' bookmark';
            if ($dialog.data('dialog'))
            {
                // Update the dialog title
                $dialog.dialog('option', 'title', title);
            }
        });

        /* Now, include the incoming bookmarkPost HTML -- this MAY cause the
         * 'isEditChanged' event to be fired if the widget finds that the
         * URL is already bookmarked by the current user.
         */
        $dialog.find('.userInput').html( html );
        var $form       = $dialog.find('form:first');
        var isModal     = false;
        var $overlayed  = $('body');

        self.disable();

        $dialog.dialog({
            autoOpen:   true,
            title:      title,
            dialogClass:'ui-dialog-bookmarkPost',
            width:      480,
            resizable:  false,
            modal:      isModal,
            open:       function(event, ui) {
                $overlayed.overlay($dialog.maxZindex() - 2);

                // Event bindings that can wait
                $form.bind('saved.bookmark', function(e, data) {
                    if (isEdit === true)
                    {
                        /* Update the presented bookmark with the newly
                         * saved data.
                         */
                        self._refreshBookmark(data);

                        // We've handled this event, so stop it.
                        e.stopPropagation();
                    }
                    else if ($pane.length > 0)
                    {
                        /* Pass this event into OUR widget so it can propagate
                         * up OUR heirarchy as well
                         */
                        self.element.trigger('saved', data);
                    }
                });

                $form.bind('complete.bookmark', function() {
                    $dialog.dialog('close');
                });
            },
            close:      function(event, ui) {
                $overlayed.unoverlay();

                $form.unbind('.bookmark')
                     .bookmarkPost('destroy');
                $dialog.dialog('destroy');
                $html.remove();
                self.enable();
            }
        });

    },

    _setState: function()
    {
        // Set the current widget state to the values of it's sub-components
        var self    = this;
        var opts    = self.options;

        opts.userId      = self.$userId.val();
        opts.itemId      = self.$itemId.val();
        opts.name        = self.$name.text();
        opts.description = self.$description.text();

        if (self.$rating.length > 0)
        {
            opts.rating  = self.$rating.stars('value');
        }

        opts.isFavorite  = self.$favorite.checkbox('isChecked');
        opts.isPrivate   = self.$private.checkbox('isChecked');

        opts.url         = self.$url.attr('href');
    },

    _resetState: function()
    {
        // Reset the values of the sub-components to the current widget state
        var self    = this;
        var opts    = self.options;

        // Squelch change-triggered item updates.
        self._squelch = true;

        self.$name.text(opts.name);
        self.$description.text(opts.description);

        if (self.$rating.length > 0)
        {
            self.$rating.stars('select', opts.rating);
        }

        self.$favorite.checkbox( (opts.isFavorite
                                    ? 'check'
                                    : 'uncheck') );
        self.$private.checkbox( (opts.isPrivate
                                    ? 'check'
                                    : 'uncheck') );

        self.$url.attr('href', opts.url);

        self._squelch = false;
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

        if (! self.options.enabled)
        {
            self.options.enabled = true;
            self.element.removeClass('ui-state-disabled');

            self.$favorite.checkbox('enable');
            self.$private.checkbox('enable');
            self.$rating.stars('enable');

            self._trigger('enabled', null, true);
        }
    },

    disable: function()
    {
        var self    = this;
        var opts    = self.options;

        if (self.options.enabled)
        {
            self.options.enabled = false;
            self.element.addClass('ui-state-disabled');

            self.$favorite.checkbox('disable');
            self.$private.checkbox('disable');
            self.$rating.stars('disable');

            self._trigger('disabled', null, true);
        }
    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Unbind events
        self.$favorite.unbind('.bookmark');
        self.$private.unbind('.bookmark');
        self.$rating.unbind('.bookmark');
        self.$edit.unbind('.bookmark');
        self.$delete.unbind('.bookmark');
        self.$save.unbind('.bookmark');

        // Remove added elements
        self.$favorite.checkbox('destroy');
        self.$private.checkbox('destroy');
        self.$rating.stars('destroy');
    }
});


}(jQuery));

