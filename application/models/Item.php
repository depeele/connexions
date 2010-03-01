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
        return __CLASS__ .'_'.  (! @empty($id['itemId'])
                                    ?  $id['itemId']
                                    : 'generic');
    }
}
