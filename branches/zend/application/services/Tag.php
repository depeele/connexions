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

        Connexions::log("Service_Tag::csList2set( %s ): [ %s ]",
                        Connexions::varExport($csList),
                        Connexions::varExport($ids));

        // Generate the set of all matches
        $set = parent::csList2set($ids, $order);
        if ( ($set->count() > 0) && $create )
        {
            /* See if there are any tags that we need to create.  This can only
             * succeed if the list we have represents tag names as opposed to
             * tagIds.  Look through the list to see if at least one is
             * non-numeric.
             */
            $by  = 'tagId';
            foreach ($ids as $val)
            {
                if (! is_numeric($val))
                {
                    $by = 'tag';
                    break;
                }
            }

            /* If 'by' is NOT 'tag', then we appear to have a list of tag
             * identifiers.  Creation doesn't make sense.
             */
            if ($by !== 'tag')
            {
                throw new Exception('Cannot create when providing tagIds');
            }

            /* See if any of the requested tags were not found...
             *
             * First, flip the array so it's keyed by tagName.
             */
            $ids = array_flip($ids);

            // Remove all tags that we successfully retrieved
            foreach ($set as $tag)
            {
                unset($ids[ $tag->tag ]);
            }

            if (count($ids) > 0)
            {
                /* There is one or more tag that does not yet exist.
                 *
                 * Create all that are missing, adding them to the set.
                 */

                /*
                Connexions::log("Service_Tag::csList2set(): "
                                .   "create %d tags [ %s ]",
                                count($ids),
                                implode(', ', array_keys($ids)));
                // */

                $tMapper = $this->_getMapper('Model_Mapper_Tag');
                foreach ($ids as $tagName => $idex)
                {
                    $tag = $tMapper->getModel( array('tag' => $tagName) );
                    if ($tag !== null)
                    {
                        $tag = $tag->save();
                        $set->append($tag);
                    }
                }

                // Sort the final set by tag.
                $set->usort('Service_Tag::sort_by_tag');
            }
        }

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
     *
     *  @return A new Model_Set_Tag instance.
     */
    public function fetchByUsers($users,
                                 $order   = null,
                                 $count   = null,
                                 $offset  = null)
    {
        if ($order === null)
        {
            $order = array('userCount     DESC',
                           'userItemCount DESC',
                           'tag           ASC');
        }

        return $this->_mapper->fetchRelated( array(
                                        'users'  => $users,
                                        'order'  => $order,
                                        'count'  => $count,
                                        'offset' => $offset,
                                    ));
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

        return $this->_mapper->fetchRelated( array(
                                        'items'  => $items,
                                        'order'  => $order,
                                        'count'  => $count,
                                        'offset' => $offset,
                                    ));
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
                                     $order     = null,
                                     $count     = null,
                                     $offset    = null)
    {
        if ($order === null)
        {
            $order = array('userItemCount DESC',
                           'userCount     DESC',
                           'tag           ASC');
        }

        $users = null;
        $items = null;
        if (! empty($bookmarks))
        {
            $ids   = (is_array($bookmarks)
                        ? $bookmarks
                        : $bookmarks->getIds());
            $users = array();
            $items = array();
            foreach ($ids as $id)
            {
                array_push($users, $id[0]);
                array_push($items, $id[1]);
            }

            // /*
            Connexions::log("Service_Tag::fetchByBookmarks(): "
                            .   "users[ %s ], items[ %s ]",
                            implode(', ', $users),
                            implode(', ', $items));
            // */
        }

        return $this->_mapper->fetchRelated( array(
                                        'users'  => $users,
                                        'items'  => $items,
                                        'order'  => $order,
                                        'count'  => $count,
                                        'offset' => $offset,
                                    ));
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
