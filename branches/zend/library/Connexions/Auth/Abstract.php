<?php
/** @file
 *
 *  An abstract authentication adapter for Connexions.
 */

/** @brief  An abstract Zend_Auth authentication adapter.
 *
 *  Note: This class is a combination Zend_Auth_Adapter_Interface and
 *        Zend_Auth_Result.  A call to authenticate() will return $this with
 *        the proper result information set.
 *
 *        Upon successful authentication, the Model_User instance representing
 *        the authenticated user MUST have been established.
 */
abstract class Connexions_Auth_Abstract extends Zend_Auth_Result
                                        implements Zend_Auth_Adapter_Interface
{
    protected   $_user          = null; // Model_User     instance
    protected   $_userAuth      = null; // Model_UserAuth instance

    /** @brief  Construct a new instatnce.
     *
     */
    public function __construct()
    {
        parent::__construct(self::FAILURE, null);
    }

    public function getUser()
    {
        return $this->_user;
    }

    /** @brief  Return the authentication type of the concrete instance. */
    public function getAuthType()
    {
        // :NOTE: The concrete classes MUST have an _authType member.
        return $this->_authType;
    }

    public function __toString()
    {
        return sprintf ("{ code: %d, identity: '%s', messages: [ %s ] }",
                        $this->_code,
                        $this->_identity,
                        (is_array($this->_messages)
                            ? implode(', ', $this->_messages)
                            : ''));
    }

    public function toArray()
    {
        return array('code'     => $this->_code,
                     'identity' => $this->_identity,
                     'messages' => $this->_messages);
    }

    /*************************************************************************
     * Protected Methods
     *
     */

    /** @brief  Set / initialize the Zend_Auth_Result portion.
     *  @param  code        The authentication status code;
     *  @param  identity    The current identity
     *                      (MAY be a Model_User instance);
     *  @param  messages    An array of messages, or single string message;
     *
     *  @return Connexions_Auth_Abstract for a fluent interface.
     */
    protected function _setResult($code     = self::FAILURE,
                                  $identity = null,
                                  $messages = array())
    {
        $this->_code     = $code;
        $this->_identity = ($identity instanceof Model_User
                                ? $identity->userId
                                : $identity);
        $this->_messages = (is_array($messages)
                                ? $messages
                                : array($messages));

        if ($code !== self::SUCCESS)
        {
            // On error, ensure that our Model_User instance is empty.
            $this->_user = null;
        }
        else
        {
            if ($identity instanceof Model_User)
            {
                $this->_user = $identity;
            }

            $this->_user->setAuthenticated();
        }

        return $this;
    }

    /** @brief  Given a user identity and/or credential, see if there is a
     *          userAuth record for the credential/authentication type pair.
     *  @param  credential      The credential to match;
     *  @param  identity        The incoming user identity [ null ];
     *
     *  @return true | false
     */
    protected function _matchAndCompare($credential, $identity = null)
    {
        $uaMapper = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $uService = Connexions_Service::factory('Service_User');
        $user     = null;
        $userAuth = null;

        // Locate the matching Model_UserAuth instance...
        if ($identity !== null)
        {
            // See if there is a Model_User instance matching the provided
            // 'identity'
            $user = $uService->find( $identity );
            if ($user === null)
            {
                $this->_setResult(self::FAILURE_IDENTITY_NOT_FOUND,
                                  $identity,
                                  "Unmatched identity");
                return false;
            }

            // Attempt to locate the Model_UserAuth instance matching 'user'
            $userAuth = $uaMapper->find( array(
                            'userId'    => $user->userId,
                            'authType'  => $this->getAuthType(),
                        ));
        }
        else
        {
            // We weren't given an identity, so perform a lookup by credential
            $userAuth = $uaMapper->find( array(
                                'authType'   => $this->getAuthType(),
                                'credential' => $credential));
        }


        if ($userAuth === null)
        {
            // CANNOT FIND a matching authentication record.
            if (! empty($identity))
                $id = $identity;
            else
                $id = $credential;

            $this->_setResult(self::FAILURE,
                              $id,
                              "Authentication failure. "
                              . "No authenticator for identity.");
            return false;
        }

        if ($user === null)
        {
            // Now, retrieve the matching Model_User instance
            $user = $userAuth->user;
        }
        else
        {
            // Make sure userAuth has the same user instance.
            $userAuth->user = $user;
        }

        /*
        Connexions::log("Connexions_Auth_Abstract::_matchUser(%s, %s): "
                        . "found Model_User: useId[ %d ], name[%s ]",
                        $identity, $credential, $user->userId, $user->name);
        // */

        /**********************************************************
         * Compare the incoming credential against the
         * authentic credential.
         *
         */
        if (! $userAuth->compare($credential))
        {
            // Invalid credential
            $this->_setResult(self::FAILURE_CREDENTIAL_INVALID,
                              $user->name,
                              "Invalid credential");
            return false;
        }

        /**********************************************************
         * Valid identity AND credential -- authentication success
         *
         */
        $this->_user     = $user;
        $this->_userAuth = $userAuth;

        $this->_setResult(self::SUCCESS);

        return true;
    }
}
