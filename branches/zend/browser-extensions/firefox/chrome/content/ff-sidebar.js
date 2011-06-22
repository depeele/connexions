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

function CSidebar()
{
    this.init();
}

CSidebar.prototype = {
    os:                     CC['@mozilla.org/observer-service;1']
                                .getService(CI.nsIObserverService),

    mainWindow:             null,
    elBookmarksCount:       null,
    elTagsCount:            null,
    elBookmarkList:         null,
    elTagList:              null,

    elBookmarksSortOrder:   null,
    elBookmarksSortBy:      null,

    panelProperties:        null,

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

    init: function() {
        this.mainWindow =
            window.QueryInterface(CI.nsIInterfaceRequestor)
                    .getInterface(CI.nsIWebNavigation)
                        .QueryInterface(CI.nsIDocShellTreeItem)
                            .rootTreeItem
                                .QueryInterface(CI.nsIInterfaceRequestor)
                                    .getInterface(CI.nsIDOMWindow);

        this.db = connexions.db;    //new Connexions_Db();

        this.prefs = CC['@mozilla.org/preferences-service;1']
                        .getService(CI.nsIPrefService)
                        .getBranch('extensions.connexions.sidebar.');

        cDebug.log("cSidebar::init(): complete");
    },

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
        var bookmarksSort        = this.prefs
                                        .getCharPref('sortOrder.bookmarks');
        cDebug.log("cSidebar::load(): bookmarksSort[ %s ]", bookmarksSort);

        bookmarksSort            = bookmarksSort.split(/\s+/);
        this.bookmarksSort.by    = bookmarksSort[0];
        this.bookmarksSort.order = bookmarksSort[1].toUpperCase();

        // Setup the tags sort information
        var tagsSort        = this.prefs.getCharPref('sortOrder.tags');
        cDebug.log("cSidebar::load(): tagsSort[ %s ]", tagsSort);

        tagsSort            = tagsSort.split(/\s+/);
        this.tagsSort.by    = tagsSort[0];
        this.tagsSort.order = tagsSort[1].toUpperCase();

        // Load observers, render, and bind events
        this._loadObservers();

        this._render();
        this._bindEvents();

        cDebug.log("cSidebar::load(): complete");
    },

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

        cDebug.log("cSidebar::showBookmarksContextMenu(): "
                    +   "%sauthenticated, %s menu items",
                    (isAuthenticated ? '' : 'NOT '),
                    nItems);

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

        cDebug.log("cSidebar::showBookmarksContextMenu():");
    },

    /** @brief  Given a search term filter the visible bookmarks and tags.
     *  @param  term    The desired search term.
     *
     */
    search: function(term) {
        cDebug.log("cSidebar::search(): term[ %s ]", term);

        // Locate all matching bookmarks
        var sort      = this.bookmarksSort.by +' '+ this.bookmarksSort.order;
        var bookmarks = connexions.db.getBookmarksByTerm( term, sort );

        cDebug.log('ff-sidebar::search():bookmarks[ %s ]',
                   cDebug.obj2str(bookmarks));
        this._renderBookmarks(bookmarks);

        // Locate all matching tags
        sort     = this.tagsSort.by +' '+ this.tagsSort.order;
        var tags = connexions.db.getTagsByTerm( term, sort );

        cDebug.log('ff-sidebar::search():tags[ %s ]',
                   cDebug.obj2str(tags));

        this._renderTags(tags);
    },

    /** @brief  Given an array of tag objects, filter the visible bookmarks
     *          by those tags.
     *  @param  tags    An array of tag objects.
     *
     */
    bookmarksFilterByTags: function( tags ) {
        cDebug.log('ff-sidebar::bookmarksFilterByTags():select tags[ %s ]',
                   cDebug.obj2str(tags));

        var sort      = this.bookmarksSort.by +' '+ this.bookmarksSort.order;
        var bookmarks = connexions.db.getBookmarksByTags( tags, sort );

        cDebug.log('ff-sidebar::bookmarksFilterByTags():bookmarks[ %s ]',
                   cDebug.obj2str(bookmarks));
        this._renderBookmarks(bookmarks);

        /* Now, update the tags to show only those used by the current
         * bookmarks
        sort       = this.tagsSort.by +' '+ this.tagsSort.order;
        var bmTags = connexions.db.getTagsByBookmarks( bookmarks, sort );
        this._renderTags(bmTags, tags);
         */
    },

    /** @brief  Open the URL of the given item.
     *  @param  event   The triggering event;
     *  @param  item    The bookmark item;
     *  @param  where   Where to open ( [current], window, tab);
     */
    openIn: function(event, item, where) {
        var bookmark    = item.getUserData('bookmark');

        cDebug.log("cSidebar::openIn(): where[ %s ], url[ %s ]",
                    where,
                    (bookmark && bookmark.url? bookmark.url:'*** UNKNOWN ***'));

        if (! bookmark || (bookmark.url === undefined))
        {
            return;
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
     */
    properties: function(event, item) {
        var bookmark    = item.getUserData('bookmark');
        cDebug.log("cSidebar::properties(): url[ %s ]",
                    (bookmark && bookmark.url? bookmark.url:'*** UNKNOWN ***'));

        this.panelProperties.load(bookmark)
                            .open(item, event);
    },

    edit: function(e, item) {
        var bookmark    = item.getUserData('bookmark');
        cDebug.log("cSidebar::edit(): url[ %s ]",
                    (bookmark && bookmark.url? bookmark.url:'*** UNKNOWN ***'));

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

        connexions.openPopupWindow( connexions.url('post'+ query),
                                    'Edit a Bookmark' );
    },

    'delete': function(e, item) {
        var bookmark    = item.getUserData('bookmark');
        var user        = connexions.getUser();

        cDebug.log('cSidebar::delete(): bookmark[ %s ], user[ %s ]',
                   cDebug.obj2str(bookmark),
                   cDebug.obj2str(user));

        if ( (! bookmark) || (! user) || (user.name === undefined))
        {
            // NOT valid!
            return;
        }

        var title       =
            connexions.getString('connexions.sidebar.bookmark.delete.title');
        var question    =
            connexions.getString('connexions.sidebar.bookmark.delete.confirm',
                                 bookmark.name);
        var answer      = connexions.confirm(title, question);

        if (! answer)
        {
            return;
        }

        cDebug.log("cSidebar::delete(): delete url[ %s ]", bookmark.url);

        var success = false;
        var res     = null;
        var params  = {
            // ID == userId:itemId
            id: user.name +':'+ bookmark.url
        };
        connexions.jsonRpc('bookmark.delete', params, {
            success: function(data, textStatus, xhr) {
                // /*
                cDebug.log('resource-connexions::delete(): RPC success: '
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
                cDebug.log('resource-connexions::sync(): RPC error: '
                            +   '[ %s ]',
                            textStatus);
                res = {
                    code:       error,
                    message:    textStatus
                };
            },
            complete: function(xhr, textStatus) {
                cDebug.log('resource-connexions::sync(): RPC complete: '
                            +   '[ %s ]',
                            textStatus);
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
    },

    /** @brief  Sort bookmarks, possibly changing the field or order.
     *  @param  field   The field to sort by [ bookmarksSort.by ];
     *  @param  order   The order to sort by [ bookmarksSort.order ];
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

        cDebug.log("cSidebar::sortBookmarks(): by[ %s ], order[ %s ]",
                   this.bookmarksSort.by, this.bookmarksSort.order);

        this._refreshBookmarks( );
    },

    /** @brief  Sort tags, possibly changing the field or order.
     *  @param  field   The field to sort by [ tagsSort.by ];
     *  @param  order   The order to sort by [ tagsSort.order ];
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

        cDebug.log("cSidebar::sortTags(): by[ %s ], order[ %s ]",
                   this.tagsSort.by, this.tagsSort.order);

        this._refreshTags( );
    },

    unload: function() {
        cDebug.log("cSidebar::unload():");
        this._unloadObservers();
    },

    /** @brief  Observer register notification topics.
     *  @param  subject The nsISupports object associated with the
     *                  notification;
     *  @param  topic   The notification topic string;
     *  @param  data    Any additional data
     *                  (JSON-encoded for 'connexions.*' topics);
     */
    observe: function(subject, topic, data) {
        var self    = this;
        if (data !== undefined)
        {
            try {
                data = JSON.parse(data);
            } catch(e) {}
        }

        /*
        cDebug.log('ff-sidebar::observer(): topic[ %s ], data[ %s ]',
                   topic, cDebug.obj2str(data));
        // */

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
            cDebug.log('ff-sidebar::observe(): connexions.syncBegin:');
            // Disable rendering updates until syncing is complete
            self.syncing = true;
            break;

        case "connexions.syncEnd":
            cDebug.log('ff-sidebar::observe(): connexions.syncEnd:');
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
        self._refreshBookmarks();
        self._refreshTags();
    },

    _emptyListItems: function(itemList) {
        /*
        cDebug.log("cSidebar::_emptyListItems: remove %s items",
                    itemList.itemCount);
        // */
        while (itemList.itemCount > 0)
        {
            itemList.removeItemAt( 0 );
        }
    },

    _refreshBookmarks: function() {
        var countBookmarks  = this.db.getTotalBookmarks();

        this.elBookmarksCount.value = countBookmarks;

        var sort    = this.bookmarksSort.by +' '+ this.bookmarksSort.order;
        this._update_bookmarksSort_ui();

        var bookmarks   = this.db.getBookmarks(sort);

        this._renderBookmarks(bookmarks);
    },

    _refreshTags: function() {
        var countTags       = this.db.getTotalTags();

        this.elTagsCount.value      = countTags;

        var sort    = this.tagsSort.by +' '+ this.tagsSort.order;
        this._update_tagsSort_ui();

        var tags    = this.db.getAllTags(sort);

        this._renderTags(tags);
    },

    _renderBookmarks: function(bookmarks) {
        var countBookmarks  = bookmarks.length;
        this.elBookmarksCount.value = countBookmarks;

        /*
        cDebug.log('cSidebar::_renderBookmarks(): '
                        + 'sortOrder[ %s ], '
                        + 'retrieved %s bookmarks, totalCount[ %s ]',
                   sortOrder, bookmarks.length, countBookmarks);
        // */

        // Empty any current items and re-fill
        this._emptyListItems( this.elBookmarkList );
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
            propCss.push( propName.join('-') );

            //propIcon.setAttribute("label", 'Hey there!!!');
            propIcon.setAttribute("class", propCss.join(' '));

            //propIcon.appendChild(document.createElement("image"));

            // Include the cells listitem and the listitem in the listbox
            row.appendChild( name );
            row.appendChild( propIcon );
            this.elBookmarkList.appendChild( row );

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
                        +   ' isFavorite[ %s ], isPrivate[ %s ]}',
                        idex,
                        bookmark.url,
                        bookmark.urlHash,
                        bookmark.name,
                        bookmark.isFavorite,
                        bookmark.isPrivate);
            // */
        }
    },

    _renderTags: function(tags, selected) {
        var countTags       = tags.length;
        this.elTagsCount.value      = countTags;

        /*
        cDebug.log('cSidebar::_renderTags(): '
                   +    'retrieved %s tags, total[ %s ]',
                   tags.length, countTags);
        // */

        // Empty any current items and re-fill
        this._emptyListItems( this.elTagList );
        this.elTagList.clearSelection();
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
            this.elTagList.appendChild( row );

            if (selected !== undefined)
            {
                cDebug.log('cSidebar::_render(): is tag selected? '
                            +   'selected[ %s ], tag[ %s ]',
                            cDebug.obj2str(selected),
                            cDebug.obj2str(tag));

                // See if this tag should be selected
                for (var jdex = 0; jdex < selected.length; jdex++)
                {
                    var selTag  = selected[jdex];

                    if (tag.id === selTag.id)
                    {
                        cDebug.log('cSidebar::_render(): tag IS selected: '
                                    +   'tag[ %s ]',
                                    cDebug.obj2str(tag));

                        this.elTagList.addItemToSelection( row );
                        break;
                    }
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
    },

    _bindEvents: function() {
        var self    = this;

        /*****************************************************************
         * Bookmark list
         *
         */
        self.elBookmarkList
                .addEventListener("click", function (e){
                    if (e.button !== 0)
                    {
                       // NOT a left-click
                       return;
                    }

                    cDebug.log('ff-sidebar::_bindEvents(click):'
                               + 'bookmarkList node[ %s ]',
                               e.target.nodeName);

                    if (e.target.nodeName !== 'listitem')
                    {
                        return;
                    }

                    self.openIn(e, e.target);
                }, false);

        self.elBookmarksMenu
                .addEventListener("popupshowing", function (e){
                    self.showBookmarksContextMenu(e);
                }, false);

        self.elBookmarksSortOrder
                .addEventListener("click", function (e){
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
        self.elTagList
                .addEventListener("click", function(e) {
                    if (e.button !== 0)
                    {
                        // NOT a left-click
                        return;
                    }

                    var items   = self.elTagList.selectedItems;
                    var nItems  = items.length;

                    /*
                    cDebug.log('ff-sidebar::_bindEvents():tagList select: '
                                + '%s/%s',
                                self.elTagList.selectedCount,
                                nItems);
                    // */

                    var tags    = [];
                    for (var idex = 0; idex < nItems; idex++)
                    {
                        var item    = self.elTagList.getSelectedItem(idex);
                        var tag     = item.getUserData('tag');

                        /*
                        cDebug.log('ff-sidebar::_bindEvents():tagList select: '
                                    + '%s: item[ %s ], tag[ %s ]',
                                    idex,
                                    cDebug.obj2str(item),
                                    cDebug.obj2str(tag));
                        // */
                        if (! tag)
                        {
                            continue;
                        }

                        tags.push(tag);
                    }

                    /*
                    cDebug.log('ff-sidebar::_bindEvents():select tags[ %s ]',
                               cDebug.obj2str(tags));
                    // */

                    self.bookmarksFilterByTags( tags );
                 }, false);

        self.elTagsSortOrder
                .addEventListener("click", function (e){
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
    },

    /** @brief  Update the bookmarks sort order image and tooltip based upon
     *          the current bookmarks sort information.
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
        self.prefs.setCharPref('sortOrder.bookmarks',
                                self.bookmarksSort.by +' '+
                                self.bookmarksSort.order);

        // Update the order tooltip and CSS classes
        self.elBookmarksSortOrder.setAttribute('tooltiptext', tooltip);
        self.elBookmarksSortOrder.setAttribute('class',
                                               'sort-order '
                                               + 'sort-'+ order);
    },

    /** @brief  Update the tags sort order image and tooltip based upon
     *          the current tags sort information.
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
        self.prefs.setCharPref('sortOrder.tags',
                                self.tagsSort.by +' '+
                                self.tagsSort.order);

        // Update the order tooltip and CSS classes
        self.elTagsSortOrder.setAttribute('tooltiptext', tooltip);
        self.elTagsSortOrder.setAttribute('class',
                                               'sort-order '
                                               + 'sort-'+ order);
    },

    /** @brief  Establish our state observers.
     */
    _loadObservers: function() {
        this.os.addObserver(this, "connexions.bookmarkAdded",    false);
        this.os.addObserver(this, "connexions.bookmarkDeleted",  false);
        this.os.addObserver(this, "connexions.bookmarkUpdated",  false);
        this.os.addObserver(this, "connexions.bookmarksDeleted", false);
        this.os.addObserver(this, "connexions.bookmarksUpdated", false);

        this.os.addObserver(this, "connexions.tagAdded",         false);
        this.os.addObserver(this, "connexions.tagsUpdated",      false);

        this.os.addObserver(this, "connexions.syncBegin",        false);
        this.os.addObserver(this, "connexions.syncEnd",          false);

        this.os.addObserver(this, "connexions.tablesEmptied",    false);
    },

    /** @brief  Establish our state observers.
     */
    _unloadObservers: function() {
        this.os.removeObserver(this, "connexions.bookmarkAdded");
        this.os.removeObserver(this, "connexions.bookmarkDeleted");
        this.os.removeObserver(this, "connexions.bookmarkUpdated");
        this.os.removeObserver(this, "connexions.bookmarksDeleted");
        this.os.removeObserver(this, "connexions.bookmarksUpdated");

        this.os.removeObserver(this, "connexions.tagAdded");
        this.os.removeObserver(this, "connexions.tagsUpdated");

        this.os.removeObserver(this, "connexions.syncBegin");
        this.os.removeObserver(this, "connexions.syncEnd");

        this.os.removeObserver(this, "connexions.tablesEmptied");
    }
};

var cSidebar    = new CSidebar();

window.addEventListener("load",   function() { cSidebar.load(); },   false);
window.addEventListener("unload", function() { cSidebar.unload(); }, false);
