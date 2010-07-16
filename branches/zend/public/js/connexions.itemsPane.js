/** @file
 *
 *  Javascript interface/wrapper for the presentation of a configurable pane
 *  which contains an item list.
 *
 *  This is class extends connexions.pane to include unobtrusive activation of
 *  any contained, pre-rendered ul.list generated via
 *  View_Helper_Html{ Bookmarks | Users }.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      connexions.pane.js
 *      connexions.itemList.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.itemsPane", $.connexions.pane, {
    version: "0.0.1",
    options: {
        // Defaults
        namespace:  ''
    },

    /** @brief  Initialize a new instance.
     */
    _create: function() {
        var self        = this;
        var opts        = self.options;

        self._init_itemList();

        self._paneInit();
    },

    /************************
     * Private methods
     *
     */
    _init_itemList: function() {
        var self            = this;
        self.$itemList  = self.element.find('ul.items');

        if (self.$itemList.length < 1)
        {
            return;
        }

        var opts    = self.options;
        var uiOpts  = (opts.itemList === undefined
                        ? {}
                        : opts.itemList);

        if (uiOpts.namespace === undefined)
        {
            uiOpts.namespace = opts.namespace;
        }

        // Instantiate the connexions.itemList widget
        self.$itemList.itemList(uiOpts);
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;

        // Remove added elements
        self.$itemList.itemList('destroy');

        self._paneDestroy();
    }
});


}(jQuery));
