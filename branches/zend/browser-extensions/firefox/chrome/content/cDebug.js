var CONNEXIONS_DEBUG_MESSAGE = 1;
var CONNEXIONS_LOG_MESSAGE   = 2;

function connexionsLogToConsole(msg, srcName, srcLine, lineNumber, 
                                columnNumber, flags, category)
{
    var consoleService  =
            Components.classes["@mozilla.org/consoleservice;1"]
                        .getService(Components.interfaces.nsIConsoleService);
    var scriptError     =
            Components.classes["@mozilla.org/scripterror;1"]
                        .createInstance(Components.interfaces.nsIScriptError);

    scriptError.init(msg, srcName, srcLine, lineNumber, 
                     columnNumber, flags, category);
    consoleService.logMessage(scriptError);
}

var cDebug = {
    _initialized:   false,
    _dbgService:    null,
    _init:          function() {
        try {
            this._dbgService =
                Components.classes[ "@mozilla.org/ybookmarks-debug-service;1" ];
            this._dbgService =
                this._dbgService
                        .getService( Components.interfaces.nsIYDebugService );
            this._consoleService =
                Components.classes[ "@mozilla.org/consoleservice;1" ];
            this._consoleService =
                this._consoleService
                        .getService( Components.interfaces.nsIConsoleService );
            this._initialized = true;
        }
        catch( e ) {
        }
    },

    Timer:  function( name ) {
        var t1, t2, delta, total = 0, count = 0;
        this.start = function() {
            ++count;
            t1 = new Date();
            return this;
        };
        this.end   = function() {
            t2    =  new Date();
            delta =  t2 - t1;
            total += delta; return this;
        };
        this.print    = function() { 
            if (count > 1) {
                /*
                repl.print("Timer (" + name + "): " + delta + " ms"
                           + "; called " + count + " times"
                           + "; total: " + total + " ms"
                           + "; avg time per call: " + (total / count) + " ms"
                ); 
                */
                cDebug.print("Timer (" + name + "): "+ delta +" ms"
                             + "; called "+ count +" times"
                             + "; total: "+ total +" ms"
                             + "; avg time per call: "+ (total / count) +" ms"
                ); 
            } else {
                /*
                repl.print("Timer (" + name + "): " + delta + " ms");
                */
                cDebug.print("Timer (" + name + "): " + delta + " ms");
            }
        };
    },

    timedFunc: function( func, desc ) {
        if (typeof arguments.callee.timer == "undefined") {
            arguments.callee.timer = {};
        }
        var id = desc || func.desc || func.name;
        if (typeof arguments.callee.timer[id] == "undefined") {
            arguments.callee.timer[id] = new cDebug.Timer(id);
        }
        var timer = arguments.callee.timer[id];

        return function() {
            timer.start();
            var ret = func.apply(null, arguments);
            timer.end();
            timer.print();
            return ret;
        };
    },

    on: function( refresh ) {
        if( !this._initialized ) {
            this._init()
        }
        if( this._initialized ) {
            if( refresh == null ) {
                refresh = false;
            }
            return this._dbgService.on( refresh );
        }
        return false;
    },
     
    print: function( message, type ) {
        if( !this._initialized ) {
            this._init()
        }
        if( this._initialized ) {
            var stackFrame = Components.stack.caller;
            var filename = stackFrame.filename;
/*          if (filename.indexOf("ybSidebarOverlay.xml") < 0 && filename.indexOf("Debug") < 0) return;*/
            if( type == null ) {
                type = CONNEXIONS_DEBUG_MESSAGE;
            }
            if( type == CONNEXIONS_LOG_MESSAGE ) {
                this._dbgService.printLog( message );
            }
            else {
                if( this._dbgService.on()) {
                    // var stackFrame = Components.stack.caller;
                    // var filename = stackFrame.filename;
                    // if (filename == "ybSidebarOverlay.xml") {
                    var lineNumber = stackFrame.lineNumber;
                    var columnNumber = stackFrame.columnNumber;
                    connexionsLogToConsole(message, filename, null,
                                           lineNumber, columnNumber,
                                           Components.interfaces
                                                .nsIScriptError.warningFlag,
                                           1);
                    this._dbgService.printDebug( message );
                    // }
                }
            }
        }
    },

    printStack: function(message) {
        var consoleService =
                Components.classes["@mozilla.org/consoleservice;1"]
                    .getService(Components.interfaces.nsIConsoleService);

        var stackFrame = Components.stack.caller;
        var arr = [message];
        var msg;
        while (stackFrame) {
            var filename = stackFrame.filename;
            if (filename == null) break;
            var lineNumber = stackFrame.lineNumber;
            arr.push("        " + filename + ":" + lineNumber);
            stackFrame = stackFrame.caller;
        }

        msg = arr.join("\n");
        stackFrame = Components.stack.caller;
        lineNumber = stackFrame.lineNumber;
        columnNumber = stackFrame.columnNumber;
        filename = stackFrame.filename;
        connexionsLogToConsole(msg, filename, null,
                               lineNumber, columnNumber,
                               Components.interfaces.nsIScriptError.errorFlag,
                               1);
        this._dbgService.printLog( msg );
     },

     assert: function( boolValue, msg ) {
        if (boolValue) return true; 
        else {
            msg = "Assertion failed: " + msg;
            var stackFrame = Components.stack.caller.caller;
            var filename = stackFrame.filename;
            var lineNumber = stackFrame.lineNumber;
            var columnNumber = stackFrame.columnNumber;
                        
            // cDebug.print(msg);
            connexionsLogToConsole(msg, filename, null,
                                   lineNumber, columnNumber,
                                   Components.interfaces
                                        .nsIScriptError.errorFlag,
                                   1);
            return false;
        }
     },
     
    /* below to be used only for debugging*/
    printOutArcs: function( datasource, resource ) {

        cDebug.print( " *********************" );
        var properties = datasource.ArcLabelsOut( resource );

        while ( properties.hasMoreElements() ) {
            var s = properties.getNext();
            s.QueryInterface( Components.interfaces.nsIRDFResource );
            var target = datasource.GetTarget ( resource, s, true );
            try {
                target.QueryInterface(Components.interfaces.nsIRDFLiteral);
                cDebug.print("Literal:"+ s.Value + " => "+ target.Value);
            } catch (e) {
                try {
                    target.QueryInterface(Components.interfaces.nsIRDFResource);
                    cDebug.print(resource.Value
                                 + ": Resource => "+ s.Value
                                 + " => " + target.Value );
                } catch (e) {

                    try {
                        target.QueryInterface(Components.interfaces.nsIRDFDate);
                        cDebug.print("Date: "
                                      + s.Value
                                      + " => " + target.Value );
                    } catch (e) {
                    }
                }
            }
        }
    },
     
    printObject: function(aObject, message) {
        var str = "";
        if (!message) message = "";
        str += message + ": ";         
        if (aObject == null) {
            str += "(null)";
            cDebug.print(str);
            return;
        }
        str += "{\n";
        for (var prop in aObject) {
            if (aObject.hasOwnProperty(prop)) {
                str += "        [" + prop + "]: "
                    +  "\"" + aObject[prop] + "\"\n";
            }
        }
        str += "}\n";
        cDebug.print(str);
    }
};
