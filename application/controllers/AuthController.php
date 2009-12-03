<?php

class AuthController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function signinAction()
    {
        $id = Zend_Registry::get('user');
        if ($id !== false)
        {
            // Redirect
        }

        // action body
        $request = $this->getRequest();

        $this->view->user = $request->getParam('user', '');
    }

    public function signoutAction()
    {
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

        if ((! @empty($pass)) && (! @empty($pass2)) &&
            ($pass != $pass2))
        {
            $this->view->error = "Passwords do not match";
        }
        else
        {
            // Add this new user to the database

            // Redirect to the 'welcome' view
        }
    }
}
