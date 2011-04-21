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
        unset($data['ratingSum']);
        unset($data['ratingAvg']);

        return $data;
    }

    /** @brief  Retrieve a set of Domain Model items via the userTagItem core
     *          table.
     *  @param  params  An array retrieval criteria:
     *                      - bookmarks     The Model_Set_Bookmark or
     *                                      Model_Bookmark, or an array of
     *                                      (userId,itemId) items to use
     *                                      in the relation;
     *                      - users         The Model_Set_User or Model_User
     *                                      instance, or an array of userIds to
     *                                      use in the relation;
     *                      - items         The Model_Set_Item or Model_Item
     *                                      instance or an array of itemIds to
     *                                      use in the relation;
     *                      - tags          The Model_Set_Tag or Model_Tag
     *                                      instance or an array of tagIds to
     *                                      use in the relation;
     *                      - order         Optional ORDER clause
     *                                      (string, array);
     *                      - count         Optional LIMIT count;
     *                      - offset        Optional LIMIT offset;
     *                      - exactUsers    If 'users' is provided,  should we
     *                                      require a match on ALL users?
     *                                      [ true ];
     *                      - exactItems    If 'items' is provided,  should we
     *                                      require a match on ALL items?
     *                                      [ true ];
     *                      - exactTags     If 'tags' is provided,  should we
     *                                      require a match on ALL tags?
     *                                      [ true ];
     *                      - where         Additional condition(s) [ null ];
     *                      - paginate      Return a Zend_Paginator instead of
     *                                      a Connexions_Model_Set.
     *                      - fields        An array of fields to return
     *                                      [ '*' ];
     *                      - excludeSec    If true, do NOT include either
     *                                      the secondary tables NOR statistics
     *                                      [ false ];
     *                      - excludeStats  If true, do NOT include statistics
     *                                      [ false ];
     *                      - rawRows       If true, return raw rows instead of
     *                                      model instances [ false ];
     *                      - group         Any additional SQL GROUP BY
     *                                      clauses [ null ];
     *
     *  @return A Connexions_Model_Set instance that provides access to all
     *          matching Domain Model instances.
     */
    public function fetchRelated( array $params = array())
    {
        $as       = $this->_getModelAlias();
        $accessor = $this->getAccessor();
        $db       = $accessor->getAdapter();

        /********************************************************************
         * Generate the primary select.
         *
         */
        $fields  = array();
        $rawRows = (isset($params['rawRows'])
                        ? $params['rawRows']
                        : false);
        if (is_array($params['fields']))
        {
            foreach ($params['fields'] as $alias => $field)
            {
                if (is_int($alias))
                {
                    array_push($fields, "{$as}.{$field}");
                }
                else
                {
                    $fields[$alias] = $field;
                }
            }

            if ($rawRows !== false)
            {
                $params['rawRows'] = $fields;
            }
        }
        else
        {
            array_push($fields, "{$as}.*");
            if ($rawRows !== false)
            {
                $params['rawRows'] = true;
            }
        }

        $select   = $db->select();
        $select->from( array( $as =>
                                $accessor->info(Zend_Db_Table_Abstract::NAME)),
                       $fields );

        if ( (! isset($params['excludeSec'])) ||
             ($params['excludeSec'] !== true) )
        {
            $this->_includeSecondarySelect($select, $as, $params);
        }


        if ( isset($params['where']) && (! empty($params['where'])) )
        {
            $where = $this->_where( (array)$params['where'] );

            /*
            Connexions::log("Model_Mapper_Base[%s]::fetchRelated(): "
                            .   "where[ %s ] == [ %s ]",
                            get_class($this),
                            Connexions::varExport($params['where']),
                            Connexions::varExport($where));
            // */

            $this->_addWhere($select, $where);
        }

        if ( isset($params['group']) && (! empty($params['group'])) )
        {
            $select->group($params['group']);
        }
         
        $order  = (isset($params['order'])  ? $params['order']  : null);
        $count  = (isset($params['count'])  ? $params['count']  : null);
        $offset = (isset($params['offset']) ? $params['offset'] : null);

        /*
        Connexions::log("Model_Mapper_Base[%s]::fetchRelated(): "
                        .   "sql[ %s ], order[ %s ], "
                        .   "count[ %s ], offset[ %s ], "
                        .   "rawRows[ %s ]",
                        get_class($this),
                        $select->assemble(),
                        ($order  ? Connexions::varExport($order)  : 'null'),
                        ($count  ? $count  : 'null'),
                        ($offset ? $offset : 'null'),
                        Connexions::varExport($rawRows));
        // */

        $set    = $this->fetch($select, $order, $count, $offset, $rawRows);

        /*
        Connexions::log("Model_Mapper_Base[%s]::fetchRelated(): "
                        .   "result set[ %s ]",
                        get_class($this),
                        $set);
        // */


        /* :XXX: We SHOULDN'T have 'paginate' true AND 'rawRows' anything other
         *       than absent or false.  If that scenario is needed, extra work
         *       needs to be done...
         */
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

    /** @brief  Given a grouping string, convert it to an SQL DATE_FORMAT
     *          string useful for grouping by a specific date/time period;
     *  @param  group   The grouping string indicating how entries should be
     *                  grouped / rolled-up.  A string of the form:
     *                          p[:b]
     *                  Where 'p' may be any reasonable combination of:
     *                      H       Hour;
     *                      D       Day;
     *                      d       Day-of-week;
     *                      W       Week (beginning on Monday);
     *                      w       Week (beginning on Sunday);
     *                      M       Month;
     *                      Y       Year;
     *
     *                  'b' is a single character indicating that the timeline
     *                  information should be grouped into one or more series
     *                  where each series is identified by 'p'.  'b' is any
     *                  single character valid for 'p' that also makes sense as
     *                  the final period/count component of a series.
     *
     *                  For example:
     *                      'YMDH'      - indicates a timeline comprised of all
     *                                    counts by year/month/day/hour with a
     *                                    single series
     *                      'YMD:H'     - indicates a timeline comprised of
     *                                    a series of counts by hour for each
     *                                    measured year/month/day;
     *                      'YMD'       - indicates a timeline comprised of all
     *                                    counts by year/month/day with a
     *                                    single series
     *                      'YM:D'      - indicates a timeline comprised of
     *                                    a series of counts by day for each
     *                                    measured year/month;
     *
     *  @return A simple object comprised of:
     *              fmt         - an SQL DATE_FORMAT string used for selecting
     *                            the data of the timeline;
     *              seriesIdLen - the numer of characters from the resulting
     *                            date that should be used to break the data
     *                            into one or more series;
     */
    protected function _normalizeGrouping($group)
    {
        $fmt         = '%Y%m%d%H%i';    // '%Y-%m-%d %H:%i:%S';
        $seriesIdLen = 0;

        if (preg_match('/([YMWwDdH:]+)/', $group, $matches))
        {
            $full  = $matches[1];
            $parts = explode(':', $full);
            $p     = $parts[0];
            $b     = (count($parts) > 1 ? $parts[1][0] : '');

            $fmt      = '';
            $totalLen = 0;
            if (strpos($full, 'Y') !== false)
            {
                // Year
                $fmt      .= '%Y';
                $totalLen += 4;
            }
            if (strpos($full, 'M') !== false)
            {
                // Month (01-12)
                $fmt      .= '%m';
                $totalLen += 2;
            }
            if (strpos($full, 'D') !== false)
            {
                // Day of month (01-31)
                $fmt      .= '%d';
                $totalLen += 2;
            }

            if (strpos($full, 'W') !== false)
            {
                // Week-of-year with Monday as the first day-of-week (01-53)
                $fmt      .= '%u';
                $totalLen += 2;
            }
            else if (strpos($full, 'w') !== false)
            {
                // Week-of-year with Sunday as the first day-of-week (01-53)
                $fmt      .= '%U';
                $totalLen += 2;
            }

            if (strpos($full, 'd') !== false)
            {
                // Day-of-week (0-6)
                $fmt      .= '%w';
                $totalLen += 1;         // Component length of just 1
            }

            if (strpos($full, 'H') !== false)
            {
                // Hour (00-23)
                $fmt      .= '%H';
                $totalLen += 2;
            }

            if (! empty($b))
            {
                /* The 'seriesIdLen' is everything EXCEPT the length of the
                 * final component indicated by 'b'.
                 *
                 * The component length of all valid 'b' values except 'd' is
                 * 2.  The component length for 'd' is 1.
                 */
                $seriesIdLen = $totalLen - ($b === 'd' ? 1 : 2);
            }
        }

        $res = array(
            'fmt'           => $fmt,
            'seriesIdLen'   => $seriesIdLen,
        );

        /*
        Connexions::log("Model_Mapper_Base(%s)::_normalizeGrouping(): "
                        .   "group[ %s ], res[ %s ]",
                        get_class($this),
                        $group,
                        Connexions::varExport($res));
        // */

        return $res;
    }

    /** @brief  Generate an alias for the model name.
     *
     *  @return An alias string.
     */
    protected function _getModelAlias()
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

        return $as;
    }

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

    /** @brief  Given a table and field, return the proper expressioni of the
     *          field.
     *  @param  table   The table in question.
     *  @param  field   The field in question.
     *  @param  as      If the target table has an alias, include it here.
     *
     *  @return The field expression.
     */
    protected function _fieldExpression($table, $field, $as = null)
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
        $as = (! empty($as)
                    ? $as .= '.'
                    : '');

        $expr = $as . $field;
        if ($table === 'item')
        {
            switch ($field)
            {
            case 'ratingAvg':
                $expr  = "(CASE WHEN {$as}ratingCount > 0 "
                       .       "THEN {$as}ratingSum / {$as}ratingCount "
                       .       "ELSE 0 END) AS ratingAvg";
                break;
            }
        }
        else if ($table === 'userTagItem')
        {
            switch ($field)
            {
            case 'userCount':
                $expr  = "COUNT(DISTINCT {$as}userId) AS userCount";
                break;

            case 'tagCount':
                $expr  = "COUNT(DISTINCT {$as}tagId) AS tagCount";
                break;

            case 'itemCount':
                $expr  = "COUNT(DISTINCT {$as}itemId) AS itemCount";
                break;

            case 'userItemCount':
                $expr  = "COUNT(DISTINCT {$as}userId,{$as}itemId) "
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
     *  @param  primeAs     The alias of the table in the primary select;
     *  @param  params      An array retrieval criteria.
     *                          excludeStats    If true, do NOT include
     *                                          statistics [ false ];
     *
     *  :NOTE:
     *
     *  @return The Zend_Db_Select instance.
     */
    protected function _includeSecondarySelect(Zend_Db_Select  $select,
                                                               $primeAs,
                                               array           $params)
    {
        $as        = 'uti';

        $orderBy   = (isset($params['order'])
                        ? (is_array($params['order'])
                            ? $params['order']
                            : array($params['order']))
                        : array());
        $groupBy   = $this->_keyNames;

        $db        = $select->getAdapter();
        $secSelect = $db->select();
        $secSelect->from(array($as => 'userTagItem'),
                         array("{$as}.*"))
                  ->group( $groupBy );

        if ( (! isset($params['excludeStats'])) ||
             ($params['excludeStats'] !== true) )
        {
            $this->_includeStatistics($select, $secSelect, $as, $params);
        }

        /*
        Connexions::log("Model_Mapper_Base[%s]::fetchRelated(): "
                        . "group by [ %s ]",
                        get_class($this),
                        Connexions::varExport(
                            $secSelect->getPart(Zend_Db_Select::GROUP)) );
        // */

        $joinCond = array();
        foreach ($secSelect->getPart(Zend_Db_Select::GROUP) as $idex => $name)
        {
            array_push($joinCond, "{$primeAs}.{$name}={$as}.{$name}");
        }

        // Join the select and sub-select
        $select->join(array($as => $secSelect),
                      implode(' AND ', $joinCond),
                      null);

        /***************************************************************
         * include any limiters in the sub-select
         *
         */

        // Bookmarks
        if ( isset($params['bookmarks']) && (! empty($params['bookmarks'])) )
        {
            $bookmarks =& $params['bookmarks'];

            if ($bookmarks instanceof Model_Set_Bookmark)
            {
                if (count($bookmarks) > 0)
                {
                    $secSelect->where('(userId,itemId) IN ?',
                                      $bookmarks->getIds());
                }
            }
            else if (is_array($bookmarks))
            {
                if (count($bookmarks) > 0)
                {
                    $secSelect->where('(userId,itemId) IN ?', $bookmarks);
                }
            }
            else if ($bookmarks instanceof Model_Bookmark)
            {
                $secSelect->where('(userId,itemId)=?',
                                   array($bookmarks->userId,
                                         $bookmarks->itemId));
            }
            else
            {
                $secSelect->where('(userId,itemId)=?', $bookmarks);
            }
        }

        // Users
        if ( isset($params['users']) && (! empty($params['users'])) )
        {
            $users =& $params['users'];

            if ($users instanceof Model_Set_User)
            {
                if (count($users) > 0)
                {
                    $secSelect->where('userId IN ?',
                                      $users->getIds());
                }
            }
            else if (is_array($users))
            {
                if (count($users) > 0)
                {
                    $secSelect->where('userId IN ?', $users);
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

            // Default 'exactUsers' is false
            if ( (isset($params['exactUsers'])) &&
                 ($params['exactUsers'] === true) )
            {
                $nUsers = count($users);
                if ($nUsers > 1)
                {
                    /*
                    Connexions::log("Model_Mapper_Base::fetchRelated(): "
                                    . "exactly %d users",
                                    $nUsers);
                    // */

                    $secSelect->having('userCount='. $nUsers);
                }
            }
        }

        // Items
        if ( isset($params['items']) && (! empty($params['items'])) )
        {
            $items =& $params['items'];

            if ($items instanceof Model_Set_Item)
            {
                if (count($items) > 0)
                {
                    $secSelect->where("{$as}.itemId IN ?",
                                      $items->getIds());
                }
            }
            else if (is_array($items))
            {
                if (count($items) > 0)
                {
                    $secSelect->where("{$as}.itemId IN ?", $items);
                }
            }
            else if ($items instanceof Model_Item)
            {
                $secSelect->where("{$as}.itemId=?", $items->itemId);
            }
            else
            {
                $secSelect->where("{$as}.itemId=?", $items);
            }

            /* Doesn't really make sense to restrict based upon itemCount
             * since in most contexts, itemCount will be 1.
             *
            if ( (! isset($params['exactItems'])) ||
                 ($params['exactItems'] !== false) )
            {
                $nItems = count($items);
                if ($nItems > 1)
                {
                    $secSelect->having('itemCount='. $nItems);
                }
            }
            */
        }

        // Tags
        if ( isset($params['tags']) && (! empty($params['tags'])) )
        {
            $tags =& $params['tags'];

            if ($tags instanceof Model_Set_Tag)
            {
                if (count($tags) > 0)
                {
                    $secSelect->where('tagId IN ?',
                                      $tags->getIds());
                }
            }
            else if (is_array($tags))
            {
                if (isset($tags[0]) && is_int($tags[0]))
                {
                    $secSelect->where('tagId IN ?', $tags);
                }
                else if (count($tags) > 0)
                {
                    // :NOTE: The primary table MUST have a 'tag' field
                    $select->where('tag IN ?', $tags);
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

            // Default 'exactTags' is true
            if ( (! isset($params['exactTags'])) ||
                 ($params['exactTags'] !== false) )
            {
                $nTags = count($tags);
                if ($nTags > 1)
                {
                    /*
                    Connexions::log("Model_Mapper_Base::fetchRelated(): "
                                    . "exactly %d tags",
                                    $nTags);
                    // */

                    $secSelect->having('tagCount='. $nTags);
                }
            }
        }

        return $secSelect;
    }

    /** @brief  Include statistics-related informatioin in the
     *          select/sub-select
     *  @param  select      The primary   Zend_Db_Select instance;
     *  @param  secSelect   The secondary Zend_Db_Select instance;
     *  @param  secAs       The alias used for 'secSelect';
     *  @param  params      An array retrieval criteria;
     *
     *  :NOTE:
     *
     *  @return $this for a fluent interface.
     */
    protected function _includeStatistics(Zend_Db_Select    $select,
                                          Zend_Db_Select    $secSelect,
                                                            $secAs,
                                          array             $params)
    {
        $accessor  = $this->getAccessor();
        $mainTable = $accessor->info(Zend_Db_Table_Abstract::NAME);

        // Include the statistics in the column list of the primary select
        $mainStatCols = array("{$secAs}.userItemCount",
                              "{$secAs}.itemCount",
                              "{$secAs}.tagCount");
        if ($mainTable === 'item')
        {
            /* Name the computed 'userCount' as 'statUserCount' and
             * include the rating average
             */
            array_push($mainStatCols, "{$secAs}.userCount as statUserCount");
            array_push($mainStatCols, $this->_fieldExpression($mainTable,
                                                              'ratingAvg'));
        }
        else
        {
            // Include 'userCount' directly (no alias)
            array_push($mainStatCols, "{$secAs}.userCount");
        }


        $select->columns( $mainStatCols );

        // Generate the statistics in the secondary select
        $secSelect->columns(array(
                                $this->_fieldExpression('userTagItem',
                                                        'userCount',
                                                        $secAs),
                                $this->_fieldExpression('userTagItem',
                                                        'tagCount',
                                                        $secAs),
                                $this->_fieldExpression('userTagItem',
                                                        'itemCount',
                                                        $secAs),
                                $this->_fieldExpression('userTagItem',
                                                        'userItemCount',
                                                        $secAs),
                            ));
    }
}
