<?php
/** @file
 *
 *  Present information about browsing
 */
global  $gTagging;

?>
<div class='helpQuestion'>How can I view someone else's bookmarks?</div>
<div class='helpAnswer'>
  You can view another user's bookmarks by navigating to their page. For
  example:
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
