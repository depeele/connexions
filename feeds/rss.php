<?php
/** @file
 *  @brief  Implment an RSS API
 *
 *  Valid API calls are:
 *    'posts' with parameters:
 *      count       The maximum number of posts to include.
 *      sort        The sort order (recent|oldest|taggers|votes|ratings).
 *      user        The user to generate the set for.
 */

$transTable    = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);

// Some additional, problematic characters
$transTable['&'] = '&';

global $gHtmlEntities;
global $gUtf8Entities;

$gHtmlEntities = array();
$gUtf8Entities = array();
foreach ($transTable as $charVal => $html)
{
    $gHtmlEntities[] = "/$html/";
    $gUtf8Entities[] = '&#'. ord($charVal) .';';
}

/** @brief  Encode a string for use in an RSS feed.
 *  @param  str     The string to encode.
 *
 *  Basically, RSS requires that HTML entities be quoted
 *
 *  @return The encoded string.
 */
function htmlentities2utf8($str)
{
    global  $gHtmlEntities;
    global  $gUtf8Entities;

    $newStr = preg_replace($gHtmlEntities, $gUtf8Entities, $str);

    return ($newStr);
}     

/** @brief  Generate an RSS 2.0 feed for a set of posts.
 *  
 *  An RSS item for a post contains:
 *      title       : The name of this item.
 *      description : The description of this item.
 *      link        : The URL of the item.
 *      pubDate     : The date/time this item was tagged (Y-m-d H:M:S)
 *      author      : The email address of the tagging user.
 *      category    : One for each tag associated with the item.
 *
 *  Parameters:
 *      count       The maximum number of posts to include (based on sort).
 *      sort        The sort order (recent|oldest|taggers|votes|ratings).
 *      user        The user to generate the set for.
 */
function    rss_posts($params)
{
    global  $gTagging;

    /*
    echo "<pre><b>rss_posts</b>, parameters:\n";
    print_r($params);
    echo "</pre>";

    echo "gTagging: curUser<pre>\n";
    print_r($gTagging->mCurUser);
    echo "</pre>\n";
    flush();
    // */

    if (isset($params['count']))
        $limit = (int)$params['count'];
    else
        $limit = 100;

    $tags  = array();
    $users = null;
    if (! empty($params['user']))
    {
        if ($params['user'] === '*')
            $users = array();
        else
        {
            $userInfo = $gTagging->mTagDB->user($params['user']);
            if (is_array($userInfo))
            {
                $users = array($userInfo['userid']);
            }
        }
    }

    if (! empty($params['tags']))
    {
        $tags = $gTagging->mTagDB->tagIds($params['tags']);
    }

    if ($users === null)
    {
        $userInfo = $gTagging->mCurUser;
        $users    = array($userInfo['userid']);
    }

    /*
    printf ("userId[%u]<br />\n", $userId);
    echo "tags<pre>\n";
    print_r($tags);
    echo "</pre>\n";
    flush();
    // */

    switch (strtolower($params['sort']))
    {
    case 'taggers':
    case 'popular':
        $orderBy = 'userCount DESC';
        break;

    case 'votes':
    case 'toprated':
        $orderBy = 'ratingCount DESC';
        break;

    case 'ratings':
        //$orderBy = 'rating DESC';
        $orderBy = 'ratingSum DESC';
        break;

    case 'oldest':
    case 'bydate':
        $orderBy = 'tagged_on ASC';
        break;

    case 'recent':
    default:
        $orderBy = 'tagged_on DESC';
        break;

    }

    $pager = $gTagging->mTagDB-> userItems($users, $tags,
                                           $gTagging->getCurUserId(),
                                           $orderBy, $limit);
    if (get_class($pager) == 'pager')
        $posts = $pager->GetPage();
    else
        $posts = $pager;

    /*
     * Generate the RSS feed information.
     */
    header('Content-Type: text/xml');
    header('Content-Encoding: utf-8');

    echo "<?xml version='1.0' encoding='utf-8' ?>\n\n";
    ?>
<rss version='2.0'>
 <channel>
  <title>Connexions Posts</title>
  <link><?php echo htmlentities($_SERVER['SCRIPT_URI']); ?></link>
  <description>Connexions Posts matching the specified criteria.</description>
  <generator>Connexions</generator>
  <ttl>60</ttl><?php

    $userCache = array();
    foreach ($posts as $key => $info)
    {
        //$info       = $posts[$key];
        $itemId     = $info['itemid'] * 1;

        if ($userId == 0)
        {
            // Retrieve the name of the user with the userid for this item.
            $thisUserId = $info['userid'];

            if (! isset($userCache[$thisUserId]))
            {
                $thisUser = $gTagging->mTagDB->user($thisUserId);
                $userCache[$thisUserId] = $thisUser;
            }
            else
            {
                $thisUser = $userCache[$thisUserId];
            }

            $info['author'] = $thisUser['email'];
        }
        else
        {
            $info['author'] = $userInfo['email'];
            $thisUserId     = $userId;
        }

        if ( (! isset($info['tags'])) || (! is_array($info['tags'])) )
        {
            // Retrieve the tags associated with this item.
            $info['tags'] = $gTagging->mTagDB->itemTags(array($thisUserId),
                                                        array($itemId),
                                                        'tag ASC');
        }
        else
        {
            $info['tags'] = array();
        }

        $md5 = md5($info['url']);

        echo "\n";?>
  <item>
   <title><?php        echo htmlentities2utf8($info['name']); ?></title>
   <description><?php  echo htmlentities2utf8($info['description']); ?></description>
   <link><?php         echo htmlentities($info['url']); ?></link>
   <pubDate><?php      echo $info['tagged_on']; ?></pubDate>
   <author><?php       echo $info['author']; ?></author>
   <guid><?php printf ("%s/details/%s", $gTagging->mFullUrl, $md5); ?></guid>
<?php

        foreach ($info['tags'] as $idex => $tagInfo)
        {
            $tag = $tagInfo['tag'];
            echo "   <category>". htmlentities2utf8($tag) ."</category>\n";
        }

        ?>
  </item><?php
    }
    unset($userCache);

    ?>
 </channel>
</rss>
<?php
}

?>
