<?php
/** @file
 *
 *  A Proxy for the original, V1 connexions API.
 */
class Service_Proxy_ApiV1
{
    /** @brief  Retrieve a set of items for the current user.
     *  @param  string apikey   Your unique api key.
     *  @param  string tags     A comma-separated list of tags to filter by.
     *
     *  @return array of bookmarks
     */
    public function posts_get($apikey,
                              $tags = null)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('Bookmark');

        $bookmarks  = $service->fetchByUsersAndTags($user, $tags,
                                                    true,     // exactUsers
                                                    true);    // exactTags

        return $bookmarks;
    }

    /** @brief  Add a new item or update an existing bookmark.
     *  @param  string apikey       Your unique api key.
     *  @param  string url          The url of the bookmark.
     *  @param  string name         The user selected name for this bookmark.
     *  @param  string tags         A comma-separated list of tags.
     *  @param  string description  The user's description for this bookmark.
     *  @param  bool   is_private   Is this item 'private' [ false ].
     *  @param  bool   is_favorite  Is this item 'private' [ false ].
     *  @param  int    rating       The user rating (0-5)  [ 0 ].
     *  @param  bool   update       Update the item if it exists [ true ].
     *
     *  @return array representing the (updated) bookmark
     */
    public function posts_add($apikey,
                              $url,
                              $name,
                              $tags,
                              $description  = '',
                              $is_private   = false,
                              $is_favorite  = false,
                              $rating       = -1,
                              $update       = true)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('Bookmark');

        $id         = array('user'  => $user,
                            'url'   => $url);

        /*
        if ($update !== true)
        {
            // FIRST perform a lookup to see if the bookmark already exists.
            // If it does, do nothing.
            $bookmark   = $service->get($id);

            printf ("posts_add: bookmark[ %s ]<br />\n",
                    $bookmark->debugDump());

            if ($bookmark->isBacked())
            {
                return;
            }
        }
        // */

        // Simply call update which will either create OR update
        $bookmark = $service->update($id,
                                     $name,
                                     $description,
                                     $rating,
                                     $is_favorite,
                                     $is_private,
                                     false, // V1 doesn't do worldModify
                                     $tags,
                                     $url);

        return $bookmark;
    }

    /** @brief  Update an existing bookmark.
     *  @param  string apikey       Your unique api key.
     *  @param  string url          The url of the bookmark.
     *  @param  string name         The user selected name for this bookmark.
     *  @param  string tags         A comma-separated list of tags.
     *  @param  string description  The user's description for this bookmark.
     *  @param  bool   is_private   Is this item 'private' [ false ].
     *  @param  bool   is_favorite  Is this item 'private' [ false ].
     *  @param  int    rating       The user rating (0-5)  [ 0 ].
     *
     *  @return array representing the (updated) bookmark
     */
    public function posts_update($apikey,
                                 $url,
                                 $name          = null,
                                 $tags          = null,
                                 $description   = '',
                                 $is_private    = false,
                                 $is_favorite   = false,
                                 $rating        = -1)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('Bookmark');

        $id         = array('userId' => $user->userId,
                            'itemId' => $url);

        // Simply call update which will either create OR update
        $bookmark = $service->update($id,
                                     $name,
                                     $description,
                                     $rating,
                                     $is_favorite,
                                     $is_private,
                                     false, // V1 doesn't do worldModify
                                     $tags,
                                     $url);

        return $bookmark;
    }

    /** @brief  Delete an existing bookmark.
     *  @param  string apikey   Your unique api key.
     *  @param  string url      The url of the bookmark to delete.
     *
     *  @return null
     */
    public function posts_delete($apikey, $url)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('Bookmark');

        $id         = array('userId' => $user->userId,
                            'itemId' => $url);

        // Simply call update which will either create OR update
        $service->delete($id);
    }

    /** @brief  Retrieve the list of tags and counts for the current user.
     *  @param  string apikey   Your unique api key.
     *
     *  @return array of tags
     */
    public function tags_get($apikey)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('Tag');

        $tags       = $service->fetchByUsers($user);

        return $tags;
    }

    /** @brief  Delete the given tag(s) for all bookmarks of the current user.
     *  @param  string apikey   Your unique api key.
     *  @param  string tags     A comma-separated list of tags.
     *
     *  @return array of items of the form:
     *              '%tag%': status
     */
    public function tags_delete($apikey, $tags)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('User');

        $res        = $service->deleteTags($user, $tags);

        return $res;
    }

    /** @brief  Rename the given tag(s).
     *  @param  string apikey   Your unique api key.
     *  @param  string old      A colon-separated list of tags.
     *  @param  string new      A parallel colon-separated list of new tags.
     *
     *  @return array of items of the form:
     *              'old':      %old%,
     *              'new':      %new%,
     *              'items':    count of successfully changed item
     *              'renames':  An associative array of old-name and a
     *                          status string indicating the rename results
     */
    public function tags_rename($apikey, $old, $new)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('User');

        $tagsOld    = preg_split('/\s*[:,]\s*/', $old);
        $tagsNew    = preg_split('/\s*[:,]\s*/', $new);

        if (count($tagsOld) !== count($tagsNew))
        {
            throw new Exception("Mismatch old/new tag counts");
        }

        $renames    = array_combine($tagsOld, $tagsNew);
        $res        = $service->renameTags($user, $renames);

        $res2 = array(
            'old'       => $old,
            'new'       => $new,
            'items'     => count($res),
            'renames'   => $res,
        );

        return $res2;
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

