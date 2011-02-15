<?php
/** @file
 *
 *  Model for the UserAuth table.
 */

class Model_UserAuth extends Model_Base
{
    const   AUTH_OPENID     = 'openid';         // Connexions_Auth_OpenId
    const   AUTH_PASSWORD   = 'password';       // Connexions_Auth_UserPassword
    const   AUTH_PKI        = 'pki';            // Connexions_Auth_ApacheSsl

    const   AUTH_DEFAULT    = self::AUTH_PASSWORD;

    /* inferred via classname
    protected   $_mapper    = 'Model_Mapper_UserAuth'; */

    // The data for this Model
    protected   $_data      = array(
            'userAuthId'    => null,
            'userId'        => null,
            'authType'      => self::AUTH_DEFAULT,
            'credential'    => '',
            'name'          => '',
    );

    // Properties not directly backed by our Mapper/DAO
    protected   $_user      = null;

    /*************************************************************************
     * Connexions_Model abstract method implementations
     *
     */

    /** @brief  Retrieve the unique identifier for this instance.  This MAY 
     *          return an array of identifiers as key/value pairs.
     *
     *  @return The unique identifier.
     */
    public function getId()
    {
        return ( $this->userAuthId );
        //return ( array( $this->userId, $this->authType, $this->credential ) );
    }

    /*************************************************************************
     * Connexions_Model - abstract method implementations
     *
     */

    /** @brief  Get a value of the given field.
     *  @param  name    The field name.
     *
     *  @return The field value (null if invalid).
     */
    public function __get($name)
    {
        switch ($name)
        {
        case 'user': $val = $this->_user();         break;
        default:     $val = parent::__get($name);   break;
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
        /*
        Connexions::log("Model_UserAuth::__set(%s, %s) from '%s'",
                        $name,
                        Connexions::varExport($value),
                        Connexions::varExport($this->__get($name)) );
        // */

        switch ($name)
        {
        case 'user':
            if (! $value instanceof Model_User)
            {
                throw new Exception("user MUST be a Model_User instance");
            }
            $this->_user = $value;
            $this->userId = $this->_user->getId();
            return;

            break;

        case 'authType':
            if (! self::validateAuthType($value))
            {
                throw new Exception("Model_UserAuth::__set({$name}, {$value}): "
                                    . "Invalid authentication type");
            }
            break;

        case 'credential':
            $value = $this->_normalizeCredential($value);
            break;
        }

        return parent::__set($name, $value);
    }

    /** @brief  Invalidate the data contained in this model instance.
     *
     *  @return $this for a fluent interface.
     */
    public function invalidate()
    {
        $this->invalidateCache();

        return parent::invalidate();
    }

    /** @brief  Invalidate our internal cache of sub-instances.
     *
     *  @return $this for a fluent interface
     */
    public function invalidateCache()
    {
        $this->_user = null;

        return $this;
    }

    /** @brief  Compare a provided credential agains this userAuth record.
     *  @param  credential  The credential to compare.
     *
     *  @return true | false
     */
    public function compare($credential)
    {
        $norm = $this->_normalizeCredential($credential);

        /*
        Connexions::log("Model_UserAuth::compare(%s): "
                        . "normalize[ %s ] to [ %s ]",
                        $credential, $norm, $this->credential);
        // */

        return ($norm === $this->credential);
    }

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        return sprintf("%d:%s:%s",
                       $this->userId, $this->authType, $this->credential);
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Given a raw credential, perform any "normalization"
     *          (e.g. passwords MUST be an MD5 of the 'UserName:password').
     *  @param  credential  The credential value to normalize (based upon the 
     *                      'authType' of this instance).
     *
     *  @return The normalized credential.
     */
    protected function _normalizeCredential($credential)
    {
        switch ($this->authType)
        {
        case self::AUTH_PASSWORD:
            // If the incoming credential is NOT an MD5 hash, convert it now
            if ( ! Connexions::isMd5($credential))
            {
                // Construct the MD5 hash representing this password
                $seed  = $this->user->name .':'. $credential;
                $mdVal = md5( $seed );

                /*
                Connexions::log("Model_UserAuth::_normalizeCredential(%s): "
                                . "password: seed[ %s ], credential[ %s ]",
                                $credential, $seed, $mdVal);
                // */

                $credential = $mdVal;
            }
            break;

        case self::AUTH_OPENID:
        case self::AUTH_PKI:
        default:
            // by default, do nothing
            break;
        }

        return $credential;
    }

    protected function _user()
    {
        if ($this->_user === null)
        {
            $this->_user = $this->getMapper()->getUser( $this );
        }

        return $this->_user;
    }

    /*************************************************************************
     * Static methods
     *
     */

    /** @brief  Given an authentication type string, check if it's valid.
     *  @param  type    The type string to check.
     *
     *  @return true (valid) or false (invalid)
     */
    public static function validateAuthType($type)
    {
        $validity = false;
        switch ($type)
        {
        case self::AUTH_OPENID:
        case self::AUTH_PASSWORD:
        case self::AUTH_PKI:
            $validity = true;
            break;
        }

        /*
        Connexions::log("Model_UserAuth::validateAuthType( %s ): %svalid",
                        $type, ($validity ? '' : 'NOT '));
        // */

        return $validity;
    }
}
