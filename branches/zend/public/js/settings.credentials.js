/** @file
 *
 *  Javascript interface/wrapper for credential management.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-renderd credentials form
 *      (application/views/scripts/settings/main-account-credentials.phtml)
 *
 *  <ul class='credentials'>
 *   <form>
 *    <li>
 *     <input type='hidden' name='userAuthId[]' ... />
 *     <div class='type'>
 *      <div class=' %authType% ' title=' %authType% '> %authType% </div>
 *     </div>
 *     <div class='field name'>
 *      <label  for='name[]'>Name</label>
 *      <input name='name[]' type='text' class='text' />
 *     </div>
 *     <div class='field credential'>
 *      <label  for='credential[]'>Credential</label>
 *      <input name='credential[]' type='text' class='text' />
 *     </div>
 *    </li>
 *    ...
 *
 *   </form>
 *  </ul>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      connexions.collapsable
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("settings.credentials", $.extend({}, $.ui.validationForm.prototype, {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Widget state (mirrors Model_UserAuth)
        userAuthId: null,

        authType:   null,
        name:       null,
        credential: null,

        validTypes: [ 'openid', 'password', 'pki' ],

        /* An element or element selector to be used to present general status
         * information.  If not provided, $.notify will be used.
         */
        $status:    null,

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
        apiKey:     null
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

        self._initialized = false;

        // Mix-in the superclass options
        self.options = $.extend({}, $.ui.validationForm.prototype.options,
                                    opts);

        // Invoke our superclass
        $.ui.validationForm.prototype._create.call(this);

        opts = self.options;

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
         * Locate pieces not collected
         * by our superclass.
         */
        opts.$credentials = self.element
                                    .find('li:has(input[name^=userAuthId])');


        // Hidden fields
        opts.$usrAuthId   = self.element.find('input[name=userAuthId]');

        // Delete and Add items
        opts.$deletes   = self.element.find('.delete');
        opts.$add       = self.element.find('.add');

        /********************************
         * Instantiate any sub-widgets
         * 
         */


        self._initialized = true;

        /********************************
         * Bind to interesting events.
         *
         */
        self._bindEvents();

        self.element.show();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function()
    {
        var self    = this;
        if (self._initialized !== true)
        {
            return;
        }

        // Invoke our superclass
        $.ui.validationForm.prototype._bindEvents.call(this);

        var opts    = self.options;

        // Handle a direct click on one of the status indicators
        var _save_click       = function(e, data) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            $.log('settings.credentials::_save_click('+ data +')');

            self._performUpdate();

            return false;
        };

        self.__delete_item    = function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            $.log('settings.credentials::__delete_item()');

            self.deleteItem(this);
        };

        var _add_item       = function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            $.log('settings.credentials::_add_item()');

            self.addItem();
        };

        /**********************************************************************
         * bind events
         *
         */
        opts.$submit.bind('click.settingsCredentials',  _save_click);
        opts.$deletes.bind('click.settingsCredentials', self.__delete_item);
        opts.$add.bind('click.settingsCredentials',     _add_item);
    },

    _performUpdate: function()
    {
        var self    = this;
        var opts    = self.options;

        if (opts.enabled !== true)
        {
            return;
        }

        var params      = {
            credentials:    []
        };

        // Fill in all credentials that have changes or are new.
        opts.$credentials.each(function() {
            var $el     = $(this);
            var $type   = $el.find('.type');
            var $name   = $el.find('.name');
            var $cred   = $el.find('.credential');

            if ( (! $type.hasClass('new'))     &&
                 (! $name.input('hasChanged')) &&
                 (! $cred.input('hasChanged')) )
            {
                return;
            }

            params.credentials.push( {
                userAuthId: $el.find('[name^=userAuthId]').val(),
                type:       $type.val(),
                name:       $name.val(),
                credential: $name.val()
            });
        });

        if (params.credentials.length < 1)
        {
            // No changes
            self._trigger('complete');
            return;
        }

        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        self.element.mask();

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.updateCredentials', params, {
            success:    function(data, textStatus, req) {
                if (data.error !== null)
                {
                    self._status(false,
                                 'Credential update failed',
                                 data.error.message);

                    return;
                }

                self._status(true,
                             'Credential update succeeded',
                             params.credentials.length
                             + ' Credential'
                             + (params.credentials.length === 1
                                    ? ''
                                    : 's')
                             + 'updated.');

                if (data.result === null)
                {
                    return;
                }

                /* :TODO: Convert any 'new' credentials, which should have a
                 *        select box for type, to "real" credentials with no
                 *        select box.
                 */

                // "Save" notification
                self._trigger('saved',    null, data.result);
                self._trigger('complete');
            },
            error:      function(req, textStatus, err) {
                self._status(false,
                             'Credential update failed',
                             textStatus);

                // :TODO: "Error" notification??
            },
            complete:   function(req, textStatus) {
                self.element.unmask();
            }
         });
    },

    _status: function(isSuccess, title, text)
    {
        var self    = this;
        var opts    = self.options;

        if ( (opts.$status === null) || (opts.$status.length < 1) )
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

    /** @brief  Request the deletion of an existing credential
     *  @param  $cred   The jQuery DOM element representing the credential to
     *                  delete.
     */
    _deleteCredential: function($cred)
    {
        var self    = this;
        var opts    = self.options;
        var params  = {
            userAuthId: $cred.find('[name^=userAuthId]').val()
        };

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.deleteCredential', params, {
            success:    function(data, textStatus, req) {
                if (data.error !== null)
                {
                    self._status(false,
                                 'Credential deletion failed',
                                 data.error.message);

                    return;
                }

                self._status(true,
                             'Credential deletion succeeded',
                             'Credential deleted');

                $cred.remove();

                // "Save" notification
                self._trigger('saved',    null, data.result);
                self._trigger('complete');
            },
            error:      function(req, textStatus, err) {
                self._status(false,
                             'Credential deletion failed',
                             textStatus);

                // :TODO: "Error" notification??
            },
            complete:   function(req, textStatus) {
                self.element.unmask();
            }
         });
    },

    _activateCredential: function($cred)
    {
        var self    = this;
        var opts    = self.options;
        var $inputs = $cred.find(  'input[type=text],'
                                 + 'input[type=password],'
                                 + 'textarea');
        var $delete = $cred.find('.delete');
        var $sel    = $cred.find('.typeSelection');

        // Activate the widgets
        $inputs.input({hideLabel: opts.hideLabels});

        // Bind events
        $delete.bind('click.settingsCredentials', self.__delete_item);

        if ($sel.length > 0)
        {
            var $cur    = $sel.find('.current:first');
            var $ctrl   = $sel.find('.type,.control');
            var $type   = $sel.find('.type');
            var $opts   = $sel.find('.options');
            var $types  = $opts.find('.option');

            $types.each(function() {
                var $el     = $(this);
                var type    = $el.find('.label').text();

                $el.data('type.settingsCredentials', type);
            });

            $cur.data('type.settingsCredentials', $cur.text());

            //'removeNew.settingsCredentials'
            $cred.bind('remove.settingsCredentials', function(e) {
                // Remove this credential, cleaning up as we go
                $types.removeData();
                $cur.removeData();
                $ctrl.unbind('.settingsCredentials');
                $types.unbind('.settingsCredentials');

                self._deactivateCredential($cred);
            });

            $ctrl.bind('click.settingsCredentials', function(e) {
                $opts.slideToggle();
            });
            $types.bind('click.settingsCredentials', function(e) {
                var $el     = $(this);
                var type    = $el.data('type.settingsCredentials');
                var curType = $cur.data('type.settingsCredentials');

                if (type === curType)
                {
                    return;
                }

                // Change the current value class and data
                $cur.removeClass(curType)
                    .addClass(type)
                    .text(type)
                    .data('type.settingsCredentials', type);

                if ((curType === 'password') || (type === 'password'))
                {
                    // We need to change the form field type
                    var $input  = $cred.find('[name^=credential]');
                    var val     = $input.val();
                    var html    = "<input type='"
                                +           (type === 'password'
                                                ? 'password'
                                                : 'text') +"' "
                                +        "name='credential[]' "
                                +       "class='text required' "
                                +       "value='"+ val +"' />";
                    var $new    = $(html);

                    $input.before( $new )
                          .input('destroy')
                          .remove();
                    $new.input({hideLabel: opts.hideLabels});
                }

                $types.removeClass('current');
                $el.addClass('current');

                $opts.slideToggle();
            });
        }

        // Finally, update the inputs and deletes item lists.
        opts.$required = self.element.find('.required');
        opts.$inputs   = self.element.find(  'input[type=text],'
                                           + 'input[type=password],'
                                           + 'textarea');
        opts.$deletes  = self.element.find('.delete');
    },

    _deactivateCredential: function($cred)
    {
        var self    = this;
        var opts    = self.options;
        var $inputs = $cred.find(  'input[type=text],'
                                 + 'input[type=password],'
                                 + 'textarea');
        var $delete = $cred.find('.delete');

        // Destroy the widgets...
        $inputs.input('destroy');

        // Unbind events
        $delete.unbind('.settingsCredentials');

        // Finally, update the inputs and deletes item lists.
        opts.$inputs  = self.element.find(  'input[type=text],'
                                          + 'input[type=password],'
                                          + 'textarea');
        opts.$deletes = self.element.find('.delete');
    },

    /************************
     * Public methods
     *
     */

    /** @brief  Append a new credential entry area.
     *
     */
    addItem: function()
    {
        var self    = this;
        var opts    = self.options;
        var cur     = opts.validTypes[0];
        var html    = "<li class='new'>"
                    +  "<div class='field typeSelection'>"
                    +   "<div class='type current "+ cur +"'>"+ cur +"</div>"
                    +   "<div class='control ui-icon ui-icon-triangle-1-s'>"
                    +    "&nbsp;"
                    +   "</div>"
                    +   "<div class='options ui-corner-all ui-state-default'>";

        $.each(opts.validTypes, function(idex, val) {
            html    +=   "<div class='option ui-state-default"
                    +               (cur === val ? ' current' : '') + "'>"
                    +      "<div class='type "+ this +"'>"+ this +"</div>"
                    +      "<div class='label'>"+ this +"</div>"
                    +    "</div>";
        });

        html        +=  "</div>"
                    +  "</div>\n"
                    +  "<div class='field name'>"
                    +   "<label for='name[]'>Name</label>"
                    +   "<input type='text' "
                    +          "name='name[]' "
                    +         "class='text' "
                    +         "value='' />"
                    +  "</div>\n"
                    +  "<div class='field credential'>"
                    +   "<label for='credential[]'>Credential</label>"
                    +   "<input type='text' "
                    +          "name='credential[]' "
                    +         "class='text required' "
                    +         "value='' />"
                    +  "</div>\n"
                    +  "<div class='control'>"
                    +   "<a class='delete' href='#'>delete</a>"
                    +  "</div>"
                    + "</li>";
        var $div        = $(html);
        var $buttons    = opts.$add.closest('.buttons');

        $buttons.before( $div );

        // :TODO: bind the new item.
        self._activateCredential($div);
    },

    /** @brief  Delete an existing credential (or credential entry area)
     *  @param  item    The targeted delete item.
     *
     */
    deleteItem: function(item)
    {
        var self    = this;
        var opts    = self.options;
        var $el     = $(item);
        var $cred   = $el.closest('li');

        // Present a confirmation dialog.
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

        $el.after( $div );
        $el.attr('disabled', true);

        $div.find('button[name=yes]').click(function(e) {
            $el.removeAttr('disabled');
            $div.remove();

            if ( $cred.hasClass('new'))
            {
                // This was an unsaved, new credential so just remove it.
                $cred.remove(); //trigger('removeNew');
                return;
            }

            // This was an existing credential, so perform a server-side delete
            self._deleteCredential($cred);
        });
        $div.find('button[name=no]').click(function() {
            $el.removeAttr('disabled');
            $div.remove();
        });

    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Cleanup

        // Unbind events
        opts.$submit.unbind('.settingsCredentials');
        opts.$deletes.unbind('.settingsCredentials');
        opts.$add.unbind('.settingsCredentials');

        // Remove added elements

        // Invoke our superclass
        $.ui.validationForm.prototype.destroy.call(this);
    }
}));

}(jQuery));

