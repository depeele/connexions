<?php
/** @file
 *
 *  A Proxy for Service_Item that exposes only publicly callable methods.
 */
class Service_Proxy_Item extends Connexions_Service_Proxy
{
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
        return $this->_service->fetchByUsers($users,
                                             $order,
                                             $count,
                                             $offset);
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
        return $this->_service->fetchByTags($tags,
                                            $exact,
                                            $order,
                                            $count,
                                            $offset);
    }

    /** @brief  Retrieve a set of items related by a set of Users and Tags.
     *  @param  users       A Model_Set_User or Model_User instance of user(s)
     *                      to match.
     *  @param  tags        A Model_Set_Tag instance or array of tags to match.
     *  @param  exactUsers  Items MUST be associated with ALL provided users
     *                      [ true ];
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
                                        $exactUsers = true,
                                        $exactTags  = true,
                                        $order      = null,
                                        $count      = null,
                                        $offset     = null)
    {
        return $this->_service->fetchByUsersAndTags($users,
                                                    $tags,
                                                    $exactUsers,
                                                    $exactTags,
                                                    $order,
                                                    $count,
                                                    $offset);
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
        return $this->_service->fetchSimilar($id,
                                             $order,
                                             $count,
                                             $offset);
    }
}
