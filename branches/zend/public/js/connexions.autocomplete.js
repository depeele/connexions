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
        delay:      200,
        minLength:  2,
        separator:  ','
    },

    _init: function() {
        var self    = this;
        var opts    = self.options;

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
