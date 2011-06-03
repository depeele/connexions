connexions.onFirefoxLoad = function(event) {
    document.getElementById("contentAreaContextMenu")
            .addEventListener("popupshowing", function (e){
                                connexions.showFirefoxContextMenu(e);
                              }, false);
};

connexions.showFirefoxContextMenu = function(event) {
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
    var items = {
        'page':  document.getElementById("context-connexions-page"),
        'link':  document.getElementById("context-connexions-link"),
        'media': document.getElementById("context-connexions-media")
    };

    items.page.hidden  = false;
    items.link.hidden  = (! gContextMenu.onLink);
    items.media.hidden = (! gContextMenu.onImage);
};

window.addEventListener("load", function () {
                            connexions.onFirefoxLoad();
                        }, false);
