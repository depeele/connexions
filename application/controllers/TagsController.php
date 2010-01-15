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
        $page      = $request->getParam('page',      null);
        $perPage   = $request->getParam('perPage',   null);

        // Tag-cloud parameters
        $maxTags   = -1;    // ALL  $request->getParam('maxTags',   null);
        $sortBy    = $request->getParam('sortBy',    null);
        $sortOrder = $request->getParam('sortOrder', null);

        // /*
        Connexions::log("TagController:: "
                            . "owners[ {$owners} ], "
                            . "page[ {$page} ], "
                            . "perPage[ {$perPage} ], "
                            . "maxTags[ {$maxTags} ], "
                            . "sortBy[ {$sortBy} ], "
                            . "sortOrder[ {$sortOrder} ]");
        // */


        $userInfo = (Object)(array( 'validIds' => null ));
        /*
        $userInfo = new Connexions_UserInfo($owners);
        if ($userInfo->hasInvalidUsers())
            $this->view->error =
                    "Invalid user(s) [ {$userInfo->invalidUsers} ]";
        */

        // Retrieve the set of tags
        $tagSet    = new Model_TagSet( $userInfo->validIds );

        $paginator = null;
        /*
        $paginator = new Zend_Paginator( $tagSet );

        // Apply the pagination parameters
        if ($page > 0)
            $paginator->setCurrentPageNumber($page);
        if ($perPage > 0)
            $paginator->setItemCountPerPage($perPage);
        */


        // Set the required view variables
        $this->view->tagSet     = $tagSet;
        $this->view->paginator  = $paginator;

        $this->view->viewer     = $viewer;
        $this->view->userInfo   = $userInfo;

        // Tag-cloud parameters
        $this->view->maxTags    = $maxTags;
        $this->view->sortBy     = $sortBy;
        $this->view->sortOrder  = $sortOrder;
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
