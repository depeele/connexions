<?php
/** @file
 *
 *  The concrete base class providing access to Model_Tag and Model_Set_Tag.
 */
class Service_Tag extends Service_Base
{
    /* inferred via classname
    protected   $_modelName = 'Model_Tag';
    protected   $_mapper    = 'Model_Mapper_Tag'; */

    /** @brief  Any default ordering that should be be merged into a specified 
     *          order.
     */
    protected   $_defaultOrdering   = array(
        'tag'       => 'ASC',
    );

    /** @brief  Retrieve a set of tags related by a set of Users.
     *  @param  users   A Model_Set_User instance or array of users to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'userCount     DESC',
     *                        'userItemCount DESC',
     *                        'tag           ASC' ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *  @param  exact   Tags MUST be associated with ALL provided users
     *                  [ false ];
     *  @param  where   Additional condition(s) [ null ];
     *
     *  @return A new Model_Set_Tag instance.
     */
    public function fetchByUsers($users,
                                 $order   = null,
                                 $count   = null,
                                 $offset  = null,
                                 $exact   = false,
                                 $where   = null)
    {
        if ($order === null)
        {
            $order = array('userCount     DESC',
                           'userItemCount DESC',
                           'tag           ASC');
        }

        $to = array('users'      => $users,
                    'exactUsers' => $exact);

        if ($where !== null)
        {
            $to['where'] = $where;
        }

        /*
        Connexions::log("Service_Tag::fetchByUsers(): %d users, to[ %s ]",
                        count($users),
                        Connexions::varExport($to));
        // */

        return $this->fetchRelated( $to,
                                    $order,
                                    $count,
                                    $offset );
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
                                 $order   = null,
                                 $count   = null,
                                 $offset  = null)
    {
        if ($order === null)
        {
            $order = array('itemCount     DESC',
                           'userItemCount DESC',
                           'tag           ASC');
        }

        $to = array('items'      => $items,
                    'exactItems' => true);

        return $this->fetchRelated( $to,
                                    $order,
                                    $count,
                                    $offset );
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
     *  @param  where       Additional condition(s) [ null ];
     *
     *  @return A new Model_Set_Tag instance.
     */
    public function fetchByBookmarks($bookmarks = null,
                                     $order     = null,
                                     $count     = null,
                                     $offset    = null,
                                     $where     = null)
    {
        if ($order === null)
        {
            $order = array('userItemCount DESC',
                           'userCount     DESC',
                           'tag           ASC');
        }

        $to = array('bookmarks'  => $bookmarks,
                    'where'      => $where);

        /*
        Connexions::log("Service_Tag::fetchByBookmarks(): %d bookmarks",
                        count($bookmarks));
        // */

        return $this->fetchRelated( $to,
                                    $order,
                                    $count,
                                    $offset );
    }

    /** @brief  Perform user autocompletion given a set of already selected
     *          users from which we need to locate the current set of
     *          user-related tags and, from that, tag-related users.
     *  @param  term    The string to autocomplete.
     *  @param  users   A Model_Set_User instance, array, or comma-separated
     *                  string of users that restrict the tags that should
     *                  be used to select related users;
     *  @param  limit   The maximum number of users to return [ 15 ];
     *
     *  @return Model_Set_User
     */
    public function autocompleteUser($term,
                                     $users = null,
                                     $limit = 15)
    {
        if ($limit < 1) $limit = 15;

        /*
        Connexions::log("Service_Tag::autocompleteUser(): "
                        .   "term[ %s ], users[ %s ], limit[ %d ]",
                        $term, $users, $limit);
        // */

        /* Retrieve the tags that define the scope for this
         * autocompletion
         */
        $tags = null;
        if (! empty($users))
        {
            $tags = $this->fetchByUsers($users);

            /*
            Connexions::log("Service_Tag::autocompleteUser(): "
                            .   "tags[ %s ]",
                            $tags);
            // */
        }

        /* Match any user with a match in:
         *  name, fullName, or email
         */
        $where = array(
            'name=*'        => $term,
            '+|fullName=*'  => $term,
            '+|email=*'     => $term,
        );
        $uService = $this->factory('Service_User');
        return $uService->fetchByTags($tags,
                                      false,        // NOT exact tags
                                      null,         // default order
                                      $limit,
                                      null,        // default offset
                                      $where);
    }

    /*********************************************************************
     * Static methods
     *
     */

    /** @brief  A sort callback to sort tags by tag name
     *  @param  a   First  tag;
     *  @param  b   Second tag;
     *
     *  @return A comparison value (-1, 0, 1).
     */
    static public function sort_by_tag($a, $b)
    {
        $aName = ($a instanceof Model_Tag
                    ? $a->tag
                    : $a['tag']);
        $bName = ($b instanceof Model_Tag
                    ? $b->tag
                    : $b['tag']);

        return strcasecmp($aName, $bName);
    }
}
