<?php
/** @file
 *
 *  Authentication controller.  This handles sign-in, sign-out, and
 *  regsitration actions.
 */

class AuthController extends Zend_Controller_Action
{
    protected $_redirector  = null;
    protected $_isWelcome   = false;

    public function init()
    {
        /* Initialize action controller here */
        $this->_redirector = $this->_helper->getHelper('Redirector');
    }

    public function signinAction()
    {
        $user = Zend_Registry::get('user');
        if ($user instanceof Model_User)
        {
            if ($user->isAuthenticated())
            {
                // Authenticated!  Redirect
                return $this->_redirector->setGotoSimple('index', 'index');
            }

            // Set a 401 (unauthorized) HTTP response code
            $response = $this->getResponse();
            $response->setHttpResponseCode(401);

            $this->view->error = $user->getError();
        }

        // NOT authenticated
        $this->view->user    = $user;
        $this->view->welcome = $this->_isWelcome;
    }

    public function signoutAction()
    {
        //$user = Zend_Registry::get('user');
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();

        // Redirect
        return $this->_redirector->setGotoSimple('index', 'index');

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
            $userModel = new Model_User($user);
            if ($userModel->isValid())
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
                     * Redirect to the 'welcome' view where the user
                     * can sign-in with some additional welcome information.
                     */
                    $this->_isWelcome = true;

                    Zend_Registry::set('user', $user);

                    //return $this->_forward('signIn');
                    return $this->_helper->redirector('signIn');

                    return $this->_redirector->setGotoSimple('welcome',
                                                             'index');
                }

                $this->view->error = "Database error";
            }
        }
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
            return $this->_helper->redirector('signIn');
            //return $this->_redirector->setGotoSimple('index', 'index');
        }

        // Grab the JsonRpc helper
        $jsonRpc = $this->_helper->getHelper('JsonRpc');

        // Is there a JSONP callback specified?
        $jsonp    = trim($request->getQuery('jsonp', ''));
        if (! empty($jsonp))
            $jsonRpc->setCallback($jsonp);

        // Retrieve the desired user name.
        $userName = trim($request->getQuery('userName', ''));

        if (strlen($userName) > 2)
        {
            // Does a user exist with the given name?
            $user = new Model_User($userName);
            if ($user->isValid())
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
}
