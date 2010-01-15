<?php
/** @file
 *
 *  Given a comma-separated array of tags, parse those tags to assemble
 *  information about those that are valid and invalid.
 */
class Connexions_TagInfo
{
    protected $_reqTags     = null;     // Original request string
    protected $_info        = null;     /* The associative array of tag
                                         * information returned by
                                         * Model_Tag::ids().
                                         */


    public function __construct($reqTags)
    {
        if (@empty($reqTags))
            return;

        if (! @is_string($reqTags))
            $reqTags = (String)$reqTags;

        $this->_reqTags =  $reqTags;

        // Retrieve tag information
        $this->_info    = Model_Tag::ids($reqTags);

        /*
        Connexions::log("Connexions_TagInfo:: "
                        . "reqTags[ {$this->reqTags} ], "
                        . "validTags[ {$this->validTags} ], "
                        . "validIds[ ".implode(', ',$this->validIds)." ], "
                        . "invalidTags[ {$this->invalidTags} ]");
        // */

        return $ret;
    }

    /** @brief  Were valid tags included in the request tags?
     *
     *  @return true | false
     */
    public function hasValidTags()
    {
        return (($this->_info !== null) && (! empty($this->_info['valid']))
                    ? true
                    : false);
    }

    /** @brief  Were invalid tags included in the request tags?
     *
     *  @return true | false
     */
    public function hasInvalidTags()
    {
        return (($this->_info !== null) && (! empty($this->_info['invalid']))
                    ? true
                    : false);
    }

    /** @brief  Given a tag name, is it in the list of v alid tags?
     *  @param  tag     The tag name.
     *
     *  @return true | false
     */
    public function isValidTag($tag)
    {
        return (($this->_info != null) && (@isset($this->_info['valid'][$tag]))
                    ? true
                    : false);
    }

    public function __toString()
    {
        return ($this->_reqTags === null
                    ? ''
                    : $this->_reqTags);
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
        case 'reqTags':     // The original request string
            $val = ($this->_reqTags === null
                        ?  ''
                        : $this->_reqTags);
            break;

        case 'valid':       /* An associative array of valid tags:
                             *  { <tag_name>: <tag_id>, ...}
                             */
            if (($this->_info !== null) && is_array($this->_info['valid']))
                $val = $this->_info['valid'];
            else
                $val = array();
            break;

        case 'validIds':    // A simple array of the ids of all valid tags.
            /* If there was a non-empty request string, but there are no valid
             * tags, return an array with a single, invalid identifier.
             */
            if ($this->_info !== null)
            {
                if ((! empty($this->_reqTags)) && empty($this->_info['valid']) )
                {
                    /* There were provided request tags, but NONE of them are
                     * valid.  Return an array with a single, invalid tag
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

        case 'validList':   // A simple array of the valid tags.
            if (($this->_info !== null) && is_array($this->_info['valid']))
            {
                $val = array_keys($this->_info['valid']);
            }
            else
            {
                $val = array();
            }
            break;

        case 'validTags':   // A comma-separated string of valid tags
            if ($this->_info !== null)
                $val = @implode(',', array_keys($this->_info['valid']) );
            else
                $val = '';
            break;

        case 'invalid':
        case 'invalidList': // A simple array of all invalid tags
            if (($this->_info !== null) && is_array($this->_info['invalid']))
                $val = $this->_info['invalid'];
            else
                $val = array();
            break;

        case 'invalidTags': // A comma-separated string of invalid tags
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
        Connexions::log("Connexinos_TagInfo::get({$name}): [ ".
                            print_r($val, true) ." ]");
        // */
        return $val;
    }
}
