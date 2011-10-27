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
        hiddenVars:     null,   // Hidden variables from the target form


        /* Configuration for any <form class='pagination'> element that 
         * will be controlled by a connexions.pagination widget.
         */
        paginator:      {},

        /* Configuration for any <div class='displayOptions'> element that 
         * will be controlled by a connexions.dropdownForm widget.
         */
        displayOptions: {}
    },

    /************************
     * Private methods
     *
     */
    _init: function() {
        var self    = this;
        var opts    = self.options;

        self.element.addClass('pane');
        self._init_paginators();
        self._init_displayOptions();

        // Include a refresh button
        self.$refresh    = $(  '<div class="refreshPane icon-default">'
                            +  '<a ref="#" '
                            +     'class="ui-icon ui-icon-arrowrefresh-1-s" '
                            +     'title="refresh this pane">'
                            +   'refresh'
                            +  '</a>'
                            + '</div>')
                                .insertAfter(self.$displayOptions);
        self.$refresh.bind('click.uipane', function(e) {
            e.preventDefault(true);

            self.reload();
        });
    },

    _init_paginators: function() {
        var self        = this;
        var opts        = self.options;

        self.$paginators    = self.element.find('form.paginator');

        self.$paginators.each(function(idex) {
            var $pForm  = $(this);

            if ($pForm.data('paginator') === undefined)
            {
                // Not yet instantiated
                $pForm.paginator({namespace:    opts.namespace,
                                  form:         $pForm,
                                  disableHover: (idex !== 0)
                                  });
            }
            else if (idex !== 0)
            {
                // Already instantiated but we need to modify 'disableHover'
                $pForm.paginator('option', 'disableHover', true);
            }

            if (opts.pageCur === null)
            {
                opts.pageCur = $pForm.paginator('getPage');
                opts.pageVar = $pForm.paginator('getPageVar');
            }
        });

        self.$paginators.bind('submit.uipane', function(e) {
            var $pForm  = $(this);

            e.preventDefault(true);
            e.stopPropagation(true);
            e.stopImmediatePropagation(true);

            // Set the target page number
            opts.page       = $pForm.paginator('getPage');

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

        if (self.$displayOptions.data('dropdownForm') === undefined)
        {
            // Not yet instantiated
            var dOpts   = (opts.displayOptions === undefined
                            ? {}
                            : opts.displayOptions);

            if (dOpts.namespace === undefined)
            {
                dOpts.namespace = opts.namespace;
            }

            // Instantiate the connexions.dropdownForm widget
            self.$displayOptions.dropdownForm(dOpts);
        }

        self.$displayOptions.bind('submit.uipane', function(e) {
            // STOP the submit event
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            // WAIT for the 'apply' event before we reload
        });

        self.$displayOptions.bind('apply.uipane', function(e) {
            self.reload();
        });
    },

    _paneDestroy: function() {
        var self    = this;

        // Unbind events
        self.$paginators.unbind('.uipane');
        self.$displayOptions.unbind('.uipane');

        // Remove added elements
        self.$paginators.paginator('destroy');
        self.$displayOptions.dropdownForm('destroy');

        self.element.removeClass('pane');
    },

    /************************
     * Public methods
     *
     */
    reload: function(completionCb) {
        var self    = this;
        var opts    = self.options;
        var loc     = window.location;
        var url     = loc.toString();
        var qSep    = '?';

        if (opts.pageVar !== null)
        {
            var re  = new RegExp(opts.pageVar +'='+ opts.pageCur);
            var rep = opts.pageVar +'='+ (opts.page !== null
                                            ? opts.page
                                            : opts.pageCur);
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
            qSep = '&';
        }

        if (opts.hiddenVars !== null)
        {
            var ns      = opts.namespace;
            var hasNs   = (ns.length > 0);

            // Also include any hidden input values in the URL.
            $.each(opts.hiddenVars, function(name,val) {
                if (hasNs)  name = ns + $.ucFirst(name);
                url += qSep + name +'='+ val;
                qSep = '&';
            });
        }

        if (opts.partial !== null)
        {
            // AJAX reload of just this pane...
            url += qSep +'format=partial&part='+ opts.partial;

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

                        /* In with the new which should come with
                         * initialization (e.g. $('#id).pane({ ... }); )
                         */
                        self.element.replaceWith(data);

                        /*
                        self.element.html(data);
                        self._create();
                        */
                    },
                    complete:   function() {
                        self.element.unmask();
                        if ($.isFunction(completionCb))
                        {
                            completionCb.call(self.element);
                        }
                    }
            });
        }
        else
        {
            // Perform a full, synchronous reload...
            $.spinner();
            window.location.assign(url);
        }
    },

    destroy: function() {
        this._paneDestroy();
    }
});


}(jQuery));
