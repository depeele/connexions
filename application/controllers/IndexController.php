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

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $viewer   =& Zend_Registry::get('user');

        $request   = $this->getRequest();
        $owner     = $request->getParam('owner',     null);
        $reqTags   = $request->getParam('tags',      null);

        /* If this is a user/"owned" area (e.g. /<userName> [/ <tags ...>]),
         * verify the validity of the requested user.
         */
        if ($owner === 'mine')
        {
            // 'mine' == the currently authenticated user
            $owner =& $viewer;
            if ( ( ! $owner instanceof Model_User) ||
                 (! $owner->isAuthenticated()) )
            {
                // Unauthenticated user -- Redirect to signIn
                return $this->_helper->redirector('signIn','auth');
            }
        }

        $ownerIds = null;
        if (! $owner instanceof Model_User)
        {
            if (@empty($owner))
                $owner = '*';
            else if ($owner !== '*')
            {
                // Is this a valid user?
                $ownerInst = Model_User::find(array('name' => $owner));
                if ($ownerInst->isBacked())
                {
                    /*
                    Connexions::log("IndexController:: Valid ".
                                            "owner[ {$ownerInst->name} ]");
                    // */

                    $owner    =& $ownerInst;
                    $ownerIds =  array($owner->userId);
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
                                            . "[ {$owner} ] "
                                            . "and set owner to '*'");
                        // */
                        $reqTags  = $owner;
                        $owner    = '*';
                    }
                    else
                    {
                        // Invalid user!
                        /*
                        Connexions::log("IndexController:: "
                                            . "Unknown User with tags; "
                                            . "set owner to '*'");
                        // */

                        $this->view->error = "Unknown user [ {$owner} ].";
                        $owner             = '*';
                    }
                }
            }
        }

        // Parse the incoming request tags
        $tagInfo = new Connexions_Set_Info($reqTags, 'Model_Tag');
        if ($tagInfo->hasInvalidItems())
            $this->view->error =
                        "Invalid tag(s) [ {$tagInfo->invalidItems} ]";

        /* Create the userItem set, scoped by any incoming valid tags and
         * possibly the owner of the area.
         */
        $userItems = new Model_UserItemSet($tagInfo->validIds, $ownerIds);


        /* Create the tagSet that will be presented in the side-bar:
         *      All tags used by all users/items contained in the current
         *      user item / bookmark set.
         */
        $tagSet = new Model_TagSet( $userItems->userIds(),
                                    $userItems->itemIds() );
        if ($owner === '*')
            $tagSet->withAnyUser();


        /********************************************************************
         * Prepare for rendering the main view.
         *
         * Notify the HtmlUserItems View Helper (used to render the main view)
         * of any incoming settings, allowing it establish any required
         * defaults.
         */
        $itemsPrefix      = 'items';
        $itemsPerPage     = $request->getParam($itemsPrefix."PerPage",   null);
        $itemsStyle       = $request->getParam($itemsPrefix."Style",     null);
        $itemsSortBy      = $request->getParam($itemsPrefix."SortBy",    null);
        $itemsSortOrder   = $request->getParam($itemsPrefix."SortOrder", null);
        $itemsStyleCustom = $request->getParam($itemsPrefix."StyleCustom",
                                                                         null);

        $uiHelper = $this->view->htmlUserItems();
        $uiHelper->setNamespace($itemsPrefix)
                 ->setSortBy($itemsSortBy)
                 ->setSortOrder($itemsSortOrder);
        if (is_array($itemsStyleCustom))
            $uiHelper->setStyle(Connexions_View_Helper_HtmlUserItems
                                                            ::STYLE_CUSTOM,
                                $itemsStyleCustom);
        else
            $uiHelper->setStyle($itemsStyle);

        /*
        Connexions::log("IndexController: uiHelper updated sort "
                            . "by[ {$uiHelper->getSortBy() } ], "
                            . "order[ {$uiHelper->getSortOrder() } ]");
        // */

        /* Ensure that the final sort information is properly reflected in
         * the source set.
         */
        $userItems->setOrder( $uiHelper->getSortBy(),
                              $uiHelper->getSortOrder() );

        /* Use the Connexions_Controller_Action_Helper_Pager to create a
         * paginator for the retrieved user items / bookmarks.
         */
        $page      = $request->getParam('page',  null);
        $paginator = $this->_helper->Pager($userItems, $page, $itemsPerPage);


        /********************************************************************
         * Prepare for rendering the right column.
         *
         * Notify the HtmlItemCloud View Helper
         * (used to render the right column) of any incoming settings, allowing
         * it establish any required defaults.
         */
        $tagsPrefix         = 'sbTags';
        $tagsPerPage        = $request->getParam($tagsPrefix."PerPage",  100);
        $tagsStyle          = $request->getParam($tagsPrefix."Style",    null);
        $tagsHighlightCount = $request->getParam($tagsPrefix."HighlightCount",
                                                                         null);
        $tagsSortBy         = $request->getParam($tagsPrefix."SortBy",  'tag');
        $tagsSortOrder      = $request->getParam($tagsPrefix."SortOrder",null);

        $cloudHelper = $this->view->htmlItemCloud();
        $cloudHelper->setNamespace($tagsPrefix)
                    ->setStyle($tagsStyle)
                    ->setItemType(Connexions_View_Helper_HtmlItemCloud::
                                                            ITEM_TYPE_TAG)
                    ->setSortBy($tagsSortBy)
                    ->setSortOrder($tagsSortOrder)
                    ->setHighlightCount($tagsHighlightCount);

        /* Retrieve the Connexions_Set_ItemList instance required by
         * Zend_Tag_Cloud to render this tag set as a cloud
         */
        $tagList = $tagSet->get_Tag_ItemList(0, $tagsPerPage, $tagInfo,
                                             ($owner !== '*'
                                                ? null
                                                : '/tagged'));


        /********************************************************************
         * Set the required view variables
         *
         */
        $this->view->owner     = $owner;
        $this->view->viewer    = $viewer;
        $this->view->tagInfo   = $tagInfo;

        $this->view->paginator = $paginator;
        $this->view->tagList   = $tagList;
    }

    /** @brief  A JSON-RPC callback to retrieve auto-completion results for 
     *          Scope Item Entry.
     *
     *  Valid incoming parameters:
     *      owner   User name that limits scope;
     *      tags    A comma-separated list of request tags that limit scope;
     *      q       The string that is being auto-completed;
     *      limit   The maximum number of items to return;
     *
     *  @return void    (Outputs JSON-RPC result data).
     */
    public function scopeautocompleteAction()
    {
        $request   = $this->getRequest();
        if (($this->_getParam('format', false) !== 'json') ||
            ($request->isPost()) )
        {
            return $this->_helper->redirector('index', 'index');
        }

        // Grab the JsonRpc helper
        $jsonRpc = $this->_helper->getHelper('JsonRpc');

        // Is there a JSONP callback specified?
        $jsonp    = trim($request->getQuery('jsonp', ''));
        if (! empty($jsonp))
            $jsonRpc->setCallback($jsonp);



        $viewer   =& Zend_Registry::get('user');

        $owner     = $request->getParam('owner', null);
        $reqTags   = $request->getParam('tags',  null);

        /* If this is a user/"owned" area (e.g. /<userName> [/ <tags ...>]),
         * verify the validity of the requested user.
         */
        if ($owner === 'mine')
        {
            // No user specified -- use the currently authenticated user
            $owner =& $viewer;
            if ( ( ! $owner instanceof Model_User) ||
                 (! $owner->isAuthenticated()) )
            {
                // Unauthenticated user -- Redirect to signIn
                //return $this->_helper->redirector('signIn','auth');
                $jsonRpc->setError("Unauthenticated user for 'mine'.");
            }
        }

        $ownerIds = null;
        if ( (! $jsonRpc->hasError()) && (! $owner instanceof Model_User) )
        {
            if (@empty($owner))
                $owner = '*';
            else if ($owner !== '*')
            {
                // Is this a valid user?
                $ownerInst = Model_User::find(array('name' => $owner));
                if ($ownerInst->isBacked())
                {
                    /*
                    Connexions::log("IndexController::ScopeAutoComplete Valid ".
                                            "owner[ {$ownerInst->name} ]");
                    // */

                    $owner    =& $ownerInst;
                    $ownerIds =  array($owner->userId);
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
                        Connexions::log("IndexController::ScopeAutoComplete "
                                            . "Unknown User and no tags; "
                                            . "use owner as tags "
                                            . "[ {$owner} ] "
                                            . "and set owner to '*'");
                        // */
                        $reqTags  = $owner;
                        $owner    = '*';
                    }
                    else
                    {
                        // Invalid user!
                        /*
                        Connexions::log("IndexController::ScopeAutoComplete "
                                            . "Unknown User with tags; "
                                            . "set owner to '*'");
                        // */

                        //$jsonRpc->setError("Unknown user [ {$owner} ].");
                        $owner = '*';
                    }
                }
            }
        }

        if ($jsonRpc->hasError())
        {
            return $jsonRpc->sendResponse();
        }


        // Parse the incoming request tags
        $tagInfo   = new Connexions_Set_Info($reqTags, 'Model_Tag');
        if ($tagInfo->hasInvalidItems())
            $jsonRpc->setError("Invalid tag(s) [ {$tagInfo->invalidItems} ]");

        /* Create the userItem set, scoped by any incoming valid tags and
         * possibly the owner of the area.
         */
        $userItems = new Model_UserItemSet($tagInfo->validIds, $ownerIds);

        /* Create the tagSet that represents:
         *      All tags used by all users/items contained in the current
         *      user item / bookmark set.
         *
         * These are the items available for scoping.
         */
        $tagSet = new Model_TagSet( $userItems->userIds(),
                                    $userItems->itemIds() );
        if ($owner === '*')
            $tagSet->withAnyUser();


        /********************************************************************
         * Prepare for rendering the JSON-RPC results.
         *
         */
        $like  = $request->getParam('q',     null);
        $limit = $request->getParam('limit', 250);
        if (! empty($like))
            $tagSet->like($like);
        if ($limit > 0)
            $tagSet = $tagSet->getItems(0, $limit);


        // Convert the matching tags to the array required by auto-completion
        $scopeData = array();
        foreach ($tagSet as $item)
        {
            $str = $item->__toString();

            if ($tagInfo->isValidItem($str))
                continue;

            array_push($scopeData, array('value' => $str));
        }

        $jsonRpc->setResult($scopeData);

        /*
        Connexions::log(sprintf("IndexController::scopeAutoCompleteAction: "
                                . "scopeData[ %s ]",
                                var_export($scopeData, true)) );
        // */

        /*  Render the JSON-RPC response.
         *      Note that this disables layouts and views, set the proper
         *      Content-Type and outputs the JSON-RPC response.
         */
        return $jsonRpc->sendResponse();
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
}
