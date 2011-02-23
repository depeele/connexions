/** @file
 *
 *  Javascript interface/wrapper for selection of an avatar image.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-renderd avatar selection form
 *      (application/views/scripts/settings/main-account-info.phtml)
 *
 *  <div id='account-info-avatar'>
 *    <div class='avatar-full'><img /></div>    // Full image preview
 *    <div class='avatar-crop'><img /></div>    // Image crop preview
 *
 *    <div class='avatar-type-selector'>
 *     <input type='radio' name='avatar-type' value='file'>Upload</input>
 *     <input type='radio' name='avatar-type' value='url'>Url</input>
 *    </div>
 *
 *    <div class='avatar-file'>
 *     <form  action='avatar-upload.php'
 *            method='POST'
 *           enctype="multipart/form-data">
 *      <input name='avatarFile' type='file' class='text' />
 *      <div class='buttons'>
 *       <input type='submit' value='Upload' id='pxUpload' />
 *       <input type='reset'  value='Clear'  id='pxClear' />
 *      </div>
 *     </form>
 *    </div>
 *
 *    <div class='avatar-url'>
 *     <label  for='avatarUrl'>URL</label>
 *     <input name='avatarUrl' type='text' />
 *    </div>
 *
 *  </div>
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *
 *      jquery.Jcrop.js
 *      jquery.fileUploader.js
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
        width:          400,
        height:         450,
        minWidth:       400,
        minHeight:      450,
        imageLoader:    'images/image_upload.gif',

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
        opts.$previewFull   = self.element.find('.avatar-full');
        opts.$previewCrop   = self.element.find('.avatar-crop');

        opts.$imageFull     = opts.$previewFull.find('img');
        opts.$imageCrop     = opts.$previewCrop.find('img');

        opts.$types         = self.element.find('.avatar-type-selection');

        opts.$inputFile     = opts.$types.find('input[name=avatarFile]');
        opts.$inputUrl      = opts.$types.find('input[name=avatarUrl]');

        opts.$buttons       = self.element.find('> .buttons button');

        opts.$buttons.button();
        opts.$types.tabs({ selected:(opts.avatar ? 1 : 0) });
        opts.$inputUrl.input({ hideLabel:true });
        opts.$inputFile.fileUploader({ imageLoader:opts.imageLoader });

        opts.avatarSizes    = {
            full:       {
                width:  0,
                height: 0
            },
            preview:    {
                width:  parseInt(opts.$previewCrop.css('width')),
                height: parseInt(opts.$previewCrop.css('height'))
            },
            max:        {
                width:  parseInt(opts.$previewFull.css('maxWidth')),
                        //opts.$previewFull.width(),
                height: parseInt(opts.$previewFull.css('maxHeight'))
                        //opts.$previewFull.height()
            }
        };

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

        /* 'success' is triggered on the $inputFile DOM element whenever
         * a valid image has been selected and successfully uploaded.
         *
         * The incoming 'ui' element contains:
         *  { msg:  %Completion message from the server%,
         *    url:  %Site-absolute URL to the uploaded image% }
         */
        opts.$inputFile
                .bind('success.avatarChooser', function(e,ui) {
                    // Update the $inputUrl, initiating jCrop and preview
                    opts.$inputUrl.input('val', ui.url);

                    // Switch to the URL tab
                    opts.$types.tabs('select', 1);
                });

        opts.$inputUrl
                .bind('validation_change.avatarChooser', function(e, isValid) {
                    if (isValid === true)
                    {
                        var $el = $(this);
                        var url = opts.$inputUrl.val();

                        self.initPreview( url );
                    }
                });

        opts.$buttons.bind('click.avatarChooser', function(e) {
            e.preventDefault();

            var $el = $(this);
            switch ($el.attr('name'))
            {
            case 'submit':  self.save();   break;
            case 'cancel':  self.close();   break;
            }
        });

        self._cropper();
    },

    _cropper: function()
    {
        var self        = this;
        var opts        = self.options;
        var size        = opts.avatarSizes;

        // Handle updating the cropped preview.
        function previewCrop(coords)
        {
            var rx  = size.preview.width  / coords.w;
            var ry  = size.preview.height / coords.h;

            // Remember these coordinates
            self.options.cropCoords = coords;
            opts.$imageCrop.css({
                width:      Math.round(rx * size.full.width)  + 'px',
                height:     Math.round(ry * size.full.height) + 'px',
                marginLeft: '-'+ Math.round(rx * coords.x) + 'px',
                marginTop:  '-'+ Math.round(ry * coords.y) + 'px'
            });
        }

        function avatarChange()
        {
            size.full.width  = opts.$imageFull.width();
            size.full.height = opts.$imageFull.height();

            // Adjust the image to fit nicely
            var diffs   = {
                width:   size.max.width  - size.full.width,
                height:  size.max.height - size.full.height
            };
            if ( (diffs.width < 0) || (diffs.height < 0) )
            {
                if (diffs.width < diffs.height)
                {
                    // Width is further off
                    opts.$imageFull.width( size.max.width );
                    opts.$imageFull.height('auto');
                }
                else
                {
                    // Height is further off
                    opts.$imageFull.height( size.max.height );
                    opts.$imageFull.width('auto');
                }

                size.full.width  = opts.$imageFull.width();
                size.full.height = opts.$imageFull.height();
            }

            /*
            opts.$previewFull.width(size.full.width);
            opts.$previewFull.height(size.full.height);
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
        }

        /******************************************************************
         * Whenever the $imageFull received a 'load' event, adjust the size to
         * fit within our full image preview area and establish a jCrop
         * instance.
         */
        opts.$imageFull.bind('load.avatarChooser', function() {
            /* Separate from the main UI thread and pause a short bit to ensure
             * that the image is fully loaded and sized.
             */
            setTimeout( function() { avatarChange(); }, 100 );
        });
    },

    /************************
     * Public methods
     *
     */

    save: function()
    {
        var self    = this;
        var opts    = self.options;
        var params  = {
            url:    opts.$inputUrl.val(),
            crop: {
                ul:     [ opts.cropCoords.x,  opts.cropCoords.y  ],
                lr:     [ opts.cropCoords.x2, opts.cropCoords.y2 ],
                width:  opts.cropCoords.w,
                height: opts.cropCoords.h
            }
        };

        self.element.mask();

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
                self.element.unmask();
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
            opts.$imageFull.width('auto');  //opts.avatarSizes.max.width);
            opts.$imageFull.height('auto'); //opts.avatarSizes.max.height);

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
        opts.$buttons.unbind('.avatarChooser');

        // Remove added elements
        opts.$types.tabs('destroy');
        //opts.$inputFile.input('destroy');
        opts.$inputUrl.input('destroy');

        // Invoke our superclass
        $.ui.dialog.prototype.destroy.call(this);
    }
}));

}(jQuery));


