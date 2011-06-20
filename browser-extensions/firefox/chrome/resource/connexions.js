/** @file
 *
 *  The primary connexions class (connexions).
 *
 *  The server uses a cookie ('api.authCookie' set in
 *  application/config/application.ini) to indicate when there is a change in
 *  user authentication.
 *
 *  We use an observer to notice this change, (re)retrieve the currently
 *  authenticated user (via jsonRpc('user.whoami')), and broadcast the change
 *  via 'connexions.userChanged'.
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false, plusplus:false, regexp:false */
/*global Components:false, cDebug:false, cDb:false, window:false */
var CC                  = Components.classes;
var CI                  = Components.interfaces;
var CR                  = Components.results;
var CU                  = Components.utils;
var CONNEXIONS_BASE_URL = "%URL%";

CU.import("resource://connexions/debug.js");
CU.import("resource://connexions/db.js");

var EXPORTED_SYMBOLS    = ['connexions'/*, $, $$ */];

/*****************************************************************************
 * Helpers
 *
function $(id)
{
    return connexions.getWindow().document.getElementById(id);
}

function $$(cssSelector)
{
    return connexions.getWindow().document.querySelectorAll(cssSelector);
}
 */

/*****************************************************************************
 * UI / Main
 *
 */
function Connexions()
{
    this.init();
}

