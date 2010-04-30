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
                                $order   = array('tagCount      DESC',
                                                 'userItemCount DESC',
                                                 'userCount     DESC'),
                                $count   = null,
                                $offset  = null)
    {
        return $this->_getMapper()->fetchRelated( null,   // user restrictions
                                                  null,   // item restrictions
                                                  $tags,  // tag restrictions
                                                  $order,
                                                  $count,
                                                  $offset);
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
                                                  'tagCount      DESC'),
                                 $count   = null,
                                 $offset  = null)
    {
        return $this->_getMapper()->fetchRelated( $users, // user restrictions
                                                  null,   // item restrictions
                                                  null,   // tag restrictions
                                                  $order,
                                                  $count,
                                                  $offset);
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
                                                  'tagCount      DESC'),
                                 $count   = null,
                                 $offset  = null)
    {
        return $this->_getMapper()->fetchRelated( null,   // user restrictions
                                                  $items, // item restrictions
                                                  null,   // tag restrictions
                                                  $order,
                                                  $count,
                                                  $offset);
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Users and Tags.
     *  @param  users   A Model_Set_User instance or array of users to match.
     *  @param  tags    A Model_Set_Tag  instance or array of tags  to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ [ 'userCount     DESC',
     *                          'tagCount      DESC',
     *                          'userItemCount DESC' ] ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByUsersAndTags($users,
                                        $tags,
                                        $order   = array('userCount     DESC',
                                                         'tagCount      DESC',
                                                         'userItemCount DESC'),
                                        $count   = null,
                                        $offset  = null)
    {
        return $this->_getMapper()->fetchRelated( $users, // user restrictions
                                                  null,   // item restrictions
                                                  $tags,  // tag restrictions
                                                  $order,
                                                  $count,
                                                  $offset);
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Items and Tags.
     *  @param  items   A Model_Set_User instance or array of items to match.
     *  @param  tags    A Model_Set_Tag  instance or array of tags  to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ [ 'itemCount     DESC',
     *                          'tagCount      DESC',
     *                          'userItemCount DESC' ] ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByItemsAndTags($items,
                                        $tags,
                                        $order   = array('itemCount     DESC',
                                                         'tagCount      DESC',
                                                         'userItemCount DESC'),
                                        $count   = null,
                                        $offset  = null)
    {
        return $this->_getMapper()->fetchRelated( null,   // user restrictions
                                                  $items, // item restrictions
                                                  $tags,  // tag restrictions
                                                  $order,
                                                  $count,
                                                  $offset);
    }
}
