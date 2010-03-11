<?php
/** @file
 *
 *  A username/password authentication adapter for Connexions.
 *
 */

class Connexions_Auth_UserPassword extends Connexions_Auth_Abstract
{
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
        // Reset our result state
        $this->_setResult();
        $$userName = null;

        if (@empty($_POST['username']))
        {
            $this->_setResult(self::FAILURE_IDENTITY_AMBIGUOUS,
                              $userName,
                              "Missing user name");
            return $this;
        }
        $userName = $_POST['username'];

        if (! isset($_POST['password']))
        {
            $this->_setResult(self::FAILURE_CREDENTIAL_INVALID,
                              $userName,
                              "Missing password");
            return $this;
        }
        $password = $_POST['password'];

        // Does 'userName' identify a valid user?
        $user = new Model_User( $userName );

        /*
        Connexions::log("Connexions_Auth_UserPassword::authenticate: "
                        . "username[ {$userName} ] "
                        . "Mapped to user:\n"
                        . $user->debugDump());
        // */

        if (! $user->isBacked())
        {
            // Invalid user
            $this->_setResult(self::FAILURE_IDENTITY_NOT_FOUND,
                              $userName,
                              "Unknown user name '{$userName}'");
            return $this;
        }

        if (! $user->authenticate($password))
        {
            // Invalid password
            $this->_setResult(self::FAILURE_CREDENTIAL_INVALID,
                              $userName,
                              "Invalid password");
            return $this;
        }

        /*****************************************************************
         * Success!
         *
         */
        $this->_setResult(self::SUCCESS,
                          $user);

        /*
        Connexions::log("Connexions_Auth_UserPassword::authenticate: "
                        . "User authenticated:\n"
                        . $user->debugDump());
        // */

        return $this;
    }
}
