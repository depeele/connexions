<?php
/** @file   Plugin framework.
 *
 *  Many ideas taken from the Picora PHP micro framework:
 *      http://livepipe.net/projects/picora/
 */

if (! is_object($gPluginDispatcher))
{
    global  $gPluginDispatcher;
    global  $gPluginController;

    $gPluginDispatcher = new PluginDispatcher();
    $gPluginController = new PluginController();
}

/** @brief  The dispatcher is responsible for mapping urls / routes to
 *          Controller methods.
 *
 *  Each route that has the same number of directory components as the current
 *  requested url is tried.  The first method that returns a response that is
 *  not false/null will be returned via the Dispatcher::dispatch() method.
 *
 *  A route string can be a literal url (e.g. '/settings/general') or may
 *  contain variables (e.g. '/$user/$tags').  Since these route strings can
 *  contain '$', they must alway be enclosed by single quotes.  The variable in
 *  the route string are collected in the order they appear and are passed as
 *  the arguments to the corresponding controller method.
 *
 *  For example:
 *      PluginDispather::addRoute(array(
 *          '/'                 => array('View',     'user'),
 *          '/$user'            => array('View',     'user'),
 *          '/$user/$tags'      => array('View',     'tags'),
 *          '/$tags'            => array('View',     'tags'),
 *          '/watchlist'        => array('Watchlist','user'),
 *          '/watchlist/$user'  => array('Watchlist','user'),
 *      ));
 *
 *      /watchlist        => Watchlist::user()
 *      /watchlist/user1  => Watchlist::user('user1')
 *
 */
class PluginDispatcher
{
    var $classes    = array();  // Loaded classes
    var $routes     = array();  // Current routes
    var $status     = array(    // Status information
            'request_url'       => '',
            'current_route'     => '',
            'current_args'      => array(),
            'current_plugin'    => '',
            'current_method'    => '',
            'current_params'    => array(),
            'base_url'          => '',
            'app_dir'           => ''
        );

    /************************************************************************
     * Protected methods.
     *
     */

    /** @brief  Attempt to load a class.
     *  @param  className   The name of the class.
     *
     *  @return true (success), false (failure).
     */
    function _load($className)
    {
        $funcId = 'PluginDispatcher::load';
        if (isset($this->classes[$className]))
            return true;

        $paths = array('_className_.php',
                       '_className_/index.php',
                       'plugin/_className_.php');
        foreach ($paths as $idex => $path)
        {
            $try = preg_replace('#_className_#', strtolower($className), $path);

            //printf ("%s: try[%s]...", $funcId, $try);
            if (file_exists($try))
            {
                //printf ("SUCCESS!<br />\n");
                include_once($try);

                $this->classes[$className] = $try;
                break;
            }
            //printf ("failed<br />\n");
        }

        return (isset($this->classes[$className]));
    }

    /** @brief  Call a mapped class/method.
     *  @param  classMethod     An array of (className, methodName).
     *  @param  params          An associative array of parameters to
     *                          make available to the class instance.
     *  @param  args            Arguments to pass to the before() and after()
     *                          methods.
     *
     *  @return The response returned by the invoked method.
     */
    function _call($classMethod, $params, $args = false)
    {
        $funcId = 'PluginDispatcher::call';
        $args = ($args ? $args : array());

        /*printf ("%s: classMethod{%s}<br />\n",
                $funcId, var_export($classMethod,true));*/
        if (! $this->_load($classMethod[0]))
        {
            printf ("<!-- %s: Cannot load PHP for class '%s' -->\n",
                    $funcId, $classMethod[0]);
            return false;
        }

        // If we've loaded a valid class then 'classMethod[0]' should be an
        // instance of PluginController.

        $inst = new $classMethod[0];
        $inst->params = $params;

        // First, invoke the before method.
        $inst->before($classMethod[1], $args);

        $resp = @call_user_func_array(array($inst, $classMethod[1]), $args);
        if ($resp)
        {
            // On success, invoke the after method.
            $cbResp = $inst->after($classMethod[1], $args, $resp);
            if (! is_null($cbResp))
                $resp = $cbResp;
        }

        return ($resp);
    }

