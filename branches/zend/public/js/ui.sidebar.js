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
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($) {

$.widget("ui.sidebar", {
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
                    // Bind any new displayOptions forms
                    self._bindReload(self.$tab);

                    // Unmask the tab panel area...
                    self.$tab.unmask();
                }
            }
        });

        // For each asynchronous tab, bind reload events
        self.element.find('ul:first li a:first').each(function(idex) {
            var url = $.data(this, 'load.tabs');
            if (url)
            {
                var $tab = self.element.find('.ui-tabs-panel').eq(idex);
                self._bindReload($tab);
            }
        });

        self._bindReload(self.element);
    },

    /************************
     * Private methods
     *
     */
    _bindReload: function(context) {
        var self    = this;

        context.find('.displayOptions')
                .dropdownForm('setApplyCb', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    // Reload the tab contents
                    self.element.tabs('load',
                                      self.element.tabs('option', 'selected'));
                });
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        this.element.tabs('destroy');
    }
});


}(jQuery));



