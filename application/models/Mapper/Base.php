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

        return $data;
    }

    /** @brief  Retrieve a set of Domain Model items via the userTagItem core
     *          table.
     *  @param  where   An array of field/value pairs to use in creating the
     *                  appropriate query.
     *  @param  users       The Model_Set_User instance or an array of userIds
     *                      to use in the relation;
     *  @param  items       The Model_Set_Item instance or an array of itemIds
     *                      to use in the relation;
     *  @param  tags        The Model_Set_Tag  instance or an array of tagIds
     *                      to use in the relation;
     *  @param  order       Optional ORDER clause (string, array)
     *  @param  count       Optional LIMIT count
     *  @param  offset      Optional LIMIT offset
     *  @param  exactTags   If 'tags' is provided,  should we require a match
     *                      on ALL tags? [ true ];
     *
     *  @return A Connexions_Model_Set instance that provides access to all
     *          matching Domain Model instances.
     */
    public function fetchRelated( $users     = null,
                                  $items     = null,
                                  $tags      = null,
                                  $order     = null,
                                  $count     = null,
                                  $offset    = null,
                                  $exactTags = true)
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

        $select   = $db->select();
        $select->from( array( $as =>
                                $accessor->info(Zend_Db_Table_Abstract::NAME) ),
                       array("{$as}.*",
                             'uti.userItemCount',
                             'uti.userCount',
                             'uti.itemCount',
                             'uti.tagCount'));

        $subSelect = $db->select();
        $subSelect->from('userTagItem',
                         array('*',
                               'userItemCount'  =>
                                                'COUNT(DISTINCT userId,itemId)',
                               'userCount'      =>
                                                'COUNT(DISTINCT userId)',
                               'itemCount'      =>
                                                'COUNT(DISTINCT itemId)',
                               'tagCount'       =>
                                                'COUNT(DISTINCT tagId)'))
                  ->group( $this->_keyName );

        if (! empty($users))
        {
            if ($users instanceof Model_Set_User)
                $subSelect->where('userId IN (?)', $users->idArray());
            else if (is_array($users))
                $subSelect->where('userId IN (?)', $users);
            else
                $subSelect->where('userId=?', $users);
        }
        if (! empty($items))
        {
            if ($items instanceof Model_Set_Item)
                $subSelect->where('itemId IN (?)', $items->idArray());
            else if (is_array($items))
                $subSelect->where('itemId IN (?)', $items);
            else
                $subSelect->where('itemId=?', $items);
        }
        if (! empty($tags))
        {
            if ($tags instanceof Model_Set_Tag)
                $subSelect->where('tagId IN (?)', $tags->idArray());
            else if (is_array($tags))
                $subSelect->where('tagId IN (?)', $tags);
            else
                $subSelect->where('tagId=?', $tags);

            if ($exactTags === true)
            {
                $nTags = count($tags);
                $subSelect->having('tagCount='. $nTags);
            }
        }


        $joinCond = array();
        foreach ($subKeys as $name)
        {
            array_push($joinCond, "{$as}.{$name}=uti.{$name}");
        }

        // Join the select and sub-select
        $select->join(array('uti' => $subSelect),
                      implode(' AND ', $joinCond),
                      null);
         
        return $this->fetch($select, $order, $count, $offset);
    }
}
