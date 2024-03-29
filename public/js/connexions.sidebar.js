/** @file
 *
 *  Javascript interface/wrapper for the presentation of a configurable
 *  sidebar.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered sidebar:
 *      - conversion of markup generated via View_Helper_HtmlSidebar, to
 *        ui.tabs instance(s);
 *      - possible asynchronous loading of tab panes with masking of the tab
 *        widget during load;
 *
 *  The pre-rendered HTML must have a form similar to:
 *      <div id='%namespace%'>
 *        <ul>
 *          <li>
 *            <a href='%paneUrl%'>
 *              <span>Pane Title</span>
 *            </a>
 *          </li>
 *          ...
 *        </ul>
 *        
 *        <!-- If these are synchronous panes, the content is here -->
 *        <div id='%paneId%'>
 *          Pane content
 *        </div>
 *        ...
 *      </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($) {

$.widget("connexions.sidebar", {
    version: "0.0.1",
    options: {
        // Defaults
        namespace:      ''      // Cookie/parameter namespace
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'change.bookmark'  when something about the bookmark is changed;
     */
    _create: function() {
        var self    = this;
        var opts    = self.options;

        opts.namespace = self.element.attr('id');

        self.element.tabs({
            cache:      true,
            cookie:     opts.namespace,
            ajaxOptions:{
                beforeSend: function() {
                    // Mask the tab panel area...
                    var sel = self.element.tabs('option', 'selected');
                    self.$tab = self.element.find('.ui-tabs-panel').eq(sel);

                    self.$tab.mask();
                },
                complete: function() {
                    // Unmask the tab panel area...
                    self.$tab.unmask();
                }
            }
        });
    },

    /************************
     * Private methods
     *
     */

    /************************
     * Public methods
     *
     */
    destroy: function() {
        this.element.tabs('destroy');
    }
});


}(jQuery));



