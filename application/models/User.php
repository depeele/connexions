<?php
/** @file
 *
 *  Model for the User table.
 *
 */

class Model_User extends Connexions_Model
{
    protected static    $table  = 'user';
    protected static    $keys   = array('userId'    => 'numeric',
                                        'name'      => 'string');
    protected static    $model  = array('userId'        => 'integer',
                                        'name'          => 'string',
                                        'password'      => 'string',

                                        'fullName'      => 'string',
                                        'email'         => 'string',
                                        'apiKey'        => 'string',
                                        'pictureUrl'    => 'string',
                                        'profile'       => 'string',
                                        'networkShared' => 'boolean',
                                        'lastVisit'     => 'date',
                                        'lastVisitFor'  => 'date',
                                        'totalTags'     => 'integer',
                                        'totalItems'    => 'integer'
    );
    public static function getTable()  { return self::$table; }
    public static function getKeys()   { return self::$keys; }
    public static function getModel()  { return self::$model; }

    protected       $_isAuthenticated   = false;

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        if ($this->isValid())
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

            $this->_error = 'Invalid password';
        }

        return false;
    }
}
