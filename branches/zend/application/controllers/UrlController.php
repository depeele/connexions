<?php
/** @file
 *
 *  This controller controls access to Url and is accessed via the url/routes:
 *      /url[/<md5 hash of url>]
 */

class UrlController extends Zend_Controller_Action
{
    protected   $_viewer    = null;
    protected   $_tagInfo   = null;
    protected   $_item      = null;
    protected   $_userItems = null;

    public function init()
    {
        /* Initialize action controller here */
        $this->_viewer  =& Zend_Registry::get('user');
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        $url     = $request->getParam('url',  null);

        if (empty($url))
            return $this->_forward('choose');

        /* If the incoming URL is NOT an MD5 hash (32 hex characters), convert
         * it to a normalzed hash now
         */
        $urlHash = Connexions::normalizedMd5($url);
        if ($urlHash !== $url)
        {
            // Redirect using the URL hash
            //$newUrl = $this->_helper->url($urlHash);

            return $this->_helper->redirector
                                    ->setGotoRoute(array('url', $urlHash));
        }

        $reqTags = $request->getParam('tags', null);

        // Parse the incoming request tags
        $this->_tagInfo = new Connexions_Set_Info($reqTags, 'Model_Tag');
        if ($this->_tagInfo->hasInvalidItems())
            $this->view->error =
                        "Invalid tag(s) [ {$this->_tagInfo->invalidItems} ]";


        // Locate the item with the requested URL (if there is one).
        $this->_item = new Model_Item($urlHash);

        // /*
        Connexions::log("UrlController:: "
                        . 'item[ '
                        .       $this->_item->debugDump() .' ]');
        // */
        if (! $this->_item->isValid())
        {
            // This URL has not been bookmarked here.
            $this->view->url   = $url;
            $this->view->error = "There are no bookarks for the provided URL.";

            return $this->_forward('choose');
        }


        /* Create the userItem set, scoped by any incoming valid tags and
         * possibly the owner of the area.
         */
        $this->_userItems = new Model_UserItemSet($this->_tagInfo->validIds,
                                                  null,    // userIds
                                                  $this->_item->itemId);

        $this->_htmlContent();
        $this->_htmlSidebar();
    }

    public function chooseAction()
    {
        // Nothing much to do...
        $this->view->renderToPlaceholder('url/choose-sidebar.phtml', 'right');
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
            $url = substr($method, 0, -6);

            return $this->_forward('index', 'url', null,
                                   array('url' => $url));
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
         * Notify the HtmlUrlItems View Helper (used to render the main view)
         * of any incoming settings, allowing it establish any required
         * defaults.
         */
        $prefix           = 'urlItems';
        $itemsPerPage     = $request->getParam($prefix."PerPage",       null);
        $itemsSortBy      = $request->getParam($prefix."SortBy",        null);
        $itemsSortOrder   = $request->getParam($prefix."SortOrder",     null);
        $itemsStyle       = $request->getParam($prefix."OptionGroup",   null);
        $itemsStyleCustom = $request->getParam($prefix."OptionGroups_option",
                                                                        null);

        // /*
        Connexions::log('UrlController::'
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

        // Initialize the Connexions_View_Helper_HtmlUrlItems helper...
        $uiHelper = $this->view->htmlUrlItems();
        $uiHelper->setNamespace($prefix)
                 ->setSortBy($itemsSortBy)
                 ->setSortOrder($itemsSortOrder);
        if (is_array($itemsStyleCustom))
            $uiHelper->setStyle(Connexions_View_Helper_HtmlUrlItems
                                                            ::STYLE_CUSTOM,
                                $itemsStyleCustom);
        else
            $uiHelper->setStyle($itemsStyle);

        /* Ensure that the final sort information is properly reflected in
         * the source set.
         */
        $this->_userItems->setOrder( $uiHelper->getSortBy() .' '.
                                     $uiHelper->getSortOrder() );

        /*
        Connexions::log("UrlController:: updated params:\n"
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
        $paginator = $this->_helper->Pager($this->_userItems, $page,
                                           $itemsPerPage);

        // Set the required view variables.
        $this->view->viewer    = $this->_viewer;
        $this->view->tagInfo   = $this->_tagInfo;
        $this->view->item      = $this->_item;
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
         */
        $tagSet = new Model_TagSet( $this->_userItems->userIds(),
                                    $this->_userItems->itemIds() );
        $tagSet->withAnyUser();

        /********************************************************************
         * Prepare for rendering the right column.
         *
         * Create a second HtmlItemCloud View Helper
         * (used to render the right column) and set it up using the incoming
         * user-cloud parameters.
         */
        $prefix             = 'sbTags';
        $tagsPerPage        = $request->getParam($prefix."PerPage",     100);
        $tagsHighlightCount = $request->getParam($prefix."HighlightCount",
                                                                        null);
        $tagsSortBy         = $request->getParam($prefix."SortBy",      'tag');
        $tagsSortOrder      = $request->getParam($prefix."SortOrder",   null);
        $tagsStyle          = $request->getParam($prefix."OptionGroup", null);

        /*
        Connexions::log('UrlController::'
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
                    ->setItemSetInfo($this->_tagInfo);
                    //->setItemBaseUrl( '/tagged');

        // Render the sidebar into the 'right' placeholder
        $this->view->renderToPlaceholder('url/sidebar.phtml', 'right');
    }
}
