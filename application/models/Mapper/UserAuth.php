<?php
/** @file
 *
 *  This mapper provides bi-directional access between the Domain Model and the
 *  underlying persistent store (in this case, a Zend_Db_Table).
 */
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

    /** @brief  Convert the incoming model into an array containing only 
     *          data that should be directly persisted.  This method may also
     *          be used to update dynamic values
     *          (e.g. update date/time, last visit date/time).
     *  @param  model   The Domain Model to reduce to an array.
     *
     *  @return A filtered associative array containing data that should 
     *          be directly persisted.
     */
    public function reduceModel(Connexions_Model $model)
    {
        // Need to KEEP the "keys" for this model
        return parent::reduceModel($model, true);
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
