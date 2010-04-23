<?php
/** @file
 *
 *  This mapper provides bi-directional access between the Domain Model and the
 *  underlying persistent store (in this case, a Zend_Db_Table).
 *
 *  Note: This mapper hides a few database related details.  The 'ownerId' and
 *        of the underlying table is presented to the Domain Model as, simply,
 *        'owner'.  The Domain Model then uses this field to provide access to
 *        the referenced Model_User instance when requested.
 *
 *        This mapper also makes three meta-data fields available:
 *          getOwner()  - retrieves the Model_User instance represented by the
 *                        'ownerId' for the Group;
 *          getMembers()- retrieves the Model_Set_User instance containing all
 *                        group members;
 *          getItems()  - retrieves the Model_Set_User|Item|Tag|Bookmark
 *                        instance, depending on the 'groupType', that contains
 *                        all items associated with this group;
 */
class Model_Mapper_Group extends Model_Mapper_Base
{
    protected   $_keyName   = 'groupId';

    // If not provided, the following will be generated from our class name:
    //      <Prefix>_Mapper_<Name>                     == Model_Mapper_Group
    //          _modelName  => <Prefix>_<Name>         == Model_Group
    //          _accessor   => <Prefix>_DbTable_<Name> == Model_DbTable_Group
    //
    //protected   $_modelName = 'Model_Group';
    protected   $_accessor  = 'Model_DbTable_MemberGroup';

    /** @brief  Retrieve a single group.
     *  @param  id      The group identifier (groupId or name)
     *
     *  @return A Model_Group instance.
     */
    public function find($id)
    {
        if (is_array($id))
        {
            $where = $id;
        }
        else if (is_string($id) && (! is_numeric($id)) )
        {
            // Lookup by group name
            $where = array('name=?' => $id);
        }
        else
        {
            $where = array('groupId=?' => $id);
        }

        /*
        Connexions::log("Model_Mapper_Group: where[ %s ]",
                        Connexions::varExport($where));
        // */

        return parent::find( $where );
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
        $data = parent::reduceModel($model);

        /* Covert any included user record to the associated database
         * identifiers (userId).
         */
        $data['ownerId']    = ( is_array($data['owner'])
                                ? $data['owner']['userId']
                                : $data['owner']);

        // Remove non-persisted fields
        unset($data['owner']);
        unset($data['members']);
        unset($data['items']);

        return $data;
    }

    /** @brief  Retrieve the user that is the owner of this group.
     *  @param  id      The userId of the desired user.
     *
     *  @return A Model_User instance.
     */
    public function getOwner( $id )
    {
        $userMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user       = $userMapper->find( $id ); //$group->user );

        /*
        Connexions::log("Model_Mapper_Group::getUser(): "
                        . "user[ %s ]",
                        $user->debugDump());
        // */

        return $user;
    }

    /** @brief  Retrieve the set of members for this group.
     *  @param  group   The Model_Group instance.
     *
     *  @return A Model_Set_Tag instance.
     */
    public function getMembers(Model_Group $group)
    {
        $row     = $this->_find( $group->groupId );
        //$members = $row->findDependentRowset('Model_DbTable_GroupMember');

        /* This does NOT return a Zend_Db_Table_Rowset but rather a simple
         * array of Zend_Db_Table_Row objects...
         */
        $members = $row->findManyToManyRowset('Model_DbTable_User',
                                              'Model_DbTable_GroupMember');
        $users = new Model_Set_User( array('totalCount' => count($members),
                                           'results'    => $members) );

        return $users;
    }

    /** @brief  Retrieve the set of items for this group.
     *  @param  group   The Model_Group instance.
     *
     *  @return A Model_Set_(User|Tag|Item|Bookmark) instance.
     */
    public function getItems(Model_Group $group)
    {
        throw new Exception('Not yet implemented');

        switch ($group->groupType)
        {
        case 'user':
        case 'tag':
        case 'item':
        case 'bookmark':
        }

        /*
        $row     = $this->_find( $group->groupId );
        //$members = $row->findDependentRowset('Model_DbTable_GroupMember');

        // This does NOT return a Zend_Db_Table_Rowset but rather a simple
        // array of Zend_Db_Table_Row objects...
        $members = $row->findManyToManyRowset('Model_DbTable_User',
                                              'Model_DbTable_GroupMember');
        $users = new Model_Set_User( array('totalCount' => count($members),
                                           'results'    => $members) );

        return $users;
        */
    }

    /** @brief  Create a new instance of the Domain Model given a raw record.
     *  @param  record  The raw record (array or Zend_Db_Table_Row).
     *
     *  Over-ride in order to "hide" userId/itemId in the user/item fields to
     *  be used to locate/instantiate the associated Domain Models on-demand.
     *
     *  Note: We could also locate/instantiate NOW, but lazy-loading is
     *        typically more cost effective.
     *
     *  @return The matching Domain Model (null if no match).
     */
    public function makeModel($record)
    {
        /* Let's be lazy ;^)
        // Retrieve the associated User and Item
        $userMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $owner      = $userMapper->find( $record->ownerId );
        */


        // Construct the raw data for the new group
        $data = ($record instanceof Zend_Db_Table_Row_Abstract
                    ? $record->toArray()
                    : $record);

        // Move the database ids to the field that will hold the Domain Model
        // instances when they are retrieved.
        $data['owner'] = $data['ownerId']; unset($data['ownerId']);

        /*
        Connexions::log("Model_Mapper_Group::makeModel(): data[ %s ]",
                        Connexions::varExport($data));
        // */

        return parent::makeModel($data);
    }

    /*********************************************************************
     * Protected helpers
     *
     */
}
