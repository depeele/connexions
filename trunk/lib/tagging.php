<?php
/** @file
 *
 *  The Tagging class implements the control for connexions.
 *
 *  It links together the User, Item, and Tag classes, provides plug-in,
 *  and structural presentation components.
 *
 * <link rel="alternate" type="application/rss+xml"
 *                  title="RSS" href="https://www.org/rss.php" />
 */

// First, setup the include path so we can always get to the necessary files
$libDir  = dirname(__FILE__);
$incPath = get_include_path() .                         // Original inc path
                PATH_SEPARATOR .
            $libDir .                                   // plus '$libDir'
                PATH_SEPARATOR .                     
            $libDir . DIRECTORY_SEPARATOR . "..";       // plus '$libDir/..'
set_include_path($incPath);

require_once('config.php');
if ($gProfile === true)
{
    if (empty($gProfileFile))   $gProfileFile = 'profile-main.txt';

    require_once('lib/profile.php');
    profile_init($gProfileFile);
}

global  $gTagging;

// Initialize our session
session_start();
/*echo "<pre>_SESSION:\n";
print_r($_SESSION);
//foreach ($_SESSION as $key => $val)
//{
//    unset($_SESSION[$key]);
//}
echo "</pre>\n";*/

require_once('lib/tagdb.php');
require_once('lib/user.php');
require_once('lib/cloud.php');
require_once('lib/paging.php');

/** @brief  The global tagging instance that provides access to all
 *          tagging data and presentation.
 */
$gTagging = new Tagging();

/*echo "_REQUEST<pre>\n";
print_r($_REQUEST);
echo "</pre>\n";*/

/** @brief  Global Item page renderer.
 *  @param  pager   The pager controlling this list of items.
 *  @param  items   The list of items to render.
 *
 *  @return The HTML representing this page of items.
 */
function itemsHtml(&$pager, &$items)
{
    global  $gTagging;

    return $gTagging->itemsHtml($items);
}

/** @brief  Global People page renderer.
 *  @param  pager   The pager controlling this list of users.
 *  @param  users   The list of users to render.
 *
 *  @return The HTML representing this page of users.
 */
function peopleHtml(&$pager, &$users)
{
    global  $gTagging;

    return $gTagging->peopleHtml($pager, $users);
}

/***************************************************************************
 * Tagging class.
 *
 */
class   Tagging
{
    var $mVersion       = array('major' => 0,
                                'minor' => 7,
                                'build' => 20070327);
    var $mBaseUrl       = '';
    var $mFullUrl       = '';
    var $mTagDB         = null;     // the main interface to the
                                    // tagging database

    var $mTagSize       = 0;        // The maximum size of a single tag

    var $mTimeNow       = 0;
    var $mCurUser       = array();

    var $mTagger        = array();
    var $mSearch        = '';
    var $mSearchContext = 'connexions';

    var $mArea          = '';       // The name of the area we're displaying
    var $mAreaUrl       = '';       // The URL  of the area we're displaying

    // Session identifiers
    var $mUsersId       = 'Tagging:userList';
    var $mTagsId        = 'Tagging:tagList';
    var $mItemsOrderId  = 'Tagging:itemsArea:order';
    var $mUsersOrderId  = 'Tagging:peopleArea:order';
    var $mTagsOrderId   = 'Tagging:tagsArea:order';
    var $mTagsTypeId    = 'Tagging:tagsArea:type';
    var $mTagsLimitId   = 'Tagging:tagsArea:limit';

    var $profile        = null;

    /** @brief  Initialize all required global information. */
    function    Tagging()
    {
        global  $gBaseUrl;
        global  $_REQUEST;
        global  $db_options;
    
        /*echo "Tagging: REQUEST<pre>\n";
        print_r($_REQUEST);
        echo "</pre>\n";*/

        $this->mTimeNow = time();
        $this->mBaseUrl = $gBaseUrl;

        $this->mFullUrl = ($_SERVER['HTTPS'] === 'on'
                            ? "https" : "http") . "://" .
                            $_SERVER['HTTP_HOST'] . $this->mBaseUrl;

        $this->mTagDB = new TagDB($db_options);

        /* Retrieve the maximum tag size from the database. */
        $this->mTagSize = $this->mTagDB->tagsMaxSize();

        // Identify and (if possible) authenticate the current user.
        $this->mCurUser = identify_user($this->mTagDB);
        //$this->mTagger  = $this->mCurUser;

        /*echo "Tagging: curUser<pre>\n";
        print_r($this->mCurUser);
        echo "</pre>\n";*/
    
        // If the profile class has been included, turn profiling on.
        global  $gProfile;
        if (is_a($gProfile, 'Profile'))
        {
            $this->profile =& $gProfile;
        }
    }

    /** @brief  Set the tagger of interest.
     *  @param  tagger  The tagger of interest.
     *
     *  The tagger of interest MAY be different than the current user.
     *
     *  @return true on success (valid tagger), false otherwise.
     */
    function setTagger($tagger)
    {
        if (empty($tagger))
            return false;

        $taggerInfo = $this->mTagDB->user($tagger);
        if (! is_array($taggerInfo))
            return false;

        /*printf ("setTagger(%s): [%s]<br />\n",
                $tagger, var_export($taggerInfo, true));*/

        $taggerInfo['links'] = null;
        $taggerInfo['tags']  = null;

        /*
         * Retrieve information about this tagger
         * (e.g. number of items, number of tags).
         */
        if ($taggerInfo['totalItems'] < 1)
            $taggerInfo['totalItems'] =
                    $this->mTagDB->userItemsCount($taggerInfo['userid'],
                                                  null, // no tag limits
                                                  $this->authenticatedUserId());
    
        if ($taggerInfo['totalTags'] < 1)
            $taggerInfo['totalTags']  =
                    $this->mTagDB->tagsCount($taggerInfo['userid']);

        $this->mTagger = $taggerInfo;
    
        /*printf ("<b>%d links, %d tags for tagger %u(%s)</b><br />\n",
                $this->mTagger['totalItems'],
                $this->mTagger['totalTags'],
                $this->mTagger['userid'],
                $this->mTagger['name']);*/

        return true;
    }

    /** @brief  Is the current user authenticated?
     *
     *  @return false (NO), else, the id of the current, authenticated user.
     */
    function authenticatedUserId()
    {
        if ((! $this->mCurUser['authenticated']) ||
            ($this->mCurUser['userid'] < 1))
            return (false);

        return ($this->mCurUser['userid']);
    }

    /** @brief  Get the id of the current user.
     *
     *  @return The id of the current user (0 if none).
     */
    function getCurUserId()
    {
        return ($this->mCurUser['userid']);
    }

    /***********************************************************************
     * General output routines.
     *
     */

    /** @brief  Generate and return the generic HTML header.
     *  @param  title   The title of this page.
     *  @param  alternates  An array of assiciative arrays containing
     *                      information about alternate forms:
     *                          - type  The type of the alternate
     *                                  (e.g. application/rss+xml).
     *                          - title The title of the alternate.
     *                          - href  The URL to the alternate.
     *  @param  jsLibs      An array of javascript libraries to include.
     *
     *  @return The HTML
     */
    function    htmlHead($title = '', $alternates = null, $jsLibs = null)
    {
        global  $gJsDebug;

        /*
         * Generate the HTML head information including the
         * javascript that we require along with any CSS.
         */
//<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
//<!DOCTYPE xhtml PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        $html = "
<html>
<head>
 <title>{$title}</title>
 <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
 <meta name='robots' content='noarchive,nofollow,noindex' />

 <script type='text/javascript'>
  var baseUrl  = '{$this->mBaseUrl}';
  var gTagSize = {$this->mTagSize};
 </script>

 <script src='{$this->mBaseUrl}/js/prototype.js'   type='text/javascript'>
    </script>
 <script src='{$this->mBaseUrl}/js/scriptaculous/scriptaculous.js'
    type='text/javascript'></script>";

        if ($gJsDebug === true)
        {
            $html .= "
 <script src='{$this->mBaseUrl}/js/jslog.js'       type='text/javascript'>
    </script>";
        }

        $html .= "
 <script src='{$this->mBaseUrl}/js/tagging.js'     type='text/javascript'>
    </script>";

        if (is_array($jsLibs))
        {
            for($idex = 0; $idex < count($jsLibs); $idex++)
            {
                $path =& $jsLibs[$idex];
                if (! preg_match("#^{$this->mBaseUrl}#", $path))
                    $path = $this->mBaseUrl .'/js/'. $path;

                $html .= "
 <script src='{$path}' type='text/javascript'></script>";
            }
        }

        $html .= "

 <link rel='stylesheet' type='text/css' href='{$this->mBaseUrl}/css/tagging.php' />";

        if (is_array($alternates))
        {
            foreach ($alternates as $key => $alt)
            {
                $html .= "\n <link rel='alternate' ";
                
                foreach ($alt as $name => $val)
                {
                    $html .= sprintf('%s="%s" ',
                                     $name, 
                                     htmlspecialchars($val));
                } 
                $html .= "/>";
            }
        }

        $html .= "
</head>";

        return ($html);
    }

    /** @brief  Given a page menu associative array, generate the HTML for the
     *          menu.
     *  @param  pageMenu    The page menu.
     *  @param  firstItem   Is the first item in the menu the first item
     *                      displayed (used primarily when 'logged in as ...'
     *                      has been output)?
     *
     *  @return The HTML
     */
    function pageMenu($pageMenu, $firstItem = true)
    {
        $curUrl = $_SERVER['SCRIPT_URL'];
        $html   = '';

        /*
         * pageMenu is an associative array of:
         *  - menuType => associative menu array
         *      where menuType is:
         *          - auth      For authenticated users
         *          - unauth    For unauthenticated users
         *          - all       For all users
         */
        foreach($pageMenu as $menuType => $menu)
        {
            $curUserId = $this->authenticatedUserId();

            if ($menuType == 'auth')
            {
                if ($curUserId === false)
                    continue;
            }
            else if ($menuType == 'unauth')
            {
                if ($curUserId !== false)
                    continue;
            }

            /*
             * Each menu is an associative array of:
             *  menuName => menu URL pattern
             *
             * Here, the menuName matches the setting of '$this->mArea' and
             * the menu URL pattern represents the URL to that area.  This
             * pattern may have one or more special markes that need to be
             * replace here (below).
             */
            foreach ($menu as $menuName => $menuUrl)
            {
                if ($firstItem) $firstItem = false;
                else            $html .= " | ";

                /*
                 * Replace any special markers in the URL:
                 *  - %base_url%
                 *  - %user_name%
                 */
                $url = $menuUrl;
                $url = preg_replace('/%base_url%/',
                                    $this->mBaseUrl, $url);
                $url = preg_replace('/%user_name%/',
                                    $this->mCurUser['name'], $url);

                if (preg_match('/%count%/', $menuName))
                {
                    // Special items that we modify to include a count.
                    $count = '';
                    switch ($menuName)
                    {
                    case 'links for you%count%':
                        // Search for items with a tag 'for:%username%'
                        $forTag = array('for:' . $this->mCurUser['name']);

                        $nItems = $this->mTagDB->userItemsCount(
                                                null,       // all users
                                                $forTag,
                                                $curUserId,
                                                $this->mCurUser['lastVisit']);
                        if ($nItems > 0)
                            $count = sprintf (" (%u)", $nItems);
                    }

                    $menuName = preg_replace('/%count%/', $count, $menuName);
                }

                /*
                 * If this menu item matches our currently displayed area,
                 * present just the name, otherwise a URL that will take
                 * you to that area.
                 */
                if (($menuName == $this->mArea) ||
                    ($url      == $curUrl))
                {
                    $html .= $menuName;
                }
                else
                {
                    $html .= "<a href='{$url}'>{$menuName}</a>";
                }
            }
        }

        return $html;
    }

