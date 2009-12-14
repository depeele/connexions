<?php
/** @file
 *
 *  A connexions router to handle the strange routing rules:
 *      /:owner         [/:tags]                Owner bookmarks
 *
 *      /network         /:owner    [/:tags]    Owner network
 *      /tags            /:owner    [/:tags]    Owner tags
 *      /subscriptions   /:owner    [/:tags]    Owner subscriptions
 *      /inbox           /:owner    [/:tags]    Owner inbox
 *
 *      /tag             /:tags                 Item tags
 *      /url             /:url                  Lookup url
 *      /people                                 People list
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
 */

class Connexions_Controller_Route
                extends Zend_Controller_Router_Route_Abstract
{
    protected $_default         = ':owner'; // The default '_routes' entry
    protected $_currentRoute    = null;
    protected $_routes          = array(
        // top/controller         sub-levels/named parameters
        ':owner'        => array(':tags'    => false),
        'network'       => array(':owner'   => array(
                                    ':tags'    => false)
                           ),
        'tags'          => array(':owner'   => array(
                                    ':tags'    => false)
                           ),
        'subscriptions' => array(':owner'   => array(
                                    ':tags'    => false)
                           ),

        'inbox'         => array(':owner'   => array(
                                    ':tags'    => false)
                           ),
  
        'tag'           => array(':tags'    => false),

        'url'           => array(':url'     => false),
        'people'        => array(),
  
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
        'auth'          => array('signin'   => false,
                                 'signout'  => false,
                                 'register' => false)
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
        if ($path instanceof Zend_Controller_Request_Http)
            $path  = $path->getPathInfo();
        else
            $path   = urldecode($path);

        $parts = explode('/', strtolower(trim($path, '/')) );

echo "<!-- Connexions_Controller_Route::match:\n";
printf (" path[ %s ], parts[ %s ]\n", $path, implode(':', $parts));
        $root = $parts[0];
        if ($root[0] == '%')
        {
            $root = Connexions::replaceables($root);
        }

        if ( @isset($this->_routes[$root]) )
        {
            $routeKey   = $root;
            $controller = $routeKey;
            $action     = 'index';
        }
        else
        {
            $routeKey   = $this->_default;
            $controller = 'index';
            $action     = $root;
        }

printf (" Root[ %s ], routeKey[ %s ], controller[ %s ], action[ %s ]\n",
        $root, $routeKey, $controller, $action);

        if ($partial)
            $this->setMatchedPath($root);

        $route  =& $this->_routes[$routeKey];
        $idex   =  1;
        $nParts =  count($parts);
        $params =  array('controller' => $controller,
                         'action'     => $action);
        while ($route && ($idex < $nParts))
        {
            if (! @is_array($route))
                break;

            foreach ($route as $key => $val)
            {
printf (" Route: key[ %s ], part#%d[ %s ]\n", $key, $idex, $parts[$idex]);

                if ($key[0] == ':')
                {
                    if ($name == ':owner')
                    {
                        $parts[$idex] = Connexions::replaceables('%user.name');
                    }

                    $name          = substr($key, 1);
                    $params[$name] = $parts[$idex];
                    $idex++;

                    $route =& $val;
                    break;
                }
                else if ($key == $parts[$idex])
                {
                    $params[$name] = $parts[$idex];
                    $idex++;

                    $route =& $val;
                    break;
                }

                // ERROR -- mismatch
                echo "\n ERROR -->\n";
                return false;
            }
        }

echo " Params:\n";
print_r($params);
echo "\n -->\n";

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
echo "\n<!-- Connexions_Controller_Route:: assemble:\n";
echo " data: "; print_r($data);
echo " currentRoute: "; print_r($this->_currentRoute);

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

printf (" url[ %s ]\n", $url);
echo "\n -->\n";
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
