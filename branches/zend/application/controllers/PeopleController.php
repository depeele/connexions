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
        $reqTags   = $request->getParam('tags',      null);

        // Pagination parameters
        $page      = $request->getParam('page',      null);

        // User parameters
        $usersPrefix      = 'users';
        $usersPerPage     = $request->getParam("{$usersPrefix}PerPage",   null);
        $usersStyle       = $request->getParam("{$usersPrefix}Style",     null);
        $usersSortBy      = $request->getParam("{$usersPrefix}SortBy",    null);
        $usersSortOrder   = $request->getParam("{$usersPrefix}SortOrder", null);
        $usersStyleCustom = $request->getParam("{$usersPrefix}StyleCustom",
                                                                          null);


        // Tag-cloud parameters
        $tagsPrefix = 'sbTags';
        $tagsPerPage        = $request->getParam("{$tagsPrefix}PerPage",  250);
        $tagsStyle          = $request->getParam("{$tagsPrefix}Style",    null);
        $tagsHighlightCount = $request->getParam("{$tagsPrefix}HighlightCount",
                                                                          null);
        $tagsSortBy         = $request->getParam("{$tagsPrefix}SortBy", 'tag');
        $tagsSortOrder      = $request->getParam("{$tagsPrefix}SortOrder",null);

        $maxTags    = $request->getParam('maxTags',   250);
        $sortBy     = $request->getParam('sortBy',    'tag');
        $sortOrder  = $request->getParam('sortOrder', null);


        /*
        Connexions::log("PeopleController:: "
                            . "tags[ ". $request->getParam('tags','') ." ], "
                            . "reqTags[ {$reqTags} ]");
        // */

        $tagInfo = new Connexions_Set_Info($reqTags, 'Model_Tag');
        if ($tagInfo->hasInvalidItems())
            $this->view->error = "Invalid tag(s) [ {$tagInfo->invalidItems} ]";


        // Retrieve the set of users
        $users     = new Model_UserSet( $tagInfo->validIds );
        $paginator = $this->_helper->Pager($users, $page, $usersPerPage);

        // Set the required view variables
        $this->view->users      = $users;
        $this->view->paginator  = $paginator;

        $this->view->viewer     = $viewer;
        $this->view->tagInfo    = $tagInfo;

        // User parameters
        $this->view->usersStyle     = $usersStyle;
        $this->view->usersMax       = $usersMax;
        $this->view->usersTop       = $usersTop;
        $this->view->usersSortBy    = $usersSortBy;
        $this->view->usersSortOrder = $usersSortOrder;

        // Tag-cloud parameters
        $this->view->tagsPrefix         = $tagsPrefix;
        $this->view->tagsStyle          = $tagsStyle;
        $this->view->tagsPerPage        = $tagsPerPage;
        $this->view->tagsHighlightCount = $tagsHighlightCount;
        $this->view->tagsSortBy         = $tagsSortBy;
        $this->view->tagsSortOrder      = $tagsSortOrder;
    }
}
