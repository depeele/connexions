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

        // Pagination parameters
        $page      = $request->getParam('page',      null);

        // User-Item parameters
        $itemsPrefix      = 'items';
        $itemsPerPage     = $request->getParam("{$itemsPrefix}PerPage",   null);
        $itemsStyle       = $request->getParam("{$itemsPrefix}Style",     null);
        $itemsSortBy      = $request->getParam("{$itemsPrefix}SortBy",    null);
        $itemsSortOrder   = $request->getParam("{$itemsPrefix}SortOrder", null);
        $itemsStyleCustom = $request->getParam("{$itemsPrefix}StyleCustom",
                                                                          null);

        /*
        Connexions::log("IndexController:: "
                            . "itemsStyleCustom[ "
                            .   print_r($itemsStyleCustom, true)
                            .       ' ]');
        // */

        // Tag-cloud parameters
        $tagsPrefix         = 'sbTags';
        $tagsPerPage        = $request->getParam("{$tagsPrefix}PerPage",  100);
        $tagsStyle          = $request->getParam("{$tagsPrefix}Style",    null);
        $tagsHighlightCount = $request->getParam("{$tagsPrefix}HighlightCount",
                                                                          null);
        $tagsSortBy         = $request->getParam("{$tagsPrefix}SortBy", 'tag');
        $tagsSortOrder      = $request->getParam("{$tagsPrefix}SortOrder",null);

        /*
        Connexions::log("IndexController:: "
                            . "owner[ {$owner} ], "
                            . "tags[ ". $request->getParam('tags','') ." ], "
                            . "reqTags[ {$reqTags} ]");
        // */

        if ($owner === 'mine')
        {
            // No user specified -- use the currently authenticated user
            $owner =& $viewer;
            if ( ( ! $owner instanceof Model_User) ||
                 (! $owner->isAuthenticated()) )
            {
                // Unauthenticated user -- Redirect to signIn
                return $this->_helper->redirector('signIn','auth');
            }
        }

        $userIds = null;
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

                    $owner   =& $ownerInst;
                    $userIds =  array($owner->userId);
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

        $tagInfo = new Connexions_Set_Info($reqTags, 'Model_Tag');
        if ($tagInfo->hasInvalidItems())
            $this->view->error =
                        "Invalid tag(s) [ {$tagInfo->invalidItems} ]";

        /* Notify the View Helper of any sort / style settings, allowing it
         * establish any required defaults.
         */
        $helper = $this->view->htmlUserItems();
        $helper->setSortBy($itemsSortBy)
               ->setSortOrder($itemsSortOrder);

        $itemsSortBy    = $helper->getSortBy();
        $itemsSortOrder = $helper->getSortOrder();

        /*
        Connexions::log("IndexController: helper updated sort "
                            . "by[ {$itemsSortBy} ], "
                            . "order[ {$itemsSortOrder} ]");
        // */

        // Create the userItem set, mirroring the specified sorting criteria.
        $userItems = new Model_UserItemSet($tagInfo->validIds, $userIds);
        if (($itemsSortBy !== null) || ($itemsSortOrder !== null))
        {
            $userItems->setOrder($itemsSortBy, $itemsSortOrder);
        }

        // Ensure that our display options match how we're retrieving the data
        $res = $userItems->getOrder();

        /*
        Connexions::log("IndexController: sort "
                            . "by[ {$itemsSortBy} ] == [ {$res['by']} ], "
                            . "order[ {$itemsSortOrder} ] == "
                            .                   "[ {$res['order']} ]");
        // */

        $itemsSortBy    = $res['by'];
        $itemsSortOrder = $res['order'];


        /* Use the Connexions_Controller_Action_Helper_Pager to create a
         * paginator
         */
        $paginator = $this->_helper->Pager($userItems, $page, $itemsPerPage);

        // Set the required view variables
        $this->view->userItems       = $userItems;
        $this->view->paginator       = $paginator;

        $this->view->owner           = $owner;
        $this->view->viewer          = $viewer;
        $this->view->tagInfo         = $tagInfo;

        // User-Item parameters
        $this->view->userItemsPrefix  = $itemsPrefix;
        //$this->view->itemsSortBy      = $itemsSortBy;
        //$this->view->itemsSortOrder   = $itemsSortOrder;
        $this->view->itemsStyle       = $itemsStyle;
        $this->view->itemsStyleCustom = $itemsStyleCustom;

        // Tag-cloud parameters
        $this->view->tagsPrefix         = $tagsPrefix;
        $this->view->tagsStyle          = $tagsStyle;
        $this->view->tagsPerPage        = $tagsPerPage;
        $this->view->tagsHighlightCount = $tagsHighlightCount;
        $this->view->tagsSortBy         = $tagsSortBy;
        $this->view->tagsSortOrder      = $tagsSortOrder;
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
        $like      = $request->getParam('q',     null);
        $limit     = $request->getParam('limit', 250);

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

        $userIds = null;
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

                    $owner   =& $ownerInst;
                    $userIds =  array($owner->userId);
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


        $tagInfo   = new Connexions_Set_Info($reqTags, 'Model_Tag');
        if ($tagInfo->hasInvalidItems())
            $jsonRpc->setError("Invalid tag(s) [ {$tagInfo->invalidItems} ]");

        $userItems = new Model_UserItemSet($tagInfo->validIds, $userIds);

        $userIds   = $userItems->userIds();
        $itemIds   = $userItems->itemIds();

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


        // Create a tag set
        $tagSet = new Model_TagSet( $userIds, $itemIds );
        if ($owner === '*')
            $tagSet->withAnyUser();
        if (! empty($like))
            $tagSet->like($like);
        if ($limit > 0)
            $tagSet = $tagSet->getItems(0, $limit);


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
