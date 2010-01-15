<?php
/** @file
 *
 *  This controller controls access to Users / People and is accessed via the
 *  url/routes:
 *      /people[/:tags]
 */

class PeopleController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body -- present all people
        $viewer    =& Zend_Registry::get('user');

        $request   = $this->getRequest();
        $reqTags   = $request->getParam('tags',    null);

        // Pagination parameters
        $page      = $request->getParam('page',    null);
        $perPage   = $request->getParam('perPage', null);

        // Tag-cloud parameters
        $maxTags   = $request->getParam('maxTags',   null);
        $sortBy    = $request->getParam('sortBy',    null);
        $sortOrder = $request->getParam('sortOrder', null);

        /*
        Connexions::log("PeopleController:: "
                            . "tags[ ". $request->getParam('tags','') ." ], "
                            . "reqTags[ {$reqTags} ]");
        // */

        $tagInfo = new Connexions_TagInfo($reqTags);
        if ($tagInfo->hasInvalidTags())
            $this->view->error = "Invalid tag(s) [ {$tagInfo->invalidTags} ]";

        $users     = new Model_UserSet( $tagInfo->validIds );
        $paginator = new Zend_Paginator( $users );

        // Apply the pagination parameters
        if ($page > 0)
            $paginator->setCurrentPageNumber($page);
        if ($perPage > 0)
            $paginator->setItemCountPerPage($perPage);


        // Set the required view variables
        $this->view->users      = $users;
        $this->view->paginator  = $paginator;

        $this->view->viewer     = $viewer;
        $this->view->tagInfo    = $tagInfo;

        // Tag-cloud parameters
        $this->view->maxTags    = $maxTags;
        $this->view->sortBy     = $sortBy;
        $this->view->sortOrder  = $sortOrder;
    }
}
