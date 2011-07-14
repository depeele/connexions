<?php
/** @file
 *
 *  A Proxy for the original, V1 connexions Atom Feeds.
 */
class Service_Proxy_Feeds_Atom
{
    /** @brief  Retrieve a set of items for the current user.
     *  @param  string user     The target user
     *                          ('*' for all, null indicates the currently
     *                           authenticated user) [ null ];
     *  @param  string tags     A comma or plus-separated list of tags to
     *                          filter by.
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
            if ($user === null)
            {
                // Use the currently authenticated user.
                $owner = Connexions::getUser();
            }
            else
            {
                // Resolve the given user.
                $owner = $this->_resolveUserName($user);

                if ( ! $owner->isBacked() )
                {
                    $this->view->error = "Unknown user [ {$user} ]";
                    return;
                }
            }

            $userSet->setResults(array( $owner ));
        }

        if (! empty($tags))
        {
            // Convert a '+'-separated list to a ','-separated list
            $tags = preg_replace('/\s*+\s*/', ',', $tags);
        }
        $tagSet = $tService->csList2set($tags);

        $bookmarks  = $service->fetchByUsersAndTags($userSet, $tagSet,
                                                    true,     // exactUsers
                                                    true);    // exactTags

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


