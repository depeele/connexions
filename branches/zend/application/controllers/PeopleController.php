<?php
/** @file
 *
 *  This controller controls access to Users / People and is accessed via the
 *  url/routes:
 *      /people[/:tags]
 */

class PeopleController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $viewer    =& Zend_Registry::get('user');

        $request   = $this->getRequest();
        $reqTags   = $request->getParam('tags',      null);

        // Parse the incoming request tags
        $tagInfo = new Connexions_Set_Info($reqTags, 'Model_Tag');
        if ($tagInfo->hasInvalidItems())
            $this->view->error =
                        "Invalid tag(s) [ {$tagInfo->invalidItems} ]";

        /* Create the user set, scoped by any incoming valid tags
         * (i.e. the set of tag-related users).
         */
        $userSet = new Model_UserSet( $tagInfo->validIds );

        /* Create the tagSet that will be presented in the side-bar:
         *      All tags used by all users contained in the current user set.
         */
        $tagSet = new Model_TagSet( $userSet->userIds() );
        $tagSet->weightBy('user');

        /********************************************************************
         * Prepare for rendering the main view.
         *
         * Notify the HtmlUsers View Helper (used to render the main view)
         * of any incoming settings, allowing it establish any required
         * defaults.
         */
        $usersPrefix      = 'users';
        $usersPerPage     = $request->getParam($usersPrefix."PerPage",   null);
        $usersStyle       = $request->getParam($usersPrefix."Style",     null);
        $usersSortBy      = $request->getParam($usersPrefix."SortBy",    null);
        $usersSortOrder   = $request->getParam($usersPrefix."SortOrder", null);
        $usersStyleCustom = $request->getParam($usersPrefix."StyleCustom",
                                                                         null);

        $uiHelper = $this->view->htmlUsers();
        $uiHelper->setNamespace($usersPrefix)
                 ->setSortBy($usersSortBy)
                 ->setSortOrder($usersSortOrder);
        if (is_array($usersStyleCustom))
            $uiHelper->setStyle(Connexions_View_Helper_HtmlUsers
                                                            ::STYLE_CUSTOM)
                     ->setShowMeta($usersStyleCustom);
        else
            $uiHelper->setStyle($usersStyle);

        /*
        Connexions::log("IndexController: uiHelper updated sort "
                            . "by[ {$uiHelper->getSortBy() } ], "
                            . "order[ {$uiHelper->getSortOrder() } ]");
        // */

        /* Ensure that the final sort information is properly reflected in
         * the source set.
         */
        $userSet->setOrder( $uiHelper->getSortBy(),
                            $uiHelper->getSortOrder() );

        /* Use the Connexions_Controller_Action_Helper_Pager to create a
         * paginator for the retrieved user set.
         */
        $page      = $request->getParam('page',  null);
        $paginator = $this->_helper->Pager($userSet, $page, $usersPerPage);


        /********************************************************************
         * Prepare for rendering the right column.
         *
         * Notify the HtmlItemCloud View Helper
         * (used to render the right column) of any incoming settings, allowing
         * it establish any required defaults.
         */
        $tagsPrefix         = 'sbTags';
        $tagsPerPage        = $request->getParam("{$tagsPrefix}PerPage",  250);
        $tagsStyle          = $request->getParam("{$tagsPrefix}Style",    null);
        $tagsHighlightCount = $request->getParam("{$tagsPrefix}HighlightCount",
                                                                          null);
        $tagsSortBy         = $request->getParam("{$tagsPrefix}SortBy", 'tag');
        $tagsSortOrder      = $request->getParam("{$tagsPrefix}SortOrder",null);


        $cloudHelper = $this->view->htmlItemCloud();
        $cloudHelper->setNamespace($tagsPrefix)
                    ->setStyle($tagsStyle)
                    ->setItemType(Connexions_View_Helper_HtmlItemCloud::
                                                            ITEM_TYPE_TAG)
                    ->setSortBy($tagsSortBy)
                    ->setSortOrder($tagsSortOrder)
                    ->setHighlightCount($tagsHighlightCount);


        /* Retrieve the Connexions_Set_ItemList instance required by
         * Zend_Tag_Cloud to render this tag set as a cloud
         */
        $tagList = $tagSet->get_Tag_ItemList(0, $tagsPerPage, $tagInfo);


        /********************************************************************
         * Set the required view variables
         *
         */
        $this->view->viewer    = $viewer;
        $this->view->tagInfo   = $tagInfo;

        $this->view->paginator = $paginator;
        $this->view->tagList   = $tagList;
    }
}
