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

    protected   $_authType          = Model_UserAuth::AUTH_DEFAULT;
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

    /** @brief  Establish the 'authType' to be used for authentication.
     *  @param  authType    authentication type -- MUST be a valid type per
     *                      User_AuthType.
     *
     *  @throws Exception('Invalid authType')
     *  @return $this for a fluent interface.
     */
    public function setAuthType($authType)
    {
        if ($this->validateAuthType($authType))
        {
            $this->_authType = $authType;
        }

        return $this;
    }

    /** @brief  Establish the 'credential' to be used for authentication.
     *  @param  credential  The authentication credential;
     *  @param  authType    OPTIONAL authentication type -- may also be set via
     *                      setAuthType().  If NOT set, the default value will
     *                      be used;
     *
     *  @throws Exception('Invalid authType')
     *  @return $this for a fluent interface.
     */
    public function setCredential($credential, $authType = null)
    {
        $this->_credential = $credential;
        if ($authType !== null)
            $this->setAuthType($authType);

        return $this;
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
            $this->setAuthType( $value );
            break;

        case 'credential':
            $this->setCredential( $value );
            break;

        case 'authenticated':
            $this->setAuthenticated( $value );
            break;

        case 'tags':
            if ( ! ($value instanceof Model_Tags))
            {
                throw new Exception("Tags can only be set using an "
                                    . "instance of Model_Tags");
            }
            $this->_tags = $value;
            break;

        case 'bookmarks':
            if ( ! ($value instanceof Model_Bookmarks))
            {
                throw new Exception("Bookmarks can only be set using an "
                                    . "instance of Model_Bookmarks");
            }
            $this->_bookmarks = $value;
            break;

        default:
            parent::__set($name, $value);
        }

        return $this;
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
    public function toArray($deep   = self::DEPTH_SHALLOW,
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

        $this->setCredential(null, Model_UserAuth::AUTH_DEFAULT);
        $this->setAuthenticated(false);

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

    /** @brief  Generate a string representation of this record.
     *
     *  @return A string.
     */
    public function debugDump()
    {
        $str = parent::debugDump();

        // Remove the trailing "\n];" (3 characters)
        $str = substr($str, 0, -3);

        // Include authentication status at the top
        $str = preg_replace('/valid \[/', 'valid, '
                                     . ($this->isAuthenticated() ? '' : 'NOT ')
                                     . 'authenticated [',
                            $str);

        // Include authentication details
        foreach (array('authType', 'credential') as $key)
        {
            $val  = $this->__get($key);
            $str .= sprintf (" %-15s == %-15s %s [ %s ]%s\n",
                             $key, gettype($val),
                             //($this->_valid[$key] !== true
                             //   ? (isset($this->_valid[$key])
                             //       ? "!"
                             //       : "?")
                             //   : " "),
                             ' ',
                             $val,
                             //($this->_valid[$key] !== true
                             //   ? (is_array($this->_valid[$key])
                             //       ? " : ". implode(', ',$this->_valid[$key])
                             //       : $this->_valid[$key])
                             //   : ''));
                             '');
        }

        $str .= "\n];";

        return $str;
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
     *  incoming information (i.e. 'credential' and 'authType').  This instance
     *  MUST be backed and valid.  If no 'authType' has been set, the default
     *  type will be used.
     *
     *  Authentication information MUST be established prior to invoking this
     *  method:
     *      - setAuthType()     Required iff NOT using the default
     *                          authentication type
     *                          (User_AuthType::AUTH_DEFAULT);
     *      - setCredential()   REQUIRED to establish the credential to use
     *                          when authenticating;
     *
     *  To authenticate:
     *      1) If THIS instance is backed and valid:
     *         a) Locate a Model_UserAuth instance using the 'userId' as well
     *            as the 'authType' (if unset, Model_UserAuth will use the
     *            default type) from THIS instance;
     *         b) Invoke 'compare()' on the Model_UserAuth instance passing in
     *            the 'credential' from THIS instance;
     *      2) Generate an appropriate Zend_Auth_Result;
     *
     *  @return Zend_Auth_Result
     */
    public function authenticate()
    {
        /*
        Connexions::log("Model_User::authenticate(): "
                        . "is %sbacked, %svalid, userId %d, "
                        . "authType '%s', credential '%s'",
                        ($this->isBacked()         ? ''        : 'NOT '),
                        ($this->isValid()          ? ''        : 'NOT '),
                        $this->userId,
                        $this->_authType, $this->_credential);
        // */


        // See if the user represented by this instnace can be located
        // either by userId or name
        $user = null;
        $auth = null;
        if ($this->isBacked() && $this->isValid())
        {
            // 1) THIS instance is backed and valid;
            $user =& $this;


            /* 1a) See if we can find a 'userAuth' record for this
             *     userId and authType
             */
            $authMapper =
                Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
            $auth       = $authMapper->find( array($user->userId,
                                                   $user->_authType) );
            if ($auth !== null)
            {
                $auth->user = $user;

                /*
                Connexions::log("Model_User::authenticate(): "
                                . "Found user '%s':%d, authType[ %s ], "
                                . "UserAuth [ %s ]",
                                $user->name, $user->userId, $user->_authType,
                                (is_object($auth)
                                    ? get_class($auth)
                                    : gettype($auth)) );
                // */

                /* 1b) Invoke 'compare()' on the Model_UserAuth instance
                 *     passing in the 'credential' from THIS instance;
                 */
                if ( $auth->compare($this->_credential) !== true )
                {
                    // Authentication failure : Invalid Credential
                    $auth = null;
                }
            }
        }

        // 2) Generate an appropriate Zend_Auth_Result;
        if ($user === null)
        {
            // This user is either non-backed or non-valid
            $result = new Zend_Auth_Result(
                            //Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
                            Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS,
                            null);
        }
        else if ($auth === null)
        {
            // Authentication failed -- invalid credential
            $result = new Zend_Auth_Result(
                            Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
                            null);
        }
        else
        {
            /* Successful authentication!
             *
             * Retrieve configuration / construction data for the
             * identified/authenticated user.
             */
            $config = $user->toArray( self::DEPTH_SHALLOW,
                                      self::FIELDS_ALL );

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

    /**********************************************
     * Additional authentication related methods
     *
     */

    /** @brief  De-authenticate this user. */
    public function logout()
    {
        Zend_Auth::getInstance()->clearIdentity();

        $this->setAuthenticated(false);
    }

    /** @brief  Add a new authenticator (Model_UserAuth entry) for this user.
     *  @param  credential      The authentication credential.
     *  @param  type            The authentication type
     *                          [ Model_UserAuth::AUTH_DEFAULT ];
     *
     *  @throws Exception('Invalid authType')
     *  @return The new Model_UserAuth instance (null on failure).
     */
    public function addAuthenticator($credential,
                                     $authType = Model_UserAuth::AUTH_DEFAULT)
    {
        if ((! $this->isBacked()) ||
            (! $this->validateAuthType($authType)) )
        {
            return null;
        }

        $authMapper =
                Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');

        $auth = $authMapper->makeModel(array(
                                'userId'      => $this->userId,
                                'authType'    => $authType,
                                'credential'  => $credential),
                                false); // not-yet-backed

        if ($auth !== null)
            $auth = $auth->save();

        return $auth;
    }

    /** @brief  Retrieve authenticator(s) (Model_UserAuth entry) for this user.
     *  @param  type            The authentication type       [ null for all ];
     *  @param  credential      The authentication credential [ null for all ].
     *
     *  @return The Model_Set_UserAuth instance containing all matching
     *          authenticators (null if none found).
     */
    public function getAuthenticator($authType   = null,
                                     $credential = null)
    {
        if (! $this->isBacked())
        {
            return null;
        }

        $criteria = array('userId' => $this->userId);
        if ($authType !== null)
            $criteria['authType'] = $authType;
        if ($credential !== null)
            $criteria['credential'] = $credential;

        $authMapper =
                Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');

        return $authMapper->fetch( $criteria );
    }

    /** @brief  Remove one or more authenticators (Model_UserAuth entry) for
     *          this user.
     *  @param  credential      The authentication credential.
     *  @param  type            The authentication type
     *                          [ Model_UserAuth::AUTH_DEFAULT ];
     *
     *  @throws Exception('Invalid authType')
     *  @return $this for a fluent interface.
     */
    public function removeAuthenticator($credential,
                                        $authType =
                                            Model_UserAuth::AUTH_DEFAULT)
    {
        if (! $this->isBacked())
        {
            return $this;
        }

        $criteria = array('userId' => $this->userId);
        if ($authType !== null)
        {
            if (! $this->validateAuthType($authType))
            {
                return null;
            }
            $criteria['authType'] = $authType;
        }
        if ($credential !== null)
            $criteria['credential'] = $credential;

        $authMapper =
                Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');

        $set = $authMapper->fetch( $criteria );

        foreach ($set as $item)
        {
            $item->delete();
        }

        return $this;
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

    /**********************************************
     * Tag Management related methods
     *
     */

    /** @brief  Given an array of tag rename information, rename tags for this 
     *          user.
     *  @param  renames     An array of tag rename information:
     *                          { 'oldTagName' => 'newTagName',
     *                            ... }
     *
     *  @return An array of status information, keyed by old tag name:
     *              { 'oldTagName'  => true (success) |
     *                                 String explanation of failure,
     *                 ... }
     */
    public function renameTags(array $renames)
    {
        $status = array();
        foreach ($renames as $old => $new)
        {
            /* 1) Verify that this user has 'old' tag;
             *    a) No  - record an error in the status for this tag and skip;
             *    b) Yes - $old is now a Model_Tag instance;
             *
             * 2) See if the 'new' tag exists;
             *    a) No  - create it;
             *    b) Yes - continue;
             *
             *    Either way, $new is now a Model_Tag instance;
             *
             * 3) Change all 'userTagItem' entries for
             *      $this->userId, $old->tagId, <item>
             *    to
             *      $this->userId, $new->tagId, <item>
             */
        }

        // 4) Update statistics;

        return $status;
    }

    /** @brief  Given an Model_Set_Tag instance or a simple array of tag names,
     *          delete all tags for the current user.  If deleting a tag will
     *          result in an "orphened bookmark" (i.e. a bookmark with no 
     *          tags), the delete of that tag will fail.
     *  @param  tags        A Model_Set_Tag instance of a simple array of tag 
     *                      names.
     *
     *  @return An array of status information, keyed by tag name:
     *              { 'tagName' => true (success) |
     *                             String explanation of failure,
     *                 ... }
     */
    public function deleteTags($tags)
    {
        $status = array();
        foreach ($tags as $tag)
        {
            /* 1) Verify that this user has 'tag';
             *    a) No  - record an error in the status for this tag and skip;
             *    b) Yes - $tag is now a Model_Tag instance;
             *
             * 2) Find all bookmaks with this tag, couting the number of unique 
             *    tags for each;
             *    a) If there is one or more bookmarks with a tag count of 1,
             *       DO NOT delete the tag.  Record an error in the status for 
             *       this tag and skip;
             *
             *    b) Otherwise, continue;
             *
             * 3) Delete all 'userTagItem' entries for
             *      $this->userId, $tag->tagId, <item>
             */
        }

        return $status;
    }

    /**********************************************
     * Statistics related methods
     *
     */

    /** @brief  Update external-table statistics related to this User instance:
     *              totalTags, totalItems
     *
     *  @return $this for a fluent interface
     */
    public function updateStatistics()
    {
        $this->getMapper()->updateStatistics( $this );

        return $this;
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Validate the provided authentication type.
     *          If validation fails, throw an exception.
     *  @param  authType    The authentication type (Model_UserAUth::AUTH_*).
     *
     *  @throws Exception("Invalid authType");
     *  @return true | false
     */
    protected function validateAuthType($authType)
    {
        if (! Model_UserAuth::validateAuthType($authType))
        {
            throw new Exception("Invalid authType");
        }

        return true;
    }

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
