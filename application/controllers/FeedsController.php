<?php
/** @file
 *
 *  This controller controls bookmark posting and is accessed
 *  via the url/routes:
 *      /post[ post parameters ]
 */

class FeedsController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public    $dependencies = array('db','layout');
    public    $contexts     = array(
                                'index' => array('json', 'rss', 'atom'),
                              );

    protected $_server              = null;
    protected $_noSidebar           = true;
    protected $_noFormatHandling    = true;

    public function init()
    {
        //Connexions::log("FeedsController::init");

        $this->_baseUrl    = $this->_helper->url(null, 'api');
        $this->_cookiePath = $this->_baseUrl;

        parent::init();
    }

    /** @brief  Default action
     *
     */
    public function indexAction()
    {
        $request = $this->_request;

        $this->view->title = 'Feeds Explorer';
        $this->view->headTitle( $this->view->title );
    }

    /** @brief  JSON Feeds
     *
     */
    public function jsonAction()
    {
        $request = $this->_request;
        $cmd     = $request->getParam('cmd', null);

        // /*
        Connexions::log("FeedsController::jsonAction(): "
                        .   "cmd[ %s ], params[ %s ], _REQUEST[ %s ]",
                        $cmd,
                        Connexions::varExport($request->getParams()),
                        Connexions::varExport($_REQUEST));
        // */

        $server  = new Zend_Json_Server();
        $server->setTarget(Connexions::url('/feeds/json/'))
               ->setAutoEmitResponse( false )
               ->setClass('Service_Proxy_Feeds_Json');
        $this->_server = $server;

        if ($request->isGet() && ($request->getParam('serviceDescription')))
        {
            // Send the service description
            /*
            Connexions::log("FeedsController::v1Action: "
                            . "return service description");
            // */

            return $this->_sendServiceDescription();
        }

        $jsonReq = null;
        if (! empty($cmd))
        {
            // Build a JSON RPC from $_REQUEST
            $jsonReq = new Connexions_Json_Server_Request_Http();
            $jsonReq->setOptions(array(
                'version'   => '2.0',
                'id'        => 1,
                'method'    => $cmd,
            ));

            foreach ($_REQUEST as $key => $val)
            {
                $jsonReq->addParam($val, $key);
            }
        }
        else
        {
            $jsonReq = null;
        }

        if ($jsonReq !== null)
        {
            // Use the defined server to handle this request
            $callback = $jsonReq->getParam('callback', null);

            // /*
            Connexions::log("FeedsController::jsonAction(): "
                            .   "cmd[ %s ], request params[ %s ], "
                            .   "callback[ %s ]",
                            $cmd,
                            Connexions::varExport($jsonReq->getParams()),
                            $callback);
            // */

            $this->_request = $jsonReq;
            Connexions::setRequest($jsonReq);
            $server->setRequest($jsonReq);

            $this->_disableRendering();
            $jsonRsp = $server->handle();

            if (! empty($callback))
            {
                echo $callback .'('. $jsonRsp .');';
            }
            else
            {
                echo $jsonRsp;
            }
        }

        /* Present the API explorer view.
         *
         * This will present the list of all available services with active
         * forms to allow direct invocation and presentation of results.
         */
        $this->view->title = 'JSON Feeds Explorer';
        $this->view->headTitle( $this->view->title );
        $this->view->server = $server;
    }

    /** @brief  RSS Feeds
     *
     */
    public function rssAction()
    {
        $request = $this->_request;

        // /*
        Connexions::log("FeedsController::rssAction(): params[ %s ]",
                        Connexions::varExport($request->getParams()));
        // */

        $this->view->title = 'RSS Feeds Explorer';
        $this->view->headTitle( $this->view->title );
    }

    /** @brief  Atom Feeds
     *
     */
    public function atomAction()
    {
        $request = $this->_request;

        // /*
        Connexions::log("FeedsController::atomAction(): params[ %s ]",
                        Connexions::varExport($request->getParams()));
        // */

        $this->view->title = 'ATOM Feeds Explorer';
        $this->view->headTitle( $this->view->title );
    }

    /*************************************************************************
     * Protected Helpers
     *
     */

    /** @brief  Disable rendering and return the service description of the
     *          current "server".
     */
    protected function _sendServiceDescription()
    {
        $this->_disableRendering();

        // Return the service description
        $this->_server->setEnvelope(Zend_Json_Server_Smd::ENV_JSONRPC_2);

        header('Content-Type: application/json');
        echo $this->_server->getServiceMap();
    }

    /** @brief  Disable view rendering.
     */
    protected function _disableRendering()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->setParam('disableOutputBuffering', true);
        $front->getDispatcher()
                ->setParam('disableOutputBuffering', true);

        $viewRenderer =
          Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');

        $view = $viewRenderer->view;
        if ($view instanceof Zend_View_Interface)
        {
            $viewRenderer->setNoRender(true);
        }

        $layout = Zend_Layout::getMvcInstance();
        if ($layout instanceof Zend_Layout)
        {
            $layout->disableLayout();
        }
    }
}
