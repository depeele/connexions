<?php
class Model_Mapper_User extends Model_Mapper_Base
{
    protected   $_keyName   = 'userId';

    // If not provided, the following will be generated from our class name:
    //      <Prefix>_Mapper_<Name>                      == Model_Mapper_User
    //          _modelName  => <Prefix>_<Name>          == Model_User
    //          _accessor   => <Prefix>_DbTable_<Name>  == Model_DbTable_User
    //
    //protected   $_modelName = 'Model_User';
    //protected   $_accessor  = 'Model_DbTable_User';

    /** @brief  Retrieve a single user.
     *  @param  id      The user identifier (userId or name)
     *
     *  @return A Model_User instance.
     */
    public function find($id)
    {
        if (is_array($id))
        {
            $where = $id;
        }
        else if (is_string($id) && (! is_numeric($id)) )
        {
            // Lookup by user name
            $where = array('name=?' => $id);
        }
        else
        {
            $where = array('userId=?' => $id);
        }

        /*
        Connexions::log("Model_Mapper_User: where[ %s ]",
                        Connexions::varExport($where));
        // */

        return parent::find( $where );
    }

    /** @brief  Retrieve a set of user-related tags
     *  @param  user    The Model_User instance.
     *
     *  @return A Model_Tag_Set
     */
    public function getTags(Model_User $user)
    {
        throw new Exception('Not yet implemented');
    }

    /** @brief  Retrieve a set of user-related items
     *  @param  user    The Model_User instance.
     *
     *  @return A Model_Item_Set
     */
    public function getItems(Model_User $user)
    {
        throw new Exception('Not yet implemented');
    }

    /** @brief  Retrieve a set of user-related bookmarks
     *  @param  user    The Model_User instance.
     *
     *  @return A Model_Bookmark_Set
     */
    public function getBookmarks(Model_User $user)
    {
        throw new Exception('Not yet implemented');
    }
}
