/** @file
 *
 *  The primary connexions class (connexions).
 *
 */
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
    prefsWindow:    null,
    strings:        null,
    debug:          null,

    init: function() {
        if (this.initialized === true)  return;

        this.initialized = true;
        this.strings     = document.getElementById("connexions-strings");
        this.debug       = cDebug;

        cDebug.log('connexions::init(): completed');
    },

    getString: function(name) {
        return (this.strings
                    ? this.strings.getString(name)
                    : null);
    },

    /** @brief  Given the contextMenu instance and desired tagging type,
     *          present the tag page.
     *  @param  el      The DOM element that is the target
     *                  (if any, SHOULD be equivalent to document.popupNode);
     *  @param  type    The desired type of tagging
     *                  ('page', 'link', 'media');
     *
     */
    tagPage: function(el, type) {
        var url, name;
        var docUrl  = (document.URL
                        ? document.URL
                        : gURLBar.value);

        cDebug.log('tagPage(): docUrl[ %s ]', docUrl);

        switch (type)
        {
        case 'page':
            url  = docUrl;
            name = (document.title
                        ? document.title
                        : url);

            var selection   = this.getSelectedText();
            var description = (selection.str ? selection.str : '');

            cDebug.log('tagPage(): type[ %s ], url[ %s ], name[ %s ], '
                            +   'description[ %s ]',
                           type, url, name, description);

            this.openTagPage(url, name, description);
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

            this.openTagPage(url, name);
            //this.popupAlert(type);
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

    loadPage: function(e, page) {
        var url = null;

        cDebug.log("loadPage(): event[ %s ], page[ %s ]",
                        cDebug.obj2str(e),
                        page);
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

        case 'main':
            url = '';
            break;
        }

        if (url !== null) {
            this.openTab(this.url(url));
        }
    },

    /** @brief  For contexts that do NOT have 'window', retrieve the CURRENT
     *          window.
     */
    getWindow: function() {
        var wind    = null;

        if (window && window.getBrowser)
        {
            //cDebug.log('connexions::getWindow(): Simple');
            wind = window;
        }
        /*
        else if (document && document.commandDispatcher)
        {
            cDebug.log('connexions::getWindow(): via document');
            wind = document.commandDispatcher.focusedWindow;
        }
        // */
        else
        {
            //cDebug.log('connexions::getWindow(): via getMostRecentWindow');
            wind = this.wm.getMostRecentWindow('navigator:browser');
        }

        return wind;
    },

    /** @brief  For contexts that do NOT have 'window', retrieve the browser
     *          instance associated with the CURRENT window.
     */
    getBrowser: function() {
        return (getBrowser
                    ? getBrowser()
                    : (this.getWindow()).getBrowser());
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

        var curWindow   = (document && document.commandDispatcher
                            ? document.commandDispatcher.focusedWindow
                            : this.getWindow());
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

    destroy: function() {
    }
};

var connexions  = new Connexions();

/*
Connexions_log('Connexions: types: number[ '+ cDebug.type(5) +' ]');
Connexions_log('Connexions: types: number[ '+ cDebug.type(5.5) +' ]');
Connexions_log('Connexions: types: bool  [ '+ cDebug.type(true) +' ]');
Connexions_log('Connexions: types: bool  [ '+ cDebug.type(false) +' ]');
Connexions_log('Connexions: types: array [ '+ cDebug.type([1,2]) +' ]');
Connexions_log('Connexions: types: regexp[ '+ cDebug.type(/abc/) +' ]');
Connexions_log('Connexions: types: object[ '+ cDebug.type({a:1}) +' ]');
Connexions_log('Connexions: types: null  [ '+ cDebug.type(null)  +' ]');
Connexions_log('Connexions: types: string[ '+ cDebug.type("string") +' ]');
Connexions_log('Connexions: types: object[ '+ cDebug.obj2str({a:1}) +' ]');
// */

window.addEventListener("load",   function(){ connexions.init(); },    false);
window.addEventListener("unload", function(){ connexions.destroy(); }, false);
