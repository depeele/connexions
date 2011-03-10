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
        apiKey:     null,
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

        var $options    = $ctl.find('> span');
        $options.hide();

        var cOffset     = $ctl.offset();
        var lOffset     = $li.offset();
        cOffset.right = cOffset.left + $ctl.width();
        lOffset.right = lOffset.left + $li.width();

        var pos         = {
            of: $ctl,
            // By default, my left/center at the right/center of $li
            my: 'top center',
            at: 'top center'
        };
        if (cOffset.right <= lOffset.right)
        {
            // ctl is IN $li, so set my right/center at the right/center of $li
            pos.my = 'right center';
            pos.at = 'right center';
        }

        $div.appendTo( $li )
            .position( pos );

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
                        $options.show();
                       }, 100);
        }

        $div.find('button[name=yes]').click(function(e) {
            _reEnable(e);

            self._performDelete($el);
        });
        $div.find('button[name=no]').click(function(e) {
            _reEnable(e);
        });
    },

    _performDelete: function($el) {
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

                if (data.result === null)   return;

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

    _edit_item: function($el) {
        var self    = this;
        var opts    = self.options;
        var $li     = $el.parents('li:first');
        var $a      = $li.find('.item:first');
        var tag     = $a.data('id');
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



