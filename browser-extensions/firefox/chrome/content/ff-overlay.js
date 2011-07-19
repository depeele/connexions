/** @file
 *
 *  The primary browser overlay.
 *
 *  Requires: chrome://connexions/connexions.js
 *  which makes available:
 *      resource://connexions/debug.js          cDebug
 *      resource://connexions/db.js             cDb
 *      resource://connexions/connexions.js     connexions
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false, plusplus:false, regexp:false */
/*global Components:false, cDebug:false, CU:false, document:false, window:false, gContextMenu:false */
CU.import('resource://connexions/debug.js');

var cOverlay = {
    load: function() {
        //cDebug.log("ff-overlay.js:load");
        document.getElementById("contentAreaContextMenu")
                .addEventListener("popupshowing", function (e){
                                    cOverlay.showContextMenu(e);
                                  }, false);
    },

    showContextMenu: function(event) {
        //cDebug.log("COverlay::showContextMenu");

        /* show or hide the menuitem based on what the context menu is on
         *  gContextMenu.isTextSelected
         *              .isContentSelected
         *              .onLink
         *              .onImage
         *              .onMailtoLink
         *              .onTextInput
         *              .onKeywordFile
         *              .linkURL        (string or function)
         *              .linkText()
         */
        /* https://developer.mozilla.org/en/XUL/PopupGuide/ContextMenus
         *                              #Determining_what_was_Context_Clicked
         * https://developer.mozilla.org/en/DOM/document.popupNode
         *
         * var element  = document.popupNode;
         * var isImage  = (element instanceof
         *                  Components.interfaces.nsIImageLoadingContent &&
         *                  element.currentURI);
         *
         */
        var items = {
            'page': document.getElementById("context-connexions-page"),
            'pageLink':
                    document
                        .getElementById("context-connexions-page-after-link"),
            'pageMedia':
                    document
                        .getElementById("context-connexions-page-after-media"),
            'link': document.getElementById("context-connexions-link"),
            'media':document.getElementById("context-connexions-media")
        };

        items.link.hidden      = (! gContextMenu.onLink);
        items.media.hidden     = (! gContextMenu.onImage);

        items.page.hidden      = ((! items.link.hidden) ||
                                  (! items.media.hidden));
        items.pageLink.hidden  = items.link.hidden;
        items.pageMedia.hidden = items.media.hidden;
    },

    unload: function() {
    }
};

window.addEventListener("load",   cOverlay.load,   false);
window.addEventListener("unload", cOverlay.unload, false);
