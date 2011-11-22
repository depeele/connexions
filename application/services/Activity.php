<?php
/** @file
 *
 *  The concrete base class providing access to Model_Activity.
 */
class Service_Activity extends Service_Base
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
     *  @param  id          Identification value(s), null to retrieve all.  MAY
     *                      be an associative array that specifically
     *                      identifies attribute/value(s) pairs
     *                      [ null == all ];
     *  @param  objectType  An array or comma-separated string of the object(s)
     *                      of interest (user, item, tag, bookmark)
     *                      [ null == all ];
     *  @param  operation   An array or comma-separated string of the
     *                      operations(s) of interest (save, update, delete)
     *                      [ null == all ];
     *  @param  order       An array of name/direction pairs representing the
     *                      desired sorting order.  The 'name's MUST be valid
     *                      for the target Domain Model and the directions a
     *                      Connexions_Service::SORT_DIR_* constant.  If an
     *                      order is omitted, Connexions_Service::SORT_DIR_ASC
     *                      will be used [ $this->_defaultOrdering ];
     *  @param  count       The maximum number of items from the full set of
     *                      matching items that should be returned
     *                      [ null == all ];
     *  @param  offset      The starting offset in the full set of matching
     *                      items [ null == 0 ].
     *  @param  since       Limit the results to activities that occurred after
     *                      this date/time [ null == no time limits ];
     *
     *  @return A new Connexions_Model_Set.
     */
    public function fetch($id           = null,
                          $objectType   = null,
                          $operation    = null,
                          $order        = null,
                          $count        = null,
                          $offset       = null,
                          $since        = null)
    {
        $ids     = $this->_csList2array($id);
        $normIds = $this->_mapper->normalizeIds($ids);
        $order   = $this->_csOrder2array($order);

        if (! empty($objectType))
        {
            $normIds = $this->_includeValues($normIds, 'objectType',
                                             $objectType);
        }

        if (! empty($operation))
        {
            $normIds = $this->_includeValues($normIds, 'operation',
                                             $operation);
        }

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
     *  @param  users       A Model_Set_User instance, array, comma-separated
     *                      string of users to match or null for all users.
     *  @param  objectType  An array or comma-separated string of the object(s)
     *                      of interest (user, item, tag, bookmark)
     *                      [ null == all ];
     *  @param  operation   An array or comma-separated string of the
     *                      operations(s) of interest (save, update, delete)
     *                      [ null == all ];
     *  @param  order       Optional ORDER clause (string, array)
     *                      [ $this->_defaultOrdering ];
     *  @param  count       Optional LIMIT count
     *  @param  offset      Optional LIMIT offset
     *  @param  since       Limit the results to activities that occurred after
     *                      this date/time [ null == no time limits ];
     *
     *  @return A new Model_Set_Activity instance.
     */
    public function fetchByUsers($users,
                                 $objectType    = null,
                                 $operation     = null,
                                 $order         = null,
                                 $count         = null,
                                 $offset        = null,
                                 $since         = null)
    {
        $users   = $this->factory('Service_User')->csList2set($users);
        $normIds = (count($users) > 0
                        ? array('userId' => $users->getIds())
                        : array());
        $order   = $this->_csOrder2array($order);

        if (! empty($objectType))
        {
            $normIds = $this->_includeValues($normIds, 'objectType',
                                             $objectType);
        }

        if (! empty($operation))
        {
            $normIds = $this->_includeValues($normIds, 'operation',
                                             $operation);
        }

        if ($since !== null)
        {
            $normIds = $this->_includeSince($normIds, $since);
        }

        Connexions::log("Model_Activity::fetchByUsers(): "
                        . "objectType[ %s ], operation[%s], normIds[ %s ]",
                        Connexions::varExport($objectType),
                        Connexions::varExport($operation),
                        Connexions::varExport($normIds));

        return $this->_mapper->fetch( $normIds,
                                      $order,
                                      $count,
                                      $offset );
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Include value restrictions.
     *  @param  id      The identifier to add restrictions to;
     *  @param  field   The field to match;
     *  @param  values  The array or comma-separates string of values;
     *
     *  @return The (possibly) modified 'id'.
     */
    protected function _includeValues(array $id, $field, $values)
    {
        if (is_string($values))
        {
            $values = preg_split('/\s*,\s*/', $values);
        }

        if (is_array($values) && (count($values) > 0))
        {
            $id[$field] = $values;
        }

        return $id;
    }

    /** @brief  Include a date/time restriction.
     *  @param  id      The identifier to add date/time restrictions to;
     *  @param  since   Limit the results to activities that occurred after
     *                  this date/time [ null == no time limits ];
     *
     *  @return The (possibly) modified 'id'.
     */
    protected function _includeSince($id, $since)
    {
        $orig = $since;
        if (is_int($since) || is_numeric($since))
        {
            // ASSUME this is a unix timestamp
            $since = strftime('%Y-%m-%d %H:%M:%S', $since);
            if ($since !== false)
            {
                $id['time >='] = $since;
            }
        }
        else if (is_string($since))
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
