<?php
/** @file
 *
 *  This controller controls access to Url and is accessed via the url/routes:
 *      /url[/<md5 hash of url>]
 */

class UrlController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public  $dependencies = array('db','layout');

    protected   $_url       = null;
    protected   $_tags      = null;

    protected   $_item      = null;

    protected   $_urlHash   = null;

    public      $contexts   = array(
                                'index' => array('partial', 'json',
                                                 'rss',     'atom'),
                              );

    /** @brief  Index/Get/Read/View action.
     *
     *  Retrieve a set of Bookmarks for the given url.
     */
    public function indexAction()
    {
        Connexions::log('UrlController::indexAction');

        $request =& $this->_request;
        $url     =  $request->getParam('url',  null);

        if (empty($url))
            return $this->_forward('choose');

        /* If the incoming URL is NOT an MD5 hash (32 hex characters), convert
         * it to a normalzed hash now
         */
        $this->_urlHash = Connexions::md5Url($url);
        if ($this->_urlHash !== $url)
        {
            // Redirect using the URL hash
            return $this->_helper->redirector
                                    ->setGotoRoute(array('url',
                                                         $this->_urlHash));
        }

        /***************************************************************
         * Process the requested 'tags'
         *
         */
        $reqTags     = $request->getParam('tags', null);
        $this->_tags = $this->service('Tag')->csList2set($reqTags);

        /***************************************************************
         * We now have a valid 'owner' ($this->_owner) and
         * 'tags' ($this->_tags)
         *
         * Adjust the URL to reflect the validated 'owner' and 'tags'
         */
        $this->_url = $request->getBasePath()
                    . '/url/'. $this->_urlHash
                    . '/' .(count($this->_tags) > 0
                            ? $this->_tags .'/'
                            : '');

        // Locate the item with the requested URL (if there is one).
        $this->_item = $this->service('Item')->find($this->_urlHash);

        // /*
        Connexions::log("UrlController:: item[ %s ]",
                        ($this->_item ? $this->_item->debugDump()
                                      : 'null'));
        // */
        if ( (! $this->_item) || (! $this->_item->isValid()) )
        {
            // This URL has not been bookmarked here.
            $this->view->url   = $url;
            $this->view->error = "There are no bookarks for the provided URL.";

            return $this->_forward('choose');
        }

        /***************************************************************
         * Set the view variables required for all views/layouts.
         *
         */
        $this->view->headTitle('Url');
        /*
        if ($this->_item  !== null)
            $this->view->headTitle($this->_item->urlHash);
        // */

        $this->view->url       = $this->_url;
        $this->view->viewer    = $this->_viewer;
        $this->view->tags      = $this->_tags;
        $this->view->item      = $this->_item;

        $this->_prepareMain('items');

        // Handle this request based on the current context / format
        $this->_handleFormat();
    }

    public function chooseAction()
    {
        // Nothing much to do...
        Connexions::log('UrlController::chooseAction');
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

    /** @brief  Prepare for rendering the main view, regardless of format.
     *
     *  This will collect the variables needed to render the main view, placing
     *  them in $view->main as a configuration array.
     */
    protected function _prepareMain($htmlNamespace  = '')
    {
        Connexions::log("UrlController::_prepareMain()");

        parent::_prepareMain($htmlNamespace);

        $extra = array(
            'items' => &$this->_item,
            'tags'  => &$this->_tags,
        );
        $this->view->main = array_merge($this->view->main, $extra);

        Connexions::log("UrlController::_prepareMain(): "
                        .   "main[ %s ]",
                        Connexions::varExport($this->view->main));
    }

    /** @brief  Prepare for rendering the sidebar view.
     *  @param  async   Should we setup to do an asynchronous render
     *                  (i.e. tab callbacks will request tab pane contents when 
     *                        needed)?
     *
     *  This will collect the variables needed to render the sidebar view,
     *  placing them in $view->sidebar as a configuration array.
     *
     *  Note: The main index view script
     *        (application/views/scripts/url/index.phtml) will also add
     *        sidebar-related rendering information to the sidbar helper.  In
     *        particular, it will notify the sidbar helper of the items that
     *        are being presented in the main view.
     */
    protected function _prepareSidebar($async = false)
    {
        parent::_prepareSidebar($async);

        $extra = array(
            'tags'  => &$this->_tags,
        );
        $this->view->sidebar = array_merge($this->view->sidebar, $extra);

        Connexions::log("UrlController::_prepareSidebar(): "
                        .   "sidebar[ %s ]",
                        Connexions::varExport($this->view->sidebar));
    }
}
