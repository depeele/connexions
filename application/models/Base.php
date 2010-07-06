<?php
/** @file
 *
 *  Base class for all Connexions Domain Models.
 *
 *  This is a simple extension of Connexions_Model that adds
 *      userItemCount, userCount, itemCount, and tagCount as
 *      non-persisted but settable fields.
 */

abstract class Model_Base extends Connexions_Model
{
    /** @brief  Set the value of the given field.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  @return $this for a fluent interface.
     */
    public function __set($name, $value)
    {
        switch ($name)
        {
        case 'userItemCount':
        case 'userCount':
        case 'itemCount':
        case 'tagCount':
        case 'ratingCount':
        case 'ratingSum':
            $value = (int)$value;
            break;

        case 'ratingAvg':
            $value = (float)$value;
            break;
        }

        if (! array_key_exists($name, $this->_data))
        {
            // Directly set this pseudo-field
            $this->_data[$name] = $value;
        }
        else
        {
            /* Invoke __set() to ensure the '_dirty' indicator is set if 
             * needed.
             */
            parent::__set($name, $value);
        }

        return $this;
    }
}
