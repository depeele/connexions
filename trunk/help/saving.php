<?php
/** @file
 *
 *  Present information about saving bookmarks.
 */
global  $gTagging;

$url       = $gTagging->mBaseUrl;
$curUserId = $gTagging->authenticatedUserId();

?>
<div class='helpQuestion'>What can be bookmarked?</div>
<div class='helpAnswer'>
    Anything with a <a href="http://prototypes.research.sn.gchq/wikipedia/index.php/Uniform_Resource_Locator"
    title="Uniform Resource Locator">URL</a> can be bookmarked.
</div>

<a name='How do I bookmark a URL'><div class='helpQuestion'>How do I bookmark a URL?</div></a>
<div class='helpAnswer'><?php

if ($curUserId === false)
{
    ?>
    First of all, you can only bookmark using the
    <a href='https://<?php
            echo $_SERVER['HTTP_HOST'] . $url; ?>'>secure version of
            <b>Connexions</b></a>.

    <br />&nbsp;<br />
    The first time to visit the secure site, an account will be created for you
    that will allow you to bookmark URLs.<?php
}
else
{
    ?>
    There are currently three ways to bookmark a URL.
    <ul>
        <li>You can follow the <a href="<? echo $url;?>/post" title="post">post</a> link.</li>
        <li>You can add a <a href="<? echo $url;?>/help/buttons" title="post to connexions">bookmarklet</a> to your web browser.</li>
        <li>You can <a href="<? echo $url;?>/settings/bookmarks/import" title="import">import your browser's bookmarks</a> into Connexions.</li>
    </ul>
    <br />
    In any case, you will be prompted for a few things:
    <ul>
        <li>The <b>name</b> of the site, which is simply a title to be displayed with the link.</li>
        <li>The <b>URL</b> for the site. This is automatically filled in, if you are using the bookmarklet method of posting.</li>
        <li>A comma separated list of <b>tags</b> for this site. The tags are simply keywords that you ascribe to the site, typically to describe the content of the site in some way. The tags should be words (or phrases) that are meaningful to you, the user.</li>
        <li>A <b>description</b> of the site. This is just a short entry describing the site.</li>
        <li>A <b>rating</b> of the site, from 1 to 5 stars:<?php

        $isActive  = true;
        $active    = '-active';
        $ratingId  = 0;
        $id        = 0;
        $rating    = 2;
        $avgRating = 3;

        $html = "
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

            if ($isActive)
            {
                $html .= "'
            title='Rate this {$idex} star out of 5' class='{$class}'
            onclick='return false;'";
            }
            else
            {
                $html .= "'
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

        echo $html;
        ?><br />
        In the above example, the user has rated the item 2 stars and the
        yellow border indicates that the average rating for all users is 3
        stars.<br />&nbsp;</li>

        <li>You may add this site to a list of personal <b>favorites</b> by clicking the <img id='star' src='../images/Fish.png' alt='star' align='absmiddle'/> located below the description box.</li>
        <li>You may make this a <b>private</b> bookmark, viewable only by you, by clicking the <img id='lock' src='../images/Pad-un-lock.png' alt='lock' align='absmiddle'/> located below the description box.</li>
    </ul><?php
}

?>
</div>

<a name='Can I make changes to a bookmark'><div class='helpQuestion'>Can I make changes to a bookmark?</div></a>
<div class='helpAnswer'><?php
if ($curUserId === false)
{
    ?>
    Once you have a <b>Connexions</b> account (by visiting the
    <a href='https://<?php
            echo $_SERVER['HTTP_HOST'] . $url; ?>'>secure version of
            <b>Connexions</b></a>), you will be able to create and edit
    bookmarks.<?php

}
else
{
    ?>
    You can edit a bookmark by clicking the <img id='user_edit'
    src='../images/Buttons-user_edit.png' alt='pencil' align='absmiddle'/>
    below the corresponding bookmark. You can also delete a bookmark by
    clicking the <img id='user_delete' src='../images/Buttons-user_delete.png'
    alt='X' align-'absmiddle'/> below the bookmark.<?php
}

?>
</div>

