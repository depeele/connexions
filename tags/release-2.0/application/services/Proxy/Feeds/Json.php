<?php
/** @file
 *
 *  A Proxy for the original, V1 connexions JSON Feeds.
 */
class Service_Proxy_Feeds_Json
{
    /** @brief  Retrieve a set of bookmarks.
     *  @param  string user     The target user
     *                          ('*' for all, null indicates the currently
     *                           authenticated user) [ null ];
     *  @param  string tags     A comma or plus-separated list of tags to
     *                          filter by.
     *  @param  string sort     A sorting order string
     *                          (taggers, popular, votes, topRated, ratings,
     *                           oldest, byDate, [recent] )
     *
     *  @param  string callback A JSONP callback.  The returned JSON will be
     *                          wrapped in a Javascript function call using
     *                          this name;
     *  @param  string count    The maximum number of items to return [ 100 ];
     *
     *  @return array of bookmarks
     */
    public function posts($user     = null,
                          $tags     = null,
                          $sort     = 'recent',
                          $callback = null,
                          $count    = 100)
    {
        $bService = $this->_service('Bookmark');
        $uService = $this->_service('User');
        $tService = $this->_service('Tag');

        switch (strtolower($sort))
        {
        case 'taggers':
        case 'popular':
            $order = 'userCount DESC, '
                   . 'updatedOn DESC, taggedOn DESC, name ASC';
            break;

        case 'toprated':
            $order = 'ratingAvg DESC, ratingCount DESC, '
                   . 'updatedOn DESC, taggedOn DESC, name ASC';
            break;

        case 'ratings':
        case 'votes':
        case 'voters':
            $order = 'ratingCount DESC, ratingAvg DESC, '
                   . 'updatedOn DESC, taggedOn DESC, name ASC';
            break;

        case 'oldest':
        case 'bydate':
            $order = 'updatedOn ASC, taggedOn ASC, name ASC';
            break;

        case 'recent':
        default:
            $order = 'updatedOn DESC, taggedOn DESC, name ASC';
        }

        $userSet = null;
        if ($user !== '*')
        {
            if (empty($user))
            {
                // Use the currently authenticated user
                $user = Connexions::getUser();
            }

            $userSet = $uService->csList2set( $user );
        }

        if (! empty($tags))
        {
            // Convert a '+'-separated list to a ','-separated list
            $tags = preg_replace('/\s*\+\s*/', ',', $tags);
        }
        $tagSet = $tService->csList2set($tags);

        Connexions::log("Service_Proxy_Feeds_Json::posts(): "
                        .   "user[ %s ], userSet[ %s ], "
                        .   "tags[ %s ], tagSet[ %s ], "
                        .   "sort[ %s == %s ], "
                        .   "count[ %s ]",
                        Connexions::varExport($user),
                        Connexions::varExport($userSet),
                        Connexions::varExport($tags),
                        Connexions::varExport($tagSet),
                        $sort, $order,
                        $count);

        $bookmarks  = $bService->fetchByUsersAndTags($userSet, $tagSet,
                                                     true,      // exactUsers
                                                     true,      // exactTags
                                                     $order,    // order
                                                     $count);

        return $bookmarks;
    }

    /** @brief  Retrieve a set of tags.
     *  @param  string user     The target user
     *                          ('*' for all, null indicates the currently
     *                           authenticated user) [ null ];
     *  @param  string sort     A sorting order string
     *                          ( [alpha], count);
     *  @param  int    atleast  Include only tags that have been used for at
     *                          least this many bookmarks [ 0 ];
     *
     *  @param  string callback A JSONP callback.  The returned JSON will be
     *                          wrapped in a Javascript function call using
     *                          this name;
     *  @param  string count    The maximum number of items to return [ 100 ];
     *
     *  @return array of bookmarks
     */
    public function tags($user     = null,
                         $sort     = 'alpha',
                         $atleast  = 0,
                         $callback = null,
                         $count    = 100)
    {
        $uService = $this->_service('User');
        $tService = $this->_service('Tag');

        switch (strtolower($sort))
        {
        case 'count':
            $order = 'userItemCount DESC, tag ASC';
            break;

        case 'alpha':
        default:
            $order = 'tag ASC, userItemCount DESC';
            break;
        }

        $userSet = null;
        if ($user !== '*')
        {
            if (empty($user))
            {
                // Use the currently authenticated user
                $user = Connexions::getUser();
            }

            $userSet = $uService->csList2set( $user );
        }

        $where = ($atleast < 1
                    ? null
                    : array('userItemCount >=' => $atleast) );

        Connexions::log("Service_Proxy_Feeds_Json::tags(): "
                        .   "user[ %s ], userSet[ %s ], "
                        .   "sort[ %s == %s ], "
                        .   "count[ %s ], where[ %s ]",
                        Connexions::varExport($user),
                        Connexions::varExport($userSet),
                        $sort, $order,
                        $count,
                        Connexions::varExport($where));

        $tags = $tService->fetchByUsers($userSet,
                                        $order,
                                        $count,
                                        null,       // offset
                                        false,      // exact
                                        $where);

        return $tags;
    }

