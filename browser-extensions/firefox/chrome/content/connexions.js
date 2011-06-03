/*****************************************************************************
 * UI / utility functions
 *
 */
var gLog  = null;

function Connexions_log(msg, stackFrame)
{
    if (gLog === null)
    {
        if (console && console.log)
        {
            gLog = function(msg) {
                console.log(msg);
            };
        }
        else
        {
            var console =
                Components
                    .classes['@mozilla.org/consoleservice;1']
                        .getService(Components.interfaces.nsIConsoleService);

            gLog = function(msg) {
                console.logStringMessage(msg);
            };
        }
    }

    if (stackFrame === undefined)
    {
        stackFrame = Components.stack.caller;
    }

    if (msg.length < 80)
    {
        msg += '                                                                               '.substr(0, 80 - msg.length);
    }

    msg += " - Source File: "+ stackFrame.filename;
    if (stackFrame.lineNumber !== undefined)
    {
        msg += ', line '+   stackFrame.lineNumber;

        if (stackFrame.columnNumber !== undefined)
        {
            msg += ', column '+ stackFrame.columnNumber;
        }
    }
    gLog(msg);
}

Connexions_log('Connexions: running...');

/*****************************************************************************
 * UI / Main
 *
 */
const CONNEXIONS_BASE_URL   = "%URL%";
const gWM                   =
        Components.classes['@mozilla.org/appshell/window-mediator;1']
                    .getService(Components.interfaces.nsIWindowMediator);

