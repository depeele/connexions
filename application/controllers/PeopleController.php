<?php
/** @file
 *
 *  This controller controls access to Users / People and is accessed via the
 *  url/routes:
 *      /people[/:tags]
 */

class PeopleController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public  $dependencies = array('db','layout');

    protected   $_url       = null;
    protected   $_tags      = null;
    protected   $_users     = null;

    protected   $_offset    = 0;
    protected   $_count     = null;
    protected   $_sortBy    = null;
    protected   $_sortOrder = null;

    public      $contexts   = array(
                                'index' => array('partial', 'json',
                                                 'rss',     'atom'),
                              );

    protected   $_viewer    = null;
    protected   $_tagInfo   = null;
    protected   $_userSet   = null;

    /** @brief  Index/Get/Read/View action.
     *
     *  Retrieve a set of Users based upon the requested 'tags'.
     *
     *  Once retrieved, perform further setup based upon the current
     *  context/format.
     */
    public function indexAction()
    {
        $request  =& $this->_request;

        // /*
        Connexions::log('PeopleController::indexAction(): '
                        .   'params[ %s ]',
                        print_r($request->getParams(), true));
        // */

        /***************************************************************
         * Process the requested 'owner' and 'tags'
         *
         */
        $reqTags   = $request->getParam('tags',      null);

        /*
        Connexions::log("IndexController::indexAction: reqTags[ %s ]",
                        $reqTags);
        // */

        // Parse the incoming request tags
        $this->_tags = $this->service('Tag')->csList2set($reqTags);

        /***************************************************************
         * We now have a valid 'owner' ($this->_owner) and
         * 'tags' ($this->_tags)
         *
         * Adjust the URL to reflect the validated 'owner' and 'tags'
         */
        $this->_url = $request->getBasePath()
                    . '/people'
                    . '/' .(count($this->_tags) > 0
                            ? $this->_tags .'/'
                            : '');

        /***************************************************************
         * Set the view variables required for all views/layouts.
         *
         */
        $this->view->headTitle('People');

        $this->view->url       = $this->_url;
        $this->view->viewer    = $this->_viewer;

        $this->view->tags      = $this->_tags;


        $this->_prepareMain('users');

        // Handle this request based on the current context / format
        $this->_handleFormat();
    }

    /*************************************************************************
     * Context-specific view initialization and invocation
     *
     */

    /** @brief  Prepare for rendering the main view, regardless of format.
     *
     *  This will collect the variables needed to render the main view, placing
     *  them in $view->main as a configuration array.
     */
    protected function _prepareMain($htmlNamespace  = '')
    {
        parent::_prepareMain($htmlNamespace);

        $extra = array(
            'tags'  => &$this->_tags,
        );
        $this->view->main = array_merge($this->view->main, $extra);

        Connexions::log("PeopleController::_prepareMain(): "
                        .   "main[ %s ]",
                        Connexions::varExport($this->view->main));
    }

}
