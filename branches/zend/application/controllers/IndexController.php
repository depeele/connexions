<?php
/** @file
 *
 *  This controller controls access to UserItems / Bookmarks and is accessed
 *  via the url/routes:
 *      /[<user>[/<tag list>]]
 *      /scopeAutoComplete?q=<query>
 *                          &limit=<max>
 *                          &format=json
 *                          &owner=<name>
 *                          &tags=<comma-separated tag list>
 */

class IndexController extends Zend_Controller_Action
{
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

        $this->_forward('index');

        // Initialize context switching
        $cs = $this->_helper->contextSwitch();
        $cs->initContext();

        /*
        $cs = $this->getHelper('contextSwitch');
        $cs->addActionContext('index', array('partial', 'json', 'rss', 'atom'))
           ->initContext();
        */
    }

    public function indexAction()
    {
        $request       =& $this->getRequest();

        $this->_owner  = $request->getParam('owner',     null);
        $reqTags       = $request->getParam('tags',      null);

        /* If this is a user/"owned" area (e.g. /<userName> [/ <tags ...>]),
         * verify the validity of the requested user.
         */
        if ($this->_owner === 'mine')
        {
            // 'mine' == the currently authenticated user
            $this->_owner =& $this->_viewer;
            if ( ( ! $this->_owner instanceof Model_User) ||
                 (! $this->_owner->isAuthenticated()) )
            {
                // Unauthenticated user -- Redirect to signIn
                return $this->_helper->redirector('signIn','auth');
            }
        }

        $ownerIds = null;
        if (! $this->_owner instanceof Model_User)
        {
            if (@empty($this->_owner))
                $this->_owner = '*';
            else if ($this->_owner !== '*')
            {
                // Is this a valid user?
                $ownerInst = Model_User::find(array('name' => $this->_owner));
                if ($ownerInst->isBacked())
                {
                    /*
                    Connexions::log("IndexController:: Valid ".
                                            "owner[ {$ownerInst->name} ]");
                    // */

                    $this->_owner =& $ownerInst;
                    $ownerIds     =  array($this->_owner->userId);
                }
                else
                {
                    /* NOT a valid user.
                     *
                     * If 'tags' wasn't spepcified, use 'owner' as 'tags'
                     */
                    if (empty($reqTags))
                    {
                        /*
                        Connexions::log("IndexController:: "
                                            . "Unknown User and no tags; "
                                            . "use owner as tags "
                                            . "[ {$this->_owner} ] "
                                            . "and set owner to '*'");
                        // */
                        $reqTags      = $this->_owner;
                        $this->_owner = '*';
                    }
                    else
                    {
                        // Invalid user!
                        /*
                        Connexions::log("IndexController:: "
                                            . "Unknown User with tags; "
                                            . "set owner to '*'");
                        // */

                        $this->view->error = "Unknown user [ "
                                           .        $this->_owner ." ].";
                        $this->_owner      = '*';
                    }
                }
            }
        }

        // Parse the incoming request tags
        $this->_tagInfo = new Connexions_Set_Info($reqTags, 'Model_Tag');
        if ($this->_tagInfo->hasInvalidItems())
            $this->view->error =
                        "Invalid tag(s) [ {$this->_tagInfo->invalidItems} ]";

        /* Create the userItem set, scoped by any incoming valid tags and
         * possibly the owner of the area.
         */
        $this->_userItems = new Model_UserItemSet($this->_tagInfo->validIds,
                                                  $ownerIds);


        // Set the view variables required for all views/layouts.
        $this->view->owner   = $this->_owner;
        $this->view->viewer  = $this->_viewer;
        $this->view->tagInfo = $this->_tagInfo;

        Connexions_Profile::checkpoint('Connexions',
                                       'IndexController::indexAction: '
                                       . 'User Item Set retrieved');

        // Handle this request based up 'format'
        $this->_handleFormat();
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

            /*
            $request = $this->getRequest();

            Connexions::log("IndexController::__call({$method}): "
                                           . "owner[ {$owner} ], "
                                           . "parameters[ "
                                           .    $request->getParam('tags','')
                                           .        " ]");
            // */

            return $this->_forward('index', 'index', null,
                                   array('owner' => $owner));
        }

        throw new Exception('Invalid method "'. $method .'" called', 500);
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
        $request =& $this->getRequest();

        $this->view->format  = $this->_helper
                                        ->contextSwitch()
                                            ->getCurrentContext();
        if (empty($this->view->format))
            $this->view->format = $request->getParam('format', 'html');

        Connexions::log("IndexController::_handleFormat(): "
                        . "format[ {$this->view->format} ]");

        switch ($this->view->format)
        {
        case 'partial':
            // Render just PART of the page
            $this->_helper->layout->setLayout('partial');

            $part = $request->getParam('part', 'content');
            switch ($part)
            {
            case 'sidebar':
                $this->_htmlSidebar(true);
                $this->render('sidebar');
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

        default:
            $this->_createPaginator($request);

            // Additional view variables for the alternate views.
            $this->view->paginator = $this->_paginator;
            break;
        }
    }

    /*************************************************************************
     * Context-specific view initialization and invocation
     *
     */

    protected function _createPaginator($req, $namespace = '')
    {
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
    }

    protected function _jsonContent()
    {
        $request =& $this->getRequest();

        $rpc = new Connexions_JsonRpc($request, 'get');
        $this->view->rpc = $rpc;

        if (! $rpc->isValid())
            return;

        $method = strtolower($rpc->getMethod());

        Connexions::log("IndexController::_jsonContent: "
                        . "method [ {$method} ]");

        switch ($method)
        {
        case 'get':
            $this->_createPaginator($rpc->getRequest());

            $items = array();
            foreach ($this->_paginator as $item)
            {
                array_push($items, $item->toArray(true));
            }

            $rpc->setResult( $items );
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
        $request =& $this->getRequest();
        $layout  =& $this->view->layout();

        /********************************************************************
         * Prepare for rendering the main view.
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
        $this->_createPaginator($request);

        // /*
        Connexions::log('IndexController::'
                            . 'prefix [ '. $prefix .' ], '
                            //. 'params [ '
                            //.   print_r($request->getParams(), true) ." ],\n"
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
        /**************************************************/


        // Set Scope information
        $scopeParts  = array('format=json',
                             'method=autocomplete');
        $scopePath   = array();
        if ($this->_owner === '*')
        {
            // Multiple / all users
            $uiHelper->setMultipleUsers();

            $scopePath = array('Bookmarks' => $this->view->baseUrl('/tagged'));
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


        // Additional view variables for the HTML view.
        $this->view->paginator = $this->_paginator;

        /* The default view script (views/scripts/index/index.phtml) will
         * render this main view
         */
        Connexions_Profile::checkpoint('Connexions',
                                       'IndexController::_htmlContent: '
                                       . 'view initialized and '
                                       . 'ready to render');
    }

    protected function _htmlSidebar($immediate = false)
    {
        $request =& $this->getRequest();

        /* Create the tagSet that will be presented in the side-bar:
         *      All tags used by all users/items contained in the current
         *      user item / bookmark set.
         *
         *  $tagSet = new Model_TagSet( $this->_userSet->userIds(),
         *                              $this->_userSet->itemIds() );
         */
        $tagSet = $this->_userItems
                            ->getRelatedSet(Connexions_Set::RELATED_TAGS);
        if ($this->_owner === '*')
            $tagSet->withAnyUser();


        /********************************************************************
         * Prepare for rendering the right column.
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

        /*
        Connexions::log('IndexController::'
                            . "right-column prefix [ {$prefix} ],\n"
                            . "    PerPage        [ {$tagsPerPage} ],\n"
                            . "    Page           [ {$tagsPage} ],\n"
                            . "    HighlightCount [ {$tagsHighlightCount} ],\n"
                            . "    SortBy         [ {$tagsSortBy} ],\n"
                            . "    SortOrder      [ {$tagsSortOrder} ],\n"
                            . "    Style          [ {$tagsStyle} ]");
        // */


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
                                        : '/tagged'));


        Connexions_Profile::checkpoint('Connexions',
                                       'IndexController::_htmlSidebar: '
                                       . 'view initialized and '
                                       . 'ready to render');

        if (! $immediate)
        {
            // Render the sidebar into the 'right' placeholder
            $this->view->renderToPlaceholder('index/sidebar.phtml', 'right');

            Connexions_Profile::checkpoint('Connexions',
                                           'IndexController::_htmlSidebar: '
                                           . 'rendered to placeholder');
        }
    }
}
