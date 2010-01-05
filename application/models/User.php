<?php
/** @file
 *
 *  Model for the User table.
 *
 */

class Model_User extends Connexions_Model
{
    protected static    $table  = 'user';
                                  // order 'keys' by most used
    protected static    $keys   = array('userId', 'name');
    protected static    $model  = array('userId'        => 'auto',
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
    public static function getTable()  { return self::$table; }
    public static function getKeys()   { return self::$keys; }
    public static function getModel()  { return self::$model; }

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

    /** @brief  Retrieve all records and return an array of instances.
     *  @param  id          The user identifier
     *                      (integrer userId or string name).
     *
     *  @return A new instance (false if no matching user).
     */
    public static function find($id)
    {
        return parent::find(__CLASS__, $id);
    }

    /** @brief  Retrieve all records and return an array of instances.
     *  @param  where   A string or associative array of restrictions.
     *
     *  @return An array of instances.
     */
    public static function fetchAll($where = null)
    {
        return parent::fetchAll(__CLASS__, $where);
    }

    /*************************************************************************
     * Protected helpers
     *
     */
}
