/** @file
 *
 *  Based on jquerytag (www.faithkadirakin.com/dev/jquerytag/) adjusted to
 *  fit nicely into jquery.ui, convert "tags" to clickable items.
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false, clearTimeout:false, setTimeout:false */
(function($) {

$.widget("ui.tagInput", $.ui.input, {
    version: "0.0.1",
    options: {
        // Defaults
        separator:      ',',
        unique:         true,
        addOnEnter:     true,
        validation:     '!empty',
        cssClass:       {
            container:  'tagInput',
            origInput:  'rawInput',
            list:       'tagList',
            item:       'tag',
            remove:     'delete',
            activeInput:'activeInput',
            measure:    'measureInput'
        }
    },

    /** @brief  Initialize a new instance.
     */
    _init: function() {
        var self        = this;
        var opts        = self.options;

        self.trimRe = new RegExp('^\s+|\s+$', 'g');
        self.sepRe  = new RegExp('\s*'+ opts.separator +'\s*');
        self.tags   = [];
        self.tagStr = '';

        self.element.addClass( opts.cssClass.origInput)
                    .wrap( "<div class='"+ opts.cssClass.container +"' />" );

        self.$container = self.element.parent();

        // Add a <ul> above the <input> to hold converted tags as <li> items
        self.$tags = $('<ul/>').addClass(opts.cssClass.list)
                               .appendTo( self.$container )
                               .css({
                                    'padding-left': self.element
                                                        .css('padding-left'),
                                    'padding-right':self.element
                                                        .css('padding-right'),
                                    'padding-top':  self.element
                                                        .css('padding-top'),
                                    'padding-bottom':self.element
                                                        .css('padding-bottom')
                               });

        /* Include a hidden li that will be usee to determine the proper width
         * given the current input characters.
         *
         * Start by measuring 'm', which will be used as the minimum width.
         */
        self.$measureLi = $('<li/>').addClass(opts.cssClass.measure)
                                    .appendTo( self.$tags );
        self.mWidth = self.$measureLi.html('m').width() + 2;
        self.$measureLi.empty();

        // Establish our initial value
        self.val( self.element.val() );

        // Invoke our super-class
        $.ui.input.prototype._init.apply(this, arguments);
    },

    _bindEvents: function() {
        var self        = this;
        var opts        = self.options;

        /* Bind our event handlers so we have the option of squelching events
         * from reaching our super-class (e.g. keydown).
         *
         * Event handlers
         */
        var _keydown    = function(e) {
        };

        var _resize     = function(e) {
            self.$tags.css({
                        /*  leave room for the resize handle since self.$tags
                         *  sits above the input area in z-order.
                         */
                width:  self.element.innerWidth() - 10,
                height: self.element.innerHeight()
            });
        };
        var _click    = function(e) {
            // Trigger 'focus' on the underlying input element
            self.element.trigger('focus');
        };
        var _focus    = function(e) {
            e.stopPropagation();
            e.preventDefault();
            self._ensureInput();
            self.$input.trigger('focus');
        };

        var _inputWidth = function() {
            var val     = self.$input.val();

            // Assign to the measuring li
            self.$measureLi.html( val );
            var width   = self.$measureLi.width() + self.mWidth;

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
                case 8:     // backspace
                    self.$inputLi.prev().remove();
                    break;

                case 46:    // delete
                    self.$inputLi.prev().remove();
                    break;

                case 37:    // left arrow
                case 38:    // up array
                    // Move the input area to the left
                    self.$inputLi.prev().before( self.$inputLi );
                    self.$input.focus();
                    break;

                case 39:    // right arrow
                case 40:    // down array
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
                 (opts.addOnEnter && (key === 13)) )
            {
                self._addTag();
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

            self._addTag();

            setTimeout(function() {
                var $li = self.$inputLi;
                self.$inputLi = self.$input = null;
                if (! $li)
                {
                    return;
                }

                /*
                $.log("ui.tagInput::_inputBlur: timeout, remove [ "
                            + $li.length +" ] '"
                            + opts.cssClass.activeInput +"' inputs");
                // */

                $li.remove();
            }, 5);
        };

        // Event bindings
        self.element
                .bind('keydown.uitaginput', _keydown)
                .bind('resize.uitaginput',  _resize)
                .bind('focus.uitaginput',   _focus);

        self.$tags
                .bind('click.uitaginput',   _click);

        // Delegate relavant input events
        self.$tags.delegate('.activeInput','keydown.uitaginput', _inputKeydown)
                  .delegate('.activeInput','keyup.uitaginput',   _inputKeyup)
                  .delegate('.activeInput','keypress.uitaginput',_inputKeypress)
                  .delegate('.activeInput','blur.uitaginput',    _inputBlur);

        /* Since textarea elements can be resized in chrome, monitor 'mouseup'
         * events on body and, when triggered, ensure that self.$tags mirrors
         * the size of the underlying textarea.
         */
        $('body').bind('mouseup.uitaginput', _resize);


        // Finally, invoke our super-class
        $.ui.input.prototype._bindEvents.apply(this, arguments);

        /* Trigger a 'resize' event on our input element to ensure that
         * self.$tags mirros its size.
         */
        self.element.trigger('resize');
    },

    /** @brief  Ensure that there is an input area within $tags that can be
     *          used to accept user input.
     */
    _ensureInput: function() {
        var self        = this;
        var opts        = self.options;

        self.$inputLi = self.$tags.find('.'+ opts.cssClass.activeInput );
        if (self.$inputLi.length < 1)
        {
            self.$inputLi   = $('<li/>').addClass(opts.cssClass.activeInput)
                                        .appendTo( self.$tags );
            self.$input     = $('<input type="text" />')
                                        .appendTo( self.$inputLi )
                                        .width( self.mWidth );
        }
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
             (opts.unique && (self.tags.indexOf(val) > -1)) )
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

    /** @brief  If the current value of the tag input control is non-empty,
     *          add a new tag.
     */
    _addTag: function() {
        var self    = this;
        var opts    = self.options;
        var val     = (self.$input ? $.trim(self.$input.val()) : '');

        self.$measureLi.html( '' );

        if (self.$input)
        {
            // Reset the input value
            self.$input.val('').width( self.mWidth );
        }

        /*
        $.log("ui.tagInput::_addTag: "
                + "val[ "+ val +" ]");
        // */

        var $tag    = self._createTag( val );
        if (! $tag)
        {
            return;
        }

        if (self.$input)
        {
            // Insert the new tag
            self.$input.closest('li').before( $tag );

            // Re-focus the input
            self.$input.focus();
        }
        else
        {
            self.$tags.append( $tag );
        }

        self._updateTags();
    },

    /** @brief  Update the tags and tagStr based upon the current items in
     *          $tags
     */
    _updateTags: function() {
        var self    = this;
        var opts    = self.options;

        self.tags   = [];
        self.$tags.find('.'+opts.cssClass.item +' > span')
                  .each(function() {
            self.tags.push( $(this).html() );
        });
        self.tagStr = self.tags.join( opts.separator );

        /*
        $.log('ui.tagInput::_updateTags: tagStr[ '+ self.tagStr +' ]');
        // */
    },

    /************************
     * Public methods
     *
     */

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

            self.tags   = $.trim(newVal).split( self.sepRe );
            self.tagStr = self.tags.join( opts.separator );

            self.$tags.find('.tag').remove();
            $.each(self.tags, function() {
                self._addTag( this );
            });
        }
        /*
        else
        {
            $.log('ui.tagInput::val(): [ '+ self.tagStr +' ]');
        }
        // */

        return self.tagStr;
    },

    /** @brief  Destroy an instance.
     */
    destroy: function() {
        var self        = this;
        var opts        = self.options;

        // Unbind
        $('body').unbind('.uitaginput');
        self.$tags.undelegate('input', '.uitaginput');
        self.element.unbind('.uitaginput');

        self.element.val = self.element.data('origValFunc');
        self.element.removeData('origValFunc');

        // Invoke our super-class
        $.ui.input.prototype.destroy.apply(this, arguments);
    }
});


}(jQuery));



