<?php
/** @file
 *
 *  This controller controls access to the creation of new userItems /
 *  Bookmarks and is accessed by an authenticated user via url/routes:
 *      /post
 */


class PostController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $viewer =& Zend_Registry::get('user');

        if ( (! $viewer instanceof Model_User) ||
             (! $viewer->isAuthenticated()) )
        {
            // Unauthenticated user -- Redirect to signIn
            return $this->_helper->redirector('signIn','auth');
        }

        $this->_helper->layout->setLayout('post');

        $request     = $this->getRequest();
        $postInfo = array(
            'name'          => $request->getParam('name',        null),
            'url'           => $request->getParam('url',         null),
            'description'   => $request->getParam('description', null),
            'tags'          => $request->getParam('tags',        null),
            'rating'        => $request->getParam('rating',      null),
            'isFavorite'    => $request->getParam('isFavorite',  false),
            'isPrivate'     => $request->getParam('isPrivate',   false)
        );

        $this->view->viewer   = $viewer;
        $this->view->postInfo = $postInfo;
    }
}
