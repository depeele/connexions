<?php
/** @file
 *
 *  This mapper provides bi-directional access between the Domain Model and the
 *  underlying persistent store (in this case, a Zend_Db_Table).
 */
class Model_Mapper_UserAuth extends Model_Mapper_Base
{
    protected   $_keyNames  = array('userAuthId');
    //protected   $_keyNames  = array('userId', 'authType', 'credential');

    // If not provided, the following will be generated from our class name:
    //      <Prefix>_Mapper_<Name>                     == Model_Mapper_UserAuth
    //          _modelName  => <Prefix>_<Name>         == Model_UserAuth
    //          _accessor   => <Prefix>_DbTable_<Name> == Model_DbTable_UserAuth
    //
    //protected   $_modelName = 'Model_UserAuth';
    //protected   $_accessor  = 'Model_DbTable_UserAuth';

    /** @brief  Given identification value(s) that will be used for retrieval,
     *          normalize them to an array of attribute/value(s) pairs.
     *  @param  id      Identification value(s) (string, integer, array).
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value pairs.
     *
     *  Note: This a support method for Services and
     *        Connexions_Model_Mapper::normalizeIds()
     *
     *  UserAuth has a multi-value key -- allow multiple values to be specified 
     *  in a string, separated by '::'.
     *
     *  @return An array containing attribute/value(s) pairs suitable for
     *          retrieval.
     */
    public function normalizeId($id)
    {
        if (is_string($id))
        {
            list($userId, $authType, $credential)   =
                                preg_split('/\s*::\s*/', $id);
            $normId                                 = array();

            // Employ Model_Mapper_User to properly interpret 'userId'
            $uMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
            $userId = $uMapper->normalizeId($userId);
            if (isset($userId['userId']))
                $normId['userId'] = $userId['userId'];
            else
            {
                // Perform a full find with 'userId'
                $user = $uMapper->find($userId);
                if ($user === null)
                {
                    /* No matching user found!
                     *
                     * Fall-back: keep whatever was provided for 'userId'
                     */
                    $normId = array_merge($normId, $userId);
                }
                else
                {
                    $normId['userId'] = $user->userId;
                }
            }

            // Normalize 'authType'
            $authType = strtolower($authType);
            switch ($authType)
            {
            case Model_UserAuth::AUTH_OPENID:
            case Model_UserAuth::AUTH_PASSWORD:
            case Model_UserAuth::AUTH_PKI:
                break;

            default:
                $authType = Model_UserAuth::AUTH_DEFAULT;
            }

            if (empty($credential))
                $credential = '';

            // We should now have a valid, new identifier.
            $id = $normId;
        }
        else
        {
            $id = parent::normalizeId($id);
        }

        return $id;
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
        // Need to KEEP ALL fields for this model.
        $data = $model->toArray( array('deep'    => false,
                                       'public'  => false,
                                       'dirty'   => false) );

        /*
        Connexions::log("Model_Mapper_UserAuth::reduceModel( %s ): [ %s ]",
                        $model, Connexions::varExport($data));
        // */

        return $data;
    }

    /** @brief  Retrieve the user related to this userAuth.
     *  @param  userAuth    The Model_UserAuth instance.
     *
     *  @return A Model_User instance.
     */
    public function getUser(Model_UserAuth $userAuth)
    {
        $userMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user       = $userMapper->find( array('userId' => $userAuth->userId));

        /*
        Connexions::log("Model_Mapper_UserAuth::getUser(): "
                        . "user[ %s ]",
                        $user->debugDump());
        // */

        return $user;
    }
}
