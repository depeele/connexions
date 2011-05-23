<?php
/** @file
 *
 *  A Proxy for Service_Tag that exposes only publicly callable methods.
 */
class Service_Proxy_Tag extends Connexions_Service_Proxy
{
    /** @brief  Retrieve a set of tags related by a set of Users.
     *  @param  users   A Model_Set_User instance or array of users to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'userCount     DESC',
     *                        'userItemCount DESC',
     *                        'tag           ASC' ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Tag instance.
     */
    public function fetchByUsers($users,
                                 $order   = 'userCount DESC, userItemCount DESC, tag ASC',
                                 $count   = null,
                                 $offset  = null)
    {
        return $this->_service->fetchByUsers($users,
                                             $order,
                                             $count,
                                             $offset);
    }

    /** @brief  Retrieve a set of tags related by a set of Items.
     *  @param  items   A Model_Set_Item instance or array of items to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'itemCount     DESC',
     *                        'userItemCount DESC',
     *                        'tag           ASC' ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Tag instance.
     */
    public function fetchByItems($items,
                                 $order   = 'itemCount DESC, userItemCount DESC, tag ASC',
                                 $count   = null,
                                 $offset  = null)
    {
        return $this->_service->fetchByItems($items,
                                             $order,
                                             $count,
                                             $offset);
    }

    /** @brief  Retrieve a set of tags related by a set of Bookmarks
     *          (actually, by the users and items represented by the 
     *           bookmarks).
     *  @param  bookmarks   A Model_Set_Bookmark instance or array of bookmark 
     *                      identifiers to match.
     *  @param  order       Optional ORDER clause (string, array)
     *                          [ 'userItemCount DESC',
     *                            'userCount     DESC',
     *                            'tag           ASC' ];
     *  @param  count       Optional LIMIT count
     *  @param  offset      Optional LIMIT offset
     *
     *  @return A new Model_Set_Tag instance.
     */
    public function fetchByBookmarks($bookmarks = null,
                                     $order     = 'userItemCount DESC, userCount DESC, tag ASC',
                                     $count     = null,
                                     $offset    = null)
    {
        return $this->_service->fetchByBookmarks($bookmarks,
                                                 $order,
                                                 $count,
                                                 $offset);
    }

    /** @brief  Perform user autocompletion given a set of already selected
     *          users from which we need to locate the current set of
     *          user-related tags and, from that, tag-related users.
     *  @param  term    The string to autocomplete.
     *  @param  context The context of completion:
     *                      - A Model_Set_User instance to be used to restrict
     *                        the tags that should then be used to select
     *                        related users;
     *                      - A Model_Set_Tag instance, array, or
     *                        comma-separated string of tags that should be
     *                        used to select related users;
     *  @param  limit   The maximum number of users to return [ 15 ];
     *
     *  @return Model_Set_User
     */
    public function autocompleteUser($term,
                                     $context   = null,
                                     $limit     = 15)
    {
        return $this->_service->autocompleteUser($term, $context, $limit);
    }
}
