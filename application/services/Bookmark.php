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

    /** @brief  Retrieve a set of bookmarks related by a set of Tags.
     *  @param  tags    A Model_Set_Tag instance or array of tags to match.
     *  @param  exact   Bookmarks MUST be associated with provided tags
     *                  [ true ];
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ [ 'tagCount      DESC',
     *                          'userItemCount DESC',
     *                          'userCount     DESC' ] ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByTags($tags,
                                $exact   = true,
                                $order   = array('tagCount      DESC',
                                                 'userItemCount DESC',
                                                 'userCount     DESC',
                                                 'taggedOn      DESC'),
                                $count   = null,
                                $offset  = null)
    {
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
     *                          'tagCount      DESC' ] ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByUsers($users,
                                 $order   = array('userCount     DESC',
                                                  'userItemCount DESC',
                                                  'tagCount      DESC',
                                                  'taggedOn      DESC'),
                                 $count   = null,
                                 $offset  = null)
    {
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
     *                      [ [ 'itemCount     DESC',
     *                          'userItemCount DESC',
     *                          'userCount     DESC',
     *                          'tagCount      DESC' ] ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByItems($items,
                                 $order   = array('itemCount     DESC',
                                                  'userItemCount DESC',
                                                  'userCount     DESC',
                                                  'tagCount      DESC',
                                                  'taggedOn      DESC'),
                                 $count   = null,
                                 $offset  = null)
    {
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
     *                              'userItemCount DESC' ] ];
     *  @param  count       Optional LIMIT count
     *  @param  offset      Optional LIMIT offset
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByUsersAndTags($users,
                                        $tags,
                                        $exactTags = true,
                                        $order     = array(
                                                        'userCount     DESC',
                                                        'tagCount      DESC',
                                                        'userItemCount DESC',
                                                        'taggedOn      DESC'),
                                        $count     = null,
                                        $offset    = null)
    {
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
     *                              'userItemCount DESC' ] ];
     *  @param  count       Optional LIMIT count
     *  @param  offset      Optional LIMIT offset
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByItemsAndTags($items,
                                        $tags,
                                        $exact   = true,
                                        $order   = array('itemCount     DESC',
                                                         'tagCount      DESC',
                                                         'userItemCount DESC',
                                                         'taggedOn      DESC'),
                                        $count   = null,
                                        $offset  = null)
    {
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
}
