<?php
include('lib/tagging.php');
$gDebug = true;

if (count($argv) > 1)
    $fileName = $argv[1];
else
    $fileName = "./del.icio.us-export.html";

$gMaxTaggers = 500;
$gTaggers    = array(1, 1); // Mark uid 0 and 1 as assigned.

initialize($fileName, $db_options['noexec']);

/***************************************************************************
 * Initialization routines.
 *
 */
function initialize($fileName, $noExec  = true)
{
    global  $gTagging;
    global  $gTaggers;
    global  $gDebug;

    // Since lib/user:identify_user() will create an initial user,
    // retrieve the details from the database.
    $user = $gTagging->mTagDB->user(1);
    $gTaggers[$user['userid']] = 1;

    if ($gDebug)
        echo "<pre>\n";

    printf ("Primary user:\n".
            "       uid     : %u\n".
            "       name    : %s\n".
            "       fullName: %s\n".
            "       email   : %s\n",
            $user['userid'],
            $user['name'],
            $user['fullName'],
            $user['email']);

    // Read and parse the del.icio.us bookmarks file
    $fh = fopen($fileName, "r");
    if ($fh === false)
    {
        die("Cannot open bookmark file '{$fileName}'\n");
    }
    
    $cnt  = 0;
    $info = null;
    while (!feof($fh))
    {
        $line = fgets($fh, 4096);

        /* Parse this line
         * A single bookmark may consist of one or two lines:
         *  <DT><A detail...</A>
         *  <DD>description
         */
        if (preg_match('/^<DT><A HREF="([^"]+)" (.*?<\/A>)/',
                        $line, $matches))
        {
            if (is_array($info))
            {
                // The previous bookmark has no description.
                $info['description'] = '';

                createDetails($user['userid'], $info, $noExec);
                $info = null;
            }

            $info   = array(
                    'url'           => $matches[1],
                    'is_private'    => 0,
                    'is_favorite'   => 0,
                    'rating'        => 0,
                    );
            $rest   = $matches[2];

            if (preg_match('/LAST_VISIT="([0-9]+)"/', $rest, $matches))
                $info['visit_time'] = $matches[1];
            if (preg_match('/ADD_DATE="([0-9]+)"/', $rest, $matches))
                $info['add_time'] = $matches[1];
            if (preg_match('/TAGS="([^"]+)"/', $rest, $matches))
                // Normalize the tags
                $info['tags'] = $matches[1];
            if (preg_match('/is_private=["\']([^"\']+)["\']/', $rest, $matches))
                $info['is_private'] = 1;
            if (preg_match('/is_favorite=["\']([^"\']+)["\']/',$rest, $matches))
                $info['is_favorite'] = 1;
            if (preg_match('/rating=["\']([^"\']+)["\']/',$rest, $matches))
                $info['rating'] = (int)$matches[1];
            if (preg_match('/votes=["\']([^"\']+)["\']/',$rest, $matches))
                $info['votes'] = (int)$matches[1];
            if (preg_match('/links=["\']([^"\']+)["\']/',$rest, $matches))
                $info['links'] = (int)$matches[1];
            if (preg_match('/>([^<]+)<\/A>/', $rest, $matches))
                $info['name'] = $matches[1];
        }
        else if (preg_match('/^<DD>(.+)$/', $line, $matches))
        {
            // A description for the bookmark
            $info['description'] = $matches[1];

            createDetails($user['userid'], $info, $noExec);
            $info = null;
        }
        else if (is_array($info))
        {
            // This bookmark has no description.
            $info['description'] = '';

            createDetails($user['userid'], $info, $noExec);
            $info = null;
        }
    }

    if (! $noExec)
    {
        foreach ($gTaggers as $uid => $exists)
        {
            if (($uid > $user['userid']) && ($exists === 1))
                $gTagging->userStatsUpdate($uid);
        }
    }

    if ($gDebug)
        echo "</pre>\n";

    fclose($fh);

    die("-- DONE\n");
}

