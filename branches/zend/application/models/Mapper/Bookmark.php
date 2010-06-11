<?php
/** @file
 *
 *  This mapper provides bi-directional access between the Domain Model and the
 *  underlying persistent store (in this case, a Zend_Db_Table).
 *
 *  Note: This mapper makes three meta-data fields available:
 *          getUser()   - retrieves the Model_User instance represented by the
 *                        'userId' for the Bookmark;
 *          getItem()   - retrieves the Model_Item instance represented by the
 *                        'itemId' for the Bookmark;
 *          getTags()   - retrieves the Model_Set_Tag instance containing all
 *                        tags directly associated with the Bookmark;
 */
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

        /*
        Connexions::log("Models_Mapper_Bookmark::save(%s, %s): [ %s ]",
                        $bookmark->userId, $bookmark->itemId,
                        $bookmark->debugDump());
        // */

        if ( (! $tags instanceof Connexions_Model_Set) || (count($tags) < 1))
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
                        $bookmark->userId, $bookmark->itemId);
        // */

        // Update table-based statistics:
        $bookmark->updateStatistics();

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
            $user = $bookmark->user;
            $item = $bookmark->item;

            // Delete all tags associated with this bookmark
            $this->deleteTags($bookmark);

            // Delete this Bookmark entry
            parent::delete($bookmark);

            // Update table-based statistics:
            $user->updateStatistics();
            $item->updateStatistics();
        }

        return $this;
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
        $data = parent::reduceModel($model, true);

        /*
        Connexions::log("Model_Mapper_Bookmark::reduceModel(%d, %d): [ %s ]",
                        $userId, $itemId,
                        Connexions::varExport($data));
        // */

        /* Covert any included user/item record to the associated database
         * identifiers (userId/itemId).
         */
        $data['rating']     = ( is_numeric($data['rating'])
                                ? $data['rating']
                                : 0 );
        $data['isFavorite'] = (bool)($data['isFavorite']);
        $data['isPrivate']  = (bool)($data['isPrivate']);

        /* Ensure that the 'updatedOn' date is the current date
         * (i.e. the actual update date).
         *
         * Let Model_Bookmark::populate() handle this
        $data['updatedOn'] = date('Y-m-d h:i:s');
         */

        return $data;
    }

    /** @brief  Retrieve a set of Domain Model items via the userTagItem core
     *          table.
     *  @param  params  An array retrieval criteria:
     *                      - users     The Model_Set_User instance or an array
     *                                  of userIds to use in the relation;
     *                      - items     The Model_Set_Item instance or an array
     *                                  of itemIds to use in the relation;
     *                      - tags      The Model_Set_Tag  instance or an array
     *                                  of tagIds to use in the relation;
     *                      - order     Optional ORDER clause (string, array);
     *                      - count     Optional LIMIT count;
     *                      - offset    Optional LIMIT offset;
     *                      - exactTags If 'tags' is provided,  should we
     *                                  require a match on ALL tags? [ true ];
     *                      - where     Additional condition(s) [ null ];
     *
     *                      - privacy   Model_User to use for privacy filter
     *                                  [ anonymous / unauthenticated ];
     *
     *  Override Model_Base::fetchRelated() to add a 'privacy' parameter.
     *
     *  @return A Connexions_Model_Set instance that provides access to all
     *          matching Domain Model instances.
     */
    public function fetchRelated( array $params = array())
    {
        $privacy = (isset($params['privacy'])
                        ? $params['privacy']
                        : null);

        if ($privacy !== false)
        {
            // Include a privacy filter
            $where = '( (b.isPrivate=0) ';

            if ( (! empty($privacy))              &&
                 ($privacy instanceof Model_User) &&
                 $privacy->isAuthenticated() )
            {
                /* Allow the authenticated user to see their own private
                 * bookmarks.
                 */
                $where .= "OR (b.userId={$privacy->userId}) ";
            }

            $where .= ')';

            if (isset($params['where']))
            {
                // Merge the privacy filter in with the incoming 'where' clause
                $newWhere = $params['where'];

                if (is_array($newWhere))
                    array_push($newWhere, $where);
                else
                    $newWhere .= ' AND '. $where;

                $where = $newWhere;
            }

            $params['where'] = $where;
        }

        /*
        Connexions::log("Model_Mapper_Bookmark::fetchRelated(): "
                        .   "params[ %s ]",
                        Connexions::varExport($params));
        // */

        return parent::fetchRelated($params);
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
        $userId = $bookmark->userId;
        $itemId = $bookmark->itemId;
        if ( ($userId <= 0) || ($itemId <= 0) )
            return null;

        /*
        Connexions::log("Model_Mapper_Bookmark::getTags(): "
                        .   "user[ %d ], item[ %d ]",
                        $userId,
                        $itemId);
        // */

        $tagMapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tags      = $tagMapper->fetchRelated( array(
                                        'users' => $userId,
                                        'items' => $itemId,
                                        'order' => 'tag ASC',
                                    ));

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
        $table->delete( array('userId=?' => $bookmark->userId,
                              'itemId=?' => $bookmark->itemId) );

        /*
        $db = $this->getAccessor()->getAdapter();
        $db->delete('userTagItem',
                    array('userId=?' => $bookmark->userId,
                          'itemId=?' => $bookmark->itemId));
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
            $table->insert( array('userId' => $bookmark->userId,
                                  'itemId' => $bookmark->itemId,
                                  'tagId'  => $tag->tagId) );
        }

        return $this;
    }

    /** @brief  Create a new instance of the Domain Model given raw data, 
     *          typically from a persistent store.
     *  @param  data        The raw data.
     *  @param  isBacked    Is the incoming data backed by persistent store?
     *                      [ true ];
     *
     *  Note: We could also locate/instantiate the associated user, item, and 
     *        tag instances NOW, but lazy-loading is typically more cost 
     *        effective.
     *
     *  @return A matching Domain Model
     *          (MAY be backed if a matching instance already exists).
    public function makeModel($data, $isBacked = true)
    {
        // Retrieve the associated User and Item
        $userMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user       = $userMapper->find( $data->userId );

        $itemMapper = Connexions_Model_Mapper::factory('Model_Mapper_Item');
        $item       = $itemMapper->find( $data->itemId );

        return parent::makeModel($data, $isBacked);
    }
     */

    /************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Include statistics-related informatioin in the
     *          select/sub-select
     *  @param  select      The primary   Zend_Db_Select instance;
     *  @param  secSelect   The secondary Zend_Db_Select instance;
     *  @param  secAs       The alias used for 'secSelect';
     *  @param  params      An array retrieval criteria;
     *
     *  Override Mapper_Base::_includeStatistics() in order to include
     *  Bookmark-specific statistics.
     *
     *  @return $this for a fluent interface.
     */
    protected function _includeStatistics(Zend_Db_Select    $select,
                                          Zend_Db_Select    $secSelect,
                                                            $secAs,
                                          array             $params)
    {
        $db        = $secSelect->getAdapter();

        // The alias of the tertiary select that we will add below
        $terAs     = 'i';

        // Include the statistics in the column list of the primary select
        $select->columns(array("{$secAs}.userCount",
                               "{$secAs}.tagCount",
                               "{$secAs}.ratingCount",
                               "{$secAs}.ratingSum",
                               "{$secAs}.ratingAvg"));

        // Generate SOME statistics in the secondary select
        $secSelect->columns(array(
                                $this->_fieldExpression('userTagItem',
                                                        'tagCount',
                                                        $secAs)));

        // Create a tertiary select to pull item-related statistics
        $terSelect = $db->select();
        $terSelect->from(array($terAs => 'item'),
                         array("{$terAs}.*",
                               $this->_fieldExpression('item', 'ratingAvg',
                                                       $terAs)) )
                  ->group("{$terAs}.itemId");
                               

        $secSelect->columns(array("{$terAs}.userCount",
                                  "{$terAs}.ratingCount",
                                  "{$terAs}.ratingSum",
                                  "{$terAs}.ratingAvg"))
                  ->join(array($terAs => $terSelect),
                         "{$secAs}.itemId = {$terAs}.itemId",
                         null);
    }
}
