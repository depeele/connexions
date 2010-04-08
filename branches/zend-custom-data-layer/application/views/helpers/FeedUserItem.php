<?php
/** @file
 *
 *  View helper to generate a Zend_Feed-compatible version of a single User
 *  Item / Bookmark.
 *
 */
class Connexions_View_Helper_FeedUserItem
                                extends Connexions_View_Helper_UserItem
{
    /** @brief  Generate an Zend_Feed-compatible version of a single
     *          User Item / Bookmark.
     *  @param  userItem    The Model_UserItem instance to present.
     *
     *  @return The Zend_Feed-compatible associative array of Builder Entry
     *          information.
     */
    public function feedUserItem(Model_UserItem $userItem)
    {
        $updated    = $this->_date2time(empty($userItem->updatedOn)
                                            ? $userItem->taggedOn
                                            : $userItem->updatedOn);
        $authorLink = sprintf("<a href='%s'>%s</a>",
                              $this->_url(array('action' =>
                                                      $userItem->user->name)),
                              $userItem->user->fullName);
        $localUrl  = $this->_url(array('action'  => 'url',
                                       'urlHash' => $userItem->item->urlHash));

        /* Since DOMDocument::createElement seems to insist on encoding '&',
         * and doing so incorrectly, handle it here.
         */
        $itemUrl = htmlspecialchars(urldecode($userItem->item->url));
        $title   = htmlentities( stripslashes($userItem->name));

        /*
        Connexions::log("Connexions_View_Helper_FeedUserItem:feedUserItem: "
                        . "url[ {$userItem->item->url} ], [ {$localUrl} ]");
        Connexions::log("Connexions_View_Helper_FeedUserItem:feedUserItem: "
                        . "title[ {$title} ]");
        // */

        $entryInfo = array(
            'title'         => $title,
            'link'          => $itemUrl,
            'description'   => $this->getSummary($userItem->description),

            'author'        => $userItem->user->fullName,
            'guid'          => $localUrl,
            'content'       => $userItem->description,
            'lastUpdate'    => $updated,
            'pubDate'       => $updated,

            'source'        => array(
                'title' => $title,
                'url'   => $localUrl,
            ),

            'category'      => array(),

            // itunes
            'owner'         => array(
                'name'  => $userItem->user->fullName,
                'email' => $userItem->user->email,
            ),
        );

        // Append additional information to the end of 'content'
        $entryInfo['content'] .= '<br />'
                              .  "Owner: {$authorEl}<br />"
                              .  ($userItem->isPrivate  ? '' : 'Not ')
                              .     'Private, '
                              .  ($userItem->isFavorite ? '' : 'Not ')
                              .     'Favorite, '
                              .  ($userItem->rating > 0
                                      ? $userItem->rating
                                      : "No")
                              .     ' Rating<br />'
                              .  'Originally tagged on: '
                              .     $userItem->taggedOn;

        // Add each tag as a category
        foreach ($userItem->tags as $tag)
        {
            array_push($entryInfo['category'], array('term' => $tag->tag));
        }

        return $entryInfo;
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    protected function _date2time($str)
    {
        $date = new Zend_Date($str);

        return $date->getTimestamp();
    }

    protected function _url($config)
    {
        return $this->view->serverUrl($this->view->url( $config ));
    }
}
