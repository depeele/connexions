<?php
/** @file
 *
 *  Model for the UserAuth table.
 */

//class Model_User extends Connexions_Model_Cached
class Model_UserAuth extends Connexions_Model
{
    /*************************************************************************
     * Connexions_Model - static, identity members
     *
     */
    public static   $table  = 'userAuth';
                              // order 'keys' by most used
    public static   $keys   = array('userId', 'credential');
    public static   $model  = array('userId'        => 'integer',
                                    'authType'      => 'string',
                                    'credential'    => 'string'
    );
    /*************************************************************************/


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
        //Connexions::log("Model::User::find: id[ ". print_r($id, true) ." ]");
        return parent::find($id, $db, __CLASS__);
    }
}
