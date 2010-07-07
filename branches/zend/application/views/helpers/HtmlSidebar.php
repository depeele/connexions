<?php
/** @file
 *
 *  View helper to render the sidebar in HTML.
 *
 *  The sidebar is a tabbed area that can present one or more information panes
 *  presenting information related to the items presented in the main view.
 */
class View_Helper_HtmlSidebar extends Zend_View_Helper_Abstract
{
    /** @brief  Set-able parameters . */
    protected   $_params    = array(
        'namespace' => 'sidebar',   /* The namespace used to identify
                                     * the sidebar as well as associated
                                     * cookies/settings/parameters.
                                     */
        'async'     => false,       // Asyncrhonous pane loading?
        'viewer'    => null,
        'users'     => null,
        'tags'      => null,
        'items'     => null,        /* The set of items presented
                                     * in the main view.
                                     */
        'panes'     => array(
            /* title,               The title of the pane;
             * url,                 The URL to use to retrieve the contents of
             *                      the pane
             */
         ),
    );

    /** @brief  Construct a new Sidebar helper.
     *  @param  config  A configuration array (see populate());
     */
    public function __construct(array $config = array())
    {
        //Connexions::log("View_Helper_HtmlSidebar::__construct()");
        if (! empty($config))
            $this->populate($config);
    }

    /** @brief  Given an array of configuration data, populate the parameter of
     *          this instance.
     *  @param  config  A configuration array that may include:
     *                      - namespace     The set of items presented in the
     *                      - viewer        The set of items presented in the
     *                      - users         The set of items presented in the
     *                      - tags          The set of items presented in the
     *                      - items         The set of items presented in the
     *                                      main view;
     *                      - panes         The set of items presented in the
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

        // /*
        $viewer = $this->_params['viewer']; unset($this->_params['viewer']);
        $users  = $this->_params['users'];  unset($this->_params['users']);
        $tags   = $this->_params['tags'];   unset($this->_params['tags']);
        $items  = $this->_params['items'];  unset($this->_params['items']);
        $panes  = $this->_params['panes'];  unset($this->_params['panes']);

        $this->_params['viewer'] = $viewer->name;
        $this->_params['users']  = ($users === null
                                        ? ''
                                        : $users->__toString());
        $this->_params['tags']   = ($tags === null
                                        ? 'none'
                                        : count($tags)
                                            . ' tags [ '
                                            . $tags->__toString() .' ]');
        $this->_params['items']  = ($items === null
                                        ? 'none'
                                        : count($items)
                                            . ' items [ '
                                            . $items->__toString() .' ]');
        $this->_params['panes']  = array_keys($panes);

        /*
        Connexions::log("View_Helper_HtmlSidebar::populate(): params[ %s ]",
                        print_r($this->_params, true));
        // */

        $this->_params['viewer'] = $viewer;
        $this->_params['users']  = $users;
        $this->_params['tags']   = $tags;
        $this->_params['items']  = $items;
        $this->_params['panes']  = $panes;
        // */

        return $this;
    }

    /** @brief  Establish the set of items being presented within this scope.
     *  @param  items   The set of items to be presented
     *                  (MUST implement the getTotalItemCount() method).
     *
     *  @return $this for a fluent interface.
     */
    public function setItems(Connexions_Model_Set   $items)
    {
        $this->_params['items'] = $items;

        return $this;
    }

    /** @brief  Retrieve configuration information for the named pane.
     *  @param  name    The name of the desired pane.
     *
     *  @return Configuration array for the named pane.
     */
    public function getPane($name)
    {
        return (is_array($this->_params['panes'][$name])
                    ? $this->_params['panes'][$name]
                    : array());
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

    /** @brief  Configure and retrive this helper instance.
     *  @param  config  A configuration array (see populate());
     *
     *  @return A (partially) configured instance of $this.
     */
    public function htmlSidebar(array $config = array())
    {
        if (! empty($config))
        {
            $this->populate($config);
        }

        return $this;
    }

    /** @brief  Render an HTML version of Item Scope.
     *  @param  path    The script path prefix to use for rendering
     *
     *  @return The HTML representation of the Item Scope.
     */
    public function render($path    = '')
    {
        // Include jQuery to initialize the sidebar
        $jQuery   = $this->view->jQuery();
        $jQuery->addOnLoad("$('#{$this->namespace}').sidebar();");

        $html     =  "<div id='{$this->namespace}'>\n"
                  .   "<ul>\n";
        $paneHtml = '';

        /*
        Connexions::log("View_Helper_HtmlSidebar::render() "
                        . "scriptPaths[ %s ], path[ %s ]",
                        Connexions::varExport($this->view->getScriptPaths()),
                        $path);
        // */

        // For async loading
        if ($this->_params['async'] === true)
        {
            $paneUrl = $this->view->url . '?format=partial&part=sidebar-';
        }
        else
            $paneUrl = '#sidebar-';

        foreach ($this->_params['panes'] as $id => $config)
        {
            $html .= "<li>"
                  .   "<a href='{$paneUrl}{$id}'>"
                  .    "<span>"
                  .     (! empty($config['title'])
                            ? $config['title']
                            : ucfirst($id) )
                  .    "</span>"
                  .   "</a>"
                  .  "</li>\n";

            if ($this->_params['async'] !== true)
            {
                $script = "{$path}sidebar-{$id}.phtml";

                /*
                Connexions::log("View_Helper_HtmlSidebar::render() "
                                . "render script[ %s ]",
                                $script);
                // */

                $paneHtml .= "<div id='sidebar-{$id}'>"
                          .    $this->view->render($script)
                          .  "</div>\n";
            }
        }

        $html     .=  "</ul>\n"
                  .  $paneHtml
                  .  "</div>\n";
        
        return $html;
    }
}
