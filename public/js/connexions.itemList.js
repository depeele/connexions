/** @file
 *
 *  Javascript interface/wrapper for the presentation of multiple items.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered list of items (View_Helper_Html{ Bookmarks | Users}), each of
 *  which will become a connexions.{ %objClass% } instance.
 *
 *  This class also handles:
 *      - hover effects for .groupHeader DOM items;
 *      - conversion of all form.item DOM items to
 *        connexions.{ %objClass% } instances;
 *
 *  View_Helper_HtmlItems will generate HTML for a item list similar
 *  to:
 *      <div id='<ns>List'>
 *        <ul class='<ns>'>
 *          <li><form class='%objClass%'> ... </form></li>
 *          ...
 *        </ul>
 *      </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.itemList", {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Defaults
        namespace:      '',
        objClass:       null,
        dimOpacity:     0.5,
        dimSpeed:       100,

        // Should item 'deleted' events be ignored?
        ignoreDeleted:  false
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'change.item'  when something about the item is changed;
     */
    _create: function()
    {
        var self        = this;
        var opts        = self.options;

        // Items
        self.$items = self.element.find('li > form');

        // Group Headers
        self.$headers = self.element.find('.groupHeader .groupType');

        if ((opts.objClass === null) && (self.$items.length > 0))
        {
            /* Determine the type/class of item by the CSS class of the
             * representative form
             */
            opts.objClass = self.$items.attr('class');

            // :XXX: IE Fix
            if (opts.objClass === undefined)
            {
                opts.objClass = self.$items[0].className;
            }
        }

        if (self.$items.length > 0)
        {
            // Instantiate each item using the identified 'objClass'
            self.$items[opts.objClass]();
        }

        // Initially dim all headers
        self.$headers.fadeTo(1, opts.dimOpacity);

        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function()
    {
        var self            = this,
            opts            = self.options,
            $groupHeaders   = self.element.find('.groupHeader');

        if (opts.ignoreDeleted !== true)
        {
            /* Include a handler for the 'deleted' event that will be
             * emitted by the instance when it believes it has been
             * "deleted".  In most cases, this belief is justified, but if
             * 'ignoreDeleted' is set, we need to ignore that belief.
             */

            // Use an event delegate
            self.element.delegate('li > form', 'deleted.itemList', function(e) {
                self._itemDeleted( $(this) );
            });
            // */
        }

        /** @brief  Handle a mouseenter/mouseleave event to highlight the
         *          appropriate group header.
         *  @param  e   The triggering event.
         */
        var groupHover = function(e) {
            /*
            console.log('groupHover:'+ e.type +': '+ e.target.nodeName);
            // */

            if (self.hoverTimer)    { clearTimeout(self.hoverTimer); }
            if (e.type === 'mouseleave')
            {
                /* mouseleave:
                 *  Wait a short bit and, if 'mouseenter' isn't triggered dim
                 *  all headers.
                 */
                self.hoverTimer = setTimeout(function() {
                    /*
                    console.log('groupHover:mouseleave: dim all headers');
                    // */

                    self.hoverTimer = null;
                    self.$headers.stop().fadeTo(opts.dimSpeed, opts.dimOpacity);
                }, 100);

                return;
            }

            /* mouseenter:
             *  Find the last group header that is ABOVE the current mouse
             *  position.
             */
            var $group
            $groupHeaders.each(function() {
                var offset  = $(this).offset();

                if (e.pageY >= offset.top)
                {
                    $group = $(this).find('.groupType');
                }
            });
            if (! $group)   { $group = self.$headers.first(); }

            /*
            console.log('groupHover:mouseenter: highlight gruop #'+
                        $group.index());
            // */
            
            // Dim all headers except the target, which will be highlighted
            var $toDim  = self.$headers.not($group);

            $toDim.stop().fadeTo(opts.dimSpeed, opts.dimOpacity);
            $group.stop().fadeTo(opts.dimSpeed, 1.0);
        };

        // Handle mouseenter/mouseleave triggered on the top-level element.
        self.element.bind('mouseenter.itemList mouseleave.itemList',
                                                            groupHover);

        // And delegate mouseenter for children elements.
        self.element.delegate('li,.groupHeader', 'mouseenter.itemList',
                                                            groupHover);
    },

    _itemDeleted: function($item)
    {
        var self        = this;

        /* Remove the given item, also removing the group header if this
         * item is the last in the group.
         */
        var $parentLi   = $item.parent('.item');

        /* If this is the last item in the group, the groupHeader will be
         * the prevous element and the next element will NOT be another
         * 'li.item'
         */
        var $group      = $parentLi.prev('.groupHeader');
        var $next       = $parentLi.next();

        // Slide the item up and then the containing 'li.item'
        $item.slideUp('fast', function() {
            $parentLi.slideUp('normal', function() {
                // Destroy the widget and remove the containing 'li.item'
                if ($item.item) { $item.item('destroy'); }
                if ($item.user) { $item.user('destroy'); }

                // Trigger an 'itemDeleted' event.
                self.element.trigger('itemDeleted', [ $item ]);

                $parentLi.remove();

                if (($group.length > 0) && (! $next.hasClass('item')) )
                {
                    /* There are no more items in the group, so remove the
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
        var self    = this;
        var opts    = self.options;

        // Unbind/delegate events
        self.element.unbind('.itemList');
        self.element.undelegate('li > form',       '.itemList');
        self.element.undelegate('li,.groupHeader', '.itemList');

        // Remove added elements
        if (self.$items.length > 0)
        {
            self.$items[opts.objClass]('destroy');
        }
    }
});


}(jQuery));
