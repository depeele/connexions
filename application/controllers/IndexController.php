<?php
/** @file
 *
 *  This controller controls access to UserItems / Bookmarks and is accessed
 *  via the url/routes:
 *      /[ (<user> | bookmarks) [/<tag list>]]
 */

class IndexController extends Zend_Controller_Action
{
    const   CRUD_SUCCESS            = 0;
    const   CRUD_UNAUTHENTICATED    = 1;
    const   CRUD_INVALID_DATA       = 2;
    const   CRUD_BACKEND_FAILURE    = 3;


    protected   $_request   = null;
    protected   $_url       = null;
    protected   $_viewer    = null;
    protected   $_owner     = null;
    protected   $_tagInfo   = null;
    protected   $_userItems = null;
    protected   $_paginator = null;

    protected   $_page      = null;
    protected   $_perPage   = null;
    protected   $_sortBy    = null;
    protected   $_sortOrder = null;

    public      $contexts   = array(
                                'index' => array('partial', 'json',
                                                 'rss',     'atom'),
                              );

    public function init()
    {
        /* Initialize action controller here */
        $this->_viewer  =& Zend_Registry::get('user');

        //$this->_forward('index');

        // Initialize context switching
        $cs = $this->_helper->contextSwitch();
        $cs->initContext();

        $this->_request =& $this->getRequest();

        /*
        $cs = $this->getHelper('contextSwitch');
        $cs->addActionContext('index', array('partial', 'json', 'rss', 'atom'))
           ->initContext();
        */
    }

    /** @brief  Index/Get/View action.
     *
     *  Retrieve a set of userItems / Bookmarks based upon the requested
     *  'owner' and/or 'tags'.
     *
     *  Once retrieved, perform further setup based upon the current
     *  context/format.
     */
    public function indexAction()
    {
        Connexions::log("IndexController::indexAction");

        $request       =& $this->_request;

        $reqOwner      = $request->getParam('owner',     null);

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

        /***************************************************************
         * Process the requested 'owner' and 'tags'
         *
         */
        $reqTags  = $request->getParam('tags',      null);

        $ownerIds = null;
        $tagIds   = null;
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
                $ownerIds     = array($this->_owner->userId);
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

        // Parse the incoming request tags
        $this->_tagInfo = new Connexions_Set_Info($reqTags, 'Model_Tag');
        if ($this->_tagInfo->hasInvalidItems())
        {
            if (! empty($this->view->error))
                $this->view->error .= '<br />';
            $this->view->error .=
                        "Invalid tag(s) [ {$this->_tagInfo->invalidItems} ]";
        }
        else
        {
            $tagIds = $this->_tagInfo->validIds;
        }

        /***************************************************************
         * We now have a valid 'owner' (ownerIds) and 'tags' ($tagIds)
         *
         * Adjust the URL to reflect the validated 'owner' and 'tags'
         */
        $this->_url = $request->getBasePath()
                    . ($this->_owner instanceof Model_User
                        ? '/'. $this->_owner->name
                        : '')
                    . ($this->_tagInfo->hasValidItems()
                        ? '/'. $this->_tagInfo->validItems
                        : '')
                    . '/';

        /* Create the userItem set, scoped by any valid tags and possibly the
         * owner of the area.
         */
        $this->_userItems = new Model_UserItemSet($tagIds, $ownerIds);


        // Set the view variables required for all views/layouts.
        if ($this->_owner !== '*')
            $this->view->headTitle($this->owner ."'s Bookmarks");
        else
            $this->view->headTitle('Bookmarks');

        $this->view->url       = $this->_url;
        $this->view->owner     = $this->_owner;
        $this->view->viewer    = $this->_viewer;
        $this->view->tagInfo   = $this->_tagInfo;
        $this->view->userItems = $this->_userItems;

        // Handle this request based on the current context / format
        $this->_handleFormat();
    }

