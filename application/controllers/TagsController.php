<?php
/** @file
 *
 *  This controller controls access to Tags and is accessed via the url/routes:
 *      /tags[/<user>]
 *      /tags/scopeAutoComplete?q=<query>
 *                             &limit=<max>
 *                             &format=json
 *                             &users=<comma-separated user list>
 */

class TagsController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $viewer =& Zend_Registry::get('user');

        $request   = $this->getRequest();
        $reqUsers  = $request->getParam('owners',    null);

        // Parse the incoming request users / owners
        $userInfo = new Connexions_Set_Info($reqUsers, 'Model_User');
        if ($userInfo->hasInvalidItems())
            $this->view->error =
                    "Invalid user(s) [ {$userInfo->invalidItems} ]";

        // Retrieve the complete set of tags
        $tagSet = new Model_TagSet( $userInfo->validIds );

        /********************************************************************
         * Prepare for rendering the main view.
         *
         * Establish the primary HtmlItemCloud View Helper, setting it up using
         * the incoming tag-cloud parameters.
         */
        $prefix             = 'tags';
        $tagsPerPage        = $request->getParam($prefix."PerPage",     250);
        $tagsHighlightCount = $request->getParam($prefix."HighlightCount",
                                                                        null);
        $tagsSortBy         = $request->getParam($prefix."SortBy",      null);
        $tagsSortOrder      = $request->getParam($prefix."SortOrder",   null);
        $tagsStyle          = $request->getParam($prefix."OptionGroup", null);

        // /*
        Connexions::log('TagsController::'
                            . 'prefix [ '. $prefix .' ], '
                            . 'params [ '
                            .   print_r($request->getParams(), true) ." ],\n"
                            . "    PerPage        [ {$tagsPerPage} ],\n"
                            . "    HighlightCount [ {$tagsHighlightCount} ],\n"
                            . "    SortBy         [ {$tagsSortBy} ],\n"
                            . "    SortOrder      [ {$tagsSortOrder} ],\n"
                            . "    Style          [ {$tagsStyle} ]");
        // */

        $cloudHelper = $this->view->htmlItemCloud();
        $cloudHelper->setNamespace($prefix)
                    ->setShowRelation( false )
                    ->setStyle($tagsStyle)
                    ->setItemType(Connexions_View_Helper_HtmlItemCloud::
                                                            ITEM_TYPE_TAG)
                    ->setItemBaseUrl( '/tagged' )
                    ->setSortBy($tagsSortBy)
                    ->setSortOrder($tagsSortOrder)
                    ->setPerPage($tagsPerPage)
                    ->setHighlightCount($tagsHighlightCount);

        /*
        Connexions::log("TagsController:: final "
                        . "tagsSortBy[ {$cloudHelper->getSortBy()} ], "
                        . "tagsSortOrder[ {$cloudHelper->getSortOrder()} ]");
        // */

        /* Ensure that the final sort information is properly reflected in
         * the source set.
         *
         * Do this NOW since we're about to use the set to create a paginator.
         */
        $tagSet->setOrder( $cloudHelper->getSortBy() .' '.
                           $cloudHelper->getSortOrder() );

        /* Use the Connexions_Controller_Action_Helper_Pager to create a
         * paginator for the retrieved tagSet.
         */
        $page      = $request->getParam('page',  null);
        $paginator = $this->_helper->Pager($tagSet,
                                           $page,
                                           $cloudHelper->getPerPage());

        $cloudHelper->setItemSet($paginator);

        /*
        $tagList = new Connexions_Set_ItemList($paginator,
                                               null,
                                               '/tagged');
        */



        /* Setup the HtmlItemScope helper.
         *
         * Begin by constructing the scope auto-completion callback URL
         */
        $scopeParts = array('format=json');
        if ($userInfo->hasValidItems())
        {
            array_push($scopeParts, 'users='. $userInfo->validItems);
        }

        $scopeCbUrl = $this->view->baseUrl('/tags/scopeAutoComplete')
                    . '?'. implode('&', $scopeParts);

        $scopeHelper = $this->view->htmlItemScope();
        $scopeHelper->setInputLabel('Users')
                    ->setInputName( 'owners')
                    ->setPath( array('Tags'  => $this->view->baseUrl('/tags')) )
                    ->setAutoCompleteUrl($scopeCbUrl);

        /********************************************************************
         * Prepare for rendering the right column.
         *
         * Create a second HtmlItemCloud View Helper
         * (used to render the right column) and set it up using the incoming
         * user-cloud parameters.
         */
        $prefix             = 'sbUsers';
        $usrsPerPage        = $request->getParam($prefix."PerPage",     500);
        $usrsHighlightCount = $request->getParam($prefix."HighlightCount",
                                                                        null);
        $usrsSortBy         = $request->getParam($prefix."SortBy",      null);
        $usrsSortOrder      = $request->getParam($prefix."SortOrder",   null);
        $usrsStyle          = $request->getParam($prefix."OptionGroup", null);

        // /*
        Connexions::log('TagsController::'
                            . "right-column prefix [ {$prefix} ],\n"
                            . "    PerPage        [ {$usrsPerPage} ],\n"
                            . "    HighlightCount [ {$usrsHighlightCount} ],\n"
                            . "    SortBy         [ {$usrsSortBy} ],\n"
                            . "    SortOrder      [ {$usrsSortOrder} ],\n"
                            . "    Style          [ {$usrsStyle} ]");
        // */

        // Retrieve the ids of all tags we're currently presenting
        $tagIds = $tagSet->tagIds();

        // Create a user set for all users that have this set of tags
        $userSet = new Model_UserSet( $tagIds );
        $userSet->withAnyTag()
                ->weightBy('tag');
    
        // Since we're caching objects, we MUST modify the $viewer object...
        //$viewer->weightBy('tag', $tagIds);

        /* Create a new instance of the HtmlItemCloud view helper since we'll
         * be presenting two different clouds.
         */
        $sbCloudHelper = new Connexions_View_Helper_HtmlItemCloud();
        $sbCloudHelper->setView($this->view)
                      ->setNamespace($prefix)
                      ->setStyle($usrsStyle)
                      ->setItemType(Connexions_View_Helper_HtmlItemCloud::
                                                            ITEM_TYPE_USER)
                      ->setItemSet($userSet)
                      ->setItemSetInfo($userInfo)
                      /*
                      ->setItemBaseUrl( ($owner !== '*'
                                            ? null
                                            : '/tagged'))
                      */
                      ->setSortBy($usrsSortBy)
                      ->setSortOrder($usrsSortOrder)
                      ->setPerPage($usrsPerPage)
                      ->setHighlightCount($usrsHighlightCount);

        /* Reflect any sorting changes introduced by HtmlItemCloud
         *      e.g. if the sort by and/or order were null and thus a default
         *           was set
        $userSet->setOrder( array('weight DESC',
                                  $sbCloudHelper->getSortBy() .' '.
                                        $sbCloudHelper->getSortOrder()) );
         */

        /* Retrieve the Connexions_Set_ItemList instance required by
         * Zend_Tag_Cloud to render this set as a cloud
        $userList = $userSet->get_Tag_ItemList(0, $usrsPerPage, $userInfo);

        $sbCloudHelper->setItemList($userList);
         */




        /*
        Connexions::log("TagsController:: Final sbUser info: "
                            . "Style[ {$sbCloudHelper->getStyle()} ], "
                            . "HighlightCount[ "
                            .       $sbCloudHelper->getHighlightCount() ." ], "
                            . "SortBy[ {$sbCloudHelper->getSortBy()} ], "
                            . "SortOrder[ {$sbCloudHelper->getSortOrder()} ]");
        // */

        /********************************************************************
         * Set the required view variables
         *
         */
        $this->view->viewer        = $viewer;
        $this->view->userInfo      = $userInfo;

        $this->view->tagSet        = $tagSet;
        $this->view->sbCloudHelper = $sbCloudHelper;
    }

    /** @brief  A JSON-RPC callback to retrieve auto-completion results for 
     *          Scope Item Entry.
     *
     *  Valid incoming parameters:
     *      users   A comma-separated list of request users that limit scope;
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

        //$owner     = $request->getParam('owner', null);
        $reqUsers  = $request->getParam('users', null);
        $like      = $request->getParam('q',     null);
        $limit     = $request->getParam('limit', 250);

        $userInfo = new Connexions_Set_Info($reqUsers, 'Model_User');
        if ($userInfo->hasInvalidItems())
            $jsonRpc->setError("Invalid user(s) [ {$userInfo->invalidItems} ]");

        if ($jsonRpc->hasError())
        {
            return $jsonRpc->sendResponse();
        }

        // Retrieve the set of tags
        $tagSet    = new Model_TagSet( $userInfo->validIds );


        $userIds   = $tagSet->tagIds();

        /*
        Connexions::log(sprintf("IndexController::scopeAutoCompleteAction: "
                                . "owner[ %s ], reqTags[ %s ], "
                                . "like[ %s ],  limit[ %d ], "
                                . "userIds[ %s ], itemIds[ %s ]",
                                $owner, $reqTags,
                                $like,  $limit,
                                @implode(', ', $userIds),
                                @implode(', ', $itemIds)) );
        // */


        // Create a user set of all users that have this set of tags
        $userSet = new Model_UserSet( $tagIds );
        $userSet->withAnyTag()
                ->weightBy('tag');


        $scopeData = array();
        foreach ($userSet as $item)
        {
            $str = $item->__toString();

            if ($userInfo->isValidItem($str))
                continue;

            array_push($scopeData, array('value' => $str));
        }

        $jsonRpc->setResult($scopeData);

        /*
        Connexions::log(sprintf("TagsController::scopeAutoCompleteAction: "
                                . "scopeData[ %s ]",
                                var_export($scopeData, true)) );
        // */

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

            return $this->_forward('index', 'tags', null,
                                   array('owner' => $owner));
        }

        throw new Exception('Invalid method "'. $method .'" called', 500);
    }
}
