<?php
/** @file
 *
 *  A Controller Plugin that handles user identification and authentication
 *  before any action is dispatched.
 */

class Connexions_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $req)
    {
        /* Attempt to identify and authenticate the current user:
         *  1) First, look in the session-based authentication store for an
         *     identity;
         *
         *     a) If an identity is found in the store, make sure it identifies
         *        a valid user;
         *        i)  If the identity represents a valid user, consider the
         *            user authenticated (should there be other checks??);
         *        ii) Otherwise, fall back to looking at the request.
         *
         *  2) If no valid identity was found in the session-based
         *     authentication store, see if our request contains identity and
         *     authentication information;
         *
         *     a) If identity and authentication information were found in the
         *        request, validate the identity and attempt authentication
         *        verification;
         *     b) If the identity is invalid or authentication fails, then we
         *        have no authenticated user and create an "invalid" Model_User
         *        instance to represent this fact;
         *
         *  3) Store the Model_User instance in the registry as 'user'
         */

        // 1) See if the session-based authentication store has an identity
        $auth = Zend_Auth::getInstance();
        $auth->setStorage(new Zend_Auth_Storage_Session('connexions', 'user'));

        $user   = null;
        $userId = $auth->getIdentity();
        /*
        printf ("Connexions_Controller_Plugin_Auth: ".
                    "UserId from session [ %s ]<br />\n",
                print_r($userId, true));
        // */

        if ($userId !== null)
        {
            /* 1.a) There appears to be identity information in our
             *      authentication store (session/cookie).  Does it identify a
             *      valid user?
             */
            $user = new Model_User($userId);
            if ($user->isBacked())
            {
                // 1.a.i) We have a valid user -- consider them authenticated.
                /*
                printf ("Connexions_Controller_Plugin_Auth: ".
                            "Initially Authenticated as [ %s ]<br />\n",
                        print_r($userId, true));
                // */
                $user->setAuthenticated();
            }
        }

        if ( ($user === null) || (! $user->isBacked()) )
        {
            // 2) Do we have identity and authentication information?
            $userId  = $req->getParam('user', null);
            $pass    = $req->getParam('password');

            /*
            printf ("Connexions_Controller_Plugin_Auth: ".
                        "User [ %s ], pass [ %s ]<br />\n",
                    $userId, $pass);
            // */

            /* Generate a Model_User instance based upon the incoming userId.
             *
             * Note: If $userId is null, this will generate a Model_User
             *       instance that is marked as 'invalid'.
             *
             * If we already have a non-null user and $userId is null, then we
             * already have a $user that is marked as invalid.  Otherwise, we
             * need to create a new Model_User instance with the given $userId.
             */
            if (($user === null) || ($userId !== null))
                $user = new Model_User($userId);

            /*
            printf ("Connexions_Controller_Plugin_Auth: ".
                        "User is%s backed, password is%s empty<br />\n",
                    ($user->isBacked() ? "" : " NOT"),
                    (@empty($pass)     ? "" : " NOT"));
            // */

            if ( $user->isBacked() && (! @empty($pass)) )
            {
                /* Perform authentication verification.
                 *
                 * Note: The Connexions_Auth adapter uses the
                 *       Model_User::authenticate method to verify credentials.
                 *       This ensures that the Model_User instance is properly
                 *       marked as authenticated or NOT authenticated in
                 *       addition to returning a valid Zend_Auth_Result.
                 */
                $adapter = new Connexions_Auth($user, $pass);
                $res     = $auth->authenticate($adapter);

                /*
                printf ("Connexions_Controller_Plugin_Auth: ".
                            "User authentication is%s valid, ".
                            "results:<pre>%s</pre>\n",
                        ($res->isValid() ? "" : " NOT"),
                        print_r($res, true));
                // */

                /*
                if (! $res->isValid())
                {
                    // Invalid password.
                    printf ("Connexions_Controller_Plugin_Auth: ".
                                "User [ %s ] NOT authenticated: [ %s ], ".
                                "user error[ %s ]<br />",
                            $userId, implode(', ', $res->getMessages()),
                            $user->getError());
                }
                // */
            }
            /*
            else
            {
                // Invalid user or missing password.
                printf ("Connexions_Controller_Plugin_Auth: ".
                            "User [ %s ] NOT authenticated: ".
                                "User is%s valid, is%s backed, ".
                                "password[ %s ]<br />\n",
                        $userId,
                        ($user->isValid()  ? "" : " NOT"),
                        ($user->isBacked() ? "" : " NOT"),
                        $pass);
            }
            // */
        }

        Zend_Registry::set('user', $user);

        /*
        printf ("Connexions_Controller_Plugin_Auth: ".
                    "user '%s' is%s authenticated<br />\n",
                $user, ($user->isAuthenticated() ? '':' NOT'));
        // */

        /* Finally, if a view is available from the registry (via Boostrap),
         * set the current ACL role used by navigation based upon the
         * authentication status of the current user.
         */
        if (Zend_Registry::isRegistered('view'))
        {
            $view = Zend_Registry::get('view');

            if ($user->isAuthenticated())
            {
                $view->navigation()->setRole('member');
            }

            /*
            printf ("Connexions_Controller_Plugin_Auth: ".
                        "user '%s', role '%s'<br />\n",
                    $user, $view->navigation()->getRole());
            // */
        }
    }
}
