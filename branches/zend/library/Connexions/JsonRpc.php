<?php
/** @file
 *
 *  Provide full JsonRpc request response handling.
 */

class Connexions_JsonRpc
{
    protected   $_callback      = '';

    protected   $_jsonRaw       = null;
    protected   $_jsonClean     = null;
    protected   $_request       = null;
    protected   $_response      = null;

    protected   $_params        = null; // Cache of $_request->getParams()

    protected   $_isValid       = false;

    /** @brief  Create a new JSON RPC handler.
     *  @param  request         The Zend_Controller_Request_Abstract instance.
     *  @param  defaultMethod   The default method to use.
     */
    public function __construct(Zend_Controller_Request_Abstract    $request,
                                $defaultMethod  = null)
    {
        $this->_initRequest($request, $defaultMethod);
        $this->_initResponse();
    }

    public function getRequest()
    {
        return $this->_request;
    }

    public function getResponse()
    {
        return $this->_response;
    }

    /** @brief  Set an error.
     *  @param  error   A Zend_Json_Server_Error instance OR a string;
     *  @param  code    If 'error' is a string, this is the associated error
     *                  code;
     *  @param  data    If 'error' is a string, this is the associated error
     *                  data;
     *
     *
     *  @return $this
     */
    public function setError($error,
                             $code  = Zend_Json_Server_Error::ERROR_OTHER,
                             $data  = null)
    {
        if ($this->_response !== null)
        {
            if (! $error instanceof Zend_Json_Server_Error)
            {
                $error = new Zend_Json_Server_Error($error, $code, $data);
            }

            $this->_reponse->setError($error);
        }

        return $this;
    }

    /** @brief  Set the result value of our response.
     *  @param  value   The result value.
     *
     *  @return $this
     */
    public function setResult($value)
    {
        if ($this->_isValid)
        {
            $this->_response->setResult($value);
        }

        return $this;
    }

    /** @brief  Is this a valid Json RPC?
     *
     *  @return true | false
     */
    public function isValid()
    {
        return $this->_isValid;
    }

    /** @brief  Retrieve the request method.
     *
     *  @return The value of the request method (null if invalid).
     */
    public function getMethod()
    {
        $method = null;
        if ($this->_isValid)
            $method = $this->_request->getMethod();

        return $method;
    }

    /** @brief  Retrieve a request parameter.
     *  @param  name        The parameter name/key/index
     *  @param  default     If the parameter does not exist, return this value.
     *
     *  @return The value of the parameter (or $default).
     */
    public function getParam($name, $default = null)
    {
        //$val = $this->_request->getParam($name);
        if (array_key_exists($name, $this->_params))
        {
            $val = $this->_params[$name];
        }
        else
        {
            $val = $default;
        }

        /*
        Connexions::log("Connexions_JsonRpc::getParam(%s, %s): val[ %s ]",
                        $name, $default, $val);
        // */

        return $val;
    }

    /** @brief  Generate the JSON RPC response, setting any required headers if
     *          they haven't already been sent.
     *
     *  @return The JSON encoded response.
     */
    public function toJson()
    {
        $this->sendHeaders();
        if ( (! $this->isValid()) ||
             ( (! $this->_response->isError()) &&
               (  $this->_response->getId() === null) ) )
        {
            $json = '';
        }
        else
        {
            $json = $this->_response->toJson();
        }

        /*
        Connexions::log("Connexions_JsonRpc::toJson: "
                        . 'is'. ($this->isValid() ? '':' NOT') .' valid, '
                        . 'is'. ($this->_response && $this->_response->isError()
                                            ? '':' NOT') .' error, '
                        . "json[ {$json} ]");
        // */

        return sprintf("%s(%s);", $this->_callback, $json);
    }

    /** @brief  If headers haven't already been sent, send them now.
     *  
     *  If ID is null, send HTTP 204, otherwise, send the content type headers.
     *
     *  return  $this
     */
    public function sendHeaders()
    {
        if ( headers_sent() || (! $this->isValid()) )
            return;

        if ( (! $this->_response->isError()) &&
             (  $this->_response->getId() === null) )
        {
            // The request was a notification that requires no response
            header('HTTP/1.1 204 No Content');
            return;
        }
    }

