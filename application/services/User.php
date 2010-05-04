<?php
/** @file
 *
 *  The concrete base class providing access to Model_User.
 */
class Service_User extends Connexions_Service
{
    /* inferred via classname
    protected   $_modelName = 'Model_User';
    protected   $_mapper    = 'Model_Mapper_User'; */

    /** @brief  Given a user identifier and/or credential, attempt to
     *          authenticate the identified user.
     *  @param  userId      The user identifier.
     *  @param  credential  The user credential, possibly authenticated
     *                      (i.e. OpenId, PKI).
     *  @param  authType    The type of authentication to perform
     *                      (Model_UserAuth::AUTH_*)
     *
     *  @return A Model_User instance with isAuthenticated() set accordingly.
     */
    public function authenticate($userId,
                                 $credential,
                                 $authType   = Model_UserAuth::AUTH_PASSWORD)
    {
        // First, see if 'userId' identifies a valid user.
        $user = $this->find( $userId );
        if ($user === null)
        {
            /* Create a new un-backed user instance.  If no 'userId' is
             * provided, the "anonymous" as the user name.
             */
            $user = $this->create( array('name'     => (is_string($userId)
                                                        ? $userId
                                                        : 'anonymous'),
                                         'fullName' => 'Visitor'
                                   ));
        }

        if ( ! $user->isBacked())
        {
            // Non-backed, "anonymous" user -- cannot be authenticated
            return $user;
        }

        // Include the incoming authentication information
        $user->authType   = $authType;
        $user->credential = $credential;

        // Attempt to authenticate this user
        $user->authenticate();

        // Remove the authentication information
        unset($user->authType);
        unset($user->credential);

        return $user;
    }

    /** @brief  Convert a comma-separated list of user names to a 
     *          Model_Set_User instance.
     *  @param  csList  The comma-separated list of user names.
     *
     *  @return Model_Set_Uset
     */
    public function csList2set($csList)
    {
        $names = preg_split('/\s*,\s*/', $csList);

        return $this->_getMapper()->fetchBy('name', $names, 'name ASC');
    }

    /** @brief  Retrieve a set of users related by a set of Tags.
     *  @param  tags    A Model_Set_Tag instance or array of tags to match.
     *  @param  exact   Users MUST be associated with provided tags [ true ];
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'tagCount DESC' ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_User instance.
     */
    public function fetchByTags($tags,
                                $exact   = true,
                                $order   = 'tagCount DESC',
                                $count   = null,
                                $offset  = null)
    {
        return $this->_getMapper()->fetchRelated( array(
                                        'tags'      => $tags,
                                        'exactTags' => $exact,
                                        'order'     => $order,
                                        'count'     => $count,
                                        'offset'    => $offset,
                                    ));
    }

    /** @brief  Given an array of tag rename information, rename tags for the
     *          provided user iff 'user' is authenticated.
     *  @param  user        The Model_User instance for which renaming should
     *                      be performed (MUST be authenticated);
     *  @param  renames     An array of tag rename information:
     *                          { 'oldTagName' => 'newTagName',
     *                            ... }
     *
     *  @throws Exception('Operation prohibited...')
     *  @return An array of status information, keyed by old tag name:
     *              { 'oldTagName'  => true (success) |
     *                                 String explanation of failure,
     *                 ... }
     */
    public function renameTags(Model_User   $user,
                               array        $renames)
    {
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }

        return $user->renameTags($renames);
    }

    /** @brief  Given a simple array of tag names, delete all tags for the
     *          currently authenticated user.  If deleting a tag will result in
     *          an "orphaned bookmark" (i.e. a bookmark with no tags), the
     *          delete of that tag will fail.
     *  @param  user        The Model_User instance for which tag deletion
     *                      should be performed (MUST be authenticated);
     *  @param  tags        A Model_Set_Tag instance or a simple array of tag 
     *                      names.
     *
     *  @return An array of status information, keyed by tag name:
     *              { 'tagName' => true (success) |
     *                             String explanation of failure,
     *                 ... }
     */
    public function deleteTags(Model_User   $user,
                                            $tags)
    {
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }

        return $user->deleteTags($tags);
    }
}
