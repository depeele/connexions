/** @file
 *
 *  Local connexions database.
 *
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false, plusplus:false, regexp:false */
/*global Components:false, cDebug:false */
var EXPORTED_SYMBOLS    = ["Connexions_Db", "cDb"];

var CC  = Components.classes;
var CI  = Components.interfaces;
var CR  = Components.results;
var CU  = Components.utils;

CU.import("resource://connexions/debug.js");

var wrapper = new Components.Constructor(
                        "@mozilla.org/storage/statement-wrapper;1",
                        CI.mozIStorageStatementWrapper,
                        "initialize");

function Connexions_Db()
{
    this.init();
}

Connexions_Db.prototype = {
    os:             CC['@mozilla.org/observer-service;1']
                        .getService(CI.nsIObserverService),

    initialized:    false,
    noSignals:      false,
    dbConnection:   null,
    dbStatements:   {},
    dbSchema:       {
        tables: {
            bookmarks:
                    "url TEXT NOT NULL DEFAULT \"\" COLLATE NOCASE,"
                   +"urlHash VARCHAR(64) NOT NULL DEFAULT \"\" COLLATE NOCASE,"
                   +"name VARCHAR(255) NOT NULL DEFAULT \"\" COLLATE NOCASE,"
                   +"description NOT NULL DEFAULT \"\","
                   +"rating UNSIGNED NOT NULL DEFAULT 0,"
                   +"isFavorite BOOL NOT NULL DEFAULT 0,"
                   +"isPrivate BOOL NOT NULL DEFAULT 0,"
                   +"taggedOn DATETIME NOT NULL DEFAULT 0,"
                   +"updatedOn DATETIME NOT NULL DEFAULT 0,"
                   +"visitedOn DATETIME NOT NULL DEFAULT 0,"
                   +"visitCount UNSIGNED NOT NULL DEFAULT 0,"
                   +"shortcut VARCHAR(64) NOT NULL DEFAULT \"\"",
            tags:  "name VARCHAR(32) NOT NULL UNIQUE COLLATE NOCASE",
            bookmarkTags:
                    "bookmarkId UNSIGNED NOT NULL,"
                   +"tagId UNSIGNED NOT NULL",
            state:  "name VARCHAR(32) NOT NULL UNIQUE,"
                   +"value TEXT NOT NULL DEFAULT \"\""
        },
        indices: {
            bookmarks_alpha:      "bookmarks(name ASC)",
            bookmarks_url:        "bookmarks(url ASC)",
            bookmarks_visitedOn:  "bookmarks(visitedOn DESC, name)",
            bookmarks_visitCount: "bookmarks(visitCount DESC, name)",
            tags_alpha:           "tags(name)",
            bookmarks_tag:        "bookmarkTags(bookmarkId, tagId)",
            tags_bookmark:        "bookmarkTags(tagId, bookmarkId)",
            state_alpha:          "state(name)"
        }
    },

    /** @brief  Initialize this instance.
     *
     *  @return this    For a fluent interface.
     */
    init: function()
    {
        if (this.initialized === true)  { return; }

        // initialization code
        this.initialized = true;

        var dirService   = CC["@mozilla.org/file/directory_service;1"]
                                .getService(CI.nsIProperties);
        var dbService    = CC["@mozilla.org/storage/service;1"]
                                .getService(CI.mozIStorageService);

        var dbFile       = dirService.get("ProfD", CI.nsIFile);
        dbFile.append("connexions.sqlite");

        if (!dbFile.exists())
        {
            // Create the database
            this.dbConnection = this._dbCreate(dbService, dbFile);

            /*
            cDebug.log("Connexions_Db::init(): Created database "
                            + "[ "+ dbFile.path +" ]");
            // */
        }
        else
        {
            // Simply open the database
            this.dbConnection = dbService.openDatabase(dbFile);
            /*
            cDebug.log("Connexions_Db::init(): Opened database "
                            + "[ "+ dbFile.path +" ]");
            // */
        }

        this._loadObservers();

        return this;
    },

    setConnexions: function(connexionsInst) {
        connexions = connexionsInst;
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
        if (this.noSignals !== true)
        {
            connexions.signal(subject, data);
        }
        return this;
    },

    /** @brief  Observer register notification topics.
     *  @param  subject The nsISupports object associated with the
     *                  notification;
     *  @param  topic   The notification topic string;
     *  @param  data    Any additional data;
     */
    observe: function(subject, topic, data) {
        var self    = this;
        /*
        if (data !== undefined)
        {
            try {
                data = JSON.parse(data);
            } catch(e) {}
        }
        // */

        // /*
        cDebug.log('Connexions_Db::observe(): topic[ %s ]',
                   topic);
        // */

        switch (topic)
        {
        case 'connexions.syncBegin':
            // Squelch all signaling until syncEnd
            self.noSignals = true;
            break;

        case 'connexions.syncEnd':
            self.noSignals = false;
            break;
        }
    },

    /************************************************************************
     * bookmarks table methods
     *
     */

    /** @brief  Retrieve the total number of bookmarks.
     *
     *  @return The total number of bookmarks.
     */
    getTotalBookmarks: function()
    {
        var fname   = 'getTotalBookmarks';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT COUNT(rowid) FROM bookmarks';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var count   = 0;
        try {
            if (stmt.executeStep())
            {
                count = stmt.getInt64(0);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return count;
    },

    /** @brief  Given a url, retrieve the matching bookmark.
     *  @param  url     The target url.
     *
     *  @return The bookmark id or null if not found.
     */
    getBookmarkId: function(url)
    {
        var fname   = 'getBookmarkId';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT rowid FROM bookmarks WHERE url = ?1';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var id  = null;
        try {
            stmt.bindUTF8StringParameter(0, url);
            if (stmt.executeStep())
            {
                id = stmt.getInt64(0);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return id;
    },

    /** @brief  Retrieve a bookmark object by url.
     *  @param  url     The target url.
     *
     *  @return A bookmark object (empty if not found).
     */
    getBookmarkByUrl: function(url)
    {
        var fname   = 'getBookmarkByUrl';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT bookmarks.rowid,bookmarks.* FROM bookmarks '
                    +   'WHERE bookmarks.url=?1';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var bookmark    = null;
        try {
            stmt.bindUTF8StringParameter(0, url);
            if (stmt.executeStep())
            {
                bookmark = self._bookmarkFromRow(stmt);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return bookmark;
    },

    /** @brief  Retrieve a bookmark object by id.
     *  @param  id      The target id.
     *
     *  @return A bookmark object (empty if not found).
     */
    getBookmarkById: function(id)
    {
        var fname   = 'getBookmarkById';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT bookmarks.rowid,bookmarks.* FROM bookmarks '
                    +   'WHERE bookmarks.rowid=?1';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var bookmark    = null;
        try {
            stmt.bindInt64Parameter(0, id);
            if (stmt.executeStep())
            {
                bookmark = self._bookmarkFromRow(stmt);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return bookmark;
    },

    /** @brief  Insert a new bookmark
     *  @param  bookmark    The bookmark object:
     *                          url, urlHash, name, description,
     *                          rating, isFavorite, isPrivate, tags
     *
     *  @return The id of the new bookmark
     */
    insertBookmark: function(bookmark)
    {
        var fname   = 'insertBookmark';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            /*  1   url
             *  2   urlHash
             *  3   name
             *  4   description
             *  5   rating
             *  6   isFavorite
             *  7   isPrivate
             *  8   taggedOn
             *  9   updatedOn
             *  10  visitedOn
             *  11  visitCount
             *  12  shortcut
             */
            var sql = 'INSERT INTO bookmarks VALUES(?1, ?2, ?3, ?4, ?5, ?6, '
                    +                              '?7, ?8, ?9, ?10, ?11, ?12)';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var id  = null;
        try {
            var now = (new Date()).getTime() / 1000;

            stmt.bindUTF8StringParameter(0, bookmark.url);
            stmt.bindUTF8StringParameter(1, (bookmark.urlHash
                                              ? bookmark.urlHash : ''));
            stmt.bindUTF8StringParameter(2, (bookmark.name
                                              ? bookmark.name : ''));
            stmt.bindUTF8StringParameter(3, (bookmark.description
                                              ? bookmark.description : ''));
            stmt.bindInt64Parameter(4, (bookmark.rating
                                              ? bookmark.rating : 0));
            stmt.bindInt32Parameter(5, (bookmark.isFavorite
                                              ? 1 : 0));
            stmt.bindInt32Parameter(6, (bookmark.isPrivate
                                              ? 1 : 0));
            stmt.bindInt64Parameter(7, (bookmark.taggedOn
                                              ? bookmark.taggedOn : now));
            stmt.bindInt64Parameter(8, (bookmark.updatedOn
                                              ? bookmark.updatedOn : now));
            stmt.bindInt64Parameter(9, (bookmark.visitedOn
                                              ? bookmark.visitedOn : 0));
            stmt.bindInt32Parameter(10, (bookmark.visitCount
                                              ? bookmark.visitedCount : 0));
            stmt.bindUTF8StringParameter(11, (bookmark.shortcut
                                              ? bookmark.shortcut : ''));

            stmt.execute();

            id = self.dbConnection.lastInsertRowID;

            /*
            cDebug.log("Connexions:Db:%s(): insert id[ %s ]",
                       fname, id);
            // */

            self.signal('connexions.bookmarkAdded', id);
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return id;
    },

    /** @brief  Add/Update a bookmark
     *  @param  bookmark    The (new) bookmark object;
     *
     *  @return The new/updated bookmark object with the (new) bookmark id and
     *          an 'addStatus' field indicating HOW the bookmark was added:
     *              'ignored'   - already existed, the new bookmark indicated
     *                            no change;
     *              'updated'   - already existed, the new bookmark had
     *                            changes;
     *              'created'   - did NOT already exist and was added;
     *              
     */
    addBookmark: function(bookmark)
    {
        /*
        cDebug.log("Connexions_Db::addBookmark(): bookmark[ %s ]",
                   cDebug.obj2str(bookmark));
        // */

        var self    = this;
        if (bookmark.url === undefined)
        {
            return null;
        }

        var existing    = self.getBookmarkByUrl(bookmark.url);

        /*
        cDebug.log("Connexions_Db::addBookmark(): from url[ %s ] == [ %s ]",
                   bookmark.url, cDebug.obj2str(existing));
        // */

        if (existing)
        {
            // Bookmark exists -- update
            bookmark.id        = existing.id;

            // Is there any change?
            if (self._bookmarksEquivalent(bookmark, existing))
            {
                // No change
                /*
                cDebug.log("Connexions_Db::addBookmark(): NO CHANGE");
                // */
                bookmark.addStatus = 'ignored';
            }
            else
            {
                bookmark.addStatus = 'updated';
                self.updateBookmark(bookmark);
            }
        }
        else
        {
            // Bookmark does NOT exist -- create
            var id = self.insertBookmark(bookmark);
            bookmark.id        = id;
            bookmark.addStatus = 'created';

            if ((id !== null) &&
                (bookmark.tags !== undefined) &&
                (bookmark.tags.length > 0))
            {
                // Add bookmark tags
                self.addBookmarkTags(id, bookmark.tags);
            }
        }

        /*
        cDebug.log("Connexions_Db::addBookmark(): complete, return [ %s ]",
                   bookmark);
        // */

        return bookmark;
    },

    /** @brief  Delete a bookmark.
     *  @param  id      The id of the target bookmark.
     *
     *  @return true | false
     */
    deleteBookmark: function(id)
    {
        var fname   = 'deleteBookmark';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'DELETE FROM bookmarks WHERE rowid=?1';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var res     = true;
        try {
            stmt.bindInt64Parameter(0, id);
            stmt.execute();
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
            res = false;
        }
        stmt.reset();

        if (res === true)
        {
            // Delete tag relations for the bookmark
            self.deleteBookmarkTags(id);

            // Finally, remove any un-referenced tags
            self.removeUnreferencedTags();

            self.signal('connexions.bookmarkDeleted', id);
        }

        return res;
    },

    /** @brief  Update a bookmark.
     *  @param  bookmark    The bookmark object:
     *                          id, url, urlHash, name, description,
     *                          rating, isFavorite, isPrivate, tags
     *
     *  @return true | false
     */
    updateBookmark: function(bookmark)
    {
        var fname   = 'updateBookmark';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'UPDATE bookmarks SET url=?1, urlHash=?2, '
                    +                      'name=?3, description=?4, '
                    +                      'rating=?5, isFavorite=?6, '
                    +                      'isPrivate=?7, taggedOn=?8, '
                    +                      'updatedOn=?9, visitedOn=?10, '
                    +                      'visitCount=?11, shortcut=?12 '
                    +     'WHERE rowid=?13';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var res     = true;
        try {
            var now = (new Date()).getTime() / 1000;

            stmt.bindUTF8StringParameter(0, bookmark.url);
            stmt.bindUTF8StringParameter(1, (bookmark.urlHash
                                              ? bookmark.urlHash : ''));
            stmt.bindUTF8StringParameter(2, (bookmark.name
                                              ? bookmark.name : ''));
            stmt.bindUTF8StringParameter(3, (bookmark.description
                                              ? bookmark.description : ''));
            stmt.bindInt64Parameter(4, (bookmark.rating
                                              ? bookmark.rating : 0));
            stmt.bindInt32Parameter(5, (bookmark.isFavorite
                                              ? 1 : 0));
            stmt.bindInt32Parameter(6, (bookmark.isPrivate
                                              ? 1 : 0));
            stmt.bindInt64Parameter(7, (bookmark.taggedOn
                                              ? bookmark.taggedOn : now));
            stmt.bindInt64Parameter(8, (bookmark.updatedOn
                                              ? bookmark.updatedOn : now));
            stmt.bindInt64Parameter(9, (bookmark.visitedOn
                                              ? bookmark.visitedOn : 0));
            stmt.bindInt32Parameter(10, (bookmark.visitCount
                                              ? bookmark.visitedCount : 0));
            stmt.bindUTF8StringParameter(11, (bookmark.shortcut
                                              ? bookmark.shortcut : ''));

            stmt.bindInt64Parameter(12, bookmark.id);

            stmt.execute();

            self.signal('connexions.bookmarkUpdated', bookmark.id);
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
            res = false;
        }
        stmt.reset();

        /* Now, if the incoming bookmark has tags, delete all existing tag
         * relations and re-create them.
         */
        if (bookmark.tags !== undefined)
        {
            // Delete all current tag relations
            self.deleteBookmarkTags(bookmark.id);

            // Create the new tag relations
            self.addBookmarkTags(bookmark.id, bookmark.tags);
        }

        // Finally, remove any un-referenced tags
        self.removeUnreferencedTags();

        return res;
    },

    /** @brief  Update the visit count for a bookmark.
     *  @param  url     The URL of the bookmark;
     *
     *  @return this    For a fluent interface.
     */
    incrementVisitCount: function(url)
    {
        var fname   = 'incrementVisitCount';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'UPDATE bookmarks SET visitCount=visitCount+1, '
                    +                      'visitedOn=?1 '
                    +                  'WHERE url=?2';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var bookmark    = self.getBookmarkByUrl(url);
        if (bookmark !== null)
        {
            try {
                var now = (new Date()).getTime() / 1000;

                stmt.bindInt64Parameter(0, now);    // visitedOn
                stmt.bindUTF8StringParameter(1, url);

                stmt.execute();

                self.signal('connexions.bookmarkUpdated', bookmark.id);
            } catch(e) {
                cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
            }
        }

        return self;
    },

    /** @brief  Retrieve a set of bookmarks.
     *  @param  sortOrder   The desired sort order:
     *                          A valid field:
     *                              url, urlHash, name, description, rating,
     *                              isFavorite, isPrivate, taggedOn, updatedOn,
     *                              visitedOn, visitCount, shortcut
     *                          A sort order:
     *                              ASC, DESC
     *
     *  @return An array of bookmark objects;
     */
    getBookmarks: function(sortOrder)
    {
        var fname       = 'getBookmarks';
        var self        = this;
        var order       = self._bookmarksOrder(sortOrder);
        var stmts       = self.dbStatements[ fname ];
        var bookmarks   = [];

        if (stmts === undefined)
        {
            // For the various sort orders
            self.dbStatements[ fname ] = stmts = {};
        }

        var stmt        = stmts[ order ];
        try {
            if (stmt === undefined)
            {
                var sql = 'SELECT b.rowid,b.* FROM bookmarks AS b '
                        +   'ORDER BY '+ order +',b.name ASC';
                stmt = self.dbConnection.createStatement(sql);
                stmts[ order ] = stmt;
            }

            while (stmt.executeStep())
            {
                var bookmark    = self._bookmarkFromRow(stmt);
                bookmarks.push(bookmark);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        if (stmt)   { stmt.reset(); }

        return bookmarks;
    },

    /** @brief  Retrieve a set of bookmarks that use all of the provided tags.
     *  @param  tags        An array of tag objects;
     *  @param  sortOrder   The desired sort order:
     *                          A valid field:
     *                              url, urlHash, name, description, rating,
     *                              isFavorite, isPrivate, taggedOn, updatedOn,
     *                              visitedOn, visitCount, shortcut
     *                          A sort order:
     *                              ASC, DESC
     *
     *  @return An array of bookmark objects;
     */
    getBookmarksByTags: function(tags, sortOrder)
    {
        var fname   = 'getBookmarksByTags';
        var self    = this;

        if ( (! tags) || (tags.length < 1))
        {
            return self.getBookmarks(sortOrder);
        }

        /* Since mozIStorageStatement provides no way to bind an array of
         * parameters, we have to construct this statement from scratch
         * everytime we need it.
         */
        var order   = self._bookmarksOrder(sortOrder);
        var tagIds  = [];
        for (var idex in tags)
        {
            var tag = tags[idex];
            tagIds.push(tag.id);
        }
        cDebug.log("Connexions_Db::%s(): %s tags[ %s ]",
                   fname, tagIds.length,
                   cDebug.obj2str(tagIds));

        var sql = "SELECT b.rowid,b.* FROM bookmarks as b "
                +   "INNER JOIN ("
                +     "SELECT bt.*,COUNT(DISTINCT bt.tagId) AS tagCount "
                +       "FROM bookmarkTags AS bt "
                +       "WHERE (bt.tagId IN ("+ tagIds.join(',') +")) "
                +       "GROUP BY bt.bookmarkId "
                +       "HAVING (tagCount="+ tagIds.length +")) AS bt "
                +   "ON b.rowid=bt.bookmarkId "
                +   "ORDER BY "+ order +",b.name ASC";

        cDebug.log("Connexions_Db::%s(): sql[ %s ]",
                   fname, sql);

        var stmt        = self.dbConnection.createStatement(sql);
        var bookmarks   = [];
        try {
            while (stmt.executeStep())
            {
                var bookmark    = self._bookmarkFromRow(stmt);
                bookmarks.push(bookmark);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return bookmarks;
    },

    /** @brief  Retrieve a set of bookmarks that match the given term.
     *  @param  term        The term to match (on url, name, description)
     *  @param  sortOrder   The desired sort order:
     *                          A valid field:
     *                              url, urlHash, name, description, rating,
     *                              isFavorite, isPrivate, taggedOn, updatedOn,
     *                              visitedOn, visitCount, shortcut
     *                          A sort order:
     *                              ASC, DESC
     *
     *  @return An array of bookmark objects;
     */
    getBookmarksByTerm: function(term, sortOrder)
    {
        var fname       = 'getBookmarksByTerm';
        var self        = this;
        var order       = self._bookmarksOrder(sortOrder);
        var stmts       = self.dbStatements[ fname ];
        var bookmarks   = [];

        if (stmts === undefined)
        {
            // For the various sort orders
            self.dbStatements[ fname ] = stmts = {};
        }

        var stmt        = stmts[ order ];
        try {
            if (stmt === undefined)
            {
                var sql = 'SELECT b.rowid,b.* FROM bookmarks AS b '
                        +   'WHERE (b.url LIKE ?1) OR '
                        +         '(b.name LIKE ?1) OR '
                        +         '(b.description LIKE ?1) '
                        +   'ORDER BY '+ order +',b.name ASC';
                stmt = self.dbConnection.createStatement(sql);
                stmts[ order ] = stmt;
            }
            stmt.bindUTF8StringParameter(0, '%'+ term +'%');

            while (stmt.executeStep())
            {
                var bookmark    = self._bookmarkFromRow(stmt);
                bookmarks.push(bookmark);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        if (stmt)   { stmt.reset(); }

        return bookmarks;
    },

    /************************************************************************
     * bookmarkTags table methods
     *
     */

    /** @brief  Given a bookmarkId and an array of raw tag strings, add
     *          bookmarkTags relation entries to link the tags to the
     *          bookmarks.
     *  @param  bookmarkId      The target bookmark;
     *  @param  tags            An array of tag strings.
     *
     *  @return this    for a fluent interface
     */
    addBookmarkTags: function(bookmarkId, tags)
    {
        var self    = this;
        for (var idex = 0; idex < tags.length; idex++)
        {
            if (! tags[idex])   { continue; }

            var tagId   = self.addTag(tags[idex]);

            self.insertBookmarkTag(bookmarkId, tagId);
        }

        return self;
    },

    /** @brief  Insert a new bookmarkTag join entry.
     *  @param  bookmarkId  The id of the bookmark;
     *  @param  tagId       The id of the tag;
     *
     *  @return The id of the new bookmarkTag
     */
    insertBookmarkTag: function(bookmarkId, tagId)
    {
        var fname   = 'insertBookmarkTag';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'INSERT INTO bookmarkTags VALUES(?1, ?2)';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var id  = null;
        try {
            stmt.bindInt64Parameter(0, bookmarkId);
            stmt.bindInt64Parameter(1, tagId);

            stmt.execute();

            id = self.dbConnection.lastInsertRowID;
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return id;
    },

    /** @brief  Delete all bookmarkTags entries for the given bookmarkId.
     *  @param  bookmarkId  The target bookmark
     *
     *  @return true | false
     */
    deleteBookmarkTags: function(bookmarkId)
    {
        var fname   = 'deleteBookmarkTags';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'DELETE FROM bookmarkTags WHERE bookmarkId=?1';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var res = false;
        try {
            stmt.bindInt64Parameter(0, bookmarkId);

            stmt.execute();
            res = true;
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return res;
    },

    /** @brief  Remove any tags that have no reference to a current bookmark.
     *
     *  @return this    for a fluent interface
     */
    removeUnreferencedTags: function()
    {
        var fname   = 'removeUnreferencedTags';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'DELETE FROM tags WHERE rowid '
                    +       'NOT IN (SELECT DISTINCT tagId FROM bookmarkTags)';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var id  = null;
        try {
            stmt.execute();

            self.signal('connexions.tagsUpdated');
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return self;
    },


    /************************************************************************
     * tags table methods
     *
     */

    /** @brief  Retrieve the total number of tags.
     *
     *  @return The total number of tags.
     */
    getTotalTags: function()
    {
        var fname   = 'getTotalTags';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT COUNT(rowid) FROM tags';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var count   = 0;
        try {
            if (stmt.executeStep())
            {
                count = stmt.getInt64(0);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return count;
    },

    /** @brief  Given a url, retrieve the matching tag.
     *  @param  name    The target tag name.
     *
     *  @return The tag id or null if not found.
     */
    getTagId: function(name)
    {
        var fname   = 'getTagId';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT rowid FROM tags WHERE name = ?1';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var id  = null;
        try {
            stmt.bindUTF8StringParameter(0, name);
            if (stmt.executeStep())
            {
                id = stmt.getInt64(0);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return id;
    },

    /** @brief  Insert a new tag
     *  @param  name        The name of the new tag;
     *
     *  @return The id of the new tag
     */
    insertTag: function(name)
    {
        var fname   = 'insertTag';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'INSERT INTO tags VALUES(?1)';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var id  = 0;
        try {
            stmt.bindUTF8StringParameter(0, name);

            stmt.execute();
            
            id = self.dbConnection.lastInsertRowID;

            self.signal('connexions.tagAdded', id);
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return id;
    },

    /** @brief  Add/Retrieve a tag
     *  @param  tag     The name of the tag;
     *
     *  @return The tagId
     */
    addTag: function(tag)
    {
        var id  = this.getTagId(tag);
        if (id === null)
        {
            // Tag does NOT exist.  Create it now.
            id = this.insertTag(tag);
        }

        return id;
    },

    /** @brief  Retrieve the set of tags for the given bookmark.
     *  @param  bookmarkId  The id of the target bookmark.
     *
     *  @return An array of tag objects;
     */
    getTags: function(bookmarkId)
    {
        var fname   = 'getTags';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT t.rowid,t.name,COUNT(bt.tagId) as frequency '
                    +   'FROM tags as t,bookmarkTags as bt '
                    +   'WHERE t.rowid = bt.tagId AND bt.bookmarkId = ?1 '
                    +   'GROUP BY t.rowid '
                    +   'ORDER BY t.name ASC';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var tags    = [];
        try {
            stmt.bindInt64Parameter(0, bookmarkId);
            while (stmt.executeStep())
            {
                var tag = self._tagFromRow(stmt);
                tags.push(tag);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return tags;
    },

    /** @brief  Retrieve a set of tags with frequency counts.
     *  @param  sortOrder   The desired sort order:
     *                          A valid field:
     *                              name, count
     *                          A sort order:
     *                              ASC, DESC
     *
     *  @return An array of tag objects;
     */
    getAllTags: function(sortOrder)
    {
        var fname   = 'getAllTags';
        var self    = this;
        var order   = self._tagsOrder(sortOrder);
        var stmts   = self.dbStatements[ fname ];
        var tags    = [];

        if (stmts === undefined)
        {
            // For the various sort orders
            self.dbStatements[ fname ] = stmts = {};
        }

        var stmt    = stmts[ order ];
        try {
            if (stmt === undefined)
            {
                var sql = 'SELECT t.rowid,t.name,COUNT(bt.tagId) as frequency '
                        +   'FROM tags as t,bookmarkTags as bt '
                        +   'WHERE t.rowid = bt.tagId '
                        +   'GROUP BY t.rowid '
                        +   'ORDER BY '+ order +',t.name ASC';
                stmt = self.dbConnection.createStatement(sql);
                stmts[ order ] = stmt;
            }

            while (stmt.executeStep())
            {
                var tag = self._tagFromRow(stmt);
                tags.push(tag);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        if (stmt)   { stmt.reset(); }

        return tags;
    },

    /** @brief  Retrieve a set of tags used by the given bookmarks.
     *  @param  bookmarks   An array of bookmark objects;
     *  @param  sortOrder   The desired sort order:
     *                          A valid field:
     *                              url, urlHash, name, description, rating,
     *                              isFavorite, isPrivate, taggedOn, updatedOn,
     *                              visitedOn, visitCount, shortcut
     *                          A sort order:
     *                              ASC, DESC
     *
     *
     *  @return An array of tag objects;
     */
    getTagsByBookmarks: function(bookmarks, sortOrder)
    {
        var fname   = 'getTagsByBookmarks';
        var self    = this;
        var order   = self._tagsOrder(sortOrder);

        /* Since mozIStorageStatement provides no way to bind an array of
         * parameters, we have to construct this statement from scratch
         * everytime we need it.
         */
        var bookmarkIds = [];
        for (var idex in bookmarks)
        {
            var bookmark = bookmarks[idex];
            bookmarkIds.push(bookmark.id);
        }
        cDebug.log("Connexions_Db::%s(): %s bookmarks[ %s ]",
                   fname, bookmarkIds.length,
                   cDebug.obj2str(bookmarkIds));

        var sql = "SELECT t.rowid,t.name,"
                +        "COUNT(DISTINCT bt.bookmarkId) as frequency "
                +   "FROM tags as t, bookmarkTags as bt "
                +   "WHERE (bt.bookmarkId IN ("+bookmarkIds.join(',')+")) "
                +     "AND (bt.tagId = t.rowid) "
                +   "GROUP BY t.rowId "
                +   "ORDER BY "+ order +",t.name ASC";

        cDebug.log("Connexions_Db::%s(): sql[ %s ]",
                   fname, sql);

        var stmt        = self.dbConnection.createStatement(sql);
        var tags        = [];
        try {
            while (stmt.executeStep())
            {
                var tag = self._tagFromRow(stmt);
                tags.push(tag);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return tags;
    },

    /** @brief  Retrieve a set of tags that match the given term.
     *  @param  term        The term to match (on url, name, description)
     *  @param  sortOrder   The desired sort order:
     *                          A valid field:
     *                              url, urlHash, name, description, rating,
     *                              isFavorite, isPrivate, taggedOn, updatedOn,
     *                              visitedOn, visitCount, shortcut
     *                          A sort order:
     *                              ASC, DESC
     *
     *  @return An array of tag objects;
     */
    getTagsByTerm: function(term, sortOrder)
    {
        var fname   = 'getTagsByTerm';
        var self    = this;
        var order   = self._tagsOrder(sortOrder);
        var stmts   = self.dbStatements[ fname ];
        var tags    = [];

        if (stmts === undefined)
        {
            // For the various sort orders
            self.dbStatements[ fname ] = stmts = {};
        }

        var stmt        = stmts[ order ];
        try {
            if (stmt === undefined)
            {
                var sql = 'SELECT t.rowid,t.name,COUNT(bt.tagId) as frequency '
                        +   'FROM tags as t,bookmarkTags as bt '
                        +   'WHERE (t.name LIKE ?1) AND (t.rowid = bt.tagId) '
                        +   'GROUP BY t.rowid '
                        +   'ORDER BY '+ order +',t.name ASC';
                cDebug.log("Connexions_Db::%s(): sql [ %s ]", fname, sql);

                stmt = self.dbConnection.createStatement(sql);
                stmts[ order ] = stmt;
            }
            stmt.bindUTF8StringParameter(0, '%'+ term +'%');

            while (stmt.executeStep())
            {
                var tag    = self._tagFromRow(stmt);
                tags.push(tag);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        if (stmt)   { stmt.reset(); }

        return tags;
    },


    /************************************************************************
     * state table methods
     *
     */

    /** @brief  Get a state object.
     *  @param  name    The name of the state;
     *
     *  @return The state object (null if not found);
     */
    getState: function(name)
    {
        var fname   = 'getState';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT rowid,state.* FROM state WHERE name=?1';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var state   = null;
        try {
            stmt.bindUTF8StringParameter(0, name);
            if (stmt.executeStep())
            {
                state = self._stateFromRow(stmt);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return state;
    },

    /** @brief  Add a new state name/value pair;
     *  @param  name    The name of the state;
     *  @param  value   The value of the state;
     *
     *  @return The id of the new bookmark
     */
    insertState: function(name, value)
    {
        var fname   = 'insertState';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'INSERT INTO state VALUES(?1, ?2)';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var id  = null;
        try {
            stmt.bindUTF8StringParameter(0, name);
            stmt.bindUTF8StringParameter(1, value);

            stmt.execute();

            id = self.dbConnection.lastInsertRowID;
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return id;
    },

    /** @brief  Update the value of an existing state item.
     *  @param  name    The name of the state;
     *  @param  value   The new value of the state;
     *
     *  @return true | false
     */
    updateState: function(name, value)
    {
        var fname   = 'updateState';
        var self    = this;
        var stmt    = self.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'UPDATE state SET value=?2 WHERE name=?1';
            stmt = self.dbConnection.createStatement(sql);
            self.dbStatements[ fname ] = stmt;
        }

        var res = true;
        try {
            stmt.bindUTF8StringParameter(0, name);
            stmt.bindUTF8StringParameter(1, value);
            stmt.execute();
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
            res = false;
        }
        stmt.reset();

        return res;
    },

    /** @brief  Get or set a state value.
     *  @param  name    The name of the state;
     *  @param  value   The new value (if not set, simply retrieve);
     *
     *  @return The (old) value;
     */
    state: function(name, value)
    {
        // First, see if the state already exists
        var self    = this;
        var state   = self.getState(name);

        /*
        cDebug.log("Connexions_Db::state(): name[ %s ], state[ %s ]",
                   name, cDebug.obj2str(state));
        // */

        if (value !== undefined)
        {
            // We're being asked to set the value
            if (state === null)
            {
                /*
                cDebug.log("Connexions_Db::state(): insert new value[ %s ]",
                           value);
                // */

                // It didn't already exists, so INSERT
                self.insertState(name, value);
            }
            else
            {
                /*
                cDebug.log("Connexions_Db::state(): update value[ %s ]",
                           value);
                // */

                // It already exists, so UPDATE
                self.updateState(name, value);
            }
        }

        /*
        cDebug.log("Connexions_Db::state(): name[ %s ] - return value[ %s ]",
                   name, (state ? state.value : 'null'));
        // */

        return (state ? state.value : null);
    },

    /************************************************************************
     * Methods involving multiple tables
     *
     */

    /** @brief  Delete all bookmarks, tags, and joins
     *
     *  @return true | false
     */
    deleteAllBookmarks: function()
    {
        var self    = this;
        for(var name in self.dbSchema.tables)
        {
            if ((name !== 'bookmarks')  &&
                (name !== 'tags')       &&
                (name !== 'bookmarkTags'))
            {
                continue;
            }

            /*
            cDebug.log("Connexions_Db::deleteAllBookmarks(): name[ %s ]",
                       name);
            // */

            self._emptyTable(name);

            self.signal('connexions.bookmarksDeleted');
        }
    },

    /** @brief  Delete all content from all tables.
     *
     *  @return true | false
     */
    emptyAllTables: function()
    {
        var self    = this;
        for(var name in self.dbSchema.tables)
        {
            /*
            cDebug.log("Connexions_Db::emptyAllTables(): name[ %s ]", name);
            // */

            self._emptyTable(name);

            self.signal('connexions.tablesEmptied');
        }
    },

    /** @brief  Perform an asynchronous SQL query.
     *  @param  sql         The SQL query string, with bindings;
     *  @param  bindings    The bindings to apply (name / value pairs);
     *  @param  callbacks   Callbacks to invoke upon completion:
     *                          {handleResult: function(resultSet) {
     *                              while (var row = resultSet.getNextRow()) {
     *                                  row.getResultByName();
     *                              }
     *                           },
     *                           handleError:  function(error) {
     *                              // error.message
     *                           },
     *                           handleCompletion:  function(reason) {
     *                              // CI.mozIStorageStatementCallback.REASON_*
     *                           }
     *                          }
     *                           
     *  @return this    For a fluent interface.
     */
    query: function(sql, bindings, callbacks)
    {
        var statement = this.dbConnection.createStatement( sql );
        var params    = statement.newBindingParamsArray();
        for (var name in bindings)
        {
            var bp  = params.newBindingParams();
            bp.bindByName(name, bindings[name]);
            params.addParams(bp);
        }
        statement.bindParameters(params);

        statement.executeAsync( callbacks );

        return this;
    },

    /***********************************************************************
     * "Private" methods
     *
     */

    /** @brief  Prepare a bookmark sort order string.
     *  @param  sortOrder   The desired sort order:
     *                          A valid field:
     *                              url, urlHash, name, description, rating,
     *                              isFavorite, isPrivate, taggedOn, updatedOn,
     *                              visitedOn, visitCount, shortcut
     *                          A sort order:
     *                              ASC, DESC
     *
     *  @return A validated bookmark sort order string;
     */
    _bookmarksOrder: function(sortOrder)
    {
        var self        = this;
        var order       = (sortOrder
                            ? sortOrder.split(/\s+/)
                            : [ 'name', 'asc' ]);

        switch (order[0])
        {
        case 'url':
        case 'urlHash':
        case 'name':
        case 'description':
        case 'rating':
        case 'isFavorite':
        case 'isPrivate':
        case 'taggedOn':
        case 'updatedOn':
        case 'vistedOn':
        case 'visitCount':
        case 'shortcut':
            break;

        default:
            order[0] = 'visitedOn';
            break;
        }

        switch (order[1].toLowerCase())
        {
        case 'asc':     order[1] = 'ASC';   break;
        case 'desc':
        default:        order[1] = 'DESC';  break;
        }
        order = order.join(' ');

        /*
        cDebug.log("Connexions_Db::_bookmarksOrder(): order[ %s ]", order);
        // */

        return order;
    },

    /** @brief  Prepare a tag sort order string.
     *  @param  sortOrder   The desired sort order:
     *                          A valid field:
     *                              name, count
     *                          A sort order:
     *                              ASC, DESC
     *
     *  @return A validated tag sort order string;
     */
    _tagsOrder: function(sortOrder)
    {
        var self        = this;
        var order       = (sortOrder
                            ? sortOrder.split(/\s+/)
                            : [ 'name', 'asc' ]);

        switch (order[0])
        {
        case 'name':
        case 'frequency':
            break;

        default:
            order[0] = 'frequency';
            break;
        }

        switch (order[1].toLowerCase())
        {
        case 'asc':     order[1] = 'ASC';   break;
        case 'desc':
        default:        order[1] = 'DESC';  break;
        }
        order = order.join(' ');

        /*
        cDebug.log("Connexions_Db::_tagsOrder(): order[ %s ]", order);
        // */

        return order;
    },

    /** @brief  Execute the given SQL as a transaction.
     *  @param  sql     The sql to execute.
     *
     *  @return true | false
     */
    _transaction: function(sql)
    {
        this.dbConnection.beginTransaction();

        var stmt;
        try {
            stmt = this.dbConnection.createStatement( sql );
            stmt.execute();
        } catch(e) {
            if (stmt !== undefined)
            {
                stmt.finalize();
            }

            cDebug.log("Connexions_Db::_transaction(): ERROR [ %s ]",
                        e);
            return false;
        }

        stmt.finalize();

        this.dbConnection.commitTransaction();

        return true;
    },

    /** @brief  Empty the contents of the given table.
     *  @param  name        The name of the table to empty.
     *
     *  @return true | false
     */
    _emptyTable: function(name)
    {
        var sql = 'DELETE FROM '+ name;

        return this._transaction( sql );
    },

    /** @brief  Drop a table, index, or collation.
     *  @param  type    The type of database item to drop
     *                  ( TABLE, INDEX, COLLATION );
     *  @param  name    The name of the item to drop;
     *
     *  @return true | false
     */
    _drop: function(type, name)
    {
        var sql = 'DROP '+ type +' '+ name;

        return this._transaction( sql );
    },

    /** @brief  Create the database and populate it with tables and indices.
     *  dbService   The mozIStorageService instance;
     *  dbFile      The nsIFile instance representing the file that will hold
     *              the database;
     *
     *  @return The database connection (mozIStorageConnection).
     */
    _dbCreate: function(dbService, dbFile)
    {
        var dbConnection = dbService.openDatabase(dbFile);

        this._dbCreateTables(dbConnection)
            ._dbCreateIndices(dbConnection);

        return dbConnection;
    },

    /** @brief  Create the tables indicated by this.dbSchema.tables.
     *  @param  dbConnection    The (new) dbConnection created by _dbCreate();
     *
     *  @return this    For a fluent interface.
     */
    _dbCreateTables: function(dbConnection)
    {
        for(var name in this.dbSchema.tables)
        {
            if (dbConnection.tableExists( name ))
            {
                continue;
            }

            var schema  = this.dbSchema.tables[name];

            /*
            cDebug.log("Connexions_Db::_dbCreateTables(): "
                            + "name[ %s ], schema[ %s ]", name, schema);
            // */

            dbConnection.createTable(name, schema);
        }

        return this;
    },

    /** @brief  Create the indices indicated by this.dbSchema.indices.
     *  @param  dbConnection    The (new) dbConnection created by _dbCreate();
     *
     *  @return this    For a fluent interface.
     */
    _dbCreateIndices: function(dbConnection)
    {
        for(var name in this.dbSchema.indices)
        {
            var sql = "CREATE INDEX IF NOT EXISTS "
                    +   name +" ON "+ this.dbSchema.indices[name];

            /*
            cDebug.log("Connexions_Db::_dbCreateIndices(): "
                            + "name[ %s ], sql[ %s ]", name, sql);
            // */

            dbConnection.executeSimpleSQL( sql );
        }

        return this;
    },

    /** @brief  Generate a bookmark object from a database row.
     *  @param  stmt    The mozIStorageStatement instance representing the
     *                  row containing the data to be used to generate the
     *                  bookmark object;
     *
     *  @return The new bookmark object.
     */
    _bookmarkFromRow: function(stmt)
    {
        var obj = {};

        try {
            obj.id          = stmt.getInt64(0);
            obj.url         = stmt.getUTF8String(1);
            obj.urlHash     = stmt.getUTF8String(2);
            obj.name        = stmt.getUTF8String(3);
            obj.description = stmt.getUTF8String(4);
            obj.rating      = stmt.getInt32(5);
            obj.isFavorite  = stmt.getInt32(6);
            obj.isPrivate   = stmt.getInt32(7);
            obj.taggedOn    = stmt.getInt64(8);
            obj.updatedOn   = stmt.getInt64(9);
            obj.visitedOn   = stmt.getInt64(10);
            obj.visitCount  = stmt.getInt32(11);
            obj.shortcut    = stmt.getUTF8String(12);

            // Ensure that boolean flags have boolean values
            obj.isFavorite = (obj.isFavorite ? true : false); 
            obj.isPrivate  = (obj.isPrivate  ? true : false); 

        } catch (e) {
            cDebug.log("Connexions_Db::_bookmarkFromRow(): ERROR [ %s ]", e);
            obj = null;
        }

        return obj;
    },

    /** @brief  Generate a tab object from a database row.
     *  @param  stmt    The mozIStorageStatement instance representing the
     *                  row containing the data to be used to generate the
     *                  tag object;
     *
     *  @return The new tag object.
     */
    _tagFromRow: function(stmt)
    {
        var obj = {};
        try {
            obj.id          = stmt.getInt64(0);
            obj.name        = stmt.getUTF8String(1);
            obj.frequency   = stmt.getInt32(2);

        } catch (e) {
            cDebug.log("Connexions_Db::_tagFromRow(): ERROR [ %s ]", e);
            if ((obj.id === undefined) || (obj.name === undefined))
            {
                obj = null;
            }
        }

        return obj;
    },

    /** @brief  Generate a state object from a database row.
     *  @param  stmt    The mozIStorageStatement instance representing the
     *                  row containing the data to be used to generate the
     *                  state object;
     *
     *  @return The new state object.
     */
    _stateFromRow: function(stmt)
    {
        var obj = {};
        try {
            obj.id    = stmt.getInt64(0);
            obj.name  = stmt.getUTF8String(1);
            obj.value = stmt.getUTF8String(2);

        } catch (e) {
            cDebug.log("Connexions_Db::_stateFromRow(): ERROR [ %s ]", e);
            if ((obj.id === undefined) || (obj.name === undefined))
            {
                obj = null;
            }
        }

        return obj;
    },

    /** @brief  Compare two bookmark objects for equivalence.
     *  @param  bm1     The first  bookmark object;
     *  @param  bm2     The second bookmark object;
     *
     *  @return true | false
     */
    _bookmarksEquivalent: function(bm1, bm2)
    {
        return ( (bm1.url         === bm2.url)         &&
                 (bm1.name        === bm2.name)        &&
                 (bm1.description === bm2.description) &&
                 (bm1.rating      === bm2.rating)      &&
                 (bm1.isFavorite  === bm2.isFavorite)  &&
                 (bm1.isPrivate   === bm2.isPrivate)   &&
                 (bm1.taggedOn    === bm2.taggedOn)    &&
                 (bm1.updatedOn   === bm2.updatedOn)   &&
                 ((bm1.visitedOn  === undefined) ||
                  (bm1.visitedOn  === bm2.visitedOn))  &&
                 ((bm1.visitCount === undefined) ||
                  (bm1.visitCount === bm2.visitCount)) &&
                 ((bm1.shortcut   === undefined) ||
                  (bm1.shortcut   === bm2.shortcut)) );
    },

    /** @brief  Establish our state observers.
     */
    _loadObservers: function() {
        this.os.addObserver(this, "connexions.syncBegin",   false);
        this.os.addObserver(this, "connexions.syncEnd",     false);
    },

    /** @brief  Establish our state observers.
     */
    _unloadObservers: function() {
        this.os.removeObserver(this, "connexions.syncBegin");
        this.os.removeObserver(this, "connexions.syncEnd");
    }
};

var cDb = new Connexions_Db();