    /** @brief  Given a class/method, locate the matching route.
     *  @param  className   The class name to match (or an array of (className,
     *                      methodName).
     *  @param  methodName  If 'className' is a string, this is the method name
     *                      to match.
     *  @return The route string that matches (false on failure).
     */
    function _getRoute($className, $methodName = false)
    {
        if (is_array($className))
        {
            $methodName = $className[1];
            $className  = $className[0];
        }

        foreach ($this->routes as $route => $classMethod)
        {
            if (($className == $classMethod[0]) &&
                ($methodName == $classMethod[1]))
            {
                return ($route);
            }
        }

        return (false);
    }

    /** @brief  Try a route.
     *  @param  route       The route to attempt.
     *  @param  classMethod The class/method to attempt.
     *  @param  args        Arguments to pass.
     *
     *  @return The response returned by the invoked method.
     */
    function _tryRoute($route, $classMethod, $args = array())
    {
        $this->status['current_route']  = $route;
        $this->status['current_plugin'] = $classMethod[0];
        $this->status['current_method'] = $classMethod[1];
        $this->status['current_args']   = $args;

        return $this->_call($classMethod,
                            $this->status['current_params'],
                            $args);
    }

    /************************************************************************
     * Public methods.
     *
     */

    /** @brief  Add a new route to a specific PluginController/method.
     *  @param  route               The URL to route or an associative array
     *                              of ('route'=> controllerMethod, ...).
     *  @param  controllerMethod    An array of (controllerName, methodName)
     */
    function addRoute($route, $controllerMethod = false)
    {
        if (is_array($route))
        {
            foreach ($route as $realRoute => $controllerMethod)
            {
                $this->addRoute($realRoute, $controllerMethod);
            }
        }
        else
        {
            $this->routes[$route] = $controllerMethod;
        }
    }

    /** @brief  Given a class/method, return the full URL used to invoke it.
     *  @param  classMethod     The class/method.
     *  @param  args            Current arguments.
     *  @param  includeBaseUrl  Should the base url be include?
     *
     *  @return A URL (false on failure).
     */
    function getUrl($classMethod, $args = false, $includeBaseUrl = true)
    {
        if (is_string($classMethod))
            $classMethod = array($this->status['current_plugin'], $classMethod);

        $route = $this->_getRoute($classMethod);

        preg_match_all('/(?<!\\\\)(\$([^\/0-9][\w\_\-]*))/e', $route, $matches);
        foreach($matches[2] as $match)
        {
            if (($match      ==  'id')  &&
                ($args['id'] === false) &&
                isset($variables['id']))
            {
                $route = str_replace('$id', 'new', $route);
            }
            else if (isset($args[$match]) && ($args[$match] !== null))
            {
                $route = str_replace('$'.$match, $args[$match], $route);
            }
            else if (is_object($args) &&
                     method_exists($varaibles,
                                   'get'.str_replace(' ','',
                                       ucwords(str_replace('_',' ', $match)))))
            {
                $route = str_replace('$'.$match,
                                     $args->{
                                   'get'.str_replace(' ','',
                                       ucwords(str_replace('_',' ', $match)))}(),
                                     $route);
            }

            if ($route && !preg_match('/\$[^\/]/', $route))
            {
                if ($includeBaseUrl)
                    $route = substr($this->status['base_url'], 0, -1).$route;
            }
            else
            {
                $route = false;
            }

            return ($route);
        }
    }

