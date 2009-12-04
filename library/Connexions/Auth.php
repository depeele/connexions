<?php
/** @file
 *
 *  An authentication adapter for Connexions.
 *
 */

/** @brief  A Zend_Auth authentication adapter.
 *
 *  Unlink other Zend_Auth_Adapters, this class implements the
 *  Zend_Auth_Adapter_Interface AND extends Zend_Auth_Result, returning itself
 *  from any call to authenticate().  This allows us to extend Zend_Auth_Result
 *  to add a getIdentityFull() method to retrieve the full identity information
 *  about the authenticated user.
 */
class Connexions_Auth extends    Zend_Auth_Result
                      implements Zend_Auth_Adapter_Interface
{
    protected   $_userCredential    = null;

    // The Model_User instance
    protected   $_user              = null;

    protected   $_isAuthenticated   = false;
    protected   $_fullIdentity      = null;

    /** @brief  Constructor.
     *  @param  user        The Model_User instance.
     *  @param  credential  The user's credential (e.g. password).
     */
    public function __construct(Model_User $user, $credential = null)
    {
        $this->_user = $user;

        if (! $user->isValid())
        {
            // Zend_Auth_Result
            $this->_code     = self::FAILURE_IDENTITY_NOT_FOUND;
            $this->_messages = array( $user->getError() );
        }
        else
        {
            $this->setCredential($credential);
        }
    }

    /** @brief  Set the credentials to be used to authenticate.
     *  @param  credential  The user's credential (e.g. password).
     *
     *  @return Connexions_Auth to provide a fluent interface
     */
    public function setCredential($credential)
    {
        $this->_credential = $credential;

        // Zend_Auth_Result
        $this->_code     = self::FAILURE_UNCATEGORIZED;
        $this->_identity = $this->_user->userId;
        $this->_messages = array();

        return $this;
    }

    /** @brief  Return the full user identity (if authenticated).
     *
     *  @return The full user identity
     */
    public function getIdentityFull()
    {
        return $this->_fullIdentity;
    }

    /** @brief  Perform an authentication attempt.
     *
     *  @throws Zend_Auth_Adapter_Exception if authentication cannot be
     *                                      performed.
     *  @return Zend_Auth_Result
     */
    public function authenticate()
    {
        /*
        Zend_Auth_Result::FAILURE
        Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND
        Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS
        Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID
        Zend_Auth_Result::FAILURE_UNCATEGORIZED
        Zend_Auth_Result::SUCCESS
        */
        if ($this->_user->authenticate($this->_credential))
        {
            $this->_code     = self::SUCCESS;
            $this->_messages = array();
        }
        else
        {
            $this->_code     = self::FAILURE_CREDENTIAL_INVALID;
            $this->_messages = array("Invalid password");
        }

        return $this;
    }
}
