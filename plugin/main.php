<?php
/** @file
 *  
 *  This is a connexions plug-in that implements the main namespace.
 */
require_once('lib/tagging.php');
require_once('lib/paging.php');

class Main extends PluginController
{
    /** @brief  Provide the main view of user(s) and tag(s).
     *  @param  user    The name/id of the user to present.
     *  @param  tags    A list of comma-separated tags to limit the display.
     *  @param  wrap    Should output be wrapped in header/footer?
     *
     *  @return true (if processed), false otherwise
     */
    function view($user, $tags, $wrap = true)
    {
        $funcId = 'Main::view';
        global  $gTagging;

        if ($user == 'for')
            return false;

        /*
        printf("%s: user[%s], tags[%s] wrap[%u]<br />\n",
                $funcId, $user, $tags, $wrap);

        echo "<pre>$funcId:\n";
        echo "_SESSION\n";
        print_r($_SESSION);
        echo "</pre>\n";
        // */
    
        // Establish defaults
        $title     = "Tagged items";
        $rssHref   = $gTagging->mFullUrl . '/feeds/rss/posts/';
        $users     = null;    // Default to recovering the user(s) from SESSION
        $limitTags = null;    // Default to recovering the tag(s)  from SESSION
    
        if ((! empty($user)) && ($user != 'tag'))
        {
            /*
             * Establish the tagger that we're displaying information about.
             * MAY be different than the current user.
             */
            if ($gTagging->setTagger($user) === false)
            {
                // This is NOT a real user - let someone else handle it.
                /*printf ("%s: user[%s] is NOT a user<br />\n",
                        $funcId, $user);*/
                return false;
            }

            $tagger = $user;
    
            // Set the area we're in
            if ($gTagging->mTagger['name'] == $gTagging->mCurUser['name'])
                $title = 'your posts';
            else
                $title  = $gTagging->mTagger['name'] . "'s posts";
   
            $gTagging->mArea = $gTagging->mTagger['name'];
            $users    = array($gTagging->mTagger['userid']);
            $rssHref .= '?user=' . $gTagging->mTagger['name'];
        }
        else
        {
            // ALL users
            $tagger   = '';
            $users    = array();
            $rssHref .= '?user=*';

            if ($user == 'tag')
                $gTagging->mArea = 'tag';
        }
    
        /*printf ("%s: users{%s}<br />\n",
                $funcId, var_export($users, true));*/
    
        // Any specified tags are used to limit the display to items with ALL
        // tags
        if (! empty($tags))
        {
            /* The incoming tag string should have tags separated by ','. */
            $tags      = rawurldecode($tags  );
            $limitTags = $gTagging->mTagDB->tagIds($tags);
    
            /*printf ("%s:[%s] => {%s}<br />\n",
                    $funcId, $tags, implode(', ', $limitTags));*/
        }
        else
        {
            //printf ("<br />\n");
            $limitTags = array();
        }
    
        if ($wrap)
        {
            /*
             * Generate the HTML for the header.
             */
            echo $gTagging->htmlHead($title,
                                 array(
                                     array('type'   => 'application/rss+xml',
                                           'title'  => 'RSS',
                                           'href'   => $rssHref,
                                          )
                                      ));
            echo $gTagging->pageHeader(false, $limitTags);
            flush();
        }
    
        /*
         * Generate the HTML for the list of tagged items.
         */
        echo "
    <div id='content'><!-- { -->
     <div id='left'><!-- { -->
      <div id='Items-top' class='ItemList'><!-- { -->";
        flush();
    
        echo $gTagging->itemsArea($users,
                                  $limitTags);
    
        echo "
      </div><!-- ItemList } -->
     </div><!-- left } -->";
        flush();
    
        /*
         * Generate the HTML for the set of tags.
         */
        echo "
     <div id='right'><!-- { -->
      <div class='UserList'><!-- { -->";
        flush();
    
        /*
         * Generate the HTML for the user (if any).
         */
        echo $gTagging->usersArea($users);
        flush();
    
        /*
         * Generate the HTML for the set of tags.
         */
        echo "
      </div><!-- UserList } -->
      <div id='Tags-top' class='TagList'><!-- { -->";
        flush();
    
        echo $gTagging->tagsArea($users,
                                 $limitTags);
    
        echo "
      </div><!-- TagList } -->
     </div><!-- right } -->
    </div><!-- content } -->";
        flush();
    
        if ($wrap)
            echo $gTagging->pageFooter();

        return (true);
    }

    function viewTags($tags)
    {
        return view(null, $tags);
    }