function createDetails($uid, $info, $noExec)
{
    global  $gTagging;
    global  $gTaggers;
    global  $gMaxTaggers;
    global  $gDebug;

    $tagArr = preg_split('/\s*,\s*/', $info['tags']);

    if (! $gDebug)
    {
        // Ensure there are not votes or links included.
        unset($info['votes']);
        $info['links'] = 1;
    }
    else
    {
        // Add random links and votes
        $info['links'] = rand(0, $gMaxTaggers * 1.5);
        //printf (">>> Random links %u ==> ", $info['links']);
        if ($info['links'] > $gMaxTaggers)
            $info['links'] -= $gMaxTaggers;
        else
            $info['links'] = 1;
        //printf ("%u (1..%u)\n", $info['links'], $gMaxTaggers);

        $info['votes'] = rand(0, $info['links'] * 2);
        if ($info['votes'] <=$info['links'])
            $info['votes'] = 0;
        else
            $info['votes'] /= 2;
    }

    printf ("-- ************************************************\n".
            "-- Create object details for uid[%u],\n".
            "--                           rating[%u], fav[%u], priv[%u],\n".
            "--                           tags {%s}%s\n".
            "--                           time [%u] {%s},\n".
            "--                           votes[%u], links[%u],\n".
            "--                           url  [%s],\n".
            "--                           name [%s],\n".
            "--                           description [%s]\n",
            $uid,
            $info['rating'], $info['is_favorite'], $info['is_private'],
            implode(",", $tagArr),
                (empty($info['tags']) ? "*** WARNING ***" : ""),
            $info['add_time'], date('Y.m.d H:i:s', $info['add_time']),
            $info['votes'], $info['links'],
            $info['url'],
            $info['name'],
            $info['description']);
    if ($noExec)
        return;

    $tagger_csl = '';

    if (! $gDebug)
    {
        // Remove the debug information (votes and links)
        unset($info['votes']);
        $info['links'] = 1;
    }

    $oid = 0;
    while ($info['links']-- > 0)
    {
        $randomTags = '';
        set_time_limit(120);

        if ($oid == 0)
        {
            // The first item should ALWAYS be owned by the identified user
            $tagger_id     = $uid;
            $rating        = $info['rating'];
            $tags          = $info['tags'];
            $is_favorite   = $info['is_favorite'];
            $is_private    = $info['is_private'];
            $timestamp     = (int)$info['add_time'];
            $description   = $info['description'];

            $oid = $gTagging->mTagDB->itemAdd($info['url']);
        }
        else
        {
            $tagger_id  = rand(2, $gMaxTaggers);

            if ($gTaggers[$tagger_id] !== 1)
            {
                $gTagging->mTagDB->userAdd('User'.$tagger_id,
                                           array('fullName' =>
                                                    'Random User '.$tagger_id));
                $gTaggers[$tagger_id] = 1;
            }

            if ($info['votes']-- > 0)
            {
                $rating = rand(1,5);
            }
            else
            {
                $rating = 0;
            }
            $is_favorite = (rand(0,1000) > 925 ? true : false);
            $is_private  = (rand(0,1000) > 990 ? true : false);
            $description = '';

            // Create a new, random timestamp
            if (rand(0,1000) > 950)
                // 0-365 days prior to today
                $timestamp   = time() - rand(0,31536000);
            else
                // 30-365 days prior to my posting time
                $timestamp   = (int)$info['add_time'] - rand(30,31536000);

            if (rand(0,1000) > 750)
            {
                // Randomly remove tags.
                $randomTags = array();
                for ($idex = 0; $idex < count($tagArr); $idex++)
                {
                    if (rand(0,1000) < 750)
                    {
                        $randomTags[]  = $tagArr[$idex];
                    }
                }

                if (rand(0,1000) > 900)
                {
                    // Add a few randomly generated tags
                    $nChars = rand(4,10);
                    $word   = '';
                    for ($idex = 0; $idex < $nChars; $idex++)
                    {
                        $word .= chr(rand(97,122));
                    }
                    $randomTags[] = $word;
                }

                if (count($randomTags) < 1)
                    // Nothing left
                    $randomTags[] = $tagArr[rand(0,count($tagArr) - 1)];
            }

            if (rand(0,1000) > 975)
            {
                // Generate a random description (just so something exists).
                $description = '';
                $nWords      = rand(5,25);
                for ($idex = 0; $idex < $nWords; $idex++)
                {
                    $nSize = rand(0,1000);  // short, medium, long
                    if ($nSize > 900)       // long
                    {
                        $nLen = rand(7,18);
                    }
                    else if ($nSize > 400)  // medium
                    {
                        $nLen = rand(4,7);
                    }
                    else                    // short
                    {
                        $nLen = rand(1,4);
                    }

                    $word = '';
                    if (! empty($description))  $word .= ' ';
                    for ($jdex = 0; $jdex < $nLen; $jdex++)
                    {
                        $word .= chr(rand(97,122));
                    }

                    $description .= $word;
                }

                if ($gDebug)
                    printf("* random description[%s]\n", $description);
            }
        }

        // Add/Update item details
        $gTagging->mTagDB->userItemModify($oid, $tagger_id,
                                          $info['name'],
                                          array('description'=>$description,
                                                'rating'     =>$rating,
                                                'is_favorite'=>$is_favorite,
                                                'is_private' =>$is_private,
                                                'tagged_on'  =>$timestamp));
        if (! empty($randomTags))
            $addTags = $randomTags;
        else
            $addTags = $tagArr;

        if ($gDebug)
        {
            printf("+ oid[%4u]: uid[%4u], ".
                                "ts[%u:%s], ".
                                "rating[%u], favorite[%u], private[%u], ".
                                "tags[%s]\n",
                    $oid, $tagger_id,
                    $timestamp, date('Y.m.d', $timestamp),
                    $rating, $is_favorite, $is_private,
                    implode(',',$addTags));
            flush();
        }
        $gTagging->mTagDB->tagsAdd($tagger_id, $oid, $addTags);
    }
}
?>
