<?php
/** @file
 *
 *  The concrete base class providing access to Model_Activity.
 */
class Service_Activity extends Connexions_Service
{
    /* inferred via classname
    protected   $_modelName = 'Model_Activity';
    protected   $_mapper    = 'Model_Mapper_Activity'; */

    /** @brief  Any default ordering that should be be merged into a specified 
     *          order.
     */
    protected   $_defaultOrdering   = array(
        'time'      => 'DESC',
    );

    /** @brief  Retrieve a set of Domain Model instances.
     *  @param  id      Identification value(s), null to retrieve all.
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value(s) pairs.
     *  @param  order   An array of name/direction pairs representing the
     *                  desired sorting order.  The 'name's MUST be valid for
     *                  the target Domain Model and the directions a
     *                  Connexions_Service::SORT_DIR_* constant.  If an order
     *                  is omitted, Connexions_Service::SORT_DIR_ASC will be
     *                  used [ $this->_defaultOrdering ];
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
                          $order    = null,
                          $count    = null,
                          $offset   = null,
                          $since    = null)
    {
        $ids     = $this->_csList2array($id);
        $normIds = $this->_mapper->normalizeIds($ids);
        $order   = $this->_csOrder2array($order);

        if ($since !== null)
        {
            $normIds = $this->_includeSince($normIds, $since);
        }

        /*
        Connexions::log("Connexions_Service::fetch() "
                        . "id[ %s ], ids[ %s ], normIds[ %s ], order[ %s ]",
                        Connexions::varExport($id),
                        Connexions::varExport($ids),
                        Connexions::varExport($normIds),
                        Connexions::varExport($order));
        // */

        return $this->_mapper->fetch( $normIds,
                                      $order,
                                      $count,
                                      $offset );

    }

    /** @brief  Retrieve a set of activities related to a set of Users.
     *  @param  users   A Model_Set_User instance, array, or comma-separated
     *                  string of users to match.
     *  @param  order   Optional ORDER clause (string, array)
     *                  [ $this->_defaultOrdering ];
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
        $ids     = array('userId' => $this->_csList2array($users));
        $normIds = $this->_mapper->normalizeIds($ids);
        if ($order !== null)
        {
            $order = $this->_csOrder2array($order);
        }

        if ($since !== null)
        {
            $normIds = $this->_includeSince($normIds, $since);
        }

        return $this->_mapper->fetch( $normIds,
                                      $order,
                                      $count,
                                      $offset );
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Include a date/time restriction.
     *  @param  id      The identifier to add date/time restrictions to;
     *  @param  since   Limit the results to activities that occurred after
     *                  this date/time [ null == no time limits ];
     *
     *  @return The (possibly) modified 'id'.
     */
    protected function _includeSince(array $id, $since)
    {
        if (is_string($since))
        {
            $since = strtotime($since);
            if ($since !== false)
            {
                // Include an additional condition in 'normIds'
                $id['time >='] = strftime('%Y-%m-%d %H:%M:%S', $since);
            }
        }

        return $id;
    }
}
