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
        /*
        Connexions::log("Mapper_Group:normalizeId(): id[ %s ]",
                        Connexions::varExport($id));
        // */

        if (is_int($id) || is_numeric($id))
        {
            $id = array('groupId' => $id);
        }
        else if (is_string($id))
        {
            $id = array('name' => $id);
        }
        else if (is_array($id) && (isset($id['ownerId'])))
        {
            //$id = $this->_normalizeOwnerId($id['ownerId']);
            $id['ownerId'] = $this->_normalizeOwnerId($id['ownerId']);
        }
        else
        {
            $id = parent::normalizeId($id);
        }

        /*
        Connexions::log("Mapper_Group:normalizeId(): == id[ %s ]",
                        Connexions::varExport($id));
        // */

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
     *  @param  order   Optional ORDER clause (string, array);
     *  @param  count   Optional LIMIT count;
     *  @param  offset  Optional LIMIT offset;
     *
     *  @return A Model_Set_User instance.
     */
    public function getMembers(Model_Group $group,
                                           $order   = null,
                                           $count   = null,
                                           $offset  = null)
    {
        $mapperName = 'Model_Mapper_User';

        /*
        Connexions::log('Model_Mapper_Group::getMembers(): '
                        .   'groupId[ %s ], type[ %s ]',
                        Connexions::varExport($group->groupId),
                        $group->groupType);
        // */

        /* This does NOT return a Zend_Db_Table_Rowset but rather a simple
         * array of Zend_Db_Table_Row objects...
         */
        $mapper = $this->factory($mapperName);
        if ($group->groupId !== null)
        {
            $tableName = $mapper->getTableName();
            $select    = $mapper->select();
            $select->from( array('gt' => 'groupMember'))
                   ->where('gt.groupId=?', $group->groupId)
                   ->join(array('u' => $tableName),
                          "gt.userId=u.{$tableName}Id")
                   ->reset(Zend_Db_Select::COLUMNS)
                   ->columns('u.*');
            if ($order !== null)
            {
                $select->order($order);
            }
            if (($count !== null) || ($offset !== null))
            {
                $select->limit($count, $offset);
            }

            /*
            Connexions::log("Model_Mapper_Group::getMembers(): "
                            .   "select[ %s ]",
                            $select->assemble());
            // */

            $members = $mapper->fetch($select);
            $members->setContext($select);
        }
        else
        {
            $members = $mapper->makeEmptySet();

            /*
            Connexions::log('Model_Mapper_Group::getMembers(): '
                            .   'groupId[ %s ], type[ %s ]: empty set[ %s ]',
                            Connexions::varExport($group->groupId),
                            $group->groupType,
                            $members->debugDump());
            // */
        }

        return $members;
    }

    /** @brief  Retrieve the set of items for this group.
     *  @param  group   The Model_Group instance;
     *  @param  order   Optional ORDER clause (string, array);
     *  @param  count   Optional LIMIT count;
     *  @param  offset  Optional LIMIT offset;
     *
     *  @return A Model_Set_(User|Tag|Item|Bookmark) instance.
     */
    public function getItems(Model_Group $group,
                                         $order     = null,
                                         $count     = null,
                                         $offset    = null)
    {
        /*
        Connexions::log('Model_Mapper_Group::getItems(): '
                        .   'groupId[ %s ], type[ %s ], '
                        .   'order[ %s ], count[ %s ], offset[ %s ]',
                        Connexions::varExport($group->groupId),
                        $group->groupType,
                        Connexions::varExport($order),
                        Connexions::varExport($count),
                        Connexions::varExport($offset));
        // */

        switch ($group->groupType)
        {
        case 'user':        $mapperName   = 'Model_Mapper_User';
                            break;
        case 'tag':         $mapperName   = 'Model_Mapper_Tag';
                            break;
        case 'item':        $mapperName   = 'Model_Mapper_Item';
                            break;
        case 'bookmark':    $mapperName   = 'Model_Mapper_Bookmark';
                            break;
        default:
            throw new Exception("Unexpected groupType[ {$group->groupType} ]");
            break;
        }

        $mapper = $this->factory($mapperName);
        if ($group->groupId !== null)
        {
            $tableName = $mapper->getTableName();
            $select    = $mapper->select();
            $select->from( array('gt' => 'groupItem'))
                   ->where('gt.groupId=?', $group->groupId)
                   ->join(array('i' => $tableName),
                          "gt.itemId=i.{$tableName}Id")
                   ->reset(Zend_Db_Select::COLUMNS)
                   ->columns('i.*')
                   ->group("i.{$tableName}Id");
            if ($order !== null)
            {
                $select->order($order);
            }
            if (($count !== null) || ($offset !== null))
            {
                $select->limit($count, $offset);
            }

            /*
            Connexions::log("Model_Mapper_Group::getItems(): "
                            .   "%s:select[ %s ]",
                            get_class($select),
                            $select->assemble());
            // */

            $items = $mapper->fetch($select);
            $items->setContext($select);
        }
        else
        {
            $items = $mapper->makeEmptySet();

            /*
            Connexions::log('Model_Mapper_Group::getItems(): '
                            .   'groupId[ %s ], type[ %s ]: empty set[ %s ]',
                            Connexions::varExport($group->groupId),
                            $group->groupType,
                            $items->debugDump());
            // */
        }


        return $items;
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

    /**********************************************
     * Member management
     *
     */

    /** @brief  Add the given user to the provided group.
     *  @param  group   The Model_Group instance;
     *  @param  user    The Model_User instance to add.
     *
     *  @return $this for a fluent interface.
     */
    public function addMember(Model_Group   $group,
                              Model_User    $user)
    {
        /* We can only add 'backed' users (since we rely on the database
         * id for the join table), so ensure that it's backed now.
         */
        if (! $user->isBacked())
        {
            $user = $user->save();
            if (! $user->isBacked())
            {
                throw new Exception("Non-backed user cannot be added");
            }
        }

        $table = $this->getAccessor('Model_DbTable_GroupMember');
        $table->insert( array('groupId' => $group->getId(),
                              'userId'  => $user->getId()) );

        return $this;
    }

    /** @brief  Remove the given user to the provided group.
     *  @param  group   The Model_Group instance;
     *  @param  user    The Model_User instance to remove.
     *
     *  @return $this for a fluent interface.
     */
    public function removeMember(Model_Group    $group,
                                 Model_User     $user)
    {
        /* We can only remove 'backed' users (since we rely on the
         * database id for the join table).
         */
        if (! $user->isBacked())
        {
            throw new Exception("Non-backed user cannot be removed");
        }

        $table = $this->getAccessor('Model_DbTable_GroupMember');
        $table->delete( array('groupId=?' => $group->getId(),
                              'userId=?'  => $user->getId()) );

        return $this;
    }

    /**********************************************
     * Item management
     *
     */

    /** @brief  Add the given item to the provided group.
     *  @param  group   The Model_Group instance;
     *  @param  item    The item to add.
     *
     *  @return $this for a fluent interface.
     */
    public function addItem(Model_Group      $group,
                            Connexions_Model $item)
    {
        /*
        Connexions::log("Model_Mapper_Group::addItem(): "
                        .   "group[ %s ], type[ %s ], class[ %s ]",
                        $group, $group->groupType, get_class($item));
        // */

        /* Ensure that $item is the proper TYPE of Connexions_Model based upon
         * 'groupType' (user, item, tag)
         */
        if ( get_class($item) !== 'Model_'. ucfirst($group->groupType) )
        {
            throw new Exception("Unexpected model instance for "
                                . "'". $group->groupType ."' group");

        }

        /* We can only add 'backed' items (since we rely on the item's database
         * id for the join table), so ensure that it's backed now.
         */
        if (! $item->isBacked())
        {
            $item = $item->save();
            if (! $item->isBacked())
            {
                throw new Exception("Non-backed item cannot be added");
            }
        }

        $table = $this->getAccessor('Model_DbTable_GroupItem');
        $table->insert( array('groupId' => $group->getId(),
                              'itemId'  => $item->getId()) );

        return $this;
    }

    /** @brief  Remove the given item to the provided group.
     *  @param  group   The Model_Group instance;
     *  @param  item    The item to remove.
     *
     *  @return $this for a fluent interface.
     */
    public function removeItem(Model_Group      $group,
                               Connexions_Model $item)
    {
        /*
        Connexions::log("Model_Mapper_Group::removeItem(): "
                        .   "group[ %s ], type[ %s ], class[ %s ]",
                        $group, $group->groupType, get_class($item));
        // */

        /* Ensure that $item is the proper TYPE of Connexions_Model based upon
         * 'groupType' (user, item, tag)
         */
        if ( get_class($item) !== 'Model_'. ucfirst($group->groupType) )
        {
            throw new Exception("Unexpected model instance for "
                                . "'". $group->groupType ."' group");

        }

        /* We can only remove 'backed' items (since we rely on the item's
         * database id for the join table).
         */
        if (! $item->isBacked())
        {
            throw new Exception("Non-backed item cannot be removed");
        }

        $table = $this->getAccessor('Model_DbTable_GroupItem');
        $table->delete( array('groupId=?' => $group->getId(),
                              'itemId=?'  => $item->getId()) );

        return $this;
    }

    /*********************************************************************
     * Protected helpers
     *
     */

    /** @brief  Given identification value(s) that will be used for retrieval,
     *          normalize the values for 'ownerId'.
     *  @param  id      Identification value(s) (string, integer, array).
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value pairs.
     *
     *  @return An array containing attribute/value(s) pairs suitable for
     *          retrieval.
     */
    protected function _normalizeOwnerId($id)
    {
        // Employ Model_Mapper_User to interpret 'userId'
        $uMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $ids     = $uMapper->normalizeIds($id);

        if ( is_string($ids) ||
             (is_array($ids) && isset($ids['name'])) )
        {
            // See if there is an existing user with the given id.
            $owners = $uMapper->fetch( $ids );
            $ids    = $owners->getIds();
        }
        else if (is_array($ids))
        {
            $ids = $ids['userId'];
        }

        /*
        Connexions::log('Mapper_Group::_normalizeOwnerId(): 2:'
                        .   'id[ %s ], ids[ %s ]',
                        Connexions::varExport($id),
                        Connexions::varExport($ids));
        // */

        return $ids;
    }

    /** @brief  Generate the secondary SQL select, primarily for restricting
     *          for privacy.
     *  @param  select      The primary Zend_Db_Select instance.
     *  @param  primeAs     The alias of the table in the primary select;
     *  @param  params      An array retrieval criteria.
     *
     *  @return The Zend_Db_Select instance.
     */
    protected function _includeSecondarySelect(Zend_Db_Select  $select,
                                                               $primeAs,
                                               array           $params)
    {
        $as        = 'gm';

        $orderBy   = (isset($params['order'])
                        ? (is_array($params['order'])
                            ? $params['order']
                            : array($params['order']))
                        : array());
        $groupBy   = $this->_keyNames;

        $db        = $select->getAdapter();
        $secSelect = $db->select();
        $secSelect->from(array($as => 'groupMember'),
                         array("{$as}.*"))
                  ->group( $groupBy );

        $joinCond = array();
        foreach ($secSelect->getPart(Zend_Db_Select::GROUP) as $idex => $name)
        {
            array_push($joinCond, "{$primeAs}.{$name}={$as}.{$name}");
        }

        // Join the select and sub-select
        $select->join(array($as => $secSelect),
                      implode(' AND ', $joinCond),
                      null);

        /***************************************************************
         * include any limiters in the sub-select
         *
         */

        // Bookmarks
        if ( isset($params['bookmarks']) && (! empty($params['bookmarks'])) )
        {
            $bookmarks =& $params['bookmarks'];

            if ($bookmarks instanceof Model_Set_Bookmark)
            {
                if (count($bookmarks) > 0)
                {
                    $secSelect->where('(userId,itemId) IN ?',
                                      $bookmarks->getIds());
                }
            }
            else if (is_array($bookmarks))
            {
                if (count($bookmarks) > 0)
                {
                    $secSelect->where('(userId,itemId) IN ?', $bookmarks);
                }
            }
            else if ($bookmarks instanceof Model_Bookmark)
            {
                $secSelect->where('(userId,itemId)=?',
                                   array($bookmarks->userId,
                                         $bookmarks->itemId));
            }
            else
            {
                $secSelect->where('(userId,itemId)=?', $bookmarks);
            }
        }

        // Users
        if ( isset($params['users']) && (! empty($params['users'])) )
        {
            $users =& $params['users'];

            if ($users instanceof Model_Set_User)
            {
                if (count($users) > 0)
                {
                    $secSelect->where('userId IN ?',
                                      $users->getIds());
                }
            }
            else if (is_array($users))
            {
                if (count($users) > 0)
                {
                    $secSelect->where('userId IN ?', $users);
                }
            }
            else if ($users instanceof Model_User)
            {
                $secSelect->where('userId=?', $users->userId);
            }
            else
            {
                $secSelect->where('userId=?', $users);
            }

            // Default 'exactUsers' is false
            if ( (isset($params['exactUsers'])) &&
                 ($params['exactUsers'] === true) )
            {
                $nUsers = count($users);
                if ($nUsers > 1)
                {
                    /*
                    Connexions::log("Model_Mapper_Base::fetchRelated(): "
                                    . "exactly %d users",
                                    $nUsers);
                    // */

                    $secSelect->having('userCount='. $nUsers);
                }
            }
        }

        // Items
        if ( isset($params['items']) && (! empty($params['items'])) )
        {
            $items =& $params['items'];

            if ($items instanceof Model_Set_Item)
            {
                if (count($items) > 0)
                {
                    $secSelect->where("{$as}.itemId IN ?",
                                      $items->getIds());
                }
            }
            else if (is_array($items))
            {
                if (count($items) > 0)
                {
                    $secSelect->where("{$as}.itemId IN ?", $items);
                }
            }
            else if ($items instanceof Model_Item)
            {
                $secSelect->where("{$as}.itemId=?", $items->itemId);
            }
            else
            {
                $secSelect->where("{$as}.itemId=?", $items);
            }

            /* Doesn't really make sense to restrict based upon itemCount
             * since in most contexts, itemCount will be 1.
             *
            if ( (! isset($params['exactItems'])) ||
                 ($params['exactItems'] !== false) )
            {
                $nItems = count($items);
                if ($nItems > 1)
                {
                    $secSelect->having('itemCount='. $nItems);
                }
            }
            */
        }

        // Tags
        if ( isset($params['tags']) && (! empty($params['tags'])) )
        {
            $tags =& $params['tags'];

            if ($tags instanceof Model_Set_Tag)
            {
                if (count($tags) > 0)
                {
                    $secSelect->where('tagId IN ?',
                                      $tags->getIds());
                }
            }
            else if (is_array($tags))
            {
                if (is_int($tags[0]))
                {
                    $secSelect->where('tagId IN ?', $tags);
                }
                else if (count($tags) > 0)
                {
                    // :NOTE: The primary table MUST have a 'tag' field
                    $select->where('tag IN ?', $tags);
                }
            }
            else if (is_int($tags))
            {
                $secSelect->where('tagId=?', $tags);
            }
            else if ($tags instanceof Model_Tag)
            {
                $secSelect->where('tagId=?', $tags->tagId);
            }
            else
            {
                // :NOTE: The primary table MUST have a 'tag' field
                $select->where('tag=?', $tags);
            }

            // Default 'exactTags' is true
            if ( (! isset($params['exactTags'])) ||
                 ($params['exactTags'] !== false) )
            {
                $nTags = count($tags);
                if ($nTags > 1)
                {
                    /*
                    Connexions::log("Model_Mapper_Base::fetchRelated(): "
                                    . "exactly %d tags",
                                    $nTags);
                    // */

                    $secSelect->having('tagCount='. $nTags);
                }
            }
        }

        return $secSelect;
    }
}
