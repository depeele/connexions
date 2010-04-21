<?php
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

    /** @brief  Filter out any data that isn't directly persisted, update any 
     *          dynamic values.
     *  @param  data    An associative array of data that is about to be 
     *                  persisted.
     *
     *  @return A filtered associative array containing data that should 
     *          be directly persisted.
     */
    public function filter(array $data)
    {
        $data = parent::filter($data);

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
