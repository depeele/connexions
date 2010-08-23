/** @file
 *
 *  Provide option groups for a set of checkbox options.  These must have the
 *  following HTML structure:
 *
 *      <div class='_NS_OptionGroups'>      // _NS_ defines the namespace
 *        ...
 *        <ul class='groups'>               // define groups
 *         <li [ class='isCustom' ] >       // 'isCustom' iff this group
 *                                          // represents the "custom" group
 *                                          // to allow the user to select
 *                                          // any desired options as opposed
 *                                          // to those associated with a
 *                                          // particular pre-defined group.
 *          <input type='radio'
 *              [ class='is
 *                 name='_NS_OptionGroup'
 *                value='GROUP-NAME'        // define GROUP-NAME
 *
 *                 [ checked='checked' if this group is selected ] />
 *
 *          <label  for='_NS_OptionGroup'>
 *           GROUP-LABEL                    // define GROUP-LABEL / title
 *          </label>
 *         </li>
 *         ...
 *        </ul>
 *        <fieldset class='options'>        // define groupable options
 *         ...
 *         <div class='option'>
 *          <input type='checkbox'
 *                class='inGroup-GROUP-NAME ...'   // One 'inGroup-*' class
 *                                                  // for each group this
 *                                                  // option is part of
 *
 *                                          // define a colon-separated
 *                                          // option name that mirrors the
 *                                          // CSS selector to this point
 *                 name='_NS_OptionGroups_option[OPTION-NAME]'
 *
 *                 [ checked='checked' if this option is selected ] />
 *
 *          <label for='_NS_OptionGroups_option[OPTION-NAME]'>
 *           OPTION-LABEL                   // define OPTION-LABEL / title
 *          </label>
 *         </div>
 *         ...
 *        </fieldset>
 *      </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, document:false, window:false */

