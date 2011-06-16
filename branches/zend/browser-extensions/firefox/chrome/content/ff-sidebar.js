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
    mainWindow:     null,
    bookmarksCount: null,
    tagsCount:      null,
    bookmarkList:   null,
    tagList:        null,
    bookmarksMenu:  null,
    db:             null,

    init: function() {
        this.mainWindow =
            window.QueryInterface(CI.nsIInterfaceRequestor)
                    .getInterface(CI.nsIWebNavigation)
                        .QueryInterface(CI.nsIDocShellTreeItem)
                            .rootTreeItem
                                .QueryInterface(CI.nsIInterfaceRequestor)
                                    .getInterface(CI.nsIDOMWindow);

        this.db = connexions.db;    //new Connexions_Db();

        cDebug.log("cSidebar::init(): complete");
    },

    load: function() {
        // Retrieve references to interactive elements
        this.bookmarksCount =
                document.getElementById("sidebar-bookmarksCount");
        this.tagsCount      =
                document.getElementById("sidebar-tagsCount");

        // These are <tree> elements
        this.bookmarkList   =
                document.getElementById("sidebar-bookmarkList");
        this.tagList        =
                document.getElementById("sidebar-tagList");

        this.bookmarksMenu  =
                document.getElementById("sidebar-bookmarks-contextMenu");

        this._render();
        this._bindEvents();

        cDebug.log("cSidebar::load(): complete");
    },

    _render: function() {
        this._renderBookmarks();
        this._renderTags();
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

    _renderBookmarks: function(sortOrder) {
        var countBookmarks  = this.db.getTotalBookmarks();

        this.bookmarksCount.value = countBookmarks;

        var bookmarks   = this.db.getBookmarks(sortOrder);

        /*
        cDebug.log('cSidebar::_renderBookmarks(): '
                        + 'sortOrder[ %s ], '
                        + 'retrieved %s bookmarks, totalCount[ %s ]',
                   sortOrder, bookmarks.length, countBookmarks);
        // */

        // Empty any current items and re-fill
        this._emptyListItems( this.bookmarkList );
        for (var idex = 0; idex < bookmarks.length; idex++)
        {
            var bookmark    = bookmarks[idex];
            var row         = document.createElement("listitem");
            var name        = document.createElement("listcell");
            var propIcon    = document.createElement("listcell");
            var propCss     = [ 'listcell-iconic', 'bookmark-properties' ];

            row.setAttribute("class", 'item-'+ (((idex+1) % 2)
                                                    ? 'odd'
                                                    : 'even'));
            row.setUserData("bookmark", bookmark, null);

            name.setAttribute("label", bookmark.name);
            name.setAttribute("class", 'bookmark-name');
            name.setAttribute("crop",  'end');

            var propName    = [];
            if (bookmark.isFavorite)    { propName.push('favorite'); }
            if (bookmark.isPrivate)     { propName.push('private');  }
            propCss.push( propName.join('-') );

            propIcon.setAttribute("label", ' ');
            propIcon.setAttribute("class", propCss.join(' '));

            // Include the cells listitem and the listitem in the listbox
            row.appendChild( name );
            row.appendChild( propIcon );
            this.bookmarkList.appendChild( row );

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

        this.tagsCount.value      = countTags;

        var tags    = this.db.getAllTags(sortOrder);

        /*
        cDebug.log('cSidebar::_renderTags(): '
                   +    'retrieved %s tags, total[ %s ]',
                   tags.length, countTags);
        // */

        // Empty any current items and re-fill
        this._emptyListItems( this.tagList );
        for (var idex = 0; idex < tags.length; idex++)
        {
            var tag         = tags[idex];
            var row         = document.createElement("listitem");
            var name        = document.createElement("listcell");
            var freq        = document.createElement("listcell");

            row.setAttribute("class", 'item-'+ (((idex+1) % 2)
                                                    ? 'odd'
                                                    : 'even'));
            row.setUserData("tag", tag, null);

            name.setAttribute("label", tag.name);
            name.setAttribute("class", 'tag-name');
            name.setAttribute("crop",  'end');

            freq.setAttribute("label", tag.frequency);
            freq.setAttribute("class", 'tag-frequency');

            // Include the cells listitem and the listitem in the listbox
            row.appendChild( name );
            row.appendChild( freq );
            this.tagList.appendChild( row );

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
        this.bookmarksMenu
                .addEventListener("popupshowing", function (e){
                                    self.showBookmarksContextMenu(e);
                                  }, false);
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
         * var isImage  = (element instanceof Components.interfaces.nsIImageLoadingContent && element.currentURI);
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

    sortBookmarks: function(order) {
        cDebug.log("cSidebar::sortBookmarks(): order[ %s ]", order);

        this._renderBookmarks( order );
    },

    sortTags: function(order) {
        cDebug.log("cSidebar::sortTags(): order[ %s ]", order);

        this._renderTags( order );
    },

    unload: function() {
        cDebug.log("cSidebar::unload():");
    }
};

var cSidebar    = new CSidebar();

window.addEventListener("load",   function() { cSidebar.load(); },   false);
window.addEventListener("unload", function() { cSidebar.unload(); }, false);
