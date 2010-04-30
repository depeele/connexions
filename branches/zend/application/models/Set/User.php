<?php
/** @file
 *
 *  A set of User Domain Models.
 *
 */

class Model_Set_User extends Connexions_Model_Set
{
    protected   $_modelName = 'Model_User';
    //protected   $_mapper    = 'Model_Mapper_User';

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
        foreach ($this->_members as $user)
        {
            if (is_object($user))
                $name = $user->name;
            else
                $name = $user['name'];

            array_push($names, $name);
        }

        return implode(',', $names);
    }
}
