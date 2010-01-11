<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $viewer =& Zend_Registry::get('user');

        $request = $this->getRequest();
        $owner   = $request->getParam('owner',   null);
        $tags    = $request->getParam('tags',    null);
        $page    = $request->getParam('page',    null);
        $perPage = $request->getParam('perPage', null);

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

        $userIds = null;
        if (! $owner instanceof Model_User)
        {
            if (@empty($owner))
                $owner = '*';
            else
            {
                // Is this a valid user?
                $owner = Model_User::find(array('name' => $owner));

                if ($owner->isBacked())
                {
                    $userIds = array($owner->userId);
                }
                else
                {
                    $this->view->error = 'Unknown user.';
                }
            }
        }

        $tagIds    = (! @empty($tags)
                        ? Model_Tag::ids($tags)
                        : array());
        $userItems = new Model_UserItemSet($tagIds, $userIds);

        $paginator = new Zend_Paginator( $userItems );

        if ($page > 0)
            $paginator->setCurrentPageNumber($page);
        if ($perPage > 0)
            $paginator->setItemCountPerPage($perPage);

        $this->view->userItems = $userItems;

        $this->view->paginator = $paginator;

        $this->view->owner     = $owner;
        $this->view->viewer    = $viewer;
        $this->view->tags      = $tags;
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
            $owner = substr($method, 0, -6);

            return $this->_forward('index', 'index', null,
                                   array('owner' => $owner));
        }

        throw new Exception('Invalid method "'. $method .'" called', 500);
    }
}
