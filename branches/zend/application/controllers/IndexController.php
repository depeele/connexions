<?php
/** @file
 *
 *  This controller controls access to Bookmarks and is accessed
 *  via the url/routes:
 *      /[ (<user> | bookmarks) [/<tag list>]]
 */

class IndexController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public  $dependencies = array('db','layout');

    protected   $_url       = null;
    protected   $_owner     = null;
    protected   $_bookmarks = null;

    protected   $_offset    = 0;
    protected   $_count     = null;
    protected   $_sortBy    = null;
    protected   $_sortOrder = null;

    public      $contexts   = array(
                                'index' => array('partial', 'json',
                                                 'rss',     'atom'),
                              );

    public function init()
    {
        parent::init();

        // Initialize context switching (via $this->contexts)
        $cs = $this->_helper->contextSwitch();
        $cs->initContext();
    }

    /** @brief  Index/Get/Read/View action.
     *
     *  Retrieve a set of Bookmarks based upon the requested
     *  'owner' and/or 'tags'.
     *
     *  Once retrieved, perform further setup based upon the current
     *  context/format.
     */
    public function indexAction()
    {
        $request  =& $this->_request;

        /***************************************************************
         * Process the requested 'owner' and 'tags'
         *
         */
        $reqOwner = $request->getParam('owner', null);
        $reqTags  = $request->getParam('tags', null);

        /* If this is a user/"owned" area (e.g. /<userName> [/ <tags ...>]),
         * verify the validity of the requested user.
         */
        if ($reqOwner === 'mine')
        {
            // 'mine' == the currently authenticated user (viewer)
            if ( ( ! $this->_viewer instanceof Model_User) ||
                 (! $this->_viewer->isAuthenticated()) )
            {
                // Unauthenticated user -- Redirect to signIn
                return $this->_helper->redirector('signIn','auth');
            }

            // Redirect to the viewer's bookmarks
            return $this->_helper->redirector($this->_viewer->name);
        }

        if ($reqOwner === '*')
        {
            $this->_owner = $reqOwner;
        }
        else
        {
            // Resolve the incoming 'owner' name.
            $user = $this->_resolveUserName($reqOwner);

            if ($user !== null) // ($user instanceof Model_User)
            {
                // A valid user
                $this->_owner = $user;
            }
            // 'owner' is NOT a valid user.
            else if (empty($reqTags))   // If 'tags' are empty, user 'owner'
            {
                /* No 'tags' were specified.  Use the owner as 'tags' and set
                 * 'owner' to '*'
                 */
                $this->_owner = '*';
                $reqTags      = $reqOwner;
            }
            else
            {
                /* 'tags' have already been specified.  Set 'owner' to '*' and
                 * report that the provided 'owner' is NOT a valid user.
                 */
                $this->_owner      = '*';
                $this->view->error = "Unknown user [ {$reqOwner} ]";
            }
        }

        Connexions::log("IndexController::indexAction: reqTags[ %s ]",
                        $reqTags);

        // Parse the incoming request tags
        $this->_tags = $this->service('Tag')->csList2set($reqTags);

        /***************************************************************
         * We now have a valid 'owner' ($this->_owner) and
         * 'tags' ($this->_tags)
         *
         * Adjust the URL to reflect the validated 'owner' and 'tags'
         */
        $this->_url = $request->getBasePath()
                    . '/' .($this->_owner instanceof Model_User
                            ? $this->_owner->name
                            : 'bookmarks')
                    . '/' .(count($this->_tags) > 0
                            ? $this->_tags .'/'
                            : '');

        /***************************************************************
         * Set the view variables required for all views/layouts.
         *
         */
        if ($this->_owner !== '*')
            $this->view->headTitle($this->_owner ."'s Bookmarks");
        else
            $this->view->headTitle('Bookmarks');

        $this->view->url       = $this->_url;
        $this->view->owner     = $this->_owner;
        $this->view->viewer    = $this->_viewer;

        $this->view->tags      = $this->_tags;


        $this->_prepareMain();

        // Handle this request based on the current context / format
        $this->_handleFormat();
    }

    /** @brief  Post action -- simply present the Post/Create view.
     *
     */
    public function postAction()
    {
        Connexions::log("IndexController::postAction");

        if ( (! $this->_viewer instanceof Model_User) ||
             (! $this->_viewer->isAuthenticated()) )
        {
            // Unauthenticated user -- Redirect to signIn
            return $this->_helper->redirector('signIn','auth');
        }

        //$this->layout->setLayout('post');
        //$this->_helper->layout->setLayout('post');

        $request  =& $this->_request;
        $bService = $this->service('Bookmark');
        $bookmark =  null;
        $postInfo =  array(
            'url'           => trim($request->getParam('url',         null)),
            'name'          => trim($request->getParam('name',        null)),
            'description'   => trim($request->getParam('description', null)),
            'rating'        => $request->getParam('rating',           null),
            'isFavorite'    => $request->getParam('isFavorite',       null),
            'isPrivate'     => $request->getParam('isPrivate',        null),
            'tags'          => trim($request->getParam('tags',        null)),
        );

        /*
        Connexions::log("IndexController::postAction: "
                        . "postInfo [ %s ]",
                        Connexions::varExport($postInfo));
        // */


        if ($request->isPost())
        {
            // This is a POST -- attempt to create/update a bookmark
            if ($postInfo['isFavorite'] === null)
                $postInfo['isFavorite'] = false;

            if ($postInfo['isPrivate'] === null)
                $postInfo['isPrivate'] = false;

            $error = null;
            try
            {
                $bookmark = $bService->get($postInfo);
                if ($bookmark === null)
                {
                    $error = "Cannot create new bookmark (internal error)";
                }
                else if (! $bookmark->isValid())
                {
                    $messages = $bookmark->getValidationMessages();
                    $errors   = array();
                    foreach ($messages as $field => $message)
                    {
                        array_push($errors,
                                   sprintf("%s: %s", $field, $message));
                    }

                    $error = implode(', ', $errors);
                }
                else
                {
                    /* Attempt to save this bookmark.  This should either
                     * update or create
                     */
                    $bookmark = $bookmark->save();
                }
            }
            catch (Exception $e)
            {
                $error = $e->getMessage();
            }

            if ($error !== null)
                $this->view->error = $error;
        }
        else
        {
            /* Initial presentation of posting form.
             *
             * Retrieve any existing bookmark for the given URL by the current
             * user.
             */
            if (! empty($postInfo['url']))
            {
                $bookmark = $bService->find( array(
                                                'user'   => $this->_viewer,
                                                'itemId' => $postInfo['url'],
                                             ));

                if ($bookmark !== null)
                {
                    /*
                    Connexions::log("IndexController::postAction: "
                                    . "existing bookmark information [ %s ]",
                                    Connexions::varExport(
                                                    $bookmark->toArray()) );
                    // */

                    /* The user has an existing bookmark.  Fill in any data
                     * that was NOT provided directly.
                     */
                    if (empty($postInfo['name']))
                        $postInfo['name'] = $bookmark->name;

                    if (empty($postInfo['description']))
                        $postInfo['description'] = $bookmark->description;

                    if ($postInfo['rating'] === null)
                        $postInfo['rating'] = $bookmark->rating;

                    if ($postInfo['isFavorite'] === null)
                        $postInfo['isFavorite'] = $bookmark->isFavorite;

                    if ($postInfo['isPrivate'] === null)
                        $postInfo['isPrivate'] = $bookmark->isPrivate;

                    if (empty($postInfo['tags']))
                        $postInfo['tags'] = $bookmark->tags->__toString();
                }
            }
            if ($postInfo['isFavorite'] === null)
                $postInfo['isFavorite'] = false;

            if ($postInfo['isPrivate'] === null)
                $postInfo['isPrivate'] = false;
        }

        $this->view->headTitle('Save a Bookmark');

        $this->view->viewer   = $viewer;
        $this->view->postInfo = $postInfo;
    }

    /*************************************************************************
     * Protected Helpers
     *
     */

    /** @brief  Determine the proper rendering format.  The only ones we deal
     *          with directly are:
     *              partial - render a single part of this page
     *              html    - normal HTML rendering
     *
     *  All others are handled by the 'contextSwitch' established in
     *  this controller's init method.
     */
    protected function _handleFormat()
    {
        $format =  $this->_helper->contextSwitch()->getCurrentContext();
        Connexions::log("IndexController::_handleFormat: context [ %s ]",
                        $format);

        if (empty($format))
            $format = $this->_request->getParam('format', 'html');

        Connexions::log("IndexController::_handleFormat: [ %s ]", $format);

        switch ($format)
        {
        case 'partial':
            /* Render just PART of the page and MAY not require the bookmark
             * paginator.
             *
             *  part=(content | sidebar([.:-](tags | people))? )
             */
            //$this->_helper->layout->setLayout('partial');
            $this->layout->setLayout('partial');

            /* Notify view scripts that we are rendering a partial
             * (asynchronously loaded portion of a full page).
             */
            $this->view->isPartial = true;

            $parts = preg_split('/\s*[\.:\-]\s*/',
                                $this->_request->getParam('part', 'content'));
            switch ($parts[0])
            {
            case 'sidebar':
                /* Render JUST the sidebar, or a single pane of the sidebar
                 *      application/views/scripts/index/sidebar.phtml
                 *      application/views/scripts/index/sidebar-tags.phtml
                 *      application/views/scripts/index/sidebar-people.phtml
                 *      application/views/scripts/index/sidebar-items.phtml
                 */
                $this->_htmlSidebar(false, (count($parts) > 1
                                                ? $parts[1]
                                                : null));
                break;

            case 'main':
                /* Render JUST the main pane
                 *      application/views/scripts/index/main.phtml
                 */
                $this->render('main');
                break;

            case 'content':
            default:
                /* Render JUST the main content section, that includes
                 * the main pane.
                 *
                 * through to perform normal rendering of
                 *      application/views/scripts/index/index.phtml
                 *
                 * This will render the primary content section, that includes
                 * the main pane.
                 */
                break;
            }
            break;

        case 'html':
            // Normal HTML rendering includes the sidebar
            $this->render('index');
            $this->_htmlSidebar();
            break;

        case 'json':
        case 'rss':
        case 'atom':
        default:
            if ($format === 'rss')
            {
                $this->view->main['feedType'] =
                                    View_Helper_FeedBookmarks::TYPE_RSS;
                $format = 'feed';
            }
            else if ($format === 'atom')
            {
                $this->view->main['feedType'] =
                                    View_Helper_FeedBookmarks::TYPE_ATOM;
                $format = 'feed';
            }

            Connexions::log("IndexController::_handleFormat: "
                            .   "render 'index-%s'",
                            $format);

            $this->render('index-'. $format);


            Connexions::log("IndexController::_handleFormat: "
                            .   "render 'index.%s' COMPLETE",
                            $format);
            break;
        }
    }

    /** @brief  Given a string that is supposed to represent a user, see if it
     *          represents a valid user.
     *  @param  name    The user name.
     *
     *  @return A Model_User instance matching 'name', null if no match.
     */
    protected function _resolveUserName($name)
    {
        $res = null;

        if ((! @empty($name)) && ($name !== '*'))
        {
            // Does the name match an existing user?
            if ($name === $this->_viewer->name)
            {
                // 'name' matches the current viewer...
                $ownerInst =& $this->_viewer;
            }
            else
            {
                //$ownerInst = Model_User::find(array('name' => $name));
                $ownerInst = $this->service('User')
                                    ->find(array('name' => $name));
            }

            // Have we located a valid, backed user?
            if ($ownerInst !== null)
            {
                // YES -- we've located an existing user.

                $res = $ownerInst;
            }
        }

        return $res;
    }

    /** @brief  Prepare for rendering the main view, regardless of format.
     *
     *  This will collect the variables needed to render the main view, placing
     *  them in $view->main as a configuration array.
     */
    protected function _prepareMain()
    {
        $request          =& $this->_request;

        $prefix           = 'items';
        $itemsStyle       = $request->getParam($prefix."OptionGroup");
        $itemsStyleCustom = $request->getParam($prefix."OptionGroups_option");

        $perPage          = $request->getParam($prefix ."PerPage");
        $page             = $request->getParam($prefix ."Page");
        $sortBy           = $request->getParam($prefix ."SortBy");
        $sortOrder        = $request->getParam($prefix ."SortOrder");

        /*
        Connexions::log('IndexController::_perpareMain(): '
                        .   'itemsStyle[ %s ], options[ %s ]',
                        $itemsStyle, Connexions::varExport($itemsStyleCustom));
        // */

        if ( ($itemsStyle === 'custom') && (is_array($itemsStyleCustom)) )
            $itemsStyle = $itemsStyleCustom;

        // Additional view variables for the HTML view.
        $this->view->main = array(
            'namespace'     => $prefix,
            'viewer'        => &$this->_viewer,
            'users'         => ($this->_owner !== '*'
                                ? $this->_owner
                                : null),
            'tags'          => &$this->_tags,
            'displayStyle'  => $itemsStyle,
            'perPage'       => $perPage,
            'page'          => $page,
            'sortBy'        => $sortBy,
            'sortOrder'     => $sortOrder,
        );
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
     *        (application/views/scripts/index/index.phtml) will also add
     *        sidebar-related rendering information to the sidbar helper.  In
     *        particular, it will notify the sidbar helper of the items that
     *        are being presented in the main view.
     */
    protected function _prepareSidebar($async = false)
    {
        $request =& $this->_request;

        $sidebar = array(
            'namespace' => 'sidebar-tab',
            'async'     => $async,
            'viewer'    => &$this->_viewer,
            'users'     => ($this->_owner !== '*'
                            ? $this->_owner
                            : null),
            'tags'      => &$this->_tags,
            // 'items'   will be set by the sidebar-tags view renderer

            'panes'     => array(
                /* Used by:
                 *      application/views/scripts/index/sidebar-tags.phtml
                 *          and from there by
                 *      application/views/helpers/HtmlItemCloud.php
                 */
                'tags'    => array(
                    'namespace'     => 'sbTags',
                    'title'         => 'Tags',
                    'weightName'    => 'userItemCount',

                    // 'related' will be set by the main view renderer
                    // 'selected'      => $this->_tags,
                    'itemType'      =>
                                View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM,
                    'itemBaseUrl'   => $this->_url,

                    'sortBy'        => $request->getParam("sbTagsSortBy"),
                    'sortOrder'     => $request->getParam("sbTagsSortOrder"),

                    'page'          => $request->getParam("sbTagsPage"),
                    'perPage'       => $request->getParam("sbTagsPerPage"),
                    'highlightCount'=> $request->getParam(
                                                    "sbTagsHighlightCount"),

                    'displayStyle'  => $request->getParam("sbTagsOptionGroup"),
                ),

                'people'  => array(
                    'namespace'     => 'sbPeople',
                    'title'         => 'People',
                    'weightName'    => 'userItemCount',

                    // 'related' will be set by the main view renderer
                    // 'selected'      => $this->_owner,
                    'itemType'      =>
                                View_Helper_HtmlItemCloud::ITEM_TYPE_USER,
                    'itemBaseUrl'   => Connexions::url('/'),    // $this->_url,

                    'sortBy'        => $request->getParam("sbPeopleSortBy"),
                    'sortOrder'     => $request->getParam("sbPeopleSortOrder"),

                    'page'          => $request->getParam("sbPeoplePage"),
                    'perPage'       => $request->getParam("sbPeoplePerPage"),
                    'highlightCount'=> $request->getParam(
                                                    "sbPeopleHighlightCount"),

                    'displayStyle'  => $request->getParam(
                                                    "sbPeopleOptionGroup"),
                ),

                'items'   => array(
                    'namespace'     => 'sbItems',
                    'title'         => 'Items',

                    // 'related' will be set by the main view renderer
                    // 'selected'      => $this->_owner,
                    'itemBaseUrl'   => $this->_url,

                    'sortBy'        => $request->getParam("sbItemsSortBy"),
                    'sortOrder'     => $request->getParam("sbItemsSortOrder"),

                    'page'          => $request->getParam("sbItemsPage"),
                    'perPage'       => $request->getParam("sbItemsPerPage"),
                    'highlightCount'=> $request->getParam(
                                                    "sbItemsHighlightCount"),

                    'displayStyle'  => $request->getParam(
                                                    "sbItemsOptionGroup"),
                ),
            ),
        );

        $this->view->sidebar = $sidebar;
    }

    /** @brief  Generate HTML for the sidebar based upon the incoming request.
     *  @param  usePlaceholder      Should the rendering be performed
     *                              immediately into a placeholder?
     *                              [ true, into the 'right' placeholder ]
     *  @param  part                The portion of the sidebar to render
     *                                  (tags | people)
     *                              [ null == all ]
     *
     */
    protected function _htmlSidebar($usePlaceholder = true,
                                    $part           = null)
    {
        //return;

        $this->_prepareSidebar( $usePlaceholder );

        if ($part !== null)
        {
            // Render just the requested part
            $this->render('sidebar-'. $part);
        }
        else
        {
            // Render the entire sidebar
            if ($usePlaceholder === true)
            {
                // Render the sidebar into the 'right' placeholder
                $this->view->renderToPlaceholder('index/sidebar.phtml',
                                                 'right');

                // /*
                Connexions_Profile::checkpoint('Connexions',
                                               'IndexController::_htmlSidebar: '
                                               . 'rendered to placeholder');
                // */
            }
            else
            {
                $this->render('sidebar');
            }
        }
    }
}
