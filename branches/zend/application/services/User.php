<?php
/** @file
 *
 *  The concrete base class providing access to Model_User.
 */
class Service_User extends Service_Base
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
     *  @param  method      The authentication method
     *                      (a dotted form of Model_UserAuth::AUTH_*)
     *  @param  credential  If needed, the user credential
     *                      (e.g. OpenId endpoint, User password);
     *  @param  id          If needed, the user identity
     *                      (e.g. User name);
     *  @param  depth       To avoid infinite recursion when we automatically
     *                      create a new userAuth record for a user and re-try
     *                      authentication [ 0 ];
     *
     *  On successful authentication, the authentication information will be
     *  stored in the authentication session, identified by the session cookie.
     *  In order to make use of the authentication, the session cookie MUST be
     *  maintained by the client and provided on future request.
     *
     *  @return A Model_User instance with isAuthenticated() set accordingly.
     */
    public function authenticate($method     = Model_UserAuth::AUTH_PASSWORD,
                                 $credential = null,
                                 $id         = null,
                                 $depth      = 0)
    {
        $auth        = Zend_Auth::getInstance();
        $authAdapter = null;

        list($method, $name) = explode('.', $method);

        // /*
        Connexions::log("Service_User::authenticate(): "
                        .   "method[ %s ], name[ %s ], "
                        .   "credential[ %s ], id[ %s ]",
                        $method, $name,
                        Connexions::varExport($credential),
                        Connexions::varExport($id) );
        // */
        switch ($method)
        {
        case Model_UserAuth::AUTH_OPENID:
            $authAdapter = new Connexions_Auth_OpenId( $credential );
            if ((! empty($name)) &&
                isset(Connexions_Auth_OpenId::$openid_endpoints[$name]))
            {
                $id = Connexions_Auth_OpenId::$openid_endpoints[$name];
            }
            break;

        case Model_UserAuth::AUTH_PKI:
            $authAdapter = new Connexions_Auth_ApacheSsl();
            break;

        case Model_UserAuth::AUTH_PASSWORD:
        default:
            $authAdapter = new Connexions_Auth_UserPassword();

            // /*
            Connexions::log("Service_User::authenticate(): "
                            .   "method[ %s ], authAdapter[ %s ]",
                            $method,
                            get_class($authAdapter));
            // */

            /* If credential and/or id are provided, push them into the
             * current request.
             */
            if (($id !== null) || ($credential !== null))
            {
                $req = Connexions::getRequest();

                /*
                Connexions::log("Service_User::authenticate(): "
                                .   "request[ %s ] parameters[ %s ]",
                                get_class($req),
                                Connexions::varExport($req->getParams()));
                // */

                if (($id !== null) && (! $req->__isset('username')))
                {
                    $req->setParam('username', $id);
                }

                if (($credential !== null) && (! $req->__isset('password')))
                {
                    $req->setParam('password', $credential);
                }

                /*
                Connexions::log("Service_User::authenticate(): "
                                .   "method[ %s ], request parameters[ %s ]",
                                $method,
                                Connexions::varExport($req->getParams()) );
                // */
            }
            break;
        }

        /*
        Connexions::log("Service_User::authenticate(): "
                        .   "method[ %s ], authAdapter[ %s ], "
                        .   "credential[ %s ], id[ %s ]",
                        $method,
                        (is_object($authAdapter)
                            ? get_class($authAdapter)
                            : gettype($authAdapter)),
                        Connexions::varExport($credential),
                        Connexions::varExport($id) );
        // */

        $authResult = $auth->authenticate( $authAdapter );

        /* If authentication is valid, attempt to retrieve the authenticated
         * Model_User instance.
         */
        $user       = ($authResult->isValid() ? $authResult->getUser() : null);

        if ($authResult->isValid() && $user)
        {
            // Log this authentication
            $this->_logActivity($user, 'update',
                                array('action'        => 'authenticate',
                                      'method'        => $method));
        }
        else
        {
            /* Authentication FAILED.
             *
             * If this is PKI-based authentication, this MAY mean that the
             * user's credential has not yet been registered.  If we have
             * verified PKI information AND a matching user, automatically
             * register the userAuth record and re-try authentication.
             */

            /*
            Connexions::log("Service_User::authenticate(): FAILED "
                            .   "method[ %s ], authAdapter[ %s ], "
                            .   "credential[ %s ], id[ %s ], "
                            .   "authResult[ %s ], user[ %s ]",
                            $method,
                            (is_object($authAdapter)
                                ? get_class($authAdapter)
                                : gettype($authAdapter)),
                            Connexions::varExport($credential),
                            Connexions::varExport($id),
                            Connexions::varExport($authResult),
                            Connexions::varExport($user) );
            // */

            if ( ($depth === 0) && ($method === Model_UserAuth::AUTH_PKI) )
            {
                $connection = Zend_Registry::get('connectionInfo');
                $pkiInfo    = Connexions::parsePki( $connection['pki'] );

                if ( $pkiInfo && (! empty($pkiInfo['name'])) )
                {
                    // See if we can find a matching user.
                    $user = $this->get(array('name' => $pkiInfo['name']));
                    if ($user->isBacked())
                    {
                        /* We found a valid user.  Create a userAuth record for
                         * them using the current, verified PKI information.
                         */
                        /*
                        Connexions::log("Service_User::authenticate(): "
                                        . "PKI authentication FAILED but "
                                        . "we found a valid user[ %s ] "
                                        . "using pkiInfo[ %s ].  "
                                        . "Create a userAuth record...",
                                        $user,
                                        Connexions::varExport($pkiInfo));
                        // */

                        $ua = $user->addAuthenticator($pkiInfo['subject'],
                                                      Model_UserAuth::AUTH_PKI);
                        $ua->name = 'PKI certificate';
                        $ua = $ua->save();

                        // Re-try authentication
                        return $this->authenticate(Model_UserAuth::AUTH_PKI,
                                                   $pkiInfo['subject'],
                                                   $user->userId,
                                                   $depth++);
                    }
                }

                /*
                Connexions::log("Service_User::authenticate(): "
                                . "PKI authentication FAILED and we "
                                . "found no user using pkiInfo[ %s ]",
                                Connexions::varExport($pkiInfo));
                // */
            }

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

    /** @brief  Revoke any current authentication.
     *
     *  @return true
     */
    public function deauthenticate()
    {
        // Log this deauthentication
        $user = Connexions::getUser();
        $this->_logActivity($user, 'update',
                            array('action' => 'deauthenticate'));


        $auth  = Zend_Auth::getInstance();
        $auth->clearIdentity();

        return true;
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

    /** @brief  Retrieve a set of users related by a set of Tags ( u(t) ).
     *  @param  tags    A Model_Set_Tag instance, array, or comma-separated
     *                  string of tags to match.
     *  @param  exact   Users MUST be associated with provided tags [ true ];
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'tagCount DESC' ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *  @param  where   Additional condition(s) [ null ];
     *
     *  @return A new Model_Set_User instance.
     */
    public function fetchByTags($tags,
                                $exact  = true,
                                $order  = null,
                                $count  = null,
                                $offset = null,
                                $where  = null)
    {
        if ($order === null)
        {
            $order = array('tagCount DESC');
        }
        else
        {
            $order = $this->_csOrder2array($order);
        }

        $to = array('tags'       => $tags,
                    'exactTags'  => $exact,
                    'where'      => $where);

        return $this->fetchRelated( $to,
                                    $order,
                                    $count,
                                    $offset );
    }

    /** @brief  Retrieve a set of users related by a set of Bookmarks
     *          (actually, by the users and items represented by the 
     *           bookmarks) ( u(b) ).
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
        else
        {
            $order = $this->_csOrder2array($order);
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
        /* SHOULD be handled by the Proxy for any API path
         * but double-check here for extra protection
         * (and since the current tests require it).
         */
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }
        // */

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
        /* SHOULD be handled by the Proxy for any API path
         * but double-check here for extra protection
         * (and since the current tests require it).
         */
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }
        // */

        // Rely on Service_Tag to properly interpret 'tags'
        $tags = $this->factory('Service_Tag')->csList2set($tags);

        return $user->deleteTags($tags);
    }

    /** @brief  Retrieve the network for the given user.
     *  @param  user        The Model_User instance for which network
     *                      should be retrieved;
     *
     *  @return A Model_Group instance.
     */
    public function getNetwork(Model_User $user)
    {
        return $user->getNetwork();
    }

    /** @brief  Add a new user to the network of this user.
     *  @param  user        The Model_User instance for which network add
     *                      should be performed (MUST be authenticated);
     *  @param  users       A Model_Set_User instance, simple array of user
     *                      identifiers, or comma-separated list of user
     *                      identifiers.
     *
     *  @return An array of status information, keyed by user name:
     *              { '%userName%'  => true (success) |
     *                                 String explanation of failure,
     *                 ... }
     */
    public function addToNetwork(Model_User $user,
                                            $users)
    {
        /* SHOULD be handled by the Proxy for any API path
         * but double-check here for extra protection
         * (and since the current tests require it).
         */
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }

        /* We grab both an array and set so we can check to see if any of the
         * identified user's are unknown and mark them as such
         */
        $ids           = $this->_csList2array($users);
        $userInstances = $this->csList2set($users);

        /*
        Connexions::log("Service_User::addToNetwork(): user[ %s ], "
                        . "users[ %s ] == ids[ %s ] == set[ %s ], source[ %s ]",
                        $user,
                        Connexions::varExport($users),
                        Connexions::varExport($ids),
                        Connexions::varExport($userInstances),
                        Connexions::varExport($userInstances->getSource()));
        // */


        /* Attempt to add all those user that are valid, marking those that are
         * 'unbacked' as "Unknown"
         */
        $res = array();
        foreach ($userInstances as $newUser)
        {
            $res[ $newUser->__toString() ] =
                ($newUser->isBacked()
                    ?  $user->addToNetwork( $newUser )
                    : 'Unknown user');
        }

        /*
        Connexions::log("Service_User::addToNetwork(): res[ %s ]",
                        Connexions::varExport($res));
        // */

        return $res;
    }

    /** @brief  Remove one or more users from the network of the identified
     *          user.
     *  @param  user        The Model_User instance for which network remove
     *                      should be performed (MUST be authenticated);
     *  @param  users       A Model_Set_User instance, simple array of user
     *                      identifiers, or comma-separated list of user
     *                      identifiers.
     *
     *  @return An array of status information, keyed by user name:
     *              { '%userName%'  => true (success) |
     *                                 String explanation of failure,
     *                 ... }
     */
    public function removeFromNetwork(Model_User $user,
                                                 $users)
    {
        /* SHOULD be handled by the Proxy for any API path
         * but double-check here for extra protection
         * (and since the current tests require it).
         */
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }
        // */

        /* We grab both an array and set so we can check to see if any of the
         * identified user's are unknown and mark them as such
         */
        $ids           = $this->_csList2array($users);
        $userInstances = $this->csList2set($users);

        /*
        Connexions::log("Service_User::addToNetwork(): user[ %s ], "
                        . "users[ %s ] == ids[ %s ] == set[ %s ], source[ %s ]",
                        $user,
                        Connexions::varExport($users),
                        Connexions::varExport($ids),
                        Connexions::varExport($userInstances),
                        Connexions::varExport($userInstances->getSource()));
        // */


        /* Attempt to remove all those user that are valid, marking those that
         * are 'unbacked' as "Unknown"
         */
        $res           = array();
        foreach ($userInstances as $remUser)
        {
            $res[ $remUser->__toString() ] =
                ($remUser->isBacked()
                    ?  $user->removeFromNetwork( $remUser )
                    : 'Unknown user');
        }

        return $res;
    }

    /** @brief  Change the visibility of the network for the given user.
     *  @param  user        The Model_User instance for which network
     *                      visibility should be changed
     *                      (MUST be authenticated);
     *  @param  visibility  The new network visibility value ('public',
     *                      'private', 'group');
     *
     *  @return The (updated) Model_Group instance representing the user's
     *          network;
     *
     */
    public function changeNetworkVisibility(Model_User $user,
                                                       $visibility)
    {
        $network = $user->getNetwork();
        $network->visibility = $visibility;

        if (! $network->isValid())
        {
            $msgStrs = array();
            foreach ($network->getValidationMessages() as $field => $msgs)
            {
                array_push($msgStrs, "'{$field}': ", implode('; ', $msgs));
            }

            throw new Exception('Invalid network data: '.
                                    implode(', ', $msgStrs));
        }
        $network = $network->save();

        return $network;
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
        if ($limit < 1) $limit = 15;

        /*
        Connexions::log("Service_User::autocomplete(): "
                        .   "term[ %s ], limit[ %d ]",
                        $term, $limit);
        // */

        $id = array('name=*'        => $term,
                    '+|fullName=*'  => $term,
                    '+|email=*'     => $term,
        );

        $users = $this->fetch($id,
                              null,     // default order
                              $limit);

        /*
        Connexions::log("Service_User::autocomplete(): "
                        .   "term[ %s ], limit[ %d ] == [ %s ]",
                        $term, $limit, $users);
        // */

        return $users;
    }

    /** @brief  Perform tag autocompletion for the given user.
     *  @param  term    The string to autocomplete.
     *  @param  context The context of completion:
     *                      - A Model_User instance, defining the specific user
     *                        to perform autocomplete for;
     *                      - A Model_Set_Tag instance, array, or
     *                        comma-separated string of tags that restrict the
     *                        bookmarks that should be used to select related
     *                        tags;
     *  @param  limit   The maximum number of tags to return [ 15 ];
     *
     *  @return Model_Set_Tag
     */
    public function autocompleteTag($term       = null,
                                    $context    = null,
                                    $limit      = 15)
    {
        if ($limit < 1) $limit = 15;

        /*
        Connexions::log("Service_User::autocompleteTag(): "
                        .   "term[ %s ], context[ %s ], limit[ %d ]",
                        $term, $context, $limit);
        // */

        /* Retrieve the users that define the scope for this
         * autocompletion
         */
        $exactUsers = false;
        if ( ! empty($context))
        {
            if ($context instanceof Model_User)
            {
                $users      = $context;
                $exactUsers = true;
            }
            else
            {
                /* ASSUME 'context' represets a set of tags that should be used
                 * to retrieve the tag-related users that will serve as the
                 * scope for the autocompletion.
                 */
                $users = $this->fetchByTags($context);
            }
        }
        else
        {
            // No user limits.
            $users = null;
        }

        /*
        Connexions::log("Service_User::autocompleteTag(): "
                        .   "users[ %s ]",
                        $users);
        // */

        /* :NOTE: To match a string in any position within the tag, use:
         *          'tag=*'
         */
        $tService = $this->factory('Service_Tag');
        return $tService->fetchByUsers($users,      // users
                                       null,        // default order
                                       $limit,
                                       null,        // default offset
                                       $exactUsers, // How to match users.
                                       array('tag=*' => $term));
    }

    /** @brief  Update a user.
     *  @param  user        The Model_User instance for which this update
     *                      is intended (MUST be authenticated);
     *  @param  fullName    The new 'fullName'   (null for no change);
     *  @param  email       The new 'email'      (null for no change);
     *  @param  pictureUrl  The new 'pictureUrl' (null for no change);
     *  @param  profile     The new 'profile'    (null for no change);
     *
     *  @return The updated user.
     */
    public function update(Model_User   $user,
                                        $fullName   = null,
                                        $email      = null,
                                        $pictureUrl = null,
                                        $profile    = null)
    {
        /* SHOULD be handled by the Proxy for any API path
         * but double-check here for extra protection
         * (and since the current tests require it).
         */
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }
        // */

        /*
        Connexions::log("Service_User::update() config [ %s, %s ]",
                        get_class($this),
                        $key, (is_object($val)
                                ? get_class($val)
                                : Connexions::varExport($val)));
        // */

        if (! empty($fullName))     $user->fullName   = $fullName;
        if (! empty($email))        $user->email      = $email;
        if (! empty($pictureUrl))   $user->pictureUrl = $pictureUrl;
        if (! empty($profile))      $user->profile    = $profile;

        if (! $user->isValid())
        {
            $msgStrs = array();
            foreach ($user->getValidationMessages() as $field => $msgs)
            {
                array_push($msgStrs, "'{$field}': ", implode('; ', $msgs));
            }

            throw new Exception('Invalid user data: '. implode(', ', $msgStrs));
        }

        /*
        Connexions::log("Service_User::update() config [ %s, %s ]",
                        get_class($this),
                        $key, (is_object($val)
                                ? get_class($val)
                                : Connexions::varExport($val)));
        // */

        //$user = $user->save();
        return $user->save();
    }

    /** @brief  Regenerate the user's API Key
     *  @param  user        The Model_User instance for which this update
     *                      is intended (MUST be authenticated);
     *
     *  @return The updated user.
     */
    public function regenerateApiKey(Model_User   $user)
    {
        /* SHOULD be handled by the Proxy for any API path.
         * but double-check here for extra protection
         * (and since the current tests require it).
         */
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }
        // */

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
        /* SHOULD be handled by the Proxy for any API path
         * but double-check here for extra protection
         * (and since the current tests require it).
         */
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }
        // */

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
     *  @param  user        The Model_User instance for which this delete
     *                      is intended (MUST be authenticated);
     *  @param  credential  The credential identifier.
     *
     *  @return The updated Model_UserAuth.
     */
    public function deleteCredential(Model_User   $user,
                                                  $credential)
    {
        /* SHOULD be handled by the Proxy for any API path
         * though the current tests require this check.
         */
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }
        // */

        /*
        Connexions::log("Service_User::deleteCredential(): "
                        .   "user[ %s ], credential[ %s ]",
                        $user,
                        Connexions::varExport($credential));
        // */

        $ua = $user->getAuthenticator( $credential );
        if ($ua !== null)
        {
            $ua->delete();

            /*
            Connexions::log("Service_User::deleteCredential(): "
                            .   "user[ %s ], deleted userAuthenticator[ %s ]",
                            $user,
                            $ua->debugDump());
            // */
        }

        return $ua;
    }

    /** @brief  Given a *local* URL to an avatar image along with cropping
     *          information, perform the image manipulation to accomplish the
     *          crop and move the resulting image to the avatar directory with
     *          a name based upon the authenticated user.
     *  @param  user    The Model_User instance to which the successfully
     *                  cropped avatar should be attached.
     *  @param  srcUrl  The *local* URL of the source image.
     *  @param  crop    Cropping information of the form:
     *                      {ul:     [ upper-left  x, upper-left  y ], (0, 0)
     *                       lr:     [ lower-right x, lower-right y ], (50, 50)
     *                       width:  crop width,                       ( 50 )
     *                       height: crop height}                      ( 50 )
     *
     *  Note: 'crop.width' / 'crop.height' represent the final width/height of
     *        the avatar image.  'crop.ul' and 'crop.lr' are used to determine
     *        the width / height of the area to retrieve from the source image.
     *
     *  @return The URL of the cropped image.
     */
    public function cropAvatar(Model_User   $user,
                                            $srcUrl,
                                            $crop)
    {
        $config  = Zend_Registry::get('config');
        $srcInfo = parse_url($srcUrl);
        if (! isset($srcInfo['scheme']))
        {
            // The incoming URL is relative.  Convert it to a local file path
            $srcPath = Connexions::url2path( $srcUrl );
        }
        else
        {
            // The incoming URL has a scheme.  Keep it as a URL.
            $srcPath = $srcUrl;
        }

        // /*
        Connexions::log("Service_User::cropAvatar(): "
                        .   "user[ %s ], srcUrl[ %s ], srcPath[ %s ], "
                        .   "crop[ %s ]",
                        $user,
                        Connexions::varExport($srcUrl),
                        Connexions::varExport($srcPath),
                        Connexions::varExport($crop) );
        // */

        $dstUrl  = $config->urls->avatar .'/'. $user->name;
        $dstPath = Connexions::url2path( $dstUrl );

        // /*
        Connexions::log("Service_User::cropAvatar(): "
                        .   "dstUrl[ %s ], dstPath[ %s ]",
                        Connexions::varExport($dstUrl),
                        Connexions::varExport($dstPath) );
        // */


        // Retrieve information about the source image.
        $srcInfo   = getimagesize($srcPath);

        // /*
        Connexions::log("Service_User::cropAvatar(): "
                        .   "srcPath[ %s ], info[ %s ]",
                        Connexions::varExport($srcPath),
                        Connexions::varExport($srcInfo) );
        // */

        $srcWidth  = $srcInfo[0];
        $srcHeight = $srcInfo[1];
        $srcMime   = $srcInfo['mime'];

        if (! isset($crop['width']))    $crop['width']  = 50;
        if (! isset($crop['height']))   $crop['height'] = 50;
        if (! is_array($crop['ul']))    $crop['ul']     = array(0, 0);
        if (! is_array($crop['lr']))    $crop['lr']     =
                array( $crop['ul'][0] + $srcWidth,
                       $crop['ul'][1] + $srcHeight );


        $thumbnail = imagecreatetruecolor($crop['width'],
                                          $crop['height']);
        switch ($srcMime)
        {
        case 'image/gif':
            $src     = imagecreatefromgif($srcPath);
            $ext     = '.gif';
            break;

        case 'image/pjpeg':
        case 'image/jpeg':
        case 'image/jpg':
            $srcMime = 'image/jpg';

            $src     = imagecreatefromjpeg($srcPath);
            $ext     = '.jpg';
            break;

        case 'image/png':
        case 'image/x-png':
            $srcMime = 'image/png';

            $src     = imagecreatefrompng($srcPath);
            $ext     = '.png';
            break;

        default:
            throw new Exception("Unsupported image type[ {$srcMime} ]");
            break;
        }

        // Include the file-type extension
        $dstPath .= $ext;
        $dstUrl  .= $ext;

        // source width / height (lr - ul)
        $srcSize = array('width'    => $crop['lr'][0] - $crop['ul'][0],
                         'height'   => $crop['lr'][1] - $crop['ul'][1],
        );

        // /*
        Connexions::log("Service_User::cropAvatar(): "
                        .   "src type[ %s ], "
                        .   "size[ %d, %d ], "
                        .   "crop( [ %d, %d ], [ %d, %d ] => [ %d, %d ]",
                        $srcMime,
                        $srcSize['width'], $srcSize['height'],
                        $crop['ul'][0], $crop['ul'][1],
                        $crop['lr'][0], $crop['lr'][1],
                        $crop['width'], $crop['height'] );
        // */

        imagecopyresampled($thumbnail, $src,
                           // destination x / y
                           0, 0,

                           // source x / y
                           $crop['ul'][0], $crop['ul'][1],

                           // destination width / height
                           $crop['width'], $crop['height'],

                           // source width / height
                           $srcSize['width'], $srcSize['height']);

        imagedestroy($src);

        switch ($srcMime)
        {
        case 'image/gif':
            imagegif($thumbnail, $dstPath);
            break;

        case 'image/jpg':
            imagejpeg($thumbnail, $dstPath, 90);
            break;

        case 'image/png':
            imagepng($thumbnail, $dstPath);
            break;

        }

        chmod($dstPath, 0644);

        // /*
        Connexions::log("Service_User::cropAvatar(): "
                        .   "final destination url[ %s ]",
                        $dstUrl);
        // */

        // Set the user's pictureUrl to the URL of this new Avatar
        $user->pictureUrl = $dstUrl;
        $user->save();

        // /*
        Connexions::log("Service_User::cropAvatar(): "
                        .   "user avatar changed to [ %s ]",
                        $user->pictureUrl);
        // */


        return $dstUrl;
    }

    /** @brief  Retrieve the Model_Set_User instance representing
     *          "contributors".
     *  @param  params  An array of optional retrieval criteria compatible with
     *                  fetchRelated() with the addition of:
     *                      - threshold The number of bookmarks required to be
     *                                  considered a "contributor".  A
     *                                  non-negative value will include users
     *                                  that have AT LEAST 'threshold'
     *                                  bookmarks, while a negative number will
     *                                  include users with UP TO the absolute
     *                                  value of 'threshold' bookmarks [ 1 ];
     *                      - order     An ORDER clause (string, array)
     *                                  [ 'totalItems DESC, name ASC' ];
     *                      - count     A  LIMIT count
     *                                  [ all ];
     *                      - offset    A  LIMIT offset
     *                                  [ 0 ];
     *
     *  @return A Model_Set_User instance representing the "contributors";
     */
    public function getContributors(array $params = array())
    {
        if ( ! isset($params['threshold']))
        {
            // Compute a threshold based upon overall statistics
            $statParams = $params;
            $statParams['aggregate'] = true;

            $stats = $this->getStatistics( $statParams );

            if ($stats['bookmarks_sd'] >= $stats['bookmarks_avg'])
            {
                // HUGE variance.  Use bookmarks_min as the threshold
                $threshold = $stats['bookmarks_min'];
            }
            else
            {
                /* Use a threshold of MAX - (SD * 2.575829).
                 *
                 * Assuming a normal distribution (not likely), this would
                 * return roughly 99% of all users.
                 */
                $threshold = ceil($stats['bookmarks_max'] -
                                  ($stats['bookmarks_sd'] * 2.575829));
            }

            /*
            Connexions::log("Service_User::getContributors(): "
                            . "min[ %s ], avg[ %s ] %s sd[ %s ], "
                            . "threshold[ %s ]",
                            Connexions::varExport($stats['bookmarks_min']),
                            Connexions::varExport($stats['bookmarks_avg']),
                            ($stats['bookmarks_avg'] > $stats['bookmarks_sd']
                                ? '>' : '<'),
                            Connexions::varExport($stats['bookmarks_sd']),
                            Connexions::varExport($threshold));
            // */


            $params['threshold'] = $threshold;
        }

        $config = $this->_normalizeParams($params);

        /*
        Connexions::log("Service_User::getContributors(): "
                        . "params[ %s ]",
                        Connexions::varExport($params));
        Connexions::log("Service_User::getContributors(): "
                        . "config[ %s ]",
                        Connexions::varExport($config));
        // */

        return $this->_mapper->getContributors($config);
    }

    /** @brief  Retrieve the COUNT of "contributors".
     *  @param  params  An array of optional retrieval criteria compatible with
     *                  fetchRelated() with the addition of:
     *                      - threshold The number of bookmarks required to be
     *                                  considered a "contributor".  A
     *                                  non-negative value will include users
     *                                  that have AT LEAST 'threshold'
     *                                  bookmarks, while a negative number will
     *                                  include users with UP TO the absolute
     *                                  value of 'threshold' bookmarks [ 1 ];
     *
     *  @return A simple array containing:
     *              {'total':        total users,
     *               'contributors': number of "contributors",
     *               'threshold':    the threshold value used}
     *
     *          If computed, aggregate stats will also be returned.
     */
    public function getContributorCount(array $params = array())
    {
        $stats = null;
        if ( ! isset($params['threshold']))
        {
            // Compute a threshold based upon overall statistics
            $statParams = $params;
            $statParams['aggregate'] = true;

            $stats = $this->getStatistics( $statParams );

            if ($stats['bookmarks_sd'] >= $stats['bookmarks_avg'])
            {
                // HUGE variance.  Use bookmarks_min as the threshold
                $threshold = $stats['bookmarks_min'];
            }
            else
            {
                /* Use a threshold of MAX - (SD * 2.575829).
                 *
                 * Assuming a normal distribution (not likely), this would
                 * return roughly 99% of all users.
                 */
                $threshold = ceil($stats['bookmarks_max'] -
                                  ($stats['bookmarks_sd'] * 2.575829));
            }

            /*
            Connexions::log("Service_User::getContributorCount(): "
                            . "min[ %s ], avg[ %s ] %s sd[ %s ], "
                            . "threshold[ %s ]",
                            Connexions::varExport($stats['bookmarks_min']),
                            Connexions::varExport($stats['bookmarks_avg']),
                            ($stats['bookmarks_avg'] > $stats['bookmarks_sd']
                                ? '>' : '<'),
                            Connexions::varExport($stats['bookmarks_sd']),
                            Connexions::varExport($threshold));
            // */

            $params['threshold'] = $threshold;
        }

        $config = $this->_normalizeParams($params);

        $res = $this->_mapper->getContributorCount($config);

        if ($stats !== null)
        {
            // Include the stats that were computed
            $res['stats'] = $stats;
        }

        return $res;
    }

    /** @brief  Retrieve the lastVisit date/times for the given user(s).
     *  @param  params  An array of optional retrieval criteria:
     *                      - users     A set of users to use in selecting the
     *                                  bookmarks used to construct the
     *                                  timeline.  A Model_Set_User instance or
     *                                  an array of userIds;
     *                      - grouping  A grouping string indicating how
     *                                  timeline entries should be grouped /
     *                                  rolled-up.  See _normalizeGrouping();
     *                                  [ 'YMDH' ];
     *                      - order     An ORDER clause (string, array)
     *                                  [ 'taggedOn DESC' ];
     *                      - count     A  LIMIT count
     *                                  [ all ];
     *                      - offset    A  LIMIT offset
     *                                  [ 0 ];
     *                      - from      A date/time string to limit the results
     *                                  to those occurring AFTER the specified
     *                                  date/time;
     *                      - until     A date/time string to limit the results
     *                                  to those occurring BEFORE the specified
     *                                  date/time;
     *
     *  @return An array of date/time strings.
     */
    public function getTimeline(array $params = array())
    {
        if (isset($params['users']) && (! empty($params['users'])) )
        {
            $params['users'] =
                $this->factory('Service_User')->csList2set($params['users']);
        }

        if (isset($params['order']) && (! empty($params['order'])) )
        {
            $params['order'] =
                $this->_csOrder2array($params['order'], true /* noExtras */);
        }

        /*
        Connexions::log("Service_User::getTimeline(): "
                        . "params[ %s ]",
                        Connexions::varExport($params));
        // */

        $timeline = $this->_mapper->getTimeline( $params );
        return $timeline;
    }

    /*************************************************************************
     * Protected Helpers
     *
     */

    /** @brief  Log activity
     *  @param  user        The Model_User instance;
     *  @param  operation   The activity/operation
     *                      ('save', 'update', 'delete');
     *  @param  properties  An array of key/value pairs representing the object
     *                      properties to include;
     *
     *  @return this for a fluent interface.
     */
    protected function _logActivity(Model_User  $user,
                                                $operation,
                                    array       $properties = array())
    {
        // Mix connection information into the properties
        $connection = Zend_Registry::get('connectionInfo');
        $properties['connection'] = $connection;

        /**************************************************
         * Store this operation in the activities table
         *
         */
        $objectType = 'user';
        $objectId   = $user->getId();

        $activity   = array(
            'userId'        => $objectId,
            'objectId'      => $objectId,
            'operation'     => $operation,
            'objectType'    => $objectType,
            'properties'    =>  Zend_Json::encode($properties),
        );

        $actor = Connexions::getUser();
        if ($actor)
        {
            $actorId = $actor->getId();
            if (($actorId > 0) && ($actorId !== $objectId))
            {
                $activity['userId'] = $actorId;
            }
        }

        /*
        Connexions::log("Service_User::_logActivity(): "
                        . "[ %s ]",
                        Connexions::varExport($activity));
        // */

        // Create a new instance and save.
        $activityInst = new Model_Activity($activity);
        $activityInst = $activityInst->save();

        /*
        Connexions::log("Service_User::_logActivity(): "
                        . "[ %s ]",
                        $activityInst->debugDump());
        // */

        return $this;
    }
}
