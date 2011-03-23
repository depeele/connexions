/** @file
 *
 *  Javascript interface/wrapper for single credential management.
 *
 *  Used from settings.credentials.js, this class handles credential-specific
 *  events and operations.  It relies on validation performed by
 *  settings.credentials/ui.validationForm, expecting all text-based input
 *  items to be ui.input instances BEFORE being instantiated.
 *
 *    <li>
 *     <input type='hidden' name='userAuthId' ... />
 *     <div class='type'>
 *      <div class=' %authType% ' title=' %authType% '> %authType% </div>
 *     </div>
 *     <div class='field name'>
 *      <label  for='name'>Name</label>
 *      <input name='name' type='text' class='text' />
 *     </div>
 *     <div class='field credential'>
 *      <label  for='credential'>Credential</label>
 *      <input name='credential' type='text' class='text' />
 *     </div>
 *    </li>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      connexions.collapsable
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("settings.credential", {
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

        opts = self.options;

        /********************************
         * Initialize jsonRpc
         *
         */
        if ((opts.jsonRpc === null) &&
            ($.isFunction($.registry)) )
        {
            var api = $.registry('api');
            if (api && api.jsonRpc)
            {
                opts.jsonRpc = $.extend({}, api.jsonRpc, opts.jsonRpc);
            }
        }

        opts.hideLabels = (opts.hideLabels === false ? false : true);

        /********************************
         * Locate our parts.
         */
        opts.$userAuthId = self.element.find('input[name^=userAuthId]');
        opts.$type       = self.element.find('.type:first');
        opts.$name       = self.element.find('input[name^=name]');
        opts.$credential = self.element.find('input[name^=credential]');


        // Delete action item
        opts.$delete      = self.element.find('.delete');

        if (! opts.$name.hasClass('ui-input'))
        {
            // Not yet instantiated as ui.input - do it now.
            opts.$name.input({hideLabel: opts.hideLabels});
            opts.$credential.input({hideLabel: opts.hideLabels});
        }

        self._activateNew();

        // Save the initial values
        opts.userAuthId = opts.$userAuthId.val();
        opts.authType   = opts.$type.data('type.settingsCredential');
        opts.name       = opts.$name.val();
        opts.credential = opts.$credential.val();

        /********************************
         * Bind to interesting events.
         *
         */
        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */

    /** @brief  If this credential represents a new credential that has
     *          a type selector, activate that type selector.
     */
    _activateNew: function()
    {
        var self    = this;
        var opts    = self.options;
        var $sel    = self.element.find('.typeSelection');
        
        opts.$type.data('type.settingsCredential', opts.$type.text());

        if ($sel.length < 1)
        {
            self._activatePassword();
            return;
        }

        var $cur    = $sel.find('.current:first');  // === opts.$type
        var $ctrl   = $sel.find('.type,.control');
        var $type   = $sel.find('.type');
        var $opts   = $sel.find('.options');
        var $types  = $sel.find('.option');

        $types.each(function() {
            var $el     = $(this);
            var type    = $el.find('.label').text();

            $el.data('type.settingsCredential', type);
        });

        $ctrl.bind('click.settingsCredential', function(e) {
            $opts.slideToggle();
        });

        $types.bind('click.settingsCredential', function(e) {
            var $el     = $(this);
            var newType = $el.data('type.settingsCredential');
            var curType = opts.$type.data('type.settingsCredential');

            if (newType === curType)
            {
                // No change
                return;
            }

            // Change the current value class and data
            $cur.removeClass(curType)
                .addClass(newType)
                .text(newType)
                .data('type.settingsCredential', newType);

            if ((curType === 'password') || (newType === 'password'))
            {
                // We need to change the form field type
                var $input  = opts.$credential;
                var val     = $input.val();
                var html    = "<input type='"
                            +           (newType === 'password'
                                            ? 'password'
                                            : 'text') +"' "
                            +        "name='credential' "
                            +       "class='text required' "
                            +       "value='"+ val +"' />";
                var $new    = $(html);

                $input.before( $new );
                $input.unbind('.settingsCredential')
                      .input('destroy')
                      .remove();

                $new.input({hideLabel: opts.hideLabels});
                opts.$credential = $new;

                if (newType === 'password')
                {
                    self._activatePassword();
                }

                // Trickle a 'rebind' event up to our parent...
                self._trigger('rebind');
            }

            $types.removeClass('current');
            $el.addClass('current');

            $opts.slideToggle();
        });

        self.element.bind('remove.settingsCredential', function(e) {
            // Remove data and unbind
            $types.removeData();
            opts.$type.removeData();
            $ctrl.unbind('.settingsCredential');
            $types.unbind('.settingsCredential');
        });

        self._activatePassword();
    },

    /** @brief  If this credential represents a password, setup a focus/blur
     *          handler to show/hide the additional fields that are required to
     *          make a change.
     */
    _activatePassword: function()
    {
        var self    = this;
        var opts    = self.options;

        if (opts.$type.data('type.settingsCredential') !== 'password')
        {
            return;
        }


        function validatePasswords($el)
        {
            var res = $.validatePasswords($el, opts.$pw_new1, opts.$pw_new2);
            if (res === true)
            {
                if (opts.$pw_change.data('closing.settingsCredential') !== true)
                {
                    //$.log("validatePassword() MATCH");
                    opts.$credential.input('val', opts.$pw_new1.val() );
                    opts.$pw_change.data('closing.settingsCredential', true);
                    opts.$pw_change.slideUp(400, function() {
                        // Blur the element that cause this validation.
                        $el.blur();
                        opts.$pw_change
                                .removeData('closing.settingsCredential');
                    });
                }
            }

            return res;
        }

        opts.$credential
                .bind('focus.settingsCredential', function() {
                    //opts.$credential.hide();

                    if (opts.$pw_change === undefined)
                    {
                        var html    = "<div class='change'>"
                                    /*
                                    +  "<div class='field pw_current'>"
                                    +   "<label for='pw_current'>"
                                    +    "Current Password"
                                    +   "</label>"
                                    +   "<input type='password' "
                                    +         "class='text required' "
                                    +          "name='pw_current' />"
                                    +  "</div>"
                                    */
                                    +  "<div class='field pw_new'>"
                                    +   "<label for='pw_new1'>"
                                    +    "New Password"
                                    +   "</label>"
                                    +   "<input type='password' "
                                    +         "class='text required' "
                                    +          "name='pw_new1' />"
                                    +  "</div>"
                                    +  "<div class='field pw_new'>"
                                    +   "<label for='pw_new2'>"
                                    +    "Verify New Password"
                                    +   "</label>"
                                    +   "<input type='password' "
                                    +         "class='text required' "
                                    +          "name='pw_new2' />"
                                    +  "</div>"
                                    + "</div>";

                        opts.$pw_change  = $(html).hide();
                        opts.$credential.after( opts.$pw_change );

                        /*
                        opts.$pw_current =
                            opts.$pw_change
                                    .find('.pw_current input[name=pw_current]')
                                    .input({hideLabel:opts.hideLabels});
                        */
                        opts.$pw_new =
                            opts.$pw_change
                                    .find('.pw_new input[name^=pw_new]');

                        opts.$pw_new1 = opts.$pw_new.first();
                        opts.$pw_new2 = opts.$pw_new.last();

                        opts.$pw_new.input({
                                        hideLabel:  opts.hideLabels,
                                        validation: function() {
                                            return validatePasswords( $(this) );
                                        }
                                    });

                        var blurTimer   = null;
                        opts.$pw_new
                                .bind('blur.settingsCredential', function(e) {
                                    /* If, after a short delay, neither of
                                     * the new password fields are the current
                                     * focus, hide the change area.
                                     */
                                    //$.log("pw_new blur...");
                                    blurTimer = setTimeout(function() {
                                        var $focus =
                                            opts.$pw_new
                                                    .filter('.ui-state-focus');

                                        /*
                                        $.log("pw_new blur nFocus[ %s ]...",
                                              $focus.length);
                                        // */
                                        if ($focus.length < 1)
                                        {
                                            // Hide the password change area.
                                            opts.$pw_change.slideUp();
                                        }
                                    }, 200);
                                })
                                .bind('focus.settingsCredential', function(e) {
                                    if (blurTimer !== null)
                                    {
                                        clearTimeout(blurTimer);
                                        blurTimer = null;

                                        /*
                                        $.log("pw_new focus/timer cleared...");
                                        // */
                                    }
                                });
                    }

                    opts.$pw_change.slideDown(400, function() {
                        opts.$credential.blur();
                        opts.$pw_new1.focus();
                    });
                });
    },

    _bindEvents: function()
    {
        var self    = this;
        var opts    = self.options;

        var _delete = function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            //$.log('settings.credential::_delete()');

            self._delete_confirm(this);
        };

        /**********************************************************************
         * bind events
         *
         */
        opts.$delete.bind('click.settingsCredential', _delete);
    },

    /** @brief  Request the deletion of an existing credential
     */
    _perform_delete: function()
    {
        var self    = this;
        var opts    = self.options;
        var params  = {
            credential: opts.$userAuthId.val()
        };

        self.element.mask();

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.deleteCredential', params, {
            success:    function(data, textStatus, req) {
                if ((data === null) || (data.error !== null))
                {
                    self._trigger('status', null, 
                                  [ false,
                                    'Credential deletion failed',
                                    (data === null
                                        ? 'Invalid JSON-RPC structure returned'
                                        : data.error.message) ]);

                    return;
                }

                self._trigger('status', null, 
                              [ true,
                                'Credential deletion succeeded',
                                'Credential deleted' ]);

                self.destroy();
                self.element.remove();

                // "Save" notification
                self._trigger('saved',    null, data.result);
                self._trigger('complete');
            },
            error:      function(req, textStatus, err) {
                self._trigger('status', null, 
                              [ false,
                                'Credential deletion failed',
                                textStatus ]);

                // :TODO: "Error" notification??
            },
            complete:   function(req, textStatus) {
                self.element.unmask();
            }
         });
    },

    /** @brief  Delete an existing credential (or credential entry area)
     *  @param  item    The targeted delete item.
     *
     */
    _delete_confirm: function(item)
    {
        var self    = this;
        var opts    = self.options;
        var $el     = $(item);
        var $cred   = self.element;

        if ($cred.attr('disabled') !== undefined)
        {
            return;
        }
        $cred.attr('disabled', true);

        $el.confirmation({
            question:   'Really delete?',
            //position:   self._confirmationPosition($ctl),
            confirmed:  function() {
                if ( $cred.hasClass('new'))
                {
                    // This was an unsaved, new credential so just remove it
                    $cred.remove();
                    return;
                }

                self._perform_delete();
            },
            closed:     function() {
                $cred.removeAttr('disabled');
            }
        });

        /*
        // Present a confirmation dialog.
        var html    = '<div class="confirm">'
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
                // This was an unsaved, new credential so just remove it
                $cred.remove();
                return;
            }

            // This was an existing credential, so perform a server-side delete
            self._perform_delete();
        });
        $div.find('button[name=no]').click(function() {
            $el.removeAttr('disabled');
            $div.remove();
        });
        // */
    },

    /************************
     * Public methods
     *
     */

    hasChanged: function()
    {
        var self    = this;
        var opts    = self.options;
        var $cred   = self.element;

        return ( ($cred.hasClass('new')                  ||
                  opts.$name.input('hasChanged')         ||
                  opts.$credential.input('hasChanged'))
                    ? true
                    : false );
    },

    reset: function()
    {
        var self    = this;
        var opts    = self.options;

        opts.$name.input('reset');
        opts.$credential.input('reset');
    },

    values: function()
    {
        var self    = this;
        var opts    = self.options;

        // Return the current values
        return { userAuthId: opts.userAuthId,
                 authType:   opts.$type.data('type.settingsCredential'),
                 name:       opts.$name.val(),
                 credential: opts.$credential.val()
        };
    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Cleanup

        // Unbind events
        if (opts.$pw_new)   opts.$pw_new.unbind('.settingsCredential');

        opts.$delete.unbind('.settingsCredential');
        self.element.unbind('.settingsCredential');

        // Remove added elements
        opts.$name.input('destroy');
        opts.$credential.input('destroy');
    }
});

}(jQuery));


