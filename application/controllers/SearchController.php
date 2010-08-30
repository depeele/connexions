<?php
/** @file
 *
 *  This controller controls access to Search and is accessed via POST to the
 *  url/routes:
 *      /search
 *          POST parameters:
 *              owner           The owner user name;
 *              tags            Tags to limit the search;
 *              searchContext   The search context;
 *              q               The search query.
 */


class SearchController extends Zend_Controller_Action
{
    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $viewer =& Zend_Registry::get('user');

        $request = $this->getRequest();
        $owner   = $request->getParam('owner',         null);
        $tags    = $request->getParam('tags',          null);
        $context = $request->getParam('searchContext', null);
        $terms   = $request->getParam('q',             null);

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

        $this->view->owner   = $owner;
        $this->view->tags    = $tags;
        $this->view->context = $context;
        $this->view->terms   = $terms;
    }
}

