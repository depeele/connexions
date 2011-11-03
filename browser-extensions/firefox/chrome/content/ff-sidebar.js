/** @file
 *
 *  The primary browser sidebar.
 *
 *  Requires: chrome://connexions/connexions.js
 *  which makes available:
 *      resource://connexions/debug.js          cDebug
 *      resource://connexions/db.js             cDb
 *      resource://connexions/connexions.js     connexions
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false, plusplus:false, regexp:false */
/*global Components:false, cDebug:false, CU:false, CI:false, connexions:false, document:false, window:false */
CU.import('resource://connexions/debug.js');
CU.import('resource://connexions/Observers.js');

function CSidebar()
{
    this.init();
}

CSidebar.prototype = {
    mainWindow:             null,
    elBookmarksCount:       null,
    elTagsCount:            null,
    elBookmarkList:         null,
    elTagList:              null,

    elBookmarksSortOrder:   null,
    elBookmarksSortBy:      null,

    panelProperties:        null,

    selectedTags:           [],

    bookmarksSort:          {
        by:     'name',
        order:  'ASC',

        /* :TODO: These min/max strings SHOULD be dynamically loaded from
         *          chrome/locale/en-US/overlay.properties
         */
        orderLabels:    {
            name:       {
                min:    'a',
                max:    'z',
            },
            url:        {
                min:    'a',
                max:    'z',
            },
            rating:     {
                min:    'low',
                max:    'high',
            },
            taggedOn:    {
                min:    'early',
                max:    'recent',
            },
            updatedOn: {
                min:    'early',
                max:    'recent',
            },
            visitedOn:  {
                min:    'early',
                max:    'recent',
            },
            visitCount: {
                min:    'seldom',
                max:    'often',
            },
        },
    },

    elTagsSortOrder:        null,
    elTagsSortBy:           null,
    tagsSort:               {
        by:     'name',
        order:  'ASC',

        /* :TODO: These min/max strings SHOULD be dynamically loaded from
         *          chrome/locale/en-US/overlay.properties
         */
        orderLabels:    {
            name:       {
                min:    'a',
                max:    'z',
            },
            frequency:  {
                min:    'seldom',
                max:    'often',
            },
        },
    },

    elBookmarksMenu:        null,
    db:                     null,
    syncing:                false,

    /** @brief  Invoked on initial instance creation.
     *
     *  @return this for a fluent interface.
     */
    init: function() {
        this.mainWindow =
            window.QueryInterface(CI.nsIInterfaceRequestor)
                    .getInterface(CI.nsIWebNavigation)
                        .QueryInterface(CI.nsIDocShellTreeItem)
                            .rootTreeItem
                                .QueryInterface(CI.nsIInterfaceRequestor)
                                    .getInterface(CI.nsIDOMWindow);

        this.db = connexions.db;    //new Connexions_Db();

        /*
        this.prefs = CC['@mozilla.org/preferences-service;1']
                        .getService(CI.nsIPrefService)
                        .getBranch('extensions.connexions.sidebar.');
        // */

        //cDebug.log("cSidebar::init(): complete");

        return this;
    },

    /** @brief  Invoked when the sidebar is loaded, refresh the view.
     *
     *  @return this for a fluent interface.
     */
    load: function() {
        // Retrieve references to interactive elements
        this.elBookmarksCount =
                document.getElementById("sidebar-bookmarksCount");
        this.elTagsCount      =
                document.getElementById("sidebar-tagsCount");

        // Sort order and by
        this.elBookmarksSortOrder = 
                document.getElementById("sidebar-bookmarksSort-order");
        this.elBookmarksSortBy = 
                document.getElementById("sidebar-bookmarksSort-by");

        this.elTagsSortOrder = 
                document.getElementById("sidebar-tagsSort-order");
        this.elTagsSortBy = 
                document.getElementById("sidebar-tagsSort-by");

        // These are <listbox> elements
        this.elBookmarkList   =
                document.getElementById("sidebar-bookmarkList");
        this.elTagList        =
                document.getElementById("sidebar-tagList");

        this.elBookmarksMenu  =
                document.getElementById("sidebar-bookmarks-contextMenu");

        // Bookmark properties panel
        var panel   = document.getElementById("sidebar-bookmark-properties");
        this.panelProperties = new CBookmark( panel );


        // Setup the bookmarks sort information
        /*
        var bookmarksSort        = this.prefs
                                        .getCharPref('sortOrder.bookmarks');
        // */
        var bookmarksSort        =
                            connexions.pref('sidebar.sortOrder.bookmarks');
        //cDebug.log("cSidebar::load(): bookmarksSort[ %s ]", bookmarksSort);

        bookmarksSort            = bookmarksSort.split(/\s+/);
        this.bookmarksSort.by    = bookmarksSort[0];
        this.bookmarksSort.order = bookmarksSort[1].toUpperCase();

        // Setup the tags sort information
        //var tagsSort        = this.prefs.getCharPref('sortOrder.tags');
        var tagsSort        = connexions.pref('sidebar.sortOrder.tags');
        //cDebug.log("cSidebar::load(): tagsSort[ %s ]", tagsSort);

        tagsSort            = tagsSort.split(/\s+/);
        this.tagsSort.by    = tagsSort[0];
        this.tagsSort.order = tagsSort[1].toUpperCase();

        // Load observers, render, and bind events
        this._loadObservers()
            ._render()
            ._bindEvents();

        //cDebug.log("cSidebar::load(): complete");

        return this;
    },

    /** @brief  The bookmarks context menu is about to be presented.
     *  @param  event   The triggering event.
     *
     *  @return this for a fluent interface.
     */
    showBookmarksContextMenu: function(event) {
        var self    = this;

        /* show or hide the menuitem based on what the context menu is on
         *  gContextMenu.isTextSelected
         *              .isContentSelected
         *              .onLink
         *              .onImage
         *              .onMailtoLink
         *              .onTextInput
         *              .onKeywordFile
         *              .linkURL        (string or function)
         *              .linkText()
         */
        /* https://developer.mozilla.org/en/XUL/PopupGuide/ContextMenus
         *                              #Determining_what_was_Context_Clicked
         * https://developer.mozilla.org/en/DOM/document.popupNode
         *
         * var element  = document.popupNode;
         * var isImage  = (element instanceof
         *                  Components.interfaces.nsIImageLoadingContent &&
         *                      element.currentURI);
         *
         */

        /* (En/Dis)able context items based upon whether we are currently
         * authenticated.
         */
        var user            = connexions.getUser();
        var isAuthenticated = ( (user !== null) && (user.name !== undefined)
                                ? true
                                : false );
        var nItems          = self.elBookmarksMenu.children.length;

        /*
        cDebug.log("cSidebar::showBookmarksContextMenu(): "
                    +   "%sauthenticated, %s menu items",
                    (isAuthenticated ? '' : 'NOT '),
                    nItems);
        // */

        for (var idex = 0; idex < nItems; idex++)
        {
            var item    = self.elBookmarksMenu.children[idex];
            //if (item.nodeName !== 'menuitem')
            if (item.hasAttribute('authenticated'))
            {
                if (isAuthenticated)
                {
                    item.disabled = '';
                }
                else
                {
                    item.disabled = 'true';
                }
            }
        }

        //cDebug.log("cSidebar::showBookmarksContextMenu():");

        return self;
    },

    /** @brief  Given a search term filter the visible bookmarks and tags.
     *  @param  term    The desired search term.
     *
     *  @return this for a fluent interface.
     */
    search: function(term) {
        //cDebug.log("cSidebar::search(): term[ %s ]", term);

        // Locate all matching bookmarks
        var sort      = this.bookmarksSort.by +' '+ this.bookmarksSort.order;
        var bookmarks = connexions.db.getBookmarksByTerm( term, sort );

        /*
        cDebug.log('cSidebar::search():bookmarks[ %s ]',
                   cDebug.obj2str(bookmarks));
        // */

        this._renderBookmarks(bookmarks);

        // Locate all matching tags
        sort     = this.tagsSort.by +' '+ this.tagsSort.order;
        var tags = connexions.db.getTagsByTerm( term, sort );

        /*
        cDebug.log('cSidebar::search():tags[ %s ]',
                   cDebug.obj2str(tags));
        // */

        this._renderTags(tags);

        return this;
    },

    /** @brief  Given an array of tag objects, filter the visible bookmarks
     *          by those tags.
     *  @param  tags    An array of tag objects.
     *
     *  @return this for a fluent interface.
     */
    bookmarksFilterByTags: function( tags ) {
        var self    = this;

        // Remember the set of selected tags
        self.selectedTags = tags;

        /*
        cDebug.log('cSidebar::bookmarksFilterByTags():selected tags[ %s ]',
                   cDebug.obj2str(tags));
        // */

        /* Update the bookmarks to show only those that use ALL of the selected
         * tags.
         */
        var sort      = self.bookmarksSort.by +' '+ self.bookmarksSort.order;
        var bookmarks = connexions.db.getBookmarksByTags( tags, sort );

        /*
        cDebug.log('cSidebar::bookmarksFilterByTags():bookmarks[ %s ]',
                   cDebug.obj2str(bookmarks));
        // */

        self._renderBookmarks(bookmarks);

        /* Now, update the tags to show only those used by ANY of the current
         * bookmarks.
         */
        self.selectedTags = tags;

        sort       = self.tagsSort.by +' '+ self.tagsSort.order;
        var bmTags = connexions.db.getTagsByBookmarks( bookmarks, sort );
        self._renderTags(bmTags);

        return self;
    },

    /** @brief  Open the URL of the given item.
     *  @param  event   The triggering event;
     *  @param  item    The bookmark item;
     *  @param  where   Where to open ( [current], window, tab);
     *
     *  @return The (new) window/tab (null on error).
     */
    openIn: function(event, item, where) {
        var bookmark    = item.getUserData('bookmark');

        /*
        cDebug.log("cSidebar::openIn(): where[ %s ], url[ %s ]",
                    where,
                    (bookmark && bookmark.url? bookmark.url:'*** UNKNOWN ***'));
        // */

        if (! bookmark || (bookmark.url === undefined))
        {
            return null;
        }

        if (where === undefined)
        {
            // Determine 'where' by the incoming event
            if (event.altKey || event.metaKey)
            {
                where = 'window';
            }
            else if (event.shiftKey)
            {
                where = 'tab';
            }
        }

        return connexions.openIn( bookmark.url, where );
    },

    /** @brief  Present the properties of a current item.
     *  @param  event   The triggering event;
     *  @param  item    The associated item (MUST have 'bookmark' user data);
     *
     *  @return this for a fluent interface.
     */
    properties: function(event, item) {
        var bookmark    = item.getUserData('bookmark');

        /*
        cDebug.log("cSidebar::properties(): url[ %s ]",
                    (bookmark && bookmark.url? bookmark.url:'*** UNKNOWN ***'));
        // */

        this.panelProperties.load(bookmark)
                            .open(item, event);

        return this;
    },

    /** @brief  Present the bookmark edit/post dialog for a specific bookmark.
     *  @param  event   The triggering event;
     *  @param  item    The target bookmark item;
     *
     *  @return this for a fluent interface.
     */
    edit: function(event, item) {
        var bookmark    = item.getUserData('bookmark');

        // /*
        cDebug.log("cSidebar::edit(): url[ %s ]",
                    (bookmark && bookmark.url? bookmark.url:'*** UNKNOWN ***'));
        // */

        var query   = '?url='+ encodeURIComponent(bookmark.url);

        /*
                    + '&name='+ encodeURIComponent(name);
        if (description !== undefined)
        {
            query += '&description='+ encodeURIComponent(description);
        }
        if (tags !== undefined)
        {
            query += '&tags='+ encodeURIComponent(tags);
        }
        // */

        query += '&noNav&closeAction=close';

        var win = connexions.openPopupWindow( connexions.url('post'+ query),
                                              'Edit a Bookmark' );

        var timer   = CC['@mozilla.org/timer;1']
                        .createInstance(CI.nsITimer);

        function onClose(e)
        {
            win.removeEventListener('unload', onClose, false);

            // /*
            cDebug.log("cSidebar::edit(): url[ %s ] - window unload",
                        bookmark.url);
            // */

            /* Wait a bit and then request a sync.
             *
             * If we do this directly, the jsonRpc call fails for some reason.
             */
            timer.initWithCallback(function() {
                // When the edit window is unloaded/closed, trigger a sync
                connexions.sync();
            }, 1000, CI.nsITimer.TYPE_ONE_SHOT);
        }

        // Give the window time to begin the initial load
        timer.initWithCallback(function() {
            // When the edit window is unloaded/closed, trigger a sync
            win.addEventListener('unload', onClose, false);
        }, 1000, CI.nsITimer.TYPE_ONE_SHOT);

        return this;
    },

    /** @brief  Confirm the delete request and, if confirmed, request
     *          server-side deletion.
     *  @param  event   The triggering event;
     *  @param  item    The target bookmark item;
     *
     *  @return this for a fluent interface.
     */
    'delete': function(e, item) {
        var self        = this;
        var bookmark    = item.getUserData('bookmark');
        var user        = connexions.getUser();

        /*
        cDebug.log('cSidebar::delete(): bookmark[ %s ], user[ %s ]',
                   cDebug.obj2str(bookmark),
                   cDebug.obj2str(user));
        // */

        if ( (! bookmark) || (! user) || (user.name === undefined))
        {
            // NOT valid!
            return self;
        }

        var title       =
            connexions.getString('connexions.sidebar.bookmark.delete.title');
        var question    =
            connexions.getString('connexions.sidebar.bookmark.delete.confirm',
                                 bookmark.name);
        var answer      = connexions.confirm(title, question);

        if (! answer)
        {
            return self;
        }

        //cDebug.log("cSidebar::delete(): delete url[ %s ]", bookmark.url);

        var success = false;
        var res     = null;
        var params  = {
            // ID == userId:itemId
            id: user.name +':'+ bookmark.url
        };
        connexions.jsonRpc('bookmark.delete', params, {
            success: function(data, textStatus, xhr) {
                /*
                cDebug.log('cSidebar::delete(): RPC success: '
                            +   'jsonRpc return[ %s ]',
                            cDebug.obj2str(data));
                // */

                if (data.error !== null)
                {
                    // ERROR!
                    res = data.error;
                }
                else
                {
                    // SUCCESS -- Add all new bookmarks.
                    success = true;
                }
            },
            error:   function(xhr, textStatus, error) {
                /*
                cDebug.log('cSidebar::delete(): RPC error: '
                            +   '[ %s ]',
                            textStatus);
                // */

                res = {
                    code:       error,
                    message:    textStatus
                };
            },
            complete: function(xhr, textStatus) {
                /*
                cDebug.log('cSidebar::delete(): RPC complete: '
                            +   '[ %s ]',
                            textStatus);
                // */

                if (success === true)
                {
                    connexions.notify('Bookmark deleted',
                                    "Successfully deleted bookmark titled "
                                    + "'"+ bookmark.name +"'");

                    /* Delete our local copy.  If this succeeds, it will signal
                     * 'connexions.deleteBookmark' which will cause the
                     * sidebar to be refreshed.
                     */
                    connexions.db.deleteBookmark(bookmark.id);
                }
                else
                {
                    // ERROR
                    connexions.notify('Bookmark deletion failed',
                                      res.message);
                }
            }
        });

        return self;
    },

    /** @brief  Sort bookmarks, possibly changing the field or order.
     *  @param  field   The field to sort by [ bookmarksSort.by ];
     *  @param  order   The order to sort by [ bookmarksSort.order ];
     *
     *  @return this for a fluent interface.
     */
    sortBookmarks: function(by, order) {
        if (by !== undefined)
        {
            this.bookmarksSort.by = by;
        }
        if (order !== undefined)
        {
            this.bookmarksSort.order = order;
        }

        /*
        cDebug.log("cSidebar::sortBookmarks(): by[ %s ], order[ %s ]",
                   this.bookmarksSort.by, this.bookmarksSort.order);
        // */

        this._refreshBookmarks( );

        return this;
    },

    /** @brief  Sort tags, possibly changing the field or order.
     *  @param  field   The field to sort by [ tagsSort.by ];
     *  @param  order   The order to sort by [ tagsSort.order ];
     *
     *  @return this for a fluent interface.
     */
    sortTags: function(by, order) {
        if (by !== undefined)
        {
            this.tagsSort.by = by;
        }
        if (order !== undefined)
        {
            this.tagsSort.order = order;
        }

        /*
        cDebug.log("cSidebar::sortTags(): by[ %s ], order[ %s ]",
                   this.tagsSort.by, this.tagsSort.order);
        // */

        this._refreshTags( );

        return this;
    },

    /** @brief  Called when the sidebar is unloaded.
     *
     *  @return this for a fluent interface.
     */
    unload: function() {
        //cDebug.log("cSidebar::unload():");
        this._unloadObservers();

        return this;
    },

    /** @brief  Observer register notification topics.
     *  @param  subject The nsISupports object associated with the
     *                  notification;
     *  @param  topic   The notification topic string;
     *  @param  data    Any additional data
     *                  (JSON-encoded for 'connexions.*' topics);
     */
    observe: function(subject, topic, data) {
        /*
        cDebug.log('cSidebar::observe(): topic[ %s ], subject[ %s ]',
                   topic, cDebug.obj2str(subject));
        // */

        var self    = this;
        switch (topic)
        {
        case "connexions.bookmarkAdded":        // Bookmark added
        case "connexions.bookmarkDeleted":      // Bookmark deleted
        case "connexions.bookmarkUpdated":      // Bookmark updated
        case "connexions.bookmarksUpdated":     // Bookmarks and tags updated
            if (self.syncing !== true)
            {
                // Re-render the bookmarks
                self._refreshBookmarks();
            }
            break;

        case "connexions.bookmarksDeleted":     // Bookmarks and tags deleted
            // Empty the current set of bookmarks and tags
            self._emptyListItems( self.elBookmarkList );
            self._emptyListItems( self.elTagList );

            self.elBookmarksCount.value = 0;
            self.elTagsCount.value      = 0;
            break;

        case "connexions.tagAdded":
        case "connexions.tagsUpdated":
            if (self.syncing !== true)
            {
                // Re-render the tags
                self._refreshTags();
            }
            break;

        case "connexions.syncBegin":
            //cDebug.log('cSidebar::observe(): connexions.syncBegin:');
            // Disable rendering updates until syncing is complete
            self.syncing = true;
            break;

        case "connexions.syncEnd":
            //cDebug.log('cSidebar::observe(): connexions.syncEnd:');
            self.syncing = false;

            // Fall through

        case "connexions.tablesEmptied":
            // (Re)Render bookmarks and tags
            self._refreshBookmarks();
            self._refreshTags();

            break;
        }
    },

    /**************************************************************************
     * "Private" methods
     *
     */

    /** @brief  Primary rendering of sort information, bookmarks and tags
     *          lists.
     *
     *  @return this for a fluent interface.
     */
    _render: function() {
        var self    = this;

        /* Make sure the proper sort ordering is currently selected for
         * bookmarks and tags.
         */
        var items   = self.elBookmarksSortBy.menupopup.children;
        var nItems  = items.length;
        var selected= 0;
        for (var idex = 0; idex < nItems; idex++)
        {
            if (items[idex].value === self.bookmarksSort.by)
            {
                selected = idex;
                break;
            }
        }
        self.elBookmarksSortBy.selectedIndex = selected;

        items    = self.elTagsSortBy.menupopup.children;
        nItems   = items.length;
        selected = 0;
        for (var idex = 0; idex < nItems; idex++)
        {
            if (items[idex].value === self.tagsSort.by)
            {
                selected = idex;
                break;
            }
        }
        self.elTagsSortBy.selectedIndex = selected;


        // Render the bookmarks and tags
        self._refreshBookmarks()
            ._refreshTags();

        return self;
    },

    /** @brief  Remove all list items from the given itemList.
     *  @param  itemList    The itemlist to empty.
     *
     *  @return this for a fluent interface.
     */
    _emptyListItems: function(itemList) {
        /*
        cDebug.log("cSidebar::_emptyListItems: remove %s items",
                    itemList.itemCount);
        // */
        while (itemList.itemCount > 0)
        {
            itemList.removeItemAt( 0 );
        }

        return this;
    },

    /** @brief  Retrieve and render ALL bookmarks.
     *  
     *  @return this for a fluent interface.
     */
    _refreshBookmarks: function() {
        var self      = this;
        var sort      = self.bookmarksSort.by +' '+ self.bookmarksSort.order;
        var bookmarks = self.db.getBookmarks(sort);

        self.elBookmarksCount.value = bookmarks.length;
        self._update_bookmarksSort_ui();

        /*
        var countBookmarks  = self.db.getTotalBookmarks();
        self.elBookmarksCount.value = countBookmarks;
        // */

        self._renderBookmarks(bookmarks);

        return self;
    },

    /** @brief  Retrieve and render ALL tags.
     *  
     *  @return this for a fluent interface.
     */
    _refreshTags: function() {
        var self    = this;
        var sort    = self.tagsSort.by +' '+ self.tagsSort.order;
        var tags    = self.db.getAllTags(sort);

        self.elTagsCount.value = tags.length;
        self._update_tagsSort_ui();

        /*
        var countTags       = self.db.getTotalTags();
        self.elTagsCount.value      = countTags;
        */

        self._renderTags(tags);

        return self;
    },

    /** @brief  Render the given set of bookmarks.
     *  @param  tags    An array of bookmark objects to render;
     *
     *  @return this for a fluent interface.
     */
    _renderBookmarks: function(bookmarks) {
        var self                    = this;
        var countBookmarks          = bookmarks.length;
        self.elBookmarksCount.value = countBookmarks;

        /*
        cDebug.log('cSidebar::_renderBookmarks(): '
                        + 'sortOrder[ %s ], '
                        + 'retrieved %s bookmarks, totalCount[ %s ]',
                   sortOrder, bookmarks.length, countBookmarks);
        // */

        // Empty any current items and re-fill
        self._emptyListItems( self.elBookmarkList );
        for (var idex = 0; idex < bookmarks.length; idex++)
        {
            var bookmark    = bookmarks[idex];
            var row         = document.createElement("listitem");
            var name        = document.createElement("listcell");
            var propIcon    = document.createElement("listcell");
            //var propCss     = [ 'listcell-iconic', 'bookmark-properties' ];
            var propCss     = [ 'bookmark-properties' ];

            row.setUserData("bookmark", bookmark, null);

            name.setAttribute("label", bookmark.name);
            name.setAttribute("class", 'bookmark-name');
            name.setAttribute("crop",  'end');

            var propName    = [];
            if (bookmark.isFavorite)    { propName.push('favorite'); }
            if (bookmark.isPrivate)     { propName.push('private');  }
            if (bookmark.worldModify)   { propName.push('worldModify');  }
            propCss.push( propName.join('-') );

            //propIcon.setAttribute("label", 'Hey there!!!');
            propIcon.setAttribute("class", propCss.join(' '));

            //propIcon.appendChild(document.createElement("image"));

            // Include the cells listitem and the listitem in the listbox
            row.appendChild( name );
            row.appendChild( propIcon );
            self.elBookmarkList.appendChild( row );

            /*
            // Ensure the name is NOT longer than the width - 16px
            var nameWidth   = name.boxObject.width;
            var nameValue   = bookmark.name;
            var iconWidth   = propIcon.boxObject.width;
            while (nameWidth > (maxWidth - (iconWidth * 2)))
            {
                nameValue = nameValue.replace(/\.\.\.$/, '')
                                     .substr(0, nameValue.length - 5)
                          + '...';
                name.setAttribute('value', nameValue);
                nameWidth = name.boxObject.width;
            }
            // */

            /*
            cDebug.log('cSidebar::_renderBookmarks(): bookmark %s '
                        +   '{url[ %s ], urlHash[ %s ], name[ %s ], '
                        +   ' isFavorite[ %s ], isPrivate[ %s ], '
                        +   'worldModify[ %s ]}',
                        idex,
                        bookmark.url,
                        bookmark.urlHash,
                        bookmark.name,
                        bookmark.isFavorite,
                        bookmark.isPrivate,
                        bookmark.worldModify);
            // */
        }

        return self;
    },

    /** @brief  Render the given set of tags.
     *  @param  tags    An array of tag objects to render;
     *
     *  @return this for a fluent interface.
     */
    _renderTags: function(tags) {
        var self                = this;
        var countTags           = tags.length;
        self.elTagsCount.value  = countTags;

        /*
        cDebug.log('cSidebar::_renderTags(): '
                   +    '%s tags, %s selected[ %s ]',
                   tags.length,
                   self.selectedTags.length,
                   cDebug.obj2str(self.selectedTags));
        // */

        // Empty any current items and re-fill
        self.elTagList.clearSelection();
        self._emptyListItems( self.elTagList );
        for (var idex = 0; idex < tags.length; idex++)
        {
            var tag         = tags[idex];
            var row         = document.createElement("listitem");
            var name        = document.createElement("listcell");
            var freq        = document.createElement("listcell");

            row.setUserData("tag", tag, null);

            name.setAttribute("label", tag.name);
            name.setAttribute("class", 'tag-name');
            name.setAttribute("crop",  'end');

            freq.setAttribute("label", tag.frequency);
            freq.setAttribute("class", 'tag-frequency');

            // Include the cells listitem and the listitem in the listbox
            row.appendChild( name );
            row.appendChild( freq );
            self.elTagList.appendChild( row );

            // See if this tag should be selected
            for (var jdex = 0; jdex < self.selectedTags.length; jdex++)
            {
                var selTag  = self.selectedTags[jdex];

                if (tag.id === selTag.id)
                {
                    /*
                    cDebug.log('cSidebar::_render(): tag IS selected: '
                                +   'tag[ %s ]',
                                cDebug.obj2str(tag));
                    // */

                    row.setAttribute('selected', true);
                    self.elTagList.addItemToSelection( row );
                    break;
                }
            }

            /*
            cDebug.log('cSidebar::_render(): tag %s '
                        +   '{name[ %s ], frequency[ %s ]}',
                        idex,
                        tag.name,
                        tag.frequency);
            // */
        }

        return self;
    },

    /** @brief  Bind events.
     *
     *  @return this for a fluent interface.
     */
    _bindEvents: function() {
        var self    = this;

        /*****************************************************************
         * Bookmark list
         *
         */
        self.elBookmarkList
                .addEventListener('click', function (e){
                    if (e.button !== 0)
                    {
                       // NOT a left-click
                       return;
                    }

                    /*
                    cDebug.log('cSidebar::_bindEvents(click):'
                               + 'bookmarkList node[ %s ]',
                               e.target.nodeName);
                    // */

                    if (e.target.nodeName !== 'listitem')
                    {
                        return;
                    }

                    self.openIn(e, e.target);
                }, false);

        self.elBookmarksMenu
                .addEventListener('popupshowing', function (e){
                    self.showBookmarksContextMenu(e);
                }, false);

        self.elBookmarksSortOrder
                .addEventListener('click', function (e){
                    if (self.bookmarksSort.order === 'ASC')
                    {
                        self.bookmarksSort.order  =  'DESC';
                    }
                    else
                    {
                        self.bookmarksSort.order  = 'ASC';
                    }

                    self._update_bookmarksSort_ui();

                    self.sortBookmarks();
                }, false);


        /*****************************************************************
         * Tag list
         *
         */
        /*
        self.elTagList
                .addEventListener('select', function(event) {
                    var listBox = event.target;
                    var item    = self.elTagList.selectedItem;
                    var tag     = item.getUserData('tag');
                    cDebug.log('cSidebar::_bindEvents():tagList select: '
                                + '%s:%s selected, %s:%s, is %sslected',
                                listBox.nodeName,
                                self.elTagList.selectedCount,
                                item.nodeName,
                                tag.name,
                                (item.selected ? '' : 'NOT '));
                }, false);
        // */
        self.elTagList
                .addEventListener('click', function(event) {
                    if (event.button !== 0)
                    {
                        // NOT a left-click
                        return;
                    }

                    /* :NOTE: Since we're emptying and re-filling the
                     *        tag list anytime a tag is selected, we cannot
                     *        rely on the listbox to keep track of the
                     *        currently selected tags.
                     *
                     *        For this reason, keep our own list of selected
                     *        tags.
                     */
                    var item        = event.target;
                    var tag         = item.getUserData('tag');
                    var wasSelected =
                            self.selectedTags.some(function(selTag) {
                                return (tag.id === selTag.id);
                            });

                    item.selected = (! wasSelected);

                    /*
                    cDebug.log('cSidebar::_bindEvents():tagList click: '
                                + '%s items, item %s %sselected, '
                                + 'alt[%s], ctl[%s], shift[%s], meta[%s]',
                                self.elTagList.itemCount,
                                self.elTagList.getIndexOfItem(item),
                                (item.selected  ? '' : 'NOT '),
                                (event.altKey   ? '1' : '0'),
                                (event.ctrlKey  ? '1' : '0'),
                                (event.shiftKey ? '1' : '0'),
                                (event.metaKey  ? '1' : '0'));

                    // */

                    // Update our list of selected tags
                    var tags    = [];
                    for (var idex = 0; idex < self.elTagList.itemCount; idex++)
                    {
                        var tagItem = self.elTagList.getItemAtIndex(idex);
                        var tag     = tagItem.getUserData('tag');

                        /*
                        cDebug.log('cSidebar::_bindEvents():tagList click: '
                                    + '#%s [ %s ], is %sselected',
                                    idex,
                                    tag.name,
                                    (tagItem.selected ? '' : 'NOT '));
                        // */

                        if (tagItem.selected)
                        {
                            tags.push(tag);
                        }
                    }

                    /*
                    cDebug.log('cSidebar::_bindEvents():select tags[ %s ]',
                               cDebug.obj2str(tags));
                    // */

                    self.bookmarksFilterByTags( tags );
                 }, false);

        self.elTagsSortOrder
                .addEventListener('click', function (e){
                    if (self.tagsSort.order === 'ASC')
                    {
                        self.tagsSort.order  =  'DESC';
                    }
                    else
                    {
                        self.tagsSort.order  = 'ASC';
                    }

                    self._update_tagsSort_ui();

                    self.sortTags();
                }, false);

        return self;
    },

    /** @brief  Update the bookmarks sort order image and tooltip based upon
     *          the current bookmarks sort information.
     *
     *  @return this for a fluent interface.
     */
    _update_bookmarksSort_ui: function() {
        var self        = this;
        var orderLabels = self.bookmarksSort
                            .orderLabels[ self.bookmarksSort.by ];
        var tooltip     = self.elBookmarksSortOrder.getAttribute('tooltiptext')
                            .replace(/\s+\(.*\)$/, '');
        var order       = self.bookmarksSort.order.toLowerCase();
        if (self.bookmarksSort.order === 'ASC')
        {
            tooltip += ' ('+ orderLabels.min +'-'
                    +        orderLabels.max +')';
        }
        else
        {
            tooltip += ' ('+ orderLabels.max +'-'
                    +        orderLabels.min +')';
        }

        // Remember the choice in our preferences
        /*
        self.prefs.setCharPref('sortOrder.bookmarks',
                                self.bookmarksSort.by +' '+
                                self.bookmarksSort.order);
        // */
        connexions.pref('sidebar.sortOrder.bookmarks',
                        self.bookmarksSort.by +' '+ self.bookmarksSort.order);

        // Update the order tooltip and CSS classes
        self.elBookmarksSortOrder.setAttribute('tooltiptext', tooltip);
        self.elBookmarksSortOrder.setAttribute('class',
                                               'sort-order '
                                               + 'sort-'+ order);

        return self;
    },

    /** @brief  Update the tags sort order image and tooltip based upon
     *          the current tags sort information.
     *
     *  @return this for a fluent interface.
     */
    _update_tagsSort_ui: function() {
        var self        = this;
        var orderLabels = self.tagsSort
                            .orderLabels[ self.tagsSort.by ];
        var tooltip     = self.elTagsSortOrder.getAttribute('tooltiptext')
                            .replace(/\s+\(.*\)$/, '');
        var order       = self.tagsSort.order.toLowerCase();
        if (self.tagsSort.order === 'ASC')
        {
            tooltip += ' ('+ orderLabels.min +'-'
                    +        orderLabels.max +')';
        }
        else
        {
            tooltip += ' ('+ orderLabels.max +'-'
                    +        orderLabels.min +')';
        }

        // Remember the choice in our preferences
        /*
        self.prefs.setCharPref('sortOrder.tags',
                                self.tagsSort.by +' '+
                                self.tagsSort.order);
        // */
        connexions.pref('sidebar.sortOrder.tags',
                        self.tagsSort.by +' '+ self.tagsSort.order);

        // Update the order tooltip and CSS classes
        self.elTagsSortOrder.setAttribute('tooltiptext', tooltip);
        self.elTagsSortOrder.setAttribute('class',
                                               'sort-order '
                                               + 'sort-'+ order);

        return self;
    },

    /** @brief  Establish our state observers.
     *
     *  @return this for a fluent interface.
     */
    _loadObservers: function() {
        Observers.add("connexions.bookmarkAdded",    this);
        Observers.add("connexions.bookmarkDeleted",  this);
        Observers.add("connexions.bookmarkUpdated",  this);

        Observers.add("connexions.bookmarksDeleted", this);
        Observers.add("connexions.bookmarksUpdated", this);

        Observers.add("connexions.tagAdded",         this);
        Observers.add("connexions.tagsUpdated",      this);

        Observers.add("connexions.syncBegin",        this);
        Observers.add("connexions.syncEnd",          this);

        Observers.add("connexions.tablesEmptied",    this);

        return this;
    },

    /** @brief  Establish our state observers.
     *
     *  @return this for a fluent interface.
     */
    _unloadObservers: function() {
        Observers.remove("connexions.bookmarkAdded",    this);
        Observers.remove("connexions.bookmarkDeleted",  this);
        Observers.remove("connexions.bookmarkUpdated",  this);

        Observers.remove("connexions.bookmarksDeleted", this);
        Observers.remove("connexions.bookmarksUpdated", this);

        Observers.remove("connexions.tagAdded",         this);
        Observers.remove("connexions.tagsUpdated",      this);

        Observers.remove("connexions.syncBegin",        this);
        Observers.remove("connexions.syncEnd",          this);

        Observers.remove("connexions.tablesEmptied",    this);

        return this;
    }
};

var cSidebar    = new CSidebar();

window.addEventListener("load",   function() { cSidebar.load(); },   false);
window.addEventListener("unload", function() { cSidebar.unload(); }, false);
