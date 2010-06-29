<?php
/** @file
 *
 *  View helper to generate a Zend_Feed-compatible version of a single User
 *  Item / Bookmark.
 *
 */
class View_Helper_FeedBookmark extends View_Helper_Bookmark
{
    /** @brief  Generate an Zend_Feed-compatible version of a single
     *          User Item / Bookmark.
     *  @param  bookmark    The Model_Bookmark instance to present.
     *
     *  @return The Zend_Feed-compatible associative array of Builder Entry
     *          information.
     */
    public function feedBookmark(Model_Bookmark $bookmark)
    {
        $updated    = $this->_date2time(empty($bookmark->updatedOn)
                                            ? $bookmark->taggedOn
                                            : $bookmark->updatedOn);
        $authorLink = sprintf("<a href='%s'>%s</a>",
                              $this->_url(array('action' =>
                                                      $bookmark->user->name)),
                              $bookmark->user->fullName);
        $localUrl  = $this->_url(array('action'  => 'url',
                                       'urlHash' => $bookmark->item->urlHash));

        /* Since DOMDocument::createElement seems to insist on encoding '&',
         * and doing so incorrectly, handle it here.
         */
        $itemUrl = htmlspecialchars(urldecode($bookmark->item->url));
        //$title   = htmlentities( stripslashes($bookmark->name));
        $title   = stripslashes($bookmark->name);
        $title   = html_entity_decode( $title, ENT_COMPAT, 'UTF-8');
        $title   = htmlspecialchars( $title );

        $descr   = stripslashes($bookmark->description);
        $descr   = html_entity_decode( $descr, ENT_COMPAT, 'UTF-8');

        /*
        Connexions::log("View_Helper_FeedBookmark:feedBookmark: "
                        . "url[ {$bookmark->item->url} ], [ {$localUrl} ]");
        Connexions::log("View_Helper_FeedBookmark:feedBookmark: "
                        . "title[ {$title} ]");
        // */

        $entryInfo = array(
            'title'         => $title,
            'link'          => $itemUrl,
            'description'   => $this->getSummary( $descr ),

            'author'        => $bookmark->user->fullName,
            'guid'          => $localUrl,
            'content'       => $descr,
            'lastUpdate'    => $updated,
            'pubDate'       => $updated,

            'source'        => array(
                'title' => $title,
                'url'   => $localUrl,
            ),

            'category'      => array(),

            // itunes
            'owner'         => array(
                'name'  => $bookmark->user->fullName,
                'email' => $bookmark->user->email,
            ),
        );

        // Append additional information to the end of 'content'
        $entryInfo['content'] .= '<br />'
                              .  "Owner: {$authorEl}<br />"
                              .  ($bookmark->isPrivate  ? '' : 'Not ')
                              .     'Private, '
                              .  ($bookmark->isFavorite ? '' : 'Not ')
                              .     'Favorite, '
                              .  ($bookmark->rating > 0
                                      ? $bookmark->rating
                                      : "No")
                              .     ' Rating<br />'
                              .  'Originally tagged on: '
                              .     $bookmark->taggedOn;

        // Add each tag as a category
        foreach ($bookmark->tags as $tag)
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
