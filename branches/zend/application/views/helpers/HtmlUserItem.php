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
    public static   $summaryMax = 20;

    /** @brief  Generate an HTML view of a single User Item / Bookmark.
     *  @param  userItem    The Model_UserItem instance to present.
     *  @param  viewer      The Model_User     instance of the current viewer
     *  @param  showParts   The parts to present [ null === 'regular' ]
     *                      (see Connexions_View_Helper_HtmlUserItems::
     *                                                          $styleParts)
     *  @param  index       The index of this item in any list [ 0 ].
     *  @return The HTML representation of the user items.
     */
    public function htmlUserItem(Model_UserItem $userItem,
                                 Model_User     $viewer,
                                                $showParts  = null,
                                                $index      = 0)
    {
        $html = '';

        if (! @is_array($showParts))
        {
            $showParts = Connexions_View_Helper_HtmlUserItems::
                            $styleParts[Connexions_View_Helper_HtmlUserItems::
                                                                STYLE_REGULAR];
        }

        $isOwner = ( ($viewer && ($userItem->user->userId === $viewer->userId))
                        ? true
                        : false );

        $itemClasses = array();
        if ($isOwner)                   array_push($itemClasses, 'mine');
        else                            array_push($itemClasses, 'other');

        if ($userItem->isFavorite)      array_push($itemClasses, 'favorite');
        if ($userItem->isPrivate)       array_push($itemClasses, 'private');
        if ($showParts['minimized'])    array_push($itemClasses, 'minimized');

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

        if ($showParts['meta'] === true)
        {
            $html .=   "<div class='meta'>";    // meta {
            if ($showParts['meta:countTaggers'] === true)
            {
               $html .= sprintf ("<a class='countTaggers' href='%s'>%d</a>",
                                 $this->view->url(array(
                                         'action'   => 'url',
                                         'urlHash'  =>
                                            $userItem->item->urlHash)),
                                 $userItem->item->userCount);
            }

            if ( ($showParts['meta:rating'] === true) &&
                 ( ($userItem->item->ratingCount > 0) ||
                   $isOwner) )
            {

                $html .=  "<div class='rating'>";       // rating {

                if ($showParts['meta:rating:stars'] === true)
                {
                    $html .= "<div class='stars'>";       // stars {

                    $ratingAvg = ($userItem->item->ratingCount > 0
                                    ? $userItem->item->ratingSum /
                                            $userItem->item->ratingCount
                                    : 0.0);

                    if ( ($showParts['meta:rating:stars:average'] === true) &&
                         ($userItem->item->ratingCount > 0) )
                    {
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
             
                    if ( ($showParts['meta:rating:stars:owner'] === true) &&
                         $isOwner )
                    {
                        $html .= $this->view->htmlStarRating($userItem->rating,
                                                            'owner');
                    }

                    $html .= "</div>";      // stars }
                }

                if ( ($showParts['meta:rating:meta'] === true) &&
                     ($userItem->item->ratingCount > 0) )
                {
                    $html .= "<div class='meta'>"
                          .  sprintf( "<span class='count'>%d</span> raters, "
                                     ."<span class='average'>%3.2f</span> avg.",
                                        $userItem->item->ratingCount,
                                        $ratingAvg)
                          .  "</div>";
                }

                $html .=  "</div>"; // rating }
            }

            $html .=   "</div>";                // meta }
        }


        $clearFloats = $showParts['minimized'];
        $html .=   "<div class='data'>";    // data {


        if ( ($showParts['minimized'] === true) &&
             ($showParts['userId']    === true) )
        {
            $html .= "<div class='userId'>"
                  .   sprintf("<a href='%s' title='%s'>%s</a>",
                              $this->view->url(array(
                                      'action' => $userItem->user->name)),
                              $userItem->user->fullName,
                              $userItem->user->name)
                  .  "</div>";
        }


        if ($showParts['itemName'] === true)
        {
            $html .= "<h4 class='itemName'>"    // itemName {
                  .  sprintf("<a href='%s' title='%s'>%s</a>",
                             $userItem->item->url,
                             $userItem->item->urlHash,
                             htmlspecialchars($userItem->name));

            if (! $showParts['minimized'] === true)
                $html .= $this->_renderHtmlControl($userItem, $isOwner);

            $html .= "</h4>";   // itemName }
        }

        if ($showParts['minimized'] === true)
            $html .= $this->_renderHtmlControl($userItem, $isOwner);

        if ($showParts['url'] === true)
        {
            $html .= "<div class='url'>"
                  .   sprintf ("<a href='%s' title='%s'>%s</a>",
                               $userItem->item->url,
                               $userItem->item->urlHash,
                               $userItem->item->url)
                  .  "</div>";
        }

        if ( ($showParts['descriptionSummary'] === true) &&
             (! @empty($userItem->description)) )
        {
            $summary = html_entity_decode($userItem->description, ENT_QUOTES);
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

            $html .= "<div class='descriptionSummary'>"
                  .   $summary
                  .  "</div>";
        }

        if ( ($showParts['description'] === true) &&
             (! @empty($userItem->description)) )
        {
            $html .= "<div class='description'>"
                  .   htmlspecialchars($userItem->description)
                  .  "</div>";
        }

        if ( ($showParts['minimized'] !== true) &&
             ($showParts['userId']    === true) )
        {
            $html .= "<br class='clear' />"
                  .  "<div class='userId'>"
                  .   sprintf("<a href='%s' title='%s'>%s</a>",
                              $this->view->url(array(
                                      'action' => $userItem->user->name)),
                              $userItem->user->fullName,
                              $userItem->user->name)
                  .  "</div>";
        }

        if ($showParts['tags'] === true)
        {
            if ($showParts['minimized'] === true)
                $html .= "<br class='clear' />";

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

        if ( $showParts['dates'] )
        {
            $html .= "<div class='dates'>";

            if ($showParts['dates:tagged'] === true)
                $html .= "<div class='tagged'>"
                      .    $userItem->taggedOn
                      .  "</div>";

            if ($showParts['dates:updated'] === true)
                $html .= "<div class='updated'>"
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