    /** @brief  Retrieve a set of people.
     *  @param  string user     The target user
     *                          ('*' for all, null indicates the currently
     *                           authenticated user) [ null ];
     *  @param  string sort     A sorting order string
     *                          (id, name, email, lastVisit, tagCount,
     *                           [itemCount] )
     *
     *  @param  string callback A JSONP callback.  The returned JSON will be
     *                          wrapped in a Javascript function call using
     *                          this name;
     *  @param  string count    The maximum number of items to return [ 100 ];
     *
     *  @return array of bookmarks
     */
    public function people($user     = null,
                           $sort     = 'itemCount',
                           $callback = null,
                           $count    = 100)
    {
        $uService = $this->_service('User');
        $tService = $this->_service('Tag');

        switch (strtolower($sort))
        {
        case 'id':
            $order = 'name ASC';
            break;

        case 'name':
            $order = 'fullName ASC';
            break;

        case 'email':
            $order = 'email ASC';
            break;

        case 'lastvisit':
            $order = 'lastVisit DESC, name ASC';
            break;

        case 'tagcount':
            $order = 'totalTags DESC, name ASC';
            break;

        case 'itemcount':
        default:
            $order = 'totalItems DESC, name ASC';
        }

        $userSet = null;
        if ($user !== '*')
        {
            if (empty($user))
            {
                // Use the currently authenticated user
                $user = Connexions::getUser();
            }

            $userSet = $uService->csList2set( $user );
        }
        else
        {
            $userSet = $uService->fetch(null,
                                        $order,
                                        $count);
        }

        Connexions::log("Service_Proxy_Feeds_Json::people(): "
                        .   "user[ %s ], userSet[ %s ], "
                        .   "sort[ %s == %s ], "
                        .   "count[ %s ]",
                        Connexions::varExport($user),
                        Connexions::varExport($userSet),
                        $sort, $order,
                        $count);

        return $userSet;
    }

    /** @brief  Retrieve a set of urls with associated tags.
     *  @param  string url      The target url;
     *  @param  string sort     A sorting order string
     *                          ( [alpha], count);
     *
     *  @param  string callback A JSONP callback.  The returned JSON will be
     *                          wrapped in a Javascript function call using
     *                          this name;
     *  @param  string count    The maximum number of items to return [ 100 ];
     *
     *  @return array of bookmarks
     */
    public function url($url,
                        $sort     = 'alpha',
                        $callback = null,
                        $count    = 100)
    {
        $iService = $this->_service('Item');

        switch (strtolower($sort))
        {
        case 'count':
            $order = 'userItemCount DESC, url ASC';
            break;

        case 'alpha':
        default:
            $order = 'url ASC, userItemCount DESC';
            break;
        }

        /*
        Connexions::log("Service_Proxy_Feeds_Json::url(): "
                        .   "url[ %s ], sort[ %s == %s ], "
                        .   "count[ %s ]",
                        $url,
                        $sort, $order,
                        $count);
        // */

        $items  = $iService->fetchSimilar($url, $order, $count,
                                          null,     // offset
                                          true);    // inclusive

        /* This legacy API is supposed to return an object of the form:
         *  { %url%: { %tag%: %count%, ... },
         *    ...
         *  }
         */
        $res = array();
        foreach ($items as $item)
        {
            $tags  = $item->tags;
            $tagAr = array();
            foreach ($tags as $tag)
            {
                $tagAr[ $tag->tag ] = $tag->userCount;
            }

            $res[ $item->url ] = $tagAr;
        }

        /*
        Connexions::log("Service_Proxy_Feeds_Json::url(): "
                        .   "items[ %s ]",
                        Connexions::varExport($items));
        // */

        return $res;
    }

    /**************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Retrieve a Connexions_Service instance.
     *  @param  name    The name of the desired service.
     *
     *  @return The Connexions_Service instance (null on failure).
     */
    protected function _service($name)
    {
        if (strpos($name, 'Service_') === false)
            $name = 'Service_'. ucfirst($name);

        return Connexions_Service::factory($name);
    }
}
