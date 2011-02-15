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

    /** @brief  Retrieve a set of users related by a set of Bookmarks
     *          (actually, by the users and items represented by the 
     *           bookmarks).
     *  @param  bookmarks   A Model_Set_Bookmark instance or array of bookmark 
     *                      identifiers to match.
     *  @param  order       Optional ORDER clause (string, array)
     *                          [ 'userItemCount DESC',
     *                            'userCount     DESC',
     *                            'tag           ASC' ];
     *  @param  count       Optional LIMIT count
     *  @param  offset      Optional LIMIT offset
     *  @param  where       Additional condition(s) [ null ];
     *
     *  @return A new Model_Set_User instance.
     */
    public function fetchByBookmarks($bookmarks = null,
                                     $order     = null,
                                     $count     = null,
                                     $offset    = null,
                                     $where     = null)
    {
        if ($order === null)
        {
            $order = array('userItemCount DESC',
                           'userCount     DESC',
                           'name          ASC');
        }

        $to = array('bookmarks'  => $bookmarks,
                    'where'      => $where);

        /*
        Connexions::log("Service_User::fetchByBookmarks(): %d bookmarks [ %s ]",
                        count($bookmarks), $bookmarks);
        // */

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

        if (! $user->isValid())
        {
            $msgStrs = array();
            foreach ($user->getValidationMessages() as $field => $msgs)
            {
                array_push($msgStrs, "'{$field}': ", implode('; ', $msgs));
            }

            throw new Exception('Invalid user data: '. implode(', ', $msgStrs));
        }

        $user = $user->save();

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
            $user = $user->save();
        }

        return $user;
    }

    /** @brief  Update the user's credentials
     *  @param  user        The Model_User instance for which this update
     *                      is intended (MUST be authenticated);
     *  @param  credentials An array of credentials of the form:
     *                          { userAuthId:   (if updating an existing
     *                                           credential),
     *                            authType:     ( Model_UserAuth::AUTH_OPENID |
     *                                            Model_UserAuth::AUTH_PKI    |
     *                                            Model_UserAuth::AUTH_PASSWORD
     *                                          )
     *                            name:         The name of the credential,
     *                            credential:   The credential data }
     *
     *  @return The updated Model_Set_UserAuth.
     */
    public function updateCredentials(Model_User   $user,
                                      array        $credentials)
    {
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }

        $uaMapper = $this->_getMapper('Model_UserAuth');
        $uaSet    = $uaMapper->makeEmptySet();
        $errors   = array();
        foreach ($credentials as $idex => $info)
        {
            /*
            Connexions::log("Service_User::updateCredentials(): "
                            .   "#%d info[ %s ]",
                            $idex,
                            Connexions::varExport($info));
            // */

            if ( ! @is_numeric($info['userAuthId']))
            {
                /* Create a new record
                 *
                 * Explicit ordering to ensure that everything is set BEFORE
                 * 'credential' so it will be properly normalized.
                 */
                $ua = $user->addAuthenticator($info['credential'],
                                              (@isset($info['authType'])
                                                ? $info['authType']
                                                : Model_UserAuth::AUTH_DEFAULT
                                              ));
                /* If a name was provided AND the new credential is valid so
                 * far, set the name.
                 */
                if ( isset($info['name']) )
                {
                    $ua->name = $info['name'];
                }
            }
            else
            {
                // Update an existing record
                $ua = $user->getAuthenticator( $info['userAuthId'] );

                if ($ua !== null)
                {
                    // Update the authenticator with the new information
                    if ( isset($info['authType']) &&
                         $ua->validateAuthType($info['authType']))
                    {
                        $ua->authType = $info['authType'];
                    }

                    if ( isset($info['name']) )
                    {
                        $ua->name = $info['name'];
                    }

                    if ( isset($info['credential']) )
                    {
                        $ua->credential = $info['credential'];
                    }

                    $ua = $ua->save();
                }
            }

            if ($ua)
            {
                // Attempt to save any changes
                $ua = $ua->save();

                // See if the updated instance is valid
                if (! $ua->isValid() )
                {
                    $errors[$idex] = $ua->getValidationMessages();
                    continue;
                }
            }

            $uaSet->append($ua);
        }

        if (count($errors) > 0)
        {
            // :XXX: One or more validation errors
            Connexions::log("Service_User::updateCredentials(): "
                            . "errors[ %s ]",
                            Connexions::varExport($errors));
        }

        /*
        Connexions::log("Service_User::updateCredentials(): "
                        . "final set[ %s ]",
                        $uaSet->debugDump());
        // */

        return $uaSet;
    }

    /** @brief  Delete a specific credential for the given user.
     *  @param  user        The Model_User instance for which this dlete
     *                      is intended (MUST be authenticated);
     *  @param  credential  The credential identifier.
     *
     *  @return The updated Model_UserAuth.
     */
    public function deleteCredential(Model_User   $user,
                                                  $credential)
    {
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }

        // /*
        Connexions::log("Service_User::deleteCredential(): "
                        .   "user[ %s ], credential[ %s ]",
                        $user,
                        Connexions::varExport($credential));
        // */

        //$ua = $user->removeCredential($credential);
    }
}
