<?php
/** @file
 *
 *  View helper to generate a Zend_Feed-compatible version of a single User.
 *
 */
class View_Helper_FeedUser extends Zend_View_Helper_Abstract
{
    /** @brief  Generate an Zend_Feed-compatible version of a single User.
     *  @param  user    The Model_User instance to present.
     *
     *  @return The Zend_Feed-compatible associative array of Builder Entry
     *          information.
     */
    public function feedUser(Model_User $user)
    {
        $updated    = $this->_date2time($user->lastVisit);
        $authorLink = sprintf("<a href='%s'>%s</a>",
                              $this->_url(array('controller' => 'index',
                                                'action'     => $user->name)),
                              $user->fullName);
        $localUrl  = $this->_url(array('controller' => 'index',
                                       'action'     => $user->name));

        /* Since DOMDocument::createElement seems to insist on encoding '&',
         * and doing so incorrectly, handle it here.
         */
        $itemUrl = htmlspecialchars($localUrl);
        //$title   = htmlentities( stripslashes($user->name));
        $title   = stripslashes($user->name);
        $title   = html_entity_decode( $title, ENT_COMPAT, 'UTF-8');
        $title   = htmlspecialchars( $title );

        $descr   = stripslashes($user->profile);
        $descr   = html_entity_decode( $descr, ENT_COMPAT, 'UTF-8');

        /*
        Connexions::log("View_Helper_FeedUser:feedUser: "
                        . "url[ {$user->item->url} ], [ {$localUrl} ]");
        Connexions::log("View_Helper_FeedUser:feedUser: "
                        . "title[ {$title} ]");
        // */

        $entryInfo = array(
            'title'         => $title,
            'link'          => $itemUrl,
            'description'   => $descr,

            'author'        => $user->fullName,
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
                'name'  => $user->fullName,
                'email' => $user->email,
            ),
        );

        // Append additional information to the end of 'content'
        if (! empty($user->pictureUrl))
        {
            /* COULD put this in an enclosure:
             *  $entryInfo['enclosure'] = array(
             *      'url'       => $user->pictureUrl,
             *      'type'      => mime type...
             *      'length'    => length of linked content in octets
             *  );
             */
            $entryInfo['content'] = "<img src='{$user->pictureUrl}' />"
                                  . $entryInfo['content'];
        }

        /*
        // Add each tag as a category
        foreach ($user->tags as $tag)
        {
            array_push($entryInfo['category'], array('term' => $tag->tag));
        }
        */

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
