Introduction
 Connexions is a tagging/folksonomy system similar to del.icio.us, simpy, furl,
 or BlinkList.

Requirements/Dependencies
 A web server that supports PHP (4 or higher), local .htaccess files, and the
 mod_rewrite URL rewriting engine.

 A modern browser that supports JavaScript (1.5 or higher) and asynchronous
 HTTP requests.  This would include Microsoft Internet Explorer 6.0 or higher,
 Mozilla Firefox 1.0/Mozilla 1.7 or higher, and Apple Safari 1.2 or higher.

 Connexions makes heavy use of the Prototype JavaScript framework (version
 1.5.0_rc0 or higher) and the Script.aculo.us library built upon it (version
 1.6.1 or higher) -- js/{prototype.js,scriptaculous}

 In addition, the following are used:
    js/autosuggest.js
    js/jslog.js
    lib/adodb
    lib/freetag
    lib/Services_JSON.php

Setup
 To get started, you will need to copy the connexions directory to a writable
 area (call it SRC).

Apache Setup
 At a minimum, the SRC directory needs to be web accessible.  This can be done
 any number of ways, but the method used for testing used an alias:

    <IfModule mod_alias.c>
        Alias /connexions/      "SRC/connexions/"

        <Directory "SRC/connexions">
            Options Indexes FollowSymLinks
            AllowOverride All
            Order allow,deny
            Allow from all
        </Directory>
    </IfModule>

 Note that 'AllowOverride All' is probably too open.  All we really need is to
 allow .htaccess files to be honored, specifically for mod_rewrites.

 With these settings in place, everything else is handled by
 "SRC/connexions/.htaccess".


 If you decide to use a method that does NOT use '/connexions' as the URL, you
 will need to modify 'gBaseUrl' in config.php.

 NOTE: Apache2 is more confusing to me...  The only way I could get the redirects to work is by placing them in the main configuration file and prepending the full path.

   <Directory "/Library/WebServer/SecureDocuments/connexions/">
     SSLRequireSSL
   </Directory>

   <IfModule mod_rewrite.c>
     RewriteEngine On
     RewriteCond "/Library/WebServer/SecureDocuments%{REQUEST_FILENAME}" !-d
     RewriteCond "/Library/WebServer/SecureDocuments%{REQUEST_FILENAME}" !-f
     RewriteRule ^/connexions/([^/]+)([\?/])?(.*)$        /connexions/index.php?Action=main:$1&params=$3  [QSA,L]

     RewriteCond %{REQUEST_URI}              !-U
     RewriteRule ^/connexions/(.*)                        /connexions/index.php?$1                        [QSA,L]
   </IfModule>


Configuration
 All configuration of connexions is done via 'SRC/config.php'.  The available
 options are:
    gUseGlobalLoading   'true/false'
                        If set to 'true', make use of a red "loading" indicator
                        whenever a potentially long (asynchronous) operation is
                        taking place.  This will appear in the upper right
                        corner of the browser window.
    gUseThumbnails      'true/false'
                        If there is a way to generate thumbnails of the URLs
                        in the database, this can be set to 'true' and, if a
                        thumbnail exists for a URL, it will be displayed.
    gJsDebug            'true/false'
                        If set to 'true', a JavaScript debugging tool will
                        be used for display of JavaScript debugging messages.
    gProfile            'true/false'
                        If set to 'true', profiling information will be
                        recorded to 'profile-main.txt' for normal activity
                        and 'profile-action.txt' for asynchronous actions.
                        Profiling can be added to any method of classes
                        based upon 'TagDB' or in the 'Tagging' class by
                        simply adding:
                            $this->profile_start('uniqueFunctionId');
                            ...
                            $this->profile_stop('uniqueFunctionId');
    gBaseUrl            The base, top-level URL to the web-accessible
                        connexions directory.
    db_options          Database options:
                            debug           If 'true', output debugging
                                            messages.
                            db_user         Database user
                            db_pass         Database password
                            db_host         Database hostname
                            db_name         Database name
                            table_prefix    Database table prefix
                            noexec          If 'true', don't actually
                                            execute any SQL statements, just
                                            output them.


Database Setup
 To initialize the database:
  cd SRC/tagging/data
  ./tagging-db.sh   # Destroys any existing tagging database and creates
                    # a new tagging database with the required tables

 If you change the database user, password, host, or name, you will need to
 modifie 'SRC/config.php' to reflect the proper database setting (db_options).

 Currently, there is no user identification/authentication.
 Identification/authentication of user #1 (elmo) is hardcoded for testing.

 The database can be left empty (except for a single user record for user
 #1/elmo) OR can be loaded with either a del.icio.us backup (e.g.
 SRC/tagging/data/del.ici.us-export-*.html) or an SQL dump of data loaded from
 the sample del.icio.us backups (e.g. 2006.08.21.sql, 2006.09.18.sql).

Loading a del.icio.us backup
 If there is a command-line version of php that supports mysql directly, you can load the backup from the command-line as follows:
  cd SRC/tagging
  php init.php <local path to del.icio.us backup file>

 Otherwise, you will need to use a softlink and a web browser:
  cd SRC/tagging
  ln -s <local path to del.icio.us backup file> del.icio.us-export.html

  <Visit '/connexions/init.php' in your web browser and wait for -- DONE>


-------------------------------------------------------------------------------
Connexion namespace:
    /                               : main page
    /popular/[tag+...]              : popular items/tags, limited by any tags
    /recent/[tag+...]               : recent items/tags, limited by any tags
    /tag/[tag+...]                  : global tags with tagged items, related
                                      tags, and active taggers, limited by any
                                      tags
