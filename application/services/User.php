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

    /** @brief  Any default ordering that should be be merged into a specified 
     *          order.
     */
    protected   $_defaultOrdering   = array(
        'lastVisit' => 'DESC',
        'name'      => 'ASC',
        'fullName'  => 'ASC',
    );

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

        $authResult = $auth->authenticate( $authAdapter );
        if ($authResult->isValid())
        {
            // Retrieve the authenticated Model_User instance
            $user = $authResult->getUser();
        }
        else
        {
            /* Get an 'anonymous' Model_User instance and do NOT mark it
             * authenticated.  Attach the current authentication results
             * so we can properly present error messages.
             */
            $user = $this->getAnonymous();
            $user->setAuthResult($authResult);
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

    /** @brief  Create a new, anonymous user -- unauthenticated and unbacked.
     *  @param  .
     *
     *  @return A Model_User instance (unauthenticated and unbacked).
     */
    public function getAnonymous()
    {
        return $this->_mapper->makeModel(array('userId'   => 0,
                                               'name'     => 'anonymous',
                                               'fullName' => 'Visitor'
                                         ), false);
    }

    /** @brief  Retrieve a set of users related by a set of Tags.
     *  @param  tags    A Model_Set_Tag instance, array, or comma-separated
     *                  string of tags to match.
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

        $to = array('tags'       => $tags,
                    'exactTags'  => $exact);

        return $this->fetchRelated( $to,
                                    $order,
                                    $count,
                                    $offset );
    }

    /** @brief  Given an array of tag rename information, rename tags for the
     *          provided user iff 'user' is authenticated.
     *  @param  user        The Model_User instance for which renaming should
     *                      be performed (MUST be authenticated);
     *  @param  renames     An array of tag rename information:
     *                          { 'oldTagName' => 'newTagName',
     *                            ... }
     *                      or a comma-separated string of tag name information
     *                      of the form:
     *                          'oldTag:newTag, ...'
     *
     *  @throws Exception('Operation prohibited...')
     *  @return An array of status information, keyed by old tag name:
     *              { 'oldTagName'  => true (success) |
     *                                 String explanation of failure,
     *                 ... }
     */
    public function renameTags(Model_User   $user,
                                            $renames)
    {
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }

        if (is_string($renames))
        {
            $ar = $this->_csList2array($renames);
            if (! empty($ar))
            {
                $renames = array();
                foreach ($ar as $item)
                {
                    list($old, $new) = preg_split('/\s*:\s*/', $item, 2);
                    $renames[$old]   = $new;
                }
            }
        }

        return $user->renameTags($renames);
    }

    /** @brief  Given a simple array of tag names, delete all tags for the
     *          currently authenticated user.  If deleting a tag will result in
     *          an "orphaned bookmark" (i.e. a bookmark with no tags), the
     *          delete of that tag will fail.
     *  @param  user        The Model_User instance for which tag deletion
     *                      should be performed (MUST be authenticated);
     *  @param  tags        A Model_Set_Tag instance, simple array of tag
     *                      identifiers, or comma-separated list of tag
     *                      identifiers.
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

        // Rely on Service_Tag to properly interpret 'tags'
        $tags = $this->factory('Service_Tag')->csList2set($tags);

        return $user->deleteTags($tags);
    }

    /** @brief  Update a user.
     *  @param  user        The Model_User instance for which this update
     *                      is intended (MUST be authenticated);
     *  @param  fullName    The new 'fullName'   (null for no change);
     *  @param  email       The new 'email'      (null for no change);
     *  @param  pictureUrl  The new 'pictureUrl' (null for no change);
     *  @param  profileUrl  The new 'profile'    (null for no change);
     *
     *  @return The updated user.
     */
    public function update(Model_User   $user,
                                        $fullName   = null,
                                        $email      = null,
                                        $pictureUrl = null,
                                        $profileUrl = null)
    {
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }

        if (! empty($fullName))     $user->fullName   = $fullName;
        if (! empty($email))        $user->email      = $email;
        if (! empty($pictureUrl))   $user->pictureUrl = $pictureUrl;
        if (! empty($profileUrl))   $user->profile    = $profileUrl;

        if ($user->isValid())
        {
            $user->save();
        }

        return $user;
    }

    /** @brief  Regenerate the user's API Key
     *  @param  user        The Model_User instance for which this update
     *                      is intended (MUST be authenticated);
     *
     *  @return The updated user.
     */
    public function regenerateApiKey(Model_User   $user)
    {
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }

        $user->apiKey = Model_User::genApiKey();

        if ($user->isValid())
        {
            $user->save();
        }

        return $user;
    }
}
