/** @file
 *
 *  Javascript interface/wrapper for selection of an avatar image.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-renderd avatar selection form
 *      (application/views/scripts/settings/main-account-info.phtml)
 *
 *  <div id='account-info-avatar'>
 *   <form>
 *    <div class='avatar-full'><img /></div>    // Full image preview
 *    <div class='avatar-crop'><img /></div>    // Image crop preview
 *
 *    <div class='avatar-type-selector'>
 *     <input type='radio' name='avatar-type' value='file'>Upload</input>
 *     <input type='radio' name='avatar-type' value='url'>Url</input>
 *    </div>
 *
 *    <div class='avatar-file'>
 *     <label  for='avatarFile'>File</label>
 *     <input name='avatarFile' type='file' />
 *    </div>
 *
 *    <div class='avatar-url'>
 *     <label  for='avatarUrl'>URL</label>
 *     <input name='avatarUrl' type='text' />
 *    </div>
 *
 *   </form>
 *  </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *      jquery.Jcrop.js
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("settings.avatarChooser", $.extend({}, $.ui.dialog.prototype, {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:    '',

    options: {
        width:      400,
        height:     400,
        minWidth:   400,
        minHeight:  400,

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
         * which is initialized from
         *      application/configs/application.ini:api
         * via
         *      application/layout/header.phtml
         */
        jsonRpc:    null
    },

    /** @brief  Create a new instance.
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
        // Mix-in the superclass options with ours
        this.options = $.extend({}, $.ui.dialog.prototype.options,
                                    this.options);

        var self            = this;
        var opts            = self.options;

        /********************************
         * Initialize jsonRpc
         *
         */
        if ( (opts.jsonRpc === null) && $.isFunction($.registry))
        {
            var api = $.registry('api');
            if (api && api.jsonRpc)
            {
                opts.jsonRpc = $.extend({}, api.jsonRpc, opts.jsonRpc);
            }
        }

        /********************************
         * Locate pieces not collected
         * by our superclass.
         */
        opts.$form          = self.element.find('form');
        opts.$previewFull   = opts.$form.find('.avatar-full');
        opts.$previewCrop   = opts.$form.find('.avatar-crop');

        opts.$imageFull     = opts.$previewFull.find('img');
        opts.$imageCrop     = opts.$previewCrop.find('img');

        opts.$types         = opts.$form.find('.avatar-type-selection');

        opts.$inputFile     = opts.$types.find('input[name=avatarFile]');
        opts.$inputUrl      = opts.$types.find('input[name=avatarUrl]');

        opts.$types.tabs({
            selected:   (opts.avatar ? 1 : 0)
        });
        opts.$form.validationForm({
            hideLabels:                 true,
            disableSubmitOnUnchanged:   false,
            validate:                   function() {self._validate.call(self);}
        });

        /********************************
         * Bind to interesting events.
         *
         */
        self._bindEvents();

        // Invoke our superclass
        $.ui.dialog.prototype._create.call(this);
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
    _init: function()
    {
        // Mix-in the superclass options with ours
        this.options = $.extend({}, $.ui.dialog.prototype.options,
                                    this.options);

        // Invoke our superclass
        $.ui.dialog.prototype._init.call(this);

        var self    = this;
        var opts    = self.options;

        /* If an avatar was provided, set it.  This will cause a
         * 'validation_change' event on $inputUrl which will (re)initialize the
         * preview information.
         */
        if (opts.avatar)
        {
            opts.$inputUrl.input('val', opts.avatar);
        }
    },


    /************************
     * Private methods
     *
     */
    _bindEvents: function()
    {
        var self        = this;
        var opts        = self.options;

        opts.$inputUrl
                .bind('validation_change.avatarChooser', function() {
                    var url = opts.$inputUrl.val();

                    self.initPreview( url );
                });

        opts.$inputFile
                .bind('change.avatarChooser', function() {
                    var url = opts.$inputFile.val();

                    /* :TODO:
                     *
                     * For local files, due to browser security issues
                     * (particularly in Chrome), we MUST upload the file to the
                     * server in order to present a preview.
                     */
                    self.initPreview( url );
                });

        opts.$form
                .bind('submit.avatarChooser', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    self.save();
                })
                .bind('cancel.avatarChooser', function(e) {
                    self.close();
                });

        /******************************************************************
         * Handle updates of the cropped, thumbnail area
         *
         */
        var fullSize    = {width:   0,
                           height:  0};
        var avatar      = {width:   parseInt(opts.$previewCrop.css('width')),
                           height:  parseInt(opts.$previewCrop.css('height'))};

        function previewCrop(coords)
        {
            var rx  = avatar.width  / coords.w;
            var ry  = avatar.height / coords.h;

            // Remember these coordinates
            //opts.cropCoords = coords;
            self.options.cropCoords = coords;
            opts.$imageCrop.css({
                width:      Math.round(rx * fullSize.width)  + 'px',
                height:     Math.round(ry * fullSize.height) + 'px',
                marginLeft: '-'+ Math.round(rx * coords.x) + 'px',
                marginTop:  '-'+ Math.round(ry * coords.y) + 'px'
            });
        }

        /******************************************************************
         * Whenever the $imageFull changes (i.e. we receive a load event),
         * adjust the size to fit within our full image preview area
         * and establish a jCrop instance.
         */
        var max = {
            width:   parseInt(opts.$previewFull.css('maxWidth')),
                     //opts.$previewFull.width(),
            height:  parseInt(opts.$previewFull.css('maxHeight'))
                     //opts.$previewFull.height()
        };

        // Whenever a new image is loaded into $imageFull, adjust...
        opts.$imageFull.bind('load.avatarChooser', function() {
            fullSize.width  = opts.$imageFull.width();
            fullSize.height = opts.$imageFull.height();

            // Adjust the image to fit nicely
            var diffs   = {
                width:   max.width  - fullSize.width,
                height:  max.height - fullSize.height
            };
            if ( (diffs.width < 0) || (diffs.height < 0) )
            {
                if (diffs.width < diffs.height)
                {
                    // Width is further off
                    opts.$imageFull.width( max.width );
                    opts.$imageFull.height('auto');
                }
                else
                {
                    // Height is further off
                    opts.$imageFull.height( max.height );
                    opts.$imageFull.width('auto');
                }

                fullSize.width  = opts.$imageFull.width();
                fullSize.height = opts.$imageFull.height();
            }

            /*
            opts.$previewFull.width(fullSize.width);
            opts.$previewFull.height(fullSize.height);
            // */


            // Destroy any existing jCrop instance
            if (opts.jcrop)
            {
                opts.jcrop.destroy();
                opts.jcrop = null;
            }

            /* Don't want to load this image AGAIN, which happens automatically
             * if we do opts.$imageFull.Jcrop() as it creates a new img tag to
             * manipulate.  For this reason, invoke $.Jcrop() directly, passing
             * in the (now loaded) source image.
             */
            opts.jcrop = $.Jcrop(opts.$imageFull[0], {
                onChange:       previewCrop,
                onSelect:       previewCrop,
                aspectRatio:    1,
                zIndex:         opts.zIndex + 5
            });
        });
    },

    _validate: function()
    {
        var self    = this;
        var opts    = self.options;
        var tab     = opts.$types.tabs('option', 'selected');
        var isValid = false;

        switch (tab)
        {
        case 0:     // File
            isValid = (opts.$inputFile.val().length > 0);
            break;

        case 1:     // URL
            isValid = (opts.$inputUrl.val().length > 0);
            break;

        }

        return isValid;
    },

    /************************
     * Public methods
     *
     */

    save: function()
    {
        var self    = this;
        var opts    = self.options;
        var tab     = opts.$types.tabs('option', 'selected');
        var params  = {
            url:    null,
            coords: opts.cropCoords
        };

        switch (tab)
        {
        case 0:     // File
            /* :TODO: Retrieve the jCrop information, and perform an upload
             * with the cropping information.
            url = opts.$inputFile.val();
             */
            break;

        case 1:     // URL
            /* :TODO: No upload required, simply save the avatar URL
             */
            params.url = opts.$inputUrl.val();
            break;
        }

        opts.$form.mask();

        $.jsonRpc(opts.jsonRpc, 'user.cropAvatar', params, {
            success: function(data) {
                if ( (! data) || (data.error !== null) )
                {
                    $.notify({
                        title:  'Avatar Update failed',
                        text:   '<p class="error">'
                              +  (data ? data.error.message : '')
                              + '</p>'
                    });
                }
                else
                {
                    // SUCCESS
                    $.notify({
                        title:  'Avatar Updated',
                        text:   ''
                    });

                    self.close();
                }
            },
            error: function(req, textStatus, err) {
                $.notify({
                    title:  'Avatar Update failed',
                    text:   '<p class="error">'
                          +  textStatus
                          + '</p>'
                });
            },
            complete: function(req, textStatus) {
                opts.$form.unmask();
            }
        });
    },

    initPreview: function(url)
    {
        var self        = this;
        var opts        = self.options;

        if (url != opts.$imageFull.attr('src'))
        {
            // Attempt to load (after re-setting width and height to auto)...
            opts.$imageFull.width('auto');  //max.width);
            opts.$imageFull.height('auto'); //max.height);

            opts.$imageFull.attr('src', url);
            opts.$imageCrop.attr('src', url);
        }
    },

    destroy: function()
    {
        var self    = this;
        var opts    = self.options;

        // Cleanup

        // Unbind events
        opts.$imageFull.unbind('.avatarChooser');
        opts.$inputUrl.unbind('.avatarChooser');
        opts.$inputFile.unbind('.avatarChooser');
        opts.$form.unbind('.avatarChooser');

        // Remove added elements
        opts.$types.tabs('destroy');
        //opts.$inputFile.input('destroy');
        opts.$inputUrl.input('destroy');

        // Invoke our superclass
        $.ui.dialog.prototype.destroy.call(this);
    }
}));

}(jQuery));


