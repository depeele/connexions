<?php
/** @file
 *
 *  This mapper provides bi-directional access between the Domain Model and the
 *  underlying persistent store (in this case, a Zend_Db_Table).
 */
class Model_Mapper_Item extends Model_Mapper_Base
{
    protected   $_keyNames  = array('itemId');

    // If not provided, the following will be generated from our class name:
    //      <Prefix>_Mapper_<Name>                      == Model_Mapper_Item
    //          _modelName  => <Prefix>_<Name>          == Model_Item
    //          _accessor   => <Prefix>_DbTable_<Name>  == Model_DbTable_Item
    //
    //protected   $_modelName = 'Model_Item';
    //protected   $_accessor  = 'Model_DbTable_Item';

    /** @brief  Given identification value(s) that will be used for retrieval,
     *          normalize them to an array of attribute/value(s) pairs.
     *  @param  id      Identification value(s) (string, integer, array).
     *                  MAY be an associative array that specifically
     *                  identifies attribute/value pairs.
     *
     *  Note: This a support method for Services and
     *        Connexions_Model_Mapper::normalizeIds()
     *
     *  @return An array containing attribute/value(s) pairs suitable for
     *          retrieval.
     */
    public function normalizeId($id)
    {
        if (is_int($id) || is_numeric($id))
        {
            $id = array('itemId' => $id);
        }
        else if (is_string($id))
        {
            /* Normalize to a 'urlHash' but if 'url' was provided, keep it in 
             * case the item is not found and we need to create it.
             */
            $newId = array();
            if (! Connexions::isMd5($id))
            {
                $newId['url'] = $id;
                $id           = Connexions::md5Url($id);
            }

            $newId['urlHash'] = $id;
            $id               = $newId;
        }

        return $id;
    }

    /** @brief  Convert the incoming model into an array containing only 
     *          data that should be directly persisted.  This method may also
     *          be used to update dynamic values
     *          (e.g. update date/time, last visit date/time).
     *  @param  model   The Domain Model to reduce to an array.
     *
     *  @return A filtered associative array containing data that should 
     *          be directly persisted.
     */
    public function reduceModel(Connexions_Model $model)
    {
        /* Save our statistics fields -- they will be removed by
         * Model_Mapper_Base
         */
        $userCount   = $model->userCount;
        $ratingCount = $model->ratingCount;
        $ratingSum   = $model->ratingSum;

        $data = parent::reduceModel($model);

        // Replace 'userCount' and 'ratingCount'
        $data['userCount']   = $userCount;
        $data['ratingCount'] = $ratingCount;
        $data['ratingSum']   = $ratingSum;

        return $data;
    }

    /** @brief  Retrieve a set of item-related users
     *  @param  item    The Model_Item instance.
     *
     *  @return A Model_User_Set
     */
    public function getUsers(Model_Item $item)
    {
        throw new Exception('Not yet implemented');
    }

    /** @brief  Retrieve a set of item-related tags
     *  @param  item    The Model_Item instance.
     *
     *  @return A Model_Tag_Set
     */
    public function getTags(Model_Item $item)
    {
        throw new Exception('Not yet implemented');
    }

    /** @brief  Retrieve a set of item-related bookmarks
     *  @param  item    The Model_Item instance.
     *
     *  @return A Model_Bookmark_Set
     */
    public function getBookmarks(Model_Item $item)
    {
        throw new Exception('Not yet implemented');
    }

    /** @brief  Given an Item Domain Model (or Item identifier), update 
     *          external-table statistics related to this item:
     *              item - userCount, ratingCount, ratingSum
     *  @param  id      A Model_Item instance or itemId.
     *
     *  @return $this for a fluent interface
     */
    public function updateStatistics($id)
    {
        if ($id instanceof Model_Item)
        {
            $item = $id;
        }
        else
        {
            $item = $this->find( array('itemId' => $id));
        }

        /* Update item-related statistics:
         *    SELECT
         *      COUNT(DISTINCT userId)      AS userCount,
         *      SUM(CASE WHEN rating > 0
         *               THEN 1
         *               ELSE 0 END)        AS ratingCount,
         *      SUM(CASE rating WHEN null
         *               THEN 0
         *               ELSE rating END)   AS ratingSum
         *        FROM  userItem
         *        WHERE itemId=?;
         */
        $table  = $this->getAccessor('Model_DbTable_UserItem');
        $select = $table->select();
        $select->from( $table->info(Zend_Db_Table_Abstract::NAME),
                        array('COUNT(DISTINCT userId)  AS userCount',
                              'SUM(CASE WHEN rating > 0 THEN 1 ELSE 0 END) '
                                . 'AS ratingCount',
                              'SUM(CASE rating WHEN null '
                                .   'THEN 0 ELSE rating END) '
                                . 'AS ratingSum') )
               ->where( 'itemId=?', $item->itemId );

        /*
        Connexions::log("Model_Mapper_Item::_updateStatistics( %d ): "
                        . "sql[ %s ]",
                        $item->itemId,
                        $select->assemble());
        // */

        $row = $select->query()->fetchObject();

        /*
        Connexions::log("Model_Mapper_Item::_updateStatistics( %d ): "
                        . "sql[ %s ]",
                        $item->itemId,
                        $select->assemble());
        Connexions::log("Model_Mapper_Item::_updateStatistics( %d ): "
                        . "row[ %s ]",
                        $item->itemId,
                        Connexions::varExport($row));
        Connexions::log("Model_Mapper_Item::_updateStatistics( %d ): "
                        . "current[ %s ]",
                        $item->itemId,
                        $item->debugDump());
        // */


        $item->userCount   = (int)$row->userCount;
        $item->ratingCount = (int)$row->ratingCount;
        $item->ratingSum   = (int)$row->ratingSum;

        /*
        Connexions::log("Model_Mapper_Item::_updateStatistics( %d ): "
                        . "Save Item[ %s ]",
                        $item->itemId,
                        $item->debugDump());
        // */

        $item = $item->save();

        return $this;
    }
}
