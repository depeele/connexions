<?php
/** @file
 *
 *  An extension of Connexions_Model_Mapper_DbTable that adds
 *      fetchRelated()
 */
abstract class Model_Mapper_Base extends Connexions_Model_Mapper_DbTable
{
    /** @brief  Convert the incoming model into an array containing only 
     *          data that should be directly persisted.  This method may also
     *          be used to update dynamic values
     *          (e.g. update date/time, last visit date/time).
     *  @param  model       The Domain Model to reduce to an array.
     *  @param  keepKeys    If keys need to be kept, a concrete sub-class can
     *                      override reduceModel() and invoke with 'true'.
     *
     *  @return A filtered associative array containing data that should 
     *          be directly persisted.
     */
    public function reduceModel(Connexions_Model $model,
                                                 $keepKeys = false)
    {
        $data = parent::reduceModel( $model, $keepKeys );

        unset($data['userItemCount']);
        unset($data['userCount']);
        unset($data['itemCount']);
        unset($data['tagCount']);
        unset($data['ratingCount']);
        unset($data['ratingAvg']);

        return $data;
    }

    /** @brief  Retrieve a set of Domain Model items via the userTagItem core
     *          table.
     *  @param  params  An array retrieval criteria:
     *                      - users     The Model_Set_User or Model_User
     *                                  instance, or an array of userIds to use
     *                                  in the relation;
     *                      - items     The Model_Set_Item or Model_Item
     *                                  instance or an array of itemIds to use
     *                                  in the relation;
     *                      - tags      The Model_Set_Tag or Model_Tag
     *                                  instance or an array of tagIds to use
     *                                  in the relation;
     *                      - order     Optional ORDER clause (string, array);
     *                      - count     Optional LIMIT count;
     *                      - offset    Optional LIMIT offset;
     *                      - exactTags If 'tags' is provided,  should we
     *                                  require a match on ALL tags? [ true ];
     *                      - where     Additional condition(s) [ null ];
     *                      - paginate  Return a Zend_Paginator instead of
     *                                  a Connexions_Model_Set.
     *
     *  @return A Connexions_Model_Set instance that provides access to all
     *          matching Domain Model instances.
     */
    public function fetchRelated( array $params = array())
    {
        $modelName = $this->getModelName();

        /* Convert the model class name to an abbreviation composed of all
         * upper-case characters following the first '_', then converted to
         * lower-case (e.g. Model_UserAuth == 'ua').
         */
        $as       = strtolower(preg_replace('/^[^_]+_([A-Z])[a-z]+'
                                            . '(?:([A-Z])[a-z]+)?'
                                            . '(?:([A-Z])[a-z]+)?$/',
                                            '$1$2$3', $modelName));
        $subKeys  = (is_array($this->_keyName)
                        ? $this->_keyName
                        : array( $this->_keyName ));

        $accessor = $this->getAccessor();
        $db       = $accessor->getAdapter();

        /********************************************************************
         * Generate the primary select.
         *
         */
        $select   = $db->select();
        $select->from( array( $as =>
                                $accessor->info(Zend_Db_Table_Abstract::NAME)),
                       array("{$as}.*",
                             'uti.userItemCount',
                             'uti.userCount',
                             'uti.itemCount',
                             'uti.tagCount'));

        $secSelect = $this->_generateSecondarySelect($select, $params);

        $joinCond = array();
        foreach ($subKeys as $name)
        {
            array_push($joinCond, "{$as}.{$name}=uti.{$name}");
        }

        /* See if there are any "special fields" indicated in the sort order.
         *
         * These are of the form:
         *      table:field
         *
         * and indicate fields from other, additional tables.
         *
         * :NOTE: The keys of any additional tables MUST ALSO be fields of the
         *        secondary table targeted by $secSelect;
         */
        $specials = (isset($params['order'])
                        ? $this->_getSpecialFields($params['order'])
                        : array());
        if (! empty($specials))
        {
            /* We need additional sub-select(s) to include the additional
             * table(s).
             */
            $secSelect = $this->_addJoins($secSelect, $specials, 'uti');
        }

        // Join the select and sub-select
        $select->join(array('uti' => $secSelect),
                      implode(' AND ', $joinCond),
                      null);



        if ( isset($params['where']))
            $select->where( $params['where'] );
         
        $order  = (isset($params['order'])  ? $params['order']  : null);
        $count  = (isset($params['count'])  ? $params['count']  : null);
        $offset = (isset($params['offset']) ? $params['offset'] : null);


        if (($count !== null) || ($offset !== null))
        {
            /* Connexions_Model_Mapper_DbTable will automatically generate a
             * 'totalCount' value numbering the entire set without limits.
             *
             *
             * :TODO: Optimize the count query, postpone the setting of
             *        'totalCount' in Connexions_Model_Mapper_DbTable, and
             *        set the 'totalCount' here.
             */

            if (count($secSelect->getPart( Zend_Db_Select::WHERE )) < 1)
            {
                /* Since there are no WHERE conditions in the sub-select.  It
                 * is purly for statistics gathering and thus not needed for
                 * counting the full result set.
                 *
                 * :TODO: Grab a clone of $select above, before the secSelect
                 *        is joined to it, remove all columns, limits,
                 *        ordering, etc., and include a simple 'COUNT(1)' to
                 *        count the full result set.
                 */
            }
        }

        // /*
        Connexions::log("Model_Mapper_Base[%s]::fetchRelated(): "
                        .   "sql[ %s ], order[ %s ], count[ %s ], offset[ %s ]",
                        get_class($this),
                        $select->assemble(),
                        ($order  ? Connexions::varExport($order)  : 'null'),
                        ($count  ? $count  : 'null'),
                        ($offset ? $offset : 'null'));
        // */

        $set    = $this->fetch($select, $order, $count, $offset);

        if ( isset($params['paginate']) && ($params['paginate'] !== false) )
        {
            $result = new Zend_Paginator( $set->getPaginatorAdapter() );
        }
        else
        {
            $result =& $set;
        }

        return $result;
    }

