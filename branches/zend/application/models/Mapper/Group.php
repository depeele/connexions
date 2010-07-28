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
    protected   $_keyNames  = array('groupId');

    // If not provided, the following will be generated from our class name:
    //      <Prefix>_Mapper_<Name>                     == Model_Mapper_Group
    //          _modelName  => <Prefix>_<Name>         == Model_Group
    //          _accessor   => <Prefix>_DbTable_<Name> == Model_DbTable_Group
    //
    //protected   $_modelName = 'Model_Group';
    protected   $_accessor  = 'Model_DbTable_MemberGroup';

    /** @brief  Given identification value(s) that will be used for retrieval,
     *          normalize them to an array of attribute/value(s) pairs.
     *  @param  id      Identification value(s) (string, integer, array).
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value pairs.
     *
     *  Note: This a support method for Services and
     *        Connexions_Model_Mapper::normalizeIds()
     *
     *  @return An array containing attribute/value(s) pairs suitable for
     *          retrieval.
     */
    public function normalizeId($id)
    {
        if (is_int($id) || is_numeric($id))
        {
            $id = array('groupId' => $id);
        }
        else if (is_string($id))
        {
            $id = array('name' => $id);
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
        $data = parent::reduceModel($model);

        /*
        Connexions::log("Model_Mapper_Group::reduceModel(%d, %d): [ %s ]",
                        $model->groupId, $data['groupId'],
                        Connexions::varExport($data));
        // */

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
        $user       = $userMapper->find( array('userId' => $id) );

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
        $row     = $this->_find( array('groupId' => $group->groupId) );
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
        $row     = $this->_find( array('groupId' => $group->groupId) );
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

    /** @brief  Create a new instance of the Domain Model given raw data, 
     *          typically from a persistent store.
     *  @param  data        The raw data.
     *  @param  isBacked    Is the incoming data backed by persistent store?
     *                      [ true ];
     *
     *  Note: We could also locate/instantiate NOW, but lazy-loading is
     *        typically more cost effective.
     *
     *  @return A matching Domain Model
     *          (MAY be backed if a matching instance already exists).
    public function makeModel($data, $isBacked = true)
    {
        $group = parent::makeModel($data, $isBacked);

        // Retrieve the associated Owner, Members, and Items
        $userMapper     = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $group->owner   = $userMapper->find( array('userId' =>
                                                        $group->ownerId) );
        //$group->members = $userMapper->find( array('userId' =>
        //                                              $group->ownerId );
    }
     */

    /*********************************************************************
     * Protected helpers
     *
     */
}
