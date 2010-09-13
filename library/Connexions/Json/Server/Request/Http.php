<?php
/** @file
 *
 *  Replacement for Zend_Json_Server_Request_Http that:
 *      - records the request method and URI;
 *      - allows a JSON-encoded string to be passed in a GET request as the
 *        Query, e.g.:
 *          http://localhost/api/v1/json-rpc?{"version":2,"method": ...}
 *
 *      - allows general, non-JSON query parameters for those simple cases
 *        where the Json Server accepts a GET request with Query parameter(s)
 *        used to determine the response.  For example, a Json server that can
 *        return the API when requested:
 *          http://localhost/api/v1/json-rpc?getApi=1
 *
 */
class Connexions_Json_Server_Request_Http extends Zend_Json_Server_Request
{
    const   SCHEME_HTTP     = 'http';
    const   SCHEME_HTTPS    = 'https';

    /********************************************
     * Much of the following is copied from
     * Zend_Controller_Request_Http
     *
     */
    protected   $_requestUri    = null;
    protected   $_rawBody       = null;
    protected   $_rawJson       = null;

    public function __construct($uri = null)
    {
        if ($uri !== null)
        {
            if (! $uri instanceof Zend_Uri)
            {
                $uri = Zend_Uri::factory($uri);
            }

            if ($uri->valid())
            {
                $path  = $uri->getPath();
                $query = $uri->getQuery();
                if (! empty($query))
                {
                    $path .= '?'. $query;
                }

                $this->setRequestUri($path);
            }
            else
            {
                throw new Exception('Invalid URI provided to constructor');
            }
        }
        else
        {
            $this->setRequestUri();
        }

        if ($this->_rawJson === null)
        {
            $this->setRawJson();
        }
    }

    /** @brief  Retrieve a member of the $_SERVER superglobal
     *  @param  key     The desired key (null == return all);
     *  @param  default The default value if 'key' is not found [ null ];
     *
     *  @return The value from $_SERVER if 'key' exists, otherwise 'default';
     *          If 'key' is null, return the entire $_SERVER array.
     */
    public function getServer($key = null, $default = null)
    {
        if ($key === null)
        {
            return $_SERVER;
        }

        return (isset($_SERVER[$key]) ? $_SERVER[$key] : $default);
    }

    /** @brief  Return the reqest URI scheme.
     *
     *  @return string
     */
    public function getScheme()
    {
        return ($this->getServer('HTTPS') == 'on'
                    ? self::SCHEME_HTTPS
                    : self::SCHEME_HTTP);
    }

    /** @brief  Return the HTTP host.
     *
     *  'Host' ':' host [ ':' port ]; Section 3.2.2
     *
     *  Note the HTTP Host header is not the same as the URI host.  It includes
     *  the port while the URI host does not.
     *
     *  @return string
     */
    public function getHttpHost()
    {
        $host = $this->getServer('HTTP_HOST');
        if (empty($host))
        {
            $scheme = $this->getScheme();
            $name   = $this->getServer('SERVER_NAME');
            $port   = $this->getServer('SERVER_PORT');

            if ( (($scheme == self::SCHEME_HTTP)  && ($port == 80)) ||
                 (($scheme == self::SCHEME_HTTPS) && ($port == 443)) )
            {
                $host = $name;
            }
            else
            {
                $host = $name .':'. $port;
            }
        }

        return $host;
    }

    /** @brief  Return the raw body of the request, if present.
     *
     *  @return string | false
     */
    public function getRawBody()
    {
        if ($this->_rawBody === null)
        {
            $body = file_get_contents('php://input');

            if (strlen(trim($body)) > 0)
            {
                $this->_rawBody = $body;
            }
            else
            {
                $this->_rawBody = false;
            }
        }

        return $this->_rawBody;
    }
    /** @brief  Return the value of the given HTTP header.
     *  @param  header  The HTTP header name;
     *
     *  @throws Exception
     *  @return string | false
     */
    public function getHeader($header)
    {
        if (empty($header))
        {
            throw new Exception('An HTTP header name is required.');
        }

        // Try to get the value from $_SERVER first.
        $val = $this->getServer('HTTP_'. strtoupper(
                                            str_replace('-','_',$header)));
        if ($val === null)
        {
            $val = false;
            if (function_exists('apache_request_headers'))
            {
                $headers = apache_request_headers();
                if (! empty($headers[$header]))
                {
                    $val = $headers[$header];
                }
            }
        }

        return $val;
    }

    /** @brief  Retrieve the HTTP request method.
     *
     *  @return string
     */
    public function getRequestMethod()
    {
        return $this->getServer('REQUEST_METHOD');
    }

    /** @brief  Was the HTTP request method POST?
     *
     *  @return true | false
     */
    public function isPost()
    {
        return ($this->getRequestMethod() == 'POST'
                    ? true
                    : false);
    }

    /** @brief  Was the HTTP request method GET?
     *
     *  @return true | false
     */
    public function isGet()
    {
        return ($this->getRequestMethod() == 'GET'
                    ? true
                    : false);
    }

    /** @brief  Was the HTTP request method PUT?
     *
     *  @return true | false
     */
    public function isPut()
    {
        return ($this->getRequestMethod() == 'PUT'
                    ? true
                    : false);
    }

    /** @brief  Was the HTTP request method DELETE?
     *
     *  @return true | false
     */
    public function isDelete()
    {
        return ($this->getRequestMethod() == 'DELETE'
                    ? true
                    : false);
    }

    /** @brief  Was the HTTP request method HEAD?
     *
     *  @return true | false
     */
    public function isHead()
    {
        return ($this->getRequestMethod() == 'HEAD'
                    ? true
                    : false);
    }

