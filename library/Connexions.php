<?php
/** @file
 *
 *  The Connexions singleton, in addition to a few general helper functions.
 *  Provides general functionality.
 *
 */
class Connexions
{
    protected static    $_user  = null;
    protected static    $_db    = null;
    protected static    $_log   = null;

    /** @brief  Provide a general logging mechanism.
     *  @param  fmt     The sprintf-like format string
     *  @param  ...     Any additional sprintf arguments matching 'fmt'.
     */
    public static function log($fmt /*, ... */ )
    {
        if (self::$_log === null)
        {
            /* Logging is established on boot via:
             *      application/Bootstrap.php
             *          Bootstrap::_initLog()
             *
             * using data from:
             *      application/configs/application.ini
             *          resources.log.*
             */
            try
            {
                self::$_log = Zend_Registry::get('log');
            }
            catch (Zend_Exception $e)
            {
                // Don't try retrieving from the registry every time...
                self::$_log = -1;
            }
        }

        if (! self::$_log instanceof Zend_Log)
        {
            //echo "Connexions::log: DISABLED [{$fmt}]\n";
            return;
        }

        /****************************************************************
         * Generate the log message.
         *
         */
        $argv = func_get_args();
        $argc = count($argv);
        if ($argc > 1)
        {
            /*$fmt = */   array_shift($argv);
            $message = vsprintf($fmt, $argv);
        }
        else
        {
            $message = $fmt;
        }

        self::$_log->log($message, Zend_Log::DEBUG);
    }

    /** @brief  Generate a print_r of the given variable, replacing all '\n'
     *          with ', '
     *  @param  var     The variable to dump.
     *
     *  @return The string representation.
     */
    public static function varExport($var)
    {
        //return str_replace("\n", '', var_export($var, true));
        return preg_replace('/\s*$\s+/ms', '', var_export($var, true));
    }

    /** @brief  Return the current Database Adapter.
     *
     *  Note: The database adapter is established on boot via:
     *              application/Bootstrap.php
     *                  Bootstrap::_initDb()
     *
     *          using data from:
     *              application/configs/application.ini
     *                  resources.db.*
     *
     *  @return The current Database Adapter (Zend_Db_Adapter_Abstract).
     */
    public static function getDb()
    {
        if (self::$_db === null)
        {
            try
            {
                self::$_db = Zend_Registry::get('db');
            }
            catch (Zend_Exception $e)
            {
                self::$_db = null;
            }
        }

        return self::$_db;
    }

    /** @brief  Clear the currently authenticated user.
     *
     */
    public static function clearUser()
    {
        self::$_user = null;
    }

    /** @brief  Return the currently authenticated user.
     *
     *  @return The currently authenticated user (false if none).
     */
    public static function getUser()
    {
        if (self::$_user === null)
        {
            try
            {
                self::$_user = Zend_Registry::get('user');
            }
            catch (Zend_Exception $e)
            {
                /* :XXX: Should we create an 'anonymous', non-backed,
                 *       unauthenticated user in this case??
                 */
                self::$_user = false;
            }
        }

        return self::$_user;
    }

    /** @brief  Retrieve the current request object.
     *
     *  @return A Zend_Controller_Request_* object.
     */
    public static function getRequest()
    {
        return Zend_Controller_Front::getInstance()->getRequest();
    }

    /** @brief  Retrieve the URL of the current request.
     *
     *  @return A URI string.
     */
    public static function getRequestUri()
    {
        return Zend_Controller_Front::getInstance()
                                ->getRequest()
                                ->getRequestUri();
    }

    /** @brief  Given a parameter name, default, and possibly a request,
     *          retrieve the given parameter from $_GET, $_POST, or cookies.
     *  @param  name        The name of the desired parameter;
     *  @param  default     Any default [null];
     *  @param  request     If provided, a Zend_Controller_Request instance.
     *
     *  @return The parameter value.
     */
    public static function getParam($name,
                                    $default    = null,
                                    $request    = null)
    {
        if ( ($request === null) ||
             ! ($request instanceof Zend_Conroller_Request_Abstract))
            $request = self::getRequest();

        $val = $request->getParam($name, $default);
        if ($val == $default)
        {
            // See if there is a cookie
            if ($request instanceof Zend_Controller_Request_Http)
            {
                $val = $request->getCookie($name, $default);
            }
        }

        return $val;
    }

