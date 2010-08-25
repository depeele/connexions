/** @file
 *
 *  Javascript interface/wrapper for the presentation of a collapsable area.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered area.
 *
 *  The pre-rendered HTML must have a form similar to:
 *      < dom container, 'element' for this class (e.g. <div>, <ul>, <li>) >
 *        <h3 class='toggle'><span>Area Title</span></h3>
 *        <div > ... </div>
 *      </ dom container >
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($) {

$.widget("connexions.collapsable", {
    version: "0.0.1",
    options: {
        // Defaults
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'collapse', 'expand', 'toggle'
     */
    _create: function() {
        var self    = this;
        var opts    = self.options;

        self.$toggle  = self.element.find('.toggle:first');
        self.$content = self.$toggle.next();

        // Add styling to the toggle and content
        self.$toggle.addClass('ui-corner-top');
        self.$content.addClass('ui-corner-bottom');

        // Add an open/close indicator
        self.$toggle.prepend( '<div class="ui-icon">&nbsp;</div>');
        self.$indicator = self.$toggle.find('.ui-icon:first');

        if (self.$toggle.hasClass('collapsed'))
        {
            // Change the indicator to "closed" and hide the content
            self.$indicator.addClass('ui-icon-triangle-1-e');
            self.$content.hide();
        }
        else
        {
            // Change the indicator to "open" and hide the content
            self.$indicator.addClass('ui-icon-triangle-1-s');
            self.$content.show();

            if (! self.$toggle.hasClass('expanded'))
            {
                self.$toggle.addClass('expanded');
            }
        }

        self._bindEvents();
    },

    _bindEvents: function() {
        var self    = this;

        self.$toggle.bind('click.collapsable', function() {
            if (self.$content.is(":hidden"))
            {
                // Show the content / open
                self.$toggle.removeClass('collapsed')
                            .addClass(   'expanded');
                self.$indicator.removeClass('ui-icon-triangle-1-e')
                               .addClass(   'ui-icon-triangle-1-s');
                self.$content.slideDown();
                    
                self.element.trigger('expand');
            }
            else
            {
                // Hide the content / close
                self.$toggle.removeClass('expanded')
                            .addClass(   'collapsed');
                self.$indicator.removeClass('ui-icon-triangle-1-s')
                               .addClass(   'ui-icon-triangle-1-e');
                self.$content.slideUp();

                self.element.trigger('collapse');
            }

            // Trigger 'toggle'
            self.element.trigger('toggle');
        });
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;
        var opts    = self.options;

        // Remove styling
        self.$toggle.addClass('ui-corner-top');
        self.$content.addClass('ui-corner-bottom');
        self.$toggle.removeClass('collapsed,expanded');

        // Remove event bindings
        self.$toggle.unbind('.collapsable');

        // Ensure that the content is visible
        self.$content.show();
    }
});


}(jQuery));