    /** @brief  Present all connexions people.
     *  @param  params  An array of provided parameters.
     *
     *  Parameters:
     *      - Order
     *      - Page
     *      - PerPage
     */
    function people()
    {
        $funcId = 'Main::people';
        global  $gTagging;
    
        // Set the area we're in
        $gTagging->mArea = 'people';
        $title           = 'People';
    
        /*
         * Generate the HTML and page headers
         */
        echo $gTagging->htmlHead($title);   //, null, array('tablesort.js'));
        echo $gTagging->pageHeader(false, null, 'people');
        flush();
    
        /*
         * Generate the HTML for the list of tagged items.
         */
        echo "
    <div id='content'><!-- { -->
     <div id='left'><!-- { -->
      <div id='Users-top'><!-- { -->";
    
        echo $gTagging->peopleArea(null);
    
        echo "
      </div><!-- Users-top } -->
     </div><!-- left } -->
    </div><!-- content } -->";
        flush();
    
        echo $gTagging->pageFooter();
        flush();

        return (true);
    }

    /** @brief  Present details about the given url.
     *  @param  url     The url to present:
     *                      - id=<itemid>
     *                      - url=<url>
     *                      - <md5 has of the url>
     *
     *  If 'url' is empty, retrieve it from _REQUEST since we have to use:
     *      details/?url=xxx
     *  in order to pass a in a raw url.
     *
     *  @return true (if processed), false otherwise
     */
    function details($url)
    {
        $funcId = 'Main::details';
        global  $gTagging;
    
        if (empty($url))
        {
            // Look in _REQUEST
            $url = $_REQUEST['url'];
        }

        require_once('lib/timeline.php');
    
        // Set the area we're in
        $gTagging->mArea = 'details';
    
        /*
         * Process our incoming parameters
         *
         * Details may be requested in one of 3 ways:
         *  - id=<ID>       By unique identifer
         *  - url=<URL>     By URL (this will be hashed and the browser
         *                  redirected
         *  - <md5 hash>    By MD5 hash of an item URL
         */
        $title = 'Details for ';
        $id    = -1;
        if (preg_match('#^(.*?)=(.*)$#', $url, $matches))
        {
            $type = $matches[1];
            $val  = $matches[2];

            switch (strtolower($type))
            {
            case 'id':
                $id = (int)$val;
                $title .= sprintf("item #%u ", $id);
                break;

            default:
                $url    = $val;
                break;
            }
        }

        if (($id < 1) && (! empty($url)) )
        {
            // Lookup the id of the requested item (if one exists).
            $item = $gTagging->mTagDB->item($url);
            if (is_array($item))
            {
                $url    = $item['url'];
                $id     = $item['itemid'];
                $md5    = md5($url);
                $title .= sprintf("%s", $url);
            }
        }

        // Defaults
        $taggerInfo = array();
        $info       = array();
        $taggers    = 0;
    
        if ($id > 0)
        {
            /*
             * Retrieve information about all taggers of this item.
             */
            $taggerInfo =  $gTagging->mTagDB->userItemsForId(
                                                    null,       // All users
                                                    $id,
                                                    $gTagging->getCurUserId(),
                                                    'tagged_on DESC',
                                                    -1);        // no paging
            if (! is_array($taggerInfo))
            {
                $id = 0;
            }
            else
            {
                $taggers    =  count($taggerInfo);
    
                // Present the information from the first (non-private) tagger
                $info       =& $taggerInfo[$taggers - 1];
                if (isset($info['url']))
                {
                    $url    = $info['url'];
                    $title .= "'{$url}'";
                }
            }
        }

        /*
         * Generate the HTML and page headers
         */
        echo $gTagging->htmlHead($title);
        echo $gTagging->pageHeader(false, null, 'details');
        echo "
    <div id='content'><!-- { -->
     <div id='left'><!-- { -->
      <div id='Items-top' class='ItemList'><!-- { -->
       <form method='get' action='{$gTagging->mBaseUrl}/details/'>
       <div class='title'><!-- { -->
        history for
         <input name='url' class='textInput' type='text' value='{$url}' size='40' />
         <input name='submit' type='submit' value='check url' />
       </div><!-- title } -->
       </form>";
    
        $tagHistory  = "
       <div class='title' style='margin-top:2em;'><b>Taggers</b>&nbsp;<sub>{$taggers}</sub></div>";
            
        if ($id > 0)
        {
            // Retrieve statistics about this item.
            $stats = $gTagging->mTagDB->itemStats($id);
    
            // Generate the HTML to present the details about this item
            echo "
       <div class='Items' style='margin:1em 0.25em 0.25em 0;'><!-- { -->
        <ol><li class='public' style='border-bottom:none !important;'>";
    
            if (isset($userTagInfo['itemid']))
            {
                // Merge the statistics into the user tag info
                $userTagInfo = array_merge($userTagInfo, $stats);
                echo $gTagging->itemHtml($userTagInfo);
            }
            else
            {
                // Merge the statistics into the tag info
                $info = array_merge($info, $stats);
                echo $gTagging->itemHtml($info);
            }
            
            echo "</li>";
    
            // Construct the timeline
            echo timeline($taggerInfo);
    
            /*
             * Walk through the set of taggers and generate item details that
             * consists of the description set by any/all taggers.
             *
             * In parallel, construct a $tagHistory of taggers.
             */
            $tsLastDesc = '';
            $tsLastTag  = '';
            $sectionCnt = 0;
            for ($idex = 0; $idex < $taggers; $idex++)
            {
                $cur =& $taggerInfo[$idex];
    
                $timestamp = strtotime($cur['tagged_on']);
    
                $tsStr     = strftime('%B %Y', $timestamp);
                $classMod  = '';
                if ($gTagging->getCurUserId() == $cur['userid'])
                {
                    $classMod .= '-self';
                }
    
                /*
                 * Add to our tag history.
                 *
                 * first, do we need a new timestamp divider?
                 */
                if ($tsStr != $tsLastTag)
                {
                    $taggerCnt = 0;    // taggers displayed for this timestamp
                    if (! empty($tsLastTag))
                        $tagHistory .= "
         </div>";
    
                    // Generate a new timestamp divider.
                    $tsLastTag = $tsStr;
    
                    $tagHistory .= "
        <div class='timestamp'>{$tsStr}</div>
         <div class='taggers'>";
                }
    
                if ($taggerCnt > 0) $tagHistory .= ', ';
                $tagHistory .= "<a class='tagger{$classMod}' href='{$gTagging->mBaseUrl}/{$cur['userName']}/'>{$cur['userName']}</a>";
                $taggerCnt++;
    
    
                // If there is no description, we're finished with this item.
                if (empty($cur['description']))
                    continue;
    
                /*
                 * This item has a description, so present this description.
                 */
                if ($tsStr != $tsLastDesc)
                {
                    // Output a new timestamp
                    $tsLastDesc = $tsStr;
                    echo "
        <div class='timestamp'>{$tsStr}</div>";
                }
    
                // Output this description
                echo "
        <div class='taggerInfo{$classMod}'>
         <div class='description'>{$cur['description']}</div>
         <div class='tagger'>
          - <a class='tagger' href='{$gTagging->mBaseUrl}/{$cur['userName']}/'>{$cur['userName']}</a>
         </div>
        </div>";
    
            }
    
            echo "
        </li>
       </div><!-- Items } -->
    ";
        }
        else
        {
            echo "
       <div style='font-size:1.25em;font-weight:bold;'>
        There is no connexion history for this url.
       </div>";
        }
    
        // Finish the item list and begin the tag list
        echo "
      </div><!-- ItemList } -->
     </div><!-- left } -->
     <div id='right'><!-- { -->
      <div id='Tags-top' class='TagList'><!-- { -->
       <div class='title'><!-- { -->
        <h3>Tags</h3>
       </div><!-- title } -->";
    
        if ($id > 0)
        {
            /*
             * Retrieve the tags associated with this item and present them as a
             * cloud
             */
            $tags = $gTagging->mTagDB->tagDetails(
                                $gTagging->mTagDB->tagsOfItems(array($id)));
    
            echo "
     <div style='margin:0 0.25em 0 0.5em;padding:0;'>";
    
            echo $gTagging->cloudHtml($tags,
                                      $gTagging->mBaseUrl . '/tag/%tag%');
    
            echo "
     </div>";
        }
    
        // Finish the output by presenting the tag history.
        echo "
        {$tagHistory}
      </div><!-- } -->
     </div><!-- } -->
    </div><!-- content } -->";
        echo $gTagging->pageFooter();

        return (true);
    }

