<?php
/** @file
 *
 *  View helper to generae a Zend_Feed for a set of User Items / Bookmarks.
 *
 */
class View_Helper_FeedBookmarks extends View_Helper_Bookmarks
{
    static public   $defaults           = array(
        'feedType'          => self::TYPE_ATOM,
    );

    const TYPE_RSS          = Zend_Feed_Writer::TYPE_RSS_ANY;
    const TYPE_ATOM         = Zend_Feed_Writer::TYPE_ATOM_ANY;

    /** @brief  Construct a new HTML Bookmarks helper.
     *  @param  config  A configuration array that may include, in addition to
     *                  what our parent accepts:
     *                      - feedType          Desired feed type style
     *                                          [ TYPE_ATOM ];
     */
    public function __construct(array $config = array())
    {
        // Include defaults for any option that isn't directly set
        foreach (self::$defaults as $key => $value)
        {
            if (! isset($config[$key]))
            {
                $config[$key] = $value;
            }
        }

        parent::__construct($config);
    }

    /** @brief  Configure and retrive this helper instance OR, if no
     *          configuration is provided, perform a render.
     *  @param  config  A configuration array (see populate());
     *
     *  @return A (partially) configured instance of $this OR, if no
     *          configuration is provided, the HTML rendering of the configured
     *          bookmarks.
     */
    public function feedBookmarks(array $config = array())
    {
        if (! empty($config))
        {
            return $this->populate($config);
        }

        return $this->render();
    }

    /** @brief  Set the current feed type.
     *  @param  type    A feed type value (self::TYPE_*)
     *                  [ self::TYPE_ATOM ];
     *
     *  @return View_Helper_FeedBookmarks for a fluent interface.
     */
    public function setFeedType($type)
    {
        switch (strtolower($type))
        {
        case self::TYPE_RSS:
            $value = self::TYPE_RSS;
            break;

        case self::TYPE_ATOM:
        default:
            $value = self::TYPE_ATOM;
            break;
        }

        /*
        Connexions::log("View_Helper_FeedBookmarks::setFeedType(): "
                        .   "type[ %s ] == [ %s ]",
                        $type, $value);
        // */

        $this->_params['feedType'] = $value;

        return $this;
    }

    /** @brief  Get the current feed type.
     *
     *  @return The current feed type (self::TYPE_*).
     */
    public function getFeedType()
    {
        return $this->_params['feedType'];
    }

    /** @brief  Generate a Zend_Feed version of a paginated set of User Items.
     *
     *  @return The Zend_Feed representation of the user items.
     */
    public function render()
    {
        $type     = $this->getFeedType();
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
        Connexions::log("View_Helper_FeedBookmarks::render(): "
                        . "type[ {$type} ], "
                        .   "main info[ ". print_r($feedInfo, true) ." ]");
        // */

        foreach ($this->paginator as $item)
        {
            $feedItem = $view->feedBookmark($item);

            /*
            Connexions::log("View_Helper_FeedBookmarks::genFeed(): item[ %s ]",
                            Connexions::varExport($feedItem));
            // */

            array_push($feedInfo['entries'], $feedItem);
        }

        $feed = Zend_Feed::importArray($feedInfo, $type);

        return $feed;
    }
}