    /**************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Given an abstract request, generate a matching JSON RPC
     *          Request.
     *  @param  request         The Zend_Controller_Request_Abstract instance.
     *  @param  defaultMethod   The default method to use.
     *
     *  @return A Zend_Json_Server_Request instance, null on failure.
     */
    protected function _initRequest(Zend_Controller_Request_Abstract $request,
                                    $defaultMethod)
    {
        $this->_isValid = false;
        $this->_request = new Zend_Json_Server_Request();

        $this->_callback = $request->getParam('jsonp',
                            $request->getParam('callback', ''));

        /* See if perhaps there is JSON-encoded data in the 'rpc' or 'json'
         * parameter.
         */
        $json = $request->getParam('rpc',
                    $request->getParam('json', null));

        if (empty($json))
        {
            /* See if this is a JSON-RPC encoded in the URL:
             *      e.g. jsonrpc=2.0&method=abc&params=<json-encoded-params>
             *           jsonrpc=2.0&method=abc&owner=def&tags=hij,klm,nop
             *
             *  {"method":"name", "params":[ params ], "id":1}
             *  {"version":"1.1", "method":"name", "params":[ params ], "id":1}
             *  {"jsonrpc":"2.0", "method":"name", "params":[ params ], "id":1}
             *  {"jsonrpc":"2.0", "method":"name",
             *      "params":{ "name":"val", ... }, "id":1}
             */
            $req = $request->getParams();

            if (! @empty($req['params']))
            {
                // JSON-encode parameters...
                $params = $req['params'];
            }
            else
            {
                /* Assemble JSON-RPC 'params' from the primary request
                 * parameters (minus 'method', 'jsonrpc', 'version', 'id')
                 */
                $params = '';
                foreach ($req as $key => $val)
                {
                    switch ($key)
                    {
                    case 'method':
                    case 'id':
                    case 'jsonrpc':
                    case 'version':
                        // skip
                        continue;

                    default:
                        $valLen  = strlen($val);
                        if (! ((is_numeric($val))                           ||
                               (($val[0] == '{') && $val[$valLen-1] == '}') ||
                               (($val[0] == '[') && $val[$valLen-1] == ']')) )
                        {
                            $val = '"'. $val .'"';
                        }

                        if ($params !== '')
                            $params .= ',';

                        $params .= '"'. $key .'":'. $val;
                    }
                }

                $params = '{'. $params .'}';
            }

            // See if we have a method
            if (! @empty($req['method']))
                $method = $req['method'];
            else
                $method = $defaultMethod;

            // See if we have an id.
            $id = false;
            if (isset($req['id']))
            {
                if (is_numeric($req['id']))
                    $id = $req['id'];
                else
                    $id = '"'. $req['id'] .'"';
            }
            else
            {
                // Force an id
                $id = 1;
            }

            // Generate a JSON-encoded JSON-RPC string.
            $json = '{'
                  .  (isset($req['jsonrpc'])
                        ? '"jsonrpc":"'. $req['jsonrpc'] .'",'
                        : (isset($req['version'])
                            ? '"version":"'. $req['version'] .'",'
                            : ''))
                  .  '"method":"'. $method .'",'
                  .  '"params":'. $params
                  .  ($id !== false
                        ? ',"id":'. $id
                        : '')
                  . '}';
        }

        if (! empty($json))
        {
            // Attempt to decode the JSON-RPC request
            $this->_jsonRaw  = $json;

            /*
            Connexions::log("Connexions_JsonRpc:: Attempt to load json "
                            . "[ {$json} ]");
            // */

            try
            {
                $this->_request->loadJson($json);

                $this->_isValid = true; // seems to be...
            }
            catch (Exception $e)
            {
                // Invalid JSON -- try a quick cleaning
                $json = $this->_cleanJson($json);

                /* Let any exception pass here since we can do nothing else
                 * to fix it...
                 */
                $this->_request->loadJson($json);

                $this->_isValid = true; // seems to be...
            }

            if ($this->_isValid)
                $this->_jsonClean = $json;
        }

        $method = $this->_request->getMethod();
        if ( $this->_request->isMethodError() || empty($method) )
        {
            // Missing / invalid method
            $this->_isValid = false;
        }

        // Cache our request parameters so we can have a smarter getParam()
        $this->_params = $this->_request->getParams();
    }

    /** @brief  Given an abstract request, generate a matching JSON RPC
     *          Request.
     *  @param  request     The Zend_Controller_Request_Abstract instance.
     *
     *  @return A Zend_Json_Server_Request instance, null on failure.
     */
    protected function _initResponse()
    {
        if (! $this->_isValid)
            return;

        $this->_response = new Zend_Json_Server_Response();

        // Initialize version and id from the request.
        $this->_response->setVersion( $this->_request->getVersion() );
        $this->_response->setId( $this->_request->getId() );
        $this->_response->setArgs( $this->_request->getParams() );
    }

    /** @brief  Cleanup a string in an attempt to produce valid JSON.
     *  @param  str     The incoming string.
     *
     *  All keys and values MUST be quoted with " and NOT '.
     *
     *  @return THe processed string.
     */
    protected function _cleanJson($str)
    {
        /* Attempt to ensure that this is valid JSON.
         *
         * All keys and values MUST be quoted with " and NOT '.
         *
         * First, extract all strings that ARE quoted with "
         */
        $nQuoted = 0;
        if (preg_match_all('/("[^"]+")/', $str, $matches))
        {
            $quoted  = $matches[1];
            $nQuoted = count($quoted);
            $str     = preg_replace('/"[^"]+"/', '%x%', $str);
        }

        // Replace all single quotes with double.
        $str = preg_replace("/'/", '"', $str);

        // Locate all keys that are not quoted...
        if (preg_match_all('/[{,]([^":}]+):/', $str, $matches))
        {
            // ... and quote them.
            foreach ($matches[1] as $match)
            {
                $str = preg_replace('/'.$match.':/', '"'.$match.'":', $str);
            }
        }

        // Re-insert the original quoted values.
        $quotesUsed = 0;
        while (($quotesUsed < $nQuoted) && preg_match('/%x%/', $str))
        {
            $str = preg_replace('/%x%/', $quoted[$quotesUsed++], $str, 1);
        }

        return $str;
    }
}
