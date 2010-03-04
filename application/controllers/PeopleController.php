<?php
/** @file
 *
 *  This controller controls access to Users / People and is accessed via the
 *  url/routes:
 *      /people[/:tags]
 */

class PeopleController extends Zend_Controller_Action
{
    protected   $_viewer    = null;
    protected   $_tagInfo   = null;
    protected   $_userSet   = null;

    public function init()
    {
        /* Initialize action controller here */
        $this->_viewer  =& Zend_Registry::get('user');
    }

    public function indexAction()
    {
        $request   = $this->getRequest();
        $reqTags   = $request->getParam('tags',      null);

        // Parse the incoming request tags
        $this->_tagInfo = new Connexions_Set_Info($reqTags, 'Model_Tag');
        if ($this->_tagInfo->hasInvalidItems())
            $this->view->error =
                        "Invalid tag(s) [ {$this->_tagInfo->invalidItems} ]";

        /* Create the user set, scoped by any incoming valid tags
         * (i.e. the set of tag-related users).
         */
        $this->_userSet = new Model_UserSet( $this->_tagInfo->validIds );


        $this->_htmlContent();
        $this->_htmlSidebar();
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
         * Notify the HtmlUsers View Helper (used to render the main view)
         * of any incoming settings, allowing it establish any required
         * defaults.
         */
        $prefix           = 'users';
        $usersPerPage     = $request->getParam($prefix."PerPage",       null);
        $usersSortBy      = $request->getParam($prefix."SortBy",        null);
        $usersSortOrder   = $request->getParam($prefix."SortOrder",     null);
        $usersStyle       = $request->getParam($prefix."OptionGroup",   null);
        $usersStyleCustom = $request->getParam($prefix."OptionGroups_option",
                                                                        null);
        // /*
        Connexions::log('PeopleController::'
                            . 'prefix [ '. $prefix .' ], '
                            . 'params [ '
                            .   print_r($request->getParams(), true) ." ],\n"
                            . "    PerPage        [ {$usersPerPage} ],\n"
                            . "    SortBy         [ {$usersSortBy} ],\n"
                            . "    SortOrder      [ {$usersSortOrder} ],\n"
                            . "    Style          [ {$usersStyle} ]"
                            . "    StyleCustom    [ "
                            .           print_r($usersStyleCustom, true) .' ]');
        // */

        // Initialize the Connexions_View_Helper_HtmlUsers helper...
        $uiHelper = $this->view->htmlUsers();
        $uiHelper->setNamespace($prefix)
                 ->setSortBy($usersSortBy)
                 ->setSortOrder($usersSortOrder);
        if (is_array($usersStyleCustom))
            $uiHelper->setStyle(Connexions_View_Helper_HtmlUsers
                                                        ::STYLE_CUSTOM,
                                $usersStyleCustom);
        else
            $uiHelper->setStyle($usersStyle);

        /*
        Connexions::log("PeopleController: uiHelper updated sort "
                            . "by[ {$uiHelper->getSortBy() } ], "
                            . "order[ {$uiHelper->getSortOrder() } ]");
        // */

        /* Ensure that the final sort information is properly reflected in
         * the source set.
         */
        $this->_userSet->setOrder( $uiHelper->getSortBy() .' '.
                                   $uiHelper->getSortOrder() );

        /*
        Connexions::log("PeopleController: userSet "
                            . "SQL[ "
                            .   $this->_userSet->select()->assemble() ." ]");
        // */

        /* Use the Connexions_Controller_Action_Helper_Pager to create a
         * paginator for the retrieved user set.
         */
        $page      = $request->getParam('page',  null);
        $paginator = $this->_helper->Pager($this->_userSet,
                                           $page, $usersPerPage);


        /********************************************************************
         * Set the required view variables
         *
         */
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
         *      All tags used by all users contained in the current user set.
         */
        $tagSet = new Model_TagSet( $this->_userSet->userIds() );
        $tagSet->weightBy('user');

        /********************************************************************
         * Prepare for rendering the right column.
         *
         * Notify the HtmlItemCloud View Helper
         * (used to render the right column) of any incoming settings, allowing
         * it establish any required defaults.
         */
        $prefix             = 'sbTags';
        $tagsPerPage        = $request->getParam("{$prefix}PerPage",    250);
        $tagsHighlightCount = $request->getParam("{$prefix}HighlightCount",
                                                                        null);
        $tagsSortBy         = $request->getParam("{$prefix}SortBy",     'tag');
        $tagsSortOrder      = $request->getParam("{$prefix}SortOrder",  null);
        $tagsStyle          = $request->getParam("{$prefix}OptionGroup",null);

        // /*
        Connexions::log('PeopleController::'
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

        // Render the sidebar into the 'right' placeholder
        $this->view->renderToPlaceholder('people/sidebar.phtml', 'right');
    }
}
