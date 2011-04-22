<?php
/** @file
 *
 *  A Proxy for Service_User that exposes only publicly callable methods.
 */
class Service_Proxy_User extends Connexions_Service_Proxy
{
    /** @brief  Given a user identifier and/or credential, attempt to
     *          authenticate the identified user.
     *  @param  authType    The type of authentication to perform
     *                      (Model_UserAuth::AUTH_*)
     *  @param  credential  Any initial user credential
     *                      (e.g. OpenId endpoint).
     *
     *  @return A Model_User instance with isAuthenticated() set accordingly.
     */
    public function authenticate($authType   = Model_UserAuth::AUTH_PASSWORD,
                                 $credential = null)
    {
        return $this->_service->authenticate($authType, $credential);
    }

    /** @brief  Retrieve a set of users related by a set of Tags.
     *  @param  tags    A comma-separated list of tags to match;
     *  @param  exact   Users MUST be associated with provided tags [ true ];
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'tagCount DESC' ];
     *  @param  count   Optional LIMIT count;
     *  @param  offset  Optional LIMIT offset;
     *
     *  @return A new Model_Set_User instance.
     */
    public function fetchByTags($tags,
                                $exact   = true,
                                $order   = null,
                                $count   = null,
                                $offset  = null)
    {
        return $this->_service->fetchByTags($tags,
                                            $exact,
                                            $order,
                                            $count,
                                            $offset);
    }

    /** @brief  Given a comma-separated list of tag rename information, rename
     *          tags for the currently authenticated user.
     *  @param  renames     A comma-separated list of tag rename information,
     *                      echo item of the form:
     *                          'oldTagName::newTagName'
     *  @param  apiKey      The apiKey for the currently authenticated user
     *                      (REQUIRED if the transport method is NOT POST);
     *
     *  @return An array of status information, keyed by old tag name:
     *              { 'oldTagName'  => true (success) |
     *                                 String explanation of failure,
     *                 ... }
     */
    public function renameTags($renames, $apiKey = null)
    {
        $user = $this->_authenticate($apiKey);

        return $this->_service->renameTags($user, $renames);
    }

    /** @brief  Given a comma-separated list of tag names, delete all tags for
     *          the currently authenticated user.  If deleting a tag will
     *          result in an "orphaned bookmark" (i.e. a bookmark with no
     *          tags), the delete of that tag will fail.
     *  @param  tags        A comma-separated list of tags.
     *  @param  apiKey      The apiKey for the currently authenticated user
     *                      (REQUIRED if the transport method is NOT POST);
     *
     *  @return An array of status information, keyed by tag name:
     *              { 'tagName' => true (success) |
     *                             String explanation of failure,
     *                 ... }
     */
    public function deleteTags($tags, $apiKey = null)
    {
        $user = $this->_authenticate($apiKey);

        return $this->_service->deleteTags($user, $tags);
    }

    /** @brief  Add a new user to the network of this user.
     *  @param  users       A Model_Set_User instance, simple array of user
     *                      identifiers, or comma-separated list of user
     *                      identifiers.
     *  @param  apiKey      The apiKey for the currently authenticated user
     *                      (REQUIRED if the transport method is NOT POST);
     *
     *  @return An array of status information, keyed by user name:
     *              { '%userName%'  => true (success) |
     *                                 String explanation of failure,
     *                 ... }
     */
    public function addToNetwork($users, $apiKey = null)
    {
        $user = $this->_authenticate($apiKey);

        return $this->_service->addToNetwork($user, $users);
    }

    /** @brief  Remove one or more users from the network of the identified
     *          user.
     *  @param  users       A Model_Set_User instance, simple array of user
     *                      identifiers, or comma-separated list of user
     *                      identifiers.
     *  @param  apiKey      The apiKey for the currently authenticated user
     *                      (REQUIRED if the transport method is NOT POST);
     *
     *  @return An array of status information, keyed by user name:
     *              { '%userName%'  => true (success) |
     *                                 String explanation of failure,
     *                 ... }
     */
    public function removeFromNetwork($users, $apiKey = null)
    {
        $user = $this->_authenticate($apiKey);

        return $this->_service->removeFromNetwork($user, $users);
    }

