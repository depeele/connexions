/** @file
 *
 *  An extension of ui.tabs to allow bookmarkable URLS for the tabs.
 *
 *  For this instance, the href of a tab is the bookmarkable URL while the
 *  tab's related panel and load URL are defined as data items on the tab
 *  anchor.  For example:
 *      <ul>
 *       <li>
 *         <a href='/settings/account'
 *            data-panel.tabs='#account'
 *            data-load.tabs='/settings?format=partial&section=account'>
 *           <span>Account</span>
 *         </a>
 *       </li>
 *       ...
 *      </ul>
 *
 *      <div id='#account'>
 *          ... Account Tab Panel ...
 *      </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.tabs.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, document:false, location:false, window:false */
(function($) {

$.widget('connexions.tabs', $.ui.tabs, {
    version: "0.0.1",
    options: {
        /* These options may also be defined as data items on the tab anchor:
         *  <a href='%tab-href%'
         *     data-panel.tabs='%panel-id%'
         *     data-load.tabs='%panel-load-url%'>
         */
        panelId:    null,   // The ID of the related panel (panel.tabs)
        loadUrl:    null    // The load URL                (load.tabs)
    },

    load: function( index ) {
        index = this._getIndex( index );
        var self = this,
            o = this.options,
            $a = this.anchors.eq( index ),
            a = $a[ 0 ],
            url = $a.data( "load.tabs" );

        this.abort();

        // not remote or from cache
        if ( !url || this.element.queue( "tabs" ).length !== 0 &&
            $a.data( "cache.tabs" ) )
        {
            this.element.dequeue( "tabs" );
            return;
        }

        // load remote from here on
        this.lis.eq( index ).addClass( "ui-state-processing" );

        if ( o.spinner )
        {
            var span = $( "span", a );
            span.data( "label.tabs", span.html() ).html( o.spinner );
        }

        this.xhr = $.ajax( $.extend( {}, o.ajaxOptions, {
            url:        url,
            success:    function( r, s ) {
                // Connexions panelId {
                self._getPanel( $a ).html( r );
                //self.element.find( self._sanitizeSelector( a.hash ) ).html(r);
                // Connexions panelId }

                // take care of tab labels
                self._cleanup();

                if ( o.cache )
                {
                    $a.data( "cache.tabs", true );
                }

                self._trigger( "load", null,
                               self._ui( self.anchors[ index ],
                                         self.panels[ index ] ) );

                try {
                    o.ajaxOptions.success( r, s );
                }
                catch ( e ) {}
            },
            error:      function( xhr, s, e ) {
                // take care of tab labels
                self._cleanup();

                self._trigger( "load", null,
                               self._ui( self.anchors[ index ],
                                          self.panels[ index ] ) );

                try {
                    /* Passing index avoid a race condition when this method is
                     * called after the user has selected another tab.  Pass
                     * the anchor that initiated this request allows loadError
                     * to manipulate the tab content panel via $(a.hash)
                     */
                    o.ajaxOptions.error( xhr, s, index, a );
                }
                catch ( e ) {}
            }
        } ) );

        // last, so that load event is fired before show...
        self.element.dequeue( "tabs" );

        return this;
    },

    /**********************************************************
     * Private methods
     *
     */

    /** @brief  Retrieve the panel related to the tab represented by the
     *          provided anchor.
     *  @param  $a      The tab anchor.
     *
     *  @return The jQuery DOM element of the related tab panel.
     */
    _getPanel: function( $a ) {
        return this.element.find(
                        this._sanitizeSelector( $a.data( 'panel.tabs' )) );
    },

    /** @brief  Retrieve the jQuery DOM element representing the tab panel
     *          related to the indexed tab.
     *  @param  index   The desired/indexed tab.
     *
     *  @return The jQuery DOM element of the related tab panel.
     */
    _getPanelByIndex: function( index ) {
        return this.element.find(this._sanitizeSelector(
                                    $.data( this.anchors[ index ],
                                            'panel.tabs' )) );
    },


    /** @brief  For connexions tabs, if there is a data item 'panel', that
     *          contains the id of the target/inline element.
     *          This allows us to specify a bookmarkable URL as the panel's
     *          href while using an pre-rendered element for the content OR a
     *          second rendering URL.
     *  @param  init    Is this widget initialization?
     *
     *
     *  This is primarily a duplicate of the _tabify method from
     *  jquery.ui.tabs.js with the exception of the 'panelId' handling.
     */
    _tabify: function( init ) {
        var self = this,
            o = this.options,
            fragmentId = /^#.+/; // Safari 2 reports '#' for an empty hash

        this.list       = this.element.find('ol,ul').eq( 0 );
        this.lis        = $( ' > li:has(a[href])', this.list );
        this.anchors    = this.lis.map(function() {
            return $('a', this)[0];
        });
        this.panels = $( [] );

        this.anchors.each(function( i, a ) {
            var $a      = $( a );
            var href    = $a.attr( "href" );

            // Connexions panelId {
            var panelId = $a.data('panel.tabs');

            if (panelId === undefined)
            {
                /* NOT a pre-loaded, inline tab
                 *
                 * For dynamically created HTML that contains a hash as href IE
                 * < 8 expands such href to the full page url with hash and
                 * then misinterprets tab as ajax.  Same consideration applies
                 * for an added tab with a fragment identifier since
                 * a[href=#fragment-identifier] does unexpectedly not match.
                 * Thus normalize href attribute...
                 */
                var hrefBase = href.split( "#" )[ 0 ],
                    baseEl;
                if ( hrefBase &&
                    ( hrefBase === location.toString().split( "#" )[ 0 ] ||
                    ( baseEl = $( "base" )[ 0 ]) &&
                      hrefBase === baseEl.href ) )
                {
                    href   = a.hash;
                    a.href = href;
                }
            }
            else
            {
                // A pre-loaded, inline tab
                $a.data( "href.tabs", href );
                href   = panelId;
            }
            // Connexions panelId }

            // inline tab
            if ( fragmentId.test( href ) )
            {
                // Connexions panelId {
                $a.data( 'panel.tabs', href );
                $a.data( 'cache.tabs', href );
                self.panels = self.panels.add( self._getPanel( $a ) );

                /*
                self.panels = self.panels.add(
                                self.element.find(
                                    self._sanitizeSelector( href ) ) );
                // */
                // Connexions panelId }

            // remote tab
            // prevent loading the page itself if href is just "#"
            }
            else if ( href && href !== "#" )
            {
                // required for restore on destroy
                $a.data( "href.tabs", href );

                // TODO until #3808 is fixed strip fragment identifier from url
                // (IE fails to load from such url)
                $a.data( "load.tabs", href.replace( /#.*$/, "" ) );

                var id = self._tabId( a );
                a.href = "#" + id;
                // Connexions panelId {
                $a.data( 'panel.tabs', '#'+ id );
                // Connexions panelId }

                var $panel = self.element.find( "#" + id );
                if ( !$panel.length )
                {
                    $panel = $( o.panelTemplate )
                        .attr( "id", id )
                        .addClass( "ui-tabs-panel ui-widget-content "
                                   +    "ui-corner-bottom" )
                        .insertAfter( self.panels[ i - 1 ] || self.list );
                    $panel.data( "destroy.tabs", true );
                }
                self.panels = self.panels.add( $panel );
            // invalid tab href
            } else {
                o.disabled.push( i );
            }
        });

        // initialization from scratch
        if ( init ) {
            // attach necessary classes for styling
            this.element.addClass( "ui-tabs ui-widget ui-widget-content "
                                    + "ui-corner-all" );
            this.list.addClass( "ui-tabs-nav ui-helper-reset "
                                    + "ui-helper-clearfix ui-widget-header "
                                    + "ui-corner-all" );
            this.lis.addClass( "ui-state-default ui-corner-top" );
            this.panels.addClass( "ui-tabs-panel ui-widget-content "
                                    + "ui-corner-bottom" );

            // Selected tab
            // use "selected" option or try to retrieve:
            // 1. from fragment identifier in url
            // 2. from cookie
            // 3. from selected class attribute on <li>
            if ( o.selected === undefined )
            {
                if ( location.hash )
                {
                    this.anchors.each(function( i, a ) {
                        if ( a.hash == location.hash )
                        {
                            o.selected = i;
                            return false;
                        }
                    });
                }
                if ( typeof o.selected !== "number" && o.cookie )
                {
                    o.selected = parseInt( self._cookie(), 10 );
                }
                if ( typeof o.selected !== "number" &&
                     this.lis.filter( ".ui-tabs-selected" ).length )
                {
                    o.selected =
                        this.lis.index(
                                this.lis.filter( ".ui-tabs-selected" ) );
                }
                o.selected = o.selected || ( this.lis.length ? 0 : -1 );
            }
            else if ( o.selected === null )
            {   // usage of null is deprecated, TODO remove in next release
                o.selected = -1;
            }

            // sanity check - default to first tab...
            o.selected = ( ( o.selected >= 0 && this.anchors[ o.selected ] ) ||
                           o.selected < 0 )
                ? o.selected
                : 0;

            // Take disabling tabs via class attribute from HTML
            // into account and update option properly.
            // A selected tab cannot become disabled.
            o.disabled = $.unique( o.disabled.concat(
                $.map( this.lis.filter( ".ui-state-disabled" ), function(n,i){
                    return self.lis.index( n );
                })
            ) ).sort();

            if ( $.inArray( o.selected, o.disabled ) != -1 )
            {
                o.disabled.splice( $.inArray( o.selected, o.disabled ), 1 );
            }

            // highlight selected tab
            this.panels.addClass( "ui-tabs-hide" );
            this.lis.removeClass( "ui-tabs-selected ui-state-active" );

            // check for length avoids error when initializing empty list
            if ( o.selected >= 0 && this.anchors.length )
            {
                // Connexions panelId {
                var $panel  = self._getPanelByIndex( o.selected );
                $panel.removeClass( 'ui-tabs-hide' );
                /*
                self.element.find(
                        self._sanitizeSelector(
                            self.anchors[ o.selected ].hash ) )
                                    .removeClass( "ui-tabs-hide" );
                // */
                // Connexions panelId }

                this.lis.eq( o.selected )
                            .addClass( "ui-tabs-selected ui-state-active" );

                // seems to be expected behavior that the show callback is fired
                self.element.queue( "tabs", function() {
                    self._trigger( "show", null,
                                   self._ui( self.anchors[ o.selected ],
                                    // Connexions panelId {
                                             $panel[ 0 ]
                                    /*
                                             self.element.find(
                                                self._sanitizeSelector(
                                                    self.anchors[ o.selected ]
                                                            .hash ) )[ 0 ]
                                    // */
                                    // Connexions panelId }
                                   )
                    );
                });

                this.load( o.selected );
            }

            // clean up to avoid memory leaks in certain versions of IE 6
            // TODO: namespace this event
            $( window ).bind( "unload", function() {
                self.lis.add( self.anchors ).unbind( ".tabs" );
                self.lis = self.anchors = self.panels = null;
            });
        // update selected after add/remove
        }
        else
        {
            o.selected =
                this.lis.index( this.lis.filter( ".ui-tabs-selected" ) );
        }

        // update collapsible
        // TODO: use .toggleClass()
        this.element[ o.collapsible
                        ? "addClass"
                        : "removeClass" ]( "ui-tabs-collapsible" );

        // set or update cookie after init and add/remove respectively
        if ( o.cookie )
        {
            this._cookie( o.selected, o.cookie );
        }

        // disable tabs
        for ( var i = 0, li; ( li = this.lis[ i ] ); i++ )
        {
            $( li )[ $.inArray( i, o.disabled ) != -1 &&
                // TODO: use .toggleClass()
                !$( li ).hasClass( "ui-tabs-selected" )
                    ? "addClass"
                    : "removeClass" ]( "ui-state-disabled" );
        }

        // reset cache if switching from cached to not cached
        if ( o.cache === false )
        {
            this.anchors.removeData( "cache.tabs" );
        }

        /* remove all handlers before, tabify may run on existing tabs after
         * add or option change
         */
        this.lis.add( this.anchors ).unbind( ".tabs" );

        if ( o.event !== "mouseover" )
        {
            var addState = function( state, el ) {
                if ( el.is( ":not(.ui-state-disabled)" ) )
                {
                    el.addClass( "ui-state-" + state );
                }
            };
            var removeState = function( state, el ) {
                el.removeClass( "ui-state-" + state );
            };
            this.lis.bind( "mouseover.tabs" , function() {
                addState( "hover", $( this ) );
            });
            this.lis.bind( "mouseout.tabs", function() {
                removeState( "hover", $( this ) );
            });
            this.anchors.bind( "focus.tabs", function() {
                addState( "focus", $( this ).closest( "li" ) );
            });
            this.anchors.bind( "blur.tabs", function() {
                removeState( "focus", $( this ).closest( "li" ) );
            });
        }

        // set up animations
        var hideFx, showFx;
        if ( o.fx )
        {
            if ( $.isArray( o.fx ) )
            {
                hideFx = o.fx[ 0 ];
                showFx = o.fx[ 1 ];
            }
            else
            {
                hideFx = showFx = o.fx;
            }
        }

        // Reset certain styles left over from animation
        // and prevent IE's ClearType bug...
        function resetStyle( $el, fx ) {
            $el.css( "display", "" );
            if ( !$.support.opacity && fx.opacity )
            {
                $el[ 0 ].style.removeAttribute( "filter" );
            }
        }

        // Show a tab...
        var showTab = showFx
            ? function( clicked, $show ) {
                $( clicked ).closest( "li" )
                            .addClass( "ui-tabs-selected ui-state-active" );

                // avoid flicker that way
                $show.hide().removeClass( "ui-tabs-hide" )
                    .animate( showFx, showFx.duration || "normal", function() {
                        resetStyle( $show, showFx );
                        self._trigger( "show", null,
                                       self._ui( clicked, $show[ 0 ] ) );
                    });
            }
            : function( clicked, $show ) {
                $( clicked ).closest( "li" )
                            .addClass( "ui-tabs-selected ui-state-active" );
                $show.removeClass( "ui-tabs-hide" );
                self._trigger( "show", null, self._ui( clicked, $show[ 0 ] ) );
            };

        // Hide a tab, $show is optional...
        var hideTab = hideFx
            ? function( clicked, $hide ) {
                $hide.animate( hideFx, hideFx.duration || "normal", function() {
                    self.lis.removeClass( "ui-tabs-selected ui-state-active" );
                    $hide.addClass( "ui-tabs-hide" );
                    resetStyle( $hide, hideFx );
                    self.element.dequeue( "tabs" );
                });
            }
            : function( clicked, $hide, $show ) {
                self.lis.removeClass( "ui-tabs-selected ui-state-active" );
                $hide.addClass( "ui-tabs-hide" );
                self.element.dequeue( "tabs" );
            };

        /* attach tab event handler, unbind to avoid duplicates from former
         * tabifying...
         */
        this.anchors.bind( o.event + ".tabs", function() {
            var el = this,
                // Connexions panelId {
                $el     = $(el),
                $li     = $el.closest( "li" ),
                $hide   = self.panels.filter( ":not(.ui-tabs-hide)" ),
                $show   = self._getPanel( $el );
                //tab     = self.lis.index( $el ),
                //$show   = self._getPanelByIndex( tab );
                /*
                $li = $(el).closest( "li" ),
                $hide = self.panels.filter( ":not(.ui-tabs-hide)" ),
                $show = self.element.find( self._sanitizeSelector( el.hash ) );
                // */
                // Connexions panelId }

            /* If tab is already selected and not collapsible or tab disabled
             * or or is already loading or click callback returns false stop
             * here.  Check if click handler returns false last so that it is
             * not executed for a disabled or loading tab!
             */
            if ( ( $li.hasClass( "ui-tabs-selected" ) && !o.collapsible) ||
                $li.hasClass( "ui-state-disabled" ) ||
                $li.hasClass( "ui-state-processing" ) ||
                self.panels.filter( ":animated" ).length ||
                self._trigger( "select", null,
                               self._ui( this, $show[ 0 ] ) ) === false )
            {
                this.blur();
                return false;
            }

            o.selected = self.anchors.index( this );

            self.abort();

            // if tab may be closed
            if ( o.collapsible )
            {
                if ( $li.hasClass( "ui-tabs-selected" ) )
                {
                    o.selected = -1;

                    if ( o.cookie )
                    {
                        self._cookie( o.selected, o.cookie );
                    }

                    self.element.queue( "tabs", function() {
                        hideTab( el, $hide );
                    }).dequeue( "tabs" );

                    this.blur();
                    return false;
                }
                else if ( !$hide.length )
                {
                    if ( o.cookie )
                    {
                        self._cookie( o.selected, o.cookie );
                    }

                    self.element.queue( "tabs", function() {
                        showTab( el, $show );
                    });

                    /* TODO make passing in node possible, see also
                     * http://dev.jqueryui.com/ticket/3171
                     */
                    self.load( self.anchors.index( this ) );

                    this.blur();
                    return false;
                }
            }

            if ( o.cookie )
            {
                self._cookie( o.selected, o.cookie );
            }

            // show new tab
            if ( $show.length )
            {
                if ( $hide.length )
                {
                    self.element.queue( "tabs", function() {
                        hideTab( el, $hide );
                    });
                }
                self.element.queue( "tabs", function() {
                    showTab( el, $show );
                });

                self.load( self.anchors.index( this ) );
            }
            else
            {
                throw "jQuery UI Tabs: Mismatching fragment identifier.";
            }

            /* Prevent IE from keeping other link focussed when using the back
             * button and remove dotted border from clicked link. This is
             * controlled via CSS in modern browsers; blur() removes focus from
             * address bar in Firefox which can become a usability and annoying
             * problem with tabs('rotate').
             */
            if ( $.browser.msie )
            {
                this.blur();
            }
        });

        // disable click in any case
        this.anchors.bind( "click.tabs", function(){
            return false;
        });
    }
});

}(jQuery));
