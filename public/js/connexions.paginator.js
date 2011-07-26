/** @file
 *
 *  Javascript interface/wrapper for the presentation of a pagination control.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered pagination control, generate via Zend_Paginator.
 *
 *  The paginator has the following HTML structure:
 *
 *      <form class='paginator'>
 *        <div class='pager'>
 *          <button type='submit' ... value='page#'>page#</button>
 *          ...
 *        </div>
 *
 *        <!-- and optionally -->
 *        <div class='info'>
 *          <div class='perPage'>
 *            <div class='itemCount'>count#</div>
 *              items with
 *            <select name='%ns%PerPage'>...</select>
 *              items per page.
 *          </div>
 *          <div class='itemRange'>
 *            Currently viewing items
 *            <div class='count'>1 - 50</div>
 *             .
 *          </div>
 *        </div>
 *      </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */

(function($) {

$.widget("connexions.paginator", {
    version: "0.1.1",
    options: {
        // Defaults
        namespace:      '',     // Form/cookie namespace
        disableHover:   false,
        page:           1,
        pageVar:        'Page'
    },

    /** @brief  Initialize a new instance.
     *
     *  Valid options are:
     *      namespace   The form / cookie namespace [ '' ];
     *
     *  @triggers:
     *      'submit'    on the controlling form when 'PerPage' select element
     *                  is changed.
     */
    _init: function() {
        var self        = this;
        var opts        = self.options;

        if (opts.namespace === null)    opts.namespace = '';

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

        // Which page is currently selected/active?
        opts.page    = self.element.find('button.ui-state-active').text();
        opts.pageVar = self.element.find('button:submit:first').attr('name');

        // Interaction events
        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self    = this;
        var opts    = self.options;

        // Add an opacity hover effect
        if (! opts.disableHover)
        {
            self.element
                .fadeTo(100, 0.5)
                .hover( function() {    // in
                            $(this).fadeTo(100, 1.0);
                        },
                        function() {    // out
                            $(this).fadeTo(100, 0.5);
                        }
                );
        }

        // Attach to any PerPage selection box
        self.element.find('.perPage select')
                .bind('change.paginator', function(e) {
                        /* On change of the PerPage select:
                         *  - set a cookie for the %ns%PerPage value...
                         */
                        var cookieOpts  = {};
                        var cookiePath  = $.registry('cookiePath');

                        if (cookiePath)
                        {
                            cookieOpts.path = cookiePath;
                        }

                        $.log("connexions.paginator: Add Cookie: "
                              + "path[%s], name[%s], value[%s]",
                              cookiePath, this.name, this.value);

                        $.cookie(this.name, this.value, cookieOpts);

                        //  - and trigger 'submit' on the pagination form.
                        self.element.submit();
                      }
                );

        // Attach to all 'submit' buttons to remember which page
        self.element.find(':submit')
                .bind('click.paginator', function(e) {
                            opts.page = $(this).val();

                            // Allow the event to bubble
                        }
                );
    },

    /** @brief  Over-ride jQuery-ui so we can handle toggling 'disableHover'
     *  @param  key     The name of the option;
     *  @param  value   The new option value;
     *
     *  @return this for a fluent interface.
     */
    _setOption: function( key, value ) {
        var self    = this;
        var opts    = self.options;

        switch (key)
        {
        case 'disableHover':
            if (opts.disableHover != value)
            {
                if (! value )
                {
                    // Add an opacity hover effect
                    self.element
                        .fadeTo(100, 0.5)
                        .bind('mouseenter.paginator', function() {
                                $(this).fadeTo(100, 1.0);
                              })
                        .bind('mouseleave.paginator', function() {
                                $(this).fadeTo(100, 0.5);
                              });
                }
                else
                {
                    // Remove the opacity hover effect
                    self.element
                        .fadeTo(100, 1.0)
                        .unbind('mouseenter.paginator')
                        .unbind('mouseleave.paginator');
                }
            }
            break;
        }

        // Invoke our superclass
        $.Widget.prototype._setOption.apply(this, arguments);
    },

    /************************
     * Public methods
     *
     */
    getPage: function() {
        return this.options.page;
    },
    getPageVar: function() {
        return this.options.pageVar;
    },

    getForm: function() {
        return this.options.form;
    },

    enable: function() {
        this.element.find(':button').removeAttr('disabled');
    },

    disable: function() {
        this.element.find(':button').attr('disabled', true);
        this.element.fadeTo(100, 1.0)
                    .unbind('.paginator');
    },

    destroy: function() {
    }
});


}(jQuery));