    /************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Given an array containing field values, see if any of the
     *          fields are "special" fields of the form 'table:field'.
     *  @param  fieldSet    An array of field-like strings.
     *
     *  During processing, convert any "special" fields in 'fieldSet' to
     *  reflect the table alias that will be used instead of using the table
     *  name directly.
     *
     *  @return An array of the form:
     *          {
     *              <tableName>: {
     *                  'table':    <tableName>,
     *                  'as':       <as alias>,
     *                  'fields':   {
     *                      <fieldName>: true,
     *                      ...
     *                  }
     *              }
     *          }
     */
    protected function _getSpecialFields(&$fieldSet)
    {
        $specials = array();

        if (! is_array($fieldSet))
            $fieldSet = array($fieldSet);

        foreach ($fieldSet as &$field)
        {
            if (strpos($field, ':'))
            {
                // This is a special 'table:field' indicator
                list($tField, $tRest)     = explode(' ', $field);
                list($table, $tableField) = explode(':', $tField);

                if (! isset($specials[$table]))
                {
                    $as = strtolower(preg_replace('/^([A-Za-z])[a-z]+'
                                                  . '(?:([A-Z])[a-z]+)?'
                                                  . '(?:([A-Z])[a-z]+)?$/',
                                                  '$1$2$3', $table));
                    $specials[$table] = array(
                        'table'     => $table,
                        'as'        => $as,
                        'fields'    => array(),
                    );
                }

                $specials[$table]['fields'][$tableField] = true;

                $field = 'uti.'. $tableField .' '. $tRest;
            }
        }

        return $specials;
    }

