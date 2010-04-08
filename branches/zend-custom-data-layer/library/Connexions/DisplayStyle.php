<?php
/** @file
 *
 *  A view helper to standardize display style presentation.
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
 *                      'options'   eiter a comma-separated string of field 
 *                                  names OR an array of field names.
 */
class Connexions_DisplayStyle
{
    protected   $_namespace     = '';
    protected   $_definition    = array();
    protected   $_fieldMap      = array();
    protected   $_groups        = array();

    /** @brief  Create a new instance.
     *  @param  config  An associative array of configuration information that
     *                  may include:
     *                      'namespace'     => <namespace string>, passed to 
     *                                          setNamespace()  method;
     *                      'definition'    => <definition array>, passed to
     *                                          setDefinition() method;
     *                      'groups'        => <group name array>, passed to
     *                                          setGroups() method;
     */
    public function __construct($config = array())
    {
        /*
        Connexions::log("Connexions_DisplayStyle: config[ "
                            . var_export($config, true)
                            .   " ]");
        // */

        if (isset($config['namespace']))
            $this->setNamespace($config['namespace']);

        if (is_array($config['definition']))
            $this->setDefinition($config['definition']);

        if (is_array($config['groups']))
            $this->setGroups($config['groups']);
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
    public function getValue($fieldName)
    {
        if (isset($this->_fieldMap[ $fieldName ]))
            $val = $this->_fieldMap[ $fieldName ]['isSet'];
        else
            $val = null;

        return $val;
    }

    /** @brief  Get the group that matches the current set of values, 
     *          established via setValues().
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
    public function getValues()
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
    public function setValuesByGroup($groupName)
    {
        if (! isset($this->_groups[$groupName]))
            return null;

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
    public function setValues($vals = array())
    {
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
                Connexions::log("Connexions_DisplayStyle:setValues: "
                                    . "Unmatched form value: "
                                    . "'{$name}' == '{$val}'");
                // */
            }
        }
    }

    /** @brief  Set the current value of a single field.
     *  @param  name    The field name.
     *  @param  val     The new value ( true | false | 'hide' ).
     *
     *  @return $self ( null if invalid field ).
     */
    public function setValue($fieldName, $val = true)
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

    /****************
     * Rendering
     *
     */

    /** @brief  Render the form fieldset representing all display-style 
     *          options.
     *  @param  opts    Additional rendering options:
     *                      'class' => Additional CSS class(es) for fieldset
     *                      'style' => Additional CSS styling   for fieldset
     *
     *  @return The HTML of the fieldset.
     */
    public function renderFieldset(array $opts = array())
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
        Connexions::log('Connexions::DisplayStyle:setDefinition: [ '
                            . var_export($definition, true)
                            .   ' ]');
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
            Connexions::log('Connexions::DisplayStyle:setDefinition: '
                                . 'field [ '. $dir['fieldName'] .' ]');
            // */

            $this->_fieldMap[ $dir['fieldName'] ] =& $dir;
        }

        /*
        Connexions::log('Connexions::DisplayStyle:setDefinition: fieldMap [ '
                            . var_export($this->_fieldMap, true)
                            .   ' ]');
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
        foreach ($options as $name => $items)
        {
            $this->setGroup($name, $items);
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
     *                                  'options'   eiter a comma-separated 
     *                                              string of field names OR
     *                                              an array of field names.
     *
     *  @return $this
     */
    public function setGroup($name, $options)
    {
        $name = strtolower($name);
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

        if (isset($options['options']))
        {
            $options = $options['options'];
        }

        // Generate the group definition
        $group = array(
            'label'     => $label,
            'options'   => array()
        );

        foreach ($options as $fieldName)
        {
            if (! isset($this->_fieldMap[$fieldName]))
            {
                // /*
                Connexions::log("Connexions_DisplayStyle:setGroup: "
                                    . "Unmatched field[ {$fieldName} ]");
                // */
                continue;
            }

            $group['options'][$fieldName] =& $this->_fieldMap[$fieldName];
        }

        $this->_groups[$name] = $group;

        return $this;
    }

    /** @brief  Get a predefined group of options with the specified name.
     *  @param  name    The name of the group.
     *
     *  @return The options for the group (or null if not a valid group).
     */
    public function getGroup($name)
    {
        $ret = (isset($this->_groups[$name])
                    ? $this->_groups[$name]
                    : null);

        return $ret;
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
