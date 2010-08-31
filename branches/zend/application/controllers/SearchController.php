<?php
/** @file
 *
 *  This controller controls access to Search and is accessed via POST to the
 *  url/routes:
 *      /search
 *          POST parameters:
 *              owner           The owner user name;
 *              tags            Tags to limit the search;
 *              searchContext   The search context;
 *              q               The search query.
 */


class SearchController extends Connexions_Controller_Action
{
    public function indexAction()
    {
        $request =& $this->_request;
        $terms   =  $request->getParam('q',             null);
        $context =  $request->getParam('searchContext', null);

        $this->view->context = $context;
        $this->view->terms   = $terms;
    }
}

