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
}
