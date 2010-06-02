/** @file
 *
 *  Javascript interface/wrapper for the presentation of a configurable pane
 *  which contains a bookmark list.
 *
 *  This is class extends ui.pane to include unobtrusive activation of any
 *  contained, pre-rendered ul.bookmarkList generated via
 *  View_Helper_HtmlBookmarks.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.pane.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("ui.bookmarksPane", $.ui.pane, {
    version: "0.0.1",
    options: {
        // Defaults
        namespace:  ''
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'change.bookmark'  when something about the bookmark is changed;
     */
    _create: function() {
        var self        = this;
        var opts        = self.options;

        self._init_bookmarkList();

        self._paneInit();
    },

    /************************
     * Private methods
     *
     */
    _init_bookmarkList: function() {
        var self            = this;
        self.$bookmarkList  = self.element.find('ul.bookmarks');

        if (self.$bookmarkList.length < 1)
        {
            return;
        }

        var opts    = self.options;
        var uiOpts  = (opts.bookmarkList === undefined
                        ? {}
                        : opts.bookmarkList);

        if (uiOpts.namespace === undefined)
        {
            uiOpts.namespace = opts.namespace;
        }

        // Instantiate the ui.bookmarkList widget
        self.$bookmarkList.bookmarkList(uiOpts);
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;

        // Remove added elements
        self.$bookmarkList.bookmarkList('destroy');

        self._paneDestroy();
    }
});


}(jQuery));



