<?php
/** @file
 *
 *  A Proxy for Service_Activity that exposes only publicly callable methods.
 */
class Service_Proxy_Activity extends Connexions_Service_Proxy
{
    /** @brief  Retrieve a set of activities related to a set of Users.
     *  @param  users   A Model_Set_User instance, array, or comma-separated
     *                  string of users to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ [ 'taggedOn      DESC',
     *                          'name          ASC',
     *                          'userCount     DESC',
     *                          'tagCount      DESC' ] ]
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *  @param  since   Limit the results to activities that occurred after
     *                  this date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Activity instance.
     */
    public function fetchByUsers($users,
                                 $order   = null,
                                 $count   = null,
                                 $offset  = null,
                                 $since   = null)
    {
        return $this->_service->fetchByUsers($users,
                                             $order,
                                             $count,
                                             $offset,
                                             $since);
    }
}
