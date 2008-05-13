<?php
/** @file
 *
 *  Present information about sharing bookmarks.
 */
global  $gTagging;

?>
<div class='helpQuestion'>How can I share my bookmarks?</div>
<div class='helpAnswer'>
  There are a number of ways to share your bookmarks. The simplest way to
  access your bookmarks is to navigate to your user page. For example:
  <pre>
    <?php echo $gTagging->mFullUrl; ?>/<b>&lt;uid&gt;</b><?php

    if ($_SERVER['HTTPS'] === 'on')
    {
        printf ("\n  or\n    http://%s%s/<b>&lt;uid&gt;</b>",
                $_SERVER['HTTP_HOST'], $gTagging->mBaseUrl);
    }
    ?>
  </pre>
</div>

<a name='Can others subscribe to my bookmarks'><div class='helpQuestion'>Can others subscribe to my bookmarks?</div></a>
<div class='helpAnswer'>
    Yes, but this is a feature that is not yet implemented. Eventually, you
    will be able to subscribe to the bookmarks of multiple users, creating a
    <b>watchlist</b>, possibly even multiple watchlists. For example, you may
    have a watchlist of people whose bookmarks you find interesting, or you may
    have an office watchlist to keep track of the bookmarks of your branch's
    fellow employees.
</div>

<a name='Can I bookmark something for another user'><div class='helpQuestion'>Can I bookmark something for another user?</div></a>
<div class='helpAnswer'>
    Yes, but this is also a feature that is not yet fully implemented. Ultimately, you will have an <b>inbox</b> which will
    contain links that have been specifically tagged for you by other users. But, these will make use of the special
    <b>for:</b> tag, so to a certain extent, this can be done. If you would like to tag a site for a particular user,
    just use the tag '<b>for:&lt;uid&gt;</b>', for example.
</div>

