<?php
/** @file
 *
 *  An extension of Connexions_Model_Mapper_DbTable that adds
 *      fetchRelated()
 */
abstract class Model_Mapper_Base extends Connexions_Model_Mapper_DbTable
{
    /** @brief  Filter out any data that isn't directly persisted, update any 
     *          dynamic values.
     *  @param  data    An associative array of data that is about to be 
     *                  persisted.
     *
     *  @return A filtered associative array containing data that should 
     *          be directly persisted.
     */
    public function filter(array $data)
    {
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
     *  @param  userIds     The array of userIds to use in the relation;
     *  @param  itemIds     The array of itemIds to use in the relation;
     *  @param  tagIds      The array of tagIds  to use in the relation;
     *  @param  exactTags   If 'tagIds' is provided,  should we require a match
     *                      on ALL tags? [ true ];
     *
     *  @return A Connexions_Model_Set instance that provides access to all
     *          matching Domain Model instances.
     */
    public function fetchRelated( $userIds   = null,
                                  $itemIds   = null,
                                  $tagIds    = null,
                                  $exactTags = true)
    {
        $modelName = $this->getModelName();

        /* Convert the model class name to an abbreviation composed of all
         * upper-case characters following the first '_', then converted to
         * lower-case (e.g. Model_UserTagItem == 'uti').
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

        if (! empty($userIds))
        {
            if (is_array($userIds))
                $subSelect->where('userId IN (?)', $userIds);
            else
                $subSelect->where('userId=?', $userIds);
        }
        if (! empty($itemIds))
        {
            if (is_array($itemIds))
                $subSelect->where('itemId IN (?)', $itemIds);
            else
                $subSelect->where('itemId=?', $itemIds);
        }
        if (! empty($tagIds))
        {
            if (is_array($tagIds))
                $subSelect->where('tagId IN (?)', $tagIds);
            else
                $subSelect->where('tagId=?', $tagIds);

            if ($exactTags === true)
            {
                $nTagIds = count($tagIds);
                $subSelect->having('tagCount='. $nTagIds);
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
         
        return $this->fetch($select);

        $accessorModels = $db->query($select)->fetchAll();

        /*
        Connexions::log("Model_Mapper_Base::fetchRelated(): "
                        . "sql[ %s ], retrieved %d items",
                        $select->assemble(), count($accessorModels));
        // */

        $domainModels   = array();

        $idex = 0;
        foreach ($accessorModels as $accessorModel)
        {
            $data = (is_object($accessorModel)
                        ? $accessorModel->toArray()
                        : $accessorModel);

            $domainModel = new $modelName(
                                array('mapper'   => $this,
                                      'isBacked' => true,
                                      'isValid'  => true,
                                      'data'     => $data));
            array_push($domainModels, $domainModel);
        }

        /*
        Connexions::log("Model_Mapper_Base::fetchRelated(): "
                        . "return %d items",
                        count($accessorModels));
        // */

        return $domainModels;
    }
}
