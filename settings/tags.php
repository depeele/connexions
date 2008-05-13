<?php
/** @file
 *
 *  This is a connexions plug-in that implements the settings/tags
 *  namespace.
 *      tags_main           - present the top-level tags options.
 *      tags_rename         - if $_SERVER['REQUEST_METHOD'] is POST, invoke
 *                            _tags_renamePost() otherwise, present a form that
 *                            describes and enables tag renaming.
 *      tags_delete         - if $_SERVER['REQUEST_METHOD'] is POST, invoke
 *                            _tags_deletePost() otherwise, present a form that
 *                            describes and enables tag deletion.
 *
 *      _tags_renamePost    - a private routine that validates inputs and
 *                            performs the renaming of tags.
 *      _tags_deletePost    - a private routine that validates inputs and
 *                            performs the deletion of tags.
 */
require_once('settings/index.php');

/** @brief  Present the top-level tag settings.
 *  @param  params  An array of incoming parameters.
 */
function tags_main($params)
{
    global  $gTagging;

    settings_nav($params);

    ?>
<div class='helpQuestion'>Tags: management</div>
<div class='helpAnswer'>
 This section allows you to <a href='tags/rename'>rename</a> or <a
 href='tags/delete'>delete</a> your tags.  For more information about tags,
 please take a look at the <a href='<?= $gTagging->mBaseUrl?>/help/about#What_are_tags'>
 help section on tags</a>.
</div>
<?php

    $curUserId = $gTagging->authenticatedUserId();

    if ($curUserId === false)
        return;

    // Present the tag cloud for the current user.
    $tagUrl = $this->mBaseUrl . '/' . $gTagging->mCurUser['name'];

    $tags = $gTagging->mTagDB->userTags(array($gTagging->getCurUserId()),
                                        'tag ASC');
    $html = $gTagging->cloudHtml($tags,
                                 $tagUrl . '/%tag%',
                                 'Alpha',                   // order
                                 null,                      // tagLimits
                                 $tagUrl . '/%tagRest%');

    echo "<div class='helpAnswer'>". $html . "</div>";
}

/** @brief  Present a tag rename form.
 *  @param  params  An array of incoming parameters.
 */
