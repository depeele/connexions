<?php
/** @file
 *
 *  A Proxy for the original, V1 connexions RSS Feeds.
 */
class Service_Proxy_Feeds_Feed
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
     *  @param  string count    The maximum number of items to return [ 100 ];
     *
     *  @return array of bookmarks
     */
    public function posts($user     = null,
                          $tags     = null,
                          $sort     = 'recent',
                          $count    = 100)
    {
        if ($count === null)    { $count = 100; }

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

        Connexions::log("Service_Proxy_Feeds_Feed::posts(): "
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