Connexions.prototype = {
    os:             CC['@mozilla.org/observer-service;1']
                        .getService(CI.nsIObserverService),
    /*
    cm:             CC["@mozilla.org/categorymanager;1"]
                        .getService(CI.nsICategoryManager),
    // */
    wm:             CC['@mozilla.org/appshell/window-mediator;1']
                        .getService(CI.nsIWindowMediator),
    tm:             null,

    initialized:    false,
    user:           null,   // The current user

    debug:          cDebug,
    db:             cDb,

    mainThread:     null,
    bookmarksThread:null,
    cookieTimer:    CC['@mozilla.org/timer;1']
                        .createInstance(CI.nsITimer),
    cookieTicking:  false,
    prefsWindow:    null,
    strings:        null,

    cookieJar:      {
        domain:     "%DOMAIN%",
        authCookie: "%AUTH_COOKIE%",
        values:     null
    },
    jsonRpcInfo:    {
        version:    "%JSONRPC_VERSION%",
        transport:  "%JSONRPC_TRANSPORT%",
        url:        "%JSONRPC_URL%",
        id:         0
    },
    state:          {
        retrieveUser:   false,
        sync:           false,
        syncStatus:     null
    },

    init: function() {
        if (this.initialized === true)  { return; }

        cDebug.log('resource-connexions::init():');

        var self    = this;

        self.initialized = true;

        // Normalize the cookie domain.
        self.cookieJar.domain = self.cookieJar.domain.toLowerCase();

        var cookies = this.db.state('cookies');
        if (cookies !== null)
        {
            cDebug.log('resource-connexions::init(): cookies from db[ %s ]',
                       cookies);

            self.cookieJar.values = self.str2cookies(cookies);
        }

        /*
        cDebug.log('resource-connexions::init(): cookieJar '
                    + 'domain[ %s ], authCookie[ %s ]',
                    self.cookieJar.domain,
                    self.cookieJar.authCookie);
        // */

        // Is this Firefox >= 3 < 4, which has a thread-manager?
        try {
            self.tm = CC['@mozilla.org/thread-manager;1']
                        .getService();
        } catch(e) {}
        if (self.tm === null)
        {
            // NOT Firefox >= 3 < 4.  Is it Firefox 4+?
        }

        self._loadObservers();

        cDebug.log('resource-connexions::init(): completed');
    },

    /** @brief  Invoked any time 'chrome/content/connexions.js' receives a
     *          'load' event.
     */
    windowLoad: function() {
        cDebug.log('resource-connexions::windowLoad()');

        var self        = this;
        var document    = self.getDocument();

        if (document && (self.strings === null))
        {
            /* Attempt to include the strings from the 'connexions-strings'
             * stringbundle established in ff-overlay.xul
             */
            try {
                var strings = document.getElementById('connexions-strings');

                /*
                cDebug.log('resource-connexions::windowLoad(): strings[ %s ]',
                           strings);
                // */

                self.setStrings( strings );
            } catch(e) {
                cDebug.log('resource-connexions::windowLoad(): '
                            + 'get connextion-strings triggered an error: %s',
                           e.message);
            }
        }

        if (self.user === null)
        {
            // Attempt to retrieve the current user.
            cDebug.log('resource-connexions::windowLoad(): retrieveUser');

            self.retrieveUser();
        }
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

        /*
        cDebug.log('resource-connexions::observe(): topic[ %s ]',
                   topic);
        // */

        switch (topic)
        {
        case 'cookie-changed':
            var cookie  = subject.QueryInterface(CI.nsICookie2);
            var noTimer = false;

            if (self.cookieTicking)
            {
                // Cancel the current cookie timer
                self.cookieTimer.cancel();
                self.cookieTicking = false;
            }

            /*
            cDebug.log('resource-connexions::observe(): cookie-changed: '
                        +   'host[ %s ], path[ %s ], name[ %s ]',
                       cookie.host, cookie.path, cookie.name);
            // */
            if (cookie  &&
                (cookie.host.toLowerCase() === self.cookieJar.domain) )
            {
                // /*
                cDebug.log('resource-connexions::observe(): '
                            +   'cookie-changed: '
                            +   'host[ %s ], path[ %s ], '
                            +   'name[ %s ], value[ %s ]',
                           cookie.host, cookie.path,
                           cookie.name, cookie.value);
                // */

                if (self.cookieJar.values === null)
                {
                    self.cookieJar.values  = {
                        __length:   0
                    };
                }

                if (self.cookieJar.values[ cookie.name ] !== cookie.value)
                {
                    // The cookie HAS changed from our stored value
                    self.cookieJar.values[ cookie.name ] = cookie.value;
                    self.cookieJar.values.__length++;
                    self.db.state('cookies', self.cookies2str());

                    // /*
                    cDebug.log('resource-connexions::observe(): '
                                +   'cookie-changed from our stored value: '
                                +   'host[ %s ], path[ %s ], name[ %s ]',
                               cookie.host, cookie.path, cookie.name);
                    // */
                }

                if (cookie.name === self.cookieJar.authCookie)
                {
                    // /*
                    cDebug.log('resource-connexions::observe(): '
                                + 'authCookie changed!');
                    // */

                    // (Re)Retrieve the authenticated user
                    self.retrieveUser();
                    noTimer = true;
                }
            }

            if (noTimer === false)
            {
                /* Set a timer to wait to see if we have more cookies.  When
                 * the timer expires, attempt to retrieve the currently
                 * authenticated user.
                 */
                self.cookieTimer.initWithCallback(function() {
                    self.retrieveUser();
                    self.cookieTicking = false;
                }, 5000, CI.nsITimer.TYPE_ONE_SHOT);
                self.cookieTicking = true;
            }
            break;
        }
    },

    /** @brief  Signal observers.
     *  @param  subject The subject name;
     *  @param  data    The event data;
     */
    signal: function(subject, data) {
        cDebug.log('resource-connexions::signal(): subject[ %s ], data[ %s ]',
                   subject, cDebug.obj2str(data));
        if (data !== undefined)
        {
            // JSON-encode the non-string
            data = JSON.stringify( data );
        }

        this.os.notifyObservers(this, subject,
                               (data === undefined ? '' : data));
        /*
        this.os.notifyObservers(null, subject,
                               (data === undefined ? '' : data));
        // */
    },

    /** @brief  Initiate the retrieval of the authenticated user.
     *  @param  callback    The callback to invoke upon success:
     *                          function(user)
     */
    retrieveUser: function(callback) {
        var self    = this;

        if (self.state.retrieveUser === true)
        {
            // In process
            return;
        }
        self.state.retrieveUser = true;

        //cDebug.log('resource-connexions::retrieveUser(): initiate...');

        /* Perform a JsonRpc request to find out who the authenticated user if
         * (if any)
         */
        self.jsonRpc('user.whoami', {}, {
            success: function(data, textStatus, xhr) {
                /*
                cDebug.log('resource-connexions::retrieveUser(): '
                            +   'jsonRpc return[ %s ]',
                            cDebug.obj2str(data));
                // */

                if (data.error !== null)
                {
                    self.user = null;
                }
                else
                {
                    self.user = data.result;
                }

                if (callback !== undefined)
                {
                    callback(self.user);
                }
            },
            error:   function(xhr, textStatus, error) {
                self.user = null;
                cDebug.log('resource-connexions::retrieveUser(): '
                            +   'ERROR retrieving user[ %s ]',
                            textStatus);
            },
            complete: function(xhr, textStatus) {
                self.signal('connexions.userChanged', self.user);
                self.state.retrieveUser = false;
            }
        });
    },

    setStrings: function(strings) {
        this.strings = strings;
    },

    /** @brief  If our strings (nsIStringBundle) have been set, retrieve and
     *          return the named string.
     *  @param  name        The name of the desired string;
     *  @param  strArray    If provided,
     *                          strings.getFormattedString(name, strArray)
     *                      will be used to retrieve the desired string.
     *                      Otherwise,
     *                          strings.getString(name)
     *                      will be used;
     *
     *  @return The desired string (null if no match);
     */
    getString: function(name, strArray) {
        var str = null;

        if (this.strings)
        {
            try {
                str = (strArray
                        ? this.strings.getFormattedString(name, strArray)
                        : this.strings.getString(name));
            } catch(e) {}
        }

        return str;
    },

    /** @brief  Given the contextMenu instance and desired tagging type,
     *          present the tag page.
     *  @param  el      The DOM element that is the target
     *                  (if any, SHOULD be equivalent to
     *                   el.ownerDocument.popupNode);
     *  @param  type    The desired type of tagging
     *                  ('page', 'link', 'media');
     *
     */
    tagPage: function(el, type) {
        var self    = this;
        var doc     = self.getDocument();   //el.ownerDocument;
        var docUrl  = doc.URL;
        var url, name;

        cDebug.log('tagPage(): docUrl[ %s ]', docUrl);

        switch (type)
        {
        case 'page':
            url  = docUrl;
            name = (doc.title
                        ? doc.title
                        : url);

            var selection   = self.getSelectedText();
            var description = (selection.str ? selection.str : '');

            cDebug.log('tagPage(): type[ %s ], url[ %s ], name[ %s ], '
                            +   'description[ %s ]',
                           type, url, name, description);

            self.openTagPage(url, name, description);
            break;

        case 'link':
            /*
            url  = (typeof(gContextMenu.linkURL) === 'string'
                        ? gContextMenu.linkURL
                        : gContextMenu.linkURL());
            name = gContextMenu.linkText();
            // */

            cDebug.log('tagPage(): type[ %s ]', type);

            // el should NEVER be null here
            url  = el.getAttribute('href');
            cDebug.log('tagPage(): type[ %s ], url[ %s ]', type, url);

            name = el.textContent;
            cDebug.log('tagPage(): type[ %s ], url[ %s ], name[ %s ]',
                            type, url, name);

            // Fall through

        case 'media':
            if (url === undefined)
            {
                /*
                url  = gContextMenu.target.getAttribute('src');
                name = gContextMenu.target.getAttribute('title');
                // */
                cDebug.log('tagPage(): type[ %s ], UNDEFINED url...',
                                type);

                // el should NEVER be null here
                url  = el.getAttribute('src');
                name = el.getAttribute('title');
                if (! name)
                {
                    name = el.getAttribute('alt');
                }
                if (! name)
                {
                    name = '';
                }
            }

            if (! url.match(/^[^:]:\/\//))
            {
                cDebug.log('tagPage(): type[ %s ], prepend docUrl...',
                                type);

                /* prepend the document URL taking care not to duplicate '/'
                 * between the document url and the target url.
                 */
                if (docUrl.match(/\/$/))
                {
                    if (url.match(/^\//))
                    {
                        url = docUrl + url.substr(1);
                    }
                    else
                    {
                        url = docUrl + url;
                    }
                }
                else if (url.match(/^\//))
                {
                    url = docUrl + url;
                }
                else
                {
                    url = docUrl +'/'+ url;
                }
            }

            cDebug.log('tagPage(): type[ %s ], final url[ %s ], name[ %s ]',
                           type, url, name);

            self.openTagPage(url, name);
            //self.popupAlert(type);
            break;
        }
    },

    popupAlert: function(msg) {
        var promptService =
                CC["@mozilla.org/embedcomp/prompt-service;1"]
                          .getService(CI.nsIPromptService);
        promptService.alert(window, 'Connexions Alert', msg);
    },

    toolbarButtonCommand: function(e) {
        // just reuse the function above.    you can change this, obviously!
        this.popupAlert('toolbar button');
    },

    showOptions: function() {
        cDebug.log("showOptions()");

        if (! this.prefsWindow || this.prefsWindow.closed) {
            var xul     = 'chrome://connexions/content/options.xul';
            var title   = 'Connexions Options';
            var opts    = 'chrome,titlebar,toolbar,centerscreen,dialog=no';
            this.prefsWindow =
                this.openXulWindow(xul, title, opts);
        }
        this.prefsWindow.focus();
    },

    /** @brief  Load a new page.
     *  @param  e           The originating event;
     *  @param  page        The connexions page to load
     *                      ( myBookmarks myTags, myNetwork, myInbox,
     *                        bookmarks, tags, people, main, signin, register)
     *  @param  type        The type of load ( [tab], popup)
     *  @param  closeAction The close action to invoke when the page is to be
     *                      closed [ 'back' ];
     */
    loadPage: function(e, page, type, closeAction) {
        var url = null;

        cDebug.log("loadPage(): event[ %s ], page[ %s ], type[ %s ]",
                        cDebug.obj2str(e),
                        page, type);
        switch (page)
        {
        case 'myBookmarks':
            url = '@mine';
            break;

        case 'myTags':
            url = 'tags/@mine';
            break;

        case 'myNetwork':
            url = 'network/@mine';
            break;

        case 'myInbox':
            url = 'inbox/@mine';
            break;

        case 'bookmarks':
            url = 'bookmarks';
            break;

        case 'tags':
            url = 'tags';
            break;

        case 'people':
            url = 'people';
            break;

        case 'signin':
            url = 'auth/signIn';
            break;

        case 'register':
            url = 'auth/register';
            break;

        case 'main':
        default:
            url = '';
            break;
        }

        if (url !== null) {
            if (closeAction !== undefined)
            {
                url += '?closeAction='+ closeAction;
            }

            url = this.url(url);

            cDebug.log("loadPage(): page[ %s ], type[ %s ], final url[ %s ]",
                       page, type, url);

            switch (type)
            {
            case 'popup':
                this.openPopupWindow( url, page );
                break;

            case 'tab':
            default:
                this.openTab( url );
                break;
            }
        }
    },

    /** @brief  For contexts that do NOT have 'window', retrieve the CURRENT
     *          window.
     */
    getWindow: function() {
        return this.wm.getMostRecentWindow('navigator:browser');
    },

    /** @brief  For contexts that do NOT have 'document', retrieve the CURRENT
     *          document.
     */
    getDocument: function() {
        return this.getWindow().document;
    },

    /** @brief  For contexts that do NOT have 'window', retrieve the browser
     *          instance associated with the CURRENT window.
     */
    getBrowser: function() {
        return this.getWindow().getBrowser();

        /*
        return (getBrowser
                    ? getBrowser()
                    : (this.getWindow()).getBrowser());
        // */
    },

    /** @brief  For contexts that do NOT have 'window', retrieve the window and
     *          invoke 'toggleSidebar()'
     */
    toggleSidebar: function(id, capture) {
        return this.getWindow().toggleSidebar(id, capture);
    },

    openXulWindow: function(xul, title, options, url) {
        return this.getWindow().openDialog(xul, title, options, url);
    },

    openTab: function(url) {
        cDebug.log("openTab(): url[ %s ]", url);

        var browser = this.getBrowser();
        var tab     = browser.addTab(url);
        browser.selectedTab = tab;

        return tab;
    },

    openPopupWindow: function(url, title) {
        var width   = 980;
        var height  = 680;
        var options = 'chrome'
                    + ',resizable'
                    + ',scrollbars'
                    + ',titlebar'
                    + ',statusbars'
                    + ',centerscreen'
                    + ',dependent'
                    + ',dialog=no'
                    + ',width=' + width
                    + ',height='+ height;

        /*
        cDebug.log("openPopupWindow(): url    [ %s ]", url);
        cDebug.log("openPopupWindow(): title  [ %s ]", title);
        cDebug.log("openPopupWindow(): options[ %s ]", options);
        cDebug.log("openPopupWindow(): xul    [ %s ]", xul);
        // */

        /*
        var xul         = 'chrome://browser/content/browser.xul';
        var newWindow   = this.openXulWindow(xul, title, options, url);
        */
        //var newWindow   = window.open(url, '_parent', options);
        var newWindow   = this.getWindow().open(url, '_blank', options);

        /*
        newWindow.addEventListener("close", function() {
                                    cDebug.log("openPopupWindow(): "
                                                   + "close event triggered");

                                    newWindow.close();
                                   }, true);
        // */

        return newWindow;
    },

    openTagPage: function(url, name, description, tags) {
        var query   = '?url='+ encodeURIComponent(url)
                    + '&name='+ encodeURIComponent(name);
        if (description !== undefined)
        {
            query += '&description='+ encodeURIComponent(description);
        }
        if (tags !== undefined)
        {
            query += '&tags='+ encodeURIComponent(tags);
        }

        query += '&noNav&closeAction=close';

        /*
        cDebug.log("openTagPage(): url[ %s ]", url);
        cDebug.log("openTagPage(): name[ %s ]", name);
        cDebug.log("openTagPage(): description[ %s ]", description);
        cDebug.log("openTagPage(): tags[ %s ]", tags);
        cDebug.log("openTagPage(): query[ %s ]", query);
        // */

        this.openPopupWindow( this.url('post'+ query),
                                    'Bookmark a Page' );
    },

    getSelectedText: function(maxLen) {
        maxLen = maxLen || 4096;

        var curWindow   = this.getWindow();
        var str         = curWindow.getSelection();
        str = str.toString();
        str = str.replace(/^\s+/, '')
                 .replace(/\s+$/, '')
                 .replace(/\s+/g, ' ');

        var origLen     = str.length;

        if (str.length > maxLen)
        {
            var pattern = new RegExp("^(?:\\s*.){0,"+ maxLen +"}");
            pattern.test(str);
            str = RegExp.lastMatch;
        }

        return {str: str, len:origLen};
    },

    url: function(path) {
        var url = CONNEXIONS_BASE_URL;
        if ((path !== undefined) && (path.length > 0))
        {
            url += path;
        }

        return url;
    },

    /** @brief  Retrieve the information about the currently authenticated
     *          user.
     *
     *  @return A user record
     */
    getUser: function() {
        return this.user;
    },

    getSyncStatus: function() {
        return this.state.syncStatus;
    },

    /** @brief  Delete all local bookmarks.
     */
    delBookmarks: function() {
        this.db.deleteAllBookmarks();
    },

    /** @brief  Syncrhonize bookmarks with connexions.
     *  @param  isReload    If true, delete all local bookmarks first.
     *
     */
    sync: function(isReload) {
        var self    = this;

        if (self.user === null)
        {
            cDebug.log("resource-connexions::sync(): NOT signed in");
            return;
        }

        if (self.state.sync === true)
        {
            // In process
            return;
        }
        self.state.sync = true;

        cDebug.log("resource-connexions::sync(): "
                    +   "signed in as [ %s ], isReload[ %s ]",
                    self.user.name, isReload);

        if (isReload)
        {
            self.delBookmarks();
        }

        /* :TODO: Perform an asynchronous request for all bookmarks and add
         * them into the local database.
         */
        var lastSync    = parseInt(self.db.state('lastSync'), 10);
        var params  = {
            users:  self.user.name,
            count:  null
        };

        if (! isNaN(lastSync))
        {
            params.since = lastSync;
        }

        cDebug.log("resource-connexions:sync(): params[ %s ]",
                   cDebug.obj2str(params));

        self.state.syncStatus  = null;

        self.signal('connexions.syncBegin');
        self.jsonRpc('bookmark.fetchByUsers', params, {
            progress: function(position, totalSize, xhr) {
                cDebug.log('resource-connexions::sync(): RPC progress: '
                            +   'position[ %s ], totalSize[ %s ]',
                            position, totalSize);
            },
            success: function(data, textStatus, xhr) {
                // /*
                cDebug.log('resource-connexions::sync(): RPC success: '
                            +   'jsonRpc return[ %s ]',
                            cDebug.obj2str(data));
                // */

                if (data.error !== null)
                {
                    // ERROR!
                    self.state.syncStatus = data;
                }
                else
                {
                    // SUCCESS -- Add all new bookmarks.
                    self.state.syncStatus = {
                        error:      null
                    };
                    self._syncAddBookmarks(data.result);
                }
            },
            error:   function(xhr, textStatus, error) {
                cDebug.log('resource-connexions::sync(): RPC error: '
                            +   '[ %s ]',
                            textStatus);
                self.state.syncStatus = {
                    error:  {
                        code:       error,
                        message:    textStatus
                    }
                };
            },
            complete: function(xhr, textStatus) {
                cDebug.log('resource-connexions::sync(): RPC complete: '
                            +   '[ %s ]',
                            textStatus);
                if (self.state.syncStatus.error !== null)
                {
                    // There was an error so the sync is complete
                    self.signal('connexions.syncEnd', self.state.syncStatus);
                    self.state.sync = false;
                }
            }
        });
    },

    /** @brief  If a sync is in progress, cancel it.
     *
     */
    syncCancel: function() {
        if (this.state.sync === true)
        {
            this.state.sync = false;
        }
    },

    /** @brief  Given a set of bookmarks retrieved from the server,
     *          add/update our local cache.
     *  @param  bookmarks   An array of bookmark objects.
     */
    _syncAddBookmarks: function(bookmarks) {
        var self    = this;

        if ((self.bookmarksThread === null) && (self.tm !== null))
        {
            /* Retrieve the main thread and create a new background thread to
             * handle bookmarks processing.
             */
            self.mainThread      = self.tm.mainThread;
            self.bookmarksThread = self.tm.newThread(0);
        }

        if (self.bookmarksThread === null)
        {
            /* No threading!!  We COULD invoke it directly, but then the main
             * UI wouldn't be updated.
             */
            throw CR.NS_ERROR_NOT_IMPLEMENTED;
        }

        cDebug.log('resource-connexions::_syncAddBookmarks(): dispatch thread');
        self.bookmarksThread.dispatch(new bookmarksThread(bookmarks),
                                      CI.nsIThread.DISPATCH_NORMAL);
    },

    /** @brief  Invoke a JsonRpc call.
     *  @param  method      The remote JsonRpc method;
     *  @param  params      Parameters required for 'method';
     *  @param  callbacks   A set of callback functions similar to those
     *                      required by jQuery.ajax():
     *                          success:    function(data, textStatus, xhr)
     *                          error:      function(xhr, textStatus, error)
     *                          complete:   function(xhr, textStatus)
     *                          progress:   function(position, totalSize, xhr)
     */
    jsonRpc: function(method, params, callbacks) {
        var self    = this;
        var rpc     = {
            version:    self.jsonRpcInfo.version,
            id:         self.jsonRpcInfo.id++,
            method:     method,
            params:     params
        };

        // Create a new XmlHttpRequest
        var xhr = CC['@mozilla.org/xmlextras/xmlhttprequest;1']
                        .createInstance(CI.nsIXMLHttpRequest);

        /** @brief  Invoke callback(s)
         *  @param  which   The name of the callback to invoke
         *                  (success, error, progress).  Note that 'complete'
         *                  will ALWAYS be invoked for 'success' and 'error'.
         *  @param  params  State parameters to be applied depending on the
         *                  callback being invoked:
         *                      {data:
         *                       textStatus:
         *                       status:
         *                       position:
         *                       totalSize: }
         */
        function invokeCallback(which, params)
        {
            var needComplete    = false;
            switch (which)
            {
            case 'success':
                needComplete = true;
                if (callbacks.success)
                {
                    callbacks.success(params.data, params.textStatus, xhr);
                }
                break;

            case 'error':
                needComplete = true;
                if (callbacks.error)
                {
                    callbacks.error(xhr, params.textStatus);
                }
                break;

            case 'progress':
                if (callbacks.error)
                {
                    callbacks.progress(params.position, params.totalSize, xhr);
                }
                break;
            }

            // For 'success' and 'error', ALWAYS invoke complete if it exists
            if (needComplete && callbacks.complete)
            {
                callbacks.complete(xhr, params.textStatus);
            }
        }

        // Handle 'onload' to report success/complete
        xhr.onload = function(event) {
            // event.target (XMLHttpRequest)
            var params  = {
                xhr:        event.target,
                status:     event.target.status,
                textStatus: event.target.statusText,
                data:       event.target.responseText
            };

            cDebug.log("connexions::jsonRpc(): onload: "
                       +   "textStatus[ %s ]",
                       params.textStatus);

            if (callbacks.success)
            {
                // Attempt to parse 'data' as JSON
                var json;
                try {
                    params.data = JSON.parse(params.data);
                } catch(e) {
                    cDebug.log("connexions::jsonRpc(): onload: "
                               + "JSON.parse error[ %s ], data[ %s ]",
                               e.message, params.data);

                    /* Invoke xhr.onerror so both the 'error' and
                     * 'complete' callback will be properly invoked.
                     */
                    params.status     = -32700; // JsonRPC: Parse Error
                    params.textStatus = e.message;
                    invokeCallback('error', params);
                    return;
                }
            }

            invokeCallback('success', params);
        };

        // Handle 'onerror' to report error/complete
        xhr.onerror = function(event) {
            // event.target (XMLHttpRequest)
            var params  = {
                xhr:        xhr,
                status:     -1,
                textStatus: "Error"
            };
            try {
                params.xhr = event.target;
            } catch(e) {}
            try {
                params.status = event.target.status;
            } catch(e) {}
            try {
                params.textStatus = event.target.statusText;
            } catch(e) {}

            cDebug.log("connexions::jsonRpc(): ERROR: "
                       +   "status[ %s ], textStatus[ %s ]",
                       params.status, params.textStatus);

            invokeCallback('error', params);
        };

        // Handle 'onprogress' to report progress
        xhr.onprogress = function(event) {
            // event.position, event.totalSize,
            // event.target (XMLHttpRequest)
            var params  = {
                xhr:        xhr,
                position:   event.position,
                totalSize:  event.totalSize
            };

            invokeCallback('progress', params);
        };


        /*
        request.onuploadprogress = function(event) {
        };
        // */

        xhr.open(self.jsonRpcInfo.transport,
                 self.jsonRpcInfo.url);

        /* Request that HTTP Cookies and Authentication information be
         * included.
         */
        xhr.withCredentials = 'true';
        xhr.setRequestHeader('Content-Type',     'application/json');
        xhr.setRequestHeader('Accept',           'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        var cookies;
        if ( (self.cookieJar.values !== null) &&
             (self.cookieJar.values.__length > 0) )
        {
            // Include cookies
            cookies = self.cookies2str();

            xhr.setRequestHeader('Cookie', cookies);
        }

        // /*
        cDebug.log("resource-connexions::jsonRpc(): "
                   +    "method[ %s ], transport[ %s ], "
                   +    "url[ %s ], cookies[ %s ]",
                   method, self.jsonRpcInfo.transport,
                   self.jsonRpcInfo.url, cookies);
        // */

        // Send the request
        xhr.send( JSON.stringify( rpc ) );
    },

    /** @brief  Convert any namve/value object to an equivalent cookie string.
     *  @param  obj     The object to convert.  If not provided, use
     *                  this.cookieJar.values
     *
     *  @return The cookie string;
     */
    cookies2str: function(obj) {
        var self        = this;
        var cookieStrs  = [];

        if (obj === undefined)  { obj = self.cookieJar.values; }

        if (obj !== null)
        {
            for (var name in obj)
            {
                if (name === '__length')    { continue; }

                var val = obj[name];
                cookieStrs.push( name +'='+ val);
            }
        }

        var str = cookieStrs.join(';');

        /*
        cDebug.log("resource-connexions::cookie2str(): "
                    +   'obj[ %s ] == str[ %s ]',
                    cDebug.obj2str(obj), str);
        // */

        return str;
    },

    /** @brief  Convert a cookie string to an equivalent name/value object.
     *  @param  str     The string to convert.
     *
     *  @return The cookie object;
     */
    str2cookies: function(str) {
        var self    = this;
        var obj     = {
            __length: 0
        };
        var parts   = str.split(/\s*;\s*/);

        for (var idex in parts)
        {
            var part    = parts[idex];
            var nameVal = part.split(/\s*=\s*/);
            var name    = nameVal[0];
            var val     = (nameVal.length > 1
                            ? nameVal[1]
                            : null);

            obj[ name ] = val;
            obj.__length++;
        }

        /*
        cDebug.log("resource-connexions::str2cookies(): "
                    +   'str[ %s ] == obj[ %s ]',
                    str, cDebug.obj2str(obj));
        // */

        return obj;
    },

    /** @brief  Retrieve all cookies in the domain specified by this.cookieJar
    getCookies: function() {
        var self            = this;
        var cookieManager   = CC['@mozilla.org/cookiemanager;1']
                                .getService(CI.nsICookieManager2);
        var nCookies        = cookieManager
                                .countCookiesFromHost(self.cookieJar.domain);
        var cookies         = cookieManager
                                .getCookiesFromHost(self.cookieJar.domain);
        //var cookies         = cookieManager.enumerator;

        cDebug.log("resource-connexions::getCookies(): "
                   +    "%s cookies from domain[ %s ]...",
                   nCookies, self.cookieJar.domain);

        self.cookieJar.values = {};
        while ( cookies.hasMoreElements() )
        {
            var cookie = cookies.getNext().QueryInterface(CI.nsICookie2);
            if ((! cookie )                              ||
                (cookie.host.toLowerCase() !== self.cookieJar.domain) )
            {
                continue;
            }

            self.cookieJar.values[cookie.name] = cookie.value;

            cDebug.log('cookie: host[ %s ], path[ %s ], name[ %s ]',
                       cookie.host, cookie.path, cookie.name);
        }

        self.db.state('cookies', self.cookies2str());

        cDebug.log("resource-connexions::getCookies(): cookie values[ %s ]",
                   cDebug.obj2str(self.cookieJar.values));
    },
     */

    destroy: function() {
        this._unloadObservers();
    },

    /*************************************************************************
     * "Private" methods
     *
     */

    /** @brief  Establish our state observers.
     */
    _loadObservers: function() {
        this.os.addObserver(this, "cookie-changed", false);
    },

    /** @brief  Establish our state observers.
     */
    _unloadObservers: function() {
        this.os.removeObserver(this, "cookie-changed");
    }
};

var connexions = new Connexions();

/*****************************************************************************
 * Worker thread and event to add retrieved bookmarks
 *
 * Note: Apparently we cannot invoke notifyObservers() from a background
 *       thread.  As a result, we must dispatch a "signalEvent" to the main
 *       thread which will cause the notifyObservers() to be invoked from it's
 *       context.
 *
 * Firefox >= 3, < 4
 */

/** @brief  The signal thread used to invoke connexions.signal within the
 *          context of the main UI thread.
 *  @param  subject     The subject string;
 *  @param  data        The data to include;
 */
var signalEvent = function(subject, data) {
    this.subject = subject;
    this.data    = data;
};

signalEvent.prototype = {
    QueryInterface: function(iid) {
        if (iid.equals(CI.nsIRunnable) ||
            iid.equals(CI.nsISupports))
        {
            return this;
        }

        throw CR.NS_ERROR_NO_INTERFACE;
    },

    run: function() {
        /*
        cDebug.log('resource-connexions::signalEvent thread: '
                    + "invoke signal with '%s'",
                    this.subject);
        // */

        // Signal progress
        connexions.signal(this.subject, this.data);
    }
};

/** @brief  The thread used to invoke connexions.db.addBookmark() within the
 *          context of the main UI thread so the database can then invoke
 *          connexions.signal().
 *  @param  bookmark    The bookmark object;
 */
var addBookmark = function(bookmark) {
    this.bookmark = bookmark;
};

addBookmark.prototype = {
    QueryInterface: function(iid) {
        if (iid.equals(CI.nsIRunnable) ||
            iid.equals(CI.nsISupports))
        {
            return this;
        }

        throw CR.NS_ERROR_NO_INTERFACE;
    },

    run: function() {
        /*
        cDebug.log('resource-connexions::addBookmark thread: '
                    + "bookmark[ %s ]",
                    cDebug.obj2str(this.bookmark));
        // */

        // Add this bookmark and, on success, signal progress.
        var res = connexions.db.addBookmark(this.bookmark);
        if (res !== null)
        {
            if (res.addStatus !== undefined)
            {
                if (connexions.state.syncStatus.progress.added === undefined)
                {
                    connexions.state.syncStatus.progress.added   = 0;
                    connexions.state.syncStatus.progress.updated = 0;
                    connexions.state.syncStatus.progress.ignored = 0;
                }

                switch (res.addStatus)
                {
                case 'created':
                    connexions.state.syncStatus.progress.added++;
                    break;

                case 'updated':
                    connexions.state.syncStatus.progress.updated++;
                    break;

                case 'ignored':
                    connexions.state.syncStatus.progress.ignored++;
                    break;
                }
            }

            connexions.state.syncStatus.progress.current++;
            connexions.signal('connexions.syncProgress',
                              connexions.state.syncStatus);
        }
    }
};

/** @brief  The thread used to add bookmarks OFF the main UI thread.
 *  @param  bookmarks   The array of bookmarks to add;
 */
var bookmarksThread = function(bookmarks) {
    this.bookmarks = bookmarks;
};

bookmarksThread.prototype = {
    QueryInterface: function(iid) {
        if (iid.equals(CI.nsIRunnable) ||
            iid.equals(CI.nsISupports))
        {
            return this;
        }

        throw CR.NS_ERROR_NO_INTERFACE;
    },

    signal: function(subject, data) {
        connexions.mainThread.dispatch(
                new signalEvent(subject, data),
                CI.nsIThread.DISPATCH_SYNC);
    },

    addBookmark: function(bookmark) {
        connexions.mainThread.dispatch(
                new addBookmark(bookmark),
                CI.nsIThread.DISPATCH_SYNC);
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
            cDebug.log('resource-connexions::bookmarksThread thread: '
                        +   'normalizeDate() ERROR: %s',
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
        var self        = this;
        var bookmarks   = self.bookmarks;

        /*
        cDebug.log('resource-connexions::bookmarksThread thread: %s bookmarks',
                   bookmarks.length);
        // */

        // Signal our first progress update
        connexions.state.syncStatus.progress = {
            total:      bookmarks.length,
            current:    0
        };

        self.signal('connexions.syncProgress', connexions.state.syncStatus);
        for each (var bookmark in bookmarks)
        {
            if (connexions.state.sync !== true)
            {
                // CANCEL the sync
                break;
            }

            /*
            cDebug.log('resource-connexions::bookmarksThread thread: '
                        +   'bookmark[ %s ]',
                        cDebug.obj2str(bookmark));
            // */

            var normalized  = self.normalizeBookmark(bookmark);

            /*
            cDebug.log('resource-connexions::bookmarksThread thread: '
                        +   'normalized[ %s ]',
                        cDebug.obj2str(normalized));
            // */

            self.addBookmark( normalized );
        }

        self.signal('connexions.syncEnd', connexions.state.syncStatus);
        self.signal('connexions.bookmarksUpdated');
        connexions.state.sync = false;
    }
};
