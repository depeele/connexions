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
            tagDate:    {
                min:    'early',
                max:    'recent',
            },
            updateDate: {
                min:    'early',
                max:    'recent',
            },
            visitDate:  {
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

        this._loadObservers();

        this._render();
        this._bindEvents();

        cDebug.log("cSidebar::load(): complete");
    },

    showBookmarksContextMenu: function(event) {
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
        cDebug.log("cSidebar::showBookmarksContextMenu():");
    },

    openIn: function(e, item, where) {
        var bookmark    = item.getUserData('bookmark');
        cDebug.log("cSidebar::openIn(): where[ %s ], url[ %s ]",
                    where,
                    (bookmark && bookmark.url? bookmark.url:'*** UNKNOWN ***'));
    },

    properties: function(e, item) {
        var bookmark    = item.getUserData('bookmark');
        cDebug.log("cSidebar::properties(): url[ %s ]",
                    (bookmark && bookmark.url? bookmark.url:'*** UNKNOWN ***'));
    },

    edit: function(e, item) {
        var bookmark    = item.getUserData('bookmark');
        cDebug.log("cSidebar::edit(): url[ %s ]",
                    (bookmark && bookmark.url? bookmark.url:'*** UNKNOWN ***'));
    },

    'delete': function(e, item) {
        var bookmark    = item.getUserData('bookmark');
        cDebug.log("cSidebar::delete(): url[ %s ]",
                    (bookmark && bookmark.url? bookmark.url:'*** UNKNOWN ***'));
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

        this._renderBookmarks( );
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

        this._renderTags( );
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
                self._renderBookmarks();
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
                self._renderTags();
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
            self._renderBookmarks();
            self._renderTags();

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
        self._renderBookmarks();
        self._renderTags();
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

    _renderBookmarks: function() {
        var countBookmarks  = this.db.getTotalBookmarks();

        this.elBookmarksCount.value = countBookmarks;

        var sort    = this.bookmarksSort.by +' '+ this.bookmarksSort.order;
        this._update_bookmarksSort_ui();

        var bookmarks   = this.db.getBookmarks(sort);

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

            /*
            row.setAttribute("class", 'item-'+ (((idex+1) % 2)
                                                    ? 'odd'
                                                    : 'even'));
            // */
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

    _renderTags: function(sortOrder) {
        var countTags       = this.db.getTotalTags();

        this.elTagsCount.value      = countTags;

        var sort    = this.tagsSort.by +' '+ this.tagsSort.order;
        this._update_tagsSort_ui();

        var tags    = this.db.getAllTags(sort);

        /*
        cDebug.log('cSidebar::_renderTags(): '
                   +    'retrieved %s tags, total[ %s ]',
                   tags.length, countTags);
        // */

        // Empty any current items and re-fill
        this._emptyListItems( this.elTagList );
        for (var idex = 0; idex < tags.length; idex++)
        {
            var tag         = tags[idex];
            var row         = document.createElement("listitem");
            var name        = document.createElement("listcell");
            var freq        = document.createElement("listcell");

            /*
            row.setAttribute("class", 'item-'+ (((idex+1) % 2)
                                                    ? 'odd'
                                                    : 'even'));
            // */
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
        this.elBookmarksMenu
                .addEventListener("popupshowing", function (e){
                                    self.showBookmarksContextMenu(e);
                                  }, false);

        this.elBookmarksSortOrder
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

        this.elTagsSortOrder
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
