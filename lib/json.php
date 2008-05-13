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
 *
 *  @return A filtered array.
 */
function    atleast_filter(&$assoc, $atleast = 0)
{
    if ($atleast == 0)
        return ($assoc);

    $newArray = array();
    foreach ($assoc as $key => $val)
    {
        if (is_numeric($val))
        {
            $val = (int)$val;

            if ($val >= $atleast)
            {
                $newArray[$key] = $val;
            }
        }
        else
        {
            $newArray[$key] = $val;
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

    /*echo "<p><b>json_tags</b>, parameters:<pre>";
    print_r($params);
    echo "</pre>";*/

    $atleast = $params['atleast'] * 1;
    $limit   = $params['count'] * 1;
    $sort    = strtolower($params['sort']);

    $userId  = null;
    if (isset($params['user']))
        $userId = $gTagging->mTagDB->userId($params['user']);
    else if (isset($params['param0']))
        $userId = $gTagging->mTagDB->userId($params['param0']);
    else
        $userId = $gTagging->mCurUser['id'];


    $tags = atleast_filter($gTagging->mFreeTag->get_tag_cloud_tags($limit,
                                                                   $userId,
                                                                   null,
                                                                   $order),
                           $atleast);

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

    /*echo "<p><b>json_posts</b>, parameters:<pre>";
    print_r($params);
    echo "</pre>";

    echo "gTagging: curUser<pre>\n";
    print_r($gTagging->mCurUser);
    echo "</pre>\n";*/

    $tags    = null;
    $limit   = $params['count'] * 1;
    $userId  = 0;
    if (! empty($params['user']))
        $userId = $gTagging->mTagDB->userId($params['user']);

    if (! empty($params['tags']))
        $tags = preg_split('#\s*[/+,]\s*#', $params['tags']);

    if ($userId < 1)
        $userId = $gTagging->mCurUser['id'];

    /*printf ("userId[%u]<br />\n", $userId);
    echo "tags<pre>\n";
    print_r($tags);
    echo "</pre>\n";*/

    switch (strtolower($params['sort']))
    {
    case 'taggers':
    case 'popular':
        $order_by = 'taggers DESC';
        break;

    case 'votes':
    case 'toprated':
        $order_by = 'votes DESC';
        break;

    case 'ratings':
        //$order_by = 'rating DESC';
        $order_by = 'avgRating DESC';
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

    $posts = $gTagging->mObj->get_objects($userId, $tags,
                                          $order_by, 0, $limit);

    // Filter out id, object_id, and tagger_id.
    foreach ($posts as $key => $val)
    {
        unset($posts[$key]['id']);
        unset($posts[$key]['object_id']);
        unset($posts[$key]['tagger_id']);
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
