<?php
/** @file
 *
 *  User Domain Model.
 *
 */

class Model_User extends Model_Taggable
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
    protected   $_authResult        = null;

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

    /** @brief  Update the last visit date of this model instance to NOW.
     *
     *  @return $this for a fluent interface.
     */
    public function updateLastVisit()
    {
        $this->__set('lastVisit', date('Y-m-d H:i:s'));

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
        if (! $this->isBacked())
        {
            /* For a new, un-backed model instance, ensure that 'apiKey' and 
             * 'lastVisit' are initialized.
             */
            if (empty($data['apiKey']))
            {
                // Generate an API key
                $data['apiKey'] = $this->genApiKey();
            }

            if (empty($data['lastVisit']))
            {
                $data['lastVisit'] = date('Y-m-d H:i:s');
            }
        }


        return parent::populate($data);
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
     *  @param  props   Generation properties:
     *                      - deep      Deep traversal (true)
     *                                    or   shallow (false)
     *                                    [true];
     *                      - public    Include only public fields (true)
     *                                    or  also include private (false)
     *                                    [true];
     *                      - dirty     Include only dirty fields (true)
     *                                    or           all fields (false);
     *                                    [false];
     *
     *  @return An array representation of this Domain Model.
     */
    public function toArray(array $props    = array())
    {
        $data = parent::toArray($props);

        if ($props['public'] !== false)
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
        $this->setAuthResult(false);

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
     *  @param  indent      The number of spaces to indent [ 0 ];
     *  @param  leaveOpen   Should the terminating '];\n' be excluded [ false ];
     *
     *  @return A string.
     */
    public function debugDump($indent       = 0,
                              $leaveOpen    = false)
    {
        $str = parent::debugDump($indent, true);

        // Include authentication status at the top
        $str = preg_replace('/valid \[/',
                            'valid, '
                            . ($this->isAuthenticated() ? '' : 'NOT ')
                            . 'authenticated [',
                            $str);

        if ($this->_credential !== null)
        {
            // Include authentication details
            foreach (array('authType', 'credential') as $key)
            {
                $val  = $this->__get($key);
                $str .= sprintf ("%s%-15s == %-15s %s [ %s ]%s\n",
                                 str_repeat(' ', $indent + 1),
                                 $key, gettype($val),
                                 ' ',
                                 $val,
                                 '');
            }
        }

        if ($leaveOpen !== true)
            $str .= "\n];";

        return $str;
    }

    /** @brief  Set the authentication state for this user.
     *  @param  result  The Zend_Auth_Result representing the authentication
     *                  state (anything else will force to un-authenticated);
     *
     *  @return $this for a fluent interface.
     */
    public function setAuthResult($result)
    {
        if ($result instanceof Zend_Auth_Result)
            $this->_authResult = $result;
        else
            $this->_authResult = null;

        return $this;
    }

    /** @brief  Get the authentication results for this user.
     *  
     *  @return The Zend_Auth_Result representing the authentication
     *          state (null if authentication has not been attempted).
     */
    public function getAuthResult()
    {
        return $this->_authResult;
    }

    /** @brief  Retrieve the authentication state for this user.
     *
     *  @return The authentication state (true | false).
     */
    public function isAuthenticated()
    {
        return ($this->_authResult !== null
                    ? $this->_authResult->isValid()
                    : false);
    }

    /**********************************************
     * Additional authentication related methods
     *
     */

    /** @brief  De-authenticate this user. */
    public function logout()
    {
        Zend_Auth::getInstance()->clearIdentity();

        $this->setAuthResult(false);
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
        $set = $this->getAuthenticator($authType, $credential);
        if ($set !== null)
        {
            foreach ($set as $item)
            {
                $item->delete();
            }
        }

        return $this;
    }

    /*************************************************************************
     * Zend_Tag_Taggable Interface (via Model_Taggable)
     *
     */
    public function getTitle()
    {
        $title = (String)($this->name);

        return $title;
    }

    public function getWeight()
    {
        $weight = $this->getParam('weight');

        if ($weight === null)
        {
            // Best guess depending on what values are set
           $weight = 0;
           if (isset($this->weight))
               $weight = $this->weight;
           else if (isset($this->tagCount))
               $weight = $this->tagCount;
           else if (isset($this->totalItems))
               $weight = $this->totalItems;

            $this->setWeight($weight);
        }

        return (Float)$weight;
    }

    /**********************************************
     * Tag Management related methods
     *
     */

    /** @brief  Given an array of tag rename information, rename tags for the
     *          provided user.
     *  @param  renames     An array of tag rename information:
     *                          { 'oldTagName' => 'newTagName',
     *                            ... }
     *
     *  @return An array of status information, keyed by old tag name:
     *              { 'oldTagName'  => true (success) |
     *                                 String explanation of failure,
     *                 ... }
     */
    public function renameTags(array    $renames)
    {
        $status    = array();
        $tagMapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        foreach ($renames as $oldName => $newName)
        {
            $oldTags = $tagMapper->fetchRelated( array(
                                        'users' => array($this->userId),
                                        'tags'  => array('tag' => $oldName)
                                    ));
            if ( ($oldTags === null) || ($oldTags->count() < 1) )
            {
                $status[$oldName] = 'unused';
                continue;
            }

            $status[$oldName] = $this->renameTag($oldTags[0], $newName);
        }

        $this->updateStatistics();

        return $status;
    }
    
    /** @brief  Rename a single tag for this user.
     *  @param  oldTag      The exsiting/old Model_Tag instance;
     *  @param  newTag      The new tag (string name or Model_Tag instance);
     *
     *  @return true (success) else a failure message (string).
     */
    public function renameTag(Model_Tag     $oldTag,
                                            $newTag)
    {
        // See if there is an exsiting Model_Tag matching 'newTag';
        $tagMapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        if (! $newTag instanceof Model_Tag)
        {
            $newTag = $tagMapper->getModel( array('tag' => $newTag) );
            if ($newTag === null)
                return 'invalid new tag';
        }

        if (! $newTag->isBacked())
        {
            // This is a brand new tag -- save it;
            $newTag = $newTag->save();
        }

        return $this->getMapper()->renameTag($this, $oldTag, $newTag);
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
        if (! $tags instanceof Model_Set_Tag)
        {
            // Convert the incoming array to a Model_Set_tag
            $tagMapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
            $tags      = $tagMapper->fetchBy('tag', $tags);
        }

        Connexions::log("Model_User::deleteTags( %s )", $tags);
        $status = array();
        foreach ($tags as $tag)
        {
            $status[$tag->tag] = $this->deleteTag($tag);
        }

        $this->updateStatistics();

        return $status;
    }

    /** @brief  Delete a single tag for this user.
     *  @param  tag     The Model_Tag instance;
     *
     *  @return true (success) else a failure message (string).
     */
    public function deleteTag(Model_Tag $tag)
    {
        return $this->getMapper()->deleteTag($this, $tag);
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
