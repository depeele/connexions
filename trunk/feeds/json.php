<?php
/** @file
 *  @brief  Implment a JSON API
 *
 *  Valid API calls are:
 *    'tags' with parameters:
 *      atleast     Include only tags that have been used for at least this
 *                  many items.
 *      count       The maximum number of tags to include (based on sort).
 *      sort        The sort order (alpha|count)
 *      callback    Wrap the object definition in a function call with the
 *                  provided name (e.g. func(...)).
 *      user        The user to generate the set for (if any).
 *
 *    'posts' with parameters:
 *      count       The maximum number of posts to include.
 *      callback    Wrap the object definition in a function call with the
 *                  provided name (e.g. func(...)).
 *      user        The user to generate the set for.
 */
require_once('lib/Services_JSON.php');

/** @brief  Given an associative array, filter it according to 'atleast'.
 *  @param  assoc   The associative array.
 *  @param  atleast Only include tags that have a count >= 'atleast'.
 *  @param  limit   Limit to the first 'limit' items.
 *
 *  @return A filtered array.
 */
function    atleast_filter(&$assoc, $atleast, $limit = 0)
{
    $newArray = array();
    $count    = 0;
    foreach ($assoc as $idex => $tagInfo)
    {
        if ($tagInfo['itemCount'] >= $atleast)
        {
            $newArray[$tagInfo['tag']] = (int)$tagInfo['itemCount'];
            $count++;
            if (($limit > 0) && ($count >= $limit))
                break;
        }
    }

    return $newArray;
}

/** @brief  Return a set of tags.
 *  
 *  A JSON tag list object is an associative array of 'tag' => freqency.
 *
 *  Parameters:
 *      atleast     Include only tags that have been used for at least this
 *                  many items.
 *      count       The maximum number of tags to include (based on sort).
 *      sort        The sort order (alpha|count)
 *      callback    Wrap the object definition in a function call with the
 *                  provided name (e.g. func(...)).
 *      user        The user to generate the set for (if any).
 */
function    json_tags($params)
{
    global  $gTagging;

    /*
    echo "<p><b>json_tags</b>, parameters:<pre>";
    print_r($params);
    echo "</pre>";
    // */

    $curUserInfo = $gTagging->mCurUser;
    $curUserId   = $curUserInfo['userid'];
    $atleast     = (isset($params['atleast']) ? (int)$params['atleast'] : 0);
    $limit       = (isset($params['count'])   ? (int)$params['count']   : 100);
    $sort        = $params['sort'];

    /*
    printf ("atleast[%u], limit[%u], sort[%s]<br />\n",
            $atleast, $limit, $sort);
    // */

    $users    = null;
    if (! empty($params['user']))
    {
        if ($params['user'] === '*')
        {
            // ALL users
            $users = array();
        }
        else
        {
            // The specified user only
            $userInfo = $gTagging->mTagDB->user($params['user']);
            $users    = array($userInfo['userid']);
        }
    }

    if ($users === null)
    {
        // By default, we limit to the current user.
        $users = array($curUserId);
    }

    if (($limit > 0) || ($atLeast > 0))
        $sortSwitch = 'count';
    else
        $sortSwitch = strtolower($sort);

    switch ($sortSwitch)
    {
    case 'count':   $orderBy = 'itemCount DESC';    break;
    case 'alpha':
    default:        $orderBy = 'tag ASC';           break;
    }

    $tags = atleast_filter($gTagging->mTagDB->itemTags($users, null,
                                                       $orderBy),
                           $atleast, $limit);

    //printf ("%u tags<br />\n", count($tags));

    // Finally, sort the results in the requested order
    switch (strtolower($sort))
    {
    case 'count':                   break;
    case 'alpha':
    default:        ksort($tags);   break;
    }

    if (isset($params['callback']))
    {
        /*
         * Generate JavaScript code that will invoke the supplied callback
         * routine with an associative array of 'tagname':'count' items.
         */
        $out    = $params['callback'] . "(";
        $outEnd = ');';
    }
    else
    {
        /*
         * Generate code that will create a 'Connexions.tags'
         */
        $out    = "if(typeof(Connexions) == 'undefined') Connexions = {};".
                  " Connexions.tags = ";
        $outEnd = ";";
    }

    $json = new Services_JSON();
    $out .= $json->encode($tags) . $outEnd;

    echo $out;
}

