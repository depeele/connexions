<?php
/** @file
 *
 *  An Apache SSL authentication adapter for Connexions.
 *
 *  This will work with Apache/PHP via HTTPS and configured to require
 *  client-side certificates.
 */

/** @brief  A Zend_Auth authentication adapter.
 *
 *  Note: This class is a combination Zend_Auth_Adapter_Interface and
 *        Zend_Auth_Result.  A call to authenticate() will return $this with
 *        the proper result information set.
 *
 *        Call getIdentity() to retrieve the Distinguished Name of the
 *        authenticated user, getIssuer() to retrieve the Distinguished Name of
 *        the certificate issuer.  getIdentity() and getIssuer() will return
 *        null if authentication failed (i.e. ! isValid()).
 */
class Connexions_Auth_ApacheSsl extends Connexions_Auth_Abstract
{
    protected   $_authType  = 'pki';    // Model_UserAuth::AUTH_PKI

    protected   $_issuer    = null;

    /** @brief  Retrieve the authenticated issuer.
     *
     *  @return Issuer string (null if not authenticated).
     */
    public function getIssuer()
    {
        return $this->_issuer;
    }

    /** @brief  Perform an authentication attempt.
     *
     *  @throws Zend_Auth_Adapter_Exception if authentication cannot be
     *                                      performed.
     *  @return Zend_Auth_Result
     *              Valid code values:
     *                  Zend_Auth_Result::FAILURE
     *                  Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND
     *                  Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS
     *                  Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID
     *                  Zend_Auth_Result::FAILURE_UNCATEGORIZED
     *                  Zend_Auth_Result::SUCCESS
     */
    public function authenticate()
    {
        $this->_setResult();

        if ( (! isset($_SERVER['SSL_CLIENT_VERIFY'])) ||
             ($_SERVER['SSL_CLIENT_VERIFY'] !== 'SUCCESS') )
        {
            // Client certificate NOT verified.
            $this->_setResult(self::FAILURE_CREDENTIAL_INVALID,
                              null,
                              "Client Certificate NOT verified");

            // /*
            Connexions::log("Connenxions_Auth_ApacheSsl::authanticate: "
                            .   "FAILED: "
                            .   "code [ {$this->_code} ], "
                            .   "identity [ {$this->_identity} ], "
                            .   "messages [ "
                            .       @implode('; ', $this->_messages) ." ]");
            // */

            return $this;
        }

        if ( @empty($_SERVER['SSL_CLIENT_I_DN']) )
        {
            // Issuer Distinguished Name NOT set.
            $this->_setResult(self::FAILURE_CREDENTIAL_INVALID,
                              null,
                              "Issuer Distinguished Name missing");

            // /*
            Connexions::log("Connenxions_Auth_ApacheSsl::authanticate: "
                            .   "FAILED: "
                            .   "code [ {$this->_code} ], "
                            .   "identity [ {$this->_identity} ], "
                            .   "messages [ "
                            .       @implode('; ', $this->_messages) ." ]");
            // */

            return $this;
        }
        $this->_issuer   = $_SERVER['SSL_CLIENT_I_DN'];


        if ( @empty($_SERVER['SSL_CLIENT_S_DN']) )
        {
            // Client Distinguished Name NOT set.
            $this->_setResult(self::FAILURE_IDENTITY_AMBIGUOUS,
                              null,
                              "Client Distinguished Name missing: "
                              .     "Issuer [ {$this->_issuer} ]");

            // /*
            Connexions::log("Connenxions_Auth_ApacheSsl::authanticate: "
                            .   "FAILED: "
                            .   "code [ {$this->_code} ], "
                            .   "identity [ {$this->_identity} ], "
                            .   "messages [ "
                            .       @implode('; ', $this->_messages) ." ]");
            // */

            return $this;
        }
        $credential = $_SERVER['SSL_CLIENT_S_DN'];


        /* We have an authenticated Issuer and Client Distinguished Name,
         * see if we can locate the user that is associated with this
         * Client Distinguished Name.  This also sets the authentication
         * results.
         */
        $this->_matchAndCompare($credential);

        return $this;
    }
}
