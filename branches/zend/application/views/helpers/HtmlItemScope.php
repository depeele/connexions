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

    protected function _initialize($scopeCbUrl)
    {
        if (self::$_initialized)
            return;

        $view   =& $this->view;

        $view->headLink()
                ->appendStylesheet($view->baseUrl('css/autoSuggest.css'));

        $jQuery = $view->jQuery();

        $baseUrl = $view->baseUrl('/');
        if ($scopeCbUrl === null)
            $jQuery->addJavascriptFile($baseUrl .'js/ui.input.js');
        else
        {
            $jQuery->addJavascriptFile($baseUrl .'js/jquery.autosuggest.js');

            list($scopeCbUrl, $scopeCbParams) = explode('?', $scopeCbUrl);
        }

        $jQuery->addOnLoad('init_itemScope();')
               ->javascriptCaptureStart();
        ?>

/************************************************
 * Initialize display options.
 *
 */
function init_itemScope()
{
    var $itemScope  = $('.itemScope');
    var $input      = $itemScope.find('.scopeEntry input');
    var scopeCbUrl  = <?= ($scopeCbUrl !== null
                                ? "'". $scopeCbUrl ."'"
                                : 'null') ?>;

    if (scopeCbUrl === null)
    {
        /* Attach ui.input to the input field with defined 'emptyText' and a
         * validation callback to enable/disable the submit button based upon
         * whether or not there is text in the search box.
         */
        $input.input(); //itemScope.find('input[emptyText]').input();
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
     *  @param  scopeCbUrl  The URL to use to retrieve scope items for scope 
     *                      entry auto-completion.
     *
     *  @return The HTML representation of the Item Scope.
     */
    public function htmlItemScope(Zend_Paginator        $paginator,
                                  Connexions_Set_info   $scopeInfo,
                                                        $inputLabel = '',
                                                        $inputName  = '',
                                                        $path       = null,
                                                        $scopeCbUrl = null)
    {
        $this->_initialize($scopeCbUrl);

        $url    = '';
        $action = '';
        $html   = '<ul class="ui-corner-top">';
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
              .    "<input     name='{$inputName}' "
              .          "emptyText='{$inputLabel}' "
              .              "class='ui-input ui-corner-all "
              .                     "ui-state-default ui-state-empty' />"
              .   "</li>"
              .   "<li class='itemCount ui-corner-tr'>"
              .    number_format($paginator->getTotalItemCount())
              .   "</li>"
              .   "<br class='clear' />"
              .  "</ul>";



        // Finalize the HTML
        $html = "<form action='${action}' "
              .        "class='itemScope'>"
              .  "<input type='hidden' name='scopeCurrent' "
              .        "value='". implode(',', $curScope) ."'>"
              .  $html
              . "</form>";

        
        return $html;
    }
}