    /** @brief  Display linksfor for the current user.
     *
     *  @return true (if processed), false otherwise
     */
    function linksFor()
    {
        global  $gTagging;
        /*echo "<div>main_for: <b>params</b><pre>\n";
        print_r($params);
        echo "</pre></div>\n";*/
    
        // Set the area we're in
        $gTagging->mArea = 'links for you';
        $title           = 'Links for';
        if (! empty($params['user']))
        {
            $user = $params['user'];
        }
        else
        {
            $user = $gTagging->mCurUser['name'];
        }
        $title .= " ". $user;
        $tag    = "for:$user";

        // If a tag doesn't already exist, create it (otherwise retrieve it)
        $tagid = $gTagging->mTagDB->tagCreate($tag);
        //printf ("%s: tag[%s], id[%u]\n", $funcId, $tag, $tagid);
    
        /*
         * Generate the HTML to represent the posting form
         */
        echo $gTagging->htmlHead($title);
        echo $gTagging->pageHeader(false, null, "for / $user");
    
        if ($user != $gTagging->mCurUser['name'])
        {
            printf("You must be logged in as <i>%s</i> to view this page.",
                   $user);
        }
        else
        {
            $this->view($user, $tag, false);
        }
    
    
        echo $gTagging->pageFooter();

        return (true);
    }
    