    /** @brief  Given a site URL, apply any 'base' url prefix and return.
     *  @param  url     The site URL.
     *
     *  @return The full site URL with any 'base' prefix.
     */
    public static function url($url)
    {
        $front  =& Zend_Controller_Front::getInstance();

        if (@is_string($url))
        {
            if ($url[0] == '/')
            {
                // Convert to a site-absolute URL
                $baseUrl = $front->getBaseUrl();

                if (strpos($url, $baseUrl) !== 0)
                    $url = $baseUrl . $url;
            }

        }
        else if (@is_array($url))
        {
            $router =& $front->getRouter();
            $url    =  $router->assemble(array(), $url);
        }

        
        return $url;
    }

    /** @brief  Given a URL and name, return the HTML of a valid anchor.
     *  @param  url         The site URL.
     *  @param  name        The anchor name/title.
     *  @param  cssClass    A CSS class string (or array of class strings).
     *
     *  @return The HTML of an anchor with a full site URL including any
     *          'base' prefix.
     */
    public static function anchor($url, $name, $cssClass = null)
    {
        if (@is_array($cssClass))
            $cssClass = implode(' ', $cssClass);

        return sprintf ("<a href='%s'%s>%s</a>",
                        self::url($url),
                        (! @empty($cssClass)
                            ? "class='". $cssClass ."'"
                            : ''),
                        $name);
    }

    /** @brief  Perform variable replacement.
     *  @param  str     The string to operate on.
     *
     *  This will replace variables of the form
     *      '%namespace.selector%'
     *          OR
     *      '%25namespace.selector%25'  (to handle URL encoding of %)
     *
     *  Recognized 'namespace's are:
     *      user    - can accept a selector identifying any field.
     *
     *  @return A string with replacements.
     */
    public static function replaceables($str)
    {
        if (preg_match_all('/%(?:25)?([^%\.]+)\.([^%]+)%(?:25)?/',
                           $str, $names))
        {
            $nNames = count($names[0]);

            for ($idex = 0; $idex < $nNames; $idex++)
            {
                $nameSpace = $names[1][$idex];
                if (empty($nameSpace))
                    continue;

                $selector  = $names[2][$idex];

                switch (strtolower($nameSpace))
                {
                case 'user':
                    $user        = self::getUser();
                    $replacement = $user->{$selector};
                    break;
                case 'html':
                    $replacement = '<'. $selector .'/>';
                    break;

                default:
                    $replacement = '!'. $nameSpace .'.'. $selector .'!';
                    break;
                }

                $str = preg_replace('/%(25)?'. $nameSpace .'.'
                                             . $selector  .'%(25)?/i',
                                    $replacement, $str);
            }
        }

        return $str;
    }

    /** @brief  Given a string, determine whether or not it APPEARS to be an
     *          MD5 hash.
     *  @param  str     The string to check.
     *
     *  @return true | false
     */
    public static function isMd5($str)
    {
        // An MD5 hash is comprised of exactly 32 hex characters.
        if ((strlen($str) === 32) &&
            (strspn($str, '0123456789abcdef') === 32))
        {
            // Appears to be an MD5 hash
            return true;
        }

        // Doesn't appear to be an MD5 hash
        return false;
    }

    /** @brief  Given a URL string, normalize and generate an MD5 hash.
     *  @param  url     The URL string to operate on.
     *
     *  @return The MD5 hash string.
     */
    public static function md5Url($str)
    {
        /* If this already appears to be an MD5 hash (32 hex characters), don't 
         * compute it again.
         */
        if (self::isMd5($str))
        {
            // Appears to ALREADY be an MD5 hash
            return $str;
        }

        // Normalize and compute the MD5 hash
        return md5( self::normalizeUrl($str) );
    }

