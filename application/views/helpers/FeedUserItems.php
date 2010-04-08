<?php
/** @file
 *
 *  View helper to generae a Zend_Feed for a set of User Items / Bookmarks.
 *
 */
class Connexions_View_Helper_FeedUserItems
                                extends Zend_View_Helper_Abstract
{
    /** @brief  Generate a Zend_Feed version of a paginated set of User Items
     *          or, if no arguments, return this helper instance.
     *  @param  paginator   The Zend_Paginator representing the items to be
     *                      presented.
     *  @param  type        The type of feed to generate
     *                      ('Atom', 'Rss', 'Pubsubhubbub')
     *
     *  @return The Zend_Feed representation of the user items, or $this.
     */
    public function feedUserItems(Zend_Paginator    $paginator  = null,
                                                    $type       = 'Atom')
    {
        /*
        Connexions::log("Connexions_View_Helper_FeedUserItems::feedUserItems: "
                        . "type[ {$type} ]");
        // */

        if ($paginator === null)
            return $this;

        return $this->render($paginator, $type);
    }

    /** @brief  Generate a Zend_Feed version of a paginated set of User Items.
     *  @param  paginator   The Zend_Paginator representing the items to be
     *                      presented.
     *  @param  type        The type of feed to generate
     *                      ('Atom', 'Rss', 'Pubsubhubbub')
     *
     *  @return The Zend_Feed representation of the user items.
     */
    public function render(Zend_Paginator   $paginator,
                                            $type)
    {
        $view     = $this->view;
        $title    = htmlspecialchars_decode(strip_tags($view->headTitle()));

        $feedInfo = array(
            'title'         => $title,
            'link'          => $view->serverUrl($view->url()),
            'charset'       => 'utf-8',
            'entries'       => array(),

            // Optional
            'description'   => $title .": {$type} feed",
            'image'         => $view->serverUrl(
                                        $view->baseUrl('images/logo.gif')),
            'ttl'           => 5,   // minutes (ignored for Atom)

            'lastUpdate'    => time(),
            'published'     => time(),
        );

        /*
        Connexions::log("Connexions_View_Helper_FeedUserItems::_genFeed: "
                        . "type[ {$type} ], "
                        .   "main info[ ". print_r($feedInfo, true) ." ]");
        // */

        foreach ($paginator as $item)
        {
            array_push($feedInfo['entries'],
                       $view->feedUserItem($item));
        }

        $feed = Zend_Feed::importArray($feedInfo, $type);

        return $feed;
    }
}
