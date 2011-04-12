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
