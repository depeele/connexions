<?php
class Model_Mapper_UserAuth extends Model_Mapper_Base
{
    protected   $_keyName   = array('userId', 'authType');

    // If not provided, the following will be generated from our class name:
    //      <Prefix>_Mapper_<Name>                     == Model_Mapper_UserAuth
    //          _modelName  => <Prefix>_<Name>         == Model_UserAuth
    //          _accessor   => <Prefix>_DbTable_<Name> == Model_DbTable_UserAuth
    //
    //protected   $_modelName = 'Model_UserAuth';
    //protected   $_accessor  = 'Model_DbTable_UserAuth';

    /** @brief  Retrieve a single userAuth.
     *  @param  id      The userAuth identifier ( [userId, authType],
     *                                            credential)
     *
     *  @return A Model_UserAuth instance.
     */
    public function find($id)
    {
        // Use 'fetch()' since this table has no clear keys...

        if ( is_array($id) )
        {
            $model = parent::find($id);
        }
        else
        {
            // ASSUME this should be a match on credential
            $models = parent::fetch( array('credential' => $id), null, 1 );
            if ($models instanceof Connexions_Model_Set)
                $model = $models[0];
            else
                $model = null;
        }

        return $model;
    }

    /** @brief  Fetch all matching model instances.
     *  @param  where   Optional WHERE clause (string, array, Zend_Db_Select)
     *  @param  order   Optional ORDER clause (string, array)
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  Note: If 'where' is a simple array of values or a single value, this
     *        method (via _where) will only match the value(s) against primary
     *        key(s).
     *
     *        To match against another field, 'where' MUST be an associative
     *        array of condition/value pairs.
     *
     *  @return A Connexions_Model_Set instance that provides access to all
     *          matching Domain Model instances.
     */
    public function fetch($where   = null,
                          $order   = null,
                          $count   = null,
                          $offset  = null)
    {
    }

    /** @brief  Retrieve the user related to this userAuth.
     *  @param  userAuth    The Model_UserAuth instance.
     *
     *  @return A Model_User instance.
     */
    public function getUser(Model_UserAuth $userAuth)
    {
        $userMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user       = $userMapper->find( $userAuth->userId );

        /*
        Connexions::log("Model_Mapper_UserAuth::getUser(): "
                        . "user[ %s ]",
                        $user->debugDump());
        // */

        return $user;
    }
}
