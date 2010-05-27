<?php
/** @file
 *
 *  This controller controls access to the "inbox" for users or user-groups.
 *  It may be access via the url/routes:
 *      /inbox/<user>[/<tag list>]]
 */

class InboxController extends Zend_Controller_Action
{
    protected   $_viewer    = null;
    protected   $_owner     = null;
    protected   $_forTag    = null;
    protected   $_tagInfo   = null;
    protected   $_userItems = null;
    protected   $_tagIds    = null;

    public function indexAction()
    {
        $this->_viewer =& Zend_Registry::get('user');

        $request       = $this->getRequest();
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


        if ( (! $this->_owner instanceof Model_User) &&
             (! empty($this->_owner)) )
        {
            /* :TODO: Allow user-groups to be named here.
             *
             * Is this a valid user?
             */
            $ownerInst = Model_User::find(array('name' => $this->_owner));
            if ($ownerInst->isBacked())
            {
                /*
                Connexions::log("IndexController:: Valid ".
                                        "owner[ {$ownerInst->name} ]");
                // */

                $this->_owner =& $ownerInst;
            }
            else
            {
                // Invalid user!
                /*
                Connexions::log("IndexController:: "
                                    . "Unknown User with tags; "
                                    . "set owner to '*'");
                // */

                $this->view->error = "Unknown user [ {$this->_owner} ].";
            }
        }

        /* :TODO: Allow filtering by "sender" (i.e. the person that tagged
         *        items for this inbox).
         */


        /* :TODO: Is 'viewer' allowed to see the inbox of 'owner'??
         *        - For a user       inbox,
         *          or  a user-group inbox with visibility:private
         *                              ONLY the owner may view
         *        - For a user-group inbox with visibility:group
         *                              ONLY members of the group may view
         *        - For a user-group inbox with visibility:public
         *                              anyone can view
         *
         * For now, only the owner may view.
         */
        if ( ( ! $this->_owner instanceof Model_User) ||
             ($this->_owner->userId !== $this->_viewer->userId) )
        {
            // Redirect to the viewer's inbox
            return $this->_forward('index', 'inbox', null,
                                   array('owner' => $this->_viewer->name));
        }
        $forTagStr = 'for:'. $this->_owner->name;
        $this->_forTag    = new Model_Tag($forTagStr);

        /*
        Connexions::log("InboxController:: forTag[ "
                            . $this->_forTag->debugDump() . " ]");
        // */


        // Parse the incoming request tags
        $this->_tagInfo = new Connexions_Set_Info($reqTags, 'Model_Tag');
        if ($this->_tagInfo->hasInvalidItems())
            $this->view->error =
                        "Invalid tag(s) [ {$this->_tagInfo->invalidItems} ]";

        /*
        Connexions::log("InboxController:: tagInfo: "
                        .   "itemClass[ %s ], reqStr[ %s ], "
                        .   "validItems[ %s ], invalidItems[ %s ]",
                        $this->_tagInfo->itemClass,
                        $this->_tagInfo->reqStr,
                        $this->_tagInfo->validItems,
                        $this->_tagInfo->invalidItems);
        // */

        /* Create the userItem set, scoped by any incoming valid tags from
         * ALL users.
         */
        $this->_tagIds = $this->_tagInfo->validIds;
        array_push($this->_tagIds, $this->_forTag->tagId);

        /*
        Connexions::log("InboxController:: tagIds[ "
                            . implode(', ', $this->_tagIds) ." ]");
        // */

        $this->_userItems = new Model_UserItemSet($this->_tagIds);


        $this->_htmlContent();
        $this->_htmlSidebar();
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

            Connexions::log("InboxController::__call({$method}): "
                                           . "owner[ {$owner} ], "
                                           . "parameters[ "
                                           .    $request->getParam('tags','')
                                           .        " ]");
            // */

            return $this->_forward('index', 'inbox', null,
                                   array('owner' => $owner));
        }

