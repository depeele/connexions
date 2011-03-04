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
                        //'async'     => true,
                        'expanded'  => true,
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

        /*
        Connexions::log("SettingsController::indexAction(): "
                        . "section[ %s ], cmd[ %s ]",
                        $section, $cmd);
        // */

        $this->view->sections = self::$sections;
        $this->view->section  = $request->getParam('section', null);
        $this->view->cmd      = $request->getParam('cmd',     null);

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
            /*
            // Retrieve all user-related tags
            $order = array('tag '.       Connexions_Service::SORT_DIR_ASC,
                           'itemCount '. Connexions_Service::SORT_DIR_DESC);
            $this->view->tags = $this->_viewer->getTags($order);
             */
            /* Prepare to present a tag list or cloud
             *  (mirrors TagsController::_prepareMain)
             */
            $extra = array(
                'panePartial'   => 'main-'. implode('-', $this->_partials),
                'showRelation'  => false,
                'itemType'      => View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM,
                'itemBaseUrl'   => $this->view->baseUrl('/bookmarks/'),
                'weightName'    => 'userItemCount',
                'weightTitle'   => 'Bookmarks with this tag',
                'titleTitle'    => 'Tag',
            );
            $config = array_merge($this->view->main, $extra);

            // Defaults
            if ( ($config['perPage'] = (int)$config['perPage']) < 1)
                $config['perPage'] = 250;
            if ( ($config['page'] = (int)$config['page']) < 1)
                $config['page'] = 1;
            if ( empty($config['sortBy']) || ($config['sortBy'] === 'title') )
                $config['sortBy'] = 'tag';
            if ( empty($config['sortOrder']) )
                $config['sortOrder'] = Connexions_Service::SORT_DIR_ASC;
            if ( empty($config['displayStyle']) )
                $config['displayStyle'] = View_Helper_HtmlItemCloud::STYLE_LIST;
            if ( empty($config['highlightCount']) )
                $config['highlightCount'] = 0;

            // Retrieve the set of tags to be presented
            $count      = $config['perPage'];
            $offset     = ($config['page'] - 1) * $count;
            $fetchOrder = $config['sortBy'] .' '. $config['sortOrder'];

            // /*
            Connexions::log("SettingsController::_prepareTags(): "
                            . "offset[ %d ], count[ %d ], order[ %s ]",
                            $offset, $count, $fetchOrder);
            // */

            $config['items'] = $this->_viewer->getTags($fetchOrder,
                                                       $count,
                                                       $offset);

            $paginator = new Zend_Paginator($config['items']
                                                ->getPaginatorAdapter());
            $paginator->setItemCountPerPage(  $config['perPage'] );
            $paginator->setCurrentPageNumber( $config['page'] );

            $config['paginator']        = $paginator;
            $config['currentSortBy']    = $config['sortBy'];
            $config['currentSortOrder'] = $config['sortOrder'];

            $this->view->main = $config;
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
     * Triggered via
     *  Connexions_Controller_Action::_handleFormat()
     *      Connexions_Controller_Action::_renderPost()
     *          Connexions_Controller_Action::_preparePost()
     *
     *  iff 'format=partial&part=post-*' AND the request method is 'POST'
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
            $this->view->error = "Cannot move temporary file";
        }
    }

    protected function _post_bookmarks_import()
    {
        /*
        Connexions::log("SettingsController::_post_bookmarks_import():");
        // */

        $file       = $_FILES['bookmarkFile'];
        $tags       = $this->_request->getParam('tags',       null);
        $visibility = strtolower($this->_request->getParam('visibility',
                                                                'private'));
        $conflict   = strtolower($this->_request->getParam('conflict',
                                                                'ignore'));
        $test       = strtolower($this->_request->getParam('test',
                                                                'no'));

        // Normalize the tag list
        $tags = implode(',', preg_split('#\s*[/,+]\s*#', $tags));

        // Normalize visibility, conflict, and test
        if ( ($visibility !== 'public') && ($visibility !== 'private') )
        {
            $visibility = 'private';
        }
        if ( ($conflict !== 'replace') && ($conflict !== 'ignore') )
        {
            $conflict = 'ignore';
        }
        if ( ($test !== 'yes') && ($test !== 'no') )
        {
            $test = 'no';
        }


        /*
        Connexions::log("SettingsController::_post_bookmarks_import(): "
                        .   "file[ %s ]",
                        Connexions::varExport($file));
        Connexions::log("SettingsController::_post_bookmarks_import(): "
                        .   "tags[ %s ], visibility[ %s ], "
                        .   "conflict[ %s ], test[ %s ]",
                        $tags, $visibility,
                        $conflict, $test);
        // */

        /********************************************************************
         * Establish the bookmark import processing state.
         *
         */
        $state = array(
            'visibility'    => $visibility, // public  | private
            'conflict'      => $conflict,   // replace | ignore
            'test'          => $test,       // yes     | no

            'fileSize'      => 0,           // File size (in bytes)
            'filePos'       => 0,           // Current offset in the file.

            'lineNum'       => 0,           // Current line in the import file
            'firstLine'     => 0,           /* The first line of the current
                                             * bookmark.
                                             */
            'lastLine'      => 0,           /* The last line of the current
                                             * bookmark.
                                             */
            'level'         => 0,           // Current folder level

            'inDD'          => false,       /* Are we currently in a 'DD' /
                                             * bookmark description?
                                             */

            /* The current stack of tags:
             *  0  == user requested tags to add to all items
             *  1+ == one level per folder
             */
            'tagStack'      => array($tags),

            'bookmark'      => null,        // Current bookmark

            'numBookmarks'  => 0,   // Total bookmark entries found
            'numFolders'    => 0,

            'numImported'   => 0,   // Total bookmarks imported
            'numIgnored'    => 0,   /* Number of bookmarks ignored because they
                                     * already existed for this user.
                                     * (iff 'conflict' === 'ignore')
                                     */
            'numErrors'     => 0,   // Total number of errors.
            'numWarnings'   => 0,   // Total number of warnings.

            'numNew'        => 0,   /* Of 'numImported', how many were
                                     * completely new
                                     */
            'numUpdated'    => 0,   /* Of 'numImported', how many were
                                     * existing bookmarks that were updated
                                     * (iff 'conflict' == 'replace')
                                     */

            'importBegin'   => 'post-bookmarks-import-stream-begin',
            'importError'   => 'post-bookmarks-import-stream-error',
                             //$this->getViewScript('post-bookmarks-error'),
            'importWarning' => 'post-bookmarks-import-stream-warning',
                             //$this->getViewScript('post-bookmarks-warning'),
            'importBookmark'=> 'post-bookmarks-import-stream-bookmark',
                             //$this->getViewScript('post-bookmarks-bookmark'),

            /*
            'errors'        => array(),
            'warnings'      => array(),
            'bookmarks'     => array(),
            // */
        );

        /* Begin the import
         *  (terminated with the final 'post-bookmarks-import.phtml').
         */
        $this->view->state = $state;
        $this->render($state['importBegin']);


        if ($this->_streaming === true)
        {
            /**************************************************************
             * This is a "streaming" request so begin sending the response
             * now.
             *
             * Bootstrap::_controllerRequest() has already disableed output
             * buffering and layouts.
             */
            $this->_response = $this->getResponse();
            $this->_response->sendResponse();
            $this->_response->clearBody();
        }


        // Open the uploaded bookmarks file
        $fh = fopen($file['tmp_name'], 'r');
        if ($fh === false)
        {
            $this->view->error = 'Cannot access the uploaded file';
            return;
        }

        // Retrieve the file size.
        $info = fstat($fh);
        $state['fileSize'] = $info['size'];

        /**************************************************************
         * Walk through the import file one line at a time.
         *
         */
        $this->_maxTime = ini_get('max_execution_time');
        while (! feof($fh))
        {
            $line = trim(fgets($fh));
            if ($line === false)
            {
                $error = "Read error on line {$state['lineNum']}";
                $this->_bookmarksImport_error($state, $error);

                continue;
            }
            $state['filePos'] = ftell($fh);
            $state['lineNum']++;
            $state['line'] =& $line;

            if (empty($line))
            {
                continue;
            }

            /**********************************************
             * A typical bookmark entry:
             *
             *  <DT><A     HREF="..."
             *         ADD_DATE="%unix ts%"
             *          PRIVATE="%0 | 1%"
             *             TAGS="%comma-separated tags%">%title%</A>
             *   <DD>%description%
             *
             * Additional parameters that may be included
             * if this is an export file from Connexions:
             *           RATING="%0-5%"
             *         FAVORITE="%0 | 1%"
             *
             * Note: the bookmark file format does NOT
             *       generally use ending tags.
             *
             */
            if (preg_match('/^<DT><A HREF="([^"]+)"([^>]*?)>([^<]+)<\/A>/i',
                                                            $line, $markInfo))
            {
                // Add any delayed bookmark.
                $this->_addBookmark($state);

                // Prepare this new bookmark
                $state['firstLine'] = $state['lastLine'] = $state['lineNum'];
                $state['numBookmarks']++;

                /* markInfo:
                 *  1   == url
                 *  2   == remainder of <a> attributes
                 *  3   == boomkark name
                 */
                $url   = $markInfo[1];
                $attrs = trim($markInfo[2]);
                $name  = $markInfo[3];

                $this->_parseBookmarkInfo($state, $url, $name, $attrs);

                continue;
            }

            /**********************************************
             * The (beginning of the) description of the
             * previous bookmark.
             *
             */
            if (preg_match('/^<DD>(.*)/', $line, $markInfo))
            {
                if (! is_array($state['bookmark']))
                {
                    $warn = "Line {$state['lineNum']}: "
                          . "Ignored bookmark description since there "
                          . "was no preceeding bookmark.";
                    $this->_bookmarksImport_warning($state, $warn);
                }
                else
                {
                    $state['inDD'] = true;
                    $state['bookmark']['description'] .= $markInfo[1];
                    $state['lastLine'] = $state['lineNum'];
                }

                continue;
            }

            /**********************************************
             * The Beginning of a folder.
             *
             * <DL>...
             *  <DT><H3 %attrs%>%title%</H3>
             *
             *  Valid H3 attributes:
             *      ADD_DATE
             *      LAST_MODIFIED
             *
             */

            // Technical start of a folder
            if (preg_match('/^<DL>/', $line))
            {
                // Add any delayed bookmark.
                $this->_addBookmark($state);

                $state['level']++;
                continue;
            }

            // Real start of a folder
            if (preg_match('/^<DT><H3([^>]+)>([^<]+)<\/H3>/i',
                                                    $line, $folderInfo))
            {
                // Add any delayed bookmark.
                $this->_addBookmark($state);

                /* folderInfo:
                 *  1   == remainder of <h3> attributes
                 *  2   == folder name
                 */
                $attrs = $folderInfo[1];
                $name  = $folderInfo[2];

                $state['numFolders']++;

                array_push($state['tagStack'], $name);

                /*
                Connexions::log("SettingsController::"
                                . "_post_bookmarks_import(): "
                                . "new folder '%s', level %d -- tagStack[ %s ]",
                                $name,
                                $state['level'],
                                implode(', ', $state['tagStack']));
                // */

                continue;
            }

            /**********************************************
             * The End of a folder.
             *
             */
            if (preg_match('/^<\/DL>/', $line))
            {
                // Add any delayed bookmark.
                $this->_addBookmark($state);

                if ($state['level'] > 0)
                {
                    array_pop($state['tagStack']);
                    $state['level']--;

                    /*
                    Connexions::log("SettingsController::"
                                    . "_post_bookmarks_import(): "
                                    . "END folder, level %d -- tagStack[ %s ]",
                                    $state['level'],
                                    implode(', ', $state['tagStack']));
                    // */

                }
                else
                {
                    $warn = "Line {$state['lineNum']}: "
                          . "Ignored mis-matched DL.";
                    $this->_bookmarksImport_warning($state, $warn);
                }
                continue;
            }

            /**********************************************
             * If we're currently "in" a DD tag
             * (bookmark description), add any line that
             * hasn't matched anything else to the
             * description.
             */
            if ($state['inDD'] === true)
            {
                $state['bookmark']['description'] .= $line;
                $state['lastLine'] = $state['lineNum'];
                continue;
            }

            // IGNORE all other lines
        }

        fclose($fh);

        /*
        Connexions::log("SettingsController::_post_bookmarks_import(): "
                        .   "complete.  Final state[ %s ]",
                        print_r($state, true));
        // */

        /* Pull out the portions of state that are useful for presentation
         * of final results
         */
        $this->view->state = $state;

        /*
        Connexions::log("SettingsController::_post_bookmarks_import(): "
                        .   "complete.  Results[ %s ]",
                        print_r($this->view->state, true));
        // */

    }

    protected function _post_bookmarks_export()
    {
        $request =& $this->_request;

        // Retrieve all user-related bookmarks params: order, count, offset
        $this->view->bookmarks   = $this->_viewer->getBookmarks();
        $this->view->includeTags = Connexions::to_bool(
                                    $request->getParam('includeTags', false));
        $this->view->includeMeta = Connexions::to_bool(
                                    $request->getParam('includeMeta', false));
    }

    /***********************************************************************
     * Private helpers
     *
     */

    private function _bookmarksImport_error(&$state, $msg)
    {
        $state['numErrors']++;

        $this->view->state = $state;
        $this->view->error = $msg;
        $this->render($state['importError']);

        if ($this->_streaming === true)
        {
            /* Perform an immediate output and clear (flush) of any
             * accumulated body content.
             */
            $this->_response->outputBody();
            $this->_response->clearBody();
        }

        /*
        echo $this->view->render($state['importError']);
         */

        //array_push($state['errors'], $msg);
    }

    private function _bookmarksImport_warning(&$state, $msg)
    {
        $state['numWarnings']++;

        $this->view->state   = $state;
        $this->view->warning = $msg;
        $this->render($state['importWarning']);

        if ($this->_streaming === true)
        {
            /* Perform an immediate output and clear (flush) of any
             * accumulated body content.
             */
            $this->_response->outputBody();
            $this->_response->clearBody();
        }

        /*
        echo $this->view->render($state['importWarning']);
         */

        //array_push($state['warnings'], $msg);
    }

    /** @brief  Output a successfully processed bookmark.
     *  @param  state       The overall processing state.
     *  @param  importState The import state of THIS bookmark
     *                      ('ignored' | 'new' | 'updated')
     *  @param  bookmark    The processed bookmark.
     */
    private function _bookmarksImport_bookmark(&$state, $importState, $bookmark)
    {
        /*
        Connexions::log("_bookmarksImport_bookmark(): "
                        . "import state[ %s ], "
                        . "bookmark[ %s ]",
                        $importState, $bookmark->debugDump());
        // */

        $this->view->state       = $state;
        $this->view->importState = $importState;
        $this->view->bookmark    = $bookmark;
        $this->render($state['importBookmark']);

        if ($this->_streaming === true)
        {
            /* Perform an immediate output and clear (flush) of any
             * accumulated body content.
             */
            $this->_response->outputBody();
            $this->_response->clearBody();
        }

        /*
        echo $this->view->render($state['importBookmark']);
         */
    }

    /** @brief  Given the current processing state, and information from
     *          a new bookmark entry, begin gathering new bookmark information.
     *  @param  state       The current processing state;
     *  @param  url         The URL        of the new bookmark;
     *  @param  name        The name/title of the new bookmark;
     *  @param  attrs       Additional bookmark attributes provided by the
     *                      bookmark entries anchor;
     */
    private function _parseBookmarkInfo(&$state, $url, $name, $attrs)
    {
        $state['bookmark'] = array(
            'userId'        => $this->_viewer->getId(),

            'name'          => $name,
            'url'           => $url,
            'description'   => '',
            'tags'          => $state['tagStack'],
            'rating'        => 0,
            'isFavorite'    => false,
            'isPrivate'     => ($state['visibility'] !== 'public'),
        );

        // Process any of the additional <a> attributes
        if (! empty($attrs))
        {
            $pairs = preg_split('/\s+/', $attrs);
            foreach ($pairs as $pair)
            {
                list($key, $val) = explode('=', $pair);
                $val = preg_replace('/"/', '', $val);

                /*
                Connexions::log("Line %s: pair[ %s ] == key[ %s ], val[ %s ]",
                                $state['lineNum'],
                                $pair,
                                $key, $val);
                // */

                /* Valid attribute keys:
                 *      ADD_DATE    Unix Timestamp
                 *      PRIVATE     0 | 1
                 *      TAGS        Comma-separated string
                 *      RATING      0-5
                 *      FAVORITE    0 | 1
                 */
                switch (strtolower($key))
                {
                case 'add_date':
                    $iVal = (int)$val;
                    if ($iVal > 0)
                    {
                        $state['bookmark']['taggedOn'] =
                                date('Y-m-d H:i:s', $iVal);
                    }
                    else
                    {
                        $warn = "Line {$state['lineNum']}: "
                              . "Ignored invalid "
                              . "ADD_DATE '{$val}'";
                        $this->_bookmarksImport_warning($state, $warn);
                    }
                    break;

                case 'private':
                    $val = strtolower($val);
                    if ( ($val === 'yes')  ||
                         ($val === 'true') ||
                         ($val === '1'))
                    {
                        $state['bookmark']['isPrivate'] = true;
                    }
                    else
                    {
                        $state['bookmark']['isPrivate'] = false;
                    }
                    break;

                case 'tags':
                    // Normalize the provided tags
                    $val = implode(',',
                                   preg_split('#\s*[/,+]\s*#', $val));

                    array_push($state['bookmark']['tags'], $val);
                    break;

                case 'rating':
                    $iVal = (int)$val;
                    if ( ($iVal >= 0) && ($iVal <= 5) )
                    {
                        $state['bookmark']['rating'] = $iVal;
                    }
                    else
                    {
                        $warn = "Line {$state['lineNum']}: "
                              . "Ignored invalid "
                              . "RATING '{$val}'";
                        $this->_bookmarksImport_warning($state, $warn);
                    }
                    break;

                case 'favorite':
                    $val = strtolower($val);
                    if ( ($val === 'yes')  ||
                         ($val === 'true') ||
                         ($val === '1'))
                    {
                        $state['bookmark']['isFavorite'] = true;
                    }
                    else
                    {
                        $state['bookmark']['isFavorite'] = false;
                    }
                    break;

                case 'icon':
                    // Silently ignore
                    break;

                default:
                    // Ignore all others with a warning
                    $warn = "Line {$state['lineNum']}: "
                          . "Ignored attribute "
                          . "'{$key}' with value '{$val}'";
                    $this->_bookmarksImport_warning($state, $warn);
                    break;
                }
            }
        }
    }

    /** @brief  Given bookmark data, attempt to add it.
     */
    private function _addBookmark(& $state)
    {
        $state['inDD'] = false;
        if (! is_array($state['bookmark']))
        {
            // No bookmark to save.
            return;
        }

        /*
        Connexions::log("SettingsController::_addBookmark(): "
                        . "state[ %s ]",
                        Connexions::varExport($state));
        Connexions::log("SettingsController::_addBookmark(): "
                        . "bookmark[ %s ]",
                        Connexions::varExport($state['bookmark']));
        // */

        $bookmark = $this->service('Bookmark')->get( $state['bookmark'] );

        if ( $bookmark->isBacked() && ($state['conflict'] === 'ignore'))
        {
            // The bookmark already exists -- IGNORE this one.
            $state['numIgnored']++;

            $this->_bookmarksImport_bookmark($state, 'ignored', $bookmark);
        }
        else
        {
            $isNew = ($bookmark->isBacked() !== true);

            // Attempt to save this new bookmark
            if ($state['test'] === 'yes')
            {
                /* ONLY a test
                 *  Do NOT actually save, but record how things WOULD have
                 *  gone.
                 */
                $state['numImported']++;

                if ($isNew) $state['numNew']++;
                else        $state['numUpdated']++;

                $this->_bookmarksImport_bookmark($state,
                                                 ($isNew ? 'new' : 'updated'),
                                                 $bookmark);
            }
            else
            {
                // Attempt to save this bookmark (new or updated).
                try
                {
                    $bookmarkSaved = $bookmark->save();

                    $state['numImported']++;

                    if ($isNew) $state['numNew']++;
                    else        $state['numUpdated']++;

                    $this->_bookmarksImport_bookmark($state,
                                                     ($isNew
                                                        ? 'new' : 'updated'),
                                                     $bookmarkSaved);
                }
                catch (Exception $e)
                {
                    $error = "Line {$state['lineNum']}: "
                           . "Error saving bookmark: "
                           . $e->getMessage();
                    $this->_bookmarksImport_error($state, $error);
                }
            }
        }

        $state['bookmark'] = null;

        // Re-set the execution timeout.
        set_time_limit($this->_maxTime);
    }
}
