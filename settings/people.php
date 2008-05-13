<?php
/** @file
 *
 *  This is a connexions plug-in that implements the settings/people
 *  namespace.
 *      people_main         - present the top-level people options.
 *      people_privacy      - if $_SERVER['REQUEST_METHOD'] is POST, invoke
 *                            _people_privacyPost() otherwise, present a form
 *                            that describes and enables modification of
 *                            privacy settings.
 *      people_subscriptions- if $_SERVER['REQUEST_METHOD'] is POST, invoke
 *                            _people_subscriptionsPost() otherwise, present a
 *                            form that describes and enables modification of
 *                            subscriptions.
 *
 *      _people_privacyPost - a private routine that validates inputs and
 *                            performs the modification of privacy settings.
 *      _people_subscriptionsPost
 *                          - a private routine that validates inputs and
 *                            performs the modification of subscriptions.
 */
require_once('settings/index.php');

/** @brief  Present the top-level people settings.
 *  @param  params  An array of incoming parameters.
 */
function people_main($params)
{
    global  $gTagging;

    settings_nav($params);

    ?>
<div class='helpQuestion'>People: management</div>
<div class='helpAnswer'>
 This section allows you to mange your <a href='people/privacy'>privacy</a> and
 <a href='people/subscriptions'>subscriptions</a> to the posts of others.  For
 more information about people, please take a look at the <a href='<?=
 $gTagging->mBaseUrl?>/help/sharing'> help section on sharing</a>.
</div>
<?php
}

/** @brief  Present a people privacy form.
 *  @param  params  An array of incoming parameters.
 */
function people_privacy($params)
{
    global  $gTagging;
    global  $_SERVER;

    settings_nav($params);

    /***********************************************************************
     * First, if this is a POST then we are actually performing a privacy.
     *
     */
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
        return _people_privacyPost();

    ?>
<style type='text/css'>
.tg_form
{
}
.tg_form label
{
    font-size: 1.25em;
}
.tg_form .grey
{   color: #666;
    font-size:  0.85em;
}
.tg_form .area
{   margin-top: 2em; }
</style>
<div class='helpQuestion'>People: privacy</div>
<div class='helpAnswer'>
 This tool allows you to modify your social-network privacy settings.
</div>
<div class='helpAnswer'>
  <form enctype='multipart/form-data' method='post'>
   <div class='tg_form'>
    <p>
     <input type='checkbox' name='watchlist_shared' id='watchlist_shared' checked='checked' />
     <label for='watchlist_shared'>allow other people to look at
     <a href='<?= $gTagging->mBaseUrl; ?>/watchlist/<?= $gTagging->mCurUser['name'] ?>'>your watchlist</a></label><br />
     <input type='submit' value='change' />
    </p>
   </div>
  </form>
</div>
<?php
}

/** @brief  subscriptions people.
 *  @param  params  An array of incoming parameters.
 */
function people_subscriptions($params)
{
    global  $gTagging;
    global  $_SERVER;

    settings_nav($params);

    /***********************************************************************
     * First, if this is a POST then we are actually performing a subscriptions.
     *
     */
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
        return _people_subscriptionsPost();

    ?>
<style type='text/css'>
.tg_form
{
    font-size: 1.0em;
}
.tg_form label
{
    font-size: 0.85em;
}
.tg_form .grey
{   color: #666;
    font-size:  0.85em;
}
.tg_form .area
{   margin-top: 2em; }
</style>
<div class='helpQuestion'>People: subscriptions</div>
<div class='helpAnswer'>
 This tool allows you to set up subscriptions to tags.  This allows you to keep
 track of all new bookmarks saved with tags that interest you.
</div>
<div class='helpAnswer'>
  <form enctype='multipart/form-data' method='post'>
   <div class='tg_form'>
    <div class='area'>
     subscribe to a tag<br />
     <table border='0'>
      <tr>
       <td><label for='subtag'>tag</label></td>
       <td><input type='text' name='subtag' /></td>
      </tr>
      <tr>
       <td><label for='subuser'>only from user</label></td>
       <td><input type='text' name='subuser' />
           <span class='grey'>(leave blank for all users)</span></td>
      </tr>
      <tr>
       <td>&nbsp;</td>
       <td><input type='submit' value='subscribe' /></td>
      </tr>
     </table>
    </div>
   </div>
  </form>
</div>
<div class='helpQuestion'>Current subscriptions</div>
<div class='helpAnswer'>
</div>
<?php
}

/** @brief  privacy people based upon the incoming post information.
 *
 *  _POST should include:
 *      - watchlist_shared  on/off net value for privacy.
 */
function _people_privacyPost()
{
    global  $gTagging;
    global  $_SERVER;
    global  $_POST;

    if ($_SERVER['REQUEST_METHOD'] != 'POST')
    {
        // Only valid if posted -- redirect to people_privacy()
        return people_privacy();
    }

    $shared    = isset($_POST['watchlist_shared']);
    $curUserId = $gTagging->authenticatedUserId();

    // Start by validating the input
    if ($curUserId === false)
    {
        ?>
<p>
 <span style='color:#f00;font-weight:bold;'>privacy error</span>:
 there are one or more missing variables...
</p><?php
        return;
    }

    echo "<p>Change to ". ($_POST['watchlist_shared'] ? "shared" : "private") .
         "</p>\n";
    echo "<pre>POST"; print_r($_POST); echo "</pre>";
}

/** @brief  subscriptions people based upon the incoming post information.
 *
 *  _POST should include:
 *      - people          an array of existing people to be subscriptionsd.
 */
function _people_subscriptionsPost()
{
    global  $gTagging;
    global  $_SERVER;
    global  $_POST;

    if ($_SERVER['REQUEST_METHOD'] != 'POST')
    {
        // Only valid if posted -- redirect to people_privacy()
        return people_subscriptions();
    }

    $curUserId = $gTagging->authenticatedUserId();

    // Start by validating the input
    if ($curUserId === false)
    {
        ?>
<p>
 <span style='color:#f00;font-weight:bold;'>subscriptions error</span>:
 there are one or more missing variables...
</p><?php
        return;
    }

    echo "<pre>POST"; print_r($_POST); echo "</pre>";
}
?>
