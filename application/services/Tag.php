<?php
/** @file
 *
 *  The concrete base class providing access to Model_Tag and Model_Set_Tag.
 */
class Service_Tag extends Connexions_Service
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

    /** @brief  Convert a comma-separated list of tags to a 
     *          Model_Set_Tag instance.
     *  @param  csList  The comma-separated list of tag identifiers;
     *  @param  order   An ordering string/array.
     *  @param  create  Should any non-existing tags be created? [ false ];
     *
     *  Override to allow the creation of tags that don't currently exist.
     *
     *  @return Model_Set_Tag
     */
    public function csList2set($csList, $order = null, $create = false)
    {
        // Parse the comma-separated-list directly -- we'll use it later.
        $ids = $this->_csList2array($csList);

        /*
        Connexions::log("Service_Tag::csList2set( %s, create=%s ): "
                        . "ids[ %s ]",
                        Connexions::varExport($csList),
                        Connexions::varExport($create),
                        Connexions::varExport($ids));
        // */

        // Generate the set of all matches
        $set = parent::csList2set($ids, $order);

        /*
        Connexions::log("Service_Tag::csList2set(): set[ %s ]",
                        $set->debugDump());
        // */

        $set->setSource($csList);

        /* Now, if the size of 'set' is less than the size of 'ids', locate the
         * missing entries and create a representative model instance for each.
         *
         * If 'create' is true, ensure that the representative model instances
         * have been saved.
         */
        if ( count($set) < count($ids) )
        {
            /*
            $filter  = $this->_getFilter(); // 'Model_Filter_Tag'
            if (! is_object($filter) )
            {
                throw new Exception('Model_Tag SHOULD have a Filter...');
            }
            // */

            /*
            Connexions::log("Service_Tag::csList2set(): "
                                . "%d tags seem to be missing...",
                            count($ids) - count($set));
            // */

            // Normalize all tags in 'ids'
            foreach($ids as $tag)
            {
                // Skip any tagId's
                if (is_int($tag))   continue;

                $data = array(
                    'tag' => $tag,
                );

                // Create an un-backed model instance.
                $model = $this->_mapper->makeModel( $data, false );

                // See if the normalized tag already exists in the set.
                if (! $set->contains($model->tag))
                {
                    /* The normalized tag does NOT exist in the set.
                     *
                     * If we've been asked to create, save this model now.
                     *
                     * Regardless, append it to the set.
                     */
                    if ($create === true)
                    {
                        $model = $model->save();
                    }
                    $set->append( $model );

                    /*
                    Connexions::log("Service_Tag::csList2set(): "
                                        . "append[ %s ]",
                                    $model->debugDump());
                    // */
                }
            }
        }

        /*
        Connexions::log("Service_Tag::csList2set(): "
                            . "resulting set[ %s ]",
                        $set->debugDump());
        // */

        return $set;
    }

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
