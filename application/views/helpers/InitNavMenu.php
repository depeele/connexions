<?php
/** @file
 *
 *  View helper to render the top navigation bar in HTML.
 *
 *  REQUIRES:
 *      application/view/scripts/nav_menu.phtml
 */
class View_Helper_InitNavMenu extends Zend_View_Helper_Abstract
{
    public static       $searchContexts = array(
        'same'          => "Search these bookmarks",
        'mybookmarks'   => "My bookmarks",
        'mynetwork'     => "My network's bookmarks",
        'all'           => "Everyone's bookmarks",
    );
    public static       $defaultContext = 'all';

    /** @brief  Initialize view variables related to rendering the
     *          navigation menu.
     *
     *  @return $this for a fluent interface.
     */
    public function initNavMenu()
    {
        // /*
        Connexions_Profile::checkpoint('Connexions',
                                       'View_Helper_InitNavMenu::begin');
        // */

        $viewer =& $this->view->viewer; //Zend_Registry::get('user');

        $searchContexts = Zend_Registry::get('config')->searchContext;
        if ($searchContexts instanceof Zend_Config)
        {
            $searchContexts = $searchContexts->toArray();
        }
        else
        {
            $searchContexts = array();
        }
 
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
        Connexions::log('View_Helper_InitNavMenu::initNavMenu(): '
                        . 'config[ %s ]',
                        Connexions::varExport($config));
        // */

        $this->view->inbox  = $config['inbox'];
        $this->view->search = $config['search'];

        // /*
        Connexions_Profile::checkpoint('Connexions',
                                       'View_Helper_InitNavMenu::end');
        // */

        return $this;
    }
}
