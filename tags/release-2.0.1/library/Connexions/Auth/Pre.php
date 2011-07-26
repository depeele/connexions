<?php
/** @file
 *
 *  A pre-authentication adapter for Connexions.
 *
 *  This is used on Bootstrap when Zend_Auth recognized a user that was
 *  previously authenticated.
 */

class Connexions_Auth_Pre extends Connexions_Auth_Abstract
{
    protected   $_authType  = 'pre';    // Model_UserAuth::AUTH_PASSWORD

    /** @brief  Construct a new instatnce.
     *
     */
    public function __construct(Model_User  $user)
    {
        parent::__construct(self::SUCCESS, $user->userId);

        $this->_user = $user;
    }

    /** @brief  Perform an authentication attempt.
     *
     *  @throws Zend_Auth_Adapter_Exception if authentication cannot be
     *                                      performed.
     *  @return Zend_Auth_Result
     *              Valid codes:
     *                  Zend_Auth_Result::FAILURE
     *                  Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND
     *                  Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS
     *                  Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID
     *                  Zend_Auth_Result::FAILURE_UNCATEGORIZED
     *                  Zend_Auth_Result::SUCCESS
     */
    public function authenticate()
    {
        return $this;
    }
}

