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
        cssClass:       {
            container:  'tagInput',
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
        self.tags   = [];
        self.tagStr = '';

        // Assemble the widget structure
        self.$container = $('<div />').addClass(opts.cssClass.container)
                                      .addClass( self.element.attr('class') )
                                      .insertAfter( self.element );

        // Move any label INTO $container
        self.$label     = self.element.siblings('label')
                                      .appendTo( self.$container );

        // Add a <ul> above the <input> to hold converted tags as <li> items
        self.$tags = $('<ul/>').addClass(opts.cssClass.list)
                               .appendTo( self.$container )
                               ; /*
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
                               */

        /* Include a hidden li that will be usee to determine the proper width
         * given the current input characters.
         *
         * Start by measuring 'm', which will be used as the minimum width.
         */
        self.$measureLi = $('<li/>').addClass(opts.cssClass.measure)
                                    .appendTo( self.$tags );
        self.mWidth = self.$measureLi.html('m').width() + 2;
        self.$measureLi.empty();

        // Include an li to hold the active input control.
        self.$inputLi   = $('<li/>').addClass(opts.cssClass.activeInput)
                                    .appendTo( self.$tags )
                                    .hide();
        self.$input     = $('<input type="text" />')
                                    .appendTo( self.$inputLi )
                                    .width( self.mWidth );

        // Setup autocompletion if needed.
        self._setupAutocomplete();

        // Establish our initial value
        self.origValue = self.element.val();
        self.val( self.origValue );

        // Invoke our super-class (which SHOULD invoke _bindEvents()
        $.ui.input.prototype._init.apply(this, arguments);
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

        // When an autocompletion item is selected, 
        acOpts.select = function(e, ui) {
            // /*
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
            $.log("ui.tagInput::_acFocus: ");
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

        /* Bind our event handlers so we have the option of squelching events
         * from reaching our super-class.
         *
         * Event handlers
         */
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
            // Trigger 'focus'
            self.element.trigger('focus');
        };
        var _focus    = function(e) {
            e.stopPropagation();
            e.preventDefault();
            self._squelchBlur = false;

            self._activeInput_show();
        };

        var _inputWidth = function() {
            var val     = self.$input.val();

            // Assign to the measuring li
            self.$measureLi.html( val );
            var width   = self.$measureLi.width() + self.mWidth;

            // /*
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
                self._addTag();
                e.preventDefault();
                return false;
            }
        };
        var _inputBlur      = function(e) {
            // /*
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

        /* Ensure that self.$tags mirrors the original size of self.element
         * and then hide self.element.
         */
        _resize();
        self.element.hide();
    },

    /** @brief  Show the active input element.
     */
    _activeInput_show: function() {
        if (this.options.enabled)
        {
            // Hide the label and show the active input element
            this.$label.hide();
            this.$inputLi.show();

            this.$input.trigger('focus');
        }

        return this;
    },

    /** @brief  Hide the active input element.
     */
    _activeInput_hide: function() {
        // Hide the active input element
        this.$inputLi.hide();

        if (this.tagStr.length < 1)
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

        // Reset the input value
        self.$input.val('').width( self.mWidth );

        /*
        $.log("ui.tagInput::_addTag: "
                + "val[ "+ val +" ]");
        // */

        var $tag    = self._createTag( val );
        if (! $tag)
        {
            return;
        }

        // Insert the new tag
        self.$input.closest('li').before( $tag );

        // Re-focus the input
        self.$input.focus();

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

        // Mirror tagStr in the underlying input element
        self.element.val( self.tagStr );

        /*
        $.log('ui.tagInput::_updateTags: tagStr[ '+ self.tagStr +' ]');
        // */
    },

    /************************
     * Public methods
     *
     */

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

            //this.element.trigger('enabled.uiinput');
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

            //this.element.trigger('disabled.uiinput');
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

            self.tags   = $.trim(newVal).split( self.sepRe );
            self.tagStr = self.tags.join( opts.separator );

            self.$tags.find('.tag').remove();
            $.each(self.tags, function() {
                self._addTag( this );
            });

            // Mirror tagStr in the underlying input element
            self.element.val( self.tagStr );
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