    public function postAction()
    {
        Connexions::log("IndexController::postAction");

        if ( (! $this->_viewer instanceof Model_User) ||
             (! $this->_viewer->isAuthenticated()) )
        {
            // Unauthenticated user -- Redirect to signIn
            return $this->_helper->redirector('signIn','auth');
        }

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

    /** @brief  Given an incoming request with userItem / Bookmark creation
     *          data, validate the request and, if valid, attempt to create a
     *          new userItem / Bookmark.
     *  @param  rpc     The incoming JsonRpc.
     *
     *  On failure/error, the rpc will have the appropriate error set.
     *
     *  @return A status code (self::CRUD_*).
     */
    protected function _create(Connexions_JsonRpc $rpc)
    {
        Connexions::log("IndexController::_create");

        if ( (! $this->_viewer instanceof Model_User) ||
             (! $this->_viewer->isAuthenticated()) )
        {
            // Unauthenticated user
            $rpc->setError('Unauthenticated.  Sign In to create bookmarks.');
            return self::CRUD_UNAUTHENTICATED;
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
            return self::CRUD_INVALID_DATA;
        }
        if (empty($itemInfo['url']))
        {
            $rpc->setError('The URL to Bookmark is required.');
            return self::CRUD_INVALID_DATA;
        }
        if (empty($itemInfo['tags']))
        {
            $rpc->setError('One or more tags are required.');
            return self::CRUD_INVALID_DATA;
        }

        /* VALID -- attempt to create the userItem / Bookmark.
         *
         *  1) See if an item exists for the given URL;
         *     a) NO  - create one;
         *     b) YES - use it;
         *  2) Fill in 'userId' and 'itemId' and create the userItem;
         *  3) Associate all tags with this userItem;
         *  4) Update statistics:
         *     a) item  userCount, ratingCount, ratingSum;
         *     b) user  totalItems, totalTags
         */

        // 1) Find / Create the item
        $item = new Model_Item( $itemInfo['url'] );
        if (! $item->isBacked())
        {
            // Save this new item.
            $item->save();
        }

        // 2) Find / Create the userItem
        $userItem = new Model_UserItem( array(
                            'userId' => $this->_viewer->userId,
                            'itemId' => $item->itemId) );
        if ($userItem->isBacked())
        {
            // The userItem / Bookmark already exists!  Update??
            $rpc->setError('Bookmark already exists.');
            return self::CRUD_INVALID_DATA;
        }

        // Save this new userItem.
        $userItem->save();

        // 3) Add tags to this userItem
        $userItem->addTags($itemInfo['tags']);

        // 4) Update statistics
        //    a) item  userCount, ratingCount, ratingSum;
        $item->userCount += 1;
        if ($userItem->rating > 0)
        {
            $item->ratingCount++;
            $item->ratingSum += $useritem->rating;
        }
        $item->save();

        // 4.b) user  totalItems, totalTags
        $this->_viewer->totalItems++;
        $this->_viewer->totalTags = count($this->_viewer->invalidateCache()
                                                        ->tags);
        $this->_viewer->save();

        $rpc->setResult('Bookmark created.');
        return self::CRUD_SUCCESS;
    }

    /** @brief  Given an incoming request with userItem / Bookmark update
     *          data, validate the request and, if valid, attempt to update an
     *          existing userItem / Bookmark.
     *  @param  rpc     The incoming JsonRpc.
     *
     *  On failure/error, the rpc will have the appropriate error set.
     *
     *  @return A status code (self::CRUD_*).
     */
    protected function _update(Connexions_JsonRpc $rpc)
    {
        Connexions::log("IndexController::_update");

        if ( (! $this->_viewer instanceof Model_User) ||
             (! $this->_viewer->isAuthenticated()) )
        {
            // Unauthenticated user
            $rpc->setError('Unauthenticated.  Sign In to update bookmarks.');
            return self::CRUD_UNAUTHENTICATED;
        }

        $itemInfo = array(
            'itemId'        => $rpc->getParam('itemId',      null),
            'name'          => $rpc->getParam('name',        null),
            'url'           => $rpc->getParam('url',         null),
            'description'   => $rpc->getParam('description', null),
            'tags'          => $rpc->getParam('tags',        null),
            'rating'        => $rpc->getParam('rating',      null),
            'isFavorite'    => $rpc->getParam('isFavorite',  false),
            'isPrivate'     => $rpc->getParam('isPrivate',   false)
        );

        // Validate and, if valid, attempt to update the usetItem / Bookmark.
        if (empty($itemInfo['itemId']))
        {
            $rpc->setError('Missing item identifier.');
            return self::CRUD_INVALID_DATA;
        }

        // Find the existing userItem / Bookmark
        $userItem = new Model_UserItem( array(
                            'userId' => $this->_viewer->userId,
                            'itemId' => $itemInfo['itemId']) );
        if (! $userItem->isBacked())
        {
            // NOT found -- create instead??
            $rpc->setError('No matching bookmark found.');
            return self::CRUD_INVALID_DATA;
        }

        if (empty($itemInfo['name']))
        {
            $rpc->setError('The Bookmark name / title is required.');
            return self::CRUD_INVALID_DATA;
        }
        if (empty($itemInfo['url']))
        {
            $rpc->setError('The URL to Bookmark is required.');
            return self::CRUD_INVALID_DATA;
        }
        if (empty($itemInfo['tags']))
        {
            $rpc->setError('One or more tags are required.');
            return self::CRUD_INVALID_DATA;
        }

        $itemInfo['urlHash'] = Connexions::md5Url($itemInfo['url']);

        /* VALID -- attempt to update an existing userItem / Bookmark...
         *
         *  1) Remove all current tags from this userItem / Bookmark;
         *  2) Find the current item as well as the item associated with the 
         *     incoming URL;
         *  3) Update item statistics, and save new item:
         *     a) item changed:
         *        i)  curItem:
         *            A)  userCount-
         *            B)  ratingCount-cur, ratingSum-cur
         *        ii) newItem
         *            A)  userCount+
         *            B)  ratingCount+new, ratingSum+new
         *
         *     b) item unchanged, has the rating changed?
         *        i)  item  ratingCount-cur, ratingSum-cur
         *        ii) item  ratingCount+new, ratingSum+new
         *
         *  4) Update full userItem / Bookmark based upon incoming data and 
         *     save it;
         *
         *  5) Associate all tags with this userItem / Bookmark;
         */

        // 1) Remove all current tags from this userItem / Bookmark;
        $userItem->removeTags();

        // 2) Find the current item as well as the item associated with the 
        //    incoming URL;
        $curItem = $userItem->item;
        $newItem = new Model_Item( $itemInfo['url'] );

        // 3) Update item statistics
        if ($curItem->itemId !== $newItem->itemId)
        {
            // 3.a.i.A) Update the current item user counts
            $curItem->userCount--;
            if ($userItem->rating > 0)
            {
                // 3.a.i.B) Update the current item rating
                $curItem->ratingCount--;
                $curItem->ratingSum -= $userItem->rating;
                $curItem->save();
            }

            // 3.a.ii.A) Update the new item user counts
            $newItem->userCount++;
        }
        else
        {
            // 3.b) item unchanged, has the rating changed?
            if ($userItem->rating !== $itemInfo['rating'])
            {
                if ($userItem->rating > 0)
                {
                    // 3.b.i) Remove old rating
                    $curItem->ratingCount--;
                    $curItem->ratingSum  -= $userItem->rating;
                    $curItem->save();
                }

                if ($itemInfo['rating'] > 0)
                {
                    // 3.b.ii) Include new rating
                    $newItem->ratingCount--;
                    $newItem->ratingSum  -= $itemInfo['rating'];
                }
            }
        }
        $newItem->save();


        // 4) Update full userItem / Bookmark based upon incoming data and save 
        //    it;
        $userItem->itemId      = $newItem->itemId;
        $userItem->name        = $itemInfo['name'];
        $userItem->url         = $itemInfo['url'];
        $userItem->description = $itemInfo['description'];
        $userItem->rating      = $itemInfo['rating'];
        $userItem->isFavorite  = $itemInfo['isFavorite'];
        $userItem->isPrivate   = $itemInfo['isPrivate'];

        $userItem->save();

        // 5) Associate all tags with this updated userItem / Bookmark;
        $userItem->addTags($itemInfo['tags']);

        $rpc->setResult('Bookmark Updated');
        return self::CRUD_SUCCESS;
    }

    /** @brief  Given incoming userItem / Bookmark identification information,
     *          validate the request and, if valid, attempt to delete an
     *          existing userItem / Bookmark.
     *  @param  rpc     The incoming JsonRpc.
     *
     *  On failure/error, the rpc will have the appropriate error set.
     *
     *  @return A status code (self::CRUD_*).
     */
    protected function _delete(Connexions_JsonRpc $rpc)
    {
        Connexions::log("IndexController::_delete");

        if ( (! $this->_viewer instanceof Model_User) ||
             (! $this->_viewer->isAuthenticated()) )
        {
            // Unauthenticated user
            $rpc->setError('Unauthenticated.  Sign In to delete bookmarks.');
            return self::CRUD_UNAUTHENTICATED;
        }

        $itemId = $rpc->getParam('itemId',      null);

        // Validate and, if valid, attempt to delete the userItem / Bookmar.
        if (empty($itemInfo['itemId']))
        {
            $rpc->setError('Missing item identifier.');
            return self::CRUD_INVALID_DATA;
        }

        // Find the existing userItem / Bookmark
        $userItem = new Model_UserItem( array(
                            'userId' => $this->_viewer->userId,
                            'itemId' => $itemInfo['itemId']) );
        if (! $userItem->isBacked())
        {
            // NOT found
            $rpc->setError('No matching bookmark found.');
            return self::CRUD_INVALID_DATA;
        }

        /* VALID -- attempt to delete this existing userItem / Bookmark...
         *
         *  1) Remove all current tags from this userItem / Bookmark;
         *  2) Delete the useritem / Bookmark;
         *  3) Update item statistics, and save new item:
         *     a) userCount-
         *     b) ratingCount-cur, ratingSum-cur
         */
        $rating  = $userItem->rating;
        $curItem = $userItem->item;

        // 1) Remove all current tags from this userItem / Bookmark;
        $userItem->removeTags();

        // 2) Delete the useritem / Bookmark;
        if (! $userItem->delete())
        {
            $rpc->setError( $userItem->getError() );
            return self::CRUD_INVALID_DATA;
        }

        // 3) Update item statistics
        if ($rating > 0)
        {
            // 2.b) rating
            $curItem->ratingCount--;
            $curItem->ratingSum -= $rating;
        }

        $curItem->userCount--;
        $curItem->save();


        $rpc->setResult('Bookmark Deleted');
        return self::CRUD_SUCCESS;
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
                $ownerInst = Model_User::find(array('name' => $name));
            }

            // Have we located a valid, backed user?
            if ($ownerInst->isBacked())
            {
                // YES -- we've located an existing user.

                $res = $ownerInst;
            }
        }

