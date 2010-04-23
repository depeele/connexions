<?php
/** @file
 *
 *  This mapper provides bi-directional access between the Domain Model and the
 *  underlying persistent store (in this case, a Zend_Db_Table).
 */
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

    /*********************************************************************
     * Protected methods
     *
     * Since a user can be queried by either userId or name, the identity
     * map for this Domain Model must be a bit more "intelligent"...
     */

    /** @brief  Save a new Model instance in our identity map.
     *  @param  id      The model instance identifier.
     *  $param  model   The model instance.
     *
     *  @return The Model instance (null if not found).
     */
    protected function _setIdentity($id, $model)
    {
        /* Ignore 'id' -- it'll include either userId, name, or both.
         *
         * Add identity map entries for both userId and name
         */
        $this->_identityMap[ $model->userId ] =& $model;
        $this->_identityMap[ $model->name   ] =& $model;

        /*
        Connexions::log("Model_Mapper_User::_setIdentity(): "
                        .   "id[ %d ], name[ %s ]",
                         $model->userId, $model->name);
        // */
    }

    /** @brief  Remove an identity map entry.
     *  @param  id      The model instance identifier.
     *  $param  model   The model instance currently mapped.
     */
    protected function _unsetIdentity($id, Connexions_Model $model)
    {
        /* Ignore 'id' -- it'll include JUST userId.
         *
         * Remove the identity map entries for both userId and name
         */
        unset($this->_identityMap[ $model->userId ]);
        unset($this->_identityMap[ $model->name   ]);

        /*
        Connexions::log("Model_Mapper_User::_unsetIdentity(): "
                        .   "id[ %d ], name[ %s ]",
                         $model->userId, $model->name);
        // */
    }
}
