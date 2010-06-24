<?php
/** @file
 *
 *  A Proxy for Service_User that exposes only publicly callable methods.
 */
class Service_Proxy_User extends Connexions_Service_Proxy
{
    /** @brief  Given a user identifier and/or credential, attempt to
     *          authenticate the identified user.
     *  @param  authType    The type of authentication to perform
     *                      (Model_UserAuth::AUTH_*)
     *  @param  credential  Any initial user credential
     *                      (e.g. OpenId endpoint).
     *
     *  @return A Model_User instance with isAuthenticated() set accordingly.
     */
    public function authenticate($authType   = Model_UserAuth::AUTH_PASSWORD,
                                 $credential = null)
    {
        return $this->_service->authenticate($authType, $credential);
    }

    /** @brief  Retrieve a set of users related by a set of Tags.
     *  @param  tags    A comma-separated list of tags to match;
     *  @param  exact   Users MUST be associated with provided tags [ true ];
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ 'tagCount DESC' ];
     *  @param  count   Optional LIMIT count;
     *  @param  offset  Optional LIMIT offset;
     *
     *  @return A new Model_Set_User instance.
     */
    public function fetchByTags($tags,
                                $exact   = true,
                                $order   = null,
                                $count   = null,
                                $offset  = null)
    {
        if (is_string($tags))
        {
            /* ASSUME this is a comma-separated string and convert it to a
             * Model_Set_Tag instance.
             */
            $tagSet = Connexions_Service::factory('Service_Tag')
                            ->csList2set($tags);

            // /*
            Connexions::log("Service_Proxy_User::fetchByTags(): "
                            .   "tag string[ %s ] == [ %s ]",
                            $tags, $tagSet);
            // */
            $tags = $tagSet;
        }

        return $this->_service->fetchByTags($tags,
                                            $exact,
                                            $order,
                                            $count,
                                            $offset);
    }

    /** @brief  Given a comma-separated list of tag rename information, rename
     *          tags for the currently authenticated user.
     *  @param  renames     A comma-separated list of tag rename information,
     *                      echo item of the form:
     *                          'oldTagName::newTagName'
     *
     *  @return An array of status information, keyed by old tag name:
     *              { 'oldTagName'  => true (success) |
     *                                 String explanation of failure,
     *                 ... }
     */
    public function renameTags($renames)
    {
        if (is_string($renames))
        {
            $ar = $this->_csList2array($renames);

            if (! empty($ar))
            {
                $renames = array();
                foreach ($ar as $item)
                {
                    list($old,$new) = split('::', $item);
                    $renames[$old] = $new;
                }
            }
        }

        return $this->_service->renameTags(Connexions::getUser(),
                                           $renames);
    }

    /** @brief  Given a comma-separated list of tag names, delete all tags for
     *          the currently authenticated user.  If deleting a tag will
     *          result in an "orphaned bookmark" (i.e. a bookmark with no
     *          tags), the delete of that tag will fail.
     *  @param  tags        A comma-separated list of tags.
     *
     *  @return An array of status information, keyed by tag name:
     *              { 'tagName' => true (success) |
     *                             String explanation of failure,
     *                 ... }
     */
    public function deleteTags($tags)
    {
        if (is_string($tags))
        {
            $tags = $this->_csList2array($tags);
        }

        return $this->_service->deleteTags(Connexions::getUser(),
                                           $tags);
    }
}
