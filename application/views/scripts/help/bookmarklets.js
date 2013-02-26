// Basic redirect
javascript:(function(){
    location.href='<?= $this->baseUrl('post') ?>'
                 +'?url='+encodeURIComponent(location.href)
                 +'&name='+encodeURIComponent(document.title)
                 +'&noNav&closeAction=close';
}())

// Basic redirect (collapsed)
javascript:(function(){location.href='<?= $this->baseUrl('post') ?>?url='+encodeURIComponent(location.href)+'&name='+encodeURIComponent(document.title)+'&noNav&closeAction=close';}())

// New Window
javascript:(function(){
    var href = encodeURIComponent(location.href),
        name = escape(document.title),
        desc = escape(window.getSelection()),
        url  = '<?= $this->baseUrl('post') ?>'
             +      '?url='+url
             +      '&name='+name
             +      '&description='+desc
             +      '&noNav'
             +      '&closeAction=close';
        w    = window.open(url, 'connexions',
                           'resizable=yes,scrollbars=yes,status=yes,'
                           + 'toolbar=no,menubar=no,centerscreen,'
                           + 'width=975,height=625');
    window.setTimeout('w.focus()', 200);
}())

// New Window (collapsed
javascript:(function(){var url=encodeURIComponent(location.href),name=escape(document.title),desc=escape(window.getSelection()),w=window.open('<?= $this->baseUrl('post') ?>'+'?url='+url+'&name='+name+'&description='+desc+'&noNav&closeAction=close','connexions','resizable=yes,scrollbars=yes,status=yes,toolbar=no,menubar=no,centerscreen,width=975,height=625');window.setTimeout('w.focus()',200);}())

// lightbox
javascript:(function(){
    var dzId='connexions_disabledZone',
        lbId='connexions_lightBox',
        ifId='connexions_iframe',
        clId='connexions_close',
        oof=document.body.style.overflow;
    function _gi(e){return document.getElementById(e);}
    function _ce(e){return document.createElement(e);}
    function _ws(){
        // Figure out the window's inner width/height in a "portable" way
        var w=0,h=0;
        if (typeof(window.innerWidth) === 'number') {
            // Non-IE
            w = window.innerWidth;
            h = window.innerHeight;
        } else if ( document.documentElement &&
                   (document.documentElement.clientWidth ||
                    document.documentElement.clientHeight)) {
            // IE 6+ in standards complient mode
            w = document.documentElement.innerWidth;
            h = document.documentElement.innerHeight;
        } else if ( document.body &&
                   (document.body.clientWidth ||
                    document.body.clientHeight)) {
            // IE 4 compatible
            w = document.body.innerWidth;
            h = document.body.innerHeight;
        }
        return [w,h];
    }
    function lbClose(){
        document.body.removeChild(_gi(lbId));
        document.body.removeChild(_gi(dzId));
        document.body.style.overflow=oof;
    }
    function lb(url){
        var s=_ws(),w=s[0],h=s[1];

        // Create a "disabled zone" that masks the entire document body
        var dz=_ce('div');
        dz.setAttribute('style', 'background:#000;'
                                 +'position:absolute;'
                                 +'z-index:100000;'
                                 +'top:0px;'
                                 +'left:0px;'
                                 +'width:'+  w +'px;'
                                 +'height:'+ h +'px;'
                                 +'opacity:0.5;'                // Standard
                                 +'-moz-opacity:0.5;'           // Mozilla
                                 +'-khtml-opacity:0.5;'         // KHTML
                                 +'filter:alpha(opacity=50);'   // IE5-7
                                                                // IE8
                                 +'filter:progid:DXImageTransform.Microsoft.Alpha(Opacity=50);'
        );
        dz.id = dzId;
        dz.addEventListener('click', lbClose, true);
        document.body.appendChild(dz);

        /* Create the lighbox, which sits just above the "diabled zone" and
         * provides a "window" around the iframe that will contain the
         * connexions posting form (from the connexions site).
         */
        var lbz=_ce('div');

        lbz.setAttribute('style','background:#fff;'
                                 +'position:fixed;'
                                 +'z-index:100001;'
                                 +'top:50px;'
                                 +'left:50px;'
                                 +'width:'+  (w-100) +'px;'
                                 +'height:'+ (h-100) +'px;'
                                 +'border:1px solid #ccc;'
                                 +'padding:4px;'
                                 +'overflow:hidden;'
        );
        lbz.id = lbId;

        /* Make sure teh following HTML does NOT have any double quotes (")
         * since, as a bookmarklet, it will often need to be contained within
         * double quotes (e.g. <a href="bookmarklet">test it</a>).
         */
        lbz.innerHTML = '<div style=\'padding:4px;'
                      +              'margin-bottom:0;'
                      +              'background:#aaa;'
                      +              'color:#fff;'
                      +              'text-shadow:none;'
                      +              'font-family:Arial,Helvetica,sans-serif;'
                      +              'font-size:14px;'
                      +              'font-weight:bold;'
                      +              'height:2.25em;\'>'
                      +  '<span style=\'float:left;\'>'
                      +   'Post&nbsp;to&nbsp;connexions'
                      +  '</span>'
                      +  '<span id=\''+ clId +'\' '
                      +         'style=\'float:right;'
                      +                 'color:#fff;'
                      +                 'cursor:pointer;\'>'
                      +   'close'
                      +  '</span>'
                      + '</div>'
                      + '<iframe id=\''+ ifId +'\' '
                      +         'width=\'100%\' height=\''+ (w-145) +'px\' '
                      +         'frameborder=\'0\' '
                      +         'style=\'overflow:auto;\'>'
                      + '</iframe>';
        document.body.appendChild(lbz);
        document.body.style.overflow='hidden';

        //Connect lbClose() to iframe.close()
        var ccl=_gi(clId);
        var cif=_gi(ifId);

        cif.close = lbClose;
        cif.src   = url;    // Redirect the iframe to the proper url

        // Connect an event listener to the close button on the lightbox
        ccl.addEventListener('click', lbClose, true);
    }

    var href=encodeURIComponent(location.href),
        name=escape(document.title),
        desc=escape(window.getSelection()),
        url='<?= $this->baseUrl('post') ?>'
           +      '?url='+href
           +      '&name='+name
           +      '&description='+desc
           +      '&noNav'
           +      '&closeAction=iframe';
    lb(url);
}())

