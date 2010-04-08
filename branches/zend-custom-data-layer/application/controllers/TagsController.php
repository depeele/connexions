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
    protected   $_viewer    = null;
    protected   $_userInfo  = null;
    protected   $_tagSet    = null;

    public function init()
    {
        /* Initialize action controller here */
        $this->_viewer  =& Zend_Registry::get('user');
    }

    public function indexAction()
    {
        $request   = $this->getRequest();
        $reqUsers  = $request->getParam('owners',    null);

        // Parse the incoming request users / owners
        $this->_userInfo = new Connexions_Set_Info($reqUsers, 'Model_User');
        if ($this->_userInfo->hasInvalidItems())
            $this->view->error =
                    "Invalid user(s) [ {$this->_userInfo->invalidItems} ]";

        // Retrieve the complete set of tags
        $this->_tagSet = new Model_TagSet( $this->_userInfo->validIds );

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

            return $this->_forward('index', 'tags', null,
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


        /* Setup the HtmlItemScope helper.
         *
         * Begin by constructing the scope auto-completion callback URL
         */
        $scopeParts = array('format=json');
        if ($this->_userInfo->hasValidItems())
        {
            array_push($scopeParts, 'users='. $this->_userInfo->validItems);
        }

        $scopeCbUrl = $this->view->baseUrl('/tags/scopeAutoComplete')
                    . '?'. implode('&', $scopeParts);

        $scopeHelper = $this->view->htmlItemScope();
        $scopeHelper->setInputLabel('Users')
                    ->setInputName( 'owners')
                    ->setPath( array('Tags'  => $this->view->baseUrl('/tags')) )
                    ->setAutoCompleteUrl($scopeCbUrl);


        // Initialize the primary Connexions_View_Helper_HtmlItemCloud helper.
        $cloudHelper = $this->view->htmlItemCloud();
        $cloudHelper->setNamespace($prefix)
                    ->setShowRelation( false )
                    ->setStyle($tagsStyle)
                    ->setItemType(Connexions_View_Helper_HtmlItemCloud::
                                                            ITEM_TYPE_TAG)
                    ->setItemBaseUrl( '/bookmarks' )
                    ->setSortBy($tagsSortBy)
                    ->setSortOrder($tagsSortOrder)
                    ->setPerPage($tagsPerPage)
                    ->setHighlightCount($tagsHighlightCount);

        /* Ensure that the final sort information is properly reflected in
         * the source set.
         *
         * Do this NOW since we're about to use the set to create a paginator.
         */
        $this->_tagSet->setOrder( $cloudHelper->getSortBy() .' '.
                                  $cloudHelper->getSortOrder() );

        /* Use the Connexions_Controller_Action_Helper_Pager to create a
         * paginator for the retrieved tagSet.
         */
        $page      = $request->getParam('page',  null);
        $paginator = $this->_helper->Pager($this->_tagSet,
                                           $page,
                                           $cloudHelper->getPerPage());

        $cloudHelper->setItemSet($paginator);


        // Set the required view variables.
        $this->view->owner     = $this->_owner;
        $this->view->viewer    = $this->_viewer;
        $this->view->userInfo  = $this->_userInfo;
        $this->view->paginator = $paginator;

        /* The default view script (views/scripts/index/index.phtml) will
         * render this main view
         */
    }

    protected function _htmlSidebar()
    {
        $request =& $this->getRequest();
        $layout  =& $this->view->layout();

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
        $tagIds = $this->_tagSet->tagIds();

        // Create a user set for all users that have this set of tags
        $userSet = new Model_UserSet( $tagIds );
        $userSet->withAnyTag()
                ->weightBy('tag');
    
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
                      ->setItemSetInfo($this->_userInfo)
                      /*
                      ->setItemBaseUrl( ($owner !== '*'
                                            ? null
                                            : '/bookmarks'))
                      */
                      ->setSortBy($usrsSortBy)
                      ->setSortOrder($usrsSortOrder)
                      ->setPerPage($usrsPerPage)
                      ->setHighlightCount($usrsHighlightCount);

        // Set the required view variables.
        $this->view->sbCloudHelper = $sbCloudHelper;

        // Render the sidebar into the 'right' placeholder
        $this->view->renderToPlaceholder('tags/sidebar.phtml', 'right');
    }

}
