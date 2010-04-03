<?php
/** @file
 *
 *  Model for the Item table.
 *
 */

class Model_Item extends Connexions_Model_Cached
{
    /*************************************************************************
     * Connexions_Model - static, identity members
     *
     */
    public static   $table  = 'item';
                              // order 'keys' by most used
    public static   $keys   = array('itemId', 'urlHash');
    public static   $model  = array('itemId'        => 'auto',
                                    'url'           => 'string',
                                    'urlHash'       => 'string',

                                    'userCount'     => 'integer',
                                    'ratingCount'   => 'integer',
                                    'ratingSum'     => 'integer'
    );

    /** @brief  The set of models that are dependent upon this model.
     *
     *  This is primarily used to perform cascade on delete
     *  (i.e. deleting a Model_User from the database will also caused the
     *        deletion of associated Model_UserAuth and Model_UserItem
     *        records).
     */
    public static   $dependents = array('Model_UserItem');

    /*************************************************************************/

    /** @brief  Set a value in this record and mark it dirty.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  Override to ensure that, when 'url' is set, we also set 'urlHash' and, 
     *  if 'urlHash' is set, it correctly matches 'url'.
     *
     *  @return true | false
     */
    public function __set($name, $value)
    {
        switch ($name)
        {
        case 'url':
            $hash = Connexions::md5Url($value);
            parent::__set('urlHash', $hash);
            break;

        case 'urlHash':
            if (! empty($this->_record['url']))
            {
                $value = Connexions::md5Url($this->_record['url']);
            }
            break;
        }

        return parent::__set($name, $value);
    }


    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        if ($this->isValid() && (! @empty($this->_record['url'])))
            return $this->_record['url'];

        return parent::__toString();
    }

    /** @brief  Notification from a related model that tags related to this 
     *          item have been updated.  Perform any required maintainence 
     *          (e.g.  updating tag statistics).
     *
     *  @return This Model_Item for a fluent interface.
     */
    public function tagsUpdated()
    {
        return $this;
    }

    /*************************************************************************
     * Connexions_Model - overloads
     *
     */

    /** @brief  Initialize this model/record.  This will cause an overall
     *          reset of this instance, possibly (re)retrieving the data.
     *  @param  id      The record identifier.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     *  Overload to allow conversion of 'id' provided as a URL to a URL hash.
     *
     *  @return Connexions_Model to provide a fluent interface.
     */
    protected function _init($id, $db = null)
    {
        if (is_string($id) && (! is_numeric($id)) )
        {
            /* Connexions::md5Url() handles deciding whether or not this is 
             * already a hash.
             */
            $id = Connexions::md5Url($id);
        }

        return parent::_init($id, $db);
    }

    /*************************************************************************
     * Connexions_Model - abstract static method implementations
     *
     */

    /** @brief  Retrieve all records and return an array of instances.
     *  @param  id      The record identifier (itemId, url, or urlHash.
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
        return __CLASS__ .'_'.  (! @empty($id['itemId'])
                                    ?  $id['itemId']
                                    : 'generic');
    }
}
