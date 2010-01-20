<?php
/** @file
 *
 *  Given a comma-separated array of item identifiers (e.g. tags, user names),
 *  parse them, assembling information about those that are valid and invalid.
 */
class Connexions_Set_Info
{
    protected $_reqStr      = null;     // Original request string
    protected $_info        = null;     /* The associative array of item
                                         * information returned by
                                         * $itemClass::ids().
                                         */
    protected $_itemClass   = null;     /* The name of the class for individual
                                         * items.
                                         */


    /** @brief  Given a comma-separated request string, generate validity
     *          information about the items.
     *  @param  reqStr      The comma-separated string of items (or an array).
     *  @param  itemClass   The Connexions_Model class that represents the
     *                      items in the list (e.g. Model_Tag, Model_User),
     *                      which implements the 'ids()' method to parse
     *                      'reqStr' into lists of valid and invalid items.
     */
    public function __construct($reqStr, $itemClass = 'Model_Tag')
    {
        $this->_itemClass = $itemClass;

        if (@empty($reqStr))
            return;

        if (! @is_string($reqStr))
            $reqStr = (String)$reqStr;

        $this->_reqStr =  $reqStr;

        // Retrieve item information
        $this->_info    = $itemClass::ids($reqStr);

        /*
        Connexions::log("Connexions_Set_Info:: "
                        . "reqStr[ {$this->reqStr} ], "
                        . "validItems[ {$this->validItems} ], "
                        . "validIds[ ".implode(', ',$this->validIds)." ], "
                        . "invalidItems[ {$this->invalidItems} ]");
        // */
    }

    /** @brief  Were valid items included in the request items?
     *
     *  @return true | false
     */
    public function hasValidItems()
    {
        return (($this->_info !== null) && (! empty($this->_info['valid']))
                    ? true
                    : false);
    }

    /** @brief  Were invalid items included in the request items?
     *
     *  @return true | false
     */
    public function hasInvalidItems()
    {
        return (($this->_info !== null) && (! empty($this->_info['invalid']))
                    ? true
                    : false);
    }

    /** @brief  Given an item name, is it in the list of valid items?
     *  @param  item    The item name.
     *
     *  @return true | false
     */
    public function isValidItem($item)
    {
        return (($this->_info != null) &&
                (@isset($this->_info['valid'][$item]))
                    ? true
                    : false);
    }

    public function __toString()
    {
        return ($this->_reqStr === null
                    ? ''
                    : $this->_reqStr);
    }

    /** @brief  Get a value.
     *  @param  name    The name of the value.
     *
     *  @return The value (or null if invalid).
     */
    public function __get($name)
    {
        switch ($name)
        {
        case 'itemClass':
            $val = $this->_itemClass;
            break;

        case 'reqStr':      // The original request string
            $val = ($this->_reqStr === null
                        ?  ''
                        : $this->_reqStr);
            break;

        case 'valid':       /* An associative array of valid items:
                             *  { <item_name>: <item_id>, ...}
                             */
            if (($this->_info !== null) && is_array($this->_info['valid']))
                $val = $this->_info['valid'];
            else
                $val = array();
            break;

        case 'validIds':    // A simple array of the ids of all valid items.
            /* If there was a non-empty request string, but there are no valid
             * items, return an array with a single, invalid identifier.
             */
            if ($this->_info !== null)
            {
                if ((! empty($this->_reqStr)) && empty($this->_info['valid']) )
                {
                    /* There were provided request items, but NONE of them are
                     * valid.  Return an array with a single, invalid item
                     * identifier to ensure that we don't match ANY valid user
                     * identifiers.
                     */
                    $val = array(-1);
                }
                else if (is_array($this->_info['valid']))
                {
                    $val = array_values($this->_info['valid']);
                }
            }
            else
            {
                $val = array();
            }
            break;

        case 'validList':   // A simple array of the valid items.
            if (($this->_info !== null) && is_array($this->_info['valid']))
            {
                $val = array_keys($this->_info['valid']);
            }
            else
            {
                $val = array();
            }
            break;

        case 'validItems':  // A comma-separated string of valid items
            if ($this->_info !== null)
                $val = @implode(',', array_keys($this->_info['valid']) );
            else
                $val = '';
            break;

        case 'invalid':
        case 'invalidList': // A simple array of all invalid items
            if (($this->_info !== null) && is_array($this->_info['invalid']))
                $val = $this->_info['invalid'];
            else
                $val = array();
            break;

        case 'invalidItems':// A comma-separated string of invalid items
            if ($this->_info !== null)
                $val = @implode(',', $this->_info['invalid']);
            else
                $val = '';
            break;

        default:
            $val = null;
            break;
        }

        /*
        Connexions::log("Connexions_Set_Info::get({$name}): [ ".
                            print_r($val, true) ." ]");
        // */
        return $val;
    }
}
