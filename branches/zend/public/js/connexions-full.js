/*
    http://www.JSON.org/json2.js
    2010-03-20

    Public Domain.

    NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.

    See http://www.JSON.org/js.html


    This code should be minified before deployment.
    See http://javascript.crockford.com/jsmin.html

    USE YOUR OWN COPY. IT IS EXTREMELY UNWISE TO LOAD CODE FROM SERVERS YOU DO
    NOT CONTROL.


    This file creates a global JSON object containing two methods: stringify
    and parse.

        JSON.stringify(value, replacer, space)
            value       any JavaScript value, usually an object or array.

            replacer    an optional parameter that determines how object
                        values are stringified for objects. It can be a
                        function or an array of strings.

            space       an optional parameter that specifies the indentation
                        of nested structures. If it is omitted, the text will
                        be packed without extra whitespace. If it is a number,
                        it will specify the number of spaces to indent at each
                        level. If it is a string (such as '\t' or '&nbsp;'),
                        it contains the characters used to indent at each level.

            This method produces a JSON text from a JavaScript value.

            When an object value is found, if the object contains a toJSON
            method, its toJSON method will be called and the result will be
            stringified. A toJSON method does not serialize: it returns the
            value represented by the name/value pair that should be serialized,
            or undefined if nothing should be serialized. The toJSON method
            will be passed the key associated with the value, and this will be
            bound to the value

            For example, this would serialize Dates as ISO strings.

                Date.prototype.toJSON = function (key) {
                    function f(n) {
                        // Format integers to have at least two digits.
                        return n < 10 ? '0' + n : n;
                    }

                    return this.getUTCFullYear()   + '-' +
                         f(this.getUTCMonth() + 1) + '-' +
                         f(this.getUTCDate())      + 'T' +
                         f(this.getUTCHours())     + ':' +
                         f(this.getUTCMinutes())   + ':' +
                         f(this.getUTCSeconds())   + 'Z';
                };

            You can provide an optional replacer method. It will be passed the
            key and value of each member, with this bound to the containing
            object. The value that is returned from your method will be
            serialized. If your method returns undefined, then the member will
            be excluded from the serialization.

            If the replacer parameter is an array of strings, then it will be
            used to select the members to be serialized. It filters the results
            such that only members with keys listed in the replacer array are
            stringified.

            Values that do not have JSON representations, such as undefined or
            functions, will not be serialized. Such values in objects will be
            dropped; in arrays they will be replaced with null. You can use
            a replacer function to replace those with JSON values.
            JSON.stringify(undefined) returns undefined.

            The optional space parameter produces a stringification of the
            value that is filled with line breaks and indentation to make it
            easier to read.

            If the space parameter is a non-empty string, then that string will
            be used for indentation. If the space parameter is a number, then
            the indentation will be that many spaces.

            Example:

            text = JSON.stringify(['e', {pluribus: 'unum'}]);
            // text is '["e",{"pluribus":"unum"}]'


            text = JSON.stringify(['e', {pluribus: 'unum'}], null, '\t');
            // text is '[\n\t"e",\n\t{\n\t\t"pluribus": "unum"\n\t}\n]'

            text = JSON.stringify([new Date()], function (key, value) {
                return this[key] instanceof Date ?
                    'Date(' + this[key] + ')' : value;
            });
            // text is '["Date(---current time---)"]'


        JSON.parse(text, reviver)
            This method parses a JSON text to produce an object or array.
            It can throw a SyntaxError exception.

            The optional reviver parameter is a function that can filter and
            transform the results. It receives each of the keys and values,
            and its return value is used instead of the original value.
            If it returns what it received, then the structure is not modified.
            If it returns undefined then the member is deleted.

            Example:

            // Parse the text. Values that look like ISO date strings will
            // be converted to Date objects.

            myData = JSON.parse(text, function (key, value) {
                var a;
                if (typeof value === 'string') {
                    a =
/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2}(?:\.\d*)?)Z$/.exec(value);
                    if (a) {
                        return new Date(Date.UTC(+a[1], +a[2] - 1, +a[3], +a[4],
                            +a[5], +a[6]));
                    }
                }
                return value;
            });

            myData = JSON.parse('["Date(09/09/2001)"]', function (key, value) {
                var d;
                if (typeof value === 'string' &&
                        value.slice(0, 5) === 'Date(' &&
                        value.slice(-1) === ')') {
                    d = new Date(value.slice(5, -1));
                    if (d) {
                        return d;
                    }
                }
                return value;
            });


    This is a reference implementation. You are free to copy, modify, or
    redistribute.
*/

/*jslint evil: true, strict: false */

/*members "", "\b", "\t", "\n", "\f", "\r", "\"", JSON, "\\", apply,
    call, charCodeAt, getUTCDate, getUTCFullYear, getUTCHours,
    getUTCMinutes, getUTCMonth, getUTCSeconds, hasOwnProperty, join,
    lastIndex, length, parse, prototype, push, replace, slice, stringify,
    test, toJSON, toString, valueOf
*/


// Create a JSON object only if one does not already exist. We create the
// methods in a closure to avoid creating global variables.

if (!this.JSON) {
    this.JSON = {};
}

(function () {

    function f(n) {
        // Format integers to have at least two digits.
        return n < 10 ? '0' + n : n;
    }

    if (typeof Date.prototype.toJSON !== 'function') {

        Date.prototype.toJSON = function (key) {

            return isFinite(this.valueOf()) ?
                   this.getUTCFullYear()   + '-' +
                 f(this.getUTCMonth() + 1) + '-' +
                 f(this.getUTCDate())      + 'T' +
                 f(this.getUTCHours())     + ':' +
                 f(this.getUTCMinutes())   + ':' +
                 f(this.getUTCSeconds())   + 'Z' : null;
        };

        String.prototype.toJSON =
        Number.prototype.toJSON =
        Boolean.prototype.toJSON = function (key) {
            return this.valueOf();
        };
    }

    var cx = /[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
        escapable = /[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
        gap,
        indent,
        meta = {    // table of character substitutions
            '\b': '\\b',
            '\t': '\\t',
            '\n': '\\n',
            '\f': '\\f',
            '\r': '\\r',
            '"' : '\\"',
            '\\': '\\\\'
        },
        rep;


    function quote(string) {

// If the string contains no control characters, no quote characters, and no
// backslash characters, then we can safely slap some quotes around it.
// Otherwise we must also replace the offending characters with safe escape
// sequences.

        escapable.lastIndex = 0;
        return escapable.test(string) ?
            '"' + string.replace(escapable, function (a) {
                var c = meta[a];
                return typeof c === 'string' ? c :
                    '\\u' + ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
            }) + '"' :
            '"' + string + '"';
    }


    function str(key, holder) {

// Produce a string from holder[key].

        var i,          // The loop counter.
            k,          // The member key.
            v,          // The member value.
            length,
            mind = gap,
            partial,
            value = holder[key];

// If the value has a toJSON method, call it to obtain a replacement value.

        if (value && typeof value === 'object' &&
                typeof value.toJSON === 'function') {
            value = value.toJSON(key);
        }

// If we were called with a replacer function, then call the replacer to
// obtain a replacement value.

        if (typeof rep === 'function') {
            value = rep.call(holder, key, value);
        }

// What happens next depends on the value's type.

        switch (typeof value) {
        case 'string':
            return quote(value);

        case 'number':

// JSON numbers must be finite. Encode non-finite numbers as null.

            return isFinite(value) ? String(value) : 'null';

        case 'boolean':
        case 'null':

// If the value is a boolean or null, convert it to a string. Note:
// typeof null does not produce 'null'. The case is included here in
// the remote chance that this gets fixed someday.

            return String(value);

// If the type is 'object', we might be dealing with an object or an array or
// null.

        case 'object':

// Due to a specification blunder in ECMAScript, typeof null is 'object',
// so watch out for that case.

            if (!value) {
                return 'null';
            }

// Make an array to hold the partial results of stringifying this object value.

            gap += indent;
            partial = [];

// Is the value an array?

            if (Object.prototype.toString.apply(value) === '[object Array]') {

// The value is an array. Stringify every element. Use null as a placeholder
// for non-JSON values.

                length = value.length;
                for (i = 0; i < length; i += 1) {
                    partial[i] = str(i, value) || 'null';
                }

// Join all of the elements together, separated with commas, and wrap them in
// brackets.

                v = partial.length === 0 ? '[]' :
                    gap ? '[\n' + gap +
                            partial.join(',\n' + gap) + '\n' +
                                mind + ']' :
                          '[' + partial.join(',') + ']';
                gap = mind;
                return v;
            }

// If the replacer is an array, use it to select the members to be stringified.

            if (rep && typeof rep === 'object') {
                length = rep.length;
                for (i = 0; i < length; i += 1) {
                    k = rep[i];
                    if (typeof k === 'string') {
                        v = str(k, value);
                        if (v) {
                            partial.push(quote(k) + (gap ? ': ' : ':') + v);
                        }
                    }
                }
            } else {

// Otherwise, iterate through all of the keys in the object.

                for (k in value) {
                    if (Object.hasOwnProperty.call(value, k)) {
                        v = str(k, value);
                        if (v) {
                            partial.push(quote(k) + (gap ? ': ' : ':') + v);
                        }
                    }
                }
            }

// Join all of the member texts together, separated with commas,
// and wrap them in braces.

            v = partial.length === 0 ? '{}' :
                gap ? '{\n' + gap + partial.join(',\n' + gap) + '\n' +
                        mind + '}' : '{' + partial.join(',') + '}';
            gap = mind;
            return v;
        }
    }

// If the JSON object does not yet have a stringify method, give it one.

    if (typeof JSON.stringify !== 'function') {
        JSON.stringify = function (value, replacer, space) {

// The stringify method takes a value and an optional replacer, and an optional
// space parameter, and returns a JSON text. The replacer can be a function
// that can replace values, or an array of strings that will select the keys.
// A default replacer method can be provided. Use of the space parameter can
// produce text that is more easily readable.

            var i;
            gap = '';
            indent = '';

// If the space parameter is a number, make an indent string containing that
// many spaces.

            if (typeof space === 'number') {
                for (i = 0; i < space; i += 1) {
                    indent += ' ';
                }

// If the space parameter is a string, it will be used as the indent string.

            } else if (typeof space === 'string') {
                indent = space;
            }

// If there is a replacer, it must be a function or an array.
// Otherwise, throw an error.

            rep = replacer;
            if (replacer && typeof replacer !== 'function' &&
                    (typeof replacer !== 'object' ||
                     typeof replacer.length !== 'number')) {
                throw new Error('JSON.stringify');
            }

// Make a fake root object containing our value under the key of ''.
// Return the result of stringifying the value.

            return str('', {'': value});
        };
    }


// If the JSON object does not yet have a parse method, give it one.

    if (typeof JSON.parse !== 'function') {
        JSON.parse = function (text, reviver) {

// The parse method takes a text and an optional reviver function, and returns
// a JavaScript value if the text is a valid JSON text.

            var j;

            function walk(holder, key) {

// The walk method is used to recursively walk the resulting structure so
// that modifications can be made.

                var k, v, value = holder[key];
                if (value && typeof value === 'object') {
                    for (k in value) {
                        if (Object.hasOwnProperty.call(value, k)) {
                            v = walk(value, k);
                            if (v !== undefined) {
                                value[k] = v;
                            } else {
                                delete value[k];
                            }
                        }
                    }
                }
                return reviver.call(holder, key, value);
            }


// Parsing happens in four stages. In the first stage, we replace certain
// Unicode characters with escape sequences. JavaScript handles many characters
// incorrectly, either silently deleting them, or treating them as line endings.

            text = String(text);
            cx.lastIndex = 0;
            if (cx.test(text)) {
                text = text.replace(cx, function (a) {
                    return '\\u' +
                        ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
                });
            }

// In the second stage, we run the text against regular expressions that look
// for non-JSON patterns. We are especially concerned with '()' and 'new'
// because they can cause invocation, and '=' because it can cause mutation.
// But just to be safe, we want to reject all unexpected forms.

// We split the second stage into 4 regexp operations in order to work around
// crippling inefficiencies in IE's and Safari's regexp engines. First we
// replace the JSON backslash pairs with '@' (a non-JSON character). Second, we
// replace all simple value tokens with ']' characters. Third, we delete all
// open brackets that follow a colon or comma or that begin the text. Finally,
// we look to see that the remaining characters are only whitespace or ']' or
// ',' or ':' or '{' or '}'. If that is so, then the text is safe for eval.

            if (/^[\],:{}\s]*$/.
test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, '@').
replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').
replace(/(?:^|:|,)(?:\s*\[)+/g, ''))) {

// In the third stage we use the eval function to compile the text into a
// JavaScript structure. The '{' operator is subject to a syntactic ambiguity
// in JavaScript: it can begin a block or an object literal. We wrap the text
// in parens to eliminate the ambiguity.

                j = eval('(' + text + ')');

// In the optional fourth stage, we recursively walk the new structure, passing
// each name/value pair to a reviver function for possible transformation.

                return typeof reviver === 'function' ?
                    walk({'': j}, '') : j;
            }

// If the text is not JSON parseable, then a SyntaxError is thrown.

            throw new SyntaxError('JSON.parse');
        };
    }
}());
/** @file
 *
 *  Provide global Javascript functionality for Connexions.
 *
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false, document:false, setTimeout:false */
(function($) {
    function init_log()
    {
        $.log = function(fmt) {
            /* :XXX: IE9 does NOT report window.console.log as a function but
             *       rather an object...
             */
            if ( (window.console     !== undefined) &&
                 (window.console.log !== undefined) )
            {
                var msg = fmt;
                for (var idex = 1; idex < arguments.length; idex++)
                {
                    msg = msg.replace(/%s/, arguments[idex]);
                }
                window.console.log(msg);
            }
        };

        $.log("Logging enabled");
    }

    /* :XXX: IE9 does NOT report window.console.log as a function but
     *       rather an object...
     */
    if ( (window.console     === undefined) ||
         (window.console.log === undefined) )
    {
        $(document).ready(init_log);
    }
    else
    {
        init_log();
    }

    /* IE6 Background Image Fix
     *  Thanks to http://www.visualjquery.com/rating/rating_redux.html
     */
    if ($.browser.msie)
    {
        try { document.execCommand("BackgroundImageCache", false, true); }
        catch(e) { }
    }

    /*************************************************************************
     * Simple utilities
     *
     */
    $.spawn = function(callback, timeout) {
        setTimeout( callback, (timeout === undefined ? 0 : timeout) );
    };

    /*************************************************************************
     * JSON-RPC helper.
     *
     */

    var _jsonRpcId  = 0;

    /** @brief  Perform a JSON-RPC call.
     *  @param  def     The JSON-RPC description object:
     *                      { version:, target:, transport: }
     *  @param  method  The desired RPC method string;
     *  @param  params  An object containing the RPC parameters to pass;
     *  @param  options $.ajax-compatible options object;
     */
    $.jsonRpc = function(def, method, params, options) {
        var rpc = {
            version:    def.version,
            id:         _jsonRpcId++,
            method:     method,
            params:     params
        };

        options = $.extend({}, options, {
                            url:        def.target,
                            type:       def.transport,
                            dataType:   'json',
                            data:       JSON.stringify(rpc)
                           });

        $.ajax(options);
    };

    /*************************************************************************
     * Simple password validation.
     *
     */

    /** @brief  Given two ui.input widgets and the one which cause the
     *          validation routine to trigger, area, check if the passwords are
     *          equivalent and more than 1 and mark BOTH passwords either valid
     *          or invalid.
     *  @param  $active     The ui.input widget (either $pass1 or $pass2) that
     *                      triggered the validation check.
     *  @param  $pass1      The ui.input widget representing password #1.
     *  @param  $pass2      The ui.input widget representing password #2.
     *
     *  @return
     */
    $.validatePasswords = function($active, $pass1, $pass2) {
        var pass1       = $pass1.val();
        var pass2       = $pass2.val();
        var res         = true;

        if ((pass1.length < 1) || (pass2.length < 1))
        {
            // Neither valid nor ivnalid
            res = undefined;

            // Also clear the validation status for the other field
            if ($active[0] === $pass1[0])
            {
                $pass2.input('valid');  //, undefined);
            }
            else
            {
                $pass1.input('valid');  //, undefined);
            }
        }
        else if (pass1 !== pass2)
        {
            // Invalid -- with message
            res = 'Passwords do not match.';

            // Only report errors on 'password2'
            if ($active[0] === $pass1[0])
            {
                $pass2.input('valid', res);
                res = undefined;
            }
            else
            {
                /* But we still  want to clear the validation status for
                 * password1
                 */
                $pass1.input('valid');  //, undefined);
            }
        }
        else
        {
            // Also report success for the other field.
            if ($active[0] === $pass1[0])
            {
                $pass2.input('valid', true);
            }
            else
            {
                $pass1.input('valid', true);
            }
        }

        return res;
    };

    /*************************************************************************
     * String extensions
     *
     */

    /** @brief  Upper-case the first character of the given string.
     *  @param  str     The string;
     *
     *  @return str with the first character upper-case.
     */
    $.ucFirst = function(str) {
        if ($.type(str) !== 'string')   return str;

        return str[0].toUpperCase() + str.substr(1);
    };

    /** @brief  Left pad the provided string to the specified number of
     *          characters using the provided padding character.
     *  @param  str         The string to pad;
     *  @param  numChars    The total number of charcters desired [ 2 ];
     *  @param  padChar     The desired padding character         [ '0' ];
     *
     *  @return A new, padded string.
     */
    $.padString = function(str, numChars, padChar) {
        numChars = numChars || 2;
        padChar  = padChar  || '0';

        // Ensure 'str' is actually a string
        str = ''+ str;

        while (str.length < numChars)
        {
            str = padChar + str;
        }

        return str;
    };

    /** @brief  Given a string, convert HTML special characters to entities.
     *  @param  str     The original string.
     *
     *  :NOTE: This should be functionally similar to PHP:htmlspecialchars()
     *
     *  @return The escaped string.
     */
    $.esc = function(str) {
        return $.htmlspecialchars(str);

        /*
        return str.replace(/&(?!amp;)/g, '&amp;')
                  .replace(/</g,         '&lt;')
                  .replace(/>/g,         '&gt;');
        // */
    };

    /** @brief  Generate a "summary" of the provided text.  This simply
     *          shortens the text to the last full word before the 'maxChars'th
     *          character.
     *  @param  text        The text to "summarize";
     *  @param  maxChars    The maximum number of characters [ 40 ];
     *
     *  :NOTE: This should be functionally similar to
     *          PHP:Connexions::getSummary()
     *
     *  @return The summary string.
     */
    $.summarize = function(text, maxChars) {
        if (maxChars === undefined) { maxChars = 40; }

        // Decode any HTML entities
        var summary = $.html_entity_decode(text, 'ENT_QUOTES');
        if (summary.length > maxChars)
        {
            // Shorten to no more than 'maxChars' characters
            summary = summary.substr(0, maxChars);

            // Shorten to the last remaining white-space
            summary = summary.substr(0, summary.lastIndexOf(' '));

            // Trim any white-space or punctuation from the end
            summary = summary.replace(/[\s\.\!\?:;\,\-]+$/, '');

            // Append '...' to indicate we've truncated.
            summary += '...';
        }

        summary = $.htmlentities(summary, 'ENT_QUOTES');

        return summary;
    };

    /**********************************
     * Borrowed from php.js
     *  http://phpjs.org/
     *
     */
    $.get_html_translation_table = function (table, quote_style) {
        // Returns the internal translation table used by htmlspecialchars and
        // htmlentities  
        // 
        // version: 1103.1210
        // discuss at: http://phpjs.org/functions/get_html_translation_table
        // +   original by: Philip Peterson
        // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +   bugfixed by: noname
        // +   bugfixed by: Alex
        // +   bugfixed by: Marco
        // +   bugfixed by: madipta
        // +   improved by: KELAN
        // +   improved by: Brett Zamir (http://brett-zamir.me)
        // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
        // +      input by: Frank Forte
        // +   bugfixed by: T.Wild
        // +      input by: Ratheous
        // %          note: It has been decided that we're not going to add
        //                  global dependencies to php.js, meaning the
        //                  constants are not real constants, but strings
        //                  instead. Integers are also supported if someone
        // %          note: chooses to create the constants themselves.
        // *     example 1: get_html_translation_table('HTML_SPECIALCHARS');
        // *     returns 1: {'"': '&quot;', '&': '&amp;', '<': '&lt;',
        //                   '>': '&gt;'}
        var entities = {},
            hash_map = {},
            decimal = 0,
            symbol = '';
        var constMappingTable = {},
            constMappingQuoteStyle = {};
        var useTable = {},
            useQuoteStyle = {};
     
        // Translate arguments
        constMappingTable[0] = 'HTML_SPECIALCHARS';
        constMappingTable[1] = 'HTML_ENTITIES';
        constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
        constMappingQuoteStyle[2] = 'ENT_COMPAT';
        constMappingQuoteStyle[3] = 'ENT_QUOTES';
     
        useTable      = (!isNaN(table)
                            ? constMappingTable[table]
                            : (table
                                ? table.toUpperCase()
                                : 'HTML_SPECIALCHARS'));
        useQuoteStyle = (!isNaN(quote_style)
                            ? constMappingQuoteStyle[quote_style]
                            : (quote_style
                                ? quote_style.toUpperCase()
                                : 'ENT_COMPAT'));
     
        if ((useTable !== 'HTML_SPECIALCHARS') &&
            (useTable !== 'HTML_ENTITIES'))
        {
            throw new Error("Table: " + useTable + ' not supported');
            // return false;
        }
     
        entities['38'] = '&amp;';
        if (useTable === 'HTML_ENTITIES')
        {
            entities['160'] = '&nbsp;';
            entities['161'] = '&iexcl;';
            entities['162'] = '&cent;';
            entities['163'] = '&pound;';
            entities['164'] = '&curren;';
            entities['165'] = '&yen;';
            entities['166'] = '&brvbar;';
            entities['167'] = '&sect;';
            entities['168'] = '&uml;';
            entities['169'] = '&copy;';
            entities['170'] = '&ordf;';
            entities['171'] = '&laquo;';
            entities['172'] = '&not;';
            entities['173'] = '&shy;';
            entities['174'] = '&reg;';
            entities['175'] = '&macr;';
            entities['176'] = '&deg;';
            entities['177'] = '&plusmn;';
            entities['178'] = '&sup2;';
            entities['179'] = '&sup3;';
            entities['180'] = '&acute;';
            entities['181'] = '&micro;';
            entities['182'] = '&para;';
            entities['183'] = '&middot;';
            entities['184'] = '&cedil;';
            entities['185'] = '&sup1;';
            entities['186'] = '&ordm;';
            entities['187'] = '&raquo;';
            entities['188'] = '&frac14;';
            entities['189'] = '&frac12;';
            entities['190'] = '&frac34;';
            entities['191'] = '&iquest;';
            entities['192'] = '&Agrave;';
            entities['193'] = '&Aacute;';
            entities['194'] = '&Acirc;';
            entities['195'] = '&Atilde;';
            entities['196'] = '&Auml;';
            entities['197'] = '&Aring;';
            entities['198'] = '&AElig;';
            entities['199'] = '&Ccedil;';
            entities['200'] = '&Egrave;';
            entities['201'] = '&Eacute;';
            entities['202'] = '&Ecirc;';
            entities['203'] = '&Euml;';
            entities['204'] = '&Igrave;';
            entities['205'] = '&Iacute;';
            entities['206'] = '&Icirc;';
            entities['207'] = '&Iuml;';
            entities['208'] = '&ETH;';
            entities['209'] = '&Ntilde;';
            entities['210'] = '&Ograve;';
            entities['211'] = '&Oacute;';
            entities['212'] = '&Ocirc;';
            entities['213'] = '&Otilde;';
            entities['214'] = '&Ouml;';
            entities['215'] = '&times;';
            entities['216'] = '&Oslash;';
            entities['217'] = '&Ugrave;';
            entities['218'] = '&Uacute;';
            entities['219'] = '&Ucirc;';
            entities['220'] = '&Uuml;';
            entities['221'] = '&Yacute;';
            entities['222'] = '&THORN;';
            entities['223'] = '&szlig;';
            entities['224'] = '&agrave;';
            entities['225'] = '&aacute;';
            entities['226'] = '&acirc;';
            entities['227'] = '&atilde;';
            entities['228'] = '&auml;';
            entities['229'] = '&aring;';
            entities['230'] = '&aelig;';
            entities['231'] = '&ccedil;';
            entities['232'] = '&egrave;';
            entities['233'] = '&eacute;';
            entities['234'] = '&ecirc;';
            entities['235'] = '&euml;';
            entities['236'] = '&igrave;';
            entities['237'] = '&iacute;';
            entities['238'] = '&icirc;';
            entities['239'] = '&iuml;';
            entities['240'] = '&eth;';
            entities['241'] = '&ntilde;';
            entities['242'] = '&ograve;';
            entities['243'] = '&oacute;';
            entities['244'] = '&ocirc;';
            entities['245'] = '&otilde;';
            entities['246'] = '&ouml;';
            entities['247'] = '&divide;';
            entities['248'] = '&oslash;';
            entities['249'] = '&ugrave;';
            entities['250'] = '&uacute;';
            entities['251'] = '&ucirc;';
            entities['252'] = '&uuml;';
            entities['253'] = '&yacute;';
            entities['254'] = '&thorn;';
            entities['255'] = '&yuml;';
        }
     
        if (useQuoteStyle !== 'ENT_NOQUOTES')
        {
            entities['34'] = '&quot;';
        }
        if (useQuoteStyle === 'ENT_QUOTES')
        {
            entities['39'] = '&#39;';
        }
        entities['60'] = '&lt;';
        entities['62'] = '&gt;';
     
     
        // ascii decimals to real symbols
        for (decimal in entities)
        {
            symbol = String.fromCharCode(decimal);
            hash_map[symbol] = entities[decimal];
        }
     
        return hash_map;
    };

    $.htmlentities = function(string, quote_style) {
        // Convert all applicable characters to HTML entities  
        // 
        // version: 1103.1210
        // discuss at: http://phpjs.org/functions/htmlentities
        // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +   improved by: nobbler
        // +    tweaked by: Jack
        // +   bugfixed by: Onno Marsman
        // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
        // +      input by: Ratheous
        // -    depends on: get_html_translation_table
        // *     example 1: htmlentities('Kevin & van Zonneveld');
        // *     returns 1: 'Kevin &amp; van Zonneveld'
        // *     example 2: htmlentities("foo'bar","ENT_QUOTES");
        // *     returns 2: 'foo&#039;bar'
        var hash_map = {},
            symbol = '',
            tmp_str = '',
            entity = '';
        tmp_str = string.toString();
     
        if (false ===
               (hash_map = $.get_html_translation_table('HTML_ENTITIES',
                                                        quote_style)))
        {
            return false;
        }

        hash_map["'"] = '&#039;';
        for (symbol in hash_map)
        {
            entity  = hash_map[symbol];
            tmp_str = tmp_str.split(symbol).join(entity);
        }
     
        return tmp_str;
    };

    $.html_entity_decode = function(string, quote_style) {
        // Convert all HTML entities to their applicable characters  
        // 
        // version: 1103.1210
        // discuss at: http://phpjs.org/functions/html_entity_decode
        // +   original by: john (http://www.jd-tech.net)
        // +      input by: ger
        // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +   bugfixed by: Onno Marsman
        // +   improved by: marc andreu
        // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +      input by: Ratheous
        // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
        // +      input by: Nick Kolosov (http://sammy.ru)
        // +   bugfixed by: Fox
        // -    depends on: get_html_translation_table
        // *     example 1: html_entity_decode('Kevin &amp; van Zonneveld');
        // *     returns 1: 'Kevin & van Zonneveld'
        // *     example 2: html_entity_decode('&amp;lt;');
        // *     returns 2: '&lt;'
        var hash_map = {},
            symbol = '',
            tmp_str = '',
            entity = '';
        tmp_str = string.toString();
     
        if (false ===
               (hash_map = $.get_html_translation_table('HTML_ENTITIES',
                                                        quote_style)))
        {
            return false;
        }
     
        // fix &amp; problem
        // http://phpjs.org/functions/get_html_translation_table:416#comment_97660
        delete(hash_map['&']);
        hash_map['&'] = '&amp;';
     
        for (symbol in hash_map)
        {
            entity = hash_map[symbol];
            tmp_str = tmp_str.split(entity).join(symbol);
        }
        tmp_str = tmp_str.split('&#039;').join("'");
     
        return tmp_str;
    };

    $.htmlspecialchars = function(string, quote_style, charset, double_encode){
        // Convert special characters to HTML entities  
        // 
        // version: 1103.1210
        // discuss at: http://phpjs.org/functions/htmlspecialchars
        // +   original by: Mirek Slugen
        // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +   bugfixed by: Nathan
        // +   bugfixed by: Arno
        // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
        // +      input by: Ratheous
        // +      input by: Mailfaker (http://www.weedem.fr/)
        // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
        // +      input by: felix
        // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
        // %        note 1: charset argument not supported
        // *     example 1: htmlspecialchars("<a href='test'>Test</a>",
        //                                   'ENT_QUOTES');
        // *     returns 1: '&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;'
        // *     example 2: htmlspecialchars("ab\"c'd",
        //                                  ['ENT_NOQUOTES', 'ENT_QUOTES']);
        // *     returns 2: 'ab"c&#039;d'
        // *     example 3: htmlspecialchars("my "&entity;" is still here",
        //                                  null, null, false);
        // *     returns 3: 'my &quot;&entity;&quot; is still here'
        var optTemp = 0,
            i = 0,
            noquotes = false;
        if (quote_style === undefined || quote_style === null)
        {
            quote_style = 2;
        }
        string = string.toString();
        if (double_encode !== false)
        {   // Put this first to avoid double-encoding
            string = string.replace(/&/g, '&amp;');
        }
        string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');
     
        var OPTS = {
            'ENT_NOQUOTES': 0,
            'ENT_HTML_QUOTE_SINGLE': 1,
            'ENT_HTML_QUOTE_DOUBLE': 2,
            'ENT_COMPAT': 2,
            'ENT_QUOTES': 3,
            'ENT_IGNORE': 4
        };
        if (quote_style === 0)
        {
            noquotes = true;
        }
        if (typeof quote_style !== 'number')
        {   // Allow for a single string or an array of string flags
            quote_style = [].concat(quote_style);
            for (i = 0; i < quote_style.length; i++)
            {
                // Resolve string input to bitwise
                // e.g. 'PATHINFO_EXTENSION' becomes 4
                if (OPTS[quote_style[i]] === 0)
                {
                    noquotes = true;
                }
                else if (OPTS[quote_style[i]])
                {
                    optTemp = optTemp | OPTS[quote_style[i]];
                }
            }
            quote_style = optTemp;
        }
        if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE)
        {
            string = string.replace(/'/g, '&#039;');
        }
        if (!noquotes)
        {
            string = string.replace(/"/g, '&quot;');
        }
     
        return string;
    };

    $.htmlspecialchars_decode = function(string, quote_style) {
        // Convert special HTML entities back to characters  
        // 
        // version: 1103.1210
        // discuss at: http://phpjs.org/functions/htmlspecialchars_decode
        // +   original by: Mirek Slugen
        // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +   bugfixed by: Mateusz "loonquawl" Zalega
        // +      input by: ReverseSyntax
        // +      input by: Slawomir Kaniecki
        // +      input by: Scott Cariss
        // +      input by: Francois
        // +   bugfixed by: Onno Marsman
        // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
        // +      input by: Ratheous
        // +      input by: Mailfaker (http://www.weedem.fr/)
        // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
        // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
        // *     example 1: htmlspecialchars_decode("<p>this -&gt; &quot;</p>",
        //                                          'ENT_NOQUOTES');
        // *     returns 1: '<p>this -> &quot;</p>'
        // *     example 2: htmlspecialchars_decode("&amp;quot;");
        // *     returns 2: '&quot;'
        var optTemp = 0,
            i = 0,
            noquotes = false;
        if (quote_style === undefined)
        {
            quote_style = 2;
        }
        string = string.toString().replace(/&lt;/g, '<').replace(/&gt;/g, '>');
        var OPTS = {
            'ENT_NOQUOTES': 0,
            'ENT_HTML_QUOTE_SINGLE': 1,
            'ENT_HTML_QUOTE_DOUBLE': 2,
            'ENT_COMPAT': 2,
            'ENT_QUOTES': 3,
            'ENT_IGNORE': 4
        };
        if (quote_style === 0)
        {
            noquotes = true;
        }
        if (typeof quote_style !== 'number')
        {   // Allow for a single string or an array of string flags
            quote_style = [].concat(quote_style);
            for (i = 0; i < quote_style.length; i++)
            {
                // Resolve string input to bitwise
                // e.g. 'PATHINFO_EXTENSION' becomes 4
                if (OPTS[quote_style[i]] === 0)
                {
                    noquotes = true;
                }
                else if (OPTS[quote_style[i]])
                {
                    optTemp = optTemp | OPTS[quote_style[i]];
                }
            }
            quote_style = optTemp;
        }
        if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE)
        {
            // PHP doesn't currently escape if more than one 0, but it should
            string = string.replace(/&#0*39;/g, "'");

            // This would also be useful here, but not a part of PHP
            // string = string.replace(/&apos;|&#x0*27;/g, "'");
        }
        if (!noquotes)
        {
            string = string.replace(/&quot;/g, '"');
        }
        // Put this in last place to avoid escape being double-decoded
        string = string.replace(/&amp;/g, '&');
     
        return string;
    };

    /*************************************************************************
     * Date extensions
     *
     */

    /** @brief  Convert a Date instance to a client-localized string of the
     *          form:
     *              YYYY-MM-DD g:mm a
     *  @param  date        The Date instance to convert.  If not provided, use
     *                      the current date/time.
     *
     *  @param  timeOnly    Exclude the date information? [ false ]
     *
     *  @return The string representation of the given date.
     */
    $.date2str = function(date, timeOnly) {
        if ( (date === undefined) || (! date instanceof Date) )
        {
            date = new Date();
        }

        if (date.getTime() <= 0)
        {
            return 'never';
        }

        var dateStr = '';
        if (timeOnly !== true)
        {
            dateStr = date.getFullYear()
                    + '-'+ $.padString((date.getMonth() + 1))
                    + '-'+ $.padString(date.getDate())
                    + ' ';
        }

        var hour        = date.getHours();
        var meridian    = 'am';
        if (hour === 0)
        {
            hour     = 12;
        }
        else if (hour === 12)
        {
            meridian = 'pm';
        }
        else if (hour > 12)
        {
            hour     -= 12;
            meridian  = 'pm';
        }

                   /* Using a string for padding works here because we'll only
                    * ever need to add 1 character at most.
                    *
                    * We use the span to try and ensure that we'll always align
                    * properly since the only value that MIGHT be in the empty
                    * field is 1.
                    */
        dateStr += $.padString(hour, 2,
                               "<span style='visibility:hidden;'>1</span>")
                + ':'+ $.padString(date.getMinutes())
                + ' '+ meridian;

        return dateStr;
    };

    /** @brief  Convert the given string into a Date instance.
     *  @param  str     The date string to convert
     *                      (MUST be GMT in the form 'YYYY-MM-DD HH:MM:SS')
     *
     *  @return The Date instance (null if invalid).
     */
    $.str2date = function(str) {
        // Ensure 'str' is a string
        str = ''+str;

        var parts       = str.split(' '),
            dateParts   = parts[0].split('-'),
            timeParts   = parts[1].split(':'),
            date        = new Date();
        dateParts[1] = parseInt(dateParts[1], 10);
        if (dateParts[1] > 0)   { dateParts[1] -= 1; }

        date.setUTCFullYear(dateParts[0] );
        date.setUTCMonth(   dateParts[1] );
        date.setUTCDate(    dateParts[2] );
        date.setUTCHours(   timeParts[0] );
        date.setUTCMinutes( timeParts[1] );
        date.setUTCSeconds( timeParts[2] );

        return date;
    };

    /*************************************************************************
     * Document-level progress/activity spinner
     *
     */
    $.spinner = function(state) {
        var $spin       = $('#pageHeader h1 a img');

        if ($spin.length > 0)
        {
            var url = $spin.attr('src');
            if (state === false)
            {
                // Turn the spinner off
                if (url.indexOf('-spinner.gif') > 0)
                {
                    $spin.attr('src', url.replace('-spinner.gif', '.gif') );
                }
            }
            else
            {
                // Turn the spinner on
                if (url.indexOf('-spinner.gif') < 0)
                {
                    $spin.attr('src', url.replace('.gif', '-spinner.gif') );
                }
            }
        }
    };

    /*************************************************************************
     * z-index
     *
     */

    /** @brief  For any element, locate the maximum z-index in its anscestor
     *          chain.
     *
     *  @return The maximum z-index.
     */
    $.fn.maxZindex = function() {
        var maxZ    = 0;

        $(this).parents().each(function() {
            //if ((! this) || (this.length < 1))  return;

            var thisZ   = $(this).css('z-index');
            if (!isNaN(thisZ)) maxZ = Math.max(maxZ, thisZ);
        });

        return maxZ;
    };

    /*************************************************************************
     * Overlay any element.
     *
     */
    $.fn.overlay = function(zIndex) {
        return this.each(function() {
            var $el         = $(this);
            var myZ         = (zIndex === undefined
                                ? $el.maxZindex()
                                : zIndex) + 1;
            var $overlay    = $('<div></div>')
                                    .addClass('ui-widget-overlay')
                                    .appendTo($el)
                                    .css({position: 'absolute',
                                          top:      0,
                                          left:     0,
                                          width:    $el.outerWidth(),
                                          height:   $el.outerHeight(),
                                          'z-index':myZ});
            /*
            if ($.fn.bgiframe)
            {
                $overlay.bgiframe();
            }
            // */

            $el.data('connexions-overlay', $overlay);
        });
    };

    $.fn.unoverlay = function() {
        return this.each(function() {
            var $el         = $(this);
            var $overlay    = $el.data('connexions-overlay');

            if ($overlay && ($overlay.length > 0))
            {
                $overlay.remove();
            }
        });
    };

    $.fn.mask = function() {
        return this.each(function() {
            $(this).overlay();

            $.spinner();
        });
    };

    $.fn.unmask = function() {
        return this.each(function() {
            $(this).unoverlay();

            $.spinner(false);
        });
    };

    /*************************************************************************
     * Handle a 'closeAction'
     *
     */

    /** @brief  Implement a generic close action based upon an incoming request
     *          value and the completion of the current task.
     *  @param  action      The desired close action [ 'back' ]:
     *                      - 'back'                move back in the browser's
     *                                              history;
     *                      - 'callback:%func%'     invoke the Javascript
     *                                              function %func%;
     *                      - 'close'               attempt to close the
     *                                              current window;
     *                      - 'hide'                hide the specified
     *                                              $container;
     *                      - 'iframe'              attempt to invoke the
     *                                              'close' function on the
     *                                              containing iframe;
     *                      - 'ignore'              do nothing;
     *                      - 'redirect:%url%'      redirect to the specified
     *                        'urlCallback:%url%'   %url%.  If %url% is empty,
     *                                              invoke 'back';
     *                      - 'reload'              simply reload the current
     *                                              window;
     *  @param  $container  For 'hide', the container to hide;
     */
    $.closeAction = function(action, $container) {
        var split   = action.indexOf(':');
        var param   = null;

        if (split <= 0)
        {
            action = action.toLowerCase();
        }
        else
        {
            param  = action.substr(split + 1);
            action = action.substr(0, split).toLowerCase();
        }

        switch (action)
        {
        case 'callback':    // Invoke the named Javascript function
            if (param && param.length)
            {
                eval(param +'();');
            }
            break;

        case 'hide':        // Hide the containing DOM element
            $container.hide();
            break;

        case 'iframe':      // Attempt to 'close' the containing iframe
            if (window.frameElement && window.frameElement.close)
            {
                window.frameElement.close();
            }
            break;

        case 'ignore':      // Do nothing
            break;


        // Actions with fallthroughs
        case 'close':       // Attempt to close the containing window
            try {
                window.close();
                break;
            } catch(e) {}

            // Cannot close -- fall through to reload

        case 'reload':      // Reload the current page
            window.location.reload();
            break;

        case 'redirect':    // Redirect to the specified URL
        case 'urlcallback':
            if (param && param.length)
            {
                location.href = param;
                break;
            }
            // No url provided -- fall through to 'back'

        case 'back':        // Move back in the browsers history
        default:
            window.history.back();
            break;
        }
    };

    /** @brief  Given the jQuery DOM element of an autoSignin checkbox,
     *          determine the new autoSignin value and set a long-term
     *          cookie with the new value.
     *  @param  $el     The jQuery DOM element of an autoSignin checkbox.
     *
     *  Given the jQuery DOM element of an autoSignin checkbox, determine the
     *  new autoSignin value and set a long-term cookie with the new value.
     *  If the new value is empty, delete the autoSignin cookie.
     *
     *  Requires: jquery.registry.js and that 'urls' be previously set to
     *            include 'base' as the site's baseUrl.
     */
    $.changeAutoSignin = function($el) {
        var cookieName  = $.registry('api').autoSigninCookie,
            targetVal   = $el.val(),
            curVal      = $.cookie( cookieName ),
            cookieOpts  = {
                'expires':  365,    // days
                'path':     $.registry('urls').base
            },
            newVal      = (curVal ? curVal : '');

        if ($el.is(':checked'))
        {
            // add targetVal
            if (newVal.length > 0)  newVal += ',';
            newVal += targetVal;
        }
        else
        {
            // remove targetVal
            var re  = new RegExp('(\s*,\s*)?'+ targetVal +'(\s*,\s*)?');
            newVal = newVal.replace(re, '');
        }

        if (newVal.length < 1)  newVal = null;

        $.cookie(cookieName, newVal, cookieOpts);
    };

    // Start the spinner immediately as well as anytime the window is unloaded
    $.spinner();

    $(window).unload(function() {
        $.spinner();
    });
 }(jQuery));
/** @file
 *
 *  Provide a simple, global registry that stores data using jQuery.data,
 *  attached to 'document'.
 *
 */
/*jslint nomen: false, laxbreak: true */
/*global jQuery:false, document:false */
(function ($) {
    $.registry = function (name, value) {
        if (value !== undefined)
        {
            // name and value given -- set
            $.data(document, name, value);
        }
        else
        {
            // name, but no value -- get
            return $.data(document, name);
        }
    };

}(jQuery));
/**
 * Cookie plugin
 *
 * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, document:false */

/**
 * Create a cookie with the given name and value and other optional parameters.
 *
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Set the value of a cookie.
 * @example $.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'jquery.com', secure: true });
 * @desc Create a cookie with all available options.
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Create a session cookie.
 * @example $.cookie('the_cookie', null);
 * @desc Delete a cookie by passing null as value. Keep in mind that you have to use the same path and domain
 *       used when the cookie was set.
 *
 * @param String name The name of the cookie.
 * @param String value The value of the cookie.
 * @param Object options An object literal containing key/value pairs to provide optional cookie attributes.
 * @option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
 *                             If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
 *                             If set to null or omitted, the cookie will be a session cookie and will not be retained
 *                             when the the browser exits.
 * @option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
 * @option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
 * @option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
 *                        require a secure protocol (like HTTPS).
 * @type undefined
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */

/**
 * Get the value of a cookie with the given name.
 *
 * @example $.cookie('the_cookie');
 * @desc Get the value of a cookie.
 *
 * @param String name The name of the cookie.
 * @return The value of the cookie.
 * @type String
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */
jQuery.cookie = function(name, value, options) {
    if (typeof value !== 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires &&
            (typeof options.expires === 'number' ||
             options.expires.toUTCString)) {
            var date;
            if (typeof options.expires === 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        if (options.path === undefined) {
            options.path = window.location.pathname;
        }

        if (options.path && (options.path.length > 1))
        {
            // Strip any trailing '/'
            options.path = options.path.replace(/\/+$/, '');
        }
        if ((options.secure                       === undefined) &&
            (window.location.protocol.substr(0,5) === 'https'))
        {
            options.secure = true;
        }

        // CAUTION: Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path   = options.path   ? '; path='   + options.path   : '';
        var domain = options.domain ? '; domain=' + options.domain : '';
        var secure = options.secure ? '; secure'                   : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie !== '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) === (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};
/**
 * jQuery.ScrollTo
 * Copyright (c) 2007-2009 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * Date: 5/25/2009
 *
 * @projectDescription Easy element scrolling using jQuery.
 * http://flesler.blogspot.com/2007/10/jqueryscrollto.html
 * Works with jQuery +1.2.6. Tested on FF 2/3, IE 6/7/8, Opera 9.5/6, Safari 3, Chrome 1 on WinXP.
 *
 * @author Ariel Flesler
 * @version 1.4.2
 *
 * @id jQuery.scrollTo
 * @id jQuery.fn.scrollTo
 * @param {String, Number, DOMElement, jQuery, Object} target Where to scroll the matched elements.
 *	  The different options for target are:
 *		- A number position (will be applied to all axes).
 *		- A string position ('44', '100px', '+=90', etc ) will be applied to all axes
 *		- A jQuery/DOM element ( logically, child of the element to scroll )
 *		- A string selector, that will be relative to the element to scroll ( 'li:eq(2)', etc )
 *		- A hash { top:x, left:y }, x and y can be any kind of number/string like above.
*		- A percentage of the container's dimension/s, for example: 50% to go to the middle.
 *		- The string 'max' for go-to-end. 
 * @param {Number} duration The OVERALL length of the animation, this argument can be the settings object instead.
 * @param {Object,Function} settings Optional set of settings or the onAfter callback.
 *	 @option {String} axis Which axis must be scrolled, use 'x', 'y', 'xy' or 'yx'.
 *	 @option {Number} duration The OVERALL length of the animation.
 *	 @option {String} easing The easing method for the animation.
 *	 @option {Boolean} margin If true, the margin of the target element will be deducted from the final position.
 *	 @option {Object, Number} offset Add/deduct from the end position. One number for both axes or { top:x, left:y }.
 *	 @option {Object, Number} over Add/deduct the height/width multiplied by 'over', can be { top:x, left:y } when using both axes.
 *	 @option {Boolean} queue If true, and both axis are given, the 2nd axis will only be animated after the first one ends.
 *	 @option {Function} onAfter Function to be called after the scrolling ends. 
 *	 @option {Function} onAfterFirst If queuing is activated, this function will be called after the first scrolling ends.
 * @return {jQuery} Returns the same jQuery object, for chaining.
 *
 * @desc Scroll to a fixed position
 * @example $('div').scrollTo( 340 );
 *
 * @desc Scroll relatively to the actual position
 * @example $('div').scrollTo( '+=340px', { axis:'y' } );
 *
 * @dec Scroll using a selector (relative to the scrolled element)
 * @example $('div').scrollTo( 'p.paragraph:eq(2)', 500, { easing:'swing', queue:true, axis:'xy' } );
 *
 * @ Scroll to a DOM element (same for jQuery object)
 * @example var second_child = document.getElementById('container').firstChild.nextSibling;
 *			$('#container').scrollTo( second_child, { duration:500, axis:'x', onAfter:function(){
 *				alert('scrolled!!');																   
 *			}});
 *
 * @desc Scroll on both axes, to different values
 * @example $('div').scrollTo( { top: 300, left:'+=200' }, { axis:'xy', offset:-20 } );
 */
;(function( $ ){
	
	var $scrollTo = $.scrollTo = function( target, duration, settings ){
		$(window).scrollTo( target, duration, settings );
	};

	$scrollTo.defaults = {
		axis:'xy',
		duration: parseFloat($.fn.jquery) >= 1.3 ? 0 : 1
	};

	// Returns the element that needs to be animated to scroll the window.
	// Kept for backwards compatibility (specially for localScroll & serialScroll)
	$scrollTo.window = function( scope ){
		return $(window)._scrollable();
	};

	// Hack, hack, hack :)
	// Returns the real elements to scroll (supports window/iframes, documents and regular nodes)
	$.fn._scrollable = function(){
		return this.map(function(){
			var elem = this,
				isWin = !elem.nodeName || $.inArray( elem.nodeName.toLowerCase(), ['iframe','#document','html','body'] ) != -1;

				if( !isWin )
					return elem;

			var doc = (elem.contentWindow || elem).document || elem.ownerDocument || elem;
			
			return $.browser.safari || doc.compatMode == 'BackCompat' ?
				doc.body : 
				doc.documentElement;
		});
	};

	$.fn.scrollTo = function( target, duration, settings ){
		if( typeof duration == 'object' ){
			settings = duration;
			duration = 0;
		}
		if( typeof settings == 'function' )
			settings = { onAfter:settings };
			
		if( target == 'max' )
			target = 9e9;
			
		settings = $.extend( {}, $scrollTo.defaults, settings );
		// Speed is still recognized for backwards compatibility
		duration = duration || settings.speed || settings.duration;
		// Make sure the settings are given right
		settings.queue = settings.queue && settings.axis.length > 1;
		
		if( settings.queue )
			// Let's keep the overall duration
			duration /= 2;
		settings.offset = both( settings.offset );
		settings.over = both( settings.over );

		return this._scrollable().each(function(){
			var elem = this,
				$elem = $(elem),
				targ = target, toff, attr = {},
				win = $elem.is('html,body');

			switch( typeof targ ){
				// A number will pass the regex
				case 'number':
				case 'string':
					if( /^([+-]=)?\d+(\.\d+)?(px|%)?$/.test(targ) ){
						targ = both( targ );
						// We are done
						break;
					}
					// Relative selector, no break!
					targ = $(targ,this);
				case 'object':
					// DOMElement / jQuery
					if( targ.is || targ.style )
						// Get the real position of the target 
						toff = (targ = $(targ)).offset();
			}
			$.each( settings.axis.split(''), function( i, axis ){
				var Pos	= axis == 'x' ? 'Left' : 'Top',
					pos = Pos.toLowerCase(),
					key = 'scroll' + Pos,
					old = elem[key],
					max = $scrollTo.max(elem, axis);

				if( toff ){// jQuery / DOMElement
					attr[key] = toff[pos] + ( win ? 0 : old - $elem.offset()[pos] );

					// If it's a dom element, reduce the margin
					if( settings.margin ){
						attr[key] -= parseInt(targ.css('margin'+Pos)) || 0;
						attr[key] -= parseInt(targ.css('border'+Pos+'Width')) || 0;
					}
					
					attr[key] += settings.offset[pos] || 0;
					
					if( settings.over[pos] )
						// Scroll to a fraction of its width/height
						attr[key] += targ[axis=='x'?'width':'height']() * settings.over[pos];
				}else{ 
					var val = targ[pos];
					// Handle percentage values
					attr[key] = val.slice && val.slice(-1) == '%' ? 
						parseFloat(val) / 100 * max
						: val;
				}

				// Number or 'number'
				if( /^\d+$/.test(attr[key]) )
					// Check the limits
					attr[key] = attr[key] <= 0 ? 0 : Math.min( attr[key], max );

				// Queueing axes
				if( !i && settings.queue ){
					// Don't waste time animating, if there's no need.
					if( old != attr[key] )
						// Intermediate animation
						animate( settings.onAfterFirst );
					// Don't animate this axis again in the next iteration.
					delete attr[key];
				}
			});

			animate( settings.onAfter );			

			function animate( callback ){
				$elem.animate( attr, duration, settings.easing, callback && function(){
					callback.call(this, target, settings);
				});
			};

		}).end();
	};
	
	// Max scrolling position, works on quirks mode
	// It only fails (not too badly) on IE, quirks mode.
	$scrollTo.max = function( elem, axis ){
		var Dim = axis == 'x' ? 'Width' : 'Height',
			scroll = 'scroll'+Dim;
		
		if( !$(elem).is('html,body') )
			return elem[scroll] - $(elem)[Dim.toLowerCase()]();
		
		var size = 'client' + Dim,
			html = elem.ownerDocument.documentElement,
			body = elem.ownerDocument.body;

		return Math.max( html[scroll], body[scroll] ) 
			 - Math.min( html[size]  , body[size]   );
			
	};

	function both( val ){
		return typeof val == 'object' ? val : { top:val, left:val };
	};

})( jQuery );/**
 * --------------------------------------------------------------------
 * jQuery-Plugin "pngFix"
 * Version: 1.2, 09.03.2009
 * by Andreas Eberhard, andreas.eberhard@gmail.com
 *                      http://jquery.andreaseberhard.de/
 *
 * Copyright (c) 2007 Andreas Eberhard
 * Licensed under GPL (http://www.opensource.org/licenses/gpl-license.php)
 *
 * Changelog:
 *    09.03.2009 Version 1.2
 *    - Update for jQuery 1.3.x, removed @ from selectors
 *    11.09.2007 Version 1.1
 *    - removed noConflict
 *    - added png-support for input type=image
 *    - 01.08.2007 CSS background-image support extension added by Scott Jehl, scott@filamentgroup.com, http://www.filamentgroup.com
 *    31.05.2007 initial Version 1.0
 * --------------------------------------------------------------------
 * @example $(function(){$(document).pngFix();});
 * @desc Fixes all PNG's in the document on document.ready
 *
 * jQuery(function(){jQuery(document).pngFix();});
 * @desc Fixes all PNG's in the document on document.ready when using noConflict
 *
 * @example $(function(){$('div.examples').pngFix();});
 * @desc Fixes all PNG's within div with class examples
 *
 * @example $(function(){$('div.examples').pngFix( { blankgif:'ext.gif' } );});
 * @desc Fixes all PNG's within div with class examples, provides blank gif for input with png
 * --------------------------------------------------------------------
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, navigator:false */

(function($) {

jQuery.fn.pngFix = function(settings) {

    // Settings
    settings = jQuery.extend({
        blankgif: 'blank.gif'
    }, settings);

    var ie55 = (navigator.appName === "Microsoft Internet Explorer" && parseInt(navigator.appVersion) === 4 && navigator.appVersion.indexOf("MSIE 5.5") !== -1);
    var ie6 = (navigator.appName === "Microsoft Internet Explorer" && parseInt(navigator.appVersion) === 4 && navigator.appVersion.indexOf("MSIE 6.0") !== -1);

    if (jQuery.browser.msie && (ie55 || ie6)) {

        //fix images with png-source
        jQuery(this).find("img[src$=.png]").each(function() {

            jQuery(this).attr('width',jQuery(this).width());
            jQuery(this).attr('height',jQuery(this).height());

            var prevStyle = '';
            var strNewHTML = '';
            var imgId = (jQuery(this).attr('id')) ? 'id="' + jQuery(this).attr('id') + '" ' : '';
            var imgClass = (jQuery(this).attr('class')) ? 'class="' + jQuery(this).attr('class') + '" ' : '';
            var imgTitle = (jQuery(this).attr('title')) ? 'title="' + jQuery(this).attr('title') + '" ' : '';
            var imgAlt = (jQuery(this).attr('alt')) ? 'alt="' + jQuery(this).attr('alt') + '" ' : '';
            var imgAlign = (jQuery(this).attr('align')) ? 'float:' + jQuery(this).attr('align') + ';' : '';
            var imgHand = (jQuery(this).parent().attr('href')) ? 'cursor:hand;' : '';
            if (this.style.border) {
                prevStyle += 'border:'+this.style.border+';';
                this.style.border = '';
            }
            if (this.style.padding) {
                prevStyle += 'padding:'+this.style.padding+';';
                this.style.padding = '';
            }
            if (this.style.margin) {
                prevStyle += 'margin:'+this.style.margin+';';
                this.style.margin = '';
            }
            var imgStyle = (this.style.cssText);

            strNewHTML += '<span '+imgId+imgClass+imgTitle+imgAlt;
            strNewHTML += 'style="position:relative;white-space:pre-line;display:inline-block;background:transparent;'+imgAlign+imgHand;
            strNewHTML += 'width:' + jQuery(this).width() + 'px;' + 'height:' + jQuery(this).height() + 'px;';
            strNewHTML += 'filter:progid:DXImageTransform.Microsoft.AlphaImageLoader' + '(src=\'' + jQuery(this).attr('src') + '\', sizingMethod=\'scale\');';
            strNewHTML += imgStyle+'"></span>';
            if (prevStyle !== ''){
                strNewHTML = '<span style="position:relative;display:inline-block;'+prevStyle+imgHand+'width:' + jQuery(this).width() + 'px;' + 'height:' + jQuery(this).height() + 'px;'+'">' + strNewHTML + '</span>';
            }

            jQuery(this).hide();
            jQuery(this).after(strNewHTML);

        });

        // fix css background pngs
        jQuery(this).find("*").each(function(){
            var bgIMG = jQuery(this).css('background-image');
            if(bgIMG.indexOf(".png") !== -1){
                var iebg = bgIMG.split('url("')[1].split('")')[0];
                jQuery(this).css('background-image', 'none');
                jQuery(this).get(0).runtimeStyle.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + iebg + "',sizingMethod='scale')";
            }
        });
        
        //fix input with png-source
        jQuery(this).find("input[src$=.png]").each(function() {
            var bgIMG = jQuery(this).attr('src');
            jQuery(this).get(0).runtimeStyle.filter = 'progid:DXImageTransform.Microsoft.AlphaImageLoader' + '(src=\'' + bgIMG + '\', sizingMethod=\'scale\');';
            jQuery(this).attr('src', settings.blankgif);
        });
    
    }
    
    return jQuery;

};

}(jQuery));
/** @file
 *
 *  A local navigation helper for sections that use local urls to identify and
 *  navigate between collapsable portions of a single page/area (e.g. help).
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.autocomplete.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, document:false */
(function($) {

    /** @brief  If the browser supports 'window.history.pushState', establish
     *          local navigation so the page doesn't have to be completely
     *          reloaded for local links between sections.
     *  @param  $body   The DOM element containing the target anchor elements
     *                  to be handled for local navigation;
     *  @param  url     The base URL that will be handled.
     */
    function setup_localNavigation($body, url)
    {
        if (! window.history.pushState)
        {
            /* HTML5 pushState/popState is NOT supported.  Local navigation
             * cannot be implemented.
             */
            return;
        }

        $body.data('localNavigation.url', url);

        // The current local history stack
        var localHistory    = [];
        $(window).bind('popstate', function(e) {
            if (localHistory.length < 1)
            {
                // No local history to use -- let the browser handle this
                return;
            }

            var state   = localHistory.pop();
            var $a      = state.context;
            var $el     = $a.parents('.collapsable:first');

            // Scroll back to the previous element
            $.scrollTo( $el, {
                duration:   800,
                onAfter:    function() {
                    $el.effect('highlight', null, 2000);
                }
            });
        });

        // Bind clicks for any local href
        $body.delegate('a[href^="'+ url +'"]', 'click', function(e) {
            var a       = this;
            var $a      = $(a);
            var href    = $a.attr('href');

            /* Doesn't work on Chrome if 'state' is non-null
            var state   = { context: a };
            var title   = $('head title').text();
            window.history.pushState(state, title, href);
            // */
            localHistory.push({ context: $a });
            window.history.pushState(null, null, href);

            href = '#' + href.replace(url +'/', '')
                             .replace(/\//g, '_')
                             .toLowerCase()

            var $el = $( href );
            if ($el.length < 1)
            {
                // Don't stop this one since we don't know where it goes...
                return;
            }

            // Ensure that all collapsable parents are expanded
            $el.parents('.collapsable').trigger('expand');
            $el.trigger('expand');

            // Give the target item time to become visible
            var timer   = window.setInterval(function() {
                if (! $el.is(':visible'))
                {
                    return;
                }

                window.clearInterval(timer);
                $.scrollTo( $el, {
                    duration:   800,
                    onAfter:    function() {
                        $el.parent().effect('highlight', null, 2000);
                    }
                });
            }, 250);

            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    }

$.fn.localNavigation = function(url) {
    this.each(function() {
        setup_localNavigation($(this), url);
    });

    // Return this for a fluent interface
    return this;
};

 }(jQuery));
/** @file
 *
 *  Provide a sprite-based checkbox.
 *
 * Take control of pre-assembled HTML of the form:
 *  <div>
 *    <label for='cbId'>Label</label>
 *    <input name='cbId' type='checkbox' value='true' />
 *  </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("ui.checkbox", {
    version: "0.1.2",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Defaults
        css:        'checkbox',             // General CSS class
        cssOn:      'on',                   // CSS class when    checked
        cssOff:     'off',                  // CSS class when un-checked
        titleOn:    'click to turn off',    // Title when    checked
        titleOff:   'click to turn on',     // Title when un-checked

        useElTitle: true,                   // Include the title of the source
                                            // element (or it's associated
                                            // label) in the title of this
                                            // checkbox.

        hideLabel:  false                   // Hide the associated label?  If
                                            // not, clicking on the title will
                                            // be the same as clicking on the
                                            // checkbox.
    },

    /** @brief  Initialize a new instance.
     *
     *  Valid options are:
     *      css         General space-separated CSS class(es) for the checkbox
     *                  [ 'checkbox' ];
     *      cssOn       Space-separated CSS class(es) when checked
     *                  [ 'on' ];
     *      cssOff      Space-separated CSS class(es) when un-checked
     *                  [ 'off' ];
     *      titleOn     Title when checked
     *                  [ 'click to turn off' ];
     *      titleOff    Title when un-checked
     *                  [ 'click to turn on' ];
     *
     *      useElTitle  Include the title of the source element (or it's
     *                  associated label) in the title of this checkbox (as a
     *                  prefix to 'titleOn' or 'titleOff')
     *                  [ true ];
     *
     *      hideLabel   Hide the associated label?  If not, clicking on the
     *                  title will be the same as clicking on the checkbox
     *                  [ false ].
     *
     *  @triggers:
     *      'enabled.uicheckbox'    when element is enabled;
     *      'disabled.uicheckbox'   when element is disabled;
     *      'checked.uicheckbox'    when element is checked;
     *      'unchecked.uicheckbox'  when element is unchecked.
     */
    _create: function() {
        var self    = this;
        var opts    = this.options;

        opts.enabled = self.element.attr('disabled') ? false : true;
        opts.checked = self.element.attr('checked')  ? true  : false;
        opts.title   = '';

        // Remember the original value
        self.element.data('value.uicheckbox', opts.checked);

        var name     = self.element.attr('name');
        var id       = self.element.attr('id');

        // Try to locate the associated label
        self.$label  = false;

        if (id)
        {
            self.$label = $('label[for='+ id +']');
        }
        if ( ((! self.$label) || (self.$label.length < 1)) && name)
        {
            self.$label = $('label[for='+ name +']');
        }

        if (opts.useElTitle === true)
        {
            opts.title = self.element.attr('title');
            if ( ((! opts.title) || (opts.title.length < 1)) &&
                 (self.$label.length > 0) )
            {
                // The element has no 'title', use the text of the label.
                opts.title = self.$label.text();
            }
        }

        var title   = opts.title
                    + (opts.checked
                            ? opts.titleOn
                            : opts.titleOff);

        // Create a new element that will be placed just after the current
        self.$el     = $(  '<span class="checkbox">'
                          + '<div '
                          +    'class="'+ opts.css
                          +      (opts.enabled ? ' '   : ' diabled ')
                          +      (opts.checked
                                    ? opts.cssOn
                                    : opts.cssOff) +'"'
                          +     (title && title.length > 0
                                    ? ' title="'+ title +'"'
                                    : '')
                          +   '>&nbsp;</div>'
                          +'</span>');
        self.img      = self.$el.find('div');

        // Insert the new element after the existing and remove the existing.
        self.$el.insertAfter(self.element);

        // Hide the original element.
        self.element.hide();

        // Create a new hidden input to represent the final value.
        self.$value = $('<input type="hidden" '
                    +               (id ? 'id="'+ id +'" '
                                        : '')
                    +          'name="'+ name +'" />');
        self.$value.attr('value', opts.checked);
        self.$value.insertBefore(self.$el);


        if (self.$label && (self.$label.length > 0))
        {
            // We have a label for this field.
            if (opts.hideLabel === true)
            {
                // Hide it.
                self.$label.hide();
            }
            else
            {
                // Treat a click on the label as a click on the item.
                self.$label.click(function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    self.$el.trigger('click',[e]);
                    return false;
                });
            }
        }

        // Interaction events
        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self    = this;

        var _mouseenter = function(e) {
            if (self.options.enabled === true)
            {
                self.$el.addClass('ui-state-hover');
            }
        };

        var _mouseleave = function(e) {
            self.$el.removeClass('ui-state-hover');
        };

        var _focus      = function(e) {
            if (self.options.enabled === true)
            {
                self.$el.addClass('ui-state-focus');
            }
        };

        var _blur       = function(e) {
            self.$el.removeClass('ui-state-focus');
        };

        var _click      = function(e) {
            self.toggle();
        };

        self.$el.bind('mouseenter.uicheckbox', _mouseenter)
                .bind('mouseleave.uicheckbox', _mouseleave)
                .bind('focus.uicheckbox',      _focus)
                .bind('blur.uicheckbox',       _blur)
                .bind('click.uicheckbox',      _click);
    },

    /************************
     * Public methods
     *
     */
    isChecked: function() {
        return this.options.checked;
    },
    isEnabled: function() {
        return this.options.enabled;
    },

    enable: function()
    {
        var self    = this;
        var opts    = self.options;

        if (! opts.enabled)
        {
            opts.enabled = true;
            self.$el.removeClass('ui-state-disabled');
            self.$el.parent().removeClass('ui-state-disabled');

            var title   = opts.title
                        + (opts.checked
                                ? opts.titleOn
                                : opts.titleOff);
            self.img.attr('title', title);

            self._trigger('enabled');
        }
    },

    disable: function()
    {
        var self    = this;
        var opts    = self.options;

        if (opts.enabled)
        {
            opts.enabled = false;
            self.$el.addClass('ui-state-disabled');
            self.$el.parent().addClass('ui-state-disabled');

            self.img.removeAttr('title');

            self._trigger('disabled');
        }
    },

    toggle: function()
    {
        if (this.options.checked)
        {
            this.uncheck();
        }
        else
        {
            this.check();
        }
    },

    check: function()
    {
        //if (this.options.enabled && (! this.options.checked))
        if (! this.options.checked)
        {
            this.options.checked = true;

            this.$value.attr('value', this.options.checked);

            this.img.removeClass(this.options.cssOff)
                    .addClass(this.options.cssOn)
                    .attr('title', this.options.title + this.options.titleOn);

            //this.element.click();
            this._trigger('change', null, 'check');
        }
    },

    uncheck: function()
    {
        //if (this.options.enabled && this.options.checked)
        if (this.options.checked)
        {
            this.options.checked = false;

            this.$value.attr('value', this.options.checked);

            this.img.removeClass(this.options.cssOn)
                    .addClass(this.options.cssOff)
                    .attr('title', this.options.title + this.options.titleOff);

            //this.element.click();
            this._trigger('change', null, 'uncheck');
        }
    },

    /** @brief  Reset the input to its original (creation or last direct set)
     *          value.
     */
    reset: function()
    {
        // Remember the original value
        if (this.element.data('value.uicheckbox'))
        {
            this.check();
        }
        else
        {
            this.uncheck();
        }
    },

    /** @brief  Has the value of this input changed from its original?
     *
     *  @return true | false
     */
    hasChanged: function()
    {
        return (this.options.checked !== this.element.data('value.uicheckbox'));
    },

    destroy: function() {
        if (this.$label)
        {
            this.$label.show();
        }

        this.$el.unbind('.uicheckbox');

        this.$value.remove();
        this.$el.remove();

        this.element.show();
    }
});


}(jQuery));
/** @file
 *
 *  Provide a ui-styled input / text input area that supports validation.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false, clearTimeout:false, setTimeout:false */
(function($) {

$.widget("ui.input", {
    version: "0.1.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Defaults
        priority:       'normal',
        $label:         null,       // The field label element.
        hideLabel:      true,       /* Should the label be hidden / used to
                                     * present a default value for the field
                                     * [ true ];
                                     */

        handleAutofill: false,      /* Should we attempt to handle issues with
                                     * browser auto-fill where input values
                                     * are automatically filled but no
                                     * 'change' or 'update' events are fired?
                                     */

        $validation:    null,       /* The element to present validation
                                     * information in [:sibling
                                     *                  .ui-field-status]
                                     */
        validation:     null        /* The validation criteria
                                     *      '!empty'
                                     *      function(value)
                                     *          returns {isValid:  true|false,
                                     *                   message: string};
                                     */
    },

    /** @brief  Initialize a new instance.
     *
     *  Valid options:
     *      priority        The priority of this field
     *                      ( ['normal'], 'primary', 'secondary');
     *      $label:         The field label element.
     *      hideLabel:      Should the label be hidden / used to present a
     *                      default value for the field [ true ];
     *      $validation:    The element to present validation information in
     *                      [ parent().find('.ui-field-status:first) ]
     *      validation:     The validation criteria:
     *                          '!empty'
     *                          function(value) that returns:
     *                              undefined   undetermined
     *                              true        valid
     *                              false       invalid
     *                              string      invalid, error message
     *
     *  @triggers:
     *      'validation_change' when the validaton state has changed;
     *      'enabled'           when element is enabled;
     *      'disabled'          when element is disabled.
     */
    _init: function()
    {
        var self    = this;
        var opts    = this.options;

        // Remember the original value (no validation)
        self.saved( true );

        opts.enabled = self.element.attr('disabled') ? false : true;

        if (opts.$validation)
        {
            if (opts.$validation.jquery === undefined)
            {
                opts.$validation = $(opts.$validation);
            }
        }
        else
        {
            /* We ASSUME that the form element is contained within a div along
             * with any  associated validation status element.
             *
             * Use the first child of our parent that has the CSS class
             *  'ui-field-status'
             */
            opts.$validation = self.element
                                        .parent()
                                            .find('.ui-field-status:first');
        }

        if ( (! opts.validation) && (self.element.hasClass('required')) )
        {
            // Use a default validation of '!empty'
            opts.validation = '!empty';
        }

        self.element.addClass( 'ui-input '
                              +'ui-corner-all ');
        self.keyTimer = null;

        if (opts.priority === 'primary')
        {
            self.element.addClass('ui-priority-primary');
        }
        else if (opts.priority === 'secondary')
        {
            self.element.addClass('ui-priority-secondary');
        }

        self.element.addClass('ui-state-default');
        if (! opts.enabled)
        {
            self.element.addClass('ui-state-disabled');
        }

        var id  = self.element.attr('id');
        if ((id === undefined) || (id.length < 1))
        {
            id = self.element.attr('name');
        }

        if ((id !== undefined) && (id.length > 0))
        {
            opts.$label  = self.element
                                .parent()
                                    .find('label[for='+ id +']');
        }
        else
        {
            opts.$label = self.element.closest('label');
        }

        if (opts.hideLabel === true)
        {
            opts.$label.addClass('ui-input-over')
                       .hide();
        }
        else
        {
            opts.$label.addClass('ui-input-over')
                       .show();
        }
        this._bindEvents();
    },

    _bindEvents: function()
    {
        var self    = this;
        var opts    = self.options;

        var _mouseenter = function(e) {
            /*
            var el  = $(this);
            if (el.input('option', 'enabled') === true)
                el.addClass('ui-state-hover');
            // */

            if (self.options.enabled === true)
            {
                self.element.addClass('ui-state-hover');
            }
        };

        var _mouseleave = function(e) {
            var el  = $(this);
            el.removeClass('ui-state-hover');
        };

        var _keydown   = function(e) {
            /*
            var el  = $(this);
            if (el.input('option', 'enabled') === true)
                el.input('validate');
            // */
            if (self.options.enabled !== true)
            {
                return;
            }

            if (self.keyTimer !== null)
            {
                clearTimeout(self.keyTimer);
            }

            if (e.keyCode === 9)    // tab
            {
                // let '_blur' handle leaving this field.
                return;
            }

            // Clear the current validation information
            self.valid(undefined);

            /* Set a timer that needs to expire BEFORE we fire the validation
             * check
             */
            self.keyTimer = setTimeout(function(){self.validate();}, 1000);
        };

        var _focus      = function(e) {
            if (self.options.enabled === true)
            {
                if (opts.hideLabel === true)
                {
                    opts.$label.hide();
                }

                self.element.removeClass('ui-state-empty')
                            .addClass('ui-state-focus ui-state-active');
            }
        };

        var _blur       = function(e) {
            self._blur();
        };

        var _validate   = function(e) {
            self.validate();
        };

        self.element
                .bind('mouseenter.uiinput', _mouseenter)
                .bind('mouseleave.uiinput', _mouseleave)
                .bind('keydown.uiinput',    _keydown)
                .bind('focus.uiinput',      _focus)
                .bind('blur.uiinput',       _blur)
                .bind('change.uiinput',     _blur)
                .bind('validate.uiinput',   _validate);

        opts.$label
                .bind('click.uiinput', function() { self.element.focus(); });

        if (self.val() !== '')
        {
            // Perform an initial validation
            self.validate();
        }
        else if (opts.hideLabel === true)
        {
            opts.$label.show();
        }

        if (opts.handleAutofill === true)
        {
            self._handleAutofill();
        }
    },

    /** @brief  Hack to try and deal with browser autofill issues when the
     *          browser doesn't fire any 'change' event on autofill.
     */
    _handleAutofill: function() {
        /* Handle some browser autocompletion issues by doing a small bit of
         * polling at 10, 100, 1000, and 10000 microseconds to see if the
         * browser makes an unannounced change to the value.
         */
        var self            = this;
        var timeout         = 1;
        var autofillCheck   = function() {
            if (self.hasChanged())
            {
                $.log('ui.input: id[ %s ] unannounced change after %sms!',
                      self.element.attr('name'), timeout);
                self._blur();
            }
            else if (timeout < 10000)
            {
                // Wait a bit longer
                timeout *= 10;

                $.log('ui.input: id[ %s ] wait for %sms for a change...',
                      self.element.attr('name'), timeout);
                window.setTimeout(function() { autofillCheck(); }, timeout);
            }
            else
            {
                $.log('ui.input: id[ %s ] NO change after %sms',
                      self.element.attr('name'), timeout);
            }
        };

        autofillCheck();
    },

    _blur: function()
    {
        var self    = this;
        var opts    = self.options;

        self.element.removeClass('ui-state-focus ui-state-active');
        if (! self.element.hasClass('ui-state-valid'))
        {
            self.validate();
        }

        if (self.val() === '')
        {
            self.element.addClass('ui-state-empty');

            if (opts.hideLabel === true)
            {
                opts.$label.show();
            }
        }
        else
        {
            if (opts.hideLabel === true)
            {
                opts.$label.hide();
            }

            self.element.removeClass('ui-state-empty');
        }
    },

    /************************
     * Public methods
     *
     */
    isEnabled: function() {
        return this.options.enabled;
    },

    isValid: function() {
        return this.options.valid;
    },

    enable: function()
    {
        if (! this.options.enabled)
        {
            this.options.enabled = true;
            this.element.removeClass('ui-state-disabled')
                        .removeAttr('disabled');
            this.options.$label
                        .removeClass('ui-state-disabled')
                        .removeAttr('disabled');

            try {
                //this.element.trigger('enabled.uiinput');
                this._trigger('enabled');
            } catch(e) {}
        }
    },

    disable: function()
    {
        if (this.options.enabled)
        {
            this.options.enabled = false;
            this.element.attr('disabled', true)
                        .addClass('ui-state-disabled');
            this.options.$label
                        .attr('disabled', true)
                        .addClass('ui-state-disabled');

            try {
                //this.element.trigger('disabled.uiinput');
                this._trigger('disabled');
            } catch(e) {}
        }
    },

    /** @brief  Reset the input to its original (creation or last direct set)
     *          value.
     */
    reset: function()
    {
        // Restore the original value
        this.val( this.element.data('value.uiinput') );

        this.element
                .removeClass('ui-state-error ui-state-valid ui-state-changed');

        // Invoke '_blur' which will cause a re-validation.
        this._blur();

        // On reset, don't leave anything marked error, valid OR changed.
        this.element
                .removeClass('ui-state-error ui-state-valid ui-state-changed');
    },

    /** @brief  Has the value of this input changed from its original?
     *
     *  @return true | false
     */
    hasChanged: function()
    {
        return (this.val() !== this.element.data('value.uiinput'));
    },

    /** @brief  Set the current validation state.
     *  @param  state   The new state:
     *                      undefined   undetermined
     *                      true        valid
     *                      false       invalid
     *                      string      invalid, error message
     */
    valid: function(state)
    {
        var self    = this;
        var opts    = this.options;
        if (state === opts.valid)
        {
            return;
        }

        // Clear out validation information
        self.element
                .removeClass('ui-state-error ui-state-valid ui-state-changed');

        var hasVal  = ( opts.$validation.length > 0);

        if ( hasVal )
        {
            opts.$validation
                .html('&nbsp;')
                .removeClass('ui-state-invalid ui-state-valid');
        }

        if (state === true)
        {
            // Valid
            self.element.addClass(   'ui-state-valid');

            if ( hasVal )
            {
                opts.$validation
                            .addClass(   'ui-state-valid');
            }
        }
        else if (state !== undefined)
        {
            // Invalid, possibly with an error message
            self.element.addClass(   'ui-state-error');

            if ( hasVal )
            {
                opts.$validation.addClass(   'ui-state-invalid');

                if (typeof state === 'string')
                {
                    opts.$validation.html(state);
                }
            }
        }

        if (self.hasChanged())
        {
            self.element.addClass('ui-state-changed');
        }

        opts.valid = state;

        // Let everyone know that the validation state has changed.
        //self.element.trigger('validation_change.uiinput');

        if (state !== undefined)
        {
            try {
                self._trigger('validation_change', null, [state]);
            } catch(e) {}
        }
    },

    getLabel: function()
    {
        return this.options.$label.text();
    },

    setLabel: function(str)
    {
        this.options.$label.text(str);
    },

    getOrigValue: function()
    {
        return this.element.data('value.uiinput');
    },

    /** @brief  This field has been successfully saved.  Update the "original"
     *          value to the current value so changes can be properly
     *          reflected.
     *  @param  noValidation    (Internal use) do NOT perform validation
     *                          [ false ];
     */
    saved: function(noValidation)
    {
        this.element.data('value.uiinput', this.val() );
        if (noValidation !== true)
        {
            // Force valid() to reset any CSS classes
            this.options.valid = undefined;
            this.validate();
        }
    },

    val: function(newVal)
    {
        if (newVal !== undefined)
        {
            newVal = $.trim(newVal);

            // Unset the current validation status
            this.element.removeClass('ui-state-valid');
            delete this.options.valid;

            var ret = this.element.val( newVal );

            // Invoke _blur() to validate
            this._blur();

            /* Do NOT set 'value.uiinput' here.  It's supposed to represent the
             * original value of the input for change purposes.
             */

            return ret;
        }

        return $.trim( this.element.val() );
    },

    validate: function()
    {
        var msg         = [];
        var newState;

        if (this.options.validation === null)
        {
            this.valid( true );
            return;
        }

        if ($.isFunction(this.options.validation))
        {
            var ret = this.options.validation.apply(this.element,
                                                    [this.val()]);
            if (typeof ret === 'string')
            {
                // Invalid with a message
                newState = false;
                msg.push(ret);
            }
            else
            {
                // true | false | undefined
                newState = ret;
            }
        }
        else if (this.options.validation === '!empty')
        {
            newState = ((this.val().length > 0)
                                    ? true
                                    : false);

            if (! newState)
            {
                msg.push('Cannot be empty');
            }
        }

        // Set the new state
        this.valid( ((newState === false) && (msg.length > 0)
                                    ? msg.join('<br />')
                                    : newState) );
    },

    destroy: function() {
        this.options.$validation
                .removeClass( 'ui-state-valid '
                             +'ui-state-invalid ');
        this.options.$label
                .unbind('.uiinput');

        this.element
                .removeClass( 'ui-state-default '
                             +'ui-state-disabled '
                             +'ui-state-hover '
                             +'ui-state-valid '
                             +'ui-state-error '
                             +'ui-state-focus '
                             +'ui-state-active '
                             +'ui-priority-primary '
                             +'ui-priority-secondary ')
                .unbind('.uiinput')
                .removeData('.uiinput');
    }
});


}(jQuery));
/** @file
 *
 *  Based on jquerytag (www.faithkadirakin.com/dev/jquerytag/) adjusted to
 *  fit nicely into jquery.ui, convert "tags" to clickable items and
 *  the option to convert the tag input box to a ui.autocomplete
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.input.js
 *      ui.autocomplete.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false, clearTimeout:false, setTimeout:false */
(function($) {

$.widget("ui.tagInput", $.ui.input, {
    version: "0.0.3",
    options: {
        // tagInput Defaults
        separator:      ',',
        unique:         true,
        addOnEnter:     true,

        height:         'height',   /* How should the height of the replacement
                                     * element be specified:
                                     *  'none'          do NOT use height;
                                     *  'cssHeight'     use the CSS height;
                                     *  'height'        use 'innerHeight' and
                                     *                  set as 'height';
                                     *  'min-height'    use 'innerHeight' and
                                     *                  set as 'min-height';
                                     */
        width:          'cssWidth', /* How should the width of the replacement
                                     * element be specified:
                                     *  'none'          do NOT use width;
                                     *  'cssWidth'      use the CSS width;
                                     *  'width'         use 'innerWidth' and
                                     *                  set as 'width';
                                     *  'min-width'     use 'innerWidth' and
                                     *                  set as 'min-width';
                                     */

        cssClass:       {
            container:  'tagInput ui-corner-all ui-state-default',
            origInput:  'rawInput',
            list:       'tagList',
            item:       'tag',
            remove:     'delete',
            activeInput:'activeInput',
            measure:    'measureInput'
        },

        /* If autocompletion is desired, 'autocomplete' can be used to pass
         * the desired options to ui.autocomplete.  If false, no autocompletion
         * will be used.
         *
         * One additional autocomplete parameter that is NOT used by
         * ui.autocomplete:
         *  addOnSelect     - should an item selected from the autocompletion
         *                    menu be automatically added (true) or just
         *                    completed into the current input area (false)
         *                    [ true ];
         */
        autocomplete:   false
    },

    /** @brief  Initialize a new instance.
     */
    _init: function() {
        var self        = this;
        var opts        = self.options;

        self.trimRe = new RegExp('^\\s+|\\s+$', 'g');
        self.sepRe  = new RegExp('\\s*'+ opts.separator +'\\s*');
        opts.tags   = [];
        opts.tagStr = '';

        // Assemble the widget structure
        self.$container = $('<div />').addClass(opts.cssClass.container)
                                      .addClass( self.element.attr('class') )
                                      .insertBefore( self.element );

        // Move any label INTO $container
        self.$label     = self.element.siblings('label')
                                      .appendTo( self.$container );

        // Add a <ul> above the <input> to hold converted tags as <li> items
        self.$tags = $('<ul/>').addClass(opts.cssClass.list)
                               .appendTo( self.$container )

        /* Ensure that self.$tags mirrors the original size of self.element
         * and then hide self.element.
         */
        self._resize();
        self.tabIndex = self.element.attr('tabIndex');
        self.element.hide();


        // Include an li to hold the active input control.
        self.$inputLi   = $('<li/>').addClass(opts.cssClass.activeInput)
                                    .appendTo( self.$tags );
        self.$input     = $('<input type="text" />')
                                    .appendTo( self.$inputLi );
        if (self.tabIndex)
        {
            self.$input.attr('tabIndex', self.tabIndex);
        }

        /* Include a hidden li that will be usee to determine the proper width
         * given the current input characters.
         *
         * Start by attempting to measure 'm', which will be used as the
         * minimum width.  This MAY not work if the widget is contained
         * in a dialog or other collapsable item that may not yet be displayed.
         */
        self.$measure = $('<div />')
                                .addClass(opts.cssClass.measure)
                                .appendTo( self.$inputLi );
        self.mWidth = self.$measure.html('m').width() + 2;
        self.$measure.html('');

        /*
        $.log("ui.tagInput::_init: mWidth[ "+ self.mWidth +" ]");
        // */

        self.$input.width( self.mWidth );
        self.$inputLi.hide();


        // Setup autocompletion if needed.
        self._setupAutocomplete();

        // Establish our initial value
        self.origValue = self.element.val();
        self.val( self.origValue );

        // Invoke our super-class (which SHOULD invoke _bindEvents()
        $.ui.input.prototype._init.apply(this, arguments);
    },

    /** @brief  Resize our input area to match the original.
     *
     *  @return this for a fluent interface
     */
    _resize:    function() {
        var self    = this;
        var opts    = self.options;
        var width   = (opts.width === 'cssWidth'
                        ? self.element.css('width')
                        : self.element.innerWidth());
        var height  = (opts.height === 'cssHeight'
                        ? self.element.css('height')
                        : self.element.innerHeight());

        if ((opts.width !== 'none') && width)
        {
            self.$container.css( (opts.width === 'min-width'
                                ? 'min-width'
                                : 'width'), width );
        }
        if ((opts.height !== 'none') && height)
        {
            self.$tags.css( (opts.height === 'min-height'
                                ? 'min-height'
                                : 'height'), height );
        }

        return;
    },

    /** @brief  If 'autocomplete' options have been provided, setup
     *          autocompletion on self.$input.
     */
    _setupAutocomplete: function() {
        var self    = this;
        var opts    = self.options;
        if (! opts.autocomplete)
        {
            return;
        }

        var acOpts  = (opts.autocomplete !== true
                            ? opts.autocomplete
                            : {});

        acOpts.position = $.extend({my:         'left top',
                                    at:         'left bottom',
                                    collision:  'none',
                                    of:         self.$inputLi},
                                    (acOpts.position === undefined
                                        ? {}
                                        : acOpts.position));

        // When an autocompletion item is selected, 
        acOpts.select = function(e, ui) {
            /*
            $.log("ui.tagInput::_acSelect: val[ "+ ui.item.value +" ]");
            // */

            self.$input.val( ui.item.value );

            /* Ensure that our input is the proper size for the selected value
             * and then focus
             */
            self.$input.trigger('resize')
                       .focus();

            if (acOpts.addOnSelect !== false)
            {
                setTimeout(function() {
                    self._squelchBlur = false;

                    // Blur to add the new item...
                    self.$input.blur();

                    // Re-focus so the user can continue with input.
                    self.element.focus();
                }, 10);
            }
        };

        /* When we focus on the autocompletion menu, squelch our handling of
         * the corresponding blur event
         */
        acOpts.focus = function(e, ui) {
            //$.log("ui.tagInput::_acFocus: ");
            self._squelchBlur = true;
        };

        self.$input.autocomplete( acOpts );
    },

    /** @brief  Establish any needed event handlers.
     */
    _bindEvents: function() {
        var self    = this;
        var opts    = self.options;
        var keyCode = $.ui.keyCode;

        var _click    = function(e) {
            // Trigger 'focus'
            self.element.focus();
        };
        var _focus    = function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            self._squelchBlur = false;

            self._activeInput_show();
        };

        var _inputWidth = function() {
            var val     = self.$input.val();

            // Assign to the measuring li
            self.$measure.html( val );
            var width   = self.$measure.width() + self.mWidth;

            /*
            $.log("ui.tagInput::_inputWidth: val[ "+ val   +" ], "
                           + "width[ "+ width +" ]");
            // */

            self.$input.width( width );
        };

        var _inputKeyup     = function(e) {
            /*
            $.log("ui.tagInput::_inputKeyup: "
                    + "val[ "+ self.$input.val() +" ]");
            // */

            _inputWidth();

            self._squelchBlur = false;
        };
        var _inputKeydown   = function(e) {
            /*
            $.log("ui.tagInput::_inputKeydown: "
                    + "val[ "+ self.$input.val() +" ]");
            // */

            _inputWidth();

            var key = e.keyCode || e.which;
            var val = self.$input.val().replace(self.trimRe, '');
            if (val.length < 1)
            {
                var squelch = true;

                self._squelchBlur = true;
                switch (key)
                {
                case keyCode.BACKSPACE:
                    self.$inputLi.prev().remove();
                    self._updateTags();
                    break;

                case keyCode.DELETE:
                    self.$inputLi.prev().remove();
                    self._updateTags();
                    break;

                case keyCode.LEFT:  // left arrow
                case keyCode.UP:    // up   arrow
                    // Move the input area to the left
                    self.$inputLi.prev().before( self.$inputLi );
                    self.$input.focus();
                    break;

                case keyCode.RIGHT: // right arrow
                case keyCode.DOWN:  // down  arrow
                    // Move the input area to the right
                    self.$inputLi.next().after( self.$inputLi );
                    self.$input.focus();
                    break;

                default:
                    squelch = false;
                }
                //self._squelchBlur = false;

                if (squelch)
                {
                    e.preventDefault();
                    //e.stopPropagation();
                    //e.stopImmediatePropagation();
                    return false;
                }
            }
        };
        var _inputKeypress  = function(e) {
            /*
            $.log("ui.tagInput::_inputKeypress: "
                    + "val[ "+ self.$input.val() +" ]");
            // */

            var key = e.keyCode || e.which;
            var val = self.$input.val().replace(self.trimRe, '');

            if ( (String.fromCharCode(key) === opts.separator) ||
                 (key                      === opts.separator) ||
                 (opts.addOnEnter && (key  === keyCode.ENTER)) )
            {
                if ( (! self.addTag()) && (key === keyCode.ENTER))
                {
                    // Trigger 'ENTER' on the original element
                    self.element.trigger(e);
                }
                e.preventDefault();
                return false;
            }
        };
        var _inputBlur      = function(e) {
            /*
            $.log("ui.tagInput::_inputBlur: "
                    + "val[ "
                    + (self.$input ? self.$input.val() : 'null') +" ]");
            // */

            if (self._squelchBlur === true)
            {
                // Don't process 'blur' if we're in a keydown handler
                self._squelchBlur = false;

                /*
                $.log("ui.tagInput::_inputBlur: squelch");
                // */
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                self.$input.focus();

                return false;
            }

            self.addTag();

            // Hide the active input element
            self._activeInput_hide();
        };

        // Event bindings
        self.element
                .bind('focus.uitaginput',   _focus);

        self.$container
                .bind('click.uitaginput',   _click);

        // Delegate relavant input events
        self.$tags.delegate('.activeInput','keydown.uitaginput', _inputKeydown)
                  .delegate('.activeInput','keyup.uitaginput',   _inputKeyup)
                  .delegate('.activeInput','keypress.uitaginput',_inputKeypress)
                  .delegate('.activeInput','blur.uitaginput',    _inputBlur)
                  .delegate('.activeInput','resize.uitaginput',  _inputWidth);

        // Finally, invoke our super-class
        $.ui.input.prototype._bindEvents.apply(this, arguments);
    },

    /** @brief  Show the active input element.
     */
    _activeInput_show: function() {
        var self    = this;
        var opts    = self.options;
        if (opts.enabled)
        {
            // Hide the label and show the active input element
            self.$label.hide();
            self.$inputLi.show();

            if (self.mWidth < 3)
            {
                // Attempt to take a valid measurement of 'm'
                self.mWidth = self.$measure.html('m').width() + 2;
                self.$measure.html('');

                /*
                $.log("ui.tagInput::_activeInput_show: "
                      + "mWidth[ "+ self.mWidth +" ]");
                // */
            }

            /*
            $.log("ui.tagInput::_activeInput_show: focus on $input");
            // */

            self.$container.addClass('ui-state-focus ui-state-active');
            self.$input.focus();
        }

        return this;
    },

    /** @brief  Hide the active input element.
     */
    _activeInput_hide: function() {
        // Hide the active input element
        this.$inputLi.hide();
        this.$container.removeClass('ui-state-focus ui-state-active');

        if (this.options.tagStr.length < 1)
        {
            // Re-show the label 
            this.$label.show();
        }

        return this;
    },

    /** @brief  Given the text of a new tag, if it is not empty, create
     *          a new DOM element representing the new tag.
     *  @param  tag     The text of the new tag;
     *
     *  @return The new DOM element (or undefined if not a valid tag);
     */
    _createTag: function(tag) {
        var self    = this;
        var opts    = self.options;
        var val     = tag.replace(self.trimRe, '');

        /*
        $.log("ui.tagInput::_createTag: "
                + "tag[ "+ tag +" ], val[ "+ val +" ]");
        // */


        if ( (val.length < 1) ||
             (opts.unique && ($.inArray(val, opts.tags) > -1)) )
        {
            // Empty or duplicate tag -- do NOT create a tag element.

            if ((val.length > 0) && self.$tags.effect)
            {
                /* This tag already exists AND we have effects available.
                 *
                 * Highlight the original tag that this new input is a
                 * duplicate of.
                 *
                 */
                var $tag    = self.$tags.find('.'+ opts.cssClass.item
                                              +' span:contains('+ val +')');
                $tag.parent().effect('highlight');
            }
            return;
        }

        // Create a new DOM element for this tag
        var $tag    = $('<li />')
                            .addClass( opts.cssClass.item );
        var $span   = $('<span />')
                            .text( val )
                            .appendTo($tag);
        var $close  = $('<a />')
                            .addClass( opts.cssClass.remove
                                       +' ui-icon ui-icon-close' )
                            .html( '&nbsp;' )
                            .appendTo($tag)
                            .click(function(e) {
                                    e.preventDefault();
                                    $tag.remove();
                                    self._updateTags();
                                });

        self.$tags.append( $tag );

        return $tag;
    },

    /** @brief  Update the tags and tagStr based upon the current items in
     *          $tags
     */
    _updateTags: function() {
        var self    = this;
        var opts    = self.options;

        opts.tags   = [];
        self.$tags.find('.'+opts.cssClass.item +' > span')
                  .each(function() {
            opts.tags.push( $(this).html() );
        });
        opts.tagStr = opts.tags.join( opts.separator );

        // Mirror tagStr in the underlying input element
        self.element.val( opts.tagStr );

        if ((! self.$inputLi.is(':visible')) && (opts.tags.length < 1))
        {
            // Show the label
            self.$label.show();
        }
        else if (opts.tags.length > 0)
        {
            // Hide the label
            self.$label.hide();
        }

        /* Since we control tags that are entered, mark the underlying element
         * as valid for the sake of widgets that include the original element
         * and expect it to use '.ui-state-valid' to indicate validity.
         */
        self.element.trigger('validate');

        // Trigger a 'change' event
        self._trigger('change');

        /*
        $.log('ui.tagInput::_updateTags: tagStr[ '+ opts.tagStr +' ]');
        // */
    },

    /************************
     * Public methods
     *
     */

    /** @brief  Add the given tag to the list.
     *  @param  val     The tag to add.
     *
     *  @return tru/false indicating whether a tag was added.
     */
    addTag: function(val) {
        var self    = this;
        var opts    = self.options;

        if (val === undefined)
        {
            val = (self.$input ? $.trim(self.$input.val()) : '');

            self.$measure.html( '' );

            // Reset the input value
            self.$input.val('').width( self.mWidth );
        }

        /*
        $.log("ui.tagInput::addTag: "
                + "val[ "+ val +" ]");
        // */

        var $tag    = self._createTag( val );
        if ($tag)
        {
            // Insert the new tag
            self.$input.closest('li').before( $tag );

            // Re-focus the input
            self.$input.focus();

            self._updateTags();
        }

        return ($tag ? true : false);
    },

    /** @brief  Delete the given tag from the list.
     *  @param  val     The tag to delete.
     *
     *  @return this for a fluent interface
     */
    deleteTag: function(val) {
        var self    = this;
        var opts    = self.options;

        /*
        $.log("ui.tagInput::deleteTag: "
                + "val[ "+ val +" ]");
        // */

        var pos = $.inArray(val, opts.tags);
        if (pos < 0)
        {
            return this;
        }

        self.$tags.find('.'+opts.cssClass.item).eq(pos).remove();
        self._updateTags();
    },

    /** @brief  Enable this control.
     *
     *  @return this for a fluent interface.
     */
    enable: function() {
        if (! this.options.enabled)
        {
            this.options.enabled = true;

            this.$container.removeClass('ui-state-disabled')
                           .removeAttr('disabled');
            this.$label.removeClass('ui-state-disabled')
                       .removeAttr('disabled');

            this._trigger('enabled');
        }

        return this;
    },

    /** @brief  Disable this control.
     *
     *  @return this for a fluent interface.
     */
    disable: function() {
        var opts    = this.options;
        if (opts.enabled)
        {
            opts.enabled = false;
            this.$container.attr('disabled', true)
                           .addClass('ui-state-disabled');
            this.$label.attr('disabled', true)
                       .addClass('ui-state-disabled');

            if (this.$inputLi.is(':visible'))
            {
                this._squelchBlur = true;
                this._activeInput_hide();
                this._squelchBlur = false;
            }

            this._trigger('disabled');
        }

        return this;
    },

    /** @brief  Reset the input to its original (creation or last direct set)
     *          value.
     *
     *  @return this for a fluent interface.
     */
    reset: function() {
        // Restore the original value
        this.val( this.origValue );

        this.$container
                .removeClass('ui-state-error ui-state-valid ui-state-changed');

        return this;
    },

    /** @brief  Has the value of this input changed from its original?
     *
     *  @return true | false
     */
    hasChanged: function() {
        return (this.val() !== this.origValue);
    },

    /** @brief  Override jQuery-ui option() so we can return 'term' as the
     *          value of the activeInput.
     *  @param  key     The desired option;
     *  @param  value   If provided, the new value;
     *
     *  @return this for a fluent interface.
     */
    option: function(key, value) {
        if ((key   === undefined) ||    // retrieve all
            (value !== undefined) ||    // set
            (typeof key !== 'string'))  // set via object
        {
            // Let the super-class handle this.
            return $.ui.input.prototype.option.apply(this, arguments);
        }

        var ret;
        switch (key)
        {
        case 'term':
            ret = this.term();
            break;

        default:
            ret = this.options[ key ];
            break;
        }

        return ret;
    },

    /** @brief  Retrieve the current value of the active input.
     *
     *  @return The current value.
     */
    term: function() {
        var self    = this;
        var opts    = self.options;
        var val     = '';
        if (self.$inputLi.is(':visible'))
        {
            val = self.$input.val();
        }

        return val;
    },

    /** @brief  Set or retrieve the current value of the tag list.
     *  @param  newVal  If provided, the new value of the tag list
     *                  (a string of items separated by opts.separator).
     *
     *  @return The current/new value.
     */
    val: function(newVal) {
        var self    = this;
        var opts    = self.options;

        if (newVal !== undefined)
        {
            // Unset the current validation status
            self.element.removeClass('ui-state-valid');
            delete self.options.valid;

            var tags    = $.trim(newVal).split( self.sepRe );

            // Empty our current tag state
            opts.tags   = [];
            opts.tagStr = '';
            self.$tags.find('.tag').remove();

            // Add new tag items for each new tag
            $.each(tags, function() {
                self.addTag( this );
            });

            // Set our current tag state
            opts.tags   = tags;
            opts.tagStr = opts.tags.join( opts.separator );

            // Mirror tagStr in the underlying input element
            self.element.val( opts.tagStr );
        }
        /*
        else
        {
            $.log('ui.tagInput::val(): [ '+ opts.tagStr +' ]');
        }
        // */

        return opts.tagStr;
    },

    /** @brief  Destroy an instance.
     */
    destroy: function() {
        var self        = this;
        var opts        = self.options;

        // Unbind
        self.element.unbind('.uitaginput');
        self.$tags.undelegate('input', '.uitaginput');
        self.$container.unbind('.uitaginput');

        /* Move the label back before the original element and ensure that both
         * the label and element are visible.
         */
        self.$label.insertBefore( self.element ).show();
        self.element.show();

        // Remove our container and everything in it.
        self.$container.remove();

        // Invoke our super-class
        $.ui.input.prototype.destroy.apply(this, arguments);
    }
});


}(jQuery));



/*!
 * jQuery UI Stars v2.1.1
 * http://plugins.jquery.com/project/Star_Rating_widget
 *
 * Copyright (c) 2009 Orkan (orkans@gmail.com)
 * Dual licensed under the MIT and GPL licenses.
 * http://docs.jquery.com/License
 *
 * $Rev: 114 $
 * $Date:: 2009-06-12 #$
 * $Build: 32 (2009-06-12)
 *
 * Take control of pre-assembled HTML:
 *  <div >
 *    <input class='ui-stars-rating' type='hidden' name='rating' value='...' />
 *    <div class='ui-stars ui-stars-cancel ...'><a ..></a></div>
 *    <div class='ui-stars ui-stars-star ...'><a ..></a></div>
 *    <div class='ui-stars ui-stars-star ...'><a ..></a></div>
 *    ...
 *  </div>
 *
 * Depends:
 *      ui.core.js
 *      ui.widget.js
 *
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($) {

$.widget("ui.stars", {
  version: "2.1.1c",

  /* Remove the strange ui.widget._trigger() class name prefix for events.
   *
   * If you need to know which widget the event was triggered from, either
   * bind directly to the widget or look at the event object.
   */
  widgetEventPrefix:    '',

  options: {
    // Defaults
    inputType: "div", // radio|select
    split: 0,
    disabled: false,
    cancelTitle: "Cancel Rating",
    cancelValue: 0,
    cancelShow: true,
    oneVoteOnly: false,
    showTitles: false,
    captionEl: null,
    callback: null, // function(ui, type, value, event)

    /*
     * CSS classes
     */
    starWidth: 16,
    baseClass:   'ui-stars',            // Included for all star/cancel items
    cancelClass: 'ui-stars-cancel',
    starClass: 'ui-stars-star',
    starOnClass: 'ui-stars-star-on',
    starHoverClass: 'ui-stars-star-hover',
    starDisabledClass: 'ui-stars-star-disabled',
    cancelHoverClass: 'ui-stars-cancel-hover',
    cancelDisabledClass: 'ui-stars-cancel-disabled'
  },

  _create: function() {
    var self = this, o = this.options, id = 0;

    //this.$stars  = $('.'+o.baseClass,   this.element);
    this.$stars  = $('.'+o.starClass,   this.element);
    this.$cancel = $('.'+o.cancelClass, this.element);
    this.$input  = $('input[type=hidden]:first', this.element);

    // How many Stars and how many are 'on'?
    o.items = this.$stars.filter('.'+o.starClass).length;
    o.value = this.$stars.filter('.'+o.starOnClass).length; // - 1;
    if (o.value > 0) {
        o.checked = o.defaultValue = o.value;
    } else {
        o.value = o.defaultValue = o.cancelValue;
    }

    if (o.disabled) {
        this.$cancel.addClass(o.cancelDisabledClass);
    }

    //o.cancelShow &= !o.disabled && !o.oneVoteOnly;
    o.cancelShow &= !o.oneVoteOnly;
    //o.cancelShow && this.element.append(this.$cancel);

    /*
     * Star selection helpers
     */
    function fillNone() {
      self.$stars.removeClass(o.starOnClass + " " + o.starHoverClass);
      self._showCap("");
    }

    function fillTo(index, hover) {
      if(index >= 0) {
        var addClass = hover ? o.starHoverClass : o.starOnClass;
        var remClass = hover ? o.starOnClass    : o.starHoverClass;

        self.$stars.eq(index)
                      .removeClass(remClass)
                      .addClass(addClass)
                    .prevAll("." + o.starClass)
                      .removeClass(remClass)
                      .addClass(addClass);
        //             .end()
        //            .end()
        self.$stars.eq(index)
                    .nextAll("." + o.starClass)
                     .removeClass(o.starHoverClass + " " + o.starOnClass);

        self._showCap(self.$stars.eq(index).find('a').attr('title'));
      }
      else {
          fillNone();
      }
    }


    /*
     * Attach stars event handler
     */
    this.$stars.bind("click.stars", function(e) {
      if(!o.forceSelect && o.disabled) {
        return false;
      }

      var i = self.$stars.index(this);
      o.checked = i;
      o.value   = i + 1;
      o.title   = $(this).find('a').attr('title');

      self.$input.val(o.value);

      fillTo(o.checked, false);
      self._disableCancel();

      if (!o.forceSelect)
      {
        self.callback(e, "star");
      }

      self._trigger('change', null, o.value);
    })
    .bind("mouseover.stars", function() {
      if(o.disabled) {
        return false;
      }
      var i = self.$stars.index(this);
      fillTo(i, true);
    })
    .bind("mouseout.stars", function() {
      if(o.disabled) {
        return false;
      }
      fillTo(o.checked, false);
    });


    /*
     * Attach cancel event handler
     */
    this.$cancel.bind("click.stars", function(e) {
      if(!o.forceSelect && (o.disabled || o.value === o.cancelValue))
      {
        return false;
      }

      o.checked = -1;
      o.value   = o.cancelValue;

      self.$input.val(o.cancelValue);

      fillNone();
      self._disableCancel();

      if (!o.forceSelect)
      {
        self.callback(e, "cancel");
      }

      self._trigger('change', null, o.value);
    })
    .bind("mouseover.stars", function() {
      if(self._disableCancel()) {
        return false;
      }
      self.$cancel.addClass(o.cancelHoverClass);
      fillNone();
      self._showCap(o.cancelTitle);
    })
    .bind("mouseout.stars", function() {
      if(self._disableCancel()) {
        return false;
      }
      self.$cancel.removeClass(o.cancelHoverClass);
      self.$stars.triggerHandler("mouseout.stars");
    });

    /*
     * Clean up to avoid memory leaks in certain versions of IE 6
     */
    $(window).unload(function(){
      self.$cancel.unbind(".stars");
      self.$stars.unbind(".stars");
      self.$stars = self.$cancel = null;
    });



    /*
     * Finally, set up the Stars
     */
    this.select(o.value);
    if (o.disabled)
    {
        this.disable();
    }

  },

  /*
   * Private functions
   */
  _disableCancel: function() {
    var o        = this.options,
        disabled = o.disabled || o.oneVoteOnly || (o.value === o.cancelValue);

    if(disabled) {
        this.$cancel.removeClass(o.cancelHoverClass)
                    .addClass(o.cancelDisabledClass);
    }
    else {
        this.$cancel.removeClass(o.cancelDisabledClass);
    }

    this.$cancel.css("opacity", disabled ? 0.5 : 1);
    return disabled;
  },
  _disableAll: function() {
    var o = this.options;
    this._disableCancel();
    if(o.disabled) {this.$stars.filter("div").addClass(o.starDisabledClass);}
    else           {this.$stars.filter("div").removeClass(o.starDisabledClass);}
  },
  _showCap: function(s) {
    var o = this.options;
    if(o.captionEl) {o.captionEl.text(s);}
  },

  /*
   * Public functions
   */
  value: function() {
    return this.options.value;
  },
  select: function(val) {
    var o = this.options,
        e = (val === o.cancelValue)
                ? this.$cancel : this.$stars.eq(val - 1);

    o.forceSelect = true;
    e.triggerHandler("click.stars");
    o.forceSelect = false;
  },
  selectID: function(id) {
    var o = this.options, e = (id === -1) ? this.$cancel : this.$stars.eq(id);
    o.forceSelect = true;
    e.triggerHandler("click.stars");
    o.forceSelect = false;
  },
  enable: function() {
    this.options.disabled = false;
    this._disableAll();
    this.element.removeClass('ui-state-disabled');

    // (Re)add the 'title' to all star controls
    this.$stars.each(function() {
        var $a      = $(this).find('a');
        var title   = $a.data('star-title');
        if (title)
        {
            $a.attr('title', title);
        }
    });
  },
  disable: function() {
    this.options.disabled = true;
    this._disableAll();
    this.element.addClass('ui-state-disabled');

    // Remove the 'title' from all star controls
    this.$stars.each(function() {
        var $a      = $(this).find('a');
        $a.data('star-title', $a.attr('title'));
        $a.removeAttr('title');
    });
  },
  hasChanged: function() {
    return (this.options.value !== this.options.defaultValue);
  },
  reset: function() {
    this.select( this.options.defaultValue );
  },
  destroy: function() {
    this.$cancel.unbind(".stars");
    this.$stars.unbind(".stars");
    this.element.unbind(".stars").removeData("stars");
  },
  callback: function(e, type) {
    var o = this.options;
    if (o.callback)
    {
        o.callback(this, type, o.value, e);
    }
    if (o.oneVoteOnly && !o.disabled)
    {
        this.disable();
    }
  }
});

}(jQuery));
/*
 * jQuery Notify UI Widget 1.2.2
 * Copyright (c) 2010 Eric Hynds
 *
 * http://www.erichynds.com/jquery/a-jquery-ui-growl-ubuntu-notification-widget/
 *
 * Depends:
 *   - jQuery 1.4
 *   - jQuery UI 1.8 widget factory
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($){

$.widget("ui.notify", {
	options: {
		speed: 500,
		expires: 5000,
		stack: 'below'
	},
	_create: function(){
		var self = this;
		this.templates = {};
		this.keys = [];
		
		// build and save templates
		this.element.addClass("ui-notify").children().addClass("ui-notify-message").each(function(i){
			var key = this.id || i;
			self.keys.push(key);
			self.templates[key] = $(this).removeAttr("id").wrap("<div></div>").parent().html(); // because $(this).andSelf().html() no workie
		}).end().empty();

        /* Ensure the notification container is NOT hidden.  It MAY be set
         * 'display:none;' to avoid visibility before CSS styles are completely
         * loaded and we've completed the instantiation, but from here on it
         * needs to be visible.
         */
		this.element.show();
	},
	create: function(template, msg, opts){
		if(typeof template === "object"){
			opts = msg;
			msg = template;
			template = null;
		}
		
		// return a new notification instance
		return new $.ui.notify.instance(this)._create(msg, $.extend({}, this.options, opts), this.templates[ template || this.keys[0]]);
	}
});

// instance constructor
$.extend($.ui.notify, {
	instance: function(widget){
		this.parent = widget;
		this.isOpen = false;
	}
});

// instance methods
$.extend($.ui.notify.instance.prototype, {
	_create: function(params, options, template){
		this.options = options;
		
		var self = this,
			
			// build html template
			html = template.replace(/#(?:\{|%7B)(.*?)(?:\}|%7D)/g,
                                    function($1, $2){
				                        return ($2 in params)
                                                ? params[$2]
                                                : '';
			                        }),
			
			// the actual message
			m = (this.element = $(html)),
			
			// close link
			closelink = m.find("a.ui-notify-close");
		
		// fire beforeopen event
		if(this._trigger("beforeopen") === false){
			return;
		}

		// clickable?
		if(typeof this.options.click === "function"){
			m.addClass("ui-notify-click").bind("click", function(e){
				self._trigger("click", e, self);
			});
		}
		
		// show close link?
		if(closelink.length && !!options.expires){
			closelink.remove();
		} else if(closelink.length){
			closelink.bind("click", function(){
				self.close();
				return false;
			});
		}
		
		this.open();
		
		// auto expire?
		if(typeof options.expires === "number"){
			window.setTimeout(function(){
				self.close();
			}, options.expires);
		}
		
		return this;
	},
	close: function(){
		var self = this, speed = this.options.speed;
		this.isOpen = false;
		
		this.element.fadeTo(speed, 0).slideUp(speed, function(){
			self._trigger("close");
		});
		
		return this;
	},
	open: function(){
		if(this.isOpen){
			return this;
		}
		
		var self = this;
		this.isOpen = true;
		
		this.element[this.options.stack === 'above'
                        ? 'prependTo'
                        : 'appendTo'](this.parent.element)
                .css({ display:"none", opacity:"" })
                .fadeIn(this.options.speed, function(){
			        self._trigger("open");
		        });
		
		return this;
	},
	widget: function(){
		return this.element;
	},
	_trigger: function(type, e, instance){
		return this.parent._trigger.call( this, type, e, instance );
	}
});

}(jQuery));
/** @file
 *
 *  Javascript interface for a confirmation mini-dialog.
 *
 *  The mini-dialog will be place over the element used in it's creation.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("ui.confirmation", {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:      '',

    options: {
        question:   'Really?',
        answers:    {
            confirm:    'Yes',
            cancel:     'No'
        },
        primary:    'cancel',   // Which button receives 'Enter',
                                // ('confirm' or 'cancel')?
        position:   {
            my: 'center middle',
            at: 'center middle'
        },

        // Completion callbacks
        confirmed:  function() {},
        canceled:   function() {},
        closed:     function() {}
    },

    /** @brief  Create a new instance.
     *
     *  @triggers:
     *      'enabled'
     *      'disabled'
     */
    _init: function()
    {
        var self    = this;
        var opts    = self.options;

        // Position the confirmation relative to the target element.
        if (opts.position.of === undefined)
        {
            opts.position.of = self.element;
        }

        /* Figure out the z-index that will allow the confirmation to appear
         * above all others.
         */
        var zIndex  = self.element.maxZindex();


        // Present a confirmation mini-dialog.
        var html    = '<div class="ui-confirmation">'
                    /*
                    +  '<span class="ui-icon ui-icon-alert" '
                    +        'style="float:left; margin:0 7px 20px 0;">'
                    +  '</span>'
                    */
                    +  opts.question +'<br />'
                    +  '<button name="yes" class="'
                    +       (opts.primary === 'confirm'
                                ? 'ui-priority-primary'
                                : 'ui-priority-secondary')
                    +       '">'+ opts.answers.confirm +'</button>'
                    +  '<button name="no" class="'
                    +       (opts.primary !== 'confirm'
                                ? ' class="ui-priority-primary"'
                                : 'ui-priority-secondary')
                    +       '">'+ opts.answers.cancel  +'</button>'
                    + '</div>';
        opts.$dialog = $(html).css({'position': 'absolute',
                                    'z-index':  zIndex + 1})
                              .appendTo('body')
                              .position( opts.position );

        self.element.attr('disabled', true);

        /********************************
         * Locate our pieces.
         *
         */
        opts.$confirm = opts.$dialog.find('button[name=yes]');
        opts.$cancel  = opts.$dialog.find('button[name=no]');
        
        /********************************
         * Bind to interesting events.
         *
         */
        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function()
    {
        var self        = this;
        var opts        = self.options;

        opts.$confirm.bind('click.confirmation', function(e) {
            self._trigger('confirmed');

            opts.$dialog.remove();
            self._trigger('closed');
            self.destroy();
        });
        opts.$cancel.bind('click.confirmation', function() {
            self._trigger('canceled');

            opts.$dialog.remove();
            self._trigger('closed');
            self.destroy();
        });

        // Handle 'ESC' as 'cancel'
        $(document).bind('keydown.confirmation', function(e) {
            switch (e.keyCode)
            {
            case 13:    // return
                switch (opts.primary)
                {
                case 'confirm':
                    opts.$confirm.click();
                    break;

                case 'cancel':
                default:
                    opts.$cancel.click();
                    break;
                }
                break;

            case 27:    // ESC
                opts.$cancel.click();
                break;
            }
        });
    },

    /************************
     * Public methods
     *
     */

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Cleanup

        // Unbind events
        opts.$confirm.unbind('.confirmation');
        opts.$cancel.unbind('.confirmation');
        $(document).unbind('.confirmation');

        // Remove added elements
        opts.$dialog.remove();
    }
});

}(jQuery));
/** @file
 *
 *  Provide a ui-styled validation form.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.input.js
 *      ui.button.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false, clearTimeout:false, setTimeout:false */
(function($) {

$.widget("ui.validationForm", {
    version: "0.1.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Defaults
        submitSelector: 'button[name=submit]',
                                    /* The jQuery selector for the
                                     * primary submit button
                                     *  [ button[name=submit] ];
                                     */
        hideLabels:     true,       /* Should the input labels be hidden / used
                                     * to present a default value for the field
                                     * [ true ];
                                     */
        disableSubmitOnUnchanged:
                        true,       /* Should the submit button be disabled
                                     * if the fields are valid but have not
                                     * changed from the initial values
                                     * [ true ];
                                     */
        handleAutofill: false,      /* Should we attempt to handle issues with
                                     * browser auto-fill where input values
                                     * are automatically filled but no
                                     * 'change' or 'update' events are fired?
                                     */

        $status:        null        /* The element to present validation
                                     * information in [:sibling
                                     *                  .ui-form-status]
                                     */
    },

    /** @brief  Initialize a new instance.
     *
     *  Valid options:
     *      $status:        The element to present validation information in
     *                      [ parent().find('.ui-form-status:first) ]
     *
     *  @triggers:
     *      'validation_change' when the validaton state has changed;
     *      'enabled'           when element is enabled;
     *      'disabled'          when element is disabled.
     *      'submit'            When the form is submitted.
     *      'cancel'            if the form has a 'cancel' button and it is
     *                          clicked.
     */
    _init: function()
    {
        var self    = this;
        var opts    = this.options;

        self.element.addClass( 'ui-form');

        if (! $.isFunction(opts.validate))
        {
            opts.validate = function() {
                return self._validate();
            };
        }

        opts.enabled = self.element.attr('disabled') ? false : true;

        if (opts.$status)
        {
            if (opts.$status.jquery === undefined)
            {
                opts.$status = $(opts.$status);
            }
        }
        else
        {
            /* We ASSUME that the form element is contained within a div along
             * with any  associated validation status element.
             *
             * Use the first child of our parent that has the CSS class
             *  'ui-form-status'
             */
            opts.$status = self.element
                                    .parents('.ui-validation-form:first')
                                        .find('.ui-form-status:first');
        }

        opts.$required = self.element.find('.required');
        opts.$inputs   = self.element.find(  'input[type=text],'
                                           + 'input[type=password],'
                                           + 'textarea');
        opts.$buttons  = self.element.find(  'button');

        if (opts.$submit === undefined)
        {
            opts.$submit = self.element.find( opts.submitSelector );
        }
        opts.$cancel   = self.element.find('button[name=cancel]');
        opts.$reset    = self.element.find('button[name=reset]');

        // Instantiate sub-widgets if they haven't already been instantiated
        opts.$inputs.each(function() {
            var $el = $(this);
            if ($el.data('input'))  return;
            $el.input({
                hideLabel:      opts.hideLabels,
                handleAutofill: opts.handleAutofill
            });
        });

        opts.$buttons.each(function() {
            var $el = $(this);
            if ($el.data('button'))  return;
            $el.button();
        });

        opts.$submit.button('disable');

        /*
        opts.$submit.button({priority:'primary', enabled:false});
        opts.$cancel.button({priority:'secondary'});
        opts.$reset.button({priority:'secondary'});
        // */

        self._bindEvents();

        // Perform an initial validation
        self.validate();
    },

    _bindEvents: function()
    {
        var self    = this;
        var opts    = self.options;

        var _validate   = function(e) {
            self.validate();
        };

        var _cancel_click   = function(e, data) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            // :TODO: "Cancel" notification
            self._trigger('canceled', null, data);
            self._trigger('complete');
        };

        var _reset_click   = function(e, data) {
            e.preventDefault();
            e.stopPropagation();

            self.reset();
        };

        opts.$inputs.bind('validation_change.uivalidationform', _validate);
        opts.$cancel.bind('click.uivalidationform',             _cancel_click);
        opts.$reset.bind('click.uivalidationform',              _reset_click);
    },

    /** @brief  Default callback for _trigger('validate')
     */
    _validate: function()
    {
        var self        = this;
        var opts        = self.options;
        var isValid     = true;

        opts.$required.each(function() {
            /*
            $.log( 'ui.validationForm::validate: '
                  +      'name[ '+ this.name +' ], '
                  +     'class[ '+ this.className +' ]');
            // */

            if (! $(this).hasClass('ui-state-valid'))
            {
                isValid = false;
                return false;
            }
        });

        return isValid;
    },

    /************************
     * Public methods
     *
     */
    isEnabled: function()
    {
        return this.options.enabled;
    },

    enable: function()
    {
        if (! this.options.enabled)
        {
            this.options.enabled = true;
            this.element.removeClass('ui-state-disabled')
                        .removeAttr('disabled');
            this.options.$label
                        .removeClass('ui-state-disabled')
                        .removeAttr('disabled');

            //this.element.trigger('enabled.uiinput');
            this._trigger('enabled');
        }
    },

    disable: function()
    {
        if (this.options.enabled)
        {
            this.options.enabled = false;
            this.element.attr('disabled', true)
                        .addClass('ui-state-disabled');
            this.options.$label
                        .attr('disabled', true)
                        .addClass('ui-state-disabled');

            //this.element.trigger('disabled.uiinput');
            this._trigger('disabled');
        }
    },

    /** @brief  Reset any ui.input fields to their original
     *          (creation or direct set) values.
     */
    reset: function()
    {
        var self    = this;
        var opts    = this.options;

        opts.$inputs.input('reset');

        // Perform a validation
        self.validate();
    },

    /** @brief  The form has been successfully submitted/saved so any
     *          "original" values (e.g. in ui.input widgets) should be
     *          updated to allow further edits to properly reflect changes.
     */
    saved: function()
    {
        this.options.$inputs.input('saved');
        this.validate();
    },

    /** @brief  Have any of the ui.input fields changed from their original
     *          values?
     *
     *  @return true | false
     */
    hasChanged: function()
    {
        var self    = this;
        var opts    = this.options;
        var hasChanged  = false;

        // Has anything changed from the forms initial values?
        opts.$inputs.each(function() {
            if ($(this).input('hasChanged'))
            {
                hasChanged = true;
                return false;
            }
        });

        return hasChanged;
    },

    /** @brief  Invoked when additional inputs have been added to the form.
     */
    rebind: function()
    {
        var self    = this;
        var opts    = self.options;

        // Make sure our lists are up-to-date
        opts.$required = self.element.find('.required');
        opts.$inputs   = self.element.find(  'input[type=text],'
                                           + 'input[type=password],'
                                           + 'textarea');

        // Unbind any existing events
        opts.$inputs.unbind('.uivalidationform');

        // and rebind
        self._bindEvents();
    },

    /** @brief  Invoked to perform validation.
     */
    validate: function()
    {
        var self        = this;
        var opts        = self.options;
        var isValid     = opts.validate();

        if (isValid === true)
        {
            opts.$status
                    .removeClass('error')
                    .addClass('success')
                    .text('');
        }
        else
        {
            opts.$status
                    .removeClass('success')
                    .addClass('error');

            if ($.type(isValid) === 'string')
            {
                opts.$status.text( isValid );
            }
        }

        if ((isValid === true) &&
            ( (opts.disableSubmitOnUnchanged === false) || self.hasChanged()) )
        {
            opts.$submit.button('enable');
        }
        else
        {
            opts.$submit.button('disable');
        }
    },

    destroy: function() {
        var self    = this;
        var opts    = self.options;

        self.element.unbind('.uivalidationform');
        opts.$inputs.unbind('.uivalidationform');
        opts.$submit.unbind('.uivalidationform');
        opts.$cancel.unbind('.uivalidationform');
        opts.$reset.unbind('.uivalidationform');

        opts.$inputs.input('destroy');
        opts.$submit.button('destroy');
        opts.$cancel.button('destroy');
        opts.$reset.button('destroy');

        self.element.removeClass('ui-form');
    }
});


}(jQuery));
/** @file
 *
 *  An extension of ui.tabs to allow bookmarkable URLS for the tabs.
 *
 *  For this instance, the href of a tab is the bookmarkable URL while the
 *  tab's related panel and load URL are defined as data items on the tab
 *  anchor.  For example:
 *      <ul>
 *       <li>
 *         <a href='/settings/account'
 *            data-panel.tabs='#account'
 *            data-load.tabs='/settings?format=partial&section=account'>
 *           <span>Account</span>
 *         </a>
 *       </li>
 *       ...
 *      </ul>
 *
 *      <div id='#account'>
 *          ... Account Tab Panel ...
 *      </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.tabs.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, document:false, location:false, window:false */
(function($) {

$.widget('connexions.tabs', $.ui.tabs, {
    version: "0.0.1",
    options: {
        /* These options may also be defined as data items on the tab anchor:
         *  <a href='%tab-href%'
         *     data-panel.tabs='%panel-id%'
         *     data-load.tabs='%panel-load-url%'>
         */
        panelId:    null,   // The ID of the related panel (panel.tabs)
        loadUrl:    null    // The load URL                (load.tabs)
    },

    load: function( index ) {
        index = this._getIndex( index );
        var self = this,
            o = this.options,
            $a = this.anchors.eq( index ),
            a = $a[ 0 ],
            url = $a.data( "load.tabs" );

        this.abort();

        // not remote or from cache
        if ( !url || this.element.queue( "tabs" ).length !== 0 &&
            $a.data( "cache.tabs" ) )
        {
            this.element.dequeue( "tabs" );
            return;
        }

        // load remote from here on
        this.lis.eq( index ).addClass( "ui-state-processing" );

        if ( o.spinner )
        {
            var span = $( "span", a );
            span.data( "label.tabs", span.html() ).html( o.spinner );
        }

        this.xhr = $.ajax( $.extend( {}, o.ajaxOptions, {
            url:        url,
            success:    function( r, s ) {
                // Connexions panelId {
                self._getPanel( $a ).html( r );
                //self.element.find( self._sanitizeSelector( a.hash ) ).html(r);
                // Connexions panelId }

                // take care of tab labels
                self._cleanup();

                if ( o.cache )
                {
                    $a.data( "cache.tabs", true );
                }

                self._trigger( "load", null,
                               self._ui( self.anchors[ index ],
                                         self.panels[ index ] ) );

                try {
                    o.ajaxOptions.success( r, s );
                }
                catch ( e ) {}
            },
            error:      function( xhr, s, e ) {
                // take care of tab labels
                self._cleanup();

                self._trigger( "load", null,
                               self._ui( self.anchors[ index ],
                                          self.panels[ index ] ) );

                try {
                    /* Passing index avoid a race condition when this method is
                     * called after the user has selected another tab.  Pass
                     * the anchor that initiated this request allows loadError
                     * to manipulate the tab content panel via $(a.hash)
                     */
                    o.ajaxOptions.error( xhr, s, index, a );
                }
                catch ( e ) {}
            }
        } ) );

        // last, so that load event is fired before show...
        self.element.dequeue( "tabs" );

        return this;
    },

    /**********************************************************
     * Private methods
     *
     */

    /** @brief  Retrieve the panel related to the tab represented by the
     *          provided anchor.
     *  @param  $a      The tab anchor.
     *
     *  @return The jQuery DOM element of the related tab panel.
     */
    _getPanel: function( $a ) {
        return this.element.find(
                        this._sanitizeSelector( $a.data( 'panel.tabs' )) );
    },

    /** @brief  Retrieve the jQuery DOM element representing the tab panel
     *          related to the indexed tab.
     *  @param  index   The desired/indexed tab.
     *
     *  @return The jQuery DOM element of the related tab panel.
     */
    _getPanelByIndex: function( index ) {
        return this.element.find(this._sanitizeSelector(
                                    $.data( this.anchors[ index ],
                                            'panel.tabs' )) );
    },


    /** @brief  For connexions tabs, if there is a data item 'panel', that
     *          contains the id of the target/inline element.
     *          This allows us to specify a bookmarkable URL as the panel's
     *          href while using an pre-rendered element for the content OR a
     *          second rendering URL.
     *  @param  init    Is this widget initialization?
     *
     *
     *  This is primarily a duplicate of the _tabify method from
     *  jquery.ui.tabs.js with the exception of the 'panelId' handling.
     */
    _tabify: function( init ) {
        var self = this,
            o = this.options,
            fragmentId = /^#.+/; // Safari 2 reports '#' for an empty hash

        this.list       = this.element.find('ol,ul').eq( 0 );
        this.lis        = $( ' > li:has(a[href])', this.list );
        this.anchors    = this.lis.map(function() {
            return $('a', this)[0];
        });
        this.panels = $( [] );

        this.anchors.each(function( i, a ) {
            var $a      = $( a );
            var href    = $a.attr( "href" );

            // Connexions panelId {
            var panelId = $a.data('panel.tabs');

            if (panelId === undefined)
            {
                /* NOT a pre-loaded, inline tab
                 *
                 * For dynamically created HTML that contains a hash as href IE
                 * < 8 expands such href to the full page url with hash and
                 * then misinterprets tab as ajax.  Same consideration applies
                 * for an added tab with a fragment identifier since
                 * a[href=#fragment-identifier] does unexpectedly not match.
                 * Thus normalize href attribute...
                 */
                var hrefBase = href.split( "#" )[ 0 ],
                    baseEl;
                if ( hrefBase &&
                    ( hrefBase === location.toString().split( "#" )[ 0 ] ||
                    ( baseEl = $( "base" )[ 0 ]) &&
                      hrefBase === baseEl.href ) )
                {
                    href   = a.hash;
                    a.href = href;
                }
            }
            else
            {
                // A pre-loaded, inline tab
                $a.data( "href.tabs", href );
                href   = panelId;
            }
            // Connexions panelId }

            // inline tab
            if ( fragmentId.test( href ) )
            {
                // Connexions panelId {
                $a.data( 'panel.tabs', href );
                $a.data( 'cache.tabs', href );
                self.panels = self.panels.add( self._getPanel( $a ) );

                /*
                self.panels = self.panels.add(
                                self.element.find(
                                    self._sanitizeSelector( href ) ) );
                // */
                // Connexions panelId }

            // remote tab
            // prevent loading the page itself if href is just "#"
            }
            else if ( href && href !== "#" )
            {
                // required for restore on destroy
                $a.data( "href.tabs", href );

                // TODO until #3808 is fixed strip fragment identifier from url
                // (IE fails to load from such url)
                $a.data( "load.tabs", href.replace( /#.*$/, "" ) );

                var id = self._tabId( a );
                a.href = "#" + id;
                // Connexions panelId {
                $a.data( 'panel.tabs', '#'+ id );
                // Connexions panelId }

                var $panel = self.element.find( "#" + id );
                if ( !$panel.length )
                {
                    $panel = $( o.panelTemplate )
                        .attr( "id", id )
                        .addClass( "ui-tabs-panel ui-widget-content "
                                   +    "ui-corner-bottom" )
                        .insertAfter( self.panels[ i - 1 ] || self.list );
                    $panel.data( "destroy.tabs", true );
                }
                self.panels = self.panels.add( $panel );
            // invalid tab href
            } else {
                o.disabled.push( i );
            }
        });

        // initialization from scratch
        if ( init ) {
            // attach necessary classes for styling
            this.element.addClass( "ui-tabs ui-widget ui-widget-content "
                                    + "ui-corner-all" );
            this.list.addClass( "ui-tabs-nav ui-helper-reset "
                                    + "ui-helper-clearfix ui-widget-header "
                                    + "ui-corner-all" );
            this.lis.addClass( "ui-state-default ui-corner-top" );
            this.panels.addClass( "ui-tabs-panel ui-widget-content "
                                    + "ui-corner-bottom" );

            // Selected tab
            // use "selected" option or try to retrieve:
            // 1. from fragment identifier in url
            // 2. from cookie
            // 3. from selected class attribute on <li>
            if ( o.selected === undefined )
            {
                if ( location.hash )
                {
                    this.anchors.each(function( i, a ) {
                        if ( a.hash == location.hash )
                        {
                            o.selected = i;
                            return false;
                        }
                    });
                }
                if ( typeof o.selected !== "number" && o.cookie )
                {
                    o.selected = parseInt( self._cookie(), 10 );
                }
                if ( typeof o.selected !== "number" &&
                     this.lis.filter( ".ui-tabs-selected" ).length )
                {
                    o.selected =
                        this.lis.index(
                                this.lis.filter( ".ui-tabs-selected" ) );
                }
                o.selected = o.selected || ( this.lis.length ? 0 : -1 );
            }
            else if ( o.selected === null )
            {   // usage of null is deprecated, TODO remove in next release
                o.selected = -1;
            }

            // sanity check - default to first tab...
            o.selected = ( ( o.selected >= 0 && this.anchors[ o.selected ] ) ||
                           o.selected < 0 )
                ? o.selected
                : 0;

            // Take disabling tabs via class attribute from HTML
            // into account and update option properly.
            // A selected tab cannot become disabled.
            o.disabled = $.unique( o.disabled.concat(
                $.map( this.lis.filter( ".ui-state-disabled" ), function(n,i){
                    return self.lis.index( n );
                })
            ) ).sort();

            if ( $.inArray( o.selected, o.disabled ) != -1 )
            {
                o.disabled.splice( $.inArray( o.selected, o.disabled ), 1 );
            }

            // highlight selected tab
            this.panels.addClass( "ui-tabs-hide" );
            this.lis.removeClass( "ui-tabs-selected ui-state-active" );

            // check for length avoids error when initializing empty list
            if ( o.selected >= 0 && this.anchors.length )
            {
                // Connexions panelId {
                var $panel  = self._getPanelByIndex( o.selected );
                $panel.removeClass( 'ui-tabs-hide' );
                /*
                self.element.find(
                        self._sanitizeSelector(
                            self.anchors[ o.selected ].hash ) )
                                    .removeClass( "ui-tabs-hide" );
                // */
                // Connexions panelId }

                this.lis.eq( o.selected )
                            .addClass( "ui-tabs-selected ui-state-active" );

                // seems to be expected behavior that the show callback is fired
                self.element.queue( "tabs", function() {
                    self._trigger( "show", null,
                                   self._ui( self.anchors[ o.selected ],
                                    // Connexions panelId {
                                             $panel[ 0 ]
                                    /*
                                             self.element.find(
                                                self._sanitizeSelector(
                                                    self.anchors[ o.selected ]
                                                            .hash ) )[ 0 ]
                                    // */
                                    // Connexions panelId }
                                   )
                    );
                });

                this.load( o.selected );
            }

            // clean up to avoid memory leaks in certain versions of IE 6
            // TODO: namespace this event
            $( window ).bind( "unload", function() {
                self.lis.add( self.anchors ).unbind( ".tabs" );
                self.lis = self.anchors = self.panels = null;
            });
        // update selected after add/remove
        }
        else
        {
            o.selected =
                this.lis.index( this.lis.filter( ".ui-tabs-selected" ) );
        }

        // update collapsible
        // TODO: use .toggleClass()
        this.element[ o.collapsible
                        ? "addClass"
                        : "removeClass" ]( "ui-tabs-collapsible" );

        // set or update cookie after init and add/remove respectively
        if ( o.cookie )
        {
            this._cookie( o.selected, o.cookie );
        }

        // disable tabs
        for ( var i = 0, li; ( li = this.lis[ i ] ); i++ )
        {
            $( li )[ $.inArray( i, o.disabled ) != -1 &&
                // TODO: use .toggleClass()
                !$( li ).hasClass( "ui-tabs-selected" )
                    ? "addClass"
                    : "removeClass" ]( "ui-state-disabled" );
        }

        // reset cache if switching from cached to not cached
        if ( o.cache === false )
        {
            this.anchors.removeData( "cache.tabs" );
        }

        /* remove all handlers before, tabify may run on existing tabs after
         * add or option change
         */
        this.lis.add( this.anchors ).unbind( ".tabs" );

        if ( o.event !== "mouseover" )
        {
            var addState = function( state, el ) {
                if ( el.is( ":not(.ui-state-disabled)" ) )
                {
                    el.addClass( "ui-state-" + state );
                }
            };
            var removeState = function( state, el ) {
                el.removeClass( "ui-state-" + state );
            };
            this.lis.bind( "mouseover.tabs" , function() {
                addState( "hover", $( this ) );
            });
            this.lis.bind( "mouseout.tabs", function() {
                removeState( "hover", $( this ) );
            });
            this.anchors.bind( "focus.tabs", function() {
                addState( "focus", $( this ).closest( "li" ) );
            });
            this.anchors.bind( "blur.tabs", function() {
                removeState( "focus", $( this ).closest( "li" ) );
            });
        }

        // set up animations
        var hideFx, showFx;
        if ( o.fx )
        {
            if ( $.isArray( o.fx ) )
            {
                hideFx = o.fx[ 0 ];
                showFx = o.fx[ 1 ];
            }
            else
            {
                hideFx = showFx = o.fx;
            }
        }

        // Reset certain styles left over from animation
        // and prevent IE's ClearType bug...
        function resetStyle( $el, fx ) {
            $el.css( "display", "" );
            if ( !$.support.opacity && fx.opacity )
            {
                $el[ 0 ].style.removeAttribute( "filter" );
            }
        }

        // Show a tab...
        var showTab = showFx
            ? function( clicked, $show ) {
                $( clicked ).closest( "li" )
                            .addClass( "ui-tabs-selected ui-state-active" );

                // avoid flicker that way
                $show.hide().removeClass( "ui-tabs-hide" )
                    .animate( showFx, showFx.duration || "normal", function() {
                        resetStyle( $show, showFx );
                        self._trigger( "show", null,
                                       self._ui( clicked, $show[ 0 ] ) );
                    });
            }
            : function( clicked, $show ) {
                $( clicked ).closest( "li" )
                            .addClass( "ui-tabs-selected ui-state-active" );
                $show.removeClass( "ui-tabs-hide" );
                self._trigger( "show", null, self._ui( clicked, $show[ 0 ] ) );
            };

        // Hide a tab, $show is optional...
        var hideTab = hideFx
            ? function( clicked, $hide ) {
                $hide.animate( hideFx, hideFx.duration || "normal", function() {
                    self.lis.removeClass( "ui-tabs-selected ui-state-active" );
                    $hide.addClass( "ui-tabs-hide" );
                    resetStyle( $hide, hideFx );
                    self.element.dequeue( "tabs" );
                });
            }
            : function( clicked, $hide, $show ) {
                self.lis.removeClass( "ui-tabs-selected ui-state-active" );
                $hide.addClass( "ui-tabs-hide" );
                self.element.dequeue( "tabs" );
            };

        /* attach tab event handler, unbind to avoid duplicates from former
         * tabifying...
         */
        this.anchors.bind( o.event + ".tabs", function() {
            var el = this,
                // Connexions panelId {
                $el     = $(el),
                $li     = $el.closest( "li" ),
                $hide   = self.panels.filter( ":not(.ui-tabs-hide)" ),
                $show   = self._getPanel( $el );
                //tab     = self.lis.index( $el ),
                //$show   = self._getPanelByIndex( tab );
                /*
                $li = $(el).closest( "li" ),
                $hide = self.panels.filter( ":not(.ui-tabs-hide)" ),
                $show = self.element.find( self._sanitizeSelector( el.hash ) );
                // */
                // Connexions panelId }

            /* If tab is already selected and not collapsible or tab disabled
             * or or is already loading or click callback returns false stop
             * here.  Check if click handler returns false last so that it is
             * not executed for a disabled or loading tab!
             */
            if ( ( $li.hasClass( "ui-tabs-selected" ) && !o.collapsible) ||
                $li.hasClass( "ui-state-disabled" ) ||
                $li.hasClass( "ui-state-processing" ) ||
                self.panels.filter( ":animated" ).length ||
                self._trigger( "select", null,
                               self._ui( this, $show[ 0 ] ) ) === false )
            {
                this.blur();
                return false;
            }

            o.selected = self.anchors.index( this );

            self.abort();

            // if tab may be closed
            if ( o.collapsible )
            {
                if ( $li.hasClass( "ui-tabs-selected" ) )
                {
                    o.selected = -1;

                    if ( o.cookie )
                    {
                        self._cookie( o.selected, o.cookie );
                    }

                    self.element.queue( "tabs", function() {
                        hideTab( el, $hide );
                    }).dequeue( "tabs" );

                    this.blur();
                    return false;
                }
                else if ( !$hide.length )
                {
                    if ( o.cookie )
                    {
                        self._cookie( o.selected, o.cookie );
                    }

                    self.element.queue( "tabs", function() {
                        showTab( el, $show );
                    });

                    /* TODO make passing in node possible, see also
                     * http://dev.jqueryui.com/ticket/3171
                     */
                    self.load( self.anchors.index( this ) );

                    this.blur();
                    return false;
                }
            }

            if ( o.cookie )
            {
                self._cookie( o.selected, o.cookie );
            }

            // show new tab
            if ( $show.length )
            {
                if ( $hide.length )
                {
                    self.element.queue( "tabs", function() {
                        hideTab( el, $hide );
                    });
                }
                self.element.queue( "tabs", function() {
                    showTab( el, $show );
                });

                self.load( self.anchors.index( this ) );
            }
            else
            {
                throw "jQuery UI Tabs: Mismatching fragment identifier.";
            }

            /* Prevent IE from keeping other link focussed when using the back
             * button and remove dotted border from clicked link. This is
             * controlled via CSS in modern browsers; blur() removes focus from
             * address bar in Firefox which can become a usability and annoying
             * problem with tabs('rotate').
             */
            if ( $.browser.msie )
            {
                this.blur();
            }
        });

        // disable click in any case
        this.anchors.bind( "click.tabs", function(){
            return false;
        });
    }
});

}(jQuery));
/** @file
 *
 *  Javascript interface/wrapper for the presentation of a collapsable area.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered area.
 *
 *  The pre-rendered HTML must have a form similar to:
 *      < dom container, 'element' for this class (e.g. <div>, <ul>, <li>) >
 *        <h3 class='toggle'><span>Area Title</span></h3>
 *        <div > ... </div>
 *      </ dom container >
 *
 *         <a href='/settings/account'
 *            data-panel.tabs='#account'
 *            data-load.tabs='/settings?format=partial&section=account'>
 *           <span>Account</span>
 *         </a>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($) {

var collapsableId   = 0;

$.widget("connexions.collapsable", {
    version: "0.0.1",
    options: {
        // Defaults
        cache:          true,
        ajaxOptions:    null,
        cookie:         null,
        idPrefix:       'connexions-collapsable-',
        panelTemplate:  '<div></div>',
        spinner:        "<em>Loading&#8230;</em>"
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'collapse', 'expand', 'toggle'
     */
    _init: function() {
        var self    = this;
        var opts    = self.options;

        self.$toggle  = self.element.find('.toggle:first');
        self.$a       = self.$toggle.find('a:first');

        if (self.$a.length > 0)
        {
            var href    = self.$a.attr('href');
            if (! href.match(/^#.+/))
            {
                // remote tab -- save the original URL
                self.$a.data('href.collapsable', href);
                var loadUrl = self.$a.data('load.collapsable');
                if (loadUrl === undefined)
                {
                    self.$a.data('load.collapsable', href.replace(/#.*$/, ''));
                }


                var contentId   = self.$a.data('cache.collapsable');
                if (contentId === undefined)
                {
                    contentId = self.$a.data('content.collapsable');
                }

                if (contentId === undefined)
                {
                    // Generate a contentId
                    contentId   = ((self.$a.title &&
                                    self.$a.title.replace(/\s/g, '_')
                                       .replace(/[^A-Za-z0-9\-_:\.]/g, '')) ||
                                   opts.idPrefix + (++collapsableId));
                    self.$a.attr('href', '#'+ contentId);
                }

                self.$content = $('#'+ contentId);
                if (self.$content.length < 1)
                {
                    self.$content = $(opts.panelTemplate)
                                        .attr('id', contentId)
                                        .addClass('ui-corner-bottom')
                                        .insertAfter(self.$toggle);
                    self.$content.data('destroy.collapsable', true);
                }
            }
        }

        if ( (! self.$content) || (self.$content.length < 1) )
        {
            self.$content = self.$toggle.next();
        }

        // Add styling to the toggle and content
        self.$toggle.addClass('ui-corner-top');
        self.$content.addClass('content ui-corner-bottom');

        // Add an open/close indicator
        self.$toggle.prepend( '<div class="ui-icon">&nbsp;</div>');
        self.$indicator = self.$toggle.find('.ui-icon:first');

        if (self.$toggle.hasClass('collapsed'))
        {
            // Change the indicator to "closed" and hide the content
            self.$indicator.addClass('ui-icon-triangle-1-e');
            self.$content.hide();
        }
        else
        {
            // Change the indicator to "open" and hide the content
            self.$indicator.addClass('ui-icon-triangle-1-s');
            self.$content.show();

            if (! self.$toggle.hasClass('expanded'))
            {
                self.$toggle.addClass('expanded');
            }

            self._load();
        }

        self._bindEvents();
    },

    _bindEvents: function() {
        var self    = this;

        self.$toggle.bind('click.collapsable', function(e) {
            e.preventDefault();
            e.stopPropagation();

            self.toggle();
        });

        self.element.bind('toggle.collapsable', function(e) {
            self.toggle();
        });
        self.element.bind('expand.collapsable', function(e) {
            self.expand();
        });
        self.element.bind('collapse.collapsable', function(e) {
            self.collapse();
        });
    },

    _load: function(callback) {
        var self    = this;
        var opts    = self.options;
        var url     = self.$a.data('load.collapsable');

        self._abort();

        if ((! url) || self.$a.data('cache.collapsable'))
        {
            if ($.isFunction(callback))
            {
                callback();
            }
            return;
        }

        $.log('connexions.collapsable: load url[ '+ url +' ]');

        // Load remote content.
        self.xhr = $.ajax($.extend({}, opts.ajaxOptions, {
            url:     url,
            beforeSend: function(xhr, textStatus) {
                self.element.addClass('ui-state-processing');
                if ( opts.spinner )
                {
                    var $span = self.$a.find('span:first');
                    $span.data( "label.collapsable", $span.html() )
                                    .html( opts.spinner );
                }

                if (opts.ajaxOptions &&
                    $.isFunction(opts.ajaxOptions.beforeSend))
                {
                    opts.ajaxOptions.beforeSend.call(self.element,
                                                     xhr, textStatus);
                }
            },
            complete: function(xhr, textStatus) {
                if (opts.ajaxOptions &&
                    $.isFunction(opts.ajaxOptions.complete))
                {
                    opts.ajaxOptions.complete.call(self.element,
                                                   xhr, textStatus);
                }

                if ( opts.spinner )
                {
                    var $span = self.$a.find('span:first');
                    $span.html( $span.data( "label.collapsable" ) )
                         .removeData( 'label.collapsable' );
                }

                self.element.removeClass('ui-state-processing');
                if ($.isFunction(callback))
                {
                    callback();
                }
            },
            success: function(res, stat) {
                self.$content.html(res);

                if (opts.cache)
                {
                    self.$a.data('cache.collapsable', true);
                }

                self._trigger('load', null, self.element);

                try {
                    opts.ajaxOptions.success(res, stat);
                }
                catch (e) {}
            },
            error:   function(xhr, stat, err) {
                self.$content.html(  "<div class='error'>"
                                   +  "Cannot load: "
                                   +  xhr.statusText
                                   + "</div>");

                self._trigger('load', null, self.element);

                try {
                    opts.ajaxOptions.error(xhr, stat, self.element, self.$a);
                }
                catch (e) {}
            }
        }));

        return this;
    },

    _abort: function() {
        var self    = this;

        if (self.xhr)
        {
            self.xhr.abort();
            delete self.xhr;
        }

        return self;
    },

    /************************
     * Public methods
     *
     */
    toggle: function() {
        var self    = this;
        var opts    = self.options;
        if (self.$content.is(":hidden"))
        {
            self.expand();
        }
        else
        {
            self.collapse();
        }

        // Trigger 'toggled'
        self.element.trigger('toggled');
    },

    expand: function() {
        var self    = this;
        var opts    = self.options;
        if (self.$content.is(":hidden"))
        {
            // Show the content / open
            self._load(function() {
                self.$toggle.removeClass('collapsed')
                            .addClass(   'expanded');
                self.$indicator.removeClass('ui-icon-triangle-1-e')
                               .addClass(   'ui-icon-triangle-1-s');
                
                self.$content.slideDown('fast', function() {
                    self.element.trigger('expanded');
                });
            });
        }
    },

    collapse: function() {
        var self    = this;
        var opts    = self.options;
        if (! self.$content.is(":hidden"))
        {
            // Hide the content / close
            self.$toggle.removeClass('expanded')
                        .addClass(   'collapsed');
            self.$indicator.removeClass('ui-icon-triangle-1-s')
                           .addClass(   'ui-icon-triangle-1-e');
            self.$content.slideUp('fast', function() {
                self.element.trigger('collapsed');
            });
        }
    },

    destroy: function() {
        var self    = this;
        var opts    = self.options;

        // Restore the href and remove data.
        var href    = self.$a.data('href.collapsable');
        if (href)
        {
            self.$a.attr('href', href);
        }
        $.each(['href', 'load', 'cache'], function(i, prefix) {
            self.$a.removeData(prefix +'.collapsable');
        });

        if (self.$content.data('destroy.collapsable'))
        {
            self.$content.remove();
        }
        else
        {
            self.$content.removeClass('ui-corner-bottom content');
        }

        // Remove styling
        self.$toggle.removeClass('ui-corner-top');
        self.$toggle.removeClass('collapsed,expanded');

        // Remove event bindings
        self.$toggle.unbind('.collapsable');
        self.element.unbind('.collapsable');

        // Ensure that the content is visible
        self.$content.show();
    }
});

}(jQuery));
/** @file
 *
 *  Provide option groups for a set of checkbox options.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      connexions.optionGroups.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($) {

$.widget("connexions.dropdownForm", {
    version: "0.1.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Defaults
        namespace:  null,   // Form/cookie namespace
        form:       null,   // Our parent/controlling form
        groups:     null    // Display style groups.
    },

    /** @brief  Initialize a new instance.
     *
     *  Valid options are:
     *      namespace   The form / cookie namespace [ '' ];
     *      groups      An object of style-name => CSS selector;
     *
     *  @triggers:
     *      'apply.uidropdownform'  when the form is submitted;
     */
    _init: function() {
        var self        = this;
        var opts        = self.options;

        self.$form      = self.element.find('form:first');
        self.$reset     = self.element.find(':reset');
        self.$submit    = self.element.find(':submit');

        /* Convert selects to buttons
        self.$form.find('.field select')
                .button();
        */

        // Add a toggle control button
        self.$control   = 
                $(  "<div class='control'>"
                  +  "<button>Display Options</button>"
                  + "</div>");

        self.$control.prependTo(self.element);

        self.$button = self.$control.find('button');
        self.$button.button({
            icons: {
                secondary:  'ui-icon-triangle-1-s'
            }
        });
        self.$control.fadeTo(100, 0.5);

        /* Activate a connexions.optionGroups handler for any container/div in
         * this form with a CSS class of 'ui-optionGroups'.
         * connexions.optionGroups handler for them.
         */
        self.element
                .find('.ui-optionGroups')
                    .optionGroups({
                        namespace:  opts.namespace,
                        form:       self.$form
                    });

        self.$form.hide();

        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self        = this;
        var opts        = self.options;
        

        // Handle a click outside of the display options form.
        var _body_click     = function(e) {
            /* Ignore this click if:
             *  - our form is currently hidden;
             *  - the target is one of the controls in OUR widget;
             */
            if (self.$form.is(':visible') &&
                (! $.contains(self.element[0], e.target)) )
            {
                /* Hide the form by triggering self.$control.click and then
                 * mouseleave
                 */
                self.$control.triggerHandler('click');
                self.$control.trigger('mouseleave', e);
            }
        };

        // Opacity hover effects
        var _mouse_enter    = function(e) {
            self.$control.fadeTo(100, 1.0);
        };

        var _mouse_leave    = function(e) {
            if ((e && e.type === 'mouseleave') && self.$form.is(':visible'))
            {
                // Don't fade if the form is currently visible
                return;
            }

            self.$control.fadeTo(100, 0.5);
        };

        var _control_click  = function(e) {
            // Toggle the displayOptions pane
            //e.preventDefault();
            //e.stopPropagation();

            self.$form.toggle();
            self.$button.toggleClass('ui-state-active');

            //return false;
        };

        var _prevent_default    = function(e) {
            // Prevent the browser default, but let the event bubble up
            e.preventDefault();
        };

        var _form_change        = function(e) {
            /*
            // Remember which fields have changed
            var changed = self.element.data('changed.uidropdownform');

            if (! $.isArray(changed))
            {
                changed = [];
            }
            changed.push(e.target);

            self.element.data('changed.uidropdownform', changed);
            */

            //$.log("connexions.dropdownForm::caught 'form:change'");

            // Any change within the form should enable the submit button
            self.$submit
                    .removeClass('ui-state-disabled')
                    .removeAttr('disabled')
                    .addClass('ui-state-default');
        };

        var _form_reset         = function(e) {
            // Serialize all form values to an array...
            var settings    = self.$form.serializeArray();
            var cookieOpts  = {};
            var cookiePath  = $.registry('cookiePath');

            if (cookiePath)
            {
                cookieOpts.path = cookiePath;
            }

            /* ...and UNSET any cookie related to each
             *      namespace +'SortBy'
             *      namespace +'SortOrder'
             *      namespace +'PerPage'
             *      namespace +'Style'
             *      and possibly
             *          namespace +'StyleCustom[ ... ]'
             */
            $(settings).each(function() {
                // /*
                $.log("connexions.dropdownForm: Delete Cookie: "
                      + "name[%s]",
                      this.name);
                // */
                $.cookie(this.name, null, cookieOpts);
            });

            if (! self._trigger('apply', e))
            {
                e.stopImmediatePropagation();
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        };

        var _form_submit        = function(e) {
            // Serialize all form values to an array...
            var settings    = self.$form.serializeArray();
            var cookieOpts  = {};
            var cookiePath  = $.registry('cookiePath');

            if (cookiePath)
            {
                cookieOpts.path = cookiePath;
            }

            /* ...and set a cookie for each
             *      namespace +'SortBy'
             *      namespace +'SortOrder'
             *      namespace +'PerPage'
             *      namespace +'Style'
             *      and possibly
             *          namespace +'StyleCustom[ ... ]'
             */
            $(settings).each(function() {
                /*
                $.log("connexions.dropdownForm: Add Cookie: "
                      + "name[%s], value[%s]",
                      this.name, this.value);
                // */
                $.cookie(this.name, this.value, cookieOpts);
            });

            if (! self._trigger('apply', e))
            {
                e.stopImmediatePropagation();
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        };

        var _form_clickSubmit   = function(e) {
            e.preventDefault();

            // Trigger the 'submit' event on the form
            self.$form.trigger('submit');
        };

        /**********************************************************************
         * bind events
         *
         */

        // Handle a click outside of the display options form.
        $('body')
                .bind('click.uidropdownform', _body_click);

        // Add an opacity hover effect to the displayOptions
        self.$control
                .bind('mouseenter.uidroppdownform', _mouse_enter)
                .bind('mouseleave.uidroppdownform', _mouse_leave)
                .bind('click.uidropdownform',       _control_click);

        self.$form
                .bind('change.uidropdownform', _form_change)
                .bind('submit.uidropdownform', _form_submit);

        self.$submit
                .bind('click.uidropdownform', _form_clickSubmit);

        self.$reset
                .bind('click.uidropdownform', _form_reset);
    },

    /************************
     * Public methods
     *
     */
    getGroup: function() {
        return this.element.find('.displayStyle')
                            .optionGroups( 'getGroup' );
    },

    setGroup: function(style) {
        return this.element.find('.displayStyle')
                            .optionGroups( 'setGroup', style );
    },

    getGroupInfo: function() {
        return this.element.find('.displayStyle')
                            .optionGroups( 'getGroupInfo' );
    },

    setApplyCb: function(cb) {
        this.options.apply = cb;
    },

    open: function() {
        if (this.element.find('form:first').is(':visible'))
        {
            // Already opened
            return;
        }

        this.element.find('.control:first').click();
    },

    close: function() {
        if (! this.element.find('form:first').is(':visible'))
        {
            // Already closed
            return;
        }

        this.element.find('.control:first').click();
    },

    enable: function(enableSubmit) {
        var self    = this;

        self.$form.find('input,select').removeAttr('disabled');

        if (enableSubmit !== true)
        {
            // Any change within the form should enable the submit button
            self.$submit
                    .removeClass('ui-state-default ui-state-highlight')
                    .addClass('ui-state-disabled')
                    .attr('disabled', true);
        }
        else
        {
            self.$submit
                    .removeClass('ui-state-disabled')
                    .removeAttr('disabled')
                    .addClass('ui-state-default');
        }
    },

    disable: function() {
        var self    = this;

        self.$form.find('input,select').attr('disabled', true);

        // Any change within the form should enable the submit button
        self.$submit
                .removeClass('ui-state-default ui-state-highlight')
                .addClass('ui-state-disabled')
                .attr('disabled', true);
    },

    destroy: function() {
        var self        = this;

        // Unbind events
        $('body')
                .unbind('.uidropdownform');

        self.$control.unbind('.uidropdownform');
        self.$control.find('a:first, .ui-icon:first')
                     .unbind('.uidropdownform');

        self.$form.unbind('.uidropdownform');

        self.$submit.unbind('.uidropdownform');
        self.$reset.unbind( '.uidropdownform');

        // Remove added elements
        self.$button.button('destroy');
        self.$control.remove();

        self.element.find('.displayStyle').optionGroups( 'destroy' );
    }
});


}(jQuery));
/** @file
 *
 *  Provide option groups for a set of checkbox options.  These must have the
 *  following HTML structure:
 *
 *      <div class='_NS_OptionGroups'>      // _NS_ defines the namespace
 *        ...
 *        <ul class='groups'>               // define groups
 *         <li [ class='isCustom' ] >       // 'isCustom' iff this group
 *                                          // represents the "custom" group
 *                                          // to allow the user to select
 *                                          // any desired options as opposed
 *                                          // to those associated with a
 *                                          // particular pre-defined group.
 *          <input type='radio'
 *              [ class='is
 *                 name='_NS_OptionGroup'
 *                value='GROUP-NAME'        // define GROUP-NAME
 *
 *                 [ checked='checked' if this group is selected ] />
 *
 *          <label  for='_NS_OptionGroup'>
 *           GROUP-LABEL                    // define GROUP-LABEL / title
 *          </label>
 *         </li>
 *         ...
 *        </ul>
 *        <fieldset class='options'>        // define groupable options
 *         ...
 *         <div class='option'>
 *          <input type='checkbox'
 *                class='inGroup-GROUP-NAME ...'   // One 'inGroup-*' class
 *                                                  // for each group this
 *                                                  // option is part of
 *
 *                                          // define a colon-separated
 *                                          // option name that mirrors the
 *                                          // CSS selector to this point
 *                 name='_NS_OptionGroups_option[OPTION-NAME]'
 *
 *                 [ checked='checked' if this option is selected ] />
 *
 *          <label for='_NS_OptionGroups_option[OPTION-NAME]'>
 *           OPTION-LABEL                   // define OPTION-LABEL / title
 *          </label>
 *         </div>
 *         ...
 *        </fieldset>
 *      </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, document:false, window:false */

(function($) {

$.widget("connexions.optionGroups", {
    version: "0.1.1",
    options: {
        // Defaults
        namespace:  null,   // Form/cookie namespace
        form:       null    // Our parent/controlling form
    },

    /** @brief  Initialize a new instance.
     *
     *  Valid options are:
     *      namespace   The form / cookie namespace [ '' ];
     *      groups      An object of group-name => CSS selector;
     *
     *  @triggers:
     *      'change'    on the controlling form when the option group is
     *                  changed, passing
     *                              data:
     *                                  {'group':    groupName,
     *                                   'selector': selector for all fields}
     */
    _create: function() {
        var self        = this;
        var opts        = this.options;

        if (opts.namespace === null)
        {
            // See if the DOM element has a 'namespace' data item
            var ns  = self.element.data('namespace');
            if (ns !== undefined)
            {
                opts.namespace = ns;
            }
            else
            {
                /* Attempt to retrieve the namespace from the CSS class
                 * '_NS_OptionGroups'
                 */
                var css = self.element.attr('class');

                ns = css.replace(/^(?:.* )?(.*?)OptionGroups(?: .*)?$/,
                                      '$1');

                if ((ns !== undefined) && (ns.length > 0))
                {
                    opts.namespace = ns;
                }
            }

        }
        if (opts.form === null)
        {
            // See if the DOM element has a 'form' data item
            var fm  = self.element.data('form');
            if (fm !== undefined)
            {
                opts.form = fm;
            }
            else
            {
                // Choose the closest form
                opts.form = self.element.closest('form');
            }
        }

        /* The currently selected group:
         *  self.element.find('ul.groups :checked').val();
         *
         * Prepare the presentation:
         *  - Remove the CSS class 'ui-state-active' from all 'li' elements;
         *  - Add the CSS class 'ui-state-active' to the 'li' element
         *    containing the currently selected group;
         *  - Hide and disable all group radio buttons;
         *  - Add the 'toggle'  class to any group NOT marked 'isCustom';
         *  - Add the 'control' class to any group marked 'isCustom';
         *  - Add a down-arrow icon to the 'isCustom' control
         *  - Append '<span class='comma'>,</span>' after all but the last 'li'
         *    element;
         *  - For all input elements, add the classes:
         *      'ui-corner-all ui-state-default'
         */
        var $groups     = self.element.find('ul.groups');

        $groups.find('li')
                .removeClass('ui-state-active')
                .addClass('ui-state-default')
                .filter(':first')
                    .addClass('ui-corner-left')
                .end()
                .find(':radio')
                    .hide();
        $groups.find(':checked')
                .parent()
                    .addClass('ui-state-active');
        $groups.find('li.isCustom')
                .addClass('control')
                .button({
                    icons: {
                        secondary:  'ui-icon-triangle-1-s'
                    }
                })
                .removeClass('ui-corner-all')
                .addClass('ui-corner-right');

        /* Now, the currently selected group can be found via:
         *  self.element.find('ul.groups :checked').val();
         *  self.element.find('ul.groups li.ui-state-active :radio').val();
         *  -- self.element.find('ul.groups input[type=hidden]').val();
         */

        // Interaction events
        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self            = this;
        var opts            = this.options;
        var $groups         = self.element.find('ul.groups');
        var $groupFieldset  = self.element.find('fieldset:first');
        var $groupControl   = $groups.find('.control:first');

        var _prevent_default        = function(e) {
            e.preventDefault();
        };

        var _groupControl_click     = function(e) {
            e.preventDefault();
            e.stopPropagation();

            self.toggleFieldset();
        };

        var _groupFieldset_change   = function(e) {
            /* The fieldset has changed so change the current group to
             * the 'isCustom' / 'control' group.
             *
             * Don't allow propagation -- we will directly trigger any events
             *                            that need to be passed on.
             */
            e.preventDefault();
            e.stopPropagation();

            var $group  = $groupControl.find(':radio');

            // Activate this group
            self.setGroup( $group.val() );

            return false;
        };

        var _group_select    = function(e) {
            /*
            if ($(e.target).is(':radio'))
                // Avoid infinite event loops ;^)
                return;
            */

            // Allow only one display group to be selected at a time
            e.preventDefault();
            e.stopPropagation();

            var $group  = $(this).find(':radio');

            // Activate this group
            self.setGroup( $group.val() );
        };

        // Bind to submit.
        var _form_submit        = function(e) {
            var cookieOpts  = {};
            var cookiePath  = $.registry('cookiePath');

            if (cookiePath)
            {
                cookieOpts.path = cookiePath;
            }

            /* Remove all cookies directly identifying options.  This is
             * because, when an option is NOT selected, it is not included so,
             * to remove a previously selected options, we must first remove
             * them all and then add in the ones that are explicitly selected.
             */
            $groupFieldset.find(':checkbox').each(function() {
                /*
                $.log("Remove Cookie: name[ %s ] / [ %s ]",
                        this.name, $(this).attr('name'));
                // */

                $.cookie( $(this).attr('name'), null, cookieOpts );
            });

            /* If the selected display group is NOT 'custom', disable
             * all the 'display custom' pane/field-set inputs so they
             * will not be included in the serialization of form
             * values.
             */
            if (! $groups.find('li.ui-state-active').hasClass('isCustom'))
            {
                // Disable all custom field values
                $groupFieldset.find(':checkbox').attr('disabled', true);
            }

            // let the form be submitted
        };


        /**********************************************************************
         * bind events
         *
         */

        /* Toggle the display group area.
         * the display group to 'custom', de-selecting the others.
         */
        $groupControl
                .bind('click.uioptiongroups', _groupControl_click);

        /* When something in the group fieldset changes, set the display group
         * to 'custom', de-selecting the others.
         */
        $groupFieldset
                .bind('change.uioptiongroups', _groupFieldset_change);

        // Allow only one display group to be selected at a time
        $groups.find('li:not(.control)')    // ('li.toggle')
                .bind('change.uioptiongroups', _group_select)
                .bind('click.uioptiongroups',  _group_select);

        // Bind to submit.
        opts.form.bind('submit.uioptiongroups', _form_submit);
    },

    /************************
     * Public methods
     *
     */
    getGroup: function() {
        /* Now, the currently selected group can be found in three ways:
         *  this.element.find('ul.groups :checked').val();
         *  this.element.find('ul.groups li.ui-state-active :radio').val();
         *  -- this.element.find('ul.groups input[type=hidden]').val();
         */
        return this.element.find('ul.groups :checked').val();
    },

    setGroup: function(group) {
        /* Now, the currently selected group can be found in three ways:
         *  this.element.find('ul.groups :checked').val();
         *  this.element.find('ul.groups li.ui-state-active :radio').val();
         *  -- this.element.find('ul.groups input[type=hidden]').val();
         */
        var self            = this;
        var $groups         = self.element.find('ul.groups');
        var $groupFieldset  = self.element.find('fieldset:first');
        var $newGroup       = $groups.find(':radio[value='+group+']');
        if ($newGroup.length !== 1)
        {
            return;
        }

        // Select the new radio button
        $groups.find(':checked').attr('checked', false)
                                .removeAttr('checked');
        $newGroup.attr('checked', 'checked');

        /* Remove 'ui-state-active' from all groups and add it JUST to the new
         * one
         */
        $groups.find('li.ui-state-active').removeClass('ui-state-active');

        var $li = $newGroup.parents('li:first');
        $li.addClass('ui-state-active');

        // Set the hidden input value
        // $groups.find('input[type=hidden]').val(group);

        if (! $li.hasClass('control'))
        {
            // Turn OFF all items in the group fieldset...
            $groupFieldset.find('input').removeAttr('checked');

            // Turn ON  the items for this new display group.
            $groupFieldset.find('.inGroup-'+ group)
                           .attr('checked', 'checked');
        }

        /* Gather the set of selected AND deselected options.  For each,
         * retrieve its name (e.g. 'sel1:sel2:sel3') and convert it to a CSS
         * selector.
         *
         * Generate an array of CSS selectors that will choose all selected
         * options and a second that will choose all de-selected options.
         */
        var selected    = [];
        var deSelected  = [];
        $groupFieldset.find('input:checked').each(function() {
            selected.push( '.' + $(this).attr('name')
                                            .replace(/^.*?\[(.*?)\]$/, '$1')
                                            .replace(/:/g, ' .') );
        });

        $groupFieldset.find('input:not(:checked)').each(function() {
            deSelected.push( '.' + $(this).attr('name')
                                            .replace(/^.*?\[(.*?)\]$/, '$1')
                                            .replace(/:/g, ' .') );
        });

        var groupInfo   = {'group'      : group,
                           'selected'   : selected,
                           'deSelected' : deSelected};

        self.element.data('groupInfo', groupInfo);

        /* Trigger the 'change' event passing the name of the new group along
         * with an array of CSS selectors that will match all items of the
         * group and an array of CSS selectors that will match all items NOT of
         * the group.
         */
        //$.log("connexions.optionGroups: trigger 'form:change'");
        self.options.form.trigger('change', groupInfo);
    },

    getGroupInfo: function() {
        return this.element.data('groupInfo');
    },

    getForm: function() {
        return this.options.form;
    },

    enable: function() {
        this.find(':input').removeAttr('disabled');
    },

    disable: function() {
        this.find(':input').attr('disabled', true);
    },

    toggleFieldset: function()
    {
        this.element.find('fieldset:first')
                                .toggleClass('ui-state-active')
                                .toggle();
    },

    destroy: function() {
        var self    = this;

        // Remove data
        self.element.find('a.option,div.option a:first')
                .removeData('group');

        // Unbind events
        var $groupControl   = self.element.find('.control:first');
        var $itemsGroup     = self.element.find('input[name='
                            +                       self.options.namespace
                            +                                   'Group]');

        /* Toggle the display group area.
         * the display group to 'custom', de-selecting the others.
         */
        $groupControl
                .unbind('.uioptiongroups');

        /* For all anchors within the control button, disable the default
         * browser action but allow the event to bubble up to any parent click
         * handlers (e.g. _groupControl_click).
         */
        $groupControl.find('> a, .control > a, .control > .ui-icon')
                .unbind('.uioptiongroups');

        /* When something in the group fieldset changes, set the display group
         * to 'custom', de-selecting the others.
         */
        self.element.find('fieldset:first')
                .unbind('.uioptiongroups');

        // Allow only one display group to be selected at a time
        self.element.find('a.option')
                .unbind('.uioptiongroups');

        // Bind to submit.
        self.options.form
                .unbind('.uioptiongroups');
    }
});


}(jQuery));
/** @file
 *
 *  Javascript interface/wrapper for the presentation of an item scope
 *  display/input area.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered area generate by View_Helper_HtmlItemScope:
 *      - conversion of the input area to a ui.input, ui.autocomplete, or
 *        ui.tagInput instance;
 *
 *  The pre-rendered HTML must have a form similar to:
 *      <form class='itemScope'>
 *        <input type='hidden' name='scopeCurrent' ... />
 *        <ul>
 *          <li class='root'>
 *            <a href='%url with no items%'> %Root Label% </a>
 *          </li>
 *
 *          <!-- For each item currently defining the scope -->
 *          <li class='scopeItem deletable'>
 *            <a href='%url with item%'> %Scope Label% </a>
 *            <a href='%url w/o  item%' class='delete'>x</a>
 *          </li>
 *
 *          <li class='scopeEntry'>
 *            <input name=' %inputName% ' value=' %inputLabel ' /> 
 *            <button type='submit'>&gt;</button>
 *          </li>
 *
 *          <li class='itemCount'> %item Count% </li>
 *        </ul>
 *      </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.input.js, ui.autocomplete.js, or ui.tagInput.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false, document:false */
(function($){

$.widget("connexions.itemScope", {
    options: {
        namespace:          '',                 // Cookie/parameter namespace

        termName:           'tag',              /* The propert(ies)
                                                 * representing the
                                                 * autocompletion match string.
                                                 *
                                                 * MAY be an array if multiple
                                                 * properties were used in the
                                                 * autocomplete and should be
                                                 * presented.  In this case,
                                                 * the first SHOULD be the
                                                 * property to use as the
                                                 * autocompletion value.
                                                 */
        weightName:         'userItemCount',    /* The property to present
                                                 * as the autocompletion
                                                 * match weight.
                                                 */

        /* General Json-RPC information:
         *  {version:   Json-RPC version,
         *   target:    URL of the Json-RPC endpoint,
         *   transport: 'POST' | 'GET'
         *   method:    RPC method name,
         *   params:    {
         *      key/value parameter pairs
         *   }
         *  }
         *
         * If not provided, 'version', 'target', and 'transport' are
         * initialized from:
         *      $.registry('api').jsonRpc
         *
         * (which is initialized from
         *      application/configs/application.ini:api
         *  via
         *      application/layout/header.phtml
         *
         */
        jsonRpc:            null,

        refocusCookie:      'itemScope-refocus',    /* The name of the cookie
                                                     * indicating the need to
                                                     * refocus on the input
                                                     * area upon
                                                     * initialization.
                                                     */

        separator:          ',',    // The term separator
        minLength:          2       // Minimum term length
    },
    _create: function(){
        var self    = this,
            opts    = self.options;

        /********************************
         * Initialize jsonRpc
         *
         */
        if ($.isFunction($.registry))
        {
            var api = $.registry('api');
            if (api && api.jsonRpc)
            {
                opts.jsonRpc = $.extend({}, api.jsonRpc, opts.jsonRpc);
            }
        }

        /********************************
         * Locate the pieces
         *
         */
        self.$input    = self.element.find('.scopeEntry :text');
        self.$curItems = self.element.find('.scopeItem');
        self.$submit   = self.element.find('.scopeEntry :submit');

        /********************************
         * Instantiate our sub-widgets
         *
         */
        self.$input.input();
        if (opts.jsonRpc !== null)
        {
            // Setup autocompletion via Json-RPC
            if (self.$input.tagInput)
            {
                self.$input.tagInput({
                    height:         'none',
                    autocomplete:   {
                        source:     function(request, response) {
                            return self._autocomplete(request,response);
                        },
                        minLength:  opts.minLength,
                        position:   {
                            offset: '0 5'
                        }
                    }
                });

                // Make it easier for _autocomplete() and destroy()
                self.autocompleteWidget = self.$input.data('tagInput');
            }
            else if (self.$input.autocomplete)
            {
                self.$input.autocomplete({
                    separator:  ',',
                    source:     function(request, response) {
                        return self._autocomplete(request,response);
                    },
                    minLength:  opts.minLength
                });

                // Make it easier for _autocomplete() and destroy()
                self.autocompleteWidget = self.$input.data('autocomplete');
            }
        }

        self._bindEvents();

        // See if we should refocus on our input area upon initialization
        var cookieOpts  = {},
            cookiePath  = $.registry('cookiePath');
        if (cookiePath)
        {
            cookieOpts.path = cookiePath;
        }

        if ($.cookie(opts.refocusCookie))
        {
            // Delete the cookie
            $.cookie(opts.refocusCookie, null, cookieOpts);

            self.$input.focus();
        }
    },

    _autocomplete: function(request, response) {
        var self    = this;
        var opts    = self.options;
        var params  = opts.jsonRpc.params;
        
        params.term = self.autocompleteWidget.option('term');

        var re      = new RegExp(params.term, 'gi');

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, opts.jsonRpc.method, params, {
            success:    function(ret, txtStatus, req){
                if (ret.error !== null)
                {
                    self.element.trigger('error', [txtStatus, req, ret.error]);
                    return;
                }

                response(
                    $.map(ret.result,
                          function(item) {
                            var str     = '';
                            var value   = null;
                            var weight  = (item[opts.weightName] === undefined
                                            ? ''
                                            : item[opts.weightName]);

                            if ($.isArray(opts.termName))
                            {
                                // Multiple match keys
                                var parts   = [];
                                $.each(opts.termName, function() {
                                    if (item[ this ] === undefined)
                                    {
                                        return;
                                    }

                                    if (value === null)
                                    {
                                        value = item[this];
                                    }

                                    str = item[this]
                                            .replace(re,
                                                     '<b>'+params.term+'</b>');

                                    parts.push( str );
                                });

                                str = parts.join(', ');
                            }
                            else
                            {
                                value = item[opts.termName];
                                str   = value
                                        .replace(re, '<b>'+params.term+'</b>');
                            }

                            return {
                                label:   '<span class="name">'
                                       +  str
                                       + '</span>'
                                       +' <span class="count">'
                                       +  weight
                                       + '</span>',
                                value: value
                            };
                          }));
                self.element.trigger('success', [ret, txtStatus, req]);
            },
            error:      function(req, txtStatus, e) {
                self.element.trigger('error', [txtStatus, req]);
            }
        });
    },

    _bindEvents: function() {
        var self    = this;
        var opts    = self.options;

        // Attach a hover effect for deletables
        var $deletables = self.element.find('.deletable a.delete');
        $deletables
                .bind('mouseenter.itemScope', function(e) {
                    $(this).css('opacity', 1.0)
                           .addClass('ui-icon-circle-close')
                           .removeClass('ui-icon-close');
                })
                .bind('mouseleave.itemScope', function(e) {
                    $(this).css('opacity', 0.25)
                           .addClass('ui-icon-close')
                           .removeClass('ui-icon-circle-close');
                })
                .trigger('mouseleave')
                .bind('click.itemScope', function(e) {
                    $.spinner();
                });

        // Attach a click handler to the submit button
        self.$submit
                .bind('click.itemScope', function(e) {
                    // Force the 'submit' event on our form
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    self.element.submit();
                });

        // Attach a 'submit' handler to the itemScope form item
        self.element
                .bind('submit.itemScope', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    // Changing scope -- adjust the form's action
                    var loc     = window.location;
                    var url     = loc.toString();
                    var scope   = self.$input.val().replace(/\s*,\s*/g, ',')
                                                   .replace(/(^,|,$)/g, '');

                    if (self.$curItems.length > 0)
                    {
                        if (scope.length > 0)
                        {
                            url += ',';
                        }
                    }
                    else if (url[url.length-1] !== '/')
                    {
                        url += '/';
                    }
                    url += scope;

                    // Simply change the browsers URL
                    $.spinner();
                    window.location.assign(url);

                    // Allow form submission to continue
                });

        /* Attach a 'keypress' handler to the itemScope input item.  On ENTER,
         * trigger 'submit' on the form item.
         */
        self.$input
                .bind('keypress.itemScope', function(e) {
                    if (e.keyCode === $.ui.keyCode.ENTER)
                    {
                        // Set the itemScope-refocus cookie
                        var cookieOpts  = {},
                            cookiePath  = $.registry('cookiePath');
                        if (cookiePath)
                        {
                            cookieOpts.path = cookiePath;
                        }

                        $.cookie(opts.refocusCookie, true, cookieOpts);
                                
                        self.element.submit();
                    }
                });
    },

    /*************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;
        var opts    = self.options;

        // Destroy widgets
        if (opts.jsonRpc !== null)
        {
            self.autocompleteWidget.destroy();
        }
        self.$input.input('destroy');

        // Unbind events
        self.element.find('.deletable a.delete').unbind('.itemScope');
        self.$submit.unbind('.itemScope');
        self.$input.unbind('.itemScope');
        self.element.unbind('.itemScope');
    }
});

}(jQuery));
/** @file
 *
 *  Javascript interface/wrapper for the presentation of a pagination control.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered pagination control, generate via Zend_Paginator.
 *
 *  The paginator has the following HTML structure:
 *
 *      <form class='paginator'>
 *        <div class='pager'>
 *          <button type='submit' ... value='page#'>page#</button>
 *          ...
 *        </div>
 *
 *        <!-- and optionally -->
 *        <div class='info'>
 *          <div class='perPage'>
 *            <div class='itemCount'>count#</div>
 *              items with
 *            <select name='%ns%PerPage'>...</select>
 *              items per page.
 *          </div>
 *          <div class='itemRange'>
 *            Currently viewing items
 *            <div class='count'>1 - 50</div>
 *             .
 *          </div>
 *        </div>
 *      </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */

(function($) {

$.widget("connexions.paginator", {
    version: "0.1.1",
    options: {
        // Defaults
        namespace:      '',     // Form/cookie namespace
        disableHover:   false,
        page:           1,
        pageVar:        'Page'
    },

    /** @brief  Initialize a new instance.
     *
     *  Valid options are:
     *      namespace   The form / cookie namespace [ '' ];
     *
     *  @triggers:
     *      'submit'    on the controlling form when 'PerPage' select element
     *                  is changed.
     */
    _init: function() {
        var self        = this;
        var opts        = self.options;

        if (opts.namespace === null)    opts.namespace = '';

        if (opts.form === null)
        {
            // See if the DOM element has a 'form' data item
            var fm  = self.element.data('form');
            if (fm !== undefined)
            {
                opts.form = fm;
            }
            else
            {
                // Choose the closest form
                opts.form = self.element.closest('form');
            }
        }

        // Which page is currently selected/active?
        opts.page    = self.element.find('button.ui-state-active').text();
        opts.pageVar = self.element.find('button:submit:first').attr('name');

        // Interaction events
        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self    = this;
        var opts    = self.options;

        // Add an opacity hover effect
        if (! opts.disableHover)
        {
            self.element
                .fadeTo(100, 0.5)
                .hover( function() {    // in
                            $(this).fadeTo(100, 1.0);
                        },
                        function() {    // out
                            $(this).fadeTo(100, 0.5);
                        }
                );
        }

        // Attach to any PerPage selection box
        self.element.find('.perPage select')
                .bind('change.paginator', function(e) {
                        /* On change of the PerPage select:
                         *  - set a cookie for the %ns%PerPage value...
                         */
                        var cookieOpts  = {};
                        var cookiePath  = $.registry('cookiePath');

                        if (cookiePath)
                        {
                            cookieOpts.path = cookiePath;
                        }

                        $.log("connexions.paginator: Add Cookie: "
                              + "path[%s], name[%s], value[%s]",
                              cookiePath, this.name, this.value);

                        $.cookie(this.name, this.value, cookieOpts);

                        //  - and trigger 'submit' on the pagination form.
                        self.element.submit();
                      }
                );

        // Attach to all 'submit' buttons to remember which page
        self.element.find(':submit')
                .bind('click.paginator', function(e) {
                            opts.page = $(this).val();

                            // Allow the event to bubble
                        }
                );
    },

    /** @brief  Over-ride jQuery-ui so we can handle toggling 'disableHover'
     *  @param  key     The name of the option;
     *  @param  value   The new option value;
     *
     *  @return this for a fluent interface.
     */
    _setOption: function( key, value ) {
        var self    = this;
        var opts    = self.options;

        switch (key)
        {
        case 'disableHover':
            if (opts.disableHover != value)
            {
                if (! value )
                {
                    // Add an opacity hover effect
                    self.element
                        .fadeTo(100, 0.5)
                        .bind('mouseenter.paginator', function() {
                                $(this).fadeTo(100, 1.0);
                              })
                        .bind('mouseleave.paginator', function() {
                                $(this).fadeTo(100, 0.5);
                              });
                }
                else
                {
                    // Remove the opacity hover effect
                    self.element
                        .fadeTo(100, 1.0)
                        .unbind('mouseenter.paginator')
                        .unbind('mouseleave.paginator');
                }
            }
            break;
        }

        // Invoke our superclass
        $.Widget.prototype._setOption.apply(this, arguments);
    },

    /************************
     * Public methods
     *
     */
    getPage: function() {
        return this.options.page;
    },
    getPageVar: function() {
        return this.options.pageVar;
    },

    getForm: function() {
        return this.options.form;
    },

    enable: function() {
        this.element.find(':button').removeAttr('disabled');
    },

    disable: function() {
        this.element.find(':button').attr('disabled', true);
        this.element.fadeTo(100, 1.0)
                    .unbind('.paginator');
    },

    destroy: function() {
    }
});


}(jQuery));
/** @file
 *
 *  Javascript interface/wrapper for the presentation of a configurable pane.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered view / pane:
 *      - conversion of any (optional) paginator markup (form.paginator),
 *        generated via View_Helper_HtmlPaginationControl, to
 *        connexions.paginator instance(s);
 *      - conversion of any (optional) display options markup
 *        (.displayOptions), generated via View_Helper_HtmlDisplayOptions, to a
 *        connexions.dropdownForm instance;
 *
 *  The pre-rendered HTML must have a form similar to:
 *      <div class='pane' ...>
 *        [ top paginator ]
 *        [ display options ]
 *
 *        content
 *
 *        [ bottom paginator ]
 *      </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      connexions.dropdownForm.js
 *      connexions.paginator.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($) {

$.widget("connexions.pane", {
    version: "0.0.1",
    options: {
        // Defaults
        namespace:      '',     // Cookie/parameter namespace
        partial:        null,   // The name of the 'partial' if asynchronous
                                // reloads are to be used on pagination or
                                // displayOption changes.

        // Information via the connexions.pagination widget(s)
        pageCur:        null,   // The current page number
        pageVar:        null,   // The page number URL variable name
        page:           null,   // The target  page number
        hiddenVars:     null,   // Hidden variables from the target form


        /* Configuration for any <form class='pagination'> element that 
         * will be controlled by a connexions.pagination widget.
         */
        paginator:      {},

        /* Configuration for any <div class='displayOptions'> element that 
         * will be controlled by a connexions.dropdownForm widget.
         */
        displayOptions: {}
    },

    /************************
     * Private methods
     *
     */
    _init: function() {
        var self    = this;
        var opts    = self.options;

        self.element.addClass('pane');
        self._init_paginators();
        self._init_displayOptions();

        // Include a refresh button
        self.$refresh    = $(  '<div class="refreshPane icon-default">'
                            +  '<a ref="#" '
                            +     'class="ui-icon ui-icon-arrowrefresh-1-s" '
                            +     'title="refresh this pane">'
                            +   'refresh'
                            +  '</a>'
                            + '</div>')
                                .insertAfter(self.$displayOptions);
        self.$refresh.bind('click.uipane', function(e) {
            e.preventDefault(true);

            self.reload();
        });
    },

    _init_paginators: function() {
        var self        = this;
        var opts        = self.options;

        self.$paginators    = self.element.find('form.paginator');

        self.$paginators.each(function(idex) {
            var $pForm  = $(this);

            if ($pForm.data('paginator') === undefined)
            {
                // Not yet instantiated
                $pForm.paginator({namespace:    opts.namespace,
                                  form:         $pForm,
                                  disableHover: (idex !== 0)
                                  });
            }
            else if (idex !== 0)
            {
                // Already instantiated but we need to modify 'disableHover'
                $pForm.paginator('option', 'disableHover', true);
            }

            if (opts.pageCur === null)
            {
                opts.pageCur = $pForm.paginator('getPage');
                opts.pageVar = $pForm.paginator('getPageVar');
            }
        });

        self.$paginators.bind('submit.uipane', function(e) {
            var $pForm  = $(this);

            e.preventDefault(true);
            e.stopPropagation(true);
            e.stopImmediatePropagation(true);

            // Set the target page number
            opts.page       = $pForm.paginator('getPage');

            // reload
            self.reload();
        });
    },

    _init_displayOptions: function() {
        var self                = this;
        self.$displayOptions    = self.element.find('div.displayOptions');

        if (self.$displayOptions.length < 1)
        {
            return;
        }

        var opts    = self.options;

        if (self.$displayOptions.data('dropdownForm') === undefined)
        {
            // Not yet instantiated
            var dOpts   = (opts.displayOptions === undefined
                            ? {}
                            : opts.displayOptions);

            if (dOpts.namespace === undefined)
            {
                dOpts.namespace = opts.namespace;
            }

            // Instantiate the connexions.dropdownForm widget
            self.$displayOptions.dropdownForm(dOpts);
        }

        self.$displayOptions.bind('submit.uipane', function(e) {
            // STOP the submit event
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            // WAIT for the 'apply' event before we reload
        });

        self.$displayOptions.bind('apply.uipane', function(e) {
            self.reload();
        });
    },

    _paneDestroy: function() {
        var self    = this;

        // Unbind events
        self.$paginators.unbind('.uipane');
        self.$displayOptions.unbind('.uipane');

        // Remove added elements
        self.$paginators.paginator('destroy');
        self.$displayOptions.dropdownForm('destroy');

        self.element.removeClass('pane');
    },

    /************************
     * Public methods
     *
     */
    reload: function(completionCb) {
        var self    = this;
        var opts    = self.options;
        var loc     = window.location;
        var url     = loc.toString();
        var qSep    = '?';

        if (opts.pageVar !== null)
        {
            var re  = new RegExp(opts.pageVar +'='+ opts.pageCur);
            var rep = opts.pageVar +'='+ (opts.page !== null
                                            ? opts.page
                                            : opts.pageCur);
            if (loc.search.length === 0)
            {
                url += '?'+ rep;
            }
            else if (! url.match(re))
            {
                url += '&'+ rep;
            }
            else
            {
                url = url.replace(re, rep);
            }
            qSep = '&';
        }

        if (opts.hiddenVars !== null)
        {
            var ns      = opts.namespace;
            var hasNs   = (ns.length > 0);

            // Also include any hidden input values in the URL.
            $.each(opts.hiddenVars, function(name,val) {
                if (hasNs)  name = ns + $.ucFirst(name);
                url += qSep + name +'='+ val;
                qSep = '&';
            });
        }

        if (opts.partial !== null)
        {
            // AJAX reload of just this pane...
            url += qSep +'format=partial&part='+ opts.partial;

            $.ajax({url:        url,
                    dataType:   'html',
                    beforeSend: function() {
                        self.element.mask();
                    },
                    error:      function(req, txtStatus, err) {
                        $.notify({
                            title:'Reload pane "'+ opts.partial +'" failed',
                            text: '<p class="error">'+ txtStatus +'</p>'});
                    },
                    success:    function(data, txtStatus, req) {
                        // Out with the old...
                        self.destroy();

                        /* In with the new which should come with
                         * initialization (e.g. $('#id).pane({ ... }); )
                         */
                        self.element.replaceWith(data);

                        /*
                        self.element.html(data);
                        self._create();
                        */
                    },
                    complete:   function() {
                        self.element.unmask();
                        if ($.isFunction(completionCb))
                        {
                            completionCb.call(self.element);
                        }
                    }
            });
        }
        else
        {
            // Perform a full, synchronous reload...
            $.spinner();
            window.location.assign(url);
        }
    },

    destroy: function() {
        this._paneDestroy();
    }
});


}(jQuery));
/** @file
 *
 *  Javascript interface/wrapper for the presentation of a configurable pane
 *  which contains an item list.
 *
 *  This is class extends connexions.pane to include unobtrusive activation of
 *  any contained, pre-rendered ul.list generated via
 *  View_Helper_Html{ Bookmarks | Users }.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      connexions.pane.js
 *      connexions.itemList.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.itemsPane", $.connexions.pane, {
    version: "0.0.1",
    options: {
        // Defaults
        namespace:  ''
    },

    /** @brief  Initialize a new instance.
     */
    _init: function() {
        var self        = this;
        var opts        = self.options;

        // Invoke our super-class
        $.connexions.pane.prototype._init.apply(this, arguments);

        // If a 'saved' event reaches us, reload the pane
        self.element.delegate('form', 'saved.itemsPane', function() {
            setTimeout(function() { self.reload(); }, 50);
        });

        self._init_itemList();
    },

    /************************
     * Private methods
     *
     */
    _init_itemList: function() {
        var self            = this;
        self.$itemList  = self.element.find('ul.items');

        if (self.$itemList.length < 1)
        {
            return;
        }

        var opts    = self.options;
        var uiOpts  = (opts.itemList === undefined
                        ? {}
                        : opts.itemList);

        if (uiOpts.namespace === undefined)
        {
            uiOpts.namespace = opts.namespace;
        }

        // Instantiate the connexions.itemList widget
        self.$itemList.itemList(uiOpts);
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;

        // Unbind events
        self.element.undelegate('form', '.itemsPane');

        // Remove added elements
        self.$itemList.itemList('destroy');

        // Invoke our super-class
        $.connexions.pane.prototype.destroy.apply(this, arguments);
    }
});


}(jQuery));
/** @file
 *
 *  Javascript interface/wrapper for the presentation of a configurable pane
 *  which contains a bookmark list.
 *
 *  This is class extends connexions.pane to include unobtrusive activation of
 *  any contained, pre-rendered ul.cloud generated via
 *  View_Helper_Html_HtmlItemCloud.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.position.js
 *      ui.confirmation.js
 *      connexions.pane.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, setTimeout:false, clearTimeout:false, document:false */
(function($) {

$.widget("connexions.cloudPane", $.connexions.pane, {
    version: "0.0.1",
    options: {
        // Defaults
        namespace:  '',

        /* General Json-RPC information:
         *  {version:   Json-RPC version,
         *   target:    URL of the Json-RPC endpoint,
         *   transport: 'POST' | 'GET'
         *  }
         *
         * If not provided, 'version', 'target', and 'transport' are
         * initialized from:
         *      $.registry('api').jsonRpc
         *
         * (which is initialized from
         *      application/configs/application.ini:api
         *  via
         *      application/layout/header.phtml
         *
         */
        jsonRpc:    null,

        /* If the JSON-RPC method is GET, the apiKey for the authenticated user
         * is required for any methods that modify data.
         */
        apiKey:     null
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'change.bookmark'  when something about the bookmark is changed;
     */
    _init: function() {
        var self        = this;
        var opts        = self.options;

        // Invoke our super-class
        $.connexions.pane.prototype._init.apply(this, arguments);

        /********************************
         * Initialize jsonRpc
         *
         */
        if ($.isFunction($.registry))
        {
            var api = $.registry('api');
            if (api && api.jsonRpc)
            {
                opts.jsonRpc = $.extend({}, api.jsonRpc, opts.jsonRpc);
            }
        }

        /********************************
         * Locate our pieces and
         * bind events
         *
         */
        //self.$doForm = self.element.find('.displayOptions form');
        self.$doForm = self.$displayOptions.find('form:first');

        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self    = this;

        /* On Display style change, toggle the state of 'highlightCount'
         *
         * Note: The connexions.dropdownForm widget that controls the display
         *       options DOM element attached a connexions.optionsGroups
         *       instance to any contained displayOptions element.  This widget
         *       will trigger the 'change' event on the displayOptions form
         *       with information about the selected display group when a
         *       change is made.
         */
        this.$doForm.bind('change.cloudPane',
                function(e, info) {
                    var $field  = $(this).find('.field.highlightCount');

                    if ( (info       === undefined) ||
                         (info.group === undefined) ||
                         (info.group === 'cloud') )
                    {
                        // Enable the 'highlightCount'
                        $field.removeClass('ui-state-disabled');
                        $field.find('select').removeAttr('disabled');
                    }
                    else
                    {
                        // Disable the 'highlightCount'
                        $field.addClass('ui-state-disabled');
                        $field.find('select').attr('disabled', true);
                    }
                });

        // Delegate any click within a '.control' element
        this.element.delegate('.item-edit, .item-delete, .item-add',
                              'click', function(e) {
            var $el = $(this);

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            if ($el.hasClass('item-edit'))
            {
                // Edit
                self._edit_item($el);
            }
            else if ($el.hasClass('item-add'))
            {
                // Delete
                self._add_user($el);
            }
            else
            {
                // Delete
                self._delete_confirm($el);
            }
        });
    },

    /** @brief  The 'edit' control item was clicked.  Present item editing
     *          along with edit save/cancel options.
     *
     *  @param  $el     The jQuery/DOM element that was clicked upon
     *                  (i.e. the '.item-edit' element);
     */
    _edit_item: function($el) {
        var self    = this;
        var $ctl    = $el.parents('.control:first');
        if ($ctl.attr('disabled') !== undefined)
        {
            return;
        }
        $ctl.attr('disabled', true);

        var opts    = self.options;
        var $li     = $el.parents('li:first');
        var $a      = $li.find('.item:first');
        var tag     = $a.data('id');

        // Present a confirmation dialog and delete.
        var html    = '<div class="edit-item">'
                    +  '<input type="text" class="text" value="'+ tag +'" />'
                    +  '<div class="buttons">'
                    +   '<span class="item-save" title="save">'
                    +    '<span class="title">Save</span>'
                    +    '<span class="icon connexions_sprites status-ok">'
                    +    '</span>'
                    +   '</span>'
                    +   '<span class="item-cancel" title="cancel">'
                    +    '<span class="title">Cancel</span>'
                    +    '<span class="icon connexions_sprites star_0_off">'
                    +    '</span>'
                    +   '</span>'
                    +  '</div>'
                    + '</div>';
        var $div    = $(html);

        $ctl.hide();

        // Activate the input area
        var width   = parseInt($a.width(), 10);
        var $input  = $div.find('input');

        $input.input()
               /* Set the font-size and width of the input control based upon
                * the tag anchor
                */
              .css('font-size', $a.css('font-size'))
              .width( width + 16 );

        // Insert and position
        $div.appendTo( $li )
            .position({
                of:     $a,
                my:     'center top',
                at:     'center top',
                offset: '0 -5' //'0 -8'
            });


        $input.focus();

        function _reEnable(e)
        {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            /* Wait a bit to remove the element so the click doesn't
             * inadvertenely hit any underlying tag element.
             */
            setTimeout(function() {
                        $ctl.removeAttr('disabled')
                            .show();

                        $div.remove();
                       }, 100);
        }

        function _doSave(e)
        {
        }

        var $save   = $div.find('.item-save');
        var $cancel = $div.find('.item-cancel');
        
        $save.click( function(e) {
            if ($input.input('hasChanged') !== true)
            {
                _reEnable(e);
                return;
            }

            self._perform_rename($el, function(result) {
                if (result !== false)
                {
                    // Change the tag.
                    var orig    = $input.input('getOrigValue');
                    var url     = $a.attr('href')
                                        .replace(/\/([^\/]+)$/, '/'+ result);

                    $a.attr('href', url)
                      .text(result)
                      .data('id', result);

                    _reEnable(e);
                }
            });
        });
        $cancel.click(function(e) {
            _reEnable(e);
        });

        // Handle 'Enter' and 'ESC' in the input element
        $input.keydown(function(e) {
            if (e.keyCode === 13)       // return
            {
                $save.click();
            }
            else if (e.keyCode === 27)  // ESC
            {
                $cancel.click();
            }
        });
    },

    /** @brief  Attempt to save a edited/renamed tag.
     *  @param  $el             The jQuery/DOM element that was originally
     *                          clicked upon to initiate this rename
     *                          (i.e. the '.item-edit' element);
     *  @param  completionCb    A callback to invoke when the rename attempt is
     *                          complete:
     *                              function(result);
     *                                  false == failure
     *                                  else  == new Tag value
     */
    _perform_rename: function($el, completionCb) {
        var self    = this;
        var opts    = self.options;
        var $li     = $el.parents('li:first');
        var $input  = $li.find('.edit-item input');
        var $a      = $li.find('.item:first');
        var oldTag  = $a.data('id');
        var newTag  = $input.val();
        var result  = false;

        /* method:  user.renameTags,
         * renames  '%old%:%new%',
         *
         * Service Returns:
         *  { %oldTag% => %status == true | message string%, ... }
         */
        var params  = {
            renames:    oldTag +':'+ newTag
        };
        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.renameTags', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'Tag rename failed',
                        text:  '<p class="error">'
                             +   (data ? data.error.message : '')
                             + '</p>'
                    });

                    return;
                }

                if (data.result === null)   { return; }

                if (data.result[oldTag] !== true)
                {
                    $.notify({
                        title: 'Tag rename failed',
                        text:  '<p class="error">'
                             +   (data ? data.result[oldTag] : '')
                             + '</p>'
                    });

                    return;
                }

                $.notify({
                    title: 'Tag renamed',
                    text:  oldTag +' renamed to '+ newTag
                });

                result = newTag;
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'Tag rename failed',
                    text:  '<p class="error">'
                         +   textStatus
                         + '</p>'
                });
            },
            complete:   function(req, textStatus) {
                completionCb(result);
            }
         });
    },

    /** @brief  The 'delete' control item was clicked.  Present a delete
     *          confirmation.
     *  @param  $el     The jQuery/DOM element that was clicked upon
     *                  (i.e. the '.item-delete' element);
     */
    _delete_confirm: function($el) {
        var self    = this;
        var $ctl    = $el.parents('.control:first');

        if ($ctl.attr('disabled') !== undefined)
        {
            return;
        }
        $ctl.attr('disabled', true);

        $ctl.confirmation({
            question:   'Really delete?',
            //position:   self._confirmationPosition($ctl),
            confirmed:  function() {
                self._perform_delete($el);
            },
            closed:     function() {
                $ctl.removeAttr('disabled');
            }
        });
    },

    /** @brief  Item deletion has been confirmed, attempt to delete the
     *          identified item
     *          (tag OR a person in the authenticated user's network).
     *  @param  $el     The jQuery/DOM element that was originally clicked upon
     *                  to initiate this deletion
     *                  (i.e. the '.item-delete' element);
     */
    _perform_delete: function($el) {
        var self    = this;
        var opts    = self.options;
        var $ul     = $el.parents('ul:first');
        var type    = $ul.data('type');

        switch (type)
        {
        case 'user':
            self._remove_user($el);
            break;

        case 'tag':
        default:
            self._delete_tag($el);
            break;
        }
    },

    /** @brief  Delete a tag.
     *  @param  $el     The jQuery/DOM element that was originally clicked upon
     *                  to initiate this deletion
     *                  (i.e. the '.item-delete' element);
     */
    _delete_tag: function($el) {
        var self    = this;
        var opts    = self.options;
        var $li     = $el.parents('li:first');
        var $a      = $li.find('.item:first');
        var tag     = $a.data('id');

        /* method:  user.deleteTags,
         * tags:    id,
         *
         * Service Returns:
         *  { %tag% => %status == true | message string%, ... }
         */
        var params  = {
            tags:   tag
        };
        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.deleteTags', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'Tag deletion failed',
                        text:  '<p class="error">'
                             +   (data ? data.error.message : '')
                             + '</p>'
                    });

                    return;
                }

                if (data.result === null)   { return; }

                if (data.result[tag] !== true)
                {
                    $.notify({
                        title: 'Tag deletion failed',
                        text:  '<p class="error">'
                             +   (data ? data.result[tag] : '')
                             + '</p>'
                    });

                    return;
                }

                $.notify({
                    title: 'Tag deleted',
                    text:  tag
                });

                // Trigger a deletion event for our parent
                $li.hide('fast', function() {
                    $li.remove();
                });
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'Tag deletion failed',
                    text:  '<p class="error">'
                         +   textStatus
                         + '</p>'
                });
            },
            complete:   function(req, textStatus) {
            }
         });
    },

    /** @brief  Add a user to the authenticated user's network.
     *  @param  $el     The jQuery/DOM element that was originally clicked upon
     *                  to initiate this deletion
     *                  (i.e. the '.item-delete' element);
     */
    _add_user: function($el) {
        var self    = this;
        var opts    = self.options;
        var $li     = $el.parents('li:first');
        var $a      = $li.find('.item:first');
        var user    = $a.data('id');

        /* method: user.removeFromNetwork,
         * users:  id,
         *
         * Service Returns:
         *  { %user% => %status == true | message string%, ... }
         */
        var params  = {
            users:  user
        };
        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.addToNetwork', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'User addition failed',
                        text:  '<p class="error">'
                             +   (data ? data.error.message : '')
                             + '</p>'
                    });

                    return;
                }

                if (data.result === null)   { return; }

                if (data.result[user] !== true)
                {
                    $.notify({
                        title: 'User addition failed',
                        text:  '<p class="error">'
                             +   (data ? data.result[user] : '')
                             + '</p>'
                    });

                    return;
                }

                $.notify({
                    title: 'User added',
                    text:  user
                });
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'User addition failed',
                    text:  '<p class="error">'
                         +   textStatus
                         + '</p>'
                });
            },
            complete:   function(req, textStatus) {
            }
         });
    },

    /** @brief  Remove a user from the authenticated user's network.
     *  @param  $el     The jQuery/DOM element that was originally clicked upon
     *                  to initiate this deletion
     *                  (i.e. the '.item-delete' element);
     */
    _remove_user: function($el) {
        var self    = this;
        var opts    = self.options;
        var $li     = $el.parents('li:first');
        var $a      = $li.find('.item:first');
        var user    = $a.data('id');

        /* method: user.removeFromNetwork,
         * users:  id,
         *
         * Service Returns:
         *  { %user% => %status == true | message string%, ... }
         */
        var params  = {
            users:  user
        };
        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.removeFromNetwork', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'User removal failed',
                        text:  '<p class="error">'
                             +   (data ? data.error.message : '')
                             + '</p>'
                    });

                    return;
                }

                if (data.result === null)   { return; }

                if (data.result[user] !== true)
                {
                    $.notify({
                        title: 'User removal failed',
                        text:  '<p class="error">'
                             +   (data ? data.result[user] : '')
                             + '</p>'
                    });

                    return;
                }

                $.notify({
                    title: 'User removed',
                    text:  user
                });

                // Trigger a deletion event for our parent
                $li.hide('fast', function() {
                    $li.remove();
                });
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'User removal failed',
                    text:  '<p class="error">'
                         +   textStatus
                         + '</p>'
                });
            },
            complete:   function(req, textStatus) {
            }
         });
    },

    /** @brief  Given a control DOM element and a new confirmation DOM element,
     *          figure out the best positioning for the confirmation and append
     *          it to the parent li.
     *  @param  $ctl            The control jQuery/DOM element;
     *  @param  $confirmation   The new confirmation jQuery/DOM element;
     *
     *  @return The proper position information.
     */
    _confirmationPosition: function($ctl, $confirmation) {
        var $li         = $ctl.parents('li:first');

        // Figure out the best place to put the confirmation.
        var cOffset     = $ctl.offset();
        var lOffset     = $li.offset();
        var pos         = {
            of: $ctl
        };
        if (cOffset.top <= lOffset.top)
        {
            /* ctl is IN $li (i.e. in a list view)
             *  set my right/center at the right/center of $ctl
             */
            pos.my = 'right bottom';
            pos.at = 'right bottom';
        }
        else
        {
            /* ctl is NOT IN $li (i.e. in a cloud view)
             *  set my top/center at the top/center of $ctl
             */
            pos.my = 'center top';
            pos.at = 'center top';
        }

        if ($confirmation !== undefined)
        {
            $confirmation.appendTo( $li )
                         .position( pos );
        }

        return pos;
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;

        // Unbind events
        self.$doForm.unbind('.cloudPane');

        // Invoke our super-class
        $.connexions.pane.prototype.destroy.apply(this, arguments);
    }
});


}(jQuery));



/** @file
 *
 *  Javascript interface/wrapper for the presentation of a configurable
 *  sidebar.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered sidebar:
 *      - conversion of markup generated via View_Helper_HtmlSidebar, to
 *        ui.tabs instance(s);
 *      - possible asynchronous loading of tab panes with masking of the tab
 *        widget during load;
 *
 *  The pre-rendered HTML must have a form similar to:
 *      <div id='%namespace%'>
 *        <ul>
 *          <li>
 *            <a href='%paneUrl%'>
 *              <span>Pane Title</span>
 *            </a>
 *          </li>
 *          ...
 *        </ul>
 *        
 *        <!-- If these are synchronous panes, the content is here -->
 *        <div id='%paneId%'>
 *          Pane content
 *        </div>
 *        ...
 *      </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($) {

$.widget("connexions.sidebar", {
    version: "0.0.1",
    options: {
        // Defaults
        namespace:      ''      // Cookie/parameter namespace
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'change.bookmark'  when something about the bookmark is changed;
     */
    _create: function() {
        var self    = this;
        var opts    = self.options;

        opts.namespace = self.element.attr('id');

        self.element.tabs({
            cache:      true,
            cookie:     opts.namespace,
            ajaxOptions:{
                beforeSend: function() {
                    // Mask the tab panel area...
                    var sel = self.element.tabs('option', 'selected');
                    self.$tab = self.element.find('.ui-tabs-panel').eq(sel);

                    self.$tab.mask();
                },
                complete: function() {
                    // Unmask the tab panel area...
                    self.$tab.unmask();
                }
            }
        });
    },

    /************************
     * Private methods
     *
     */

    /************************
     * Public methods
     *
     */
    destroy: function() {
        this.element.tabs('destroy');
    }
});


}(jQuery));



/** @file
 *
 *  Javascript interface/wrapper for the presentation of multiple items.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered list of items (View_Helper_Html{ Bookmarks | Users}), each of
 *  which will become a connexions.{ %objClass% } instance.
 *
 *  This class also handles:
 *      - hover effects for .groupHeader DOM items;
 *      - conversion of all form.item DOM items to
 *        connexions.{ %objClass% } instances;
 *
 *  View_Helper_HtmlItems will generate HTML for a item list similar
 *  to:
 *      <div id='<ns>List'>
 *        <ul class='<ns>'>
 *          <li><form class='%objClass%'> ... </form></li>
 *          ...
 *        </ul>
 *      </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.itemList", {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Defaults
        namespace:      '',
        objClass:       null,
        dimOpacity:     0.5,
        dimSpeed:       100,

        // Should item 'deleted' events be ignored?
        ignoreDeleted:  false
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'change.item'  when something about the item is changed;
     */
    _create: function()
    {
        var self        = this;
        var opts        = self.options;

        // Items
        self.$items = self.element.find('li > form');

        // Group Headers
        self.$headers = self.element.find('.groupHeader .groupType');

        if ((opts.objClass === null) && (self.$items.length > 0))
        {
            /* Determine the type/class of item by the CSS class of the
             * representative form
             */
            opts.objClass = self.$items.attr('class');

            // :XXX: IE Fix
            if (opts.objClass === undefined)
            {
                opts.objClass = self.$items[0].className;
            }
        }

        if (self.$items.length > 0)
        {
            // Instantiate each item using the identified 'objClass'
            self.$items[opts.objClass]();
        }

        // Initially dim all headers
        self.$headers.fadeTo(1, opts.dimOpacity);

        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function()
    {
        var self            = this,
            opts            = self.options,
            $groupHeaders   = self.element.find('.groupHeader');

        if (opts.ignoreDeleted !== true)
        {
            /* Include a handler for the 'deleted' event that will be
             * emitted by the instance when it believes it has been
             * "deleted".  In most cases, this belief is justified, but if
             * 'ignoreDeleted' is set, we need to ignore that belief.
             */

            // Use an event delegate
            self.element.delegate('li > form', 'deleted.itemList', function(e) {
                self._itemDeleted( $(this) );
            });
            // */
        }

        /** @brief  Handle a mouseenter/mouseleave event to highlight the
         *          appropriate group header.
         *  @param  e   The triggering event.
         */
        var groupHover = function(e) {
            /*
            console.log('groupHover:'+ e.type +': '+ e.target.nodeName);
            // */

            if (self.hoverTimer)    { clearTimeout(self.hoverTimer); }
            if (e.type === 'mouseleave')
            {
                /* mouseleave:
                 *  Wait a short bit and, if 'mouseenter' isn't triggered dim
                 *  all headers.
                 */
                self.hoverTimer = setTimeout(function() {
                    /*
                    console.log('groupHover:mouseleave: dim all headers');
                    // */

                    self.hoverTimer = null;
                    self.$headers.stop().fadeTo(opts.dimSpeed, opts.dimOpacity);
                }, 100);

                return;
            }

            /* mouseenter:
             *  Find the last group header that is ABOVE the current mouse
             *  position.
             */
            var $group
            $groupHeaders.each(function() {
                var offset  = $(this).offset();

                if (e.pageY >= offset.top)
                {
                    $group = $(this).find('.groupType');
                }
            });
            if (! $group)   { $group = self.$headers.first(); }

            /*
            console.log('groupHover:mouseenter: highlight gruop #'+
                        $group.index());
            // */
            
            // Dim all headers except the target, which will be highlighted
            var $toDim  = self.$headers.not($group);

            $toDim.stop().fadeTo(opts.dimSpeed, opts.dimOpacity);
            $group.stop().fadeTo(opts.dimSpeed, 1.0);
        };

        // Handle mouseenter/mouseleave triggered on the top-level element.
        self.element.bind('mouseenter.itemList mouseleave.itemList',
                                                            groupHover);

        // And delegate mouseenter for children elements.
        self.element.delegate('li,.groupHeader', 'mouseenter.itemList',
                                                            groupHover);
    },

    _itemDeleted: function($item)
    {
        var self        = this;

        /* Remove the given item, also removing the group header if this
         * item is the last in the group.
         */
        var $parentLi   = $item.parent('.item');

        /* If this is the last item in the group, the groupHeader will be
         * the prevous element and the next element will NOT be another
         * 'li.item'
         */
        var $group      = $parentLi.prev('.groupHeader');
        var $next       = $parentLi.next();

        // Slide the item up and then the containing 'li.item'
        $item.slideUp('fast', function() {
            $parentLi.slideUp('normal', function() {
                // Destroy the widget and remove the containing 'li.item'
                if ($item.item) { $item.item('destroy'); }
                if ($item.user) { $item.user('destroy'); }

                // Trigger an 'itemDeleted' event.
                self.element.trigger('itemDeleted', [ $item ]);

                $parentLi.remove();

                if (($group.length > 0) && (! $next.hasClass('item')) )
                {
                    /* There are no more items in the group, so remove the
                     * group header
                     */
                    $group.slideUp('normal', function() {
                        $group.remove();
                    });
                }
            });
        });
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;
        var opts    = self.options;

        // Unbind/delegate events
        self.element.unbind('.itemList');
        self.element.undelegate('li > form',       '.itemList');
        self.element.undelegate('li,.groupHeader', '.itemList');

        // Remove added elements
        if (self.$items.length > 0)
        {
            self.$items[opts.objClass]('destroy');
        }
    }
});


}(jQuery));
/** @file
 *
 *  Javascript interface/wrapper for the presentation of a search box with
 *  drop down context selection.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered area generate by view/scripts/nav_menu.phtml:
 *      - conversion of the input area to a ui.input instance;
 *
 *  The pre-rendered HTML must have a form similar to:
 *      <form id='search'>
 *        <div class='searchBox'>
 *          <div class='searchInput'>
 *            <div class='choices'>
 *              <input type='hidden' name='searchContext' ... />
 *              <ul class='sub list'>
 *                <li id='search-choice-%name%'> %title% </li>
 *                ...
 *              </ul>
 *            </div>
 *            
 *            <input type='text' name='terms' class='input' ... />
 *          </div>
 *          <button class='submit' ...>&nbsp;</button>
 *        </div>
 *      </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.input.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($){

$.widget("connexions.search", {
	options: {
	},
	_create: function(){
		var self    = this;
        var opts    = self.options;

        self.$input         = self.element.find('input[name=terms]');
        self.$submit        = self.element.find('button.submit');
        self.$context       = self.element.find('input[name=searchContext]');
        self.$choices       = self.element.find('.choices .list');
        self.contextLabel   = self.$choices.find('li.active').text();

        // Initially disable the submit button
        self.$submit.addClass('ui-state-disabled')
                    .attr('disabled', true);

        /* Attach a ui.input widget to the input field with defined validation
         * callback to enable/disable the submit button based upon whether or
         * not there is text in the search box.
         */
        self.$input.input({
            validation: function(val) {
                if (val.length > 0)
                {
                    self.$submit.removeClass('ui-state-disabled')
                                .removeAttr('disabled');
                }
                else
                {
                    self.$submit.addClass('ui-state-disabled')
                                .attr('disabled', true);
                }

                // ALWAYS return true.  There really in no "invalid" search
                return true;
            }
        });
        self.$input.input('setLabel', self.contextLabel);

        self._bindEvents();
	},

    _bindEvents: function() {
        var self    = this;
        var opts    = self.options;

        // Activate our search choice selections
        self.$choices.find('li')
                .bind('mousedown.search', function(e) {
                    /* We're changing the label text so, before 'blur' is
                     * fired, remove the existing label text.
                     *
                     * This fixes a flicker issue where the old label text
                     * would be placed in the input field only to be removed
                     * when we re-focus on that field.
                     */
                    self.$input.input('setLabel', null);
                })
                .bind('click.search', function(e) {
                    var $li         = $(this);

                    // Grab the new context value from li.id
                    var newChoice   = $li.attr('id').replace(/search-choice-/,
                                                             '');

                    // Set the new context value
                    self.$context.val(newChoice);

                    // Remember this context value via cookie
                    $.cookie('searchContext', newChoice);

                    // Grab the new label value for the query input box
                    self.contextLabel = $li.text();

                    // Remove the 'active' class from all siblings...
                    $li.siblings('.active').removeClass('active');

                    // Add the 'active' class to THIS element.
                    $li.addClass('active');

                    // Set the new label text and focus on the input field.
                    self.$input.input('setLabel', self.contextLabel);
                    /*
                    if ($.isFunction(self.$input.focus))
                    {
                        self.$input.focus();
                    }
                    */
                });
    },

    /*************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;

        // Destroy widgets
        self.$input.input('destroy');

        // Unbind events
        self.$choices.find('li').unbind('.search');
    }
});

}(jQuery));
/** @file
 *
 *  Javascript interface/wrapper for the posting of a bookmark.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-renderd bookmark post from
 *      (application/views/scripts/post/main.phtml)
 *
 *      - conversion of markup for suggestions to ui.tabs instance(s) 
 *        possibly containing connexions.collapsible instance(s);
 *
 *
 *  <form>
 *   <input name='mode' type='hidden' value='edit' />
 *   <div class='item-status'>
 *    <div class='field favorite'>
 *     <label  for='isFavorite'>Favorite</label>
 *     <input name='isFavorite' type='checkbox' />
 *    </div>
 *    <div class='field private'>
 *     <label  for='isPrivate'>Private</label>
 *     <input name='isPrivate' type='checkbox' />
 *    </div>
 *    <div class='field worldModify'>
 *     <label  for='worldModify'>World modifiable</label>
 *     <input name='worldModify' type='checkbox' />
 *    </div>
 *   </div>
 *   <div class='item-data'>
 *    <div class='field userRating'>
 *     <?= View_Helper_HtmlStarRating output ?>
 *    </div>
 *    <div class='field item-name'>
 *     <label  for='name'>Bookmark name / title</label>
 *     <input name='name' type='text' class='required' />
 *    </div>
 *    <div class='field item-url'>
 *     <label  for='url'>URL to bookmark</label>
 *     <input name='url' type='text' class='required' />
 *    </div>
 *    <div class='field item-description'>
 *     <label     for='description'>
 *       Description / Notes for this bookmark
 *     </label>
 *     <textarea name='description'>...</textarea>
 *    </div>
 *    <div class='field item-tags'>
 *     <label     for='tags'>Tags</label>
 *     <textarea name='tags' class='required'>...</textarea>
 *    </div>
 *   </div>
 *   <div class='buttons'>
 *    <button name='submit'>Save</button>
 *    <button name='cancel'>Cancel</button>
 *   </div>
 *
 *   <div class='suggestions' style='display:none;'>
 *    <ul>
 *     <li><a href='#suggestions-tags'><span>Tags</span></a></li>
 *     <li><a href='#suggestions-people'><span>People</span></a></li>
 *    </ul>
 *
 *    <ul id='suggestions-tags'>
 *     <li class='collapsable'>
 *      <h3 class='tooggle'><span>Recommended</span></h3>
 *      <div class='cloud'>
 *      </div>
 *     </li>
 *
 *     <li class='collapsable'>
 *      <h3 class='tooggle'><span>Your Top 100</span></h3>
 *      <div class='cloud'>
 *      </div>
 *     </li>
 *    </ul>
 *
 *    <ul id='suggestions-people'>
 *     <li class='collapsable'>
 *      <h3 class='tooggle'><span>Network</span></h3>
 *      <div class='cloud'>
 *      </div>
 *     </li>
 *    </ul>
 *   </div>
 *
 *  </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      connexions.collapsable
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.bookmarkPost", {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Widget state (mirrors Model_Bookmark)
        userId:     null,
        itemId:     null,

        name:       null,
        description:null,
        rating:     null,
        isFavorite: null,
        isPrivate:  null,
        worldModify:null,

        tags:       null,
        url:        null,

        // taggedOn and updateOn are not user editable

        /* An element or element selector to be used to present general status
         * information.  If not provided, $.notify will be used.
         */
        $status:    null,   //'.status',

        /* General Json-RPC information:
         *  {version:   Json-RPC version,
         *   target:    URL of the Json-RPC endpoint,
         *   transport: 'POST' | 'GET'
         *  }
         *
         * If not provided, 'version', 'target', and 'transport' are
         * initialized from:
         *      $.registry('api').jsonRpc
         *
         * (which is initialized from
         *      application/configs/application.ini:api
         *  via
         *      application/layout/header.phtml
         */
        jsonRpc:    null,

        /* If the JSON-RPC method is GET, the apiKey for the authenticated user
         * is required for any methods that modify data.
         */
        apiKey:     null,

        /* The posting mode:
         *      save/null   The user is saving a new bookmark;
         *      modify      The user is modifying an existing bookmark and
         *                  is permitted to edit JUST name, description, and
         *                  tags;
         *      edit        The user is modifying an existing bookmark and
         *                  is permitted full editing;
         *
         * For modes save/post, changes are NOT required to data fields before
         * saving AND ALL fields will be included in the update regardless of
         * whether they've changed.
         */
        mode:       'save',

        // Widget state
        enabled:    true
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'enabled'
     *      'disabled'
     *      'urlChanged'        -- new URL/bookmark data
     *      'modeChanged'       -- new URL/bookmark data
     *      'saved'
     *      'canceled'
     *      'complete'
     */
    _init: function()
    {
        var self        = this;
        var opts        = self.options;

        self.element.addClass('ui-form ui-bookmarkPost');

        /********************************
         * Initialize jsonRpc
         *
         */
        if ($.isFunction($.registry))
        {
            var api = $.registry('api');
            if (api && api.jsonRpc)
            {
                opts.jsonRpc = $.extend({}, api.jsonRpc, opts.jsonRpc);
            }
        }

        if ((opts.$status !== null) && (opts.$status.jquery === undefined))
        {
            opts.$status = $(opts.$status);
        }

        /********************************
         * Locate the pieces
         *
         */
        opts.$required    = self.element.find('.required');

        // Hidden fields
        opts.$mode        = self.element.find('input[name=mode]');
        opts.$userId      = self.element.find('input[name=userId]');
        opts.$itemId      = self.element.find('input[name=itemId]');

        if (opts.$mode.length > 0)
        {
            opts.mode = opts.$mode.val();
        }
        else if (opts.mode === null)
        {
            opts.mode = 'save';
        }

        // Text fields
        opts.$name        = self.element.find('input[name=name]');
        opts.$url         = self.element.find('input[name=url]');
        opts.$description = self.element.find('textarea[name=description]');
        opts.$tags        = self.element.find('textarea[name=tags]');

        // Non-text fields
        opts.$favorite    = self.element.find('input[name=isFavorite]');
        opts.$private     = self.element.find('input[name=isPrivate]');
        opts.$worldModify = self.element.find('input[name=worldModify]');
        opts.$rating      = self.element.find('.userRating .stars-wrapper');

        // Buttons
        opts.$save        = self.element.find('button[name=submit]');
        opts.$cancel      = self.element.find('button[name=cancel]');
        opts.$reset       = self.element.find('button[name=reset]');

        // All input[text/password] and textarea elements
        opts.$inputs      = self.element.find(  'input[type=text],'
                                              + 'input[type=password],'
                                              + 'textarea');

        // click-to-edit elements
        opts.$cte         = self.element.find('.click-to-edit');

        // 'suggestions' div -- to be converted to ui.tabs
        opts.$suggestions = self.element.find('.suggestions');

        // 'collapsable' elements -- to be converted to connexions.collapsable
        opts.$collapsable = self.element.find('.collapsable');

        /********************************
         * Instantiate our sub-widgets
         *
         */

        // Tag autocompletion
        opts.$tags.tagInput({
            height:     'min-height',
            width:      'none',         // Let our CSS handle the width
            change:     function() {
                /*
                $.log('connexions.bookmarkPost::'
                       + '$tags.change( "'+ opts.$tags.val() +'" )');
                // */

                // Highlight the "new" tags and validate
                self._highlightTags();
                self.validate();
            },
            autocomplete:   {
                source:     function(req, rsp) {
                    /*
                    $.log('connexions.bookmarkPost::'
                           + '$tags.source('+ req.term +')');
                    // */
                    return self._autocomplete(req, rsp);
                },
                change:     function(e, ui) {
                    /*
                    $.log('connexions.bookmarkPost::'
                           + '$tags.change( "'+ opts.$tags.val() +'" )');
                    // */
                    self._highlightTags();
                },
                close:  function(e, ui) {
                    // A tag has been completed.  Perform highlighting.
                    /*
                    $.log('connexions.bookmarkPost::$tags.close()');
                    // */

                    self._highlightTags();
                }
            }
        });

        // Status - Favorite
        opts.$favorite.checkbox({
            css:        'connexions_sprites',
            cssOn:      'star_fill',
            cssOff:     'star_empty',
            titleOn:    'Favorite: click to remove from Favorites',
            titleOff:   'Click to add to Favorites',
            useElTitle: false,
            hideLabel:  true
        });

        // Status - Private
        opts.$private.checkbox({
            css:        'connexions_sprites',
            cssOn:      'lock_fill',
            cssOff:     'lock_empty',
            titleOn:    'Private: click to share',
            titleOff:   'Public: click to mark as private',
            useElTitle: false,
            hideLabel:  true
        });

        // Status - World Modifiable
        opts.$worldModify.checkbox({
            css:        'connexions_sprites',
            cssOn:      'worldModify_fill',
            cssOff:     'worldModify_empty',
            titleOn:    'World Modifiable: click to mark as editable by you',
            titleOff:   'Editable by you: click to mark as world modifiable',
            useElTitle: false,
            hideLabel:  true
        });

        // Rating - average and user
        opts.$rating.stars({
            //split:    2
        });

        opts.$save.addClass('ui-priority-primary')
                  .button({disabled: true});

        opts.$cancel.addClass('ui-priority-secondary')
                    .button({disabled: false});
        opts.$reset.addClass('ui-priority-secondary')
                    .button({disabled: false});

        opts.$suggestions.tabs();
        opts.$collapsable.collapsable();

        /* Style all remaining input[type=text|password] / textarea controls
         * with ui.input
         */
        opts.$inputs.input();

        // Add 'ui-field-info' for all required fields
        opts.$required.after(  '<div class="ui-field-info">'
                             +  '<div class="ui-field-status"></div>'
                             +  '<div class="ui-field-requirements">'
                             +   'required'
                             +  '</div>'
                             + '</div>');

        opts.$required
                .filter('[name=tags]')
                    .next('.ui-field-info')
                        .find('.ui-field-requirements')
                            .text('comma-separated, 30 characters per tag - '
                                  + 'required');

        /********************************
         * Initialize our state and bind
         * to interesting events.
         *
         */
        self._setStateFromForm();
        self._bindEvents();

        $.ui.dialog.prototype._init.call(self);

        /* In case we're embedded direclty in a page and won't receive
         * an 'open' event.
         */
        self._onOpen();
    },

    /** @brief  On open, (Re)size all 'ui-field-info' elements to match their
     *          corresponding input field.
     */
    _onOpen: function()
    {
        var self    = this;
        var opts    = self.options;

        opts.$required.each(function() {
            var $input = $(this);

            $input.next().css('width', $input.css('width'));
        });
    },

    _setStateFromForm: function()
    {
        // Set the current widget state to the values of it's sub-components
        var self    = this;
        var opts    = self.options;
        var oldMode = opts.mode;

        opts.name        = opts.$name.val();
        opts.description = opts.$description.val();
        opts.tags        = opts.$tags.val();

        opts.isFavorite  = opts.$favorite.checkbox('isChecked');
        opts.isPrivate   = opts.$private.checkbox('isChecked');
        opts.worldModify = opts.$worldModify.checkbox('isChecked');

        opts.url         = opts.$url.val();

        if (opts.$mode.length > 0)
        {
            opts.mode  = opts.$mode.val();
        }

        if (opts.$userId.length > 0)
        {
            opts.userId  = opts.$userId.val();
        }

        if (opts.$userId.length > 0)
        {
            opts.itemId  = opts.$itemId.val();
        }

        if (opts.$rating.length > 0)
        {
            opts.rating  = opts.$rating.stars('value');
        }

        if (opts.tags.length > 0)
        {
            self._highlightTags();
        }

        if (opts.mode === 'modify')
        {
            // Disable those properties that cannot be changed in 'modify' mode
            opts.$url.input('disable');
            opts.$favorite.checkbox('disable');
            opts.$private.checkbox('disable');
            opts.$worldModify.checkbox('disable');
            opts.$rating.stars('disable');
        }
        else
        {
            // Ensure that everything is enabled
            opts.$favorite.checkbox('enable');
            opts.$url.input('enable');
            opts.$private.checkbox('enable');
            opts.$worldModify.checkbox('enable');
            opts.$rating.stars('enable');
        }

        /* If the value of 'mode' is changing, trigger 'modeChanged' making
         * sure this.options.mode reflects the new  value BEFORE triggering.
         */
        if (oldMode !== opts.mode)
        {
            self.element.trigger('modeChanged', opts.mode);
        }
    },

    _setFormFromState: function(newMode)
    {
        // Set the current widget state to the values of it's sub-components
        var self    = this;
        var opts    = self.options;

        /* Set the value of the underlying controls as well as notifying the
         * ui.input widget of the new value
         */
        opts.$name.input('val', opts.name);
        opts.$description.input('val', opts.description);
        opts.$tags.tagInput('val', opts.tags);

        //opts.$name.val(opts.name).input('val', opts.name);
        //opts.$description.val(opts.description).input('val', opts.description);
        //opts.$tags.val(opts.tags).input('val', opts.tags);

        opts.$favorite.checkbox( opts.isFavorite ? 'check' : 'uncheck' );
        opts.$private.checkbox(  opts.isPrivate  ? 'check' : 'uncheck' );
        opts.$worldModify.checkbox(  opts.worldModify  ? 'check' : 'uncheck' );

        /* Do NOT use opts.$url.input('val', opts.url) since this will fire a
         * 'change' event, causing _url_changed() to be invoked, resulting in
         * another call to this method, ...
         */
        opts.$url.val( (opts.itemId && (! $.isNumeric(opts.itemId))
                            ? opts.itemId
                            : opts.url) );

        if (opts.$mode.length > 0)
        {
            opts.$mode.val(newMode);
        }

        if (opts.$userId.length > 0)
        {
            opts.$userId.val(opts.userId);
        }

        if (opts.$userId.length > 0)
        {
            opts.$itemId.val(opts.itemId);
        }

        if (opts.$rating.length > 0)
        {
            //opts.$rating.stars('value', opts.rating);
            opts.$rating.stars('option', 'value', opts.rating)
                        .stars('select', opts.rating);
        }

        if (newMode === 'modify')
        {
            // Disable those properties that cannot be changed in 'modify' mode
            opts.$favorite.checkbox('disable');
            opts.$private.checkbox('disable');
            opts.$worldModify.checkbox('disable');
            opts.$rating.stars('disable');
        }
        else
        {
            // Ensure that everything is enabled
            opts.$favorite.checkbox('enable');
            opts.$private.checkbox('enable');
            opts.$worldModify.checkbox('enable');
            opts.$rating.stars('enable');
        }

        /* If the value of 'mode' is changing, trigger 'modeChanged' making
         * sure this.options.mode reflects the new  value BEFORE triggering.
         */
        if (newMode && (newMode !== opts.mode))
        {
            opts.mode = newMode;
            self.element.trigger('modeChanged', opts.mode);
        }
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function()
    {
        var self            = this;
        var opts            = self.options;
        var tagsTabIndex    = Math.floor(opts.$tags.attr('tabIndex'));

        // Handle a direct click on one of the status indicators
        var _save_click       = function(e, data) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            $.log('connexions.bookmarkPost::_save_click('+ data +')');

            self._performUpdate();

            return false;
        };

        var _cancel_click   = function(e, data) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            // :TODO: "Cancel" notification
            self._trigger('canceled', null, data);
            self._trigger('complete');
        };

        var _reset_click   = function(e, data) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            self.reset();
        };

        var _validation_change  = function(e, data) {
            /* On ANY validation change, remove the 'click-to-edit' class and
             * unbind this listener.
             */
            var $el = $(this);
            if ($el.data('validationInitialized') !== true)
            {
                $el.data('validationInitialized', true);
                return;
            }

            $el.removeClass('click-to-edit')
               .unbind('validationChange');
        };

        var _url_change = function(e, data) {
            var $el = $(this);
            if ($el.hasClass('ui-state-valid'))
            {
                /* We have a new, valid URL.  See if there is a bookmark
                 * that matches.
                 */
                self._url_changed();
            }
        };

        var _validate_form  = function() {
            self.validate();
        };

        /* Context bind this function in 'self/this' so we can use it
         * outside of this routine.
         */
        self._tagClick = function( event ) {
            event.preventDefault();
            event.stopPropagation();

            var $li     = $(this);
            var $item   = $li.find('a:first');
            var tag     = $item.data('id');
            var tags    = opts.$tags.val();

            if ($li.hasClass('user'))
            {
                // This is a 'user' item, likely in the 'People' tab.
                tag = 'for:'+ tag;
            }

            if ($item.hasClass('selected'))
            {
                // De-select / remove
                opts.$tags.tagInput('deleteTag', tag);
            }
            else
            {
                opts.$tags.tagInput('addTag', tag);
            }

            self._highlightTags();
        };

        /** @brief  Handle tab/backtab for input focus changes.  This is
         *          primarily to ensure that opts.$tags receives focus events.
         */
        var keyCode         = $.ui.keyCode;
        var _form_tabFocus  = function(e) {
            var key     = e.keyCode || e.which;

            if (key === keyCode.TAB)
            {
                var $target     = $(e.target);
                var tabIndex    = Math.floor($target.attr('tabIndex'));

                $.log('connexions.bookmarkPost::_form_tabFocus(): '
                      + 'target[ '+ $target.attr('name') +' ], '
                      + 'tabIndex[ '+ tabIndex +' ], '
                      + 'tagsTabIndex[ '+ tagsTabIndex +' ], '
                      + 'key[ '+ key +' ], '
                      + 'shiftKey[ '+ e.shiftKey +' ]');

                /* Tab       == forward  one field
                 * Shift-Tab == backward one field
                 */
                if ( (   e.shiftKey  && ((tabIndex - 1) === tagsTabIndex)) ||
                     ((! e.shiftKey) && ((tabIndex + 1) === tagsTabIndex)) )
                {
                    // opts.$tags is to be the newly focused field
                    $.log('connexions.bookmarkPost::_form_tabFocus(): '
                          + 'trigger focus for opts.$tags');
                    opts.$tags.focus();

                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                }
            }
        };

        /**********************************************************************
         * bind events
         *
         */
        opts.$inputs.bind('validation_change.bookmarkPost',
                                                _validate_form);
        opts.$favorite.bind('change.bookmarkPost',
                                                _validate_form);
        opts.$private.bind('change.bookmarkPost',
                                                _validate_form);
        opts.$worldModify.bind('change.bookmarkPost',
                                                _validate_form);
        opts.$rating.bind('change.bookmarkPost',
                                                _validate_form);

        opts.$cte.bind('validation_change.bookmarkPost',
                                                _validation_change);

        opts.$save.bind('click.bookmarkPost',   _save_click);
        opts.$cancel.bind('click.bookmarkPost', _cancel_click);
        opts.$reset.bind('click.bookmarkPost',  _reset_click);

        opts.$url.bind('validation_change.bookmarkPost',
                                                _url_change);

        opts.$suggestions.delegate('.cloud .cloudItem,'
                                   + '.cloud .Item_List li:not(.header)',
                                   'click.bookmarkPost',
                                                self._tagClick);

        self.element.bind('keydown.bookmarkPost', _form_tabFocus);
        self.element.bind('open.bookmarkPost',    function() {
            self._onOpen();
        });

        _validate_form();
    },

    /** @brief  Perform a Json-RPC call to "update" (possibly save) the
     *          bookmark represented by this dialog.
     */
    _performUpdate: function()
    {
        var self    = this;
        var opts    = self.options;

        if (opts.enabled !== true)
        {
            return;
        }


        // Gather the current data about this item.
        var nonEmpty    = false;
        var params      = {
            /* id is required: For 'Edit' is should be the userId/itemId of
             * this bookmark
             */
            id: {
                userId: opts.userId,
                itemId: opts.itemId
            }
        };

        if (opts.mode === 'save')
        {
            /* For 'Save', userId MUST be empty/null to notify Service_Bookmark
             * to use the authenticated user's id.
             */
            params.id.userId = null;
        }

        // Include all fields that have changed.
        if ( (opts.mode === 'save') ||
             (opts.$name.val() !== opts.name) )
        {
            params.name = opts.$name.val();
            nonEmpty    = true;
        }

        if ( (opts.mode === 'save') ||
             (opts.$description.val() !== opts.description) )
        {
            params.description = opts.$description.val();
            nonEmpty           = true;
        }

        if ( (opts.mode === 'save') ||
             ((opts.$tags.length > 0) &&
              (opts.$tags.val() !== opts.tags)) )
        {
            params.tags = opts.$tags.val();
            nonEmpty    = true;
        }

        if ( (opts.mode === 'save') ||
             (opts.$favorite.checkbox('isChecked') !== opts.isFavorite) )
        {
            params.isFavorite = opts.$favorite.checkbox('isChecked');
            nonEmpty          = true;
        }

        if ( (opts.mode === 'save') ||
             (opts.$private.checkbox('isChecked') !== opts.isPrivate) )
        {
            params.isPrivate = opts.$private.checkbox('isChecked');
            nonEmpty         = true;
        }

        if ( (opts.mode === 'save') ||
             (opts.$worldModify.checkbox('isChecked') !== opts.worldModify) )
        {
            params.worldModify = opts.$worldModify.checkbox('isChecked');
            nonEmpty           = true;
        }

        if ( (opts.mode === 'save') ||
             ((opts.$rating.length > 0) &&
              (opts.$rating.stars('value') !== opts.rating)) )
        {
            params.rating = opts.$rating.stars('value');
            nonEmpty      = true;
        }

        if ( (opts.mode === 'save') ||
             (opts.$url.val() !== opts.url) )
        {
            // The URL has changed -- pass it in
            params.url = opts.$url.val();
            nonEmpty   = true;
        }

        if (nonEmpty !== true)
        {
            // Nothing to save.
            self._trigger('complete');
            return;
        }

        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        self.element.mask();

        var verb    = (opts.mode === 'save'
                        ? 'save'
                        : 'update');

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'bookmark.update', params, {
            success:    function(data, textStatus, req) {
                if (data.error !== null)
                {
                    self._status(false,
                                 'Bookmark '+ verb +' failed',
                                 data.error.message);

                    return;
                }

                self._status(true,
                             'Bookmark '+ verb +' succeeded',
                             'Bookmark '+ verb +'d'
                             /*
                                          (opts.itemId === null
                                            ? 'created'
                                            : 'updated')
                             */
                );

                if (data.result === null)
                {
                    return;
                }

                self.options = $.extend(self.options, data.result);
                opts = self.options;

                if ($.isArray(opts.tags))
                {
                    var tags    = [];
                    $.each(opts.tags, function() {
                        tags.push(this);
                    });

                    opts.tags = tags.join(',');
                }
                if ($.isPlainObject(opts.item))
                {
                    opts.url = opts.item.url;
                }

                // "Save" notification
                self._trigger('saved',    null, data.result);

                /* Finally, update the form state
                 *
                 * :XXX: We do this AFTER triggering 'saved' so any
                 *       'modeChanged' event won't confuse anyone listeing to
                 *       both 'saved' and 'isEventChanged' events since
                 *       technically, the 'saved' event should reflect the
                 *       'mode' value BEFORE the new form data is applied.
                 */
                self._setFormFromState();

                self._trigger('complete');
            },
            error:      function(req, textStatus, err) {
                self._status(false,
                             'Bookmark '+ verb +' failed',
                             textStatus);

                // :TODO: "Error" notification??
            },
            complete:   function(req, textStatus) {
                self.element.unmask();
            }
         });
    },

    /** @brief  After a change to the item's URL, first check to see if there
     *          is a matching bookmark.  If not, perform a HEAD request to
     *          retrieve information.
     */
    _url_changed: function()
    {
        var self    = this;
        var opts    = self.options;

        if (opts.enabled !== true)
        {
            return;
        }


        // Gather the current data about this item.
        var url     = opts.$url.val();
        var params  = {
            /* id is required: For 'Edit' is should be the userId/itemId of
             * this bookmark
             */
            id: { userId: opts.userId, itemId: url }
        };

        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        self.element.mask();

        /* Perform a JSON-RPC call to attempt to retrieve new bookmark
         * information.
         */
        var bookmarkFound   = false;
        $.jsonRpc(opts.jsonRpc, 'bookmark.find', params, {
            success:    function(data, textStatus, req) {
                if (data.error !== null)
                {
                    // Let 'bookmarkFound' remain false
                    $.notify({title: 'Cannot retrieve bookmark',
                              text:  data.error.message});
                    /*
                    self._status(false,
                                 'Cannot retrieve bookmark',
                                 data.error.message);
                    // */
                    return;
                }
                if (data.result === null)
                {
                    // Let 'bookmarkFound' remain false
                    return;
                }

                bookmarkFound = true;

                // Update the presentation with the new bookmark data.
                self.options = $.extend(self.options, data.result);
                opts = self.options;

                if ($.isArray(opts.tags))
                {
                    var tags    = [];
                    $.each(opts.tags, function() {
                        tags.push(this);
                    });

                    opts.tags = tags.join(',');
                }
                if ($.isPlainObject(opts.item) && opts.item.url)
                {
                    opts.url = opts.item.url;
                }
                else
                {
                    opts.url = url;
                }

                self._setFormFromState('edit');
                self.validate();

                self.element.trigger('urlChanged');
            },
            error:      function(req, textStatus, err) {
                $.notify({title: 'Cannot retrieve bookmark',
                          text:  textStatus});
                /*
                self._status(false,
                             'Cannot retrieve bookmark',
                             textStatus);
                // */

                // :TODO: "Error" notification??
            },
            complete:   function(req, textStatus) {
                self.element.unmask();

                // If a matching bookmark wasn't found, perform a HEAD request.
                if (bookmarkFound === false)
                {
                    $.notify({title: 'Pulling information',
                              text:  'Performing a HEAD request on URL<br />'
                                     +  "<tt>'"+ url +"'</tt>"});
                    self._headers( url );
                }
                else
                {
                    /********************************************************
                     * Also, update the recommended tags section in the
                     * suggestions area.
                     *
                     */
                    self._update_recommendedTags( url );
                }
            }
         });
    },

    /** @brief  Callback for _headers() to process retrieved site headers.
     *  @param  headers     An object containing title and meta items from
     *                      the sites <head> section.
     *
     */
    _headers_success: function(headers)
    {
        var self    = this;
        var opts    = self.options;

        if ( ! opts.$name.input('hasChanged') )
        {
            // See if we can find the title
            if (headers.title.length > 0)
            {
                /* Do NOT use input('val') here since we don't want to 
                 * alter the field's default value.
                 */
                opts.$name.val(headers.title );
                opts.$name.blur();
            }
        }

        if ( ! opts.$description.input('hasChanged') )
        {
            // See if there is a '<meta name="description">'
            var $desc   = headers.meta.filter('meta[name=description]');
            if ($desc.length > 0)
            {
                opts.$description.val($desc.attr('content') );
                opts.$description.blur();
            }
        }

        if ( ! opts.$tags.input('hasChanged') )
        {
            // See if there is a '<meta name="keywords">'
            var $keywords   = headers.meta.filter('meta[name=keywords]');
            if ($keywords.length > 0)
            {
                opts.$tags.tagInput('val', $keywords.attr('content') );
                opts.$tags.blur();
            }
        }
    },

    /** @brief  Make a request to our server for the retrieval of 'title' and
     *          'meta' items from within the <head> element of the web page at
     *          the given URL.
     *  @param  url     The desired URL.
     *  @param  callback    The callback to invoke upon successful retrieval:
     *                          callback( headers )
     */
    _headers: function(url, callback)
    {
        var self    = this;
        var opts    = self.options;

        if (self.headersUrl === url)
        {
            // We've already done a check for this URL.
            return;
        }
        self.headersUrl = url;


        /********************************************************
         * Generate a JSON-RPC to perform the header retrieval.
         *
         */
        var params  = {
            url:        url,
            keepTags:   'title,meta'
        };

        $.jsonRpc(opts.jsonRpc, 'util.getHead', params, {
            success:    function(data, textStatus, req) {
                if (data.error !== null)
                {
                    /*
                    self._status(false,
                                 'URL header retrieval',
                                 data.error.message);
                    // */

                    self.headersUrl = null;
                    return;
                }

                if (data.result === null)
                {
                    self.headersUrl = null;
                    return;
                }

                var $head   = $('<div />');
                $head.html( data.result.html );

                // Now, pull out all title and meta items
                var headers = {
                    title:  $head.find('title').text(),
                    meta:   $head.find('meta')
                };

		        if ($.isFunction(callback))
                {
			        callback(headers);
		        }
                else
                {
                    self._headers_success(headers);
                }
            },
            error:      function(req, textStatus, err) {
                // :TODO: "Error" notification / invalid URL??
                self.headersUrl = null;
            },
            complete:   function(req, textStatus) {
                // :TODO: Some indication of completion?
            }
         });

        /********************************************************
         * Also, update the recommended tags section in the
         * suggestions area.
         *
         */
        self._update_recommendedTags(url);
    },

    /** @brief  Make a request to our server for the retrieval of recommended
     *          tags for the given 'url'.
     *  @param  url     The desired URL.
     */
    _update_recommendedTags: function(url)
    {
        var self    = this;
        var opts    = self.options;
        var apiUrl  = document.location.protocol +'//'+ document.location.host
                    + $.registry('urls').base +'post/';

        $.ajax({
            url:    apiUrl,
            data:   {
                format: 'partial',
                part:   'main-tags-recommended',
                url:    url
            },
            success: function(data) {
                var $content    = opts.$suggestions
                                        .find('#suggestions-tags '
                                                +'.tags-recommended .content');

                $content.html( data );

                self._highlightTags();
            }
        });
    },

    _highlightTags: function()
    {
        var self    = this;
        var opts    = self.options;

        if (opts.$suggestions.length < 1)
        {
            // No suggestions area so no tags to highlight
            return;
        }

        // Find all tags in the suggestions area
        var $cloudTags  = opts.$suggestions.find('.cloud .cloudItem a,'
                                                 + '.cloud .Item_List li a');

        // Remove any existing highlights
        $cloudTags.filter('.selected').removeClass('selected');

        // Highlight any currently selected tags.
        var tags    = opts.$tags.tagInput('option', 'tags');  //val();
        var nTags   = tags.length;
        var tag     = null;

        if (nTags < 1)
        {
            return;
        }

        //tags  = tags.split(/\s*,\s*/);
        //nTags = tags.length;

        var forRe   = /^for:/;
        for (var idex = 0; idex < nTags; idex++)
        {
            tag = tags[idex];
            if (tag.length < 1)
            {
                continue;
            }

            tag = tag.replace('"', '\"');
            if (forRe.test(tag))
            {
                // 'for:' user sharing tag
                tag = tag.replace(forRe, '');
            }
            else
            {
                tag = tag.toLowerCase();
            }
            //$.log('connexions.bookmarkPost::_highlightTags('+ tag +')');

            $cloudTags.filter('.item[data-id="'+ tag +'"]')
                      .addClass('selected');

            /*
            $cloudTags.filter(':contains("'+ tag +'")')
                      .addClass('selected');
            // */
        }
    },

    _autocomplete: function(request, response)
    {
        var self    = this;
        var opts    = self.options;
        var term    = opts.$tags.tagInput('option', 'term');
        var re      = new RegExp(term, 'gi');
        var params  = {
            id:     { userId: opts.userId, itemId: opts.itemId },
            term:   term
        };


        /* If no itemId was provided (or the URL has changed), use the current
         * URL value.
         */
        if ( (params.id.itemId === null) ||
             (opts.$url.val()  !== opts.url) )
        {
            // The URL has changed -- pass it in
            params.id.itemId = opts.$url.val();
        }

        $.jsonRpc(opts.jsonRpc, 'bookmark.autocompleteTag', params, {
            success:    function(ret, txtStatus, req){
                if (ret.error !== null)
                {
                    self.element.trigger('error', [txtStatus, req, ret.error]);
                    return;
                }

                var res = $.map(ret.result, function(item) {
                    var str     = item.tag.replace(re, '<b>'+ term +'</b>');
                    var weight  = item.userItemCount;

                    return {
                        label:   '<span class="name">'+ str +'</span>'
                                +'<span class="count">'+ weight +'</span>',
                        value:  item.tag
                    };
                });

                response( res );
                self.element.trigger('success', [ret, txtStatus, req]);
            },
            error:      function(req, txtStatus, e) {
                self.element.trigger('error', [txtStatus, req]);
            }
        });
    },

    _status: function(isSuccess, title, text)
    {
        var self    = this;
        var opts    = self.options;

        if (opts.$status === null)
        {
            if ((title !== undefined) && (text !== undefined))
            {
                $.notify({title: title, text: text});
            }
        }
        else
        {
            var msg = '';
            /*
            if (title !== undefined)
            {
                msg += '<h3>'+ title +'</h3>';
            }
            */
            if (text !== undefined)
            {
                msg += text;
            }

            opts.$status.html(msg);

            if (isSuccess)
            {
                opts.$status.removeClass('error').addClass('success');
            }
            else
            {
                opts.$status.removeClass('success').addClass('error');
            }
        }
    },

    /************************
     * Public methods
     *
     */
    isEnabled: function()
    {
        return this.options.enabled;
    },

    enable: function()
    {
        var self    = this;
        var opts    = self.options;

        if (! opts.enabled)
        {
            opts.enabled = true;
            self.element.removeClass('ui-state-disabled');

            opts.$favorite.checkbox('enable');
            opts.$private.checkbox('enable');
            opts.$worldModify.checkbox('enable');
            opts.$rating.stars('enable');
            opts.$inputs.input('enable');

            self._trigger('enabled', null, true);
        }
    },

    disable: function()
    {
        var self    = this;
        var opts    = self.options;

        if (opts.enabled)
        {
            opts.enabled = false;
            self.element.addClass('ui-state-disabled');

            opts.$favorite.checkbox('disable');
            opts.$private.checkbox('disable');
            opts.$worldModify.checkbox('disable');
            opts.$rating.stars('disable');
            opts.$inputs.input('disable');

            self._trigger('disabled', null, true);
        }
    },

    /** @brief  Reset any ui.input fields to their original
     *          (creation or direct set) values.
     */
    reset: function()
    {
        var self        = this;
        var opts        = self.options;

        opts.$favorite.checkbox('reset');
        opts.$private.checkbox('reset');
        opts.$worldModify.checkbox('reset');
        opts.$rating.stars('reset');
        opts.$inputs.input('reset');

        self._trigger('reset');
        self.headersUrl = undefined;

        self.validate();
    },

    validate: function()
    {
        var self        = this;
        var opts        = self.options;
        var isValid     = true;
        var hasChanged  = self.hasChanged();

        if (hasChanged)
        {
            opts.$required.each(function() {
                if (! $(this).hasClass('ui-state-valid'))
                {
                    isValid = false;
                    return false;
                }
            });

            if (isValid)
            {
                self._status(true);
            }
            else
            {
                self._status(false);
            }
        }

        if ( isValid && ((opts.mode === 'save') || hasChanged) )
        {
            opts.$save.button('enable');
        }
        else
        {
            opts.$save.button('disable');
        }
    },

    /** @brief  Have any of the ui.input fields changed from their original
     *          values?
     *
     *  @return true | false
     */
    hasChanged: function()
    {
        var self        = this;
        var opts        = self.options;
        var hasChanged  = false;

        // Has anything changed from the forms initial values?
        opts.$inputs.each(function() {
            if ($(this).input('hasChanged'))
            {
                hasChanged = true;
                return false;
            }
        });

        if ((! hasChanged) &&
            (opts.$favorite.checkbox('hasChanged')      ||
             opts.$private.checkbox('hasChanged')       ||
             opts.$worldModify.checkbox('hasChanged')   ||
             opts.$rating.stars('hasChanged')) )
        {
            hasChanged = true;
        }

        return hasChanged;
    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Cleanup
        opts.$save.removeClass('ui-priority-primary');
        opts.$cancel.removeClass('ui-priority-secondary');
        opts.$reset.removeClass('ui-priority-secondary');
        opts.$required.next('.ui-field-info').remove();

        self.element.removeClass('ui-form');

        // Unbind events
        self.element.unbind('.bookmarkPost');

        opts.$inputs.unbind('.bookmarkPost');
        opts.$favorite.unbind('.bookmarkPost');
        opts.$private.unbind('.bookmarkPost');
        opts.$worldModify.unbind('.bookmarkPost');
        opts.$rating.unbind('.bookmarkPost');
        opts.$cte.unbind('.bookmarkPost');
        opts.$save.unbind('.bookmarkPost');
        opts.$cancel.unbind('.bookmarkPost');
        opts.$reset.unbind('.bookmarkPost');

        opts.$url.unbind('.bookmarkPost');
        opts.$tags.unbind('.bookmarkPost');

        opts.$suggestions.undelegate('.cloud .cloudItem,'
                                     + '.cloud .Item_List li:not(.header)',
                                     '.bookmarkPost');

        // Remove added elements
        opts.$favorite.checkbox('destroy');
        opts.$private.checkbox('destroy');
        opts.$worldModify.checkbox('destroy');
        opts.$rating.stars('destroy');
        opts.$inputs.input('destroy');
        opts.$save.button('destroy');
        opts.$cancel.button('destroy');
        opts.$reset.button('destroy');

        opts.$suggestions.tabs('destroy');
        opts.$collapsable.collapsable('destroy');
    }
});

}(jQuery));
/** @file
 *
 *  An extension of ui.autocomplete to handle completion based upon the
 *  position of the cursor.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.autocomplete.js
 *      jquery.ui.subclass.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, document:false */
(function($) {

$.widget('connexions.autocomplete', $.ui.autocomplete, {
    version: "0.0.1",
    options: {
        delay:      300,
        minLength:  2,

        // If a separator is NOT specified, revert to jquery-ui.autocomplete
        separator:  null    //','
    },

    _init: function() {
        var self    = this;
        var opts    = self.options;
        if (opts.separator === null)
        {
            // No special setup -- allow our super-class to run.
            return $.ui.autocomplete.prototype._init.apply(this, arguments);
        }

        $.each(['search','focus','select'], function() {
            var tName   = this;
            var cbLocal = self['_do_' + tName];
            var cb      = ($.isFunction(opts[tName]) ? opts[tName] : null);

            /* Over-ride this _trigger callbacks, invoking the existing
             * callback iff our local callback returns something other than
             * false.
             */
            opts[this] = function(event, data) {
                var res = cbLocal.call(self, event, data);
                if (res !== false)
                {
                    if (cb !== null)
                    {
                        res = cb.call( self.element[0], event, data);
                    }
                }

                return res;
            };
        });
    },

    /**********************************************************
     * Private methods
     *
     */
    _do_search: function() {
        var term    = this._curTerm();
        //$.log("connexions.autocomplete:_do_search(): term[ "+ term +" ]");

        if (term.length < this.options.minLength)
        {
            return false;
        }
    },

    _do_focus: function() {
        // prevent insertion on focus
        return false;
    },

    _do_select: function(event, ui) {
        var opts    = this.options;
        var reClean = new RegExp('(\\s*'+ opts.separator +'\\s*)+', 'g');
        var val     = opts.val.substring(0, opts.start)
                    + ui.item.value
                    + opts.val.substring(opts.end)
                    + opts.separator
                    + ' ';
        val = val.replace(reClean, opts.separator+' ');

        /*
        $.log("connexions.autocomplete:_do_select(): "
              + "opts.val[ "+ opts.val +" ], "
              + "[ "+ opts.start +' .. '+ opts.end +" ], "
              + " === val[ "+ val +" ]");
        // */


        //this.value = val;
        this.element.val(val);
        return false;
    },

    _curTerm: function() {
        var self    = this;
        var opts    = self.options;

        opts.start  = self._selectionStart();
        opts.end    = self._selectionEnd();
        opts.val    = self.element.val();
        if (opts.start === opts.end)
        {
            /* Current term is NOT selected.  Look backward from 'start' to
             * find the previous separator, and forward from 'end' to the next
             * separator.
             */
            opts.end    = opts.val.indexOf(opts.separator, opts.start);
            if (opts.end < 0)
            {
                opts.end = opts.val.length;
            }

            var sep     = opts.val.indexOf(opts.separator, 0);
            var newSt   = 0;
            while ((sep >= 0) && (sep < opts.start))
            {
                while ( (sep < opts.end) &&
                        (opts.val.substr(++sep,1).match(/\s/)) )
                {
                }

                newSt = sep;
                sep   = opts.val.indexOf(opts.separator, sep);
            }

            opts.start = newSt;
        }

        opts.term = opts.val.substring(opts.start, opts.end);

        return opts.term;
    },

    _selectionStart: function() {
        var self    = this;
        var val     = 0;
        if (self.element[0].createTextRange)
        {
            // IE
            var range   = document.selection.createRange().duplicate();
            var ival    = self.element.val();
            range.moveEnd('character', ival.length);
            if (range.text === '')
            {
                val = ival.length;
            }
            else
            {
                val = ival.lastIndexOf(range.text);
            }
        }
        else
        {
            val = self.element.attr('selectionStart');
        }

        return val;
    },

    _selectionEnd: function() {
        var self    = this;
        var val     = 0;
        if (self.element[0].createTextRange)
        {
            // IE
            var range   = document.selection.createRange().duplicate();
            var ival    = self.element.val();
            range.moveEnd('character', -(ival.length));
            val = range.text.length;
        }
        else
        {
            val = self.element.attr('selectionEnd');
        }

        return val;
    }
});

}(jQuery));
/** @file
 *
 *  Javascript interface/wrapper for the presentation of a single bookmark.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered bookmark item (View_Helper_HtmlBookmark):
 *      - convert (optional Favorite and Privacy checkboxes into image-based
 *        hover buttons;
 *      - convert any (optional) star rating presentation to an active ui.stars
 *        widget;
 *      - allow in-line, on demand editing of the bookmark if it has a
 *        '.control .item-edit' link;
 *      - allow in-line, on demand deletion of the bookmark if it has a
 *        '.control .item-delete' link;
 *      - allow in-line, on demand saving of the bookmark if it has a
 *        '.control .item-save' link;
 *
 *  View_Helper_HtmlBookmark will generate HTML for a bookmark similar to:
 *     <form class='bookmark'>
 *       <input type='hidden' name='userId' value='...' />
 *       <input type='hidden' name='itemId' value='...' />
 *
 *       <!-- Status -->
 *       <div class='status'>
 *         <div class='favorite'>
 *           <input type='checkbox' name='isFavorite' value='...' />
 *         </div>
 *         <div class='private'>
 *           <input type='checkbox' name='isPrivate' value='...' />
 *         </div>
 *       </div>
 *
 *       <!-- Stats: item:stats -->
 *       <div class='stats'>
 *
 *         <!-- item:stats:count -->
 *         <a class='count' ...> count </a>
 *
 *         <!-- item:stats:rating -->
 *         <div class='rating'>
 *           <div class='stars'>
 *
 *             <!-- item:stats:rating:stars -->
 *             <div class='ui-stars-wrapper'> ... </div>
 *           </div>
 *
 *           <!-- item:stats:rating:info -->
 *           <div class='info'>
 *             <span class='count'> count </span> raters,
 *             <span class='average'> average </span> avg.
 *           </div>
 *         </div>
 *       </div>
 *
 *       <!-- Bookmark Data: item:data -->
 *       <div class='data'>
 *
 *         <!-- User Identification: item:data:userId -->
 *         <div class='userId'>
 *           <a ...>
 *
 *             <!-- item:data:userId:avatar -->
 *             <div class='img'>
 *               <img ... avatar image ... />
 *             </div>
 *
 *             <!-- item:data:userId:id -->
 *             <span class='name'> userName </span>
 *           </a>
 *         </div>
 *
 *         <!-- Owner controls -->
 *         <div class='control'>
 *           <a class='item-edit' ...>EDIT</a> |
 *           <a class='item-delete' ...>DELETE</a>
 *
 *           <a class='item-save' ...>SAVE</a>
 *         </div class='control'>
 *
 *         <!-- Item Name: item:data:itemName -->
 *         <h4 class='itemName'> <a ...> title </a> </h4>
 *
 *         <!-- Item Url: item:data:url -->
 *         <div class='url'><a ..> url </a></div>
 *
 *         <!-- Item Description: item:data:description -->
 *         <div class='description'>
 *
 *           <!-- Item Description: item:data:description:summary -->
 *           <div class='summary'> description summary </div>
 *
 *           <!-- Item Description: item:data:description:full -->
 *           <div class='full'> description full </div>
 *         </div class='description'>
 *
 *         <!-- Item Tags: item:data:tags -->
 *         <ul class='tags'>
 *           <li class='tag'><a ...> tag </a></li>
 *           ...
 *         </ul>
 *
 *         <!-- Item Dates: item:data:dates -->
 *         <div class='dates'>
 *
 *           <!-- item:data:dates:tagged -->
 *           <div class='tagged'> tagged date </div>
 *
 *           <!-- item:data:dates:updated -->
 *           <div class='updated'> updated date </div>
 *         </div>
 *       </div>
 *     </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.bookmark", {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Widget state (mirrors Model_Bookmark)
        userId:     null,
        itemId:     null,
        name:       null,
        description:null,
        rating:     null,
        isFavorite: null,
        isPrivate:  null,
        worldModify:null,

        tags:       null,
        url:        null,

        // taggedOn and updateOn are not user editable

        /* A change callback
         *      function(data)
         *          return true  to allow the change
         *          return false to abort the change
         */
        change:     null,

        /* General Json-RPC information:
         *  {version:   Json-RPC version,
         *   target:    URL of the Json-RPC endpoint,
         *   transport: 'POST' | 'GET'
         *  }
         *
         * If not provided, 'version', 'target', and 'transport' are
         * initialized from:
         *      $.registry('api').jsonRpc
         *
         * (which is initialized from
         *      application/configs/application.ini:api
         *  via
         *      application/layout/header.phtml
         *
         */
        jsonRpc:    null,

        /* If the JSON-RPC method is GET, the apiKey for the authenticated user
         * is required for any methods that modify data.
         */
        apiKey:     null,

        // Widget state
        enabled:    true
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'enabled'
     *      'disabled'
     *      'deleted'
     */
    _create: function()
    {
        var self        = this;
        var opts        = self.options;

        /********************************
         * Initialize jsonRpc
         *
         */
        if ($.isFunction($.registry))
        {
            var api = $.registry('api');
            if (api && api.jsonRpc)
            {
                opts.jsonRpc = $.extend({}, api.jsonRpc, opts.jsonRpc);
            }
        }

        /********************************
         * Locate the pieces
         *
         */
        self.$userId      = self.element.find('input[name=userId]');
        self.$itemId      = self.element.find('input[name=itemId]');
        self.$name        = self.element.find('.itemName a');
        self.$description = self.element.find('.description');

        self.$rating      = self.element.find('.rating .stars .owner');
        self.$favorite    = self.element.find('input[name=isFavorite]');
        self.$private     = self.element.find('input[name=isPrivate]');
        self.$worldModify = self.element.find('input[name=worldModify]');

        self.$dates       = self.element.find('.dates');
        self.$dateTagged  = self.$dates.find('.tagged');
        self.$dateUpdated = self.$dates.find('.updated');

        //self.$tags        = self.element.find('input[name=tags]');
        self.$tags        = self.element.find('.tags');
        self.tagTmpl      = self.$tags.find('.tag-template').html();

        self.$edit        = self.element.find('.control .item-edit');
        self.$delete      = self.element.find('.control .item-delete');
        self.$save        = self.element.find('.control .item-save');

        self.$url         = self.element.find('.itemName a,.url a');

        /********************************
         * Localize dates
         *
         */
        self._localizeDates();

        /********************************
         * Instantiate our sub-widgets
         *
         */

        // Status - Favorite
        self.$favorite.checkbox({
            css:        'connexions_sprites',
            cssOn:      'star_fill',
            cssOff:     'star_empty',
            titleOn:    'Favorite: click to remove from Favorites',
            titleOff:   'Click to add to Favorites',
            useElTitle: false,
            hideLabel:  true
        });

        // Status - Private
        self.$private.checkbox({
            css:        'connexions_sprites',
            cssOn:      'lock_fill',
            cssOff:     'lock_empty',
            titleOn:    'Private: click to share',
            titleOff:   'Public: click to mark as private',
            useElTitle: false,
            hideLabel:  true
        });

        // Status - World Modifiable
        self.$worldModify.checkbox({
            css:        'connexions_sprites',
            cssOn:      'worldModify_fill',
            cssOff:     'worldModify_empty',
            titleOn:    'World Modifiable: click to mark as editable by you',
            titleOff:   'Editable by you: click to mark as world modifiable',
            useElTitle: false,
            hideLabel:  true
        });

        // Rating - average and user
        self.$rating.stars({
            //split:    2
        });


        /********************************
         * Initialize our state and bind
         * to interesting events.
         *
         */
        self._setState();
        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function()
    {
        var self    = this;
        var opts    = self.options;

        self._squelch = false;

        // Handle a direct click on one of the status indicators
        var _update_item      = function(e, data) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            self._performUpdate();
        };

        // Handle item-edit
        var _edit_click  = function(e) {
            var mode    = $(this).text().toLowerCase();

            if (self.options.enabled === true)
            {
                // Popup a dialog with a post form for this item.
                var formUrl = self.$edit.attr('href')
                            +   '&format=partial'
                            +   '&part=main';
                            //+   '&excludeSuggestions=true';

                $.get(formUrl,
                      function(data) {
                        self._showBookmarkDialog(data, mode);
                      });
            }

            e.preventDefault();
            e.stopPropagation();
        };

        // Handle item-delete
        var _delete_click  = function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            if (self.options.enabled !== true)
            {
                return;
            }
            self.disable();

            self.$delete.confirmation({
                question:   'Really delete?',
                confirmed:  function() {
                    self._performDelete();
                },
                closed:     function() {
                    self.enable();
                }
            });
        };

        // Handle item-save
        var _save_click  = function(e) {
            if (self.options.enabled === true)
            {
                // Popup a dialog with a post form for this item.
                var formUrl = self.$save.attr('href')
                            +   '&format=partial'
                            +   '&part=main';

                $.get(formUrl,
                      function(data) {
                        self._showBookmarkDialog(data, 'save');
                      });
            }

            e.preventDefault();
            e.stopPropagation();
        };

        /**********************************************************************
         * bind events
         *
         */

        /*
        self.$favorite.bind('click.bookmark',     _update_item);
        self.$private.bind('click.bookmark',      _update_item);
        self.$worldModify.bind('click.bookmark',  _update_item);
        self.$rating.bind('click.bookmark',       _update_item);
        */

        self.element.bind('change.bookmark',    _update_item);

        self.$edit.bind('click.bookmark',       _edit_click);
        self.$delete.bind('click.bookmark',     _delete_click);
        self.$save.bind('click.bookmark',       _save_click);
    },


    /** @brief  Given a date/time string, localize it to the client-side
     *          timezone.
     *  @param  utcStr      The date/time string in UTC and in the form:
     *                          YYYY-MM-DD HH:mm:ss
     *
     *  @return The localized time string.
     */
    _localizeDate: function(utcStr, groupBy)
    {
        var self        = this;
        groupBy         = (groupBy === undefined
                            ? self.$dates.data('groupBy')
                            : groupBy);

        var timeOnly    = ((groupBy === undefined) ||
                           (groupBy.indexOf(utcStr.substr(0,10)) < 0)
                            ? false // NOT timeOnly
                            : true  // timeOnly
        );

        return $.date2str( $.str2date( utcStr ), timeOnly );
    },
    // */

    /** @brief  Update presented dates to the client-side timezone.
     */
    _localizeDates: function()
    {
        var self    = this;
        var groupBy = self.$dates.data('groupBy');
        var utcStr;
        var newStr;
        var timeOnly;

        if (self.$dateTagged.length > 0)
        {
            newStr = self._localizeDate(self.$dateTagged.data('utcdate'),
                                        groupBy);

            self.$dateTagged.html( newStr );
        }

        if (self.$dateUpdated.length > 0)
        {
            newStr = self._localizeDate(self.$dateUpdated.data('utcdate'),
                                        groupBy);

            self.$dateUpdated.html( newStr );
        }
    },

    _performDelete: function( )
    {
        var self    = this;
        var opts    = self.options;
        var params  = {
            id:     { userId: opts.userId, itemId: opts.itemId }
        };
        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'bookmark.delete', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'Bookmark delete failed',
                        text:  '<p class="error">'
                             +   (data ? data.error.message : '')
                             + '</p>'
                    });

                    return;
                }

                // Trigger a deletion event for our parent
                self._trigger('deleted');
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'Bookmark delete failed',
                    text:  '<p class="error">'
                         +   textStatus
                         + '</p>'
                });
            },
            complete:   function(req, textStatus) {
            }
         });
    },

    _performUpdate: function()
    {
        var self    = this;
        var opts    = self.options;

        if ((opts.enabled !== true) || (self._squelch === true))
        {
            return;
        }

        // Gather the current data about this item.
        var nonEmpty    = false;
        var params      = {
            id: {
                userId: opts.userId,
                itemId: opts.itemId
            }
        };

        // Only include those portions that have changed
        if (self.$name.text() !== opts.name)
        {
            params.name = self.$name.text();
            nonEmpty    = true;
        }

        if (self.$description.text() !== opts.description)
        {
            params.description = self.$description.text();
            nonEmpty           = true;
        }

        /* Tags are currently NOT directly editable, so ignore them for now.
        if ( (self.$tags.length > 0) &&
             (self.$tags.text() !== opts.tags) )
        {
            params.tags = self.$tags.text();
            nonEmpty    = true;
        }
        // */

        if (self.$favorite.checkbox('isChecked') !== opts.isFavorite)
        {
            params.isFavorite = self.$favorite.checkbox('isChecked');
            nonEmpty          = true;
        }

        if (self.$private.checkbox('isChecked') !== opts.isPrivate)
        {
            params.isPrivate = self.$private.checkbox('isChecked');
            nonEmpty         = true;
        }

        if (self.$worldModify.checkbox('isChecked') !== opts.worldModify)
        {
            params.worldModify = self.$worldModify.checkbox('isChecked');
            nonEmpty           = true;
        }

        if ( (self.$rating.length > 0) &&
             (self.$rating.stars('value') !== opts.rating) )
        {
            params.rating = self.$rating.stars('value');
            nonEmpty      = true;
        }

        if (self.$url.attr('href') !== opts.url)
        {
            // The URL has changed -- pass it in
            params.url = self.$url.attr('href');
            nonEmpty   = true;
        }

        if (nonEmpty !== true)
        {
            return;
        }

        $.log('connexions.bookmark::_performUpdate()');

        /* If there is a 'change' callback, invoke it.
         *
         * If it returns false, terminate the change.
         */
        if ($.isFunction(self.options.change))
        {
            if (! self.options.change(params))
            {
                // Rollback state.
                self._resetState();

                return;
            }
        }

        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'bookmark.update', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'Bookmark update failed',
                        text:  '<p class="error">'
                             +   (data ? data.error.message : '')
                             + '</p>'
                    });

                    // rollback state
                    self._resetState();
                    return;
                }

                if (data.result === null)
                {
                    return;
                }

                self._refreshBookmark(data.result);
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'Bookmark update failed',
                    text:  '<p class="error">'
                         +   textStatus
                         + '</p>'
                });

                // rollback state
                self._resetState();
            },
            complete:   function(req, textStatus) {
            }
         });
    },

    /** @brief  Given new bookmark data from either an update or an edit,
     *          refresh the bookmark presentation from the provided data.
     *  @param  data    Result data representing the bookmark.
     */
    _refreshBookmark: function(data)
    {
        var self    = this;
        var opts    = self.options;

        self._squelch = true;

        if (opts.itemId !== data.itemId)
        {
            // Update the edit and delete URLs
            var url;
            url = self.$edit.attr('href')
                    .replace('id='+ opts.userId +':'+ opts.itemId,
                             'id='+ opts.userId +':'+ data.itemId);
            self.$edit.attr('href', url);

            /* :NOTE: The $delete url isn't really used.  See _performDelete()
             *        This change is really about keeping the UI consistent.
             */
            url = self.$delete.attr('href')
                    .replace('/'+ opts.userId +':'+ opts.itemId,
                             '/'+ opts.userId +':'+ data.itemId);
            self.$delete.attr('href', url);
        }

        // Include the updated data
        self.$itemId.val( data.itemId );
        self.$name.text(  data.name );

        // Update description (both full and summary if they're presented)
        var $desc_full  = self.$description.find('.full');
        var $desc_sum   = self.$description.find('.summary');
        if ($desc_sum.length > 0)
        {
            // summarize will perform an $.htmlentities() on the result.
            if (data.description.length > 0)
            {
                $desc_sum.html( '&mdash; '+ $.summarize( data.description ) );
            }
            else
            {
                $desc_sum.html( '' );
            }
        }
        if ($desc_full.length > 0)
        {
            $desc_full.html( $.esc(data.description) );
        }

        // Update tags
        if ($.isArray(data.tags) && (self.tagTmpl.length > 0))
        {
            /* Update the tag using the '.tag-template' DOM element that SHOULD
             * have been found in the tags area.
             */
            var tagHtml = '';

            $.each(data.tags, function() {
                tagHtml += self.tagTmpl.replace(/%tag%/g, this);
            });

            // Replace the existing tags with the new.
            self.$tags.html( tagHtml );
        }

        self.$rating.stars('select',data.rating);

        self.$favorite.checkbox((data.isFavorite      ? 'check' : 'uncheck') );
        self.$private.checkbox( (data.isPrivate       ? 'check' : 'uncheck') );
        self.$worldModify.checkbox( (data.worldModify ? 'check' : 'uncheck') );
        self.$url.attr('href',  data.url);

        // Update and localize the dates
        self.$dateTagged.data( 'utcdate', data.taggedOn  );
        self.$dateUpdated.data('utcdate', data.updatedOn );
        self._localizeDates();

        // Alter our parent to reflect 'isPrivate'
        var parent  = self.element.parent();
        if (data.isPrivate)
        {
            parent.addClass('private');
        }
        else
        {
            parent.removeClass('private');
        }
        self._squelch = false;

        // set state
        self._setState();

        // Animate a highlight of this bookmark
        self.element.effect('highlight', {}, 2000);
    },

    _showBookmarkDialog: function(html, mode)
    {
        var self    = this;
        var opts    = self.options;
        var title  = (mode === 'save'
                        ? 'Save'
                        : (mode === 'edit'
                            ? 'Edit'
                            : 'Modify'))
                   + ' bookmark';
        var dialog  = '<div>'      // dialog {
                    +  '<div class="ui-validation-form">'  // validation-form {
                    +   '<div class="userInput lastUnit">'
                           // bookmarkPost HTML goes here
                    +   '</div>'
                    +  '</div>'                            // validation-form }
                    + '</div>';    // dialog }

        var $pane   = self.element.parents('.pane:first');
        var $html   = $(dialog).hide()
                               .appendTo( 'body' );
        var $dialog = $html.first();

        /* Establish an event delegate for the 'modeChanged' event BEFORE
         * evaluating the incoming HTML 
         */
        $dialog.delegate('form', 'modeChanged.bookmark', function() {
            // Update the dialog header
            mode = $dialog.find('form:first')
                            .bookmarkPost('option', 'mode');
            title  = (mode === 'save'
                        ? 'Save'
                        : (mode === 'edit'
                            ? 'Edit YOUR'
                            : 'Modify'))
                   + ' bookmark';
            if ($dialog.data('dialog'))
            {
                // Update the dialog title
                $dialog.dialog('option', 'title', title);
            }
        });

        /* Now, include the incoming bookmarkPost HTML -- this MAY cause the
         * 'modeChanged' event to be fired if the widget finds that the
         * URL is already bookmarked by the current user.
         */
        $dialog.find('.userInput').html( html );
        var $form       = $dialog.find('form:first');
        var isModal     = false;
        var $overlayed  = $('body');

        self.disable();

        $dialog.dialog({
            autoOpen:   true,
            title:      title,
            dialogClass:'ui-dialog-bookmarkPost',
            width:      480,
            resizable:  false,
            modal:      isModal,
            open:       function(event, ui) {
                $overlayed.overlay($dialog.maxZindex() - 2);

                /* Notify the connexions.bookmarkPost widget that the dialog is
                 * opened and it can now perform any visibility-based resizing.
                 */
                $form.trigger('open');

                // Event bindings that can wait
                $form.bind('saved.bookmark', function(e, data) {
                    if (mode !== 'save')
                    {
                        /* Update the presented bookmark with the newly
                         * saved data.
                         */
                        self._refreshBookmark(data);

                        // We've handled this event, so stop it.
                        e.stopPropagation();
                    }
                    else if ($pane.length > 0)
                    {
                        /* Pass this event into OUR widget so it can propagate
                         * up OUR heirarchy as well
                         */
                        self.element.trigger('saved', data);
                    }
                });

                $form.bind('complete.bookmark', function() {
                    $dialog.dialog('close');
                });
            },
            close:      function(event, ui) {
                $overlayed.unoverlay();

                $form.unbind('.bookmark')
                     .bookmarkPost('destroy');
                $dialog.dialog('destroy');
                $html.remove();
                self.enable();
            }
        });

    },

    _setState: function()
    {
        // Set the current widget state to the values of it's sub-components
        var self    = this;
        var opts    = self.options;

        opts.userId      = self.$userId.val();
        opts.itemId      = self.$itemId.val();
        opts.name        = self.$name.text();
        opts.description = self.$description.text();

        if (self.$rating.length > 0)
        {
            opts.rating  = self.$rating.stars('value');
        }

        opts.isFavorite  = self.$favorite.checkbox('isChecked');
        opts.isPrivate   = self.$private.checkbox('isChecked');
        opts.worldModify = self.$worldModify.checkbox('isChecked');

        opts.url         = self.$url.attr('href');
    },

    _resetState: function()
    {
        // Reset the values of the sub-components to the current widget state
        var self    = this;
        var opts    = self.options;

        // Squelch change-triggered item updates.
        self._squelch = true;

        self.$name.text(opts.name);
        self.$description.text(opts.description);

        if (self.$rating.length > 0)
        {
            self.$rating.stars('select', opts.rating);
        }

        self.$favorite.checkbox( (opts.isFavorite
                                    ? 'check'
                                    : 'uncheck') );
        self.$private.checkbox( (opts.isPrivate
                                    ? 'check'
                                    : 'uncheck') );
        self.$worldModify.checkbox( (opts.worldModify
                                    ? 'check'
                                    : 'uncheck') );

        self.$url.attr('href', opts.url);

        self._squelch = false;
    },

    /************************
     * Public methods
     *
     */
    isEnabled: function()
    {
        return this.options.enabled;
    },

    enable: function()
    {
        var self    = this;
        var opts    = self.options;

        if (! self.options.enabled)
        {
            self.options.enabled = true;
            self.element.removeClass('ui-state-disabled');

            self.$favorite.checkbox('enable');
            self.$private.checkbox('enable');
            self.$worldModify.checkbox('enable');
            self.$rating.stars('enable');

            self._trigger('enabled', null, true);
        }
    },

    disable: function()
    {
        var self    = this;
        var opts    = self.options;

        if (self.options.enabled)
        {
            self.options.enabled = false;
            self.element.addClass('ui-state-disabled');

            self.$favorite.checkbox('disable');
            self.$private.checkbox('disable');
            self.$worldModify.checkbox('disable');
            self.$rating.stars('disable');

            self._trigger('disabled', null, true);
        }
    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Unbind events
        self.$favorite.unbind('.bookmark');
        self.$private.unbind('.bookmark');
        self.$worldModify.unbind('.bookmark');
        self.$rating.unbind('.bookmark');
        self.$edit.unbind('.bookmark');
        self.$delete.unbind('.bookmark');
        self.$save.unbind('.bookmark');

        // Remove added elements
        self.$favorite.checkbox('destroy');
        self.$private.checkbox('destroy');
        self.$worldModify.checkbox('destroy');
        self.$rating.stars('destroy');
    }
});


}(jQuery));

/** @file
 *
 *  Javascript interface/wrapper for the presentation of a single user.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered user item (View_Helper_HtmlUsersUser):
 *      - allow in-line, on demand editing of the user if it has a
 *        '.control .item-edit' link;
 *      - allow in-line, on demand deletion of the user if it has a
 *        '.control .item-delete' link;
 *
 *  View_Helper_HtmlUsersUser will generate HTML for a user similar to:
 *     <form class='user'>
 *       <input type='hidden' name='userId' value='...' />
 *
 *       <!-- Stats: item:stats -->
 *       <div class='stats'>
 *
 *         <!-- item:stats:countItems -->
 *         <a class='countItems' ...> count </a>
 *
 *         <!-- item:stats:countTags -->
 *         <a class='countTags' ...> count </a>
 *       </div>
 *
 *       <!-- User Data: item:data -->
 *       <div class='data'>
 *
 *         <!-- item:data:avatar -->
 *         <div class='avatar'>
 *           <div class='img'>
 *             <img ... avatar image ... />
 *           </div>
 *         </div>
 *
 *         <!-- item:data:relation -->
 *         <div class='relation'>
 *           <div class='%relation%'>%relationStr%</div>
 *         </div>
 *
 *         <!-- item:data:userId -->
 *         <div class='userId'>
 *           <a ...> user-name </a>
 *         </div>
 *
 *         <!-- item:data:fullName -->
 *         <div class='fullName'>
 *           <a ...> user's full name </a>
 *         </div>
 *
 *         <!-- item:data:email -->
 *         <div class='email'>
 *           <a ...> user's email address </a>
 *         </div>
 *
 *         <!-- item:data:tags : top 5 tags -->
 *         <ul class='tags'>
 *           <li class='tag'><a ...> tag </a></li>
 *           ...
 *         </ul>
 *
 *         <!-- Item Dates: item:data:dates -->
 *         <div class='dates'>
 *
 *           <!-- item:data:dates:lastVisit -->
 *           <div class='lastVisit'> lastVisit date </div>
 *         </div>
 *       </div>
 *     </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.confirmation.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.user", {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Widget state (mirrors Model_User)
        userId:     null,
        name:       null,
        fullName:   null,
        email:      null,
        apiKey:     null,
        pictureUrl: null,
        profile:    null,
        lastVisit:  null,

        // taggedOn and updateOn are not user editable

        /* A change callback
         *      function(data)
         *          return true  to allow the change
         *          return false to abort the change
         */
        change:     null,

        /* General Json-RPC information:
         *  {version:   Json-RPC version,
         *   target:    URL of the Json-RPC endpoint,
         *   transport: 'POST' | 'GET'
         *  }
         *
         * If not provided, 'version', 'target', and 'transport' are
         * initialized from:
         *      $.registry('api').jsonRpc
         *
         * (which is initialized from
         *      application/configs/application.ini:api
         *  via
         *      application/layout/header.phtml
         *
         */
        jsonRpc:    null,

        // Widget state
        enabled:    true
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'enabled'
     *      'disabled'
     *      'deleted'
     */
    _create: function()
    {
        var self        = this;
        var opts        = self.options;

        /********************************
         * Initialize jsonRpc
         *
         */
        if ($.isFunction($.registry))
        {
            var api = $.registry('api');
            if (api && api.jsonRpc)
            {
                opts.jsonRpc = $.extend({}, api.jsonRpc, opts.jsonRpc);
            }
        }

        /********************************
         * Locate the pieces
         *
         */
        self.$userId      = self.element.find('input[name=userId]');
        self.$name        = self.element.find('.userId a');
        self.$fullName    = self.element.find('.fullName');
        self.$email       = self.element.find('.email a');

        self.$dates       = self.element.find('.dates');
        self.$lastVisit   = self.$dates.find('.lastVisit');

        self.$relation    = self.element.find('.relation');
        self.$add         = self.element.find('.control > .item-add');
        self.$remove      = self.element.find('.control > .item-delete');

        /********************************
         * Instantiate our sub-widgets
         *
         */

        /********************************
         * Localize dates
         *
         */
        self._localizeDates();

        /********************************
         * Initialize our state and bind
         * to interesting events.
         *
         */
        self._setState();
        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function()
    {
        var self    = this;
        var opts    = self.options;

        self._squelch = false;

        // Handle a direct click on one of the status indicators
        var _update_user      = function(e, data) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            self._performUpdate();
        };

        // Handle item-edit
        var _add_click  = function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            if (self.options.enabled !== true)
            {
                return;
            }
            self.disable();

            // Add this user to our network
            self._performAdd();

            self.enable();
        };

        // Handle item-delete
        var _remove_click  = function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            e.stopPropagation();

            if (self.options.enabled !== true)
            {
                return;
            }
            self.disable();

            self.$remove.confirmation({
                question:   'Really delete?',
                confirmed:  function() {
                    self._performDelete();
                },
                closed:     function() {
                    self.enable();
                }
            });
        };

        /**********************************************************************
         * bind events
         *
         */

        self.element.bind('change.user',    _update_user);

        self.$add.bind(   'click.user',     _add_click);
        self.$remove.bind('click.user',     _remove_click);
    },

    _performAdd: function( )
    {
        var self    = this;
        var opts    = self.options;

        var params  = {
            users:  opts.name   //opts.userId
        };

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.addToNetwork', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'User addition failed',
                        text:  '<p class="error">'
                             +   (data ? data.error.message : '')
                             + '</p>'
                    });

                    return;
                }

                if (data.result === null)   { return; }

                if (data.result[opts.name] !== true)
                {
                    $.notify({
                        title: 'User addition failed',
                        text:  '<p class="error">'
                             +   (data ? data.result[opts.name] : '')
                             + '</p>'
                    });

                    return;
                }

                $.notify({
                    title: 'User added',
                    text:  opts.name
                });

                // Adjust the relation information.
                self._updateRelation('add');
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'User addition failed',
                    text:  '<p class="error">'
                         +   textStatus
                         + '</p>'
                });
            },
            complete:   function(req, textStatus) {
            }
         });
    },

    _performDelete: function( )
    {
        var self    = this;
        var opts    = self.options;

        var params  = {
            users:  opts.name
        };

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.removeFromNetwork', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'User removal failed',
                        text:  '<p class="error">'
                             +   (data ? data.error.message : '')
                             + '</p>'
                    });

                    return;
                }

                if (data.result === null)   { return; }

                if (data.result[opts.name] !== true)
                {
                    $.notify({
                        title: 'User removal failed',
                        text:  '<p class="error">'
                             +   (data ? data.result[opts.name] : '')
                             + '</p>'
                    });

                    return;
                }

                $.notify({
                    title: 'User removed',
                    text:  opts.name
                });

                // Adjust the relation information.
                self._updateRelation('remove');

                // Trigger a 'deleted' event for our parent.
                self._trigger('deleted');
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'User removal failed',
                    text:  '<p class="error">'
                         +   textStatus
                         + '</p>'
                });
            },
            complete:   function(req, textStatus) {
            }
         });
    },

    _performUpdate: function()
    {
        var self    = this;
        var opts    = self.options;

        if ((opts.enabled !== true) || (self._squelch === true))
        {
            return;
        }

        // Gather the current data about this item.
        var nonEmpty    = false;
        var params      = {
            id: { userId: opts.userId }
        };

        if (self.$name.text() !== opts.name)
        {
            params.name = self.$name.text();
            nonEmpty    = true;
        }

        if (self.$fullName.text() !== opts.fullName)
        {
            params.fullName = self.$fullName.text();
            nonEmpty        = true;
        }

        if (self.$email.text() !== opts.email)
        {
            params.email = self.$email.text();
            nonEmpty     = true;
        }

        if (nonEmpty !== true)
        {
            return;
        }

        $.log('connexions.user::_performUpdate()');

        /* If there is a 'change' callback, invoke it.
         *
         * If it returns false, terminate the change.
         */
        if ($.isFunction(self.options.change))
        {
            if (! self.options.change(params))
            {
                // Rollback state.
                self._resetState();

                return;
            }
        }

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.update', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'User update failed',
                        text:  '<p class="error">'
                             +   (data ? data.error.message : '')
                             + '</p>'
                    });

                    // rollback state
                    self._resetState();
                    return;
                }

                if (data.result === null)
                {
                    return;
                }

                self._squelch = true;

                // Include the updated data
                self.$name.text(            data.result.name );
                self.$fullName.text(        data.result.fullName );

                self.$email.text(           data.result.email);
                self.$email.attr('href',    'mailto:'+ data.result.email);

                // Update and localize the dates
                self.$lastVisit.data('utcdate', data.result.lastVisit);
                self._localizeDates();

                self._squelch = false;

                // set state
                self._setState();
            },
            error:      function(req, textStatus, err) {
                $.notify({
                    title: 'User update failed',
                    text:  '<p class="error">'
                         +   textStatus
                         + '</p>'
                });

                // rollback state
                self._resetState();
            },
            complete:   function(req, textStatus) {
            }
         });
    },

    /** @brief  Update the relation indicator as well as the controls
     *          based upon a successful add or remove operation.
     *  @param  op      The operation which succeeded ( 'add' | 'remove' );
     */
    _updateRelation: function(op) {
        var self            = this;
        var opts            = self.options;
        // self, mutual, amIn, isIn
        var prevRelation    = self.$relation.data('relation');

        /* The prevRelation should be one of:
         *  mutual  (amIn / isIn)
         *  isIn    (following)
         *
         *  'self' and 'amIn' SHOULD NOT be seen here since relation controls
         *  SHOULD be hidden/inactive in those cases.
         */
        if (op === 'add')
        {
            /* Added to the authenticated users network
             *
             * Transition:
             *  mutual:     *no change*
             *  none:       isIn
             *  amIn:       mutual
             *
             * In either case, deactivate the 'add' and active the 'del'
             * controls.
             */
            if (prevRelation === 'none')
            {
                var title   = 'following';
                self.$relation.attr('title', title)
                               .data('relation', 'isIn');
                self.$relation.find('.relation-none')
                              .removeClass('relation-none')
                              .addClass('relation-isIn')
                              .text(title);
            }
            else if (prevRelation === 'amIn')
            {
                var title   = 'mutual followers';
                self.$relation.attr('title', title)
                               .data('relation', 'mutual');
                self.$relation.find('.relation-amIn')
                              .removeClass('relation-amIn')
                              .addClass('relation-mutual')
                              .text(title);
            }

            self.$add.attr('disabled', true)
                     .hide();
            self.$remove
                     .removeAttr('disabled')
                     .show();
        }
        else
        {
            /* Deleted from the authenticated users network
             *
             * Transition:
             *  mutual:     amIn
             *  isIn:       none
             *
             * In either case, deactivate the 'del' and active the 'add'
             * controls.
             */
            if (prevRelation === 'mutual')
            {
                var title   = 'follower';
                self.$relation.attr('title', title)
                              .data('relation', 'amIn');
                self.$relation.find('.relation-mutual')
                              .removeClass('relation-mutual')
                              .addClass('relation-amIn')
                              .text(title);
            }
            else if (prevRelation === 'isIn')
            {
                var title   = 'no relation';
                self.$relation.attr('title', title)
                              .data('relation', 'none');
                self.$relation.find('.relation-isIn')
                              .removeClass('relation-isIn')
                              .addClass('relation-none')
                              .text(title);
            }

            self.$remove
                     .attr('disabled', true)
                     .hide();
            self.$add.removeAttr('disabled')
                     .show();
        }
    },

    _setState: function()
    {
        // Set the current widget state to the values of it's sub-components
        var self    = this;
        var opts    = self.options;

        opts.userId      = self.$userId.val();
        opts.name        = self.$name.text();
        opts.fullName    = self.$fullName.text();
        opts.email       = self.$email.text();
    },

    _resetState: function()
    {
        // Reset the values of the sub-components to the current widget state
        var self    = this;
        var opts    = self.options;

        // Squelch change-triggered item updates.
        self._squelch = true;

        self.$name.text(opts.name);
        self.$fullName.text(opts.fullName);

        self.$email.text(        opts.email);
        self.$email.attr('href', 'mailto:'+ opts.email);

        self._squelch = false;
    },

    /** @brief  Given a date/time string, localize it to the client-side
     *          timezone.
     *  @param  utcStr      The date/time string in UTC and in the form:
     *                          YYYY-MM-DD HH:mm:ss
     *
     *  @return The localized time string.
     */
    _localizeDate: function(utcStr)
    {
        return $.date2str( $.str2date( utcStr ) );
    },

    /** @brief  Update presented dates to the client-side timezone.
     */
    _localizeDates: function()
    {
        var self    = this,
            newStr;

        if (self.$lastVisit.length > 0)
        {
            newStr = self._localizeDate(self.$lastVisit.data('utcdate'));
            self.$lastVisit.html( newStr );
        }
    },

    /************************
     * Public methods
     *
     */
    isEnabled: function()
    {
        return this.options.enabled;
    },

    enable: function()
    {
        var self    = this;
        var opts    = self.options;

        if (! self.options.enabled)
        {
            self.options.enabled = true;
            self.element.removeClass('ui-state-disabled');

            self._trigger('enabled', null, true);
        }
    },

    disable: function()
    {
        var self    = this;
        var opts    = self.options;

        if (self.options.enabled)
        {
            self.options.enabled = false;
            self.element.addClass('ui-state-disabled');

            self._trigger('disabled', null, true);
        }
    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Unbind events
        self.$add.unbind('.user');
        self.$remove.unbind('.user');

        // Remove added elements
    }
});


}(jQuery));

/* Plugin for jQuery for working with colors.
 * 
 * Version 1.1.
 * 
 * Inspiration from jQuery color animation plugin by John Resig.
 *
 * Released under the MIT license by Ole Laursen, October 2009.
 *
 * Examples:
 *
 *   $.color.parse("#fff").scale('rgb', 0.25).add('a', -0.5).toString()
 *   var c = $.color.extract($("#mydiv"), 'background-color');
 *   console.log(c.r, c.g, c.b, c.a);
 *   $.color.make(100, 50, 25, 0.4).toString() // returns "rgba(100,50,25,0.4)"
 *
 * Note that .scale() and .add() return the same modified object
 * instead of making a new one.
 *
 * V. 1.1: Fix error handling so e.g. parsing an empty string does
 * produce a color rather than just crashing.
 */ 

(function($) {
    $.color = {};

    // construct color object with some convenient chainable helpers
    $.color.make = function (r, g, b, a) {
        var o = {};
        o.r = r || 0;
        o.g = g || 0;
        o.b = b || 0;
        o.a = a != null ? a : 1;

        o.add = function (c, d) {
            for (var i = 0; i < c.length; ++i)
                o[c.charAt(i)] += d;
            return o.normalize();
        };
        
        o.scale = function (c, f) {
            for (var i = 0; i < c.length; ++i)
                o[c.charAt(i)] *= f;
            return o.normalize();
        };
        
        o.toString = function () {
            if (o.a >= 1.0) {
                return "rgb("+[o.r, o.g, o.b].join(",")+")";
            } else {
                return "rgba("+[o.r, o.g, o.b, o.a].join(",")+")";
            }
        };

        o.normalize = function () {
            function clamp(min, value, max) {
                return value < min ? min: (value > max ? max: value);
            }
            
            o.r = clamp(0, parseInt(o.r), 255);
            o.g = clamp(0, parseInt(o.g), 255);
            o.b = clamp(0, parseInt(o.b), 255);
            o.a = clamp(0, o.a, 1);
            return o;
        };

        o.clone = function () {
            return $.color.make(o.r, o.b, o.g, o.a);
        };

        return o.normalize();
    }

    // extract CSS color property from element, going up in the DOM
    // if it's "transparent"
    $.color.extract = function (elem, css) {
        var c;
        do {
            c = elem.css(css).toLowerCase();
            // keep going until we find an element that has color, or
            // we hit the body
            if (c != '' && c != 'transparent')
                break;
            elem = elem.parent();
        } while (!$.nodeName(elem.get(0), "body"));

        // catch Safari's way of signalling transparent
        if (c == "rgba(0, 0, 0, 0)")
            c = "transparent";
        
        return $.color.parse(c);
    }
    
    // parse CSS color string (like "rgb(10, 32, 43)" or "#fff"),
    // returns color object, if parsing failed, you get black (0, 0,
    // 0) out
    $.color.parse = function (str) {
        var res, m = $.color.make;

        // Look for rgb(num,num,num)
        if (res = /rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(str))
            return m(parseInt(res[1], 10), parseInt(res[2], 10), parseInt(res[3], 10));
        
        // Look for rgba(num,num,num,num)
        if (res = /rgba\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]+(?:\.[0-9]+)?)\s*\)/.exec(str))
            return m(parseInt(res[1], 10), parseInt(res[2], 10), parseInt(res[3], 10), parseFloat(res[4]));
            
        // Look for rgb(num%,num%,num%)
        if (res = /rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(str))
            return m(parseFloat(res[1])*2.55, parseFloat(res[2])*2.55, parseFloat(res[3])*2.55);

        // Look for rgba(num%,num%,num%,num)
        if (res = /rgba\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\s*\)/.exec(str))
            return m(parseFloat(res[1])*2.55, parseFloat(res[2])*2.55, parseFloat(res[3])*2.55, parseFloat(res[4]));
        
        // Look for #a0b1c2
        if (res = /#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(str))
            return m(parseInt(res[1], 16), parseInt(res[2], 16), parseInt(res[3], 16));

        // Look for #fff
        if (res = /#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(str))
            return m(parseInt(res[1]+res[1], 16), parseInt(res[2]+res[2], 16), parseInt(res[3]+res[3], 16));

        // Otherwise, we're most likely dealing with a named color
        var name = $.trim(str).toLowerCase();
        if (name == "transparent")
            return m(255, 255, 255, 0);
        else {
            // default to black
            res = lookupColors[name] || [0, 0, 0];
            return m(res[0], res[1], res[2]);
        }
    }
    
    var lookupColors = {
        aqua:[0,255,255],
        azure:[240,255,255],
        beige:[245,245,220],
        black:[0,0,0],
        blue:[0,0,255],
        brown:[165,42,42],
        cyan:[0,255,255],
        darkblue:[0,0,139],
        darkcyan:[0,139,139],
        darkgrey:[169,169,169],
        darkgreen:[0,100,0],
        darkkhaki:[189,183,107],
        darkmagenta:[139,0,139],
        darkolivegreen:[85,107,47],
        darkorange:[255,140,0],
        darkorchid:[153,50,204],
        darkred:[139,0,0],
        darksalmon:[233,150,122],
        darkviolet:[148,0,211],
        fuchsia:[255,0,255],
        gold:[255,215,0],
        green:[0,128,0],
        indigo:[75,0,130],
        khaki:[240,230,140],
        lightblue:[173,216,230],
        lightcyan:[224,255,255],
        lightgreen:[144,238,144],
        lightgrey:[211,211,211],
        lightpink:[255,182,193],
        lightyellow:[255,255,224],
        lime:[0,255,0],
        magenta:[255,0,255],
        maroon:[128,0,0],
        navy:[0,0,128],
        olive:[128,128,0],
        orange:[255,165,0],
        pink:[255,192,203],
        purple:[128,0,128],
        violet:[128,0,128],
        red:[255,0,0],
        silver:[192,192,192],
        white:[255,255,255],
        yellow:[255,255,0]
    };
})(jQuery);
/* first an inline dependency, jquery.colorhelpers.js, we inline it here
*  for convenience
*/

/* Plugin for jQuery for working with colors.
 * 
 * Version 1.0.
 * 
 * Inspiration from jQuery color animation plugin by John Resig.
 *
 * Released under the MIT license by Ole Laursen, October 2009.
 *
 * Examples:
 *
 *   $.color.parse("#fff").scale('rgb', 0.25).add('a', -0.5).toString()
 *   var c = $.color.extract($("#mydiv"), 'background-color');
 *   console.log(c.r, c.g, c.b, c.a);
 *   $.color.make(100, 50, 25, 0.4).toString() // returns "rgba(100,50,25,0.4)"
 *
 * Note that .scale() and .add() work in-place instead of returning
 * new objects.
 */ 
(function($){$.color={};$.color.make=function(E,D,B,C){var F={};F.r=E||0;F.g=D||0;F.b=B||0;F.a=C!=null?C:1;F.add=function(I,H){for(var G=0;G<I.length;++G){F[I.charAt(G)]+=H}return F.normalize()};F.scale=function(I,H){for(var G=0;G<I.length;++G){F[I.charAt(G)]*=H}return F.normalize()};F.toString=function(){if(F.a>=1){return"rgb("+[F.r,F.g,F.b].join(",")+")"}else{return"rgba("+[F.r,F.g,F.b,F.a].join(",")+")"}};F.normalize=function(){function G(I,J,H){return J<I?I:(J>H?H:J)}F.r=G(0,parseInt(F.r),255);F.g=G(0,parseInt(F.g),255);F.b=G(0,parseInt(F.b),255);F.a=G(0,F.a,1);return F};F.clone=function(){return $.color.make(F.r,F.b,F.g,F.a)};return F.normalize()};$.color.extract=function(C,B){var D;do{D=C.css(B).toLowerCase();if(D!=""&&D!="transparent"){break}C=C.parent()}while(!$.nodeName(C.get(0),"body"));if(D=="rgba(0, 0, 0, 0)"){D="transparent"}return $.color.parse(D)};$.color.parse=function(E){var D,B=$.color.make;if(D=/rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(E)){return B(parseInt(D[1],10),parseInt(D[2],10),parseInt(D[3],10))}if(D=/rgba\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]+(?:\.[0-9]+)?)\s*\)/.exec(E)){return B(parseInt(D[1],10),parseInt(D[2],10),parseInt(D[3],10),parseFloat(D[4]))}if(D=/rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(E)){return B(parseFloat(D[1])*2.55,parseFloat(D[2])*2.55,parseFloat(D[3])*2.55)}if(D=/rgba\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\s*\)/.exec(E)){return B(parseFloat(D[1])*2.55,parseFloat(D[2])*2.55,parseFloat(D[3])*2.55,parseFloat(D[4]))}if(D=/#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(E)){return B(parseInt(D[1],16),parseInt(D[2],16),parseInt(D[3],16))}if(D=/#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(E)){return B(parseInt(D[1]+D[1],16),parseInt(D[2]+D[2],16),parseInt(D[3]+D[3],16))}var C=$.trim(E).toLowerCase();if(C=="transparent"){return B(255,255,255,0)}else{D=A[C];return B(D[0],D[1],D[2])}};var A={aqua:[0,255,255],azure:[240,255,255],beige:[245,245,220],black:[0,0,0],blue:[0,0,255],brown:[165,42,42],cyan:[0,255,255],darkblue:[0,0,139],darkcyan:[0,139,139],darkgrey:[169,169,169],darkgreen:[0,100,0],darkkhaki:[189,183,107],darkmagenta:[139,0,139],darkolivegreen:[85,107,47],darkorange:[255,140,0],darkorchid:[153,50,204],darkred:[139,0,0],darksalmon:[233,150,122],darkviolet:[148,0,211],fuchsia:[255,0,255],gold:[255,215,0],green:[0,128,0],indigo:[75,0,130],khaki:[240,230,140],lightblue:[173,216,230],lightcyan:[224,255,255],lightgreen:[144,238,144],lightgrey:[211,211,211],lightpink:[255,182,193],lightyellow:[255,255,224],lime:[0,255,0],magenta:[255,0,255],maroon:[128,0,0],navy:[0,0,128],olive:[128,128,0],orange:[255,165,0],pink:[255,192,203],purple:[128,0,128],violet:[128,0,128],red:[255,0,0],silver:[192,192,192],white:[255,255,255],yellow:[255,255,0]}})(jQuery);

/* Javascript plotting library for jQuery, v. 0.6.
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
* 
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
* 
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
 */
 
// the actual Flot code
(function($) {
    function Plot(placeholder, data_, options_, plugins) {
        // data is on the form:
        //   [ series1, series2 ... ]
        // where series is either just the data as [ [x1, y1], [x2, y2], ... ]
        // or { data: [ [x1, y1], [x2, y2], ... ], label: "some label", ... }
        
        var series = [],
            options = {
                // the color theme used for graphs
                colors: ["#edc240", "#afd8f8", "#cb4b4b", "#4da74d", "#9440ed"],
                legend: {
                    show: true,
                    noColumns: 1, // number of colums in legend table
                    labelFormatter: null, // fn: string -> string
                    labelBoxBorderColor: "#ccc", // border color for the little label boxes
                    container: null, // container (as jQuery object) to put legend in, null means default on top of graph
                    position: "ne", // position of default legend container within plot
                    margin: 5, // distance from grid edge to default legend container within plot
                    backgroundColor: null, // null means auto-detect
                    backgroundOpacity: 0.85 // set to 0 to avoid background
                },
                xaxis: {
                    position: "bottom", // or "top"
                    mode: null, // null or "time"
                    color: null, // base color, labels, ticks
                    tickColor: null, // possibly different color of ticks, e.g. "rgba(0,0,0,0.15)"
                    transform: null, // null or f: number -> number to transform axis
                    inverseTransform: null, // if transform is set, this should be the inverse function
                    min: null, // min. value to show, null means set automatically
                    max: null, // max. value to show, null means set automatically
                    autoscaleMargin: null, // margin in % to add if auto-setting min/max
                    autoscaleMax: null, // maximum value to autoscale to
                    ticks: null, // either [1, 3] or [[1, "a"], 3] or (fn: axis info -> ticks) or app. number of ticks for auto-ticks
                    tickFormatter: null, // fn: number -> string
                    labelWidth: null, // size of tick labels in pixels
                    labelHeight: null,
                    labelAngle: 0, // an angle from 90 to -90 
                    tickLength: null, // size in pixels of ticks, or "full" for whole line
                    alignTicksWithAxis: null, // axis number or null for no sync
                    
                    // mode specific options
                    tickDecimals: null, // no. of decimals, null means auto
                    tickSize: null, // number or [number, "unit"]
                    minTickSize: null, // number or [number, "unit"]
                    monthNames: null, // list of names of months
                    timeformat: null, // format string to use
                    twelveHourClock: false // 12 or 24 time in time mode
                },
                yaxis: {
                    autoscaleMargin: 0.02,
                    labelAngle: 0, // an angle from 90 to -90 
                    position: "left" // or "right"
                },
                xaxes: [],
                yaxes: [],
                series: {
                    points: {
                        show: false,
                        radius: 3,
                        lineWidth: 2, // in pixels
                        fill: true,
                        fillColor: "#ffffff",
                        symbol: "circle" // or callback
                    },
                    lines: {
                        // we don't put in show: false so we can see
                        // whether lines were actively disabled 
                        lineWidth: 2, // in pixels
                        fill: false,
                        fillColor: null,
                        steps: false
                    },
                    bars: {
                        show: false,
                        lineWidth: 2, // in pixels
                        barWidth: 1, // in units of the x axis
                        fill: true,
                        fillColor: null,
                        align: "left", // or "center" 
                        horizontal: false
                    },
                    shadowSize: 3
                },
                grid: {
                    show: true,
                    aboveData: false,
                    color: "#545454", // primary color used for outline and labels
                    backgroundColor: null, // null for transparent, else color
                    borderColor: null, // set if different from the grid color
                    tickColor: null, // color for the ticks, e.g. "rgba(0,0,0,0.15)"
                    labelMargin: 5, // in pixels
                    axisMargin: 8, // in pixels
                    borderWidth: 2, // in pixels
                    markings: null, // array of ranges or fn: axes -> array of ranges
                    markingsColor: "#f4f4f4",
                    markingsLineWidth: 2,
                    // interactive stuff
                    clickable: false,
                    hoverable: false,
                    autoHighlight: true, // highlight in case mouse is near
                    mouseActiveRadius: 10 // how far the mouse can be away to activate an item
                },
                hooks: {}
            },
        canvas = null,      // the canvas for the plot itself
        overlay = null,     // canvas for interactive stuff on top of plot
        eventHolder = null, // jQuery object that events should be bound to
        ctx = null, octx = null,
        xaxes = [], yaxes = [],
        plotOffset = { left: 0, right: 0, top: 0, bottom: 0},
        canvasWidth = 0, canvasHeight = 0,
        plotWidth = 0, plotHeight = 0,
        hasCSS3transform = false,
        hooks = {
            processOptions: [],
            processRawData: [],
            processDatapoints: [],
            drawSeries: [],
            draw: [],
            bindEvents: [],
            drawOverlay: []
        },
        plot = this;

        // public functions
        plot.setData = setData;
        plot.setupGrid = setupGrid;
        plot.draw = draw;
        plot.getPlaceholder = function() { return placeholder; };
        plot.getCanvas = function() { return canvas; };
        plot.getPlotOffset = function() { return plotOffset; };
        plot.width = function () { return plotWidth; };
        plot.height = function () { return plotHeight; };
        plot.offset = function () {
            var o = eventHolder.offset();
            o.left += plotOffset.left;
            o.top += plotOffset.top;
            return o;
        };
        plot.getData = function () { return series; };
        plot.getAxis = function (dir, number) {
            var a = (dir == "x" ? xaxes : yaxes)[number - 1];
            if (a && !a.used)
                a = null;
            return a;
        };
        plot.getAxes = function () {
            var res = {}, i;
            for (i = 0; i < xaxes.length; ++i)
                res["x" + (i ? (i + 1) : "") + "axis"] = xaxes[i] || {};
            for (i = 0; i < yaxes.length; ++i)
                res["y" + (i ? (i + 1) : "") + "axis"] = yaxes[i] || {};

            // backwards compatibility - to be removed
            if (!res.x2axis)
                res.x2axis = { n: 2 };
            if (!res.y2axis)
                res.y2axis = { n: 2 };
            
            return res;
        };
        plot.getXAxes = function () { return xaxes; };
        plot.getYAxes = function () { return yaxes; };
        plot.getUsedAxes = getUsedAxes; // return flat array with x and y axes that are in use
        plot.c2p = canvasToAxisCoords;
        plot.p2c = axisToCanvasCoords;
        plot.getOptions = function () { return options; };
        plot.highlight = highlight;
        plot.unhighlight = unhighlight;
        plot.triggerRedrawOverlay = triggerRedrawOverlay;
        plot.pointOffset = function(point) {
            return {
                left: parseInt(xaxes[axisNumber(point, "x") - 1].p2c(+point.x) + plotOffset.left),
                top: parseInt(yaxes[axisNumber(point, "y") - 1].p2c(+point.y) + plotOffset.top)
            };
        };

        // public attributes
        plot.hooks = hooks;
        
        // initialize
        initPlugins(plot);
        parseOptions(options_);
        constructCanvas();
        setData(data_);
        setupGrid();
        draw();
        bindEvents();


        function executeHooks(hook, args) {
            args = [plot].concat(args);
            for (var i = 0; i < hook.length; ++i)
                hook[i].apply(this, args);
        }

        function initPlugins() {
            for (var i = 0; i < plugins.length; ++i) {
                var p = plugins[i];
                p.init(plot);
                if (p.options)
                    $.extend(true, options, p.options);
            }
        }
        
        /**
         * Parses and given options, providing sensible defaults when an option is unspecified.
         * Executes "processOptions" hook when complete.
         *
         * @param opts <Object> containing options for plot.
         */
        function parseOptions(opts) {
            var i;
            
            $.extend(true, options, opts);
            
            if (options.xaxis.color == null)
                options.xaxis.color = options.grid.color;
            if (options.yaxis.color == null)
                options.yaxis.color = options.grid.color;
            
            if (options.xaxis.tickColor == null) // backwards-compatibility
                options.xaxis.tickColor = options.grid.tickColor;
            if (options.yaxis.tickColor == null) // backwards-compatibility
                options.yaxis.tickColor = options.grid.tickColor;

            if (options.grid.borderColor == null)
                options.grid.borderColor = options.grid.color;
            if (options.grid.tickColor == null)
                options.grid.tickColor = $.color.parse(options.grid.color).scale('a', 0.22).toString();
            
            // fill in defaults in axes, copy at least always the
            // first as the rest of the code assumes it'll be there
            for (i = 0; i < Math.max(1, options.xaxes.length); ++i)
                options.xaxes[i] = $.extend(true, {}, options.xaxis, options.xaxes[i]);
            for (i = 0; i < Math.max(1, options.yaxes.length); ++i)
                options.yaxes[i] = $.extend(true, {}, options.yaxis, options.yaxes[i]);
            // backwards compatibility, to be removed in future
            if (options.xaxis.noTicks && options.xaxis.ticks == null)
                options.xaxis.ticks = options.xaxis.noTicks;
            if (options.yaxis.noTicks && options.yaxis.ticks == null)
                options.yaxis.ticks = options.yaxis.noTicks;
            if (options.x2axis) {
                options.x2axis.position = "top";
                options.xaxes[1] = options.x2axis;
            }
            if (options.y2axis) {
                if (options.y2axis.autoscaleMargin === undefined)
                    options.y2axis.autoscaleMargin = 0.02;
                options.y2axis.position = "right";
                options.yaxes[1] = options.y2axis;
            }
            if (options.grid.coloredAreas)
                options.grid.markings = options.grid.coloredAreas;
            if (options.grid.coloredAreasColor)
                options.grid.markingsColor = options.grid.coloredAreasColor;
            if (options.lines)
                $.extend(true, options.series.lines, options.lines);
            if (options.points)
                $.extend(true, options.series.points, options.points);
            if (options.bars)
                $.extend(true, options.series.bars, options.bars);
            if (options.shadowSize != null)
                options.series.shadowSize = options.shadowSize;

            for (i = 0; i < options.xaxes.length; ++i)
                getOrCreateAxis(xaxes, i + 1).options = options.xaxes[i];
            for (i = 0; i < options.yaxes.length; ++i)
                getOrCreateAxis(yaxes, i + 1).options = options.yaxes[i];

            // add hooks from options
            for (var n in hooks)
                if (options.hooks[n] && options.hooks[n].length)
                    hooks[n] = hooks[n].concat(options.hooks[n]);

            executeHooks(hooks.processOptions, [options]);
        }

        function setData(d) {
            series = parseData(d);
            fillInSeriesOptions();
            processData();
        }
        
        /**
         * Parses data object to ensure that series data are in a consistent Object structure -
         * 	    [{data: [[1,2], [2,3]]}]
         *
         * @param d <Array<Object>> containing either an Array of Arrays (e.g. [[[1,2], [2,3]]]) or
         *      an Array of Objects (e.g [{data: [[1,2], [2,3]]}])
         */
        function parseData(d) {
            var res = [];
            for (var i = 0; i < d.length; ++i) {
                var s = $.extend(true, {}, options.series);

                if (d[i].data != null) {
                    s.data = d[i].data; // move the data instead of deep-copy
                    delete d[i].data;

                    $.extend(true, s, d[i]);

                    d[i].data = s.data;
                }
                else
                    s.data = d[i];
                res.push(s);
            }

            return res;
        }
        
        function axisNumber(obj, coord) {
            var a = obj[coord + "axis"];
            if (typeof a == "object") // if we got a real axis, extract number
                a = a.n;
            if (typeof a != "number")
                a = 1; // default to first axis
            return a;
        }

        function canvasToAxisCoords(pos) {
            // return an object with x/y corresponding to all used axes 
            var res = {}, i, axis;
            for (i = 0; i < xaxes.length; ++i) {
                axis = xaxes[i];
                if (axis && axis.used)
                    res["x" + axis.n] = axis.c2p(pos.left);
            }

            for (i = 0; i < yaxes.length; ++i) {
                axis = yaxes[i];
                if (axis && axis.used)
                    res["y" + axis.n] = axis.c2p(pos.top);
            }
            
            if (res.x1 !== undefined)
                res.x = res.x1;
            if (res.y1 !== undefined)
                res.y = res.y1;

            return res;
        }
        
        function axisToCanvasCoords(pos) {
            // get canvas coords from the first pair of x/y found in pos
            var res = {}, i, axis, key;

            for (i = 0; i < xaxes.length; ++i) {
                axis = xaxes[i];
                if (axis && axis.used) {
                    key = "x" + axis.n;
                    if (pos[key] == null && axis.n == 1)
                        key = "x";

                    if (pos[key] != null) {
                        res.left = axis.p2c(pos[key]);
                        break;
                    }
                }
            }
            
            for (i = 0; i < yaxes.length; ++i) {
                axis = yaxes[i];
                if (axis && axis.used) {
                    key = "y" + axis.n;
                    if (pos[key] == null && axis.n == 1)
                        key = "y";

                    if (pos[key] != null) {
                        res.top = axis.p2c(pos[key]);
                        break;
                    }
                }
            }
            
            return res;
        }
        
        function getUsedAxes() {
            var res = [], i, axis;
            for (i = 0; i < xaxes.length; ++i) {
                axis = xaxes[i];
                if (axis && axis.used)
                    res.push(axis);
            }
            for (i = 0; i < yaxes.length; ++i) {
                axis = yaxes[i];
                if (axis && axis.used)
                    res.push(axis);
            }
            return res;
        }

        function getOrCreateAxis(axes, number) {
            if (!axes[number - 1])
                axes[number - 1] = {
                    n: number, // save the number for future reference
                    direction: axes == xaxes ? "x" : "y",
                    options: $.extend(true, {}, axes == xaxes ? options.xaxis : options.yaxis)
                };
                
            return axes[number - 1];
        }

        /**
         * Assign default options/colors to the current series where options are invalid or
         * unspecified. 
         *
         * @see #setData(<Object>)
         */
        function fillInSeriesOptions() {
            var i;
            
            // collect what we already got of colors
            var neededColors = series.length,
                usedColors = [],
                assignedColors = [];
            for (i = 0; i < series.length; ++i) {
                var sc = series[i].color;
                if (sc != null) {
                    --neededColors;
                    if (typeof sc == "number")
                        assignedColors.push(sc);
                    else
                        usedColors.push($.color.parse(series[i].color));
                }
            }
            
            // we might need to generate more colors if higher indices
            // are assigned
            for (i = 0; i < assignedColors.length; ++i) {
                neededColors = Math.max(neededColors, assignedColors[i] + 1);
            }

            // produce colors as needed
            var colors = [], variation = 0;
            i = 0;
            while (colors.length < neededColors) {
                var c;
                if (options.colors.length == i) // check degenerate case
                    c = $.color.make(100, 100, 100);
                else
                    c = $.color.parse(options.colors[i]);

                // vary color if needed
                var sign = variation % 2 == 1 ? -1 : 1;
                c.scale('rgb', 1 + sign * Math.ceil(variation / 2) * 0.2)

                // FIXME: if we're getting to close to something else,
                // we should probably skip this one
                colors.push(c);
                
                ++i;
                if (i >= options.colors.length) {
                    i = 0;
                    ++variation;
                }
            }

            // fill in the options
            var colori = 0, s;
            for (i = 0; i < series.length; ++i) {
                s = series[i];
                
                // assign colors
                if (s.color == null) {
                    s.color = colors[colori].toString();
                    ++colori;
                }
                else if (typeof s.color == "number")
                    s.color = colors[s.color].toString();

                // turn on lines automatically in case nothing is set
                if (s.lines.show == null) {
                    var v, show = true;
                    for (v in s)
                        if (s[v] && s[v].show) {
                            show = false;
                            break;
                        }
                    if (show)
                        s.lines.show = true;
                }

                // setup axes
                s.xaxis = getOrCreateAxis(xaxes, axisNumber(s, "x"));
                s.yaxis = getOrCreateAxis(yaxes, axisNumber(s, "y"));
            }
        }
        
        function processData() {
            var topSentry = Number.POSITIVE_INFINITY,
                bottomSentry = Number.NEGATIVE_INFINITY,
                i, j, k, m, length,
                s, points, ps, x, y, axis, val, f, p;

            function initAxis(axis, number) {
                if (!axis)
                    return;
                
                axis.datamin = topSentry;
                axis.datamax = bottomSentry;
                axis.used = false;
            }

            function updateAxis(axis, min, max) {
                if (min < axis.datamin)
                    axis.datamin = min;
                if (max > axis.datamax)
                    axis.datamax = max;
            }

            // Initialize all axes
            for (i = 0; i < xaxes.length; ++i)
                initAxis(xaxes[i]);
            for (i = 0; i < yaxes.length; ++i)
                initAxis(yaxes[i]);
            
            for (i = 0; i < series.length; ++i) {
                s = series[i];
                s.datapoints = { points: [] };
                
                executeHooks(hooks.processRawData, [ s, s.data, s.datapoints ]);
            }
            
            // first pass: clean and copy data
            for (i = 0; i < series.length; ++i) {
                s = series[i];

                var data = s.data, format = s.datapoints.format;

                if (!format) {
                    format = [];
                    // find out how to copy
                    format.push({ x: true, number: true, required: true });
                    format.push({ y: true, number: true, required: true });

                    if (s.bars.show || (s.lines.show && s.lines.fill)) {
                        format.push({ y: true, number: true, required: false, defaultValue: 0 });
                        if (s.bars.horizontal) {
                            delete format[format.length - 1].y;
                            format[format.length - 1].x = true;
                        }
                    }
                    
                    s.datapoints.format = format;
                }

                if (s.datapoints.pointsize != null)
                    continue; // already filled in

                s.datapoints.pointsize = format.length;
                
                ps = s.datapoints.pointsize;
                points = s.datapoints.points;

                insertSteps = s.lines.show && s.lines.steps;
                s.xaxis.used = s.yaxis.used = true;
                
                for (j = k = 0; j < data.length; ++j, k += ps) {
                    p = data[j];

                    var nullify = p == null;
                    if (!nullify) {
                        for (m = 0; m < ps; ++m) {
                            val = p[m];
                            f = format[m];

                            if (f) {
                                if (f.number && val != null) {
                                    val = +val; // convert to number
                                    if (isNaN(val))
                                        val = null;
                                }

                                if (val == null) {
                                    if (f.required)
                                        nullify = true;
                                    
                                    if (f.defaultValue != null)
                                        val = f.defaultValue;
                                }
                            }
                            
                            points[k + m] = val;
                        }
                    }
                    
                    if (nullify) {
                        for (m = 0; m < ps; ++m) {
                            val = points[k + m];
                            if (val != null) {
                                f = format[m];
                                // extract min/max info
                                if (f.x)
                                    updateAxis(s.xaxis, val, val);
                                if (f.y)
                                    updateAxis(s.yaxis, val, val);
                            }
                            points[k + m] = null;
                        }
                    }
                    else {
                        // a little bit of line specific stuff that
                        // perhaps shouldn't be here, but lacking
                        // better means...
                        if (insertSteps && k > 0
                            && points[k - ps] != null
                            && points[k - ps] != points[k]
                            && points[k - ps + 1] != points[k + 1]) {
                            // copy the point to make room for a middle point
                            for (m = 0; m < ps; ++m)
                                points[k + ps + m] = points[k + m];

                            // middle point has same y
                            points[k + 1] = points[k - ps + 1];

                            // we've added a point, better reflect that
                            k += ps;
                        }
                    }
                }
            }

            // give the hooks a chance to run
            for (i = 0; i < series.length; ++i) {
                s = series[i];
                
                executeHooks(hooks.processDatapoints, [ s, s.datapoints]);
            }

            // second pass: find datamax/datamin for auto-scaling
            for (i = 0; i < series.length; ++i) {
                s = series[i];
                points = s.datapoints.points,
                ps = s.datapoints.pointsize;

                var xmin = topSentry, ymin = topSentry,
                    xmax = bottomSentry, ymax = bottomSentry;
                
                for (j = 0; j < points.length; j += ps) {
                    if (points[j] == null)
                        continue;

                    for (m = 0; m < ps; ++m) {
                        val = points[j + m];
                        f = format[m];
                        if (!f)
                            continue;
                        
                        if (f.x) {
                            if (val < xmin)
                                xmin = val;
                            if (val > xmax)
                                xmax = val;
                        }
                        if (f.y) {
                            if (val < ymin)
                                ymin = val;
                            if (val > ymax)
                                ymax = val;
                        }
                    }
                }
                
                if (s.bars.show) {
                    //store barLeft to prevent recalculation allowing overwrite in procDatapoints hook.
                    if(s.bars.barLeft==undefined)
                        s.bars.barLeft= s.bars.align == "left" ? 0 : -s.bars.barWidth/2;
                    // make sure we got room for the bar on the dancing floor
                    var delta = s.bars.barLeft;
                    if (s.bars.horizontal) {
                        ymin += delta;
                        ymax += delta + s.bars.barWidth;
                    }
                    else {
                        xmin += delta;
                        xmax += delta + s.bars.barWidth;
                    }
                }
                
                updateAxis(s.xaxis, xmin, xmax);
                updateAxis(s.yaxis, ymin, ymax);
            }

            $.each(getUsedAxes(), function (i, axis) {
                if (axis.datamin == topSentry)
                    axis.datamin = null;
                if (axis.datamax == bottomSentry)
                    axis.datamax = null;
            });
        }

        /**
         * Create plot canvas and overlay canvas elements (utilizing excanvas where needed),
         * set their dimensions, append to the placeholder, and assign global canvas context
         * variables.
         */
        function constructCanvas() {
            canvasWidth = placeholder.width();
            canvasHeight = placeholder.height();

             // excanvas hack, if there are any canvases here, whack
             // the state on them manually
            if (window.G_vmlCanvasManager)
                placeholder.find("canvas").each(function () {
                    this.context_ = null;
                });
            
            placeholder.html(""); // clear placeholder
            
            if (placeholder.css("position") == 'static')
                placeholder.css("position", "relative"); // for positioning labels and overlay

            if (canvasWidth <= 0 || canvasHeight <= 0)
                throw "Invalid dimensions for plot, width = " + canvasWidth + ", height = " + canvasHeight;

            function makeCanvas(cssClass, offset) {
                var c   = document.createElement('canvas');
                var $c  = $(c);
                c.width = canvasWidth;
                c.height = canvasHeight;
                
                if (cssClass) {
                    $c.addClass(cssClass);
                }

                if (offset !== undefined) {
                    $c.css({
                        position:   'absolute',
                        left:       offset.left,
                        top:        offset.top
                    });
                }

                $c.appendTo(placeholder);
                
                if (!c.getContext) // excanvas hack
                    c = window.G_vmlCanvasManager.initElement(c);

                return c;
            }
            
            // the canvas
            canvas = makeCanvas('plot');
            ctx = canvas.getContext("2d");

            var offset  = $(canvas).position();

            // overlay canvas for interactive features
            overlay = makeCanvas('overlay', offset);
            octx = overlay.getContext("2d");

        }

        function bindEvents() {
            // we include the canvas in the event holder too, because IE 7
            // sometimes has trouble with the stacking order
            eventHolder = $([overlay, canvas]);

            // bind events
            if (options.grid.hoverable) {
                eventHolder.mousemove(onMouseMove);
                eventHolder.mouseleave(onMouseLeave);
            }

            if (options.grid.clickable)
                eventHolder.click(onClick);

            executeHooks(hooks.bindEvents, [eventHolder]);
        }

        function setTransformationHelpers(axis) {
            // set helper functions on the axis, assumes plot area
            // has been computed already
            
            function identity(x) { return x; }
            
            var s, m, t = axis.options.transform || identity,
                it = axis.options.inverseTransform;
            
            if (axis.direction == "x") {
                // precompute how much the axis is scaling a point
                // in canvas space
                s = axis.scale = plotWidth / (t(axis.max) - t(axis.min));
                m = t(axis.min);

                // data point to canvas coordinate
                if (t == identity) // slight optimization
                    axis.p2c = function (p) { return (p - m) * s; };
                else
                    axis.p2c = function (p) { return (t(p) - m) * s; };
                // canvas coordinate to data point
                if (!it)
                    axis.c2p = function (c) { return m + c / s; };
                else
                    axis.c2p = function (c) { return it(m + c / s); };
            }
            else {
                s = axis.scale = plotHeight / (t(axis.max) - t(axis.min));
                m = t(axis.max);
                
                if (t == identity)
                    axis.p2c = function (p) { return (m - p) * s; };
                else
                    axis.p2c = function (p) { return (m - t(p)) * s; };
                if (!it)
                    axis.c2p = function (c) { return m - c / s; };
                else
                    axis.c2p = function (c) { return it(m - c / s); };
            }
        }

        //in order to move the labels after they have been rotated, we need to know some
        //things about the dimensions of it.  This is made harder by the fact that IE
        //"fixes" the post-rotation div.  What it does is do the rotation, and then 
        //move the result back into the original div.  All the CSS3-supporting browsers
        //do the rotation and then flow everything else around the original element.
        //Also, the div width and height aren't consistently changed between browsers,
        //so we have to calculate those too (even though the *display* of them is all
        //the same).
        function calculateRotatedDimensions(width,height,angle){
            if (!angle)
                return {};

            var rad = angle * Math.PI / 180,
                sin = Math.sin(rad),
                cos = Math.cos(rad);

            // Rotated lower-right coordinate - :XXX: connexions {
            var origCoords      = {
                    // (0 ,0), (w,0), (w ,-h ), (0,-h)
                tl: { x:     0, y:        0 },
                tr: { x: width, y:        0 },
                br: { x: width, y:  -height },
                bl: { x:     0, y:  -height },

                // Aligned
                al: { x:     0, y:-height/2 },  // left-middle  (-h/2,0)
                ar: { x: width, y:-height/2 }   // right-middle (w,-h/2)
            };
            var rotatedCoords   = { };
            var limits          = {
                min:{ x: 9999, y: 9999 },
                max:{ x:-9999, y:-9999 }
            };

            // Rotate each coordinate in 'origCoords'
            $.each(origCoords, function(pos, coords) {
                var rotCoords   = {
                    x: (coords.x * cos) - (coords.y * sin),
                    y: (coords.x * sin) + (coords.y * cos)
                };
                rotatedCoords[pos] = rotCoords;

                // Tracks limits, including left/right/top/bottom-most
                if (rotCoords.x < limits.min.x)
                {
                    // Min x thus far
                    limits.min.x = rotCoords.x;
                    limits.lm    = rotCoords;
                }

                if (rotCoords.x > limits.max.x)
                {
                    // Max x thus far
                    limits.max.x = rotCoords.x;
                    limits.rm    = rotCoords;
                }

                if (rotCoords.y < limits.min.y)
                {
                    // Min y thus far
                    limits.min.y = rotCoords.y;
                    limits.bm    = rotCoords;
                }

                if (rotCoords.y > limits.max.y)
                {
                    // Max y thus far
                    limits.max.y = rotCoords.y;
                    limits.tm    = rotCoords;
                }
            });
            // The bounding box is (min.x,min.y) - (max.x,max.y)
            var rotatedWidth    = limits.max.x - limits.min.x;
            var rotatedHeight   = limits.max.y - limits.min.y;

            var res ={
                width:      rotatedWidth,
                height:     rotatedHeight, 
                a_left:     rotatedCoords.al,
                a_right:    rotatedCoords.ar,
                topmost:    limits.tm,
                bottommost: limits.bm,
                leftmost:   limits.lm
            };
            return res;
            // :XXX: connexions }


            // original calculations
            var x1 =  cos * width,
                y1 =  sin * width;
            var x2 = -sin * height,
                y2 =  cos * height;
            var x3 = cos * width - sin * height,
                y3 = sin * width + cos * height;
            var minX = Math.min(0, x1, x2, x3),
                maxX = Math.max(0, x1, x2, x3),
                minY = Math.min(0, y1, y2, y3),
                maxY = Math.max(0, y1, y2, y3);

            //next figure out the x,y locations of certain points on the rotated
            //rectangle
            //specifically, if our rectangle is defined by (0 ,0),(w,0),(w ,-h ),(-h,0)
            //for negative angles:
            //  -we need to know where (-h',0'), as it is the left-most point
            //  -we need to know where (-h/2',0') is , for center alignment
            //  -and the same for the right side - (w',0') and (w',-h/2')
            var aligned_left = { x: height/2 * sin, y: height/2 * cos};
            var aligned_right = {x: (width*cos + height/2*sin), y: (width*sin - height/2*cos)};//(w',-h/2')
            var topmost,bottommost,leftmost;
            if (angle < 0){
                bottommost = { x: (width*cos + height*sin), y:(width*sin - height*cos)};//(w',-h')
                leftmost = { x: height * sin, y: height * cos};
            } else {
                topmost = { x: x1, y: y1};//(w',0)
                bottommost = { x: height * sin, y: -height*cos};//(0',-h')
            }

            return {width:(maxX-minX),height:(maxY - minY), 
                    a_left:aligned_left,a_right:aligned_right,
                    topmost:topmost,bottommost:bottommost,leftmost:leftmost};
        }

        // For the given axis, determine what offsets to place the labels assuming
        // that they are angled instead of centered on the tick
        // for top/bottom positioned axes, this returns the fixed top and also
        // a left offset from the tick
        // for left/right axes, a fixed left and a top offset
        function calculateAxisAngledLabels(axis){
            var angle = axis.options.labelAngle;
            if (angle == undefined || angle == 0)
                return {}; 
            var box = axis.box;
            var dims = calculateRotatedDimensions(axis.options.origWidth,axis.options.origHeight,angle);
            var align = "left";
            var oLeft=0, oTop=0, top, left;

            if (axis.position == 'bottom'){
                top = box.top + box.padding;
                if (angle < 0) {
                    if (hasCSS3transform)
                        oLeft = -dims.a_left.x;
                    else
                        oLeft = dims.a_left.x;
                } else {
                    align = "right";
                    oLeft = -dims.a_right.x;
                    if (hasCSS3transform)
                        top += dims.topmost.y;
                }
            } else if (axis.position == 'top') {
                top = box.top; 
                if (hasCSS3transform && angle > 0)
                    top += box.height - box.padding + dims.bottommost.y;

                if (angle < 0)
                    align = "right";
                if (!hasCSS3transform && angle < 0){
                    oLeft = -dims.width - dims.a_left.x;
                } else {
                    if (angle < 0)
                        oLeft = -dims.a_right.x;
                    else 
                        oLeft = -dims.a_left.x;
                }
            } else if (axis.position == 'left') {
                align = "right";
                left = box.left;
                if (angle < 0) {
                    oTop = dims.a_right.y;
                    if (hasCSS3transform)
                        left -= dims.leftmost.x;
                } else {
                    //left += (axis.options.origWidth-dims.width);
                    if (!hasCSS3transform)
                        oTop = -dims.a_left.y;
                    else
                        oTop = dims.a_right.y;
                }
            } else if (axis.position == 'right') {
                align = "left";
                left = box.left + box.padding;
                if (angle < 0) {
                    if (hasCSS3transform)
                        left -= dims.leftmost.x;
                    oTop = -dims.a_left.y;
                } else {
                    if (!hasCSS3transform)
                        oTop = -dims.height + dims.a_left.y;
                    else
                        oTop = -dims.a_left.y;
                }
            }

            return {top: top, left: left, oTop: oTop, oLeft: oLeft, align: align };
        }

        function measureTickLabels(axis) {
            if (!axis)
                return;
            
            var opts = axis.options, i, ticks = axis.ticks || [], labels = [],
                l, w = opts.labelWidth, h = opts.labelHeight, dummyDiv;

            function makeDummyDiv(labels, width) {
                return $('<div style="position:absolute;top:-10000px;' + width + '">' +
                         '<div class="' + axis.direction + 'Axis ' + axis.direction + axis.n + 'Axis">'
                         + labels.join("") + '</div></div>')
                    .appendTo(placeholder);
            }
            
            if (axis.direction == "x" && axis.options.labelAngle == 0) {
                // to avoid measuring the widths of the labels (it's slow), we
                // construct fixed-size boxes and put the labels inside
                // them, we don't need the exact figures and the
                // fixed-size box content is easy to center
                if (w == null)
                    w = Math.floor(canvasWidth / (ticks.length > 0 ? ticks.length : 1));

                // measure x label heights
                if (h == null) {
                    labels = [];
                    for (i = 0; i < ticks.length; ++i) {
                        l = ticks[i].label;
                        if (l)
                            labels.push('<div class="tickLabel" style="float:left;width:' + w + 'px">' + l + '</div>');
                    }

                    if (labels.length > 0) {
                        // stick them all in the same div and measure
                        // collective height
                        labels.push('<div style="clear:left"></div>');
                        dummyDiv = makeDummyDiv(labels, "width:10000px;");
                        h = dummyDiv.height();
                        dummyDiv.remove();
                    }
                }
            }
            else if (w == null || h == null) {
                // calculate y label dimensions
                for (i = 0; i < ticks.length; ++i) {
                    l = ticks[i].label;
                    if (l)
                        labels.push('<div class="tickLabel">' + l + '</div>');
                }
                
                if (labels.length > 0) {
                    dummyDiv = makeDummyDiv(labels, "");

                    var $labels = dummyDiv.find('.tickLabel');
                    var width   = $labels.width();
                    var height  = $labels.height();

                    if (axis.options.labelAngle != 0){
                        var dims = calculateRotatedDimensions(
                                    width,   //dummyDiv.children().width(),
                                    height,  //dummyDiv.find("div.tickLabel").height(),
                                    axis.options.labelAngle);
                        axis.options.origHeight = height;    //dummyDiv.find("div.tickLabel").height();
                        axis.options.origWidth = width;  //dummyDiv.children().width();
                        if (h == null)
                            h = dims.height;
                        if (w == null)
                            w = dims.width;
                    } else {
                        if (w == null)
                            w = width;   //dummyDiv.children().width();
                        if (h == null)
                            h = height;  //dummyDiv.find("div.tickLabel").height();
                    }
                    dummyDiv.remove();
                }
            }

            if (w == null)
                w = 0;
            if (h == null)
                h = 0;

            axis.labelWidth = w;
            axis.labelHeight = h;
        }

        function computeAxisBox(axis) {
            if (!axis || !axis.labelWidth || !axis.labelHeight)
                return;

            // find the bounding box of the axis by looking at label
            // widths/heights and ticks, make room by diminishing the
            // plotOffset

            var lw = axis.labelWidth,
                lh = axis.labelHeight,
                pos = axis.options.position,
                tickLength = axis.options.tickLength,
                axismargin = options.grid.axisMargin,
                padding = options.grid.labelMargin,
                all = axis.direction == "x" ? xaxes : yaxes,
                index;

            // determine axis margin
            var samePosition = $.grep(all, function (a) {
                return a && a.options.position == pos && (a.labelHeight || a.labelWidth);
            });
            if ($.inArray(axis, samePosition) == samePosition.length - 1)
                axismargin = 0; // outermost

            // determine tick length - if we're innermost, we can use "full"
            if (tickLength == null)
                tickLength = "full";

            var sameDirection = $.grep(all, function (a) {
                return a && (a.labelHeight || a.labelWidth);
            });

            var innermost = $.inArray(axis, sameDirection) == 0;
            if (!innermost && tickLength == "full")
                tickLength = 5;
                
            if (!isNaN(+tickLength))
                padding += +tickLength;

            // compute box
            if (axis.direction == "x") {
                lh += padding;
                
                if (pos == "bottom") {
                    plotOffset.bottom += lh + axismargin;
                    axis.box = {
                        top:    plotOffset.top +
                                (canvasHeight - plotOffset.bottom),
                        height: lh
                    };
                }
                else {
                    axis.box = {
                        top:    plotOffset.top + axismargin,
                        height: lh
                    };
                    plotOffset.top += lh + axismargin;
                }
            }
            else {
                lw += padding;
                
                if (pos == "left") {
                    axis.box = {
                        top:    plotOffset.top,
                        left:   plotOffset.left + axismargin,
                        width:  lw
                    };
                    plotOffset.left += lw + axismargin;
                }
                else {
                    plotOffset.right += lw + axismargin;
                    axis.box = {
                        top:    plotOffset.top,
                        left:   plotOffset.left +
                                (canvasWidth - plotOffset.right),
                        width: lw
                    };
                }
            }

             // save for future reference
            axis.position = pos;
            axis.tickLength = tickLength;
            axis.box.padding = padding;
            axis.innermost = innermost;
        }

        function fixupAxisBox(axis) {
            if (!axis || !axis.labelWidth || !axis.labelHeight)
                return;
            
            // set remaining bounding box coordinates
            if (axis.direction == "x") {
                axis.box.left = plotOffset.left;
                axis.box.width = plotWidth;
            }
            else {
                axis.box.top = plotOffset.top;
                axis.box.height = plotHeight;
            }
        }
        
        function setupGrid() {
            var axes = getUsedAxes(), j, k;

            // compute axis intervals
            for (k = 0; k < axes.length; ++k)
                setRange(axes[k]);

            
            //plotOffset.left = plotOffset.right = plotOffset.top = plotOffset.bottom = 0;
            plotOffset = $(canvas).position();
            plotOffset.right = plotOffset.bottom = 0;


            if (options.grid.show) {
                // make the ticks
                for (k = 0; k < axes.length; ++k) {
                    setupTickGeneration(axes[k]);
                    setTicks(axes[k]);
                    snapRangeToTicks(axes[k], axes[k].ticks);
                }

                // find labelWidth/Height, do this on all, not just
                // used as we might need to reserve space for unused
                // too if their labelWidth/Height is set
                for (j = 0; j < xaxes.length; ++j)
                    measureTickLabels(xaxes[j]);
                for (j = 0; j < yaxes.length; ++j)
                    measureTickLabels(yaxes[j]);
                    
                // compute the axis boxes, start from the outside (reverse order)
                for (j = xaxes.length - 1; j >= 0; --j)
                    computeAxisBox(xaxes[j]);
                for (j = yaxes.length - 1; j >= 0; --j)
                    computeAxisBox(yaxes[j]);

                // make sure we've got enough space for things that
                // might stick out
                var maxOutset = 0;
                for (var i = 0; i < series.length; ++i)
                    maxOutset = Math.max(maxOutset, 2 * (series[i].points.radius + series[i].points.lineWidth/2));

                for (var a in plotOffset) {
                    plotOffset[a] += options.grid.borderWidth;
                    plotOffset[a] = Math.max(maxOutset, plotOffset[a]);
                }
            }
            
            plotWidth = canvasWidth - plotOffset.left - plotOffset.right;
            plotHeight = canvasHeight - plotOffset.bottom - plotOffset.top;

            // now we got the proper plotWidth/Height, we can compute the scaling
            for (k = 0; k < axes.length; ++k)
                setTransformationHelpers(axes[k]);

            if (options.grid.show) {
                for (k = 0; k < axes.length; ++k)
                    fixupAxisBox(axes[k]);
                
                insertAxisLabels();
            }
            
            insertLegend();
        }
        
        function setRange(axis) {
            var opts = axis.options,
                min = +(opts.min != null ? opts.min : axis.datamin),
                max = +(opts.max != null ? opts.max : axis.datamax),
                delta = max - min;

            if (delta == 0.0) {
                // degenerate case
                var widen = max == 0 ? 1 : 0.01;

                if (opts.min == null)
                    min -= widen;
                // alway widen max if we couldn't widen min to ensure we
                // don't fall into min == max which doesn't work
                if (opts.max == null || opts.min != null)
                    max += widen;
            }
            else {
                // consider autoscaling
                var margin = opts.autoscaleMargin;
                var margin_max = opts.autoscaleMax;

                if (margin != null) {
                    if (opts.min == null) {
                        min -= delta * margin;
                        // make sure we don't go below zero if all values
                        // are positive
                        if (min < 0 && axis.datamin != null && axis.datamin >= 0)
                            min = 0;
                    }
                    if (opts.max == null) {
                        max += delta * margin;
                        max = margin_max && (max > margin_max) ? margin_max : max
                        if (max > 0 && axis.datamax != null && axis.datamax <= 0)
                            max = 0;
                    }
                }
            }
            axis.min = min;
            axis.max = max;
        }

        function setupTickGeneration(axis) {
            var opts = axis.options;
                
            // estimate number of ticks
            var noTicks;
            if (typeof opts.ticks == "number" && opts.ticks > 0)
                noTicks = opts.ticks;
            else if (axis.direction == "x")
                 // heuristic based on the model a*sqrt(x) fitted to
                 // some reasonable data points
                noTicks = 0.3 * Math.sqrt(canvasWidth);
            else
                noTicks = 0.3 * Math.sqrt(canvasHeight);

            var delta = (axis.max - axis.min) / noTicks,
                size, generator, unit, formatter, i, magn, norm;

            if (opts.mode == "time") {
                // pretty handling of time
                
                // map of app. size of time units in milliseconds
                var timeUnitSize = {
                    "second": 1000,
                    "minute": 60 * 1000,
                    "hour": 60 * 60 * 1000,
                    "day": 24 * 60 * 60 * 1000,
                    "month": 30 * 24 * 60 * 60 * 1000,
                    "year": 365.2425 * 24 * 60 * 60 * 1000
                };


                // the allowed tick sizes, after 1 year we use
                // an integer algorithm
                var spec = [
                    [1, "second"], [2, "second"], [5, "second"], [10, "second"],
                    [30, "second"], 
                    [1, "minute"], [2, "minute"], [5, "minute"], [10, "minute"],
                    [30, "minute"], 
                    [1, "hour"], [2, "hour"], [4, "hour"],
                    [8, "hour"], [12, "hour"],
                    [1, "day"], [2, "day"], [3, "day"],
                    [0.25, "month"], [0.5, "month"], [1, "month"],
                    [2, "month"], [3, "month"], [6, "month"],
                    [1, "year"]
                ];

                var minSize = 0;
                if (opts.minTickSize != null) {
                    if (typeof opts.tickSize == "number")
                        minSize = opts.tickSize;
                    else
                        minSize = opts.minTickSize[0] * timeUnitSize[opts.minTickSize[1]];
                }

                for (var i = 0; i < spec.length - 1; ++i)
                    if (delta < (spec[i][0] * timeUnitSize[spec[i][1]]
                                 + spec[i + 1][0] * timeUnitSize[spec[i + 1][1]]) / 2
                       && spec[i][0] * timeUnitSize[spec[i][1]] >= minSize)
                        break;
                size = spec[i][0];
                unit = spec[i][1];
                
                // special-case the possibility of several years
                if (unit == "year") {
                    magn = Math.pow(10, Math.floor(Math.log(delta / timeUnitSize.year) / Math.LN10));
                    norm = (delta / timeUnitSize.year) / magn;
                    if (norm < 1.5)
                        size = 1;
                    else if (norm < 3)
                        size = 2;
                    else if (norm < 7.5)
                        size = 5;
                    else
                        size = 10;

                    size *= magn;
                }

                axis.tickSize = opts.tickSize || [size, unit];
                
                generator = function(axis) {
                    var ticks = [],
                        tickSize = axis.tickSize[0], unit = axis.tickSize[1],
                        d = new Date(axis.min);
                    
                    var step = tickSize * timeUnitSize[unit];

                    if (unit == "second")
                        d.setUTCSeconds($.plot.floorInBase(d.getUTCSeconds(), tickSize));
                    if (unit == "minute")
                        d.setUTCMinutes($.plot.floorInBase(d.getUTCMinutes(), tickSize));
                    if (unit == "hour")
                        d.setUTCHours($.plot.floorInBase(d.getUTCHours(), tickSize));
                    if (unit == "month")
                        d.setUTCMonth($.plot.floorInBase(d.getUTCMonth(), tickSize));
                    if (unit == "year")
                        d.setUTCFullYear($.plot.floorInBase(d.getUTCFullYear(), tickSize));
                    
                    // reset smaller components
                    d.setUTCMilliseconds(0);
                    if (step >= timeUnitSize.minute)
                        d.setUTCSeconds(0);
                    if (step >= timeUnitSize.hour)
                        d.setUTCMinutes(0);
                    if (step >= timeUnitSize.day)
                        d.setUTCHours(0);
                    if (step >= timeUnitSize.day * 4)
                        d.setUTCDate(1);
                    if (step >= timeUnitSize.year)
                        d.setUTCMonth(0);


                    var carry = 0, v = Number.NaN, prev;
                    do {
                        prev = v;
                        v = d.getTime();
                        ticks.push(v);
                        if (unit == "month") {
                            if (tickSize < 1) {
                                // a bit complicated - we'll divide the month
                                // up but we need to take care of fractions
                                // so we don't end up in the middle of a day
                                d.setUTCDate(1);
                                var start = d.getTime();
                                d.setUTCMonth(d.getUTCMonth() + 1);
                                var end = d.getTime();
                                d.setTime(v + carry * timeUnitSize.hour + (end - start) * tickSize);
                                carry = d.getUTCHours();
                                d.setUTCHours(0);
                            }
                            else
                                d.setUTCMonth(d.getUTCMonth() + tickSize);
                        }
                        else if (unit == "year") {
                            d.setUTCFullYear(d.getUTCFullYear() + tickSize);
                        }
                        else
                            d.setTime(v + step);
                    } while (v < axis.max && v != prev);

                    return ticks;
                };

                formatter = function (v, axis) {
                    var d = new Date(v);

                    // first check global format
                    if (opts.timeformat != null)
                        return $.plot.formatDate(d, opts.timeformat, opts.monthNames);
                    
                    var t = axis.tickSize[0] * timeUnitSize[axis.tickSize[1]];
                    var span = axis.max - axis.min;
                    var suffix = (opts.twelveHourClock) ? " %p" : "";
                    
                    if (t < timeUnitSize.minute)
                        fmt = "%h:%M:%S" + suffix;
                    else if (t < timeUnitSize.day) {
                        if (span < 2 * timeUnitSize.day)
                            fmt = "%h:%M" + suffix;
                        else
                            fmt = "%b %d %h:%M" + suffix;
                    }
                    else if (t < timeUnitSize.month)
                        fmt = "%b %d";
                    else if (t < timeUnitSize.year) {
                        if (span < timeUnitSize.year)
                            fmt = "%b";
                        else
                            fmt = "%b %y";
                    }
                    else
                        fmt = "%y";
                    
                    return $.plot.formatDate(d, fmt, opts.monthNames);
                };
            }
            else {
                // pretty rounding of base-10 numbers
                var maxDec = opts.tickDecimals;
                var dec = -Math.floor(Math.log(delta) / Math.LN10);
                if (maxDec != null && dec > maxDec)
                    dec = maxDec;

                magn = Math.pow(10, -dec);
                norm = delta / magn; // norm is between 1.0 and 10.0
                
                if (norm < 1.5)
                    size = 1;
                else if (norm < 3) {
                    size = 2;
                    // special case for 2.5, requires an extra decimal
                    if (norm > 2.25 && (maxDec == null || dec + 1 <= maxDec)) {
                        size = 2.5;
                        ++dec;
                    }
                }
                else if (norm < 7.5)
                    size = 5;
                else
                    size = 10;

                size *= magn;
                
                if (opts.minTickSize != null && size < opts.minTickSize)
                    size = opts.minTickSize;

                axis.tickDecimals = Math.max(0, maxDec != null ? maxDec : dec);
                axis.tickSize = opts.tickSize || size;

                generator = function (axis) {
                    var ticks = [];

                    // spew out all possible ticks
                    var start = $.plot.floorInBase(axis.min, axis.tickSize),
                        i = 0, v = Number.NaN, prev;
                    do {
                        prev = v;
                        v = start + i * axis.tickSize;
                        ticks.push(v);
                        ++i;
                    } while (v < axis.max && v != prev);
                    return ticks;
                };

                formatter = function (v, axis) {
                    return v.toFixed(axis.tickDecimals);
                };
            }

            if (opts.alignTicksWithAxis != null) {
                var otherAxis = (axis.direction == "x" ? xaxes : yaxes)[opts.alignTicksWithAxis - 1];
                if (otherAxis && otherAxis.used && otherAxis != axis) {
                    // consider snapping min/max to outermost nice ticks
                    var niceTicks = generator(axis);
                    if (niceTicks.length > 0) {
                        if (opts.min == null)
                            axis.min = Math.min(axis.min, niceTicks[0]);
                        if (opts.max == null && niceTicks.length > 1)
                            axis.max = Math.max(axis.max, niceTicks[niceTicks.length - 1]);
                    }
                    
                    generator = function (axis) {
                        // copy ticks, scaled to this axis
                        var ticks = [], v, i;
                        for (i = 0; i < otherAxis.ticks.length; ++i) {
                            v = (otherAxis.ticks[i].v - otherAxis.min) / (otherAxis.max - otherAxis.min);
                            v = axis.min + v * (axis.max - axis.min);
                            ticks.push(v);
                        }
                        return ticks;
                    };
                    
                    // we might need an extra decimal since forced
                    // ticks don't necessarily fit naturally
                    if (axis.mode != "time" && opts.tickDecimals == null) {
                        var extraDec = Math.max(0, -Math.floor(Math.log(delta) / Math.LN10) + 1),
                            ts = generator(axis);

                        // only proceed if the tick interval rounded
                        // with an extra decimal doesn't give us a
                        // zero at end
                        if (!(ts.length > 1 && /\..*0$/.test((ts[1] - ts[0]).toFixed(extraDec))))
                            axis.tickDecimals = extraDec;
                    }
                }
            }

            axis.tickGenerator = generator;
            if ($.isFunction(opts.tickFormatter))
                axis.tickFormatter = function (v, axis) { return "" + opts.tickFormatter(v, axis); };
            else
                axis.tickFormatter = formatter;
        }
        
        function setTicks(axis) {
            axis.ticks = [];

            var oticks = axis.options.ticks, ticks = [];
            if (oticks == null || (typeof oticks == "number" && oticks > 0))
                ticks = axis.tickGenerator(axis);
            else if (oticks) {
                if ($.isFunction(oticks))
                    // generate the ticks
                    ticks = oticks({ min: axis.min, max: axis.max });
                else
                    ticks = oticks;
            }

            // clean up/labelify the supplied ticks, copy them over
            var i, v;
            for (i = 0; i < ticks.length; ++i) {
                var label = null;
                var t = ticks[i];
                if (typeof t == "object") {
                    v = t[0];
                    if (t.length > 1)
                        label = t[1];
                }
                else
                    v = t;
                if (label == null)
                    label = axis.tickFormatter(v, axis);
                axis.ticks[i] = { v: v, label: label };
            }
        }

        function snapRangeToTicks(axis, ticks) {
            if (axis.options.autoscaleMargin && ticks.length > 0) {
                // snap to ticks
                if (axis.options.min == null) {
                    axis.min = Math.min(axis.min, ticks[0].v);
                }
                if (axis.options.max == null && ticks.length > 1) {
                    axis.max = Math.max(axis.max, ticks[ticks.length - 1].v);
                    if (axis.options.autoscaleMax) {
                        axis.max = Math.min(axis.max, axis.options.autoscaleMax);
                    }
                }
            }
        }
      
        /**
         * Draw the grid (if applicable) and all series, with the order depending on whether the
         * grid should be above the series or not.
         */
        function draw() {
            ctx.clearRect(0, 0, canvasWidth, canvasHeight);

            var grid = options.grid;

            // draw background, if any
            if (grid.show && grid.backgroundColor)
                drawBackground();
            
            if (grid.show && !grid.aboveData)
                drawGrid();

            for (var i = 0; i < series.length; ++i) {
                executeHooks(hooks.drawSeries, [ctx, series[i]]);
                drawSeries(series[i]);
            }

            executeHooks(hooks.draw, [ctx]);
            
            if (grid.show && grid.aboveData)
                drawGrid();
        }

        function extractRange(ranges, coord) {
            var axis, from, to, axes, key;

            axes = getUsedAxes();
            for (i = 0; i < axes.length; ++i) {
                axis = axes[i];
                if (axis.direction == coord) {
                    key = coord + axis.n + "axis";
                    if (!ranges[key] && axis.n == 1)
                        key = coord + "axis"; // support x1axis as xaxis
                    if (ranges[key]) {
                        from = ranges[key].from;
                        to = ranges[key].to;
                        break;
                    }
                }
            }

            // backwards-compat stuff - to be removed in future
            if (!ranges[key]) {
                axis = coord == "x" ? xaxes[0] : yaxes[0];
                from = ranges[coord + "1"];
                to = ranges[coord + "2"];
            }

            // auto-reverse as an added bonus
            if (from != null && to != null && from > to) {
                var tmp = from;
                from = to;
                to = tmp;
            }
            
            return { from: from, to: to, axis: axis };
        }
        
        function drawBackground() {
            ctx.save();
            ctx.translate(plotOffset.left, plotOffset.top);

            ctx.fillStyle = getColorOrGradient(options.grid.backgroundColor, plotHeight, 0, "rgba(255, 255, 255, 0)");
            ctx.fillRect(0, 0, plotWidth, plotHeight);
            ctx.restore();
        }

        function drawGrid() {
            var i;
            
            ctx.save();
            ctx.translate(plotOffset.left, plotOffset.top);

            // draw markings
            var markings = options.grid.markings;
            if (markings) {
                if ($.isFunction(markings)) {
                    var axes = plot.getAxes();
                    // xmin etc. is backwards compatibility, to be
                    // removed in the future
                    axes.xmin = axes.xaxis.min;
                    axes.xmax = axes.xaxis.max;
                    axes.ymin = axes.yaxis.min;
                    axes.ymax = axes.yaxis.max;
                    
                    markings = markings(axes);
                }

                for (i = 0; i < markings.length; ++i) {
                    var m = markings[i],
                        xrange = extractRange(m, "x"),
                        yrange = extractRange(m, "y");

                    // fill in missing
                    if (xrange.from == null)
                        xrange.from = xrange.axis.min;
                    if (xrange.to == null)
                        xrange.to = xrange.axis.max;
                    if (yrange.from == null)
                        yrange.from = yrange.axis.min;
                    if (yrange.to == null)
                        yrange.to = yrange.axis.max;

                    // clip
                    if (xrange.to < xrange.axis.min || xrange.from > xrange.axis.max ||
                        yrange.to < yrange.axis.min || yrange.from > yrange.axis.max)
                        continue;

                    xrange.from = Math.max(xrange.from, xrange.axis.min);
                    xrange.to = Math.min(xrange.to, xrange.axis.max);
                    yrange.from = Math.max(yrange.from, yrange.axis.min);
                    yrange.to = Math.min(yrange.to, yrange.axis.max);

                    if (xrange.from == xrange.to && yrange.from == yrange.to)
                        continue;

                    // then draw
                    xrange.from = xrange.axis.p2c(xrange.from);
                    xrange.to = xrange.axis.p2c(xrange.to);
                    yrange.from = yrange.axis.p2c(yrange.from);
                    yrange.to = yrange.axis.p2c(yrange.to);
                    
                    if (xrange.from == xrange.to || yrange.from == yrange.to) {
                        // draw line
                        ctx.beginPath();
                        ctx.strokeStyle = m.color || options.grid.markingsColor;
                        ctx.lineWidth = m.lineWidth || options.grid.markingsLineWidth;
                        ctx.moveTo(xrange.from, yrange.from);
                        ctx.lineTo(xrange.to, yrange.to);
                        ctx.stroke();
                    }
                    else {
                        // fill area
                        ctx.fillStyle = m.color || options.grid.markingsColor;
                        ctx.fillRect(xrange.from, yrange.to,
                                     xrange.to - xrange.from,
                                     yrange.from - yrange.to);
                    }
                }
            }
            
            // draw the ticks
            var axes = getUsedAxes(), bw = options.grid.borderWidth;

            for (var j = 0; j < axes.length; ++j) {
                var axis = axes[j], box = axis.box,
                    t = axis.tickLength, x, y, xoff, yoff;

                if (axis.ticks.length == 0)
                    continue;
                
                ctx.strokeStyle = axis.options.tickColor || $.color.parse(axis.options.color).scale('a', 0.22).toString();
                ctx.lineWidth = 1;

                // find the edges
                if (axis.direction == "x") {
                    x = 0;
                    if (t == "full")
                        y = (axis.position == "top" ? 0 : plotHeight);
                    else
                        y = box.top - plotOffset.top + (axis.position == "top" ? box.height : 0);
                }
                else {
                    y = 0;
                    if (t == "full")
                        x = (axis.position == "left" ? 0 : plotWidth);
                    else
                        x = box.left - plotOffset.left + (axis.position == "left" ? box.width : 0);
                }
                
                // draw tick bar
                if (!axis.innermost) {
                    ctx.beginPath();
                    xoff = yoff = 0;
                    if (axis.direction == "x")
                        xoff = plotWidth;
                    else
                        yoff = plotHeight;
                    
                    if (ctx.lineWidth == 1) {
                        x = Math.floor(x) + 0.5;
                        y = Math.floor(y) + 0.5;
                    }

                    ctx.moveTo(x, y);
                    ctx.lineTo(x + xoff, y + yoff);
                    ctx.stroke();
                }

                // draw ticks
                ctx.beginPath();
                for (i = 0; i < axis.ticks.length; ++i) {
                    var v = axis.ticks[i].v;
                    
                    xoff = yoff = 0;

                    if (v < axis.min || v > axis.max
                        // skip those lying on the axes if we got a border
                        || (t == "full" && bw > 0
                            && (v == axis.min || v == axis.max)))
                        continue;

                    if (axis.direction == "x") {
                        x = axis.p2c(v);
                        yoff = t == "full" ? -plotHeight : t;
                        
                        if (axis.position == "top")
                            yoff = -yoff;
                    }
                    else {
                        y = axis.p2c(v);
                        xoff = t == "full" ? -plotWidth : t;
                        
                        if (axis.position == "left")
                            xoff = -xoff;
                    }

                    if (ctx.lineWidth == 1) {
                        if (axis.direction == "x")
                            x = Math.floor(x) + 0.5;
                        else
                            y = Math.floor(y) + 0.5;
                    }

                    ctx.moveTo(x, y);
                    ctx.lineTo(x + xoff, y + yoff);
                }
                
                ctx.stroke();
            }
            
            
            // draw border
            if (bw) {
                ctx.lineWidth = bw;
                ctx.strokeStyle = options.grid.borderColor;
                ctx.strokeRect(-bw/2, -bw/2, plotWidth + bw, plotHeight + bw);
            }

            ctx.restore();
        }


        function insertAxisLabels() {
            //figure out whether the browser supports CSS3 2d transforms 
            //for label angle, logic borrowed from Modernizr
            var transform = undefined,addRotateLabelStyles = function () {},
            props = [ 'transformProperty', 'WebkitTransform', 'MozTransform', 'OTransform', 'msTransform' ],
            prefix = [ '', '-webkit-', '-moz-', '-o-', '-ms-' ],
            testEl = document.createElement('flotelement');

            for ( var i in props) {
                if ( testEl.style[ props[i] ] !== undefined ) {
                    transform = prefix[i];
                    break;
                }
            }

            if (transform != undefined) { //use CSS3 2d transforms
                hasCSS3transform = true;
                addRotateLabelStyles = function(styles,axis){
                    //flip the angle so CSS3 and Filter work the same way
                    styles.push(transform+"transform:rotate("+-axis.options.labelAngle+"deg)");
                    styles.push(transform+"transform-origin:top left");
                }
            } else if (typeof testEl.style.filter == 'string' || 
                       typeof testEl.style.filters == 'object') { //IE without 2d transforms
                addRotateLabelStyles = function(styles,axis) {
                    var rad = axis.options.labelAngle * Math.PI / 180,
                    cos = Math.cos(rad),
                    sin = Math.sin(rad);
                 
                    styles.push("filter:progid:DXImageTransform.Microsoft.Matrix(M11="+cos+", M12="+sin+", M21="+(-sin)+", M22="+cos+",sizingMethod='auto expand'");
                }
            } 

            placeholder.find(".tickLabels").remove();
            
            var html = ['<div class="tickLabels">'];

            var axes = getUsedAxes();
            for (var j = 0; j < axes.length; ++j) {
                var axis = axes[j], box = axis.box;
                var angledPos = calculateAxisAngledLabels(axis);
                //debug: html.push('<div style="position:absolute;opacity:0.10;background-color:red;left:' + box.left + 'px;top:' + box.top + 'px;width:' + box.width +  'px;height:' + box.height + 'px"></div>')
                html.push('<div class="' + axis.direction + 'Axis ' + axis.direction + axis.n + 'Axis" style="color:' + axis.options.color + '">');
                for (var i = 0; i < axis.ticks.length; ++i) {
                    var tick = axis.ticks[i];
                    if (!tick.label || tick.v < axis.min || tick.v > axis.max)
                        continue;

                    var pos = {}, align;
                    
                    if (axis.direction == "x") {
                        if (axis.options.labelAngle != 0){
                            align = angledPos.align;
                            pos.left = Math.round(plotOffset.left + axis.p2c(tick.v));
                            pos.left += angledPos.oLeft;
                            pos.top = angledPos.top;
                        } else {
                            align = "center";
                            pos.left = Math.round(plotOffset.left + axis.p2c(tick.v) - axis.labelWidth/2);
                            if (axis.position == "bottom")
                                pos.top = box.top + box.padding;
                            else
                                pos.bottom = canvasHeight - (box.top + box.height - box.padding);
                        }
                    } 
                    else {
                        if (axis.options.labelAngle != 0){
                            align = angledPos.align;
                            pos.top = Math.round(plotOffset.top + axis.p2c(tick.v));
                            pos.top += angledPos.oTop;
                            pos.left = angledPos.left;
                        } else {
                            //pos.top = Math.round(plotOffset.top + axis.p2c(tick.v) - axis.labelHeight/2);
                            pos.top = Math.round(box.top + axis.p2c(tick.v));
                            if (axis.position == "left") {
                                pos.right = canvasWidth - (box.left + box.width - box.padding)
                                align = "right";
                            }
                            else {
                                pos.left = box.left + box.padding;
                                align = "left";
                            }
                        }
                    }

                    pos.width = (axis.options.labelAngle != 0)?axis.options.origWidth:axis.labelWidth;

                    var style = ["position:absolute", "text-align:" + align ];
                    for (var a in pos)
                        style.push(a + ":" + pos[a] + "px")

                    if (axis.options.labelAngle != 0)
                        addRotateLabelStyles(style,axis);
                    
                    html.push('<div class="tickLabel" style="' + style.join(';') + '">' + tick.label + '</div>');
                }
                html.push('</div>');
            }

            html.push('</div>');

            placeholder.append(html.join(""));
        }

        /**
         * Draw given series, with it's options.
         *
         * @param series <Object> containing series options
         */
        function drawSeries(series) {
            if (series.lines.show)
                drawSeriesLines(series);
            if (series.bars.show)
                drawSeriesBars(series);
            if (series.points.show)
                drawSeriesPoints(series);
        }
        
        function drawSeriesLines(series) {
            function plotLine(datapoints, xoffset, yoffset, axisx, axisy) {
                var points = datapoints.points,
                    ps = datapoints.pointsize,
                    prevx = null, prevy = null;
                
                ctx.beginPath();
                for (var i = ps; i < points.length; i += ps) {
                    var x1 = points[i - ps], y1 = points[i - ps + 1],
                        x2 = points[i], y2 = points[i + 1];
                    
                    if (x1 == null || x2 == null)
                        continue;

                    // clip with ymin
                    if (y1 <= y2 && y1 < axisy.min) {
                        if (y2 < axisy.min)
                            continue;   // line segment is outside
                        // compute new intersection point
                        x1 = (axisy.min - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y1 = axisy.min;
                    }
                    else if (y2 <= y1 && y2 < axisy.min) {
                        if (y1 < axisy.min)
                            continue;
                        x2 = (axisy.min - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y2 = axisy.min;
                    }

                    // clip with ymax
                    if (y1 >= y2 && y1 > axisy.max) {
                        if (y2 > axisy.max)
                            continue;
                        x1 = (axisy.max - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y1 = axisy.max;
                    }
                    else if (y2 >= y1 && y2 > axisy.max) {
                        if (y1 > axisy.max)
                            continue;
                        x2 = (axisy.max - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y2 = axisy.max;
                    }

                    // clip with xmin
                    if (x1 <= x2 && x1 < axisx.min) {
                        if (x2 < axisx.min)
                            continue;
                        y1 = (axisx.min - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x1 = axisx.min;
                    }
                    else if (x2 <= x1 && x2 < axisx.min) {
                        if (x1 < axisx.min)
                            continue;
                        y2 = (axisx.min - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x2 = axisx.min;
                    }

                    // clip with xmax
                    if (x1 >= x2 && x1 > axisx.max) {
                        if (x2 > axisx.max)
                            continue;
                        y1 = (axisx.max - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x1 = axisx.max;
                    }
                    else if (x2 >= x1 && x2 > axisx.max) {
                        if (x1 > axisx.max)
                            continue;
                        y2 = (axisx.max - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x2 = axisx.max;
                    }

                    if (x1 != prevx || y1 != prevy)
                        ctx.moveTo(axisx.p2c(x1) + xoffset, axisy.p2c(y1) + yoffset);
                    
                    prevx = x2;
                    prevy = y2;
                    ctx.lineTo(axisx.p2c(x2) + xoffset, axisy.p2c(y2) + yoffset);
                }
                ctx.stroke();
            }

            function plotLineArea(datapoints, axisx, axisy) {
                var points = datapoints.points,
                    ps = datapoints.pointsize,
                    bottom = Math.min(Math.max(0, axisy.min), axisy.max),
                    i = 0, top, areaOpen = false,
                    ypos = 1, segmentStart = 0, segmentEnd = 0;

                // we process each segment in two turns, first forward
                // direction to sketch out top, then once we hit the
                // end we go backwards to sketch the bottom
                while (true) {
                    if (ps > 0 && i > points.length + ps)
                        break;

                    i += ps; // ps is negative if going backwards

                    var x1 = points[i - ps],
                        y1 = points[i - ps + ypos],
                        x2 = points[i], y2 = points[i + ypos];

                    if (areaOpen) {
                        if (ps > 0 && x1 != null && x2 == null) {
                            // at turning point
                            segmentEnd = i;
                            ps = -ps;
                            ypos = 2;
                            continue;
                        }

                        if (ps < 0 && i == segmentStart + ps) {
                            // done with the reverse sweep
                            ctx.fill();
                            areaOpen = false;
                            ps = -ps;
                            ypos = 1;
                            i = segmentStart = segmentEnd + ps;
                            continue;
                        }
                    }

                    if (x1 == null || x2 == null)
                        continue;

                    // clip x values
                    
                    // clip with xmin
                    if (x1 <= x2 && x1 < axisx.min) {
                        if (x2 < axisx.min)
                            continue;
                        y1 = (axisx.min - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x1 = axisx.min;
                    }
                    else if (x2 <= x1 && x2 < axisx.min) {
                        if (x1 < axisx.min)
                            continue;
                        y2 = (axisx.min - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x2 = axisx.min;
                    }

                    // clip with xmax
                    if (x1 >= x2 && x1 > axisx.max) {
                        if (x2 > axisx.max)
                            continue;
                        y1 = (axisx.max - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x1 = axisx.max;
                    }
                    else if (x2 >= x1 && x2 > axisx.max) {
                        if (x1 > axisx.max)
                            continue;
                        y2 = (axisx.max - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x2 = axisx.max;
                    }

                    if (!areaOpen) {
                        // open area
                        ctx.beginPath();
                        ctx.moveTo(axisx.p2c(x1), axisy.p2c(bottom));
                        areaOpen = true;
                    }
                    
                    // now first check the case where both is outside
                    if (y1 >= axisy.max && y2 >= axisy.max) {
                        ctx.lineTo(axisx.p2c(x1), axisy.p2c(axisy.max));
                        ctx.lineTo(axisx.p2c(x2), axisy.p2c(axisy.max));
                        continue;
                    }
                    else if (y1 <= axisy.min && y2 <= axisy.min) {
                        ctx.lineTo(axisx.p2c(x1), axisy.p2c(axisy.min));
                        ctx.lineTo(axisx.p2c(x2), axisy.p2c(axisy.min));
                        continue;
                    }
                    
                    // else it's a bit more complicated, there might
                    // be a flat maxed out rectangle first, then a
                    // triangular cutout or reverse; to find these
                    // keep track of the current x values
                    var x1old = x1, x2old = x2;

                    // clip the y values, without shortcutting, we
                    // go through all cases in turn
                    
                    // clip with ymin
                    if (y1 <= y2 && y1 < axisy.min && y2 >= axisy.min) {
                        x1 = (axisy.min - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y1 = axisy.min;
                    }
                    else if (y2 <= y1 && y2 < axisy.min && y1 >= axisy.min) {
                        x2 = (axisy.min - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y2 = axisy.min;
                    }

                    // clip with ymax
                    if (y1 >= y2 && y1 > axisy.max && y2 <= axisy.max) {
                        x1 = (axisy.max - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y1 = axisy.max;
                    }
                    else if (y2 >= y1 && y2 > axisy.max && y1 <= axisy.max) {
                        x2 = (axisy.max - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y2 = axisy.max;
                    }

                    // if the x value was changed we got a rectangle
                    // to fill
                    if (x1 != x1old) {
                        ctx.lineTo(axisx.p2c(x1old), axisy.p2c(y1));
                        // it goes to (x1, y1), but we fill that below
                    }
                    
                    // fill triangular section, this sometimes result
                    // in redundant points if (x1, y1) hasn't changed
                    // from previous line to, but we just ignore that
                    ctx.lineTo(axisx.p2c(x1), axisy.p2c(y1));
                    ctx.lineTo(axisx.p2c(x2), axisy.p2c(y2));

                    // fill the other rectangle if it's there
                    if (x2 != x2old) {
                        ctx.lineTo(axisx.p2c(x2), axisy.p2c(y2));
                        ctx.lineTo(axisx.p2c(x2old), axisy.p2c(y2));
                    }
                }
            }

            ctx.save();
            ctx.translate(plotOffset.left, plotOffset.top);
            ctx.lineJoin = "round";

            var lw = series.lines.lineWidth,
                sw = series.shadowSize;
            // FIXME: consider another form of shadow when filling is turned on
            if (lw > 0 && sw > 0) {
                // draw shadow as a thick and thin line with transparency
                ctx.lineWidth = sw;
                ctx.strokeStyle = "rgba(0,0,0,0.1)";
                // position shadow at angle from the mid of line
                var angle = Math.PI/18;
                plotLine(series.datapoints, Math.sin(angle) * (lw/2 + sw/2), Math.cos(angle) * (lw/2 + sw/2), series.xaxis, series.yaxis);
                ctx.lineWidth = sw/2;
                plotLine(series.datapoints, Math.sin(angle) * (lw/2 + sw/4), Math.cos(angle) * (lw/2 + sw/4), series.xaxis, series.yaxis);
            }

            ctx.lineWidth = lw;
            ctx.strokeStyle = series.color;
            var fillStyle = getFillStyle(series.lines, series.color, 0, plotHeight);
            if (fillStyle) {
                ctx.fillStyle = fillStyle;
                plotLineArea(series.datapoints, series.xaxis, series.yaxis);
            }

            if (lw > 0)
                plotLine(series.datapoints, 0, 0, series.xaxis, series.yaxis);
            ctx.restore();
        }

        function drawSeriesPoints(series) {
            function plotPoints(datapoints, radius, fillStyle, offset, shadow, axisx, axisy, symbol) {
                var points = datapoints.points, ps = datapoints.pointsize;

                for (var i = 0; i < points.length; i += ps) {
                    var x = points[i], y = points[i + 1];
                    if (x == null || x < axisx.min || x > axisx.max || y < axisy.min || y > axisy.max)
                        continue;
                    
                    ctx.beginPath();
                    x = axisx.p2c(x);
                    y = axisy.p2c(y) + offset;
                    if (symbol == "circle")
                        ctx.arc(x, y, radius, 0, shadow ? Math.PI : Math.PI * 2, false);
                    else
                        symbol(ctx, x, y, radius, shadow);
                    ctx.closePath();
                    
                    if (fillStyle) {
                        ctx.fillStyle = fillStyle;
                        ctx.fill();
                    }
                    ctx.stroke();
                }
            }
            
            ctx.save();
            ctx.translate(plotOffset.left, plotOffset.top);

            var lw = series.points.lineWidth,
                sw = series.shadowSize,
                radius = series.points.radius,
                symbol = series.points.symbol;
            if (lw > 0 && sw > 0) {
                // draw shadow in two steps
                var w = sw / 2;
                ctx.lineWidth = w;
                ctx.strokeStyle = "rgba(0,0,0,0.1)";
                plotPoints(series.datapoints, radius, null, w + w/2, true,
                           series.xaxis, series.yaxis, symbol);

                ctx.strokeStyle = "rgba(0,0,0,0.2)";
                plotPoints(series.datapoints, radius, null, w/2, true,
                           series.xaxis, series.yaxis, symbol);
            }

            ctx.lineWidth = lw;
            ctx.strokeStyle = series.color;
            plotPoints(series.datapoints, radius,
                       getFillStyle(series.points, series.color), 0, false,
                       series.xaxis, series.yaxis, symbol);
            ctx.restore();
        }

        function drawBar(x, y, b, barLeft, barRight, offset, fillStyleCallback, axisx, axisy, c, horizontal, lineWidth) {
            var left, right, bottom, top,
                drawLeft, drawRight, drawTop, drawBottom,
                tmp;

            // in horizontal mode, we start the bar from the left
            // instead of from the bottom so it appears to be
            // horizontal rather than vertical
            if (horizontal) {
                drawBottom = drawRight = drawTop = true;
                drawLeft = false;
                left = b;
                right = x;
                top = y + barLeft;
                bottom = y + barRight;

                // account for negative bars
                if (right < left) {
                    tmp = right;
                    right = left;
                    left = tmp;
                    drawLeft = true;
                    drawRight = false;
                }
            }
            else {
                drawLeft = drawRight = drawTop = true;
                drawBottom = false;
                left = x + barLeft;
                right = x + barRight;
                bottom = b;
                top = y;

                // account for negative bars
                if (top < bottom) {
                    tmp = top;
                    top = bottom;
                    bottom = tmp;
                    drawBottom = true;
                    drawTop = false;
                }
            }
           
            // clip
            if (right < axisx.min || left > axisx.max ||
                top < axisy.min || bottom > axisy.max)
                return;
            
            if (left < axisx.min) {
                left = axisx.min;
                drawLeft = false;
            }

            if (right > axisx.max) {
                right = axisx.max;
                drawRight = false;
            }

            if (bottom < axisy.min) {
                bottom = axisy.min;
                drawBottom = false;
            }
            
            if (top > axisy.max) {
                top = axisy.max;
                drawTop = false;
            }

            left = axisx.p2c(left);
            bottom = axisy.p2c(bottom);
            right = axisx.p2c(right);
            top = axisy.p2c(top);
            
            // fill the bar
            if (fillStyleCallback) {
                c.beginPath();
                c.moveTo(left, bottom);
                c.lineTo(left, top);
                c.lineTo(right, top);
                c.lineTo(right, bottom);
                c.fillStyle = fillStyleCallback(bottom, top);
                c.fill();
            }

            // draw outline
            if (lineWidth > 0 && (drawLeft || drawRight || drawTop || drawBottom)) {
                c.beginPath();

                // FIXME: inline moveTo is buggy with excanvas
                c.moveTo(left, bottom + offset);
                if (drawLeft)
                    c.lineTo(left, top + offset);
                else
                    c.moveTo(left, top + offset);
                if (drawTop)
                    c.lineTo(right, top + offset);
                else
                    c.moveTo(right, top + offset);
                if (drawRight)
                    c.lineTo(right, bottom + offset);
                else
                    c.moveTo(right, bottom + offset);
                if (drawBottom)
                    c.lineTo(left, bottom + offset);
                else
                    c.moveTo(left, bottom + offset);
                c.stroke();
            }
        }
        
        function drawSeriesBars(series) {
            function plotBars(datapoints, barLeft, barRight, offset, fillStyleCallback, axisx, axisy) {
                var points = datapoints.points, ps = datapoints.pointsize;
                
                for (var i = 0; i < points.length; i += ps) {
                    if (points[i] == null)
                        continue;
                    drawBar(points[i], points[i + 1], points[i + 2], barLeft, barRight, offset, fillStyleCallback, axisx, axisy, ctx, series.bars.horizontal, series.bars.lineWidth);
                }
            }

            ctx.save();
            ctx.translate(plotOffset.left, plotOffset.top);

            // FIXME: figure out a way to add shadows (for instance along the right edge)
            ctx.lineWidth = series.bars.lineWidth;
            ctx.strokeStyle = series.color;
            var barLeft = series.bars.barLeft;
            var fillStyleCallback = series.bars.fill ? function (bottom, top) { return getFillStyle(series.bars, series.color, bottom, top); } : null;
            plotBars(series.datapoints, barLeft, barLeft + series.bars.barWidth, 0, fillStyleCallback, series.xaxis, series.yaxis);
            ctx.restore();
        }

        function getFillStyle(filloptions, seriesColor, bottom, top) {
            var fill = filloptions.fill;
            if (!fill)
                return null;

            if (filloptions.fillColor)
                return getColorOrGradient(filloptions.fillColor, bottom, top, seriesColor);
            
            var c = $.color.parse(seriesColor);
            c.a = typeof fill == "number" ? fill : 0.4;
            c.normalize();
            return c.toString();
        }
        
        function insertLegend() {
            placeholder.find(".legend").remove();

            if (!options.legend.show)
                return;
            
            var fragments = [], rowStarted = false,
                lf = options.legend.labelFormatter, s, label;
            for (var i = 0; i < series.length; ++i) {
                s = series[i];
                label = s.label;
                if (!label)
                    continue;
                
                if (i % options.legend.noColumns == 0) {
                    if (rowStarted)
                        fragments.push('</tr>');
                    fragments.push('<tr>');
                    rowStarted = true;
                }

                if (lf)
                    label = lf(label, s);
                
                fragments.push(
                    '<td class="legendColorBox"><div style="border:1px solid ' + options.legend.labelBoxBorderColor + ';padding:1px"><div style="width:4px;height:0;border:5px solid ' + s.color + ';overflow:hidden"></div></div></td>' +
                    '<td class="legendLabel">' + label + '</td>');
            }
            if (rowStarted)
                fragments.push('</tr>');
            
            if (fragments.length == 0)
                return;

            var table = '<table style="color:' + options.grid.color + '">' + fragments.join("") + '</table>';
            if (options.legend.container != null)
                $(options.legend.container).html(table);
            else {
                var pos = "",
                    p = options.legend.position,
                    m = options.legend.margin;
                if (m[0] == null)
                    m = [m, m];
                if (p.charAt(0) == "n")
                    pos += 'top:' + (m[1] + plotOffset.top) + 'px;';
                else if (p.charAt(0) == "s")
                    pos += 'bottom:' + (m[1] + plotOffset.bottom) + 'px;';
                if (p.charAt(1) == "e")
                    pos += 'right:' + (m[0] + plotOffset.right) + 'px;';
                else if (p.charAt(1) == "w")
                    pos += 'left:' + (m[0] + plotOffset.left) + 'px;';
                var legend = $('<div class="legend">' + table.replace('style="', 'style="position:absolute;' + pos +';') + '</div>').appendTo(placeholder);
                if (options.legend.backgroundOpacity != 0.0) {
                    // put in the transparent background
                    // separately to avoid blended labels and
                    // label boxes
                    var c = options.legend.backgroundColor;
                    if (c == null) {
                        c = options.grid.backgroundColor;
                        if (c && typeof c == "string")
                            c = $.color.parse(c);
                        else
                            c = $.color.extract(legend, 'background-color');
                        c.a = 1;
                        c = c.toString();
                    }
                    var div = legend.children();
                    $('<div style="position:absolute;width:' + div.width() + 'px;height:' + div.height() + 'px;' + pos +'background-color:' + c + ';"> </div>').prependTo(legend).css('opacity', options.legend.backgroundOpacity);
                }
            }
        }


        // interactive features
        
        var highlights = [],
            redrawTimeout = null;
        
        // returns the data item the mouse is over, or null if none is found
        function findNearbyItems(mouseX, mouseY, seriesFilter) {
            var maxDistance = options.grid.mouseActiveRadius,
                smallestDistance = maxDistance * maxDistance + 1,
                item = null, foundPoint = false, i, j,
                nearbyItems = [];

            for (i = series.length - 1; i >= 0; --i) {
                if (!seriesFilter(series[i]))
                    continue;
                
                var s = series[i],
                    axisx = s.xaxis,
                    axisy = s.yaxis,
                    points = s.datapoints.points,
                    ps = s.datapoints.pointsize,
                    mx = axisx.c2p(mouseX), // precompute some stuff to make the loop faster
                    my = axisy.c2p(mouseY),
                    maxx = maxDistance / axisx.scale,
                    maxy = maxDistance / axisy.scale;

                if (s.lines.show || s.points.show) {
                    for (j = 0; j < points.length; j += ps) {
                        var x = points[j], y = points[j + 1];
                        if (x == null)
                            continue;
                        
                        // For points and lines, the cursor must be within a
                        // certain distance to the data point
                        if (x - mx > maxx || x - mx < -maxx ||
                            y - my > maxy || y - my < -maxy)
                            continue;

                        var nearItem = [i, j / ps];
                        nearbyItems.push(nearItem);                            

                        // We have to calculate distances in pixels, not in
                        // data units, because the scales of the axes may be different
                        var dx = Math.abs(axisx.p2c(x) - mouseX),
                            dy = Math.abs(axisy.p2c(y) - mouseY),
                            dist = dx * dx + dy * dy; // we save the sqrt

                        // use <= to ensure last point takes precedence
                        // (last generally means on top of)
                        if (dist < smallestDistance) {
                            smallestDistance = dist;
                            item = nearItem;
                        }
                    }
                }
                    
                if (s.bars.show && !item) { // no other point can be nearby
                    var barLeft = s.bars.barLeft,
                        barRight = barLeft + s.bars.barWidth;
                    
                    for (j = 0; j < points.length; j += ps) {
                        var x = points[j], y = points[j + 1], b = points[j + 2];
                        if (x == null)
                            continue;
  
                        // for a bar graph, the cursor must be inside the bar
                        if (series[i].bars.horizontal ? 
                            (mx <= Math.max(b, x) && mx >= Math.min(b, x) && 
                             my >= y + barLeft && my <= y + barRight) :
                            (mx >= x + barLeft && mx <= x + barRight &&
                             my >= Math.min(b, y) && my <= Math.max(b, y)))
                                item = [i, j / ps];
                    }
                }
            }

           if (item) {
                // In some cases (bars, e.g.), we may end up with no nearbyItems; populate it with
                // item in those cases.
                if (nearbyItems.length == 0) {
                    nearbyItems.push(item);
                }
                
                var rv = {item: null, all: []};
                var temp_item = null;
                for (var k = 0; k < nearbyItems.length; k += 1) {
                    temp_item = nearbyItems[k];
                    i = temp_item[0];
                    j = temp_item[1];
                    ps = series[i].datapoints.pointsize;

                    return_item = { datapoint: series[i].datapoints.points.slice(j * ps, (j + 1) * ps),
                             dataIndex: j,
                           series: series[i],
                         seriesIndex: i };

                    if (temp_item == item) {
                        rv.item = return_item;
                    }
                    else {
                        rv.all.push(return_item);
                    }
                }
                return rv;
            }

            
            return null;
        }

        function onMouseMove(e) {
            if (options.grid.hoverable)
                triggerClickHoverEvent("plothover", e,
                                       function (s) { return s["hoverable"] != false; });
        }

        function onMouseLeave(e) {
            if (options.grid.hoverable)
                triggerClickHoverEvent("plothover", e,
                                       function (s) { return false; });
        }

        function onClick(e) {
            triggerClickHoverEvent("plotclick", e,
                                   function (s) { return s["clickable"] != false; });
        }

        // trigger click or hover event (they send the same parameters
        // so we share their code)
        function triggerClickHoverEvent(eventname, event, seriesFilter) {
            var offset = eventHolder.offset(),
                canvasX = event.pageX - offset.left - plotOffset.left,
                canvasY = event.pageY - offset.top - plotOffset.top,
            pos = canvasToAxisCoords({ left: canvasX, top: canvasY });

            pos.pageX = event.pageX;
            pos.pageY = event.pageY;

            var nearby_items = findNearbyItems(canvasX, canvasY, seriesFilter);
            var item = nearby_items && nearby_items.item;
            nearby_items = nearby_items && nearby_items.all

            if (item) {
                // fill in mouse pos for any listeners out there
                item.pageX = parseInt(item.series.xaxis.p2c(item.datapoint[0]) + offset.left + plotOffset.left);
                item.pageY = parseInt(item.series.yaxis.p2c(item.datapoint[1]) + offset.top + plotOffset.top);
            }

            if (options.grid.autoHighlight) {
                // clear auto-highlights
                for (var i = 0; i < highlights.length; ++i) {
                    var h = highlights[i];
                    if (h.auto == eventname &&
                        !(item && h.series == item.series &&
                          h.point[0] == item.datapoint[0] &&
                          h.point[1] == item.datapoint[1]))
                        unhighlight(h.series, h.point);
                }
                
                if (item)
                    highlight(item.series, item.datapoint, eventname);
            }
            
            placeholder.trigger(eventname, [ pos, item, nearby_items ]);
        }

        function triggerRedrawOverlay() {
            if (!redrawTimeout)
                redrawTimeout = setTimeout(drawOverlay, 30);
        }

        function drawOverlay() {
            redrawTimeout = null;

            // draw highlights
            octx.save();
            octx.clearRect(0, 0, canvasWidth, canvasHeight);
            octx.translate(plotOffset.left, plotOffset.top);
            
            var i, hi;
            for (i = 0; i < highlights.length; ++i) {
                hi = highlights[i];

                if (hi.series.bars.show)
                    drawBarHighlight(hi.series, hi.point);
                else
                    drawPointHighlight(hi.series, hi.point);
            }
            octx.restore();
            
            executeHooks(hooks.drawOverlay, [octx]);
        }
        
        function highlight(s, point, auto) {
            if (typeof s == "number")
                s = series[s];

            if (typeof point == "number") {
                var ps = s.datapoints.pointsize;
                point = s.datapoints.points.slice(ps * point, ps * (point + 1));
            }

            var i = indexOfHighlight(s, point);
            if (i == -1) {
                highlights.push({ series: s, point: point, auto: auto });

                triggerRedrawOverlay();
            }
            else if (!auto)
                highlights[i].auto = false;
        }
            
        function unhighlight(s, point) {
            if (s == null && point == null) {
                highlights = [];
                triggerRedrawOverlay();
            }
            
            if (typeof s == "number")
                s = series[s];

            if (typeof point == "number")
                point = s.data[point];

            var i = indexOfHighlight(s, point);
            if (i != -1) {
                highlights.splice(i, 1);

                triggerRedrawOverlay();
            }
        }
        
        function indexOfHighlight(s, p) {
            for (var i = 0; i < highlights.length; ++i) {
                var h = highlights[i];
                if (h.series == s && h.point[0] == p[0]
                    && h.point[1] == p[1])
                    return i;
            }
            return -1;
        }
        
        function drawPointHighlight(series, point) {
            var x = point[0], y = point[1],
                axisx = series.xaxis, axisy = series.yaxis;
            
            // Don't draw highlight if point is not within axes bounds
            if (x < axisx.min || x > axisx.max || y < axisy.min || y > axisy.max)
                return;
            
            var pointRadius = series.points.radius + series.points.lineWidth / 2;
            octx.lineWidth = pointRadius;
            octx.strokeStyle = $.color.parse(series.color).scale('a', 0.5).toString();
            var radius = 1.5 * pointRadius,
                x = axisx.p2c(x),
                y = axisy.p2c(y);
            
            octx.beginPath();
            if (series.points.symbol == "circle")
                octx.arc(x, y, radius, 0, 2 * Math.PI, false);
            else
                series.points.symbol(octx, x, y, radius, false);
            octx.closePath();
            octx.stroke();
        }

        function drawBarHighlight(series, point) {
            octx.lineWidth = series.bars.lineWidth;
            octx.strokeStyle = $.color.parse(series.color).scale('a', 0.5).toString();
            var fillStyle = $.color.parse(series.color).scale('a', 0.5).toString();
            drawBar(point[0], point[1], point[2] || 0, series.bars.barLeft, series.bars.barLeft + series.bars.barWidth,
                    0, function () { return fillStyle; }, series.xaxis, series.yaxis, octx, series.bars.horizontal, series.bars.lineWidth);
        }

        function getColorOrGradient(spec, bottom, top, defaultColor) {
            if (typeof spec == "string")
                return spec;
            else {
                // assume this is a gradient spec; IE currently only
                // supports a simple vertical gradient properly, so that's
                // what we support too
                var gradient = ctx.createLinearGradient(0, top, 0, bottom);
                
                for (var i = 0, l = spec.colors.length; i < l; ++i) {
                    var c = spec.colors[i];
                    if (typeof c != "string") {
                        var co = $.color.parse(defaultColor);
                        if (c.brightness != null)
                            co = co.scale('rgb', c.brightness)
                        if (c.opacity != null)
                            co.a *= c.opacity;
                        c = co.toString();
                    }
                    gradient.addColorStop(i / (l - 1), c);
                }
                
                return gradient;
            }
        }
    }

    $.plot = function(placeholder, data, options) {
        //var t0 = new Date();
        var plot = new Plot($(placeholder), data, options, $.plot.plugins);
        //(window.console ? console.log : alert)("time used (msecs): " + ((new Date()).getTime() - t0.getTime()));
        return plot;
    };

    $.plot.plugins = [];

    // returns a string with the date d formatted according to fmt
    $.plot.formatDate = function(d, fmt, monthNames) {
        var leftPad = function(n) {
            n = "" + n;
            return n.length == 1 ? "0" + n : n;
        };
        
        var r = [];
        var escape = false, padNext = false;
        var hours = d.getUTCHours();
        var isAM = hours < 12;
        if (monthNames == null)
            monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

        if (fmt.search(/%p|%P/) != -1) {
            if (hours > 12) {
                hours = hours - 12;
            } else if (hours == 0) {
                hours = 12;
            }
        }
        for (var i = 0; i < fmt.length; ++i) {
            var c = fmt.charAt(i);
            
            if (escape) {
                switch (c) {
                case 'h': c = "" + hours; break;
                case 'H': c = leftPad(hours); break;
                case 'M': c = leftPad(d.getUTCMinutes()); break;
                case 'S': c = leftPad(d.getUTCSeconds()); break;
                case 'd': c = "" + d.getUTCDate(); break;
                case 'm': c = "" + (d.getUTCMonth() + 1); break;
                case 'y': c = "" + d.getUTCFullYear(); break;
                case 'b': c = "" + monthNames[d.getUTCMonth()]; break;
                case 'p': c = (isAM) ? ("" + "am") : ("" + "pm"); break;
                case 'P': c = (isAM) ? ("" + "AM") : ("" + "PM"); break;
                case '0': c = ""; padNext = true; break;
                }
                if (c && padNext) {
                    c = leftPad(c);
                    padNext = false;
                }
                r.push(c);
                if (!padNext)
                    escape = false;
            }
            else {
                if (c == "%")
                    escape = true;
                else
                    r.push(c);
            }
        }
        return r.join("");
    };
    
    /**
     * Floor given number, n, to nearby lower multiple of base.
     *
     * @param n <Number> to round
     * @param base <Number> 
     * @return <Number> closest multiple of base to n by floor
     */
    $.plot.floorInBase = function(n, base) {
        return base * Math.floor(n / base);
    }
    
})(jQuery);
/** @file
 *
 *  A jQuery-UI / flot-based graphical timeline capable of presenting timeline
 *  data generated by connexions.
 *
 *  The target DOM element MAY contain a 'div.timeline-plot',
 *  'div.timeline-legend', and/or 'div.timeline-annotation'.  If it does not,
 *  these DOM elements will be added.
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false, plusplus:false */
/*global jQuery:false, window:false */
(function($) {

function leftPad(val, fillChar, padLen)
{
    fillChar = fillChar || '0';
    padLen   = padLen   || 2;

    val = "" + val;
    return val.length < padLen ? fillChar + val : val;
}

var numericRe = /^[0-9]+(\.[0-9]*)?$/;
              //   year      month           day
var datePat   = '^([0-9]{4})(0[0-9]|1[0-2])?([0-2][0-9]|3[01])?'
              //   hour               minute       second
              + '([0-1][0-9]|2[0-3])?([0-5][0-9])?([0-5][0-9])?$';
var dateRe    = new RegExp(datePat);

$.widget('connexions.timeline', {
    version:    '0.0.1',
    options:    {
        // Defaults
        xDataHint:      null,   /* hour | day-of-week |
                                 *  day | week | month | year
                                 *  fmt:%date format% - also implies that the
                                 *                      x-values are
                                 *                      date/times.
                                 */
        xLegendHint:    null,   /* Primarily for asynchronously loaded data,
                                 * a hint about how to format legend values
                                 * (same values are xDataHint).
                                 */

        css:            null,   /* Additional CSS class(es) to apply to the
                                 * primary DOM element
                                 */
        annotation:     null,   // Any annotation to include for this timtline
        rawData:        null,   // Raw, connexions timeline data to present
        data:           [],     // Initial, empty data

        width:          null,   // The width of the timeline plot area
        height:         null,   // The height of the timeline plot area
        hwRatio:        9/16,   /* Ratio of height to width
                                 * (used if 'height' is not specified)
                                 */


        hideLegend:     false,  // Hide the legend?
        valueInLegend:  true,   /* Show the y hover value(s) in the series
                                 * legend (hideLegend should be true);
                                 */
        valueInTips:    false,  // Show the y hover value(s) in series graph
        replaceLegend:  false,  /* Should the data label completely replace
                                 * any existing legend text when the value
                                 * is being presented? [ false ];
                                 */
        createControls: false,  /* Should controls be created if not provided
                                 * in the markup? [ false ];
                                 */

        /* General Json-RPC information:
         *  {version:   Json-RPC version,
         *   target:    URL of the Json-RPC endpoint,
         *   transport: 'POST' | 'GET'
         *  }
         *
         * If not provided, 'version', 'target', and 'transport' are
         * initialized from:
         *      $.registry('api').jsonRpc
         *
         * which is initialized from
         *      application/configs/application.ini:api
         * via
         *      application/layout/header.phtml
         */
        jsonRpc:    null,
        rpcMethod:  'bookmark.getTimeline',
        rpcParams:  null,   /* Any RPC parameter required by 'rpcMethod:
                             *  {
                             *      'tags':     Context-restricting tags,
                             *      'group':    Timeline grouping indicator,
                             *  }
                             */

        /* Place a general limit on the maximum amount of data returned.
         * Too much and we'll kill the browser.
         */
        maxCount:   1000,

        // DataType value->label tables
        hours:      [ '12a',  '1a',  '2a',  '3a',  '4a',  '5a',
                       '6a',  '7a',  '8a',  '9a', '10a', '11a',
                      '12p',  '1p',  '2p',  '3p',  '4p',  '5p',
                       '6p',  '7p',  '8p',  '9p', '10p', '11p' ],
        months:     [ 'January',   'Febrary', 'March',    'April',
                      'May',       'June',    'July',     'August',
                      'September', 'October', 'November', 'December' ],
        days:       [ 'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa' ],

        /* Timeline grouping indicator map to information about how to
         * format/present the x-axis tick labels as well as the legend.
         */
        grouping:   {
            // Straight Timelines
            'YM':   {
                group:          'Simple Timelines',
                name:           'Year, Month',
                xDataHint:      'fmt:%Y %b',
                replaceLegend:  true
            },
            'Y':    {
                group:          'Simple Timelines',
                name:           'Year',
                xDataHint:      'fmt:%Y',
                replaceLegend:  true
            },
            'M':    {
                group:          'Simple Timelines',
                name:           'Month',
                xDataHint:      'mon',
                replaceLegend:  true
            },
            'w':    {
                group:          'Simple Timelines',
                name:           'Week',
                xDataHint:      'week',
                replaceLegend:  true
            },
            'D':    {
                group:          'Simple Timelines',
                name:           'Day',
                xDataHint:      'day',
                replaceLegend:  true
            },
            'd':    {
                group:          'Simple Timelines',
                name:           'Day-of-week',
                xDataHint:      'day-of-week',
                replaceLegend:  true
            },
            'H':    {
                group:          'Simple Timelines',
                name:           'Hour',
                xDataHint:      'hour',
                replaceLegend:  true
            },

            // Series Timelines (by Year)
            'Y:M':  {
                group:          'Series (by Year)',
                name:           'Month',
                xDataHint:      'mon',
                xLegendHint:    'year',
                replaceLegend:  false
            },
            'Y:D':  {
                group:          'Series (by Year)',
                name:           'Day (of month)',
                xDataHint:      'day',
                xLegendHint:    'year',
                replaceLegend:  false
            },
            'Y:d':  {
                group:          'Series (by Year)',
                name:           'Day (of week)',
                xDataHint:      'day-of-week',
                xLegendHint:    'year',
                replaceLegend:  false
            },
            'Y:H':  {
                group:          'Hour',
                name:           'Month',
                xDataHint:      'hour',
                xLegendHint:    'year',
                replaceLegend:  false
            },

            // Series Timelines (by Month)
            'M:D':  {
                group:          'Series (by Month)',
                name:           'Day (of month)',
                xDataHint:      'day',
                xLegendHint:    'mon',
                replaceLegend:  false
            },
            'M:d':  {
                group:          'Series (by Month)',
                name:           'Day (of week)',
                xDataHint:      'day-of-week',
                xLegendHint:    'mon',
                replaceLegend:  false
            },
            'M:H':  {
                group:          'Series (by Month)',
                name:           'Hour',
                xDataHint:      'hour',
                xLegendHint:    'mon',
                replaceLegend:  false
            },

            // Series Timelines (by Week)
            'w:d':  {
                group:          'Series (by Week)',
                name:           'Day (of week)',
                xDataHint:      'day-of-week',
                replaceLegend:  false
            },
            'w:H':  {
                group:          'Series (by Week)',
                name:           'Hour',
                xDataHint:      'hour',
                replaceLegend:  false
            },

            // Series Timelines (by Day-of-Month)
            'D:H':  {
                group:          'Series (by Day-of-Month)',
                name:           'Hour',
                xDataHint:      'hour',
                replaceLegend:  false
            },

            // Series Timelines (by Day-of-Week)
            'd:H':  {
                group:          'Series (by Day-of-Week)',
                name:           'Hour',
                xDataHint:      'hour',
                xLegendHint:    'day-of-week',
                replaceLegend:  false
            }
        }
    },

    /************************
     * Private methods
     *
     */
    _init: function() {
        var self        = this;
        var opts        = self.options;

        /********************************
         * Initialize jsonRpc
         *
         */
        if ( $.isFunction($.registry) )
        {
            if (opts.jsonRpc === null)
            {
                var api = $.registry('api');
                if (api && api.jsonRpc)
                {
                    opts.jsonRpc = $.extend({}, api.jsonRpc, opts.jsonRpc);
                }
            }
        }

        /********************************
         * Remember initial position
         * settings and add appropriate
         * CSS classes.
         *
         */
        self.element.data('orig-position', self.element.css('position'));
        self.element.css('position', 'relative')
                    .addClass('ui-timeline');

        if ( opts.hideLegend !== true )
        {
            self.element.addClass('ui-timeline-labeled');
        }

        if ($.type(opts.css) === 'string')
        {
            self.element.addClass( opts.css );
        }


        /********************************
         * Locate/create our primary
         * pieces.
         *
         */
        self.$controls = self.element.find('.timeline-controls');
        if ((self.$controls.length < 1) && (opts.createControls === true))
        {
            // Create controls based upon 'opts.grouping'
            self.$controls = self._createControls();
        }
        self.$grouping = self.$controls
                                .find(':input[name="timeline.grouping"]')
                                .input();

        self.$timeline = self.element.find('.timeline-plot');
        if (self.$timeline.length < 1)
        {
            // Append a plot container
            self.$timeline = $('<div class="timeline-plot"></div>');
            self.$timeline.data('remove-on-destroy', true);
            self.element.append(self.$timeline);
        }

        self.$legend = self.element.find('.timeline-legend');
        if ( (self.$legend.length < 1) && (opts.hideLegend !== true) )
        {
            // Append a legend container
            self.$legend = $('<div class="timeline-legend"></div>');

            self.$legend.data('remove-on-destroy', true);
            self.element.append(self.$legend);
        }
        else if ( opts.hideLegend === true )
        {
            self.$legend.hide();
        }


        if (opts.annotation)
        {
            self.$annotation = self.element.find('.timeline-annotation');
            if (self.$annotation.length < 1)
            {
                // Append a annotation container
                self.$annotation = $('<div class="timeline-annotation"></div>');

                self.$annotation.data('remove-on-destroy', true);
                self.element.append(self.$annotation);
            }

            self.$annotation.html(opts.annotation);
        }

        self.hlTimer = null;

        // Ensure that our plot area has a non-zero height OR width
        var width   = self.$timeline.width();
        var height  = self.$timeline.height();

        if (opts.width !== null)
        {
            self.$timeline.css('width', opts.width);
            width = self.$timeline.width();

            if (height <= 10)
            {
                height = width * opts.hwRatio;
                self.$timeline.css('height', height);
            }
        }
        if (opts.height !== null)
        {
            // Measure the timeline height given a parent height.
            self.$timeline.css('height', '100%');
            self.element.css('height', opts.height);
            height = self.$timeline.height();

            // Reset the parent and the timeline heights based upon
            // measurements
            self.element.css('height', 'auto');
            self.$timeline.height( height );

            if (width <= 10)
            {
                width = height / opts.hwRatio;
                self.$timeline.width( width );
            }
        }

        if (width <= 10)
        {
            // Force to the width of the container
            self.$timeline.width( self.element.width() );
            width = self.$timeline.width();
        }

        if (height <= 10)
        {
            // Force the height to a ratio of the width
            height = width * opts.hwRatio;
            self.$timeline.height( height );
        }

        // Interaction events
        self._bindEvents();


        window.setTimeout(function() { self._createPlot(); }, 50);
    },

    _bindEvents: function() {
        var self        = this;
        var opts        = self.options;

        // Handle 'plothover' events
        self.$timeline.bind('plothover', function(e, pos, item) {
            if (! self.hlTimer)
            {
                self.hlTimer = window.setTimeout(function() {
                                                    self._updateHighlights(pos);
                                                 }, 50);
            }
        });

        self.$controls.delegate('input,select', 'change', function(e) {
            self._reload( self.$grouping.val() );
        });
    },

    /** @brief  Create timeline controls based upon this.options.grouping.
     *
     *  @return The jQuery DOM element representing the new controls.
     */
    _createControls: function() {
        var self        = this;
        var opts        = self.options;
        var $controls   = $('<div />').addClass('timeline-controls');
        var $select     = $('<select name="timeline.grouping" />')
                                .appendTo($controls);
        var $group      = null;
        var lastGroup   = null;
        var curGroup    = (opts.rpcParams !== null
                                ? opts.rpcParams.grouping
                                : null);

        $.each(opts.grouping, function(key, info) {
            if (lastGroup !== info.group)
            {
                $group = $('<optgroup label="'+ info.group +'" />')
                            .appendTo($select);
                lastGroup = info.group;
            }
            if ((curGroup === null) || (curGroup === key))
            {
                curGroup = key;
            }

            $group.append(  '<option value="'+ key +'"'
                          +     (curGroup === key ? ' selected' : '') +'>'
                          +  info.name
                          + '</option>');
        });

        $controls.appendTo(self.element);

        return $controls;
    },

    /** @brief  Asynchronously (re)load the data for the presented timeline.
     *  @param  grouping    The new grouping value;
     *
     *  Use this.options:
     *      jsonRpc     the primary Json-RPC information;
     *      rpcMethod   the Json-RPC method;
     *      rpcParams   additional Json-RPC method parameters;
     */
    _reload: function(grouping) {
        var self    = this;
        var opts    = self.options;
        var params  = opts.rpcParams;

        params.grouping = grouping;
        if (opts.maxCount > 0)
        {
            params.count = opts.maxCount;
        }

        // What's the xDataHint based upon 'grouping'?
        var info    = opts.grouping[ grouping ];
        if (info !== undefined)
        {
            opts.xDataHint     = info.xDataHint;
            opts.replaceLegend = (info.replaceLegend === true
                                    ? true
                                    : false);
            if (info.xLegendHint !== undefined)
            {
                opts.xLegendHint = info.xLegendHint;
            }

            if (info.rpcParams !== undefined)
            {
                params = $.extend(params, info.rpcParams);
            }
        }

        self.element.mask();
        $.jsonRpc(opts.jsonRpc, opts.rpcMethod, params, {
            success: function(data) {
                if ( (! data) || (data.error !== null) )
                {
                    $.notify({
                        title:  'Timeline Update failed',
                        text:   '<p class="error">'
                              +  (data ? data.error.message : '')
                              + '</p>'
                    });
                    return;
                }

                opts.data = self._convertData(data.result);
                self._draw();
            },
            error: function(req, textStatus, err) {
                $.notify({
                    title:  'Timeline Update failed',
                    text:   '<p class="error">'
                          +  textStatus
                          + '</p>'
                });
            },
            complete: function(req, textStatus) {
                self.element.unmask();
            }
        });

    },

    /** @brief  Use this.options to generate/update the current timeline.
     *  
     *  Use this.options:
     *      rpcParams.grouping  to determine how to format the ticks and legend;
     *      data                the (converted) plot data;
     */
    _draw: function() {
        var self    = this;
        var opts    = self.options;

        self.$plot  = $.plot(self.$timeline, opts.data, self.flotOpts);

        // Cache the size of the plot box/area
        self.plotBox        = self.$plot.getPlotOffset();
        self.plotBox.width  = self.$plot.width();
        self.plotBox.height = self.$plot.height();
        self.plotBox.right  = self.plotBox.left + self.plotBox.width;
        self.plotBox.bottom = self.plotBox.top  + self.plotBox.height;

        /*
        // Position the legend to the right of the plot box/area
        self.$legend.css({
            position:   'absolute',
            top:        self.plotBox.top   - 5,
            left:       self.plotBox.right + 10
        });
        // */
        self.$legends = self.$legend.find('.legendLabel');

        // Wrap the current content of each legend item in 'timeline-text'
        self.$legends.each(function() {
            var $legend = $(this);

            if ($legend.find('.timeline-text').length < 1)
            {
                $legend.html(  '<span class="timeline-text">'
                             +   $legend.html()
                             + '</span>');
            }
        });
    },

    _createPlot: function() {
        var self            = this;
        var opts            = self.options;
        var flotDefaults    = {
            grid:       { hoverable:true, autoHighlight:false },
            points:     { show:true },
            lines:      { show:true },
            legend:     { container: self.$legend },
            xaxis:      {
                tickFormatter: function(val,data) {
                    return self._xTickFormatter(val, data);
                }
            }
        };

        // Default flot options
        self.flotOpts   = $.extend(true, {}, flotDefaults, opts.flot);

        if (opts.rpcParams !== null)
        {
            var info    = opts.grouping[ opts.rpcParams.grouping ];

            if (info !== undefined)
            {
                opts.xDataHint = info.xDataHint;
                opts.xDataHint     = info.xDataHint;
                opts.replaceLegend = (info.replaceLegend === true
                                        ? true
                                        : false);
                if (info.xLegendHint !== undefined)
                {
                    opts.xLegendHint = info.xLegendHint;
                }
            }
        }

        // Convert any raw data
        if ( $.isArray(opts.rawData) || $.isPlainObject(opts.rawData) )
        {
            opts.data   = self._convertData(opts.rawData);
        }

        self._draw();
    },

    /** @brief  Convert incoming, raw, connexions timeline data into a form
     *          usable by flot.
     *  @param  rawData The raw, connexions timeline data to convert.
     *
     *  @return An array of data series suitable for flot.
     */
    _convertData: function(rawData) {
        var self    = this;
        var opts    = self.options;
        var data    = [];

        opts.xDataType = undefined;
        opts.xDateFmt  = undefined;

        $.each(rawData, function(key, vals) {
            var info    = { label: self._hintFormatter(key, opts.xLegendHint),
                            data:  [] };

            $.each(vals, function(x, y) {
                y = (numericRe.test(y) ? parseInt(y, 10) : y);

                var match   = dateRe.exec(x);
                if (match !== null)
                {
                    match[2] = (match[2] === undefined ? 0 : match[2] - 1);
                    match[3] = (match[3] === undefined ? 1 : match[3]);
                    match[4] = (match[4] === undefined ? 0 : match[4]);
                    match[5] = (match[5] === undefined ? 0 : match[5]);
                    match[6] = (match[6] === undefined ? 0 : match[6]);

                    var newX    = new Date(match[1], match[2], match[3],
                                           match[4], match[5], match[6]);

                    info.data.push([newX, y]);

                    opts.xDataType     = 'date';
                    if (($.type(opts.xDataHint) === 'string') &&
                        (opts.xDataHint.substr(0,4) === 'fmt:'))
                    {
                        opts.xDateFmt  = opts.xDataHint.substr(4);
                        opts.xDataHint = 'fmt';
                    }
                }
                else
                {
                    x = (numericRe.test( x )
                            ? parseInt(x, 10)
                            : x);
                    info.data[x] = [x, y];
                }
            });

            data.push(info);
        });

        return data;
    },

    /** @brief  Given a Date instance and format string, generate a string
     *          representation of the Date.
     *  @param  date    The Date instance;
     *  @param  fmt     The format string;
     *                      %Y  - Year              ( 2010 )
     *                      %m  - Month             ( 01 - 12 )
     *                      %d  - Day               ( 01 - 31 )
     *                      %w  - Day-of-week       ( 0  -  6 )
     *                      %H  - Hour              ( 00 - 23 )
     *                      %M  - Minute            ( 00 - 59 )
     *                      %S  - Second            ( 00 - 59 )
     *                      %z  - Timezone Offset
     *
     *                      %B  - Month Name        ( January - December )
     *                      %b  - Month Name        ( Jan     - Dec      )
     *                      
     *                      %A  - Day-of-week Name  ( Sunday  - Saturday )
     *                      %a  - Day-of-week Name  ( Su      - Sa       )
     *
     *                      %h  - Hour Name         ( 12a     - 11p      )
     *
     *  @return A string representation of the Date.
     */
    _formatDate: function(date, fmt) {
        var self    = this;
        var opts    = self.options;
        var isFmt   = false;
        var res     = [];
        var str;
        for (var idex = 0; idex < fmt.length; idex++)
        {
            var fmtChar = fmt.charAt(idex);
            if (isFmt)
            {
                isFmt = false;
                switch (fmtChar)
                {
                case 'Y':   // Year
                    str = date.getFullYear();
                    break;
    
                case 'm':   // Month (01-12)
                    str = leftPad(date.getMonth() + 1);
                    break;

                case 'd':   // Day   (01-31)
                    str = leftPad(date.getDate());
                    break;

                case 'w':   // Day-of-week (0 - 6)
                    str = date.getDay();
                    break;

                case 'H':   // Hours (00-23)
                    str = leftPad(date.getHours());
                    break;

                case 'M':   // Minutes (00-59)
                    str = leftPad(date.getMinutes());
                    break;

                case 'S':   // Seconds (00-59)
                    str = leftPad(date.getSeconds());
                    break;

                case 'z':   // Timezone offset
                    str = date.getTimezoneOffset();
                    break;

                // Number to String mappings
                case 'B':   // Month (January - December)
                    str = opts.months[date.getMonth()];
                    break;
    
                case 'b':   // Month (Jan - Dec)
                    str = opts.months[date.getMonth()].substr(0,3);
                    break;
    
                case 'A':   // Day-of-week   (Sunday - Saturday)
                    str = opts.days[date.getDay()];
                    break;

                case 'a':   // Day-of-week   (Su - Sa)
                    str = opts.days[date.getDay()].substr(0,2);
                    break;

                case 'h':   // Hours (12a-11p)
                    str = leftPad(opts.hours[date.getHours()], '&nbsp;', 3);
                    break;

                default:
                    str = fmtChar;
                    break;
                }

                res.push(str);
            }
            else if (fmtChar === '%')
            {
                isFmt = true;
            }
            else
            {
                res.push(fmtChar);
            }
        }
    
        return res.join('');
    },

    /** @brief  Given a value, hint, and possibly dateFmt, format the value.
     *  @param  val     The value to format;
     *  @param  hint    The formatting hint (hour | day-of-week |
     *                                       day | week | month | year
     *                                       fmt -- requires 'dateFmt' and
     *                                              also implies that the value
     *                                              is (now) a Date instance);
     *  @param  dateFmt The date format string (iff 'hint' === 'fmt');
     *
     *  @return The formatted string.
     */
    _hintFormatter: function(val, hint, dateFmt) {
        if (! hint)
        {
            return val;
        }

        var self        = this;
        var opts        = self.options;

        switch (hint)
        {
        case 'hour':
            if (val instanceof Date)
            {
                val = val.getHours();
            }
            val = leftPad(opts.hours[ val ], '&nbsp;', 3);
            break;

        case 'day-of-week':
            if (val instanceof Date)
            {
                val = val.getDay();
            }
            val = opts.days[ val ];
            break;

        case 'month':
        case 'mon':
            if (val instanceof Date)
            {
                val = val.getMonth() + 1;
            }
            val = opts.months[ (val > 0 ? val - 1 : val) ];

            if ((hint === 'mon') && (val !== undefined))
            {
                val = val.substr(0,3);
            }
            break;

        case 'day':
            if (val instanceof Date)
            {
                val = val.getDate();
            }
            val = leftPad(val);
            break;

        case 'year':
            if (val instanceof Date)
            {
                val = val.getFullYear();
            }
            break;

        case 'fmt':
            if (val instanceof Date)
            {
                val = self._formatDate(val, dateFmt);
            }
            break;

        // No special formatting
        case 'week':
        }

        return (val === undefined ? '' : val);
    },

    _xTickFormatter: function(val, data) {
        var self        = this;
        var opts        = self.options;

        if (opts.xDataType === 'date')
        {
            val = new Date( val );
        }
        
        return self._hintFormatter(val, opts.xDataHint, opts.xDateFmt);
    },

    /** @brief  Present a "tip" for the given series and point
     *  @param  idex    The series index;
     *  @param  series  The series
     *  @param  p       The point
     */
    _showTip:   function(idex, series, p) {
        if (p === null)
        {
            return;
        }

        var self        = this;
        var opts        = self.options;
        var hlRadius    = (series.points.radius + series.points.lineWidth);
        var itemPos     = {
            top:    series.yaxis.p2c(p[1]),
            left:   series.xaxis.p2c(p[0])
        };
        //var x           = series.xaxis.tickFormatter(p[0], series.xaxis);
        var y           = series.yaxis.tickFormatter(p[1], series.yaxis);
        var str         = y;    //x +': '+ y;

        var tip         = '<div class="timeline-tooltip">'
                        +  str
                        + '</div>';
        var yaxis       = series.yaxis;
        var $tip        = $(tip).css({
                            position:           'absolute',
                            top:                itemPos.top  - hlRadius - 5,
                            left:               itemPos.left + (hlRadius*2)
                                                             + 5,
                            'text-align':       'left',
                            /*
                            'font-size':        yaxis.font.size +'px',
                            'font-family':      yaxis.font.family,
                            'font-weight':      yaxis.font.weight,
                            */
                            width:              yaxis.labelWidth +'px',
                            height:             yaxis.labelHeight +'px',
                            color:              series.yaxis.options.color
                        });
        self.$timeline.append($tip);

        /* Adjust the position of the tip to be within the bounds of the
         * plot
         */
        var tipBox      = $tip.position();
        tipBox.width    = $tip.outerWidth();
        tipBox.height   = $tip.outerHeight();
        tipBox.left    += tipBox.width;
        tipBox.right    = tipBox.left + tipBox.width;
        tipBox.bottom   = tipBox.top  + tipBox.height;

        if (tipBox.top    < self.plotBox.top)
        {
            tipBox.top = itemPos.top + hlRadius + hlRadius + 5;
        }
        else if (tipBox.bottom > self.plotBox.bottom)
        {
            tipBox.top = self.plotBox.bottom - tipBox.height - 5;
        }

        if (tipBox.left   < self.plotBox.left)
        {
            tipBox.left = self.plotBox.left + 5;
        }
        else if (tipBox.right > self.plotBox.right)
        {
            tipBox.left = self.plotBox.right - tipBox.width - 10;
        }

        $tip.css({
            top:    tipBox.top,
            left:   tipBox.left
        });
    },

    /** @brief  Update the legend to include the current y value for the
     *          specified series.
     *  @param  idex    The series index;
     *  @param  series  The series;
     *  @param  p       The point;
     */
    _updateLegend: function(idex, series, p) {
        if (p === null)
        {
            return;
        }

        var self    = this;
        var opts    = self.options;
        var $legend = self.$legends.eq(idex);
        var $stat   = $legend.find('.timeline-stat');
        var x       = series.xaxis.tickFormatter(p[0], series.xaxis);
        var y       = series.yaxis.tickFormatter(p[1], series.yaxis);
        var str     = (opts.replaceLegend ? '' : ': ') + x +': '+ y;

        if ($stat.length > 0)
        {
            $stat.html( str )
                 .show();
        }
        else
        {
            var html    = '<span class="timeline-stat">'
                        +  str
                        + '</span>';

            $legend.append(  html );

        }

        if (opts.replaceLegend)
        {
            $legend.find('.timeline-text').hide();
        }
    },

    /** @brief  Update the highlighted points based upon the reported mouse
     *          position.
     *  @param  pos     The reported mouse position;
     */
    _updateHighlights: function(pos) {
        var self    = this;
        var opts    = self.options;

        // Clear all highlights, tips, and label stats and tips
        self.$plot.unhighlight();
        self.$timeline.find('.timeline-tooltip').remove();
        self.$legends
                .find('.timeline-stat').hide()
                .end()
                .find('.timeline-text').show();

        var axes    = self.$plot.getAxes();
        if ( (pos.x < axes.xaxis.min) || (pos.x > axes.xaxis.max) ||
             (pos.y < axes.yaxis.min) || (pos.y > axes.yaxis.max) )
        {
            self.hlTimer = null;
            return;
        }

        var idex, jdex, dataset = self.$plot.getData();
        for (idex = 0; idex < dataset.length; idex++)
        {
            var series  = dataset[idex];
            var p       = null;
            var p1      = null;
            var p2      = null;

            // Locate the items on either side of the mouse's x position
            for (jdex = 0; jdex < series.data.length; jdex++)
            {
                if (series.data[jdex] === undefined)
                {
                    continue;
                }

                var pt  = series.data[jdex];
                if ( pt[0] > pos.x )
                {
                    if ((p2 === null) || (pt[0] < p2[0]))
                    {
                        p2 = pt;
                    }
                }
                else if ( pt[0] < pos.x )
                {
                    if ((p1 === null) || (pt[0] > p1[0]))
                    {
                        p1  = pt;
                    }
                }
            }

            // Choose the closest point
            if (p1 === null)
            {
                p = p2;
            }
            else if (p2 === null)
            {
                p = p1;
            }
            else
            {
                if (Math.abs(pos.x - p1[0]) < Math.abs(pos.x - p2[0]))
                {
                    p = p1;
                }
                else
                {
                    p = p2;
                }
            }

            // Highlight the point
            self.$plot.highlight(series, p, false);

            // Present point information in legend and/or tips
            if (opts.valueInLegend)
            {
                self._updateLegend(idex, series, p);
            }
            if (opts.valueInTips)
            {
                self._showTip(idex, series, p);
            }
        }

        self.hlTimer = null;
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self        = this;
        var opts        = self.options;

        // Remove added items
        self.$plot.shutdown();
        self.$timeline.find('.timeline-tooltip').remove();

        if (self.$annotation.data('remove-on-destroy') === true)
        {
            self.$annotation.remove();
        }

        if (self.$legend.data('remove-on-destroy') === true)
        {
            self.$legend.remove();
        }
        else
        {
            // Unwrap the legend text
            self.$legends.each(function() {
                var $legend = $(this);

                $legend.html(  $legend.find('.timeline-text').html() );
            });
        }

        if (self.$timeline.data('remove-on-destroy') === true)
        {
            self.$timeline.remove();
        }

        self.element.css('position', self.element.data('orig-position'))
                    .removeData('orig-position')
                    .removeClass('ui-timeline');

        if ( opts.hideLegend !== true )
        {
            self.element.removeClass('ui-timeline-labeled');
        }

        if ($.type(opts.css) === 'string')
        {
            self.element.removeClass( opts.css );
        }
    }
});
    
}(jQuery));
