<?php
class Model_Mapper_Bookmark extends Model_Mapper_Base
{
    protected   $_keyName   = array('userId', 'itemId');

    // If not provided, the following will be generated from our class name:
    //      <Prefix>_Mapper_<Name>                     == Model_Mapper_Bookmark
    //          _modelName  => <Prefix>_<Name>         == Model_Bookmark
    //          _accessor   => <Prefix>_DbTable_<Name> == Model_DbTable_Bookmark
    //
    //protected   $_modelName = 'Model_Bookmark';
    protected   $_accessor  = 'Model_DbTable_UserItem';

    /** @brief  Save the given model instance.
     *  @param  bookmark    The bookmark Domain Model instance to save.
     *
     *  We over-ride in order to maintain statistics counts for User and Item.
     *
     *  @return The updated domain model instance.
     */
    public function save(Connexions_Model $bookmark)
    {
        $accessor      = $this->getAccessor();
        $ratingChanged = false;
        $id            = $bookmark->getId();
        $tags          = $bookmark->tags;       // Save the tags

        if ( (! $tags instanceof Connexions_Model_Set) || empty($tags) )
            throw new Exception("Bookmarks require at least one tag");

        if ($id)
        {
            // Update -- delete any existing tags.
            $this->deleteTags($bookmark);
        }

        // Allow our parent to perform the actual insert/update
        $bookmark = parent::save($bookmark);

        /* If there were tags associated with this bookmark (SHOULD always be),
         * (re)add them.
         */
        if ( $tags instanceof Connexions_Model_Set )
        {
            // Persist all tags
            $this->addTags($bookmark, $tags);
        }

        /*
        Connexions::log("Model_Mapper_Bookmark::save( %d, %d )",
                        $bookmark->user->userId, $bookmark->item->itemId);
        // */

        // Update table-based statistics:
        $this->_updateStatistics( $bookmark );

        return $bookmark;
    }

    /** @brief  Delete the data for the given model instance.
     *  @param  bookmark    The bookmark Domain Model instance to delete.
     *
     *  Override in order to:
     *      - maintain statistics in User and Item;
     *      - delete associated entries from the 'userTagItem' table;
     *
     *  @return $this for a fluent interface.
     */
    public function delete(Connexions_Model $bookmark)
    {
        $accessor = $this->getAccessor();
        $id       = $bookmark->getId();

        if ($id)
        {
            /*
            Connexions::log("Model_Mapper_Bookmark::delete( %s )",
                            Connexions::varExport($id));
            // */

            // Delete all tags associated with this bookmark
            $this->deleteTags($bookmark);

            // Delete this Bookmark entry
            parent::delete($bookmark);

            // Update table-based statistics:
            $this->_updateStatistics( $id );
        }

        return $this;
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

        /* Covert any included user/item recrod to the associated database
         * identifiers (userId/itemId).
         */
        $data['userId']     = ( is_array($data['user'])
                                ? $data['user']['userId']
                                : $data['user']);
        $data['itemId']     = ( is_array($data['item'])
                                ? $data['item']['itemId']
                                : $data['item']);
        $data['rating']     = ( is_numeric($data['rating'])
                                ? $data['rating']
                                : 0 );
        $data['isFavorite'] = (bool)($data['isFavorite']);
        $data['isPrivate']  = (bool)($data['isPrivate']);

        // Remove non-persisted fields
        unset($data['user']);
        unset($data['item']);
        unset($data['tags']);

        /* Ensure that the 'updatedOn' date is the current date
         * (i.e. the actual update date).
         */
        $data['updatedOn'] = date('Y-m-d h:i:00');

        return $data;
    }

    /** @brief  Retrieve the user related to this bookmark.
     *  @param  id      The userId of the desired user.
     *
     *  @return A Model_User instance.
     */
    public function getUser( $id )
    {
        $userMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user       = $userMapper->find( $id ); //$bookmark->user );

        /*
        Connexions::log("Model_Mapper_Bookmark::getUser(): "
                        . "user[ %s ]",
                        $user->debugDump());
        // */

        return $user;
    }

    /** @brief  Retrieve the item related to this bookmark.
     *  @param  id      The itemId of the desired item.
     *
     *  @return A Model_Item instance.
     */
    public function getItem( $id )
    {
        $itemMapper = Connexions_Model_Mapper::factory('Model_Mapper_Item');
        $item       = $itemMapper->find( $id );

        /*
        Connexions::log("Model_Mapper_Bookmark::getItem(): "
                        . "item[ %s ]",
                        $item->debugDump());
        // */

        return $item;
    }

    /** @brief  Retrieve a set of bookmark-related tags
     *  @param  bookmark    The Model_Bookmark instance.
     *
     *  @return A Model_Set_Tag instance.
     */
    public function getTags(Model_Bookmark $bookmark)
    {
        $userId = $bookmark->user;
        $itemId = $bookmark->item;

        if ($userId instanceof Model_User)
            $userId = $userId->userId;
        if ($itemId instanceof Model_Item)
            $itemId = $itemId->itemId;

        if ( ($userId <= 0) || ($itemId <= 0) )
            return null;

        /*
        Connexions::log("Model_Mapper_Bookmark::getTags(): "
                        .   "user[ %d ], item[ %d ]",
                        $userId,
                        $itemId);
        // */

        $tagMapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tags      = $tagMapper->fetchRelated( $userId,
                                               $itemId );

        return $tags;
    }

