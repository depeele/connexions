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

        // Initialize context switching
        $cs = $this->_helper->contextSwitch();
        $cs->initContext();

        /*
        $cs = $this->getHelper('contextSwitch');
        $cs->addActionContext('index', array('partial', 'json', 'rss', 'atom'))
           ->initContext();
        */
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
                    . ($this->_owner instanceof Model_User
                        ? '/'. $this->_owner->name
                        : '')
                    . (count($this->_tags) > 0
                        ? '/'. $this->_tags
                        : '')
                    . '/';

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


        /* Prepare for rendering the main view, regardless of format
         *
         * Notify the HtmlBookmarks View Helper (used to render the main view)
         * of any incoming settings, allowing it establish any required
         * defaults.
         */
        $prefix           = 'items';
        $itemsStyle       = $request->getParam($prefix."OptionGroup");
        $itemsStyleCustom = $request->getParam($prefix."OptionGroups_option");

        $perPage          = $request->getParam($namespace ."PerPage");
        $page             = $request->getParam($namespace ."Page");
        $sortBy           = $request->getParam($namespace ."SortBy");
        $sortOrder        = $request->getParam($namespace ."SortOrder");

        /*
        Connexions::log('IndexController::_htmlContent(): '
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
        $postInfo = array(
            'name'          => $request->getParam('name',        null),
            'url'           => $request->getParam('url',         null),
            'description'   => $request->getParam('description', null),
            'tags'          => $request->getParam('tags',        null),
            'rating'        => $request->getParam('rating',      null),
            'isFavorite'    => $request->getParam('isFavorite',  false),
            'isPrivate'     => $request->getParam('isPrivate',   false)
        );

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

            $parts = preg_split('/\s*[\.:\-]\s*/',
                                $this->_request->getParam('part', 'content'));
            switch ($parts[0])
            {
            case 'sidebar':
                $this->_htmlSidebar(false, (count($parts) > 1
                                                ? $parts[1]
                                                : null));
                break;

            case 'content':
            default:
                $this->_htmlContent();
                break;
            }
            break;

        case 'html':
            // Normal HTML rendering
            $this->_htmlContent();
            $this->_htmlSidebar();
            break;

        case 'json':
            $this->_jsonContent();
            break;

        case 'rss':
        case 'atom':
            // Additional view variables for the alternate views.
            $this->view->paginator = $this->_createPaginator($this->_request);
            $this->render('index');

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

    /** @brief  Create a paginator ($this->_paginator) for the current
     *          Bookmark set.
     *  @param  request     The request to retrieve parameters from.
     *  @param  namespace   The namespace.
     *
     *  :Note: This also initializes _sortBy and _sortOrder
     *
     *  @return void
     */
    protected function _createPaginator($request, $namespace = '')
    {
        // /*
        Connexions_Profile::checkpoint('Connexions',
                                       'IndexController::_createPaginator: '
                                       . '%s: begin',
                                       $namespace);
        // */

        /***************************************************************
         * Retrieve any sort and paging parameters from the RPC request,
         * falling back to helper-controlled defaults.
         *
         */
        $page      = $request->getParam($namespace ."Page",    1);
        $perPage   = $request->getParam($namespace ."PerPage");
        $sortBy    = $request->getParam($namespace ."SortBy",
                                        View_Helper_Bookmarks::
                                                        $defaults['sortBy']);
        $sortOrder = $request->getParam($namespace ."SortOrder",
                                        View_Helper_Bookmarks::
                                                        $defaults['sortOrder']);

        if ($perPage < 1)
            $perPage = View_Helper_Bookmarks::$defaults['perPage'];

        $count   = $perPage;
        $offset  = $count * ($page > 0 ? $page - 1 : 0);

        $fetchOrder = array($sortBy .' '. $sortOrder);
        $ownerId    = ($this->_owner === '*'
                        ? null
                        : $this->_owner->userId);

        $this->_sortBy    = $sortBy;
        $this->_sortOrder = $sortOrder;


        // /*
        Connexions::log("IndexController::_createPaginator: "
                        .   "owner[ %s:%d ], "
                        .   "%d tags[ %s ], "
                        .   "page[ %d ], perPage[ %d ], "
                        .   "offset[ %d ], count[ %d ], "
                        .   "sortBy[ %s ], sortOrder[ %s ], "
                        .   "fetchOrder[ %s ]",
                        $this->_owner, $ownerId,
                        count($this->_tags), $this->_tags,
                        $page,   $perPage,
                        $offset, $count,
                        $sortBy, $sortOrder,
                        Connexions::varExport($fetchOrder));
        // */


        // Grab the owner/tag related bookmarks
        $bookmarks = $this->service('Bookmark')
                            ->fetchByUsersAndTags($ownerId,
                                                  $this->_tags,
                                                  true, // exactTags
                                                  $fetchOrder,
                                                  $count,
                                                  $offset);

        /*
        Connexions::log("IndexController::_createPaginator: %d/%d bookmarks",
                        count($bookmarks), $bookmarks->getTotalCount());
        // */

        // Create a paginator
        $paginator = new Zend_Paginator( $bookmarks->getPaginatorAdapter() );

        $paginator->setItemCountPerPage( $perPage );
        $paginator->setCurrentPageNumber($page );

        // /*
        Connexions_Profile::checkpoint('Connexions',
                                       'IndexController::_createPaginator: '
                                       . '%s: page %d, perPage %d, '
                                       . '%d pages, %d/%d items: end',
                                       $namespace,
                                       $paginator->getCurrentPageNumber(),
                                       $paginator->getItemCountPerPage(),
                                       count($paginator),
                                       $paginator->getCurrentItemCount(),
                                       $bookmarks->getTotalCount());
        // */

        return $paginator;
    }

    /*****************************************************
     * Json-RPC CRUD operations for Bookmarks
     *
     */

    /** @brief  Given an incoming request with Bookmark creation
     *          data, validate the request and, if valid, attempt to create a
     *          new Bookmark.
     *  @param  rpc     The incoming JsonRpc.
     *
     *  On failure/error, the rpc will have the appropriate error set.
     *
     *  @return void
     */
    protected function _create(Connexions_JsonRpc $rpc)
    {
        Connexions::log("IndexController::_create");

        if ( (! $this->_viewer instanceof Model_User) ||
             (! $this->_viewer->isAuthenticated()) )
        {
            // Unauthenticated user
            $rpc->setError('Unauthenticated.  Sign In to create bookmarks.');
            return;
        }

        $itemInfo = array(
            'name'          => $rpc->getParam('name',        null),
            'url'           => $rpc->getParam('url',         null),
            'description'   => $rpc->getParam('description', null),
            'tags'          => $rpc->getParam('tags',        null),
            'rating'        => $rpc->getParam('rating',      null),
            'isFavorite'    => $rpc->getParam('isFavorite',  false),
            'isPrivate'     => $rpc->getParam('isPrivate',   false)
        );

        // Validate and, if valid, attempt to create this new item.
        if (empty($itemInfo['name']))
        {
            $rpc->setError('The Bookmark name / title is required.');
            return;
        }
        if (empty($itemInfo['url']))
        {
            $rpc->setError('The URL to Bookmark is required.');
            return;
        }
        if (empty($itemInfo['tags']))
        {
            $rpc->setError('One or more tags are required.');
            return;
        }

        // VALID -- create and save the Bookmark.
        $bookmark = $this->service('Bookmark')
                            ->create( array(
                                'user'      => $this->_viewer,
                                'itemUrl'   => $itemInfo['url'],
                                'tags'      => $itemInfo['tags'],
                            ));

        // Save this (new) Bookmark.
        $bookmark->save();

        $rpc->setResult('Bookmark created.');
    }

    /** @brief  Given an incoming request with Bookmark
     *          identification data, retrieve the matching Bookmark
     *          and return it.
     *  @param  rpc     The incoming JsonRpc.
     *
     *  On failure/error, the rpc will have the appropriate error set.
     *
     *  @return void
     */
    protected function _read(Connexions_JsonRpc $rpc)
    {
        $this->view->paginator = $this->_createPaginator($rpc);

        $this->render('index');
    }

    /** @brief  Given an incoming request with Bookmark update
     *          data, validate the request and, if valid, attempt to update an
     *          existing Bookmark.
     *  @param  rpc     The incoming JsonRpc.
     *
     *  On failure/error, the rpc will have the appropriate error set.
     *
     *  @return void
     */
    protected function _update(Connexions_JsonRpc $rpc)
    {
        Connexions::log("IndexController::_update");

        if ( (! $this->_viewer instanceof Model_User) ||
             (! $this->_viewer->isAuthenticated()) )
        {
            // Unauthenticated user
            $rpc->setError('Unauthenticated.  Sign In to update bookmarks.');
            return;
        }

        $itemInfo = array(
            // item identifier: bookmark == $this->_viewer->userId, itemId
            'itemId'        => $rpc->getParam('itemId',      null),

            // New item information
            'url'           => $rpc->getParam('url',         null),

            // New bookmark information
            'name'          => $rpc->getParam('name',        null),
            'description'   => $rpc->getParam('description', null),
            'tags'          => $rpc->getParam('tags',        null),
            'rating'        => $rpc->getParam('rating',      null),
            'isFavorite'    => $rpc->getParam('isFavorite',  false),
            'isPrivate'     => $rpc->getParam('isPrivate',   false)
        );

        // Validate and, if valid, attempt to update the Bookmark.
        if ($itemInfo['itemId'] === null)
        {
            $rpc->setError('Missing item identifier.');
            return;
        }

        // Find the existing Bookmark
        $bookmark = $this->service('Bookmark')
                            ->find( array(
                                'userId' => $this->_viewer->userId,
                                'itemId' => $itemInfo['itemId']
                              ));
        if ( $bookmark === null )
        {
            // NOT found -- create instead??
            $rpc->setError('No matching bookmark found.');
            return;
        }

        if (empty($itemInfo['tags']))
        {
            $rpc->setError('One or more tags are required.');
            return;
        }

        // For all others, missing information defaults to the current value
        if (empty($itemInfo['url']))
            $itemInfo['url'] = $bookmark->item->url;
        if (empty($itemInfo['name']))
            $itemInfo['name'] = $uesrItem->name;

        // Compute the normalized hash for the incoming URL
        $itemInfo['urlHash'] = Connexions::md5Url($itemInfo['url']);

        /*** :XXX: ***

        /* VALID -- attempt to update an existing Bookmark...
         *
         *  1) Find the current item as well as the item associated with the 
         *     incoming URL;
         *  2) See if the item is changing:
         *     i)  YES - the item has changed;
         *         a) Remove all current tags from this Bookmark;
         *         b) Change the itemId to the identifier of the new item;
         *     ii) No change
         *         a) Change the current set of tags for this
         *            Bookmark;
         *
         *  3) Update full Bookmark based upon incoming data and 
         *     save it;
         */

        /* 1) Find the current item as well as the item associated with the 
         *    incoming URL;
         */
        $curItem = $bookmark->item;
        $newItem = $this->service('Item')
                            ->find( $itemInfo['urlHash'] );

        // 2) See if the item is changing...
        if ($curItem->itemId !== $newItem->itemId)
        {
            /* 2.i.a) YES - the item has changed, remove all tags associated 
             *              with the current item
             */
            $bookmark->tagsDelete();

            if (! $newItem->isBacked())
                // Save the new item
                $newItem->save();

            // 2.i.b) Change the itemId to the identifier of the new item;
            $bookmark->itemId = $newItem->itemId;
        }
        else
        {
            /* 2.ii.a) NO - the item is unchanged, change the current set of 
             *              tags for this
             */
            $bookmark->tagsUpdate($itemInfo['tags']);
        }

        // 3) Update full Bookmark based upon incoming data and save 
        //    it;
        $bookmark->name        = $itemInfo['name'];
        $bookmark->description = $itemInfo['description'];
        $bookmark->rating      = $itemInfo['rating'];
        $bookmark->isFavorite  = $itemInfo['isFavorite'];
        $bookmark->isPrivate   = $itemInfo['isPrivate'];

        $bookmark->save();

        $rpc->setResult('Bookmark Updated');
    }

    /** @brief  Given incoming Bookmark identification information,
     *          validate the request and, if valid, attempt to delete an
     *          existing bookmark.
     *  @param  rpc     The incoming JsonRpc.
     *
     *  On failure/error, the rpc will have the appropriate error set.
     *
     *  @return void
     */
    protected function _delete(Connexions_JsonRpc $rpc)
    {
        Connexions::log("IndexController::_delete");

        if ( (! $this->_viewer instanceof Model_User) ||
             (! $this->_viewer->isAuthenticated()) )
        {
            // Unauthenticated user
            $rpc->setError('Unauthenticated.  Sign In to delete bookmarks.');
            return;
        }

        $itemId = $rpc->getParam('itemId',      null);

        // Validate and, if valid, attempt to delete the bookmark.
        if (empty($itemInfo['itemId']))
        {
            $rpc->setError('Missing item identifier.');
            return;
        }

        // Find the existing Bookmark
        $bookmark = new Model_Bookmark( array(
                            'userId' => $this->_viewer->userId,
                            'itemId' => $itemInfo['itemId']) );
        if (! $bookmark->isBacked())
        {
            // NOT found
            $rpc->setError('No matching bookmark found.');
            return;
        }

        /* VALID -- attempt to delete this existing Bookmark...
         *
         *  1) Remove all current tags from this Bookmark;
         *  2) Delete the Bookmark;
         *  3) Notify related models of this update;
         */
        $rating  = $bookmark->rating;
        $curItem = $bookmark->item;

        // 1) Remove all current tags from this Bookmark;
        $bookmark->tagsDelete();

        // 2) Delete the Bookmark;
        if (! $bookmark->delete())
        {
            $rpc->setError( $bookmark->getError() );
            return;
        }

        // 3) Notify related models of this update

        $rpc->setResult('Bookmark Deleted');
    }

    /*************************************************************************
     * Context-specific view initialization and invocation
     *
     */

    /** @brief  Generate a JsonRPC from the incoming request, using a default
     *          method of 'read' and then perform any requested action.
     *
     *  This will populate $this->view->rpc for use in Bootstrap::jsonp_post()
     *  for final output.
     */
    protected function _jsonContent()
    {
        $request =& $this->_request;

        if ($request->isPost())
        {
            // Create a new Bookmark
            $defMethod = 'create';
        }
        else if ($request->isPut())
        {
            // Update an existing Bookmark
            $defMethod = 'update';
        }
        else if ($request->isDelete())
        {
            // Delete an existing Bookmark
            $defMethod = 'delete';
        }
        else // $request->isGet()
        {
            // Read an existing Bookmark
            $defMethod = 'read';
        }

        $rpc = new Connexions_JsonRpc($this->_request, $defMethod);
        $this->view->rpc = $rpc;

        if (! $rpc->isValid())
            return;

        $method = strtolower($rpc->getMethod());

        /*
        Connexions_Profile::checkpoint('Connexions',
                                       'IndexController::_jsonContent: '
                                       . "method[ {$method} ]");
        // */

        switch ($method)
        {
        case 'create':
            $this->_create($rpc);
            break;

        case 'read':
            $this->_read($rpc);
            break;

        case 'update':
            $this->_update($rpc);
            break;

        case 'delete':
            $this->_delete($rpc);
            break;

        case 'autocomplete':
            /* Autocompletion callback for tag entry
             *
             * Locate all tags associated with the current bookmarks that
             * also match the beginning of the completion string.
             */
            $tagSet = $this->_bookmarks->getRelatedSet('tags');

            // Retrieve the term we're supposed to match
            $like = $rpc->getParam('term', $rpc->getParam('q', null));
            if (empty($like))
            {
                // No term was provided -- limit to 500 entries
                $tagSet->limit(500);
            }
            else
            {
                // Limit to tags that look like the requested term
                $tagSet->like($like);
            }

            $tags = array();
            foreach ($tagSet as $tag)
            {
                array_push($tags, $tag->tag);
            }

            $rpc->setResult( $tags );
            break;

        default:
            // Unhandled JSON-RPC method
            $rpc->setError("Unknown method '{}'",
                           Zend_Json_Server_Error::ERROR_INVALID_METHOD);
            break;
        }
    }

    /** @brief  Generate HTML for the primary body/content based upon the
     *          incoming request.
     *
     *  This will create a 'paginator' for the previously created _bookmarks
     *  set, initialize the View_Helper_HtmlBookmarks and
     *  View_Helper_HtmlItemScope view helpers, and populate any
     *  additional view variables all based upon the incoming request.
     */
    protected function _htmlContent()
    {
        return;

        $request =& $this->_request;

        /* Prepare for rendering the main view.
         *
         * Notify the HtmlBookmarks View Helper (used to render the main view)
         * of any incoming settings, allowing it establish any required
         * defaults.
         */
        $prefix           = 'items';
        $itemsStyle       = $request->getParam($prefix."OptionGroup");
        $itemsStyleCustom = $request->getParam($prefix."OptionGroups_option");

        $perPage          = $request->getParam($namespace ."PerPage");
        $page             = $request->getParam($namespace ."Page");
        $sortBy           = $request->getParam($namespace ."SortBy");
        $sortOrder        = $request->getParam($namespace ."SortOrder");

        /*
        Connexions::log('IndexController::_htmlContent(): '
                        .   'itemsStyle[ %s ], options[ %s ]',
                        $itemsStyle, Connexions::varExport($itemsStyleCustom));
        // */

        if ( ($itemsStyle === 'custom') && (is_array($itemsStyleCustom)) )
            $itemsStyle = $itemsStyleCustom;

        /* Generate a paginator for the requested item set.  This will also
         * initialize '_page, '_perPage', and '_sortOrder'
        $paginator = $this->_createPaginator($request, $prefix);
         */

        /*
        Connexions_Profile::checkpoint('Connexions',
                                       'IndexController::_htmlContent: '
                                       . 'HtmlItemScope Helper initialized');
        // */


        // Additional view variables for the HTML view.
        $this->view->main = array(
            'namespace'     => $prefix,
            'displayStyle'  => $itemsStyle,
            'perPage'       => $perPage,
            'page'          => $page,
            'sortBy'        => $sortBy,
            'sortOrder'     => $sortOrder,
        );

        /* The default view script (views/scripts/index/index.phtml) will
         * render this main view
         */
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
        return;

        if (($part === null) || ($part === 'tags'))
        {
            $this->_htmlSidebar_prepareTags();
        }

        switch ($part)
        {
        case 'tags':
            $this->render('sidebar-tags');
            break;

        case 'people':
            $this->render('sidebar-people');
            break;

        default:
            // Render the entire sidebar
            if ($usePlaceholder === true)
            {
                // Render the sidebar into the 'right' placeholder
                $this->view->renderToPlaceholder('index/sidebar.phtml',
                                                 'right');

                /*
                Connexions_Profile::checkpoint('Connexions',
                                               'IndexController::_htmlSidebar: '
                                               . 'rendered to placeholder');
                // */
            }
            else
            {
                    $this->render('sidebar');
            }
            break;
        }

    }

    /** @brief  Create a set of tags related to the current bookmarks and
     *          prepare the View_Helper_HtmlItemCloud view helper to
     *          render them.
     */
    protected function _htmlSidebar_prepareTags()
    {
        $request =& $this->_request;

        $prefix             = 'sbTags';
        $tagsPerPage        = $request->getParam($prefix."PerPage",     100);
        $tagsPage           = $request->getParam($prefix."Page",        1);
        $tagsHighlightCount = $request->getParam($prefix."HighlightCount",
                                                                        null);
        $tagsSortBy         = $request->getParam($prefix."SortBy",      'tag');
        $tagsSortOrder      = $request->getParam($prefix."SortOrder",   null);
        $tagsStyle          = $request->getParam($prefix."OptionGroup", null);

        /* Order for limited retrieval should user the default sort order.
         *
         * Once the limited set if retrieved, THEN apply
         * $tagsSortBy/$tagsSortOrder
         */
        $fetchOrder = null;
        $count      = $tagsPerPage;
        $offset     = ($tagsPage > 0
                        ? ($tagsPage - 1) * $tagsPerPage
                        : 0);

        /* Create the tagSet that will be presented in the side-bar:
         *      All tags used by all users/items contained in the current
         *      bookmark set.
         *
         *  $tagSet = new Model_TagSet( $this->_userSet->userIds(),
         *                              $this->_userSet->itemIds() );
         */
        if (count($this->_tags) < 1)
        {
            /* There were no requested tags that limited the bookmark
             * retrieval, so for the sidebar, retrieve ALL tags...
             */
            if ($this->_owner === '*')
            {
                // ... of ALL users
                $tagSet = $this->service('Tag')->fetch(null,    // no criteria
                                                       $fetchOrder,
                                                       $count,
                                                       $offset);
            }
            else
            {
                // ... of a single user
                $tagSet = $this->service('Tag')
                                    ->fetchByUsers( $this->_owner,
                                                    $fetchOrder,
                                                    $count,
                                                    $offset );
            }
        }
        else
        {
            // Tags related to the given bookmarks
            $tagSet = $this->service('Tag')
                                ->fetchByBookmarks( $this->_bookmarks,
                                                    $fetchOrder,
                                                    $count,
                                                    $offset );
        }

        /*
        $tagSet = $this->service('Tag')->fetchByBookmarks($this->_bookmarks);
        if ($this->_owner === '*')
            $tagSet->withAnyUser();
         */


        /* Prepare to render the tags in the sidebar.
         *
         * Notify the HtmlItemCloud View Helper
         * (used to render the right column) of any incoming settings, allowing
         * it establish any required defaults.
         */

        // Initialize the View_Helper_HtmlItemCloud helper...
        $cloudHelper = $this->view->htmlItemCloud();
        $cloudHelper->setNamespace($prefix)
                    ->setStyle($tagsStyle)
                    ->setItemType(View_Helper_HtmlItemCloud::ITEM_TYPE_TAG)
                    ->setSortBy($tagsSortBy)
                    ->setSortOrder($tagsSortOrder)
                    ->setPerPage($tagsPerPage)
                    ->setHighlightCount($tagsHighlightCount)
                    ->setItemSet($tagSet)
                    ->setItemSetInfo($this->_tags)
                    ->setItemBaseUrl( ($this->_owner !== '*'
                                        ? null
                                        : '/bookmarks'));
    }
}
