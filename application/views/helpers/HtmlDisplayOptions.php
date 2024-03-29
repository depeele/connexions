<?php
/** @file
 *
 *  A view helper to standardize display options presentation.
 *
 *  Display Options MAY contain a display style area comprised of a fieldset of
 *  option checkboxes along with one or more pre-defined option groups.
 *
 *  Available display style options are defined via an associative array passed 
 *  to setDefinition().  The keys are colon-separated strings that define the
 *  field name as well as the CSS heirarchy to use when rendering.   The values
 *  can be any combination of simple strings and/or arrays:
 *      - A simple string value defines the label to be presented for the 
 *        identified option;
 *
 *      - An array value defines multiple properties, primarily form-related, 
 *        for the option.  These MUST include 'label' and MAY include any of 
 *        the following additional properties:
 *          'label'         REQUIRED -- the label for the form field 
 *                          representing this item;
 *          'labelCss'      any specific CSS class to be used for the DOM
 *                          label;
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
 *          'item:stats:count'          => array(
 *              'label'         => 'user count',
 *              'containerCss'  => 'ui-corner-bottom'
 *          ),
 *          'item:stats:rating:stars'   => 'rating stars'
 *      );
 *
 *
 *  Pre-defined groups can be specified via either a string or array:
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
 *
 *
 *  REQUIRES:
 *      application/view/scripts/displayOptions.phtml
 */
