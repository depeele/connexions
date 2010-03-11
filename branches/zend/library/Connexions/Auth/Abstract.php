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
    protected   $_user      = null; // Model_User instance

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

    /** @brief  Set / initialize the Zend_Auth_Result portion.
     *  @param  code        The authentication status code.
     *  @param  identity    The current identity (MAY be a Model_User instance).
     *  @param  messages    An array of messages, or single string message.
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
        else if ($identity instanceof Model_User)
        {
            $this->_user = $identity;
        }

        return $this;
    }
}
