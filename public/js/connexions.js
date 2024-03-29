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