class View_Helper_HtmlDisplayOptions extends Zend_View_Helper_Abstract
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
                    "View_Helper_HtmlDisplayOptions::"
                    . "htmlDisplayOptions(): identified  "
                    . "namespace [ {$namespace} ]");
            // */

            if ( (! empty($this->_namespace)) &&
                 ($namespace !== $this->_namespace) )
            {
                // Different namespace!
                if (@isset(self::$_initialized[$namespace]))
                {
                    // /*
                    Connexions::log("View_Helper_HtmlDisplayOptions::"
                                    . "htmlDisplayOptions(): auto-switch "
                                    . "namespaces: '%s' -> '%s'",
                                    $this->_namespace,
                                    $namespace);
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
                    // /*
                    Connexions::log(
                            "View_Helper_HtmlDisplayOptions::"
                            . "htmlDisplayOptions(): new namespace: "
                            .   " old[ %s ], config[ %s ]",
                            $this->_namespace,
                            Connexions::varExport($config));
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

        /*
        Connexions::log('View_Helper_HtmlDisplayOptions:'
                            . 'fieldMap [ %s ]',
                            var_export($this->_fieldMap, true));
        Connexions::log('View_Helper_HtmlDisplayOptions:'
                            . 'definition [ %s ]',
                            var_export($this->_definition, true));
        Connexions::log('View_Helper_HtmlDisplayOptions:'
                            . 'groups [ %s ]',
                            var_export($this->_groups, true));
        // */

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

    public function __get($name)
    {
        $val = null;
        switch ($name)
        {
        case 'namespace':
            $val = $this->_namespace;
            break;

        case 'definition':
            $val =& $this->_definition;
            break;

        case 'fields':
            $val =& $this->_fields[$this->_namespace];
            break;

        case 'groups':
            $val =& $this->_groups;
            break;

        default:
            $val = parent::__get($name);
            break;
        }

        return $val;
    }

    /** @brief  Retrieve the primary configuration information.
     *
     *  @return An array containing:
     *              namespace
     *              groups
     *              definition
     */
    public function getConfig()
    {
        $config = array(
            'namespace'  => $this->getNamespace(),
            'groups'     => $this->getGroups(),
            'definition' => $this->getDefinition(),
        );

        return $config;
    }

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
        // First, how many fields are set
        $fieldCount = 0;
        foreach ($this->_fieldMap as $name => &$def)
        {
            if ($def['isSet'] === true)
            {
                $fieldCount++;
            }
        }

        $bestMatch  = $this->_currentGroup;
        $bestCount  = 0;
        $exactMatch = false;
        $custom     = null;
        foreach ($this->_groups as $name => $group)
        {
            /*
            Connexions::log("View_Helper_HtmlDisplayOptions::"
                            .   "getBestGroupMatch(): "
                            .   "checking group[ %s ] best thus far[ %s ]...",
                            $name, $bestMatch);
            // */

            if ($group['isCustom'])
            {
                $custom = $name;
                continue;
            }

            $isMatch    = true;
            $matchCount = 0;
            foreach ($group['options'] as $fieldName => $data)
            {
                if ($data['isSet'] !== true)
                {
                    /*
                    Connexions::log("View_Helper_HtmlDisplayOptions::"
                                    .   "getBestGroupMatch(): "
                                    .   "group[ %s ] MISSED due to missing "
                                    .   "field [ %s ]",
                                    $name, $fieldName);
                    // */

                    $isMatch = false;
                    break;
                }

                $matchCount++;
            }

            if ($isMatch)
            {
                if ($matchCount > $bestCount)
                {
                    $bestMatch  = $name;
                    $bestCount  = $matchCount;
                    $exactMatch = (($matchCount == count($group['options'])) &&
                                   ($matchCount == $fieldCount));
                }
            }
        }

        /*
        Connexions::log("View_Helper_HtmlDisplayOptions::getBestGroupMatch(): "
                        .   "best match[ %s ], is %sexact",
                        $bestMatch, ($exactMatch ? '' : 'NOT '));
        // */

        if ( ($custom !== null) && (! $exactMatch) )
        {
            // Revert to custom since there are no exact matches.
            $bestMatch = $custom;

            /*
            Connexions::log("View_Helper_HtmlDisplayOptions::"
                            . "getBestGroupMatch(): "
                            . "no exact match - revert to custom group [ %s ]",
                            $bestMatch);
            // */
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

    public function __set($name, $val)
    {
        switch ($name)
        {
        case 'namespace':
            $this->setNamespace($val);
            break;

        case 'definition':
            if (! is_array($val))
                throw new Exception("'definition' MUST be an array");

            $this->setDefinition($val);
            break;

        case 'groups':
            if (! is_array($val))
                throw new Exception("'groups' MUST be an array");

            $this->setGroups($val);
            break;

        default:
            $val = parent::__get($name);
            break;
        }
    }

    /** @brief  Set field values by an established group.
     *  @param  groupName       The name of the desired group.
     *  @param  customValues    If 'groupName' identifies the "custom" group,
     *                          the set of values may be provided here.
     *
     *
     *  @return $this (null if 'groupName' is not a valid group).
     */
    public function setGroup($groupName, $customValues = null)
    {
        if (! is_array($customValues))  { $customValues = null; }

        /*
        Connexions::log("View_Helper_HtmlDisplayOptions::setGroup( %s ): "
                        .   "is %svalid; customValues[ %s ]",
                        $groupName,
                        (isset($this->_groups[$groupName]) ? '' : 'NOT '),
                        print_r($customValues, true));
        // */

        if (! isset($this->_groups[$groupName]))
        {
            //throw new Exception("setGroup() INVALID group '{$groupName}'");
            return null;
        }

        $this->_currentGroup = $groupName;

        // First, unset all current values
        foreach ($this->_fieldMap as $name => &$def)
        {
            $def['isSet'] = false;
        }

        // Now, set all values of the named group.
        $setFields = array();

        if (($this->_groups[$groupName]['isCustom']) &&
            (! empty($customValues)))
        {
            foreach ($customValues as $fieldName => $value)
            {
                if (! isset($this->_fieldMap[$fieldName]))
                    continue;

                $this->_fieldMap[$fieldName]['isSet'] = true;
                array_push($setFields, $fieldName);
            }
        }
        else
        {
            foreach ($this->_groups[$groupName]['options'] as
                                                    $fieldName => $data)
            {
                $this->_fieldMap[$fieldName]['isSet'] = true;
                array_push($setFields, $fieldName);
            }
        }

        /*
        Connexions::log("View_Helper_HtmlDisplayOptions::setGroup( %s ): "
                        .   "set fields[ %s ]",
                        $groupName,
                        implode(', ', $setFields));
        // */

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
        Connexions::log("View_Helper_HtmlDisplayOptions:"
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
                /*
                Connexions::log("View_Helper_HtmlDisplayOptions:"
                                    . "setGroupValues: "
                                    .   "Unmatched form value: "
                                    .       "'{$name}' == '{$val}'");
                // */
            }
        }

        $this->_currentGroup = $this->getBestGroupMatch();

        /*
        Connexions::log("View_Helper_HtmlDisplayOptions:"
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
        $res = $this->view->partial('displayOptions.phtml',
                                     array(
                                         'helper'     =>  $this,
                                     ));
        return $res;
    }

    /** @brief  Render a single element.
     *
     *  Helper method for the displayOptions.phtml view script.
     *
     *
     *  @return The HTML of the element.
     */
    public function renderOptionGroupsElement($name, &$val, $indent = 1)
    {
        $inStr      = str_repeat(' ', $indent);
        $isOption   = (is_array($val) && (isset($val['label'])));
        $hasClass   = (is_array($val) && (isset($val['containerCss'])));
        $hasTitle   = (is_array($val) && (isset($val['containerTitle'])));
        $el         = (is_array($val) && (isset($val['containerEl']))
                            ? $val['containerEl']
                            : 'div');
        $html       = $inStr ."<${el} class='{$name}"
                    .                        ($isOption
                                                ? ' option'
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
                $ns        = "{$this->_namespace}OptionGroups_option";
                $fId       = "{$ns}-{$name}";
                $fName     = "{$ns}[{$val['fieldName']}]";

                /* Assemble the list of groups this field belongs to and add
                 * them as 'inGroup-<name>' css classes.
                 */
                $cssGroups = '';
                foreach ($val['inGroup'] as $groupName)
                {
                    $cssGroups .= " inGroup-{$groupName}";
                }

                /*
                Connexions::log("View_Helper_HtmlDisplayOptions:"
                            . "renderOptionGroupsElement: "
                            .   "field [ ". print_r($val, true) ." ]");
                // */

                if (isset($val['extraPre']))
                    $html .= $inStr .' '. $val['extraPre'] ."\n";

                $html .= $inStr." <input type='checkbox' "
                      .                "class='{$cssGroups}' "
                      .                   "id='{$fId}'"
                      .                 "name='{$fName}' "
                      .                     ($val['isSet'] === true
                                                ? " checked='checked'"
                                                : "") ." />\n"
                      .  $inStr." <label for='{$fId}'"
                      .     (isset($val['labelCss'])
                              ? " class='{$val['labelCss']}'"
                              : ''). ">"
                      .            $val['label']
                      .          "</label>\n";

                if (isset($val['extraPost']))
                    $html .= $inStr .' '. $val['extraPost'] ."\n";
            }

            foreach ($val as $cName => $cVal)
            {
                if ( ($cName !== 'inGroup') && is_array($cVal))
                    $html .= $this->renderOptionGroupsElement($cName, $cVal,
                                                   $indent + $addIndent);
            }
        }

        $html .= $inStr ."</{$el}>\n";

        if (is_array($val) && isset($val['containerPost']))
            $html .= $inStr . $val['containerPost'] ."\n";

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
        /*
        Connexions::log("View_Helper_HtmlDisplayOptions::"
                            .   "setNamespace( {$namespace} )");
        // */

        $this->_namespace = $namespace;

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
        Connexions::log('View_Helper_HtmlDisplayOptions:'
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
            $dir['inGroup']   = array();

            if (is_array($defLabel))
            {
                /* Additional details for this item:
                 *      'label'
                 *      'pre'
                 *      'post'
                 *
                 *      'labelCss'
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

            /*
            Connexions::log('View_Helper_HtmlDisplayOptions:"
                                . 'setDefinition: '
                                .   'field [ '. $dir['fieldName'] .' ]');
            // */

            $this->_fieldMap[ $dir['fieldName'] ] =& $dir;
        }

        /*
        Connexions::log('View_Helper_HtmlDisplayOptions:'
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
        Connexions::log("View_Helper_HtmlDisplayOptions::"
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
                            "View_Helper_HtmlDisplayOptions::"
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

            /*
            Connexions::log(
                    "View_Helper_HtmlDisplayOptions::"
                    .   "setGroups: No 'custom' option defined, create an "
                    .       "additional, custom group [ "
                    .           print_r($opts, true) . " ]");
            // */

            $this->defineGroup('custom', $opts);
        }

        return $this;
    }

    /** @brief  Retrieve the current groups.
     *
     *  @return The current groups.
     */
    public function getGroups()
    {
        return $this->_groups;
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
        Connexions::log("View_Helper_HtmlDisplayOptions::"
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
                Connexions::log("View_Helper_HtmlDisplayOptions:"
                                    . "defineGroup: "
                                    .   "Unmatched field[ {$fieldName} ]");
                // */
                continue;
            }

            // Add this group to the list of groups this field belongs to
            array_push($this->_fieldMap[ $fieldName ]['inGroup'], $name);

            // Associate this field with the 'options' of the group.
            $group['options'][$fieldName] =& $this->_fieldMap[$fieldName];
        }

        /*
        Connexions::log("View_Helper_HtmlDisplayOptions::"
                            .   "defineGroup: '{$name}', final:"
                            .       "[ ". print_r($group, true) ." ]");
        // */
        $this->_groups[$name] = $group;

        return $this;
    }
}
