/** @file
 *
 *  Javascript interface/wrapper for the management of multiple credentials.
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
 *      settings.credential.js
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
                                    .find('li:has(input[name^=credential])');


        // Add item
        opts.$add       = self.element.find('.add');

        /********************************
         * Instantiate any sub-widgets
         * 
         */
        opts.$credentials.each(function() {
            self._activateCredential( $(this) );
        });


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

        var _add_item       = function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            $.log('settings.credentials::_add_item()');

            self.addItem();
        };
        var _status         = function(e, isSuccess, title, text) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            self._status(isSuccess, title, text);
        };

        /**********************************************************************
         * bind events
         *
         */
        opts.$submit.bind('click.settingsCredentials',  _save_click);
        opts.$add.bind('click.settingsCredentials',     _add_item);
        self.element.bind('status.settingsCredentials', _status);
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
            var $cred   = $(this);

            if (! $cred.credential('hasChanged'))
            {
                return;
            }

            params.credentials.push( $cred.credential('values') );
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

    _activateCredential: function($cred)
    {
        var self    = this;
        var opts    = self.options;

        $cred.credential({jsonRpc: opts.jsonRpc,
                          apiKey:  opts.apiKey});

        $cred.bind('remove.settingsCredentials', function() {
            self._deactivateCredential( $cred );
        });
    },

    _deactivateCredential: function($cred)
    {
        var self    = this;
        var opts    = self.options;

        $cred.credential('destroy');

        // Refresh the list of credentials
        opts.$credentials = self.element
                                    .find('li:has(input[name^=credential])');

        // Re-bind to account for the destroyed inputs
        self.rebind();  //$.ui.validationForm.prototype.rebind.call(this);
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
        var valids  = $.settings.credential.prototype.options.validTypes;
        var self    = this;
        var opts    = self.options;
        var cur     = valids[0];
        var html    = "<li class='new'>"
                    +  "<div class='field typeSelection'>"
                    +   "<div class='type current "+ cur +"'>"+ cur +"</div>"
                    +   "<div class='control ui-icon ui-icon-triangle-1-s'>"
                    +    "&nbsp;"
                    +   "</div>"
                    +   "<div class='options ui-corner-all ui-state-default'>";

        $.each(valids, function(idex, val) {
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

        // Activate the new item.
        self._activateCredential($div);

        // Refresh theh list of credentials
        opts.$credentials = self.element
                                    .find('li:has(input[name^=credential])');

        // Re-bind to take into account the new inputs
        self.rebind();  //$.ui.validationForm.prototype.rebind.call(this);
    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Cleanup

        // Unbind events
        opts.$submit.unbind('.settingsCredentials');
        opts.$add.unbind('.settingsCredentials');

        // Remove added elements

        // Invoke our superclass
        $.ui.validationForm.prototype.destroy.call(this);
    }
}));

}(jQuery));

