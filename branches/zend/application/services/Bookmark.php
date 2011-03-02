<?php
/** @file
 *
 *  The concrete base class providing access to Model_Bookmark and 
 *  Model_Set_Bookmark.
 */
class Service_Bookmark extends Connexions_Service
{
    /* inferred via classname
    protected   $_modelName = 'Model_Bookmark';
    protected   $_mapper    = 'Model_Mapper_Bookmark'; */

    /** @brief  Any default ordering that should be be merged into a specified 
     *          order.
     */
    protected   $_defaultOrdering   = array(
        'taggedOn'  => 'DESC',
        'name'      => 'ASC',
        'userCount' => 'DESC',
        'tagCount'  => 'DESC',
    );

    /** @brief  Find an existing Domain Model instance, updating it with the
     *          provided data, or Create a new Domain Model instance,
     *          initializing it with the provided data.
     *  @param  id      Identification value(s) (string, integer, array).
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value pairs.
     *                  For a Model_Bookmark, there are a few special
     *                  attributes supported:
     *                      1) The user/owner may be identified in one of three
     *                         ways:
     *                          - 'userId'  as an integer identifier;
     *                          - 'userId'  as a  string user-name;
     *
     *                      2) The referenced Item may be identified in one of
     *                         seven ways:
     *                          - 'itemId'  as an integer identifier;
     *                          - 'itemId'  as a  string url;
     *                          - 'itemId'  as a  string url-hash;
     *
     *                      3) Tags may be identified in one of two ways:
     *                          - 'tags'    as a Model_Set_Tag instance;
     *                          - 'tags'    as a comma-separated string;
     *
     *  @return A (possibly new) Domain Model instance.
     *          Note: If the returned instance is new or modified, and the
     *                caller wishes the instance to persist, they must invoke:
     *                    $model = $model->save()
     */
    public function get($id)
    {
        $normId = $this->_mapper->normalizeId($id);

        /*
        Connexions::log("Service_Bookmark::get(): "
                        . "id[ %s ] == normalized[ %s ]",
                        Connexions::varExport($id),
                        Connexions::varExport($normId));
        // */

        if (! isset($normId['itemId']))
        {
            // No existing item was found.  Can we create one now?
            if ( empty($id['url']) )
            {
                throw new Exception("Cannot create the missing item from "
                                    . "[ "
                                    .   Connexions::varExport($normId)
                                    . " ]");
            }

            /*
            Connexions::log("Service_Bookmark::get(): "
                            . "create new item using [ %s ]",
                            $normId['url']);
            // */

            // Create a NEW item!
            $iService = $this->factory('Service_Item');
            $item     = $iService->get( array('url' => $id['url'] ));
            if (! $item->isBacked())
                $item = $item->save();

            unset($id['url']);
            unset($normId['url']);

            $normId['itemId'] = $item->itemId;
        }

        if (is_array($id))
        {
            // Extract any specified tags, creating any that aren't found...
            $tags = $this->_prepareTags($id, true);
            unset($id['tags']);
        }

        /************************************************************
         * Does a matching bookmark already exists?
         *
         */
        $bookmark = parent::find( array(
                                    'userId' => $normId['userId'],
                                    'itemId' => $normId['itemId'],
                                  ));
        if ($bookmark !== null)
        {
            /*
            Connexions::log("Service_Bookmark::get(): "
                            . "found bookmark[ %s ]",
                            $bookmark->debugDump());
            // */

            // Update this bookmark with any new, incoming data.
            if (is_array($id))
            {
                // Make sure we don't over-ride any of the keys...
                unset($id['userId']);
                unset($id['itemId']);

                $bookmark->populate($id);
            }
        }
        else
        {
            /*
            Connexions::log("Service_Bookmark::get(): "
                            . "create new bookmark...");
            // */

            // When creating a bookmark, there MUST be tags.
            if (empty($tags))
            {
                throw new Exception("No tags provided.");
            }

            /* Merge the normalized id information back into the original,
             * incoming id data.
             */
            if (! is_array($id))
                $id = array();
            $id = array_merge($id, $normId);


            // Create a new instance.
            $bookmark = $this->_mapper->getModel($id);
        }

        if (! empty($tags))
        {
            /* Add the specified set of tags to the bookmark.
             * For an existing bookmark, this will remove any previous tags and 
             * add the tags in the new set.
             */
            $bookmark->tags = $tags;
        }

        return $bookmark;
    }

