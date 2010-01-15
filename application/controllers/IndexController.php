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
        $viewer =& Zend_Registry::get('user');

        $request = $this->getRequest();
        $owner   =           $request->getParam('owner',   null);
        $reqTags = urldecode($request->getParam('tags',    null));
        $page    =           $request->getParam('page',    null);
        $perPage =           $request->getParam('perPage', null);

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
                     * If 'reqTags' wasn't spepcified, use 'owner' as 'reqTags'
                     */
                    if (empty($reqTags))
                    {
                        $reqTags  = $owner;
                        $owner    = '*';
                    }
                    else
                    {
                        // Invalid user!
                        /*
                        Connexions::log("IndexController:: Unknown User, ".
                                            "user owner as tags [ {$owner} ]");
                        // */

                        $this->view->error = "Unknown user [ {$owner} ].";
                        $owner             = '*';
                    }
                }
            }
        }

        $reqTagInfo  = null;
        $validTagIds = null;
        $validTags   = '';
        if (! @empty($reqTags))
        {
            /* Retrieve the tag identifiers for all valid tags and idientify
             * which are invalid.
             */
            $reqTagInfo = Model_Tag::ids($reqTags);
            $validTags  = @implode(',', $reqTagInfo['valid']);

            if (! @empty($reqTagInfo['invalid']))
            {
                $this->view->error = 'Invalid tag(s) [ '
                                   .    implode(', ',
                                                $reqTagInfo['invalid']) .' ]';
            }


            if (@empty($reqTagInfo['valid']))
            {
                /* NONE of the provided tags are valid.  Use a reqTagInfo array
                 * with a single, invalid tag identifier to ensure that
                 * we don't match ANY user items.
                 */
                $validTagIds = array(-1);
            }
            else
            {
                $validTagIds = array_values($reqTagInfo['valid']);
            }
        }

        /*
        Connexions::log("IndexController:: "
                            . "owner[ {$owner} ], "
                            . "tags[ {$reqTags} ], "
                            . "validTags[ {$validTags} ], "
                            . "validTagIds[ ".print_r($validTagIds,true)." ], "
                            . "reqTagInfo[ ". print_r($reqTagInfo, true) ." ]");
        // */

        $userItems = new Model_UserItemSet($validTagIds, $userIds);
        $paginator = new Zend_Paginator( $userItems );

        if ($page > 0)
            $paginator->setCurrentPageNumber($page);
        if ($perPage > 0)
            $paginator->setItemCountPerPage($perPage);

        $this->view->userItems  = $userItems;
        $this->view->paginator  = $paginator;

        $this->view->owner      = $owner;
        $this->view->viewer     = $viewer;
        $this->view->reqTags    = $reqTags;
        $this->view->reqTagInfo = $reqTagInfo;
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
