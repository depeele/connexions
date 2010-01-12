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
        // weightValue, url
        $val = (@isset($this->_params[$name])
                    ? $this->_params[$name]
                    : null);
        if (($val === null) && ($name === 'url'))
            $val = (String)($this->tag);

        return $val;
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

    public function setParam($name, $value)
    {
        // weightValue, url
        $this->_params[$name] = $value;
    }

    /*************************************************************************
     * Static methods
     *
     */

    /** @brief  Given a set of tags, retrieve the tag identifier for each.
     *  @param  tags    The set of tags as a comma-separated string or array.
     *
     *  @return An array of tag identifiers.
     */
    public static function ids($tags)
    {
        if (! @is_array($tags))
            $tags = preg_split('/\s*,\s*/', $tags);

        $db     = Connexions::getDb();
        $select = $db->select()
                     ->from(self::$table,
                            'tagId')
                     ->where('tag IN (?)', $tags);
        $stmt   = $select->query(); //Zend_Db::FETCH_NUM);
        $recs   = $stmt->fetchAll();

        // Convert the returned array of records to a simple array of ids
        $ids    = array();
        foreach ($recs as $idex => $row)
        {
            $ids[] = $row['tagId']; // $row[0];
        }

        return $ids;
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