    /** @brief  Find an existing Domain Model instance.
     *  @param  id      Identification value(s) (string, integer, array).
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value pairs.
     *                  For a Model_Bookmark, there are a few special
     *                  attributes supported:
     *                      1) The user/owner may be identified in one of three
     *                         ways:
     *                          - 'user'   as a  Model_User instance;
     *                          - 'userId' as an integer identifier;
     *                          - 'userId' as a  string user-name;
     *
     *                      2) The referenced Item may be identified in one of
     *                         seven ways:
     *                          - 'item'        as a  Model_Item instance;
     *                          - 'itemId'      as an integer identifier;
     *                          - 'itemId'      as a  string url-hash;
     *                          - 'itemUrlHash' as a  string url-hash;
     *                          - 'urlHash'     as a  string url-hash;
     *                          - 'itemUrl'     as a  string url;
     *                          - 'url'         as a  string url;
     *
     *  @return A Domain Model instance, or null if not found.
     */
    public function find($id)
    {
        $normId = $this->_mapper->normalizeId($id);

        /*
        Connexions::log("Service_Bookmark::find(): normalized id[ %s ]",
                        Connexions::varExport($normId));
        // */

        if ( empty($normId['userId']) || empty($normId['itemId']) )
        {
            return null;
        }

        return parent::find(array(
                                'userId' => $normId['userId'],
                                'itemId' => $normId['itemId'],
                            ));
    }

    /** @brief  Retrieve a set of Domain Model instances.
     *  @param  id      Identification value(s), null to retrieve all.
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value(s) pairs.
     *  @param  order   An array of name/direction pairs representing the
     *                  desired sorting order.  The 'name's MUST be valid for
     *                  the target Domain Model and the directions a
     *                  Connexions_Service::SORT_DIR_* constant.  If an order
     *                  is omitted, Connexions_Service::SORT_DIR_ASC will be
     *                  used [ no specified order ];
     *  @param  count   The maximum number of items from the full set of
     *                  matching items that should be returned
     *                  [ null == all ];
     *  @param  offset  The starting offset in the full set of matching items
     *                  [ null == 0 ].
     *  @param  since   Limit the results to bookmarks updated after this
     *                  date/time [ null == no time limits ];
     *
     *  @return A new Connexions_Model_Set.
     */
    public function fetch($id       = null,
                          $order    = null,
                          $count    = null,
                          $offset   = null,
                          $since    = null)
    {
        $ids     = $this->_csList2array($id);
        $normIds = $this->_mapper->normalizeIds($ids);
        $order   = $this->_csOrder2array($order);

        $normIds = $this->_includeSince($normIds, $since);

        return $this->_mapper->fetch( $normIds,
                                      $order,
                                      $count,
                                      $offset );
    }

    /** @brief  Retrieve a paginated set of Domain Model instances.
     *  @param  id      An array of 'property/value' pairs identifying the
     *                  desired model(s), or null to retrieve all.
     *  @param  order   An array of name/direction pairs representing the
     *                  desired sorting order.  The 'name's MUST be valid for
     *                  the target Domain Model and the directions a
     *                  Connexions_Service::SORT_DIR_* constant.  If an order
     *                  is omitted, Connexions_Service::SORT_DIR_ASC will be
     *                  used [ no specified order ];
     *  @param  since   Limit the results to bookmarks updated after this
     *                  date/time [ null == no time limits ];
     *
     *  @return A new Connexions_Model_Set.
     */
    public function fetchPaginated($id      = null,
                                   $order   = null,
                                   $since   = null)
    {
        $ids     = $this->_csList2array($id);
        $normIds = $ids;    //$this->_mapper->normalizeIds($ids);
        $order   = $this->_csOrder2array($order);

        $normIds = $this->_includeSince($normIds, $since);

        $set = $this->_mapper->fetch( $normIds, $order );
        return new Zend_Paginator( $set->getPaginatorAdapter() );
    }
                                      
