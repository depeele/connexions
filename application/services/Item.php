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

    /** @brief  Retrieve a set of items related by a set of Users.
     *  @param  users   A Model_Set_User instance, array, or comma-separated
     *                  string of users to match.
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
        // Rely on Service_User to properly interpret 'users'
        $users = $this->factory('Service_User')->csList2set($users);

        if ($order === null)
            $order = array('uti.userCount     DESC',
                           'uti.tagCount      DESC',
                           'uti.userItemCount DESC',
                           'i.urlHash         ASC');

        return $this->_mapper->fetchRelated( array(
                                        'users'  => $users,
                                        'order'  => $order,
                                        'count'  => $count,
                                        'offset' => $offset,
                                    ));
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
        // Rely on Service_Tag to properly interpret 'tags'
        $tags = $this->factory('Service_Tag')->csList2set($tags);

        if ($order === null)
            $order = array('uti.tagCount      DESC',
                           'uti.userCount     DESC',
                           'uti.userItemCount DESC',
                           'i.urlHash         ASC');

        return $this->_mapper->fetchRelated( array(
                                        'tags'      => $tags,
                                        'exactTags' => $exact,
                                        'order'     => $order,
                                        'count'     => $count,
                                        'offset'    => $offset,
                                    ));
    }

    /** @brief  Retrieve a set of items related by a set of Users and Tags.
     *  @param  users   A Model_Set_User or Model_User instance of user(s)
     *                  to match.
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
    public function fetchByUsersAndTags($users,
                                        $tags,
                                        $exact   = true,
                                        $order   = null,
                                        $count   = null,
                                        $offset  = null)
    {
        /* Rely on Service_User and Service_Tag to properly interpret 'users'
         * and 'tags'
         */
        $users = $this->factory('Service_User')->csList2set($users);
        $tags  = $this->factory('Service_Tag')->csList2set($tags);

        if ($order === null)
            $order = array('uti.userItemCount DESC',
                           'uti.userCount     DESC',
                           'uti.tagCount      DESC',
                           'i.urlHash         ASC');

        return $this->_mapper->fetchRelated( array(
                                        'users'     => $users,
                                        'tags'      => $tags,
                                        'exactTags' => $exact,
                                        'order'     => $order,
                                        'count'     => $count,
                                        'offset'    => $offset,
                                    ));
    }
}
