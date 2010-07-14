<?php
/** @file
 *
 *  View helper to render a single User Item / Bookmark in HTML.
 *
 */
class View_Helper_HtmlBookmark extends View_Helper_Bookmark
{
    protected   $_bookmark      = null;
    protected   $_viewer        = null;
    protected   $_showParts     = array();

    protected   $_isOwner       = false;
    protected   $_clearFloats   = false;

    /** @brief  Generate an HTML view of a single User Item / Bookmark.
     *  @param  bookmark    The Model_Bookmark instance to present.
     *  @param  viewer      The Model_User     instance of the current viewer
     *  @param  showParts   The parts to present.
     *  @param  index       The index of this item in any list [ 0 ].
     *  @return The HTML representation of the user items.
     */
    public function htmlBookmark(Model_Bookmark $bookmark,
                                 Model_User     $viewer,
                                 array          $showParts  = array(),
                                                $index      = 0)
    {
        $html           = '';
        $this->_isOwner = ( ($viewer &&
                             ($bookmark->user->userId === $viewer->userId))
                                ? true
                                : false );

        $itemClasses    = array();
        if ($this->_isOwner)            array_push($itemClasses, 'mine');
        else                            array_push($itemClasses, 'other');

        if ($bookmark->isFavorite)      array_push($itemClasses, 'favorite');
        if ($bookmark->isPrivate)       array_push($itemClasses, 'private');
        if ($showParts['minimized'])    array_push($itemClasses, 'minimized');
        if ($showParts['minimized'] &&
            ($showParts['item:data:userId'] !== true))
                                        array_push($itemClasses, 'no-userId');

        $this->_bookmark  =& $bookmark;
        $this->_viewer    =& $viewer;
        $this->_showParts =& $showParts;

        $html .= "<li class='item "             // item {
              .             implode(' ', $itemClasses) . "'>"
              .   "<form class='bookmark'>"     // bookmark {
              .    $this->_renderHiddenData()
              .    $this->_renderStatus()
              .    $this->_renderStats()
              .    "<div class='data'>";    // data {

        $this->_clearFloats = $showParts['minimized'];
        if ( ($showParts['minimized']        === true) &&
             ($showParts['item:data:userId'] === true) )
        {
            $html .= $this->_renderUserId();
        }

        if ($viewer->isAuthenticated())
            $html .= $this->_renderHtmlControl();

        $html .= $this->_renderItemName()
              .  $this->_renderItemUrl()
              .  $this->_renderItemDescription();

        if ( ($showParts['minimized']        !== true) &&
             ($showParts['item:data:userId'] === true) )
        {
            $html .= "<br class='clear' />"
                  .  $this->_renderUserId();
        }

        $html .= $this->_renderItemTags()
              .  $this->_renderItemDates();

        if ($this->_clearFloats)
            $html .= "<br class='clear' />";

        $html .=   "</div>"     // data }
              .   "</form>"     // bookmark }
              .  "</li>";       // item }

        return $html;
    }

    /*************************************************************************
     * Protected helpers
     *
     */
    protected function _renderHiddenData()
    {
        $html = sprintf( "<input type='hidden' name='userId' value='%s' />"
                        ."<input type='hidden' name='itemId' value='%s' />",
                        $this->_bookmark->user->userId,
                        $this->_bookmark->itemId);

        return $html;
    }

    protected function _renderStatus()
    {
        $html =    "<div class='status'>";

        if ($this->_isOwner)
        {
            $html .= "<div class='favorite'>"
                  .  "<input type='checkbox' name='isFavorite' value='true'"
                  .     ($this->_bookmark->isFavorite ? " checked='true'" : "")
                  .         "/>"
                  . "</div>"
                  . "<div class='private'>"
                  .  "<input type='checkbox' name='isPrivate' value='true'"
                  .     ($this->_bookmark->isPrivate  ? " checked='true'" : "")
                  .         "/>"
                  . "</div>";
        }

        $html .=   "</div>";

        return $html;
    }