    /** @brief  Retrieve a set of bookmarks related by a set of Tags.
     *  @param  tags    A Model_Set_Tag instance, array, or comma-separated
     *                  string of tags to match.
     *  @param  exact   Bookmarks MUST be associated with provided tags
     *                  [ true ];
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ [ 'tagCount      DESC',
     *                          'taggedOn      DESC',
     *                          'name          ASC',
     *                          'userCount     DESC' ] ]
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *  @param  since   Limit the results to bookmarks updated after this
     *                  date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByTags($tags,
                                $exact   = true,
                                $order   = null,
                                $count   = null,
                                $offset  = null,
                                $since   = null)
    {
        $to = array('tags'      => $tags,
                    'exactTags' => $exact,
                    'where'     => $this->_includeSince(array(), $since) );

        return $this->fetchRelated( $to,
                                    $order,
                                    $count,
                                    $offset );
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Users.
     *  @param  users   A Model_Set_User instance, array, or comma-separated
     *                  string of users to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ [ 'taggedOn      DESC',
     *                          'name          ASC',
     *                          'userCount     DESC',
     *                          'tagCount      DESC' ] ]
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *  @param  since   Limit the results to bookmarks updated after this
     *                  date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByUsers($users,
                                 $order   = null,
                                 $count   = null,
                                 $offset  = null,
                                 $since   = null)
    {
        $to = array('users'      => $users,
                    'exactUsers' => false,  // userCount doesn't matter
                    'where'      => $this->_includeSince(array(), $since) );

        return $this->fetchRelated( $to,
                                    $order,
                                    $count,
                                    $offset );
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Items.
     *  @param  items   A Model_Set_Item instance, array, or comma-separated
     *                  string of items to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ [ 'taggedOn      DESC',
     *                          'name          ASC',
     *                          'userCount     DESC',
     *                          'tagCount      DESC' ] ]
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *  @param  since   Limit the results to bookmarks updated after this
     *                  date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByItems($items,
                                 $order   = null,
                                 $count   = null,
                                 $offset  = null,
                                 $since   = null)
    {
        $to = array('items'      => $items,
                    'exactItems' => false,  // itemCount doesn't matter
                    'where'      => $this->_includeSince(array(), $since) );

        return $this->fetchRelated( $to,
                                    $order,
                                    $count,
                                    $offset );
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Users and Tags.
     *  @param  users       A Model_Set_User instance, array, or
     *                      comma-separated string of users to match.
     *  @param  tags        A Model_Set_Tag instance, array, or comma-separated
     *                      string of tags to match.
     *  @param  exactUsers  Bookmarks MUST be associated with ALL provided
     *                      users [ false ];
     *  @param  exactTags   Bookmarks MUST be associated with provided tags
     *                      [ true ];
     *  @param  order       Optional ORDER clause (string, array)
     *                      [ [ 'taggedOn      DESC',
     *                          'name          ASC',
     *                          'userCount     DESC',
     *                          'tagCount      DESC' ] ]
     *  @param  count       Optional LIMIT count
     *  @param  offset      Optional LIMIT offset
     *  @param  since       Limit the results to bookmarks updated after this
     *                      date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByUsersAndTags($users,
                                        $tags,
                                        $exactUsers = false,
                                        $exactTags  = true,
                                        $order      = null,
                                        $count      = null,
                                        $offset     = null,
                                        $since      = null)
    {
        $to = array('users'      => $users,
                    'tags'       => $tags,
                    'exactUsers' => $exactUsers,
                    'exactTags'  => $exactTags,
                    'where'      => $this->_includeSince(array(), $since) );

        return $this->fetchRelated( $to,
                                    $order,
                                    $count,
                                    $offset );
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Items and Tags.
     *  @param  items       A Model_Set_Item instance, array, or
     *                      comma-separated string of items to match.
     *  @param  tags        A Model_Set_Tag instance, array, or comma-separated
     *                      string of tags to match.
     *  @param  exactTags   Bookmarks MUST be associated with provided tags
     *                      [ true ];
     *  @param  order       Optional ORDER clause (string, array)
     *                      [ [ 'taggedOn      DESC',
     *                          'name          ASC',
     *                          'userCount     DESC',
     *                          'tagCount      DESC' ] ]
     *  @param  count       Optional LIMIT count
     *  @param  offset      Optional LIMIT offset
     *  @param  since       Limit the results to bookmarks updated after this
     *                      date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByItemsAndTags($items,
                                        $tags,
                                        $exact   = true,
                                        $order   = null,
                                        $count   = null,
                                        $offset  = null,
                                        $since   = null)
    {
        $to = array('items'      => $items,
                    'tags'       => $tags,
                    'exactItems' => false,  // itemCount doesn't matter
                    'exactTags'  => $exact,
                    'where'      => $this->_includeSince(array(), $since) );

        return $this->fetchRelated( $to,
                                    $order,
                                    $count,
                                    $offset );
    }

    /** @brief  Retrieve the set of bookmarks in the given user's inbox
     *          that have been updated since the given date/time and
     *          have all of the given tags.
     *  @param  user    A Model_Set_User instance representing the target user.
     *  @param  tags    A Model_Set_Tag instance, array, or comma-separated
     *                  string of tags to match
     *                  (retrieved bookmarks will have ALL tags).
     *  @param  since   Limit the results to bookmarks updated after this
     *                  date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchInbox($user,
                               $tags    = null,
                               $since   = null)
    {
        if (! $user instanceof Model_User)
        {
            $user = $this->factory('Service_User')
                            ->find($user);

            if (! $user)
            {
                // Unknown user -- empty Inbox
                return $this->_mapper->makeEmptySet();
            }
        }

        /*****************************************************
         * Generate a 'for:%user%' tag and append it to any
         * provided tags.
         *
         */
        $forTag = 'for:'. $user->name;
        if (empty($tags))
        {
            $tags = $forTag;
        }
        else if (is_string($tags))
        {
            if (! preg_match('/,\s*$/', $tags))
                $tags .= ',';
            $tags .= $forTag;
        }
        else if (is_array($tags))
        {
            array_push($tags, $forTag);
        }
        else if ( $tags instanceof Model_Set_Tag )
        {
            $tags->append( $this->factory('Service_tag')
                                    ->get($forTag) );
        }

