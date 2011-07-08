<?php
define('RPC_DIR', realpath(dirname(__FILE__) .'/..'));

require_once(RPC_DIR. '/bootstrap.php');

$config  = Connexions::getConfig();
$baseUrl = $config->urls->base;

?>
<html>
 <head>
  <title>Json-Rpc tester</title>
  <link type='text/css' rel='stylesheet' href='<?= $baseUrl ?>/css/themes/connexions/jquery-ui.css' />
  <style type='text/css'>
  body {
    font-family:    sans-serif;
    font-size:      14px;
  }
  h4, h3, h2 {
    margin-bottom:  0;
  }
  form {
    padding:            0.5em;
    margin:             0 1em;
    border:             1px solid #ccc;
    background-color:   #eee;
  }
  ul {
    margin:             0;
    padding:            0;
  }
  li {
    list-style: none;
  }
  hr {
    size:               0;
    border:             1px dotted #ccc;
  }

  #services {
    height:             75%;
    overflow:           auto;
  }
  .ui-accordion .ui-accordion-header .ui-icon {
    display:            inline-block;
  }
  .ui-accordion .ui-accordion-content {
    padding:            0 1em 0.5em;
  }
  .control {
    cursor:             pointer;
  }
  .field {
    clear:              left;
  }
  .field label {
    clear:              left;
    float:              left;
    width:              7em;
  }
  .field input, .field textarea {
    float:              left;
  }
  .ui-widget .ui-button {
    font-size:          0.8em;
  }
  .ui-button-text-only .ui-button-text {
    padding:            0.25em 0.5em;
  }
  .results {
    padding:            0 1em 1em;
    margin:             1em;
    border:             1px solid #ccc;
    background-color:   #eee;
  }
  .sub-items {
    padding:            0 0 0 9em;
  }
  .brace {
    color:          #0a0;
  }
  .literal {
    color:          #f0f;
  }
  .string {
    color:          #f00;
  }
  .key {
    color:          #000;
  }
  .keyword {
    color:          #f0f;
  }

  .number {
    color:          #00f;
  }

  </style>

  <script type='text/javascript' src='<?= $baseUrl ?>/js/jquery.min.js'></script>
  <script type='text/javascript' src='<?= $baseUrl ?>/js/jquery-ui.min.js'></script>
  <script type='text/javascript' src='<?= $baseUrl ?>/js/json2.js'></script>
  <script type='text/javascript' src='<?= $baseUrl ?>/js/jsDump.js'></script>
  <script type='text/javascript'>
    (function($) {
        $(document).ready(function() {
            var rpcId       = 1;
            var useJsDump   = true;
            var rawData     = null;
            var $result     = $('#result');

            // Format 'data' according to 'useJsDump'
            function data2html(data)
            {
                if (data === undefined) data    = rawData;
                else                    rawData = data;

                return (data === null
                        ? ''
                        : (useJsDump
                            ? jsDump.parse( data )
                            : JSON.stringify(data, null, '  ')
                                  .replace(/</, '&lt;')
                                  .replace(/>/, '&gt;')
                                  .replace(/&/, '&amp;')) );
            }

            $result.click(function() {
                // Clicking on the result area will toggle the formatting.
                var $pre    = $result.find('pre');

                useJsDump = (useJsDump === false ? true : false);

                $pre.html( data2html() );
            });

            /** @brief  On form submission, generate and initiate a Json-RPC.
             *  @param  e   The form submit event.
             *
             *  Usage: $('form').bind('submit', rpc_submit)
             *                  .bind('success',
             *                        function(e, data, txtStatus, req) ... )
             *                  .bind('error',
             *                        function(e, txtStatus, req) ... );
             *
             *  By default, result data will be presented in the div with
             *  id 'result'
             */
            function rpc_submit(e)
            {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                var $form   = $(e.target);
                var url     = document.location.href;
                
                url = url.substr(0, url.lastIndexOf('/')+1)
                    + $form.attr('action');

                // Assemble the Json-RPC structure
                var rpc = {
                    version: 2.0,
                    method:  $form.find('input[name=method]').val(),
                    id:      rpcId++,
                    params:  {
                    }
                };

                // Include all parameters
                $form.find('input:not(:hidden,:submit)').each(function() {
                    var $field  = $(this);

                    switch ($field.attr('type'))
                    {
                    case 'checkbox':
                    case 'radio':
                        rpc.params[ $field.attr('name') ] =
                                                    $field.attr('checked');
                        break;

                    default:
                        rpc.params[ $field.attr('name') ] = $field.val();
                        break;
                    }
                });

                // Invoke the Json-RPC
                $.ajax({
                    type:     $form.attr('method'),
                    url:      $form.attr('action'),
                    data:     JSON.stringify(rpc),
                    dataType: 'json',
                    success: function(data, txtStatus, req) {
                        $form.trigger('success', [data, txtStatus, req]);

                        $result.html(  '<h3 class="success">'
                                     +  rpc.method +': '+ txtStatus
                                     + '</h3>'
                                     + '<pre>'
                                     +  data2html(data)
                                     + '</pre>');
                    },
                    error: function(req, txtStatus, e) {
                        $form.trigger('error', [txtStatus, req]);

                        // /*
                        $result.html(  '<h3 class="error">'
                                     +  rpc.method +': '+ txtStatus
                                     + '</h3>');
                        // */
                    }
                });
            }

            /** @brief  Given a service description, generate a matching form
             *          for that service.
             *  @param  service     The service description:
             *                          { envelope:     'JSON-RPC-2.0',
             *                            transport:    'POST',
             *                            parameters:   [
             *                              {type:      %string%,
             *                               name:      %parmater name%,
             *                               optional:  true/false,
             *                               default:   %default value%
             *                              },
             *                              ...
             *                             ],
             *                             returns:     [ ... ]
             *                          }
             *
             *  @return HTML for this method.
             */
            function buildForm(name, info, target)
            {
                var parts   = name.split('.');
                var lTmpl   = '<li><h4>%method_label%</h4>'
                            +  '<form method="%transport%" '
                            +        'action="json-rpc.php">'
                            +   '<input name="method" type="hidden" '
                            +         'value="%method%" />'
                            +   '%params%'
                            +  '<button>POST</button>'
                            +  '<button>GET</button>'
                            +  '</form>'
                            + '</li>';
                var pTmpl   =   '<div class="field">'
                            +    '<label for="%name%">%name%</label>'
                            +    '<input name="%name%" '
                            +           'type="%type%" '
                            +          'value="%default%" />'
                            +   '</div>';

                var nParams = info.parameters.length;
                var params  = '';

                for (var idex = 0; idex < nParams; idex++)
                {
                    var param   = info.parameters[idex];

                    params += pTmpl.replace(/%name%/g, param.name)
                                   .replace(/%type%/g, 'text')
                                   .replace(/%default%/g,
                                                (param['default'] !== undefined
                                                    ? param['default']
                                                    : ''));
                }

                var li      = lTmpl.replace(/%method%/g,       name)
                                   .replace(/%method_label%/g, parts[1])
                                   .replace(/%transport%/g,    info.transport)
                                   .replace(/%params%/g,       params);

                return li;
            }

            /** @brief  Given a service map, generate forms for each service.
             *  @param  map         The service map is of the form:
             *                          { envelope:     'JSON-RPC-2.0',
             *                            transport:    'POST',
             *                            contentType:  'application/json',
             *                            SMDVersion:   '2.0',
             *                            target:       '/api/v2/json-rpc',
             *                            services:     [
             *                              % name %: % service Description %,
             *                              ...
             *                            ]
             *                          }
             */
            function buildForms(map)
            {
                var $ul         = $('#services');
                var $section    = null;
                var lastSection = null;
                $.each(map.services, function(name, info) {
                    var parts   = name.split('.');

                    if (lastSection !== parts[0])
                    {
                        var li   = '<li>'
                                 +   '<h3>'
                                 +    "<span class='control'></span>"
                                 +    '<a href="#">'+ parts[0] +'</a>'
                                 +   '</h3>'
                                 + '</li>';
                        var sect = '<ul></ul>';
                        var $li  = $(li);
                        $section = $(sect);
                        
                        $li.addClass( 'ui-accordion-li-fix' )
                                .find('h3')
                                    .addClass(  'ui-accordion-header '
                                              + 'ui-state-default '
                                              + 'ui-helper-reset '
                                              + 'ui-corner-top' )
                                .find('span.control')
                                    .addClass(  'ui-icon '
                                              + 'ui-icon-triangle-1-e' );

                        $section.addClass(      'service '
                                              + parts[0] +' '
                                              + 'ui-accordion-content '
                                              + 'ui-helper-reset '
                                              + 'ui-widget-content '
                                              + 'ui-corner-bottom' )
                                 .hide();

                        $ul.append( $li.append($section) );

                        lastSection = parts[0];
                    }

                    var form    = buildForm(name, info, map.target);

                    $section.append(form);
                });
            }

            function bindForms()
            {
                var $forms      = $('#services form');
                var $buttons    = $forms.find('button');

                $forms.bind('submit.rpc',  rpc_submit);

                $buttons.bind('click.rpc', function(e) {
                    e.preventDefault();

                    var $button = $(this);
                    var $form   = $button.parents('form:first');

                    // Use the button text as the new form method.
                    $form.attr('method', $button.text());

                    $form.submit();
                });
            }

            // Fetch the service description and then build forms...
            var indicator   = {
                opened: 'ui-icon-triangle-1-s',
                closed: 'ui-icon-triangle-1-e'
            };
            $.getJSON('json-rpc.php?serviceDescription=1',
                      function(data, txtStatus)
                      {
                        buildForms(data);

                        var $ul = $('#services');
                        $ul.addClass(  'ui-accordion '
                                     + 'ui-widget '
                                     + 'ui-helper-reset '
                                     + 'ui-accordion-icons' );

                        $ul.find('h3').click(function() {
                            var $ctl    = $(this);
                            var $body   = $ctl.next();
                            var $toggle = $ctl.find('span:first');

                            if ($body.is(':hidden'))
                            {
                                $toggle.removeClass(indicator['closed'])
                                       .addClass(   indicator['opened']);
                            }
                            else
                            {
                                $toggle.removeClass(indicator['opened'])
                                       .addClass(   indicator['closed']);
                            }

                            $body.slideToggle('fast');
                            return false;
                        });

                        $ul.find('button').button().click(function() {
                        });

                        bindForms();
                      });
        });
    }(jQuery));
  </script>
 </head>
 <body>
  <ul id='services'>
  </ul>
  <hr />
  <div class='results'>
   <h3>Results</h3>
   <div id='result'></div>
  </div>
 </body>
</html>
