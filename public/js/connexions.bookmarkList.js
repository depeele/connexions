/** @file
 *
 *  Javascript interface/wrapper for the presentation of multiple bookmarks.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered list of bookmark items (View_Helper_HtmlBookmarks), each of
 *  which will become a connexions.bookmark instance.
 *
 *  This class also handles:
 *      - hover effects for .groupHeader DOM items;
 *      - conversion of all form.bookmark DOM items to connexions.bookmark
 *        instances;
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
 *      ui.widget.js
 *      connexions.bookmark.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.bookmarkList", {
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


        self.$bookmarks.bookmark({
            'deleted': function(e, data) {
                // Remove this bookmark
                self._bookmarkDeleted( $(this) );
            }
        });

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

    _bookmarkDeleted: function($bookmark)
    {
        /* Remove the given bookmark, also removing the group header if this
         * bookmark is the last in the group.
         */
        var $item       = $bookmark.parent('.item');

        /* If this is the last bookmark in the group, the groupHeader will be
         * the prevous element and the next element will NOT be another
         * 'li.item'
         */
        var $group      = $item.prev('.groupHeader');
        var $next       = $item.next();

        // Slide the bookmark up and then the containing 'li.item'
        $bookmark.slideUp('fast', function() {
            $item.slideUp('normal', function() {
                // Destroy the widget and remove the containing 'li.item'
                $bookmark.bookmark('destroy');
                $item.remove();

                if (($group.length > 0) && (! $next.hasClass('item')) )
                {
                    /* There are no more bookmarks in the group, so remove the
                     * group header
                     */
                    $group.slideUp('normal', function() {
                        $group.remove();
                    });
                }
            });
        });
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


