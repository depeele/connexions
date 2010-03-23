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
