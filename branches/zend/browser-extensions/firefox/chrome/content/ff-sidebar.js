CU.import("resource://connexions/debug.js");

function _cSidebar()
{
    this.init();
}

_cSidebar.prototype = {
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

        this.db = new Connexions_Db();

        cDebug.log("cSidebar::init(): complete");
    },

    load: function() {
        // Retrieve references to interactive elements
        this.bookmarksCount = document
                                    .getElementById("sidebar-bookmarksCount");
        this.tagsCount      = document
                                    .getElementById("sidebar-tagsCount");

        this.bookmarkList   = document
                                    .getElementById("sidebar-bookmarkList");
        this.tagList        = document
                                    .getElementById("sidebar-tagList");

        this.bookmarksMenu  = document
                                    .getElementById("sidebar-bookmarks-contextMenu");

        this._render();
        this._bindEvents();

        cDebug.log("cSidebar::load(): complete");
    },

    _render: function() {
        var countBookmarks  = this.db.getTotalBookmarks();
        var countTags       = this.db.getTotalTags();
        var maxWidth        = this.bookmarkList.boxObject.width;

        cDebug.log('cSidebar::_render(): bookmarks[ %s ], tags[ %s ]',
                   countBookmarks, countTags);
        cDebug.log('cSidebar::_render(): bookmarkList width[ %s ]',
                   maxWidth);

        this.bookmarksCount.value = countBookmarks;
        this.tagsCount.value      = countTags;

        var bookmarks   = this.db.getBookmarks();
        cDebug.log('cSidebar::_render(): retrieved %s bookmarks',
                   bookmarks.length);

        for (var idex = 0; idex < bookmarks.length; idex++)
        {
            var bookmark    = bookmarks[idex];
            var item        = document.createElement("richlistitem");
            var name        = document.createElement("label");
            var propIcon    = document.createElement("image");
            var propCss     = [ 'bookmark-properties' ];

            name.setAttribute("value", bookmark.name);
            name.setAttribute("class", 'bookmark-name');

            if (bookmark.isFavorite)
            {
                if (bookmark.isPrivate)
                {
                    propCss.push('favorite-private');
                }
                else
                {
                    propCss.push('favorite');
                }
            }
            else if (bookmark.isPrivate)
            {
                propCss.push('private');
            }
            propIcon.setAttribute("class", propCss.join(' '));

            item.appendChild( name );
            item.appendChild( propIcon );

            this.bookmarkList.appendChild( item );

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

            cDebug.log('cSidebar::_render(): name width:3 [ %s ], '
                        +   'iconProps CSS [ %s ], prop width[ %s ]',
                       name.boxObject.width,
                       propCss.join(' '),
                       propIcon.boxObject.width);

            cDebug.log('cSidebar::_render(): bookmark %s '
                        +   '{url[ %s ], urlHash[ %s ], name[ %s ], '
                        +   ' isFavorite[ %s ], isPrivate[ %s ]}',
                        idex,
                        bookmark.url,
                        bookmark.urlHash,
                        bookmark.name,
                        bookmark.isFavorite,
                        bookmark.isPrivate);
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
        cDebug.log("cSidebar::openIn(): where[ %s ]", where);
    },

    properties: function(e, item) {
        cDebug.log("cSidebar::properties():");
    },

    edit: function(e, item) {
        cDebug.log("cSidebar::edit():");
    },

    delete: function(e, item) {
        cDebug.log("cSidebar::delete():");
    },

    sortBookmarks: function(order) {
        cDebug.log("cSidebar::sortBookmarks(): order[ %s ]", order);
    },

    sortTags: function(order) {
        cDebug.log("cSidebar::sortTags(): order[ %s ]", order);
    },

    unload: function() {
        cDebug.log("cSidebar::unload():");
    }
};

var cSidebar    = new _cSidebar();

window.addEventListener("load",   function() { cSidebar.load(); },   false);
window.addEventListener("unload", function() { cSidebar.unload(); }, false);