    /** @brief  Generate a page used to enter a new item.
     *  @param  params  An array of provided parameters.
     *
     *  Parameters:
     *      name/title  The name/title for this new item.
     *      url         The url for this new item.
     *      description The description for this new item.
     *      tags        The tags for this new item.
     *      id          If non-zero, the item-identifier this
     *                  new item is associated with.
     *      closeAction What action should occur on completion:
     *                          - close     attempt to close the window
     *                          - redirect  redirect to 'url'
     */
    function post($params)
    {
        $funcId = 'Main::post';
        global  $gTagging;
    
        if (empty($params))
            $params = $_REQUEST;

        /*
        echo "<pre>$funcId: params:\n";
        print_r($params);
        echo "</pre>\n";
        // */
    
        // Process our incoming parameters
        $name        = (! empty($params['name'])
                            ? $params['name']
                            : $params['title']);
        $url         = $params['url'];
        $description = $params['description'];
        $tags        = $params['tags'];
        $itemid      = (int)$params['id'];
        $fullHeader  = $params['fullHeader'];
        $closeAction = (! empty($params['closeAction'])
                            ? strtolower($params['closeAction'])
                            : 'redirect');
    
        $avgRating   = 0.0;
        $votes       = 0;
        $taggers     = 0;
    
        // If a URL was provided, retrieve it's identifier (if it has one).
        if (! empty($url))
        {
            $itemid = $gTagging->mTagDB->itemId($url);
        }
    
        // Set the area we're in
        $gTagging->mArea = 'post';
        $title           = 'Posting';
        if (! empty($url))
            $title .= " about $url";
    
        /*
        printf ("%s: url[%s], id[%u]<pre>\n",
                $funcId, $url, $itemid);
        print_r($params);
        echo "</pre>\n";
        printf ("curUser[%s]<br />\n",
                var_export($gTagging->mCurUser, true));
        // */
    
        if ($itemid > 0)
        {
            /*
             * This item has been previously tagged.  See if it has been tagged
             * by the current user.
             */
            $info = $gTagging->mTagDB->userItem(
                                            $gTagging->getCurUserId(),
                                            $itemid);
    
            // Also retrieve item statistics
            $stats = $gTagging->mTagDB->itemStats($itemid);
    
            if (is_array($info))
            {
                // Remember any new name, description, and tags that were passed
                // in.
                if (! empty($name))         $info['name']        = $name;
                if (! empty($description))  $info['description'] = $description;
                if (! empty($tags))         $info['tags']        = $tags;
    
                // Merge the statistics with the retrieve item information.
                $info = array_merge($info, $stats);
            }
            else
            {
                /*
                 * There is no user-specific information for this item so we
                 * will create $info below.  For now, extract the stats into
                 * local variables so the information is available for that
                 * creation.
                 */
                extract($stats);
            }
        }
    
        if (! is_array($info))
        {
            $info     = array(
                                'itemid'        => $itemid,
                                'name'          => $name,
                                'url'           => $url,
                                'description'   => $description,
                                'tags'          => $tags,
                                'rating'        => 0,
                                'is_favorite'   => false,
                                'is_private'    => false,
                                'taggers'       => $taggers,
                                'votes'         => $votes,
                                'avgRating'     => $avgRating
                             );
        }
    
        /*
         * Generate the HTML to represent the posting form
         */
        echo $gTagging->htmlHead($title);
        echo $gTagging->pageHeader(! $fullHeader
                                        ? true
                                        : false);
        ?>
    <script type='text/javascript'>
    function finishedLoading()
    {
        $('global_loading').hide();
        if (window.focus)   {window.focus();}
    }
    </script>
    <body topmargin='0' leftmargin='0' onload='finishedLoading()'><?php
    
        echo $gTagging->editItemHtml($info, true, $closeAction);
        flush();
    
    ?>
    </body>
    </html>
    <?php

        return (true);
    }

    /** @brief  A catch all, Not-Yet-Implemented display.
     */
    function    nyi($area)
    {
        global  $gTagging;
    
        // Set the area we're in
        $gTagging->mArea = $area;
        $title           = ucfirst($area);
    
        /*
         * Generate the HTML to represent the posting form
         */
        echo $gTagging->htmlHead($title);
        echo $gTagging->pageHeader(false, $tags);
    
        echo "<div class='helpQuestion'>Not yet implemented</div>\n";
    
        echo $gTagging->pageFooter();

        return (true);
    }
}
?>
