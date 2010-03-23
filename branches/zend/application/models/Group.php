<?php
/** @file
 *
 *  Model for the memberGroup table ('group' is a keyword in SQL...)
 */

class Model_Group extends Connexions_Model
                implements  Zend_Tag_Taggable
{
    /*************************************************************************
     * Connexions_Model - static, identity members
     *
     */
    public static   $table  = 'memberGroup';
                              // order 'keys' by most used
    public static   $keys   = array('groupId', 'name');
    public static   $model  = array('groupId'       => 'auto',
                                    'name'          => 'string',

                                    // enum('user', 'item', 'tag')
                                    'groupType'     => 'string',

                                    // enum('owner', 'group')
                                    'controlMembers'=> 'string',
                                    'controlItems'  => 'string',

                                    // enum('private', 'group', 'public')
                                    'visibility'    => 'string',

                                    'canTransfer'   => 'boolean',

                                    // Reference to the User table
                                    'ownerId'       => 'integer'
    );
    /*************************************************************************/

    protected       $_isAuthenticated   = false;

    // Associated model caches
    protected       $_owner             = null; // Model_User
    protected       $_members           = null; // Model_GroupMemberSet
    protected       $_items             = null; // Model_GroupItemSet


    /** @brief  Get a value of the given field.
     *  @param  name    The field name.
     *
     *  Override to allow retrieval of group-related members and items.
     *
     *  @return The field value (or null if invalid field).
     */
    public function __get($name)
    {
        switch ($name)
        {
        case 'owner':   $res =& $this->_owner();        break;
        case 'members': $res =& $this->_members();      break;
        case 'items':   $res =& $this->_items();        break;
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
     *  @return true | false
     */
    public function __set($name, $value)
    {
        $res = false;
        switch ($name)
        {
        case 'owner':   // :TODO: if 'isTransferable', change owner.

        case 'members':
        case 'items':
            // Do NOT allow external replacement of sub-instances.
            break;

        case 'groupType':
            // Valid values: user, item, tag
            $value = strtolower($value);
            switch ($value)
            {
            case 'user':
            case 'item':
            case 'tag':
                $res = parent::__set($name, $value);
                break;

            default:
                $res = false;
            }
            break;

        case 'controlMembers':
        case 'controlItems':
            // Valid values: owner, group
            $value = strtolower($value);
            switch ($value)
            {
            case 'owner':
            case 'group':
                $res = parent::__set($name, $value);
                break;

            default:
                $res = false;
            }
            break;

        case 'visibility':
            // Valid values: private, group, public
            $value = strtolower($value);
            switch ($value)
            {
            case 'private':
            case 'group':
            case 'public':
                $res = parent::__set($name, $value);
                break;

            default:
                $res = false;
            }
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

    /*************************************************************************
     * Connexions_Model overrides
     *
     */

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

        return $weight;
    }

    /*************************************************************************
     * Protected helpers
     *
     */
    protected function _owner()
    {
        if ($this->_owner === null)
        {
            if (isset($this->_record['ownerId']))
            {
                $this->_owner =
                    new Model_User(array($this->_record['ownerId']));
            }
        }

        return $this->_owner;
    }

    protected function _members()
    {
        if ($this->_members === null)
        {
            if (@isset($this->_record['groupId']))
            {
                $this->_members =
                    new Model_GroupMemberSet(array($this->_record['groupId']));
            }
        }

        return $this->_members;
    }

    protected function _items()
    {
        if ($this->_items === null)
        {
            if (@isset($this->_record['groupId']))
            {
                $this->_items =
                    new Model_GroupItemSet(array($this->_record['groupId']));
            }
        }

        return $this->_items;
    }

    /*************************************************************************
     * Static methods
     *
     */

    /** @brief  Given a set of group names, retrieve the identifier for each.
     *  @param  names   The set of names as a comma-separated string or array.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     *  @return An array
     *              { valid:   { <groupName>: <group id>, ... },
     *                invalid: [invalid group names, ...]
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
         *   groupName => groupId
         *
         * This will be used for the list of valid names.
         */
        $valid  = array();
        foreach ($recs as $idex => $row)
        {
            $valid[$row['name']] = $row['groupId'];
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
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     *  @return A new instance (false if no matching group).
     */
    public static function find($id, $db = null)
    {
        //Connexions::log("Model::Group::find: id[ ". print_r($id, true) ." ]");
        return parent::find($id, $db, __CLASS__);
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
            if (! @empty($id['groupId']))
                $instanceId .= $id['groupId'];
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
        Connexions::log("Model_Group::_instanceId: "
                            . "id[ ". print_r($id, true) ." ], "
                            . "instanceId[ {$instanceId} ]");
        // */

        return $instanceId;
    }
}
