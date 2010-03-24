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

    public function init()
    {
        /* Initialize action controller here */
        $this->_viewer  =& Zend_Registry::get('user');
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
        $prefix           = 'items';
        $itemsPerPage     = $request->getParam($prefix."PerPage",       null);
        $itemsSortBy      = $request->getParam($prefix."SortBy",        null);
        $itemsSortOrder   = $request->getParam($prefix."SortOrder",     null);
        $itemsStyle       = $request->getParam($prefix."OptionGroup",   null);
        $itemsStyleCustom = $request->getParam($prefix."OptionGroups_option",
                                                                        null);

        // /*
        Connexions::log('IndexController::'
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


        // Set Scope information
        $scopeHelper = $this->view->htmlItemScope();
        $scopeParts  = array('format=json');
        if ($this->_owner === '*')
        {
            // Multiple / all users
            $uiHelper->setMultipleUsers();

            $scopeHelper->setPath(array('Bookmarks' =>
                                            $this->view->baseUrl('/tagged')));
        }
        else
        {
            // Single user
            $ownerStr = (String)$this->_owner;

            $uiHelper->setSingleUser();

            $scopeHelper->setPath(array($ownerStr =>
                                            $this->view->baseUrl($ownerStr)));

            array_push($scopeParts, 'owner='. $ownerStr);
        }

        if ($this->_tagInfo->hasValidItems())
        {
            array_push($scopeParts, 'tags='. $this->_tagInfo->validItems);
        }

        $scopeCbUrl = $this->view->baseUrl('/scopeAutoComplete')
                    . '?'. implode('&', $scopeParts);
        $scopeHelper->setAutoCompleteUrl( $scopeCbUrl );



        /* Ensure that the final sort information is properly reflected in
         * the source set.
         */
        $this->_userItems->setOrder( $uiHelper->getSortBy() .' '.
                                     $uiHelper->getSortOrder() );

        /*
        Connexions::log("IndexController:: updated params:\n"
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


        // Set the required view variables.
        $this->view->owner     = $this->_owner;
        $this->view->viewer    = $this->_viewer;
        $this->view->tagInfo   = $this->_tagInfo;
        $this->view->paginator = $paginator;

        /* The default view script (views/scripts/index/index.phtml) will
         * render this main view
         */
    }

    protected function _htmlSidebar()
    {
        $request =& $this->getRequest();
        $layout  =& $this->view->layout();

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
        $tagsHighlightCount = $request->getParam($prefix."HighlightCount",
                                                                        null);
        $tagsSortBy         = $request->getParam($prefix."SortBy",      'tag');
        $tagsSortOrder      = $request->getParam($prefix."SortOrder",   null);
        $tagsStyle          = $request->getParam($prefix."OptionGroup", null);

        // /*
        Connexions::log('IndexController::'
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


        // Render the sidebar into the 'right' placeholder
        $this->view->renderToPlaceholder('index/sidebar.phtml', 'right');

        //$layout->right = $this->view->render('index/sidebar.phtml');
        //$layout->right = $this->render('sidebar');
    }
}
