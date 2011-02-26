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
        // /*
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

        // Normalize the tags
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


        // /*
        Connexions::log("SettingsController::_post_bookmarks_import(): "
                        .   "file[ %s ]",
                        Connexions::varExport($file));
        Connexions::log("SettingsController::_post_bookmarks_import(): "
                        .   "tags[ %s ], visibility[ %s ], "
                        .   "conflict[ %s ], test[ %s ]",
                        $tags, $visibility,
                        $conflict, $test);
        // */

        $fh = fopen($file['tmp_name'], 'r');
        if ($fh === false)
        {
            $this->view->error = 'Cannot access the uploaded file';
            return;
        }

        /**************************************************************
         * Walk through the import file one line at a time.
         *
         */
        $state = array(
            'conflict'      => $conflict,   // replace | ignore
            'test'          => $test,       // yes     | no

            'lineNum'       => 0,           // Current line in the import file
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

            'numNew'        => 0,   /* Of 'numImported', how many were
                                     * completely new
                                     */
            'numUpdated'    => 0,   /* Of 'numImported', how many were
                                     * existing bookmarks that were updated
                                     * (iff 'conflict' == 'replace')
                                     */

            'errors'        => array(),
            'warnings'      => array(),
        );
        while (! feof($fh))
        {
            $line = trim(fgets($fh));
            if ($line === false)
            {
                array_push($state['errors'],
                           "Read error on line {$state['lineNum']}");
                continue;
            }
            $state['lineNum']++;

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
             */
            if (preg_match('/^<DT><A HREF="([^"]+)([^>]*?)>([^<]+)<\/A>/i',
                                                            $line, $markInfo))
            {
                $state['inDD'] = false;
                if (is_array($state['bookmark']))
                {
                    // Delayed bookmark - add it now.
                    $this->_addBookmark($state);
                }
                $state['numBookmarks']++;

                /* markInfo:
                 *  1   == url
                 *  2   == remainder of <a> attributes
                 *  3   == boomkark name
                 */
                $url   = $markInfo[1];
                $attrs = $markInfo[2];
                $name  = $markInfo[3];

                $state['bookmark'] = array(
                    'userId'        => $this->_viewer->getId(),
                    'itemId'        => $url,

                    'name'          => $name,
                    'description'   => '',
                    'tags'          => $state['tagStack'],
                    'rating'        => 0,
                    'isFavorite'    => false,
                    'isPrivate'     => ($visibility !== 'public'),
                );

                // Process any of the additional <a> attributes
                if (! empty($attrs))
                {
                    $pairs = preg_split('/\s+/', $attrs);
                    foreach ($pairs as $pair)
                    {
                        list($key, $val) = split('=', $pair);
                        $val = preg_replace('/"/', '', $val);

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
                                array_push($state['warnings'],
                                           "Line {$state['lineNum']}: "
                                            . "Ignored invalid "
                                            . "ADD_DATE '{$val}'");
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
                                array_push($state['warnings'],
                                           "Line {$state['lineNum']}: "
                                            . "Ignored invalid "
                                            . "RATING '{$val}'");
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

                        default:
                            Connexions::log("Line %s: Ignore attribute "
                                            . "key[ %s ], value[ %s ], "
                                            . "line[ %s ]",
                                            $state['lineNum'],
                                            $key, $val,
                                            $line);
                            /*
                            array_push($state['warnings'],
                                       "Line {$state['lineNum']}: "
                                        . "Ignored attribute "
                                        . "'{$key}' with value '{$val}'");
                            // */
                            break;
                        }
                    }
                }

                continue;
            }

            /**********************************************
             * The description of the previous bookmark.
             *
             */
            if (preg_match('/^<DD>(.*)/', $line, $markInfo))
            {
                $state['inDD'] = true;
                if (! is_array($state['bookmark']))
                {
                    array_push($state['warnings'],
                               "Line {$state['lineNum']}: "
                               . "Ignored DD with no preceeding DT.");
                }
                else
                {
                    $state['bookmark']['description'] .= $markInfo[1];
                }

                continue;
            }

            /**********************************************
             * The End of a folder.
             *
             */
            if (preg_match('/^<\/DL>/', $line))
            {
                if (is_array($state['bookmark']))
                {
                    // Delayed bookmark - add it now.
                    $this->_addBookmark($state);
                }

                if ($state['level'] > 0)
                {
                    array_pop($state['tagStack']);
                    $state['level']--;
                }
                else
                {
                    array_push($state['warnings'],
                               "Line {$state['lineNum']}: "
                               . "Ignored mis-matched DL.");
                }
                continue;
            }

            /**********************************************
             * The Beginning of a folder.
             *
             *  <DT><H3 %attrs%>%title%</H3>
             *
             *  Valid H3 attributes:
             *      ADD_DATE
             *      LAST_MODIFIED
             *
             */
            if (preg_match('/^<DT><H3([^>]+)>([^<]+)<\/H3>/i',
                                                    $line, $folderInfo))
            {
                if (is_array($state['bookmark']))
                {
                    // Delayed bookmark - add it now.
                    $this->_addBookmark($state);
                }

                /* folderInfo:
                 *  1   == remainder of <h3> attributes
                 *  2   == folder name
                 */
                $attrs = $folderInfo[1];
                $name  = $folderInfo[2];

                $state['numFolders']++;
                $state['level']++;

                array_push($state['tagStack'], $name);

                continue;
            }

            /**********************************************
             * If we're currently "in" a DD tag, add
             * any line that hasn't matched anything else
             * to the description.
             */
            if ($state['inDD'] === true)
            {
                $state['bookmark']['description'] .= $line;
                continue;
            }

            // IGNORE all other lines
        }

        fclose($fh);

        $this->view->results = $html;
    }

    protected function _post_bookmarks_export()
    {
    }

    /***********************************************************************
     * Private helpers
     *
     */

    /** @brief  Given bookmark data, attempt to add it.
     */
    private function _addBookmark(& $state)
    {
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
        }
        else
        {
            $isNew = $bookmark->isBacked();

            // Attempt to save this new bookmark
            if ($state['test'] === 'yes')
            {
                /* ONLY a test
                 *  Do NOT actually save (but record how things WOULD have
                 *  gone.
                 */
                $state['numImported']++;

                if ($isNew) $state['numNew']++;
                else        $state['numUpdated']++;
            }
            else
            {
                // Attempt to save this bookmark (new or updated).
                try
                {
                    $bookmark = $bookmark->save();

                    $state['numImported']++;

                    if ($isNew) $state['numNew']++;
                    else        $state['numUpdated']++;
                }
                catch (Exception $e)
                {
                    array_push($state['errors'],
                               "Line {$state['lineNum']}: "
                               . "Error saving bookmark: "
                               . $e->getMessage());
                }
            }
        }

        $state['bookmark'] = null;
    }
}
