<?php
/** @file
 *  
 *  This is a connexions plug-in that implements the watchlist namespace.
 */
require_once('lib/tagging.php');

class Watchlist extends PluginController
{
    /** @brief  Provide the main watchlist view of user(s) and tag(s).
     *  @param  user    The name/id of the user to present.
     *  @param  tags    A list of comma-separated tags to limit the display.
     *
     *  @return true (if processed), false otherwise
     */
    function view($user, $tags)
    {
        $funcId = 'Watchlist::view';
        global  $gTagging;

        if (empty($user))
        {
            // Default to the current user.
            $user = $gTagging->mCurUser['name'];
        }

        if ($gTagging->setTagger($user) === false)
        {
            // This is NOT a real user - let someone else handle it.
            /*printf ("%s: user[%s] is NOT a user<br />\n",
                    $funcId, $user);*/
            return false;
        }
        /*printf("%s: user[%s], tags[%s] wrap[%u]<br />\n",
                $funcId, $user, $tags, $wrap);*/

        /*
        echo "<pre>Main::view:\n";
        echo "_SESSION\n";
        print_r($_SESSION);
        echo "</pre>\n";
        // */
    
        // Establish defaults
        $limitTags = null;    // Default to recovering the tag(s)  from SESSION

        // Set the area we're in
        $gTagging->mArea = 'watchlist';
        $title           = 'Watchlist';
    
        if ($user != $gTagging->mCurUser['name'])
        {
            $gTagging->mArea .= " / $user";
            $title           .= "/$user";
        }


        // Any specified tags are used to limit the display to items with ALL
        // tags
        if (! empty($tags))
        {
            /* The incoming tag string should have tags separated by ','. */
            $tags      = rawurldecode($tags  );
            $limitTags = $gTagging->mTagDB->tagIds($tags);
    
            /*printf (":[%s] => {%s}<br />\n",
                    $tags, implode(', ', $limitTags));*/
        }
        else
        {
            //printf ("<br />\n");
            $limitTags = array();
        }
    
        // Retrieve the watchlist, including watchers and relations.
        $watchList    = $gTagging->mTagDB->watchlist($user);
        $watchListIds = $gTagging->mTagDB->watchListIds($user);

        /*
         * Generate the HTML for the header.
         */
        echo $gTagging->htmlHead($title);
        echo $gTagging->pageHeader(false, $limitTags);
        flush();
    
        /*
         * Generate the HTML for the list of tagged items.
         */
        echo "
    <div id='content'><!-- { -->
     <div id='left'><!-- { -->
      <div id='Items-top' class='ItemList'><!-- { -->";
        flush();
    
        if (is_array($watchListIds) && count($watchListIds) > 0)
        {
            echo $gTagging->itemsArea($watchListIds,
                                      $limitTags,
                                      $itemsOrder);
        }
        else
        {
            echo "
       <div class='title'>
        <h3>This watchlist is currently empty</h3>
       </div>";
        }

        echo "
      </div><!-- ItemList } -->
     </div><!-- } -->";
        flush();
    
        /*
         * Generate the HTML for the set of tags.
         */
        echo "
     <div id='right'><!-- { -->";
        flush();
    
        echo "
      <div id='WatchList-top' class='WatchList'><!-- { -->";
        flush();

        echo $gTagging->watchListArea($watchList);

        echo "
       <br style='clear:left;' />
      </div><!-- WatchList } -->";
        flush();
    
        echo "
      <div id='Tags-top' class='TagList'><!-- { -->";
        flush();
    
        if (is_array($watchListIds) && count($watchListIds) > 0)
        {
            echo $gTagging->tagsArea($watchListIds,
                                     $limitTags);
        }
        else
        {
            echo "
       <br />";
        }
    
        echo "
      </div><!-- TagList } -->
     </div><!-- right } -->
    </div><!-- content } -->";
        flush();
    
        echo $gTagging->pageFooter();

        return (true);
    }

    function viewTags($tags)
    {
        $funcId = 'WatchList::viewTags';

        // Redirect
        return $this->view(null, $tags);
    }
}
?>

