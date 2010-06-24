<?php
/** @file
 *
 *  This mapper provides bi-directional access between the Domain Model and the
 *  underlying persistent store (in this case, a Zend_Db_Table).
 */
class Model_Mapper_Tag extends Model_Mapper_Base
{
    protected   $_keyNames  = array('tagId');

    // If not provided, the following will be generated from our class name:
    //      <Prefix>_Mapper_<Name>                      == Model_Mapper_Tag
    //          _modelName  => <Prefix>_<Name>          == Model_Tag
    //          _accessor   => <Prefix>_DbTable_<Name>  == Model_DbTable_Tag
    //
    //protected   $_modelName = 'Model_Tag';
    //protected   $_accessor  = 'Model_DbTable_Tag';

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
            $id = array('tagId' => $id);
        }
        else if (is_string($id))
        {
            $id = array('tag'   => $id);
        }

        return $id;
    }

    /** @brief  Retrieve a set of tag-related users
     *  @param  tag     The Model_Tag instance.
     *
     *  @return A Model_User_Set
     */
    public function getUsers(Model_Tag $tag)
    {
        throw new Exception('Not yet implemented');
    }

    /** @brief  Retrieve a set of tag-related items
     *  @param  tag     The Model_Tag instance.
     *
     *  @return A Model_Item_Set
     */
    public function getItems(Model_Tag $tag)
    {
        throw new Exception('Not yet implemented');
    }

    /** @brief  Retrieve a set of tag-related bookmarks
     *  @param  tag     The Model_Tag instance.
     *
     *  @return A Model_Bookmark_Set
     */
    public function getBookmarks(Model_Tag $tag)
    {
        throw new Exception('Not yet implemented');
    }

    /*********************************************************************
     * Protected methods
     *
     * Since a tag can be queried by either tagId or tag name, the identity
     * map for this Domain Model must be a bit more "intelligent"...
     */

    /** @brief  Save a new Model instance in our identity map.
     *  @param  id      The model instance identifier.
     *  $param  model   The model instance.
     *
     */
    protected function _setIdentity($id, $model)
    {
        /* Ignore 'id' -- it'll include either tagId, tag, or both.
         *
         * Add identity map entries for both tagId and tag
         */
        parent::_setIdentity($model->tagId, $model);
        parent::_setIdentity($model->tag,   $model);

        /*
        Connexions::log("Model_Mapper_Tag::_setIdentity(): "
                        .   "id[ %d ], tag[ %s ]",
                         $model->tagId, $model->tag);
        // */
    }

    /** @brief  Remove an identity map entry.
     *  @param  id      The model instance identifier.
     *  $param  model   The model instance currently mapped.
     */
    protected function _unsetIdentity($id, Connexions_Model $model)
    {
        /* Ignore 'id' -- it'll include JUST tagId.
         *
         * Remove the identity map entries for both tagId and tag
         */
        unset($this->_identityMap[ $model->tagId ]);
        unset($this->_identityMap[ $model->tag   ]);

        /*
        Connexions::log("Model_Mapper_Tag::_unsetIdentity(): "
                        .   "id[ %d ], tag[ %s ]",
                         $model->tagId, $model->tag);
        // */
    }
}
