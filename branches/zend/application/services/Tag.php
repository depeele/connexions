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
     *  @param  csList  The comma-separated list of tags;
     *  @param  create  Should any non-existing tags be created? [ false ];
     *
     *  @return Model_Set_Uset
     */
    public function csList2set($csList, $create = false)
    {
        $names = (empty($csList)
                    ? array()
                    : preg_split('/\s*,\s*/', strtolower($csList)) );

        // /*
        Connexions::log("Service_Tag::csList2set( [ %s ], %screate ): "
                        .   "%snames[ %s ]",
                        $csList, ($create ? '' : 'DO_NOT '),
                        (empty($names) ? "empty " : count($names) .' '),
                        implode(', ', $names));
        // */

        if (empty($names))
        {
            $set = $this->_getMapper()->makeEmptySet();
        }
        else
        {
            $set = $this->_getMapper()->fetchBy('tag', $names, 'tag ASC');
            $set->setSource($csList);

            if ($create)
            {
                /* See if any of the request tags we not found...
                 *
                 * First, flip the array so it's keyed by tagName.
                 */
                $names = array_flip($names);

                // Remove all tags that we successfully retrieved
                foreach ($set as $tag)
                {
                    unset($names[ $tag->tag ]);
                }

                if (count($names) > 0)
                {
                    /*
                    Connexions::log("Service_Tag::csList2set(): "
                                    .   "create %d tags [ %s ]",
                                    count($names),
                                    implode(', ', array_keys($names)));
                    // */

                    /* There is one or more tag that does not yet exist.
                     *
                     * Create all that are missing, adding them to the set.
                     */
                    $tMapper = $this->_getMapper('Model_Mapper_Tag');
                    foreach ($names as $tagName => $idex)
                    {
                        $tag = $tMapper->getModel( array('tag' => $tagName) );
                        if ($tag !== null)
                        {
                            $tag = $tag->save();
                            $set->append($tag);
                        }
                    }

                    $set->usort('Service_Tag::sort_by_tag');
                }
            }
        }

        return $set;
    }

    /** @brief  Retrieve a set of tags related by a set of Users.
     *  @param  users   A Model_Set_User instance or array of users to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'userCount DESC' ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Tag instance.
     */
    public function fetchByUsers($users,
                                 $order   = 'userCount DESC',
                                 $count   = null,
                                 $offset  = null)
    {
        return $this->_getMapper()->fetchRelated( array(
                                        'users'  => $users,
                                        'order'  => $order,
                                        'count'  => $count,
                                        'offset' => $offset,
                                    ));
    }

    /** @brief  Retrieve a set of tags related by a set of Items.
     *  @param  items   A Model_Set_Item instance or array of items to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'itemCount DESC' ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Tag instance.
     */
    public function fetchByItems($items,
                                 $order   = 'itemCount DESC',
                                 $count   = null,
                                 $offset  = null)
    {
        return $this->_getMapper()->fetchRelated( array(
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
     *                          [ 'userItemCount DESC' ];
     *  @param  count       Optional LIMIT count
     *  @param  offset      Optional LIMIT offset
     *
     *  @return A new Model_Set_Tag instance.
     */
    public function fetchByBookmarks($bookmarks = null,
                                     $order     = 'userItemCount DESC',
                                     $count     = null,
                                     $offset    = null)
    {
        $users = null;
        $items = null;
        if (! empty($bookmarks))
        {
            $ids   = (is_array($bookmarks)
                        ? $bookmarks
                        : $bookmarks->idArray());
            $users = array();
            $items = array();
            foreach ($ids as $id)
            {
                array_push($users, $id[0]);
                array_push($items, $id[1]);
            }

            /*
            Connexions::log("Service_Tag::fetchByBookmarks(): "
                            .   "users[ %s ], items[ %s ]",
                            implode(', ', $users),
                            implode(', ', $items));
            // */
        }

        return $this->_getMapper()->fetchRelated( array(
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
