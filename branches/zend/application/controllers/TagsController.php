<?php
/** @file
 *
 *  This controller controls access to Tags and is accessed via the url/routes:
 *      /tags[/<user>]
 */

class TagsController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $viewer =& Zend_Registry::get('user');

        $request   = $this->getRequest();
        $owners    = $request->getParam('owners',    null);

        // Pagination parameters
        $page        = $request->getParam('page',           null);

        // Tag-cloud parameters
        $tagsPerPage            = $request->getParam('tagsPerPage',     250);
        $tagsStyle              = $request->getParam('tagsStyle',       null);
        $tagsHighlightCount     = $request->getParam('tagsHighlightCount',
                                                                        null);
        $tagsSortBy             = $request->getParam('tagsSortBy',      null);
        $tagsSortOrder          = $request->getParam('tagsSortOrder',   null);

        // User-cloud parameters
        $sbUsersStyle           = $request->getParam('sbUsersStyle',    null);
        $sbUsersPerPage         = $request->getParam('sbUsersPerPage',  500);
        $sbUsersHighlightCount  = $request->getParam('sbUsersHighlightCount',
                                                                        null);
        $sbUsersSortBy          = $request->getParam('sbUsersSortBy',   null);
        $sbUsersSortOrder       = $request->getParam('sbUsersSortOrder',null);


        // /*
        Connexions::log("TagsController:: "
                            . "owners[ {$owners} ], "
                            . "page[ {$page} ], "
                            . "tagsPerPage[ {$tagsPerPage} ], "
                            . "tagsStyle[ {$tagsStyle} ], "
                            . "tagsHighlightCount[ "
                            .                   "{$tagsHighlightCount} ], "
                            . "tagsSortBy[ {$tagsSortBy} ], "
                            . "tagsSortOrder[ {$tagsSortOrder} ], "
                            . "sbUsersStyle[ {$sbUsersStyle} ], "
                            . "sbUsersPerPage[ {$sbUsersPerPage} ], "
                            . "sbUsersHighlightCount[ "
                            .                   "{$sbUsersHighlightCount} ], "
                            . "sbUsersSortBy[ {$sbUsersSortBy} ], "
                            . "sbUsersSortOrder[ {$sbUsersSortOrder} ]");
        // */


        $userInfo = new Connexions_Set_Info($owners, 'Model_User');
        if ($userInfo->hasInvalidItems())
            $this->view->error =
                    "Invalid user(s) [ {$userInfo->invalidItems} ]";

        // Retrieve the set of tags
        $tagSet    = new Model_TagSet( $userInfo->validIds );
        $paginator = $this->_helper->Pager($tagSet, $page, $tagsPerPage);

        // Set the required view variables
        $this->view->tagSet     = $tagSet;
        $this->view->paginator  = $paginator;

        $this->view->viewer     = $viewer;
        $this->view->userInfo   = $userInfo;

        // Tag-cloud parameters
        $this->view->tagsStyle              = $tagsStyle;
        $this->view->tagsHighlightCount     = $tagsHighlightCount;
        $this->view->tagsSortBy             = $tagsSortBy;
        $this->view->tagsSortOrder          = $tagsSortOrder;

        // User-cloud parameters
        $this->view->sbUsersStyle           = $sbUsersStyle;
        $this->view->sbUsersPerPage         = $sbUsersPerPage;
        $this->view->sbUsersHighlightCount  = $sbUsersHighlightCount;
        $this->view->sbUsersSortBy          = $sbUsersSortBy;
        $this->view->sbUsersSortOrder       = $sbUsersSortOrder;
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

            return $this->_forward('index', 'tags', null,
                                   array('owner' => $owner));
        }

        throw new Exception('Invalid method "'. $method .'" called', 500);
    }
}
