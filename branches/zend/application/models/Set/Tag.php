<?php
/** @file
 *
 *  A set of Tag Domain Models.
 *
 */

class Model_Set_Tag extends Connexions_Model_Set
{
    protected   $_modelName = 'Model_Tag';
    //protected   $_mapper    = 'Model_Mapper_Tag';

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
        $tags = array();
        foreach ($this->_members as $tag)
        {
            if (is_object($tag))
                $name = $tag->name;
            else
                $name = $tag['tag'];

            array_push($tags, $name);
        }

        return implode(',', $tags);
    }
}
