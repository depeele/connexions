<?php
/** @file
 *
 *  A view helper to standardize display options presentation.
 *
 *  Display Options MAY contain a display style area comprised of a fieldset of
 *  option checkboxes along with one or more pre-defined option groups.
 *
 *  Available display style options are defined via an associative array passed 
 *  to setDefinition().  The keys colon-separated strings that define the field 
 *  name as well as the CSS heirarchy to use when rendering.   The values
 *  values can be any combination of simple strings and/or arrays:
 *      - A simple string value defines the label to be presented for the 
 *        identified option;
 *
 *      - An array value defines multiple properties, primarily form-related, 
 *        for the option.  These MUST include 'label' and MAY include any of 
 *        the following additional properties:
 *          'label'         REQUIRED -- the label for the form field 
 *                          representing this item;
 *          'type'          the form-field input type
 *                              ( ['checkbox'], 'radio' );
 *          'extraPre'      a string of HTML that is to be included within the 
 *                          DOM container for this item BEFORE the form 
 *                          elements of the item itself;
 *          'extraPost'     a string of HTML that is to be included within the 
 *                          DOM container for this item AFTER the form elements 
 *                          of the item itself;
 *
 *          'containerEl'   the DOM element to use for the DOM container of 
 *                          this item [ 'div' ];
 *          'containerCss'  the CSS class to be used for the DOM container of 
 *                          this item (in addition to the default 'field');
 *          'containerPre'  a string of HTML that is to be included BEFORE the
 *                          DOM container of this item;
 *          'containerPost' a string of HTML that is to be included AFTER the
 *                          DOM container of this item;
 *
 *
 *  An example definition:
 *      array(
 *          'item:stats:countTaggers'   => array(
 *              'label'         => 'user count',
 *              'containerCss'  => 'ui-corner-bottom'
 *          ),
 *          'item:stats:rating:stars'   => 'rating stars'
 *      );
 *
 *
 *  Pre-defined groups can be defined via either a string or array:
 *      - string:   a comma-separated string of field names; each must match 
 *                  one of the options in the definition of this display
 *                  style.  In this case, the name used for this group will be 
 *                  the lower-case version of the option name, while the label 
 *                  will be the ucfirst() version of the name;
 *      - array:    an associative array defining this group, containing:
 *                      'label'     the label for this group;
 *                      'isCustom'  is this the item that represents ANY
 *                                  value selection? ( true | [false] );
 *                      'options'   eiter a comma-separated string of field 
 *                                  names OR an array of field names.
 */
