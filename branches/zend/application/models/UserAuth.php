<?php
/** @file
 *
 *  Model for the UserAuth table.
 */

//class Model_User extends Connexions_Model_Cached
class Model_UserAuth extends Connexions_Model
{
    //protected   $_mapper    = 'Model_Mapper_UserAuth';

    // The data for this Model
    protected   $_data      = array(
            'userId'        => null,
            'authType'      => 'password',
            'credential'    => '',
    );

    // Properties not directly backed by our Mapper/DAO
    protected   $_user      = null;

    /*************************************************************************
     * Connexions_Model abstract method implementations
     *
     */
    public function getId()
    {
        return ( $this->isBacked()
                    ? array( $this->userId, $this->authType )
                    : null );
    }

    /*************************************************************************
     * Connexions_Model - abstract static method implementations
     *
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

        case 'authType':
            switch ($value)
            {
            case 'password':
            case 'openid':
            case 'pki':
                break;

            default:
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

    /** @brief  Retrieve all records and return an array of instances.
     *  @param  id      The record identifier.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     *  @return A new instance (false if no matching user).
     */
    public static function find($id, $db = null)
    {
        //Connexions::log("Model::User::find: id[ ". print_r($id, true) ." ]");
        return parent::find($id, $db, __CLASS__);
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

        Connexions::log("Model_UserAuth::compare(%s): "
                        . "normalize[ %s ] to [ %s ]",
                        $credential, $norm, $this->credential);

        return ($norm === $this->credential);
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
        case 'password':
            // If the incoming credential is NOT an MD5 hash, convert it now
            if ( ! Connexions::isMd5($credential))
            {
                // Construct the MD5 hash representing this password
                $seed  = $this->user->name .':'. $credential;
                $mdVal = md5( $seed );

                Connexions::log("Model_UserAuth::_normalizeCredential(%s): "
                                . "password: seed[ %s ], credential[ %s ]",
                                $credential, $seed, $mdVal);

                $credential = $mdVal;
            }
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
}
