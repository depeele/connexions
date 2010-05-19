/** @file
 *
 *  Javascript interface/wrapper for the presentation of multiple bookmarks.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered list of bookmark items (View_Helper_HtmlBookmarks), each of
 *  which will become a ui.bookmark instance.
 *
 *  This class also handles:
 *      - hover effects for .groupHeader DOM items;
 *      - conversion of all form.bookmark DOM items to ui.bookmark instances;
 *
 *  View_Helper_HtmlBookmarks will generate HTML for a bookmark list similar
 *  to:
 *      <div id='<ns>List'>
 *        <ul class='<ns>'>
 *          <li><form class='bookmark'> ... </form></li>
 *          ...
 *        </ul>
 *      </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.bookmark.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("ui.bookmarkList", {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Defaults
        namespace:  '',
        dimOpacity: 0.5
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'change.bookmark'  when something about the bookmark is changed;
     */
    _create: function()
    {
        var self    = this;
        var opts    = self.options;

        // Bookmarks
        self.$bookmarks = self.element.find('form.bookmark');

        // Group Headers
        self.$headers = self.element.find('.groupHeader .groupType');


        self.$bookmarks.bookmark();

        self.$headers
                .fadeTo(100, opts.dimOpacity)
                .hover( function() {    // in
                            self.$headers.fadeTo(100, 1.0);
                        },
                        function() {    // out
                            self.$headers.fadeTo(100, opts.dimOpacity);
                        }
                );

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
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self        = this;

        // Unbind events
        self.$headers.unbind('hover');

        // Remove added elements
        self.$bookmarks.bookmark('destroy');
    }
});


}(jQuery));


