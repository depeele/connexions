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

        /*
         * :XXX: Can't really use this since we have different feeds types
         *       (json, rss, atom).
         *
        $server = new Zend_Json_Server();
        $server->setTarget(Connexions::url('/feeds/'))
               ->setAutoEmitResponse( false )
               ->setClass('Service_Proxy_Feeds_Json', 'json')
               ->setClass('Service_Proxy_Feeds_Feed', 'rss')
               ->setClass('Service_Proxy_Feeds_Feed', 'atom');
        $this->_server = $server;
        $this->view->server = $server;
        // */

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

            /*
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

            $result = $jsonRsp->getResult();
            if (is_object($result))
            {
                if (method_exists($result, 'toArray'))
                {
                    // Don't perform deep conversion
                    $result = $result->toArray( array('deep' => false) );
                }
                else if (method_exists($result, '__toString'))
                {
                    $result = $result->__toString();
                }
            }
            $result = json_encode( $result );

            /*
            Connexions::log("FeedsController::jsonAction(): "
                            . "result[ %s ]",
                            Connexions::varExport($result));
            // */

            header('Content-Type: application/json');
            if (! empty($callback))
            {
                echo $callback .'('. $result .');';
            }
            else
            {
                echo $result;
            }
            return;
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
        $this->_feed( View_Helper_FeedBookmarks::TYPE_RSS );
    }

    /** @brief  Atom Feeds
     *
     */
    public function atomAction()
    {
        $this->_feed( View_Helper_FeedBookmarks::TYPE_ATOM );
    }

    /*************************************************************************
     * Protected Helpers
     *
     */

    /** @brief  Common functionality for Zend_Feed types.
     *  @param  type    The Feed type (View_Helper_FeedBookmarks::TYPE_*);
     */
    protected function _feed($type)
    {
        $typeStr = strtolower($type);
        $request = $this->_request;
        $cmd     = $request->getParam('cmd', null);

        // /*
        Connexions::log("FeedsController::_feed(): type[ %s ], cmd[ %s ], "
                        .   "params[ %s ]",
                        Connexions::varExport($type),
                        $cmd,
                        Connexions::varExport($request->getParams()));
        // */

        /* :NOTE: Even though this is NOT a JSON feed, we use the
         *        Zend_Json_Server to provide the ability to return a service
         *        description.
         */
        $server  = new Zend_Json_Server();
        $server->setTarget(Connexions::url("/feeds/{$typeStr}/"))
               ->setAutoEmitResponse( false )
               ->setClass('Service_Proxy_Feeds_Feed');
        $this->_server = $server;

        if ($request->isGet() && ($request->getParam('serviceDescription')))
        {
            // Send the service description
            /*
            Connexions::log("FeedsController::_feed(): "
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

        if ($jsonReq !== null)
        {
            Connexions::log("FeedsController::_feed(): "
                            . "pseudo-JsonRpc request[ %s ]",
                            $jsonReq);

            //$this->_request = $jsonReq;
            //Connexions::setRequest($jsonReq);
            $server->setRequest($jsonReq);

            $this->_disableRendering();
            $jsonRsp = $server->handle();

            //$this->_enableRendering();
            $this->view->main = array(
                'feedType'  => $type,
                'bookmarks' => $jsonRsp->getResult(),
            );

            // /*
            Connexions::log("FeedsController::_feed(): "
                            . "main[ %s ]",
                            Connexions::varExport($this->view->main));
            // */
            $this->view->title = "{$cmd} {$type} Feed";
        }
        else
        {
            $this->view->title = strtoupper($type) ." Feeds Explorer";
        }

        /* Present the API explorer view.
         *
         * This will present the list of all available services with active
         * forms to allow direct invocation and presentation of results.
         */
        $this->view->headTitle( $this->view->title );
        $this->view->server   = $server;
        $this->view->feedType = $feed;

        $this->render('feed');
    }

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

    /** @brief  (Re)enable view rendering.
     */
    protected function _enableRendering()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->setParam('disableOutputBuffering', false);
        $front->getDispatcher()
                ->setParam('disableOutputBuffering', false);

        $viewRenderer =
          Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');

        $view = $viewRenderer->view;
        if ($view instanceof Zend_View_Interface)
        {
            $viewRenderer->setNoRender(false);
        }

        $layout = Zend_Layout::getMvcInstance();
        if ($layout instanceof Zend_Layout)
        {
            $layout->enableLayout();
        }
    }
}
