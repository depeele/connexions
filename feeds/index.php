<?php
/** @file
 *  
 *  This is a connexions plug-in that implements access to top-level feeds.
 *
 *  It will make use of any files within this (feeds) directory to provide
 *  that specific type of feed.
 *
 *  If the file has been included directly, we will "redirect" to the top-level
 *  plugin.php so it can call the proper class method.
 */
$directInclude = false;
if (! class_exists('PluginController'))
{
    /*
     * This was included directly.  Include the plugin library so we can use
     * PluginController, define the class and then, at the end, redirect to the
     * top-level plugin.php which will properly invoke an instance and call the
     * proper class method.
     */
    $directInclude = true;
    require_once("../lib/plugin.php");
}

class Feeds extends PluginController
{
    function main($type, $cmd, $params)
    {
        global  $gTagging;

        /*printf("Feeds::main: type[%s], cmd[%s], params{%s}<br />\n",
                $type, $cmd, var_export($params, true));*/
        $callTop = true;
        $try     = 'feeds/'.$type.'.php';
        if (file_exists($try))
        {
            $callTop = false;
            $call    = $type . '_' . $cmd;

            //printf ("include[%s] to call [%s]<br />\n", $try, $call);
            include_once($try);

            if (function_exists("{$call}"))
            {
                $callTop = false;
                $call($this->parseParams($params));
            }
        }

        if ($callTop)
            $this->_top();

        return true;
    }

    function _top()
    {
        global  $gTagging;

        // Set the area we're in
        $path        = 'feeds';
        $crumb       = $path;

        $gTagging->mArea = $path;

        echo $gTagging->htmlHead($gTagging->mArea);
        echo $gTagging->pageHeader(false, null, $crumb);

        echo "
<div class='helpQuestion'>Available feeds:</div>
 <div class='helpAnswer'>
  <ul>";

        // Provide a list of available feeds
        $dir     = 'feeds';
        $baseUrl = $gTagging->mBaseUrl . "/$dir";
        if (is_dir($dir))
        {
            if ($dh = opendir($dir))
            {
                while (($file = readdir($dh)) !== false)
                {
                    $name = basename($file, '.php');
                    if (preg_match('#^(index|\.)#', $name))
                        continue;

                    // Now, locate all available feed commands
                    $lines = implode('', file("$dir/$file"));

                    printf ("<li><b>%s</b>", $name);

                    preg_match_all("/^function\s+{$name}_([^\(\s]+)\s*?\(/m",
                                   $lines, $matches);
                    if (is_array($matches))
                    {
                        //var_dump($matches);
                        foreach ($matches[1] as $idex => $cmd)
                        {
                            if ($idex > 0)  echo ", ";
                            else            echo " - ";

                            printf ("<a href='%s/%s/%s'>%s</a>",
                                    $baseUrl, $name, $cmd, $cmd);
                        }
                    }

                    echo "</li>\n";
                }
                closedir($dh);
            }
        }

        echo "
  </ul>
 </div>
<div>";

        echo $gTagging->pageFooter();

        return true;
    }
}

// If we arrived here directly, redirect to our top-level plugin manager
if ($directInclude)
{
    // Redirect to the top-level plugin.php
    $_GET['__route__'] = 'feeds';
    chdir('..');
    include_once("plugin.php");
}
?>