    /** @brief  Generate and return the tagging page header.
     *  @param  simple          Output a simple header (true) or complete
     *                          (false).
     *  @param  tags            An array of current context tags.
     *  @param  staticPath      A static path to include in the page location.
     *  @param  search          The current search term.
     *  @param  searchContext   The current search context.
     *
     *  @return The HTML
     */
    function pageHeader($simple         = false,
                        $tags           = array(),
                        $staticPath     = null,
                        $search         = '',
                        $searchContext  = 'connexions')
    {
        global  $gPageMenu;

        $html = "
<body topmargin='0' leftmargin='0'>
<div id='global_loading'><img src='{$this->mBaseUrl}/images/Progress.gif' align='absmiddle' alt='' />&nbsp;Loading...</div>
<div id='header'><!-- { -->
 <div id='pageLoc'><!-- { -->
  <a id='root' href='{$this->mBaseUrl}/'>conn<img id='logo' src='{$this->mBaseUrl}/images/Tagging-Logo2.png' alt='X' align='absmiddle'/>ions</a>";

        if (! $simple)
        {
            /*
             * For this non-simple page, include current location information.
             */
            $url    = $this->mBaseUrl . '/';

            if (! empty($staticPath))
            {
                // We are displaying a page with a static path.
                $html .= " / $staticPath";
            }
            else
            {
                /*
                 * We are displaying a page with a dynamic path.
                 *
                 * Parse the curent url and route to present the header
                 * crumb.
                 */
                global $gPluginDispatcher;
                $rteParts = preg_split('#\s*/\s*#',
                                  $gPluginDispatcher->status['current_route']);
                $urlParts = preg_split('#\s*/\s*#',
                                  $gPluginDispatcher->status['request_url']);

                $url   = $this->mBaseUrl;
                $count = count($urlParts);
                for ($idex = 1; $idex < $count; $idex++)
                {
                    $arg  = $urlParts[$idex];
                    if ($rteParts[$idex] == '$tags')
                    {
                        $tagStr = $arg;
                        continue;
                    }

                    if (empty($arg))
                        continue;

                    $url  .= "/$arg";
                    $html .= " / ";
                    if ( ($arg != 'tag') && ($idex < ($count - 1)) )
                        $html .= "<a href='$url' class='path'>";

                    $html .= $arg;

                    if ($idex < ($count - 1))
                        $html .= "</a>";
                }

                // Remember the URL to this area.
                $this->mAreaUrl = $url;

                $html .= " / <input id='crumb' class='crumb' value='{$tagStr}' /><script type='text/javascript'>if(window.Crumb) Crumb.go(";
                
                if ($url == $this->mBaseUrl)
                    $html .= "null";
                else
                    $html .= "\"{$url}\"";
                $html .= ");</script>";
            }

            $html .= "
  <div id='accountCtl'><!-- { -->";

            $html .= $this->pageMenu($gPageMenu['left']);

            $html .= "
  </div><!-- accountCtl } -->";
        }

        /* Finish the page location information and begin the page control area
         */
        $html .= "
 </div><!-- } -->
 <div id='pageCtl'><!-- { -->";

        if (! $simple)
        {
            /*
             * For this non-simple page, include a search form.
             */
            $html .= "
  <form id='PageSearch' action='{$this->mBaseUrl}/search/'>
      <input name='value'  type='text' size='15' value='{$search}' />
      <select name='context'>";

            $contexts = array('connexions', 'user', 'google');
            foreach ($contexts as $context)
            {
                $html .= sprintf ("\n".
                              "       <option value='%s'%s>%s</option>",
                              $context,
                              ($context == $searchContext ? ' selected' : ''),
                              $context);
            }

            $html .= "
      </select>
      <input name='submit' type='submit' value='search' />
  </form>";

        }

        /*
         * Include account control/management (e.g. authentication information,
         * and any right page menu items).
         */
        $html .= "
  <div id='accountCtl'><!-- { -->";

        $firstItem = true;

        if ($this->authenticatedUserId() !== false)
        {
            // This user is authenticated -- show who it is and present
            // mine, settings and post
            $html .= "
   logged in as <b>{$this->mCurUser['name']}</b>";
            $firstItem = false;
        }

        $html .= $this->pageMenu($gPageMenu['right'], $firstItem);

        $html .= "
  </div><!-- accountCtl } -->
 </div><!-- pageCtl } -->
 </div><!-- pageCtl } -->
</div><!-- header } -->";

