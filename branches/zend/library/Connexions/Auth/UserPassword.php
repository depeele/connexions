<?php
/** @file
 *
 *  A username/password authentication adapter for Connexions.
 *
 *  The "Sign-In" form MUST be submitted via POST and MUST include:
 *      - username
 *      - password
 */

class Connexions_Auth_UserPassword extends Connexions_Auth_Abstract
{
    protected   $_authType  = 'password';   // Model_UserAuth::AUTH_PASSWORD

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
        $userName = null;

        /*
        Connexions::log("Connexions_Auth_UserPassword::authenticate: "
                        . "_POST: username[ %s ], password[ %s ]",
                        $_POST['username'], $_POST['password']);
        // */

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


        /* Match and Compare the identity and credential.  This also sets the
         * authentication results.
         */
        $this->_matchAndCompare($password, $userName);

        return $this;
    }
}