var   connexions = {
    prefsWindow:    null,
    initialized:    false,
    strings:        null,

    log: function(fmt) {
        var msg = fmt;
        for (var idex = 1; idex < arguments.length; idex++)
        {
            //msg = msg.replace(/%s/, connexions.obj2str(arguments[idex]));
            msg = msg.replace(/%s/, arguments[idex]);
        }

        Connexions_log(msg, Components.stack.caller);
    },

    type: function(obj) {
        var type    = (obj === null ? 'null' : typeof(obj));
        if (type === 'object')
        {
            // What TYPE of object
            if (obj.length) type = 'array';
            if (obj.exec)   type = 'regexp';
            if (obj.now)    type = 'date';
        }

        return type;
    },

    obj2str: function(obj, depth, maxDepth) {
        var str = "";
        if (obj == null) {
            str += "null";
            return str;
        }

        var type    = connexions.type(obj);
        depth    = depth || 0;
        maxDepth = maxDepth || 5;

        if (depth > maxDepth)
        {
            return "%Exceeded depth "+ maxDepth +"%";
        }

        /*
        Connexions_log('obj2str: obj[ '+ obj +' ], type[ '+ type +' ], '
                        +'depth[ '+ depth +' ], max[ '+ maxDepth +' ]');
        // */
        switch (type)
        {
        case 'boolean':
            str += type +"[ "+ (obj ? 'true' : 'false') +" ]";
            break;

        case 'string':
            str += type +'[ "'+ obj +'" ]';
            break;

        case 'function':
            str += type;
            break;

        case 'number':
        case 'date':
        case 'regexp':
            str += type +"[ "+ obj +" ]";
            break;

        case 'array':
        case 'object':
            var open    = (type === 'array' ? '[' : '{');
            var close   = (type === 'array' ? ']' : '}');
            var parts   = [];
            var indent  = '                                                 '
                            .substr(0, (depth + 1) * 2);

            for (var prop in obj) {
                if (obj.hasOwnProperty(prop))
                {
                    if (type === 'array')
                    {
                        str = connexions.obj2str(obj[prop], depth+1);
                    }
                    else
                    {
                        str = indent
                            +'"'+ prop +'": '
                            + connexions.obj2str(obj[prop], depth+1);
                    }
                    parts.push(str);
                }
            }
            if (type === 'object')
            {
                type = String(obj);
                type = type.replace(/^\s*\[\s*/, '')
                           .replace(/\s*\]\s*$/, '')
                           .replace(/\s+Object$/, '');
            }
            str = type + open + parts.join(', ') + close;
        }

        return str;
    },

    init: function() {
        // initialization code
        connexions.strings     = document.getElementById("connexions-strings");
        connexions.initialized = true;

        connexions.log('connexions::init(): completed');
    },

    getString: function(name) {
        return (connexions.strings
                    ? connexions.strings.getString(name)
                    : null);
    },

    tagPage: function(e, type) {
        switch (type)
        {
        case 'page':
            connexions.openTagPage();
            break;

        case 'link':
        case 'media':
            connexions.popupAlert(type);
            break;
        }
    },

    popupAlert: function(msg) {
        var promptService =
                Components.classes["@mozilla.org/embedcomp/prompt-service;1"]
                          .getService(Components.interfaces.nsIPromptService);
        promptService.alert(window, 'Connexions Alert', msg);
    },

    toolbarButtonCommand: function(e) {
        // just reuse the function above.    you can change this, obviously!
        connexions.popupAlert('toolbar button');
    },

    showOptions: function() {
        connexions.log("showOptions()");

        if (! connexions.prefsWindow || connexions.prefsWindow.closed) {
            var xul     = 'chrome://connexions/content/options.xul';
            var title   = 'Connexions Options';
            var opts    = 'chrome,titlebar,toolbar,centerscreen,dialog=no';
            connexions.prefsWindow =
                connexions.openWindow(xul, title, opts);
        }
        connexions.prefsWindow.focus();
    },

    loadPage: function(e, page) {
        var url = null;

        connexions.log("loadPage(): event[ %s ], page[ %s ]",
                        connexions.obj2str(e),
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
        }

        if (url) {
            connexions.browserLoadPage(e, connexions.url(url));
        }
    },

    getBrowser: function() {
        return (gWM.getMostRecentWindow('navigator:browser')).getBrowser();
        //document.getElementById('content');
    },

    browserLoadPage: function(e, url) {
        var browser = connexions.getBrowser();

        // Open the url in a new tab
        connexions.openTab(url);
    },

    openWindow: function(xul, title, options, url) {
        return window.openDialog(xul, title, options, url);
    },

    openTab: function(url) {
        var browser = connexions.getBrowser();
        var tab     = browser.addTab(url);
        browser.selectedTab = tab;

        return tab;
    },

    openPopupWindow: function(url, title) {
        var xul     = 'chrome://browser/content/browser.xul';
        var width   = 800;
        var height  = 500;
        var options = 'chrome,titlebar,centerscreen,dialog=no,'
                    +   'width='+ width
                    +   'height='+ height;

        return openWindow(xul, title, options, url);
    },

    openTagPage: function() {
        var selection   = connexions.getSelectedText();
        var description = (selection.str ? selection.str : '');
        var browser     = connexions.getBrowser();
        var webNav      = browser.webNavigation;
        var location    = (webNav.currentURI
                            ? webNav.currentURI.spec
                            : gURLBar.value);
        var name        = (webNav.document.title
                            ? webNav.document.title
                            : location);

        connexions.log("openTagPage(): description[ %s ]", description);
        connexions.log("openTagPage(): location[ %s ]", location);
        connexions.log("openTagPage(): name[ %s ]", name);

        connexions.openPopupWindow(
                connexions.url('post'
                               + '?url='+ encodeURIComponent(location)
                               + '&name='+ encodeURIComponent(name)
                               + '&description='
                                    + encodeURIComponent(description)
                               + '&closeAction=close'),
                'Bookmark a Page');
    },

    getSelectedText: function(maxLen) {
        maxLen = maxLen || 4096;

        var curWindow   = document.commandDispatcher.focusedWindow;
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

/*
Connexions_log('Connexions: types: number[ '+ connexions.type(5) +' ]');
Connexions_log('Connexions: types: number[ '+ connexions.type(5.5) +' ]');
Connexions_log('Connexions: types: bool  [ '+ connexions.type(true) +' ]');
Connexions_log('Connexions: types: bool  [ '+ connexions.type(false) +' ]');
Connexions_log('Connexions: types: array [ '+ connexions.type([1,2]) +' ]');
Connexions_log('Connexions: types: regexp[ '+ connexions.type(/abc/) +' ]');
Connexions_log('Connexions: types: object[ '+ connexions.type({a:1}) +' ]');
Connexions_log('Connexions: types: null  [ '+ connexions.type(null)  +' ]');
Connexions_log('Connexions: types: string[ '+ connexions.type("string") +' ]');
// */
Connexions_log('Connexions: types: object[ '+ connexions.obj2str({a:1}) +' ]');

window.addEventListener("load",   connexions.init,    false);
window.addEventListener("unload", connexions.destroy, false);
