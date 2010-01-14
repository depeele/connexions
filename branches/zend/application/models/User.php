<?php
/** @file
 *
 *  Model for the User table.
 *
 */

class Model_User extends Connexions_Model_Cached
{
    /*************************************************************************
     * Connexions_Model - static, identity members
     *
     */
    public static   $table  = 'user';
                              // order 'keys' by most used
    public static   $keys   = array('userId', 'name');
    public static   $model  = array('userId'        => 'auto',
                                    'name'          => 'string',
                                    'password'      => 'string',

                                    'fullName'      => 'string',
                                    'email'         => 'string',
                                    'apiKey'        => 'string',
                                    'pictureUrl'    => 'string',
                                    'profile'       => 'string',
                                    'networkShared' => 'boolean',
                                    'lastVisit'     => 'datetime',
                                    'lastVisitFor'  => 'datetime',
                                    'totalTags'     => 'integer',
                                    'totalItems'    => 'integer'
    );
    /*************************************************************************/

    protected       $_isAuthenticated   = false;

    /** @brief  Set a value in this record and mark it dirty.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  Override to properly encode 'password' when set.
     *
     *  @return true | false
     */
    public function __set($name, $value)
    {
        if ($name === 'password')
            $value = md5($value);

        return parent::__set($name, $value);
    }

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        if ($this->isValid() && (! @empty($this->_record['name'])))
            return $this->_record['name'];

        return parent::__toString();
    }

    /** @brief  Is this user authenticated?
     *
     *  @return true | false
     */
    public function isAuthenticated()
    {
        return $this->_isAuthenticated;
    }

    /** @brief  Set the authentication state.
     *  @param  isAuthenticated     true | false
     *
     *  @return Model_User to provide a fluent interface.
     */
    public function setAuthenticated($isAuthenticated   = true)
    {
        $this->_isAuthenticated = $isAuthenticated;
        
        return $this;
    }

    /** @brief  Validate the user's password.
     *  @param  pass        The password to validate.
     *
     *  @return true | false
     */
    public function authenticate($pass)
    {
        $this->_isAuthenticated = false;

        if ($this->isValid())
        {
            $checkPass = md5($pass);
            if ($this->_record['password'] == $checkPass)
            {
                $this->_isAuthenticated = true;
                return true;
            }

            $this->_error = 'Invalid password.';
        }

        return false;
    }

    /*************************************************************************
     * Connexions_Model - abstract static method implementations
     *
     */

    /** @brief  Retrieve all records and return an array of instances.
     *  @param  id      The record identifier.
     *
     *  @return A new instance (false if no matching user).
     */
    public static function find($id)
    {
        //Connexions::log("Model::User::find: id[ ". print_r($id, true) ." ]");
        return parent::find(__CLASS__, $id);
    }

    /*************************************************************************
     * Connexions_Model_Cached - abstract static method implementations
     *
     */

    /** @brief  Given a record identifier, generate an unique instance
     *          identifier.
     *  @param  id      The record identifier.
     *
     *  @return A unique instance identifier string (null if invalid).
     */
    protected static function _instanceId($id)
    {
        $instanceId = __CLASS__ .'_';
        if (@is_array($id))
        {
            if (! @empty($id['userId']))
                $instanceId .= $id['userId'];
            else if (! @empty($id['name']))
                $instanceId .= $id['name'];
            else
            {
                // INVALID
                $instanceId = null;
            }
        }
        else if (@is_string($id))
            $instanceId .= $id;
        else
        {
            // INVALID
            $instanceId = null;
        }

        /*
        Connexions::log("Model_User::_instanceId: "
                            . "id[ ". print_r($id, true) ." ], "
                            . "instanceId[ {$instanceId} ]");
        // */

        return $instanceId;
    }
}
