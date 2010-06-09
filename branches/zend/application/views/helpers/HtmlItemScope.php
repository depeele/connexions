<?php
/** @file
 *
 *  View helper to render the item scope -- root (e.g. user-name, main section
 *  name), sub-section name, scope items (e.g. tags), scope entry, item count
 *  all in HTML.
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

        'autoCompleteUrl'   => null,    /*  The URL to use to retrieve scope
                                         *  items for scope entry
                                         *  auto-completion.
                                         */

        'items'             => null,    /* The set of items to be presented
                                         *  MUST implement the
                                         *      getTotalItemCount() method
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
     *                      - autoCompleteUrl   The URL to use to retrieve
     *                                          scope items for scope entry
     *                                          auto-completion.
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
            if ( ($this->_autoCompleteUrl === null) && ($force !== true) )
                /* Postpone initialization to see if the _autoCompleteUrl is
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
            $jQuery->addOnLoad("$('.{$namespace}ItemScope').itemScope({"
                                .    "autocompleteSrc:"
                                .       "'{$this->_autoCompleteUrl}'});");

            if (false)
            {
            $baseUrl = $view->baseUrl('/');
            if (! empty($this->_autoCompleteUrl))
            {
                /* Now done in application/layouts/header.phtml
                $jQuery->addJavascriptFile($baseUrl.'js/jquery.autosuggest.js');
                */

                list($scopeCbUrl, $scopeCbParams)
                                = explode('?', $this->_autoCompleteUrl);
            }
            $jQuery->addOnLoad("init_{$namespace}ItemScope();")
                   ->javascriptCaptureStart();  // jQuery {
            ?>

/************************************************
 * Initialize display options.
 *
 */
function init_<?= $namespace ?>ItemScope()
{
    var $itemScope  = $('.<?= $namespace ?>ItemScope');
    var $input      = $itemScope.find('.scopeEntry input');
    var scopeCbUrl  = <?= ($scopeCbUrl !== null
                                ? "'". $scopeCbUrl ."'"
                                : 'null') ?>;

    if (scopeCbUrl === null)
    {
        $input.input();
    }
    else
    {
        var $label  = $input.parent().find('label');

        // queryParam, extraParams
        // Attach autoSuggest to our input box
        $input.autoSuggest(scopeCbUrl,
                           {startText:   $label.text(),
                            extraParams: '&<?= $scopeCbParams ?>',
                            minChars:    2,
                            keyDelay:    200,
                            retrieveComplete: function(data) {
                                // JSON-RPC return
                                if (data.result)
                                    return data.result;
                            }});

        $input = $itemScope.find('.scopeEntry input.as-values');
        $label.hide();
    }


    // Attach a hover effect for deletables
    $itemScope.find('.deletable a.delete')
                .css('opacity', 0.25)
                .hover(
        // in
        function() {
            $(this).css('opacity', 1.0)
                   .addClass('ui-icon-circle-close')
                   .removeClass('ui-icon-close');
        },
        // out
        function() {
            $(this).addClass('ui-icon-close')
                   .removeClass('ui-icon-circle-close')
                   .css('opacity', 0.25);
        }
    );

    var $pForm  = $itemScope.closest('form');

    // Add an on-submit handler to our parent form
    $pForm.submit(function() {
        // Changing scope - adjust the form...
        var scope   = (scopeData === null
                        ? $input.input('val')
                        : $input.val().replace(/,$/, ''));
        var current = $pForm.find('input[name=scopeCurrent]').val();
        var action  = $pForm.attr('action') +'/'+ current;

        if (scope.length > 0)
        {
            // Change the form action to include the new scope
            if (current.length > 0)
                action += ',';
            action += scope;
        }
        $pForm.attr('action', action);

        // Disable scope -- this removes these items from form serialization.
        $input.attr('disabled', true);

        var ser = $pForm.serialize();
        var a   = 1;
    });
}
            <?php
            $jQuery->javascriptCaptureEnd();    // jQuery }
            }

            self::$_initialized[$namespace] = true;
        }

        return $this;
    }

    /** @brief  Establish the set of items being presented within this scope.
     *  @param  items   The set of items to be presented
     *                  (MUST implement the getTotalItemCount() method).
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

        $url    = '';
        $action = '';
        $html   = '<ul class="ui-corner-top">';
        if ( @is_array($this->path))
        {
            $cssClass = 'root ui-corner-tl';
            foreach ($this->path as $pathName => $pathUrl)
            {
                $html .= sprintf(  "<li class='%s'>"
                                 .  "<a href='%s'>%s</a>"
                                 . "</li>",
                                 $cssClass,
                                 $pathUrl, $pathName);

                if (strpos($cssClass, 'root') !== false)
                {
                    $action = $pathUrl;

                    $cssClass = 'section';
                }
                else
                {
                    array_push($curScope, $pathName);
                }

                $url = $pathUrl;
            }
        }

        $curScope = array();
        $scope    = $this->scope;
        if ( $scope && (count($scope) > 0))
        {
            //$html .= "<li class='scopeItems'>"
            //      .   "<ul>";
        
            // Grab the original request URL and clean it up.
            $reqUrl = Connexions::getRequestUri();

                      // remove the query/fragment
            $reqUrl = preg_replace('/[\?#].*$/', '',  $reqUrl);
            $reqUrl = urldecode($reqUrl);
                      // collapse white-space
            $reqUrl = preg_replace('/\s\s+/',    ' ', $reqUrl);
            $reqUrl = rtrim($reqUrl, " \t\n\r\0\x0B/");
            $reqUrl = str_replace('/'. $scope->getSource(), '', $reqUrl);
        
            //Connexions::log("ItemScope: reqUrl[ {$reqUrl} ]");
        
            $validList = preg_split('/\s*,\s*/', $scope->__toString());
            foreach ($validList as $idex => $name)
            {
                if (in_array($name, $this->_hiddenItems))
                    continue;

                /* Get the set of all OTHER scope items (i.e. everything EXCEPT
                 * the current) and use it to construct the URL to use for
                 * removing this item from the scope.
                 */
                $others = array_diff($validList, array($name));
                $remUrl = $reqUrl .'/'. implode(',', $others);
        
                /*
                Connexions::log("HtmlItemScope: reqUrl[ {$reqUrl} ], "
                                        . "name[ {$name} ], "
                                        . "remUrl[ {$remUrl} ]");
                // */
        
                $html .= sprintf (  "<li class='scopeItem deletable'>"
                                  .  "<a href='%s/%s'>%s</a>"
                                  .  "<a href='%s' "
                                  .     "class='delete ui-icon ui-icon-close'>"
                                  .   "x"
                                  .  "</a>"
                                  . "</li>",
                                  $url, $name, $name,
                                  $remUrl);

                array_push($curScope, $name);
            }
        
            //$html .=  "</ul>"
            //      .  "</li>";
        }
        
        $html .=  "<li class='scopeEntry'>"
              .    "<label  for='{$this->inputName}'>"
              .     $this->inputLabel
              .    "</label>"
              .    "<input name='{$this->inputName}' />"
              .    "<button type='submit'>&gt;</button>"
              .   "</li>"
              .   "<li class='itemCount ui-corner-tr'>"
              .    number_format($this->items->getTotalItemCount())
              .   "</li>"
              .   "<br class='clear' />"
              .  "</ul>";



        // Finalize the HTML
        $html = "<form action='{$action}' "
              .        "class='itemScope {$this->namespace}ItemScope ui-form'>"
              .  "<input type='hidden' name='scopeCurrent' "
              .        "value='". implode(',', $curScope) ."'>"
              .  $html
              . "</form>";

        
        return $html;
    }
}
