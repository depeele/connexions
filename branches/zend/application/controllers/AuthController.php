<?php
/** @file
 *
 *  This controller handles sigin-in, sign-out and registration and is accessed
 *  via the url/routes:
 *      /auth/signIn
 *      /auth/signOut
 *      /auth/register
 *      /auth/checkuser?format=json&userName=<name>
 */

class AuthController extends Zend_Controller_Action
{
    //protected $_redirector  = null;

    public function init()
    {
        /* Initialize action controller here */
        //$this->_redirector = $this->_helper->getHelper('Redirector');
    }

    public function signinAction()
    {
        $viewer = Zend_Registry::get('user');
        if ($viewer instanceof Model_User)
        {
            if ($viewer->isAuthenticated())
            {
                // Authenticated!  Redirect
                return $this->_helper->redirector('index','index');
                //return $this->_redirector->setGotoSimple('index', 'index');
            }

            // Set a 401 (unauthorized) HTTP response code
            $response = $this->getResponse();
            $response->setHttpResponseCode(401);

            $this->view->error = $viewer->getError();
        }

        //$request   = $this->getRequest();
        $this->_helper->layout->setLayout('auth');

        // NOT authenticated
        $this->view->user    = $viewer;
    }

    public function signoutAction()
    {
        //$viewer = Zend_Registry::get('user');
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();

        // Redirect
        return $this->_helper->redirector('index','index');
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
            $userModel = new Model_User($user);
            if ($userModel->isBacked())
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
                    return $this->_helper->redirector('index', 'welcome');
                }

                $this->view->error = "Database error";
            }
        }

        $this->_helper->layout->setLayout('auth');
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
            if ($user->isBacked())
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
