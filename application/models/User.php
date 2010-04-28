<?php
/** @file
 *
 *  User Domain Model.
 *
 */

class Model_User extends Model_Base
                    implements  Zend_Tag_Taggable,
                                Zend_Auth_Adapter_Interface
{
    /* inferred via classname
    protected   $_mapper    = 'Model_Mapper_User'; */

    // The data for this Model
    protected   $_data      = array(
            'userId'        => null,
            'name'          => '',
            'fullName'      => '',
            'email'         => '',
            'apiKey'        => '',
            'pictureUrl'    => '',
            'profile'       => '',
            'lastVisit'     => '',

            /* Note: these items are typically computed and may not be 
             *       persisted directly.
             */
            'totalTags'     => 0,
            'totalItems'    => 0,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );

    // Data that is an instance of another Model or Model_Set
    protected   $_tags              = null;
    protected   $_bookmarks         = null;

    protected   $_authType          = Model_UserAuth::AUTH_PASSWORD;
    protected   $_credential        = null;
    protected   $_isAuthenticated   = false;

    /*************************************************************************
     * Connexions_Model abstract method implementations
     *
     */

    /** @brief  Retrieve the unique identifier for this instance.  This MAY 
     *          return an array of identifiers as key/value pairs.
     *
     *  @return The unique identifier.
     */
    public function getId()
    {
        return ( $this->userId );
    }

    public function setAuthType($authType)
    {
        $this->_authType = $authType;
    }

    public function setCredential($credential)
    {
        $this->_credential = $credential;
    }

    /*************************************************************************
     * Connexions_Model overrides
     *
     */

    /** @brief  Given incoming record data, populate this model instance.
     *  @param  data    Incoming key/value record data.
     *
     *  @return $this for a fluent interface.
     */
    public function populate($data)
    {
        if (empty($data['apiKey']))
        {
            // Generate an API key
            $data['apiKey'] = $this->genApiKey();
        }

        if (empty($data['lastVisit']))
        {
            // Initialize the last visit date to NOW.
            $data['lastVisit'] = date('Y-m-d H:i:s');
        }

        parent::populate($data);
    }

    /** @brief  Save this instancne.
     *
     *  Override to update 'lastVisit'
     *
     *  @return The (updated) instance.
     */
    public function save()
    {
        // On save, modify 'lastVisit' to NOW.
        $this->lastVisit = date('Y-m-d H:i:s');

        return parent::save();
    }

    /** @brief  Get a value of the given field.
     *  @param  name    The field name.
     *
     *  @return The field value (null if invalid).
     */
    public function __get($name)
    {
        switch ($name)
        {
        case 'authType':      $val = $this->_authType;        break;
        case 'credential':    $val = $this->_credential;      break;
        case 'authenticated': $val = $this->_isAuthenticated; break;
        case 'tags':          $val = $this->_tags();          break;
        case 'bookmarks':     $val = $this->_bookmarks();     break;
        default:              $val = parent::__get($name);    break;
        }

        return $val;
    }

    /** @brief  Set the value of the given field.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  @return $this for a fluent interface.
     */
    public function __set($name, $value)
    {
        switch ($name)
        {
        case 'authType':
            $this->_authType = $value;
            return;

            break;

        case 'credential':
            $this->_credential = $value;
            return;

            break;

        case 'authenticated':
            $this->_isAuthenticated = (bool)$value;
            return;

            break;

        case 'tags':
            if ( ! ($value instanceof Model_Tags))
            {
                throw new Exception("Tags can only be set using an "
                                    . "instance of Model_Tags");
            }
            $this->_tags = $value;
            return;

            break;

        case 'bookmarks':
            if ( ! ($value instanceof Model_Bookmarks))
            {
                throw new Exception("Bookmarks can only be set using an "
                                    . "instance of Model_Bookmarks");
            }
            $this->_bookmarks = $value;
            return;

            break;
        }

        return parent::__set($name, $value);
    }

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        if (! empty($this->name))
            return $this->name;

        return parent::__toString();
    }

    /** @brief  Return an array version of this instance.
     *  @param  deep    Should any associated models be retrieved?
     *                      [ Connexions_Model::DEPTH_DEEP ] |
     *                        Connexions_Model::DEPTH_SHALLOW
     *  @param  public  Include only "public" information?
     *                      [ Connexions_Model::FIELDS_PUBLIC ] |
     *                        Connexions_Model::FIELDS_ALL
     *
     *  @return An array representation of this Domain Model.
     */
    public function toArray($deep   = self::DEPTH_DEEP,
                            $public = self::FIELDS_PUBLIC)
    {
        $data = $this->_data;

        if ($public === self::FIELDS_PUBLIC)
        {
            unset($data['apiKey']);
        }

        return $data;
    }

    /** @brief  Invalidate the data contained in this model instance.
     *
     *  @return $this for a fluent interface.
     */
    public function invalidate()
    {
        $this->invalidateCache();

        $this->_authType        = Model_UserAuth::AUTH_PASSWORD;
        $this->_credential      = null;
        $this->_isAuthenticated = false;

        return parent::invalidate();
    }

    /** @brief  Invalidate our internal cache of sub-instances.
     *
     *  @return $this for a fluent interface
     */
    public function invalidateCache()
    {
        $this->_tags        = null;
        $this->_bookmarks   = null;

        return $this;
    }

    /** @brief  Set the authentication state for this user.
     *  @param  val     The new state (true | false).
     *
     *  @return $this for a fluent interface.
     */
    public function setAuthenticated($state = true)
    {
        $this->_isAuthenticated = (bool)$state;

        return $this;
    }

    /** @brief  Retrieve the authentication state for this user.
     *
     *  @return The authentication state (true | false).
     */
    public function isAuthenticated()
    {
        return $this->_isAuthenticated;
    }

    /*************************************************************************
     * Zend_Auth_Adapter_Interface
     *
     */

    /** @brief  Perform an authentication check.
     *
     *  This makes use of the current Model_User instance as the holder of 
     *  incoming information
     *      (i.e. 'userId' or 'name' AND 'credential' AND 'authType').
     *
     *  To authenticate:
     *      1) If THIS instance is backed
     *         a) THEN use THIS instance as the identified user;
     *         b) ELSE locate a valid user using the 'userId' or 'name' from 
     *            THIS instance,
     *      2) Locate a Model_UserAuth instance using the 'userId' of the 
     *         identified user as well as the 'authType' from THIS instance;
     *      3) Invoke 'compare()' on the Model_UserAuth instance passing in the 
     *         'credential' from THIS instance;
     *      4) Generate an appropriate Zend_Auth_Result;
     *
     *
     *  Note: This requires AT LEAST
     *              'name' OR 'userId',
     *              setCredential() and,
     *              unless the authentication type is 'password', setAuthType()
     *
     *  @return Zend_Auth_Result
     */
    public function authenticate()
    {
        // See if the user represented by this instnace can be located
        // either by userId or name
        $user = null;
        $auth = null;
        if ($this->isBacked())
        {
            // 1a) Use THIS instance as the validated user;
            $user =& $this;
        }
        else
        {
            // 1b) Locate a valid, backed user...
            $mapper = $this->getMapper();
            if ($this->userId > 0)
            {
                $user = $mapper->find( $this->userId );
            }
            else if ( ! empty($this->name) )
            {
                $matches = $mapper->fetch( array('name=?' => $this->name) );
                if (! empty($matches))
                {
                    $user = $matches[0];
                }
            }
            // else, Ambiguous User -- handled below...
        }

        // 2)
        if ( $user !== null )
        {
            /* 2) See if we can find a 'userAuth' record for this
             *    userId and authType
             *
             * Note: We're using the 'userId' of the located user instance and 
             *       the 'authType' and 'credential' of THIS instance.
             *       They MAY be different (see 1b above).
             */
            $authMapper =
                Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
            $auth       = $authMapper->find( array($user->userId,
                                                   $this->_authType) );
            if ($auth !== null)
                $auth->user = $user;


            /*
            Connexions::log("Model_User::authenticate(): "
                            . "Found user '%s':%d, authType[ %s ], "
                            . "UserAuth [ %s ]",
                            $user->name, $user->userId, $this->_authType,
                            (is_object($auth)
                                ? get_class($auth)
                                : gettype($auth)) );
            // */

            /* 3) Invoke 'compare()' on the Model_UserAuth instance passing in 
             *    the 'credential' from THIS instance;
             */
            if ( (! $auth instanceof Model_UserAuth) ||
                 (  $auth->compare($this->_credential) !== true ) )
            {
                // Authentication failure : Invalid Credential
                $auth = null;
            }
        }


        // 4) Generate an appropriate Zend_Auth_Result;
        if ($user === null)
        {
            $result = new Zend_Auth_Result(
                            //Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
                            Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS,
                            null);
        }
        else if ($auth === null)
        {
            $result = new Zend_Auth_Result(
                            Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
                            null);
        }
        else
        {
            /* Retrieve configuration / construction data for the
             * identified/authenticated user.
             */
            $config = $user->toArray( self::DEPTH_SHALLOW,
                                      self::FIELDS_ALL );

            if ($user !== $this)
            {
                /* Update THIS model to match the identified/authenticated user
                 *
                 * :WARNING: We may have an Identity Map issue since we're
                 *           basically duplicating an existing instance.
                 */
                $this->populate( $config );
                $this->setIsBacked( $user->isBacked() );
            }

            $this->setAuthenticated();

            /* Generate a SUCCESS result using the configuration/construction
             * data as the identification.  This will be stored by Zend_Auth
             * and is what we will see in the session next time this user loads
             * the page.
             */
            $result = new Zend_Auth_Result(Zend_Auth_Result::SUCCESS,
                                           $config);
        }

        return $result;
    }

    /** @brief  De-authenticate this user. */
    public function logout()
    {
        Zend_Auth::getInstance()->clearIdentity();

        $this->setAuthenticated(false);
    }

    /*************************************************************************
     * Zend_Tag_Taggable Interface
     *
     */
    protected       $_params    = array();

    public function getParam($name)
    {
        // weightValue, url, selected
        $val = (@isset($this->_params[$name])
                    ? $this->_params[$name]
                    : null);
        return $val;
    }

    public function setParam($name, $value)
    {
        // weightValue, url, selected
        $this->_params[$name] = $value;
    }

    public function getTitle()
    {
        $title = (String)($this->name);

        return $title;
    }

    public function getWeight()
    {
        $weight = 0;
        if (isset($this->weight))
            $weight = (Float)($this->weight);
        else if (isset($this->tagCount))
            $weight = (Float)($this->tagCount);
        else if (isset($this->totalItems))
            $weight = (Float)($this->totalItems);

        return $weight;
    }

    /*************************************************************************
     * Protected helpers
     *
     */
    protected function _tags()
    {
        if ($this->_tags === null)
        {
            $this->_tags = $this->getMapper()->getTags( $this );
        }

        return $this->_tags;
    }

    protected function _bookmarks()
    {
        if ($this->_bookmarks)
        {
            $this->_bookmarks = $this->getMapper()->getBookmarks( $this );
        }

        return $this->_bookmarks;
    }

    /*************************************************************************
     * Static methods
     *
     */

    /** @brief  Generate a new API key with characters [a-zA-Z0-9].
     *  @param  len The length of the new key [ 10 ].
     *
     *  @return The new key.
     */
    public static function genApiKey($len = 10)
    {
        $chars    = array_merge(range('a','z'),range('A','Z'),range('0','9'));
        $nChars   = count($chars) - 1;
        $key      = '';

        list($ms) = explode(' ', microtime());
        srand($ms * 100000);

        for ($idex = 0; $idex < $len; $idex++)
        {
            $key .= $chars[ rand(0, $nChars) ];
        }

        return $key;
    }
}
