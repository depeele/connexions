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

        // Retrieve the request
        $request = $this->getRequest();
        if ($request === null)
        {
            // There is not request so we cannot authenticate...
            return $this;
        }

        $userName = $request->getParam('username', null);
        $password = $request->getParam('password', null);

        // /*
        Connexions::log("Connexions_Auth_UserPassword::authenticate: "
                        . "username[ %s ], password[ %s ]",
                        $userName, $password);
        // */

        if (@empty($userName))
        {
            $this->_setResult(self::FAILURE_IDENTITY_AMBIGUOUS,
                              $userName,
                              "Missing user name");
            return $this;
        }

        if ($password === null)
        {
            $this->_setResult(self::FAILURE_CREDENTIAL_INVALID,
                              $userName,
                              "Missing password");
            return $this;
        }


        /* Match and Compare the identity and credential.  This also sets the
         * authentication results.
         */
        $this->_matchAndCompare($password, $userName);

        return $this;
    }
}
