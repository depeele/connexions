<?php
/** @file
 *
 *  This controller controls access to UserItems / Bookmarks and is accessed
 *  via the url/routes:
 *      /[<user>[/<tag list>]]
 */

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $viewer   =& Zend_Registry::get('user');

        $request   = $this->getRequest();
        $owner     = $request->getParam('owner',   null);
        $reqTags   = $request->getParam('tags',    null);

        // Pagination parameters
        $page      = $request->getParam('page',    null);
        $perPage   = $request->getParam('perPage', null);

        // Tag-cloud parameters
        $maxTags   = $request->getParam('maxTags',   null);
        $sortBy    = $request->getParam('sortBy',    null);
        $sortOrder = $request->getParam('sortOrder', null);

        /*
        Connexions::log("IndexController:: "
                            . "owner[ {$owner} ], "
                            . "tags[ ". $request->getParam('tags','') ." ], "
                            . "reqTags[ {$reqTags} ]");
        // */

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
                $ownerInst = Model_User::find(array('name' => $owner));
                if ($ownerInst->isBacked())
                {
                    /*
                    Connexions::log("IndexController:: Valid ".
                                            "owner[ {$ownerInst->name} ]");
                    // */

                    $owner   =& $ownerInst;
                    $userIds =  array($owner->userId);
                }
                else
                {
                    /* NOT a valid user.
                     *
                     * If 'tags' wasn't spepcified, use 'owner' as 'tags'
                     */
                    if (empty($reqTags))
                    {
                        /*
                        Connexions::log("IndexController:: "
                                            . "Unknown User and no tags; "
                                            . "use owner as tags [ {$owner} ] "
                                            . "and set owner to '*'");
                        // */
                        $reqTags  = $owner;
                        $owner    = '*';
                    }
                    else
                    {
                        // Invalid user!
                        /*
                        Connexions::log("IndexController:: "
                                            . "Unknown User with tags; "
                                            . "set owner to '*'");
                        // */

                        $this->view->error = "Unknown user [ {$owner} ].";
                        $owner             = '*';
                    }
                }
            }
        }

        $tagInfo = new Connexions_TagInfo($reqTags);
        if ($tagInfo->hasInvalidTags())
            $this->view->error = "Invalid tag(s) [ {$tagInfo->invalidTags} ]";

        $userItems = new Model_UserItemSet($tagInfo->validIds, $userIds);
        $paginator = new Zend_Paginator( $userItems );

        // Apply the pagination parameters
        if ($page > 0)
            $paginator->setCurrentPageNumber($page);
        if ($perPage > 0)
            $paginator->setItemCountPerPage($perPage);


        // Set the required view variables
        $this->view->userItems  = $userItems;
        $this->view->paginator  = $paginator;

        $this->view->owner      = $owner;
        $this->view->viewer     = $viewer;
        $this->view->tagInfo    = $tagInfo;

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

            /*
            $request = $this->getRequest();

            Connexions::log("IndexController::__call({$method}): "
                                           . "owner[ {$owner} ], "
                                           . "parameters[ "
                                           .    $request->getParam('tags','')
                                           .        " ]");
            // */

            return $this->_forward('index', 'index', null,
                                   array('owner' => $owner));
        }

        throw new Exception('Invalid method "'. $method .'" called', 500);
    }
}
