<?php
/** @file
 *
 *  A set of Bookmark / UserItem Domain Models.
 *
 */

class Model_Set_Bookmark extends Connexions_Model_Set
{
    protected   $_modelName = 'Model_Bookmark';
    //protected   $_mapper    = 'Model_Mapper_Bookmark';

    /*************************************************************************
     * Conversions
     *
     */

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        $ids = array();
        foreach ($this->_members as $bookmark)
        {
            if (is_object($bookmark))
                $id = $bookmark->userId .':'. $bookmark->itemId;
            else
                $id = $bookmark['userId'] .':'. $bookmark['itemId'];

            array_push($ids, $id);
        }

        return implode(',', $ids);
    }
}