    /** @brief  Perform user autocompletion.
     *  @param  term        The string to autocomplete.
     *  @param  limit       The maximum number of tags to return [ 15 ];
     *
     *  @return Model_Set_User
     */
    public function autocomplete($term,
                                 $limit = 15)
    {
        return $this->_service->autocomplete($term, $limit);
    }

    /** @brief  Perform tag autocompletion, possibly based upon a set of
     *          seleted tags.
     *  @param  term        The string to autocomplete.
     *  @param  tags        A Model_Set_Tag instance, array, or comma-separated
     *                      string of tags that restrict the bookmarks that
     *                      should be used to select related tags -- defines
     *                      the 'context';
     *  @param  limit       The maximum number of tags to return [ 15 ];
     *
     *  @return Model_Set_Tag
     */
    public function autocompleteTag($term       = null,
                                    $tags       = null,
                                    $limit      = 15)
    {
        return $this->_service->autocompleteTag($term, $tags, $limit);
    }

    /** @brief  Perform tag autocompletion for the (authenticated) user.
     *  @param  term        The string to autocomplete.
     *  @param  limit       The maximum number of tags to return [ 15 ];
     *  @param  apiKey      The apiKey for the currently authenticated user
     *                      (REQUIRED if the transport method is NOT POST);
     *
     *  @return Model_Set_Tag
     */
    public function autocompleteMyTags($term    = null,
                                       $limit   = 15,
                                       $apiKey  = null)
    {
        $user = $this->_authenticate($apiKey);

        return $this->_service->autocompleteTag($term, $user, $limit);
    }

    /** @brief  Update the currently authenticated user.
     *  @param  fullName    The new 'fullName'   (null for no change);
     *  @param  email       The new 'email'      (null for no change);
     *  @param  pictureUrl  The new 'pictureUrl' (null for no change);
     *  @param  profileUrl  The new 'profile'    (null for no change);
     *  @param  apiKey      The apiKey for the currently authenticated user
     *                      (REQUIRED if the transport method is NOT POST);
     *
     *  @return The updated user.
     */
    public function update($fullName    = null,
                           $email       = null,
                           $pictureUrl  = null,
                           $profileUrl  = null,
                           $apiKey      = null)
    {
        $user = $this->_authenticate($apiKey);

        return $this->_service->update($user, $fullName, $email,
                                       $pictureUrl, $profileUrl);
    }

    /** @brief  Regenerate the API Key for the currently authenticated user.
     *  @param  apiKey      The apiKey for the currently authenticated user
     *                      (REQUIRED if the transport method is NOT POST);
     *
     *  @return The new Api Key (false on error).
     */
    public function regenerateApiKey($apiKey    = null)
    {
        $user   = $this->_authenticate($apiKey);
        $apiKey = $user->apiKey;

        $user   = $this->_service->regenerateApiKey($user);
        return ($user->apiKey != $apiKey
                    ? $user->apiKey
                    : false);
    }

    /** @brief  Update the user's credentials
     *  @param  credentials An array of credentials of the form:
     *                          { userAuthId:   (if updating an existing
     *                                           credential),
     *                            authType:     ( Model_UserAuth::AUTH_OPENID |
     *                                            Model_UserAuth::AUTH_PKI    |
     *                                            Model_UserAuth::AUTH_PASSWORD
     *                                          )
     *                            name:         The name of the credential,
     *                            credential:   The credential data }
     *  @param  apiKey      The apiKey for the currently authenticated user
     *                      (REQUIRED if the transport method is NOT POST);
     *
     *  @return The updated user.
     */
    public function updateCredentials($credentials, $apiKey = null)
    {
        $user = $this->_authenticate($apiKey);

        return ($this->_service->updateCredentials($user, $credentials));
    }

    /** @brief  Delete a specific credential for the given user.
     *  @param  credential  The credential identifier.
     *  @param  apiKey      The apiKey for the currently authenticated user
     *                      (REQUIRED if the transport method is NOT POST);
     *
     *  @return The updated user.
     */
    public function deleteCredential($credential, $apiKey = null)
    {
        $user = $this->_authenticate($apiKey);

        return ($this->_service->deleteCredential($user, $credential));
    }

