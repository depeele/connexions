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
 *      connexions.pane.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, setTimeout:false, clearTimeout:false */
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
    _create: function() {
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
         * Instantiate our sub-widgets
         *
         */

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

        // Delegate any click within a '.control' element
        this.element.delegate('.item-edit, .item-delete',
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

        self._placeConfirmation($ctl, $div);

        function _reEnable(e)
        {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            /* Wait a bit to remove the element so the click doesn't
             * inadvertenely hit any underlying tag element.
             */
            setTimeout(function() {
                        $ctl.removeAttr('disabled');
                        $div.remove();
                       }, 100);
        }

        var $yes    = $div.find('button[name=yes]');
        var $no     = $div.find('button[name=no]');

        $yes.click(function(e) {
            _reEnable(e);

            self._perform_delete($el);
        });
        $no.click(function(e) {
            _reEnable(e);
        });

        // Handle 'Enter' and 'ESC' in the input element
        $(document).keydown(function(e) {
            if (e.keyCode === 13)       // return
            {
                $yes.click();
            }
            else if (e.keyCode === 27)  // ESC
            {
                $no.click();
            }
        });
    },

    /** @brief  Item deletion has been confirmed, attempt to delete the
     *          identified item.
     *  @param  $el     The jQuery/DOM element that was originally clicked upon
     *                  to initiate this deletion
     *                  (i.e. the '.item-delete' element);
     */
    _perform_delete: function($el) {
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

    /** @brief  Given a control DOM element and a new confirmation DOM element,
     *          figure out the best positioning for the confirmation and append
     *          it to the parent li.
     *  @param  $ctl            The control jQuery/DOM element;
     *  @param  $confirmation   The new confirmation jQuery/DOM element;
     */
    _placeConfirmation: function($ctl, $confirmation) {
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

        $confirmation.appendTo( $li )
                     .position( pos );
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



