<?php
/** @file
 *
 *  A set of Item Domain Models.
 *
 */

class Model_Set_Item extends Connexions_Model_Set
{
    protected   $_modelName = 'Model_Item';
    //protected   $_mapper    = 'Model_Mapper_Item';

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
        $hashes = array();
        foreach ($this->_members as $item)
        {
            if (is_object($item))
                $urlHash = $item->urlHash;
            else
                $urlHash = $item['urlHash'];

            array_push($hashes, $urlHash);
        }

        return implode(',', $hashes);
    }
}
