/** @file
 *
 *  Javascript interface/wrapper to handle presentation and activation of the
 *  bookmarks import section.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-renderd account-info form
 *      (application/views/scripts/settings/main-bookmarks-import.phtml)
 *
 *  <div id='bookmarks-import'>
 *   <form target='bookmark-import-results'>
 *    <input type='file' name='bookmarkFile' />
 *
 *    <input type='radio' name='visibility' value='private' checked='true' />
 *    <input type='radio' name='visibility' value='public' />
 *
 *    <input type='radio' name='conflict' value='replace' />
 *    <input type='radio' name='conflict' value='replace' checked='true' />
 *
 *    <input type='radio' name='test' value='yes' />
 *    <input type='radio' name='test' value='no' checked='true' />
 *
 *    <input type='submit' name='submit' value='Import' />
 *   </form>
 *   <div class='results-section section'>
 *    <iframe id='bookmark-import-results'
 *          name='bookmark-import-results'
 *           src='about:blank'
 *         style='display:none'></iframe>
 *   </div>
 *  </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.input.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("settings.bookmarksImport", {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        instIdPrefix:   'bookmark-import-browser-instructions'
    },

    /** @brief  Initialize a new instance.
     *
     */
    _create: function()
    {
        var self    = this;
        var opts    = self.options;
        var $inputs = self.element.find('input,textarea');

        opts.$form       = self.element.find('form:first');

        opts.$file       = $inputs.filter('[type=file]');
        opts.$inputs     = $inputs.filter('[type=text],[type=file],textarea');
        opts.$buttonSets = $inputs.filter('[type=radio]').parent();
        opts.$submit     = $inputs.filter('[type=submit]');

        opts.$results    = self.element.find('.results-section');
        opts.$iframe     = opts.$results.find('iframe:first');

        opts.$inst       = opts.$form.find('[id^='+ opts.instIdPrefix +']');
        opts.$instA      = opts.$form.find('.alternatives a')

        // Create sub-widgets
        opts.$inputs.input({ hideLabel: false });
        opts.$buttonSets.buttonset();
        opts.$submit.button({disabled:true});

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

        // Handle showing instructions for specific browsers
        opts.$form.delegate('.alternatives a', 'click', function(e) {
            e.preventDefault();

            var $a      = $(this);
            var id      = '#'+ opts.instIdPrefix +'-'
                        + $a.attr('href').replace(/.*\?ua=/, '');
            var $inst   = opts.$form.find( id );

            // Hide all instructions
            opts.$inst.hide();
            opts.$instA.css('text-decoration', 'none');

            if ($inst.length > 0)
            {
                // Show the instructions for the chosen browser.
                $inst.show();
                $a.css('text-decoration', 'underline');
            }
            else
            {
                // Show the generic instructions
                opts.$form.find('#'+ opts.instIdPrefix).show();
            }
        });

        // Handle changes to the import file
        opts.$file.bind('change.settingsBookmarksImport', function(e) {
            if (opts.$file.val().length > 0)
            {
                opts.$submit.button('enable');
            }
            else
            {
                opts.$submit.button('disable');
            }
        });

        // Handle import form submission
        opts.$form.bind('submit.settingsBookmarksImport', function(e) {
            opts.$form.mask();

            opts.$iframe.contents().find('body').empty();
            opts.$results.show('fast', function() {
                // Scroll down so the results are visible
                $.scrollTo( opts.$submit.parent(), {duration: 800} );
            });

            // Allow the event to propagate
        });

        // When the progress iframe is fully loaded, unmask our import form
        opts.$iframe.bind('load.settingsBookmarksImport', function(e) {
            opts.$form.unmask();

            //var content = opts.$iframe.contents();
        });
    },

    /************************
     * Public methods
     *
     */

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Cleanup

        // Unbind events
        opts.$form.unbind('.settingsBookmarksImport');
        opts.$file.unbind('.settingsBookmarksImport');
        opts.$iframe.unbind('.settingsBookmarksImport');

        // Remove added elements
        opts.$inputs.input('destroy');
        opts.$buttonSets.buttonset('destroy');
        opts.$submit.button('destroy');
    }
});

}(jQuery));
