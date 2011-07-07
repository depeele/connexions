/** @file
 *
 *  A worker thread for adding bookmarks.
 *
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false, plusplus:false, regexp:false */
/*global Components:false, cDebug:false, CU:false, CC:false, CI:false, connexions:false, document:false, window:false */
var EXPORTED_SYMBOLS    = ["BookmarksWorker"];

var CC  = Components.classes;
var CI  = Components.interfaces;
var CR  = Components.results;
var CU  = Components.utils;

CU.import('resource://connexions/debug.js');

/*****************************************************************************
 * The bookmarks worker thread.
 *
 */

/** @brief  The thread used to add bookmarks OFF the main UI thread.
 *  @param  sync    The synchronization data containing:
 *                      deletions:  An array of bookmark identifiers;
 *                      updates:    An array of bookmark objects;
 */
var BookmarksWorker = function(sync) {
    this.sync = sync;
};

BookmarksWorker.prototype = {
    QueryInterface: function(iid) {
        if (iid.equals(CI.nsIRunnable) ||
            iid.equals(CI.nsISupports))
        {
            return this;
        }

        throw CR.NS_ERROR_NO_INTERFACE;
    },

    /** @brief  Signal observers.
     *  @param  subject The subject name;
     *  @param  data    The event data;
     *
     *  :NOTE: Relay on connexions for signaling.  It will handle invoking this
     *         signal request on the main thread.
     *
     *  @return this    For a fluent interface.
     */
    signal: function(subject, data) {
        connexions.signal(subject, data);

        return this;
    },

    /** @brief  Given a normalized bookmark object, attempt to add it and
     *          signal any progress.
     *  @param  bookmark    The normalized bookmark object.
     *
     *  :NOTE: Relay on connexions.
     *
     *  @return this for a fluent interface.
     */
    addBookmark: function(bookmark) {
        connexions.addBookmark(bookmark);

        return this;
    },

    /** @brief  Given a bookmark identifier (url), attempt to delete it and
     *          signal any progress.
     *  @param  id          The bookmark identifier (url).
     *
     *  :NOTE: Relay on connexions.
     *
     *  @return this for a fluent interface.
     */
    deleteBookmark: function(id) {
        connexions.deleteBookmark(id);

        return this;
    },


    /** @brief  Given a date string of the form 'YYYY-MM-DD hh:mm:ss', convert
     *          it to a UNIX timestamp.
     *  @param  dateStr     The date string;
     *
     *  @return The equivalent UNIX timestamp.
     */
    normalizeDate: function(dateStr) {
        var normalized  = 0;
        var parts       = dateStr.split(' ');
        var dateParts   = parts[0].split('-');
        var timeParts   = parts[1].split(':');
        var dateInfo    = {
            year:   parseInt(dateParts[0], 10),
            month:  parseInt(dateParts[1], 10),
            day:    parseInt(dateParts[2], 10),

            hour:   parseInt(timeParts[0], 10),
            min:    parseInt(timeParts[1], 10),
            sec:    parseInt(timeParts[2], 10)
        };

        try {
            var utc  = Date.UTC(dateInfo.year, dateInfo.month-1, dateInfo.day,
                                dateInfo.hour, dateInfo.min, dateInfo.sec);

            normalized = utc / 1000;
        } catch (e) {
            cDebug.log('BookmarksWorker::normalizeDate() ERROR: %s',
                        e.message);
        }

        return normalized;
    },

    /** @brief  Given an incoming "boolean" value, convert it to a native
     *          boolean.
     *  @param  val         The incoming value;
     *
     *  @return The equivalent boolean.
     */
    normalizeBool: function(val) {
        return (val ? true : false);
    },

    /** @brief  Given an incoming bookmark object, normalize it to an object
     *          acceptable for our local database.
     *  @param  bookmark    The bookmark object to normalize;
     *
     *  The incoming bookmark will have the form:
     *      userId:         string: useName,
     *      itemId:         string: itemUrl,
     *      name:           string,
     *      description:    string,
     *      rating:         integer,
     *      isFavorite:     integer,
     *      isPrivate:      integer,
     *      taggedOn:       string: 'YYYY-MM-DD hh:mm:ss',
     *      updatedOn:      string: 'YYYY-MM-DD hh:mm:ss',
     *      ratingAvg:      number,
     *      tags:           [ tag strings ],
     *
     *  We need the form:
     *      url:            string,
     *      urlHash:        string,
     *      name:           string,
     *      description:    string,
     *      rating:         integer,
     *      isFavorite:     boolean,
     *      isPrivate:      boolean,
     *      taggedOn:       integer: (UNIX Date/Time),
     *      updatedOn:      integer: (UNIX Date/Time),
     *      tags:           [ tag strings ],
     *      visitedOn:      integer: (UNIX Date/Time),
     *      visitCount:     integer,
     *      shortcut:       string
     *
     *  @return The equivalent normalized bookmark object;
     */
    normalizeBookmark: function(bookmark) {
        var self        = this;
        var normalized  = {
            url:            bookmark.itemId,
          //urlHash:        connexions.md5(bookmark.itemId),
            name:           bookmark.name,
            description:    bookmark.description,
            rating:         bookmark.rating,
            isFavorite:     self.normalizeBool(bookmark.isFavorite),
            isPrivate:      self.normalizeBool(bookmark.isPrivate),
            taggedOn:       self.normalizeDate(bookmark.taggedOn),
            updatedOn:      self.normalizeDate(bookmark.updatedOn),
            tags:           bookmark.tags,
        };
    
        return normalized;
    },

    run: function() {
        var self    = this;
        var sync    = self.sync;

        // Establish syncStatus.progress
        connexions.state.syncStatus.progress = {
            total:      sync.deletions.length + sync.updates.length,
            current:    0,
            added:      0,
            updated:    0,
            ignored:    0,
            deleted:    0
        };

        // /*
        cDebug.log('BookmarksWorker::run(): syncStatus[ %s ]',
                    cDebug.obj2str(connexions.state.syncStatus));
        // */

        // Signal our beginning progress update
        self.signal('connexions.syncProgress', connexions.state.syncStatus);

        // First, deletions
        for each (var bookmark in sync.deletions)
        {
            if (connexions.state.sync !== true)
            {
                // CANCEL the sync
                break;
            }

            self.deleteBookmark(bookmark.itemId);
        }

        // Second, updates
        for each (var bookmark in sync.updates)
        {
            if (connexions.state.sync !== true)
            {
                // CANCEL the sync
                break;
            }

            var normalized  = self.normalizeBookmark(bookmark);

            /*
            cDebug.log('BookmarksWorker::run(): normalized[ %s ]',
                        cDebug.obj2str(normalized));
            // */

            self.addBookmark( normalized );
        }

        connexions.state.sync = false;
        self.signal('connexions.syncEnd', connexions.state.syncStatus);
    }
};

/* Place this at the bottom to mitigate the dependency loop between
 * connexions.ja and bookmarks-worker.js
 */
CU.import('resource://connexions/connexions.js');