        return ($html);
    }

    /** @brief  Generate and return the tagging page footer.
     *
     *  @return The HTML
     */
    function pageFooter()
    {
        $html = "
<div id='footer'><!-- { -->
    connexions
</div><!-- } -->
<script type='text/javascript'>
$('global_loading').hide();
</script>

</body>
</html>";

        return ($html);
    }

    /** @brief  Generate the HTML for star ratings.
     *  @param  ratingId    The prefix for the unique identifier for this
     *                      rating (completed using 'id').
     *  @param  id          The unique identifier of the item being rated.
     *  @param  rating      The current rating value.
     *  @param  avgRating   The global average rating.
     *  @param  immediate   Use the RateVote javascript call to immediately
     *                      set a vote via AJAX OR the RateSet javascript call
     *                      to simply set the value for later use.
     *  @param  isActive    Should this be an active control (i.e. is the
     *                      current user the owner)?
     *
     *  Ratings can be generate to occur immediately (using AJAX) or simply
     *  set the value for later submission.
     */
    function ratingHtml($ratingId, $id, $rating, $avgRating,
                        $immediate, $isActive = true)
    {
        if ($immediate)
            $jsCall = 'RateVote';
        else
            $jsCall = 'RateSet';

        $type = substr($ratingId, strrpos($ratingId, '.')+1);
        $id   = (int)$id;

        $html = sprintf("<!-- rating[%u], avgRating[%f : %u] -->\n",
                         $rating, $avgRating, ceil($avgRating));;
        if (! $immediate)
        {
            /*
             * For this non-immediate rating item, include a hidden form
             * element for this item that will hold the value that is set.
             */
            $html .= "
       <input name='Rating' id='{$ratingId}.{$id}.Rate-input' type='hidden' value='{$rating}' />";
        }

        if ($isActive)  $active = '-active';
        else            $active = '';

        /*
         * Include the current star rating along with javascript that will
         * highlight the star(s) the mouse is over and allow a click on
         * an individual rating.
         */
        $html .= "
      <ul class='star-rating{$active}' id='{$ratingId}.{$id}.Rate' title='{$rating}' >";

        $numNames = array( 'zero','one','two','three','four','five' );
        for ($idex = 1; $idex < count($numNames); $idex++)
        {
            $level = count($numNames) - $idex;
            $class = $numNames[$idex] . '-stars';
            if ($rating == $idex)
                $class .= '-focus';

            $html .= "
     <li><a href='javascript:void(0);'
            id='{$ratingId}.{$id}.Rate.{$idex}'
            style='z-index:{$level};";
            if ($idex <= ceil($avgRating))
            {
                $html .= ' border-bottom: 1px solid #f8c95a;'.
                         ' border-top:    1px solid #f8c95a;';
                if ($idex == 1)
                    $html .= ' border-left: 1px solid #f8c95a;';
                if ($idex == ceil($avgRating))
                    $html .= ' border-right: 1px solid #f8c95a;';
            }

            $html .= "'";
            if ($isActive)
            {
                if ($idex == $rating)
                {
                    $title = "Remove your {$idex} star rating";
                }
                else
                {
                    $title = "Rate this {$type} {$idex} out of 5 star" .
                                ($idex == 1 ? '' : 's');
                }

                $html .= " title='{$title}' class='{$class}'
            onclick='{$jsCall}(this, \"$type\", {$idex})'";
            }
            else
            {
                $html .= "
            title='{$idex} out of 5' class='{$class}'";
            }

            if (($rating > 0) && ($rating > $idex) && $isActive)
            {
                $html .= " onmouseover='RateOver(this, {$idex})'".
                         " onmouseout='RateOut(this, {$idex})'";
            }

            $html .= "></a></li>";
        }

        $html .= "
      </ul>";

        return ($html);
    }

    /** @brief  Given a single tagged item, generate HTML to present
     *          information about that item.
     *  @param  info    Item details representing the item.
     *  @param  inclUrl Should the items URL be included?
     *
     *  @return The HTML.
     */
    function itemHtml(&$info, $inclUrl = false)
    {
        $funcId = 'itemHtml';
        //$this->profile_start($funcId);
        $id = $info['itemid'] * 1;

        $curUserId = $this->authenticatedUserId();

        $tag_user = $this->mCurUser;
        if (($curUserId !== false) && ($curUserId == $info['userid']))
        {
            // The current user is the one that provided this information.
            $isOwner   =  true;
            $user_info =& $info;
        }
        else
        {
            /*
             * The current user did not generate this specific tagging
             * information, so include the name of the the user that did.
             */
            $isOwner  = false;
            $tag_user = $this->mTagDB->user($info['userid']);

            // Grab any information this tagger has on this item.
            if ($tag_user !== false)
            {
                $user_info = $this->mTagDB->userItem(
                                        $tag_user['userid'],
                                        $id);
            }
            else
            {
                $user_info = array();
            }

            // Don't show the actual rating of another user.
            $info['rating']      = 0;
            $user_info['rating'] = (int)$user_info['rating'];
        }

        /*
         * Generate the HTML to display the item information.
         */
        $htmlName = htmlspecialchars(stripslashes($info['name']),   ENT_QUOTES);
        $htmlDesc = htmlspecialchars(stripslashes($info['description']),
                                                                    ENT_QUOTES);

        $itemClass = ($info['is_private'] ? "private":"public");

        $html = "
 <div id='Disp.Item.{$id}' class='{$itemClass}'><!-- { -->
  <h4>
   <a id='Disp.Item.{$id}.Name' href='{$info['url']}' title='{$htmlName}'>{$htmlName}";

        global  $gUseThumbnails;
        if ($gUseThumbnails)
        {
            $thumb = "Thumbnail/" . md5($info['url']). ".jpg";
            if (file_exists("$thumb"))
            {
                $html .= "<img src='{$this->mBaseUrl}/{$thumb}' />";
            }
        }

        $html .= "</a>
  </h4>
  <div class='details'><!-- { -->
    <div class='popularity'><!-- { -->";

        // How many non-zero ratings are there for this item?
        if ($info['ratingCount'] > 0)
        {
            $html .= "
     <div class='votes'><!-- { -->
      <span id='RateCnt{$id}'>{$info['ratingCount']}</span> rater";

            if ($info['ratingCount'] != 1)    $html .= "s";
    
            $html .= ":
     </div><!-- } -->";
        }

        $html .= "
     <div class='rating'><!-- { -->";

        if ($isOwner || ($info['rating'] > 0) || ($info['ratingCount'] > 0))
        {
            if ($info['ratingCount'] > 0)
                $avgRating = $info['ratingSum'] / $info['ratingCount'];
            else
                $avgRating = 0.0;

            $html .= $this->ratingHtml('Disp.Item', $id,
                                       $info['rating'], $avgRating,
                                       true, $isOwner);
        }

        $html .= "
     </div><!-- rating } -->";

        $html .= "
    </div><!-- popularity } -->
    <div class='whowhenwhere'><!-- { -->";

        if (! $isOwner)
        {
            /*
             * The current user did not generate this specific tagging
             * information, so include the name of the the user that did.
             */
            $html .= "by <a href='{$this->mBaseUrl}/{$tag_user['name']}'>{$tag_user['name']}</a> ";
        }

        if (! isset($info['timestamp']))
        {
            // Parse it from info['tagged_on']
            $info['timestamp'] = strtotime($info['tagged_on']);
        }

        // Include the date/time information
        $html .= "<span class='date'>" .
                 strftime('%Y.%m.%d@%H:%M:%S', $info['timestamp']);
                 "</span>";

        $html .= "
    </div><!-- whowhenwhere } -->
    <div id='Disp.Item.{$id}.Description' class='description'>{$htmlDesc}</div>
    <div class='tags'><!-- { -->";

        // Include the list of tags for this item.
        $tagStr    = '';
        $tagMarkup = '';

        /*
         * Do NOT retrieve tags if they've already been retrieved OR
         * this is a general page with no tagger identified.
         */
        if (//($this->mTagger['userid'] > 0) &&
            ((! isset($info['tags'])) || (! is_array($info['tags'])) ) )
        {
            // Retrieve all tags (for this user) associated with this item.
            $info['tags'] = $this->mTagDB->itemTags(array($tag_user['userid']),
                                                    array($id));
        }
        else
        {
            $info['tags'] = array();
        }

        $first = true;
        foreach ($info['tags'] as $idex => $tagInfo)
        {
            $tag        = $tagInfo['tag'];

            $tagStr    .= ($first ? '' : ', ') . $tag;
            $tagMarkup .= ($first ? '' : ' &middot; ') .
                          "<a href='{$this->mBaseUrl}/{$tag_user['name']}/".
                            "{$tag}'>{$tag}</a>";
            $first = false;
        }

        if (! empty($tagMarkup))
        {
            $html .= "tags: {$tagMarkup}";
        }
        $info['tags'] = $tagStr;

        $html .= "
    <div id='Disp.Item.{$id}.Tags' style='display:none;'>{$tagStr}</div>
    </div><!-- tags } -->";

        if ($inclUrl)
        {
            // The caller would like the URL to be presented, so include it now.
            $url = substr($info['url'], 0, 80);
            if (strlen($info['url']) > 80)
                $url .= '...';

            $html .= "
    <div class='url'>{$url}</div>";
        }
   
        $html .= "
  </div><!-- details } -->";

        if (true)
        {
            $html .= "
  <div class='meta'><!-- { -->";

        if ($isOwner)
        {
            $tagClass = "TagAction";
        }
        else
        {
            $tagClass = "TagActionOthers";
        }

        $md5 = md5($info['url']);

        /*
         * Generate the action information.  This is comprised of at least
         * a button presenting the total number of taggers and, depending
         * on the authentication status of the current user and whether or
         * not this item "belongs" to this user...
         */
        if ($info['userCount'] > 9999)
            $adjStyle = "style='font-size:.95em;'";
        else if ($info['userCount'] > 999)
            $adjStyle = "style='font-size:1.1em;'";
        else
            $adjStyle = "";

        $html .= "
   <div class='{$tagClass}' id='3d{$id}a'>
    <a class='Tops'  {$adjStyle}href='{$this->mBaseUrl}/details/{$md5}' id='3d{$id}o'>{$info['userCount']}</a>";

        /*
         * If this user is the item owner OR is authenticated AND has not yet
         * tagged this item, present tag actions.
         */
        if ($isOwner ||
            (($curUserId !== false) &&
             ($user_info['itemid'] != $id)) )
        {
            /*
             * Authenticated users are presented additional actions:
             *  - owner:                    favorite and private status
             *                              'edit' and 'delete' actions
             *  -authenticated (non-owner): 'tag' and 'quicklink' actions
             */
            if ($isOwner)
                $html .= "
    <a class='Bright' href='javascript:ItemDelete(\"{$id}\")' id='3d{$id}i' ></a>";
            else
                $html .= "<span class='Bright'></span>";
    //<a class='Bright' href='javascript:QuickLink(\"{$id}\")' id='3d{$id}i' ></a>";

            $html .="
    <a class='Bleft' href='";

            if ($isOwner)
                $html .= "javascript:ItemEdit(\"{$id}\")";
            else
                $html .= "javascript:ItemTag(\"{$id}\")";

            $html .= "' id='3d{$id}u'></a>";
        }

       $html .= "
   </div>

   <div class='control'><!-- { -->";

        if ($isOwner)
        {
            /*
             * Authenticated users that owns this item:
             *  - include favorite and private status.
             */
            if ($info['is_favorite'])
            {
                $html .= "
<a href='javascript:void(0);' onclick='ToggleFavorite(this, true);' id='Disp.Item.{$id}.Favorite-a'><img src='{$this->mBaseUrl}/images/Star.png' class='favorite'  alt='On' id='Disp.Item.{$id}.Favorite' title='Click to remove this link from your favorites' /></a>";
            }
            else
            {
                $html .= "
<a href='javascript:void(0);' onclick='ToggleFavorite(this, true)' id='Disp.Item.{$id}.Favorite-a'><img src='{$this->mBaseUrl}/images/Fish.png' class='favorite' alt='Off' id='Disp.Item.{$id}.Favorite' title='Click to add this link to your favorites' /></a>";
            }

            $html .= "<br />";

            if ($info['is_private'])
            {
                $html .= "
<a href='javascript:void(0);' onclick='TogglePrivacy(this, true)' id='Disp.Item.{$id}.Privacy-a'><img src='{$this->mBaseUrl}/images/Padlock.png' class='sharing'  alt='private'  id='Disp.Item.{$id}.Privacy' title='Click to make this link public' /></a>";
            }
            else
            {
                $html .= "
<a href='javascript:void(0);' onclick='TogglePrivacy(this, true)' id='Disp.Item.{$id}.Privacy-a'><img src='{$this->mBaseUrl}/images/Pad-un-lock.png' class='sharing'  alt='public' id='Disp.Item.{$id}.Privacy' title='Click to make this link private' /></a>";
            }
        }

        $html .= "
   </div><!-- control } -->
  </div><!-- meta } -->";
        }

       $html .= "
 </div><!-- Disp.Item } -->";
        if ($isOwner)
        {
            /*
             * Authenticated users that owns this item:
             *  - include a hidden edit form that is toggled using
             *    the 'edit' action included above.
             */
            $html .= "
 <div id='Edit.Item.{$info['itemid']}' style='display:none;'><!-- { -->
"           . $this->editItemHtml($info) . "
 </div><!-- Edit.Item } -->";
        }

        //$this->profile_stop($funcId);
        return ($html);
    }

    /** @brief  Given a specific set of tags, generate HTML to present the tags
     *          with javascript to add/remove individual items to/from
     *  @param  name    Name of this area.
     *  @param  state   Control state of this area.
     *  @param  visibility.
     *  @param  info    Item details representing the item being tagged.
     *  @param  tags    An associative array indicating whether or not a
     *                  specific tag is selected AND the numeric portion of the
     *                  identifier(s) of the DOM elements for all tags.
     *  @param  tagSet  The set of tag to present.
     *  @param  tagCnt  The increasing count of presented tags.
     *  @param  curTags The set of currently selected tags.
     *
     *  @return The HTML.
     */
    function tagCloudEntryHtml($name, $state,
                               &$info, &$tags, &$tagSet, &$tagCnt, &$curTags)
    {
        $funcId     = 'Tagging::tagCloudEntryHtml';
        $visibility = ($state == 'open' ? "" : " style='display:none;'");

        /*
        printf("%s: name[%s], state[%s], info{%s}, tags{%s}, tagSet{%s}<br/>\n",
               $funcId, $name, $state,
               var_export($info, true),
               var_export($tags, true),
               var_export($tagSet, true));
        */

        // Instantiate a cloud generator
        $gen = new cloud_tag();

        $gen->set_label('cloud');   // css class will be cloud0, cloud1, ...
        $gen->set_tagsizes(7);      // Use 7 font sizes

        $tagCloud = $gen->make_cloud($tagSet, 'Alpha');


        $html = "
<div class='fold'>
 <div id='{$name}.ctl' class='ctl-{$state}' onclick='javascript:toggleFold(\"{$name}\");'>{$name} tags</div>
 <div id='{$name}'     class='body'{$visibility}>
  <ul class='bundle'>
   <li class='cloud'>
    <ul>";

        //foreach ($tagSet as $tag => $count)
        foreach ($tagCloud as $tag => $item)
        {
            /*
             * Choose the color based upon the count with the color rage:
             *  #23598c, #23598d, #235992, #235994, #2359aa, #2359ff;
             *
             *  This is a range in blue of 115.
             */
            $selected   = @in_array($tag, $curTags);

            if (is_array($tags[$tag]))
            {
                $tags[$tag]['ids'][] = $tagCnt;
            }
            else
            {
                $tags[$tag] = array('selected'  => $selected,
                                    'ids'       => array($tagCnt));
            }

            $html .= sprintf("
     <li><a id='tag.%u' class='tag%s %s' title='%s post%s'
            href='javascript:swapTag(\"%s\");'>%s</a></li>",
                            $tagCnt,
                            ($selected ? ' selected' : ''),
                            $item['class'],
                            number_format($item['count']),
                                ($item['count'] == 1 ? "" : "s"),
                            $tag,
                            htmlspecialchars(stripslashes($tag)) );

            $tagCnt++;
        }

        $html .= "
    </ul>
   </li>
  </ul>
 </div>
</div>";

        return $html;
    }

    /** @brief  Given a single tagged item, generate HTML to present tags
     *          with javascript to add/remove individual items to/from the
     *          that list and provide tag entry suggestions/autocompletion.
     *  @param  info    Item details representing the item.
     */
    function tagEntryHtml(&$info)
    {
        global  $_REQUEST;

        /*
         * Add a display of tags with javascript to add/remove individual
         * items to/from the tag list and provide tag entry suggestions.
         *  - tags      Is an associative array of:
         *                  'tagName':{'selected': true/false, 'ids':[...]}
         *              Where 'ids' are the numeric portion of the DOM
         *              identifiers representing the tags (e.g. tag.0, tag.1).
         */
        $tagCnt           = 0;
        $tags             = array();
        $dispTags['cur']  = is_array($info['tags'])
                                ? $info['tags']
                                : $this->mTagDB->tagStr2Array($info['tags']);

        $tagArr = $this->mTagDB->userTags(array($this->getCurUserId()),
                                          'tag ASC');
        $userTags   = array();
        $userTagIds = array();
        foreach ($tagArr as $idex => $tagInfo)
        {
            $userTags[$tagInfo['tag']] = $tagInfo['itemCount'];
            $userTagIds[] = $tagInfo['tagid'];
        }
        $dispTags['user'] = $userTags;

        if ($info['itemid'] > 0)
        {
            // Retrieve the top 20 tags used by any user for this item.
            $tagArr = $this->mTagDB->itemTags(null,    // All users
                                              array($info['itemid']),
                                              'uniqueItemCount ASC,'.
                                                        'userCount ASC,'.
                                                        'tag ASC',
                                              20);

            $popIds  = array();
            $popTags = array();
            foreach ($tagArr as $idex => $tagInfo)
            {
                $popTags[$tagInfo['tag']] = $tagInfo['itemCount'];
                $popIds[] = $tagInfo['tagid'];
            }
            $dispTags['pop'] = $popTags;

            // Retrieve the top 20 tags of items tags with any of the popular
            // tags == T(I(t)).
            $tagArr = $this->mTagDB->tagDetails(
                            $this->mTagDB->tagsOfItems(
                                $this->mTagDB->itemsOfTags($popIds)),
                            'itemCount DESC', 20);
            $recTags = array();
            foreach ($tagArr as $idex => $tagInfo)
            {
                $recTags[$tagInfo['tag']] = $tagInfo['itemCount'];
            }
            $dispTags['rec'] = $recTags;
            //$dispTags['rec'] = array();
        }
        else
        {
            $dispTags['pop'] = array();
            $dispTags['rec'] = array();
        }

        if (count($dispTags['rec']) > 0)
        {
            // Generate HTML for the set of recommended tags.
            $state = $_REQUEST['foldState_recommended'];
            if (($state !== 'open') && ($state !== 'close'))
                $state = 'open';

            $tagHtml .= $this->tagCloudEntryHtml('recommended', $state,
                                                 $info,
                                                 $tags,
                                                 $dispTags['rec'],
                                                 $tagCnt,
                                                 $dispTags['cur']);
        }

        if (count($dispTags['pop']) > 0)
        {
            // Generate HTML for the set of popular tags.
            $state = $_REQUEST['foldState_popular'];
            if (($state !== 'open') && ($state !== 'close'))
                $state = 'open';

            $tagHtml .= $this->tagCloudEntryHtml('popular', $state,
                                                 $info,
                                                 $tags,
                                                 $dispTags['pop'],
                                                 $tagCnt,
                                                 $dispTags['cur']);
        }

        /*
         * Generate HTML for to present the user tags as a cloud
         * the included the ability to click on individual tags to add them
         * to the tag list.
         */
        $state = $_REQUEST['foldState_user'];
        if (($state !== 'open') && ($state !== 'close'))
            $state = 'open';
        $visibility = ($state == 'open' ? "" : " style='display:none;'");

        $tagHtml .= $this->tagCloudEntryHtml('your', $state,
                                             $info,
                                             $tags,
                                             $dispTags['user'],
                                             $tagCnt,
                                             $dispTags['cur']);
                                             
        $tagHtml .= "
<div id='Edit.Item.{$info['itemid']}.Suggest' class='autosuggest'><ul></ul></div>";

        /*
         * Generate the JavaScript that describes the available tags and their
         * current selection status.
         */
        $jsHtml = "
<script src='{$this->mBaseUrl}/js/autosuggest.js'   type='text/javascript'>
    </script>
<script type='text/javascript'>
tags = {";

        $first = true;
        foreach ($tags as $key => $val)
        {
            if ($first) $first = false;
            else        $jsHtml .= ",";

            $jsHtml .= '"'.$key . '":{';
            $jsHtml .= '"selected":' .($val['selected'] ? 'true' : 'false').',';
            $jsHtml .= '"ids":[' . implode(',', $val['ids']) . ']';
            $jsHtml .= '}';
        }

        $jsHtml .= "};
</script>";

        $tagHtml .= "
<script src='{$this->mBaseUrl}/js/tags.js'   type='text/javascript'></script>
<script type='text/javascript'>
tagInput   = $('Edit.Item.{$info['itemid']}.Tags');" . "
tagSuggest = $('Edit.Item.{$info['itemid']}.Suggest');" . "
new AutoSuggest(tagInput, tagSuggest, ', ', tags);
</script>";


        return ($jsHtml . $tagHtml);
    }

    /** @brief  Given a single tagged item, generate HTML to present editable
     *          information about that item.
     *  @param  info        Item details representing the item.
     *  @param  useTagEntry Should tag entry be presented?
     *  @param  closeAction What action should occur on completion:
     *                          - close     attempt to close the window
     *                          - hide      hide the edit form
     *                          - redirect  redirect to $info['url']
     *
     *  The generated HTML may be used to edit information for an existing item
     *  or enter information for a new item.
     *
     *  Note: 'info' must include 'url' and/or 'id' to allow location of
     *        information related to the item.
     *
     *  @return The HTML
     */
    function editItemHtml(&$info, $useTagEntry = false, $closeAction = 'hide')
    {
        if (! empty($info['url']))
            $id = $this->mTagDB->itemId($info['url']);
        else
            $id = (int)$info['itemid'];

        $info['itemid'] = $id;

        if (($id > 0) && (empty($info['tags'])) )
        {
            /*
            $tagArr = $this->mTagDB->itemTags(array($this->getCurUserId()),
                                              array($id));
            $tags = '';
            foreach($tagArr as $idex => $tagInfo)
            {
                if (! empty($tags)) $tags .= ', ';

                $tags .= $tagInfo['tag'];
            }
            $info['tags'] = $tags;
            */

            $tagArr = $this->mTagDB->tagNames(
                        $this->mTagDB->tags(array($this->getCurUserId()),
                                            array($id)));

            $info['tags'] = implode(', ', $tagArr);
        }

        // Begin generation of the edit form
        $html = "
<form id='Edit.Item.{$id}.Form'
      action='javascript:void(0);'><!-- { -->";

        if (isset($info['itemid']))
        {
            // This item already exists so include its unique identifier.
            $html .= "
 <input name='Id'     type='hidden' value='{$info['itemid']}' />";
        }

        if (isset($info['userid']))
        {
            // This item has a tagger so include that tagger identifier.
            $html .= "
 <input name='Tagger' type='hidden' value='{$info['userid']}' />";
        }

        /*
         * Generate a table the presents any existing information about this
         * item with inputs that permit modification of that information.
         */
        $html .= "
 <table>
  <tr valign='top' align='left'>
   <td>
    <p style='margin-bottom: 1em;'>
       <label for='Name'>Name</label><br />
       <input name='Name'
              id='Edit.Item.{$id}.Name'
              tabindex='1' type='text' size='40'
              class='textinput' value='{$info['name']}' />
    </p>
    <p style='margin-bottom: 1em;'>
       <label for='Url'>Url</label><br />
       <input name='Url'
              id='Edit.Item.{$id}.Url'
              tabindex='2'  type='text' size='40'
              class='textinput'  value='{$info['url']}' />
    </p>
    <p style='margin-bottom: 1em;'>
       <label for='Tags'>Tags</label><br />
       <input name='Tags'
              id='Edit.Item.{$id}.Tags'
              tabindex='3' type='text' size='40'
              class='textinput' autocomplete='off'
              onkeypress='return ValidateTagChar(this, event)'
              value='{$info['tags']}' />
       <br />
       <span style='display:block; margin:0 1em 0 2em; text-align:right;'>
        use comma (,) to separate tags.<br />
        <span id='Edit.Item.{$id}.Tags.Characters' style='display:none' class='Lucida'>!$&amp;+|?/\ characters are not allowed</span><br/>
        <span id='Edit.Item.{$id}.Tags.Length' style='display:none' class='Lucida'>Each tag is limited to {$this->mTagSize} characters.</span>
       </span>
    </p>
   </td>
   <td>
    <p style='margin-bottom: 1em;'>
      <label for='Description'>Description</label><br />
      <textarea name='Description'
                id='Edit.Item.{$id}.Description'
                tabindex='4' cols='40' rows='7'
                class='textarea'>{$info['description']}</textarea>
    </p>
   </td>
  </tr>
  <tr valign='top' align='left'>
   <td valign='top'>
    <table cellpadding='0' cellspacing='0'>
     <tr valign='top'>
      <td>";

        if ($info['ratingCount'] > 0)
            $avgRating = $info['ratingSum'] / $info['ratingCount'];
        else
            $avgRating = 0;

        $html .= $this->ratingHtml('Edit.Item', $id,
                                   (is_numeric($info['rating'])
                                        ? $info['rating']    : 0),
                                   $avgRating,
                                   false);

        $html .= "
      </td>
      <td valign='top'>
       <p style='padding-left: 1em;'>(rate this site";

        if ($info['ratingCount'] > 0)
        {
            $html .= sprintf(", %u already ha%s",
                             $info['ratingCount'],
                             ($info['ratingCount'] == 1 ? "s" : "ve"));
        }

        $html .= ")</p>
      </td>
     </tr valign='top'>
    </table cellpadding='0' cellspacing='0'>
   </td>
   <td valign='top' align='right'>
    <div style='float:left;width:32;padding:0px;'>
     <input name='Favorite' id='Edit.Item.{$id}.Favorite-input' type='hidden' value='" .
     ( $info['is_favorite'] ? 'On' : 'Off') . "' />
     <input name='Privacy'  id='Edit.Item.{$id}.Privacy-input' type='hidden' value='" .
     ( $info['is_private'] ? 'private' : 'public') . "' />
     <a href='javascript:void(0);' onclick='ToggleFavorite(this, false)' id='Edit.Item.{$id}.Favorite-a'><img src='{$this->mBaseUrl}/images/".
        ( $info['is_favorite'] ? 'Star.png' : 'Fish.png') .
            "' class='favorite' alt='".
        ( $info['is_favorite'] ? 'On' : 'Off') .
            "'id='Edit.Item.{$id}.Favorite' title='Click to ".
        ( $info['is_favorite'] ? 'remove this link from':'add this link to').
            " your favorites' /></a>
     <a href='javascript:void(0);' onclick='TogglePrivacy(this, false)' id='Edit.Item.{$id}.Privacy-a'><img src='{$this->mBaseUrl}/images/".
        ( $info['is_private'] ? 'Padlock.png' : 'Pad-un-lock.png') .
            "' class='sharing'  alt='".
        ( $info['is_private'] ? 'private' : 'public') .
            "' id='Edit.Item.{$id}.Privacy' title='Click to make this link " .
        ( $info['is_private'] ? 'public' : 'private') . "' /></a>
    </div>";

        /*
         * Include the save/cancel buttons with JavaScript that will
         * properly finish the editing and close/hide/redirect based upon
         * 'closeAction'.
         */
        $closeAction = strtolower($closeAction);
        $html .= "
    <input type='submit' class='buttonSubmit' value='Save' id='Edit.Item.{$id}.Submit' onclick='FinishItemEdit(\"{$id}\", \"{$closeAction}\"); return false;' />
    <input type='submit' class='buttonSubmit' value='Cancel' id='Edit.Item.{$id}.Cancel' onclick='CloseItemEdit(\"{$id}\", null, \"{$info['url']}\", \"{$closeAction}\"); return false;' />
   </td>
  </tr>
 </table>
</form><!-- } -->";

        if ($useTagEntry)
        {
            $html .= $this->tagEntryHtml($info);
        }

        return ($html);
    }

    /** @brief  Given a set of tagged items, generate and return the HTML to
     *          present them.
     *  @param  items   The set of tagged items.
     *  @param  offset  The first item to output.
     *  @param  count   The number of items to output.
     *  @param  inclUrl Include the url for each item?
     *
     *  @return The HTML
     */
    function itemsHtml(&$items, $offset = 0, $count = 0, $inclUrl = false)
    {
        if ($count < 1)
            $count = count($items);

        $html = "<ol>\n";
        $idex = 0;
        if(is_array($items))
        {
            foreach ($items as $key => $info)
            {
                if ($idex >= $offset)
                {
                    $html .= "<li id='Item{$info['itemid']}'>";
                    $html .= $this->itemHtml($info, $inclUrl);
                    $html .= "</li>\n";

                    $count--;

                    if ($count < 1)
                        break;
                }
            }
        }
        $html .= "</ol>\n";

        return ($html);
    }

    /** @brief  Given a set of tags, generate and return the HTML to present
     *          them.
     *  @param  user    Information about the current user.
     *  @param  tags    The set of tags.
     *  @param  type    What type of output is this
     *                      'recent', 'date', 'popular'
     *
     *  'tags' is an associative array with the following components:
     *      name        - the name of the tag
     *      count       - the number of times the tag has been used
     *      tagged_on   - the string represtation of the last time the tag was
     *                    used
     *      timestamp   - the UNIX timestamp equivalent to 'tagged_on'
     *      is_favorite - is this tag a favorite?
     *
     *  @return The HTML
     */
    function tagsHtml($user, $tags, $type)
    {
        $html = "";

        for ($idex = 0; $idex < count($tags); $idex++)
        //foreach ($tags as $key => $tagInfo)
        {
            $tagInfo = $tags[$idex];
            $tag     = $tagInfo['tag'];

            if ($type == 'popular')
            {
                $detail = number_format($tagInfo['count']) . " link" .
                            ($tagInfo['count'] == 1 ? "" : "s");
            }
            else
            {
                $detail = strftime("%b %e, %Y <small>%A</small>",
                                    $tagInfo['timestamp']);
            }

            if ($tagInfo['is_favorite'])
            {
                $html .= "
<img src='{$this->mBaseUrl}/images/Star.png' style='cursor:pointer;'  onclick='ToggleFavorite(this, true)' alt='On' id='Disp.Tag.{$tag}.Favorite' title='Click to remove this tag from your favorites' />";
            }
            else
            {
                $html .= "
<img src='{$this->mBaseUrl}/images/Fish.png' style='cursor:pointer;'  onclick='ToggleFavorite(this, true)' alt='Off' id='Disp.Tag.{$tag}.Favorite' title='Click to add this tag to your favorites' />";
            }

            $html .= "&nbsp;<a href='{$this->mBaseUrl}/{$user['name']}/{$tag}'>" .
                     "{$tag}</a> - {$detail}<br />\n";
        }

        return ($html);
    }

    /** @brief  Generate the HTML to display and change watchlist relations.
     *  @param  imgId   The unique DOM id for the image to be generated.
     *  @param  userInfo    The information about the user to present.
     *
     *  @return HTML
     */
    function userRelationHtml($imgId, $userInfo)
    {
        $funcId     = 'Tagging:userRelationHtml';
        $html       = '';
        $curUserId  = $this->authenticatedUserId();
        $dispUserId = $this->mTagger['userid'];
        $isSelf     = ($curUserId == $userInfo['userid']);

printf ("%s: curUserId[%u], userid[%u], dispUserid[%u]<br />\n",
        $funcId, $curUserId, $userInfo['userid'], $dispUerId);

        // Grab the relation with respect to the current user.
        $relation = $this->mTagDB->watchlistEntry($curUserId,
                                                  $userInfo['userid']);

        switch ($relation['relation'])
        {
        case 'watched':
            $imgOut    = $this->mBaseUrl . '/images/watched.gif';
            $imgTitle  = "Watching";
            $imgIn     = $this->mBaseUrl . '/images/removeRed.gif';
            $imgTitle .= ": Remove user {$userInfo['name']} from your watchlist";
            $imgAction = 'delete';
            break;

        case 'mutual':
            $imgTitle  = "Mutual";
            $imgOut    = $this->mBaseUrl . '/images/mutual.gif';
            $imgTitle .= ": Remove user {$userInfo['name']} from your watchlist";
            $imgIn     = $this->mBaseUrl . '/images/removeRed.gif';
            $imgAction = 'delete';
            break;

        case 'watcher':
            $imgOut    = $this->mBaseUrl . '/images/watcher.gif';
            $imgTitle  = "Watching You";
            $imgTitle .= ": Add user {$userInfo['name']} to your watchlist";
            $imgIn     = $this->mBaseUrl . '/images/addBlue.gif';
            $imgAction = 'add';
            break;

        case 'self':
            $imgIn     = $this->mBaseUrl . '/images/transparent.gif';
            $imgOut    = $this->mBaseUrl . '/images/transparent.gif';
            $imgTitle  = "";
            $imgAction = '';
            break;

        default:
            if ($isSelf)
            {
                $imgOut   = $this->mBaseUrl . '/images/transparent.gif';
                $imgTitle = '';
            }
            else
            {
                $imgIn     = $this->mBaseUrl . '/images/addBlue.gif';
                $imgOut    = $this->mBaseUrl . '/images/addGrey.gif';
                $imgTitle  = "Add user {$userInfo['name']} to your watchlist";
                $imgAction = 'add';
            }
            break;
        }

        $html .= "
     <div class='relation'><!-- { -->";

        if (! $isSelf)
        {
            $html .= "
      <a href='javascript:void(0);'
               title='{$imgTitle}'
                 alt='{$imgAction}'
             onclick='ChangeStatus(\"{$imgId}\", \"{$type}\", {$userInfo['userid']}, \"{$imgAction}\", {$dispUserId})'
         onmouseover='Status(\"{$imgId}\", \"{$imgIn}\")'
          onmouseout='Status(\"{$imgId}\", \"{$imgOut}\")'>";
        }

        $html .= "
       <img id='{$imgId}'
            src='{$imgOut}'
            title='{$imgTitle}'
            class='status' />";

        if (! $isSelf)
        {
            $html .= "
      </a>";
        }

        $html .= "
     </div><!-- relation } -->";

        return ($html);
    }

    /** @brief  Given a user, generate and return HTML to present information
     *          about that user.
     *  @param  user    Information about the current user.
     *  @param  props   Properties to include:
     *                      - 'icon'
     *                      - 'userName'
     *                      - 'fullName'
     *                      - 'contactInfo'
     *                      - 'editProfile'
     *                      - 'iconLink'
                            - 'watchlistLink'
     *                      - 'rating'          - watchlist rating stars
     *                      - 'relation'        - watchlist relation
     *
     *  @return The HTML
     */
    function userHtml($user,
                      $type     = 'user',
                      $props    = array('icon',
                                        'fullName',
                                        'contactInfo',
                                        'relation',
                                        'editProfile'))
    {
        $funcId = 'Tagging::userHtml';
        global  $gBaseUrl;

        $ownersArea = ($this->mCurUser['name'] == $this->mTagger['name']);
        $curUserId  = $this->authenticatedUserId();
        $isSelf     = ($curUserId == $userInfo['userid']);

        // Ensure that we fully normalized user information.
        if (empty($user['pictureUrl']))
            $this->mTagDB->userNormalize($user);

        $userDivId = "Disp.User.". $user['userid'];

        $html = "
   <div id='{$userDivId}' class='user'><!-- { -->
     <div class='userImg'><!-- { -->";

        if (@in_array('iconLink', $props))
            $html .= "<a href='{$gBaseUrl}/{$user['name']}'>";

        $html .= "
       <img src='{$user['pictureUrl']}'
            class='thumbnail'
            alt='{$user['fullName']}'
            title='{$user['fullName']}' />";

        if (@in_array('iconLink', $props))
            $html .= "</a>";

        if ($isSelf && @in_array('editProfile', $props))
        {
            $html .= "
           <a href='{$gBaseUrl}/settings/general'>edit profile</a>";
        }

        $html .= "
     </div><!-- userImg -->
     <div class='userInfo'><!-- { -->";

        if (@in_array('fullName', $props))
        {
            $html .= "
       <div class='fullName'>{$user['fullName']}</div>";
        }

        if (@in_array('userName', $props))
        {
            $html .= "
      <div class='userName'>{$user['name']}</div>";
        }

        if (@in_array('contactInfo', $props))
        {
            $html .= "
         <div class='contactInfo'>
           <a href='mailto:{$user['email']}'
              title='Send email to {$user['fullName']}'>{$user['email']}</a>
         </div>";
        }

        if (@in_array('watchlistLink', $props))
        {
            $html .= "
         <div class='watchlistLink'>
           (&nbsp;<a href='{$gBaseUrl}/{$user['name']}/watchlist'
                     title=\"{$user['fullName']}'s watchlist\">watchlist</a>&nbsp;)
         </div>";
        }

        if (@in_array('rating', $props) && isset($user['rating']))
        {
            $html .= "
         <div class='rating'>";
            $html .= $this->ratingHtml('Disp.User', $user['userid'],
                                       $user['rating'],
                                       0.0,             // avgRating
                                       true,            // immediate
                                       $ownersArea);    // isActive
            $html .= "
         </div>";
        }

        if (@in_array('relation', $props))
        {
            $imgId = $userDivId.".Status";

            if (false)
            {
            $html .= $this->userRelationHtml($imgId,
                                             $user);
            }
            else
            {
printf ("%s: curUserId[%u], userid[%u], dispUserid[%u]<br />\n",
        $funcId, $curUserId, $user['userid'], $dispUerId);
            // Grab the relation with respect to the current user.
            $relation = $this->mTagDB->watchlistEntry($curUserId,
                                                      $user['userid']);
    
            switch ($relation['relation'])
            {
            case 'watched':
                $imgOut    = $this->mBaseUrl . '/images/watched.gif';
                $imgTitle  = "Watching";
                $imgIn     = $this->mBaseUrl . '/images/removeRed.gif';
                $imgTitle .= ": Remove user {$user['name']} from your watchlist";
                $imgAction = 'delete';
                break;
    
            case 'mutual':
                $imgTitle  = "Mutual";
                $imgOut    = $this->mBaseUrl . '/images/mutual.gif';
                $imgTitle .= ": Remove user {$user['name']} from your watchlist";
                $imgIn     = $this->mBaseUrl . '/images/removeRed.gif';
                $imgAction = 'delete';
                break;
    
            case 'watcher':
                $imgOut    = $this->mBaseUrl . '/images/watcher.gif';
                $imgTitle  = "Watching You";
                $imgTitle .= ": Add user {$user['name']} to your watchlist";
                $imgIn     = $this->mBaseUrl . '/images/addBlue.gif';
                $imgAction = 'add';
                break;
    
            case 'self':
                $imgIn     = $this->mBaseUrl . '/images/transparent.gif';
                $imgOut    = $this->mBaseUrl . '/images/transparent.gif';
                $imgTitle  = "";
                $imgAction = '';
                break;
    
            default:
                if ($isSelf)
                {
                    $imgOut   = $this->mBaseUrl . '/images/transparent.gif';
                    $imgTitle = '';
                }
                else
                {
                    $imgIn     = $this->mBaseUrl . '/images/addBlue.gif';
                    $imgOut    = $this->mBaseUrl . '/images/addGrey.gif';
                    $imgTitle  = "Add user {$user['name']} to your watchlist";
                    $imgAction = 'add';
                }
                break;
            }
    
            $html .= "
         <div class='relation'><!-- { -->";
    
            if (! $isSelf)
            {
                $dispUserId = $this->mTagger['userid'];

                $html .= "
          <a href='javascript:void(0);'
                   title='{$imgTitle}'
                     alt='{$imgAction}'
                 onclick='ChangeStatus(\"{$imgId}\", \"{$type}\", {$user['userid']}, \"{$imgAction}\", {$dispUserId})'
             onmouseover='Status(\"{$imgId}\", \"{$imgIn}\")'
              onmouseout='Status(\"{$imgId}\", \"{$imgOut}\")'>";
            }
    
            $html .= "
           <img id='{$imgId}'
                src='{$imgOut}'
                title='{$imgTitle}'
                class='status' />";
    
            if (! $isSelf)
            {
                $html .= "
          </a>";
            }
    
            $html .= "
         </div><!-- relation } -->";
            }
        }

        $html .= "
     </div><!-- userInfo } -->
   </div><!-- user } -->
";

        return ($html);
    }

    /** @brief  Given a set of users, generate an return the HTML to
     *          present them in a condensed users area.
     *  @param  users   The list of users to render.
     *  @param  props   Properties to include:
     *                      - 'icon'
     *                      - 'userName'
     *                      - 'fullName'
     *                      - 'contactInfo'
     *                      - 'editProfile'
     *                      - 'iconLink'
     *                      - 'relation'
     *                      - 'watchlistLink'
     *
     *  @return The HTML.
     */
    function usersArea($users    = null,
                       $props    = array('icon',
                                         'fullName',
                                         'contactInfo',
                                         'relation',
                                         'editProfile',
                                         'watchlistLink'))
    {
        global  $_SESSION;
        $funcId = 'Tagging::usersArea';

        $curUser = $this->authenticatedUserId();

        // Manage session information
        if (($users === null) && (is_array($_SESSION[$this->mUsersId])))
            $users = $_SESSION[$this->mUsersId];
        else
            $_SESSION[$this->mUsersId] = $users;

        $html = '';
        if (is_array($users) && (count($users) > 0))
        {
            $html = "
   <div class='users'>";

            foreach ($users as $idex => $userId)
            {
                if (@in_array('rating',   $props) ||
                    @in_array('relation', $props))
                {
                    // Retrieve the watchlist entry for curUser->userId
                    $userInfo = $this->mTagDB->watchlistEntry($curUser,$userId);
                }
                else
                {
                    $userInfo = $this->mTagDB->user($userId);
                }

                $html .= "
         <div id='User{$userInfo['userid']}'>";
                $html    .= $this->userHtml($userInfo, 'user', $props);
                $html .= "
         </div>";
            }
            $html .= "
   </div>";
        }

        return ($html);
    }

    /** @brief  Given a set of users, generate and output the HTML to
     *          present them in a condensed users area.
     *  @param  users   The list of users to render.
     *                      - 'userid'
     *                      - 'fullName'
     *                      - 'email'
     *                      - 'pictureUrl'
     *                      - 'profile'
     *                      - 'lastVisit'
     *                      - 'totalTags'
     *                      - 'totalItems'
     *                      - 'watcher'=true    if a watcher
     *                      - 'watcherRating'   if a watcher
     *                      - 'watched'=true    if watched
     *                      - 'rating'          if watched
     *  @param  props   Properties to include:
     *                      - 'icon'
     *                      - 'userName'
     *                      - 'fullName'
     *                      - 'editProfile'
     *                      - 'iconLink'
     *                      - 'rating'
     *                      - 'relation'
     */
    function watchListArea($users   = null,
                           $props   = array('icon',
                                            'iconLink',
                                            'userName',
                                            'rating',
                                            'relation'))
    {
        $funcId  = 'Tagging::watchListArea';
        $curUser = $this->authenticatedUserId();

        if ($this->mCurUser['name'] != $this->mTagger['name'])
        {
            $possessive = $this->mTagger['name'] . "'s";
            $ownersArea = false;
        }
        else
        {
            $possessive = "your";
            $ownersArea = true;
        }

        $html = '';

        $inputId = 'WatchList.add';
        if ($ownersArea)
        {
            $html = "
        <form action='javascript:void(0);'>
         <table border='0'>
          <tr>
           <td align='left'>
            <h3>{$possessive} people</h3>
           </td>
           <td align='right'>
            <input id='{$inputId}'
                   class='smallInput' type='text' name='user' value='' />
           </td>
           <td align='left'>
            <input type='submit' name='submit' value='add'
                 onclick='WatchlistAdd(\"{$inputId}\")' />
           </td>
           <td id='{$inputId}.status' align='left'>&nbsp;
           </td>
          </tr>
         </table>
        </form>";
        }
        else
        {
            $html .= "
         <h3>{$possessive} people</h3>";
        }
        if (is_array($users) && (count($users) > 0))
        {
            $html .= "
         <div class='users'>";

            foreach ($users as $name => $userInfo)
            {
                if (($userInfo['relation'] != 'watched') &&
                    ($userInfo['relation'] != 'mutual'))
                    continue;

                $html .= "
         <div id='User{$userInfo['userid']}'>";
                $html .= $this->userHtml($userInfo, 'watchlist', $props);
                $html .= "
         </div>";
            }
            $html .= "
         </div>
         <div style='clear:left; margin-bottom:1ex;'>&nbsp;</div>
         <h3>{$possessive} watchers</h3>
         <div class='users'>";

            foreach ($users as $name => $userInfo)
            {
                if ($userInfo['relation'] != 'watcher')
                    continue;

                $html .= "
         <div id='User{$userInfo['userid']}'>";
                $html .= $this->userHtml($userInfo, 'watchlist', $props);
                $html .= "
         </div>";
            }

            $html .= "
         </div>";
        }

        return ($html);
    }

    /** @brief  Given a set of users, generate and return the HTML to
     *          present them.
     *  @param  pager   The pager controlling this list of users.
     *  @param  users   The list of users to render.
     *
     *  @return The HTML
     */
    function peopleHtml(&$pager, &$users)
    {
        global  $_SESSION;

        $funcId = 'Tagging::peopleHtml';

        // Manage session information
        $order = $_SESSION[$this->mUsersOrderId];

        $html = ''; //$funcId . ": order[$order]<br />";



        // Inside of <tr id='Users'> from peopleArea
        $html .= "
       <table class='people sortable'>
        <thead>
         <tr>
          <th class='nosort'>&nbsp;</th>";

        // Ordering       id                 sqlField       order   reverse
        $options  = array('id'      => array('name',        'ASC',  'DESC'),
                          'Name'    => array('fullName',    'ASC',  'DESC'),
                          'email'   => array('email',       'ASC',  'DESC'),
                          'Tags'    => array('totalTags',   'DESC', 'ASC'),
                          'Items'   => array('totalItems',  'DESC', 'ASC'));
        foreach ($options as $value => $sqlSort)
        {
            $html .= "
          <th class='sortcol";

            if ( ($order == $value) || ($order == strtolower($value)) )
            {
                // Normal order
                $orderBy = $sqlSort[0] . ' ' . $sqlSort[1];
                $change  = sprintf('ChangeDisplay("Users", "r%s")', $value);
                $html   .= " sortasc";
            }
            else if ( ($order == "r".$value) ||
                      ($order == "r".strtolower($value)) )
            {
                // Reverse order
                $orderBy = $sqlSort[0] . ' ' . $sqlSort[2];
                $change  = sprintf('ChangeDisplay("Users", "%s")', $value);
                $html   .= " sortdesc";
            }
            else
            {
                // NOT sorted by this field
                $change = sprintf('ChangeDisplay("Users", "%s")', $value);
            }

            $html   .= "' title='Sort' onclick='{$change}'>
           {$value}
          </th>";
        }

        $html .= "
          <th class='nosort'>Relation</th>
         </tr>
        </thead>
        <tbody>";

        $count = 0;
        foreach ($users as $idex => $user)
        {
            // Make sure we have fully normalized information.
            if (empty($user['pictureUrl']))
                $user = $this->mTagDB->userNormalize($user);
            if (! isset($user['rating']))
            {
                $curUser = $this->authenticatedUserId();
                $user = array_merge($user,
                                    $this->mTagDB->watchlistRelation(
                                        $curUser, $user['userid']));
            }

            if (($count % 2) == 0)
                $class = 'rowodd';
            else
                $class = 'roweven';

            $totalTagsStr  = number_format($user['totalTags']);
            $totalItemsStr = number_format($user['totalItems']);

            $html .= "
         <tr class='{$class}'>
          <td><img class='person' src='{$user['pictureUrl']}'
                    alt='{$user['fullName']}'
                    title='{$user['fullName']}' /></td>
          <td><a href='{$this->mBaseUrl}/{$user['name']}'>
                    {$user['name']}</a></td>
          <td>{$user['fullName']}</td>
          <td><a href='mailto:{$user['email']}'>
                    {$user['email']}</a></td>
          <td>{$totalTagsStr}</td>
          <td>{$totalItemsStr}</td>
          <td>{$user['relation']}</td>
         </tr>";

            $count++;
        }

        $html .= "
        </tbody>
       </table>";

        return ($html);
    }

    /***********************************************************************
     * Display routines used by action.php
     *
     */

    /** @brief  Generate and return the HTML to present a list of items.
     *  @param  users       If provided, the set of user(s) whose items we are
     *                      displaying.
     *  @param  tags        If provided, the set of tag(s) that all items MUST
     *                      have.
     *  @param  order       The order of items (Recent, Oldest, Votes, Ratings)
     *
     *  @return The HTML
     */
    function itemsArea($users,
                       $tags,
                       $order   = null)
    {
        global  $_SESSION;

        $funcId  = 'Tagging::itemsArea';

        $curUser = $this->authenticatedUserId();

        // Manage session information
        $vars = array('users'       => array($this->mUsersId,       null),
                      'tags'        => array($this->mTagsId,        null),
                      'order'       => array($this->mItemsOrderId,  'Recent'));
        foreach ($vars as $varName => $varInfo)
        {
            $sessName = $varInfo[0];
            $default  = $varInfo[1];
            if ($$varName === null)
            {
                // Recover the value from our session if possible.
                if (isset($_SESSION[$sessName]))
                    $$varName = $_SESSION[$sessName];
                else
                    // No value in the session - use the default
                    $$varName = $default;
            }

            // Set the session value
            $_SESSION[$sessName] = $$varName;
        }

        /*
        echo "<pre>$funcId: users:";
        print_r($users);
        echo "tags:";
        print_r($tags);
        echo "order:";
        print_r($order);
        echo "</pre>\n";
        //  */

        /*
         * Generate the HTML for the selector that permits the user to
         * select the order of display:
         *  - Recent        by post date (newest first)
         *  - Oldest        by post date (oldest first)
         *  - Taggers       by number of taggers
         *  - Votes         by number of votes (i.e. non-zero ratings)
         *  - Ratings       by average rating
         */
        $html = "
 <div class='title'><!-- { -->
  <div class='Selector'><!-- { -->";

        // Ordering - Recent | Oldest | Taggers | Votes | Ratings
        $options  = array('Recent'  => 'tagged_on DESC',
                          'Oldest'  => 'tagged_on ASC',
                          'Taggers' => 'userCount DESC',
                          'Votes'   => 'ratingCount DESC',
                          'Ratings' => 'ratingSum DESC');
        $oCount = 0;
        foreach ($options as $value => $sqlSort)
        {
            if ($oCount++ > 0)
                $html .= ", ";

            if ( ($order == $value) || ($order == strtolower($value)) )
            {
                $orderBy = $sqlSort;
                $html   .= "
      <b>{$value}</b>";
            }
            else
            {
                $change = sprintf('ChangeDisplay("Items", "%s")', $value);
                $html .= "
     <a href='javascript:{$change}'>{$value}</a>";
            }
        }

        $pager = $this->mTagDB->userItems($users, $tags, $curUser,
                                          $orderBy,
                                          20,           // PerPage
                                          'itemsHtml');
        $maxPage      = $pager->PageCount();
        $linkCount    = $pager->RecordCount();
        $linkCountStr = number_format($linkCount);

        $html .= "
  </div><!-- } -->
  <h3>Tagged Items <sub style='font-weight:normal;'>{$linkCountStr}</sub></h3>
 </div><!-- title } -->
 <div id='PagerTop' class='Pager' style='clear:both;'><!-- { -->";

        /*
         * If there is more than 1 page of items, include paging controls
         * with a unique DOM id of 'ItemsTop'
         */
        if ($maxPage > 1)
        {
            $html .= $pager->controlHtml('Top', 'Items');
        }

        $html .= "
 </div><!-- } -->
 <div id='Items'><!-- { -->";

        $html .= $pager->pageHtml();    // itemsHtml

        $html .= "
 </div><!-- Items } -->
 <div id='PagerBottom' class='Pager'><!-- { -->";

        /*
         * If there is more than 1 page of items, include paging controls
         * with a unique DOM id of 'ItemsBottom'
         */
        if ($maxPage > 1)
        {
            $html .= $pager->controlHtml('Bottom', 'Items');
        }
        $pager->Close();

        $html .= "
 </div><!-- } -->";

        return ($html);
    }

    /** @brief  Given a set of tags, generate the HTML for a tag cloud.
     *  @param  tags        The set of tags.
     *  @param  tagUrl      The basic url to use for the tag anchors.
     *                      Any occurrance of '%tag%' in this string will
     *                      be replaced with the actual tag being displayed.
     *  @param  order       The final display order of the cloud
     *                      ('Alpha' or 'Count')  [DEFAULT: 'Alpha'].
     *  @param  selected    If provided, this array of tags will be used
     *                      to decide whether the 'selected' CSS class should
     *                      be included on the tag anchor.
     *  @param  selUrl      The basic url to use for the tag anchors that
     *                      are marked 'selected' (defaults to 'tagUrl').
     *                      Any occurrance of '%tagRest%' in this string will
     *                      be replaced with the ',' delimited list of all
     *                      selected tags exluding the one that is being
     *                      formatted ('%tag%' will also be replaced as in
     *                      'tagUrl').
     *  @param  tagId       The base of the unique identifier for the tag
     *                      anochors (the tag number plus any provided
     *                                tagOffset are appended to this base).
     *  @param  tagOffset   The offset to add to the tag number in the
     *                      generation of the unique identifier.
     *  @param  color_base  A three element array of decimal red,green,blue
     *                      values used as the base color when coloring tags
     *                      [DEFAULT: 35,89,140]
     *  @param  color_mod   Which color from 'base_color' to adjust
     *                      [DEFAULT: 2 == blue]
     *  @param  min_font    The minimum font size [DEFAULT: 10].
     *  @param  max_font    The maximum font size [DEFAULT: 20].
     *  @param  font_units  The font size units   [DEFAULT: 'px'].
     */
    function cloudHtml  ($tags,
                         $tagUrl,
                         $order         = 'Alpha',
                         $selected      = null,
                         $selUrl        = null,
                         $tagId         = 'cloud.tag.',
                         $tagOffset     = 0,
                         $color_base    = null,
                         $color_mod     = 2,
                         $min_font      = 10,
                         $max_font      = 24,
                         $font_unit     = 'px')
    {
        $funcId = 'Tagging::cloudHtml';

        // Instantiate a cloud generator
        $gen = new cloud_tag();

        $gen->set_label('cloud');   // css class will be cloud0, cloud1, ...
        $gen->set_tagsizes(7);      // Use 7 font sizes

        /*echo "<pre>cloudHtml: tags\n";
        print_r($tags);
        print_r($selected);
        echo "</pre>";*/
        
        if (is_array($tags[0]))
        {
            /*
             * This should be an array of associative arrays each containing
             * information about a specific tag.
             *
             * Generate a simple associative array of 'tag' => 'count'
             * and a second associative array of 'tag' => isSelected?
             */
            $aTags = array();
            foreach ($tags as $idex => $tagInfo)
            {
                $aTags[$tagInfo['tag']] = $tagInfo['itemCount'];
                if ($tagInfo['selected'])
                    $selected[] = $tagInfo['tag'];
            }
        }
        else
        {
            // This is simply an associative array of 'tag' => 'count'
            $aTags = $tags;
        }

        /*
        printf ("<b>%s</b>: aTags{%s}<br />\n",
                $funcId, var_export($aTags, true));
        printf ("<b>%s</b>: selected{%s}<br />\n",
                $funcId, var_export($selected, true));
        */

        $tagCloud = $gen->make_cloud($aTags, $order);

        $html = "
<ul class='bundle'>
 <li class='cloud'>
  <ul>";
        $idex = 0;
        foreach ($tagCloud as $tag => $item)
        {
            // tag -> {count, bucket, class}
            $isSelected = @in_array($tag, $selected);

            if ($isSelected && (! empty($selUrl)) )
            {
                /*
                 * This tag is currently selected.  Generate a URL that
                 * will exclude this tag while keeping all other currently
                 * selected tags.
                 *
                 * If there is only one tag selected, it MUST be this one
                 * (easy), otherwise, figure out all the OTHER selected tags.
                 *
                 * See if there is anything currently selected.  If so, we
                 * need 'tagRest' to include all tags EXCEPT 'tag'
                 */
                $tagRest = '';
                if (count($selected) > 1)
                {
                    /*
                     * Generate tagRest as a ',' delimited string containing
                     * all tags EXCEPT the current one.
                     */
                    for ($idex = 0; $idex < count($selected); $idex++)
                    {
                        $selTag = $selected[$idex];

                        if ($selTag != $tag)
                        {
                            if (! empty($tagRest))
                                $tagRest .= ',';

                            //x$tagRest .= preg_replace('/\s+/', '_', $selTag);
                            $tagRest .= $selTag;
                        }
                    }
                }

                $href = preg_replace('/%tagRest%/i', $tagRest, $selUrl);
            }
            else
            {
                /*
                 * This tag is NOT currently selected.  Generate a URL that
                 * will include this tag with all those currently selected.
                 */
                //x$tagRest = preg_replace('/\s+/', '_', $tag);
                $tagRest = $tag;
                if (count($selected) > 0)
                {
                    //x$tagSel   = preg_replace('/\s+/', '_', implode(',', $selected));
                    $tagSel   = implode(',', $selected);
                    if (! empty($tagSel))
                        $tagRest .= ',' . $tagSel;
                }

                $href = preg_replace('/%tag%/i', $tagRest, $tagUrl);
            }

            $html .= sprintf("
   <li><a id='%s%u' class='tag%s %s' title='%s post%s' href='%s'>%s</a></li>",
                              $tagId, $idex + $tagOffset,
                              ($isSelected ? ' selected':''),
                              $item['class'],
                              number_format($item['count']),
                                    ($item['count'] == 1 ? "" : "s"),
                              $href,
                              htmlspecialchars(stripslashes($tag)) );
            $idex++;
        }
        $html .= "
  </ul>
 </li>
</ul>";

        return ($html);

        /*******************************************************************/
        if (! is_array($color_base))
            $color_base = array(35, 89, 140);  // Starting color: decimal RGB
        if ($color_mod > 2)
            $color_mod = 2;

        // Get the min and max qty of tagged items in the set
        if ( (! is_array($aTags)) || (count($aTags) < 1))
        {
            $aTags   = array();
            $min_qty = 0;
            $max_qty = 0;
        }
        else
        {
            $min_qty = min(array_values($aTags));
            $max_qty = max(array_values($aTags));
        }

        /*
         * For every additional tagged item from min to max, we add $step to
         * the font size.
         */
        $spread = $max_qty - $min_qty;
        if (0 == $spread)
        {   // Divide by zero
            $spread = 1;
        }
        $step    = ($max_font - $min_font)/($spread);
        $clrStep = (255 - $color_base[$color_mod])/($spread);

        // Apply the ordering...
        if ($order == 'Alpha')
        {
            // Sort by key/tag name.
            ksort($aTags);
        }
        else
        {
            // Sort by tag count.
            asort($aTags, SORT_NUMERIC);
        }

        /*
         * Since the original tags is alphabetically ordered, we can now create
         * the tag cloud by just putting a span on each element, multiplying
         * the diff between min and qty by $step.
         */
        $html = "
<ul class='bundle'>
 <li class='cloud'>
  <ul>";
        $idex = 0;
        foreach ($aTags as $tag => $qty)
        {
            $size     = $min_font + ($qty - $min_qty) * $step;
            $colorAdj = ($qty - $min_qty) * $clrStep;
            $color    = sprintf ("#%02x%02x%02x",
                                 $color_base[0] + ($color_mod==0 ?$colorAdj:0),
                                 $color_base[1] + ($color_mod==1 ?$colorAdj:0),
                                 $color_base[2] + ($color_mod==2 ?$colorAdj:0));

            $isSelected = @in_array($tag, $selected);
            if ($isSelected && (! empty($selUrl)) )
            {
                /*
                 * This tag is currently selected.  Generate a URL that
                 * will exclude this tag while keeping all other currently
                 * selected tags.
                 *
                 * If there is only one tag selected, it MUST be this one
                 * (easy), otherwise, figure out all the OTHER selected tags.
                 *
                 * See if there is anything currently selected.  If so, we
                 * need 'tagRest' to include all tags EXCEPT 'tag'
                 */
                $tagRest = '';
                if (count($selected) > 1)
                {
                    /*
                     * Generate tagRest as a ',' delimited string containing
                     * all tags EXCEPT the current one.
                     */
                    for ($idex = 0; $idex < count($selected); $idex++)
                    {
                        $selTag = $selected[$idex];

                        if ($selTag != $tag)
                        {
                            if (! empty($tagRest))
                                $tagRest .= ',';

                            //x$tagRest .= preg_replace('/\s+/', '_', $selTag);
                            $tagRest .= $selTag;
                        }
                    }
                }

                $href = preg_replace('/%tagRest%/i', $tagRest, $selUrl);
            }
            else
            {
                /*
                 * This tag is NOT currently selected.  Generate a URL that
                 * will include this tag with all those currently selected.
                 */
                //x$tagRest = preg_replace('/\s+/', '_', $tag);
                $tagRest = $tag;
                if (count($selected) > 0)
                {
                    //x$tagSel   = preg_replace('/\s+/', '_', implode(',', $selected));
                    $tagSel   = implode(',', $selected);
                    if (! empty($tagSel))
                        $tagRest .= ',' . $tagSel;
                }

                $href = preg_replace('/%tag%/i', $tagRest, $tagUrl);
            }

            $html .= sprintf("
   <li><a id='%s%u' class='tag%s' title='%u post%s' style='font-size:%u%s;color:%s;' href='%s'>%s</a></li>",
                              $tagId, $idex + $tagOffset,
                              ($isSelected ? ' selected':''),
                              $qty, ($qty == 1 ? "" : "s"),
                              $size, $font_units,
                              $color,
                              $href,
                              htmlspecialchars(stripslashes($tag)) );
            $idex++;
        }
        $html .= "
  </ul>
 </li>
</ul>";

        return $html;
    }

    /** @brief  Display tags.
     *  @param  users       If provided, the set of user(s) whose items we are
     *                      displaying (which should in-turn limit the tags).
     *  @param  tagLimits   If provided, the set of tag(s) that all items MUST
     *                      have (which should in-turn limit the items).
     *  @param  dispType    The type of tag display (List, [Cloud])
     *  @param  order       The order of tags ([Alpha], Count)
     *  @param  limit       The number of tags to display (0 == all)
     *
     *  @return The HTML representing the tags area.
     */
    function tagsArea($users,
                      $tagLimits,
                      $dispType     = null,
                      $order        = null,
                      $limit        = null)
    {
        $funcId = 'Tagging::tagsArea';
        //$this->profile_start($funcId);

        // Manage session information
        $vars = array('users'       => array($this->mUsersId,       null),
                      'tagLimits'   => array($this->mTagsId,        null),
                      'dispType'    => array($this->mTagsTypeId,    'Cloud'),
                      'order'       => array($this->mTagsOrderId,   'Alpha'),
                      'limit'       => array($this->mTagsLimitId,   100));
        foreach ($vars as $varName => $varInfo)
        {
            $sessName = $varInfo[0];
            $default  = $varInfo[1];
            if ($$varName === null)
            {
                // Recover the value from our session if possible.
                if (isset($_SESSION[$sessName]))
                    $$varName = $_SESSION[$sessName];
                else
                    // No value in the session - use the default
                    $$varName = $default;
            }

            // Set the session value
            $_SESSION[$sessName] = $$varName;
        }

        $html   = '';
        $items  = null;
        $nItems = 0;
        if (is_array($tagLimits) && (count($tagLimits) > 0))
        {
            // Retrieve the set of items containing the given tags.
            $items  = $this->mTagDB->items($users, $tagLimits);
            $nItems = count($items);
        }

        /*
        echo "<pre>$funcId: users:";
        print_r($users);
        echo "tagLimits:\n";
        print_r($tagLimits);
        echo "dispType: $dispType\n";
        echo "order: $order\n";
        echo "limit: $limit\n";
        echo "items:\n";
        print_r($items);
        echo "</pre>\n";
        //  */


        /*
         * Generate the HTML for the selector that permits the user to
         * select how to display the tags:
         *  - Alpha | Count     order by tag name or tag count;
         *  - List  | Cloud     display as a list of cloud;
         *  - 25 ... all        how many tags to display
         */
        $html .= "
 <div class='title'><!-- { -->
  <div class='Selector'><!-- { -->";

        // Ordering - Alpha | Count
        $options  = array('Alpha'   => 'userCount DESC,tag ASC',
                          'Count'   => 'userCount DESC,tag ASC');
        $oCount = 0;
        foreach ($options as $value => $sqlSort)
        {
            if ($oCount++ > 0)
                $html .= ", ";

            if ( ($order == $value) || ($order == strtolower($value)) )
            {
                $orderBy = $sqlSort;
                $html   .= "
      <b>{$value}</b>";
            }
            else
            {
                $change = sprintf('ChangeDisplay("Tags", "%s", %u, "%s")',
                                  $value, $limit, $dispType);
                $html .= "
     <a href='javascript:{$change}'>{$value}</a>";
            }
        }

        // Display type - List | Cloud
        $html .= "<br />\n";

        $options  = array('List', 'Cloud');
        $oCount = 0;
        foreach ($options as $idex => $value)
        {
            if ($oCount++ > 0)
                $html .= ", ";

            if ( ($dispType == $value) || ($dispType == strtolower($value)) )
            {
                $html .= "
      <b>{$value}</b>";
            }
            else
            {
                $change = sprintf('ChangeDisplay("Tags", "%s", %u, "%s")',
                                  $order, $limit, $value);
                $html .= "
     <a href='javascript:{$change}'>{$value}</a>";
            }
        }

        // Limits - 25, 50, 100, 250, 500, all
        $html .= "<br />\n";

        $options  = array(25  => '25',   50 => '50',
                          100 => '100', 250 => '250',
                          500 => '500',   0 => 'all');
        $oCount = 0;
        foreach ($options as $value => $dispValue)
        {
            if ($oCount++ > 0)
                $html .= ", ";

            if ($limit == $value)
            {
                $html .= "
      <b>{$dispValue}</b>";
            }
            else
            {
                $change = sprintf('ChangeDisplay("Tags", "%s", %u, "%s")',
                                  $order, $value, $dispType);
                $html .= "
     <a href='javascript:{$change}'>{$dispValue}</a>";
            }
        }

        // Retrieve the tags
        $tags         = $this->mTagDB->itemTags($users, $items, $orderBy);
        $totalTags    = count($tags);
        $totalTagsStr = number_format($totalTags);

        $html .= "
  </div><!-- } -->
  <h3>Tags <sub style='font-weight:normal;'>{$totalTagsStr}</sub></h3>
 </div><!-- title } -->
 <div style='margin:0 0.25em 0 0.5em;padding:0;'>";

        if ($totalTags < 1)
        {
            // No tags to display -- we're done.
            $html .= "
 </div>";
            return ($html);
        }

        // Generate the base url use for all tags.  This is comprised
        // of the global base url + the name of the user (if any).
        /*$tagUrl = $this->mBaseUrl;
        if (is_array($users) && (count($users) == 1))
        {
            $userInfo = $this->mTagDB->user($users[0]);
            $tagUrl .= '/' . $userInfo['name'];
        }
        else
        {
            $tagUrl .= '/tag';
        }
        */
        $tagUrl = $this->mAreaUrl;
        if ($tagUrl == $this->mBaseUrl)
            $tagUrl .= '/tag';

        if ($limit > 0)
            // Limit the presented tags.
            $tags = array_slice($tags, 0, $limit);

        if (is_array($tagLimits) && is_array($tags))
        {
            // Mark which tags are currently selected.
            foreach ($tags as $idex => $tagInfo)
            {
                $tags[$idex]['selected'] = @in_array($tagInfo['tagid'],
                                                     $tagLimits);
            }
        }

        /*
         * Display the tags according to the selected 'dispType' - List | Cloud
         */
        switch($dispType)
        {
        case 'List':
            $html .= "
<ul class='bundle'>
 <li class='list'>
  <ul>";
            $tagCnt = 0;
            foreach ($tags as $idex => $tagInfo)
            {
                $tag   = $tagInfo['tag'];
                $qty   = $tagInfo['userCount'];

                $html .= sprintf("
   <li><span class='count'>%s</span> <a id='list.tag.%u' class='tag%s' title='%s post%s' href='%s'>%s</a></li>",
                                 number_format($qty),
                                 $tagCnt++,
                                 ($tagInfo['selected'] ? ' selected':''),
                                 number_format($qty), ($qty == 1 ? "" : "s"),
                                 $tagUrl . '/' . $tag,
                                 htmlspecialchars(stripslashes($tag)) );
            }

            $html .= "
  </ul>
 </li>
</ul>";
            break;

        case 'Cloud':
        default:
            $html .= $this->cloudHtml($tags,
                                      $tagUrl . '/%tag%',
                                      $order,
                                      null,
                                      $tagUrl . '/%tagRest%');
            break;
        }

        $html .= "
 </div>";

        //$this->profile_stop($funcId);
        return ($html);
    }

    /** @brief  Display user(s).
     *  @param  order       The order of users (id,    rId,
     *                                          Name,  rName,
     *                                          email, rEmail,
     *                                          Tags,  rTags,
     *                                          Items, rItems).
     *
     *  @return The HTML representing the users area.
     */
    function peopleArea($order   = 'Items')
    {
        global  $_SESSION;

        $funcId = 'Tagging::peopleArea';

        // Manage session information
        if (($order === null) && (is_array($_SESSION[$this->mUsersOrderId])))
            $order = $_SESSION[$this->mUsersOrderId];

        // Setup to use the appropriate database query sort
        switch (strtolower($order))
        {
        case 'tags':      $order   = 'tags';
                          $orderBy = 'totalTags ASC';
                          break;
        case 'rtags':     $order   = 'rtags';
                          $orderBy = 'totalTags DESC';
                          break;
        case 'id':        $order   = 'id';
                          $orderBy = 'name     ASC';
                          break;
        case 'rid':       $order   = 'rid';
                          $orderBy = 'name     DESC';
                          break;
        case 'name':      $order   = 'name';
                          $orderBy = 'fullname ASC';
                          break;
        case 'rname':     $order   = 'rname';
                          $orderBy = 'fullname DESC';
                          break;
        case 'email':     $order   = 'email';
                          $orderBy = 'email    ASC';
                          break;
        case 'remail':    $order   = 'remail';
                          $orderBy = 'email    DESC';
                          break;
        case 'items':     $order   = 'items';
                          $orderBy = 'totalItems ASC';
                          break;
        case 'ritems':
        default:          $order   = 'ritems';
                          $orderBy = 'totalItems DESC';
                          break;
        }

        $_SESSION[$this->mUsersOrderId]  = $order;

        $pager     = $this->mTagDB->users(null,         // All users
                                          $orderBy,
                                          'peopleHtml'); // renderer
        $maxPage   = $pager->PageCount();

        $stats['users']        = number_format($pager->RecordCount());
                                 // $this->mTagDB->userCount()
        $stats['contributors'] = number_format(
                                        $this->mTagDB->userCountContributors());
        $stats['items']        = number_format($this->mTagDB->userItemsCount(
                                            null,   // All users
                                            null,   // No tag limits
                                            $this->getCurUserId()));
        $stats['tags']         = number_format($this->mTagDB->tagsCount());

        /*
         * Generate the HTML for the row header/selector that permits the user
         * to select the order of display:
         *  - id    / rid       user id:name    (or reverse)
         *  - Name  / rName     user fullName   (or reverse)
         *  - email / remail    user email      (or reverse)
         *  - Tags  / rTags     user tag count  (or reverse)
         *  - Items / rItems    user item count (or reverse)
         */
        $html .= "
    <table class='peopleContainer'>
     <tr>
      <td align='right' valign='top'>
       <div id='PagerTop' class='Pager' style='clear:both;'><!-- { -->";

        /*
         * If there is more than 1 page of items, include paging controls
         * with a unique DOM id of 'UsersTop'
         */
        if ($maxPage > 1)
        {
            $html .= $pager->controlHtml('Top', 'Users');
        }

        $html .= "
        </div><!-- } -->
      </td>
      <td>&nbsp;</td>
     </tr>
     <tr>
      <td id='Users' valign='top'>";

        $html .= $pager->pageHtml();    // peopleHtml

        $html .= "
      </td>
      <td valign='top'>
       <table class='stats'>
        <tr><th>Total users </th><td>{$stats['users']}</td></tr>
        <tr><th>Contributors</th><td>{$stats['contributors']}</td></tr>
        <tr><th>Unique items</th><td>{$stats['items']}</td></tr>
        <tr><th>Unique tags </th><td>{$stats['tags']}</td></tr>
       </table>
      </td>
     </tr>
     <tr>
      <td align='right' valign='top'>
        <div id='PagerTop' class='Pager' style='clear:both;'><!-- { -->";
    
        /*
         * If there is more than 1 page of items, include paging controls
         * with a unique DOM id of 'ItemsTop'
         */
        if ($maxPage > 1)
        {
            $html .= $pager->controlHtml('Bottom', 'Users');
        }
    
        $html .= "
        </div><!-- } -->
      </td>
      <td>&nbsp;</td>
     </tr>
    </table>";

        return ($html);
    }

    /***********************************************************************
     * Item and Tag management
     *
     */

    /** @brief  Update item & tag count statistics for the given user.
     *  @param  userid      The user to update.
     */
    function userStatsUpdate($userid)
    {
        $this->mTagDB->userStatsUpdate($userid,
                                       -1,      // do NOT change lastVisit
                                       null,    // Recompute totalTags
                                       null);   // Recompute totalItems
    }

    /** @brief  Create a new tagged item for the current user.
     *  @param  url         The URL for this new item.
     *  @param  name        The name for this new item.
     *  @param  description The description for this new item.
     *  @param  tags        A comma separated list of tags to apply to this
     *                      item.
     *  @param  rating      The user rating of this item (0-5).
     *  @param  is_favorite Is this item a user favorite?
     *  @param  is_private  Is this item private (or public)?
     *
     *  @return true (success) or false (failure)
     */
    function itemCreate($url,
                        $name,
                        $description,
                        $tags,
                        $rating,
                        $is_favorite,
                        $is_private)
    {
        return $this->itemModify(0, $url, $name, $description,
                                 $tags, $rating, $is_favorite, $is_private);
    }

    /** @brief  Modify an existing tagged item for the current user.
     *  @param  itemid      The id of the item to modify.
     *  @param  url         The URL for this new item.
     *  @param  name        The name for this new item.
     *  @param  description The description for this new item.
     *  @param  tags        A comma separated list of tags to apply to this
     *                      item.
     *  @param  rating      The user rating of this item (0-5).
     *  @param  is_favorite Is this item a user favorite?
     *  @param  is_private  Is this item private (or public)?
     *
     *  @return true (success) or false (failure)
     */
    function itemModify($itemid,
                        $url,
                        $name,
                        $description,
                        $tags,
                        $rating,
                        $is_favorite,
                        $is_private)
    {
        $funcId = 'Tagging::itemModify';
        $this->profile_start($funcId,
                             "itemid[%s], url[%s], name[%s], description[%s], ".
                             "tags[%s], rating[%u], is_favorite[%u], ".
                             "is_private[%u]",
                             $itemid, $url, $name, $description,
                             $tags, $rating, $is_favorite,
                             $is_private);

        $curUserId = $this->authenticatedUserId();
        if ($curUserId === false)
        {
            // NOT permitted!
            $this->profile_stop($funcId, "Unauthenticated user");
            return false;
        }

        if ($itemid < 1)
        {
            // Lookup by url
            $itemid = $this->mTagDB->itemId($url);
            if ($itemid < 1)
            {
                // NO - add a new top-level item
                $itemid = $this->mTagDB->itemAdd($url);
                if ($itemid < 1)
                {
                    $this->profile_stop($funcId, "FAILED to create new item");
                    return false;
                }
            }
        }

        // Add/Update item details
        $res = $this->mTagDB->userItemModify($curUserId, $itemid,
                                             $name,
                                             array('description'=>$description,
                                                   'rating'     =>$rating,
                                                   'is_favorite'=>$is_favorite,
                                                   'is_private' =>$is_private));
        if ($res === false)
            $this->profile_checkpoint($funcId, "FAILED TagDB::userItemModify");

        // Update the tags associated with this item.
        $res = $this->mTagDB->tagsChange($curUserId, $itemid,
                                         $tags);
        if ($res === false)
            $this->profile_checkpoint($funcId, "FAILED TagDB::tagsChange");

        // Update tag and item counts for this user.
        $this->userStatsUpdate($curUserId);

        $this->profile_stop($funcId);
        return ($res);
    }

    /** @brief  Change one or more of the indicators (favorite, private,
     *          rating) for an existing item.
     *  @param  itemid      The unique identifier of the item.
     *  @param  rating      Rating             [null == do not change]
     *  @param  is_favorite Is this a favorite [null == do not change]
     *  @param  is_private  Is this private    [null == do not change]
     */ 
    function itemChangeIndicators($itemid,
                                  $rating        = null,
                                  $is_favorite   = null,
                                  $is_private    = null)
    {
        $curUserId = $this->authenticatedUserId();

        if ($curUserId === false)
            // NOT permitted!
            return false;

        $details = array();
        if ($rating !== null)
            $details['rating'] = $rating;
        if ($is_favorite !== null)
            $details['is_favorite'] = $is_favorite;
        if ($is_private !== null)
            $details['is_private'] = $is_private;

        return ($this->mTagDB->userItemModify($curUserId, $itemid,
                                              null, $details));
    }

    /** @brief  Delete the identified item for the current user.
     *  @param  itemid  The unique identifier of the item to delete.
     *
     *  @return true (success) or false (failure)
     */
    function itemDelete($itemid)
    {
        $funcId    = 'Tagging::itemDelete';
        $curUserId = $this->authenticatedUserId();

        $this->profile_start($funcId, "itemid[%u], curUserId[%u]",
                             $itemid, $curUserId);

        if ($curUserId === false)
        {
            // Unauthenticated/unknown: NOT permitted!
            $this->profile_stop($funcId, "Unauthenticated user!");
            return false;
        }

        // Delete all tags for this item.
        $this->profile_checkpoint($funcId, "Delete item tags...");
        if (! $this->mTagDB->tagsDelete($curUserId,
                                        $itemid, null))
        {
            $this->profile_stop($funcId, "Error in tagsDelete");
            return false;
        }

        $this->profile_checkpoint($funcId, "Delete userItem...");
        if (! $this->mTagDB->userItemDelete($curUserId, $itemid))
        {
            $this->profile_stop($funcId, "Error in userItemDelete");
            return false;
        }

        // Update tag and item counts for this user.
        $this->profile_checkpoint($funcId, "Update user stats...");
        $this->userStatsUpdate($curUserId);

        $this->profile_stop($funcId, "DONE");
        return true;
    }

    /***********************************************************************
     * Profiling
     *
     */

    /** @brief  Start profiling */
    function profile_start()
    {
        return ($this->profile
                    ? $this->profile->vstart(func_get_args()) : 0);
    }

    /** @brief  Checkpoint profiling */
    function profile_checkpoint()
    {
        return ($this->profile
                    ? $this->profile->vcheckpoint(func_get_args()) : 0);
    }

    /** @brief  Stop profiling */
    function profile_stop()
    {
        return ($this->profile
                    ? $this->profile->vstop(func_get_args()) : 0);
    }
}

?>
