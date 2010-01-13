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
        $tags    = urldecode($request->getParam('tags',    null));
        $page    =           $request->getParam('page',    null);
        $perPage =           $request->getParam('perPage', null);

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

        $tagIds = null;
        if (! @empty($tags))
        {
            /* Retrieve the tag identifiers for all valid tags and idientify
             * which are invalid.
             */
            $tagIds = Model_Tag::ids($tags);

            if (! @empty($tagIds['invalid']))
            {
                // Remove all invalid tags from our original tag string
                foreach ($tagIds['invalid'] as $tag)
                {
                    // Remove this invalid tag from the tag string
                    $reTag = preg_replace("#[/']#", '\\.', $tag);
                    $re    = "/(^{$reTag}\\s*(,\\s*|$)|\\s*,\\s*{$reTag})/";
                    $tags = preg_replace($re, '', $tags);
                }

                $this->view->error = 'Invalid tag(s) [ '
                                   .    implode(', ',$tagIds['invalid']) .' ]';
            }

            if (@empty($tagIds['valid']))
            {
                /* NONE of the provided tags are valid.  Use a tagIds array
                 * with a single, invalid tag identifier to ensure that
                 * we don't match ANY user items.
                 */
                $tagIds['valid'] = array(-1);
            }

            $tagIds = array_values($tagIds['valid']);
        }

        $userItems = new Model_UserItemSet($tagIds['valid'],
                                           $userIds);
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
