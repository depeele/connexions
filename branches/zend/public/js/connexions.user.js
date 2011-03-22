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

        self.$relation    = self.element.find('.relation');
        self.$edit        = self.element.find('.control > .item-edit');
        self.$delete      = self.element.find('.control > .item-delete');

        /********************************
         * Instantiate our sub-widgets
         *
         */

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
                self._showModifyDialog();
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

            // Present a confirmation dialog and delete.
            var html    = '<div class="confirm">'
                        /*
                        +  '<span class="ui-icon ui-icon-alert" '
                        +        'style="float:left; margin:0 7px 20px 0;">'
                        +  '</span>'
                        */
                        +  'Really delete?<br />'
                        +  '<button name="yes">Yes</button>'
                        +  '<button name="no" >No</button>'
                        + '</div>';
            var $div    = $(html);

            self.$delete.after( $div );
            self.$delete.attr('disabled', true);

            $div.find('button[name=yes]').click(function(e) {
                self.$delete.removeAttr('disabled');
                $div.remove();

                self._performDelete();
            });
            $div.find('button[name=no]').click(function() {
                self.$delete.removeAttr('disabled');
                $div.remove();
            });
        };

        /**********************************************************************
         * bind events
         *
         */

        self.element.bind('change.user',    _update_item);

        self.$edit.bind('click.user',       _edit_click);
        self.$delete.bind('click.user',     _delete_click);
    },

    _performDelete: function( )
    {
        var self    = this;
        var opts    = self.options;

        if (opts.enabled !== true)
        {
            return;
        }

        var params  = {
            id: { userId: opts.userId }
        };

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.delete', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'User delete failed',
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
                    title: 'User delete failed',
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

    _showModifyDialog: function()
    {
        /* :TODO: Create and present a dialog (or in-line editor) for
         *        modification
         */
        return;
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
        self.$edit.unbind('.user');
        self.$delete.unbind('.user');

        // Remove added elements
    }
});


}(jQuery));