(function($) {

$.widget("connexions.optionGroups", {
    version: "0.1.1",
    options: {
        // Defaults
        cookiePath: null,   // Cookie path (defaults to window.location.pathname)
        namespace:  null,   // Form/cookie namespace
        form:       null    // Our parent/controlling form
    },

    /** @brief  Initialize a new instance.
     *
     *  Valid options are:
     *      namespace   The form / cookie namespace [ '' ];
     *      groups      An object of group-name => CSS selector;
     *
     *  @triggers:
     *      'change'    on the controlling form when the option group is
     *                  changed, passing
     *                              data:
     *                                  {'group':    groupName,
     *                                   'selector': selector for all fields}
     */
    _create: function() {
        var self        = this;
        var opts        = this.options;

        if (opts.namespace === null)
        {
            // See if the DOM element has a 'namespace' data item
            var ns  = self.element.data('namespace');
            if (ns !== undefined)
            {
                opts.namespace = ns;
            }
            else
            {
                /* Attempt to retrieve the namespace from the CSS class
                 * '_NS_OptionGroups'
                 */
                var css = self.element.attr('class');

                ns = css.replace(/^(?:.* )?(.*?)OptionGroups(?: .*)?$/,
                                      '$1');

                if ((ns !== undefined) && (ns.length > 0))
                {
                    opts.namespace = ns;
                }
            }

        }
        if (opts.form === null)
        {
            // See if the DOM element has a 'form' data item
            var fm  = self.element.data('form');
            if (fm !== undefined)
            {
                opts.form = fm;
            }
            else
            {
                // Choose the closest form
                opts.form = self.element.closest('form');
            }
        }

        /* The currently selected group:
         *  self.element.find('ul.groups :checked').val();
         *
         * Prepare the presentation:
         *  - Remove the CSS class 'ui-state-active' from all 'li' elements;
         *  - Add the CSS class 'ui-state-active' to the 'li' element
         *    containing the currently selected group;
         *  - Hide and disable all group radio buttons;
         *  - Add the 'toggle'  class to any group NOT marked 'isCustom';
         *  - Add the 'control' class to any group marked 'isCustom';
         *  - Add a down-arrow icon to the 'isCustom' control
         *  - Append '<span class='comma'>,</span>' after all but the last 'li'
         *    element;
         *  - For all input elements, add the classes:
         *      'ui-corner-all ui-state-default'
         */
        var $groups     = self.element.find('ul.groups');

        $groups.find('li')
                .removeClass('ui-state-active')
                .addClass('ui-state-default')
                .filter(':first')
                    .addClass('ui-corner-left')
                .end()
                .find(':radio')
                    .hide();
        $groups.find(':checked')
                .parent()
                    .addClass('ui-state-active');
        $groups.find('li.isCustom')
                .addClass('control')
                .button({
                    icons: {
                        secondary:  'ui-icon-triangle-1-s'
                    }
                })
                .removeClass('ui-corner-all')
                .addClass('ui-corner-right');

        /* Now, the currently selected group can be found via:
         *  self.element.find('ul.groups :checked').val();
         *  self.element.find('ul.groups li.ui-state-active :radio').val();
         *  -- self.element.find('ul.groups input[type=hidden]').val();
         */

        // Interaction events
        self._bindEvents();


        /* If the currently selected group is NOT the 'isCustom' group, toggle
         * the fieldset control closed.
         */
        if (! $groups.find('li.ui-state-active').hasClass('isCustom'))
        {
            self.toggleFieldset();
        }
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self            = this;
        var opts            = this.options;
        var $groups         = self.element.find('ul.groups');
        var $groupFieldset  = self.element.find('fieldset:first');
        var $groupControl   = $groups.find('.control:first');

        var _prevent_default        = function(e) {
            e.preventDefault();
        };

        var _groupControl_click     = function(e) {
            e.preventDefault();
            e.stopPropagation();

            self.toggleFieldset();
        };

        var _groupFieldset_change   = function(e) {
            /* The fieldset has changed so change the current group to
             * the 'isCustom' / 'control' group.
             *
             * Don't allow propagation -- we will directly trigger any events
             *                            that need to be passed on.
             */
            e.preventDefault();
            e.stopPropagation();

            var $group  = $groupControl.find(':radio');

            // Activate this group
            self.setGroup( $group.val() );

            return false;
        };

        var _group_select    = function(e) {
            /*
            if ($(e.target).is(':radio'))
                // Avoid infinite event loops ;^)
                return;
            */

            // Allow only one display group to be selected at a time
            e.preventDefault();
            e.stopPropagation();

            var $group  = $(this).find(':radio');

            // Activate this group
            self.setGroup( $group.val() );
        };

        // Bind to submit.
        var _form_submit        = function(e) {
            var cookieOpts  = {
                path: (opts.cookiePath === null
                        ? window.location.pathname
                        : opts.cookiePath)
            };
            if (window.location.protocol === 'https')
            {
                cookieOpts.secure = true;
            }

            /* Remove all cookies directly identifying options.  This is
             * because, when an option is NOT selected, it is not included so,
             * to remove a previously selected options, we must first remove
             * them all and then add in the ones that are explicitly selected.
             */
            $groupFieldset.find(':checkbox').each(function() {
                /*
                $.log("Remove Cookie: name[ %s ] / [ %s ]",
                        this.name, $(this).attr('name'));
                // */

                $.cookie( $(this).attr('name'), null, cookieOpts );
            });

            /* If the selected display group is NOT 'custom', disable
             * all the 'display custom' pane/field-set inputs so they
             * will not be included in the serialization of form
             * values.
             */
            if (! $groups.find('li.ui-state-active').hasClass('isCustom'))
            {
                // Disable all custom field values
                $groupFieldset.find(':checkbox').attr('disabled', true);
            }

            // let the form be submitted
        };


        /**********************************************************************
         * bind events
         *
         */

        /* Toggle the display group area.
         * the display group to 'custom', de-selecting the others.
         */
        $groupControl
                .bind('click.uioptiongroups', _groupControl_click);

        /* When something in the group fieldset changes, set the display group
         * to 'custom', de-selecting the others.
         */
        $groupFieldset
                .bind('change.uioptiongroups', _groupFieldset_change);

        // Allow only one display group to be selected at a time
        $groups.find('li:not(.control)')    // ('li.toggle')
                .bind('change.uioptiongroups', _group_select)
                .bind('click.uioptiongroups',  _group_select);

        // Bind to submit.
        opts.form.bind('submit.uioptiongroups', _form_submit);
    },

    /************************
     * Public methods
     *
     */
    getGroup: function() {
        /* Now, the currently selected group can be found in three ways:
         *  this.element.find('ul.groups :checked').val();
         *  this.element.find('ul.groups li.ui-state-active :radio').val();
         *  -- this.element.find('ul.groups input[type=hidden]').val();
         */
        return this.element.find('ul.groups :checked').val();
    },

    setGroup: function(group) {
        /* Now, the currently selected group can be found in three ways:
         *  this.element.find('ul.groups :checked').val();
         *  this.element.find('ul.groups li.ui-state-active :radio').val();
         *  -- this.element.find('ul.groups input[type=hidden]').val();
         */
        var self            = this;
        var $groups         = self.element.find('ul.groups');
        var $groupFieldset  = self.element.find('fieldset:first');
        var $newGroup       = $groups.find(':radio[value='+group+']');
        if ($newGroup.length !== 1)
        {
            return;
        }

        // Select the new radio button
        $groups.find(':checked').attr('checked', false)
                                .removeAttr('checked');
        $newGroup.attr('checked', 'checked');

        /* Remove 'ui-state-active' from all groups and add it JUST to the new
         * one
         */
        $groups.find('li.ui-state-active').removeClass('ui-state-active');

        var $li = $newGroup.parents('li:first');
        $li.addClass('ui-state-active');

        // Set the hidden input value
        // $groups.find('input[type=hidden]').val(group);

        if (! $li.hasClass('control'))
        {
            // Turn OFF all items in the group fieldset...
            $groupFieldset.find('input').removeAttr('checked');

            // Turn ON  the items for this new display group.
            $groupFieldset.find('.inGroup-'+ group)
                           .attr('checked', 'checked');
        }

        /* Gather the set of selected AND deselected options.  For each,
         * retrieve its name (e.g. 'sel1:sel2:sel3') and convert it to a CSS
         * selector.
         *
         * Generate an array of CSS selectors that will choose all selected
         * options and a second that will choose all de-selected options.
         */
        var selected    = [];
        var deSelected  = [];
        $groupFieldset.find('input:checked').each(function() {
            selected.push( '.' + $(this).attr('name')
                                            .replace(/^.*?\[(.*?)\]$/, '$1')
                                            .replace(/:/g, ' .') );
        });

        $groupFieldset.find('input:not(:checked)').each(function() {
            deSelected.push( '.' + $(this).attr('name')
                                            .replace(/^.*?\[(.*?)\]$/, '$1')
                                            .replace(/:/g, ' .') );
        });

        var groupInfo   = {'group'      : group,
                           'selected'   : selected,
                           'deSelected' : deSelected};

        self.element.data('groupInfo', groupInfo);

        /* Trigger the 'change' event passing the name of the new group along
         * with an array of CSS selectors that will match all items of the
         * group and an array of CSS selectors that will match all items NOT of
         * the group.
         */
        //$.log("connexions.optionGroups: trigger 'form:change'");
        self.options.form.trigger('change', groupInfo);
    },

    getGroupInfo: function() {
        return this.element.data('groupInfo');
    },

    getForm: function() {
        return this.options.form;
    },

    enable: function() {
        this.find(':input').removeAttr('disabled');
    },

    disable: function() {
        this.find(':input').attr('disabled', true);
    },

    toggleFieldset: function()
    {
        this.element.find('fieldset:first')
                                .toggleClass('ui-state-active')
                                .toggle();
    },

    destroy: function() {
        var self    = this;

        // Remove data
        self.element.find('a.option,div.option a:first')
                .removeData('group');

        // Unbind events
        var $groupControl   = self.element.find('.control:first');
        var $itemsGroup     = self.element.find('input[name='
                            +                       self.options.namespace
                            +                                   'Group]');

        /* Toggle the display group area.
         * the display group to 'custom', de-selecting the others.
         */
        $groupControl
                .unbind('.uioptiongroups');

        /* For all anchors within the control button, disable the default
         * browser action but allow the event to bubble up to any parent click
         * handlers (e.g. _groupControl_click).
         */
        $groupControl.find('> a, .control > a, .control > .ui-icon')
                .unbind('.uioptiongroups');

        /* When something in the group fieldset changes, set the display group
         * to 'custom', de-selecting the others.
         */
        self.element.find('fieldset:first')
                .unbind('.uioptiongroups');

        // Allow only one display group to be selected at a time
        self.element.find('a.option')
                .unbind('.uioptiongroups');

        // Bind to submit.
        self.options.form
                .unbind('.uioptiongroups');
    }
});


}(jQuery));
