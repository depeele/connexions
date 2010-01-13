<?php
/** @file
 *
 *  Model for the Tag table.
 *
 */

class Model_Tag extends Connexions_Model_Cached
                implements  Zend_Tag_Taggable
{
    /*************************************************************************
     * Connexions_Model - static, identity members
     *
     */
    public static   $table  = 'tag';
                              // order 'keys' by most used
    public static   $keys   = array('tagId', 'tag');
    public static   $model  = array('tagId' => 'auto',
                                    'tag'   => 'string'
    );

    protected       $_params    = array();

    /*************************************************************************/

    /** @brief  Set a value in this record and mark it dirty.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  Override to ensure that 'tag' is normalized.
     *
     *  @return true | false
     */
    public function __set($name, $value)
    {
        if ($name === 'tag')
            $value = strtolower($value);

        return parent::__set($name, $value);
    }

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        if ($this->isValid() && (! @empty($this->_record['tag'])))
            return $this->_record['tag'];

        return parent::__toString();
    }

    /*************************************************************************
     * Zend_Tag_Taggable Interface
     *
     */
    public function getParam($name)
    {
        // weightValue, url, selected
        $val = (@isset($this->_params[$name])
                    ? $this->_params[$name]
                    : null);
        return $val;
    }

    public function setParam($name, $value)
    {
        // weightValue, url, selected
        $this->_params[$name] = $value;
    }

    public function getTitle()
    {
        $title = (String)($this->tag);

        return $title;
    }

    public function getWeight()
    {
        $weight = (Float)($this->userItemCount);

        return $weight;
    }

    /*************************************************************************
     * Static methods
     *
     */

    /** @brief  Given a set of tags, retrieve the tag identifier for each.
     *  @param  tags    The set of tags as a comma-separated string or array.
     *
     *  @return An array
     *              { valid:   { <tagName>: <tag id>, ... },
     *                invalid: [invalid tag names, ...]
     *              }
     */
    public static function ids($tags)
    {
        if (@empty($tags))
            return null;

        if (! @is_array($tags))
            $tags = preg_split('/\s*,\s*/', $tags);

        $db     = Connexions::getDb();
        $select = $db->select()
                     ->from(self::$table)
                     ->where('tag IN (?)', $tags);
        $recs   = $select->query()->fetchAll();

        /* Convert the returned array of records to a simple array of
         *   tagName => tagId
         *
         * This will be used for the list of valid tags.
         */
        $valid  = array();
        foreach ($recs as $idex => $row)
        {
            $valid[$row['tag']] = $row['tagId'];
        }

        $invalid = array();
        if (count($valid) < count($tags))
        {
            // Include invalid entries for those tags that are invalid
            foreach ($tags as $tag)
            {
                if (! @isset($valid[$tag]))
                    $invalid[] = $tag;
            }
        }

        return array('valid' => $valid, 'invalid' => $invalid);
    }

    /*************************************************************************
     * Connexions_Model - abstract static method implementations
     *
     */

    /** @brief  Retrieve all records and return an array of instances.
     *  @param  id      The record identifier.
     *
     *  @return A new instance (false if no matching user).
     */
    public static function find($id)
    {
        return parent::find(__CLASS__, $id);
    }

    /*************************************************************************
     * Connexions_Model_Cached - abstract static method implementations
     *
     */

    /** @brief  Given a record identifier, generate an unique instance
     *          identifier.
     *  @param  id      The record identifier.
     *
     *  @return A unique instance identifier string.
     */
    protected static function _instanceId($id)
    {
        return __CLASS__ .'_'.  (! @empty($id['tagId'])
                                    ?  $id['tagId']
                                    : 'generic');
    }
}
