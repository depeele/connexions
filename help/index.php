<?php
/** @file
 *  
 *  This is the Help plugin controller.
 *
 *  It will make use of any files within this (help) directory to present
 *  help information.
 *
 *  If the file has been included directly, we will "redirect" to the top-level
 *  plugin.php so it can call the proper class method.
 */
$directInclude = false;
if (! class_exists('PluginController'))
{
    // This was included directly.  Include the plugin library so we can use
    // PluginController, define the class and then, at the end, redirect to the
    // top-level plugin.php which will properly invoke an instance and call the
    // proper class method.
    $directInclude = true;
    require_once("../lib/plugin.php");
}

class Help extends PluginController
{
    function main($topic, $subTopic = 'main')
    {
        global  $gTagging;

        $parts = preg_split('/#/', $topic);
        if (count($parts) > 1)
        {
            $topic = $parts[0];
            $mark  = $parts[1];
        }

        // Set the area we're in
        $path        = 'help';
        $crumb       = $path;
        if (! empty($topic))
        {
            $crumb = "<a href='{$gTagging->mBaseUrl}/$path'>$path</a>";
            $path .= "/$topic";

            if ($subTopic !== 'main')
            {
                $crumb .= " / <a href='{$gTagging->mBaseUrl}/$path'>$topic</a>".
                          " / $subTopic";
                $path  .= "/$subTopic";
            }
            else
            {
                $crumb .= " / $topic";
            }
        }

        $gTagging->mArea = $path;

        echo $gTagging->htmlHead($gTagging->mArea);
        echo $gTagging->pageHeader(false, null, $crumb);

        $callTop = true;
        $try     = 'help/'.$topic.'.php';
        if (file_exists($try))
        {
            $callTop = false;
            $call    = $topic . '_' . $subTopic;

            //printf ("include[%s] to call [%s]<br />\n", $try, $call);
            include_once($try);

            if (function_exists("{$call}"))
            {
                $call(array());
            }
        }

        if ($callTop)
            $this->_top();

        echo $gTagging->pageFooter();

        return true;
    }

    function _top()
    {
        global $gTagging;

        $curUserId = $gTagging->authenticatedUserId();
    
    ?>
    <style type='text/css'>
    #leftcol    { float:left; width:33em; margin:0 1em 0 0; padding:0; list-style-type:none; }
    #rightcol   { float:left; width:30em; margin:0; padding:0; list-style-type:none; }
    
    .helplist { font-size:.9em; }
    .helplist li {margin:0 0 0.5em 0; padding:0; border:1px solid whilte; }
    .helplist li h3 {font-size:1em; float:left; text-align:right; width:9em; margin:0; padding:0;}
    .helplist li p {margin:0 0 0.5em 12em; padding:0 0 0 1em;}
    .helplist li p.additional {_text-indent:0; }
    .helplist li ul {list-style-type:none; margin:0 0 1em 10em; padding:0;}
    .helplist li ul li {float:none; width:auto; margin:0 0 0.1em 0; padding:0; }
    </style>
    
    <div class='infobar'>
     <h2>Help: descriptions, definitiona, and details.</h2>
    </div>
    <ul id='leftcol' class='helplist'>
     <li><h3>Getting started</h3>
        <p>for the beginner, the currious, and you.</p>
        <ul>
          <li><a href='about'>what is connexions?</a></li>
          <li><a href='about#What_is_social_bookmarking'>what is social bookmarking?</a></li>
          <li><a href='about#What_are_tags'>what are tags?</a></li>
        </ul>
     </li>
     <li><h3>Bookmarking</h3>
        <p>how to make your own bookmarks</p>
        <ul>
          <li><a href='saving'>what can be bookmarked?</a></li>
          <li><a href='saving#How_do_I_bookmark_a_URL'>how do I bookmark a URL?</a></li>
          <li><a href='saving#Can_I_make_changes_to_a_bookmark'>can I make changes to a bookmark?</a></li>
          <li><a href='buttons'>bookmarklet buttons for any browser</a></li>
        </ul>
     </li>
     <li><h3>Sharing</h3>
        <p>with friends, coworkers, the connexions community, and more</p>
        <ul>
          <li><a href='sharing'>how can I share my bookmarks?</a></li>
          <li><a href='sharing#Can_others_subscribe_to_my_bookmarks'>can others subscribe to my bookmarks?</a></li>
          <li><a href='sharing#Can_I_bookmark_something_for_another_user'>can I bookmark something for another user?</a></li>
        </ul>
     </li>
     <li><h3>Browsing</h3>
        <p>find other people's bookmarks</p>
        <ul>
          <li><a href='browsing'>a guide to browsing bookmarks</a></li>
        </ul>
     </li>
    
     <li><h3>Navigation</h3>
        <p>what are those links at the top of the page?</p>
        <ul><?php
    
        if ($curUserId !== false)
        {
            ?>
          <li><a href='navlinks#mine'>mine</a></li>
          <li><a href='navlinks#post'>post</a></li>
          <li><a href='navlinks#settings'>settings</a></li><?php
        }
        ?>
    
          <li><a href='navlinks#help'>help</a></li><?php
    
     ?>
        </ul>
     </li>
    </ul>
    <ul id='rightcol' class='helplist'>
     <li><h3>FAQ</h3>
        <p>account help, technical issues, etc.</p>
        <ul>
          <li><a href='faq' class='empty'>frequently asked questions</a></li>
        </ul>
     </li>
     <li><h3>Advanced</h3>
        <p>more to play with...</p>
        <ul>
          <li><a href='navigation' class='empty'>navigation tips</a></li>
          <li><a href='search' class='empty'>search tips</a></li>
          <li><a href='details'>url detail pages</a></li>
        </ul>
     </li>
     <li><h3>Developers</h3>
        <p>access data and build cool stuff</p>
        <ul>
         <li><a href='api' class='empty'>api</a></li>
         <li><a href='json'>json</a></li>
         <li><a href='rss'>rss</a></li>
        </ul>
     </li>
    </ul>
<?
    
        include_once('settings/index.php');
        settings_nav(null);
    }
}

// If we arrived here directly, redirect to our top-level plugin manager
if ($directInclude)
{
    // Redirect to the top-level plugin.php
    $_GET['__route__'] = 'help';
    chdir('..');
    include_once("plugin.php");
}
?>
