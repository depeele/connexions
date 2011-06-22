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
var EXPORTED_SYMBOLS    = ['connexions'/*, $, $$ */];

var CC                  = Components.classes;
var CI                  = Components.interfaces;
var CR                  = Components.results;
var CU                  = Components.utils;
var CONNEXIONS_BASE_URL = "%URL%";

CU.import("resource://connexions/debug.js");
CU.import("resource://connexions/db.js");

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
    appVersion:     0.0,

    user:           null,   // The current user

    debug:          cDebug,
    db:             null,

    bookmarksThread:null,
    cookieTimer:    CC['@mozilla.org/timer;1']
                        .createInstance(CI.nsITimer),
    cookieTicking:  false,
    prefsWindow:    null,
    strings:        null,

    pendingNotifications:   [],

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

        // Postpone until now to allow us to move the inclusion of db.js down
        cDb.setConnexions(this);
        this.db    = cDb;

        cDebug.log('resource-connexions::init():');

        var self    = this;

        self.initialized = true;
        self.appVersion  = self.getAppVersion();

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
     *
     *  @return this for a fluent interface.
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

        return self;
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
        case 'connexions.syncEnd':
            // Our addBookmarks worker has completed the sync.
            self.signal('connexions.bookmarksUpdated');
            self.state.sync = false;
            break;

        case 'http-on-modify-request':
            /* An HTTP request is about to be sent.
             *
             * Locate the target URL and invoke db.incrementVisitCount() with
             * that url.  If it is the URL of an existing bookmark, the visit
             * count and visitedOn date will be updated.
             */
            subject.QueryInterface(CI.nsIHttpChannel);

            var url         = subject.URI.spec;
            /*
            cDebug.log('resource-connexions::observe(): topic[ %s ], url[ %s ]',
                        topic, url);
            // */

            self.db.incrementVisitCount( url );
            break;

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

        // Firefox 4- alert notification events
        case 'alertfinished':
        case 'alertclickcallback':
            var cookie      = parseInt(data, 10);
            var nCallbacks  = self.pendingNotifications.length;

            /* If this callback is at the END of the notifications queue, pop
             * it off.  Hopefully this won't get too deep...
             */
            var callback    = ( (cookie + 1) === nCallbacks
                                ? self.pendingNotifications.pop()
                                : (cookie < nCallbacks
                                    ? self.pendingNotifications[cookie]
                                    : null) );
            if (callback)
            {
                if ((topic === 'alertclickcallback') &&
                    (callback.click !== undefined))
                {
                    callback.click();
                }
                else if ((topic === 'alertfinished') &&
                         (callback.close !== undefined))
                {
                    callback.close();
                }
            }
        }
    },

    /** @brief  Signal observers.
     *  @param  subject The subject name;
     *  @param  data    The event data;
     *
     *  @return this for a fluent interface.
     */
    signal: function(subject, data) {
        if (! this.tm.isMainThread)
        {
            /* Apparently, nsI* interfaces (e.g. this.os, nsIObserverService)
             * can only be invoked from the main thread.  Since we're NOT on
             * the main thread, use MainThread to dispatch a signal request to
             * the main thread.
             */
            /*
            cDebug.log('resource-connexions::signal(): subject[ %s ] -- '
                        + 'dispatch to main thread',
                       subject);
            // */
            this.tm.mainThread.dispatch(
                new MainThread('signal', {subject: subject,
                                          data:    data}),
                CI.nsIThread.DISPATCH_SYNC);
            return this;
        }

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

        return this;
    },

    /** @brief  Retrieve the version of our host application (i.e. Firefox)
     *
     *  @return The version information.
     */
    getAppVersion: function() {
        if (this.appVersion < 1)
        {
            // assuming we're running under Firefox
            var appInfo = CC["@mozilla.org/xre/app-info;1"]
                                .getService(CI.nsIXULAppInfo);

            this.appVversion = parseFloat(appInfo.version);
        }

        return this.appVersion;

        /*
        var vc      = CC["@mozilla.org/xpcom/version-comparator;1"]
                            .getService(CI.nsIVersionComparator);

        if (vc.compare(appInfo.version, "1.5") >= 0) {
            // running under Firefox 1.5 or later
        }
        // */
    },

    /** @brief  Initiate the retrieval of the authenticated user.
     *  @param  callback    The callback to invoke upon success:
     *                          function(user)
     *
     *  @return this for a fluent interface.
     */
    retrieveUser: function(callback) {
        var self    = this;

        if (self.state.retrieveUser === true)
        {
            // In process
            return self;
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

        return self;
    },

    /** @brief  Set our localized string bundle.
     *  @param  strings A string bundle.
     *
     *  @return this for a fluent interface.
     */
    setStrings: function(strings) {
        this.strings = strings;

        return this;
    },

    /** @brief  Given an object, return it's "primative" type.
     *  @param  obj     The object;
     *
     *  @return The type:
     *              object, array, regexp, date, string, number
     */
    type: function(obj) {
        var type    = (obj === null ? 'null' : typeof(obj));
        if (type === 'object')
        {
            // What TYPE of object
            if (obj.length) { type = 'array'; }
            if (obj.exec)   { type = 'regexp'; }
            if (obj.now)    { type = 'date'; }
        }

        return type;
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
            if ((strArray !== undefined) && (this.type(strArray) !== 'array'))
            {
                strArray = [ strArray ];
            }

            try {
                str = (strArray
                        ? this.strings.getFormattedString(name, strArray)
                        : this.strings.getString(name));
            } catch(e) {
                cDebug.log('connexions-resource::getString(): ERROR: '
                            + 'name[ %s ]: %s',
                            name, e.message);
            }
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
     *  @return this for a fluent interface.
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

        return self;
    },

    /** @brief  Present a popup alert
     *  @param  msg     The message to present;
     *
     *  @return this for a fluent interface.
     */
    popupAlert: function(msg) {
        var promptService =
                CC["@mozilla.org/embedcomp/prompt-service;1"]
                          .getService(CI.nsIPromptService);
        promptService.alert(this.getWindow(), 'Connexions Alert', msg);

        return this;
    },

    /** @brief  Present a confirmation dialog.
     *  @param  title       The dialog itle;
     *  @param  question    The confirmation question;
     *
     *  @return true | false
     */
    'confirm': function(title, msg) {
        var promptService =
                CC["@mozilla.org/embedcomp/prompt-service;1"]
                          .getService(CI.nsIPromptService);
        return promptService.confirm(this.getWindow(), title, msg);
    },

    /** @brief  Present a system notification.
     *  @param  title       The notification title;
     *  @param  msg         The notification message;
     *  @param  iconUrl     An options URL to an icon to include;
     *  @param  callbacks   Desired callbacks:
     *                          {click: function(),
     *                           close: function() }
     *
     *  :NOTE: For Firefox <4, this works nicely on Windows but NOT on
     *         OSX.  On OSX, notifications are completely ignored.
     *
     *  @return this for a fluent interface.
     */
    notify: function(title, msg, iconUrl, callbacks) {
        var self    = this;

        cDebug.log('resource-connexions::notify(): title[ %s ], msg[ %s ]',
                    title, msg);

        if (self.appVersion >= 4.0)
        {
          // Firefox 4+
          try {
            var notify =
                    navigator.mozNotification.createNotification(title, msg,
                                                                  iconUrl);

            cDebug.log('resource-connexions::notify(): using Firfox 4+');

            if (callbacks !== undefined)
            {
                if (callbacks.click !== undefined)
                {
                    notify.onclick = callbacks.click;
                }
                if (callbacks.close !== undefined)
                {
                    notify.onclose = callbacks.close;
                }
            }

            notify.show();
            return this;
          } catch(e) {}
        }

        // Firefox 4- (or if Firefox 4+ fails)
        var idex;
        try {
            var as      = CC['@mozilla.org/alerts-service;1']
                            .getService(CI.nsIAlertsService);
            idex = self.pendingNotifications.length;

            cDebug.log('resource-connexions::notify(): using Firfox 4-');

            self.pendingNotifications.push( callbacks );

            /* close and click callbacks are handled by our 'observe' method
             * for 'alertfinished' and 'alertclickcallback'
             */
            as.showAlertNotification(iconUrl, title, msg,
                                     true,                  // textClickable
                                     'connexions-'+idex,    // cookie
                                     self,                  // alertListener
                                     'connexions-alert');
        } catch(e) {
            cDebug.log('resource-connexions::notify(): ERROR: %s',
                        e.message);

            if (idex !== undefined)
            {
                self.pendingNotifications.pop();
            }
        }

        return this;
    },

    /** @brief  Open the options windows.
     *
     *  @return this for a fluent interface.
     */
    showOptions: function() {
        cDebug.log("showOptions()");

        if (! this.prefsWindow || this.prefsWindow.closed) {
            var xul     = 'chrome://connexions/content/options.xul';
            var title   = 'Connexions Options';
            var opts    = 'chrome,titlebar,toolbar,centerscreen,dialog=yes';
            this.prefsWindow =
                this.openXulWindow(xul, title, opts);
        }
        this.prefsWindow.focus();

        return this;
    },

    /** @brief  Load a new page.
     *  @param  event       The originating event;
     *  @param  page        The connexions page to load
     *                      ( myBookmarks myTags, myNetwork, myInbox,
     *                        bookmarks, tags, people, main, signin, register)
     *  @param  where       Where to open ( [current], window, tab, popup);
     *  @param  closeAction The close action to invoke when the page is to be
     *                      closed [ 'back' ];
     *
     *  Open the new page based upon the current keyboard state:
     *      shift:      in a new window;
     *      meta/ctrl:  in a new tab;
     *      -none-:     in the current window/tab;
     *
     *  :NOTE: Up to at least Firefox 3.6.17, <menuitem oncommand> does NOT
     *         pass along any button or keyboard state, making it extremely
     *         difficult (i.e. impossible) to modify the open behavious based
     *         upon the current keyboard state.
     *
     *  @return this for a fluent interface.
     */
    loadPage: function(event, page, where, closeAction) {
        var url = null;

        /* event properties:
         *  target
         *  currentTarget
         *  button              0:left, 1:middle, 2:right
         *  detail              Number of clicks
         *  screenX,screenY     Mouse position relative to tl screen
         *  clientX,clientY     Mouse position relative to tl document
         *  keyCode
         *  charCode
         *  altKey, ctrlKey, shiftKey, metaKey
         *  which
         *
         * event methods:
         *  stopPropagation()
         *  preventDefault()
         *
        cDebug.log("loadPage(): "
                   + "event[ %s ], "
                   + "event[ type:%s, button:%s, keyCode:%s, charCode:%s, "
                   +        "alt:%s, ctrl:%s, shift:%s, meta:%s, which:%s], "
                   + "page[ %s ], where[ %s ]",
                        cDebug.obj2str(event),
                        event.type,
                        event.button, event.keyCode, event.charCode,
                        event.altKey, event.ctrlKey,
                        event.shiftKey, event.metaKey,
                        event.which,
                        page, where);
        // */

        if (where === undefined)
        {
            /* Determine 'where' by the incoming event.
             *
             * Modeled after browser.js::whereToOpenLink() (which is
             * unavailable in this context),  return one of:
             *      current, tab, tabshifted, window
             *
             * Do NOT include 'save' as an option since we don't expect that to
             * be useful in this context.
             */
            if (event.metaKey || event.ctrlKey)
            {
                if (event.shiftKey)
                {
                    where = 'tabshifted';
                }
                else
                {
                    where = 'tab';
                }
            }
            /*
            else if (event.altKey)
            {
                where = 'save';
            }
            // */
            else if (event.shiftKey)
            {
                where = 'window';
            }
            else
            {
                where = 'current';
            }
        }
        cDebug.log("loadPage(): page[ %s ], where[ %s ]",
                    page, where);

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

            /*
            cDebug.log("loadPage(): page[ %s ], where[ %s ], final url[ %s ]",
                       page, where, url);
            // */

            this.openIn( url, where );
        }

        return this;
    },

    /** @brief  For contexts that do NOT have 'window', retrieve the CURRENT
     *          window.
     *
     *  @return The current window.
     */
    getWindow: function() {
        return this.wm.getMostRecentWindow('navigator:browser');
    },

    /** @brief  For contexts that do NOT have 'document', retrieve the CURRENT
     *          document.
     *
     *  @return The document of the current window.
     */
    getDocument: function() {
        return this.getWindow().document;
    },

    /** @brief  For contexts that do NOT have 'window', retrieve the browser
     *          instance associated with the CURRENT window.
     *
     *  @return The browser instance of the current window.
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
     *
     *  @return this for a fluent interface.
     */
    toggleSidebar: function(id, capture) {
        this.getWindow().toggleSidebar(id, capture);
        return this;
    },

    /** @brief  Open a new XUL dialog.
     *  @param  xul         The URL to the XUL overlay;
     *  @param  title       The window title;
     *  @param  options     Window properties;
     *  @param  data        Data (object) to be passed to the new window;
     *
     *  @return The new dialog window
     */
    openXulWindow: function(xul, title, options, data) {
        return this.getWindow().openDialog(xul, title, options, data);
    },

    /** @brief  Open the given URL as specified by 'where';
     *  @param  url     The desired URL;
     *  @param  where   Where to open:
     *                      [current]   The currently active window/tab;
     *                      window      A new window;
     *                      tab         A new tab;
     *                      tabshifted  A new tab (in the background);
     *                      popup       In a popup window;
     *
     *  @return The (new) window
     */
    openIn: function(url, where) {
        var self    = this;

        cDebug.log("openIn(): url[ %s ], where[ %s ]",
                    url, where);

        /* FIRST, look through each browser and tab to see if this URL is
         * already open.  If it is, focus on the indow/tab.
         */
        var be      = self.wm.getEnumerator('navigator:browser');
        var res     = false;
        while ((res === false) && (be.hasMoreElements()))
        {
            var win = be.getNext();
            var tb = win.gBrowser;

            // Check each tab of this browser instance
            var nTabs   = tb.browsers.length;
            for (var idex = 0; idex < nTabs; idex++)
            {
                var browser     = tb.getBrowserAtIndex(idex);
                if ( url === browser.currentURI.spec )
                {
                    /* We found a window/tab that is opened to the desired URL!
                     *
                     * Select this tab and focus on this window.
                     */
                    tb.selectedTab = tb.tabContainer.childNodes[idex];
                    win.focus();

                    res = win;
                    break;
                }
            }
        }

        if (res === false)
        {
            /* The URL isn't already opened.  Open it now in a way compatable
             * with browser.js::openUILinkIn(), which is unavailable in this
             * context.
             */
            switch (where)
            {
            case 'window':
                res = self.openWindow( url );
                break;

            case 'tab':
            case 'tabshifted':
                res = self.openTab( url, (where === 'tabshifted'));
                break;

            case 'popup':
                res = self.openPopupWindow( url );
                break;

            case 'current':
            default:
                res = self.openCurrent( url );
                break;
            }
        }

        return res;
    },

    /** @brief  Open the given URL in the currently active window/tab.
     *  @param  url     The desired url;
     *  @param  name    The new window name (no white-space, e.g. '_blank');
     *
     *  @return The window.
     */
    openCurrent: function(url, name) {
        var win = this.getWindow();

        if (win)
        {
            // Use an existing browser window
            //win.delayedOpenTab(url, null, null, null, null);
            win.content.document.location = url;
        }
        else
        {
            // No browser windows are open, so open a new one
            win = this.openWindow(url, name);
        }

        return win;
    },

    /** @brief  Open the given URL in a new tab.
     *  @param  url             The desired url;
     *  @param  inBackground    Should the new tab remain in the background
     *                          (true) or receive immediate focus? [ false ]
     *
     *  @return The new tab/window.
     */
    openTab: function(url, inBackground) {
        cDebug.log("openTab(): url[ %s ], inBackground[ %s ]",
                   url, cDebug.obj2str(inBackground));

        var browser = this.getBrowser();
        var tab;

        if (inBackground === undefined) { inBackground = false; }

        /* Firefox 3.6+
         *  tab = browser.loadOneTab( url, {loadInBackground: inBackground});
         */
        tab = browser.loadOneTab( url,
                                  null,     // referrerURI
                                  null,     // charset
                                  null,     // postData
                                  inBackground);
        /*
        tab = browser.addTab(url);
        browser.selectedTab = tab;
        */

        return tab;
    },

    /** @brief  Open the given URL in a new, normal browser window.
     *  @param  url     The desired url;
     *  @param  name    The new window name (no white-space, e.g. '_blank');
     *
     *  @return The new window.
     */
    openWindow: function(url, name) {
        var options = 'titlebar=yes'
                    + ',menubar=yes'
                    + ',toolbar=yes'
                    + ',location=yes'
                    + ',personalbar=yes'
                    + ',scrollbars=yes'
                    + ',resizable=yes'
                    + ',status=yes'
                    + ',centerscreen'
                    + ',dialog=no';

        return this._openWindow(url, name, options);
    },

    /** @brief  Open the given URL in a new, popup/dialog window.
     *  @param  url     The desired url;
     *  @param  name    The new window name (no white-space, e.g. '_blank');
     *
     *  @return The new window.
     */
    openPopupWindow: function(url, name) {
        var width   = 980;
        var height  = 680;
        var options = 'chrome'
                    + ',dependent'
                    + ',titlebar=yes'
                    + ',menubar=no'
                    + ',toolbar=no'
                    + ',scrollbars=yes'
                    + ',resizable=yes'
                    + ',status=yes'
                    + ',dialog=yes'
                    + ',centerscreen'
                    + ',width=' + width
                    + ',height='+ height;

        return this._openWindow(url, name, options);
    },

    /** @brief  Open the bookmark post page in a new popup window.
     *  @param  url         The url to bookmark;
     *  @param  name        The name of the new bookmark;
     *  @param  description The description of the new bookmark;
     *  @param  tags        A comma-separated list of tags;
     *
     *  @return The new window.
     */
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

        return this.openPopupWindow( this.url('post'+ query),
                                    'Bookmark a Page' );
    },

    /** @brief  Retrieve any selected text from the currently active window.
     *  @param  maxLen  The maximum number of characters to return;
     *
     *  @return The selected text.
     */
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

    /** @brief  Given a connexions-relative path, return a complete, absolute
     *          URL.
     *  @param  path    The connexions-relative path.
     *
     *  @return The equivalent absolute URL.
     */
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

    /** @brief  Retrieve the current sync status.
     *
     *  @return The current sync status.
     */
    getSyncStatus: function() {
        return this.state.syncStatus;
    },

    /** @brief  Delete all local bookmarks.
     *
     *  @return this for a fluent interface.
     */
    delBookmarks: function() {
        this.db.deleteAllBookmarks();

        return this;
    },

    /** @brief  Initiate a Sync with connexions.
     *  @param  isReload    If true, delete all local bookmarks first.
     *
     *  @return this for a fluent interface.
     */
    sync: function(isReload) {
        var self    = this;

        if (self.user === null)
        {
            cDebug.log("resource-connexions::sync(): NOT signed in");
            return this;
        }

        if (self.state.sync === true)
        {
            // In process
            return this;
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
                    self.addBookmarks(data.result);
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

        return this;
    },

    /** @brief  If a sync is in progress, cancel it.
     *
     *  @return this for a fluent interface.
     */
    syncCancel: function() {
        if (this.state.sync === true)
        {
            this.state.sync = false;
        }

        return this;
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
     *
     *  @return The active XMLHttpRequest instance.
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
                   +    "url[ %s ], cookies[ %s ], rpc[ %s ]",
                   method, self.jsonRpcInfo.transport,
                   self.jsonRpcInfo.url, cookies,
                   cDebug.obj2str(rpc));
        // */

        // Send the request
        xhr.send( JSON.stringify( rpc ) );

        return xhr;
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

    /** @brief  Given a set of bookmarks retrieved from the server,
     *          add/update our local cache.
     *  @param  bookmarks   An array of bookmark objects.
     *
     *  :NOTE: This makes use of BookmarksWorker
     *         (from chrome/resource/bookmarks-worker.js) as a worker thread to
     *         move bookmark processing off the main UI thread.
     */
    addBookmarks: function(bookmarks) {
        var self    = this;

        if ((self.bookmarksThread === null) && (self.tm !== null))
        {
            /* Retrieve the main thread and create a new background thread to
             * handle bookmarks processing.
             */
            self.bookmarksThread = self.tm.newThread(0);
        }

        cDebug.log('resource-connexions::addBookmarks(): signal worker');
        self.bookmarksThread.dispatch(new BookmarksWorker(bookmarks),
                                      CI.nsIThread.DISPATCH_NORMAL);
    },

    /** @brief  Given a normalized bookmark object, attempt to add it and
     *          signal any progress.
     *  @param  bookmark    The normalized bookmark object.
     *
     *  :NOTE: This will typically be called from BookmarksWorker
     *         (chrome/resource/bookmarks-worker.js) when it needs to add
     *         a bookmark.  We handle things this way because the database
     *         objects makes use of connexions.signal() which can only be
     *         invoked form the main thread.
     *
     *  @return this for a fluent interface.
     */
    addBookmark: function(bookmark) {
        var self    = this;
        var res     = self.db.addBookmark(bookmark);
        if (res !== null)
        {
            if (res.addStatus !== undefined)
            {
                if (self.state.syncStatus.progress.added === undefined)
                {
                    self.state.syncStatus.progress.added   = 0;
                    self.state.syncStatus.progress.updated = 0;
                    self.state.syncStatus.progress.ignored = 0;
                }

                switch (res.addStatus)
                {
                case 'created':
                    self.state.syncStatus.progress.added++;
                    break;

                case 'updated':
                    self.state.syncStatus.progress.updated++;
                    break;

                case 'ignored':
                    self.state.syncStatus.progress.ignored++;
                    break;
                }
            }

            self.state.syncStatus.progress.current++;
            self.signal('connexions.syncProgress', self.state.syncStatus);
        }

        return self;
    },

    /** @brief  Destroy/unload this instance.
     */
    destroy: function() {
        this._unloadObservers();
    },

    /*************************************************************************
     * "Private" methods
     *
     */

    /** @brief  Receive an addBookmarks worker message.
     *  @param  event   The Post event which SHOULD contain a data item of the
     *                  form:
     *                      { type: 'signal | call', ... }
     */
    _addBookmarksMessage: function(event) {
        var self    = this;
        var info    = event.data;

        switch (info.type)
        {
        case 'signal':
            // Signal the provided 'subject' and 'data'
            self.signal(info.subject, info.data);
            break;

        case 'call':
            self[info.method].apply(self, info.args);
            break;
        }
    },

    /** @brief  Open the given URL in a new, normal browser window.
     *  @param  url         The desired url;
     *  @param  name        The new window name (no white-space, e.g. '_blank');
     *  @param  options     The window options;
     *
     *  @return The new window.
     */
    _openWindow: function(url, name, options) {
        /*
        var xul         = 'chrome://browser/content/browser.xul';
        var newWindow   = this.openXulWindow(xul, name, options, url);
        */
        if (name !== undefined) name = name.replace(/\s+/g, '_').toLowerCase();

        var newWindow   = this.getWindow().open(url, name, options);

        return newWindow;
    },

    /** @brief  Establish our state observers.
     */
    _loadObservers: function() {
        this.os.addObserver(this, "cookie-changed",         false);
        this.os.addObserver(this, "http-on-modify-request", false);
        this.os.addObserver(this, "connexions.syncEnd",     false);
    },

    /** @brief  Establish our state observers.
     */
    _unloadObservers: function() {
        this.os.removeObserver(this, "cookie-changed");
        this.os.removeObserver(this, "http-on-modify-request");
        this.os.removeObserver(this, "connexions.syncEnd");
    }
};

var connexions  = new Connexions();

/* Place this at the bottom to mitigate the dependency loop between
 * connexions.js and bookmarks-worker.js
 */
CU.import("resource://connexions/bookmarks-worker.js");

/*****************************************************************************
 * Interface from a worker thread back to the main thread.
 *
 */

/** @brief  The signal thread used to invoke connexions.signal within the
 *          context of the main UI thread.
 *  @param  type        The type of call back to the main thread
 *                      ( signal | call );
 *  @param  data        Type-specific data:
 *                          signal: {subject, data}
 *                          call:   {method,  args}
 */
var MainThread = function(type, data) {
    this.type = type;
    this.data = data;
};

MainThread.prototype = {
    QueryInterface: function(iid) {
        if (iid.equals(CI.nsIRunnable) ||
            iid.equals(CI.nsISupports))
        {
            return this;
        }

        throw CR.NS_ERROR_NO_INTERFACE;
    },

    run: function() {
        var info    = this.data;

        switch (this.type)
        {
        case 'signal':
            connexions.signal(info.subject, info.data);
            break;

        case 'call':
            connexions[info.method].apply(connexions, info.args);
            break;
        }
    }
};
