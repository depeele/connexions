<?php
/** @file
 *
 *  Present information about RSS feeds
 */

/** @brief  Present help/rss information. */
function rss_main($params)
{
    global  $gTagging;

    $rssFeed = $gTagging->mBaseUrl . '/feeds/rss';
    $rssHelp = $gTagging->mBaseUrl . '/help/rss';

    ?>
<div class='helpQuestion'>RSS Feeds</div>
<div class='helpAnswer'>
 <ul>
  <li><a href='http://en.wikipedia.org/wiki/RSS'>RSS</a> (<b>Really Simple
  Syndication</b>) is an XML-based format used for easily sharing information
  in a programmatically accessible fashion. Specifically, RSS can be used for
  any dynamic information that can be expressed as a list. The classic example
  is news headlines and snippets.</li>
  <li>You can use RSS feeds at connexions to fetch, remix, and mashup a varity
  of data for use in your own custom application and browser-based presentation
  styles.</li>
 </ul>
</div>
<div class='helpQuestion'>Available RSS Feeds</div>
<div class='helpAnswer'>
 <ul>
  <li><a href='<?echo $rssHelp;?>/posts'>posts</a> - Return a list of
  items that have been tagged (e.g. 
  <a href='<?echo $rssFeed;?>/posts'><?echo $rssFeed;?>/posts</a>)</li>
 </ul>

 <blockquote>If the RSS feed is retrieved using <b>https</b>, the <b>posts</b>
 will automatically be for you.  Otherwise, they will be all non-private items
 from all users.</blockquote>
</div>
<div class='helpQuestion'>RSS Parameters</div>
<div class='helpAnswer'>
 Any RSS feed may be passed parameters using the following form:
 <blockquote style='margin-top:0px;'>
  <b><?echo $rssFeed;?>/<i>posts</i>/?<i>parameters</i></b>
 </blockquote>

 Available parameters for <a href='<?echo $rssHelp;?>/posts'>posts</a>:
 <ul style='margin-top:0px;'>
  <li><b>user</b>: Specify a specific user to retrieve posts for (* == all).</li>
  <li><b>count</b>: The maximum number of items to return (default <b>100</b>.</li>
  <li><b>sort</b>: The sort order (note, if <i>count</i> is specified,
          <i>sort</i> will modify which items are returned):
   <ul style='margin-top:0px;'>
    <li><b>recent</b>: most recent first</li>
    <li><b>oldest</b>: oldest first</li>
    <li><b>taggers</b>: by number of taggers, highest first</li>
    <li><b>voters</b>: by total votes, highest first</li>
    <li><b>ratings</b>: by rating, highest first</li>
   </ul>
 </ul>
</div><?php
}

/** @brief  Present help/rss/posts information. */
function rss_posts($params)
{
    global  $gTagging;

    $rssFeed = $gTagging->mBaseUrl . '/feeds/rss/posts/';

    ?>
<div class='helpQuestion'>RSS <b>posts</b> Parameters</div>
<div class='helpAnswer'>
 The RSS <b>posts</b> feed may be passed parameters using the following form:
 <blockquote style='margin-top:0px;'>
  <b><?echo $rssFeed;?>?<i>parameters</i></b>
 </blockquote>

 Available parameters for <a href='<?echo $rssFeed;?>?'>posts</a>:
 <ul style='margin-top:0px;'>
  <li><b>user</b>: Specify a specific user to retrieve posts for (* == all).</li>
  <li><b>count</b>: The maximum number of items to return (default <b>100</b>.</li>
  <li><b>sort</b>: The sort order (note, if <i>count</i> is specified,
          <i>sort</i> will modify which items are returned):
   <ul style='margin-top:0px;'>
    <li><b>recent</b>: most recent first</li>
    <li><b>oldest</b>: oldest first</li>
    <li><b>taggers</b>: by number of taggers, highest first</li>
    <li><b>voters</b>: by total votes, highest first</li>
    <li><b>ratings</b>: by rating, highest first</li>
   </ul>
 </ul>
</div>

<div class='helpQuestion'>What's included in the RSS <b>posts</b> feed?</div>
<div class='helpAnswer'>
 The RSS <b>posts</b> feed is an RSS 2.0 channel consisting of a single RSS
 <i>item</i> for every matching <b>Connexions</b> item.  Each item will
 include:
 <ul>
  <li><b>title</b>: The name of this item.</li>
  <li><b>description</b>: The description of this item.</li>
  <li><b>link</b>: The URL of the item.</li>
  <li><b>pubDate</b>: The date/time this item was tagged (Y-m-d H:M:S)</li>
  <li><b>author</b>: The email address of the tagging user.</li>
  <li><b>category</b>: One for each tag associated with the item.</li>
 </ul>
</div><?php
}
