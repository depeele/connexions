<?php
/** @file
 *
 *  This controller controls access to a Users Network and is accessed via the
 *  url/routes:
 *      /network[/<user>]
 */


class NetworkController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $viewer =& Zend_Registry::get('user');

        $request = $this->getRequest();
        $owner   = $request->getParam('owner', null);
        $tags    = $request->getParam('tags',  null);

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
        $this->view->tags   = $tags;
    }
}
