<?php
/** @file
 *
 *  This mapper provides bi-directional access between the Domain Model and the
 *  underlying persistent store (in this case, a Zend_Db_Table).
 */
class Model_Mapper_Activity extends Model_Mapper_Base
{
    protected   $_keyNames  = array('activityId');

    // If not provided, the following will be generated from our class name:
    //      <Prefix>_Mapper_<Name>                     == Model_Mapper_Activity
    //          _modelName  => <Prefix>_<Name>         == Model_Activity
    //          _accessor   => <Prefix>_DbTable_<Name> == Model_DbTable_Activity
    //
    //protected   $_modelName = 'Model_Activity';
    //protected   $_accessor  = 'Model_DbTable_Activity';

    /** @brief  Convert the incoming model into an array containing only 
     *          data that should be directly persisted.  This method may also
     *          be used to update dynamic values
     *          (e.g. update date/time, last visit date/time).
     *  @param  model   The Domain Model to reduce to an array.
     *
     *  Over-ride Connexions_Model_Mapper since our model's toArray() is a
     *  little special.
     *
     *  @return A filtered associative array containing data that should 
     *          be directly persisted.
     */
    public function reduceModel(Connexions_Model $model)
    {
        return $model->toArray( array('deep'    => false,
                                      'public'  => false,
                                      'dirty'   => true,
                                      'raw'     => true) );
    }

    /** @brief  Retrieve the user related to this activity.
     *  @param  activity    The Model_Activity instance.
     *
     *  @return A Model_User instance.
     */
    public function getUser(Model_Activity $activity)
    {
        $userMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user       = $userMapper->find( array('userId' => $activity->userId));

        /*
        Connexions::log("Model_Mapper_Activity::getUser(): "
                        . "user[ %s ]",
                        ($user
                            ? $user->debugDump()
                            : 'null'));
        // */

        return $user;
    }

    /** @brief  Retrieve the object related to this activity.
     *  @param  activity    The Model_Activity instance.
     *
     *  @return A Connexions_Model instance.
     */
    public function getObject(Model_Activity $activity)
    {
        $sName   = 'Service_'. ucfirst($activity->objectType);
        $service = Connexions_Service::factory($sName);
        $object  = $service->find( $activity->objectId );

        /*
        Connexions::log("Model_Mapper_Activity::getObject(): "
                        . "service[ %s ], id[ %s ], object[ %s ]",
                        $sName,
                        Connexions::varExport($activity->objectId),
                        ($object
                            ? $object->debugDump()
                            : 'null'));
        // */

        return $object;
    }
}
