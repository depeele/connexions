<?php
/** @file
 *
 *  View helper to render the item scope -- root (e.g. user-name, main section
 *  name), sub-section name, scope items (e.g. tags), scope entry, item count
 *  all in HTML.
 */
class Connexions_View_Helper_HtmlItemScope extends Zend_View_Helper_Abstract
{
    /** @brief  Set-able parameters . */
    protected       $_namespace         = null;     /* To allow render() to
                                                     * initialize if the
                                                     * namespace has not yet
                                                     * been set.
                                                     */
    protected       $_inputLabel        = 'Items';
    protected       $_inputName         = 'items';
    protected       $_path              = null;
    protected       $_autoCompleteUrl   = null;
    protected       $_hiddenItems       = array();

    /** @brief  Variable Namespace/Prefix initialization indicators. */
    static protected $_initialized  = array();


    /** @brief  Render an HTML version of Item Scope.
     *  @param  paginator   The Zend_Paginator representing the items to be
     *                      presented;
     *  @param  scopeInfo   A Connexions_Set_Info instance containing
     *                      information about the requested scope items
     *                      (e.g.  tags, user);
     *  @param  inputLabel  The text to present when the input box is empty;
     *  @param  inputName   The form-name for the input box;
     *  @param  path        A simple array containing the names and urls of the
     *                      path items to the current scope:
     *                          array(root-name => root-url,
     *                                item-name => item-url,
     *                                ...)
     *  @param  scopeCbUrl  The URL to use to retrieve scope items for scope 
     *                      entry auto-completion.
     *
     *  @return The HTML representation of the Item Scope.
     */
    public function htmlItemScope(Zend_Paginator        $paginator  = null,
                                  Connexions_Set_Info   $scopeInfo  = null,
                                                        $inputLabel = '',
                                                        $inputName  = '',
                                                        $path       = null,
                                                        $scopeCbUrl = null)
    {
        if ($paginator === null)
            return $this;

        return $this->render($paginator, $scopeInfo,
                             $inputLabel, $inputName, $path, $scopeCbUrl);
    }

