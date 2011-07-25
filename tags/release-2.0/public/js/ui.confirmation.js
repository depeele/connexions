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