        throw new Exception('Invalid method "'. $method .'" called', 500);
    }

    /*************************************************************************
     * Context-specific view initialization and invocation
     *
     */
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
        $prefix           = 'inboxItems';
        $itemsPerPage     = $request->getParam($prefix."PerPage",       null);
        $itemsSortBy      = $request->getParam($prefix."SortBy",        null);
        $itemsSortOrder   = $request->getParam($prefix."SortOrder",     null);
        $itemsStyle       = $request->getParam($prefix."OptionGroup",   null);
        $itemsStyleCustom = $request->getParam($prefix."OptionGroups_option",
                                                                        null);

        /*
        Connexions::log('InboxController::'
                            . 'prefix [ '. $prefix .' ], '
                            . 'params [ '
                            .   print_r($request->getParams(), true) ." ],\n"
                            . "    PerPage        [ {$itemsPerPage} ],\n"
                            . "    SortBy         [ {$itemsSortBy} ],\n"
                            . "    SortOrder      [ {$itemsSortOrder} ],\n"
                            . "    Style          [ {$itemsStyle} ],\n"
                            . "    StyleCustom    [ "
                            .           print_r($itemsStyleCustom, true) .' ]');
        // */

        // Initialize the Connexions_View_Helper_HtmlUserItems helper...
        $uiHelper = $this->view->htmlUserItems();
        $uiHelper->setNamespace($prefix)
                 ->setSortBy($itemsSortBy)
                 ->setSortOrder($itemsSortOrder);
        if (is_array($itemsStyleCustom))
            $uiHelper->setStyle(Connexions_View_Helper_HtmlUserItems
                                                            ::STYLE_CUSTOM,
                                $itemsStyleCustom);
        else
            $uiHelper->setStyle($itemsStyle);

        $uiHelper->setMultipleUsers();


        // Set Scope information
        $scopeParts  = array('format=json');
        if ($this->_tagInfo->hasValidItems())
        {
            array_push($scopeParts, 'tags='. $this->_tagInfo->validItems);
        }

        $inboxUrl    = $this->view->baseUrl('/inbox/'. $this->_owner->name);
        $scopeCbUrl  = $this->view->baseUrl('/scopeAutoComplete')
                     . '?'. implode('&', $scopeParts);

        $scopeHelper = $this->view->htmlItemScope();
        $scopeHelper->setNamespace($prefix)
                    ->setInputLabel('Tags')
                    ->setInputName( 'tags')
                    ->setPath(array('Inbox' => $inboxUrl))
                    ->setAutoCompleteUrl( $scopeCbUrl );


        /* Ensure that the final sort information is properly reflected in
         * the source set.
         */
        $this->_userItems->setOrder( $uiHelper->getSortBy() .' '.
                                     $uiHelper->getSortOrder() );

        // /*
        Connexions::log("InboxController:: updated params:\n"
                            . '    SortBy         [ '
                            .           $uiHelper->getSortBy() ." ],\n"
                            . '    SortOrder      [ '
                            .           $uiHelper->getSortOrder() ." ],\n"
                            . '    Style          [ '
                            .           $uiHelper->getStyle() ." ],\n"
                            . '    ShowMeta       [ '
                            .           print_r($uiHelper->getShowMeta(),
                                                true) .' ]');
        // */


        /* Use the Connexions_Controller_Action_Helper_Pager to create a
         * paginator for the retrieved user items / bookmarks.
         */
        $page      = $request->getParam('page',  null);
        $paginator = $this->_helper->Pager($this->_userItems,
                                           $page, $itemsPerPage);

        /********************************************************************
         * Set the required view variables and render this main view
         *
         */
        $this->view->owner     = $this->_owner;
        $this->view->viewer    = $this->_viewer;
        $this->view->tagInfo   = $this->_tagInfo;
        $this->view->paginator = $paginator;
    }

    protected function _htmlSidebar()
    {
        $request =& $this->getRequest();
        $layout  =& $this->view->layout();

        /* Create the tagSet that will be presented in the side-bar:
         *      All tags used by all users/items contained in the current
         *      user item / bookmark set.
         *
         *  $tagSet = new Model_TagSet( $this->_userItems->userIds(),
         *                              $this->_userItems->itemIds() );
         *  $tagSet->withAnyUser();
         */
         $tagSet = $this->_userItems
                            ->getRelatedSet(Connexions_Set::RELATED_TAGS)
                            ->withAnyUser();

        /* Create the userSet that will be presented in the side-bar:
         *      All users that have tagged something for this user.
         *
         *  $senderSet = new Model_UserSet( $this->_tagIds,
         *                                  $this->_userItems->itemIds(),
         *                                  $this->_userItems->userIds() );
         *  $senderSet->weightBy('item');
         */
        $senderSet = $this->_userItems
                            ->getRelatedSet(Connexions_Set::RELATED_USERS,
                                            $this->_tagIds)
                            ->weightBy('item');
            
        /********************************************************************
         * Prepare for rendering the right column.
         *
         * Notify the HtmlItemCloud View Helper
         * (used to render the right column) of any incoming settings, allowing
         * it establish any required defaults.
         */
        $prefix             = 'sbTags';
        $tagsPerPage        = $request->getParam($prefix."PerPage",     100);
        $tagsHighlightCount = $request->getParam($prefix."HighlightCount",
                                                                        null);
        $tagsSortBy         = $request->getParam($prefix."SortBy",      'tag');
        $tagsSortOrder      = $request->getParam($prefix."SortOrder",   null);
        $tagsStyle          = $request->getParam($prefix."OptionGroup", null);

        /*
        Connexions::log('InboxController::'
                            . "right-column prefix [ {$prefix} ],\n"
                            . "    PerPage        [ {$tagsPerPage} ],\n"
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
                                                            ITEM_TYPE_ITEM)
                    ->setSortBy($tagsSortBy)
                    ->setSortOrder($tagsSortOrder)
                    ->setPerPage($tagsPerPage)
                    ->setHighlightCount($tagsHighlightCount)
                    ->setItemSet($tagSet)
                    ->setItemSetInfo($this->_tagInfo)
                    ->setItemBaseUrl( ($this->_owner !== '*'
                                        ? null
                                        : '/bookmarks'))
                    // Do NOT show the 'for:<user>' tag
                    ->addHiddenItem($this->_forTag->tag);  //$forTagStr);


        // Set the additional required view variables
        $this->view->senderSet = $senderSet;


        // Render the sidebar into the 'right' placeholder
        $this->view->renderToPlaceholder('inbox/sidebar.phtml', 'right');
    }
}
