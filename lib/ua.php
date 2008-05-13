<?php
/** @file
 *
 *  User agent identification.
 */

/** @brief  The set of know user-agents.
 *
 *  This associative array is comprised of a user-agent key that references
 *  a second associative array of:
 *      - name      The human-readble agent name
 *      - pattern   The regular expression used against
 *                  $_SERVER['HTTP_USER_AGENT'] to identfy this user-agent.
 *      - key       Will be added in the following loop to mirror the
 *                  key that references this associative array.
 */
global  $gUaKnown;
$gUaKnown = array(
    'firefox'   => array('name'         => 'Firefox',
                         'pattern'      => "/firefox/i",
                        ),
    'safari'    => array('name'     => 'Safari',
                         'pattern'  => "/safari/i",
                        ),
    'opera'     => array('name'     => 'Opera',
                         'pattern'  => "/opera/i",
                         ),
    'ie'        => array('name'     => 'Internet Explorer',
                         'pattern'  => "/msie/i",
                        ),
    'default'   => 'firefox',
                );

/* Add a 'key' entry for each item. */
//reset($gUaKnown);
foreach ($gUaKnown as $key => $info)
{
    if (! is_array($info))
        continue;

    $gUaKnown[$key]['key'] = $key;
}

//printf ("known[%s]<br />\n", var_export($gUaKnown, true));


/** @brief  return information about the current/selected user agent.
 *  @param  ua      If provided, return information about THIS agent.
 *
 *  @return An associative array of information.
 *          - name      The human readable name of the agent
 *          - key       The key for this entry.
 */
function ua_get($ua = null)
{
    global  $gUaKnown;
    global  $_SERVER;

    $agent = $_SERVER["HTTP_USER_AGENT"];

    if (! empty($ua))
    {
        $ua     = strtolower($ua);
        $uaInfo = $gUaKnown[$ua];
    }
    else
    {
        /* User agent strings:
         *  Firefox 1.5:
         *      Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; en-US; rv:1.8)
         *          Gecko/20051111 Firefox/1.5
         *  Netscape 7.1:
         *      Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; en-US; rv:1.4)
         *          Gecko/20030624 Netscape/7.1 (USG-10)
         *  Safari 2.0.3 (417.8):
         *      Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en-us)
         *          AppleWebKit/417.9 (KHTML, like Gecko) Safari/417.8
         *  Internet Explorer 5.2:
         *      Mozilla/4.0 (compatible; MSIE 5.23; Mac_PowerPC)
         *
         */
        reset($gUaKnown);
        foreach ($gUaKnown as $key => $info)
        {
            if (! is_array($info))
                continue;
    
            if (preg_match($info['pattern'], $agent))
            {
                $ua     = $key;
                $uaInfo = $info;
            }
        }
    }
    
    if (is_array($uaInfo))
    {
        // Merge in any missing values using the 'default'
        $uaInfo = array_merge($gUaKnown[$gUaKnown['default']], $uaInfo);
    }
    else
    {
        $uaInfo = $gUaKnown[$gUaKnown['default']];
        $ua     = 'default';
    }

    return ($uaInfo);
}

?>
