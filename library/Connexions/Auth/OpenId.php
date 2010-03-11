<?php
/** @file
 *
 *  An OpenId authentication adapter for Connexions.
 *
 */

class Connexions_Auth_OpenId extends Connexions_Auth_Abstract
{
    /** @brief  URL of the identification endpoint.
     *  @var    string
     */
    protected   $_endpoint      = null;

    /** @brief  Reference to an implementation of a storage object
     *  @var    Zend_OpenId_Consumer_Storage
     */
    private $_storage           = null;

    /** @brief  The URL to redirect response from server to
     *  @var    string
     */
    private $_returnTo          = null;

    /** @brief  The HTTP URL to identify consumer on server
     *  @var    string
     */
    private $_root              = null;

    /** @brief  Extension object or array of extensions objects
     *  @var    string
     */
    private $_extensions        = null;

    /** @brief  The response object to perform HTTP or HTML form redirection
     *  @var    Zend_Controller_Response_Abstract
     */
    private $_response          = null;

    /** @brief  Enables or disables interaction with user during authentication
     *          on OpenID provider.
     *  @var    bool
     */
    private $_check_immediate   = false;

    /** @brief  HTTP client to make HTTP requests
     *  @var    Zend_Http_Client $_httpClient
     */
    private $_httpClient        = null;

    /** @brief  SReg extension values (on successful authentication) */
    protected   $_nickname      = null;
    protected   $_fullname      = null;


    /** @brief  Construct a new instatnce.
     *  @param  endpoint    The desired endpoint.
     */
    public function __construct($endpoint   = null,
                                $extensions = null)
    {
        $this->_endpoint   = $endpoint;
        $this->_extensions = $extensions;

        parent::__construct(self::FAILURE, null);
    }

    public function getNickname()
    {
        return $this->_nickname;
    }

    public function getFullname()
    {
        return $this->_fullname;
    }

    /** @brief  Set the identification endpoint.
     *  @param  $endpoint   The identification endpoint (URL).
     *
     *
     *  @return Connexions_Auth_OpenId Provides a fluent interface
     */
    public function setEndpoint($endpoint)
    {
        $this->_setResult();
        $this->_endpoint = $endpoint;

        return $this;
    }

    /** @brief  Sets the storage implementation which will be use by OpenId
     *  @param  Zend_OpenId_Consumer_Storage $storage
     *
     *  @return Zend_Auth_Adapter_OpenId Provides a fluent interface
     */
    public function setStorage(Zend_OpenId_Consumer_Storage $storage)
    {
        $this->_setResult();
        $this->_storage = $storage;
        return $this;
    }

    /** @brief  Sets the HTTP URL to redirect response from server to
     *  @param  string $returnTo
     *
     *  @return Zend_Auth_Adapter_OpenId Provides a fluent interface
     */
    public function setReturnTo($returnTo)
    {
        $this->_setResult();
        $this->_returnTo = $returnTo;
        return $this;
    }

    /** @brief  Sets HTTP URL to identify consumer on server
     *  @param  string $root
     *
     *  @return Zend_Auth_Adapter_OpenId Provides a fluent interface
     */
    public function setRoot($root)
    {
        $this->_setResult();
        $this->_root = $root;
        return $this;
    }

    /** @brief  Sets OpenID extension(s)
     *  @param  mixed $extensions
     *
     *  @return Zend_Auth_Adapter_OpenId Provides a fluent interface
     */
    public function setExtensions($extensions)
    {
        $this->_setResult();
        $this->_extensions = $extensions;
        return $this;
    }

    /** @brief  Sets an optional response object to perform HTTP or HTML form
     *          redirection
     *  @param  string $root
     *
     *  @return Zend_Auth_Adapter_OpenId Provides a fluent interface
     */
    public function setResponse($response)
    {
        $this->_setResult();
        $this->_response = $response;
        return $this;
    }

    /** @brief  Enables or disables interaction with user during authentication
     *          on OpenID provider.
     *  @param  bool $check_immediate
     *
     *  @return Zend_Auth_Adapter_OpenId Provides a fluent interface
     */
    public function setCheckImmediate($check_immediate)
    {
        $this->_setResult();
        $this->_check_immediate = $check_immediate;
        return $this;
    }

    /** @brief  Sets HTTP client object to make HTTP requests
     *  @param  Zend_Http_Client $client HTTP client object to be used
     */
    public function setHttpClient($client)
    {
        $this->_setResult();
        $this->_httpClient = $client;
    }

