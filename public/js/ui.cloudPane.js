/** @file
 *
 *  Javascript interface/wrapper for the presentation of a configurable pane
 *  which contains a bookmark list.
 *
 *  This is class extends ui.pane to include unobtrusive activation of any
 *  contained, pre-rendered ul.cloud generated via
 *  View_Helper_Html_HtmlItemCloud.
 *
 *  Requires:
 *      ui.pane.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("ui.cloudPane", $.ui.pane, {
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

        //self._init_cloud();
        self._paneInit();

        self.$optionsForm = self.element.find('.displayOptions form');

        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        /* On Display style change, toggle the state of 'highlightCount'
         *
         * Note: The ui.dropdownForm widget that controls the display options
         *       DOM element attached a ui.optionsGroups instance to any
         *       contained displayOptions element.  This widget will trigger
         *       the 'change' event on the displayOptions form with information
         *       about the selected display group when a change is made.
         */
        this.$optionsForm.bind('change.cloudPane',
                function(e, info) {
                    var $field  = $(this).find('.field.highlightCount');

                    if (info.group === 'cloud')
                    {
                        // Enable the 'highlightCount'
                        $field.removeClass('ui-state-disabled');
                        $field.find('select').removeAttr('disabled');
                    }
                    else
                    {
                        // Disable the 'highlightCount'
                        $field.addClass('ui-state-disabled');
                        $field.find('select').attr('disabled', true);
                    }
                });
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;

        // Unbind events
        self.$optionsForm.unbind('.cloudPane');

        self._paneDestroy();
    }
});


}(jQuery));



