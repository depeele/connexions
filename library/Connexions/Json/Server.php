<?php
/** @file
 *
 *  Extend Zend_Json_Server to make use of Connexions_Json_Server_Request_Http
 *  and Connexions_Json_Server_Response_Http to allow JSONP callbacks.
 *
 */
class Connexions_Json_Server extends Zend_Json_Server
{
    /** @brief  Get JSON-RPC request object
     *
     *  @return Zend_Json_Server_Request
     */
    public function getRequest()
    {
        if (($request = $this->_request) === null)
        {
            $this->setRequest(new Connexions_Json_Server_Request_Http());
        }

        return $this->_request;
    }

    /** @brief  Get JSON-RPC response object
     *
     *  @return Zend_Json_Server_Response
     */
    public function getResponse()
    {
        if (($response = $this->_response) === null)
        {
            $this->setResponse(new Connexions_Json_Server_Response_Http());
        }

        return $this->_response;
    }

    /** @brief  Set response state
     *
     *  @return Zend_Json_Server_Response
     */
    protected function _getReadyResponse()
    {
        $request  = $this->getRequest();
        $response = $this->getResponse();

        /* If the current request has a 'getCallback' method, and the current
         * response has a 'setCallback' method, marry the two.
         */
        if (method_exists($request,  'getCallback') &&
            method_exists($response, 'setCallback'))
        {
            $response->setCallback( $request->getCallback() );
        }

        $response->setServiceMap($this->getServiceMap());
        if (($id = $request->getId()) !== null)
        {
            $response->setId($id);
        }
        if (($version = $request->getVersion()) !== null)
        {
            $response->setVersion($version);
        }

        return $response;
    }
}