    /** @brief  Authenticates the given OpenId identity.  Defined by
     *          Zend_Auth_Adapter_Interface.
     *
     *  @throws Zend_Auth_Adapter_Exception If answering the authentication
     *                                      query is impossible
     *
     *  @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $this->_setResult();

        if ($this->_extensions === null)
        {
            // Default extension request.
            $this->_extension = new Zend_OpenId_Extension_Sreg(
                                    array('nickname' => false,  // optional
                                          'fullname' => false   // optional
                                    ),
                                    null,    // policyUrl
                                    1.1);    // version
        }

        /*
        Connexions::log("Connexions_Auth_OpenId::authenticate: "
                            . "OpenId.identifier[ {$this->_endpoint} ]");
        // */

        $endpoint = $this->_endpoint;
        if (!empty($endpoint))
        {
            $consumer = new Zend_OpenId_Consumer($this->_storage);
            $consumer->setHttpClient($this->_httpClient);

            /* login() is never returns on success */
            if (!$this->_check_immediate)
            {
                /*
                Connexions::log("Connexions_Auth_OpenId::authenticate: "
                                . "login...");
                // */

                if (!$consumer->login($endpoint,
                                      $this->_returnTo,
                                      $this->_root,
                                      $this->_extensions,
                                      $this->_response))
                {
                    /*
                    Connexions::log("Connexions_Auth_OpenId::authenticate: "
                                    . "login FAILED "
                                    . "[ {$consumer->getError()} ]");
                    // */
                    $this->_setResult(Zend_Auth_Result::FAILURE,
                                      $endpoint,
                                      "Authentication failed",
                                            $consumer->getError());
                }
            }
            else
            {
                /*
                Connexions::log("Connexions_Auth_OpenId::authenticate: "
                                . "check...");
                // */
                if (!$consumer->check($endpoint,
                                      $this->_returnTo,
                                      $this->_root,
                                      $this->_extensions,
                                      $this->_response))
                {
                    $this->_setResult(Zend_Auth_Result::FAILURE,
                                     $endpoint,
                                     "Authentication failed",
                                           $consumer->getError());
                }
            }
        }
        else
        {
            /*
            Connexions::log("Connexions_Auth_OpenId::authenticate: "
                            . "non-POST, auth response?");
            // */
            $params = (isset($_SERVER['REQUEST_METHOD']) &&
                       $_SERVER['REQUEST_METHOD']=='POST') ? $_POST: $_GET;

            $consumer = new Zend_OpenId_Consumer($this->_storage);
            $consumer->setHttpClient($this->_httpClient);

            if ($consumer->verify($params,
                                  $endpoint,
                                  $this->_extensions))
            {
                /* We've successfully authenticated via OpenId.
                 *
                 * Now, can we locate the user that is associated with this
                 * authenticated endpoint?
                 */
                $this->_endpoint = $endpoint;

                $this->_matchUser();
            }
            else
            {
                $this->_setResult(Zend_Auth_Result::FAILURE,
                                  $endpoint,
                                  "Authentication failed",
                                        $consumer->getError());
            }
        }

        return $this;
    }


    /** @brief  OpenId authentication has been successful.  We have an
     *          authenticated endpoint.  Now, see if we can find a valid user
     *          associated with the endpoint.
     *
     *  MAY modify our Zend_Auth_Result information.
     */
    protected function _matchUser()
    {
        if ($this->_extensions !== null)
        {
            $data            = $this->_extensions->getProperties();
            $this->_nickname = (isset($data['nickname'])
                                    ? $data['nickname']
                                    : null);
            $this->_fullname = (isset($data['fullname'])
                                    ? $data['fullname']
                                    : null);
        }

        $userAuth = new Model_UserAuth( $this->_endpoint );

        /*
        Connexions::log("Connexions_Auth_OpenId::_matchUser: "
                            . "OpenId.identifier[ {$this->_endpoint} ], "
                            . "SRE.nickname[ {$this->_nickname} ], "
                            . "SRE.fullname[ {$this->_fullname} ], "
                            . "UserAuth map:\n"
                            . $userAuth->debugDump());
        // */

        if ($userAuth->isBacked())
        {
            /* We found a matching authentication record.  Now,
             * retrieve the identified user.
             */
            $user = new Model_User( $userAuth->userId );

            /*
            Connexions::log("Connexions_Auth_OpenId::_matchUser: "
                            . "OpenId.identifier[ {$this->_endpoint} ], "
                            . "Mapped to user:\n"
                            . $user->debugDump());
            // */
        }

        if ( ($user === null) || (! $user->isBacked()) )
        {
            // FAILURE
            $this->_setResult(self::FAILURE,
                              $this->_endpoint,
                              "OpenId login failure. "
                                        . "Unmapped endpoint "
                                        .   "[ {$this->_endpoint} ], "
                                        .   "nickname [ {$this->_nickname} ], "
                                        .   "fullname [ {$this->_fullname} ]");
        }
        else
        {
            // SUCCESS
            $user->setAuthenticated();

            $this->_setResult(self::SUCCESS,
                              $user);
        }

        return $this;
    }
}
