<?php
/** @file
 *
 *  This controller controls bookmark posting and is accessed
 *  via the url/routes:
 *      /post[ post parameters ]
 */

class ApiController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public    $dependencies = array('db','layout');
    public    $contexts     = array(
                                'index' => array('json', 'partial'),
                              );

    protected $_server              = null;
    protected $_noSidebar           = true;
    protected $_noFormatHandling    = true;

    public function init()
    {
        //Connexions::log("ApiController::init");

        $this->_baseUrl    = $this->_helper->url(null, 'api');
        $this->_cookiePath = $this->_baseUrl;

        parent::init();
    }

    /** @brief  API V1
     *
     *  Version 1 API interface.
     */
    public function v1Action()
    {
        $request = $this->_request;

        $request = $this->_request;
        $server  = new Zend_Json_Server();
        $server->setTarget(Connexions::url('/api/'))
               ->setClass('Service_Proxy_ApiV1');
        $this->_server = $server;

        if ($request->isGet() && ($request->getParam('serviceDescription')))
        {
            // Send the service description
            /*
            Connexions::log("ApiController::v1Action: "
                            . "return service description");
            // */

            return $this->_sendServiceDescription();
        }

        $jsonReq = null;
        $jsonRsp = null;
        $jsonRpc = $request->getParam('jsonRpc');
        $action  = $request->getParam('action', null);
        $cmd     = $request->getParam('cmd',    null);
        $subCmd  = $request->getParam('subCmd', null);

        /*
        Connexions::log("ApiController::v1Action: "
                        . "action[ %s ], cmd[ %s ], subCmd[ %s ], "
                        . "jsonRpc[ %s ], params[ %s ]",
                        $action, $cmd, $subCmd, $jsonRpc,
                        Connexions::varExport($request->getParams()));
        // */

        $jsonReq = new Connexions_Json_Server_Request_Http();
        $json    = $jsonReq->getRawJson();
        if (! empty($jsonRpc))
        {
            // Attempt to set the request from 'jsonRpc'
            try
            {
                $jsonReq->setRawJson( $this->_cleanupJson($jsonRpc) );
            }
            catch (Exception $e)
            {
                $err = new Zend_Json_Server_Error(
                                "Invalid JSON: {$e->getMessage()}",
                                Zend_Json_Server_Error::ERROR_PARSE);
                $jsonRsp = new Zend_Json_Server_Response();
                $jsonRsp->setError( $err );
            }
        }
        else if ( ! empty($json))
        {
            list($cmd, $subCmd) = explode('_', $jsonReq->getMethod());
        }
        else if ( (! empty($cmd)) && (! empty($subCmd)) )
        {
            // Build a JSON RPC from $_REQUEST
            $jsonReq->setOptions(array(
                'version'   => '2.0',
                'id'        => 1,
                'method'    => $cmd .'_'. $subCmd,
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

        if ($jsonRsp !== null)
        {
            // Directly output the response (most likely an error)
            $this->_disableRendering();
            echo $jsonRsp;
            return;
        }
        else if ($jsonReq !== null)
        {
            // Use the defined server to handle this request

            /*
            Connexions::log("ApiController::v1Action(): "
                            .   "cmd[ %s ], subCmd[ %s ], request params[ %s ]",
                            $cmd, $subCmd,
                            Connexions::varExport($jsonReq->getParams()) );
            // */

            $this->_request = $jsonReq;
            Connexions::setRequest($jsonReq);
            $server->setRequest($jsonReq);

            $this->_disableRendering();
            return $server->handle();
        }

        /* Present the API explorer view.
         *
         * This will present the list of all available services with active
         * forms to allow direct invocation and presentation of results.
         */
        $this->view->title = 'Api V1 Explorer';
        $this->view->headTitle( $this->view->title );
        $this->view->server = $server;
    }

    /** @brief  API V2
     *
     *  Version 2 API interface.
     */
    public function v2Action()
    {
        $request = $this->_request;
        $server  = new Zend_Json_Server();
        $server->setTarget(Connexions::url('/api/v2/json-rpc'))
               ->setClass('Service_Proxy_User',     'user')
               ->setClass('Service_Proxy_Item',     'item')
               ->setClass('Service_Proxy_Tag',      'tag')
               ->setClass('Service_Proxy_Bookmark', 'bookmark')
               ->setClass('Service_Proxy_Activity', 'activity')
               ->setClass('Service_Util',           'util');
        $this->_server = $server;

        if ($request->isGet() && ($request->getParam('serviceDescription')))
        {
            // Send the service description
            return $this->_sendServiceDescription();
        }

        $cmd = $request->getParam('cmd', null);
        if (! empty($cmd))
        {
            /* The only valid 'cmd' here is 'json-rpc' but we'll let the server
             * instance take care of that via $server->handle().
             *
             * This will also handle and invalid JsonRpc request, even if it is
             * mal-formed JSON.
             */
            $request = new Connexions_Json_Server_Request_Http();

            /*
            Connexions::log("ApiController::v2(): "
                            .   "cmd[ %s ], request params[ %s ]",
                            $cmd,
                            Connexions::varExport($request->getParams()) );
            // */

            $this->_request = $request;
            Connexions::setRequest($request);
            $server->setRequest($request);

            $this->_disableRendering();
            return $server->handle();
        }

        /* Present the API explorer view.
         *
         * This will present the list of all available services with active
         * forms to allow direct invocation and presentation of results.
         */
        $this->view->title = 'Api V2 Explorer';
        $this->view->headTitle( $this->view->title );
        $this->view->server = $server;
    }

    /** @brief Redirect all un-matched actions to 'v1Action'
     *  @param  method      The target method.
     *  @param  args        Incoming arguments.
     *
     */
    public function __call($method, $args)
    {
        if (substr($method, -6) == 'Action')
        {
            /*
            Connexions::log("ApiController::__call(): "
                            .   "forward method[ %s ], args[ %s ]",
                            $method,
                            Connexions::varExport($args));
            // */

            return $this->_forward('v1');
        }

        throw new Exception('Invalid method "'. $method .'" called', 500);
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

    /** @brief  Prepare for rendering the main view, regardless of format.
     *
     *  This will collect the variables needed to render the main view, placing
     *  them in $view->main as a configuration array.
     */
    protected function _prepare_main()
    {
        //Connexions::log("ApiController::_prepare_main():");

        parent::_prepare_main();

        $request  =& $this->_request;
        $postInfo =  $request->getParams();

        // /*
        Connexions::log("ApiController::indexAction: "
                        . "params [ %s ]",
                        Connexions::varExport( $postInfo ));
        // */


        if ($request->isPost())
        {
            // This is a POST -- attempt to create/update a bookmark
            $this->_doPost( $postInfo );
        }
        else
        {
            /* Initial presentation of posting form.
             *
             * Retrieve any existing bookmark for the given URL by the current
             * user.
             */
            $bookmark = $this->_doGet( $postInfo );
        }

        $this->view->postInfo   = $postInfo;
    }

    /** @brief  Given incoming POST data, attempt to create/update a bookmark.
     *  @param  param   postInfo    An array of incoming POST data.
     *
     *  @return The new/updated Model_Bookmark instance (null on error).
     */
    protected function _doPost(array &$postInfo)
    {
        // /*
        Connexions::log("ApiController: _doPost[ %s ]",
                        Connexions::varExport($postInfo));
        // */
    }

    /** @brief  Given incoming bookmark-related data, see if a matching
     *          bookmark exists and, if so, update 'postInfo' to represent the
     *          data of the bookmark.
     *  @param  param   postInfo    An array of incoming data.
     *
     *  @return The matching Model_Bookmark instance (null if no match).
     */
    protected function _doGet(array &$postInfo)
    {
        // /*
        Connexions::log("ApiController: _doGet[ %s ]",
                        Connexions::varExport($postInfo));
        // */
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

    /** @brief  Given a string that is SUPPOSED to be JSON-encoded, clean it up
     *          in an attempt to ensure that it is valid JSON.
     *  @param  str     The string to clean.
     *
     *  Strictly valid JSON MUST have all keys and any string values quoted
     *  with " (not ').
     *
     *  @return The processed string.
     */
    protected function _cleanupJson($str)
    {
        $str = urldecode($str);
    
        // First, extract all strings that are quoted with "
        if (preg_match_all('/("[^"]+")/', $str, $matches))
        {
            $quoted  = $matches[1];
            $nQuoted = count($quoted);
            $str     = preg_replace('/"[^"]+"/', '%x%', $str);
        }
        else
        {
            $nQuoted = 0;
        }
    
        // Remove all white-space around ',:{}'
        //$str = preg_replace("/\s*([,:\\{\\}])\s*/", '$1', $str);
    
        // Replace all unescaped single quotes with double.
        $str = preg_replace("/([^\\\\%])'/", '$1"', $str);
    
        // Locate all keys that are not quoted, and quote them.
        preg_match_all('/\s*[{,]\s*([^"\'%:]+):/', $str, $matches);
        foreach($matches[1] as $match)
        {
            $with = preg_replace('/\s+/', '', $match);
            $str  = preg_replace('/'. $match .':/', "\"{$with}\":", $str);
        }
    
        // Finally, re-insert the initial quoted values.
        $quotesUsed = 0;
        while (($quotesUsed < $nQuoted) && preg_match('/%x%/', $str))
        {
            $str = preg_replace('/%x%/', $quoted[$quotesUsed++], $str, 1);
        }
    
        return $str;
    }
}
