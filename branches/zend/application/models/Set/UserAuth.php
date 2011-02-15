<?php
/** @file
 *
 *  A set of UserAuth Domain Models.
 *
 */

class Model_Set_UserAuth extends Connexions_Model_Set
{
    protected   $_modelName = 'Model_UserAuth';
    //protected   $_mapper    = 'Model_Mapper_UserAuth';

    // Properties not directly backed by our Mapper/DAO
    protected   $_user      = null;

    /** @brief  Get a value of the given field.
     *  @param  name    The field name.
     *
     *  @return The field value (null if invalid).
     */
    public function __get($name)
    {
        $val    = null;
        switch ($name)
        {
        case 'user': $val = $this->_user();         break;
        }

        return $val;
    }

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
        case 'user':
            if (! $value instanceof Model_User)
            {
                throw new Exception("user MUST be a Model_User instance");
            }
            $this->_user = $value;
            return;

            break;
        }

        return $this;
    }

    /** @brief  Compare a provided credential against all userAuth records in
     *          this set.
     *  @param  credential  The credential to compare.
     *
     *  @return true | false
     */
    public function compare($credential)
    {
        // /*
        Connexions::log("Model_Set_UserAuth::compare(%s): "
                        . "%d members...",
                        $credential, count($this));
        // */

        foreach($this as $auth)
        {
            $auth->user = $this->_user;

            if ($auth->compare($credential) === true)
            {
                return true;
            }
        }

        return false;

        return ($norm === $this->credential);
    }
}
