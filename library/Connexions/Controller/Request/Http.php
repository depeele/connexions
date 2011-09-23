<?php
/** @file
 *
 *  An HTTP controller request that normalizes parameters.
 *
 */
class Connexions_Controller_Request_Http
            extends Zend_Controller_Request_Http
{
    /** @brief  Access values contained in the superglobals as public members
     *          Order of precedence: 1. GET, 2. POST, 3. COOKIE, 4. SERVER, 5.
     *          ENV
     *  @param  key     The desired parameter (string);
     *
     *  @see http://msdn.microsoft.com/en-us/library/system.web.httprequest.item.aspx
     *  @return mixed
     */
    public function __get($key)
    {
        /*
        Connexions::log("Connexions_Controller_Request_Http::__get(): "
                        . "key[ %s ]",
                        $key);
        // */

        switch (true) {
            case isset($this->_params[$key]):
                return $this->_normalize($this->_params[$key]);
            case isset($_GET[$key]):
                return $this->_normalize($_GET[$key]);
            case isset($_POST[$key]):
                return $this->_normalize($_POST[$key]);
            case isset($_COOKIE[$key]):
                return $_COOKIE[$key];
            case ($key == 'REQUEST_URI'):
                return $this->getRequestUri();
            case ($key == 'PATH_INFO'):
                return $this->getPathInfo();
            case isset($_SERVER[$key]):
                return $_SERVER[$key];
            case isset($_ENV[$key]):
                return $_ENV[$key];
            default:
                return null;
        }
    }

    /** @brief  Retrieve a member of the $_GET superglobal
     *  @param  key     The parameter to retrieve (string).  If null, return
     *                  the entire $_GET array;
     *  @param  default The default value to use if key is not found (mixed);
     *
     *  @return The value if the key exists, otherwise null.
     */
    public function getQuery($key = null, $default = null)
    {
        if ($key === null)
        {
            return $_GET;
        }

        return (isset($_GET[$key])
                    ? $this->_normalize($_GET[$key])
                    : $default);
    }

    /** @brief  Retrieve a member of the $_POST superglobal
     *  @param  key     The parameter to retrieve (string).  If null, return
     *                  the entire $_POST array;
     *  @param  default The default value to use if key is not found (mixed);
     *
     *  @return The value if the key exists, otherwise null.
     */
    public function getPost($key = null, $default = null)
    {
        if ($key === null)
        {
            return $_POST;
        }

        return (isset($_POST[$key])
                    ? $this->_normalize($_POST[$key])
                    : $default);
    }

    /** @brief  Retrieves a parameter from the instance. Priority is in the
     *          order of userland parameters (see {@link setParam()}), $_GET,
     *          $_POST.
     *  @param  key     The parameter to retrieve (string).  If the key is an
     *                  alias, the actual key aliased will be used.
     *  @param  default The default value to use if key is not found (mixed);
     *
     *  @return The value if the key exists, otherwise null.
     */
    public function getParam($key, $default = null)
    {
        $keyName = ( ($alias = $this->getAlias($key)) !== null
                        ? $alias
                        : $key );
        $val     = $default;

        if (isset($this->_params[$keyName]))
        {
            $val = $this->_normalize( $this->_params[$keyName] );
        }
        else
        {
            $paramSources = $this->getParamSources();
            if ( in_array('_GET', $paramSources) &&
                 isset($_GET[$keyName]) )
            {
                $val = $this->_normalize( $_GET[$keyName] );
            }
            else if ( in_array('_POST', $paramSources) &&
                      isset($_POST[$keyName]) )
            {
                $val = $this->_normalize( $_POST[$keyName] );
            }
        }

        return $val;
    }

    /** @brief  Perform normalization.
     *  @param  val     The value to normalize;
     *
     *  Normalization here applies only to strings and means URL decoding and
     *  trimming any leading/trailing white-space.
     *
     *  @return The normalized value.
     */
    protected function _normalize($val)
    {
        if (is_string($val))
        {
            $val = trim( urldecode( $val ) );

            /*
            $enc  = mb_detect_encoding($val);
            if ( ($enc !== 'UTF-8') || (! mb_check_encoding($val, 'UTF-8')) )
            {
                $val = utf8_encode($val);
            }
            // */
        }

        return $val;
    }
}
