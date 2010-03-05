<?php
/** @file
 *
 *  Model for the User table.
 *
 *  Note: Since we pull tag sets with varying weight measuremennts so, at least
 *        for now, DO NOT change the base class from Connexions_Model to
 *        Connexions_Model_Cached to make it cacheable.
 */

//class Model_User extends Connexions_Model_Cached
class Model_User extends Connexions_Model
                implements  Zend_Tag_Taggable
{
    /*************************************************************************
     * Connexions_Model - static, identity members
     *
     */
    public static   $table  = 'user';
                              // order 'keys' by most used
    public static   $keys   = array('userId', 'name');
    public static   $model  = array('userId'        => 'auto',
                                    'name'          => 'string',
                                    'password'      => 'string',

                                    'fullName'      => 'string',
                                    'email'         => 'string',
                                    'apiKey'        => 'string',
                                    'pictureUrl'    => 'string',
                                    'profile'       => 'string',
                                    'networkShared' => 'boolean',
                                    'lastVisit'     => 'datetime',
                                    'lastVisitFor'  => 'datetime',
                                    'totalTags'     => 'integer',
                                    'totalItems'    => 'integer'
    );
    /*************************************************************************/

    protected       $_isAuthenticated   = false;

    protected       $_tags              = null; // user-related tags

    /** @brief  Get a value of the given field.
     *  @param  name    The field name.
     *
     *  Override to allow retrieval of user-related tags.
     *
     *  @return The field value (or null if invalid field).
     */
    public function __get($name)
    {
        switch ($name)
        {
        case 'tags':    $res =& $this->_tags();         break;
        default:        $res =  parent::__get($name);   break;
        }

        if (! empty($field))
            $res = $res->{$field};

        return $res;
    }

    /** @brief  Set a value in this record and mark it dirty.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  Override to properly encode 'password' when set.
     *
     *  @return true | false
     */
    public function __set($name, $value)
    {
        $res = false;
        switch ($name)
        {
        case 'tags':
            // Do NOT allow external replacement of sub-instances.
            break;

        case 'password':
            $value = md5($value);
            $res   =  parent::__set($name, $value);
            break;

        default:
            $res =  parent::__set($name, $value);
            break;
        }

        return $res;
    }

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        if ($this->isValid() && (! @empty($this->_record['name'])))
            return $this->_record['name'];

        return parent::__toString();
    }

    /** @brief  Is this user authenticated?
     *
     *  @return true | false
     */
    public function isAuthenticated()
    {
        return $this->_isAuthenticated;
    }

    /** @brief  Set the authentication state.
     *  @param  isAuthenticated     true | false
     *
     *  @return Model_User to provide a fluent interface.
     */
    public function setAuthenticated($isAuthenticated   = true)
    {
        $this->_isAuthenticated = $isAuthenticated;
        
        return $this;
    }

    /** @brief  Validate the user's password.
     *  @param  pass        The password to validate.
     *
     *  @return true | false
     */
    public function authenticate($pass)
    {
        $this->_isAuthenticated = false;

        if ($this->isValid())
        {
            $checkPass = md5($pass);
            if ($this->_record['password'] == $checkPass)
            {
                $this->_isAuthenticated = true;
                return true;
            }

            $this->_error = 'Invalid password.';
        }

        return false;
    }

    /** @brief  Set the weighting.
     *  @param  by      Weight by ('tag', 'item', 'userItem').
     *  @param  tagIds  If provided, an array of tagIds to limit the query.
     *
     *  @return $this
     */
    public function weightBy($by, $tagIds = null)
    {
        $cols = array();

        switch (strtolower($by))
        {
        case 'tag':
            $cols['weight'] = 'COUNT(DISTINCT uti.tagId)';
            break;

        case 'item':
            $cols['weight'] = 'COUNT(DISTINCT uti.itemId)';
            break;

        case 'useritem':
        default:            // Default to 'userItem'
            $cols['weight'] = 'COUNT(DISTINCT uti.userId,uti.itemId)';
            break;
        }

        $select = $this->_db->select()
                            ->from(array('u'  => $this->_table))
                            ->join(array('uti'=> 'userTagItem'),
                                         '(u.userId=uti.userId)',
                                         '')
                            ->group('u.userId')
                            ->where('u.userId=?', $this->userId)
                            ->columns($cols);

        if (! @empty($tagIds))
        {
            // Tag Restrictions -- required 'userTagItem'
            $select->where('uti.tagId IN (?)', $tagIds)
                   ->having('COUNT(DISTINCT uti.tagId)='.count($tagIds));
        }
        /*
        Connexions::log("Model_User::weightBy({$by}): "
                            . "sql[ ". $select->assemble() ." ]");
        // */

        $recs   = $select->query()->fetchAll();

        if (@count($recs) == 1)
        {
            /*
            Connexions::log(
                    sprintf("Model_User::weightBy: %d record [ %s ]",
                            count($recs), print_r($recs[0], true)) );
            // */

            // Include this 'weight' in our record data
            $this->_record['weight'] = $recs[0]['weight'];
        }

        return $this;
    }

    /*************************************************************************
     * Connexions_Model overrides
     *
     */

    /** @brief  Validate the given field.
     *  @param  record  The record to validate within.
     *  @param  field   The field to validate.
     *
     *  Note: This is overridden to generate any missing, auto-generated data
     *        (e.g. apiKey).
     *
     *  @return true | false
     */
    protected function _validateField(&$record, $field)
    {
        //Connexions::log("Model_User::_validateField({$field})");

        if ($field === 'apiKey')
        {
            if (! @isset($record[$field]))
            {
                $record[$field] = self::genApiKey();
            }
        }

        return parent::_validateField($record, $field);
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
        if (@isset($this->weight))
            $weight = (Float)($this->weight);
        else if (@isset($this->tagCount))
            $weight = (Float)($this->tagCount);
        else
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
            if (@isset($this->_record['userId']))
            {
                $this->_tags =
                    new Model_TagSet(array($this->_record['userId']));
            }
        }

        return $this->_tags;
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
        $nChars   = count($chars);
        $key      = '';

        list($ms) = explode(' ', microtime());
        srand($ms * 100000);

        for ($idex = 0; $idex < $len; $idex++)
        {
            $key .= $chars[ rand(0, $nChars) ];
        }

        return $key;
    }

    /** @brief  Given a set of user names, retrieve the identifier for each.
     *  @param  names   The set of names as a comma-separated string or array.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     *  @return An array
     *              { valid:   { <userName>: <user id>, ... },
     *                invalid: [invalid user names, ...]
     *              }
     */
    public static function ids($names, $db = null)
    {
        if (@empty($names))
            return null;

        if (! @is_array($names))
            $names = preg_split('/\s*,\s*/', $names);

        if ($db === null)
            $db = Connexions::getDb();
        $select = $db->select()
                     ->from(self::$table)
                     ->where('name IN (?)', $names);
        $recs   = $select->query()->fetchAll();

        /* Convert the returned array of records to a simple array of
         *   userName => userId
         *
         * This will be used for the list of valid names.
         */
        $valid  = array();
        foreach ($recs as $idex => $row)
        {
            $valid[$row['name']] = $row['userId'];
        }

        $invalid = array();
        if (count($valid) < count($names))
        {
            // Include invalid entries for those names that are invalid
            foreach ($names as $name)
            {
                if (! @isset($valid[$name]))
                    $invalid[] = $name;
            }
        }

        return array('valid' => $valid, 'invalid' => $invalid);
    }

    /*************************************************************************
     * Connexions_Model - abstract static method implementations
     *
     */

    /** @brief  Retrieve all records and return an array of instances.
     *  @param  id      The record identifier.
     *
     *  @return A new instance (false if no matching user).
     */
    public static function find($id)
    {
        //Connexions::log("Model::User::find: id[ ". print_r($id, true) ." ]");
        return parent::find(__CLASS__, $id);
    }

    /*************************************************************************
     * Connexions_Model_Cached - abstract static method implementations
     *
     */

    /** @brief  Given a record identifier, generate an unique instance
     *          identifier.
     *  @param  id      The record identifier.
     *
     *  @return A unique instance identifier string (null if invalid).
     */
    protected static function _instanceId($id)
    {
        $instanceId = __CLASS__ .'_';
        if (@is_array($id))
        {
            if (! @empty($id['userId']))
                $instanceId .= $id['userId'];
            else if (! @empty($id['name']))
                $instanceId .= $id['name'];
            else
            {
                // INVALID
                $instanceId = null;
            }
        }
        else if (@is_string($id))
            $instanceId .= $id;
        else
        {
            // INVALID
            $instanceId = null;
        }

        /*
        Connexions::log("Model_User::_instanceId: "
                            . "id[ ". print_r($id, true) ." ], "
                            . "instanceId[ {$instanceId} ]");
        // */

        return $instanceId;
    }
}
