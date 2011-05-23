<?php
/** @file
 *
 *  A Proxy for Service_Activity that exposes only publicly callable methods.
 */
class Service_Proxy_Activity extends Connexions_Service_Proxy
{
    /** @brief  Retrieve a set of Domain Model instances.
     *  @param  id      Identification value(s), null to retrieve all.
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value(s) pairs.
     *  @param  order   An array of name/direction pairs representing the
     *                  desired sorting order.  The 'name's MUST be valid for
     *                  the target Domain Model and the directions a
     *                  Connexions_Service::SORT_DIR_* constant.  If an order
     *                  is omitted, Connexions_Service::SORT_DIR_ASC will be
     *                  used [ 'time DESC' ];
     *  @param  count   The maximum number of items from the full set of
     *                  matching items that should be returned
     *                  [ null == all ];
     *  @param  offset  The starting offset in the full set of matching items
     *                  [ null == 0 ].
     *  @param  since   Limit the results to activities that occurred after
     *                  this date/time [ null == no time limits ];
     *
     *  @return A new Connexions_Model_Set.
     */
    public function fetch($id       = null,
                          $order    = 'time DESC',
                          $count    = 50,
                          $offset   = 0,
                          $since    = null)
    {
        return $this->_service->fetch($id,
                                      $order,
                                      $count,
                                      $offset,
                                      $since);
    }

    /** @brief  Retrieve a set of activities related to a set of Users.
     *  @param  users   A Model_Set_User instance, array, or comma-separated
     *                  string of users to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                  [ 'time DESC' ];
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *  @param  since   Limit the results to activities that occurred after
     *                  this date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Activity instance.
     */
    public function fetchByUsers($users,
                                 $order    = 'time DESC',
                                 $count    = 50,
                                 $offset   = 0,
                                 $since   = null)
    {
        return $this->_service->fetchByUsers($users,
                                             $order,
                                             $count,
                                             $offset,
                                             $since);
    }
}
