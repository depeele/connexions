<?php
/** @file
 *
 *  The Connexions singleton, in addition to a few general helper functions.
 *  Provides general functionality.
 *
 */
class Connexions
{
    protected static    $_user          = null;
    protected static    $_db            = null;
    protected static    $_log           = null;
    protected static    $_request       = null;
    protected static    $_config        = null;
    protected static    $_urlMap        = array();
    protected static    $_externalUrls  = null;

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
            /*
            $nSlots = count(explode('%', $fmt));
            if ($nSlots > $argc)
            {
                throw new Exception("Too few arguments: {$nSlots} > {$argc}");
            }
            // */


            /*$fmt = */   array_shift($argv);
            $message = vsprintf($fmt, $argv);
        }
        else
        {
            $message = $fmt;
        }

        self::$_log->log($message, Zend_Log::DEBUG);
    }

    protected static $_varExport_Stack  = array();

    /** @brief  Generate a print_r of the given variable, replacing all '\n'
     *          with ', '
     *  @param  var     The variable to dump.
     *
     *  @return The string representation.
     */
    public static function varExport($var)
    {
        foreach (self::$_varExport_Stack as $item)
        {
            if ($var === $item)
                return ("*recursion*");
        }

        array_push(self::$_varExport_Stack, $var);

        if (is_object($var))
        {
            $str = get_class($var) .'[ ';

            if (method_exists($var, '__toString'))
                $str .= strval($var);
            else if (method_exists($var, 'toArray'))
                $str .= self::varExport($var->toArray());
            else
                $str .= 'object';

            $str .= ' ]';
        }
        else if (is_array($var))
        {
            $parts  = array();
            $keys   = array_keys($var);

            /* Are the first two keys of $var sequential integers?  If so,
             * ASSUME $var is a simple array with sequential integer keys.  If
             * NOT, treat $var as an object.
             */
            $intKey = (is_int($keys[0]) && is_int($keys[1]) &&
                        ($keys[1] == $keys[0] + 1));
            $open   = ($intKey == true ? '[ ' : '{ ');
            $close  = ($intKey == true ? ' ]' : ' }');

            foreach ($var as $key => $val)
            {
                if ($intKey === true)
                    $str = self::varExport($val);
                else
                    $str = $key .':'. self::varExport($val);

                array_push($parts, $str);
            }

            $str = $open . implode(', ', $parts) . $close;
        }
        else if (is_string($var))
        {
            $str = '"'. preg_replace('/"/', '\\"', strval($var)) .'"';
        }
        else if (is_null($var))
        {
            $str = 'null';
        }
        else if (is_bool($var))
        {
            $str = gettype($var) .'[ '. ($var ? 'true' : 'false') .' ]';
        }
        else
        {
            $str = gettype($var) .'[ '. strval($var) .' ]';
        }

        array_pop(self::$_varExport_Stack);
        return $str;




        $str = var_export($var, true);
        return preg_replace('/\s*$\s+/ms', '', $str);

        //return str_replace("\n", '', var_export($var, true));
        //return preg_replace('/\s*$\s+/ms', '', var_export($var, true));
        //return preg_replace('/\s*$\s+/ms', '', print_r($var, true));
    }

    /** @brief  Generate a backtrace without an overwhelming amount a detailed
     *          object data.
     *  @param  return      Return the backtrace as a string (false) or print
     *                      it out directly (true)   [ false ].
     *
     *  @return The backtrace as a string if 'return' is true.
     */
    public static function backtrace($return = false)
    {
        $bt = debug_backtrace();

        $ret = '';
        array_shift($bt);
        foreach ($bt as $item)
        {
            $str = self::_sprint_backtrace($item);
            if ($return)
                $ret .= $str;
            else
                echo $str;
        }

        if ($return)
            return ($ret);
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
                 *       Currently handled in application/Bootstrap.php
                 *
                 *       Do NOT set $_user to false.  We use this method
                 *       early (via Connexions_Model::_logActivity()) BEFORE
                 *       Bootstrap has completed.  If we set it to false here,
                 *       it won't be properly retrieved/cached when it finally
                 *       IS available.
                 */
                //self::$_user = false;
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
        if (self::$_request === null)
        {
            /* By default, retrieve the request associated with the front
             * controller.  This will only work if we're using controllers
             * (e.g. MVC).
             */
            self::setRequest(Zend_Controller_Front::getInstance()
                                                        ->getRequest());
        }

        return self::$_request;
    }

    /** @brief  Set the globally accessible request object.
     *  @param  request     The request object.
     */
    public static function setRequest($request)
    {
        self::$_request = $request;
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

    /** @brief  Return the current Configuration data.
     *
     *  @return The current Configuration data.
     */
    public static function getConfig($force = false)
    {
        if ( ($force === true) || (self::$_config === null))
        {
            self::$_config = Zend_Registry::get('config');
        }

        return self::$_config;
    }

    /** @brief  Retrive Json-RPC api information from our application
     *          configuration
     *          (application/configs/application.ini : api.jsonRpc)
     *
     */
    public static function getJsonRpcInfo()
    {
        try
        {
            //$jsonRpc = Zend_Registry::get('config')
            $jsonRpc = self::getConfig()
                                ->get('api')
                                    ->get('jsonRpc')
                                        ->toArray();
        }
        catch (Exception $e)
        {
            throw new Exception("Missing application configuration for "
                                    . "'api.jsonRpc'");
        }

        return $jsonRpc;
    }

    /** @brief  Retrive a named external url from our application configuration
     *          (application/configs/application.ini : externalUrls.%name%)
     *
     *  @return The url string (null if not found).
     */
    public static function getExternalUrl($name)
    {
        /*
        Connexions::log("Connexions::getExternalUrl(): name[ %s ]",
                        $name);
        // */

        if (self::$_externalUrls === null)
        {
            self::$_externalUrls = self::getConfig()
                                    ->get('externalUrls')
                                        ->toArray();
        }

        $url = (isset(self::$_externalUrls[$name])
                    ? self::$_externalUrls[$name]
                    : null);

        /*
        Connexions::log("Connexions::getExternalUrl(): name[ %s ] == [ %s ]",
                        $name, $url);
        // */

        return $url;
    }

    /** @brief  Given a Wikipedia term, return the appropriate URL.
     *  @param  term    The desired term.
     *
     *  @return The Wikipedia URL.
     */
    public static function wikipedia($term)
    {
        return str_replace('%term%', $term, self::getExternalUrl('wikipedia'));
    }

    /** @brief  Given a Wikipedia term, return the HTML of a link to the
     *          Wikipedia term.
     *  @param  term    The desired term.
     *  @param  title   The title to present [ $term ];
     *
     *  @return The HTML of a link to the Wikipedia term.
     */
    public static function wikipedia_a($term, $title = null)
    {
        $title = (empty($title) ? $term : $title);
        return  "<span class='external-link'>"
               . "<a class='wikipedia' "
               .     "href='". self::wikipedia($term) ."'>{$title}</a>"
               . "<span class='icon connexions_sprites link-wikipedia'></span>"
               ."</span>";
    }

    /** @brief  Given an RFC number, return the appropriate URL.
     *  @param  number  The desired rfc number.
     *
     *  @return The RFC URL.
     */
    public static function rfc($number)
    {
        list($number, $section) = explode('#', $number);

        $url = str_replace('%number%', $number, self::getExternalUrl('rfc'));
        if (! empty($section))  $url .= '#'. $section;

        return $url;
    }

    /** @brief  Given an RFC number, return the HTML of a link to the RFC.
     *  @param  number  The desired RFC number.
     *  @param  title   The title to present [ $term ];
     *
     *  @return The HTML of a link to the RFC.
     */
    public static function rfc_a($number, $title = null)
    {
        $title = (empty($title) ? "RFC {$number}" : $title);
        return  "<span class='external-link'>"
               . "<a class='rfc' "
               .     "href='". self::rfc($number) ."'>{$title}</a>"
               . "<span class='icon connexions_sprites link-rfc'></span>"
               ."</span>";
    }

    /** @brief  Given a site URL, apply any 'base' url prefix and return.
     *  @param  url     The site URL.
     *
     *  @return The full site URL with any 'base' prefix.
     */
    public static function url($url)
    {
        if (@is_string($url))
        {
            /* Special-case for avatar images from the original site
             * having a relative url beginning with 'images/avatar/'.
             */
            if (($url[0] == '/') || (substr($url, 0, 14) === 'images/avatar/'))
            {
                // Convert to a site-absolute URL
                // $front  =& Zend_Controller_Front::getInstance();
                // $baseUrl = $front->getBaseUrl();
                try
                {
                    //$baseUrl = Zend_Registry::get('config')
                    $baseUrl = self::getConfig()
                                        ->get('urls')
                                            ->get('base');
                }
                catch (Exception $e)
                {
                    $baseUrl = '';
                }

                if (strpos($url, $baseUrl) !== 0)
                {
                    if ($url[0] !== '/')
                    {
                        $url = '/'. $url;
                    }

                    $url = $baseUrl . $url;
                }

                /* Make sure we don't end up with something like '//' in this
                 * site-local URL (occurs when our base url is simply '/').
                 */
                $url = preg_replace('#/+#', '/', $url);
            }

        }
        else if (@is_array($url))
        {
            $router =& Zend_Controller_Front::getInstance()->getRouter();
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

    /** @brief  Given an HTTP URL, if it is absolute, return it, otherwise,
     *          ensure that it is a full site URL with any 'base' prefix.
     *  @param  url     The URL string.
     *
     *  @return The absolute HTTP URL OR full site URL.
     */
    public static function httpUrl($url)
    {
        if (! preg_match('#^https?://#', $url))
        {
            /* The url has no http[s] protocol so ASSUME it's a site URL that
             * should be made full.
             */
            $url = self::url($url);
        }

        return $url;
    }

    /** @brief  Retrieve or establish a named URL to Path mapping.
     *  @param  key     The name of the mapping.
     *  @param  entry   null for retrieval or an array of
     *                  {url: %url%, path: %path%} to set.
     *
     *  @return The named map entry.
     */
    public static function urlPathMap($key, $entry = null)
    {
        if (is_array($entry))
        {
            self::$_urlMap[ $key ] = $entry;
        }

        return self::$_urlMap[ $key ];
    }

    /** @brief  Given a site URL, convert it to a local, absolute file path.
     *  @param  url     The site URL.
     *
     *  @return The local, absolute file path.
     */
    public static function url2path($url)
    {
        $url       = self::url($url);
        $baseEntry = self::urlPathMap('base');

        $path = $baseEntry['path']
              . preg_replace('#^'. $baseEntry['url'] .'#', '', $url);
        
        /*
        Connexions::log("Connexions::url2path( %s ): "
                        .   "baseUrl[ %s ], basePath[ %s ] == [ %s ]",
                        $url, $baseEntry['url'], $baseEntry['path'], $path);
        // */

        return $path;
    }

    /** @brief  Given a URL string, explode it into it's component pieces.
     *  @param  url     The URL string.
     *
     *  @return An associative array of component pieces (false on error):
     *              scheme, host, port, user, pass, path, query, fragment,
     *              queryArray
     */
    public static function urlExplode( $url )
    {
        $pieces = parse_url($url);
        if ($pieces === false)
        {
            return false;
        }

        $pieces['query'] = (empty($pieces['query'])
                                ? array()
                                : parse_str( $pieces['query'] ));

        return $pieces;
    }

    /** @brief  Given URL pieces generated by urlExplode(), return the
     *          corresponding URL string.
     *  @param  url     The URL pieces generated by urlExplode().
     *
     *  @return A URL string.
     */
    public static function urlImplode( $url )
    {
        $str = '';
        if (! empty($url['scheme']))
        {
            $str .= $url['scheme'] .'://';

            if ((! empty($url['user'])) && (isset($url['pass'])))
            {
                $str .= $url['user'] .':'. $url['pass'] .'@';
            }

            if (! empty($url['host']))
            {
                $str .= $url['host'];

                if (! empty($url['port']))
                {
                    $str .= ':'. $url['port'];
                }
            }
        }

        if (! empty($url['path']))
        {
            $str .= $url['path'];
        }

        if (! empty($url['query']))
        {
            $sep = '?';
            foreach ($url['query'] as $key => $val)
            {
                $str .= $sep . $key .'='. urlencode($val);
                $sep  = '&';
            }
        }

        if (! empty($url['fragment']))
        {
            $str .= '#'. $url['fragment'];
        }

        return $str;
    }

    /** @brief  Retrieve the maximum upload size.
     *
     *  @return The maximum upload size (in bytes).
     */
    public static function getMaxUploadSize()
    {
        $sizes = array( ini_get('upload_max_filesize'),
                        ini_get('post_max_size'),
        );

        $maxSize = 0;
        foreach ($sizes as $size)
        {
            if (preg_match('/([0-9]+)\s*([KMGTB])?/', $size, $parts))
            {
                $size = (int)$parts[1];

                switch (strtolower($parts[2]))
                {
                case 'k':   // kilo-bytes
                    $size *= 1024;
                    break;

                case 'm':   // mega-bytes
                    $size *= 1024 * 1024;
                    break;

                case 'g':   // giga-bytes
                    $size *= 1024 * 1024 * 1024;
                    break;

                case 't':   // tera-bytes
                    $size *= 1024 * 1024 * 1024 * 1024;
                    break;
                }
            }

            if ($size > $maxSize)   $maxSize = $size;
        }

        return $maxSize;
    }

    /** @brief  Given a previous depth and current depth, close all tags until 
     *          we're back to the current tag depth.
     *  @param  indent          The indent for the current depth;
     *  @param  prevDepth       The previous depth;
     *  @param  depth           The current depth;
     *  @param  closeTags       The tag(s) required to close a level;
     */
    public static function closeTags($indent,
                                     $prevDepth,
                                     $depth         = 0,
                                     $closeTags     = '</div>')
    {
        $ind = $indent . str_repeat(' ', $idex);

        // Close tags until we're at current depth
        for ($idex = $prevDepth; $idex > $depth; $idex--)
        {
            /*
            printf("<!-- closeTags: prevDepth[ %d ], depth[ %d ], "
                   .               "idex[ %d ] -->\n",
                    $prevDepth, $depth, $idex);
            // */

            echo $ind, $closeTags, "\n";
        }
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
     *      html    - include '< />' surrounding the provided element.
     *      url     - the selector will be passed to Connexions::url() to
     *                generate a full site URL with any 'base' prefix.
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

                case 'url':
                    $replacement = self::url($selector);
                    break;

                default:
                    $replacement = '!'. $nameSpace .'.'. $selector .'!';
                    break;
                }

                $pat = preg_quote($nameSpace .'.'. $selector, '/');
                $str = preg_replace('/%(25)?'. $pat .'%(25)?/i',
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

        // Generate a normalized URI: See RFC 3986, section 6
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

    /** @brief  Generate a "summary" of provided text.  This simply shortens
     *          the text to the last full word before the 'maxChars'th
     *          character.
     *  @param  text        The text to "summarieze";
     *  @param  maxChars    The maximum number of characters [ 160 ];
     *
     *  @return The summary string.
     */
    public static function getSummary($text,
                                      $maxChars = 160)
    {
        $summary = html_entity_decode($text, ENT_QUOTES);
        if (strlen($summary) > $maxChars)
        {
            // Shorten to no more than 'maxChars' characters
            $summary = substr($summary, 0, $maxChars);
            $summary = substr($summary, 0, strrpos($summary, " "));

            // Trim any white-space or punctuation from the end
            $summary = rtrim($summary, " \t\n\r.!?:;,-");

            $summary .= '...';
        }
        $summary = htmlspecialchars($summary, ENT_QUOTES);

        /*
        Connexions::log("getSummary(): text[ %s ], max[ %d ] == [ %s ]",
                        $text, $maxChars, $summary);
        // */

        return $summary;
    }

    /** @brief  Given a URL string, convert it to HTML text that can be wrapped
     *          by the browser where needed.
     *  @param  url     The URL string.
     *
     *  @return A string of HTML representing the URL.
     */
    public static function wrappableUrl($url)
    {
        /* Since a url is typically a LARGE, contiguous string, url decode it,
         * and add white-space around every non-word character [/.,&=?_-].
         */
        $url = preg_replace('/\s*(\W|_)\s*/', ' $1 ', urldecode($url));

        // Remove white-space from the schema
        $url = preg_replace('/^(\S+)\s+:\s*\/\s+\//', '$1://', $url);

        // Make the url HTML-safe
        $url = htmlspecialchars($url);

        /* To make a nicer presentation, replace ALL white-space with a
         * Zero Width Space (&$8203;).  This will allow line-wrapping without
         * visual white-space clutter.
         *
         * We could also use a Hair Space (&#8202;) if we want to see that the
         * white-space exists.
         */
        $url = preg_replace('/\s+/', '&#8203;', $url);

        return $url;
    }

    /** @brief  Given a date/time string, convert it to a Unix timestamp.
     *  @param  str     The date/time string.
     *
     *  This is used for RSS feed generation as well as bookmark exports.
     *
     *  @return The Unix timestamp.
     */
    public static function date2time($str)
    {
        $res    = 0;
        try
        {
            $date = new Zend_Date($str);
            $res = $date->getTimestamp();
        }
        catch (Zend_Date_Exception $e)
        {
            Connexions::log("Connexions::date2time(): str[ %s ], Exception: %s",
                            $str, $e->getMessage());
        }

        return $res;
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

    /** @brief  Convert a value to boolean.
     *  @param  val     The value to convert.
     *
     *  Conversion from     to:
     *              true,  'true',  'yes', (int)!= 0, (float)!= 0.0   => true
     *              false, 'false', 'no',  (int)== 0, (float)== 0.0   => false
     *
     *  @return The boolean value.
     */
    public static function to_bool($val)
    {
        /*
        Connexions::log("Connexions::to_bool( %s ): type[ %s ]",
                        $val, gettype($val));
        // */

        switch (gettype($val))
        {
        case 'boolean':
            // Nothing to do -- it's already boolean
            break;

        case 'integer':
            $val = ($val === 0 ? false : true);
            break;

        case 'double':
            $val = (round($val) === 0 ? false : true);
            break;

        case 'NULL':
            $val = false;
            break;

        case 'string':
            switch (strtolower($val))
            {
            case 'false':
            case 'no':
            case '0':
            case '0.0':
                $val = false;
                break;

            default:
                $val = true;
                break;
            }
            break;

        default:
            // Evertyhing else should be fine with a simple cast
            $val = (bool)$val;
        }

        /*
        Connexions::log("Connexions::to_bool(): result[ %s ]",
                        Connexions::varExport($val));
        // */

        return $val;
    }

    /*************************************************************************
     * Browser detection
     *
     * More flexible/performant than PHP's get_browser(), which, according to 
     * documentation:
     *      http://php.net/manual/en/function.get-browser.php
     *
     * and testing requires a detailed initialization file, e.g.:
     *      http://browsers.garykeith.com/stream.asp?PHP_BrowsCapINI)
     */

    protected static $_browsers = array(
        // shorthand-id => user agent regex a capture for version number.

        /* Specific browsers
        'saf'   => 'safari/([0-9\\.]+)',
        'ch'    => 'chrome/([0-9\\.]+)',
        'ff'    => 'firefox/([0-9\\.]+)',
        'nav'   => 'navigator(?:[0-9]+)?/([0-9\\.]+)',
        */

        // Browser classes
        'ie'    => 'msie ([0-9\\.]+)',
        'wk'    => 'webkit/([0-9\\.]+)',
        'gk'    => 'gecko/([0-9\\.]+)',

        'op'    => 'opera[ /]([0-9\\.]+)',
        'konq'  => 'konqueror/([0-9\\.]+)',

        // Nearly all call themselves 'Mozilla/*'
        'moz'   => 'mozilla/([0-9\\.]+)',

        'other' => null,
    );

    protected static $_browser  = null;

    /** @brief  Retrieve the name and version of the current user agent.
     *
     *  @return An array containing the name and version.
     */
    public static function getBrowser()
    {
        if (self::$_browser !== null)
            return self::$_browser;

        $data      = array('id'      => null,
                           'version' => null,
                           'major'   => null,
                           'minor'   => null,
                           'extra'   => null,
        );

        $userAgent = ( isset( $_SERVER['HTTP_USER_AGENT'] )
                        ? strtolower( $_SERVER['HTTP_USER_AGENT'] )
                        : '');

        foreach (self::$_browsers as $id => $idPattern)
        {
            if (preg_match('#'. $idPattern .'#i', $userAgent, $match))
            {
                /* We have identified the browser type, and should have a
                 * version number in $match[1];
                 */
                $data['id']      = $id;
                $data['version'] = $match[1];

                $version = explode('.', $match[1]);

                $data['major'] = array_shift($version);
                $data['minor'] = array_shift($version);
                $data['extra'] = implode('.', $version);
                break;
            }
        }

        self::$_browser = (object)$data;

        return self::$_browser;
    }

    /*************************************************************************
     * Form helpers
     *
     */

    /** @brief  Generate a namespaced form variable name depending on the
     *          given namespace.
     *  @param  namespace   The namespace;
     *  @param  name        The form variable name;
     *
     *  @return A properly namespaced form variable name.
     */
    public static function nsParamName($namespace, $name)
    {
        $res  = $name;
        if ( ! empty($namespace))
        {
            $res = $namespace . ucfirst($name);
        }

        return $res;
    }

    /*************************************************************************
     * PKI helpers
     *
     */

    /** @brief  If the provided PKI information is NOT empty and has been
     *          marked verified, pull user identification information from it.
     *  @param  pki     The PKI information:
     *                      { verified: true/false,
     *                        issuer:   The issuer's  Distinguished Name,
     *                        subject:  The subject's Distinguished Name
     *                      }
     *
     *  :NOTE: Typically, 'pki' will have been generated by _commonConnection()
     *         within Bootstrap.php.  This bootstrap method normalizes
     *         connection information and makes it available application-wide
     *         via Zend_Registry::get('connectionInfo').  Any verified PKI
     *         information will be represented by the non-null value of 'pki'
     *         within this connecion information.
     *
     *  @return pkiInfo (or null if no valid, verified PKI information).
     */
    public static function parsePki($pki)
    {
        if ( ! ($pki && ($pki['verified'] === true)) )
        {
            /*
            Connexions::log("Connexions::parsePki(): "
                            . "NO verified PKI information");
            // */

            return null;
        }

        // Attempt to parse the PKI subject
        $pkiInfo = array(
            'subject'   => $pki['subject'],
            'name'      => null,
            'email'     => null,
            'fullName'  => null,
        );

        $needUserName = true;
        if (preg_match('/email(?:Address)=([^\/]+)/i',
                       $pki['subject'], $match))
        {
            /*
            Connexions::log("Connexions::parsePki(): "
                            . "subject[ %s ], email match[ %s ]",
                            $pki['subject'],
                            Connexions::varExport($match));
            // */
    
            $pkiInfo['email'] = $match[1];
            if (empty($pkiInfo['name']))
            {
                $pkiInfo['name'] = $pkiInfo['email'];
            }
        }
    
        if (preg_match('/CN=([^\/]+)/i',
                            $pki['subject'], $match))
        {
            /*
            Connexions::log("Connexions::parsePki(): "
                            . "subject[ %s ], CN match[ %s ]",
                            $pki['subject'],
                            Connexions::varExport($match));
            // */
    
            $pkiInfo['fullName'] = $match[1];
            if (empty($pkiInfo['name']))
            {
                $pkiInfo['name'] = $pkiInfo['fullName'];
            }
        }
    
        /* Apply the Model_User filter to the constructed user name to ensure
         * that it's valid.
         */
        $filter = Zend_Registry::get('user')->getFilter();
        $filter->setData( array('name'      => $pkiInfo['name'],
                                'fullName'  => $pkiInfo['fullName'],
                                'email'     => $pkiInfo['email']) );
        $pkiInfo['name']     = $filter->getUnescaped('name');
        $pkiInfo['fullName'] = $filter->getUnescaped('fullName');
        $pkiInfo['email']    = $filter->getUnescaped('email');
    
        /* See if 'name' identifies an existing user
        $user = $this->service('User')->get(array('name' => $pkiInfo['name']));
        $pkiInfo['newUser'] = !($user->isBacked());
        // */

        /*
        Connexions::log("Connexions::parsePki(): "
                        . "Sanitized certificate data: [ %s ]",
                        Connexions::varExport($pkiInfo));
        // */

        return $pkiInfo;
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Generate a string representation of a backtrace item.
     *  @param  item    The backtrace item.
     *
     *  @return A string representation.
     */
    protected static function _sprint_backtrace($item)
    {
        $str = sprintf ("%s line %d: %s%s(",
                        $item['file'], $item['line'],
                        (! empty($item['class'])
                            ? $item['class'] .'::'
                            : ''),
                        $item['function']);

        foreach ($item['args'] as $idex => $arg)
        {
            switch (gettype($arg))
            {
            case 'boolean':
                $type = 'boolean';
                $val  = ($arg ? 'true' : 'false');
                break;
            case 'object':
                $type = get_class($arg);
                if (! method_exists($arg, '__toString'))
                {
                    $val = 'Object';
                    break;
                }
                // fall through

            default:
                $val = strval($arg);
                break;
            }

            $str .= sprintf ("%s%s[%s]",
                             ($idex > 0 ? ', ' : ''),
                             $type, $val);
        }
        $str .= ")\n";

        return $str;
    }

}

/****************************************************************************
 * General helper functions.
 *
 */

