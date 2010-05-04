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

    /**********************************************
     * Tag Management related methods
     *
     */

    /** @brief  Rename a single tag for a specific user.
     *  @param  user        The associated user;
     *  @param  oldTag      The exsiting/old Model_Tag instance;
     *  @param  newTag      The new Model_Tag instance;
     *
     *  @return true (success) else a failure message (string).
     */
    public function renameTag(Model_User    $user,
                              Model_Tag     $oldTag,
                              Model_Tag     $newTag)
    {
        /* Change all 'userTagItem' entries for
         *      $user->userId, $oldTag->tagId, <item>
         *    to
         *      $user->userId, $newTag->tagId, <item>
         *
         *     UPDATE IGNORE userTagItem
         *            SET tagId=$newTag->tagId
         *        WHERE userId=? AND tagId=$oldTag;
         *
         * :NOTE: IGNORE is not an options with Zend...
         */
        $uti = $this->getAccessor('Model_DbTable_UserTagItem');

        $update = array('tagId'      => $newTag->tagId);
        $where  = array('userId = ?' => $user->userId,
                        'tagId  = ?' => $oldTag->tagId);
        try
        {
            $uti->update($update, $where);
        }
        catch (Exception $e)
        {
            /* Ignore this exception, ASSUMING that it's a
             * 'Duplicate entry' exception, ASSUMING that everything else
             * was properly updated (i.e. new entries are inserted BEFORE
             * the exception is thrown).
             *
             * If the ASSUMPTIONS are TRUE, then the only thing we should
             * do here is delete all remaining entries that match the
             * update 'where' condition to clear out any extraneous entries
             * due to the exception.
             */
            $uti->delete($where);


            /* If the ASSUMPTIONS are FALSE, this renaming becomes
             * significantly more complex/costly, involving multiple
             * queries, updates, deletes, and insertions similar to:
             *      1) select all userTagItem matches using the
             *         provided userId and old tag identifiers;
             *      2) for each row, see if a matching entry exists for
             *         the new tag (userId, newTagId, itemId);
             *         a) YES
             *            i)  delete the old entry
             *                  DELETE FROM userTagItem
             *                      WHERE userId=<userId>   AND
             *                             tagId=<oldTagId> AND
             *                            itemId=<itemId>;
             *            ii) insert the new entry
             *                              (userId,newTagId,itemId);
             *                  INSERT INTO userTagItem
             *                      SET userId=<userId>,
             *                           tagId=<newTagId>,
             *                          itemId=<itemId>;
             *         b) NO
             *              Perform an update to change the old tagId
             *              to the new tagId;
             *                  UPDATE userTagItem
             *                      SET   tagId=<newTagId>
             *                      WHERE userId=<userId> AND
             *                             tagId=<oldTagId> AND
             *                            itemId=<itemId>;
             */
        }

        return true;
    }

    /** @brief  Delete the given users use of the provided tag.  If deleting a
     *          tag will result in one or more "orphaned bookmarks"
     *          (i.e. a bookmark with no tags), deletion of the tag will fail.
     *  @param  user        The associated user;
     *  @param  tag         A Model_Tag instance;
     *
     *  @return true (success) else a failure message (string).
     */
    public function deleteTag(Model_User $user,
                              Model_Tag  $tag)
    {
        /* See if there are any bookmarks for this user that use ONLY the given
         * tag.  These will be "orphaned bookmarks" if we delete this tag.
         */
        $bmMapper   = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmarks  = $bmMapper->fetchRelated( array(
                                    'users'     => array($user->userId),
                                    'where'     => "((tagId={$tag->tagId}) "
                                                .  'AND (tagCount < 2))',
                                    'privacy'   => false,
                                ));

        $numOrphans = $bookmarks->count();

        // /*
        Connexions::log("Model_Mapper_User::deleteTag( %d ): "
                        .   "tag[ %d:%s ], %d orphan(s)",
                        $user->userId,
                        $tag->tagId, $tag->tag,
                        $numOrphans);
        // */

        if ($numOrphans > 0)
        {
            /* There will be one or more "orphaned bookmarks" if we delete this
             * tag.
             */
            $status = 'Deleting this tag will orphan '
                    . $numOrphans
                    . ' bookmark'
                    .    ($numOrphans === 1
                            ? ''
                            : 's');
        }
        else
        {
            // All bookmarks have additional tags.  Delete this tag.
            $uti   = $this->getAccessor('Model_DbTable_UserTagItem');

            $where = array('userId = ?' => $user->userId,
                           'tagId  = ?' => $tag->tagId);
            $count = $uti->delete($where);

            if ($count > 0)
            {
                $status = true;
            }
            else
            {
                $status = 'No entries deleted -- Expected one or more???';
            }
        }

        return $status;
    }

    /** @brief  Given a User Domain Model (or User identifier), update 
     *          external-table statistics related to this user:
     *              totalTags, totalItems
     *  @param  id      A Model_User instance or userId.
     *
     *  @return $this for a fluent interface
     */
    public function updateStatistics($id)
    {
        if ($id instanceof Model_User)
        {
            $user = $id;
        }
        else
        {
            $user = $this->find($id);
        }

        /* Update user-related statistics:
         *     SELECT COUNT(DISTINCT tagId)  AS totalTags,
         *            COUNT(DISTINCT itemId) AS totalItems
         *        FROM  userTagItem
         *        WHERE userId=?;
         */
        $table  = $this->getAccessor('Model_DbTable_UserTagItem');
        $select = $table->select();
        $select->from( $table->info(Zend_Db_Table_Abstract::NAME),
                        array('COUNT(DISTINCT tagId)  AS totalTags',
                              'COUNT(DISTINCT itemId) AS totalItems') )
               ->where( 'userId=?', $user->userId );

        /*
        Connexions::log("Model_Mapper_User::_updateStatistics( %d ): "
                        . "sql[ %s ]",
                        $user->userId,
                        $select->assemble());
        // */

        $row = $select->query()->fetchObject();

        /*
        Connexions::log("Model_Mapper_User::_updateStatistics( %d ): "
                        . "for User: row[ %s ]",
                        $user->userId,
                        Connexions::varExport($row));
        // */

        $user->totalTags  = $row->totalTags;
        $user->totalItems = $row->totalItems;
        $user = $user->save();

        return $this;
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
