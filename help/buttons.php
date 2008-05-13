<?php
/** @file
 *
 *  Present information about buttons/bookmarklets.
 */

global  $gTagging;
require_once('lib/ua.php');

$buttonInfo = array(
    'firefox'   => array('bookmark'     => 'Drag this link to your bookmarks toolbar, or right-click it and choose Bookmark This Link...',
                         'popup_js'     => "javascript:(function(){href=location.href;url=encodeURIComponent(href);name=escape(document.title);desc=escape(window.getSelection());window.open('{$gTagging->mFullUrl}/post?url='+url+'&name='+name+'&description='+desc+'&closeAction=close','connexions','toolbar=no,menubar=no,resizable=yes,status=yes,width=800,height=500');})()",
                        ),
    'safari'    => array('bookmark' => 'Drag this link to your bookmarks bar, or right-click it and choose Add Link to Bookmarks...',
                        ),
    'opera'     => array('bookmark' => 'Drag this link to your Personal Bar, or right-click it and choose Bookmark link...',
                         ),
    'ie'        => array('bookmark' => 'Right-click this link and choose Add to Favorites.',
                         'popup_js' => "javascript:(function(){href=location.href;url=encodeURIComponent(href);name=escape(document.title);desc=escape(document.selection.createRange().text);window.open('{$gTagging->mFullUrl}/post?url='+url+'&name='+name+'&description='+desc+'&closeAction=close','connexions','toolbar=no,menubar=no,resizable=yes,status=yes,width=800,height=500');})()",
                        ),
                );


$uaInfo = ua_get($params['ua']);
$ua     = $uaInfo['key'];
$uaInfo = array_merge($uaInfo, $buttonInfo[$ua]);

printf("<!-- agent[%s], ua[%s], name[%s] -->\n", $agent, $ua, $uaInfo['name']);

?>
<div class='helpQuestion'>What are buttons?</div>
<div class='helpAnswer'>
 Buttons or bookmarklets are links that you can add to your browser's Bookmarks
 to provide an easy way to post new and view existing connexions items.
</div>

<div class='helpQuestion'>What do they do?</div>
<div class='helpAnswer'>
 The <a
  href="javascript:(function(){location.href='<?=$gTagging->mFullUrl?>/post?url='+encodeURIComponent(location.href)+'&name='+encodeURIComponent(document.title);})()"
  title="post to connexions"
  onclick="window.alert('<?php echo $uaInfo['bookmark']; ?>');return false;">
 post to connexions</a> button allows you to post the currently displayed page
 to connexions.  This will visit the connexions post page and then, upon
 completion, return to the original page.
</div><?php

    $js = $uaInfo['popup_js'];
    if (! empty($uaInfo['popup_js']))
    {
        echo "
<div class='helpAnswer'>
 This <a href=\"{$uaInfo['popup_js']}\" title=\"post to connnexions\"
  onclick=\"window.alert('{$uaInfo['bookmark']}');return false;\">
 post to connexions</a> button will grab any current selection as the post
 description, and open a separate post window.
</div>";
    }

?>
<div class='helpAnswer'>
 The <a
  href="javascript:(function(){location.href='<?=$gTagging->mFullUrl?>/home';})()"
  title="my connexions"
  onclick="window.alert('<?php echo $uaInfo['bookmark'];?>');return false;">
 my connexions</a> button allows you to quickly view your current connextions
 items.
</div>
<div class='helpAnswer' style='margin:1em 4em;font-size:.75em;'>
 (Show instructions for: <?php

    reset($gUaKnown);
    $first = true;
    foreach ($gUaKnown as $key => $info)
    {
        if ( ! is_array($info) )
            continue;

        if ($first) $first = false;
        else        echo ", ";

        if ( $uaInfo['name'] == $info['name'] )
            printf ("<b>%s</b>", $info['name']);
        else
            printf("<a href='?ua=%s'>%s</a>",
                   $key, $info['name']);
    }
 ?>)
</div>
<?php

/** @brief  If the current user-agent supports it, present a button that
 *          will pop up a window.
 *  @param  ua          The simplified user agent.
 *  @param  bookmark    The agent-specific text describing how to bookmark.
 */
function showUAbutton($ua, $bookmark)
{
    global  $gTagging;

    $js = '';
    switch ($ua)
    {
    case 'ie':
        $js = "javascript:(function(){href=location.href;url=encodeURIComponent(href);name=escape(document.title);desc=escape(document.selection.createRange().text);window.open('{$gTagging->mFullUrl}/post?url='+url+'&name='+name+'&description='+desc+'&closeAction=close','connexions','toolbar=no,menubar=no,resizable=yes,status=yes,width=800,height=500');})()";
        break;

    case 'firefox':
    case 'netscape':
    case 'gecko':
    default:
        $js = "javascript:(function(){href=location.href;url=encodeURIComponent(href);name=escape(document.title);desc=escape(window.getSelection());window.open('{$gTagging->mFullUrl}/post?url='+url+'&name='+name+'&description='+desc+'&closeAction=close','connexions','toolbar=no,menubar=no,resizable=yes,status=yes,width=800,height=500');})()";
        break;
    }

    if (! empty($js))
    {
        echo "
<div class='helpAnswer'>
 This <a href=\"{$js}\" title=\"post to connnexions\"
  onclick=\"window.alert('{$bookmark}');return false;\">
 post to connexions</a> button will grab any current selection as the post
 description, and open a separate post window.
</div>";
    }
}
?>
