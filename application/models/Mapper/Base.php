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
        $accessor = $this->getAccessor();
        $db       = $accessor->getAdapter();

        /********************************************************************
         * Generate the primary select.
         *
         */
        $select   = $db->select();
        $select->from( array( $as =>
                                $accessor->info(Zend_Db_Table_Abstract::NAME)),
                       array("{$as}.*"));

        $this->_includeSecondarySelect($select, $as, $params);


        if ( isset($params['where']))
            $select->where( $params['where'] );
         
        $order  = (isset($params['order'])  ? $params['order']  : null);
        $count  = (isset($params['count'])  ? $params['count']  : null);
        $offset = (isset($params['offset']) ? $params['offset'] : null);

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
        $groupBy   = $this->_keyName;

        $db        = $select->getAdapter();
        $secSelect = $db->select();
        $secSelect->from(array($as => 'userTagItem'),
                         array("{$as}.*"))
                  ->group( $groupBy );

        $this->_includeStatistics($select, $secSelect, $as, $params);

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
                    $secSelect->where("{$as}.itemId IN (?)",
                                      $items->idArray());
                }
            }
            else if (is_array($items))
            {
                if (count($items) > 0)
                {
                    $secSelect->where("{$as}.itemId IN (?)", $items);
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
        // Include the statistics in the column list of the primary select
        $select->columns(array("{$secAs}.userItemCount",
                               "{$secAs}.userCount",
                               "{$secAs}.itemCount",
                               "{$secAs}.tagCount"));

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
