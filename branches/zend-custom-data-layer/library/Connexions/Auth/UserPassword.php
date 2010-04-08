<?php
/** @file
 *
 *  A username/password authentication adapter for Connexions.
 *
 */

class Connexions_Auth_UserPassword extends Connexions_Auth_Abstract
{
    /** @brief  Return the authentication type of the concreted instance. */
    public function getAuthType()
    {
        return 'password';
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

        /* See if we can find a credential that matches, along with a valid
         * user
         */
        if (! $this->_matchUser($userName, md5($userName .':'. $password)))
        {
            // Error set by _matchUser
            return $this;
        }

        if ($this->_user->name !== $userName)
        {
            // Invalid password -- at least for THIS user...
            $this->_setResult(self::FAILURE_CREDENTIAL_INVALID,
                              $userName,
                              "Invalid password");

            /*
            Connexions::log("Connexions_Auth_UserPassword::authenticate: "
                            . "Mis-matched user name: [ %s ] !== [ %s ]",
                            $this->_user->name, $userName);
            // */
            return $this;
        }


        /*****************************************************************
         * Success!
         *
         */
        $this->_setResult(self::SUCCESS, $userName);

        /*
        Connexions::log("Connexions_Auth_UserPassword::authenticate: "
                        . "User authenticated:%s\n",
                        $this->_user->debugDump());
        // */

        return $this;
    }
}
