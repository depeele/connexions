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
                                    .css({width:    $el.outerWidth(),
                                          height:   $el.outerHeight(),
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
    _create: function()
    {
        var self    = this;
        var opts    = this.options;

        // Remember the original value
        self.saved();

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

        self.element
                .bind('mouseenter.uiinput', _mouseenter)
                .bind('mouseleave.uiinput', _mouseleave)
                .bind('keydown.uiinput',    _keydown)
                .bind('focus.uiinput',      _focus)
                .bind('blur.uiinput',       _blur);

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
        if (state === this.options.valid)
        {
            return;
        }

        // Clear out validation information
        this.element
                .removeClass('ui-state-error ui-state-valid ui-state-changed');

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

        if (this.hasChanged())
        {
            this.element.addClass('ui-state-changed');
        }

        this.options.valid = state;

        // Let everyone know that the validation state has changed.
        //this.element.trigger('validation_change.uiinput');

        if (state !== undefined)
        {
            this._trigger('validation_change', null, [state]);
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
     */
    saved: function()
    {
        this.element.data('value.uiinput', this.val() );
    },

    val: function(newVal)
    {
        if (newVal !== undefined)
        {
            newVal = $.trim(newVal);

            this.element.data('value.uiinput', newVal);
            var ret = this.element.val( newVal );

            // Invoke _blur() to validate
            this._blur();
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
  },
  disable: function() {
    this.options.disabled = true;
    this._disableAll();
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
     */
    _create: function()
    {
        var self    = this;
        var opts    = this.options;

        self.element.addClass( 'ui-form');

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
                                    .parent()
                                        .find('.ui-form-status:first');
        }

        opts.$required = self.element.find('.required');
        opts.$inputs   = self.element.find(  'input[type=text],'
                                           + 'input[type=password],'
                                           + 'textarea');
        if (opts.$submit === undefined)
        {
            opts.$submit = self.element.find( opts.submitSelector );
        }
        opts.$cancel   = self.element.find('button[name=cancel]');
        opts.$reset    = self.element.find('button[name=reset]');

        // Instantiate sub-widgets
        opts.$inputs.input({hideLabel: opts.hideLabels});
        opts.$submit.button({priority:'primary', enabled:false});
        opts.$cancel.button({priority:'secondary'});
        opts.$reset.button({priority:'secondary'});

        self._bindEvents();
    },

    _bindEvents: function()
    {
        var self    = this;
        var opts    = self.options;

        var _validate   = function(e) {
            self.validate();
        };

        var _reset      = function(e) {
            e.preventDefault();
            e.stopPropagation();

            self.reset();
        };

        opts.$inputs.bind('validation_change.uivalidationform', _validate);
        opts.$reset.bind('click.uivalidationform', _reset);

        // Perform an initial validation
        self.validate();
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
        var isValid     = true;
        var hasChanged  = self.hasChanged();

        if (hasChanged)
        {
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

            if (isValid)
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
            }
        }

        if (isValid &&
            ( (opts.disableSubmitOnUnchanged === false) || hasChanged) )
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

        opts.$inputs.unbind('.uivalidationform');
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
        panelTemplate:  '<div></div>'
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'collapse', 'expand', 'toggle'
     */
    _create: function() {
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
                self.$a.data('load.collapsable', href.replace(/#.*$/, ''));

                var id  = ((self.$a.title &&
                            self.$a.title.replace(/\s/g, '_')
                                         .replace(/[^A-Za-z0-9\-_:\.]/g, '')) ||
                           opts.idPrefix + (++collapsableId));
                self.$a.attr('href', '#'+ id);

                self.$content = $('#'+ id);
                if (self.$content.length < 1)
                {
                    self.$content = $(opts.panelTemplate)
                                        .attr('id', id)
                                        .addClass('ui-corner-bottom')
                                        .insertAfter(self.$toggle);
                    self.$content.data('destroy.collapsable', true);
                }
                self.$content.addClass('content');
            }
        }
        else
        {
            self.$content = self.$toggle.next();
            self.$content.addClass('ui-corner-bottom');
        }

        // Add styling to the toggle and content
        self.$toggle.addClass('ui-corner-top');

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

        self.$toggle.bind('click.collapsable', function() {
            if (self.$content.is(":hidden"))
            {
                // Show the content / open
                self.$toggle.removeClass('collapsed')
                            .addClass(   'expanded');
                self.$indicator.removeClass('ui-icon-triangle-1-e')
                               .addClass(   'ui-icon-triangle-1-s');
                self.$content.slideDown();
                    
                self.element.trigger('expand');
                self._load();
            }
            else
            {
                // Hide the content / close
                self.$toggle.removeClass('expanded')
                            .addClass(   'collapsed');
                self.$indicator.removeClass('ui-icon-triangle-1-s')
                               .addClass(   'ui-icon-triangle-1-e');
                self.$content.slideUp();

                self.element.trigger('collapse');
            }

            // Trigger 'toggle'
            self.element.trigger('toggle');
        });
    },

    _load: function() {
        var self    = this;
        var opts    = self.options;
        var url     = self.$a.data('load.collapsable');

        self._abort();

        if ((! url) || self.$a.data('cache.collapsable'))
        {
            return;
        }

        // Load remote content.
        self.xhr = $.ajax($.extend({}, opts.ajaxOptions, {
            url:     url,
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
        cookiePath: null,   // Cookie path (defaults to window.location.pathname)
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
        self.element
                .find('.ui-optionGroups')
                    .optionGroups({
                        cookiePath: opts.cookiePath,
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
            var cookieOpts  = {
                path: (opts.cookiePath === null
                        ? window.location.pathname
                        : opts.cookiePath)
            };
            //e.preventDefault();

            if (window.location.protocol === 'https')
            {
                cookieOpts.secure = true;
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
                $.log("Add Cookie: name[%s], value[%s]",
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
        cookiePath: null,   // Cookie path (defaults to window.location.pathname)
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
            var cookieOpts  = {
                path: (opts.cookiePath === null
                        ? window.location.pathname
                        : opts.cookiePath)
            };
            if (window.location.protocol === 'https')
            {
                cookieOpts.secure = true;
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
        var params  = opts.jsonRpc.params;
        
        params.str  = self.$input.autocomplete('option', 'term');

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
                        /* On change of the PerPage select:
                         *  - set a cookie for the %ns%PerPage value...
                         */
                        $.log("Add Cookie: name[%s], value[%s]",
                              this.name, this.value);
                        $.cookie(this.name, this.value);

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

        if (opts.hiddenVars !== null)
        {
            // Also include any hidden input values in the URL.
            $.each(opts.hiddenVars, function(name,val) {
                url += '&'+ name +'='+ val;
            });
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
    _create: function() {
        var self        = this;
        var opts        = self.options;

        self._init_itemList();

        self._paneInit();
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

        // Remove added elements
        self.$itemList.itemList('destroy');

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
        namespace:  '',
        objClass:   null,
        dimOpacity: 0.5
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
        var objClass    = opts.objClass;

        // Items
        self.$items = self.element.find('li > form');

        // Group Headers
        self.$headers = self.element.find('.groupHeader .groupType');

        if (objClass === null)
        {
            /* Determine the type/class of item by the CSS class of the
             * representative form
             */
            objClass = opts.objClass = self.$items.attr('class');
        }

        if (self.$items.length > 0)
        {
            self.$items[objClass]({
                'deleted': function(e, data) {
                    // Remove this item
                    self._itemDeleted( $(this) );
                }
            });
        }

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

    _itemDeleted: function($item)
    {
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
                $item.item('destroy');
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

        // Unbind events
        self.$headers.unbind('hover');

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
 *      (application/views/scripts/post/index-partial.phtml)
 *
 *      - conversion of markup for suggestions to ui.tabs instance(s) 
 *        possibly containing connexions.collapsible instance(s);
 *
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

        tags:       null,
        url:        null,

        // taggedOn and updateOn are not user editable

        /* An element or element selector to be used to present general status
         * information.  If not provided, $.notify will be used.
         */
        $status:    null,

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

        // Widget state
        enabled:    true
    },

    /** @brief  Initialize a new instance.
     *
     *  @triggers:
     *      'enabled'
     *      'disabled'
     *      'saved'
     *      'canceled'
     *      'complete'
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
        opts.$userId      = self.element.find('input[name=userId]');
        opts.$itemId      = self.element.find('input[name=itemId]');

        // Text fields
        opts.$name        = self.element.find('input[name=name]');
        opts.$url         = self.element.find('input[name=url]');
        opts.$description = self.element.find('textarea[name=description]');
        opts.$tags        = self.element.find('textarea[name=tags]');

        // Non-text fields
        opts.$favorite    = self.element.find('input[name=isFavorite]');
        opts.$private     = self.element.find('input[name=isPrivate]');
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
        opts.$tags.autocomplete({
            source: function(req, rsp) {
                $.log('connexions.bookmarkPost::$tags.source('+ req.term +')');
                return self._autocomplete(req, rsp);
            },
            change: function(e, ui) {
                $.log('connexions.bookmarkPost::$tags.change( "'
                        + opts.$tags.val() +'" )');
                self._highlightTags();
            },
            close: function(e, ui) {
                // A tag has been completed.  Perform highlighting.
                $.log('connexions.bookmarkPost::$tags.close()');
                self._highlightTags();
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

        /* (Re)size all 'ui-field-info' elements to match their corresponding
         * input field
         */
        opts.$required.each(function() {
            var $input = $(this);

            $input.next().css('width', $input.css('width'));
        });

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

        opts.name        = opts.$name.val();
        opts.description = opts.$description.val();
        opts.tags        = opts.$tags.val();

        opts.isFavorite  = opts.$favorite.checkbox('isChecked');
        opts.isPrivate   = opts.$private.checkbox('isChecked');

        opts.url         = opts.$url.val();

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
    },

    _setFormFromState: function()
    {
        // Set the current widget state to the values of it's sub-components
        var self    = this;
        var opts    = self.options;

        opts.$name.val(opts.name);
        opts.$description.val(opts.description);
        opts.$tags.val(opts.tags);

        opts.$favorite.checkbox( opts.isFavorite ? 'check' : 'uncheck' );
        opts.$private.checkbox(  opts.isPrivate  ? 'check' : 'uncheck' );

        opts.$url.val(opts.url);

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
            opts.$rating.stars('value', opts.rating);
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
                /* We have a valid URL.  If any of name, description, or tags
                 * are empty, perform a HEAD request to fill in target-based
                 * suggestions.
                 */
                if ( (! opts.$name.input('hasChanged')) ||
                     (! opts.$description.input('hasChanged')) ||
                     (! opts.$tags.input('hasChanged')) )
                {
                    self._headers(opts.$url.val());
                }
            }
        };

        var _validate_form  = function() {
            self.validate();
        };

        var _tagInput       = function( event ) {
            var keyCode = $.ui.keyCode;
            if ( event.keyCode === $.ui.keyCode.COMMA)
            {
                // This is the end of a tag -- treat it as a 'select' event
                // and close the menu
                var menu    = opts.$tags.autocomplete('widget');

                //event.preventDefault();
                //event.stopPropagation();
                opts.$tags.autocomplete('close');
            }
        };

        /* Context bind this function in 'self/this' so we can use it
         * outside of this routine.
         */
        self._tagClick = function( event ) {
            event.preventDefault();
            event.stopPropagation();

            var $el     = $(this);
            var tag     = $el.text();
            var tags    = opts.$tags.val();

            if ($el.hasClass('selected'))
            {
                // De-select / remove
                var re  = new RegExp('\\s*'+ tag +'\\s*[,]?');
                tags    = tags.replace(re, '');
            }
            else
            {
                // Select / add
                if (! tags.match(/,\s*$/))
                {
                    tags += ', ';
                }
                tags += tag;
            }

            opts.$tags.val(tags);
            self._highlightTags();
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
        opts.$rating.bind('change.bookmarkPost',
                                                _validate_form);

        opts.$cte.bind('validation_change.bookmarkPost',
                                                _validation_change);

        opts.$save.bind('click.bookmarkPost',   _save_click);
        opts.$cancel.bind('click.bookmarkPost', _reset_click);//_cancel_click);
        opts.$reset.bind('click.bookmarkPost',  _reset_click);

        opts.$url.bind('validation_change.bookmarkPost',
                                                _url_change);

        opts.$tags.bind('keydown.bookmarkPost', _tagInput);

        opts.$suggestions.find('.cloud .cloudItem a')
                    .bind('click.bookmarkPost', self._tagClick);

        _validate_form();
    },

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
            id:     { userId: opts.userId, itemId: opts.itemId }
        };

        // Include all fields that have changed.
        if (opts.$name.val() !== opts.name)
        {
            params.name = opts.$name.val();
            nonEmpty    = true;
        }

        if (opts.$description.val() !== opts.description)
        {
            params.description = opts.$description.val();
            nonEmpty           = true;
        }

        if ( (opts.$tags.length > 0) &&
             (opts.$tags.val() !== opts.tags) )
        {
            params.tags = opts.$tags.val();
            nonEmpty    = true;
        }

        if (opts.$favorite.checkbox('isChecked') !== opts.isFavorite)
        {
            params.isFavorite = opts.$favorite.checkbox('isChecked');
            nonEmpty          = true;
        }

        if (opts.$private.checkbox('isChecked') !== opts.isPrivate)
        {
            params.isPrivate = opts.$private.checkbox('isChecked');
            nonEmpty         = true;
        }

        if ( (opts.$rating.length > 0) &&
             (opts.$rating.stars('value') !== opts.rating) )
        {
            params.rating = opts.$rating.stars('value');
            nonEmpty      = true;
        }

        if (opts.$url.val() !== opts.url)
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

        // If no itemId was provided, use the final URL.
        if (params.id.itemId === null)
        {
            params.id.itemId = (params.url !== undefined
                                ? params.url
                                : opts.url);
        }

        if (opts.apiKey !== null)
        {
            params.apiKey = opts.apiKey;
        }

        self.element.mask();

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'bookmark.update', params, {
            success:    function(data, textStatus, req) {
                if (data.error !== null)
                {
                    self._status(false,
                                 'Bookmark update failed',
                                 data.error.message);

                    return;
                }

                self._status(true,
                             'Bookmark update succeeded',
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
                self._trigger('saved',    null, data.result);
                self._trigger('complete');
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
                opts.$name.trigger('blur');
            }
        }

        if ( ! opts.$name.input('hasChanged') )
        {
            // See if there is a '<meta name="description">'
            var $desc   = headers.meta.filter('meta[name=description]');
            if ($desc.length > 0)
            {
                opts.$description.val($desc.attr('content') );
                opts.$description.trigger('blur');
            }
        }

        if ( ! opts.$tags.input('hasChanged') )
        {
            // See if there is a '<meta name="keywords">'
            var $keywords   = headers.meta.filter('meta[name=keywords]');
            if ($keywords.length > 0)
            {
                opts.$tags.val($keywords.attr('content') );
                opts.$tags.trigger('blur');
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

                    return;
                }

                if (data.result === null)
                {
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
                //self.headersUrl = null;
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
        $.ajax({
            url:    ($.registry('urls')).base +'/post/',
            data:   {
                format: 'partial',
                part:   'main-tags-recommended',
                url:    url
            },
            success: function(data) {
                var $content    = opts.$suggestions
                                        .find('#suggestions-tags '
                                                +'.recommended .content');

                // Unbind current tag click handler
                opts.$suggestions.find('.cloud .cloudItem a')
                    .unbind('.bookmarkPost');

                $content.html( data );

                // Re-bind tag click handler to the new content
                opts.$suggestions.find('.cloud .cloudItem a')
                    .bind('click.bookmarkPost', self._tagClick);

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
        var $cloudTags  = opts.$suggestions.find('.cloud .cloudItem a');

        // Remove any existing highlights
        $cloudTags.filter('.selected').removeClass('selected');

        // Highlight any currently selected tags.
        var tags    = opts.$tags.val();
        var nTags   = tags.length;
        var tag     = null;

        if (nTags < 1)
        {
            return;
        }

        tags  = tags.split(/\s*,\s*/);
        nTags = tags.length;
        for (var idex = 0; idex < nTags; idex++)
        {
            tag = tags[idex].toLowerCase();
            if (tag.length < 1)
            {
                continue;
            }

            tag = tag.replace('"', '\"');
            $.log('connexions.bookmarkPost::_highlightTags('+ tag +')');

            $cloudTags.filter(':contains("'+ tag +'")').addClass('selected');
        }
    },

    _autocomplete: function(request, response)
    {
        var self    = this;
        var opts    = self.options;
        var params  = {
            id: { userId: opts.userId, itemId: opts.itemId }
        };


        /* If no itemId was provided (or the URL has changed), use the current
         * URL value.
         */
        if ( (params.id.itemId === null) ||
             (opts.$url.val()       !== opts.url) )
        {
            // The URL has changed -- pass it in
            params.id.itemId = opts.$url.val();
        }

        params.str = opts.$tags.autocomplete('option', 'term');

        $.jsonRpc(opts.jsonRpc, 'bookmark.autocompleteTag', params, {
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

        if (hasChanged && isValid)
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
            (opts.$favorite.checkbox('hasChanged') ||
             opts.$private.checkbox('hasChanged')  ||
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
        opts.$inputs.unbind('.bookmarkPost');
        opts.$favorite.unbind('.bookmarkPost');
        opts.$private.unbind('.bookmarkPost');
        opts.$rating.unbind('.bookmarkPost');
        opts.$cte.unbind('.bookmarkPost');
        opts.$save.unbind('.bookmarkPost');
        opts.$cancel.unbind('.bookmarkPost');
        opts.$reset.unbind('.bookmarkPost');

        opts.$url.unbind('.bookmarkPost');
        opts.$tags.unbind('.bookmarkPost');

        opts.$suggestions.find('.cloud .cloudItem a')
                    .unbind('.bookmarkPost');

        // Remove added elements
        opts.$favorite.checkbox('destroy');
        opts.$private.checkbox('destroy');
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

            self._performUpdate();
        };

        // Handle item-edit
        var _edit_click  = function(e) {
            if (self.options.enabled === true)
            {
                // Popup a dialog with a post form for this item.
                var formUrl;
                try
                {
                    formUrl = $.registry('urls').base +'/post'
                            +       '?format=partial'
                            +       '&part=main'
                            +       '&url='+ opts.url
                            +       '&excludeSuggestions=true';
                }
                catch(err)
                {
                    // return and let the click propagate
                    return;
                }

                $.get(formUrl,
                      function(data) {
                        self._showBookmarkDialog('Edit', data);
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

            // Present a confirmation dialog and delete.
            var html    = '<div class="confirm">'
                        /*
                        +  '<span class="ui-icon ui-icon-alert" '
                        +        'style="float:left; margin:0 7px 20px 0;">'
                        +  '</span>'
                        */
                        +  'Really delete?<br />'
                        +  '<button name="yes">Yes</button>'
                        +  '<button name="no" >No</button>'
                        + '</div>';
            var $div    = $(html);

            self.$delete.after( $div );
            self.$delete.attr('disabled', true);

            $div.find('button[name=yes]').click(function(e) {
                self.$delete.removeAttr('disabled');
                $div.remove();

                self._performDelete();
            });
            $div.find('button[name=no]').click(function() {
                self.$delete.removeAttr('disabled');
                $div.remove();
            });
        };

        // Handle save-delete
        var _save_click  = function(e) {
            if (self.options.enabled === true)
            {
                // Popup a dialog with a post form for this item.
                var formUrl;
                try
                {
                    formUrl = $.registry('urls').base +'/post'
                            +       '?format=partial'
                            +       '&part=main'
                            +       '&url='+ opts.url
                            // Include suggestions
                            //+       '&excludeSuggestions=true'
                            ;
                }
                catch(err)
                {
                    // return and let the click propagate
                    return;
                }

                $.get(formUrl,
                      function(data) {
                        self._showBookmarkDialog('Save', data);
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
        self.$favorite.bind('click.bookmark', _update_item);
        self.$private.bind('click.bookmark',  _update_item);
        self.$rating.bind('click.bookmark',   _update_item);
        */

        self.element.bind('change.bookmark',    _update_item);

        self.$edit.bind('click.bookmark',       _edit_click);
        self.$delete.bind('click.bookmark',     _delete_click);
        self.$save.bind('click.bookmark',       _save_click);
    },

    _performDelete: function( )
    {
        var self    = this;
        var opts    = self.options;

        if (opts.enabled !== true)
        {
            return;
        }

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
            id:     { userId: opts.userId, itemId: opts.itemId }
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
    },

    _showBookmarkDialog: function(title, html)
    {
        html = '<div class="ui-validation-form" style="padding:0;">'
             +  '<div class="userInput lastUnit">'
             +   html
             +  '</div>'
             + '</div>';

        var $form   = $(html);

        //$form.find('form').bookmarkPost();

        $form.dialog({
            autoOpen:   true,
            title:      title +' bookmark',
            width:      480,
            resizable:  false,
            modal:      true,
            open:       function(event, ui) {
                $form.find('form').bookmarkPost({
                    saved:      function(event, data) {
                        /* Update the presented bookmark with the newly saved
                         * data.
                         */
                        var a   = 1;
                    },
                    complete:   function() {
                        $form.dialog('close');
                    }
                });
            },
            close:      function(event, ui) {
                $form.dialog('destroy');
                $form.remove();
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

        self.$edit        = self.element.find('.control .item-edit');
        self.$delete      = self.element.find('.control .item-delete');

        /********************************
         * Instantiate our sub-widgets
         *
         */

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
            if (self.options.enabled === true)
            {
                self._showModifyDialog();
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

            // Present a confirmation dialog and delete.
            var html    = '<div class="confirm">'
                        /*
                        +  '<span class="ui-icon ui-icon-alert" '
                        +        'style="float:left; margin:0 7px 20px 0;">'
                        +  '</span>'
                        */
                        +  'Really delete?<br />'
                        +  '<button name="yes">Yes</button>'
                        +  '<button name="no" >No</button>'
                        + '</div>';
            var $div    = $(html);

            self.$delete.after( $div );
            self.$delete.attr('disabled', true);

            $div.find('button[name=yes]').click(function(e) {
                self.$delete.removeAttr('disabled');
                $div.remove();

                self._performDelete();
            });
            $div.find('button[name=no]').click(function() {
                self.$delete.removeAttr('disabled');
                $div.remove();
            });
        };

        /**********************************************************************
         * bind events
         *
         */

        self.element.bind('change.user',    _update_item);

        self.$edit.bind('click.user',       _edit_click);
        self.$delete.bind('click.user',     _delete_click);
    },

    _performDelete: function( )
    {
        var self    = this;
        var opts    = self.options;

        if (opts.enabled !== true)
        {
            return;
        }

        var params  = {
            id: { userId: opts.userId }
        };

        // Perform a JSON-RPC call to perform the update.
        $.jsonRpc(opts.jsonRpc, 'user.delete', params, {
            success:    function(data, textStatus, req) {
                if ( (! data) || (data.error !== null))
                {
                    $.notify({
                        title: 'User delete failed',
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
                    title: 'User delete failed',
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

    _showModifyDialog: function()
    {
        /* :TODO: Create and present a dialog (or in-line editor) for
         *        modification
         */
        return;
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
        self.$edit.unbind('.user');
        self.$delete.unbind('.user');

        // Remove added elements
    }
});


}(jQuery));

