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
class View_Helper_HtmlItemScope extends View_Helper_Abstract
{
    /** @brief  Defaults for set-able parameters . */
    protected   $_defaults  = array(
        'namespace'         => '',

        'hideInput'         => false,
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

    /** @brief  Set the namespace, converting null to ''.
     *  @param  namespace   The (new) namespace.
     *
     *  @return $this for a fluent interface.
     */
    public function setNamespace($namespace)
    {
        if ($namespace === null)
            $namespace = '';

        $this->_params['namespace'] = $namespace;

        return $this;
    }

    /** @brief  Set jsonRpc settings, mixing with the system default values
     *          (Connexions::getJsonRpcInfo()).
     *  @param  config  An array of JsonRpc configuration information
     *                  (e.g. method, params)
     *
     *  @return $this for a fluent interface.
     */
    public function setJsonRpc($config)
    {
        /*
        Connexions::log("View_Helper_HtmlItemScope::setJsonRpc( %s ):",
                        Connexions::varExport($config));
        // */

        $defaults = Connexions::getJsonRpcInfo();
        if (is_array($config))
        {
            $config = array_merge($defaults, $config);
        }
        else
        {
            $config = $defaults;
        }

        /*
        Connexions::log("View_Helper_HtmlItemScope::setJsonRpc(): "
                        . "final config[ %s ]",
                        Connexions::varExport($config));
        // */

        $this->_params['jsonRpc'] = $config;

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
     *                  items that define the scope (or null).
     *
     *  @return $this for a fluent interface.
     */
    public function setScope($scope)
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
        $res = $this->view->partial('itemScope.phtml',
                                     array('helper' => $this));
        return $res;
    }
}
