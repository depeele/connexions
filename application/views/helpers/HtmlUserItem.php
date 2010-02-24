<?php
/** @file
 *
 *  View helper to render a single User Item / Bookmark in HTML.
 *
 */
class Connexions_View_Helper_HtmlUserItem extends Zend_View_Helper_Abstract
{
    /** @brief  The maximum number of characters to include in a summary,
     *          particularly a summary of a description.
     */
    public static   $summaryMax = 40;

    /** @brief  Generate an HTML view of a single User Item / Bookmark.
     *  @param  userItem    The Model_UserItem instance to present.
     *  @param  viewer      The Model_User     instance of the current viewer
     *  @param  showParts   The parts to present.
     *  @param  index       The index of this item in any list [ 0 ].
     *  @return The HTML representation of the user items.
     */
    public function htmlUserItem(Model_UserItem $userItem,
                                 Model_User     $viewer,
                                 array          $showParts  = array(),
                                                $index      = 0)
    {
        $html    = '';
        $isOwner = ( ($viewer && ($userItem->user->userId === $viewer->userId))
                        ? true
                        : false );

        $itemClasses = array();
        if ($isOwner)                   array_push($itemClasses, 'mine');
        else                            array_push($itemClasses, 'other');

        if ($userItem->isFavorite)      array_push($itemClasses, 'favorite');
        if ($userItem->isPrivate)       array_push($itemClasses, 'private');
        if ($showParts['minimized'])    array_push($itemClasses, 'minimized');
        if ($showParts['minimized'] &&
            ($showParts['item:data:userId'] !== true))
                                        array_push($itemClasses, 'no-userId');

        $html .= "<li class='item "             // item {
              .             implode(' ', $itemClasses) . "'>"
              .   "<form class='userItem'>"     // userItem {
              .    "<div class='status'>";

        if ($isOwner)
        {
            $html .= "<div class='favorite'>"
                  .  "<input type='checkbox' name='isFavorite' value='true'"
                  .     ($userItem->isFavorite ? " checked='true'" : "")
                  .         "/>"
                  . "</div>"
                  . "<div class='private'>"
                  .  "<input type='checkbox' name='isPrivate' value='true'"
                  .     ($userItem->isPrivate  ? " checked='true'" : "")
                  .         "/>"
                  . "</div>";
        }

        $html .=   "</div>";

        if ($showParts['item:stats'] === true)
        {
            $html .=   "<div class='stats'>";   // stats {
            if ($showParts['item:stats:countTaggers'] === true)
            {
               $html .= sprintf (  "<a class='countTaggers ui-corner-bottom' "
                                 .     "href='%s'>%d</a>",
                                 $this->view->url(array(
                                         'action'   => 'url',
                                         'urlHash'  =>
                                            $userItem->item->urlHash)),
                                 $userItem->item->userCount);
            }

            /*
            if ( ($showParts['item:stats:rating'] === true) &&
                 ( ($userItem->item->ratingCount > 0) ||
                   $isOwner) )
            */
            if ($showParts['item:stats:rating'] === true)
            {
                $html .=  "<div class='rating'>";       // rating {

                if ($showParts['item:stats:rating:stars'] === true)
                {
                    $html .= "<div class='stars'>";       // stars {

                    $ratingAvg = ($userItem->item->ratingCount > 0
                                    ? $userItem->item->ratingSum /
                                            $userItem->item->ratingCount
                                    : 0.0);

                    if ($userItem->item->ratingCount > 0)
                    {
                        // Present the avarage
                        $ratingTitle = sprintf("%d raters, %5.2f avg.",
                                               $userItem->item->ratingCount,
                                               $ratingAvg);

                        $html .= $this
                                    ->view
                                      ->htmlStarRating($ratingAvg,
                                                       ($isOwner
                                                        ? "average-owner"
                                                        : "average"),
                                                       true,    // read-only
                                                       $ratingTitle);
                    }

                    if ( $isOwner )
                    {
                        // Present the owner's rating
                        $html .= $this
                                ->view
                                  ->htmlStarRating($userItem->rating,
                                                   'owner',
                                                   false,   // read-only
                                                   $ratingTitle);
                    }

                    $html .= "</div>";      // stars }
                }

                if ( ($showParts['item:stats:rating:info'] === true) &&
                     ($userItem->item->ratingCount > 0) )
                {
                    $html .= "<div class='info'>"
                          .  sprintf( "<span class='count'>%d</span> raters, "
                                     ."<span class='average'>%3.2f</span> avg.",
                                        $userItem->item->ratingCount,
                                        $ratingAvg)
                          .  "</div>";
                }

                $html .=  "</div>"; // rating }
            }

            $html .=   "</div>";                // stats }
        }

        $clearFloats = $showParts['minimized'];
        $html .=   "<div class='data'>";    // data {


        if ( ($showParts['minimized']        === true) &&
             ($showParts['item:data:userId'] === true) )
        {
            $html .= $this->_renderUserId($userItem, $showParts);
        }


        if (($showParts['minimized'] === true) && ($viewer->isAuthenticated()))
            $html .= $this->_renderHtmlControl($userItem, $isOwner);

        if ($showParts['item:data:itemName'] === true)
        {
            $html .= "<h4 class='itemName'>"    // itemName {
                  .  sprintf("<a href='%s' title='%s'>%s</a>",
                             $userItem->item->url,
                             $userItem->item->urlHash,
                             htmlspecialchars($userItem->name));

            if ((! $showParts['minimized'] === true) &&
                ($viewer->isAuthenticated()) )
                $html .= $this->_renderHtmlControl($userItem, $isOwner);

            $html .= "</h4>";   // itemName }
        }

        if ($showParts['item:data:url'] === true)
        {
            $html .= "<div class='url'>"
                  .   sprintf ("<a href='%s' title='%s'>%s</a>",
                               $userItem->item->url,
                               $userItem->item->urlHash,
                               $userItem->item->url)
                  .  "</div>";
        }

        if ( ($showParts['item:data:description'] === true) &&
             (! @empty($userItem->description)) )
        {
            $html .= "<div class='description'>";   // description {


            if ($showParts['item:data:description:summary'] === true)
            {
                $summary = html_entity_decode($userItem->description,
                                              ENT_QUOTES);
                if (strlen($summary) > self::$summaryMax)
                {
                    // Shorten to no more than 'summaryMax' characters
                    $summary = substr($summary, 0, self::$summaryMax);
                    $summary = substr($summary, 0, strrpos($summary, " "));

                    // Trim any white-space or punctuation from the end
                    $summary = rtrim($summary, " \t\n\r.!?:;,-");

                    $summary .= '...';
                }
                $summary = htmlentities($summary, ENT_QUOTES);

                if ($showParts['minimized'] === true)
                    $summary = "&mdash; ". $summary;

                $html .= "<div class='summary'>"
                      .   $summary
                      .  "</div>";
            }

            if ($showParts['item:data:description:full'] === true)
            {
                $html .= "<div class='full'>"
                      .   htmlspecialchars($userItem->description)
                      .  "</div>";
            }

            $html .= "</div>";                      // description }
        }

        if ( ($showParts['minimized']        !== true) &&
             ($showParts['item:data:userId'] === true) )
        {
            $html .= "<br class='clear' />"
                  .  $this->_renderUserId($userItem, $showParts);
        }

        if ($showParts['item:data:tags'] === true)
        {
            /*
            if ( ($showParts['minimized']        === true) ||
                 ($showParts['item:data:userId'] !== true) )
                $html .= "<br class='clear' />";
            */

            $html .= "<ul class='tags'>";       // tags {

            foreach ($userItem->tags as $tag)
            {
                $html .= "<li class='tag'>"
                      .   "<a href='"
                      .     $this->view->url(array(
                                        'action' => 'tagged',
                                        'tag'    => $tag->tag))
                      .         "'>{$tag->tag}</a>"
                      .  "</li>";
            }

            $html .= "</ul>"                    // tags }
                  .  "<br class='clear' />";

            $clearFloats = false;
        }

        if ( $showParts['item:data:dates'] )
        {
            $html .= "<div class='dates'>";

            if ($showParts['item:data:dates:tagged'] === true)
            {
                $dateTitle = 'date tagged';
                if (($showParts['item:data:dates:updated'] === true) &&
                    ($userItem->updatedOn == $userItem->taggedOn) )
                {
                    $dateTitle .= '/updated';
                }

                $html .= "<div class='tagged' title='{$dateTitle}'>"
                      .    $userItem->taggedOn
                      .  "</div>";
            }

            if (($showParts['item:data:dates:updated'] === true) &&
                ($userItem->updatedOn != $userItem->taggedOn) )
                $html .= "<div class='updated' title='date updated'>"
                      .    $userItem->updatedOn
                      .  "</div>";

            $html .= "</div>"
                  .  "<br class='clear' />";

            $clearFloats = false;
        }

        if ($clearFloats)
            $html .= "<br class='clear' />";

        $html .=   "</div>"     // data }
              .   "</form>"     // userItem }
              .  "</li>";       // item }

        return $html;
    }

