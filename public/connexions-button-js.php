<?php
require_once('./bootstrap.php');
require_once(LIBRARY_PATH .'/Zend/View/Helper/ServerUrl.php');

$helper = new Zend_View_Helper_ServerUrl();

$buttonHtmlUrl = $helper->serverUrl('/connexions-button.html');

header('Content-type: text/javascript');
?>
/** @file
 *
 *  Create a simple connexions button on the including page.
 *
 *  Usage:
 *      <div id='connexions-button'></div>
 *      <script src='<?= $helper->serverUrl('/connexions-button.js') ?>'></script>
 */
(function() {
var container   = document.getElementById('connexions-button'),
    url         = window.location.href,
    title       = document.title,
    style       = {
        container:  [
            'position:relative',
            'height:20px',
            'width:90px',
            'padding:0',
            'margin:0',
            // inline-block
            'display:-moz-inline-stack',    // OLD mozilla browsers
            'display:inline-block',         // modern browsers
            // IE -- trigger 'hasLayout' and set display to inline
            'zoom:1',
            '*display:inline'
        ],
        iframe:     [
            'position:static',
            'top:0',
            'left:0',
            'margin:0',
            'border:none',
            'width:100%',
            'height:100%'
        ]
    };

// Include styling for the container
container.setAttribute('style', style.container.join(';'));

// Create the iframe
var iframe      = document.createElement('iframe');
iframe.setAttribute('allowtransparency', 'true');
iframe.setAttribute('frameborder', '0');
iframe.setAttribute('hspace', '0');
iframe.setAttribute('vspace', '0');
iframe.setAttribute('marginheight', '0');
iframe.setAttribute('marginwidth', '0');
iframe.setAttribute('tabindex', '0');
iframe.setAttribute('frameborder', '0');
iframe.setAttribute('style', style.iframe.join(';'));
iframe.src = '<?= $buttonHtmlUrl ?>'
           +    '?url='+    encodeUriComponent(url)
           +    '&title='+  encodeUriComponent(title);

// Add the iframe to the container
container.appendChild(iframe);

}(window));
