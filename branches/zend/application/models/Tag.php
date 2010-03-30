<?php
/** @file
 *
 *  Model for the Tag table.
 *
 *  Note: Since we pull tag sets with varying weight measuremennts so, at least
 *        for now, DO NOT change the base class from Connexions_Model to
 *        Connexions_Model_Cached to make it cacheable.
 */

class Model_Tag extends Connexions_Model
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

    /** @brief  Return an associative array representing this tag.
     *  @param  public  Include only "public" information?
     *
     *  @return An associaitve array.
     */
    public function toArray($public = true)
    {
        $ret = $this->_record;
        if ($public)
        {
            // Remove non-public information
            $ret = $ret['tag'];
        }

        return $ret;
    }

    /*************************************************************************
     * Zend_Tag_Taggable Interface
     *
     */
    protected       $_params    = array();

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
        if (@isset($this->weight))
            $weight = (Float)($this->weight);
        else
            $weight = (Float)($this->userItemCount);

        /*
        Connexions::log(
                sprintf("Model_Tag::getWeight: "
                            . "weight[ %s ], "
                            . "userItemCount[ %s ] == [ %s ]",
                                $this->weight,
                                $this->userItemCount,
                                $weight));
        // */

        return $weight;
    }

    /*************************************************************************
     * Static methods
     *
     */

    /** @brief  Given a set of tags, retrieve the tag identifier for each.
     *  @param  tags    The set of tags as a comma-separated string or array.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     *  @return An array
     *              { valid:   { <tagName>: <tag id>, ... },
     *                invalid: [invalid tag names, ...]
     *              }
     */
    public static function ids($tags, $db = null)
    {
        if (@empty($tags))
            return null;

        if (! @is_array($tags))
            $tags = preg_split('/\s*,\s*/', $tags);

        if ($db === null)
            $db = Connexions::getDb();
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
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     *  @return A new instance (false if no matching user).
     */
    public static function find($id, $db = null)
    {
        return parent::find($id, $db, __CLASS__);
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