    /** @brief  Attempt to dispatch to a plugin based on our current routes.
     *  @param  appDir      The directory the application is running in.
     *  @param  baseUrl     The base URL that should be used.
     *  @param  reqUrl      The URL being requested.
     *
     *  @return The response from a controller.
     */
    function dispatch($appDir, $baseUrl, $reqUrl)
    {
        $funcId = 'PluginDispatcher::dispatch';

        $this->status['current_params'] = array_merge($_POST, $_GET);
        unset($this->status['current_params']['__route__']);

        $this->status['app_dir']     = $appDir;
        $this->status['base_url']    = $baseUrl;
        $this->status['request_url'] = $reqUrl;

        $len = strlen($reqUrl);
        if ( ($len > 1) && substr($reqUrl, -1, 1) == '/')
        {
            // Strip the trailing '/'
            $reqUrl = substr($reqUrl, 0, $len - 1);
        }

        foreach ($this->routes as $route => $classMethod)
        {
            /*printf ("%s: reqUrl[%s], route[%s], classMethod{%s]<br />\n",
                    $funcId, $reqUrl, $route, var_export($classMethod,true));*/

            if ( ($reqUrl == $route) &&
                 ($resp = $this->_tryRoute($route, $classMethod)) )
            {
                return ($resp);
            }

            if (preg_replace('{([^/]+)}', '*', $route) ==
                preg_replace('{([^/]+)}', '*', $reqUrl))
            {
                preg_match_all('{([^/]+)?}', $route,  $routeParts);
                preg_match_all('{([^/]+)?}', $reqUrl, $urlParts);

                /*printf ("%s: routeParts{%s}, urlParts{%s}<br />\n",
                        $funcId,
                        var_export($routeParts, true),
                        var_export($urlParts, true));*/

                $args = array();
                foreach ($urlParts[0] as $key => $reqUrlPart)
                {
                    if ($reqUrlPart == '')
                        continue;

                    /*printf ("%s: %s = %s: routePart: %s<br />\n",
                            $funcId, $key, $reqUrlPart,
                            $routeParts[0][$key]);*/
                    if (strpos($routeParts[0][$key], '$') !== false)
                        $args[] = $reqUrlPart;
                    else if ($routeParts[0][$key] != $reqUrlPart)
                        continue 2;
                }
                if ($resp = $this->_tryRoute($route, $classMethod, $args))
                    return ($resp);
            }
        }

        // No handler for the requested url
        printf ("<!-- No handler for '%s' -->\n", $reqUrl);
    }

    /** @brief  Return a value from the dispatcher status.
     *  @param  key     The desired value (null for all):
     *                      - request_url
     *                      - current_route
     *                      - current_args
     *                      - current_plugin
     *                      - current_method
     *                      - current_params
     *                      - base_url
     *                      - app_dir
     *
     *  @return The value (false on failure).
     */
    function getStatus($key = false)
    {
        $res = false;

        if ($key !== false)
        {
            if (isset($this->status[$key]))
                $res = $this->status[$key];
        }
        else
        {
            $res = $this->status;
        }

        return ($res);
    }
}

/** @brief  This is the parent of any plugin that will actually perform the
 *          desired logic.
 *
 *  In the PluginDispatcher, you can add routes that will map specific url(s)
 *  to a controller (subclassed from PluginController) and methos.  Each method
 *  can then:
 *      - return a response string      (success)
 *      - redirect to another method    (success)
 *      - return null                   (failure, try another controller).
 */
class PluginController
{
    /** @brief  Given a set of comma separated parameters, parse them
     *          into an associative array.
     *  @param  str     The parameter string.
     *
     *  @return An associtive array.
     */
    function parseParams($str)
    {
        $params = array();
        if (! empty($str))
        {
            // parse the parameters into an associative array
            $nameVal = preg_split('/\s*,\s*/', $str);
            foreach ($nameVal as $idex => $val)
            {
                $tuple = preg_split('/\s*=\s*/', $val);
                $params[$tuple[0]] = $tuple[1];
            }
        }

        return ($params);
    }

    /** @brief  Called for any access that is attempted to an
     *          unknown/unimplemented action.
     *  @param  params  An array of provided parameters.
     *
     *  Parameters:
     *      params      Provided parameters, including the specified api and
     *                  call.
     */
    function error($params)
    {
        echo "
    <div style='margin:1em 0 2em 3em;'>
     <span style='color:#f00; font-weight:bold;'>*** ERROR:</span> ";
    
        if (is_string($params))
            $params['error_message'] = $params;

        if (! empty($params['error_message']))
        {
            echo $params['error_message'];
        }
    
        echo "
    </div>";
    }

    /** @brief  Invoked BEFORE any other controller method.
     *  @param  method      The name of the method that will be invoked.
     *  @param  args        Arguments that will be passed to the method.
     *
     *  This method MAY be called multiple times while the dispatcher searches
     *  for a method that will return a response.
     *
     *  This may be overridden by the plugin.
     *
     *  @return ignored
     */
    function before($method, $args)
    {
    }

    /** @brief  Invoked AFTER a successful method call.
     *  @param  method      The name of the method that was invoked.
     *  @param  args        Arguments that were passed to the method.
     *  @param  resp        The response returned by the method call.
     *
     *  This method MAY be called multiple times while the dispatcher searches
     *  for a method that will return a response.
     *
     *  This may be overridden by the plugin.
     *
     *  @return Return null to leave 'resp' untouched, otherwise the return
     *          value will become the response.
     */
    function after($method, $args, $resp)
    {
        return null;
    }
}

?>
