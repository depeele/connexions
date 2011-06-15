<?php
/** @file
 *
 *  This controller handles sign-in, sign-out and registration and is accessed
 *  via the url/routes:
 *      /auth/signIn
 *      /auth/signOut
 *      /auth/register
 *      /auth/checkuser?format=json&userName=<name>
 */

class AuthController extends Connexions_Controller_Action
{
    protected   $_flashMessenger    = null;
    protected   $_redirector        = null;
    protected   $_noFormatHandling  = true;
    protected   $_noSidebar         = true;
    protected   $_api               = null;

    public function init()
    {
        /* Initialize action controller here */
        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
        $this->_redirector     = $this->_helper->getHelper('Redirector');

        $config = Zend_Registry::get('config');
        $this->_api =& $config->api;

        parent::init();
    }

    public function signinAction()
    {
        $uService =  $this->service('User');
        $request  =& $this->_request;
        $user     =  null;
        if ($request->isPost())
        {
            /* This is a POST request -- Perform authentication based upon the
             * specified method
             */
            $method = $request->getParam('method');
            $user   = $uService->authenticate( $method );

            $this->_userAuthCookie($user);
        }
        else if (isset($request->openid_mode))
        {
            // This an OpenId response.
            Connexions::log("AuthController: Handle OpenId response: "
                            .   "mode [ {$request->openid_mode} ]");

            $user = $uService->authenticate(Model_UserAuth::AUTH_OPENID);
        }

        if ($user === null)
        {
            // No authentication yet attempted -- present the auth form
            return $this->_showAuthenticationForm();
        }

        if (! $user->isAuthenticated())
        {
            /* Unsuccessful authentication -- present the authentcation
             * form
             */
            $authResult = $user->getAuthResult();
            if ($authResult)
            {
                $messages   = $authResult->getMessages();
                $messages   = (is_array($messages)
                                ? implode('; ', $authResult->getMessages())
                                : '');

                Connexions::log("AuthController: Authentication Results: "
                                .   "code[ {$authResult->getCode()} ], "
                                .   "messages[ {$messages} ], "
                                .   "identity[ {$authResult->getIdentity()} ]");

                $this->view->error = $messages;
            }
            else
            {
                $this->view->error = "Invalid authentication";
            }

            return $this->_showAuthenticationForm();
        }

        /********************************************************************
         * Authentication Success
         *
         */

        // /*
        Connexions::log("AuthController: Authentication Success: "
                        .   "id[ %s ], user: %s\n",
                        $user, $user->debugDump());
        // */

        Zend_Registry::set('user', $user);

        // See if we should re-direct
        $onSuccess = null;
        $messages  = null;

        if ($this->_flashMessenger->hasMessages())
        {
            $messages = $this->_flashMessenger->getMessages();
            foreach ($messages as $msg)
            {
                if (preg_match('/^onSuccess:(.*)$/i', $msg, $matches))
                {
                    $onSuccess = $matches[1];

                    if (preg_match('/[?&]noNav/i', $onSuccess))
                    {
                        /* The target URL specifies 'noNav' so the
                         * signin page should reflect that.
                         */
                        $this->_noNav           = true;
                        $this->view->excludeNav = true;
                    }
                    break;
                }
            }
        }

        /*
        Connexions::log("AuthController::signinAction(): "
                        .   "messages[ %s ], onSuccess[ %s ], noNav[ %s ]",
                        Connexions::varExport($messages),
                        $onSuccess,
                        Connexions::varExport($this->_noNav));
        // */

        if (empty($onSuccess))
        {
            // Re-direct to the main page
            return $this->_redirector->gotoSimple('index','index');
        }
        else
        {
            return $this->_redirector->gotoUrl($onSuccess);
        }
    }

    public function signoutAction()
    {
        $uService = $this->service('User');
        $uService->deauthenticate();

        // Set the userAuth cookie to null
        $this->_userAuthCookie();

        /*
        //$viewer = Zend_Registry::get('user');
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();
        // */

        // Redirect
        return $this->_redirector->gotoSimple('index','index');
        //return $this->_redirector->setGotoSimple('index', 'index');

        // action body
    }