function tags_rename($params)
{
    global  $gTagging;
    global  $_SERVER;

    settings_nav($params);

    $curUserId = $gTagging->authenticatedUserId();

    if ($curUserId === false)
        return;

    /***********************************************************************
     * First, if this is a POST then we are actually performing a rename.
     *
     */
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
        return _tags_renamePost();

    $tagUrl = $gTagging->mBaseUrl . '/' . $gTagging->mCurUser['name'];
    $tags   = $gTagging->mTagDB->userTags(array($gTagging->getCurUserId()),
                                          'tag ASC');

    ?>
<style type='text/css'>
.tg_form
{
   font-size:  0.85em;
   line-height:1.2em;
   margin: 0.5em 0 0.5em 2em;
}
.tg_form .grey
{   color: #666; }
table.editList
{
    border-collapse:collapse;
    border: 1px solid #c1dad7;
    background-color: #fff;
}
table.editList th
{
    background-color: #d6eef0;
    border: 1px solid #c1dad7;
    padding: 4px;
    text-align: center;
}
table.editList tr.even, table.editList tr.even input
{
    background-color: #f5fafa;
}
table.editList tr.odd, table.editList tr.odd input
{
    background-color: #fff;
}
table.editList td
{
    background-color: transparent;
    border: 1px solid #c1dad7;
    text-align: center;
}
table.editList input    /* style the tag edit boxes */
{
    border:     0;
    text-align: center;
}
</style>
<div class='helpQuestion'>Tags: rename</div>
<div class='helpAnswer'>
 This tool allows the renaming of existing tags that you have used.
</div>
<div class='helpAnswer'>
  Click on any tag name below to edit it.
  <form enctype='multipart/form-data' method='post'>
   <div class='tg_form'>
    <table class='editList'>
     <tr><th>Tags</th><th>Uses</th></tr>
   <?php

    $count  = 0;
    foreach ($tags as $idex => $tagInfo)
    {
        $tag = $tagInfo['tag'];
        $qty = $tagInfo['itemCount'];

        if (($count % 2) == 0)
            $class = 'even';
        else
            $class = 'odd';

        echo "     <tr class='$class'>".
                    "<input type='hidden' name='tags[$count]' value='$tag' />".
                    "<td>".
                    "<input type='text' name='newTags[$count]' value='$tag' />".
                    "</td><td><a href='$tagUrl/$tag'>". (int)$qty ."</a></td></tr>\n";
        $count++;
    }

   ?>
    </table>
    <input type='submit' value='rename' />
   </div>
  </form>
</div>
<?php
}

/** @brief  delete tags.
 *  @param  params  An array of incoming parameters.
 */
function tags_delete($params)
{
    global  $gTagging;
    global  $_SERVER;

    settings_nav($params);

    $curUserId = $gTagging->authenticatedUserId();

    if ($curUserId === false)
        return;

    /***********************************************************************
     * First, if this is a POST then we are actually performing a delete.
     *
     */
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
        return _tags_deletePost();

    $tagUrl = $this->mBaseUrl . '/' . $gTagging->mCurUser['name'];
    $tags   = $gTagging->mTagDB->userTags(array($gTagging->getCurUserId()),
                                          'tag ASC');

    ?>
<style type='text/css'>
.tg_form
{
   font-size:  0.85em;
   line-height:1.2em;
   margin: 0.5em 0 0.5em 2em;
}
.tg_form .grey
{   color: #666; }
table.editList
{
    border-collapse:collapse;
    border: 1px solid #c1dad7;
    background-color: #fff;
}
table.editList th
{
    background-color: #d6eef0;
    border: 1px solid #c1dad7;
    padding: 4px;
    text-align: center;
}
table.editList tr.even, table.editList tr.even input
{
    background-color: #f5fafa;
}
table.editList tr.odd, table.editList tr.odd input
{
    background-color: #fff;
}
table.editList td
{
    background-color: transparent;
    border: 1px solid #c1dad7;
    text-align: center;
}
</style>
<div class='helpQuestion'>Tags: delete</div>
<div class='helpAnswer'>
 This tool allows the deletion of existing tags.
</div>
<div class='helpAnswer'>
 If by deleting a tag, an item that you have tagged no longer has any tags
 associated with it, the item will also be deleted.

  <form enctype='multipart/form-data' method='post'>
   <div class='tg_form'>
    <table class='editList'>
     <tr><th>Tags</th><th>Uses</th><th>Delete?</th></tr>
   <?php

    $count = 0;
    foreach ($tags as $idex => $tagInfo)
    {
        $tag = $tagInfo['tag'];
        $qty = $tagInfo['itemCount'];

        if (($count % 2) == 0)
            $class = 'even';
        else
            $class = 'odd';

        echo "     <tr class='$class'>".
                    "<td>$tag</td>".
                    "<td>". (int)$qty ."</td>".
                    "<td><input type='checkbox' name='tags[$tag]' /></td>".
                    "</tr>\n";
        $count++;
    }

   ?>
    </table>
    <input type='submit' value='delete' />
   </div>
  </form>
</div>
<?php
}

/** @brief  rename tags based upon the incoming post information.
 *
 *  _POST should include:
 *      - tags          an array of existing tags.
 *      - newTags       an array (parallel to 'tags') of new tag names.
 */
function _tags_renamePost()
{
    global  $gTagging;
    global  $_SERVER;
    global  $_POST;
    global  $_FILES;

    if ($_SERVER['REQUEST_METHOD'] != 'POST')
    {
        // Only valid if posted -- redirect to tags_rename()
        return tags_rename();
    }

    $curUserId = $gTagging->authenticatedUserId();

    // Start by validating the input
    if ( ($curUserId === false)         ||
         (! isset($_POST['tags']))      ||
         (! isset($_POST['newTags'])) )
    {
        ?>
<p>
 <span style='color:#f00;font-weight:bold;'>rename error</span>:
 there are one or more missing variables...
</p><?php
        return;
    }

    //echo "<pre style='width:40%; float:left;'>Rename tags: orig"; print_r($_POST['tags']); echo "</pre>\n";
    //echo "<pre style='width:40%; float:left;'>Rename tags: new "; print_r($_POST['newTags']); echo "</pre>\n";

    // First, figure out how many tags are being renamed.
    $rename = array();
    for ($idex = 0; $idex < count($_POST['tags']); $idex++)
    {
        $old = $_POST['tags'][$idex];
        $new = $_POST['newTags'][$idex];
        if ($old == strtolower($new))
            continue;

        $oldTags = array($old);

        // Split out the new tags (one or more may replace an old tag)
        $newTags = preg_split('/\s*,\s*/', $new);

        $rename[] = array('oldTags' => $oldTags,
                          'newTags' => $newTags);
    }
    $nTags = count($rename);

    ?>
<div class='helpQuestion'><?php printf ("Renaming %u tag%s",
                                        $nTags,($nTags == 1 ? "": "s"));?></div>
<div class='helpAnswer'>
<?php

    for ($idex = 0; $idex < $nTags; $idex++)
    {
        $oldTags = $rename[$idex]['oldTags'];
        $newTags = $rename[$idex]['newTags'];

        // Retrieve all user-items tagged with $old
        $items  = $gTagging->mTagDB->items(array($gTagging->getCurUserId()),
                                           $gTagging->mTagDB->tagIds($oldTags));
        $nItems = count($items);

        //printf ("<b>%s</b> to <b style='color:#393;'>%s</b>...",
        printf ("<b>%s</b> to <span class='success'>%s</span> (%u item%s)...",
                implode(', ', $oldTags),
                implode(', ', $newTags),
                $nItems,
                ($nItems == 1 ? "" : "s"));
        flush();

        // Add the new tags and then delete the old (to ensure the item doesn't
        // get deleted due to no tags).
        for ($jdex = 0; $jdex < $nItems; $jdex++)
        {
            printf ("<sup>%u</sup>.", $items[$jdex]);
            flush();
            //printf ("++ '%s' ++", implode(', ', $newTags));
            if (! $gTagging->mTagDB->tagsAdd($gTagging->getCurUserId(), 
                                             $items[$jdex],
                                             $newTags))
            {
                $nNew = count($newTags);
                printf("<span class='error'>".
                            "Cannot add new tag%s</span>.",
                            ($nNew == 1 ? "" : "s")); 
                continue;
            }
            printf (".");
            flush();

            //printf ("-- '%s' --", implode(', ', $oldTags));
            if (! $gTagging->mTagDB->tagsDelete($gTagging->getCurUserId(), 
                                                $items[$jdex],
                                                $oldTags))
            {
                echo "<span class='error'>Cannot delete old tag</span>.";
                continue;
            }
            printf (".");
            flush();
        }
        printf ("done<br />\n");
        flush();
    }

    ?>
</div>
<?php
}

/** @brief  delete tags based upon the incoming post information.
 *
 *  _POST should include:
 *      - tags          an array of existing tags to be deleted.
 */
function _tags_deletePost()
{
    global  $gTagging;
    global  $_POST;

    if ($_SERVER['REQUEST_METHOD'] != 'POST')
    {
        // Only valid if posted -- redirect to tags_delete()
        // and fake the params...
        $params = array('Action'    => 'main:settings',
                        'params'    => null,
                        'crumb'     =>
                            "<a href='/connexions-test/settings'>settings</a>".
                                " / ".
                            "<a href='/connexions-test/settings/tags'>".
                                "tags</a>".
                                " / delete"
                        );
        return tags_delete($params);
    }

    /*echo "<pre>_POST";
        var_dump($_POST); echo "</pre>";*/

    // Start by validating the input
    $curUserId = $gTagging->authenticatedUserId();

    if ( ($curUserId === false) ||
         (! isset($_POST['tags'])) )
    {
        ?>
<p>
 <span style='color:#f00;font-weight:bold;'>delete error</span>:
 there are one or more missing variables...
</p><?php
        return;
    }

    $nTags = count($_POST['tags']);

    ?>
<div class='helpQuestion'><?php printf ("Deleting %u tag%s",
                                        $nTags,($nTags == 1 ? "": "s"));?></div>
<div class='helpAnswer'>
<?php

    //echo "<pre>_POST['tags']"; print_r($_POST['tags']); echo "</pre>";
    $userid     = $gTagging->getCurUserId();
    $delayItems = array();
    foreach ($_POST['tags'] as $tag => $flag)
    {
        // Retrieve all user-items tagged with $tag
        $items  = $gTagging->mTagDB->items(array($userid),
                                           $gTagging->mTagDB->tagIds($tag));

        $nItems = count($items);
        printf ("<b>%s</b> (%u item%s)...",
                $tag, $nItems, ($nItems == 1 ? "" : "s"));
        flush();

        for ($jdex = 0; $jdex < count($items); $jdex++)
        {
            $itemid = (int)$items[$jdex];

            printf ("<sup>%u</sup>", $itemid);
            flush();

            // Grab all the tags connected to this specific item...
            $itemTags = count($gTagging->mTagDB->itemTags(array($userid),
                                                          array($itemid)));
            if ($itemTags < 2)
            {
                // This item will be deleted if this tag is removed...
                $item_info = $this->mTagDB->userItem($userid, $itemid);

                $delayItems[] = $item_info;
                echo "#";
                flush();
                continue;
            }

            //printf ("-- '%s' (%u left)", $tag, $itemTags);
            if (! $gTagging->mTagDB->tagsDelete($userid, $itemid,
                                                array($tag)))
            {
                echo "<span class='error'>Cannot delete tag</span>.";
                continue;
            }

            printf (".");
            flush();
        }
        printf (". done<br />\n");
        flush();
    }

    // Now, deal with any delayed items.
    $nItems = count($delayItems);
    if ($nItems > 0)
    {
        $verb = ($nItems == 1 ? "is" : "are");
        $noun = ($nItems == 1 ? "It" : "They");

        printf ("<div style='margin-top:1em;'>There %s %u item%s that will ".
                "be deleted if the last tag is removed.  %s %s presented ".
                "below for your action.</div>",
                $verb,
                $nItems,
                ($nItems == 1 ? "" : "s"),
                $noun, $verb);

        $html = $gTagging->itemsHtml($delayItems);

        ?>
<div id='content'>
 <div id='left'>
  <div class='ItemList'>
   <?= $html ?>
  </div>
 </div>
</div>
<div>&nbsp;</div>
<?php

    }

    ?>
</div>
<?php
}
?>
