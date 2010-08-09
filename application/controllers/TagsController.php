<?php
/** @file
 *
 *  This controller controls access to Tags and is accessed via the url/routes:
 *      /tags[/<user>]
 */

class TagsController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public  $dependencies = array('db','layout');

    protected   $_url       = null;
    protected   $_users     = null;

    public      $contexts   = array(
                                'index' => array('partial', 'json',
                                                 'rss',     'atom'),
                              );

    /** @brief  Index/Get/Read/View action.
     *
     *  Retrieve a set of Tags based upon the requested 'users'.
     *
     *  Once retrieved, perform further setup based upon the current
     *  context/format.
     */
    public function indexAction()
    {
        Connexions::log("TagsController::indexAction(): - start");

        $request  =& $this->_request;

        /***************************************************************
         * Process the requested 'users'/'owners'
         *
         */
        $reqUsers = $request->getParam('owners', null);

        // Parse the incoming request users / owners
        $this->_users = $this->service('User')->csList2set($reqUsers);

        /***************************************************************
         * We now have a set of valid 'users' ($this->_users).
         *
         * Adjust the URL to reflect the validated 'users'
         */
        $this->_url = $request->getBasePath()
                    . '/tags'
                    . '/' .(count($this->_users) > 0
                            ? $this->_users .'/'
                            : '');

        /***************************************************************
         * Set the view variables required for all views/layouts.
         *
         */
        $this->view->url    = $this->_url;
        $this->view->viewer = $this->_viewer;

        $this->view->users  = $this->_users;

        // Handle this request based on the current context / format
        $this->_handleFormat('tags');

        Connexions::log("TagsController::indexAction(): - complete");
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

    /*************************************************************************
     * Protected Helpers
     *
     */

    /** @brief  Prepare for rendering the main view, regardless of format.
     *
     *  This will collect the variables needed to render the main view, placing
     *  them in $view->main as a configuration array.
     */
    protected function _prepareMain($htmlNamespace  = '')
    {
        parent::_prepareMain($htmlNamespace);

        $extra = array(
            'users' => $this->_users,
        );
        $config = array_merge($this->view->main, $extra);

        // Defaults
        if ( ($config['perPage'] = (int)$config['perPage']) < 1)
            $config['perPage'] = 250;

        if ( ($config['page'] = (int)$config['page']) < 1)
            $config['page'] = 1;

        if ((empty($config['sortBy'])) || ($config['sortBy'] === 'title'))
            $config['sortBy'] = 'tag';

        if (empty($config['sortOrder']))
            $config['sortOrder'] = Connexions_Service::SORT_DIR_ASC;

        if (empty($config['displayStyle']))
            $config['displayStyle'] = View_Helper_HtmlItemCloud::STYLE_CLOUD;

        if (empty($config['highlightCount']))
            $config['highlightCount'] = 0;

        // Retrieve the set of tags to be presented.
        $count      = $config['perPage'];
        $offset     = ($config['page'] - 1) * $count;
        $fetchOrder = $config['sortBy'] .' '. $config['sortOrder'];

        Connexions::log("TagsController::_prepareMain(): "
                        . "offset[ %d ], count[ %d ], order[ %s ]",
                        $count, $offset, $fetchOrder);
        $config['tags'] = Connexions_Service::factory('Model_Tag')
                                    ->fetchByUsers($this->_users,
                                                   $fetchOrder,
                                                   $count,
                                                   $offset);

        $paginator   =  new Zend_Paginator($config['tags']
                                                ->getPaginatorAdapter());
        $paginator->setItemCountPerPage( $config['perPage'] );
        $paginator->setCurrentPageNumber($config['page'] );

        $config['paginator'] = $paginator;

        $this->view->main = $config;

        /*
        Connexions::log("TagsController::_prepareMain(): "
                        .   "main[ %s ]",
                        Connexions::varExport($this->view->main));
        // */
    }

    /** @brief  Prepare for rendering the sidebar view.
     *  @param  async   Should we setup to do an asynchronous render
     *                  (i.e. tab callbacks will request tab pane contents when 
     *                        needed)?
     *
     *  This will collect the variables needed to render the sidebar view,
     *  placing them in $view->sidebar as a configuration array.
     *
     *  Note: The main index view script
     *        (application/views/scripts/tags/index.phtml) will also add
     *        sidebar-related rendering information to the sidbar helper.  In
     *        particular, it will notify the sidbar helper of the items that
     *        are being presented in the main view.
     */
    protected function _prepareSidebar($async = false)
    {
        // Our tags sidebar MAY need main-view variables set...
        if (! isset($this->view->main))
        {
            $this->_prepareMain();
        }

        parent::_prepareSidebar($async);

        $extra = array(
            'users' => $this->_users,
        );
        $this->view->sidebar = array_merge($this->view->sidebar, $extra);

        /*
        Connexions::log("TagsController::_prepareSidebar(): "
                        .   "sidebar[ %s ]",
                        Connexions::varExport($this->view->sidebar));
        // */
    }
}
