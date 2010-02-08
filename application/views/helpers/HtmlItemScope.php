<?php
/** @file
 *
 *  View helper to render the item scope -- root (e.g. user-name, main section
 *  name), sub-section name, scope items (e.g. tags), scope entry, item count
 *  all in HTML.
 */
class Connexions_View_Helper_HtmlItemScope extends Zend_View_Helper_Abstract
{
    static protected    $_initialized   = false;

    protected function _initialize()
    {
        if (self::$_initialized)
            return;

        $view   =& $this->view;
        $jQuery = $view->jQuery();

        $jQuery->addJavascriptFile($view->baseUrl('js/ui.input.js'))
               ->addOnLoad('init_itemScope();')
               ->javascriptCaptureStart();

        ?>

/************************************************
 * Initialize display options.
 *
 */
function init_itemScope()
{
    var $itemScope  = $('.itemScope');
    var $input      = $itemScope.find('input');

    /* Attach ui.input to the input field with defined 'emptyText' and a
     * validation callback to enable/disable the submit button based upon
     * whether or not there is text in the search box.
     */
    $itemScope.find('input[emptyText]').input();

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
        var scope   = $input.input('val');
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
        $jQuery->javascriptCaptureEnd();

        self::$_initialized = true;
    }

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
     *
     *  @return The HTML representation of the Item Scope.
     */
    public function htmlItemScope(Zend_Paginator        $paginator,
                                  Connexions_Set_info   $scopeInfo,
                                                        $inputLabel = '',
                                                        $inputName  = '',
                                                        $path       = null)
    {
        $this->_initialize();

        $url    = '';
        $action = '';
        $html   = '<ul>';
        if ( @is_array($path))
        {
            $cssClass = 'root ui-corner-tl';
            foreach ($path as $pathName => $pathUrl)
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
            $html .= "<li class='scopeItems'>"
                  .   "<ul>";
        
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
        
                $html .= sprintf (  "<li class='deletable'>"
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
        
            $html .=  "</ul>"
                  .  "</li>";
        }
        
        $html .=  "<li class='scopeEntry'>"
              .    "<input     name='{$inputName}' "
              .          "emptyText='{$inputLabel}' "
              .              "class='ui-input ui-corner-all "
              .                     "ui-state-default ui-state-empty' />"
              .   "</li>"
              .   "<li class='itemCount ui-corner-tr'>"
              .    number_format($paginator->getTotalItemCount())
              .   "</li>"
              .  "</ul>"
              .  "<br class='clear' />";



        // Finalize the HTML
        $html = "<form action='${action}' "
              .        "class='itemScope ui-corner-all'>"
              .  "<input type='hidden' name='scopeCurrent' "
              .        "value='". implode(',', $curScope) ."'>"
              .  $html
              . "</form>";

        
        return $html;
    }
}
