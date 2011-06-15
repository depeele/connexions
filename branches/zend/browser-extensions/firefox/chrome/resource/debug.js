/** @file
 *
 *  General console logging.
 *
 */
let EXPORTED_SYMBOLS    = ["Connexions_log", "cDebug"];

const CC                = Components.classes;
const CI                = Components.interfaces;
const CR                = Components.results;
const CU                = Components.utils;

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
            var console = CC['@mozilla.org/consoleservice;1']
                            .getService(CI.nsIConsoleService);

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

function Debug()
{
    this.init();
}

Debug.prototype = {
    initialized:    false,

    init: function() {
        if (this.initialized === true)  return;

        this.initialized = true;
    },

    log: function(fmt) {
        var msg = fmt;
        for (var idex = 1; idex < arguments.length; idex++)
        {
            //msg = msg.replace(/%s/, this.obj2str(arguments[idex]));
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

        var type    = this.type(obj);
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
                        str = this.obj2str(obj[prop], depth+1);
                    }
                    else
                    {
                        str = indent
                            +'"'+ prop +'": '
                            + this.obj2str(obj[prop], depth+1);
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
    }
};

var cDebug  = new Debug();

//Connexions_log('Connexions_log: available...');
cDebug.log('cDebug.log: [ %s ]', 'available');
