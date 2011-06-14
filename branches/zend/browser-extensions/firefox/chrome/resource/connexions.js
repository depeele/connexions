/** @file
 *
 *  The primary connexions class (connexions).
 *
 */
var EXPORTED_SYMBOLS    = ['connexions'];

const CC                    = Components.classes;
const CI                    = Components.interfaces;
const CR                    = Components.results;
const CU                    = Components.utils;
const CONNEXIONS_BASE_URL   = "%URL%";

CU.import("resource://connexions/debug.js");
CU.import("resource://connexions/db.js");

/*****************************************************************************
 * UI / Main
 *
 */
function Connexions()
{
    this.init();
}

Connexions.prototype = {
    wm:             CC['@mozilla.org/appshell/window-mediator;1']
                        .getService(CI.nsIWindowMediator),
    initialized:    false,
    user:           null,   // The current user

    debug:          cDebug,
    db:             connexions_db,

    prefsWindow:    null,
    strings:        null,

    cookieJar:      {
        domain:     "%DOMAIN%",
        values:     null,
    },
    jsonRpcInfo:    {
        version:    "%JSONRPC_VERSION%",
        transport:  "%JSONRPC_TRANSPORT%",
        url:        "%JSONRPC_URL%",
        id:         0
    },

    init: function() {
        if (this.initialized === true)  return;

        cDebug.log('resource-connexions::init():');

        var self    = this;

        self.initialized = true;

        // Normalize the cookie domain.
        self.cookieJar.domain = self.cookieJar.domain.toLowerCase();

        cDebug.log('resource-connexions::init(): completed');
    },

    /** @brief  Initiate the retrieval of the authenticated user.
     *  @param  callback    The callback to invoke upon success:
     *                          function(user)
     */
    retrieveUser: function(callback) {
        var self    = this;

        cDebug.log('resource-connexions::retrieveUser(): initiate...');

        /* Perform a JsonRpc request to find out who the authenticated user if
         * (if any)
         */
        self.jsonRpc('user.whoami', {}, {
            success: function(data, textStatus, xhr) {
                cDebug.log('resource-connexions::retrieveUser(): '
                            +   'jsonRpc return[ %s ]',
                            cDebug.obj2str(data));

                if (data.error !== null)
                {
                    return;
                }

                self.user = data.result;

                if (callback !== undefined)
                {
                    callback(self.user);
                }
            },
            error:   function(xhr, textStatus, error) {
                cDebug.log('resource-connexions::retrieveUser(): '
                            +   'ERROR retrieving user[ %s ]',
                            textStatus);
            }
        });

    },

    setStrings: function(strings) {
        this.strings = strings;
    },

    getString: function(name) {
        return (this.strings
                    ? this.strings.getString(name)
                    : null);
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
        var doc     = el.ownerDocument;
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
        var wind    = null;

        cDebug.log('resource-connexions::getWindow(): '
                        +   'via getMostRecentWindow');
        wind = this.wm.getMostRecentWindow('navigator:browser');

        return wind;
    },

    /** @brief  For contexts that do NOT have 'window', retrieve the browser
     *          instance associated with the CURRENT window.
     */
    getBrowser: function() {
        return (this.getWindow()).getBrowser();

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
        return (this.getWindow()).toggleSidebar(id, capture);
    },

    openXulWindow: function(xul, title, options, url) {
        return (this.getWindow()).openDialog(xul, title, options, url);
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
        var newWindow   = (this.getWindow()).open(url, '_blank', options);

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

    sync: function(isReload) {
        cDebug.log("resource-connexions::sync(): isReload[ %s ]", isReload);

        if (isReload)
        {
            connexions_db.emptyAllTables();
        }

        /* :TODO: Perform an asynchronous request for all bookmarks and add
         * them into the local database.
         */
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

        if (callbacks.success || callbacks.complete)
        {
            // Handle 'onload' to report success/complete
            xhr.onload = function(event) {
                // event.target (XMLHttpRequest)
                var data        = event.target.responseText;
                var textStatus  = event.target.statusText;

                if (callbacks.success)
                {
                    // Attempt to parse 'data' as JSON
                    var json;
                    try {
                        json = JSON.parse(data);
                    } catch(e) {
                        if (callbacks.error)
                        {
                            callbacks.error(xhr, textStatus,"JSON parse error");
                            return;
                        }
                    }

                    callbacks.success(json, textStatus, xhr);
                }

                if (callbacks.complete) callbacks.complete(xhr, textStatus);
            };
        }

        if (callbacks.error || callbacks.complete)
        {
            // Handle 'onerror' to report error/complete
            xhr.onerror = function(event) {
                // event.target (XMLHttpRequest)
                var status      = (event.target !== undefined
                                    ? event.target.status
                                    : -1);
                var textStatus  = "Error";
                try {
                    textStatus = event.target.statusText;
                } catch(e) {}

                if (callbacks.error)    callbacks.error(xhr, textStatus,status);
                if (callbacks.complete) callbacks.complete(xhr, textStatus);
            };
        }

        if (callbacks.progress)
        {
            // Handle 'onprogress' to report progress
            xhr.onprogress = function(event) {
                // event.position, event.totalSize,
                // event.target (XMLHttpRequest)

                callbacks.progress(event.position, event.totalSize, xhr);
            };
        }

        /*
        request.onuploadprogress = function(event) {
        };
        // */

        // /*
        cDebug.log("resource-connexions::jsonRpc(): "
                   +    "method[ %s ], transport[ %s ], url[ %s ]",
                   method, self.jsonRpcInfo.transport, self.jsonRpcInfo.url);
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

        if ((self.cookieJar.values === null) ||
            (self.cookieJar.values.length < 1) )
        {
            // Refresh cookies
            self.getCookies();
        }

        if (self.cookieJar.values.length > 0)
        {
            // Include cookies
            var cookie  = self.cookieJar.values.join(';');

            // /*
            cDebug.log("resource-connexions::jsonRpc(): "
                        +   "cookies[ %s ] == cookie[ %s ]",
                       cDebug.obj2str( self.cookieJar.values ),
                       cookie);
            // */

            xhr.setRequestHeader('Cookie', cookie);
        }

        // Send the request
        xhr.send( JSON.stringify( rpc ) );
    },

    /** @brief  Retrieve all cookies in the domain specified by this.cookieJar
     */
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

        self.cookieJar.values = [];
        while ( cookies.hasMoreElements() )
        {
            var cookie = cookies.getNext().QueryInterface(CI.nsICookie2);
            // /*
            if (! cookie )  continue;
            cDebug.log('cookie: host[ %s ], path[ %s ], name[ %s ]',
                       cookie.host, cookie.path, cookie.name);
            // */

            if ((! cookie )                              ||
                (cookie.host.toLowerCase() !== self.cookieJar.domain) )
            {
                continue;
            }

            self.cookieJar.values.push( cookie.name +'='+ cookie.value);

            /*
            cDebug.log('cookie: host[ %s ], path[ %s ], name[ %s ]',
                       cookie.host, cookie.path, cookie.name);
            // */
        }

        cDebug.log("resource-connexions::getCookies(): cookie values[ %s ]",
                   cDebug.obj2str(self.cookieJar.values));
    },

    destroy: function() {
    }
};

var connexions = new Connexions();