    /** @brief  Delete all tags from the identified Bookmark instance.
     *  @param  bookmark    The Model_Bookmark instance.
     *
     *  return  $this for a fluent interface.
     */
    public function deleteTags(Model_Bookmark $bookmark)
    {
        $table = $this->getAccessor('Model_DbTable_UserTagItem');
        $table->delete( array('userId=?' => $bookmark->user->userId,
                              'itemId=?' => $bookmark->item->itemId) );

        /*
        $db = $this->getAccessor()->getAdapter();
        $db->delete('userTagItem',
                    array('userId=?' => $bookmark->user->userId,
                          'itemId=?' => $bookmark->item->itemId));
        */
    }

    /** @brief  Add a set of tags to the identified Bookmark instance.
     *  @param  bookmark    The Model_Bookmark instance.
     *  @param  tags        An Model_Set_Tag instance.
     *
     *  return  $this for a fluent interface.
     */
    public function addTags(Model_Bookmark $bookmark, Model_Set_Tag $tags)
    {
        $table = $this->getAccessor('Model_DbTable_UserTagItem');
        foreach ($tags as $tag)
        {
            $table->insert( array('userId' => $bookmark->user->userId,
                                  'itemId' => $bookmark->item->itemId,
                                  'tagId'  => $tag->tagId) );
        }

        return $this;
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
        $user       = $userMapper->find( $record->userId );

        $itemMapper = Connexions_Model_Mapper::factory('Model_Mapper_Item');
        $item       = $itemMapper->find( $record->itemId );
        */


        // Construct the raw data for the new bookmark
        $data = ($record instanceof Zend_Db_Table_Row_Abstract
                    ? $record->toArray()
                    : $record);

        // Move the database ids to the field that will hold the Domain Model
        // instances when they are retrieved.
        $data['user'] = $data['userId']; unset($data['userId']);
        $data['item'] = $data['itemId']; unset($data['itemId']);

        /*
        Connexions::log("Model_Mapper_Bookmark::makeModel(): data[ %s ]",
                        Connexions::varExport($data));
        // */

        return parent::makeModel($data);
    }

    /*********************************************************************
     * Protected helpers
     *
     */

    /** @brief  Given a Bookmark Domain Model, update external-table statistics
     *          related to this bookmark:
     *              user - totalTags, totalItems
     *              item - userCount, ratingCount, ratingSum
     *  @param  id      An array of (userId, itemId) identifying the target
     *                  Bookmark OR a Bookmark Domain Model instance.
     *
     *  @return $this for a fluent interface
     */
    protected function _updateStatistics($id)
    {
        if ($id instanceof Model_Bookmark)
        {
            $user =& $id->user;
            $item =& $id->item;
        }
        else
        {
            $user = $this->getUser($id[0]);
            $item = $this->getItem($id[1]);
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
        Connexions::log("Model_Mapper_Bookmark::_updateStatistics( %d, %d ): "
                        . "sql[ %s ]",
                        $user->userId, $item->itemId,
                        $select->assemble());
        // */

        $row = $select->query()->fetchObject();

        /*
        Connexions::log("Model_Mapper_Bookmark::_updateStatistics( %d, %d ): "
                        . "row[ %s ]",
                        $user->userId, $item->itemId,
                        Connexions::varExport($row));
        // */

        $user->totalTags  = $row->totalTags;
        $user->totalItems = $row->totalItems;
        $user = $user->save();


        /* Update item-related statistics:
         *    SELECT
         *      COUNT(DISTINCT userId)                            AS userCount,
         *      SUM(CASE WHEN rating > 0 THEN 1 ELSE 0 END)       AS ratingCount,
         *      SUM(CASE rating WHEN null THEN 0 ELSE rating END) AS ratingSum
         *        FROM  userItem
         *        WHERE itemId=?;
         */
        $table  = $this->getAccessor('Model_DbTable_UserItem');
        $select = $table->select();
        $select->from( $table->info(Zend_Db_Table_Abstract::NAME),
                        array('COUNT(DISTINCT userId)  AS userCount',
                              'SUM(CASE WHEN rating > 0 THEN 1 ELSE 0 END) '
                                . 'AS ratingCount',
                              'SUM(CASE rating WHEN null THEN 0 ELSE rating END) '
                                . 'AS ratingSum') )
               ->where( 'itemId=?', $item->itemId );

        $row = $select->query()->fetchObject();

        /*
        Connexions::log("Model_Mapper_Bookmark::_updateStatistics( %d, %d ): "
                        . "sql[ %s ], row[ %s ]",
                        $user->userId, $item->itemId,
                        $select->assemble(),
                        Connexions::varExport($row));
        // */

        $item->userCount   = $row->userCount;
        $item->ratingCount = $row->ratingCount;
        $item->ratingSum   = $row->ratingSum;
        $item = $item->save();

        if ($id instanceof Model_Bookmark)
        {
            $id->user = $user;
            $id->item = $item;
        }

        return $this;
    }
}
