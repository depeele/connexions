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
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("ui.bookmark", {
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

        url:        null,

        // taggedOn and updateOn are not user editable

        /* A change callback
         *      function(data)
         *          return true  to allow the change
         *          return false to abort the change
         */
        change:     null,

        // Widget state
        enabled:    true
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'enabled.bookmark'
     *      'disabled.bookmark'
     */
    _create: function()
    {
        var self        = this;
        var opts        = self.options;

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

        self.$edit        = self.element.find('.control .item-edit');
        self.$delete      = self.element.find('.control .item-delete');
        self.$save        = self.element.find('.control .item-save');

        self.$url         = self.element.find('.itemName a,.url a');

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

            $.log('ui.bookmark::_update_item('+ data +')');

            if ((self.options.enabled !== true) || (self._squelch === true))
            {
                return;
            }

            // Gather the current data about this item.
            var data    = {
                userId:     opts.userId,
                itemId:     opts.itemId,
                isFavorite: self.$favorite.checkbox('isChecked'),
                isPrivate:  self.$private.checkbox('isChecked')
            };

            if (self.$name.length > 0)
            {
                data.name = self.$name.text();
            }

            if (self.$description.length > 0)
            {
                data.description = self.$description.text();
            }

            if (self.$rating.length > 0)
            {
                data.rating = self.$rating.stars('value');
            }

            /* If there is a 'change' callback, invoke it.
             *
             * If it returns false, terminate the change.
             */
            if ($.isFunction(self.options.change))
            {
                if (! self.options.change(data))
                {
                    // Rollback state.
                    self._resetState();

                    return;
                }
            }


            /* Perform an AJAJ call to update this item
            $.ajax({
                url:        '/api/v1/bookmark/update.json',
                type:       'POST',
                dataType:   'json',
                data:       data,
                success:    function(data, textStatus, req) {
                    // set state
                    self._setState();
                },
                error:      function(req, textStatus, err) {
                    // rollback state
                    self._resetState();
                },
                complete:   function(req, textStatus) {
                }
             });
             */
        };

        // Handle item-edit
        var _edit_click  = function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (self.options.enabled !== true)
            {
                return;
            }
        };

        // Handle item-delete
        var _delete_click  = function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (self.options.enabled !== true)
            {
                return;
            }
        };

        // Handle save-delete
        var _save_click  = function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (self.options.enabled !== true)
            {
                return;
            }
        };

        /**********************************************************************
         * bind events
         *
         */

        /*
        self.$favorite.bind('click.bookmark', _update_item);
        self.$private.bind('click.bookmark',  _update_item);
        self.$rating.bind('click.bookmark',          _update_item);
        */

        self.element.bind('change.bookmark',    _update_item);

        self.$edit.bind('click.bookmark',       _edit_click);
        self.$delete.bind('click.bookmark',     _delete_click);
        self.$save.bind('click.bookmark',       _save_click);
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

        if (opts.isFavorite)
        {
            self.$favorite.checkbox('check');
        }
        else
        {
            self.$favorite.checkbox('uncheck');
        }

        if (opts.isPrivate)
        {
            self.$private.checkbox('check');
        }
        else
        {
            self.$private.checkbox('uncheck');
        }

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

