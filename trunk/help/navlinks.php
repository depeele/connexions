<?php
/** @file
 *
 *  Present help information about navigation
 */

global  $gTagging;

?>
<div class='helpQuestion'>What are those links at the top of the page?</div>
<div class='helpAnswer'>
 The navigation links at the top of the page are short-cuts to commonly used
 areas of <b>Connexions</b>.
</div>

<?php
$curUserId = $gTagging->authenticatedUserId();

if ($curUserId !== false)
{
    /*****************************************************************
     * Information for authenticated users only.
     */
    ?>
<a name='mine'><div class='helpQuestion'>What is the <b>mine</b> link for?</div></a>
<div class='helpAnswer'>
 The <a href='<?
    printf ("%s/%s", $gTagging->mBaseUrl, $gTagging->mCurUser['name']);
    ?>'>mine</a> link is a quick way to get back to the list of items that you
 have tagged.
</div>

<a name='post'><div class='helpQuestion'>What is the <b>post</b> link for?</div></a>
<div class='helpAnswer'>
 The <a href='<?= $gTagging->mBaseUrl ?>/post'>post</a> link will present a
 form that you can fill in to tag a URL without actually visiting the URL.
</div>

<a name='settings'><div class='helpQuestion'>What is the <b>settings</b> link for?</div></a>
<div class='helpAnswer'>
 The <a href='<?= $gTagging->mBaseUrl ?>/settings'>settings</a> link will
 take you to the settings area where you can adjust the current settings of
 your <b>Connextions</b> account.
</div><?php
}

/*****************************************************************
 * Information for both authenticated an unauthenticated users.
 */
?>
<a name='help'><div class='helpQuestion'>What is the <b>help</b> link for?</div></a>
<div class='helpAnswer'>
 The <a href='<?= $gTagging->mBaseUrl ?>/help/'>help</a> link takes you to the
 <b>Connexions</b> help area for help on how to use <b>Connexions</b>.
</div>
