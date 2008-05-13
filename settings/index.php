<?php
/** @file
 *  
 *  This is a connexions plug-in that implements top-level settings.
 *
 *  It will make use of any files within this (settings) directory to provide
 *  user settings control.
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

class Settings extends PluginController
{
    function main($type, $cmd = 'main')
    {
        global  $gTagging;

        // Set the area we're in
        $path        = 'settings';
        $crumb       = $path;
        if (! empty($type))
        {
            $crumb = "<a href='{$gTagging->mBaseUrl}/$path'>$path</a>";
            $path .= "/$type";

            if ($cmd !== 'main')
            {
                $crumb .= " / <a href='{$gTagging->mBaseUrl}/$path'>$type</a>".
                          " / $cmd";
                $path  .= "/$cmd";
            }
            else
            {
                $crumb .= " / $type";
            }
        }

        $gTagging->mArea = $path;

        echo $gTagging->htmlHead($gTagging->mArea);
        echo $gTagging->pageHeader(false, null, $crumb);

        $curUserId = $gTagging->authenticatedUserId();
        if ($curUserId !== false)
        {
            // Only present to authenticated users
            $callTop = true;
            $try     = 'settings/'.$type.'.php';
            if (file_exists($try))
            {
                $callTop = false;
                $call    = $type . '_' . $cmd;

                //printf ("include[%s] to call [%s]<br />\n", $try, $call);
                include_once($try);

                if (function_exists("{$call}"))
                {
                    $call(array());
                }
            }

            if ($callTop)
                $this->_top();
        }

        echo $gTagging->pageFooter();

        return true;
    }

    function bookmarks($cmd)
    {
        global  $gTagging;

        $curUserId = $gTagging->authenticatedUserId();
        if ($curUserId === false)
            // The current user is NOT authenticated - present nothing
            return false;

        // Set the area we're in
        $path   = 'settings';
        $crumb  = "<a href='{$gTagging->mBaseUrl}/$path'>$path</a>";
        $path  .= "/bookmarks";
        $crumb .= " / <a href='{$gTagging->mBaseUrl}/$path'>bookmarks</a>";

        if (! empty($cmd))
            $crumb .= " / $cmd";

        $gTagging->mArea = $path;

        if ($cmd != 'export')
        {
            echo $gTagging->htmlHead($gTagging->mArea);
            echo $gTagging->pageHeader(false, null, $crumb);
        }

        $try = 'settings/bookmarks.php';
        if (file_exists($try))
        {
            $call = 'bookmarks_' . $cmd;

            //printf ("include[%s] to call [%s]<br />\n", $try, $call);
            include_once($try);

            if (function_exists("{$call}"))
            {
                $params['crumb'] = $crumb;
                $call($params);
            }
        }

        if ($cmd != 'export')
            echo $gTagging->pageFooter();

        return true;
    }


    function _top()
    {
        settings_nav($params);

        ?>
<div class='helpQuestion'>Settings</div>
<div class='helpAnswer'>
 Using the links above, you can make changes to your account as well as
 manage the bookmarks, tags, and relationships here on <b>connexions</b>.
</div>
<div class='helpAnswer'>
 Some functionality is not yet implemented but will hopefully be available
 before too long.  Enjoy!
</div>
<?php
    }
}

/** @brief  Present settings navigation.
 *  @param  params  An array of parameters
 */
function settings_nav($params)
{
    global  $gTagging;

    $curUserId = $gTagging->authenticatedUserId();
    if ($curUserId === false)
        // The current user is NOT authenticated - present nothing
        return;

    // The current user is authenticated so present settings navigation
    $userName     = $gTagging->mCurUser['name'];
    $userSettings = $gTagging->mBaseUrl . '/settings';

    ?>
<style type='text/css'>
<? include('css/settings.css'); ?>
</style>
<div class='infobar'>
 <h2>Settings: where you can make changes to your account (also available on your <a href='<?echo $userSettings;?>/'>settings</a> page)</h2>
</div>
<div id='settings_nav'>
 <ul>
  <li class='settings_heading'>
   <h3>Account</h3>
   <ul>
    <li><span>&raquo;</span><a href='<?echo $userSettings;?>/general'>general settings</a></li>
   </ul>
  </li>
  <li class='settings_heading'>
   <h3>Bookmarks</h3>
   <ul>
    <li><span>&raquo;</span><a href='<?echo $userSettings;?>/bookmarks/import'>import</a></li>
    <li><span>&raquo;</span><a href='<?echo $userSettings;?>/bookmarks/export'>export</a></li>
   </ul>
  </li>
  <li class='settings_heading'>
   <h3>Tags</h3>
   <ul>
    <li><span>&raquo;</span><a href='<?echo $userSettings;?>/tags/rename'>rename</a></li>
    <li><span>&raquo;</span><a href='<?echo $userSettings;?>/tags/delete'>delete</a></li>
   </ul>
  </li>
  <li class='settings_heading'>
   <h3>People</h3>
   <ul>
    <li><span>&raquo;</span><a href='<?echo $userSettings;?>/people/privacy' class='empty'>privacy</a></li>
    <li><span>&raquo;</span><a href='<?echo $userSettings;?>/people/subscriptions' class='empty'>subscriptions</a></li>
   </ul>
  </li>
 </ul>
</div>
<div class='settings_nav_line'>&nbsp;</div><?php

    // Now, if we need to go deeper, figure out where we're headed
    if (isset($params['params']))
    {
        // Split the params by '/'
        $subs = preg_split('#\s*[/+,]\s*#', $params['params']);

        if (count($subs) > 0)
        {
            // Now, see if there is a handler for the first component.
            $settings_major = array_shift($subs);
            $settings_func  = array_shift($subs);
            $settings_rest  = implode('/', $subs);

            if (file_exists("settings/{$settings_major}.php"))
            {
                include_once("settings/{$settings_major}.php");
                $params['settings_major'] = $settings_major;
                $params['settings_func']  = $settings_func;
                $params['settings_rest']  = $settings_rest;

                $apiCall = $settings_major . '_' . $settings_func;

                if ( (! empty($settings_func)) &&
                     function_exists("$apiCall") )
                {
                    $apiCall($params);
                }

            }
        }
    }
   //printf ("params[%s]<br />\n", var_export($params,true)); 
}


// If we arrived here directly, redirect to our top-level plugin manager
if ($directInclude)
{
    // Redirect to the top-level plugin.php
    $_GET['__route__'] = 'settings';
    chdir('..');
    include_once("plugin.php");
}
?>
