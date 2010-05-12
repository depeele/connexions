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

    protected   $_authResult    = null;

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
        $auth        = Zend_Auth::getInstance();
        $authAdapter = null;
        switch ($authType)
        {
        case Model_UserAuth::AUTH_OPENID:
            $authAdapter = new Connexions_Auth_OpenId( $credential );
            break;

        case Model_UserAuth::AUTH_PKI:
            $authAdapter = new Connexions_Auth_ApacheSsl();
            break;

        case Model_UserAuth::AUTH_PASSWORD:
        default:
            $authAdapter = new Connexions_Auth_UserPassword();
            break;
        }

        /*
        Connexions::log("Service_User::authenticate(): "
                        .   "authType[ %s ], authAdapter[ %s ]",
                        $authType,
                        (is_object($authAdapter)
                            ? get_class($authAdapter)
                            : gettype($authAdapter)));
        // */

        $this->_authResult = $auth->authenticate( $authAdapter );
        if ($this->_authResult->isValid())
        {
            // Retrieve the authenticated Model_User instance
            $user = $this->_authResult->getUser();
        }
        else
        {
            /* Create a non-backed, unauthenticated 'anonymous' Model_User
             * instance
             */
            $user = $this->create( array('name'     => 'anonymous',
                                         'fullName' => 'Guest') );
        }

        /*
        Connexions::log("Service_User::authenticate(): "
                        .   "authType[ %s ], authAdapter[ %s ], user[ %s ]",
                        $authType,
                        (is_object($authAdapter)
                            ? get_class($authAdapter)
                            : gettype($authAdapter)),
                        $user->debugDump());
        // */

        return $user;
    }

    /** @brief  Retrieve the authentication result from the last authentcate()
     *          call.
     *
     *  @return Zend_Auth_Result (or null if authenticate() was not invoked).
     */
    public function getAuthResult()
    {
        return $this->_authResult;
    }

    /** @brief  Convert a comma-separated list of user names to a 
     *          Model_Set_User instance.
     *  @param  csList  The comma-separated list of user names.
     *
     *  @return Model_Set_Uset
     */
    public function csList2set($csList)
    {
        $names = (empty($csList)
                    ? array()
                    : preg_split('/\s*,\s*/', strtolower($csList)) );

        if (empty($names))
        {
            $set = $this->_getMapper()->makeEmptySet();
        }
        else
        {
            $set = $this->_getMapper()->fetchBy('name', $names, 'name ASC');
            $set->setSource($csList);
        }

        return $set;
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
                                $order   = null,
                                $count   = null,
                                $offset  = null)
    {
        if ($order === null)
            $order = 'tagCount DESC';

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
