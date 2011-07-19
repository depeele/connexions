/** @file
 *
 *  The bookmark properties overlay.
 *
 *  Requires: chrome://connexions/connexions.js
 *  which makes available:
 *      resource://connexions/debug.js          cDebug
 *      resource://connexions/db.js             cDb
 *      resource://connexions/connexions.js     connexions
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false, plusplus:false, regexp:false */
/*global Components:false, cDebug:false, CU:false, CC:false, CI:false, connexions:false, document:false, window:false */
CU.import('resource://connexions/debug.js');

function CBookmark(el)
{
    this.init(el);
}

CBookmark.prototype = {
    panel:      null,
    el:         {
        id:             null,
        url:            null,
        urlHash:        null,
        name:           null,
        description:    null,
        rating:         null,
        isFavorite:     null,
        isPrivate:      null,

        taggedOn:       null,
        updatedOn:      null,
        visitedOn:      null,
        visitCount:     null,
        shortcut:       null,

        tags:           null
    },

    init: function(el) {
        this.panel = el;
    },

    load: function(bookmark) {
        var self    = this;

        // Update the bookmark from the database
        self.bookmark = bookmark = connexions.db.getBookmarkById(bookmark.id);

        if ((bookmark.tags === undefined) &&
            (bookmark.id   !== undefined))
        {
            // Retrieve the tags associated with this bookmark
            bookmark.tags = connexions.db.getTags(bookmark.id);
        }

        /*
        cDebug.log('bookmark-properties::load(): bookmark[ %s ]',
                   cDebug.obj2str(bookmark));
        // */

        /* This ASSUMES that the keys in 'self.el' match those in a bookmark
         * object (see chrome/resource/db.js : _bookmarkFromRow()) AND the id's
         * used in chrome/content/bookmark-properties.xul
         */
        for(var key in self.el)
        {
            self.el[key] = document.getElementById('bookmark-'+ key);

            if (self.el[key] && (bookmark[key] !== undefined))
            {
                var val = bookmark[key];
                switch (key)
                {
                case 'isFavorite':
                case 'isPrivate':
                    val = (val ? 'true' : 'false');
                    break;

                case 'taggedOn':
                case 'updatedOn':
                case 'visitedOn':
                    var date = parseInt( val, 10 );
                    if (date > 0)
                    {
                        date = new Date ( date * 1000 );
                        val  = date.toLocaleString();
                    }
                    else
                    {
                        val = '';
                    }
                    break;

                case 'tags':
                    var tags    = [];
                    for each (var tag in val)
                    {
                        tags.push(tag.name);
                    }
                    val = tags.join(', ');
                    break;
                }

                /*
                cDebug.log('bookmark-properties::load(): %s value[ %s ]',
                           key, val);
                // */

                self.el[key].value = val;
            }
        }

        self._bindEvents();

        //cDebug.log("cBookmark.load(): complete");

        return self;
    },

    unload: function() {
        //cDebug.log("cBookmark.unload():");
    },

    open: function(el, event) {
                              // 'after_pointer',
        this.panel.openPopup(el, 'start_before',
                             0, 0,    // x,y
                             false,   // isContextMenu
                             false,   // attributesOverride
                             event);  // triggerEvent

        return this;
    },

    /************************************************************************
     * "Private" methods
     *
     */
    _bindEvents: function() {
        var self    = this;
    }
};
