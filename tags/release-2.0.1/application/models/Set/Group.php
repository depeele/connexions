<?php
/** @file
 *
 *  A set of Group Domain Models.
 *
 */

class Model_Set_Group extends Connexions_Model_Set
{
    protected   $_modelName = 'Model_Group';
    //protected   $_mapper    = 'Model_Mapper_Group';

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
        $names = array();
        foreach ($this->_members as $group)
        {
            if (is_object($group))
                $name = $group->name;
            else
                $name = $group['name'];

            array_push($names, $name);
        }

        return implode(',', $names);
    }
}