// lightbox (collapsed)
javascript:(function(){var dzId='connexions_disabledZone',lbId='connexions_lightBox',ifId='connexions_iframe',clId='connexions_close',oof=document.body.style.overflow;function _gi(e){return document.getElementById(e);}function _ce(e){return document.createElement(e);}function _ws(){var w=0,h=0;if (typeof(window.innerWidth)==='number'){w=window.innerWidth;h=window.innerHeight;}else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)){w=document.documentElement.innerWidth;h=document.documentElement.innerHeight;}else if (document.body && (document.body.clientWidth || document.body.clientHeight)){w=document.body.innerWidth;h=document.body.innerHeight;}return [w,h];} function lbClose(){document.body.removeChild(_gi(lbId));document.body.removeChild(_gi(dzId));document.body.style.overflow=oof;} function lb(url){var s=_ws(),w=s[0],h=s[1],dz=_ce('div');dz.setAttribute('style', 'background:#000;position:absolute;z-index:100000;top:0px;left:0px;width:'+w+'px;height:'+h+'px;opacity:0.5;-moz-opacity:0.5;-khtml-opacity:0.5;filter:alpha(opacity=50);filter:progid:DXImageTransform.Microsoft.Alpha(Opacity=50);');dz.id=dzId;dz.addEventListener('click',lbClose,true);document.body.appendChild(dz);var lbz=_ce('div');lbz.setAttribute('style','background:#fff;position:fixed;z-index:100001;top:50px;left:50px;width:'+(w-100)+'px;height:'+(h-100)+'px;border:1px solid #ccc;padding:4px;overflow:hidden;');lbz.id=lbId;lbz.innerHTML='<div style=\'padding:4px;margin-bottom:0;background:#aaa;color:#fff;text-shadow:none;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;height:2.25em;\'><span style=\'float:left;\'>Post&nbsp;to&nbsp;connexions</span><span id=\''+clId+'\' style=\'float:right;color:#fff;cursor:pointer;\'>close</span></div><iframe id=\''+ifId+'\' width=\'100%\' height=\''+(w-145)+'px\' frameborder=\'0\' style=\'overflow:auto;\'></iframe>';document.body.appendChild(lbz);document.body.style.overflow='hidden';var ccl=_gi(clId),cif=_gi(ifId);cif.close=lbClose;cif.src=url;ccl.addEventListener('click',lbClose,true);} var href=encodeURIComponent(location.href),name=escape(document.title),desc=escape(window.getSelection()),url='<?= $this->baseUrl('post') ?>?url='+href+'&name='+name+'&description='+desc+'&noNav'+'&closeAction=iframe';lb(url);}())

