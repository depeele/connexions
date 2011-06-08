/** @file
 *
 *  Local connexions database.
 *
 */
const CC    = Components.classes;
const CI    = Components.interfaces;
const CR    = Components.results;
const CU    = Components.utils;

CU.import("resource://connexions/debug.js");


let EXPORTED_SYMBOLS    = ["Connexions_Db"];

function Connexions_Db()
{
    this.init();
}

Connexions_Db.prototype = {
    initialized:    false,
    dbConnection:   null,
    dbStatements:   {},
    dbSchema:       {
        tables: {
            bookmarks:      "url NOT NULL DEFAULT \"\",\
                             urlHash NOT NULL DEFAULT \"\",\
                             name NOT NULL DEFAULT \"\" COLLATE NOCASE,\
                             description NOT NULL DEFAULT \"\",\
                             rating UNSIGNED NOT NULL DEFAULT 0,\
                             isFavorite UNSIGNED NOT NULL DEFAULT 0,\
                             isPrivate UNSIGNED NOT NULL DEFAULT 0,\
                             taggedOn UNSIGNED NOT NULL DEFAULT 0,\
                             updatedOn UNSIGNED NOT NULL DEFAULT 0,\
                             visitedOn UNSIGNED NOT NULL DEFAULT 0,\
                             visitCount UNSIGNED NOT NULL DEFAULT 0,\
                             shortcut NOT NULL DEFAULT \"\"",
            tags:           "name NOT NULL DEFAULT \"\" COLLATE NOCASE",
            bookmarkTags:   "bookmarkId UNSIGNED NOT NULL DEFAULT 0,\
                             tagId UNSIGNED NOT NULL DEFAULT 0",
        },
        indices: {
            bookmarks_alpha:      "bookmarks(name ASC)",
            bookmarks_url:        "bookmarks(url ASC)",
            bookmarks_visitedOn:  "bookmarks(visitedOn DESC, name)",
            bookmarks_visitCount: "bookmarks(visitCount DESC, name)",
            bookmarks_tag:        "bookmarkTags(bookmarkId, tagId)",
            tags_bookmark:        "bookmarkTags(tagId, bookmarkId)",
        }
    },

    init: function()
    {
        if (this.initialized === true)  return;

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
            this.dbConnection = this._dbCreate(dbService, dbFile);
            cDebug.log("Connexions_Db::init(): Created database "
                            + "[ "+ dbFile.path +" ]");
        }
        else
        {
            this.dbConnection = dbService.openDatabase(dbFile);
            cDebug.log("Connexions_Db::init(): Opened database "
                            + "[ "+ dbFile.path +" ]");
        }
    },

    /** @brief  Retrieve the total number of bookmarks.
     *
     *  @return The total number of bookmarks.
     */
    getTotalBookmarks: function()
    {
        var fname   = 'getTotalBookmarks';
        var stmt    = this.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT COUNT(rowid) FROM bookmarks';
            stmt = this.dbConnection.createStatement(sql);
            this.dbStatements[ fname ] = stmt;
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

    /** @brief  Retrieve the total number of tags.
     *
     *  @return The total number of tags.
     */
    getTotalTags: function()
    {
        var fname   = 'getTotalTags';
        var stmt    = this.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT COUNT(rowid) FROM tags';
            stmt = this.dbConnection.createStatement(sql);
            this.dbStatements[ fname ] = stmt;
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
        var stmt    = this.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT rowid FROM bookmarks WHERE url = ?1';
            stmt = this.dbConnection.createStatement(sql);
            this.dbStatements[ fname ] = stmt;
        }

        var id  = null;
        try {
            stmt.bindUTF8StringParemeter(0, url);
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

    /** @brief  Given a url, retrieve the matching tag.
     *  @param  name    The target tag name.
     *
     *  @return The tag id or null if not found.
     */
    getTagId: function(name)
    {
        var fname   = 'getTagId';
        var stmt    = this.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT rowid FROM tags WHERE name = ?1';
            stmt = this.dbConnection.createStatement(sql);
            this.dbStatements[ fname ] = stmt;
        }

        var id  = null;
        try {
            stmt.bindUTF8StringParemeter(0, name);
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
        var stmt    = this.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT bookmarks.rowid,bookmarks.* FROM bookmarks '
                    +   'WHERE bookmarks.url=?1';
            stmt = this.dbConnection.createStatement(sql);
            this.dbStatements[ fname ] = stmt;
        }

        var bookmark    = null;
        try {
            stmt.bindUTF8StringParemeter(0, url);
            if (stmt.executeStep())
            {
                bookmark = this._bookmarkFromRow(stmt);
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
        var stmt    = this.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT bookmarks.rowid,bookmarks.* FROM bookmarks '
                    +   'WHERE bookmarks.rowid=?1';
            stmt = this.dbConnection.createStatement(sql);
            this.dbStatements[ fname ] = stmt;
        }

        var bookmark    = null;
        try {
            stmt.bindInt64Parameter(0, id);
            if (stmt.executeStep())
            {
                bookmark = this._bookmarkFromRow(stmt);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return bookmark;
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
        var fname   = 'getBookmarks';
        var stmt    = this.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'SELECT bookmarks.rowid,bookmarks.* FROM bookmarks '
                    +   'ORDER BY ?1,name';
            stmt = this.dbConnection.createStatement(sql);
            this.dbStatements[ fname ] = stmt;
        }

        var bookmarks   = [];
        try {
            var order   = (sortOrder
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

            stmt.bindUTF8StringParameter(0, order.join(' '));
            while (stmt.executeStep())
            {
                var bookmark    = this._bookmarkFromRow(stmt);
                bookmarks.push(bookmark);
            }
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return bookmarks;
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
        var stmt    = this.dbStatements[ fname ];
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
            stmt = this.dbConnection.createStatement(sql);
            this.dbStatements[ fname ] = stmt;
        }

        var id  = null;
        try {
            var now = (new Date()).getTime() * 1000;

            stmt.bindUTF8StringParameter(0, bookmark['url']);
            stmt.bindUTF8StringParameter(1, (bookmark['urlHash']
                                              ? bookmark['urlHash'] : ''));
            stmt.bindUTF8StringParameter(2, (bookmark['name']
                                              ? bookmark['name'] : ''));
            stmt.bindUTF8StringParameter(3, (bookmark['description']
                                              ? bookmark['description'] : ''));
            stmt.bindInt64Parameter(4, (bookmark['rating']
                                              ? bookmark['rating'] : 0));
            stmt.bindInt32Parameter(5, (bookmark['isFavorite']
                                              ? 1 : 0));
            stmt.bindInt32Parameter(6, (bookmark['isPrivate']
                                              ? 1 : 0));
            stmt.bindInt64Parameter(7, (bookmark['taggedOn']
                                              ? bookmark['taggedOn'] : now));
            stmt.bindInt64Parameter(8, (bookmark['updatedOn']
                                              ? bookmark['updatedOn'] : now));
            stmt.bindInt64Parameter(9, (bookmark['visitedOn']
                                              ? bookmark['visitedOn'] : now));
            stmt.bindInt32Parameter(10, (bookmark['visitCount']
                                              ? bookmark['visitedCount'] : 0));
            stmt.bindUTF8StringParameter(11, (bookmark['shortcut']
                                              ? bookmark['shortcut'] : ''));

            stmt.execute();

            id = this.dbConnection.lastInsertRowId;
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
        var stmt    = this.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'INSERT INTO tags VALUES(?1)';
            stmt = this.dbConnection.createStatement(sql);
            this.dbStatements[ fname ] = stmt;
        }

        var id  = 0;
        try {
            stmt.bindUTF8StringParameter(0, name);

            stmt.execute();
            
            id = this.dbConnection.lastInsertRowId;
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();

        return id;
    },

    /** @brief  Insert a new bookmarkTag join entry.
     *  @param  bookmarkId  The id of the bookmark;
     *  @param  tagId       The id of the tag;
     *
     *  @return void
     */
    insertBookmarkTag: function(bookmarkId, tagId)
    {
        var fname   = 'insertBookmarkTag';
        var stmt    = this.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'INSERT INTO bookmarksTags VALUES(?1, ?2)';
            stmt = this.dbConnection.createStatement(sql);
            this.dbStatements[ fname ] = stmt;
        }

        try {
            stmt.bindInt64Parameter(0, bookmarkId);
            stmt.bindInt64Parameter(1, tagId);

            stmt.execute();
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
        }
        stmt.reset();
    },

    /** @brief  Add/Retrieve a tag
     *  @param  tag     The name of the tag;
     *
     *  @return The tag
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

    /** @brief  Add/Update a bookmark
     *  @param  bookmark    The (new) bookmark object;
     *
     *  @return The id of the new/updated bookmark;
     */
    addBookmark: function(bookmark)
    {
        if (bookmark['url'] === undefined)
        {
            return null;
        }

        var cur = this.getBookmarkByUrl(bookmark['url']);
        if (cur)
        {
            // Bookmark exists -- update
            this.updateBookmark(bookmark);
        }
        else
        {
            // Bookmark does NOT exist -- create
            id = this.insertBookmark(bookmark);

            if ((id !== null) &&
                (bookmark['tags'] !== undefined) &&
                (bookmark['tags'].length > 0))
            {
                var tags    = bookmark['tags'];
                for (var idex = 0; idex < tags.length; idex++)
                {
                    if (! tags[idex])   continue;

                    var tagId   = this.addTag(tags[idex]);

                    this.insertBookmarkTag(id, tagId);
                }
            }
        }

        return id;
    },

    /** @brief  Update a bookmark.
     *  @param  bookmark    The bookmark object:
     *                          url, urlHash, name, description,
     *                          rating, isFavorite, isPrivate, tags
     *
     *  @return true | false
     */
    updateBookmark: function(bookmark)
    {
        var fname   = 'updateBookmark';
        var stmt    = this.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'UPDATE bookmarks SET url=?1, urlHash=?2, '
                    +                      'name=?3, description=?4, '
                    +                      'rating=?5, isFavorite=?6, '
                    +                      'isPrivate=?7, taggedOn=?8, '
                    +                      'updatedOn=?9, visitedOn=?10, '
                    +                      'visitCount=?11, shortcut=?12';
                    +                              '?7, ?8, ?9, ?10, ?11, ?12)';
            stmt = this.dbConnection.createStatement(sql);
            this.dbStatements[ fname ] = stmt;
        }

        var res     = true;
        try {
            var now = (new Date()).getTime() * 1000;

            stmt.bindUTF8StringParameter(0, bookmark['url']);
            stmt.bindUTF8StringParameter(1, (bookmark['urlHash']
                                              ? bookmark['urlHash'] : ''));
            stmt.bindUTF8StringParameter(2, (bookmark['name']
                                              ? bookmark['name'] : ''));
            stmt.bindUTF8StringParameter(3, (bookmark['description']
                                              ? bookmark['description'] : ''));
            stmt.bindInt64Parameter(4, (bookmark['rating']
                                              ? bookmark['rating'] : 0));
            stmt.bindInt32Parameter(5, (bookmark['isFavorite']
                                              ? 1 : 0));
            stmt.bindInt32Parameter(6, (bookmark['isPrivate']
                                              ? 1 : 0));
            stmt.bindInt64Parameter(7, (bookmark['taggedOn']
                                              ? bookmark['taggedOn'] : now));
            stmt.bindInt64Parameter(8, (bookmark['updatedOn']
                                              ? bookmark['updatedOn'] : now));
            stmt.bindInt64Parameter(9, (bookmark['visitedOn']
                                              ? bookmark['visitedOn'] : now));
            stmt.bindInt32Parameter(10, (bookmark['visitCount']
                                              ? bookmark['visitedCount'] : 0));
            stmt.bindUTF8StringParameter(11, (bookmark['shortcut']
                                              ? bookmark['shortcut'] : ''));

            stmt.execute();
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
            res = false;
        }
        stmt.reset();

        return res;
    },

    /** @brief  Update the visit count for a bookmark.
     *  @param  url     The URL of the bookmark;
     *
     *  @return void
     */
    incrementVisitCount: function(url)
    {
        var fname   = 'incrementVisitCount';
        var stmt    = this.dbStatements[ fname ];
        if (stmt === undefined)
        {
            var sql = 'UPDATE bookmarks SET visitCount=visitCount+1, '
                    +                      'visitedOn=?1 '
                    +                  'WHERE url=?2';
            stmt = this.dbConnection.createStatement(sql);
            this.dbStatements[ fname ] = stmt;
        }

        try {
            var now = (new Date()).getTime() * 1000;

            stmt.bindInt64Parameter(0, now);    // visitedOn
            stmt.bindUTF8StringParameter(1, url);

            stmt.execute();
        } catch(e) {
            cDebug.log("Connexions_Db::%s(): ERROR [ %s ]", fname, e);
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
    },

    /***********************************************************************
     * "Private" methods
     *
     */
    _dbCreate: function(dbService, dbFile)
    {
        var dbConnection = dbService.openDatabase(dbFile);
        this._dbCreateTables(dbConnection);
        this._dbCreateIndices(dbConnection);
        return dbConnection;
    },

    _dbCreateTables: function(dbConnection)
    {
        for(var name in this.dbSchema.tables)
        {
            if (dbConnexions.tableExists( name ))
            {
                continue;
            }

            var schema  = this.dbSchema.tables[name];

            Connexions_log("Connexions_Db::_dbCreateTables(): "
                            + "name[ "+ name +" ], schema[ "+ schema +" ]");

            dbConnection.createTable(name, schema);
        }
    },

    _dbCreateIndices: function(dbConnection)
    {
        for(var name in this.dbSchema.indices)
        {
            var sql = "CREATE INDEX IF NOT EXISTS "
                    +   name +" ON "+ this.dbSchema.indices[name];

            Connexions_log("Connexions_Db::_dbCreateIndices(): "
                            + "name[ "+ name +" ], sql[ "+ sql +" ]");

            dbConnection.executeSimpleSQL( sql );
        }
    },

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

        } catch (e) {
            cDebug.log("Connexions_Db::_bookmarkFromRow(): ERROR [ %s ]", e);
            obj = null;
        }

        return obj;
    }
};
