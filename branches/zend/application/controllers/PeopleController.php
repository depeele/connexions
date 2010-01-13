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
        $viewer  =& Zend_Registry::get('user');

        $request = $this->getRequest();
        $tags    = urldecode($request->getParam('tags',    null));
        $page    =           $request->getParam('page',    null);
        $perPage =           $request->getParam('perPage', null);

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

        $users     = new Model_UserSet( $tagIds );
        $paginator = new Zend_Paginator( $users );

        if ($page > 0)
            $paginator->setCurrentPageNumber($page);
        if ($perPage > 0)
            $paginator->setItemCountPerPage($perPage);

        $this->view->paginator = $paginator;

        $this->view->viewer    = $viewer;
        $this->view->users     = $users;
        $this->view->tags      = $tags;
    }
}