    public function registerAction()
    {
        // action body
        $request = $this->getRequest();

        $user       = $request->getParam('user',      '');
        $fullName   = $request->getParam('fullName', '');
        $email      = $request->getParam('email',    '');
        $pass       = $request->getParam('password',  '');
        $pass2      = $request->getParam('password2', '');
        $includePki = Connexions::to_bool($request->getParam('includePki',
                                                             true));
        $autoSignin = $request->getParam('autoSignin', null);

        /* To create a new user we need:
         *  - a POST request;
         *  - a non-empty user;
         *  - includePki == true OR
         *      non-empty $pass and $pass2;
         */
        if ($request->isPost() &&
            (! @empty($user))  &&
            ( ($includePki == true) ||
              ((! @empty($pass)) && (! @empty($pass2))) ) )
        {
            // Gather user data.
            $res      = null;
            $userData = array(
                'name'          => $user,
                'fullName'      => $fullName,
                'email'         => $email,
                'credentials'   => array(),
            );

            if ($includePki && $this->_pki && $this->_pki['verified'])
            {
                array_push($userData['credentials'], array(
                    'type'  =>  Model_UserAuth::AUTH_PKI,
                    'value' =>  $this->_pki['subject']
                ));
            }

            if (! empty($pass))
            {
                if ($pass != $pass2)
                {
                    $res = "Passwords do not match.";
                }
                else
                {
                    array_push($userData['credentials'], array(
                        'type'  => Model_UserAuth::AUTH_PASSWORD,
                        'value' => $pass
                    ));
                }
            }

            if ($res === null)
            {
                if (! empty($userData['credentials']))
                {
                    $res = $this->_registerUser($userData);
                    if ($res === true)
                    {
                        /* SUCCESS -- redirect the newly registered
                         *            (and authenticated) user to the primary
                         *            bookmarks page
                         */
                        return $this->_redirector->gotoSimple('index',
                                                              'bookmarks');
                    }
                }
                else
                {
                    $res = "Missing credentials";
                }
            }

            // ERROR
            $this->view->error = $res;
        }

        // Include form variables we can re-use
        $this->view->user       = $user;
        $this->view->fullName   = $fullName;
        $this->view->email      = $email;
        $this->view->pass       = $pass;
        $this->view->includePki = $includePki;
        $this->view->autoSignin = $autoSignin;

        return $this->_showAuthenticationForm();
    }

    /** @brief  An AJAJ auto-complete-like callback used to check whether or
     *          not a given username is in-use.
     */
    public function checkuserAction()
    {
        $request = $this->getRequest();

        if (($this->_getParam('format', false) !== 'json') ||
            ($request->isPost()) )
        {
            return $this->_redirector->gotoSimple('signIn');
        }

        // Grab the JsonRpc helper
        $jsonRpc = $this->_helper->getHelper('JsonRpc');

        // Is there a JSONP callback specified?
        $jsonp = trim($request->getQuery('jsonp', ''));
        if (! empty($jsonp))
            $jsonRpc->setCallback($jsonp);

        // Retrieve the desired user name.
        $userName = trim($request->getQuery('userName', ''));

        if (strlen($userName) > 2)
        {
            // Does a user exist with the given name?
            $user = $this->service('User')
                                    ->find(array('name' => $userName));
            if ($user && $user->isBacked())
            {
                // This user name is taken.
                $jsonRpc->setError("User name is already taken.");
            }
            else
            {
                // User name is NOT taken (no error, just echo the name)
                $jsonRpc->setResult($userName);
            }
        }
        else
        {
            // User-name too short...
            $jsonRpc->setError("User name is too short.");
        }

        // Encode and send the response
        $jsonRpc->sendResponse();
    }

    /*************************************************************************
     * Protected Helpers
     *
     */

    /** @brief  Prepare for rendering the main view, regardless of format.
     *
     *  This will collect the variables needed to render the main view, placing
     *  them in $view->main as a configuration array.
     */
    protected function _prepare_main()
    {
        //Connexions::log("AuthController::_prepare_main():");

        parent::_prepare_main();

        $request  =& $this->_request;

        /* Allow 'closeAction', 'onSuccess', and/or 'noNav' to be specified in
         * the request.
         */
        $this->view->closeAction = trim($request->getParam('closeAction',
                                                           'back'));
        $this->view->onSuccess   =
            trim($request->getParam('onSuccess', $this->view->closeAction));
        $this->_noNav            =
            Connexions::to_bool($request->getParam('noNav', false));
        $this->view->excludeNav  = $this->_noNav;
        $this->view->autoSignin  = $request->getParam('autoSignin', null);

        /*
        Connexions::log("AuthController::_prepare_main(): "
                        . "closeAction[ %s ], onSuccess[ %s ], noNav[ %s ]",
                        $this->view->closeAction,
                        $this->view->onSuccess,
                        Connexions::varExport($this->view->noNav));
        // */
    }

    /** @brief  Update the cookie used to signal the client side to user
     *          authentication changes.
     *  @param  user    The (new) user.
     */
    protected function _userAuthCookie($user = null)
    {
        $authCookie = $this->_api->authCookie;

        $expires = time() + (60 * 60 * 24 * 365);
        if ($user && $user->isAuthenticated())
        {
            // Set the authCookie to identify the new user.
            $cookieVal = $user->__toString();
        }
        else
        {
            // Set the authCookie to null to indicate NO user.
            $cookieVal = null;
        }

        // /*
        Connexions::log("AuthController::_userAuthCookie(): "
                        .   "authCookie[ %s ], value[ %s ]",
                        Connexions::varExport($authCookie),
                        Connexions::varExport($cookieVal));

        // */

        setcookie( $authCookie,
                   $cookieVal,
                   $expires,
                   $this->_rootUrl .'/',
                   '',  //$this->_connection['domain'],
                   $this->_connection['https']);
    }

