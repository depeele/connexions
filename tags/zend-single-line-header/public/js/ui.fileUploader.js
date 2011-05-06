/** @file
 *
 *  Javascript interface/wrapper for selection and upload of an image.
 *
 *  This is primarily a class to provide unobtrusive activation of a
 *  pre-renderd image selection form
 *      (application/views/scripts/settings/main-account-info.phtml)
 *
 *  <div class='avatar-file'>
 *   <form  action='avatar-upload.php'
 *          method='POST'
 *         enctype="multipart/form-data">
 *    <input name='avatarFile' type='file' class='text' />
 *   </form>
 *  </div>
 *
 *  $('.avatar-file input[name=avatarFile]').fileUploader();
 *
 *  Requires:
 *      ui.core.js
 *      ui.widget.js
 *
 *
 *  Simplified from jquery.fileUploader by John Lanz (http://pixelcone.com) to
 *  deal with just a single file that is uploaded automatically.  Also modified
 *  to be a jQuery-ui widget.
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false */
(function($) {

$.widget("ui.fileUploader", {
    version: "0.0.1",

    /* Remove the strange ui.widget._trigger() class name prefix for events.
     *
     * If you need to know which widget the event was triggered from, either
     * bind directly to the widget or look at the event object.
     */
    widgetEventPrefix:      '',

    options: {
        imageLoader:        '',
        allowedExtension:   'jpg|jpeg|gif|png',

        // Completion callbacks
        success:            function() {},
        error:              function() {},
        complete:           function() {}
    },

    /** @brief  Create a new instance.
     *
     *  @triggers:
     *      'enabled'
     *      'disabled'
     */
    _create: function()
    {
        var self        = this;
        var opts        = self.options;

        opts.reValid    = new RegExp(opts.allowedExtension + '$', 'i');

        /********************************
         * Locate our pieces.
         *
         */
        opts.$form          = self.element.closest('form');

        opts.$form.addClass('ui-fileUploader');

        // Include the upload iframe and target the form to it.
        var iframeId    = 'fileUploader_iframe';
        var iframe      = '<iframe id="'+ iframeId +'" '
                        +       'name="'+ iframeId +'" '
                        +        'src="about:blank" '
                        +      'style="display:none">'
                        + '</iframe>';
        opts.$iframe = $(iframe);
        self.element.after( opts.$iframe );

        opts.$form.attr('target', iframeId);

        // Include the upload progress image.
        var status      = '<div class="status">'
                        +  '<div class="message"></div>'
                        +  '<div class="progress">';

        if ($.trim(opts.imageLoader) !== '')
        {
            status      += '<img src="'+ opts.imageLoader +'" '
                        +       'alt="Uploader" />';
        }
        status          += '</div>'
                        + '</div>';

        opts.$status = $( status );
        self.element.after( opts.$status );

        opts.$message  = opts.$status.find('.message');
        opts.$progress = opts.$status.find('.progress');

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

        self.element.bind('change.fileUploader', function(e) {
            // On change, begin an upload.
            var file    = self._validate();
            if (file === false)
            {
                // Invalid file
                opts.$status.removeClass('success in-progress')
                             .addClass('error');
                opts.$message.text('Invalid file');

                self.element.val('');
                return false;
            }

            opts.$status.removeClass('success error in-progress');
            self._upload();
        });
    },

    /** @brief  Perform an immediate upload.
     */
    _upload: function()
    {
        var self        = this;
        var opts        = self.options;

        opts.$status.removeClass('success error')
                     .addClass('in-progress');
        opts.$message.text('Uploading...');
        opts.$progress.show();

        opts.$form.submit();
        opts.$iframe.load(function(e) {
            var $res    = opts.$iframe.contents();
            var status  = $res.find('#status').text();
            var msg     = $res.find("#message").text();

            opts.$status.removeClass('in-progress');
            opts.$message.text(msg);

            if (status === 'success')
            {
                var url = $res.find("#url").text();

                opts.$status.addClass('success');

                self._trigger('success', e, { msg: msg, url: url });
            }
            else
            {
                opts.$status.addClass('error');

                self._trigger('error', e, { msg: msg });
            }
            self._trigger('complete', e, { msg: msg });

            opts.$progress.hide();
        });
    },

    _validate: function()
    {
        var self    = this;
        var opts    = self.options;
        var file    = self.element.val();

        if (file.indexOf('/') > -1)
        {
            file = file.substring(file.lastIndexOf('/') + 1);
        }
        else if (file.indexOf('\\') > -1)
        {
            file = file.substring(file.lastIndexOf('\\') + 1);
        }

        if (opts.reValid.test(file))
        {
            return file;
        }

        return false;
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
        self.element.unbind('.fileUploader');

        // Remove added elements
        opts.$form.removeAttr('target') 
                  .removeClass('ui-fileUploader');

        opts.$iframe.remove();
        opts.$status.remove();
    }
});

}(jQuery));



