<?php
/** @file
 *
 *  A connexions router to handle the strange routing rules:
 *      /                                       All bookmarks
 *      /:owner          [/:tags]               Bookmarks of the given owner,
 *                                              possibly limited by a set of
 *                                              tags
 *      /tagged          [/:tags]               Bookmarks of any owner,
 *                                              possibly limited by a set of
 *                                              tags
 *      /scopeAutoComplete                      Auto-completion callback for
 *                                              scope entry related to user 
 *                                              item presentation.
 *
 *      /tags            [/:owners]             Tags, possibly limited by a set
 *                                              of owner(s).
 *
 *      /network         /:owner    [/:tags]    Owner network
 *
 *      /subscriptions   /:owner    [/:tags]    Owner subscriptions
 *      /inbox           /:owner    [/:tags]    Owner inbox
 *
 *      /url             /:url      [/:tags]    Lookup url
 *      /people          [/:tags]               People list
 *
 *      /settings       [/:type     [/:cmd]]    Viewer settings
 *
 *      /help           [/*]                    Help
 *
 *      /api            [/:cmd      [/:subCmd   [/:params]]]
 *                                              RESTful API (or JsonRPC)
 *
 *      /post           [/:params]              Post a new bookmark
 *
 *      /search         [/:context  [/:terms]]  Search
 *
 *      /auth           /signIn
 *                      /signOut
 *                      /register
 *                      /checkUser
 */

