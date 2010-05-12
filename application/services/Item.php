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

    /** @brief  Convert a comma-separated list of urlHashes to a 
     *          Model_Set_Item instance.
     *  @param  csList  The comma-separated list of urlHashes.
     *
     *  @return Model_Set_Uset
     */
    public function csList2set($csList)
    {
        $hashes = (empty($csList)
                    ? array()
                    : preg_split('/\s*,\s*/', strtolower($csList)) );

        if (empty($hashes))
        {
            $set = $this->_getMapper()->makeEmptySet();
        }
        else
        {
            $set = $this->_getMapper()->fetchBy('urlHash', $hashes,
                                                'urlHash ASC');
            $set->setSource($csList);
        }

        return $set;
    }

    /** @brief  Retrieve a set of items related by a set of Users.
     *  @param  users   A Model_Set_User instance or array of users to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'userCount DESC, tagCount DESC,
     *                         userItemCount DESC, urlHash ASC' ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Item instance.
     */
    public function fetchByUsers($users,
                                 $order   = null,
                                 $count   = null,
                                 $offset  = null)
    {
        if ($order === null)
            $order = array('uti.userCount     DESC',
                           'uti.tagCount      DESC',
                           'uti.userItemCount DESC',
                           'i.urlHash         ASC');

        return $this->_getMapper()->fetchRelated( array(
                                        'users'  => $users,
                                        'order'  => $order,
                                        'count'  => $count,
                                        'offset' => $offset,
                                    ));
    }

    /** @brief  Retrieve a set of items related by a set of Tags.
     *  @param  tags    A Model_Set_Tag instance or array of tags to match.
     *  @param  exact   Items MUST be associated with provided tags [ true ];
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
            $order = array('uti.tagCount      DESC',
                           'uti.userCount     DESC',
                           'uti.userItemCount DESC',
                           'i.urlHash         ASC');

        return $this->_getMapper()->fetchRelated( array(
                                        'tags'      => $tags,
                                        'exactTags' => $exact,
                                        'order'     => $order,
                                        'count'     => $count,
                                        'offset'    => $offset,
                                    ));
    }
}
