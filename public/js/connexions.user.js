/** @file
 *
 *  Javascript interface/wrapper for the presentation of a single user.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered user item (View_Helper_HtmlUsersUser):
 *      - allow in-line, on demand editing of the user if it has a
 *        '.control .item-edit' link;
 *      - allow in-line, on demand deletion of the user if it has a
 *        '.control .item-delete' link;
 *
 *  View_Helper_HtmlUsersUser will generate HTML for a user similar to:
 *     <form class='user'>
 *       <input type='hidden' name='userId' value='...' />
 *
 *       <!-- Stats: item:stats -->
 *       <div class='stats'>
 *
 *         <!-- item:stats:countItems -->
 *         <a class='countItems' ...> count </a>
 *
 *         <!-- item:stats:countTags -->
 *         <a class='countTags' ...> count </a>
 *       </div>
 *
 *       <!-- User Data: item:data -->
 *       <div class='data'>
 *
 *         <!-- item:data:avatar -->
 *         <div class='avatar'>
 *           <div class='img'>
 *             <img ... avatar image ... />
 *           </div>
 *         </div>
 *
 *         <!-- item:data:relation -->
 *         <div class='relation'>
 *           <div class='%relation%'>%relationStr%</div>
 *         </div>
 *
 *         <!-- item:data:userId -->
 *         <div class='userId'>
 *           <a ...> user-name </a>
 *         </div>
 *
 *         <!-- item:data:fullName -->
 *         <div class='fullName'>
 *           <a ...> user's full name </a>
 *         </div>
 *
 *         <!-- item:data:email -->
 *         <div class='email'>
 *           <a ...> user's email address </a>
 *         </div>
 *
 *         <!-- item:data:tags : top 5 tags -->
 *         <ul class='tags'>
 *           <li class='tag'><a ...> tag </a></li>
 *           ...
 *         </ul>
 *
 *         <!-- Item Dates: item:data:dates -->
 *         <div class='dates'>
 *
 *           <!-- item:data:dates:lastVisit -->
 *           <div class='lastVisit'> lastVisit date </div>
 *         </div>
 *       </div>
 *     </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.confirmation.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.user", {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Widget state (mirrors Model_User)
        userId:     null,
        name:       null,
        fullName:   null,
        email:      null,
        apiKey:     null,
        pictureUrl: null,
        profile:    null,
        lastVisit:  null,

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
        self.$name        = self.element.find('.userId a');
        self.$fullName    = self.element.find('.fullName');
        self.$email       = self.element.find('.email a');

        self.$dates       = self.element.find('.dates');
        self.$lastVisit   = self.$dates.find('.lastVisit');

        self.$relation    = self.element.find('.relation');
        self.$add         = self.element.find('.control > .item-add');
        self.$remove      = self.element.find('.control > .item-delete');

        /********************************
         * Instantiate our sub-widgets
         *
         */

        /********************************
         * Localize dates
         *
         */
        self._localizeDates();

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
        var _update_user      = function(e, data) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            self._performUpdate();
        };

        // Handle item-edit
        var _add_click  = function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            if (self.options.enabled !== true)
            {
                return;
            }
            self.disable();

            // Add this user to our network
            self._performAdd();

            self.enable();
        };

        // Handle item-delete
        var _remove_click  = function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            if (self.options.enabled !== true)
            {
                return;
            }
            self.disable();

            self.$remove.confirmation({
                question:   'Really delete?',
                confirmed:  function() {
                    self._performDelete();
                },
                closed:     function() {
                    self.enable();
                }
            });
        };

        /**********************************************************************
         * bind events
         *
         */

        self.element.bind('change.user',    _update_user);

        self.$add.bind(   'click.user',     _add_click);
        self.$remove.bind('click.user',     _remove_click);
    },

    _performAdd: function( )
    {
        var self    = this;
        var opts    = self.options;

        var params  = {
            users:  opts.name   //opts.userId
        };

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.addToNetwork', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'User addition failed',
                        text:  '<p class="error">'
                             +   (data ? data.error.message : '')
                             + '</p>'
                    });

                    return;
                }

                if (data.result === null)   { return; }

                if (data.result[opts.name] !== true)
                {
                    $.notify({
                        title: 'User addition failed',
                        text:  '<p class="error">'
                             +   (data ? data.result[opts.name] : '')
                             + '</p>'
                    });

                    return;
                }

                $.notify({
                    title: 'User added',
                    text:  opts.name
                });

                // Adjust the relation information.
                self._updateRelation('add');
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'User addition failed',
                    text:  '<p class="error">'
                         +   textStatus
                         + '</p>'
                });
            },
            complete:   function(req, textStatus) {
            }
         });
    },

    _performDelete: function( )
    {
        var self    = this;
        var opts    = self.options;

        var params  = {
            users:  opts.name
        };

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.removeFromNetwork', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'User removal failed',
                        text:  '<p class="error">'
                             +   (data ? data.error.message : '')
                             + '</p>'
                    });

                    return;
                }

                if (data.result === null)   { return; }

                if (data.result[opts.name] !== true)
                {
                    $.notify({
                        title: 'User removal failed',
                        text:  '<p class="error">'
                             +   (data ? data.result[opts.name] : '')
                             + '</p>'
                    });

                    return;
                }

                $.notify({
                    title: 'User removed',
                    text:  opts.name
                });

                // Adjust the relation information.
                self._updateRelation('remove');

                // Trigger a 'deleted' event for our parent.
                self._trigger('deleted');
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'User removal failed',
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
            id: { userId: opts.userId }
        };

        if (self.$name.text() !== opts.name)
        {
            params.name = self.$name.text();
            nonEmpty    = true;
        }

        if (self.$fullName.text() !== opts.fullName)
        {
            params.fullName = self.$fullName.text();
            nonEmpty        = true;
        }

        if (self.$email.text() !== opts.email)
        {
            params.email = self.$email.text();
            nonEmpty     = true;
        }

        if (nonEmpty !== true)
        {
            return;
        }

        $.log('connexions.user::_performUpdate()');

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

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.update', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'User update failed',
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

                self._squelch = true;

                // Include the updated data
                self.$name.text(            data.result.name );
                self.$fullName.text(        data.result.fullName );

                self.$email.text(           data.result.email);
                self.$email.attr('href',    'mailto:'+ data.result.email);

                // Update and localize the dates
                self.$lastVisit.data('utcdate', data.result.lastVisit);
                self._localizeDates();

                self._squelch = false;

                // set state
                self._setState();
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'User update failed',
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

    /** @brief  Update the relation indicator as well as the controls
     *          based upon a successful add or remove operation.
     *  @param  op      The operation which succeeded ( 'add' | 'remove' );
     */
    _updateRelation: function(op) {
        var self            = this;
        var opts            = self.options;
        // self, mutual, amIn, isIn
        var prevRelation    = self.$relation.data('relation');

        /* The prevRelation should be one of:
         *  mutual  (amIn / isIn)
         *  isIn    (following)
         *
         *  'self' and 'amIn' SHOULD NOT be seen here since relation controls
         *  SHOULD be hidden/inactive in those cases.
         */
        if (op === 'add')
        {
            /* Added to the authenticated users network
             *
             * Transition:
             *  mutual:     *no change*
             *  none:       isIn
             *  amIn:       mutual
             *
             * In either case, deactivate the 'add' and active the 'del'
             * controls.
             */
            if (prevRelation === 'none')
            {
                var title   = 'following';
                self.$relation.attr('title', title)
                               .data('relation', 'isIn');
                self.$relation.find('.relation-none')
                              .removeClass('relation-none')
                              .addClass('relation-isIn')
                              .text(title);
            }
            else if (prevRelation === 'amIn')
            {
                var title   = 'mutual followers';
                self.$relation.attr('title', title)
                               .data('relation', 'mutual');
                self.$relation.find('.relation-amIn')
                              .removeClass('relation-amIn')
                              .addClass('relation-mutual')
                              .text(title);
            }

            self.$add.attr('disabled', true)
                     .hide();
            self.$remove
                     .removeAttr('disabled')
                     .show();
        }
        else
        {
            /* Deleted from the authenticated users network
             *
             * Transition:
             *  mutual:     amIn
             *  isIn:       none
             *
             * In either case, deactivate the 'del' and active the 'add'
             * controls.
             */
            if (prevRelation === 'mutual')
            {
                var title   = 'follower';
                self.$relation.attr('title', title)
                              .data('relation', 'amIn');
                self.$relation.find('.relation-mutual')
                              .removeClass('relation-mutual')
                              .addClass('relation-amIn')
                              .text(title);
            }
            else if (prevRelation === 'isIn')
            {
                var title   = 'no relation';
                self.$relation.attr('title', title)
                              .data('relation', 'none');
                self.$relation.find('.relation-isIn')
                              .removeClass('relation-isIn')
                              .addClass('relation-none')
                              .text(title);
            }

            self.$remove
                     .attr('disabled', true)
                     .hide();
            self.$add.removeAttr('disabled')
                     .show();
        }
    },

    _setState: function()
    {
        // Set the current widget state to the values of it's sub-components
        var self    = this;
        var opts    = self.options;

        opts.userId      = self.$userId.val();
        opts.name        = self.$name.text();
        opts.fullName    = self.$fullName.text();
        opts.email       = self.$email.text();
    },

    _resetState: function()
    {
        // Reset the values of the sub-components to the current widget state
        var self    = this;
        var opts    = self.options;

        // Squelch change-triggered item updates.
        self._squelch = true;

        self.$name.text(opts.name);
        self.$fullName.text(opts.fullName);

        self.$email.text(        opts.email);
        self.$email.attr('href', 'mailto:'+ opts.email);

        self._squelch = false;
    },

    /** @brief  Given a date/time string, localize it to the client-side
     *          timezone.
     *  @param  utcStr      The date/time string in UTC and in the form:
     *                          YYYY-MM-DD HH:mm:ss
     *
     *  @return The localized time string.
     */
    _localizeDate: function(utcStr)
    {
        return $.date2str( $.str2date( utcStr ) );
    },

    /** @brief  Update presented dates to the client-side timezone.
     */
    _localizeDates: function()
    {
        var self    = this,
            newStr;

        if (self.$lastVisit.length > 0)
        {
            newStr = self._localizeDate(self.$lastVisit.data('utcdate'));
            self.$lastVisit.html( newStr );
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

        if (! self.options.enabled)
        {
            self.options.enabled = true;
            self.element.removeClass('ui-state-disabled');

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

            self._trigger('disabled', null, true);
        }
    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Unbind events
        self.$add.unbind('.user');
        self.$remove.unbind('.user');

        // Remove added elements
    }
});


}(jQuery));