/** @brief  Return a set of posts.
 *  
 *  A JSON post list object is an array of associative arrays.  Each contained
 *  associative array is comprised of:
 *      'url'           => the url of the item
 *      'name'          => the tagger-speicified name of the item
 *      'description'   => the tagger-speicified description of the item
 *      'tagged_on'     => the textual date/time this object was tagged by
 *                         this tagger.
 *      'timestamp'     => A UNIX timestamp equivalent of 'tagged_on'
 *      'taggers'       => the number of taggers that have also tagged this
 *                         item.
 *      'votes'         => the number of taggers that have provided a rating
 *                         for this item
 *      'avgRating'     => the average rating of this item
 *
 *    If authenticated as id of the requested tagger, it will also include:
 *      'is_private'    => is this item marked 'private' (0/1)?
 *      'is_favorite'   => is this item marked 'favorite' (0/1)?
 *      'rating'        => the tagger's rating of this item
 *
 *  Parameters:
 *      count       The maximum number of posts to include (based on sort).
 *      sort        The sort order (recent|oldest|taggers|votes|ratings).
 *      callback    Wrap the object definition in a function call with the
 *                  provided name (e.g. func(...)).
 *      user        The user to generate the set for.
 */
function    json_posts($params)
{
    global  $gTagging;
    global  $_REQUEST;

    /*
    echo "<pre><b>json_posts</b>, parameters:\n";
    print_r($params);
    echo "<b>_REQUEST:</b>\n";
    print_r($_REQUEST);
    echo "</pre>";
    // */

    /*
    echo "gTagging: curUser<pre>\n";
    print_r($gTagging->mCurUser);
    echo "</pre>\n";
    // */

    $curUserInfo = $gTagging->mCurUser;
    $curUserId   = $curUserInfo['userid'];
    $limit       = 100;

    if (is_numeric($params['count']))
        $limit = (int)$params['count'];

    if ($limit < 1)
        $limit = 100;

    $tags     = null;
    $users    = null;
    if (! empty($params['user']))
    {
        if ($params['user'] === '*')
        {
            // ALL users
            $users = array();
        }
        else
        {
            // The specified user only
            $userInfo = $gTagging->mTagDB->user($params['user']);
            $users    = array($userInfo['userid']);
        }
    }

    if ($users === null)
    {
        // By default, we limit to the current user.
        $users = array($curUserId);
    }

    if (! empty($params['tags']))
    {
        $tags = $gTagging->mTagDB->tagIds($params['tags']);
    }

    switch (strtolower($params['sort']))
    {
    case 'taggers':
    case 'popular':
        $order_by = 'userCount DESC';
        break;

    case 'votes':
    case 'toprated':
        $order_by = 'ratingCount DESC';
        break;

    case 'ratings':
        //$order_by = 'rating DESC';
        $order_by = 'ratingSum DESC';
        break;

    case 'oldest':
    case 'bydate':
        $order_by = 'tagged_on ASC';
        break;

    case 'recent':
    default:
        $order_by = 'tagged_on DESC';
        break;

    }

    $pager  = $gTagging->mTagDB->userItems($users,          // users
                                           $tags,           // tags
                                           $curUserId,      // curUser
                                           $order_by,       // orderBy
                                           $limit);         // perPage
    if ($limit < 1)
    {
        // userItems will return all items directly
        $posts = $pager;
    }
    else
    {
        // userItems will return a pager to be used to retrieve items.
        $posts = $pager->GetPage();
        $pager->Close();
    }

    // Filter out:
    //  - itemid, userid, fullName, email, pictureUrl, profile, lastVisit,
    //    totalTags, totalItems
    $userCache = array();

    foreach ($posts as $key => $post)
    {
        unset($post['itemid']);
        unset($post['userid']);
        unset($post['fullName']);
        unset($post['email']);
        unset($post['pictureUrl']);
        unset($post['profile']);
        unset($post['lastVisit']);
        unset($post['totalTags']);
        unset($post['totalItems']);
        $posts[$key] = $post;
    }

    if (isset($params['callback']))
    {
        /*
         * Generate JavaScript code that will invoke the supplied callback
         * routine with an associative array of 'tagname':'count' items.
         */
        $out    = $params['callback'] . "(";
        $outEnd = ');';
    }
    else
    {
        /*
         * Generate code that will create a 'Connexions.posts'
         */
        $out    = "if(typeof(Connexions) == 'undefined') Connexions = {};".
                  " Connexions.posts = ";
        $outEnd = ";";
    }

    $json = new Services_JSON();
    $out .= $json->encode($posts) . $outEnd;

    echo $out;
}
?>
