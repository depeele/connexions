/** @file
 *
 *  Javascript interface/wrapper for the presentation of a configurable pane
 *  which contains a bookmark list.
 *
 *  This is class extends connexions.pane to include unobtrusive activation of
 *  any contained, pre-rendered ul.cloud generated via
 *      view/scripts/settings/main-tags-manage-list.phtml
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      ui.position.js
 *      connexions.pane.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, setTimeout:false, clearTimeout:false, document:false */
(function($) {

$.widget("settings.tagsManagePane", $.connexions.pane, {
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
         * Instantiate our sub-widgets
         *
         */

        self.$optionsForm = self.element.find('.displayOptions form');

        self.$controls = self.element.find('.list-controls');
        self.$deletes  = self.$controls.find('button[name=delete]');
        self.$deletes.button({disabled: true});

        self.$list = self.element.find('.Item_List');

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
        self.$optionsForm.bind('change.tagsManagePane',
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

        self.$deletes.click(function(e) {
            // Attempt to delete all checked tags
            var $checked    = self.$list.find('li:not(.header,.footer) '
                                                                + ':checked')
                                        .siblings('.item');
            if ($checked.length < 1) { return; }

            self._perform_delete($checked);
        });
        self.$controls.find(':checkbox').change(function(e) {
            var $el      = $(this);
            if ($el.is(':checked'))
            {
                // Check all items
                self.$list.find('li:not(.header,.footer) :checkbox')
                            .attr('checked', true);
                self.$deletes.button('enable');
            }
            else
            {
                // Uncheck all items
                self.$list.find('li:not(.header,.footer) :checkbox')
                            .removeAttr('checked');
                self.$deletes.button('disable');
            }
        });

        /* On any checkbox click, see if the 'delete' button should be
         * disabled or enabled.
         *
         * :NOTE: Do NOT use 'change' since we directly set/remote the
         *        'checked' attribute when the checkbox in the header/footer is
         *        clicked.
         */
        self.$list.delegate(':checkbox', 'click', function(e) {
            var $checked    = self.$list.find('li:not(.header,.footer) '
                                                                + ':checked');
            if ($checked.length > 0)
            {
                self.$deletes.button('enable');
            }
            else
            {
                self.$deletes.button('disable');
            }
        });

        // Delegate any click within a '.control' element
        self.element.delegate('.item-edit, .item-delete',
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
              .width( width * 2.5 );

        // Insert and position
        $div.appendTo( $li )
            .position({
                of:     $a,
                my:     'left top',
                at:     'left top',
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

        /* method:  user.deleteTags,
         * tags:    id,
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

        var $li = $el.parents('li:first');
        var $a  = $li.find('.item:first');

        $ctl.confirmation({
            question:   'Really delete?',
            primary:    'confirm',
            position:   {
                my: 'left middle',
                at: 'left middle'
            },
            confirmed:  function() {
                self._perform_delete($a);
            },
            closed:     function() {
                $ctl.removeAttr('disabled');
            }
        });
    },

    /** @brief  Item deletion has been confirmed, attempt to delete the
     *          identified item.
     *  @param  $a      The jQuery/DOM anchor representing the tag(s) to be
     *                  deleted.
     */
    _perform_delete: function($a) {
        var self    = this;
        var opts    = self.options;
        var $li     = $a.parents('li:first');
        var tags    = [];
        var $aMap   = {};

        /* Gather the tags to be deleted along with generating a map between
         * tag and the matching jQuery/DOM node.
         */
        $a.each(function() {
            var $el = $(this);
            var id  = $el.data('id');
            tags.push( id );
            $aMap[ id ] = $el;
        });

        /* method:  user.deleteTags,
         * tags:    'tag, tag, tag, ...'
         *
         * Service Returns:
         *  { %tag% => %status == true | message string%, ... }
         */
        var params  = {
            tags:   tags.join(',')
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

                // Consolidate all errors and successes
                var errors      = [];
                var successes   = [];
                $.each(data.result, function( tag, val ) {
                    if (val !== true)
                    {
                        errors.push( tag +': '+ val );

                        /* Remove this tag from the $aMap which will be used to
                         * remove successfully deleted items from our presented
                         * list.
                         */
                        delete $aMap[ tag ];
                    }
                    else
                    {
                        successes.push( tag );
                    }
                });

                if (errors.length > 0)
                {
                    // Report any deletion failures
                    $.notify({
                        title: 'Tag deletion failed',
                        text:  $.map(errors, function(val, idex) {
                                    return '<p class="error">'+ val +'</p>';
                               }).join('')
                    });
                }

                if (successes.length > 0)
                {
                    // Report any deletion successes
                    $.notify({
                        title: 'Tag'+ (successes.length > 1 ? 's' :'')
                                    +' deleted',
                        text:  successes.join(', ')
                    });

                    /* Initiate the removal of all successfully deleted tags
                     * from our presented list.
                     */
                    $.each(successes, function(idex, val) {
                        var $elA    = $aMap[ val ];
                        if ($elA === undefined) { return; }

                        var $elLi   = $elA.parents('li:first');
                        $elLi.hide('fast', function() {
                            $(this).remove();
                        });
                    });
                }

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

    /************************
     * Public methods
     *
     */

    /** @brief  Get/set the current filter.
     *  @param  filter      If provided, set the filter.
     *
     *  @return The current/new filter.
     */
    filter: function(filter) {
        var self    = this;
        var opts    = self.options;
        if (opts.hiddenVars === null)   opts.hiddenVars = {};

        if (filter === undefined)
        {
            filter = opts.hiddenVars.filter;
        }
        else
        {
            opts.hiddenVars.filter = filter;
        }

        return filter;
    },

    /** @brief  Remove any filter.
     *
     *  @return this for a fluent interface.
     */
    removeFilter: function() {
        var self    = this;
        var opts    = self.options;
        if (opts.hiddenVars === null)   opts.hiddenVars = {};

        delete opts.hiddenVars.filter;

        return this;
    },

    destroy: function() {
        var self    = this;

        // Unbind events
        self.$optionsForm.unbind('.tagsManagePane');

        // Destroy sub-widgets
        self.$deletes.button('destroy');

        // Invoke our super-class
        $.connexions.pane.prototype.destroy.apply(this, arguments);
    }
});


}(jQuery));