    /** @brief  Was the HTTP request method OPTIONS?
     *
     *  @return true | false
     */
    public function isOptions()
    {
        return ($this->getRequestMethod() == 'OPTIONS'
                    ? true
                    : false);
    }

    /** @brief  Is the request a Javascript XMLHttpRequest?
     *
     *  @return true | false
     */
    public function isXmlHttpRequest()
    {
        return ($this->getHeader('X_REQUESTED_WITH') == 'XMLHttpRequest');
    }

    /** @brief  Is this a Flash request?
     *
     *  @return true | false
     */
    public function isFlashRequest()
    {
        $header = strtolower($this->getHeader('USER_AGENT'));
        return (strstr($header, ' flash') ? true : false);
    }

    /** @brief  Set QUERY values
     *  @param  spec        The QUERY string or array
     *  @param  value       If 'spec' is a string, the value.
     *
     *  @return $this for a fluent interface
     */
    public function setQuery($spec, $value = null)
    {
        if ( (! is_array($spec)) && ($value === null) )
        {
            throw new Exception('Invalid value passed to setQuery(); '
                                .   'must be either array of values or '
                                .   'key/value pair.');
        }

        if (is_array($spec) && ($value === null))
        {
            foreach ($spec as $key => $value)
            {
                $this->setQuery($key, $value);
            }
        }
        else
        {
            $this->addParam($value, (string)$spec);
        }

        return $this;
    }

    /** @brief  Set the REQUEST_URI
     *  @param  requestUri  The REQUEST_URI - it not provided, uses a value
     *                      from $_SERVER, taking into account platform
     *                      differences between Apache and IIS;
     *
     *  @return $this for a fluent interface
     */
    public function setRequestUri($requestUri = null)
    {
        if ($requestUri === null)
        {
            if (isset($_SERVER['HTTP_X_REWRITE_URL']))
            {
                // Check this first so IIS will catch
                $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
            }
            else if (isset($_SERVER['IIS_WasUrlRewritten']) &&
                     $_SERVER['IIS_WasUrlRewritten'] == '1' &&
                     isset($_SERVER['UNENCODED_URL'])       &&
                     $_SERVER['UNENCODED_URL'] != '')
            {
                // IIS7 with URL Rewrite: make sure we get the unencoded url
                $requestUri = $_SERVER['UNENCODED_URL'];
            }
            else if (isset($_SERVER['REQUEST_URI']))
            {
                $requestUri = $_SERVER['REQUEST_URI'];

                /* HTTP Proxy reqs setup request URI with scheme and host
                 * [and port] + the url path, only use url path.
                 */
                $schemeAndHttpHost = $this->getScheme()
                                   .    '://'
                                   .    $this->getHttpHost();

                if (strpos($requestUri, $schemeAndHttpHost) === 0)
                {
                    $requestUri = substr($requestUri,
                                         strlen($schemeAndHttpHost));
                }
            }
            else if (isset($_SERVER['ORIG_PATH_INFO']))
            {
                // IIS 5.0, PHP as CGI
                $requestUri = $_SERVER['ORIG_PATH_INFO'];
                if (! empty($_SERVER['QUERY_STRING']))
                {
                    $requestUri .= '?'. $_SERVER['QUERY_STRING'];
                }
            }
            else
            {
                // NO valid Request URI.
                return $this;
            }
        }
        elseif (! is_string($requestUri))
        {
            // NOT a valid Request URI.
            return $this;
        }

        // Set any QUERY parameters
        if (($pos = strpos($requestUri, '?')) !== false)
        {
            $query = substr($requestUri, $pos+1);

            // Allow the query to be a JSON-encoded string.
            if (($query[0] === '{') && ($query[strlen($query)-1] === '}'))
            {
                // This MAY be a raw JSON-encoded string...
                $json = rawurldecode($query);

                try
                {
                    $this->setRawJson($json);

                    /*
                    Connexions::log("Connexions_Json_Server_Request_Http::"
                                    . "setRequestUri(): "
                                    . "Query is JSON [ %s ]",
                                    $json);
                    // */

                    /* It WAS valid JSON.  The entire string will have been
                     * consumed, so we have no query left to deal with.
                     */
                    $query = null;
                }
                catch (Exception $e)
                {
                    // NOT valid JSON - just set it as a parameter
                }
            }

            if (! empty($query))
            {
                parse_str($query, $vars);
                $this->setQuery($vars);
            }
        }

        $this->_requestUri = $requestUri;

        return $this;
    }

    /** @brief  Returns the REQUEST_URI.
     *
     *  @return string
     */
    public function getRequestUri()
    {
        if (empty($this->_requestUri))
        {
            $this->setRequestUri();
        }

        return $this->_requestUri;
    }

    /** @brief  Get JSON from raw POST body
     *
     *  @return string
     */
    public function getRawJson()
    {
        if ($this->_rawJson === null)
        {
            $this->setRawJson();
        }
        return $this->_rawJson;
    }

    /** @brief  Set the Raw JSON, loading any variables.
     *  @param  rawJson     The Raw JSON string.
     *
     *  @return $this for a fluent interface.
     */
    public function setRawJson($rawJson = null)
    {
        if (! empty($rawJson))
        {
            // Is 'rawJson' valid?  If not, it will throw an exception
            Zend_Json::decode($rawJson);
        }
        if ($rawJson === null)
        {
            $rawJson = $this->getRawBody();
        }

        if (empty($rawJson))
        {
            // Nothing to set.
            return $this;
        }


        if ($this->_rawJson !== null)
        {
            // Clear out current parameters
        }

        $this->_rawJson = $rawJson;
        if (!empty($this->_rawJson))
        {
            $this->loadJson($this->_rawJson);
        }

        return $this;
    }
}