    /** @brief  Given an existing Zend_Db_Select instance and an array of
     *          special fields generated by _getSpecialFields(), add
     *          sub-selects to include the requested tables/fields.
     *  @param  select      The primary Zend_Db_Select instance.
     *  @param  specials    Special fields informationi generated by
     *                      _getSpecialFields();
     *  @param  mainAs      The alias name of the primary table;
     *
     *  :NOTE: The tables added to the select MUST have key fields shared with
     *         the primary select table.
     *
     *  @return The new Zend_Db_Select with additional sub-selects.
     */
    protected function _addJoins(Zend_Db_Select $select,
                                 array          $specials,
                                                $mainAs)
    {
        $db = $select->getAdapter();
        foreach ($specials as $table => $info)
        {
            /* Attempt to locate the mapper for this table
             *  Try 'Model_Mapper_<ucfirst $table>'
             */
            $mapperName = 'Model_Mapper_'. ucfirst($table);
            Connexions::log("Mapper_Base::_addJoins(): "
                            .   "table[ %s ], find mapper[ %s ]...",
                            $table, $mapperName);

            $mapper     = Connexions_Model_Mapper::factory($mapperName);
            if (! $mapper)
            {
                throw new Exception("Cannot locate mapper for "
                                    .   "table '{$table}'");
            }

            // Keys for this table / sub-select
            $subKeys  = (is_array($mapper->_keyName)
                            ? $mapper->_keyName
                            : array( $mapper->_keyName ));

            $fields      = $subKeys;
            $extraFields = array();
            foreach ($info['fields'] as $field => $bool)
            {
                $expr = $this->_fieldExpression($table, $info['as'], $field);

                array_push($extraFields, $info['as'] .'.'. $field);
                array_push($fields, $expr);
            }

            $subSelect = $db->select();
            $subSelect->from($table, $fields);

            $joinCond = array();
            foreach ($subKeys as $name)
            {
                array_push($joinCond,
                           "{$info['as']}.{$name}={$mainAs}.{$name}");
            }

            // Join the select and sub-select
            $select->join(array($info['as'] => $subSelect),
                          implode(' AND ', $joinCond),
                          null);

            // Include the fields of interest in the columns returned.
            $select->columns($extraFields);
        }

        return $select;
    }

    /** @brief  Given a table and field, return the proper expressioni of the
     *          field.
     *  @param  table   The table in question.
     *  @param  field   The field in question.
     *
     *  @return The field expression.
     */
    protected function _fieldExpression($table, $as, $field)
    {
        /* Handle constructed fields
         *  item.ratingAvg              =  (CASE WHEN ratingCount > 0
         *                                       THEN ratingSum/ratingCount
         *                                       ELSE 0 END) as ratingAvg
         *  userTagItem.userCount       = COUNT(DISTINCT userId)
         *  userTagItem.tagCount        = COUNT(DISTINCT tagId)
         *  userTagItem.itemCount       = COUNT(DISTINCT itemId)
         *  userTagItem.userItemCount   = COUNT(DISTINCT userId,itemId)
         */
        $expr = $field;
        if ($table === 'item')
        {
            switch ($field)
            {
            case 'ratingAvg':
                $expr  = "(CASE WHEN ratingCount > 0 "
                       .       "THEN ratingSum / ratingCount "
                       .       "ELSE 0 END) AS ratingAvg";
                break;
            }
        }
        else if ($table === 'userTagItem')
        {
            switch ($field)
            {
            case 'userCount':
                $expr  = "COUNT(DISTINCT {$as}.userId) AS userCount";
                break;

            case 'tagCount':
                $expr  = "COUNT(DISTINCT {$as}.tagId) AS tagCount";
                break;

            case 'itemCount':
                $expr  = "COUNT(DISTINCT {$as}.itemId) AS itemCount";
                break;

            case 'userItemCount':
                $expr  = "COUNT(DISTINCT {$as}.userId,{$as}.itemId) "
                       .                              "AS userItemCount";
                break;
            }
        }

        return $expr;
    }

