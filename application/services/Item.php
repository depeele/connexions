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
        $hashes = preg_split('/\s*,\s*/', $csList);

        return $this->_getMapper()->fetchBy('urlHash', $hashes, 'urlHash ASC');
    }

    /** @brief  Retrieve a set of items related by a set of Users.
     *  @param  users   A Model_Set_User instance or array of users to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'userCount DESC' ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Item instance.
     */
    public function fetchByUsers($users,
                                 $order   = 'uti.userCount DESC',
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

    /** @brief  Retrieve a set of items related by a set of Tags.
     *  @param  tags    A Model_Set_Tag instance or array of tags to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'tagCount DESC' ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Model_Set_Item instance.
     */
    public function fetchByTags($tags,
                                $order   = 'tagCount DESC',
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
}
