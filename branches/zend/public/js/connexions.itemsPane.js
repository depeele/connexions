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
    _init: function() {
        var self        = this;
        var opts        = self.options;

        // Invoke our super-class
        $.connexions.pane.prototype._init.apply(this, arguments);

        // If a 'saved' event reaches us, reload the pane
        self.element.delegate('form', 'saved.itemsPane', function() {
            setTimeout(function() { self.reload(); }, 50);
        });

        self._init_itemList();
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

        // Unbind events
        self.element.undelegate('form', '.itemsPane');

        // Remove added elements
        self.$itemList.itemList('destroy');

        // Invoke our super-class
        $.connexions.pane.prototype.destroy.apply(this, arguments);
    }
});


}(jQuery));
