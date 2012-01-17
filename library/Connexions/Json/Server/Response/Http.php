<?php
/** @file
 *
 *  Replacement for Zend_Json_Server_Response_Http that:
 *      - allows a JSONP callback to be set;
 *      - in toJson(), if a callback is set, include that callback in the JSON
 *        encoded response;
 *
 */
class Connexions_Json_Server_Response_Http
                                extends Zend_Json_Server_Response_Http
{
    protected   $_callback      = null;

    /** @brief  Set a JSONP callback.
     *  @param  callback    The callback string;
     *
     *  @return Zend_Json_Server_Response_Http
     */
    public function setCallback($callback)
    {
        $this->_callback = $callback;

        return $this;
    }

    /** @brief  Retrieve the JSONP callback.
     *
     *  @return The JSONP callback (null if none).
     */
    public function getCallback()
    {
        return $this->_callback;
    }

    /** @brief  Cast this response as JSON.
     *
     *  @return The JSON-encoded string
     */
    public function toJson()
    {
        // Override Zend_Json_Server_Response_Http {
        $this->sendHeaders();
        if (!$this->isError() && ($this->getId() === null))
        {
            return '';
        }

        // Override Zend_Json_Server_Response {
        if ($this->isError())
        {
            $response = array(
                'result' => null,
                'error'  => $this->getError()->toArray(),
                'id'     => $this->getId(),
            );
        }
        else
        {
            // :XXX: If the result can be simplified, do it {
            $result = $this->getResult();
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
            // :XXX: If the result can be simplified, do it }

            $response = array(
                'result' => $result,
                'id'     => $this->getId(),
                'error'  => null,
            );
        }

        if ( ($version = $this->getVersion()) !== null)
        {
            $response['jsonrpc'] = $version;
        }

        require_once 'Zend/Json.php';
        $json = Zend_Json::encode($response);
        // Override Zend_Json_Server_Response }

        // If there is a JSONP callback, include it in the JSON encoding
        if (($callback = $this->getCallback()) !== null)
        {
            $json = $callback .'('. $json .')';
        }

        return $json;
        // Override Zend_Json_Server_Response_Http }
    }
}