        return $res;
    }

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

        /*
        Connexions::log("IndexController::_handleFormat(): "
                        . "format[ {$format} ]");
        // */

        switch ($format)
        {
        case 'partial':
            /* Render just PART of the page and MAY not require the userItem
             * paginator.
             */
            $this->_helper->layout->setLayout('partial');

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
            $this->_createPaginator($this->_request);

            // Additional view variables for the alternate views.
            $this->view->paginator = $this->_paginator;
            $this->render('index');

            break;
        }
    }

    /*************************************************************************
     * Context-specific view initialization and invocation
     *
     */

    /** @brief  Create a paginator ($this->_paginator) for the current
     *          userItem / Bookmark set.
     *  @param  req         The request to retrieve parameters from.
     *  @param  namespace   The namespace.
     *
     *  This will ALSO adjust the sort order for _userItems and fill in the
     *  following members:
     *      _paginator
     *      _page
     *      _perPage
     *      _sortBy
     *      _sortOrder
     *
     *  @return void
     */
    protected function _createPaginator($req, $namespace = '')
    {
        /*
        Connexions_Profile::checkpoint('Connexions',
                                       'IndexController::_createPaginator: '
                                       . '%s: begin',
                                       $namespace);
        // */

        /* Retrieve any sort and paging parameters from the RPC request,
         * falling back to helper-controlled defaults.
         */
        $this->_page      = $req->getParam($namespace ."Page",    1);
        $this->_perPage   = $req->getParam($namespace ."PerPage",
                                    Connexions_View_Helper_UserItems::
                                                $defaults['perPage']);
        $this->_sortBy    = $req->getParam($namespace ."SortBy",
                                    Connexions_View_Helper_UserItems::
                                                $defaults['sortBy']);
        $this->_sortOrder = $req->getParam($namespace ."SortOrder",
                                    Connexions_View_Helper_UserItems::
                                                $defaults['sortOrder']);

        /* Ensure that the final sort information is properly reflected in
         * the source set.
         */
        $this->_userItems->setOrder( $this->_sortBy .' '. $this->_sortOrder );

        // Create a paginator
        $this->_paginator = $this->_helper->Pager($this->_userItems,
                                                  $this->_page,
                                                  $this->_perPage);

        // /*
        Connexions_Profile::checkpoint('Connexions',
                                       'IndexController::_createPaginator: '
                                       . '%s: page %d, perPage %d, '
                                       . '%d pages, %d/%d items: end',
                                       $namespace,
                                       $this->_page, $this->_perPage,
                                       count($this->_paginator),
                                       $this->_paginator->getCurrentItemCount(),
                                       count($this->_userItems));
        // */
    }

    /** @brief  Generate a JsonRPC from the incoming request, using a default
     *          method of 'read' and then perform any requested action.
     *
     *  This will populate $this->view->rpc for use in Bootstrap::jsonp_post()
     *  for final output.
     */
    protected function _jsonContent()
    {
        $rpc = new Connexions_JsonRpc($this->_request, 'read');
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
            $this->_createPaginator($rpc);

            $this->view->paginator = $this->_paginator;

            $this->render('index');
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
             * Locate all tags associated with the current userItems that
             * also match the beginning of the completion string.
             */
            $tagSet = $this->_userItems->getRelatedSet('tags');

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

    protected function _htmlContent()
    {
        $request =& $this->_request;

        /* Prepare for rendering the main view.
         *
         * Notify the HtmlUserItems View Helper (used to render the main view)
         * of any incoming settings, allowing it establish any required
         * defaults.
         */
        $prefix           = 'items';
        $itemsStyle       = $request->getParam($prefix."OptionGroup",   null);
        $itemsStyleCustom = $request->getParam($prefix."OptionGroups_option",
                                                                        null);

        /* Generate a paginator for the requested item set.  This will also
         * initialize '_page, '_perPage', '_sortBy', and '_sortOrder'
         */
        $this->_createPaginator($request, $prefix);

        /*
        Connexions::log('IndexController::'
                            . 'prefix [ '. $prefix .' ], '
                            . "    PerPage        [ {$this->_perPage} ],\n"
                            . "    Page           [ {$this->_page} ],\n"
                            . "    SortBy         [ {$this->_sortBy} ],\n"
                            . "    SortOrder      [ {$this->_sortOrder} ],\n"
                            . "    Style          [ {$itemsStyle} ],\n"
                            . "    StyleCustom    [ "
                            .           print_r($itemsStyleCustom, true) .' ]');
        // */

        // Initialize the Connexions_View_Helper_HtmlUserItems helper...
        $uiHelper = $this->view->htmlUserItems();
        $uiHelper->setNamespace($prefix)
                 ->setPerPage($this->_perPage)
                 ->setSortBy($this->_sortBy)
                 ->setSortOrder($this->_sortOrder);
        if (is_array($itemsStyleCustom))
            $uiHelper->setStyle(Connexions_View_Helper_HtmlUserItems
                                                            ::STYLE_CUSTOM,
                                $itemsStyleCustom);
        else
            $uiHelper->setStyle($itemsStyle);

        /*
        Connexions_Profile::checkpoint('Connexions',
                                       'IndexController::_htmlContent: '
                                       . 'HtmlUserItems helper initialized');
        // */

        /**************************************************/


        // Set Scope information
        $scopeParts  = array('format=json',
                             'method=autocomplete');
        $scopePath   = array();
        if ($this->_owner === '*')
        {
            // Multiple / all users
            $uiHelper->setMultipleUsers();

            $scopePath = array('Bookmarks' =>
                                    $this->view->baseUrl('/bookmarks'));
        }
        else
        {
            // Single user
            $ownerStr = (String)$this->_owner;

            $uiHelper->setSingleUser();

            $scopePath = array($ownerStr => $this->view->baseUrl($ownerStr));

            array_push($scopeParts, 'owner='. $ownerStr);
        }

        if ($this->_tagInfo->hasValidItems())
        {
            array_push($scopeParts, 'tags='. $this->_tagInfo->validItems);
        }

        $scopeCbUrl  = $this->view->url() .'?'. implode('&', $scopeParts);

        $scopeHelper = $this->view->htmlItemScope();
        $scopeHelper->setNamespace($prefix)
                    ->setInputLabel('Tags')
                    ->setInputName( 'tags')
                    ->setPath( $scopePath )
                    ->setAutoCompleteUrl( $scopeCbUrl );

        /*
        Connexions_Profile::checkpoint('Connexions',
                                       'IndexController::_htmlContent: '
                                       . 'HtmlItemScope Helper initialized');
        // */


        // Additional view variables for the HTML view.
        $this->view->paginator = $this->_paginator;

        /* The default view script (views/scripts/index/index.phtml) will
         * render this main view
         */
        /*
        Connexions_Profile::checkpoint('Connexions',
                                       'IndexController::_htmlContent: '
                                       . 'view initialized and '
                                       . 'ready to render');
        // */
    }

    protected function _htmlSidebar($usePlaceholder = true,
                                    $part           = null)
    {
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

    protected function _htmlSidebar_prepareTags()
    {
        $request =& $this->_request;

        /* Create the tagSet that will be presented in the side-bar:
         *      All tags used by all users/items contained in the current
         *      userItem / bookmark set.
         *
         *  $tagSet = new Model_TagSet( $this->_userSet->userIds(),
         *                              $this->_userSet->itemIds() );
         */
        $tagSet = $this->_userItems
                            ->getRelatedSet(Connexions_Set::RELATED_TAGS);
        if ($this->_owner === '*')
            $tagSet->withAnyUser();


        /* Prepare to render the tags in the sidebar.
         *
         * Notify the HtmlItemCloud View Helper
         * (used to render the right column) of any incoming settings, allowing
         * it establish any required defaults.
         */
        $prefix             = 'sbTags';
        $tagsPerPage        = $request->getParam($prefix."PerPage",     100);
        $tagsPage           = $request->getParam($prefix."Page",        1);
        $tagsHighlightCount = $request->getParam($prefix."HighlightCount",
                                                                        null);
        $tagsSortBy         = $request->getParam($prefix."SortBy",      'tag');
        $tagsSortOrder      = $request->getParam($prefix."SortOrder",   null);
        $tagsStyle          = $request->getParam($prefix."OptionGroup", null);

        // Initialize the Connexions_View_Helper_HtmlItemCloud helper...
        $cloudHelper = $this->view->htmlItemCloud();
        $cloudHelper->setNamespace($prefix)
                    ->setStyle($tagsStyle)
                    ->setItemType(Connexions_View_Helper_HtmlItemCloud::
                                                            ITEM_TYPE_TAG)
                    ->setSortBy($tagsSortBy)
                    ->setSortOrder($tagsSortOrder)
                    ->setPerPage($tagsPerPage)
                    ->setHighlightCount($tagsHighlightCount)
                    ->setItemSet($tagSet)
                    ->setItemSetInfo($this->_tagInfo)
                    ->setItemBaseUrl( ($this->_owner !== '*'
                                        ? null
                                        : '/bookmarks'));
    }
}