    protected function _renderStats()
    {
        $html = '';

        if ($this->_showParts['item:stats'] !== true)
            return $html;

        $html .=   "<div class='stats'>";
        if ($this->_showParts['item:stats:count'] === true)
        {
            $countValue = $this->_bookmark->item->userCount;
            $countTitle = "user". ($count !== 0 ? 's' : '');

            $html .= sprintf(  "<a class='count ui-corner-bottom' "
                             .    "title='%s' "
                             .     "href='%s'>%d</a>",
                             $countTitle,
                             $this->view->url(array(
                                     'action'   => 'url',
                                     'urlHash'  =>
                                        $this->_bookmark->item->urlHash)),
                             $countValue);
        }

        /*
        if ( ($this->_showParts['item:stats:rating'] === true) &&
             ( ($this->_bookmark->item->ratingCount > 0) ||
               $this->_isOwner) )
        */
        if ($this->_showParts['item:stats:rating'] === true)
        {
            $html .=  "<div class='rating'>";       // rating {

            if (($this->_showParts['item:stats:rating:stars'] === true) &&
                ( ($this->_bookmark->item->ratingCount > 0) ||
                  $this->_isOwner ) )
            {
                $html .= "<div class='stars'>";       // stars {

                $ratingAvg = ($this->_bookmark->item->ratingCount > 0
                                ? $this->_bookmark->item->ratingSum /
                                        $this->_bookmark->item->ratingCount
                                : 0.0);

                if ($this->_bookmark->item->ratingCount > 0)
                {
                    // Present the avarage
                    $ratingTitle = sprintf("%d raters, %5.2f avg.",
                                           $this->_bookmark->item->ratingCount,
                                           $ratingAvg);

                    $html .= $this
                                ->view
                                  ->htmlStarRating($ratingAvg,
                                                   ($this->_isOwner
                                                    ? "average-owner"
                                                    : "average"),
                                                   $ratingTitle,
                                                   true);   // read-only
                }

                if ( $this->_isOwner )
                {
                    // Present the owner's rating
                    $html .= $this
                            ->view
                              ->htmlStarRating($this->_bookmark->rating,
                                               'owner');
                }

                $html .= "</div>";      // stars }
            }

            if ( ($this->_showParts['item:stats:rating:info'] === true) &&
                 ($this->_bookmark->item->ratingCount > 0) )
            {
                $html .= "<div class='info'>"
                      .  sprintf( "<span class='count'>%d</span> raters, "
                                 ."<span class='average'>%3.2f</span> avg.",
                                    $this->_bookmark->item->ratingCount,
                                    $ratingAvg)
                      .  "</div>";
            }

            $html .=  "</div>"; // rating }
        }

        $html .=   "</div>";

        return $html;
    }

    protected function _renderItemName()
    {
        $html = '';
        if ($this->_showParts['item:data:itemName'] !== true)
            return $html;

        $html .= "<h4 class='itemName'>"    // itemName {
              .  sprintf("<a href='%s' title='%s'>%s</a>",
                         $this->_bookmark->item->url,
                         $this->_bookmark->item->urlHash,
                         htmlspecialchars($this->_bookmark->name));

        /*
        if ((! $this->_showParts['minimized'] === true) &&
            ($viewer->isAuthenticated()) )
            $html .= $this->_renderHtmlControl($this->_isOwner);
        */

        $html .= "</h4>";   // itemName }

        return $html;
    }

    protected function _renderItemUrl()
    {
        $html = '';
        if ($this->_showParts['item:data:url'] !== true)
            return $html;

        $html .= "<div class='url'>"
              .   sprintf ("<a href='%s' title='%s'>%s</a>",
                           $this->_bookmark->item->url,
                           $this->_bookmark->item->urlHash,
                           $this->_bookmark->item->url)
              .  "</div>";

        return $html;
    }

    protected function _renderItemDescription()
    {
        $html = '';
        if ( ($this->_showParts['item:data:description'] !== true) ||
             (@empty($this->_bookmark->description)) )
            return $html;

        $html .= "<div class='description'>";   // description {


        if ($this->_showParts['item:data:description:summary'] === true)
        {
            $summary = $this->getSummary($this->_bookmark->description);

            if ($this->_showParts['minimized'] === true)
                $summary = "&mdash; ". $summary;

            $html .= "<div class='summary'>"
                  .   $summary
                  .  "</div>";
        }

        if ($this->_showParts['item:data:description:full'] === true)
        {
            $html .= "<div class='full'>"
                  .   htmlspecialchars($this->_bookmark->description)
                  .  "</div>";
        }

        $html .= "</div>";                      // description }

        return $html;
    }

