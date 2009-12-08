<?php
/** @file
 *
 *  Authentication controller.  This handles sign-in, sign-out, and
 *  regsitration actions.
 */

class AuthController extends Zend_Controller_Action
{
    protected $_redirector  = null;

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
        $this->view->user  = $user;
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
            $this->view->error = "Passwords do not match";
        }
        else
        {
            // Add this new user to the database

            // Redirect to the 'welcome' view
            return $this->_redirector->setGotoSimple('welcome', 'index');
        }
    }
}
