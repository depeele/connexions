/** @file
 *
 *  Javascript interface/wrapper to handle API Key presentation and
 *  re-generation.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-renderd account-apiKey form
 *      (application/views/scripts/settings/main-account-apikey.phtml)
 *
 *  <form id='account-apiKey'>
 *   <div class='legend'>Current API Key:</div>
 *   <div class='data'>
 *    <h3 class='apiKey'>%apiKey%</h3>
 *    <div class='buttons'>
 *     <button name='submit'>Regenerate</button>
 *    </div>
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

$.widget("settings.apiKey", {
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
        opts.$submit = self.element.find('button[name=submit]');
        opts.$apiKey = self.element.find('.apiKey');

        opts.$submit.button();

        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function()
    {
        var self    = this;

        self.element.bind('submit.settingsApiKey', function(e) {
            e.preventDefault();
            e.stopPropagation();

            self.regenerate();
        });
    },

    /************************
     * Public methods
     *
     */

    /** @brief  Regenerate the API Key.
     *
     */
    regenerate: function()
    {
        var self    = this;
        var opts    = self.options;

        self.element.mask();

        $.jsonRpc(opts.jsonRpc, 'user.regenerateApiKey', {}, {
            success: function(data) {
                if ( (! data) || (data.error !== null) )
                {
                    $.notify({
                        title:  'API Key Regeneration failed',
                        text:   '<p class="error">'
                              +  (data ? data.error.message : '')
                              + '</p>'
                    });
                }
                else
                {
                    // SUCCESS
                    opts.$apiKey.text( data.result );

                    $.notify({
                        title:  'API Key Regenerated',
                        text:   ''
                    });
                }
            },
            error: function(req, textStatus, err) {
                $.notify({
                    title:  'API Key Regneration failed',
                    text:   '<p class="error">'
                          +  textStatus
                          + '</p>'
                });
            },
            complete: function(req, textStatus) {
                self.element.unmask();
            }
        });
    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Cleanup

        // Unbind events
        self.element.unbind('.settingsApiKey');

        // Remove added elements
        opts.$submit.button('destroy');
    }
});

}(jQuery));


