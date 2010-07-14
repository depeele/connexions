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
            if ((window.console !== undefined) &&
                $.isFunction(window.console.log))
            {
                var msg = fmt;
                for (var idex = 1; idex < arguments.length; idex++)
                {
                    msg = msg.replace(/%s/, arguments[idex]);
                }
                window.console.log(msg);
            }
        };

        /*
        $.log = ((window.console !== undefined) &&
                 $.isFunction(window.console.log)
                    ?  window.console.log
                    : function() {});
        */

        $.log("Logging enabled");
    }

    if ( (window.console === undefined) || (! $.isFunction(window.console.log)))
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
     * Dynamic script inclusion -- Based upon jquery-include.js
     *
     * Note: This modifies jQuery.ready to wait for any scripts that have been
     *       queued for dynamic loading.
     */

    if (false) {
    /* Overload jQuery's onDomReady
     *
     * Note: This MUST be BEFORE we redefine $.ready() so we can remove the
     *       current jQuery.ready event listener.
     */
    if ($.browser.mozilla || $.browser.opera)
    {
        document.removeEventListener('DOMContentLoaded', $.ready, false);
        document.addEventListener('DOMContentLoaded', function(){ $.ready(); },
                                  false);
    }
    $.event.remove(window, 'load', $.ready);
    $.event.add(window, 'load', function(){ $.ready(); });

    function scriptLoaded(script, url)
    {
        $.includeScripts[url] = true;

        // Invoke all callbacks that we have queued for this script
        $.each($.includeCallbacks[url], function(idex, onload) {
            onload.call(script);
        });
    }

    $.extend({
        includeScripts:     {}, // by url: false | $(script)
        includeCallbacks:   {}, // by url: array( onload callbacks )
        includeTimer:       null,
        include: function(url, onload) {
            if ($.includeScripts[url] !== undefined)
            {
                if (typeof onload === 'function')
                {
                    if ($.includeScripts[url] !== false)
                    {
                        // Already loaded, invoke the callback immediately
                        onload($.includeScripts[url]);
                    }
                    else
                    {
                        // Not yet loaded, push the callback on our list
                        $.includeCallbacks[url].push(onload);
                    }
                }
                return;
            }

            var script                = document.createElement('script');
            script.type               = 'text/javascript';
            script.onload             = function() {
                scriptLoaded(script, url);
            };
            script.onreadystatechange = function() {
                if ( (script.readyState !== 'complete') &&
                     (script.readyState !== 'loaded') )
                {
                    return;
                }

                scriptLoaded(script, url);
            };
            script.src                = url;

            // Mark this script as not-yet-loaded
            $.includeScripts[url]     = false;
            $.includeCallbacks[url] = [];
            if (typeof onload === 'function')
            {
                $.includeCallbacks[url].push(onload);
            }

            // Put the script into the DOM -- loading begins now
            document.getElementsByTagName('head')[0].appendChild(script);
        },

        /* Replace jQuery.ready to wait for included scripts to be loaded */
        _ready: $.ready,
        ready: function() {
            var isReady = true;

            // See if all included scripts have loaded
            $.each($.includeScripts, function(url, state) {
                if (state === false)
                {
                    return (isReady = false); // Stop traversal
                }
            });

            if (isReady)
            {
                /* All included scripts have loaded, invoke the original
                 * jQuery.ready()
                 */
                $._ready.apply($, arguments);
            }
            else
            {
                // NOT all included script are loaded, wait a bit...
                setTimeout($.ready, 10);
            }
        }
    });

    }
    /*************************************************************************/

    /*************************************************************************
     * Overlay any element.
     *
     */
    $.fn.mask = function() {
        return this.each(function() {
            var $spin       = $('#pageHeader h1 a img');
            var $el         = $(this);
            var zIndex      = $el.css('z-index');
            if (zIndex === 'auto')
            {
                zIndex = 99999;
            }
            else
            {
                zIndex++;
            }

            var $overlay    = $('<div></div>')
                                    .addClass('ui-widget-overlay')
                                    .appendTo($el)
                                    .css({width:    $el.width(),
                                          height:   $el.height(),
                                          'z-index':zIndex});

            var url = $spin.attr('src');
            $spin.attr('src', url.replace('.gif', '-spinner.gif') );

            if ($.fn.bgiframe)
            {
                $overlay.bgiframe();
            }
        });
    };

    $.fn.unmask = function() {
        return this.each(function() {
            var $spin       = $('#pageHeader h1 a img');
            var $el         = $(this);
            var $overlay    = $el.find('.ui-widget-overlay');

            $overlay.remove();

            var url = $spin.attr('src');
            $spin.attr('src', url.replace('-spinner.gif', '.gif') );
        });
    };

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
        // CAUTION: Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
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
/*jslint nomen: false, laxbreak: true */