    /** @brief  Register a new user.
     *  @param  user        Incoming user data of the form:
     *                          {name:      userName,
     *                           fullName:  fullName,
     *                           email:     email,
     *                           credentials:   [
     *                              {type:  'password | pki',
     *                               value: credential value},
     *                              ...
     *                           ]}
     *
     *  @return true on success, string error message on error.
     */
    protected function _registerUser($user)
    {
        // /*
        Connexions::log("AuthController::_registerUser(): user[ %s ]",
                        Connexions::varExport($user));
        // */

        // Add this new user to the database
        $userModel = $this->service('User')
                                ->get(array('name' => $user['name']));
        if ( (! $userModel) || $userModel->isBacked())
        {
            // /*
            Connexions::log("AuthController::_registerUser(): "
                            . "Error retrieving userModel for user[ %s ]",
                            $user['name']);
            // */

            return "User Name is already taken.";
        }

        // Set the user name and save the user mode.
        $userModel->name = $user['name'];
        if (! empty($user['fullName']))
        {
            $userModel->fullName = $user['fullName'];
        }
        if (! empty($user['email']))
        {
            $userModel->email = $user['email'];
        }

        /*
        Connexions::log("AuthController::_registerUser(): user[ %s ]",
                        $user['name']);
        // */

        $userModel = $userModel->save();
        if (! $userModel)
        {
            // /*
            Connexions::log("AuthController::_registerUser(): "
                            .   "Cannot create new user for user[ %s ]",
                            $user['name']);
            // */

            return "Cannot create new user";
        }

        /*
        Connexions::log("AuthController::_registerUser(): "
                        .   "New user created[ %s ]",
                        $userModel->debugDump());
        // */


        // Now, add credentials for this user.
        $errors   = array();
        $authCred = null;
        foreach ($user['credentials'] as $credential)
        {
            $userAuth   = null;
            try {
                $userAuth = $userModel->addAuthenticator($credential['value'],
                                                         $credential['type']);

                if ($userAuth === null)
                {
                    array_push($errors,
                                "Cannot add {$credential['type']} credential");
                }
                else if ($authCred === null)
                {
                    $authCred = $credential;
                }
            } catch (Exception $e) {
                Connexions::log("AuthController::_registerUser(): "
                                .   "ERROR adding credential[ %s ]",
                                $e->getMessage());
                array_push($errors,
                            "Cannot add {$credential['type']} credential: "
                                . $e->getMessage());
            }

            /*
            Connexions::log("AuthController::_registerUser(): "
                            .   "credential[ %s ] %sadded",
                            Connexions::varExport($credential),
                            ($userAuth !== null
                                ? ''
                                : 'NOT '));
            // */
        }

        if (! empty($errors))
        {
            // /*
            Connexions::log("AuthController::_registerUser(): "
                            .   "error(s) [ %s ]",
                            Connexions::varExport($errors));
            // */

            $userModel->delete();

            return implode('; ', $errors);
        }

        /*
        Connexions::log("AuthController::_registerUser(): "
                        .   "perform an initial authentication for [ %s ] "
                        .   "using credential[ %s ]",
                        $userModel,
                        Connexions::varExport($authCred));
        // */

        /* We've successfully registered this new user
         *
         * Perform an initial authentication with the first credential.
         */
        $uService  = $this->service('User');
        $userModel = $uService->authenticate( $authCred['type'],
                                              $authCred['value'],
                                              $userModel->id );

        // /*
        Connexions::log("AuthController::_registerUser(): "
                        .   "authentication %s",
                        ($userModel->isAuthenticated()
                            ? 'success'
                            : 'FAILURE'));
        // */

        $this->_userAuthCookie($userModel);

        return true;
    }

    /** @brief  Prepare to (re)show the authentication form.  If there are any
     *          flash messages that indicate a 'returnTo' URL, forward them on.
     */
    protected function _showAuthenticationForm()
    {
        $this->_prepare_main();

        if ($this->_flashMessenger->hasMessages())
        {
            // Forward any 'onSuccess' flash message
            $messages = $this->_flashMessenger->getMessages();
            foreach ($messages as $msg)
            {
                if (preg_match('/^onSuccess:/i', $msg))
                {
                    // /*
                    Connexions::log(
                            "AuthController::_showAuthenticationForm(): "
                            . "forward flash message '%s'",
                            $msg);
                    // */

                    $this->_flashMessenger->addMessage($msg);

                    if (preg_match('/[\?\&]noNav/i', $msg))
                    {
                        /* The target URL specifies 'noNav' so the
                         * signin page should reflect that.
                         */
                        $this->_noNav           = true;
                        $this->view->excludeNav = true;

                        // /*
                        Connexions::log(
                                "AuthController::_showAuthenticationForm(): "
                                . "respect 'noNav'");
                        // */
                    }
                }
            }
        }

        $this->_helper->layout->setLayout('auth');
    }
}
