<?php
/** @file
 *
 *  A set of Activity Domain Models.
 *
 */

class Model_Set_Activity extends Connexions_Model_Set
{
    protected   $_modelName = 'Model_Activity';
    //protected   $_mapper    = 'Model_Mapper_Activity';

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
        $strs = array();
        foreach ($this->_members as $activity)
        {
            array_push($strs, (String)$activity);
        }

        return implode(',', $strs);
    }
}

