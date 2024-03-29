<?php
/** @file
 *
 *  A Proxy for Service_Bookmark that exposes only publicly callable methods.
 */
class Service_Proxy_Bookmark extends Connexions_Service_Proxy
{
    /** @brief  Retrieve a set of Domain Model instances.
     *  @param  id      Identification value(s), null to retrieve all.
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value(s) pairs.
     *  @param  order   An array of name/direction pairs representing the
     *                  desired sorting order.  The 'name's MUST be valid for
     *                  the target Domain Model and the directions a
     *                  Connexions_Service::SORT_DIR_* constant.  If an order
     *                  is omitted, Connexions_Service::SORT_DIR_ASC will be
     *                  used [ 'taggedOn DESC, updatedOn DESC, name ASC' ];
     *  @param  count   The maximum number of items from the full set of
     *                  matching items that should be returned
     *                  [ 50 ];
     *  @param  offset  The starting offset in the full set of matching items
     *                  [ 0 ].
     *  @param  since   Limit the results to bookmarks updated after this
     *                  date/time [ null == no time limits ];
     *
     *  @return A new Connexions_Model_Set.
     */
    public function fetch($id       = null,
                          $order    = 'taggedOn DESC, updatedOn DESC, name ASC',
                          $count    = 50,
                          $offset   = 0,
                          $since    = null)
    {
        return $this->_service->fetch($id, $order, $count, $offset, $since);
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Tags.
     *  @param  tags    A Model_Set_Tag instance or array of tags to match.
     *  @param  exact   Bookmarks MUST be associated with provided tags
     *                  [ true ];
     *  @param  order   Optional ORDER clause (string, array)
     *                  [ 'taggedOn DESC, updatedOn DESC, name ASC' ];
     *  @param  count   Optional LIMIT count [ 50 ];
     *  @param  offset  Optional LIMIT offset [ 0 ];
     *  @param  since   Limit the results to bookmarks updated after this
     *                  date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByTags($tags,
                                $exact   = true,
                                $order   = 'taggedOn DESC, updatedOn DESC, name ASC',
                                $count   = 50,
                                $offset  = 0,
                                $since   = null)
    {
        return $this->_service->fetchByTags($tags,
                                            $exact,
                                            $order,
                                            $count,
                                            $offset,
                                            $since);
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Users.
     *  @param  users   A Model_Set_User instance or array of users to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                  [ 'taggedOn DESC, updatedOn DESC, name ASC' ];
     *  @param  count   Optional LIMIT count [ 50 ];
     *  @param  offset  Optional LIMIT offset [ 0 ];
     *  @param  since   Limit the results to bookmarks updated after this
     *                  date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByUsers($users,
                                 $order   = 'taggedOn DESC, updatedOn DESC, name ASC',
                                 $count   = 50,
                                 $offset  = 0,
                                 $since   = null)
    {
        return $this->_service->fetchByUsers($users,
                                             $order,
                                             $count,
                                             $offset,
                                             $since);
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Items.
     *  @param  items   A Model_Set_Item instance or array of items to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                  [ 'taggedOn DESC, updatedOn DESC, name ASC' ];
     *  @param  count   Optional LIMIT count [ 50 ];
     *  @param  offset  Optional LIMIT offset [ 0 ];
     *  @param  since   Limit the results to bookmarks updated after this
     *                  date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByItems($items,
                                 $order   = 'taggedOn DESC, updatedOn DESC, name ASC',
                                 $count   = 50,
                                 $offset  = 0,
                                 $since   = null)
    {
        return $this->_service->fetchByItems($items,
                                             $order,
                                             $count,
                                             $offset,
                                             $since);
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Users and Tags.
     *  @param  users       A Model_Set_User instance or array of users to
     *                      match.
     *  @param  tags        A Model_Set_Tag  instance or array of tags  to
     *                      match.
     *  @param  exactUsers  Bookmarks MUST be associated with provided users
     *                      [ true ];
     *  @param  exactTags   Bookmarks MUST be associated with provided tags
     *                      [ true ];
     *  @param  order       Optional ORDER clause (string, array)
     *                      [ 'taggedOn DESC, updatedOn DESC, name ASC' ];
     *  @param  count       Optional LIMIT count [ 50 ];
     *  @param  offset      Optional LIMIT offset [ 0 ];
     *  @param  since       Limit the results to bookmarks updated after this
     *                      date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByUsersAndTags($users,
                                        $tags,
                                        $exactUsers = true,
                                        $exactTags  = true,
                                        $order      = 'taggedOn DESC, updatedOn DESC, name ASC',
                                        $count      = 50,
                                        $offset     = 0,
                                        $since      = null)
    {
        return $this->_service->fetchByUsersAndTags($users,
                                                    $tags,
                                                    $exactUsers,
                                                    $exactTags,
                                                    $order,
                                                    $count,
                                                    $offset,
                                                    $since);
    }

    /** @brief  Retrieve a set of bookmarks related by a set of Items and Tags.
     *  @param  items       A Model_Set_User instance or array of items to
     *                      match.
     *  @param  tags        A Model_Set_Tag  instance or array of tags  to
     *                      match.
     *  @param  exactTags   Bookmarks MUST be associated with provided tags
     *                      [ true ];
     *  @param  order       Optional ORDER clause (string, array)
     *                      [ 'taggedOn DESC, updatedOn DESC, name ASC' ];
     *  @param  count       Optional LIMIT count [ 50 ];
     *  @param  offset      Optional LIMIT offset [ 0 ];
     *  @param  since       Limit the results to bookmarks updated after this
     *                      date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Bookmark instance.
     */
    public function fetchByItemsAndTags($items,
                                        $tags,
                                        $exact   = true,
                                        $order   = 'taggedOn DESC, updatedOn DESC, name ASC',
                                        $count   = 50,
                                        $offset  = 0,
                                        $since   = null)
    {
        return $this->_service->fetchByItemsAndTags($items,
                                                    $tags,
                                                    $exact,
                                                    $order,
                                                    $count,
                                                    $offset,
                                                    $since);
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
     *  @param  items       A Model_Set_Item instance, array, or
     *                      comma-separated string of items that restrict the
     *                      bookmarks that should be used to select related
     *                      tags -- a third component of 'context';
     *  @param  limit       The maximum number of tags to return [ 15 ];
     *
     *  @return Model_Set_Tag
     */
    public function autocompleteTag($term,
                                    $tags   = null,
                                    $users  = null,
                                    $items  = null,
                                    $limit  = 15)
    {
        return $this->_service->autocompleteTag($term, $tags, $users, $items,
                                                $limit);
    }

    /** @brief  Retrieve an existing bookmark.
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
     *  @param  apiKey  The apiKey for the currently authenticated user
     *                  (REQUIRED if the transport method is NOT POST);
     *
     *  @return Model_Bookmark (or null if not found).
     */
    public function find($id,
                         $apiKey = null)
    {
        // Require 'apiKey' authentication if GET
        $user     = $this->_authenticate($apiKey);

        // Attempt to retrieve the bookmark
        $bookmark = $this->_service->find($id);

        /*
        Connexions::log("Service_Proxy_Bookmark::find(): "
                        .   "id[ %s ], apiKey[ %s ] == [ %s ]",
                        Connexions::varExport($id),
                        Connexions::varExport($apiKey),
                        ($bookmark ? $bookmark->debugDump() : 'null'));
        // */

        if ( ($bookmark !== null) && (! $bookmark->isBacked()) )
        {
            // NOT a backed instance -- return null
            $bookmark = null;
        }

        return $bookmark;
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
     *  @param  worldModify If non-null,  the (new) world modify value;
     *  @param  tags        If non-empty, the (new) set of tags;
     *  @param  url         If non-empty, the (new) URL associated with this
     *                      bookmark (MAY create a new Item);
     *  @param  apiKey      The apiKey for the currently authenticated user
     *                      (REQUIRED if the transport method is NOT POST);
     *
     *  @return Model_Bookmark
     */
    public function update($id,
                           $name            = null,
                           $description     = null,
                           $rating          = 0,
                           $isFavorite      = false,
                           $isPrivate       = false,
                           $worldModify     = false,
                           $tags            = null,
                           $url             = null,
                           $apiKey          = null)
    {
        $user = $this->_authenticate($apiKey);

        return $this->_service->update($id, $name, $description,
                                       $rating,
                                       $isFavorite, $isPrivate, $worldModify,
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
     *  @param  apiKey      The apiKey for the currently authenticated user
     *                      (REQUIRED if the transport method is NOT POST);
     *
     *  @return void
     */
    public function delete($id, $apiKey = null)
    {
        $user = $this->_authenticate($apiKey);

        return $this->_service->delete($id);
    }

    /** @brief  Retrieve the taggedOn date/times for the given user(s) and/or
     *          item(s).
     *  @param  users       A Model_Set_User instance, array, or
     *                      comma-separated string of users to match.
     *  @param  items       A Model_Set_Item instance, array, or
     *                      comma-separated string of items to match.
     *  @param  tags        A Model_Set_Tag instance, array, or comma-separated
     *                      string of tags to match.
     *  @param  grouping    How entries should be grouped / rolled-up.  A
     *                      string specifying an ISO 8601 duration
     *                      (e.g. 'P2Y4DT6H8M' == 2 years, 4 days, 6 hours, 8
     *                            minutes).
     *                      If not specified, no grouping will be performed.
     *                      Note that if grouping is employed, the returned
     *                      data will be reduced to single date/time instances
     *                      using the FIRST field indicated by any 'order'
     *                      parameter [ null ];
     *  @param  order       An array of name/direction pairs representing the
     *                      desired sorting order.  The 'name's MUST be
     *                      'taggedOn' or 'updatedOn' and the directions a
     *                      Connexions_Service::SORT_DIR_* constant.  If an
     *                      order is omitted, Connexions_Service::SORT_DIR_ASC
     *                      will be used [ {taggedOn: 'ASC'} ];
     *  @param  count       An OPTIONAL LIMIT count  [ no limit ];
     *  @param  offset      An OPTIONAL LIMIT offset [ 0 ];
     *  @param  from        Limit the results to date/times AFTER this
     *                      date/time
     *                      [ null == no date/time from restriction ];
     *  @param  until       Limit the results to date/times BEFORE this
     *                      date/time
     *                      [ null == no date/time until restriction ];
     *
     *  @return An array of date/time / count mappings.
     */
    public function getTimeline($users      = null,
                                $items      = null,
                                $tags       = null,
                                $grouping   = 'YMDH',
                                $order      = null,
                                $count      = null,
                                $offset     = 0,
                                $from       = null,
                                $until      = null)
    {
        $params = array();
        if (! empty($users))    $params['users']    = $users;
        if (! empty($items))    $params['items']    = $items;
        if (! empty($tags))     $params['tags']     = $tags;
        if (! empty($grouping)) $params['grouping'] = $grouping;
        if (! empty($order))    $params['order']    = $order;
        if (! empty($count))    $params['count']    = $count;
        if (! empty($offset))   $params['offset']   = $offset;
        if (! empty($from))     $params['from']     = $from;
        if (! empty($until))    $params['until']    = $until;

        $timeline = $this->_service->getTimeline( $params );
        return $timeline;
    }
}
