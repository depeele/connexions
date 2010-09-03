<?php
/** @file
 *
 *  The concrete base class providing access to Model_Item and Model_Set_Item.
 */
class Service_Item extends Connexions_Service
{
    /* inferred via classname
    protected   $_modelName = 'Model_Item';
    protected   $_mapper    = 'Model_Mapper_Item'; */

    /** @brief  Any default ordering that should be be merged into a specified 
     *          order.
     */
    protected   $_defaultOrdering   = array(
        'url'       => 'ASC',
    );

    /** @brief  Retrieve a set of items related by a set of Users.
     *  @param  users   A Model_Set_User instance, array, or comma-separated
     *                  string of users to match.
     *  @param  exact   Items MUST be associated with ALL provided users
     *                  [ false ];
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'userCount DESC, tagCount DESC,
     *                         userItemCount DESC, urlHash ASC' ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Item instance.
     */
    public function fetchByUsers($users,
                                 $exact     = false,
                                 $order     = null,
                                 $count     = null,
                                 $offset    = null)
    {
        if ($order === null)
        {
            $order = array('uti.userCount     DESC',
                           'uti.tagCount      DESC',
                           'uti.userItemCount DESC',
                           'i.urlHash         ASC');
        }

        $to = array('users'      => $users,
                    'exactUsers' => $exact);
        return $this->fetchRelated( $to,
                                    $order,
                                    $count,
                                    $offset );
    }

    /** @brief  Retrieve a set of items related by a set of Tags.
     *  @param  tags    A Model_Set_Tag instance or array of tags to match.
     *  @param  exact   Items MUST be associated with ALL provided tags
     *                  [ true ];
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'tagCount DESC, userCount DESC, 
     *                         userItemCount DESC, urlHash ASC' ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Item instance.
     */
    public function fetchByTags($tags,
                                $exact   = true,
                                $order   = null,
                                $count   = null,
                                $offset  = null)
    {
        if ($order === null)
        {
            $order = array('uti.tagCount      DESC',
                           'uti.userCount     DESC',
                           'uti.userItemCount DESC',
                           'i.urlHash         ASC');
        }

        $to = array('tags'      => $tags,
                    'exactTags' => $exact);
        return $this->fetchRelated( $to,
                                    $order,
                                    $count,
                                    $offset );
    }

    /** @brief  Retrieve a set of items related by a set of Users and Tags.
     *  @param  users       A Model_Set_User or Model_User instance of user(s)
     *                      to match.
     *  @param  tags        A Model_Set_Tag instance or array of tags to match.
     *  @param  exactUsers  Items MUST be associated with ALL provided users
     *                      [ false ];
     *  @param  exactTags   Items MUST be associated with ALL provided tags
     *                      [ true ];
     *  @param  order       Optional ORDER clause (string, array)
     *                      [ 'tagCount DESC, userCount DESC, 
     *                         userItemCount DESC, urlHash ASC' ];
     *  @param  count       Optional LIMIT count
     *  @param  offset      Optional LIMIT offset
     *
     *  @return A new Model_Set_Item instance.
     */
    public function fetchByUsersAndTags($users,
                                        $tags,
                                        $exactUsers = false,
                                        $exactTags  = true,
                                        $order      = null,
                                        $count      = null,
                                        $offset     = null)
    {
        if ($order === null)
            $order = array('uti.userItemCount DESC',
                           'uti.userCount     DESC',
                           'uti.tagCount      DESC',
                           'i.urlHash         ASC');

        $to = array('users'      => $users,
                    'tags'       => $tags,
                    'exactUsers' => $exactUsers,
                    'exactTags'  => $exactTags);

        return $this->fetchRelated( $to,
                                    $order,
                                    $count,
                                    $offset );
    }

    /** @brief  Retrieve a set of items that are "similar" to the provided
     *          item (i.e. similar to the Item's URL -- actually, having the
     *                     same host).
     *  @param  id      A Model_Item instance, string url or urlHash, or an
     *                  array of 'property/value' pairs.
     *  @param  order   Optional ORDER clause (string, array);
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Item instance.
     */
    public function fetchSimilar($id,
                                 $order   = null,
                                 $count   = null,
                                 $offset  = null)
    {
        if (! $id instanceof Model_Item)
        {
            $normId = $this->_mapper->normalizeId($id);
            $item   = $this->_mapper->find( $normId );
        }
        else
            $item = $id;

        return $this->_mapper->fetchSimilar($item,
                                            $order,
                                            $count,
                                            $offset);
    }
}
