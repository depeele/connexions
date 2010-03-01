<?php
/** @file
 *
 *  This controller controls access to Url and is accessed via the url/routes:
 *      /url[/<md5 hash of url>]
 */

class UrlController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
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

        $viewer =& Zend_Registry::get('user');
        $reqTags = $request->getParam('tags', null);

        // Parse the incoming request tags
        $tagInfo = new Connexions_Set_Info($reqTags, 'Model_Tag');
        if ($tagInfo->hasInvalidItems())
            $this->view->error =
                        "Invalid tag(s) [ {$tagInfo->invalidItems} ]";


        // /*
        Connexions::log("UrlController:: "
                        . 'tagIds[ '
                        .       implode(', ', $tagInfo->validIds) .' ], '
                        . "url[ {$url} ], "
                        . "urlHash[ {$urlHash} ]");
        // */
        $item = new Model_Item($urlHash);

        // /*
        Connexions::log("UrlController:: "
                        . 'item[ '
                        .       $item->debugDump() .' ]');
        // */
        if (! $item->isValid())
        {
            // This URL has not been bookmarked here.
            $this->view->url   = $url;
            $this->view->error = "There are no bookarks for the provided URL.";

            return $this->_forward('choose');
        }

        /* Create the userItem set, scoped by any incoming valid tags and
         * possibly the owner of the area.
         */
        $userItems = new Model_UserItemSet($tagInfo->validIds,
                                           null,    // userIds
                                           $item->itemId);

        /* Create the tagSet that will be presented in the side-bar:
         *      All tags used by all users/items contained in the current
         *      user item / bookmark set.
         */
        $tagSet = new Model_TagSet( $userItems->userIds(),
                                    $userItems->itemIds() );
        $tagSet->withAnyUser();

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
        $userItems->setOrder( $uiHelper->getSortBy() .' '.
                              $uiHelper->getSortOrder() );

        /*
        Connexions::log("UrlController: userItems "
                        . "SQL[ ". $userItems->select()->assemble() ." ]");
        // */

        /* Use the Connexions_Controller_Action_Helper_Pager to create a
         * paginator for the retrieved user items / bookmarks.
         */
        $page      = $request->getParam('page',  null);
        $paginator = $this->_helper->Pager($userItems, $page,
                                           $itemsPerPage);

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

        // /*
        Connexions::log('UrlController::'
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
                                        : '/tagged'));

        /********************************************************************
         * Set the required view variables
         *
         */
        $this->view->viewer    = $viewer;
        $this->view->item      = $item;
        $this->view->tagInfo   = $tagInfo;

        $this->view->paginator = $paginator;
        $this->view->tagSet    = $tagSet;
    }

    public function chooseAction()
    {
        // Nothing much to do...
        $this->view->viewer    = Zend_Registry::get('user');
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
}
