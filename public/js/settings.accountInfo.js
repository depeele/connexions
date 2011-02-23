/** @file
 *
 *  Javascript interface/wrapper to handle general account information
 *  presentation and activation.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-renderd account-info form
 *      (application/views/scripts/settings/main-account-info.phtml)
 *
 *  <div id='account-info'>
 *   <form class='ui-validation-form'>
 *    <div class='legend'><!-- legend { -->
 *     <div class='avatar'>
 *      <div class='connexions_sprites user_bg'></div>
 *          OR
 *      <img src='%avatar URL%' />
 *     </div>
 *     <div class='user-name'>%user name%</div>
 *     <div class='user-date'>%last visit date%</div>
 *    </div><!-- legend } -->
 *    <div class='userInput'><!-- userInput { -->
 *     <h3 class='user-fullName'> ... </h3>
 *     <div class='user-email'>   ... </div>
 *     <div class='user-profile'> ... </div>
 *     <div class='buttons'><!-- buttons { -->
 *      <button name='reset' >Reset</button>
 *      <button name='submit'>Save</button>
 *     </div><!-- buttons } -->
 *    </div><!-- userInput } -->
 *   </form>
 *  </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.validationForm.js
 *      ui.input.js
 *
 *      settings.avatarChooser.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("settings.accountInfo", {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
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
         * which is initialized from
         *      application/configs/application.ini:api
         * via
         *      application/layout/header.phtml
         */
        jsonRpc:    null
    },

    /** @brief  Initialize a new instance.
     *
     */
    _create: function()
    {
        var self    = this;
        var opts    = self.options;

        /********************************
         * Initialize jsonRpc
         *
         */
        if ( (opts.jsonRpc === null) && $.isFunction($.registry))
        {
            var api = $.registry('api');
            if (api && api.jsonRpc)
            {
                opts.jsonRpc = $.extend({}, api.jsonRpc, opts.jsonRpc);
            }
        }

        /********************************
         * Locate pieces not collected
         * by our superclass.
         */
        opts.$form     = self.element.find('form:first');
        opts.$fullName = opts.$form.find('.user-fullName input');
        opts.$email    = opts.$form.find('.user-email input');
        opts.$profile  = opts.$form.find('.user-profile input');
        opts.$avatar   = opts.$form.find('.avatar');

        opts.$chooser  = self.element.find('#account-info-avatar');

        opts.$form.validationForm({hideLabels:false});

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

        opts.$form.bind('submit.settingsAccountInfo', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (opts.$form.validationForm('isValid'))
            {
                self.save();
            }
        });

        opts.$avatar.attr('title', 'click-to-edit')
                    .bind('click.settingsAccountInfo', function(e) {
            // Popup an avatar selection dialog 
            var $img    = opts.$avatar.find('img');

            opts.$chooser.avatarChooser( {
                avatar: ($img.length > 0 ? $img.attr('src') : null),
                modal:  true
            } );
        });
    },

    /************************
     * Public methods
     *
     */

    /** @brief  Attempt to save the new user information.
     *
     */
    save: function()
    {
        var self    = this;
        var opts    = self.options;
        var params  = {
            fullName:   opts.$fullName.val(),
            email:      opts.$email.val(),
            profile:    opts.$profile.val()
        };
        var $avatar = opts.$avatar.find('img');

        if ($avatar.length > 0)
        {
            params.pictureUrl = $avatar.attr('src');
        }

        opts.$form.mask();

        $.jsonRpc(opts.jsonRpc, 'user.update', params, {
            success: function(data) {
                if ( (! data) || (data.error !== null) )
                {
                    $.notify({
                        title:  'Information Update failed',
                        text:   '<p class="error">'
                              +  (data ? data.error.message : '')
                              + '</p>'
                    });
                }
                else
                {
                    // SUCCESS
                    $.notify({
                        title:  'Information Updated',
                        text:   ''
                    });

                    opts.$form.validationForm('saved');
                }
            },
            error: function(req, textStatus, err) {
                $.notify({
                    title:  'Information Update failed',
                    text:   '<p class="error">'
                          +  textStatus
                          + '</p>'
                });
            },
            complete: function(req, textStatus) {
                opts.$form.unmask();
            }
        });
    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Cleanup

        // Unbind events
        opts.$form.unbind('.settingsAccountInfo');
        opts.$avatar.unbind('.settingsAccountInfo');

        // Remove added elements
        opts.$form.validationForm('destroy');
    }
});

}(jQuery));