    /** @brief  Given a *local* URL to an avatar image along with cropping
     *          information, perform the image manipulation to accomplish the
     *          crop and move the resulting image to the avatar directory with
     *          a name based upon the authenticated user.
     *
     *  @param  url     The URL of the source avatar image
     *                  (it is expected to be a URL that is local to this
     *                   server).
     *  @param  crop    Cropping information of the form:
     *                      {ul:     [ upper-left  x, upper-left  y ], (0, 0)
     *                       lr:     [ lower-right x, lower-right y ], (50, 50)
     *                       width:  crop width,                       ( 50 )
     *                       height: crop height}                      ( 50 )
     *  @param  apiKey  The apiKey for the currently authenticated user
     *                  (REQUIRED if the transport method is NOT POST);
     *
     *  @return The URL of the cropped image.
     */
    public function cropAvatar($url, $crop, $apiKey = null)
    {
        // /*
        Connexions::log("Service_Proxy_User::cropAvatar(): "
                        .   "url[ %s ], "
                        .   "crop[ %s ], "
                        .   "apiKey[ %s ]",
                        Connexions::varExport($url),
                        Connexions::varExport($crop),
                        Connexions::varExport($apiKey) );
        // */

        $user = $this->_authenticate($apiKey);

        return ($this->_service->cropAvatar($user, $url, $crop));
    }

    /** @brief  Retrieve the Model_Set_User instance representing
     *          "contributors".
     *  @param  threshold   The number of bookmarks required to be considered a
     *                      "contributor".  A non-negative value will retrieve
     *                      users that have AT LEAST 'threshold' bookmarks,
     *                      while a negative number will retrieve users with
     *                      UP TO the absolute value of 'threshold'
     *                      bookmarks [ 1 ].
     *  @param  count       Optional LIMIT count  [ 50 ];
     *  @param  offset      Optional LIMIT offset [ 0 ];
     *
     *  @return A Model_Set_User instance representing the "contributors";
     */
    public function getContributors($threshold  = 1,
                                    $count      = 50,
                                    $offset     = null)
    {
        return ($this->_service->getContributors($threshold, $count, $offset));
    }

    /** @brief  Retrieve the COUNT of "contributors".
     *  @param  threshold   The number of bookmarks required to be considered a
     *                      "contributor".  A non-negative value will include
     *                      users that have AT LEAST 'threshold' bookmarks,
     *                      while a negative number will include users with
     *                      UP TO the absolute value of 'threshold'
     *                      bookmarks [ 1 ].
     *
     *  @return An integer COUNT representing the "contributors";
     */
    public function getContributorCount($threshold  = 1)
    {
        return ($this->_service->getContributorCount($threshold));
    }

    /** @brief  Retrieve the lastVisit date/times for the given user(s).
     *  @param  users   A Model_Set_User instance, array, or comma-separated
     *                  string of users to match.
     *  @param  group   A grouping string indicating how entries should be
     *                  grouped / rolled-up.  See
     *                  Model_Mapper_Base::_normalizeGrouping()
     *                  [ null == no grouping / roll-up ];
     *  @param  order   An order string:
     *                      'taggedOn ASC|DESC'
     *                      'updatedOn ASC|DESC'
     *                  used [ 'taggedOn ASC' ];
     *  @param  count   An OPTIONAL LIMIT count  [ no limit ];
     *  @param  offset  An OPTIONAL LIMIT offset [ 0 ];
     *  @param  from    Limit the results to date/times AFTER this date/time
     *                  [ null == no starting time limit ];
     *  @param  until   Limit the results to date/times BEFORE this date/time
     *                  [ null == no ending time limit ];
     *                  null == no time limits ];
     *
     *  @return An array of date/time / count mappings.
     */
    public function getTimeline($users,
                                $group  = null,
                                $order  = null,
                                $count  = null,
                                $offset = null,
                                $from   = null,
                                $until  = null)
    {
        $params = array();
        if (! empty($users))    $params['users']    = $users;
        if (! empty($group))    $params['grouping'] = $group;
        if (! empty($order))    $params['order']    = $order;
        if (! empty($count))    $params['count']    = $count;
        if (! empty($offset))   $params['offset']   = $offset;
        if (! empty($from))     $params['from']     = $from;
        if (! empty($until))    $params['until']    = $until;

        $timeline = $this->_service->getTimeline( $params );
        return $timeline;
    }
}