    /** @brief  Set the namespace, primarily for forms and cookies.
     *  @param  namespace   A string prefix.
     *  @param  force       Should initialization be forced even if the
     *                      auto-completion url has not yet been set? [ false ]
     *
     *  @return Connexions_View_Helper_HtmlItemScope for a fluent interface.
     */
    public function setNamespace($namespace, $force = false)
    {
        /*
        Connexions::log("Connexions_View_Helper_HtmlItemScope::"
                            .   "setNamespace( {$namespace} )");
        // */

        $this->_namespace = $namespace;

        if (! @isset(self::$_initialized[$namespace]))
        {
            if ( ($this->_autoCompleteUrl === null) && ($force !== true) )
                /* Postpone initialization to see if the _autoCompleteUrl is
                 * set before we render.
                 */
                return $this;

            $view   =& $this->view;

            $view->headLink()
                    ->appendStylesheet($view->baseUrl('css/autoSuggest.css'));

            $jQuery = $view->jQuery();

            $baseUrl = $view->baseUrl('/');
            if (empty($this->_autoCompleteUrl))
                $jQuery->addJavascriptFile($baseUrl.'js/ui.input.min.js');
            else
            {
                $jQuery->addJavascriptFile($baseUrl.'js/jquery.autosuggest.js');

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
        // queryParam, extraParams
        // Attach autoSuggest to our input box
        $input.autoSuggest(scopeCbUrl,
                           {startText:   $input.attr('emptyText'),
                            extraParams: '&<?= $scopeCbParams ?>',
                            minChars:    2,
                            keyDelay:    200,
                            retrieveComplete: function(data) {
                                // JSON-RPC return
                                if (data.result)
                                    return data.result;
                            }});

        $input = $itemScope.find('.scopeEntry input.as-values');
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

            self::$_initialized[$namespace] = true;
        }

        return $this;
    }

    /** @brief  Get the current namespace.
     *
     *  @return The string namespace.
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /** @brief  Render an HTML version of Item Scope.
     *  @param  paginator   The Zend_Paginator representing the items to be
     *                      presented -- used to present the item count;
     *  @param  scopeInfo   A Connexions_Set_Info instance containing
     *                      information about the requested scope items
     *                      (e.g.  tags, user);
     *  @param  inputLabel  The text to present when the input box is empty;
     *  @param  inputName   The form-name for the input box;
     *  @param  path        A simple array containing the names and urls of the
     *                      path items to the current scope:
     *                          array(root-name => root-url,
     *                                item-name => item-url,
     *                                ...)
     *  @param  scopeCbUrl  The URL to use to retrieve scope items for scope 
     *                      entry auto-completion.
     *
     *  @return The HTML representation of the Item Scope.
     */
    public function render(Zend_Paginator       $paginator,
                           Connexions_Set_Info  $scopeInfo,
                                                $inputLabel = '',
                                                $inputName  = '',
                                                $path       = null,
                                                $scopeCbUrl = null)
    {
        if (! empty($inputLabel))   $this->setInputLabel($inputLabel);
        if (! empty($inputName))    $this->setInputName($inputName);
        if ($path       !== null)   $this->setPath($path);
        if ($scopeCbUrl !== null)   $this->setAutoCompleteUrl($scopeCbUrl);

        if ($this->_namespace === null)
            $this->setNamespace('');

        if (! @isset(self::$_initialized[$this->_namespace]))
        {
            // Initialization has been postponed.  Force it.
            $this->setNamespace($this->_namespace, true);
        }

        $url    = '';
        $action = '';
        $html   = '<ul class="ui-corner-top">';
        if ( @is_array($this->_path))
        {
            $cssClass = 'root ui-corner-tl';
            foreach ($this->_path as $pathName => $pathUrl)
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
        if ( $scopeInfo->hasValidItems())
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
            $reqUrl = str_replace('/'. $scopeInfo->reqStr, '', $reqUrl);
        
            //Connexions::log("ItemScope: reqUrl[ {$reqUrl} ]");
        
            foreach ($scopeInfo->valid as $name => $id)
            {
                if (in_array($name, $this->_hiddenItems))
                    continue;

                /* Get the set of all OTHER scope items (i.e. everything EXCEPT
                 * the current) and use it to construct the URL to use for
                 * removing this item from the scope.
                 */
                $others = array_diff($scopeInfo->validList, array($name));
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
              .    "<input     name='{$this->_inputName}' "
              .          "emptyText='{$this->_inputLabel}' "
              .              "class='ui-input ui-corner-all "
              .                     "ui-state-default ui-state-empty' />"
              .   "</li>"
              .   "<li class='itemCount ui-corner-tr'>"
              .    number_format($paginator->getTotalItemCount())
              .   "</li>"
              .   "<br class='clear' />"
              .  "</ul>";



        // Finalize the HTML
        $html = "<form action='{$action}' "
              .        "class='itemScope {$this->_namespace}ItemScope'>"
              .  "<input type='hidden' name='scopeCurrent' "
              .        "value='". implode(',', $curScope) ."'>"
              .  $html
              . "</form>";

        
        return $html;
    }

    /** @brief  Set the input label.
     *  @param  inputLabel  The label string.
     *
     *  @return Connexions_View_Helper_HtmlItemScope for a fluent interface.
     */
    public function setInputLabel($inputLabel)
    {
        $this->_inputLabel = $inputLabel;

        return $this;
    }

    /** @brief  Get the current input label.
     *
     *  @return  The label string.
     */
    public function getInputLabel()
    {
        return $this->_inputLabel;
    }

    /** @brief  Set the input field-name.
     *  @param  inputName   The name string.
     *
     *  @return Connexions_View_Helper_HtmlItemScope for a fluent interface.
     */
    public function setInputName($inputName)
    {
        $this->_inputName = $inputName;

        return $this;
    }

    /** @brief  Get the current input field-name.
     *
     *  @return  The name string.
     */
    public function getInputName()
    {
        return $this->_inputName;
    }

    /** @brief  Set the current "path".
     *  @param  path    An array of path information.
     *
     *  @return Connexions_View_Helper_HtmlItemScope for a fluent interface.
     */
    public function setPath($path)
    {
        $this->_path = $path;

        return $this;
    }

    /** @brief  Get the current path.
     *
     *  @return  The path.
     */
    public function getPath()
    {
        return $this->_path;
    }

    /** @brief  Set the auto-completion URL.
     *  @param  url     The url string.
     *
     *  @return Connexions_View_Helper_HtmlItemScope for a fluent interface.
     */
    public function setAutoCompleteUrl($url)
    {
        $this->_autoCompleteUrl = $url;

        return $this;
    }

    /** @brief  Get the current auto-completion URL.
     *
     *  @return  The url string.
     */
    public function getAutoCompleteUrl()
    {
        return $this->_autoCompleteUrl;
    }

    /** @brief  Add a scope item that should be hidden.
     *  @param  str     The scope item (name).
     *
     *  @return Connexions_View_Helper_HtmlItemScope for a fluent interface.
     */
    public function addHiddenItem($str)
    {
        array_push($this->_hiddenItems, $str);
    }
}
