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
            if ( empty($normId['url']) )
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
            $item     = $iService->get( array('url' => $normId['url'] ));
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
     *
     *  @return A new Connexions_Model_Set.
     */
    public function fetch($id       = null,
                          $order    = null,
                          $count    = null,
                          $offset   = null)
    {
        $ids     = $this->_csList2array($id);
        $normIds = $this->_mapper->normalizeIds($ids);
        $order   = $this->_csOrder2array($order);

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
     *
     *  @return A new Connexions_Model_Set.
     */
    public function fetchPaginated($id      = null,
                                   $order   = null)
    {
        $ids     = $this->_csList2array($id);
        $normIds = $ids;    //$this->_mapper->normalizeIds($ids);
        $order   = $this->_csOrder2array($order);

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
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByTags($tags,
                                $exact   = true,
                                $order   = null,
                                $count   = null,
                                $offset  = null)
    {
        // Rely on Service_Tag to properly interpret 'tags'
        $tags = $this->factory('Service_Tag')->csList2set($tags);

        if ($order === null)
        {
            $order   = array(
                             'tagCount      DESC',
                             'taggedOn      DESC',
                             'name          ASC',
                             'userCount     DESC',
                       );
        }
        else
        {
            $order = $this->_extraOrder($order);
        }

        $tags = $this->_prepareTags( array('tags' => $tags) );

        return $this->_mapper->fetchRelated( array(
                                        'tags'      => $tags,
                                        'exactTags' => $exact,
                                        'order'     => $order,
                                        'count'     => $count,
                                        'offset'    => $offset,
                                        'where'     => $where,
                                        'privacy'   => $this->_curUser(),
                                    ));
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
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByUsers($users,
                                 $order   = null,
                                 $count   = null,
                                 $offset  = null)
    {
        // Rely on Service_User to properly interpret 'users'
        $users = $this->factory('Service_User')->csList2set($users);

        if ($order === null)
        {
            $order   = array(
                             'taggedOn      DESC',
                             'name          ASC',
                             'userCount     DESC',
                             'tagCount      DESC',
                       );
        }
        else
        {
            $order = $this->_extraOrder($order);
        }

        return $this->_mapper->fetchRelated( array(
                                        'users'   => $users,
                                        'order'   => $order,
                                        'count'   => $count,
                                        'offset'  => $offset,
                                        'privacy' => $this->_curUser(),
                                    ));
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
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByItems($items,
                                 $order   = null,
                                 $count   = null,
                                 $offset  = null)
    {
        // Rely on Service_Item to properly interpret 'items'
        $items = $this->factory('Service_Item')->csList2set($items);

        if ($order === null)
        {
            $order   = array(
                             'taggedOn      DESC',
                             'name          ASC',
                             'userCount     DESC',
                             'tagCount      DESC',
                       );
        }
        else
        {
            $order = $this->_extraOrder($order);
        }

        return $this->_mapper->fetchRelated( array(
                                        'items'   => $items,
                                        'order'   => $order,
                                        'count'   => $count,
                                        'offset'  => $offset,
                                        'privacy' => $this->_curUser(),
                                    ));
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Users and Tags.
     *  @param  users       A Model_Set_User instance, array, or
     *                      comma-separated string of users to match.
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
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByUsersAndTags($users,
                                        $tags,
                                        $exactTags = true,
                                        $order     = null,
                                        $count     = null,
                                        $offset    = null)
    {
        /* Rely on Service_User/Service_Tag to properly interpret 'users' and
         * 'tags'
         */
        $users = $this->factory('Service_User')->csList2set($users);
        $tags  = $this->factory('Service_Tag')->csList2set($tags);

        if ($order === null)
        {
            $order   = array(
                             'taggedOn      DESC',
                             'name          ASC',
                             'userCount     DESC',
                             'tagCount      DESC',
                       );
        }
        else
        {
            $order = $this->_extraOrder($order);
        }

        return $this->_mapper->fetchRelated( array(
                                        'users'     => $users,
                                        'tags'      => $tags,
                                        'exactTags' => $exactTags,
                                        'order'     => $order,
                                        'count'     => $count,
                                        'offset'    => $offset,
                                        'privacy'   => $this->_curUser(),
                                    ));
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
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByItemsAndTags($items,
                                        $tags,
                                        $exact   = true,
                                        $order   = null,
                                        $count   = null,
                                        $offset  = null)
    {
        /* Rely on Service_Item/Service_Tag to properly interpret 'items' and
         * 'tags'
         */
        $items = $this->factory('Service_Item')->csList2set($items);
        $tags  = $this->factory('Service_Tag')->csList2set($tags);

        if ($order === null)
        {
            $order   = array(
                             'taggedOn      DESC',
                             'name          ASC',
                             'userCount     DESC',
                             'tagCount      DESC',
                       );
        }
        else
        {
            $order = $this->_extraOrder($order);
        }

        return $this->_mapper->fetchRelated( array(
                                        'items'     => $items,
                                        'tags'      => $tags,
                                        'exactTags' => $exact,
                                        'order'     => $order,
                                        'count'     => $count,
                                        'offset'    => $offset,
                                        'privacy'   => $this->_curUser(),
                                    ));
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

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Given an ordering, include additional ordering criteria that
     *          will help make result sets consistent.
     *  @param  order   The incoming order criteria.
     *
     *  @return A new order criteria array.
     */
    protected function _extraOrder($order)
    {
        $newOrder = (is_array($order)
                        ? $order
                        : (is_string($order)
                            ? array($order)
                            : array()));

        $orderMap = array();
        foreach ($newOrder as $ord)
        {
            list($by, $dir) = preg_split('/\s+/', $ord);
            $orderMap[$by] = $dir;
        }

        /* Include additional, distiguishing order:
         *      taggedOn  DESC
         *      name      ASC
         *      userCount DESC
         *      tagCount  DESC
         */
        if (! isset($orderMap['taggedOn']))
            array_push($newOrder, 'taggedOn DESC');

        if (! isset($orderMap['name']))
            array_push($newOrder, 'name ASC');

        if (! isset($orderMap['userCount']))
            array_push($newOrder, 'userCount DESC');

        if (! isset($orderMap['tagCount']))
            array_push($newOrder, 'tagCount DESC');

        return $newOrder;
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

    /** @brief  Retrieve the currently identified user.
     *
     *  @return A Model_User instance or null if none.
     */
    protected function _curUser()
    {
        $user = Connexions::getUser();
        if ($user === false)
            $user = null;

        return $user;
    }
}
