<?php
class Model_Mapper_Tag extends Model_Mapper_Base
{
    protected   $_keyName   = 'tagId';

    // If not provided, the following will be generated from our class name:
    //      <Prefix>_Mapper_<Name>                      == Model_Mapper_Tag
    //          _modelName  => <Prefix>_<Name>          == Model_Tag
    //          _accessor   => <Prefix>_DbTable_<Name>  == Model_DbTable_Tag
    //
    //protected   $_modelName = 'Model_Tag';
    //protected   $_accessor  = 'Model_DbTable_Tag';

    /** @brief  Retrieve a single tag.
     *  @param  id      The tag identifier (tagId or tag name)
     *
     *  @return A Model_Tag instance.
     */
    public function find($id)
    {
        if (is_array($id))
        {
            $where = $id;
        }
        else if (is_string($id) && (! is_numeric($id)) )
        {
            // Lookup by tag name
            $where = array('tag=?' => $id);
        }
        else
        {
            $where = array('tagId=?' => $id);
        }

        return parent::find( $where );
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
}