    /*************************************************************************
     * Protected helpers
     *
     */
    protected function _renderUserId($userItem, $showParts)
    {
        $showAvatar = (($showParts['item:data:userId:avatar'] === true) &&
                       ( ! @empty($userItem->user->pictureUrl)) );

        $html =  "<div class='userId'>"
              .   sprintf("<a href='%s' title='%s'>",
                          $this->view->url(array(
                                  'action' => $userItem->user->name)),
                          $userItem->user->fullName);

        $html .=   "<div class='img icon-highlight'>";
        if ( $showAvatar )
        {
            // Include the user's picture / avatar
            $html .= sprintf ("<img src='%s' />",
                              $userItem->user->pictureUrl);
        }
        else
        {
            // Include the default user icon
            $html .= "<div class='ui-icon ui-icon-person'>"
                  .   "&nbsp;"
                  .  "</div>";
        }
        $html .=   "</div>";

        $html .=   "<span class='name'>{$userItem->user->name}</span>"
              .   "</a>"
              .  "</div>";

        return $html;
    }

    protected function _renderHtmlControl($userItem, $isOwner)
    {
        $html = "<div class='control'>";  //  control {

        if ($isOwner)
        {
            $html .= sprintf( "<a class='item-edit'   href='%s'>EDIT</a> | "
                             ."<a class='item-delete' href='%s'>DELETE</a>",
                            $this->view->url(array(
                                    'action' => 'itemEdit',
                                    'item'   => $userItem->itemId)),
                            $this->view->url(array(
                                    'action' => 'itemDelete',
                                    'item'   => $userItem->itemId)) );
        }
        else
        {
            $saveUrl = $this->view->url(array('action' => 'post'))
                     .  '?name='.        urlencode($userItem->name)
                     .  '&url='.         urlencode($userItem->item->url)
                     .  '&description='. urlencode($userItem->description)
                     .  '&tags='.        urlencode(''.$userItem->tags);

            $html .= sprintf ( "<a class='item-save'   href='%s'>SAVE</a>",
                              $saveUrl);
        }

        $html .=  "</div>"; //  control }

        return $html;
    }
}