class Connexions_View_Helper_HtmlDisplayOptions
                                    extends Zend_View_Helper_Abstract
{
    /** @brief  Namespace initialization indicators. */
    static protected    $_initialized   = array();

    protected           $_namespace     = '';
    protected           $_fields        = array();  // Namespaced form fields

    /** @brief  Value Groups members */
    protected           $_definition    = array();
    protected           $_fieldMap      = array();
    protected           $_groups        = array();

    protected           $_currentGroup  = null;

    /** @brief  Set the View object
     *  @param  view    The view object
     *
     *  @return $this
     */
    public function setView(Zend_View_Interface $view)
    {
        /*
        Connexions::log(
                "Connexions_View_Helper_HtmlDisplayOptions::"
                . "setView()");
        // */

        parent::setView($view);

        if (@isset(self::$_initialized['__global__']))
            return $this;

        // Include general, required view information
        $view   = $this->view;
        $jQuery = $view->jQuery();

        $jQuery->addJavascriptFile($view->baseUrl('js/jquery.cookie.js'))
               ->addJavascriptFile($view->baseUrl('js/ui.optionGroups.js'))
               ->addJavascriptFile($view->baseUrl('js/ui.dropdownForm.js'))
               ->javascriptCaptureStart();
        ?>

/************************************************
 * Initialize display options.
 *
 */
function init_DisplayOptions(opts)
{
    var $displayOptions = $('.'+ opts.namespace +'-displayOptions');
    if ( $displayOptions.length > 0 )
    {
        // Initialize the display options control
        //opts.form = $form;
        $displayOptions.dropdownForm( opts );
    }

    return;
}

        <?php
        $jQuery->javascriptCaptureEnd();

        self::$_initialized['__global__'] = true;

        /*
        Connexions::log(
                "Connexions_View_Helper_HtmlDisplayOptions::"
                . "setView(): COMPLETE");
        // */

        return $this;
    }

    /** @brief  Retrieve the HtmlDisplayOptions instance.
     *  @param  config  An associative array of configuration information that
     *                  may include:
     *                      'namespace'     => <namespace string>, passed to 
     *                                          setNamespace()  method;
     *                      'definition'    => <definition array>, passed to
     *                                          setDefinition() method;
     *                      'groups'        => <group name array>, passed to
     *                                          setGroups() method;
     *
     *
     *  Note: This handles multiple namespaces, each with a unique instance.
     *
     *  @return $this
     */
    public function htmlDisplayOptions(array $config = array())
    {
        if (isset($config['namespace']))
        {
            $namespace = $config['namespace'];

            /*
            Connexions::log(
                    "Connexions_View_Helper_HtmlDisplayOptions::"
                    . "htmlDisplayOptions(): identified  "
                    . "namespace [ {$namespace} ]");
            // */

            if ( (! empty($this->_namespace)) &&
                 ($namespace !== $this->_namespace) )
            {
                // Different namespace!
                if (@isset(self::$_initialized[$namespace]))
                {
                    /*
                    Connexions::log(
                            "Connexions_View_Helper_HtmlDisplayOptions::"
                            . "htmlDisplayOptions(): auto-switch "
                            . "namespaces: '{$this->_namespace}' -> "
                            .             "'{$namespace}'");
                    // */

                    /* An instance exists for this namespace; use and return
                     * it.
                     */
                    $inst = self::$_initialized[$namespace];

                    // No need to switch namespaces
                    unset($config['namespace']);
                }
                else
                {
                    /*
                    Connexions::log(
                            "Connexions_View_Helper_HtmlDisplayOptions::"
                            . "htmlDisplayOptions(): new namespace: "
                            .   " old[ {$this->_namespace} ], "
                            .   " config[ "
                            .       print_r($config, true) ." ]");
                    // */

                    /* Create and initialize for this namespace; use and return
                     * it.
                     */
                    $inst = new $this();
                    $inst->setView($this->view);
                }

                // Invoke htmlDisplayOptions on the new instance.
                return $inst->htmlDisplayOptions($config);
            }


            // Coopt the initial instance for this namespace...
        }

        if (is_array($config['definition']))
            $this->setDefinition($config['definition']);

        if (is_array($config['groups']))
            $this->setGroups($config['groups']);

        if (isset($config['namespace']))
            $this->setNamespace($config['namespace']);

        return $this;
    }

    /**************************************************************************
     * Primary use methods
     *
     */

    /****************
     * Retrieval
     *
     */

    /** @brief  Retrieve the current namespace.
     *
     *  @return The current namespace.
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /** @brief  If 'name' is not provided, retrieve the name of the current
     *          group, otherwise, retrieve the definition of the named group if
     *          it exists.
     *  @param  name    If provided, the name of the desired group.
     *
     *  @return The group value string | the group definition
     */
    public function getGroup($name = null)
    {
        if ($name === null)
        {
            $ret = $this->_currentGroup;
        }
        else
        {
            $ret = (isset($this->_groups[$name])
                        ? $this->_groups[$name]
                        : null);
        }

        return $ret;
    }

    /** @brief  Get the full, CSS selector for the given group.
     *  @param  name    The name of the group.
     *
     *  @return The CSS selector string (or null if not a valid group).
     */
    public function getGroupSelector($name)
    {
        $selector = null;
        if (isset($this->_groups[$name]))
        {
            $selector = array();
            foreach ($this->_groups[$name]['options'] as $fieldName => $info)
            {
                array_push($selector, $info['selector'] .' input');
            }

            $selector = implode(', ', $selector);
        }

        return $selector;
    }

    /** @brief  Get a mapping for all groups of
     *              'groupName' => full CSS selector for that group.
     *
     *  @return The mapping array.
     */
    public function getGroupsMap()
    {
        $map = array();
        foreach ($this->_groups as $groupName => $info)
        {
            $map[ $groupName ] = $this->getGroupSelector($groupName);
        }

        return $map;
    }

    /** @brief  Retrieve the current value of a single field.
     *  @param  name    The field name.
     *
     *  @return The current value ( true | false, null if invalid field ).
     */
    public function getGroupValue($fieldName)
    {
        if (isset($this->_fieldMap[ $fieldName ]))
            $val = $this->_fieldMap[ $fieldName ]['isSet'];
        else
            $val = null;

        return $val;
    }

    /** @brief  Get the group that matches the current set of values, 
     *          established via setGroupValues().
     *
     *  @return The group name (or null if none matched).
     */
    public function getBestGroupMatch()
    {
        $bestMatch = null;
        $bestCount = 0;
        foreach ($this->_groups as $name => $group)
        {
            $isMatch    = true;
            $matchCount = 0;
            foreach ($group['options'] as $fieldName => $data)
            {
                if ($data['isSet'] !== true)
                {
                    $isMatch = false;
                    break;
                }

                $matchCount++;
            }

            if ($isMatch)
            {
                if ($matchCount > $bestCount)
                {
                    $bestMatch = $name;
                    $bestCount = $matchCount;
                }
            }
        }

        return $bestMatch;
    }

    /** @brief  Retrieve the current values, filling in additional, useful 
     *          meta-information.
     *
     *  @return The current values.
     */
    public function getGroupValues()
    {
        $vals = array();

        foreach ($this->_fieldMap as $name => &$def)
        {
            $vals[ $name ] = $def['isSet'];
        }

        /* Walk again, splitting each into pieces so we can also set 
         * interveening meta-information
         */
        foreach ($this->_fieldMap as $name => &$def)
        {
            // Split into pieces so we can also set meta-information
            $parseName = $name;
            while ( ($pos = strrpos($parseName, ':')) !== false)
            {
                $pre = substr($parseName, 0, $pos);

                if (! isset($vals[$pre]))
                {
                    /* The interveening item name has NOT yet been set.
                     * Set it to the current item's value.
                     */
                    /*
                    printf ("  %-30s: %-25s == %s\n",
                            $parseName, $pre,
                            ($vals[ $name ] ? 'true' : 'false'));
                    */

                    $vals[$pre] = $vals[ $name ];
                }
                else if (($vals[$pre] === false) && ($vals[$name] === true))
                {
                    /* The interveening item name was ALREADY set to 'false'
                     * and this new item is set to 'true' -- override the old 
                     * 'false' value with this new 'true'.
                     *
                     * In essence, true has priority over false in respect to 
                     * its effect on interveening meta-information.
                     */
                    /*
                    printf ("  %-30s: %-25s == change to true\n",
                            $parseName, $pre);
                    */

                    $vals[$pre] = true;
                }

                $parseName = $pre;
            }
        }

        return $vals;
    }

    /****************
     * Setting
     *
     */

    /** @brief  Set field values by an established group.
     *  @param  groupName   The name of the desired group.
     *
     *  @return $this (null if 'groupName' is not a valid group).
     */
    public function setGroup($groupName)
    {
        if (! isset($this->_groups[$groupName]))
            return null;

        $this->_currentGroup = $groupName;

        // First, unset all current values
        foreach ($this->_fieldMap as $name => &$def)
        {
            $def['isSet'] = false;
        }

        // Now, set all values of the named group.
        foreach ($this->_groups[$groupName]['options'] as $fieldName => $data)
        {
            $this->_fieldMap[$fieldName]['isSet'] = true;
        }

        return $this;
    }

    /** @brief  Set field values based upon incoming form data.
     *  @param  vals    An associative array of incoming form values
     *                  (from a form GET/POST or cookies).
     *
     *  @return $this
     */
    public function setGroupValues($vals = array())
    {
        /*
        Connexions::log("Connexions_View_Helper_HtmlDisplayOptions:"
                            . "setGroupValues: "
                            .   "in [ ". print_r($vals, true) ." ]");
        // */

        foreach ($this->_fieldMap as $name => &$def)
        {
            $val = false;
            if (isset($vals[$name]))
            {
                switch (strtolower($vals[$name]))
                {
                case 'on':
                case 'yes':
                case 'true':
                case '1':
                    $val = true;
                    break;

                case 'hide':
                    $val = 'hide';
                    break;
                }
            }

            $def['isSet'] = $val;
        }

        // Verify that there aren't any un-matched fields
        foreach ($vals as $name => $val)
        {
            if (! isset($this->_fieldMap[$name]))
            {
                // /*
                Connexions::log("Connexions_View_Helper_HtmlDisplayOptions:"
                                    . "setGroupValues: "
                                    .   "Unmatched form value: "
                                    .       "'{$name}' == '{$val}'");
                // */
            }
        }

        $this->_currentGroup = $this->getBestGroupMatch();

        /*
        Connexions::log("Connexions_View_Helper_HtmlDisplayOptions:"
                            . "setGroupValues: "
                            .   "group [ {$this->_currentGroup} ], "
                            .   "final [ ". print_r($this->_fieldMap, true)
                            .                                           " ]");
        // */

        return $this;
    }

    /** @brief  Set the current value of a single field.
     *  @param  name    The field name.
     *  @param  val     The new value ( true | false | 'hide' ).
     *
     *  @return $this ( null if invalid field ).
     */
    public function setGroupValue($fieldName, $val = true)
    {
        if (isset($this->_fieldMap[ $fieldName ]))
        {
            //$ret = $this->_fieldMap[ $fieldName ]['isSet'];
            $ret = $this;
            $this->_fieldMap[ $fieldName ]['isSet'] = $val;
        }
        else
        {
            $ret = null;
        }

        return $ret;
    }

    /** @brief  Add a form field for presentation.
     *  @param  cssClass    The CSS class of this field.
     *  @param  html        The form-based HTML representing this field.
     *
     *  @return $this
     */
    public function addFormField($cssClass, $html)
    {
        $namespace = $this->_namespace;
        if (! is_array($this->_fields[$namespace]))
            $this->_fields[$namespace] = array();

        $this->_fields[$namespace][$cssClass] = $html;

        return $this;
    }

    /****************
     * Rendering
     *
     */

    /** @brief  Render the Display Options control.
     *  @param  opts    Additional rendering options:
     *                      'class' => Additional CSS class(es)
     *                      'style' => Additional CSS styling
     *
     *  @return The HTML of the control.
     */
    public function render(array $opts = array())
    {
        $namespace = $this->_namespace;
        $html      = '';

        $html .= "<div class='displayOptions "              // displayOptions {
              .              "{$namespace}-displayOptions'>"
              .   "<form "                                          // form {
              .         "class='ui-state-active ui-corner-all'>";

        // Include all form fields (added via addFormField()).
        if (@is_array($this->_fields[$namespace]))
        {
            foreach ($this->_fields[$namespace] as $cssClass => $fieldHtml)
            {
                $html .= "<div class='field {$cssClass}'>"
                      .   $fieldHtml
                      .  "</div>";
            }
        }

        // Include the display style area (if one exists)
        $html .= $this->renderDisplayStyle();

        $html .=   "<div id='buttons-global' class='buttons'>"
              .     "<button type='submit' "
              .            "class='ui-button ui-corner-all "
              .                  "ui-state-default ui-state-disabled' "
              .            "value='custom'"
              .         "disabled='true'>apply</button>"
              .    "</div>";

        $html .=  "</form>" // form }
              .  "</div>";  // displayOptions }

        return $html;
    }

    /** @brief  Render the display style control.
     *  @param  opts    Additional rendering options:
     *                      'class' => Additional CSS class(es)
     *                      'style' => Additional CSS styling
     *
     *  @return The HTML of the fieldset.
     */
    public function renderDisplayStyle(array $opts = array())
    {
        $namespace = $this->_namespace;

        $html =   "<div class='field displayStyle "     // displayStyle {
              .                     "{$namespace}-DisplayStyle'>"
              .    "<label for='{$namespace}Style'>Display</label>"
              .    "<input type='hidden' name='{$namespace}Style' "
              .          "value='{$this->_currentGroup}' />";

        $idex       = 0;
        $parts = array();
        foreach ($this->_groups as $key => $info)
        {
            $title    = $info['label'];
            $itemHtml = '';
            $cssClass = 'option';

            if ($info['isCustom'])
            {
                $itemHtml .= "<div class='{$cssClass} control "
                          .             "ui-corner-all ui-state-default"
                          .     ($key === $this->_currentGroup
                                    ? " ui-state-active"
                                    : "")
                          .                 "'>";
                $cssClass  = '';
            }

            $cssClass .= " {$namespace}Style-{$key}";
            if ($key == $this->_currentGroup)
                $cssClass .= ' option-selected';

            $itemHtml .= "<a class='{$cssClass}' "
                      .      "href='?{$namespace}Style={$key}'>{$title}</a>";

            if ($info['isCustom'])
            {
                $itemHtml .=  "<div class='ui-icon ui-icon-triangle-1-s'>"
                          .    "&nbsp;"
                          .   "</div>"
                          .  "</div>";
            }

            array_push($parts, $itemHtml);
        }

        $html .= implode("<span class='comma'>, </span>", $parts)
              .  "<br class='clear' />";


        $html .= $this->renderGroupValues(array(
                            'class' => 'custom',
                            /*
                            'style' => ($this->_groups[$this->_currentGroup]
                                                                ['isCustom']
                                            ? 'display:none;'
                                            : '')*/ ) );

        $html .=  "</div>";                     // displayStyle }

        return $html;
    }

    /** @brief  Render the form fieldset representing all display-style 
     *          options.
     *  @param  opts    Additional rendering options:
     *                      'class' => Additional CSS class(es) for fieldset
     *                      'style' => Additional CSS styling   for fieldset
     *
     *  @return The HTML of the fieldset.
     */
    public function renderGroupValues(array $opts = array())
    {
        /*
        Connexions::log("Connexions_View_Helper_HtmlDisplayOptions:"
                            . "renderGroupValues: "
                            .   "_fieldMap [ "
                            .       print_r($this->_fieldMap, true)
                            .                                           " ], "
                            .   "_definition [ "
                            .       print_r($this->_definition, true)
                            .                                           " ]");
        // */

        $html = '';
        if (! empty($this->_definition))
        {
            $html = sprintf("<fieldset class='%s%s'%s>\n",
                            $this->_namespace,
                            (isset($opts['class']) ? " {$opts['class']}"
                                                   : ''),
                            (isset($opts['style']) ? " style='{$opts['style']}'"
                                                   : ''));

            foreach ($this->_definition as $name => $val)
            {
                $html .= $this->_renderElement($name, $val);
            }

            $html .= "</fieldset>\n";
        }

        return $html;
    }


    /**************************************************************************
     * Initialization Methods
     *
     */

    /** @brief  Establish the display-options namespace.
     *  @param  namespace   A namespace string.
     *
     *  @return $this
     */
    public function setNamespace($namespace)
    {
        // /*
        Connexions::log("Connexions_View_Helper_HtmlDisplayOptions::"
                            .   "setNamespace( {$namespace} )");
        // */

        $this->_namespace = $namespace;

        if (! @isset(self::$_initialized[$namespace]))
        {
            // Include namespace-specific view information
            $view   = $this->view;
            $jQuery = $view->jQuery();

            $opts = array('namespace' => $namespace,
                          'groups'    => $this->getGroupsMap());

            /*
            Connexions::log("Connexions_View_Helper_HtmlDisplayOptions::"
                                .   "opts[ ". print_r($opts, true) ." ]");
            // */

            $jQuery->addOnLoad("init_DisplayOptions(".json_encode($opts).");");

            self::$_initialized[$namespace] = $this;
        }

        return $this;
    }

    /** @brief  Define the display style.
     *  @param  definition  An associative array of simple colon-separated
     *                      field names that define the visual heiarchy of the 
     *                      item whose display we control.  Each selector
     *                      should indicate its presentation label.
     *
     *  @return $this
     */
    public function setDefinition(array $definition)
    {
        /*
        Connexions::log('Connexions_View_Helper_HtmlDisplayOptions:'
                            . 'setDefinition: [ '
                            .   var_export($definition, true)
                            .       ' ]');
        // */

        $this->_definition = array();
        $this->_fieldMap   = array();
        foreach ($definition as $fieldName => $defLabel)
        {
            $path     =  explode(':', trim($fieldName));
            $dir      =& $this->_definition;

            foreach ($path as $comp)
            {
                if (! is_array($dir[$comp]))
                    $dir[$comp] = array();

                $dir =& $dir[$comp];
            }

            $dir['fieldName'] = $fieldName;
            $dir['selector']  = '.'. implode(' .', $path);

            if (is_array($defLabel))
            {
                /* Additional details for this item:
                 *      'label'
                 *      'pre'
                 *      'post'
                 *
                 *      'containerPre'
                 *      'containerPost'
                 *      'containerCss'
                 */
                $dir = array_merge($dir, $defLabel);
            }
            else
            {
                $dir['label']    = $defLabel;
            }

            if (! isset($dir['isSet']))
                $dir['isSet'] = true;

            // Validate the type, defaulting to 'checkbox'
            //if (! isset($dir['type']))  $dir['type'] = 'checkbox';

            switch (@strtolower($dir['type']))
            {
            case 'radio':
                $dir['type'] = 'radio';
                break;

            case 'checkbox':
            default:
                $dir['type'] = 'checkbox';
                break;
            }

            /*
            Connexions::log('Connexions::View_Helper_HtmlDisplayOptions:"
                                . 'setDefinition: '
                                .   'field [ '. $dir['fieldName'] .' ]');
            // */

            $this->_fieldMap[ $dir['fieldName'] ] =& $dir;
        }

        /*
        Connexions::log('Connexions::View_Helper_HtmlDisplayOptions:'
                            . 'setDefinition: fieldMap [ '
                            .   var_export($this->_fieldMap, true)
                            .       ' ]');
        // */

        return $this;
    }

    /** @brief  Retrieve the current definition.
     *
     *  @return The current definition.
     */
    public function getDefinition()
    {
        return $this->_definition;
    }

    /** @brief  Set multiple predefined groups of options.
     *  @param  options     An associative array of group name / options array.
     *
     *  @return $this
     */
    public function setGroups(array $options)
    {
        /*
        Connexions::log("Connexions_View_Helper_HtmlDisplayOptions::"
                            .   "setGroups: "
                            .       "options[ ". print_r($options, true) ." ]");
        // */

        // Define groups, ensuring that no more than 1 is defined as 'isCustom'
        $hasCustom = false;
        foreach ($options as $name => $itemOpts)
        {
            $this->defineGroup($name, $itemOpts);

            if ($this->_groups[$name]['isCustom'])
            {
                if ($hasCustom)
                {
                    // We already have 'custom' set...
                    /*
                    Connexions::log(
                            "Connexions_View_Helper_HtmlDisplayOptions::"
                            .   "setGroups: "
                            .       "disallow second 'custom' group");
                    // */
                    $this->_groups[$name]['isCustom'] = false;
                }

                $hasCustom = true;
            }
        }

        if ( (! empty($this->_definition)) && (! $hasCustom) )
        {
            /* We have definitions, but no group had the 'isCustom' flag.
             *
             * Create a new 'custom' group that indicates ALL fields
             * (could also indicate NO fields -- 'options' => array() ).
             */
            $opts = array('label'    => 'Custom',
                          'isCustom' => true,
                          'options'  => array_keys($this->_fieldMap));

            // /*
            Connexions::log(
                    "Connexions_View_Helper_HtmlDisplayOptions::"
                    .   "setGroups: No 'custom' option defined, create an "
                    .       "additional, custom group [ "
                    .           print_r($opts, true) . " ]");
            // */

            $this->defineGroup('custom', $opts);
        }

        return $this;
    }

    /** @brief  Set a predefined group of options with a specific name.
     *  @param  name    The name of the group.
     *  @param  options The group definition as either a string or array:
     *                      string  a comma-separated string of field names;
     *                              each must match one of the options in the 
     *                              definition of this display style.  In this 
     *                              case, the name used for this group will be 
     *                              the lower-case version of the option name, 
     *                              while the label will be the ucfirst() 
     *                              version of the name;
     *                      array   an associative array defining this group, 
     *                              containing:
     *                                  'label'     the label for this group;
     *                                  'isCustom'  is this the item that
     *                                              represents ANY value
     *                                              selection?
     *                                              ( true | [false] );
     *                                  'options'   eiter a comma-separated 
     *                                              string of field names OR
     *                                              an array of field names.
     *
     *  @return $this
     */
    public function defineGroup($name, $options)
    {
        $name = strtolower($name);

        /*
        Connexions::log("Connexions_View_Helper_HtmlDisplayOptions::"
                            .   "defineGroup: '{$name}', in:"
                            .       "[ ". print_r($options, true) ." ]");
        // */

        if (! is_array($options))
            $options = preg_split('/\s*,\s*/', $options);

        // Figure out the label for this group
        if (isset($options['label']))
        {
            $label = $options['label'];
        }
        else
        {
            $label = ucfirst($name);
        }

        $isCustom = (isset($options['isCustom']) && $options['isCustom']);


        if (isset($options['options']))
        {
            $options = $options['options'];
        }

        // Generate the group definition
        $group = array(
            'label'     => $label,
            'isCustom'  => $isCustom,
            'options'   => array()
        );

        foreach ($options as $fieldName)
        {
            if (! isset($this->_fieldMap[$fieldName]))
            {
                // /*
                Connexions::log("Connexions_View_Helper_HtmlDisplayOptions:"
                                    . "defineGroup: "
                                    .   "Unmatched field[ {$fieldName} ]");
                // */
                continue;
            }

            $group['options'][$fieldName] =& $this->_fieldMap[$fieldName];
        }

        /*
        Connexions::log("Connexions_View_Helper_HtmlDisplayOptions::"
                            .   "defineGroup: '{$name}', final:"
                            .       "[ ". print_r($group, true) ." ]");
        // */
        $this->_groups[$name] = $group;

        return $this;
    }

    /*************************************************************************
     * Protected Helpers
     *
     */

    /** @brief  Render a single element.
     *
     *  @return The HTML of the element.
     */
    protected function _renderElement($name, &$val, $indent = 1)
    {
        $inStr      = str_repeat(' ', $indent);
        $isField    = (is_array($val) && (isset($val['label'])));
        $hasClass   = (is_array($val) && (isset($val['containerCss'])));
        $hasTitle   = (is_array($val) && (isset($val['containerTitle'])));
        $el         = (is_array($val) && (isset($val['containerEl']))
                            ? $val['containerEl']
                            : 'div');
        $html       = $inStr ."<${el} class='{$name}"
                    .                        ($isField
                                                ? ' field'
                                                : '')
                    .                        ($hasClass
                                                ? " {$val['containerCss']}"
                                                : '') ."'"
                    .               ($hasTitle
                                        ? " title='{$val['containerTitle']}'"
                                        : '')
                    .              ">\n";

        if (is_array($val) && isset($val['containerPre']))
            $html = $inStr . $val['containerPre'] ."\n". $html;

        if (is_array($val))
        {
            $addIndent = 1;
            if (isset($val['label']))
            {
                // This is a form-field
                $addIndent = 1;
                $ns        = "{$this->_namespace}StyleCustom";
                $fName     = "{$ns}[{$val['fieldName']}]";
                $fId       = "{$ns}-{$name}";

                /*
                Connexions::log("Connexions_View_Helper_HtmlDisplayOptions:"
                            . "_renderElement: "
                            .   "field [ ". print_r($val, true) ." ]");
                // */

                if (isset($val['extraPre']))
                    $html .= $inStr .' '. $val['extraPre'] ."\n";

                $html .= $inStr." <input type='{$val['type']}' "
                      .                 "name='{$fName}' "
                      .                   "id='{$fId}'"
                      .                     ($val['isSet'] === true
                                                ? " checked='true'"
                                                : "") ." />\n"
                      .  $inStr." <label for='{$fId}'>"
                      .           $val['label']
                      .          "</label>\n";

                if (isset($val['extraPost']))
                    $html .= $inStr .' '. $val['extraPost'] ."\n";
            }

            foreach ($val as $cName => $cVal)
            {
                if (is_array($cVal))
                    $html .= $this->_renderElement($cName, $cVal,
                                                   $indent + $addIndent);
            }
        }

        $html .= $inStr ."</{$el}>\n";

        if (is_array($val) && isset($val['containerPost']))
            $html .= $inStr . $val['containerPost'] ."\n";

        return $html;
    }
}
