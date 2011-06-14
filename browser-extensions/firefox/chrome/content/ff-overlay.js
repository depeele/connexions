/** @file
 *
 *  The primary browser overlay.
 *
 *  Requires: chrome://connexions/connexions.js
 */
CU.import('resource://connexions/debug.js');

var cOverlay = {
    load: function() {
        document.getElementById("contentAreaContextMenu")
                .addEventListener("popupshowing", function (e){
                                    cOverlay.showContextMenu(e);
                                  }, false);
    },

    showContextMenu: function(event) {
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
         * var isImage  = (element instanceof Components.interfaces.nsIImageLoadingContent && element.currentURI);
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

window.addEventListener("load",   function() { cOverlay.load(); },   false);
window.addEventListener("unload", function() { cOverlay.unload(); }, false);