*   /post/[params]                  : generically post a new item
*   /feeds/<api>/<call>/[params]    : feed plugins:
                                        api : rss, html, js, json
                                        call: dependant upon 'api'
    /for/<user>/[tag+...]           : items tagged for the speicific user,
                                      limited by any tags
    /network/<user>/                : friends network for the specific user.

*   /<user>/[tag+...]               : user page with items/tags possibly
                                      limited by tags
*   /<user>?<params>                : user post MUST specifify at least url=

*   /details/<url hash/url>         : details about the identified item

    /search/<term>/[params]         : search for the given term.

    /settings/<user>                : user settings
    /logout/                        : user logout

    /help/[topic]                   : help

Generally:
    /call/params...                 : If no 'main_<call>' function, assume
                                      <call> is a user.

                                      Need:
                                        main_popular
                                        main_recent
                                        main_tag
                                        main_post
                                        main_settings
                                        main_logout
                                        main_help
                                        main_feeds  => plugin api
                                        main_for
                                        main_network


Connexion schema:
 I have found that with >10,000 users, ~257 items, and only 414 tags, the
 current freetag schema breaks down.  Retrieval slows to a crawl taking
 multiple seconds to retrieve items, tags, and associated statistics.  Even
 when the retrieval is limited to a specific user, retrival times are
 consistently above 1 second.

 One possible solution is suggested by Nitin Borwankar in:
   - http://tagschema.com/blogs/tagschema/2005/05/slicing-and-dicing-data-20-part-2.html
   - http://tagschema.com/blogs/tagschema/2005/10/many-dimensions-of-relatedness-in.html
 
 This solutions uses three data tables, three association tables and one fact
 table as opposed to the single data table and single fact table used by
 freetag.

 If it is true that SQL joins provide quick data access, then the additional
 association tables should remove any bottlenecks and improve performance.
 Particularly when one or more dimensions (user, item, tag) grow large.


 There are three basic entities, each with its own data table:
    user        userid, name, ...
    tag         tagid,  name, ...
    item        itemid, url,  ...

 Each pair has an association table that may contain additional information
 about the association:
    useritem    useritem    * -> 1 user     |   userid/itemid, name,
                useritem    1 -> 1 item     |   description, rating,
                                            |   is_favorite, is_private

    usertag     usertag     * -> 1 user     |   userid/tagid,
                usertag     1 -> 1 tag      |   is_favorite

    itemtag     itemtag     1 -> 1 tag      |   itemid/tagid
                itemtag     * -> 1 item     |

 The whole combination has a fact table that is a 3-way association:
    useritemtag useritemtag * -> 1 user     |   userid/itemid/tagid
                useritemtag * -> 1 item     |
                useritemtag 1 -> 1 tag      |


 Given these, Nitin suggests the following (paraphrased):
    Let the letter i, t, and u represent a specific 'item', 'tag', and 'user'
    respectively.
    Let the upper case I, T, and U represent mappings as follows:
        Let U(i) be all users of item i (with id == x):
                SELECT u.* FROM user u, useritem ui
                       WHERE u.id = ui.userid AND ui.itemid = x
        Let U(t) be all users of tag t (with id == x):
                SELECT u.* FROM user u, usertag ut
                       WHERE u.id = ut.userid AND ut.tagid = x
    Similarly:
        Let I(u) be all items of   user u
        Let I(t) be all items with tag t
        Let T(u) be all tags  of   user u
        Let T(i) be all tags  of   item i

    Now, we can combine these like T(U(t)) to be the set of all tags of all
    users of a single tag t (with id == x):
                SELECT t.* FROM tag t, usertag ut
                       WHERE ut.userid in
                        (SELECT userid from usertag where tagid = x)

    Further, U, T, and I are idempotent.  That is, U(U()) == U()

    Relatedness:
      items:
        I(T(i)) tag related items                   (i.e. all items of all tags
                                                     of item i).
        I(U(i)) user related items                  (i.e. all items of all
                                                     users of item i).
        U(T(i)) tag related users for item i        (i.e. users who tagged this
                                                     item in a similar manner -
                                                     the T-cluster of users for
                                                     item i.  All users of all
                                                     tags of item i).
        T(U(i)) user related tags for item i        (i.e. all tags of all users
                                                     who have tagged this item.
                                                     the collective tag-wisdom
                                                     about this item).
      tags:
        T(U(t)) user related tags                   (i.e. all tags of all users
                                                     that have a tag t).
        T(I(t)) item related tags                   (i.e. all tags of all items
                                                     having tag t).
        U(I(t)) item related user for tag t         (i.e. users who have items
                                                     with this tag - I-cluster
                                                     of users for tag t).
        I(U(t)) user related items for tag t        (i.e. all items of all
                                                     users with this tag -
                                                     items you might find
                                                     interesting if you have
                                                     tag t).

      users:
        U(T(u)) tag related users of user u         (i.e. all users of all tags
                                                     of user u).
        U(I(u)) item related users of user u        (i.e. all users of all
                                                     items of user u).
        I(T(u)) all items with all tags of user u   (i.e. all items of all tags
                                                     of user u - the T-cluster
                                                     of items for user u).
        T(I(u)) all tags of all items of user u     (i.e. the I-cluster of tags
                                                     for user u).


Which modules are responsible for which tables:
 lib/user.php       - user, useritem, usertag (when user add/del)
 lib/item.php       - item, useritem, itemtag (when item add/del)
 lib/tag.php        - tag,  itemtag,  usertag (when tag  add/del)

 lib/tagging.php    - usertagitem (calls user, item, tag for others)
