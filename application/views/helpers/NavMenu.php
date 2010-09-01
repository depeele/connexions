<?php
/** @file
 *
 *  View helper to render the top navigation bar in HTML.
 *
 *  REQUIRES:
 *      application/view/scripts/nav_menu.phtml
 */
class View_Helper_NavMenu extends Zend_View_Helper_Abstract
{
    public static       $searchContexts = array(
        'same'          => array(
            'title'     => "Search this view",
            'resource'  => 'guest',
        ),
        'mybookmarks'   => array(
            'title'     => "My bookmarks",
            'resource'  => 'member',
        ),
        'mynetwork'     => array(
            'title'     => "My network's bookmarks",
            'resource'  => 'member',
        ),
        'all'           => array(
            'title'     => "Everyone's bookmarks",
            'resource'  => 'guest',
        ),
    );
    public static       $defaultContext = 'all';

    /** @brief  Initialize view variables related to rendering the
     *          navigation menu.
     *
     *  @return $this for a fluent interface.
     */
    public function navMenu()
    {
        // /*
        Connexions_Profile::checkpoint('Connexions',
                                       'View_Helper_NavMenu::begin');
        // */

        $viewer =& $this->view->viewer; //Zend_Registry::get('user');

        /*
        $searchContexts = Zend_Registry::get('config')->searchContext;
        if ($searchContexts instanceof Zend_Config)
        {
            $searchContexts = $searchContexts->toArray();
        }
        else
        {
            $searchContexts = array();
        }
        */
 
        $config = array(
            'inbox'     => null,
            'search'    => array(
                'contexts'  => self::$searchContexts,   //$searchContexts,
                'context'   => self::$defaultContext,
            ),
        );

        if ( ($viewer instanceof Model_User) && $viewer->isAuthenticated())
        {
            // See if this user has any inbox items they have not yet seen
            $lastVisit = (isset($this->view->lastVisitFor)
                            ? $this->view->lastVisitFor
                            : $viewer->lastVisitFor);

            $bookmarks = Connexions_Service::factory('Model_Bookmark')
                                        ->fetchInbox($viewer,
                                                     null,  // no extra tags
                                                     $lastVisit);
            $config['inbox'] = array(
                'lastVisit' => $lastVisit,
                'unread'    => $bookmarks->getTotalCount(),
            );
        }

        /*
        Connexions::log('View_Helper_NavMenu::initNavMenu(): '
                        . 'config[ %s ]',
                        Connexions::varExport($config));
        // */

        $this->view->inbox  = $config['inbox'];
        $this->view->search = $config['search'];

        // /*
        Connexions_Profile::checkpoint('Connexions',
                                       'View_Helper_NavMenu::end');
        // */

        return $this;
    }

    /** @brief  Determin whether or not the requested search id is presentable
     *          to the current user.
     *  @param  id      The search id (from $this->searchContexts);
     *
     *  @return true | false
     */
    public function searchAccept($id)
    {
        $res = false;
        if (isset(self::$searchContexts[$id]))
        {
            switch (self::$searchContexts[$id]['resource'])
            {
            case 'guest':
                // Publically accessible
                $res = true;
                break;

            case 'member':
                if ( ($this->view->viewer instanceof Model_User) &&
                     ($this->view->viewer->isAuthenticated()) )
                {
                    $res = true;
                }
                break;
            }
        }

        return $res;
    }
}
