<?php
class Model_Mapper_Item extends Model_Mapper_Base
{
    protected   $_keyName   = 'itemId';

    // If not provided, the following will be generated from our class name:
    //      <Prefix>_Mapper_<Name>                      == Model_Mapper_Item
    //          _modelName  => <Prefix>_<Name>          == Model_Item
    //          _accessor   => <Prefix>_DbTable_<Name>  == Model_DbTable_Item
    //
    //protected   $_modelName = 'Model_Item';
    //protected   $_accessor  = 'Model_DbTable_Item';

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
        unset($data['itemCount']);
        unset($data['tagCount']);

        return $data;
    }

    /** @brief  Retrieve a single item.
     *  @param  id      The item identifier (itemId or urlHash)
     *
     *  @return A Model_Item instance.
     */
    public function find($id)
    {
        if (is_array($id))
        {
            $where = $id;
        }
        else if (is_string($id) && (! is_numeric($id)) )
        {
            // Lookup by item urlHash
            $where = array('urlHash=?' => $id);
        }
        else
        {
            $where = array('itemId=?' => $id);
        }

        /*
        Connexions::log("Model_Mapper_Item: where[ %s ]",
                        Connexions::varExport($where));
        // */

        return parent::find( $where );
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
}
