<?php

class NetworkController extends Zend_Controller_Action
{
    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $viewer =& Zend_Registry::get('user');

        $request = $this->getRequest();
        $owner   = $request->getParam('user', null);

        if ($owner === 'mine')
        {
            // No user specified -- use the currently authenticated user
            $owner =& $viewer;
            if ( ( ! $owner instanceof Model_User) ||
                 (! $owner->isAuthenticated()) )
            {
                // Unauthenticated user -- Redirect to signIn
                return $this->_helper->redirector('signIn','auth');
            }
        }

        if (! $owner instanceof Model_User)
        {
            if (@empty($owner))
                $owner = '*';
            else
            {
                // Is this a valid user?
                $owner = new Model_User($owner);

                if (! $owner->isBacked())
                {
                    $this->view->error = 'Unknown user.';
                }
            }
        }

        $this->view->owner  = $owner;
        $this->view->viewer = $viewer;
    }

    /** @brief Redirect all other actions to 'index'
     *  @param  method      The target method.
     *  @param  args        Incoming arguments.
     *
     */
    public function __call($method, $args)
    {
        if (substr($method, -6) == 'Action')
        {
            $user = substr($method, 0, -6);

            return $this->_forward('index', 'network', null,
                                   array('user' => $user));
        }

        throw new Exception('Invalid method "'. $method .'" called', 500);
    }
}
