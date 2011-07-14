<?php
/** @file
 *
 *  A Proxy for the original, V1 connexions JSON Feeds.
 */
class Service_Proxy_Feeds_Json
{
    /** @brief  Retrieve a set of items for the current user.
     *  @param  string user     The target user
     *                          ('*' for all, null indicates the currently
     *                           authenticated user) [ null ];
     *  @param  string tags     A comma or plus-separated list of tags to
     *                          filter by.
     *  @param  string callback A JSONP callback.  The returned JSON will be
     *                          wrapped in a Javascript function call using
     *                          this name;
     *  @param  string count    The maximum number of items to return [ 100 ];
     *
     *  @return array of bookmarks
     */
    public function posts($user     = null,
                          $tags     = null,
                          $callback = null,
                          $count    = 100)
    {
        $bService = $this->_service('Bookmark');
        $uService = $this->_service('User');
        $tService = $this->_service('Tag');

        $userSet = null;
        if ($user !== '*')
        {
            // Use the currently authenticated user
            $userSet = $uService->makeEmptySet();

            // Resolve the incoming 'owner' name.
            if (empty($user))
            {
                // Use the currently authenticated user.
                $owner = Connexions::getUser();
            }
            else
            {
                // Resolve the given user.
                $owner = $this->_resolveUserName($user);
                if ( (! $owner) || (! $owner->isBacked()) )
                {
                    throw new Exception("Unknown user [ {$user} ]");
                    return;
                }
            }

            $userSet->setResults(array( $owner ));
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
                        .   "count[ %s ]",
                        Connexions::varExport($user),
                        Connexions::varExport($userSet),
                        Connexions::varExport($tags),
                        Connexions::varExport($tagSet),
                        $count);

        $bookmarks  = $bService->fetchByUsersAndTags($userSet, $tagSet,
                                                     true,      // exactUsers
                                                     true,      // exactTags
                                                     null,      // order
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

    /** @brief  Given a string that is supposed to represent a user, see if it
     *          represents a valid user.
     *  @param  name    The user name.
     *
     *  @return A Model_User instance matching 'name', null if no match.
     */
    protected function _resolveUserName($name)
    {
        $res = null;

        if ((! @empty($name)) && ($name !== '*'))
        {
            /* Retieve a model representing the target user
             * (MAY be unbacked).
             */
            $userInst = $this->service('User')
                                ->get(array('name' => $name));

            // Have we located a user?
            if ($userInst !== null)
            {
                // YES -- we have a user model instance, possibly unbacked.
                $res = $userInst;
            }
        }

        return $res;
    }

    /** @brief  Retrieve the currently authenticated user and validate the
     *          provided API key.
     *  @param  apikey  The API key that should be associated with the
     *                  currently authenticated user.
     *
     *  Override Connexions_Service_Proty::_authenticate() to REQUIRE an ApiKey
     *  regardless of request method.
     *
     *  @throw  Exception('Invalid apikey')
     *
     *  @return The currently authenticated user.
     */
    protected function _authenticate($apikey)
    {
        $user = Connexions::getUser();
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }

        if ($user->apiKey !== $apikey)
        {
            throw new Exception('Invalid apikey.');
        }

        return $user;
    }
}


