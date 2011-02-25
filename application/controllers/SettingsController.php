<?php
/** @file
 *
 *  This controller controls access to an authenticated Users settings and is
 *  accessed via the url/routes:
 *      /settings       [/:type     [/:cmd]]    Viewer settings
 */


class SettingsController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public  $dependencies   = array('db','layout');
    public  $contexts       = array('index' => array('partial'));


    public static   $sections   = array(
        'account'   => array(
            'title'     => 'Account',
            'script'    => 'main-account',
            'cmds'      => array(
                  array('title'     => 'Info',
                        'expanded'  => true,
                        'script'    => 'main-account-info'),
                  array('title'     => 'Credentials',
                        'async'     => true,
                        'script'    => 'main-account-credentials'),
                  array('title'     => 'API Key',
                        'script'    => 'main-account-apikey'),
              ),
        ),
        'bookmarks' => array(
            'title'     => 'Bookmarks',
            'script'    => 'main-bookmarks',
            'cmds'      => array(
                  array('title'     => 'Import',
                        'async'     => true,
                        'script'    => 'main-bookmarks-import'),
                  array('title'     => 'Export',
                        'async'     => true,
                        'script'    => 'main-bookmarks-export'),
                  /*
                  array('title'     => 'Groups',
                        'async'     => true,
                        'script'    => 'main-bookmarks-groups'),
                  // */
              ),
        ),
        'tags'      => array(
            'title'     => 'Tags',
            'cssClass'  => 'settingsTags',
            'script'    => 'main-tags',
            'cmds'      => array(
                  array('title' => 'Rename',
                        'async'     => true,
                        'script'    => 'main-tags-rename'),
                  array('title'     => 'Delete',
                        'async'     => true,
                        'script'    => 'main-tags-delete'),
                  /*
                  array('title'     => 'Groups',
                        'async'     => true,
                        'script'    => 'main-tags-groups'),
                  // */
              ),
        ),
        'people'    => array('title'   => 'People',
              'script'  => 'main-people',
              'cmds'    => array(
                  array('title'     => 'Network',
                        'async'     => true,
                        'script'    => 'main-people-network'),
                  /*
                  array('title'     => 'Groups',
                        'async'     => true,
                        'script'    => 'main-people-groups'),
                  // */
              ),
        ),
    );

    public function indexAction()
    {
        $viewer =& $this->_viewer;

        // Use the currently authenticated user
        if ( ( ! $viewer instanceof Model_User) ||
             (! $viewer->isAuthenticated()) )
        {
            // Unauthenticated user -- Redirect to signIn
            return $this->_redirectToSignIn();
        }

        $request =& $this->_request;

        $section =  $request->getParam('section', null);
        $cmd     =  $request->getParam('cmd',     null);

        /*
        Connexions::log("SettingsController::indexAction(): "
                        . "section[ %s ], cmd[ %s ]",
                        $section, $cmd);
        // */

        if ($request->isPost())
        {
            $method = "_post_{$section}_{$cmd}";

            /*
            Connexions::log("SettingsController::indexAction(): "
                            . "POST -- check method[ %s ]",
                            $method);
            // */

            if (method_exists( $this, $method ))
            {
                $this->{$method}();

                $this->layout->setLayout('partial');
                $this->view->isPartial = true;
                $this->_partials       = array($section, $cmd);

                $script = 'post-' . implode('-', $this->_partials);
                return $this->render($script);
            }
        }

        $this->view->sections = self::$sections;
        $this->view->section  = $section;
        $this->view->cmd      = $cmd;

        $this->_handleFormat('settings');
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
            // Redirect
            return $this->_forward('index');
        }

        throw new Exception('Invalid method "'. $method .'" called', 500);
    }

    /** @brief  Prepare for rendering the main view, regardless of format.
     *
     *  This will collect the variables needed to render the main view, placing
     *  them in $view->main as a configuration array.
     */
    protected function _prepareMain($htmlNamespace  = '')
    {
        parent::_prepareMain($htmlNamespace);

        if ( count($this->_partials) > 0 )
        {
            /*
            Connexions::log("SettingsController::_prepareMain(): "
                            . "section[ %s ], partials[ %s ]",
                            $this->_partials[0],
                            Connexions::varExport($this->_partials));
            // */

            switch ($this->_partials[0])
            {
            case 'account':
                $this->_prepareAccount();
                break;

            case 'bookmarks':
                $this->_prepareBookmarks();
                break;

            case 'tags':
                $this->_prepareTags();
                break;

            case 'people':
                $this->_preparePeople();
                break;
            }
        }
    }

    protected function _prepareAccount()
    {
        /*
        Connexions::log("SettingsController::_prepareAccount(): "
                        . "cmd[ %s ], partials[ %s ]",
                        $this->_partials[1],
                        Connexions::varExport($this->_partials));
        // */

        switch ($this->_partials[1])
        {
        case 'info':
        case 'apikey':
            // All information available via $this->view->viewer
            break;

        case 'credentials':
            // Retrieve all credentials for the current user.
            $this->view->credentials = $this->_viewer->getAuthenticator();
            break;
        }
    }

    protected function _prepareBookmarks()
    {
        /*
        Connexions::log("SettingsController::_prepareBookmarks(): "
                        . "cmd[ %s ], partials[ %s ]",
                        $this->_partials[1],
                        Connexions::varExport($this->_partials));
        // */

        switch ($this->_partials[1])
        {
        case 'import':
            break;

        case 'export':
            break;

        case 'groups':
            // Retrieve all bookmark groups
            break;
        }
    }

    protected function _prepareTags()
    {
        /*
        Connexions::log("SettingsController::_prepareTags(): "
                        . "cmd[ %s ], partials[ %s ]",
                        $this->_partials[1],
                        Connexions::varExport($this->_partials));
        // */

        switch ($this->_partials[1])
        {
        case 'rename':
        case 'delete':
            // Retrieve all user-related tags
            $this->view->tags = $this->_viewer->getTags();
            break;

        case 'groups':
            // Retrieve all tag groups
            break;
        }
    }

    protected function _preparePeople()
    {
        /*
        Connexions::log("SettingsController::_preparePeople(): "
                        . "cmd[ %s ], partials[ %s ]",
                        $this->_partials[1],
                        Connexions::varExport($this->_partials));
        // */

        switch ($this->_partials[1])
        {
        case 'network':
            // Retrieve the current users network
            break;

        case 'groups':
            // Retrieve all people groups
            break;
        }
    }

    /** @brief  Render the sidebar based upon the incoming request.
     *  @param  usePlaceholder      Should the rendering be performed
     *                              immediately into a placeholder?
     *                              [ true, into the 'right' placeholder ]
     *
     */
    protected function _renderSidebar($usePlaceholder = true)
    {
        // NO sidebar
    }

    /***********************************************************************
     * Request POST handlers
     *
     */
    protected function _post_account_avatar()
    {
        $config       = Zend_Registry::get('config');
        $urlBase      = $config->urls->base;
        $urlAvatar    = $config->urls->avatar;
        $urlAvatarTmp = $config->urls->avatarTmp;

        $file         = $_FILES['avatarFile'];

        $uploadDir    = realpath( APPLICATION_WEBROOT .'/'
                                  . preg_replace("#^{$urlBase}#",
                                                 '',
                                                 $urlAvatarTmp) );
        $avatarFile   = basename( $file['name']);
        $uploadFile   = $uploadDir .'/'. $avatarFile;
        $uploadUrl    = $urlAvatarTmp .'/'. $avatarFile;

        /*
        Connexions::log("SettingsController::_post_account_avatar(): "
                        .   "file[ %s ]",
                        Connexions::varExport($file));
        Connexions::log("SettingsController::_post_account_avatar(): "
                        .   "uploadDir[ %s ]",
                        $uploadDir);
        Connexions::log("SettingsController::_post_account_avatar(): "
                        .   "avatarFile[ %s ]",
                        $avatarFile);
        Connexions::log("SettingsController::_post_account_avatar(): "
                        .   "uploadFile[ %s ]",
                        $uploadFile);
        Connexions::log("SettingsController::_post_account_avatar(): "
                        .   "uploadUrl[ %s ]",
                        $uploadUrl);
        // */

        $this->view->file = $file;
        $this->view->url  = $uploadUrl;

        if (! move_uploaded_file($file['tmp_name'], $uploadFile))
        {
            $this->view->error = true;
        }
    }

    protected function _post_bookmarks_import()
    {
        $config       = Zend_Registry::get('config');
        $file         = $_FILES['bookmarkFile'];

        // /*
        Connexions::log("SettingsController::_post_bookmarks_import(): "
                        .   "file[ %s ]",
                        Connexions::varExport($file));
        // */

        $html = file_get_contents($file['tmp_name']);

        $this->view->results = $html;
    }

    protected function _post_bookmarks_export()
    {
    }
}