    protected function _renderItemTags()
    {
        $html = '';
        if ($this->_showParts['item:data:tags'] !== true)
            return $html;

        /*
        if ( ($this->_showParts['minimized']        === true) ||
             ($this->_showParts['item:data:userId'] !== true) )
            $html .= "<br class='clear' />";
        */

        $html .= "<ul class='tags'>";       // tags {

        // Retrieve the tags and sort them in alpha order
        $tags = $this->_bookmark->tags;    //->setOrder('tag');

        foreach ($tags as $tag)
        {
            $html .= "<li class='tag'>"
                  .   "<a href='"
                  .     $this->view->url(array(
                                    'action' => 'index',
                                    'owner'  => $this->_bookmark->user->name,
                                    'tag'    => $tag->tag))
                  .         "'>{$tag->tag}</a>"
                  .  "</li>";
        }

        $html .= "</ul>"                    // tags }
              .  "<br class='clear' />";

        $this->_clearFloats = false;

        return $html;
    }

    protected function _renderItemDates()
    {
        $html = '';
        if ( ! $this->_showParts['item:data:dates'] )
            return $html;

        $html .= "<div class='dates'>";

        if ($this->_showParts['item:data:dates:tagged'] === true)
        {
            $dateTitle = 'date tagged';
            if (($this->_showParts['item:data:dates:updated'] === true) &&
                ($this->_bookmark->updatedOn == $this->_bookmark->taggedOn) )
            {
                $dateTitle .= '/updated';
            }

            $html .= "<div class='tagged' title='{$dateTitle}'>"
                  .    $this->_bookmark->taggedOn
                  .  "</div>";
        }

        if (($this->_showParts['item:data:dates:updated'] === true) &&
            ($this->_bookmark->updatedOn != $this->_bookmark->taggedOn) )
            $html .= "<div class='updated' title='date updated'>"
                  .    $this->_bookmark->updatedOn
                  .  "</div>";

        $html .=  "<br class='clear' />"
              .  "</div>";

        $this->_clearFloats = false;

        return $html;
    }

    protected function _renderUserId()
    {
        $html =  "<div class='userId'>"
              .   sprintf("<a href='%s' title='%s'>",
                          $this->view->url(array(
                                  'action' => $this->_bookmark->user->name)),
                          $this->_bookmark->user->fullName);

        if ($this->_showParts['item:data:userId:avatar'] === true)
        {
            $html .=   "<div class='img icon-highlight'>";

            if ( ! @empty($this->_bookmark->user->pictureUrl))
            {
                // Include the user's picture / avatar
                $html .= sprintf ("<img src='%s' />",
                                  $this->_bookmark->user->pictureUrl);
            }
            else
            {
                // Include the default user icon
                $html .= "<div class='ui-icon ui-icon-person'>"
                      .   "&nbsp;"
                      .  "</div>";
            }

            $html .=   "</div>";
        }

        if ($this->_showParts['item:data:userId:id'] === true)
        {
            $html .= "<span class='name'>{$this->_bookmark->user->name}</span>";
        }

        $html .=  "</a>"
              .  "</div>";

        return $html;
    }

    protected function _renderHtmlControl()
    {
        $html = "<div class='control'>";  //  control {

        if ($this->_isOwner)
        {
            $editUrl = $this->view->url(array('action' => 'post'))
                     .  '?url='
                     .          urlencode($this->_bookmark->item->url);
                    /*
                     .  '?itemId='. $this->_bookmark->itemId;

                     .  '?url='
                     .          urlencode($this->_bookmark->item->url);

                            $this->view->url(array(
                                    'action' => 'itemEdit',
                                    'item'   =>
                                        $this->_bookmark->user->userId
                                        . '.'. $this->_bookmark->itemId)),
                    // */

            $html .= sprintf( "<a class='item-edit'   href='%s' "
                             .                     "target='_blank'>EDIT</a>"
                             .  " | "
                             ."<a class='item-delete' href='%s' "
                              .                    "target='_blank'>DELETE</a>",
                             $editUrl,
                             $this->view->url(array(
                                    'action' => 'itemDelete',
                                    'item'   =>
                                        $this->_bookmark->user->userId
                                        . '.'. $this->_bookmark->itemId)) );
        }
        else
        {
            $saveUrl = $this->view->url(array('action' => 'post'))
                     .  '?name='
                     .          urlencode($this->_bookmark->name)
                     .  '&url='
                     .          urlencode($this->_bookmark->item->url)
                     .  '&description='
                     .          urlencode($this->_bookmark->description)
                     .  '&tags='
                     .          urlencode(''.$this->_bookmark->tags);

            $html .= sprintf ( "<a class='item-save'  href='%s' "
                              .                    "target='_blank'>SAVE</a>",
                              $saveUrl);
        }

        $html .=  "</div>"; //  control }

        return $html;
    }
}