        /*****************************************************
         * Construct a 'to' relation restriction that includes
         * the user, ALL tags, and a 'since'
         *
         */
        $to = array('tags'       => $tags,
                    'exactTags'  => true,
                    'where'      => $this->_includeSince(array(), $since) );

        return $this->fetchRelated( $to );
    }

    /** @brief  Perform tag autocompletion within the given context.
     *  @param  term        The string to autocomplete.
     *  @param  tags        A Model_Set_Tag instance, array, or comma-separated
     *                      string of tags that restrict the bookmarks that
     *                      should be used to select related tags -- one
     *                      component of 'context';
     *  @param  users       A Model_Set_User instance, array, or
     *                      comma-separated string of users that restrict the
     *                      bookmarks that should be used to select related
     *                      tags -- a second component of 'context';
     *  @param  limit       The maximum number of tags to return;
     *
     *  @return Model_Set_Tag
     */
    public function autocompleteTag($str,
                                    $tags   = null,
                                    $users  = null,
                                    $limit  = 50)
    {
        /*
        Connexions::log("Service_Bookmark::autocompleteTag(): "
                        .   "str[ %s ], tags[ %s ], users[ %s ], limit[ %d ]",
                        $str, $tags, $users, $limit);
        // */

        /* Rely on Service_Tag/Service_User to properly interpret 'tags' and
         * 'users'
         */
        $tService = $this->factory('Service_Tag');

        if ( (! empty($tags)) || (! empty($users)) )
        {
            // Retrieve the set of bookmarks that we need related tags for
            $bookmarks = $this->fetchByUsersAndTags($users, $tags);
        }
        else
        {
            $bookmarks = null;
        }

        /*
        Connexions::log("Service_Bookmark::autocompleteTag(): "
                        .   "bookmarks[ %s ]",
                        $bookmarks);
        // */

        return $tService->fetchByBookmarks($bookmarks,
                                           null,        // default order
                                           $limit,
                                           null,        // default offset
                                           array('tag=^' => $str));
    }

    /** @brief  Update/Create a bookmark.
     *  @param  id          Identification value(s) (string, integer, array).
     *                      MAY be an associative array that specifically
     *                      identifies attribute/value pairs.
     *                      For a Model_Bookmark, there are a few special
     *                      attributes supported:
     *                          1) The user/owner may be identified in one of
     *                             three ways:
     *                              - 'user'   as a  Model_User instance;
     *                              - 'userId' as an integer identifier;
     *                              - 'userId' as a  string user-name;
     *
     *                          2) The referenced Item may be identified in one
     *                             of seven ways:
     *                              - 'item'        as a  Model_Item instance;
     *                              - 'itemId'      as an integer identifier;
     *                              - 'itemId'      as a  string url-hash;
     *                              - 'itemUrlHash' as a  string url-hash;
     *                              - 'urlHash'     as a  string url-hash;
     *                              - 'itemUrl'     as a  string url;
     *                              - 'url'         as a  string url;
     *  @param  name        If non-empty, the (new) Bookmark name;
     *  @param  description If non-null,  the (new) description;
     *  @param  rating      If >= 0,      the (new) rating;
     *  @param  isFavorite  If non-null,  the (new) favorite value;
     *  @param  isPrivate   If non-null,  the (new) privacy value;
     *  @param  tags        If non-empty, the (new) set of tags;
     *  @param  url         If non-empty, the (new) URL associated with this
     *                      bookmark (MAY create a new Item);
     *
     *  @return Model_Bookmark
     */
    public function update($id,
                           $name            = null,
                           $description     = null,
                           $rating          = -1,
                           $isFavorite      = null,
                           $isPrivate       = null,
                           $tags            = null,
                           $url             = null)
    {
        /*
        Connexions::log("Service_Bookmark::update() "
                        . "id[ %s ], name[ %s ], description[ %s ], "
                        . "rating[ %s ], isFavorite[ %s ], isPrivate[ %s ], "
                        . "tags[ %s ], url[ %s ]",
                        Connexions::varExport($id),
                        Connexions::varExport($name),
                        Connexions::varExport($description),
                        Connexions::varExport($rating),
                        Connexions::varExport($isFavorite),
                        Connexions::varExport($isPrivate),
                        Connexions::varExport($tags),
                        Connexions::varExport($url));
        // */

        // First, attempt to normalize the incoming bookmark id.
        $id = $this->_mapper->normalizeId($id);

        /* Now, if the bookmark's userId != the current authenticated userId,
         * FAIL.
         */
        if (empty($id['userId']))
        {
            $id['userId'] = $this->_curUser()->userId;
        }
        else if ($id['userId'] !== $this->_curUser()->userId)
        {
            throw new Exception("Cannot update bookmarks of/for others");
        }

        // Finally, if we weren't given an itemId, user any incoming 'url'
        if (empty($id['itemId']))
        {
            $id['itemId'] = $url;
        }

        /*
        Connexions::log("Service_Bookmark::update(): "
                        . "adjusted id[ %s ]",
                        Connexions::varExport($id));
        // */

        try
        {
            $bookmark = $this->get($id);
        }
        catch (Exception $e)
        {
            $bookmark = null;
        }

        if (! $bookmark)
        {
            throw new Exception('Cannot locate bookmark [ '
                                . Connexions::varExport($id) .' ]');
        }

        $update = array();

        if (! empty($name))         $bookmark->name         = $name;
        if (! empty($url))          $bookmark->url          = $url;
        if (! empty($description))  $bookmark->description  = $description;
        if (  $rating     >=  -1)   $bookmark->rating       = $rating;
        if (  $isFavorite !== null) $bookmark->isFavorite   = $isFavorite;
        if (  $isPrivate  !== null) $bookmark->isPrivate    = $isPrivate;
        if (! empty($tags))
        {
            $bookmark->tags = $this->_prepareTags( array('tags' => $tags),
                                                   true );
        }

        /*
        Connexions::log("Service_Bookmark::update() "
                        .   "update [ %s ], array[ %s ]",
                        $bookmark->debugDump(),
                        Connexions::varExport($bookmark->toArray()));
        // */

        if (! $bookmark->isValid())
        {
            $msgs = $bookmark->getValidationMessages();

            Connexions::log("Service_Bookmark::update() "
                            .   "invalid bookmark [ %s ]",
                            Connexions::varExport($msgs));

            throw new Exception ('Invalid bookmark [ '
                                 . Connexions::varExport($msgs)
                                 . ' ]');
        }

        $bookmark = $bookmark->save();

        /*
        Connexions::log("Service_Bookmark::update() "
                        .   "updated [ %s ]",
                        $bookmark->debugDump());
        // */

        return $bookmark;
    }

    /** @brief  Delete a bookmark.
     *  @param  id          Identification value(s) (string, integer, array).
     *                      MAY be an associative array that specifically
     *                      identifies attribute/value pairs.
     *                      For a Model_Bookmark, there are a few special
     *                      attributes supported:
     *                          1) The user/owner may be identified in one of
     *                             three ways:
     *                              - 'user'   as a  Model_User instance;
     *                              - 'userId' as an integer identifier;
     *                              - 'userId' as a  string user-name;
     *
     *                          2) The referenced Item may be identified in one
     *                             of seven ways:
     *                              - 'item'        as a  Model_Item instance;
     *                              - 'itemId'      as an integer identifier;
     *                              - 'itemId'      as a  string url-hash;
     *                              - 'itemUrlHash' as a  string url-hash;
     *                              - 'urlHash'     as a  string url-hash;
     *                              - 'itemUrl'     as a  string url;
     *                              - 'url'         as a  string url;
     *
     *  @return void
     */
    public function delete($id)
    {
        // /*
        Connexions::log("Service_Bookmark::delete(): id[ %s ]",
                        Connexions::varExport($id));
        // */

        /* So we can FAIL if the given userId is NOT the current authenticated
         * user's id without leaking whether or not the bookmark exists, we
         * first, attempt to normalize the incoming bookmark id.
         */
        $id = $this->_mapper->normalizeId($id);

        /* If the bookmark's userId != the current authenticated userId,
         * FAIL.
         */
        if (empty($id['userId']))
        {
            $id['userId'] = $this->_curUser()->userId;
        }
        else if ($id['userId'] !== $this->_curUser()->userId)
        {
            throw new Exception("Cannot delete bookmarks of/for others");
        }

        $bookmark = $this->find($id);

        // If the bookmark wasn't found, FAIL
        if (! $bookmark)
        {
            throw new Exception('Cannot locate bookmark [ '
                                . Connexions::varExport($id) .' ]');
        }

        // /*
        Connexions::log("Service_Bookmark::delete(): bokmark [ %s ]",
                        $bookmark->debugDump());
        // */

        $bookmark->delete();
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Include a date/time restriction.
     *  @param  id      The identifier to add date/time restrictions to;
     *  @param  since   Limit the results to bookmarks updated after this
     *                  date/time [ null == no time limits ];
     *
     *  @return The (possibly) modified 'id'.
     */
    protected function _includeSince(array $id, $since)
    {
        if (is_string($since))
        {
            $since = strtotime($since);
            if ($since !== false)
            {
                // Include an additional condition in 'normIds'
                $id['updatedOn >='] = strftime('%Y-%m-%d %H:%M:%S', $since);
            }
        }

        return $id;
    }

    /** @brief  Given an array of name/value pairs to be used in creating a new
     *          Bookmark, see if there is a valid Model_Set_Tag identified.
     *  @param  data    An array of name/value pairs to be used to initialize
     *                  a new Model_Bookmark instance.  The referenced
     *                  Model_Set_Tag may be identified in one of two ways:
     *                          - 'tags' as a Model_Set_Tag instance;
     *                          - 'tags' as a comma-separated string;
     *  @param  create  Should tags that aren't found be created?
     *
     *  @return A Model_Set_Tag instance of resolved tags, null if none found.
     */
    protected function _prepareTags(array $data, $create = false)
    {
        $tags = null;
        if ( isset($data['tags']))
        {
            if ($data['tags'] instanceof Model_Set_Tag)
            {
                $tags = $data['tags'];
            }
            else if (is_array($data['tags']))
            {
                $data['tags'] = implode(',', $data['tags']);
            }

            if (is_string($data['tags']))
            {
                /* ASSUME that this is a comma-separated list of tags, telling
                 * csList2set() to create any tags that don't already exist.
                 */
                $tService = Connexions_Service::factory('Service_Tag');
                $tags     = $tService->csList2set($data['tags'], null, $create);
            }
        }

        if (empty($tags))
            $tags = null;

        /*
        if ( ($tags === null) || empty($tags) )
        {
            throw new Exception("No tags provided.");
        }
         */

        return $tags;
    }
}
