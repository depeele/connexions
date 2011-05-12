/** @file
 *
 *  Javascript interface/wrapper for the presentation of a search box with
 *  drop down context selection.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered area generate by view/scripts/nav_menu.phtml:
 *      - conversion of the input area to a ui.input instance;
 *
 *  The pre-rendered HTML must have a form similar to:
 *      <form id='search'>
 *        <div class='searchBox'>
 *          <div class='searchInput'>
 *            <div class='choices'>
 *              <input type='hidden' name='searchContext' ... />
 *              <ul class='sub list'>
 *                <li id='search-choice-%name%'> %title% </li>
 *                ...
 *              </ul>
 *            </div>
 *            
 *            <input type='text' name='terms' class='input' ... />
 *          </div>
 *          <button class='submit' ...>&nbsp;</button>
 *        </div>
 *      </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.input.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($){

$.widget("connexions.search", {
	options: {
	},
	_create: function(){
		var self    = this;
        var opts    = self.options;

        self.$input         = self.element.find('input[name=terms]');
        self.$submit        = self.element.find('button.submit');
        self.$context       = self.element.find('input[name=searchContext]');
        self.$choices       = self.element.find('.choices .list');
        self.contextLabel   = self.$choices.find('li.active').text();

        // Initially disable the submit button
        self.$submit.addClass('ui-state-disabled')
                    .attr('disabled', true);

        /* Attach a ui.input widget to the input field with defined validation
         * callback to enable/disable the submit button based upon whether or
         * not there is text in the search box.
         */
        self.$input.input({
            validation: function(val) {
                if (val.length > 0)
                {
                    self.$submit.removeClass('ui-state-disabled')
                                .removeAttr('disabled');
                }
                else
                {
                    self.$submit.addClass('ui-state-disabled')
                                .attr('disabled', true);
                }

                // ALWAYS return true.  There really in no "invalid" search
                return true;
            }
        });
        self.$input.input('setLabel', self.contextLabel);

        self._bindEvents();
	},

    _bindEvents: function() {
        var self    = this;
        var opts    = self.options;

        // Activate our search choice selections
        self.$choices.find('li')
                .bind('mousedown.search', function(e) {
                    /* We're changing the label text so, before 'blur' is
                     * fired, remove the existing label text.
                     *
                     * This fixes a flicker issue where the old label text
                     * would be placed in the input field only to be removed
                     * when we re-focus on that field.
                     */
                    self.$input.input('setLabel', null);
                })
                .bind('click.search', function(e) {
                    var $li         = $(this);

                    // Grab the new context value from li.id
                    var newChoice   = $li.attr('id').replace(/search-choice-/,
                                                             '');

                    // Set the new context value
                    self.$context.val(newChoice);

                    // Remember this context value via cookie
                    $.cookie('searchContext', newChoice);

                    // Grab the new label value for the query input box
                    self.contextLabel = $li.text();

                    // Remove the 'active' class from all siblings...
                    $li.siblings('.active').removeClass('active');

                    // Add the 'active' class to THIS element.
                    $li.addClass('active');

                    // Set the new label text and focus on the input field.
                    self.$input.input('setLabel', self.contextLabel);
                    /*
                    if ($.isFunction(self.$input.focus))
                    {
                        self.$input.focus();
                    }
                    */
                });
    },

    /*************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;

        // Destroy widgets
        self.$input.input('destroy');

        // Unbind events
        self.$choices.find('li').unbind('.search');
    }
});

}(jQuery));