    /** @brief  Generate the secondary SQL select, primarily for increasing the
     *          performance of the query (via 'GROUP BY'), but also for
     *          retrieving statistics.
     *  @param  select      The primary Zend_Db_Select instance.
     *  @param  params      An array retrieval criteria.
     *
     *  @return The Zend_Db_Select instance.
     */
    protected function _generateSecondarySelect(Zend_Db_Select  $select,
                                                array           $params)
    {
        $db        = $select->getAdapter();
        $secSelect = $db->select();
        $secSelect->from(array('uti' => 'userTagItem'),
                         array('uti.*',
                               $this->_fieldExpression('userTagItem', 'uti',
                                                            'userCount'),
                               $this->_fieldExpression('userTagItem', 'uti',
                                                            'tagCount'),
                               $this->_fieldExpression('userTagItem', 'uti',
                                                            'itemCount'),
                               $this->_fieldExpression('userTagItem', 'uti',
                                                            'userItemCount'),
                               /*
                               'userItemCount'  =>
                                                'COUNT(DISTINCT userId,itemId)',
                               'userCount'      =>
                                                'COUNT(DISTINCT userId)',
                               'itemCount'      =>
                                                'COUNT(DISTINCT itemId)',
                               'tagCount'       =>
                                                'COUNT(DISTINCT tagId)',
                                */
                         ))
                  ->group( $this->_keyName );

        // include any limiters in the sub-select
        if ( isset($params['users']) && (! empty($params['users'])) )
        {
            $users =& $params['users'];

            if ($users instanceof Model_Set_User)
            {
                if (count($users) > 0)
                {
                    $secSelect->where('userId IN (?)',
                                      $users->idArray());
                }
            }
            else if (is_array($users))
            {
                if (count($users) > 0)
                {
                    $secSelect->where('userId IN (?)', $users);
                }
            }
            else if ($users instanceof Model_User)
            {
                $secSelect->where('userId=?', $users->userId);
            }
            else
            {
                $secSelect->where('userId=?', $users);
            }
        }
        if ( isset($params['items']) && (! empty($params['items'])) )
        {
            $items =& $params['items'];

            if ($items instanceof Model_Set_Item)
            {
                if (count($items) > 0)
                {
                    $secSelect->where('uti.itemId IN (?)',
                                      $items->idArray());
                }
            }
            else if (is_array($items))
            {
                if (count($items) > 0)
                {
                    $secSelect->where('uti.itemId IN (?)', $items);
                }
            }
            else if ($items instanceof Model_Item)
            {
                $secSelect->where('uti.itemId=?', $items->itemId);
            }
            else
            {
                $secSelect->where('uti.itemId=?', $items);
            }
        }
        if ( isset($params['tags']) && (! empty($params['tags'])) )
        {
            $tags =& $params['tags'];

            if ($tags instanceof Model_Set_Tag)
            {
                if (count($tags) > 0)
                {
                    $secSelect->where('tagId IN (?)',
                                      $tags->idArray());
                }
            }
            else if (is_array($tags))
            {
                if (is_int($tags[0]))
                {
                    $secSelect->where('tagId IN (?)', $tags);
                }
                else if (count($tags) > 0)
                {
                    // :NOTE: The primary table MUST have a 'tag' field
                    $select->where('tag IN (?)', $tags);
                }
            }
            else if (is_int($tags))
            {
                $secSelect->where('tagId=?', $tags);
            }
            else if ($tags instanceof Model_Tag)
            {
                $secSelect->where('tagId=?', $tags->tagId);
            }
            else
            {
                // :NOTE: The primary table MUST have a 'tag' field
                $select->where('tag=?', $tags);
            }

            if ( (! isset($params['exactTags'])) ||
                 ($params['exactTags'] !== false) )
            {
                $nTags = count($tags);
                if ($nTags > 1)
                    $secSelect->having('tagCount='. $nTags);
            }
        }

        return $secSelect;
    }
}
