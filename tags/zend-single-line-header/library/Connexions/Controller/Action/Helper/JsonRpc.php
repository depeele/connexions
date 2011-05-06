<?php
/** @file
 *
 *  A general action helper used to send a JsonRpc response.
 *
 */

class Connexions_Controller_Action_Helper_JsonRpc
            extends Zend_Controller_Action_Helper_Abstract
{
    const ERROR_OTHER           = -32000;
    const ERROR_INVALID_REQUEST = -32600;
    const ERROR_INVALID_METHOD  = -32601;
    const ERROR_INVALID_PARAMS  = -32602;
    const ERROR_INTERNAL        = -32603;
    const ERROR_INVALID_JSON    = -32700;
    const ERROR_PARSE           = -32768;

    /** @brief  Suppress exit when send() is called
     */
    public $suppressExit        = false;

    /** @brief  The JsonRpc response
     *
     *  Initialized in init()
     */
    protected $_jsonRpc         = null;

    /** @brief  A JSONP callback to use.
     *
     *  Reset in init()
     */
    protected $_callback        = null;

    /** @brief  (Re)initialize this helper. */
    public function init()
    {
        $this->_jsonRpc = array(
            'jsonrpc'   => '2.0'
        );
        $this->_callback = null;
    }

    /** @brief  Set the JsonP callback value.
     *  @param  jsonp   The callback value.
     *
     *  @return $this
     */
    public function setCallback($jsonp)
    {
        $this->_callback = $jsonp;

        return $this;
    }

    /** @brief  Set the JsonRpc response data.
     *  @param  data    The response data.
     *
     *  @return $this
     */
    public function setResult($data)
    {
        $this->_jsonRpc['result'] = $data;

        return $this;
    }

    /** @brief  Set the JsonRpc error information.
     *  @param  message     The error message string.
     *  @param  code        The error code (ERROR_*).
     *
     *  @return $this
     */
    public function setError($message, $code = ERROR_OTHER)
    {
        $this->_jsonRpc['error'] = array(
            'code'      => $code,
            'message'   => $message
        );

        return $this;
    }

    /** @brief  Do we have an error?
     *
     *  @return true | false
     */
    public function hasError()
    {
        return (@is_array($this->_jsonRpc['error'])
                    ? true
                    : false);
    }

    /*************************************************************************/

    /** @brief  Disable layouts and view renderer.
     *
     *  @return $this
     */
    public function disableLayouts()
    {
        if ( ($layout = Zend_Layout::getMvcInstance()) !== null)
            $layout->disableLayout();

        Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')
                ->setNoRender(true);

        return $this;
    }

    /** @brief  Encode data to JSON.
     *  @param  keepLayouts Should layout be kept?
     *
     *  @return The JSON encoded string.
     */
    public function encodeJson($keepLayouts = false)
    {
        $json = Zend_Controller_Action_HelperBroker::getStaticHelper('Json')
                    ->encodeJson($this->_jsonRpc, $keepLayouts);

        if ($this->_callback !== null)
            $json = $this->_callback .'('. $json .')';

        return $json;
    }

    /** @brief  Send the JsonRpc response.
     *  @param  data        The data to encode.
     *  @param  keepLayouts Should layout be kept?
     *
     *  @return string|void
     */
    public function sendResponse($data = null, $keepLayouts = false)
    {
        if (! $keepLayouts)
            $this->disableLayouts();

        if ($data !== null)
            $this->setResult($data);

        $json = $this->encodeJson($keepLayouts);

        //$response = Zend_Controller_Front::getInstance()->getResponse();
        $response = $this->getResponse();
        $response->setHeader('Content-Type', 'application/json');
        $response->setBody($json);

        if (! $this->suppressExit)
        {
            $response->sendResponse();
            exit;
        }

        return $json;
    }

    /** @brief  Allow calling this helper as a broker method.
     *  @praam  data        The result data
     *  @param  keepLayouts Should layout be kept?
     *
     *  Called via 
     *  @return string|void
     */
    public function direct($data, $keepLayouts = false)
    {
        return $this->sendResponse($data, $keepLayouts);
    }
}