    /** @brief  Normalize a URL.
     *  @param  url     The URL string to normalize.
     *
     *  @return The normalized URL string.
     */
    public static function normalizeUrl($url)
    {
        // decode and lower-case the incoming url
        $url = urldecode($url);
        $url = strtolower($url);
        $url = trim(preg_replace('/\s+/', ' ', $url), " \t");

        $uri = parse_url( $url );

        $scheme = null;
        $host   = null;
        $port   = null;
        $path   = null;

        // Generate a normalized URI: See RFC 3886, section 6
        foreach ($uri as $part => $val)
        {
            switch ($part)
            {
            case 'scheme':
                // schemes are case-insensitive
                $scheme = $val;
                break;

            case 'host':
                // hostnames are case-insensitive
                $host = $val;
                break;

            case 'port':
                // ports should be integer
                $port = (int)$val;
                break;

            case 'user':
            case 'pass':
                // no change
                break;

            case 'path':
                // Convert any '\' to '/'
                $path = str_replace('\\', '/', $val);

                // Collapse and trim white-space
                $path = trim(preg_replace('/\s+/', ' ', $path));

                /* Make the path absolute, collapsing all '.' and '..' portions 
                 * of the path.
                 */
                $dirs = array();
                foreach (explode('/', $path) as $dir)
                {
                    if ($dir === '.')
                        continue;
                    if ($dir === '..')
                    {
                        array_pop($dirs);
                        continue;
                    }

                    array_push($dirs, $dir);
                }

                $val  = implode('/', $dirs);
                break;

            case 'query':
                // Collapse and trim white-space
                $val = trim(preg_replace('/\s+/', ' ', $val));

                // Ensure a normalized order
                parse_str($val, $query);
                ksort($query);

                /* Re-build the query -- this will also perform urlencode() on 
                 *                       each element.
                 */
                $val = http_build_query($query);
                break;

            case 'fragment':
                // no change
                break;

            default:
                break;
            }

            $uri[$part] = $val;
        }

        if ($port !== null)
        {
            // Remove default port number for known schemes
            //      RFC 3986, section 6.2.3
            if ($scheme === 'mailto')
                $defPort = 25;
            else
                $defPort = getservbyname($scheme, 'tcp');

            if ($port === $defPort)
            {
                unset($uri['port']);
            }
        }

        // Scheme based normalization
        //      RFC 3986, section 6.2.3
        if (! empty($host))
        {
            $uri['host'] = $host;
            if (empty($path))
                $uri['path'] = '/';
        }
        else if (! empty($path))
        {
            $uri['path'] = $path;
        }

        /**************************************************
         * Construct the normalized URL
         *
         *  $normUrl = http_build_url($uri);
         */
        $normUrl = $uri['scheme'] .':';
        if ($uri['scheme'] !== 'mailto')
        {
            $normUrl .= '//';

            if (! @empty($uri['user']))
            {
                $normUrl .= $uri['user'];
                if (! @empty($uri['pass']))
                    $normUrl .= ':'. $uri['pass'];
                $normUrl .= '@';
            }

            $normUrl .= $uri['host'];
            if (isset($uri['port']) && ($uri['port'] > 0))
                $normUrl .= ':'. $uri['port'];
        }
        else
        {
            // mailto
            $uri['path'] = trim($uri['path'], '/');
        }

        $normUrl .= $uri['path'];
        if (! @empty($uri['query']))
            $normUrl .= '?'. $uri['query'];

        if (! @empty($uri['fragment']))
            $normUrl .= '#'. $uri['fragment'];

        return $normUrl;
    }

    /** @brief  Search for 'needle' in 'haystack' including any sub-arrays
     *          within 'haystack'.
     *  @param  needle      The item to locate (mixed).
     *  @param  haystack    The array to search.
     *
     *  @return true | false
     */
    public static function in_array($needle, array $haystack)
    {
        foreach ($haystack as $key => $value)
        {
            if ($needle === $value)
                return true;

            if (is_array($value))
            {
                if ( ($res = self::in_array($needle, $value)) !== false)
                    return true;
            }
        }

        return false;
    }

}

/****************************************************************************
 * General helper functions.
 *
 */

