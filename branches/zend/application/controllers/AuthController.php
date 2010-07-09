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
    // Pre-defined openid endpoints: method = 'openid.<name>';
    static public   $openid_endpoints   = array(
      'google' => 'https://www.google.com/accounts/o8/id',
      'yahoo'  => 'http://open.login.yahooapis.com/openid20/www.yahoo.com/xrds'
    );

    protected   $_flashMessenger    = null;
    protected   $_redirector        = null;

    public function init()
    {
        /* Initialize action controller here */
        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
        $this->_redirector     = $this->_helper->getHelper('Redirector');
    }

    public function signinAction()
    {
        $uService = $this->service('User');
        $request  = $this->getRequest();
        $authType = null;
        if ($request->isPost())
        {
            /* This is a POST request -- Perform authentication based upon the
             * specified method
             */
            $id = null;
            list($method, $name) = explode('.',
                                           $request->getParam('method', ''));

            Connexions::log("AuthController: "
                            .   "method[ {$method} ], name[ {$name} ]");

            switch ($method)
            {
            case 'apachessl':
                $authType    = Model_UserAuth::AUTH_PKI;

                //$authAdapter = new Connexions_Auth_ApacheSsl();
                break;

            case 'openid':
                $authType = Model_UserAuth::AUTH_OPENID;
                if ((! empty($name)) && isset(self::$openid_endpoints[$name]))
                    $id = self::$openid_endpoints[$name];
                else
                    $id = $request->getParam('identity', null);

                Connexions::log("AuthController: "
                                .   "OpenId, endpoint[ {$id} ]");

                //$authAdapter = new Connexions_Auth_OpenId($id);
                break;

            case 'userpassword':
            default:
                $authType = Model_UserAuth::AUTH_PASSWORD;

                //$authAdapter = new Connexions_Auth_UserPassword();
                //$this->view->username = $authAdapter->getIdentity();
                break;
            }
        }
        else if (isset($request->openid_mode))
        {
            // This an OpenId response.
            Connexions::log("AuthController: Handle OpenId response: "
                            .   "mode [ {$request->openid_mode} ]");

            $authType    = Model_UserAuth::AUTH_OPENID;
            //$authAdapter = new Connexions_Auth_OpenId();
        }

        if ($authType === null)
        {
            // No authentication yet attempted -- present the auth form
            return $this->_showAuthenticationForm();
        }

        /*********************************************************************
         * Attempt authentication
         *
         */
        $user = $uService->authenticate($authType, $id);

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
                    break;
                }
            }
        }

        Connexions::log("AuthController::signinAction(): "
                        .   "messages[ %s ], onSuccess[ %s ]",
                        Connexions::varExport($messages),
                        $onSuccess);

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
        //$viewer = Zend_Registry::get('user');
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();

        // Redirect
        return $this->_redirector->gotoSimple('index','index');
        //return $this->_redirector->setGotoSimple('index', 'index');

        // action body
    }

    public function registerAction()
    {
        // action body
        $request = $this->getRequest();

        $user  = $request->getParam('user',      '');
        $pass  = $request->getParam('password',  '');
        $pass2 = $request->getParam('password2', '');

        $this->view->user = $user;
        $this->view->pass = $pass;

        if (@empty($user) || @empty($pass) || @empty($pass2))
        {
            // Present the registration form
        }
        else if ($pass != $pass2)
        {
            $this->view->error = "Passwords do not match.";
        }
        else
        {
            // Add this new user to the database
            $userModel = $this->service('User')
                                    ->get(array('name' => $user));
            if ( (! $userModel) || $userModel->isBacked())
            {
                $this->view->error = "User Name is already taken.";
            }
            else
            {
                // Set the password for this new user and save the record.
                $userModel->name     = $user;
                $userModel->password = $pass;

                if ($userModel->save())
                {
                    /* We've successfully registered this new user
                     *
                     * Mark this user as 'authenticated' by performing a
                     * write() of the user's name to the authentication store.
                     */
                    $auth = Zend_Auth::getInstance();
                    $auth->getStorage()->write($user);

                    // Redirect to a new user welcome.
                    return $this->_redirector->gotoSimple('index', 'welcome');
                }

                $this->view->error = "Database error";
            }
        }

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

    /** @brief  Prepare to (re)show the authentication form.  If there are any
     *          flash messages that indicate a 'returnTo' URL, forward them on.
     */
    protected function _showAuthenticationForm()
    {
        if ($this->_flashMessenger->hasMessages())
        {
            // Forward any 'onSuccess' flash message
            $messages = $this->_flashMessenger->getMessages();
            foreach ($messages as $msg)
            {
                if (preg_match('/^onSuccess:/i', $msg))
                {
                    Connexions::log(
                            "AuthController::_showAuthenticationForm(): "
                            . "forward flash message '%s'",
                            $msg);

                    $this->_flashMessenger->addMessage($msg);
                }
            }
        }

        $this->_helper->layout->setLayout('auth');
    }
}
