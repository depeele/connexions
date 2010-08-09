<?php
/** @file
 *
 *  View helper to render the item scope -- root (e.g. user-name, main section
 *  name), sub-section name, scope items (e.g. tags), scope entry, item count
 *  all in HTML.
 *
 *  REQUIRES:
 *      application/view/scripts/itemScope.phtml
 */
class View_Helper_HtmlItemScope extends Zend_View_Helper_Abstract
{
    /** @brief  Set-able parameters . */
    protected   $_params    = array(
        'namespace'         => null,

        'inputLabel'        => 'Items', /* The text to present when the
                                         * input box is empty;
                                         */

        'inputName'         => 'items', // The form-name for the input box;

        'path'              => null,    /*  A simple array containing the names
                                         *  and urls of the path items to the
                                         *  current scope:
                                         *      array(root-name => root-url,
                                         *            item-name => item-url,
                                         *            ...)
                                         */

        'jsonRpc'           => null,    /*  Json-Rpc call data of the form:
                                         *      {version:   %RPC Version%,
                                         *       target:    % RPC URL %,
                                         *       transport: % POST | GET %,
                                         *       method:    % RPC method %,
                                         *       params:    {
                                         *          rpc parameter(s)
                                         *       }
                                         *      }
                                         */


        'items'             => null,    /* The set of items to be presented
                                         *  MUST implement either
                                         *      getTotalItemCount()
                                         *   OR getTotalCount()
                                         */
        'scope'             => null,    /* A Connexions_Model_Set of items that
                                         * define the current scope.  MUST
                                         * implement the 
                                         *      getSource() method
                                         */
    );

    protected       $_hiddenItems       = array();

    /** @brief  Variable Namespace/Prefix initialization indicators. */
    static protected $_initialized  = array();

    /** @brief  Construct a new Bookmarks helper.
     *  @param  config  A configuration array (see populate());
     */
    public function __construct(array $config = array())
    {
        //Connexions::log("View_Helper_HtmlItemScope::__construct()");
        if (! empty($config))
            $this->populate($config);
    }

    /** @brief  Given an array of configuration data, populate the parameter of
     *          this instance.
     *  @param  config  A configuration array that may include:
     *                      - namespace         The namespace to use for all
     *                                          cookies/parameters/settings
     *                                          [ '' ];
     *                      - inputLabel        The text to present when the
     *                                          input box is empty [ 'Items' ];
     *                      - inputName         The form-name for the input box
     *                                          [ 'items' ];
     *                      - path              A simple array containing the
     *                                          names and urls of the path
     *                                          items to the current scope:
     *                                            array(root-name => root-url,
     *                                                  item-name => item-url,
     *                                                  ...)
     *                      - jsonRpc           Json-Rpc call data of the form:
     *                                              {version:   % RPC version %,
     *                                               target:    % RPC URL %,
     *                                               transport: % POST | GET %,
     *                                               method:    % RPC method %,
     *                                               params:    {
     *                                                  rpc parameter(s)
     *                                               }
     *                                              }
     *
     *  @return $this for a fluent interface.
     */
    public function populate(array $config)
    {
        foreach ($config as $key => $value)
        {
            $this->__set($key, $value);
            //$this->_params[$key] = $value;
        }

        /*
        Connexions::log("View_Helper_HtmlItemScope::populate(): params[ %s ]",
                        print_r($this->_params, true));

        // */

        return $this;
    }

    /** @brief  Set the namespace, primarily for forms and cookies.
     *  @param  namespace   A string prefix.
     *  @param  force       Should initialization be forced even if the
     *                      auto-completion url has not yet been set? [ false ]
     *
     *  @return $this for a fluent interface.
     */
    public function setNamespace($namespace, $force = false)
    {
        /*
        Connexions::log("View_Helper_HtmlItemScope::"
                            .   "setNamespace( {$namespace} )");
        // */

        $this->_params['namespace'] = $namespace;

        if (! @isset(self::$_initialized[$namespace]))
        {
            if ( ($this->_params['jsonRpc'] === null) && ($force !== true) )
                /* Postpone initialization to see if the 'jsonRpc' is
                 * set before we render.
                 */
                return $this;

            $view   =& $this->view;

            /*
            $view->headLink()
                    ->appendStylesheet($view->baseUrl('css/autoSuggest.css'));
             */

            $jQuery = $view->jQuery();

            /* Now done in application/layouts/header.phtml
            else
                $jQuery->addJavascriptFile($baseUrl.'js/ui.input.min.js');
             */
            $jQuery->addOnLoad("$('.{$namespace}ItemScope').itemScope("
                                .    json_encode($this->_params) .');');

            self::$_initialized[$namespace] = true;
        }

        return $this;
    }

    /** @brief  Establish the set of items being presented within this scope.
     *  @param  items   The set of items to be presented
     *                  (MUST implement either
     *                      getTotalItemCount()
     *                   OR getTotalCount()).
     *
     *  @return $this for a fluent interface.
     */
    public function setItems($items)
    {
        $this->_params['items'] = $items;

        return $this;
    }

    /** @brief  Establish the set of items that define the scope.
     *  @param  scope   The Connexions_Model_Set instance containing the set of
     *                  items that define the scope.
     *
     *  @return $this for a fluent interface.
     */
    public function setScope(Connexions_Model_Set $scope)
    {
        $this->_params['scope'] = $scope;

        return $this;
    }

    /** @brief  Add a scope item that should be hidden.
     *  @param  str     The scope item (name).
     *
     *  @return $this for a fluent interface.
     */
    public function addHiddenItem($str)
    {
        array_push($this->_hiddenItems, $str);
    }

    /** @brief  Is the item with the given name hidden?
     *  @param  name    The item name.
     *
     *  @return true | false
     */
    public function isHiddenItem($name)
    {
        return (in_array($name, $this->_hiddenItems));
    }

    public function __set($key, $value)
    {
        $method = 'set'. ucfirst($key);
        if (method_exists($this, $method))
        {
            $this->{$method}($value);
        }
        else
        {
            $this->_params[$key] = $value;
        }
    }

    public function __get($key)
    {
        return (isset($this->_params[$key])
                    ? $this->_params[$key]
                    : null);
    }

    /** @brief  Render an HTML version of Item Scope.
     *  @param  config  A configuration array (see populate());
     *
     *  @return A configured instance of $this (if $config is provided),
     *          otherwise, the HTML representation of the Item Scope.
     */
    public function htmlItemScope(array $config = array())
    {
        if (! empty($config))
        {
            return $this->populate($config);
        }

        return $this->render();
    }

    /** @brief  Render an HTML version of Item Scope.
     *
     *  @return The HTML representation of the Item Scope.
     */
    public function render()
    {
        $namespace = $this->namespace;
        if ($namespace === null)
            $this->setNamespace('');

        if (! @isset(self::$_initialized[$namespace]))
        {
            // Initialization has been postponed.  Force it.
            $this->setNamespace($namespace, true);
        }

        $res = $this->view->partial('itemScope.phtml',
                                     array(
                                         'helper'     =>  $this,
                                     ));
        return $res;
    }
}