(function($) {

jQuery.fn.pngFix = function(settings) {

	// Settings
	settings = jQuery.extend({
		blankgif: 'blank.gif'
	}, settings);

	var ie55 = (navigator.appName == "Microsoft Internet Explorer" && parseInt(navigator.appVersion) == 4 && navigator.appVersion.indexOf("MSIE 5.5") != -1);
	var ie6 = (navigator.appName == "Microsoft Internet Explorer" && parseInt(navigator.appVersion) == 4 && navigator.appVersion.indexOf("MSIE 6.0") != -1);

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
			if (prevStyle != ''){
				strNewHTML = '<span style="position:relative;display:inline-block;'+prevStyle+imgHand+'width:' + jQuery(this).width() + 'px;' + 'height:' + jQuery(this).height() + 'px;'+'">' + strNewHTML + '</span>';
			}

			jQuery(this).hide();
			jQuery(this).after(strNewHTML);

		});

		// fix css background pngs
		jQuery(this).find("*").each(function(){
			var bgIMG = jQuery(this).css('background-image');
			if(bgIMG.indexOf(".png")!=-1){
				var iebg = bgIMG.split('url("')[1].split('")')[0];
				jQuery(this).css('background-image', 'none');
				jQuery(this).get(0).runtimeStyle.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + iebg + "',sizingMethod='scale')";
			}
		});
		
		//fix input with png-source
		jQuery(this).find("input[src$=.png]").each(function() {
			var bgIMG = jQuery(this).attr('src');
			jQuery(this).get(0).runtimeStyle.filter = 'progid:DXImageTransform.Microsoft.AlphaImageLoader' + '(src=\'' + bgIMG + '\', sizingMethod=\'scale\');';
   		jQuery(this).attr('src', settings.blankgif)
		});
	
	}
	
	return jQuery;

};

})(jQuery);
/** @file
 *
 *  Provide a sprite-based checkbox.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("ui.checkbox", {
    version: "0.1.1",

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

        var name     = self.element.attr('name');
        var id       = self.element.attr('id');

        // Try to locate the associated label
        self.$label  = false;

        if (id)
        {
            self.$label = $('label[for='+ id +']');
        }
        if ((! self.$label) && name)
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
        if (! this.options.enabled)
        {
            this.options.enabled = true;
            this.$el.removeClass('ui-state-disabled');

            this._trigger('enabled');
        }
    },

    disable: function()
    {
        if (this.options.enabled)
        {
            this.options.enabled = false;
            this.$el.addClass('ui-state-disabled');

            this._trigger('disabled');
        }
    },

    toggle: function()
    {
        if (! this.options.enabled)
        {
            return;
        }

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
        if (this.options.enabled && (! this.options.checked))
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
        if (this.options.enabled && this.options.checked)
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
        $validation:    null,       /* The element to present validation
                                     * information in [:sibling
                                     *                  .ui-field-status]
                                     */
        validation:     null,       /* The validation criteria
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
    _create: function()
    {
        var self    = this;
        var opts    = this.options;

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

        opts.$label.addClass('ui-input-over')
                   .hide();

        self._bindEvents();
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
                opts.$label.hide();

                self.element.removeClass('ui-state-empty')
                            .addClass('ui-state-focus ui-state-active');
            }
        };

        var _blur       = function(e) {
            self.element.removeClass('ui-state-focus ui-state-active');
            if (! self.element.hasClass('ui-state-valid'))
            {
                self.validate();
            }

            if ($.trim(self.val()) === '')
            {
                self.element.addClass('ui-state-empty');

                opts.$label.show();
            }
        };

        self.element
                .bind('mouseenter.uiinput', _mouseenter)
                .bind('mouseleave.uiinput', _mouseleave)
                .bind('keydown.uiinput',    _keydown)
                .bind('focus.uiinput',      _focus)
                .bind('blur.uiinput',       _blur);

        opts.$label
                .bind('click.uiinput', function() { self.element.focus(); });

        if ($.trim(self.val()) !== '')
        {
            // Perform an initial validation
            self.validate();
        }
        else
        {
            opts.$label.show();
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

    /** @brief  Set the current validation state.
     *  @param  state   The new state:
     *                      undefined   undetermined
     *                      true        valid
     *                      false       invalid
     *                      string      invalid, error message
     */
    valid: function(state)
    {
        if (state === this.options.valid)
        {
            return;
        }

        // Clear out validation information
        this.element
                .removeClass('ui-state-error ui-state-valid');

        this.options.$validation
                .html('&nbsp;')
                .removeClass('ui-state-invalid ui-state-valid');

        if (state === true)
        {
            // Valid
            this.element.addClass(   'ui-state-valid');

            this.options.$validation
                        .addClass(   'ui-state-valid');
        }
        else if (state !== undefined)
        {
            // Invalid, possibly with an error message
            this.element.addClass(   'ui-state-error');

            this.options.$validation
                        .addClass(   'ui-state-invalid');

            if (typeof state === 'string')
            {
                this.options.$validation
                            .html(state);
            }
        }

        this.options.valid = state;

        // Let everyone know that the validation state has changed.
        //this.element.trigger('validation_change.uiinput');
        this._trigger('validation_change', null, [state]);
    },

    getLabel: function()
    {
        return this.options.$label.text();
    },

    setLabel: function(str)
    {
        this.options.$label.text(str);
    },

    val: function(newVal)
    {
        return this.element.val();
    },

    validate: function()
    {
        var msg         = [];
        var newState;

        if (this.options.validation === null)
        {
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
            msg.push('Cannot be empty');
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
                .unbind('.uiinput');
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
  version: "2.1.1b",

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
        o.value = o.cancelValue;
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

      !o.forceSelect && self.callback(e, "star");

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

      !o.forceSelect && self.callback(e, "cancel");
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
    o.disabled && this.disable();

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
  },
  disable: function() {
    this.options.disabled = true;
    this._disableAll();
  },
  destroy: function() {
    this.$cancel.unbind(".stars");
    this.$stars.unbind(".stars");
    this.element.unbind(".stars").removeData("stars");
  },
  callback: function(e, type) {
    var o = this.options;
    o.callback && o.callback(this, type, o.value, e);
    o.oneVoteOnly && !o.disabled && this.disable();
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
    _create: function() {
        var self        = this;
        var opts        = self.options;

        self.$form      = self.element.find('form:first');
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
        self.element.find('.ui-optionGroups').optionGroups();

        self.$form.hide();

        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self        = this;
        

        // Handle a click outside of the display options form.
        var _body_click     = function(e) {
            if (self.$form.is(':visible') &&
                (! $.contains(self.$form[0], e.target)) )
            {
                /* Hide the form by triggering self.$control.click and then
                 * mouseleave
                 */
                self.$control.trigger('click');

                self._trigger('mouseleave', e);
                //self.element.trigger('mouseleave');
            }
        };

        // Opacity hover effects
        var _mouse_enter    = function(e) {
            self.$control.fadeTo(100, 1.0);
        };

        var _mouse_leave    = function(e) {
            if (self.$form.is(':visible'))
            {
                // Don't fade if the form is currently visible
                return;
            }

            self.$control.fadeTo(100, 0.5);
        };

        var _control_click  = function(e) {
            // Toggle the displayOptions pane
            e.preventDefault();
            e.stopPropagation();

            self.$form.toggle();
            self.$button.toggleClass('ui-state-active');

            return false;
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

        var _form_submit        = function(e) {
            // Serialize all form values to an array...
            var settings    = self.$form.serializeArray();
            //e.preventDefault();

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
                $.log("Add Cookie: name[%s], value[%s]",
                      this.name, this.value);
                // */
                $.cookie(this.name, this.value);
            });

            if (! self._trigger('apply', e))
            {
                e.stopImmediatePropagation();
                e.preventDefault();
                e.stopPropagation();
                return false;
            }

            /*
            var callback    = self.options.apply;
            if ($.isFunction(callback))
            {
                callback.call( self.element[0], e);
                //self.options.submitCb(e, self);
            }
            else
            {
                // Reload so our URL won't be polluted with form variables that
                // we've just placed into cookies.
                window.location.reload();
            }
            */
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
/*global jQuery:false, document:false */

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


        /* If the currently selected group is NOT the 'isCustom' group, toggle
         * the fieldset control closed.
         */
        if (! $groups.find('li.ui-state-active').hasClass('isCustom'))
        {
            self.toggleFieldset();
        }
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        var self            = this;
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

                $.cookie( $(this).attr('name'), null );
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
        self.options.form
                .bind('submit.uioptiongroups', _form_submit);
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
 *      - conversion of the input area to either a ui.input or ui.autocomplete
 *        instance;
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
 *      ui.input.js  OR ui.autocomplete.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false, document:false */
(function($){

$.widget("connexions.itemScope", {
    options: {
        namespace:          '',     // Cookie/parameter namespace

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
        rpcId:              1,      // The initial RPC identifier

        separator:          ',',    // The term separator
        minLength:          2       // Minimum term length
    },
    _create: function(){
        var self    = this;
        var opts    = self.options;

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
            self.$input.autocomplete({
                source:     function(request, response) {
                    return self._autocomplete(request,response);
                },
                minLength:  opts.minLength
            });
        }

        self._bindEvents();
    },

    _autocomplete: function(request, response) {
        var self    = this;
        var opts    = self.options;
        var id      = opts.rpcId++;
        var data    = {
            version:    opts.jsonRpc.version,
            id:         id,
            method:     opts.jsonRpc.method,
            params:     opts.jsonRpc.params
        };

        data.params.str = self.$input.autocomplete('option', 'term');

        $.ajax({
            type:       opts.jsonRpc.transport,
            url:        opts.jsonRpc.target,
            dataType:   "json",
            data:       JSON.stringify(data),
            success:    function(ret, txtStatus, req){
                if (ret.error !== null)
                {
                    self.element.trigger('error', [txtStatus, req, ret.error]);
                    return;
                }

                response(
                    $.map(ret.result,
                          function(item) {
                            return {
                                label:   '<span class="name">'
                                       +  item.tag
                                       + '</span>'
                                       +' <span class="count">'
                                       +  item.userItemCount
                                       + '</span>',
                                value: item.tag
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
                .trigger('mouseleave');

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
                                                   .replace(/,$/, '');
                    if (url[url.length-1] !== '/')
                    {
                        url += '/';
                    }

                    if (scope.length > 0)
                    {
                        // Include the new scope item(s)
                        if (self.$curItems.length > 0)
                        {
                            url += ',';
                        }
                        url += scope;
                    }

                    // Simply change the browsers URL
                    window.location.assign(url);

                    // Allow form submission to continue
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
            self.$input.autocomplete('destroy');
        }
        self.$input.input('destroy');

        // Unbind events
        self.element.find('.deletable a.delete').unbind('.itemScope');
        self.$submit.unbind('.itemScope');
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
    _create: function() {
        var self        = this;
        var opts        = self.options;

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
        self.element.find('select[name='+ opts.namespace +'PerPage]')
                .bind('change.paginator', function(e) {
                        /* On change of the PerPage select, trigger 'submit' on
                         * the pagination form.
                         */
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

        // Attach to any 'submit' event on the top-level form.
        self.element
                .bind('submit.paginator', function(e) {
                        // Serialize all form values to an array...
                        var settings    = self.element.serializeArray();

                        /* ...and set a cookie for each:
                         *      %ns%PerPage
                         */
                        $(settings).each(function() {
                            $.log("Add Cookie: name[%s], value[%s]",
                                  this.name, this.value);
                            $.cookie(this.name, this.value);
                        });

                        /* Finally, since we've set all parameters as
                         * cookies, we don't need to actually SUBMIT this
                         * form.  Disable the event and reload the window.
                        e.stopImmediatePropagation();
                        e.preventDefault();
                        e.stopPropagation();

                        window.location.reload();
                         */
                      }
                );

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
        this.find(':button').removeAttr('disabled');
    },

    disable: function() {
        this.find(':button').attr('disabled', true);
    },

    destroy: function() {
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
        rpcId:      1,      // The initial RPC identifier

        // Widget state
        enabled:    true
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'enabled.bookmark'
     *      'disabled.bookmark'
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

        self.$tags        = self.element.find('input[name=tags]');

        self.$edit        = self.element.find('.control .item-edit');
        self.$delete      = self.element.find('.control .item-delete');
        self.$save        = self.element.find('.control .item-save');

        self.$url         = self.element.find('.itemName a,.url a');

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

            $.log('connexions.bookmark::_update_item('+ data +')');

            if ((self.options.enabled !== true) || (self._squelch === true))
            {
                return;
            }

            // Gather the current data about this item.
            var nonEmpty    = false;
            var params      = {
                id: { userId: opts.userId, itemId: opts.itemId }
            };

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

            if ( (self.$tags.length > 0) &&
                 (self.$tags.text() !== opts.tags) )
            {
                params.tags = self.$tags.text();
                nonEmpty    = true;
            }

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

            var rpc = {
                version: opts.jsonRpc.version,
                id:      opts.rpcId++,
                method:  'bookmark.update',
                params:  params
            };

            // Perform a JSON-RPC call to update this item
            $.ajax({
                url:        opts.jsonRpc.target,
                type:       opts.jsonRpc.transport,
                dataType:   'json',
                data:       JSON.stringify(rpc),
                success:    function(data, textStatus, req) {
                    if (data.error !== null)
                    {
                        $.notify({
                            title: 'Bookmark update failed',
                            text:  '<p class="error">'
                                 +   data.error.message
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
                    self.$itemId.val(           data.result.itemId );
                    self.$name.text(            data.result.name );
                    self.$description.text(     data.result.description );

                    self.$tags.text(            data.result.tags );

                    self.$rating.stars('select',data.result.rating);

                    self.$favorite.checkbox(    (data.result.isFavorite
                                                    ? 'check'
                                                    : 'uncheck') );
                    self.$private.checkbox(     (data.result.isPrivate
                                                    ? 'check'
                                                    : 'uncheck') );
                    self.$url.attr('href',      data.result.url);

                    // Alter our parent to reflect 'isPrivate'
                    var parent  = self.element.parent();
                    if (data.result.isPrivate)
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

            return false;
        };

        // Handle item-edit
        var _edit_click  = function(e) {
            return;

            e.preventDefault();
            e.stopPropagation();

            if (self.options.enabled !== true)
            {
                return;
            }
        };

        // Handle item-delete
        var _delete_click  = function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (self.options.enabled !== true)
            {
                return;
            }
        };

        // Handle save-delete
        var _save_click  = function(e) {
            return;


            var formUrl;

            if (self.options.enabled === true)
            {
                // Popup a dialog with a post form for this item.
                try
                {
                    formUrl = $.registry('urls').base +'/post'
                            +       '?format=partial'
                            +       '&url='+ opts.url;
                }
                catch(e)
                {
                    // return and let the click propagate
                    return;
                }
            }

            e.preventDefault();
            e.stopPropagation();

            $.get(formUrl,
                  function(data) {
                    self._dialog_save(data);
                  });


        };

        /**********************************************************************
         * bind events
         *
         */

        /*
        self.$favorite.bind('click.bookmark', _update_item);
        self.$private.bind('click.bookmark',  _update_item);
        self.$rating.bind('click.bookmark',   _update_item);
        */

        self.element.bind('change.bookmark',    _update_item);

        self.$edit.bind('click.bookmark',       _edit_click);
        self.$delete.bind('click.bookmark',     _delete_click);
        self.$save.bind('click.bookmark',       _save_click);
    },

    _dialog_save: function(html)
    {
        html = '<div class="ui-validation-form">'
             +  '<div class="userInput lastUnit">'
             +   html
             +  '</div>'
             + '</div>';

        var $form   = $(html);

        $form.find('form').bookmarkPost();

        $form.dialog({
            autoOpen:   true,
            height:     350,
            width:      450,
            modal:      true,
            close: function() {
                //allFields.val('').removeClass('ui-state-error');
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
        self.$rating.unbind('.bookmark');
        self.$edit.unbind('.bookmark');
        self.$delete.unbind('.bookmark');
        self.$save.unbind('.bookmark');

        // Remove added elements
        self.$favorite.checkbox('destroy');
        self.$private.checkbox('destroy');
        self.$rating.stars('destroy');
    }
});


}(jQuery));

/** @file
 *
 *  Javascript interface/wrapper for the presentation of multiple bookmarks.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-rendered list of bookmark items (View_Helper_HtmlBookmarks), each of
 *  which will become a connexions.bookmark instance.
 *
 *  This class also handles:
 *      - hover effects for .groupHeader DOM items;
 *      - conversion of all form.bookmark DOM items to connexions.bookmark
 *        instances;
 *
 *  View_Helper_HtmlBookmarks will generate HTML for a bookmark list similar
 *  to:
 *      <div id='<ns>List'>
 *        <ul class='<ns>'>
 *          <li><form class='bookmark'> ... </form></li>
 *          ...
 *        </ul>
 *      </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      connexions.bookmark.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.bookmarkList", {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        // Defaults
        namespace:  '',
        dimOpacity: 0.5
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'change.bookmark'  when something about the bookmark is changed;
     */
    _create: function()
    {
        var self    = this;
        var opts    = self.options;

        // Bookmarks
        self.$bookmarks = self.element.find('form.bookmark');

        // Group Headers
        self.$headers = self.element.find('.groupHeader .groupType');


        self.$bookmarks.bookmark();

        self.$headers
                .fadeTo(100, opts.dimOpacity)
                .hover( function() {    // in
                            self.$headers.fadeTo(100, 1.0);
                        },
                        function() {    // out
                            self.$headers.fadeTo(100, opts.dimOpacity);
                        }
                );

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
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self        = this;

        // Unbind events
        self.$headers.unbind('hover');

        // Remove added elements
        self.$bookmarks.bookmark('destroy');
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


        /* Configuration for any <form class='pagination'> element that 
         * will be controlled by a connexions.pagination widget.
         */
        paginator:      {},

        /* Configuration for any <div class='displayOptions'> element that 
         * will be controlled by a connexions.dropdownForm widget.
         */
        displayOptions: {}
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'change.bookmark'  when something about the bookmark is changed;
     */
    _create: function() {
        this._paneInit();
    },

    /************************
     * Private methods
     *
     */
    _paneInit: function() {
        this._init_paginators();
        this._init_displayOptions();
    },

    _init_paginators: function() {
        var self        = this;
        var opts        = self.options;

        self.$paginators    = self.element.find('form.paginator');

        self.$paginators.each(function(idex) {
            var $pForm  = $(this);

            $pForm.paginator({namespace:    opts.namespace,
                              form:         $pForm,
                              disableHover: (idex !== 0)
                              });

            if (opts.page === null)
            {
                opts.pageCur = $pForm.paginator('getPage');
                opts.pageVar = $pForm.paginator('getPageVar');
            }
        });

        self.$paginators.bind('submit.uipane', function(e) {
            e.preventDefault(true);
            e.stopPropagation(true);
            e.stopImmediatePropagation(true);

            // Set the target page number
            opts.page = $(this).paginator('getPage');

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
        var uiOpts  = (opts.displayOptions === undefined
                        ? {}
                        : opts.displayOptions);

        if (uiOpts.namespace === undefined)
        {
            uiOpts.namespace = opts.namespace;
        }

        if (! $.isFunction(uiOpts.apply))
        {
            uiOpts.apply = function(e) {
                /* dropdownForm sets cookies for any form values, so we can
                 * simplify the form submission process (ensuring a clean url)
                 * by simply re-loading the window.  The reload will cause the
                 * new cookie values to be applied.
                 */
                e.stopImmediatePropagation();
                e.preventDefault();
                e.stopPropagation();

                self.reload();
            };
        }

        // Instantiate the connexions.dropdownForm widget
        self.$displayOptions.dropdownForm(uiOpts);
    },
    _paneDestroy: function() {
        var self    = this;

        // Remove added elements
        self.$paginators.paginator('destroy');
        self.$displayOptions.dropdownForm('destroy');
    },

    /************************
     * Public methods
     *
     */
    reload: function(page) {
        var self    = this;
        var opts    = self.options;
        var re      = new RegExp(opts.pageVar +'='+ opts.pageCur);
        var rep     = opts.pageVar +'='+ opts.page;
        var loc     = window.location;
        var url     = loc.toString();

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

        if (opts.partial !== null)
        {
            // AJAX reload of just this pane...
            url += '&format=partial&part='+ opts.partial;

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

                        // In with the new.
                        self.element.html(data);
                        self._create();
                    },
                    complete:   function() {
                        self.element.unmask();
                    }
            });
        }
        else
        {
            // Perform a full, synchronous reload...
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
 *  which contains a bookmark list.
 *
 *  This is class extends connexions.pane to include unobtrusive activation of
 *  any contained, pre-rendered ul.bookmarkList generated via
 *  View_Helper_HtmlBookmarks.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      connexions.pane.js
 *      connexions.bookmarkList.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.bookmarksPane", $.connexions.pane, {
    version: "0.0.1",
    options: {
        // Defaults
        namespace:  ''
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'change.bookmark'  when something about the bookmark is changed;
     */
    _create: function() {
        var self        = this;
        var opts        = self.options;

        self._init_bookmarkList();

        self._paneInit();
    },

    /************************
     * Private methods
     *
     */
    _init_bookmarkList: function() {
        var self            = this;
        self.$bookmarkList  = self.element.find('ul.bookmarks');

        if (self.$bookmarkList.length < 1)
        {
            return;
        }

        var opts    = self.options;
        var uiOpts  = (opts.bookmarkList === undefined
                        ? {}
                        : opts.bookmarkList);

        if (uiOpts.namespace === undefined)
        {
            uiOpts.namespace = opts.namespace;
        }

        // Instantiate the connexions.bookmarkList widget
        self.$bookmarkList.bookmarkList(uiOpts);
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;

        // Remove added elements
        self.$bookmarkList.bookmarkList('destroy');

        self._paneDestroy();
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
 *      connexions.pane.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("connexions.cloudPane", $.connexions.pane, {
    version: "0.0.1",
    options: {
        // Defaults
        namespace:  ''
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'change.bookmark'  when something about the bookmark is changed;
     */
    _create: function() {
        var self        = this;
        var opts        = self.options;

        //self._init_cloud();
        self._paneInit();

        self.$optionsForm = self.element.find('.displayOptions form');

        self._bindEvents();
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function() {
        /* On Display style change, toggle the state of 'highlightCount'
         *
         * Note: The connexions.dropdownForm widget that controls the display
         *       options DOM element attached a connexions.optionsGroups
         *       instance to any contained displayOptions element.  This widget
         *       will trigger the 'change' event on the displayOptions form
         *       with information about the selected display group when a
         *       change is made.
         */
        this.$optionsForm.bind('change.cloudPane',
                function(e, info) {
                    var $field  = $(this).find('.field.highlightCount');

                    if (info.group === 'cloud')
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
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self    = this;

        // Unbind events
        self.$optionsForm.unbind('.cloudPane');

        self._paneDestroy();
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
                    // Bind any new displayOptions forms
                    self._bindReload(self.$tab);

                    // Unmask the tab panel area...
                    self.$tab.unmask();
                }
            }
        });

        // For each asynchronous tab, bind reload events
        self.element.find('ul:first li a:first').each(function(idex) {
            var url = $.data(this, 'load.tabs');
            if (url)
            {
                var $tab = self.element.find('.ui-tabs-panel').eq(idex);
                self._bindReload($tab);
            }
        });

        self._bindReload(self.element);
    },

    /************************
     * Private methods
     *
     */
    _bindReload: function(context) {
        var self    = this;

        context.find('.displayOptions')
                .dropdownForm('setApplyCb', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    // Reload the tab contents
                    self.element.tabs('load',
                                      self.element.tabs('option', 'selected'));
                });
    },

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
 *            <input type='text' name='q' class='input' ... />
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

        self.$input         = self.element.find('input[name=q]');
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
 *  pre-renderd bookmark post form
 *      (application/views/scripts/post/index-partial.phtml)
 *
 *  <form>
 *   <div class='item-status'>
 *    <div class='field favorite'>
 *     <label  for='isFavorite'>Favorite</label>
 *     <input name='isFavorite' type='checkbox' />
 *    </div>
 *    <div class='field private'>
 *     <label  for='isPrivate'>Private</label>
 *     <input name='isPrivate' type='checkbox' />
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
 *  </form>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
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

        tags:       null,
        url:        null,

        // taggedOn and updateOn are not user editable

        /* An element or element selector to be used to present general status
         * information.  If not provided, $.notify will be used.
         */
        statusEl:   null,

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
         * If not provided, 'method' will be:
         *      'bookmarks.update'
         *
         */
        jsonRpc:    null,
        rpcId:      1,      // The initial RPC identifier

        // Widget state
        enabled:    true
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'enabled.bookmarkPost'
     *      'disabled.bookmarkPost'
     *      'saved.bookmarkPost'
     *      'canceled.bookmarkPost'
     */
    _create: function()
    {
        var self        = this;
        var opts        = self.options;

        // Hide the form while we prepare it...
        self.element.hide();

        self.element.addClass('ui-form');

        /********************************
         * Initialize jsonRpc
         *
         */
        if ($.isFunction($.registry))
        {
            var api = $.registry('api');
            if (api && api.jsonRpc)
            {
                opts.jsonRpc = $.extend({method: 'bookmark.update'},
                                        api.jsonRpc, opts.jsonRpc);
            }
        }

        if ((opts.statusEl !== null) && (opts.statusEl.jquery === undefined))
        {
            opts.statusEl = $(opts.statusEl);
        }

        /********************************
         * Locate the pieces
         *
         */
        self.$required    = self.element.find('.required');

        self.$userId      = self.element.find('input[name=userId]');
        self.$itemId      = self.element.find('input[name=itemId]');

        self.$favorite    = self.element.find('input[name=isFavorite]');
        self.$private     = self.element.find('input[name=isPrivate]');
        self.$rating      = self.element.find('.userRating .stars-wrapper');

        self.$name        = self.element.find('input[name=name]');
        self.$url         = self.element.find('input[name=url]');
        self.$description = self.element.find('textarea[name=description]');
        self.$tags        = self.element.find('textarea[name=tags]');

        self.$save        = self.element.find('button[name=submit]');
        self.$cancel      = self.element.find('button[name=cancel]');

        // All input[text/password] and textarea elements
        self.$inputs      = self.element.find(  'input[type=text],'
                                              + 'input[type=password],'
                                              + 'textarea');

        // click-to-edit elements
        self.$cte         = self.element.find('.click-to-edit');

        /********************************
         * Instantiate our sub-widgets
         *
         */

        // Tag autocompletion
        self.$tags.autocomplete({
            source: function(req, rsp) {
                return self._autocomplete(req, rsp);
            }
        });

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

        // Rating - average and user
        self.$rating.stars({
            //split:    2
        });

        self.$save.addClass('ui-priority-primary')
                  .button({disabled: true});

        self.$cancel.addClass('ui-priority-secondary')
                    .button({disabled: false});

        /* Style all remaining input[type=text|password] / textarea controls
         * with ui.input
         */
        self.$inputs.input();

        // Add 'ui-field-info' for all required fields
        self.$required.after(  '<div class="ui-field-info">'
                             +  '<div class="ui-field-status"></div>'
                             +  '<div class="ui-field-requirements">'
                             +   'required'
                             +  '</div>'
                             + '</div>');

        self.$required
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

        self.element.show();
    },

    _setStateFromForm: function()
    {
        // Set the current widget state to the values of it's sub-components
        var self    = this;
        var opts    = self.options;

        opts.name        = self.$name.val();
        opts.description = self.$description.val();
        opts.tags        = self.$tags.val();

        opts.isFavorite  = self.$favorite.checkbox('isChecked');
        opts.isPrivate   = self.$private.checkbox('isChecked');

        opts.url         = self.$url.val();

        if (self.$userId.length > 0)
        {
            opts.userId  = self.$userId.val();
        }

        if (self.$userId.length > 0)
        {
            opts.itemId  = self.$itemId.val();
        }

        if (self.$rating.length > 0)
        {
            opts.rating  = self.$rating.stars('value');
        }
    },

    _setFormFromState: function()
    {
        // Set the current widget state to the values of it's sub-components
        var self    = this;
        var opts    = self.options;

        self.$name.val(opts.name);
        self.$description.val(opts.description);
        self.$tags.val(opts.tags);

        self.$favorite.checkbox( opts.isFavorite ? 'check' : 'uncheck' );
        self.$private.checkbox(  opts.isPrivate  ? 'check' : 'uncheck' );

        self.$url.val(opts.url);

        if (self.$userId.length > 0)
        {
            self.$userId.val(opts.userId);
        }

        if (self.$userId.length > 0)
        {
            self.$itemId.val(opts.itemId);
        }

        if (self.$rating.length > 0)
        {
            self.$rating.stars('value', opts.rating);
        }
    },

    /************************
     * Private methods
     *
     */
    _bindEvents: function()
    {
        var self    = this;
        var opts    = self.options;

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

        var _validate_form  = function() {
            var isValid     = true;

            self.$required.each(function() {
                if (! $(this).hasClass('ui-state-valid'))
                {
                    isValid = false;
                    return false;
                }
            });

            if (isValid)
            {
                self.$save.button('enable');
                self._status(true);
            }
            else
            {
                self.$save.button('disable');
                self._status(false);
            }
        };


        /**********************************************************************
         * bind events
         *
         */
        self.$inputs.bind('validation_change.bookmarkPost',
                                                _validate_form);

        self.$cte.bind('validation_change.bookmarkPost',
                                                _validation_change);

        self.$save.bind('click.bookmarkPost',   _save_click);
        self.$cancel.bind('click.bookmarkPost', _cancel_click);

        _validate_form();
    },

    _performUpdate: function() {
        var self    = this;
        var opts    = self.options;

        if (opts.enabled !== true)
        {
            return;
        }


        // Gather the current data about this item.
        var nonEmpty    = false;
        var params      = {
            id: { userId: opts.userId, itemId: opts.itemId }
        };

        // Include all fields that have changed.
        if (self.$name.val() !== opts.name)
        {
            params.name = self.$name.val();
            nonEmpty    = true;
        }

        if (self.$description.val() !== opts.description)
        {
            params.description = self.$description.val();
            nonEmpty           = true;
        }

        if ( (self.$tags.length > 0) &&
             (self.$tags.val() !== opts.tags) )
        {
            params.tags = self.$tags.val();
            nonEmpty    = true;
        }

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

        if ( (self.$rating.length > 0) &&
             (self.$rating.stars('value') !== opts.rating) )
        {
            params.rating = self.$rating.stars('value');
            nonEmpty      = true;
        }

        if (self.$url.val() !== opts.url)
        {
            // The URL has changed -- pass it in
            params.url = self.$url.val();
            nonEmpty   = true;
        }
        if (nonEmpty !== true)
        {
            return;
        }

        // If no itemId was provided, use the final URL.
        if (params.id.itemId === null)
        {
            params.id.itemId = (params.url !== undefined
                                ? params.url
                                : opts.url);
        }

        // Generate a JSON-RPC to perform the update.
        var rpc = {
            version: opts.jsonRpc.version,
            id:      opts.rpcId++,
            method:  opts.jsonRpc.method,
            params:  params
        };

        self.element.mask();

        // Perform a JSON-RPC call to update this item
        $.ajax({
            url:        opts.jsonRpc.target,
            type:       opts.jsonRpc.transport,
            dataType:   'json',
            data:       JSON.stringify(rpc),
            success:    function(data, textStatus, req) {
                if (data.error !== null)
                {
                    self._status(false,
                                 'Bookmark update failed',
                                 data.error.message);

                    return;
                }

                self._status(true,
                             null,
                             'Bookmark '+ (opts.itemId === null
                                            ? 'created'
                                            : 'updated'));

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
                        tags.push(this.tag);
                    });

                    opts.tags = tags.join(',');
                }

                self._setFormFromState();

                // "Save" notification
                self._trigger('saved', null, data.result);
            },
            error:      function(req, textStatus, err) {
                self._status(false,
                             'Bookmark update failed',
                             textStatus);

                // :TODO: "Error" notification??
            },
            complete:   function(req, textStatus) {
                self.element.unmask();
            }
         });
    },

    _autocomplete: function(request, response) {
        var self    = this;
        var opts    = self.options;
        var id      = opts.rpcId++;
        var data    = {
            version:    opts.jsonRpc.version,
            id:         id,
            method:     'bookmark.autocompleteTag',
            params:     { id: { userId: opts.userId, itemId: opts.itemId } }
        };

        // If no itemId was provided, use the final URL.
        if (data.params.id.itemId === null)
        {
            // The URL has changed -- pass it in
            data.params.id.itemId = self.$url.val();
        }

        data.params.str = self.$tags.autocomplete('option', 'term');

        $.ajax({
            type:       opts.jsonRpc.transport,
            url:        opts.jsonRpc.target,
            dataType:   "json",
            data:       JSON.stringify(data),
            success:    function(ret, txtStatus, req){
                if (ret.error !== null)
                {
                    self.element.trigger('error', [txtStatus, req, ret.error]);
                    return;
                }

                response(
                    $.map(ret.result,
                          function(item) {
                            return {
                                label:   '<span class="name">'
                                       +  item.tag
                                       + '</span>'
                                       +' <span class="count">'
                                       +  item.userItemCount
                                       + '</span>',
                                value: item.tag
                            };
                          }));
                self.element.trigger('success', [ret, txtStatus, req]);
            },
            error:      function(req, txtStatus, e) {
                self.element.trigger('error', [txtStatus, req]);
            }
        });
    },

    _status: function(isSuccess, title, text) {
        var self    = this;
        var opts    = self.options;

        if (opts.statusEl === null)
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

            opts.statusEl.html(msg);

            if (isSuccess)
            {
                opts.statusEl.removeClass('error').addClass('success');
            }
            else
            {
                opts.statusEl.removeClass('success').addClass('error');
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

            self.$favorite.checkbox('enable');
            self.$private.checkbox('enable');
            self.$rating.stars('enable');
            self.$inputs.input('enable');

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

            self.$favorite.checkbox('disable');
            self.$private.checkbox('disable');
            self.$rating.stars('disable');
            self.$inputs.input('disable');

            self._trigger('disabled', null, true);
        }
    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Cleanup
        self.$save.removeClass('ui-priority-primary');
        self.$cancel.removeClass('ui-priority-secondary');
        self.$required.next('.ui-field-info').remove();

        self.element.removeClass('ui-form');

        // Unbind events
        self.$inputs.unbind('.bookmarkPost');
        self.$cte.unbind('.bookmarkPost');
        self.$save.unbind('.bookmarkPost');
        self.$cancel.unbind('.bookmarkPost');

        // Remove added elements
        self.$favorite.checkbox('destroy');
        self.$private.checkbox('destroy');
        self.$rating.stars('destroy');
        self.$inputs.input('destroy');
        self.$save.button('destroy');
        self.$cancel.button('destroy');
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
