<?php
/** @file
 *
 *  This controller controls access to the "inbox" for users or user-groups.
 *  It may be access via the url/routes:
 *      /inbox/<user>[/<tag list>]]
 */

class InboxController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $viewer   =& Zend_Registry::get('user');

        $request  = $this->getRequest();
        $owner    = $request->getParam('owner',     null);
        $reqTags  = $request->getParam('tags',      null);

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


        if ( (! $owner instanceof Model_User) && (! empty($owner)) )
        {
            /* :TODO: Allow user-groups to be named here.
             *
             * Is this a valid user?
             */
            $ownerInst = Model_User::find(array('name' => $owner));
            if ($ownerInst->isBacked())
            {
                /*
                Connexions::log("IndexController:: Valid ".
                                        "owner[ {$ownerInst->name} ]");
                // */

                $owner =& $ownerInst;
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
            }
        }



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
        if ( ( ! $owner instanceof Model_User) ||
             ($owner->userId !== $viewer->userId) )
        {
            // Redirect to the viewer's inbox
            return $this->_forward('index', 'inbox', null,
                                   array('owner' => $viewer->name));
        }
        $forTagStr = 'for:'. $owner->name;
        $forTag    = new Model_Tag($forTagStr);

        /*
        Connexions::log("InboxController:: forTag[ "
                            . $forTag->debugDump() . " ]");
        // */


        // Parse the incoming request tags
        $tagInfo = new Connexions_Set_Info($reqTags, 'Model_Tag');
        if ($tagInfo->hasInvalidItems())
            $this->view->error =
                        "Invalid tag(s) [ {$tagInfo->invalidItems} ]";

        /*
        Connexions::log(sprintf("InboxController:: tagInfo: "
                                .   "itemClass[ %s ], reqStr[ %s ], "
                                .   "validItems[ %s ], invalidItems[ %s ]",
                                $tagInfo->itemClass,
                                $tagInfo->reqStr,
                                $tagInfo->validItems,
                                $tagInfo->invalidItems));
        // */

        /* Create the userItem set, scoped by any incoming valid tags from
         * ALL users.
         */
        $tagIds = $tagInfo->validIds;
        array_push($tagIds, $forTag->tagId);

        /*
        Connexions::log("InboxController:: tagIds[ "
                            . implode(', ', $tagIds) ." ]");
        // */

        $userItems = new Model_UserItemSet($tagIds);


        /* Create the tagSet that will be presented in the side-bar:
         *      All tags used by all users/items contained in the current
         *      user item / bookmark set.
         */
        $tagSet = new Model_TagSet( $userItems->userIds(),
                                    $userItems->itemIds() );
        $tagSet->withAnyUser();

        /* Create the userSet that will be presented in the side-bar:
         *      All users that have tagged something for this user.
         */
        $senderSet = new Model_UserSet( $tagIds,
                                        $userItems->itemIds(),
                                        $userItems->userIds() );
        $senderSet->weightBy('item');


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

        // Set Scope information
        $scopeHelper = $this->view->htmlItemScope();

        $uiHelper->setMultipleUsers();

        $scopeHelper->setPath(array('Inbox' =>
                                        $this->view->baseUrl('/inbox/'.
                                                                $owner->name)));



        /* Ensure that the final sort information is properly reflected in
         * the source set.
         */
        $userItems->setOrder( $uiHelper->getSortBy() .' '.
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
        $paginator = $this->_helper->Pager($userItems, $page, $itemsPerPage);


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
                    ->setItemSetInfo($tagInfo)
                    ->setItemBaseUrl( ($owner !== '*'
                                        ? null
                                        : '/tagged'))
                    // Do NOT show the 'for:<user>' tag
                    ->addHiddenItem($forTagStr);

        /********************************************************************
         * Set the required view variables
         *
         */
        $this->view->owner     = $owner;
        $this->view->viewer    = $viewer;
        $this->view->tagInfo   = $tagInfo;

        $this->view->paginator = $paginator;
        $this->view->senderSet = $senderSet;
        $this->view->tagSet    = $tagSet;
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
}
