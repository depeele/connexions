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
        $owners    = $request->getParam('owners',    null);

        // Pagination parameters
        $page        = $request->getParam('page',           null);

        // Tag-cloud parameters
        $tagsPrefix         = 'tags';
        $tagsPerPage        = $request->getParam("{$tagsPrefix}PerPage",  250);
        $tagsStyle          = $request->getParam("{$tagsPrefix}Style",    null);
        $tagsHighlightCount = $request->getParam("{$tagsPrefix}HighlightCount",
                                                                          null);
        $tagsSortBy         = $request->getParam("{$tagsPrefix}SortBy",   null);
        $tagsSortOrder      = $request->getParam("{$tagsPrefix}SortOrder",null);

        // User-cloud parameters
        $usersPrefix            = 'sbUsers';
        $usersStyle           = $request->getParam("{$usersPrefix}Style", null);
        $usersPerPage         = $request->getParam("{$usersPrefix}PerPage",
                                                                          500);
        $usersHighlightCount  = $request->getParam(
                                            "{$usersPrefix}HighlightCount",
                                                                          null);
        $usersSortBy          = $request->getParam("{$usersPrefix}SortBy",null);
        $usersSortOrder       = $request->getParam("{$usersPrefix}SortOrder",
                                                                          null);


        /*
        Connexions::log("TagsController:: "
                            . "owners[ {$owners} ], "
                            . "page[ {$page} ], "
                            . "tagsPerPage[ {$tagsPerPage} ], "
                            . "tagsStyle[ {$tagsStyle} ], "
                            . "tagsHighlightCount[ "
                            .                   "{$tagsHighlightCount} ], "
                            . "tagsSortBy[ {$tagsSortBy} ], "
                            . "tagsSortOrder[ {$tagsSortOrder} ], "
                            . "usersStyle[ {$usersStyle} ], "
                            . "usersPerPage[ {$usersPerPage} ], "
                            . "usersHighlightCount[ "
                            .                   "{$usersHighlightCount} ], "
                            . "usersSortBy[ {$usersSortBy} ], "
                            . "usersSortOrder[ {$usersSortOrder} ]");
        // */


        $userInfo = new Connexions_Set_Info($owners, 'Model_User');
        if ($userInfo->hasInvalidItems())
            $this->view->error =
                    "Invalid user(s) [ {$userInfo->invalidItems} ]";

        // Retrieve the set of tags
        $tagSet    = new Model_TagSet( $userInfo->validIds );
        $paginator = $this->_helper->Pager($tagSet, $page, $tagsPerPage);

        // Set the required view variables
        $this->view->tagSet     = $tagSet;
        $this->view->paginator  = $paginator;

        $this->view->viewer     = $viewer;
        $this->view->userInfo   = $userInfo;

        // Tag-cloud parameters
        $this->view->tagsPrefix           = $tagsPrefix;
        $this->view->tagsStyle            = $tagsStyle;
        $this->view->tagsHighlightCount   = $tagsHighlightCount;
        $this->view->tagsSortBy           = $tagsSortBy;
        $this->view->tagsSortOrder        = $tagsSortOrder;

        // User-cloud parameters
        $this->view->usersPrefix          = $usersPrefix;
        $this->view->usersStyle           = $usersStyle;
        $this->view->usersPerPage         = $usersPerPage;
        $this->view->usersHighlightCount  = $usersHighlightCount;
        $this->view->usersSortBy          = $usersSortBy;
        $this->view->usersSortOrder       = $usersSortOrder;
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
