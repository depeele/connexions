/** @file
 *
 *  Javascript interface/wrapper for the presentation of a configurable pane.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered view / pane:
 *      - conversion of any (optional) paginator markup (form.paginator),
 *        generated via View_Helper_HtmlPaginationControl, to
 *        connexions.paginator instance(s);
 *      - conversion of any (optional) display options markup
 *        (.displayOptions), generated via View_Helper_HtmlDisplayOptions, to a
 *        connexions.dropdownForm instance;
 *
 *  The pre-rendered HTML must have a form similar to:
 *      <div class='pane' ...>
 *        [ top paginator ]
 *        [ display options ]
 *
 *        content
 *
 *        [ bottom paginator ]
 *      </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      connexions.dropdownForm.js
 *      connexions.paginator.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($) {

$.widget("connexions.pane", {
    version: "0.0.1",
    options: {
        // Defaults
        namespace:      '',     // Cookie/parameter namespace
        partial:        null,   // The name of the 'partial' if asynchronous
                                // reloads are to be used on pagination or
                                // displayOption changes.

        // Information via the connexions.pagination widget(s)
        pageCur:        null,   // The current page number
        pageVar:        null,   // The page number URL variable name
        page:           null,   // The target  page number


        /* Configuration for any <form class='pagination'> element that 
         * will be controlled by a connexions.pagination widget.
         */
        paginator:      {},

        /* Configuration for any <div class='displayOptions'> element that 
         * will be controlled by a connexions.dropdownForm widget.
         */
        displayOptions: {}
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'change.bookmark'  when something about the bookmark is changed;
     */
    _create: function() {
        this._paneInit();
    },

    /************************
     * Private methods
     *
     */
    _paneInit: function() {
        this._init_paginators();
        this._init_displayOptions();
    },

    _init_paginators: function() {
        var self        = this;
        var opts        = self.options;

        self.$paginators    = self.element.find('form.paginator');

        self.$paginators.each(function(idex) {
            var $pForm  = $(this);

            $pForm.paginator({namespace:    opts.namespace,
                              form:         $pForm,
                              disableHover: (idex !== 0)
                              });

            if (opts.page === null)
            {
                opts.pageCur = $pForm.paginator('getPage');
                opts.pageVar = $pForm.paginator('getPageVar');
            }
        });

        self.$paginators.bind('submit.uipane', function(e) {
            e.preventDefault(true);
            e.stopPropagation(true);
            e.stopImmediatePropagation(true);

            // Set the target page number
            opts.page = $(this).paginator('getPage');

            // reload
            self.reload();
        });
    },

    _init_displayOptions: function() {
        var self                = this;
        self.$displayOptions    = self.element.find('div.displayOptions');

        if (self.$displayOptions.length < 1)
        {
            return;
        }

        var opts    = self.options;
        var uiOpts  = (opts.displayOptions === undefined
                        ? {}
                        : opts.displayOptions);

        if (uiOpts.namespace === undefined)
        {
            uiOpts.namespace = opts.namespace;
        }

        if (! $.isFunction(uiOpts.apply))
        {
            uiOpts.apply = function(e) {
                /* dropdownForm sets cookies for any form values, so we can
                 * simplify the form submission process (ensuring a clean url)
                 * by simply re-loading the window.  The reload will cause the
                 * new cookie values to be applied.
                 */
                e.stopImmediatePropagation();
                e.preventDefault();
                e.stopPropagation();

                self.reload();
            };
        }

        // Instantiate the connexions.dropdownForm widget
        self.$displayOptions.dropdownForm(uiOpts);
    },
    _paneDestroy: function() {
        var self    = this;

        // Remove added elements
        self.$paginators.paginator('destroy');
        self.$displayOptions.dropdownForm('destroy');
    },

    /************************
     * Public methods
     *
     */
    reload: function(page) {
        var self    = this;
        var opts    = self.options;
        var re      = new RegExp(opts.pageVar +'='+ opts.pageCur);
        var rep     = opts.pageVar +'='+ opts.page;
        var loc     = window.location;
        var url     = loc.toString();

        if (loc.search.length === 0)
        {
            url += '?'+ rep;
        }
        else if (! url.match(re))
        {
            url += '&'+ rep;
        }
        else
        {
            url = url.replace(re, rep);
        }

        if (opts.partial !== null)
        {
            // AJAX reload of just this pane...
            url += '&format=partial&part='+ opts.partial;

            $.ajax({url:        url,
                    dataType:   'html',
                    beforeSend: function() {
                        self.element.mask();
                    },
                    error:      function(req, txtStatus, err) {
                        $.notify({
                            title:'Reload pane "'+ opts.partial +'" failed',
                            text: '<p class="error">'+ txtStatus +'</p>'});
                    },
                    success:    function(data, txtStatus, req) {
                        // Out with the old...
                        self.destroy();

                        // In with the new.
                        self.element.html(data);
                        self._create();
                    },
                    complete:   function() {
                        self.element.unmask();
                    }
            });
        }
        else
        {
            // Perform a full, synchronous reload...
            window.location.assign(url);
        }
    },

    destroy: function() {
        this._paneDestroy();
    }
});


}(jQuery));



