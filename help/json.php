<?php
/** @file
 *
 *  Present information about JSON feeds
 */

/** @brief  Present help/json information. */
function json_main($params)
{
    global  $gTagging;

    $jsonFeed = $gTagging->mBaseUrl . '/feeds/json';
    $jsonHelp = $gTagging->mBaseUrl . '/help/json';

    ?>
<div class='helpQuestion'>JSON Feeds</div>
<div class='helpAnswer'>
 <ul>
  <li><a href='http://en.wikipedia.org/wiki/JSON'>JSON</a> (<b>JavaScript Object
  Notation</b>) is a lightweight data-interchange format aimed at eeasy use in
  browser-based code.  It is also used in many desktop or server-side
  programming environments.</li>
  <li>You can use JSON feeds at connexions to fetch, remix, and mashup a varity
  of data for use in your own custom application and browser-based presentation
  styles.</li>
 </ul>
</div>
<div class='helpQuestion'>Available JSON Feeds</div>
<div class='helpAnswer'>
 <ul>
  <li><a href='<?echo $jsonHelp;?>/posts'>posts</a> - Return a list of
  items that have been tagged (e.g.  
    <a href='<?echo $jsonFeed;?>/posts'><?echo $jsonFeed; ?>/posts</a>)</li>
  <li><a href='<?echo $jsonHelp;?>/tags'>tags</a> - Return a list of tags
  (e.g. <a href='<?echo $jsonFeed;?>/tags'><?echo $jsonFeed;?>/tags</a>).</li>
 </ul>

 <blockquote>If the JSON feed is retrieved using <b>https</b>, the <b>posts</b>
 or <b>tags</b> will automatically be for you.  Otherwise, they will be all
 non-private items from all users.</blockquote>
</div>
<div class='helpQuestion'>JSON Parameters</div>
<div class='helpAnswer'>
 Any JSON feed may be passed parameters using the following form:
 <blockquote style='margin-top:0px;'>
  <b><?echo $jsonFeed;?>/<i>[posts|tags]</i>/?<i>parameters</i></b>
 </blockquote>

 Available parameters for both <a href='<?echo $jsonHelp;?>/tags'>tags</a> and <a href='<?echo $jsonHelp;?>/posts'>posts</a>:
 <ul style='margin-top:0px;'>
  <li><b>user</b>: Specify a specific user to retrieve posts or tags for (* == all).</li>
  <li><b>callback</b>: Wrap the object definition in a function call with the given name (e.g. process(...)).</li>
  <li><b>count</b>: The maximum number of items to return.</li>
 </ul>

 <br />
 Additional parameters for <a href='<?echo $jsonHelp;?>/tags'>tags</a>:
 <ul style='margin-top:0px;'>
  <li><b>sort</b>: The sorting order (alpha|count).  This will effect which items are returned if <b>count</b> is specified.</li>
  <li><b>atleast</b>: Include only tags that have been used for at least this many items.</li>
 </ul>
</div><?php
}

/** @brief  Present help/json/posts information. */
function json_posts($params)
{
    global  $gTagging;

    $jsonFeed = $gTagging->mBaseUrl . '/feeds/json/posts/';

    ?>
<div class='helpQuestion'>JSON <b>posts</b> Parameters</div>
<div class='helpAnswer'>
 The JSON <b>posts</b> feed may be passed parameters using the following form:
 <blockquote style='margin-top:0px;'>
  <b><?echo $jsonFeed;?>?<i>parameters</i></b>
 </blockquote>

 Available parameters for <a href='<?echo $jsonFeed;?>?'>posts</a>:
 <ul style='margin-top:0px;'>
  <li><b>user</b>: Specify a specific user to retrieve posts for (* == all).</li>
  <li><b>callback</b>: Wrap the object definition in a function call with the given name (e.g. process(...)).</li>
  <li><b>count</b>: The maximum number of items to return.</li>
 </ul>
</div>

<div class='helpQuestion'>What's included in the JSON <b>posts</b> feed?</div>
<div class='helpAnswer'>
 The JSON <b>posts</b> feed is comprised of a JSON object named
 '<b>Connexions</b>' that contains a single item (<b>posts</b>).  This item is
 an array of objects that each contain the following items:
 <ul>
  <li><b>url</b>: The URL of the item.</li>
  <li><b>name</b>: The name of this item.</li>
  <li><b>description</b>: The description of this item.</li>
  <li><b>user</b>: The <b>Connexions</b> name for the tagging user.</li>
  <li><b>rating</b>: The rating given to this item.</li>
  <li><b>is_favorite</b>: Is the item marked as a favorite?</li>
  <li><b>is_private</b>: Is the item marked as private?  This will always be
  false/0 if <i>user</i> is not you.</li>
  <li><b>tagged_on</b>: The date/time this item was tagged (Y-m-d H:M:S)</li>
  <li><b>timestamp</b>: A UNIX timestamp version of <i>tagged_on</i></li>
  <br />
  <li><b>userCount</b>: The number of users that have tagged this item.</li>
  <li><b>ratingCount</b>: The number of users that have rated this item.</li>
  <li><b>ratingSum</b>: The sum of all ratings.</li>
  <br />
  <li><b>users</b>: An alternate name for <i>userCount</i></li>
  <li><b>taggers</b>: An alternate name for <i>userCount</i></li>
  <li><b>votes</b>: An alternate name for <i>ratingCount</i></li>
  <li><b>avgRating</b>: <i>ratingSum</i> / <i>ratingCount</i></li>
 </ul>
</div><?php
}

/** @brief  Present help/json/tags information. */
function json_tags($params)
{
    global  $gTagging;

    $jsonFeed = $gTagging->mBaseUrl . '/feeds/json/tags/';

    ?>
<div class='helpQuestion'>JSON <b>tags</b> Parameters</div>
<div class='helpAnswer'>
 The JSON <b>tags</b> feed may be passed parameters using the following form:
 <blockquote style='margin-top:0px;'>
  <b><?echo $jsonFeed;?>?<i>parameters</i></b>
 </blockquote>

 Available parameters for <a href='<?echo $jsonFeed;?>?'>tags</a>:
 <ul style='margin-top:0px;'>
  <li><b>user</b>: Specify a specific user to retrieve tags for (* == all).</li>
  <li><b>callback</b>: Wrap the object definition in a function call with the given name (e.g. process(...)).</li>
  <li><b>count</b>: The maximum number of items to return.</li>
  <li><b>sort</b>: The sorting order (alpha|count).  This will effect which items are returned if <b>count</b> is specified.</li>
  <li><b>atleast</b>: Include only tags that have been used for at least this many items.</li>
 </ul>
</div>

<div class='helpQuestion'>What's included in the JSON <b>tags</b> feed?</div>
<div class='helpAnswer'>
 The JSON <b>tags</b> feed is comprised of a JSON object named
 '<b>Connexions</b>' that contains a single item (<b>tags</b>).  This item is
 an object comprised of tag-names and their associated counts.
</div><?php
}
