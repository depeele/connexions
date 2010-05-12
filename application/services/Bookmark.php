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

    /** @brief  Create a new, unbacked Domain Model instance.  If a matching
     *          Bookmark already exists, retrieve the matching instance and
     *          update it with the incoming data.
     *  @param  data    An array of name/value pairs used to initialize the
     *                  Domain Model.  All 'name's MUST be valid for the target
     *                  Domain Model.  For a new Model_Bookmark, there are a
     *                  few special exceptions.
     *                      1) The user/owner may be identified in one of three
     *                         ways:
     *                          - 'user' as a Model_User instance;
     *                          - 'userId' as an integer identifier;
     *                          - 'userId' as a  string user-name;
     *                      2) The referenced Item may be identified in one of
     *                         five ways:
     *                          - 'item'   as a  Model_Item instance;
     *                          - 'itemId' as an integer identifier;
     *                          - 'itemId' as a  string url-hash;
     *                          - 'itemUrl';
     *                          - 'itemUrlHash';
     *                      3) Tags may be identified in one of two ways:
     *                          - 'tags' as a Model_Set_Tag instance;
     *                          - 'tags' as a comma-separated string;
     *
     *  @return A (possibly new) Domain Model instance.
     *          Note: If the returned instance is new or modified, and the
     *                caller wishes the instance to persist, they must invoke
     *                either:
     *                    $model = $model->save()
     *                or
     *                    $model = $this->update($model)
     */
    public function create(array $data)
    {
        $data = $this->_prepareUserId($data);
        $data = $this->_prepareItemId($data);
        $data = $this->_prepareTags($data);

        // Pull 'tags' out -- we'll add them once the Bookmark is created.
        $tags = $data['tags'];
        unset($data['tags']);

        // Does a matching bookmark already exists?
        $boomkark = $this->_getMapper()
                            ->find( array(
                                'userId' => $data['userId'],
                                'itemId' => $data['itemId'],
                              ));
        if ($bookmark !== null)
        {
            // Update this bookmark with any new, incoming data.
            $bookmark->populate($data);
        }
        else
        {
            $bookmark = parent::create($data);
        }

        $bookmark->tags = $tags;

        return $bookmark;
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Tags.
     *  @param  tags    A Model_Set_Tag instance or array of tags to match.
     *  @param  exact   Bookmarks MUST be associated with provided tags
     *                  [ true ];
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ [ 'tagCount      DESC',
     *                          'userItemCount DESC',
     *                          'userCount     DESC',
     *                          'taggedOn      DESC',
     *                          'name          ASC'  ] ];
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
        if ($order === null)
        {
            $order   = array('tagCount      DESC',
                             'userItemCount DESC',
                             'userCount     DESC',
                             'taggedOn      DESC',
                             'name          ASC');
        }

        return $this->_getMapper()->fetchRelated( array(
                                        'tags'      => $tags,
                                        'exactTags' => $exact,
                                        'order'     => $order,
                                        'count'     => $count,
                                        'offset'    => $offset,
                                        'where'     => $where,
                                        'privacy'   => Connexions::getUser(),
                                    ));
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Users.
     *  @param  users   A Model_Set_User instance or array of users to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ [ 'userCount     DESC',
     *                          'userItemCount DESC',
     *                          'tagCount      DESC',
     *                          'taggedOn      DESC',
     *                          'name          ASC'  ] ];
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
        if ($order === null)
        {
            $order   = array('userCount     DESC',
                             'userItemCount DESC',
                             'tagCount      DESC',
                             'taggedOn      DESC',
                             'name          ASC');
        }

        return $this->_getMapper()->fetchRelated( array(
                                        'users'   => $users,
                                        'order'   => $order,
                                        'count'   => $count,
                                        'offset'  => $offset,
                                        'privacy' => Connexions::getUser(),
                                    ));
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Items.
     *  @param  items   A Model_Set_Item instance or array of items to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ [ 'userItemCount DESC',
     *                          'userCount     DESC',
     *                          'tagCount      DESC',
     *                          'taggedOn      DESC',
     *                          'name          ASC'  ] ];
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
        if ($order === null)
        {
            $order   = array('userItemCount DESC',
                             'userCount     DESC',
                             'tagCount      DESC',
                             'taggedOn      DESC',
                             'name          ASC');
        }

        return $this->_getMapper()->fetchRelated( array(
                                        'items'   => $items,
                                        'order'   => $order,
                                        'count'   => $count,
                                        'offset'  => $offset,
                                        'privacy' => Connexions::getUser(),
                                    ));
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Users and Tags.
     *  @param  users       A Model_Set_User instance or array of users to
     *                      match.
     *  @param  tags        A Model_Set_Tag  instance or array of tags  to
     *                      match.
     *  @param  exactTags   Bookmarks MUST be associated with provided tags
     *                      [ true ];
     *  @param  order       Optional ORDER clause (string, array)
     *                          [ [ 'userCount     DESC',
     *                              'tagCount      DESC',
     *                              'userItemCount DESC',
     *                              'taggedOn      DESC',
     *                              'name          ASC'  ] ];
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
        if ($order === null)
        {
            $order     = array('userCount     DESC',
                               'tagCount      DESC',
                               'userItemCount DESC',
                               'taggedOn      DESC',
                               'name          ASC');
        }

        return $this->_getMapper()->fetchRelated( array(
                                        'users'     => $users,
                                        'tags'      => $tags,
                                        'exactTags' => $exactTags,
                                        'order'     => $order,
                                        'count'     => $count,
                                        'offset'    => $offset,
                                        'privacy'   => Connexions::getUser(),
                                    ));
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Items and Tags.
     *  @param  items       A Model_Set_User instance or array of items to
     *                      match.
     *  @param  tags        A Model_Set_Tag  instance or array of tags  to
     *                      match.
     *  @param  exactTags   Bookmarks MUST be associated with provided tags
     *                      [ true ];
     *  @param  order       Optional ORDER clause (string, array)
     *                          [ [ 'itemCount     DESC',
     *                              'tagCount      DESC',
     *                              'userItemCount DESC',
     *                              'taggedOn      DESC',
     *                              'name          ASC'  ] ];
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
        if ($order === null)
        {
            $order   = array('itemCount     DESC',
                             'tagCount      DESC',
                             'userItemCount DESC',
                             'taggedOn      DESC',
                             'name          ASC' );
        }

        return $this->_getMapper()->fetchRelated( array(
                                        'items'     => $items,
                                        'tags'      => $tags,
                                        'exactTags' => $exact,
                                        'order'     => $order,
                                        'count'     => $count,
                                        'offset'    => $offset,
                                        'privacy'   => Connexions::getUser(),
                                    ));
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Given an array of name/value pairs to be used in creating a new
     *          Bookmark, see if there is a valid Model_User identified.
     *  @param  data    An array of name/value pairs to be used to initialize
     *                  a new Model_Bookmark instance.  The referenced User may
     *                  be identified in one of three ways:
     *                          - 'user' as a Model_User instance;
     *                          - 'userId' as an integer identifier;
     *                          - 'userId' as a  string user-name;
     *
     *  @throw  Exception("Cannot locate User by id...");
     *          Exception("No valid user identified...");
     *
     *  @return A new 'data' array, cleaned of extraneous User identification
     *          information (i.e. 'user'), leaving simply 'userId'.
     */
    protected function _prepareUserId(array $data)
    {
        $uMapper = $this->_getMapper('Model_Mapper_User');
        $user    = null;
        if ( isset($data['user']) && ($data['user'] instanceof Model_User) )
        {
            $user = $data['user'];
        }
        else if ( isset($data['userId']) )
        {
            // Find the target User by id
            $user = $uMapper->find( $data['userId'] );
        }

        if ($user === null)
        {
            throw new Exception("No User identified.  "
                                .   "Please identify the target User via "
                                .   "instance or userId.");
        }

        /* We should now have a Model_User instance representing the
         * desired user.
         *
         * Remove all other identification options
         */
        unset($data['user']);

        $data['userId'] = $user->userId;

        return $data;
    }

    /** @brief  Given an array of name/value pairs to be used in creating a new
     *          Bookmark, see if there is a valid Model_Item identified.
     *  @param  data    An array of name/value pairs to be used to initialize
     *                  a new Model_Bookmark instance.  The referenced Item may
     *                  be identified in one of five ways:
     *                          - 'item'   as a  Model_Item instance;
     *                          - 'itemId' as an integer identifier;
     *                          - 'itemId' as a  string url-hash;
     *                          - 'itemUrl';
     *                          - 'itemUrlHash';
     *
     *  @throw  Exception("Cannot locate Item by id...");
     *          Exception("No valid item identified...");
     *
     *  @return A new 'data' array, cleaned of extraneous Item identification
     *          information (i.e. 'item', 'itemUrl', 'itemUrlHash'), leaving
     *          simply 'itemId'.
     */
    protected function _prepareItemId(array $data)
    {
        $iMapper = $this->_getMapper('Model_Mapper_Item');
        if ( isset($data['item']) && ($data['item'] instanceof Model_Item) )
        {
            $item = $data['item'];
        }
        else if ( isset($data['itemId']) )
        {
            // Find the target Item by id
            $item = $iMapper->find( $data['itemId'] );
            if ($item === null)
            {
                // Cannot locate the specified Item by Id...
                throw new Exception("Cannot locate Item by "
                                    .   "id[ {$data['itemId']} ]");
            }
        }
        else
        {
            if ( isset($data['itemUrl']) )
            {
                /* Find or create the target Item by url -- actually, by the
                 * hash of the normalized URL.
                 */
                $hash = Connexions::md5Url($data['itemUrl']);
            }
            else if ( isset($data['itemUrlHash']) )
            {
                $hash = $data['itemUrlHash'];
            }

            if (empty($hash))
            {
                throw new Exception("No valid item identifier provided.  "
                                    .   "Please identify the target Item via "
                                    .   "instance, itemId, itemUrl, or "
                                    .   "itemUrlHash.");
            }

            $item = $iMapper->getModel( $hash );

            if (! $item->isBacked())
                $item = $item->save();
        }

        /* We should now have a Model_Item instance representing the
         * desired URL
         *
         * Remove all other identification options
         */
        unset($data['itemUrl']);
        unset($data['itemUrlHash']);
        unset($data['item']);

        $data['itemId'] = $item->itemId;

        return $data;
    }

    /** @brief  Given an array of name/value pairs to be used in creating a new
     *          Bookmark, see if there is a valid Model_Set_Tag identified.
     *  @param  data    An array of name/value pairs to be used to initialize
     *                  a new Model_Bookmark instance.  The referenced
     *                  Model_Set_Tag may be identified in one of two ways:
     *                          - 'tags' as a Model_Set_Tag instance;
     *                          - 'tags' as a comma-separated string;
     *
     *  @return A new 'data' array that includes 'tags'.
     */
    protected function _prepareTags(array $data)
    {
        $tags = null;
        if ( isset($data['tags']))
        {
            if ($data['tags'] instanceof Model_Set_Tag)
            {
                $tags = $data['tags'];
            }
            else if (is_string($data['tags']))
            {
                /* ASSUME that this is a comma-separated list of tags, telling
                 * csList2set() to create any tags that don't already exist.
                 */
                $tService = Connexions_Service::factory('Service_Tag');
                $tags     = $tService->csList2set($data['tags'], true);
            }
        }

        if ( ($tags === null) || empty($tags) )
        {
            throw new Exception("No tags provided.");
        }

        // Save the resolved tag set
        $data['tags'] = $tags;

        return $data;
    }
}