class Connexions_Controller_Route
                extends Zend_Controller_Router_Route_Abstract
{
    protected $_default         = '/';      // The default '_routes' entry
    protected $_currentRoute    = null;
    protected $_routes          = array(
        // top/controller         sub-levels/named parameters
        '/'             => array(':controller'  => 'index',
                                 ':action'      => 'index',
                                 'scopeAutoComplete'    => false,
                                 ':owner'       => array(
                                    ':tags'    => false)
                           ),
        'tagged'        => array(':controller'  => 'index',
                                 ':action'      => 'index',
                                 ':owner'       => '*',
                                 ':tags'        => false
                           ),
        'tags'          => array(':owners'  => false),

        'network'       => array(':owner'   => array(
                                    ':tags'    => false)
                           ),
        'subscriptions' => array(':owner'   => array(
                                    ':tags'    => false)
                           ),

        'inbox'         => array(':owner'   => array(
                                    ':tags'    => false)
                           ),
  
        'url'           => array(':url'     => array(
                                    ':tags'    => false)
                           ),
        'people'        => array(':tags'    => false),
  
        'settings'      => array(':type'    => array(
                                    ':cmd'     => false)
                           ),
  
        'help'          => array(':topic'   => false),
  
        'api'           => array(':cmd'     => array(
                                    ':subCmd'  => array(
                                        ':params'  => false)
                                    )
                           ),
  
        'post'          => array(':params'  => false),
  
        'search'        => array(':context' => array(
                                    ':terms'   => false)
                           ),
        'auth'          => array('signIn'   => false,
                                 'signOut'  => false,
                                 'register' => false,
                                 'checkuser'=> false)
    );

    /** @brief  Retrieve an instance based upon configuration.
     *  @param  config      A Zend_Config instance.
     *
     *  @return A new Connexions_Controller_Route instance.
     */
    public static function getInstance(Zend_Config  $config)
    {
        return new self();
    }

    /** @brief  Matches a user submitted path with a defined route.
     *  @param  path        The path used to match against this route.
     *  @param  partial     Are partial matches permitted?
     *
     *  Assignes and returns an array of defaults on a successful match.
     *
     *  @return array | false
     */
    public function match($path, $partial = false)
    {
        $params = array();
        if ($path instanceof Zend_Controller_Request_Http)
        {
            $params = $path->getCookie();
            $path   = $path->getPathInfo();
        }
        else
        {
            $path = urldecode($path);
        }

        $parts = explode('/', strtolower(trim($path, '/')) );

        /*
        Connexions::log("Connexions_Controller_Route::match: "
                            . "path[ {$path} ], "
                            . "parts[ ". implode(':',$parts) ." ]");
        // */

        $root = $parts[0];
        if (substr($root,0,1) == '%')
        {
            $root = Connexions::replaceables($root);
        }

        if ( @isset($this->_routes[$root]) )
        {
            $routeKey   = $root;
            $controller = $routeKey;
            $action     = 'index';
            $idex       = 1;    // Skip the first part since we've already
                                // matched it.
        }
        else
        {
            $routeKey   = $this->_default;
            $controller = 'index';
            $action     = 'index';
            $idex       = 0;    // Still need to match this first part
        }

        if ($partial)
            $this->setMatchedPath($root);

        $route  =& $this->_routes[$routeKey];
        $nParts =  count($parts);

        //$params =  array('controller' => $controller,
        //                 'action'     => $action);
        $params['controller'] = $controller;
        $params['action']     = $action;

        /*
        Connexions::log("Connexions_Controller_Route::match: "
                            . "root[ {$root} ], "
                            . "routeKey[ {$routeKey} ], "
                            . "controller[ {$controller} ], "
                            . "action[ {$action} ], "
                            . "idex[ {$idex} ], "
                            . "nParts[ {$nParts} ]");
        // */

        while ($route && ($idex <= $nParts))
        {
            if (! @is_array($route))
                break;

            $newRoute = null;
            foreach ($route as $key => $val)
            {
                /*
                Connexions::log("Connexions_Controller_Route::match: Route: "
                                    . "key[ {$key} ], "
                                    . "part#{$idex}[ {$parts[$idex]} ]");
                // */

                if ($key[0] == ':')
                {
                    $name = substr($key, 1);
                    if (is_string($val))
                    {
                        // Pre-defined value
                        $params[$name] = $val;
                    }
                    else if (@isset($parts[$idex]))
                    {
                        // Pull the value from the URL
                        $params[$name] = $parts[$idex];
                        $idex++;

                        $newRoute = $val;
                        break;
                    }
                }
                else if ( (@isset($parts[$idex])) &&
                          (strcasecmp($key, $parts[$idex]) === 0) )
                {
                    // This is a non-parametric node that defines the action
                    $params['action'] = $key;
                    $idex++;

                    $newRoute = $val;
                    break;
                }
            }
            $route = $newRoute;
        }

        if (($idex < $nParts) && (! $partial))
        {
            /*
            Connexions::log("Connexions_Controller_Route::match: ERROR");
            // */

            // ERROR -- mismatch
            return false;
        }

        /*
        Connexions::log(
                sprintf("Connexions_Controller_Route::match: "
                            . "Params [ %s ]",
                         print_r($params, true)) );
        // */

        // Remember this current route
        $this->_currentRoute = array(
            'key'           => $routeKey,
            'root'          => $root,
            'params'        => $params
        );

        return $params;
    }

    /** @brief  Assemble a URL path defined by this route.
     *  @param  data    An array of name/value pairs used as parameters.
     *  @param  reset   Should all parameters be reset to defaults?
     *  @param  encode  Should we encode the URL parts on output?
     *  @param  partial Partial?
     *
     *  @return Resulting URL path string.
     */
    public function assemble($data      = array(),
                             $reset     = false,
                             $encode    = false)
    {
        /*
        echo "\n<!-- Connexions_Controller_Route:: assemble:\n";
        echo " data: "; print_r($data);
        echo " currentRoute: "; print_r($this->_currentRoute);
        // */

        $url = '';

        foreach ($data as $key => $val)
        {
            if ($val[0] == '%')
            {
                $val = Connexions::replaceables($val);
            }

            if ($key == 'controller')
            {
                if ($val != 'index')
                    $url .= $val .'/';
            }
            else if ($key == 'action')
            {
                if ($val != 'index')
                    $url .= $val .'/';
            }
            else
            {
                $url .= $val .'/';
            }
        }
        $url = rtrim($url, '/');

        /*
        printf (" url[ %s ]\n", $url);
        echo "\n -->\n";
        // */

        return $url;
    }

    /** @brief  Process a request and sets its controller and action.  If no
     *          route was possible, an exception is thrown.
     *  @param  request     The abstract request to process.
     *
     *  @throws Zend_Controller_Router_Exception
     *
     *  @return Zend_Controller_Request_Abstract | boolean
    public function route(Zend_Controller_Request_Abstract $request)
    {
        if (! $request instanceof Zend_Controller_Request_Http)
            throw new Zend_Controller_Router_Exception(
                            'Connexions_Controller_Router requires a '
                            .   'Zend_Controller_Request_Http-based request '
                            .   'object');

        $path  = explode('/', strtolower($request->getPathInfo()));

        if ( @isset($this->_routes[$path[0]]) )
        {
            $routeKey   = $path[0];
            $controller = $routeKey;
            $action     = 'index';
        }
        else
        {
            $routeKey   = $this->_default;
            $controller = 'index';
            $action     = 'index';
        }

        $request->setControllerName($controller);
        $request->setActionName($action);

        $route = $this->_routes[$routeKey];

        $idex   = 1;
        $nPath  = count($path);
        $params = array();
        foreach ($route as $key => $required)
        {
            if ($idex > $nPath)
            {
                if ($required === true)
                {
                    // Missing required parameter -- FAIL
                    return false;
                }

                // SUCCESS
                break;
            }

            $name = substr($key, 1);

            $params[$name] = $path[$idex];
            $request->setParam($name, $path[$idex]);
            $idex++;
        }

        // Remember this current route
        $this->_currentRoute = array(
            'key'           => $routeKey,
            'controller'    => $controller,
            'action'        => $action,
            'root'          => $path[0],
            'params'        => $params
        );

        return $request;
    }
     */

}
