<?php
/** @file
 *
 *  A Proxy for Service_Bookmark that exposes only publicly callable methods.
 */
class Service_Proxy_Bookmark extends Connexions_Service_Proxy
{
    /** @brief  Retrieve a set of bookmarks related by a set of Tags.
     *  @param  tags    A Model_Set_Tag instance or array of tags to match.
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
        return $this->_service->fetchByTags($tags,
                                            $exact,
                                            $order,
                                            $count,
                                            $offset);
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Users.
     *  @param  users   A Model_Set_User instance or array of users to match.
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
        return $this->_service->fetchByUsers($users,
                                             $order,
                                             $count,
                                             $offset);
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Items.
     *  @param  items   A Model_Set_Item instance or array of items to match.
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
        return $this->_service->fetchByItems($items,
                                             $order,
                                             $count,
                                             $offset);
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Users and Tags.
     *  @param  users       A Model_Set_User instance or array of users to
     *                      match.
     *  @param  tags        A Model_Set_Tag  instance or array of tags  to
     *                      match.
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
        return $this->_service->fetchByUsersAndTags($users,
                                                    $tags,
                                                    $exactTags,
                                                    $order,
                                                    $count,
                                                    $offset);
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Items and Tags.
     *  @param  items       A Model_Set_User instance or array of items to
     *                      match.
     *  @param  tags        A Model_Set_Tag  instance or array of tags  to
     *                      match.
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
        return $this->_service->fetchByItemsAndTags($items,
                                                    $tags,
                                                    $exact,
                                                    $order,
                                                    $count,
                                                    $offset);
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
        return $this->_service->autocompleteTag($str, $tags, $users, $limit);
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
     *  @param  rating      If non-null,  the (new) rating;
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
                           $rating          = 0,
                           $isFavorite      = false,
                           $isPrivate       = false,
                           $tags            = null,
                           $url             = null)
    {
        return $this->_service->update($id, $name, $description,
                                       $rating, $isFavorite, $isPrivate,
                                       $tags, $url);
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
        return $this->_service->delete($id);
    }
}
