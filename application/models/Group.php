<?php
/** @file
 *
 *  Group Domain Model.
 *
 */

class Model_Group extends Model_Base
{
    /* inferred via classname
    protected   $_mapper    = 'Model_Mapper_Group'; */

    // The data for this Model
    protected   $_data      = array(
            'groupId'           => null,
            'name'              => null,
            'groupType'         => 'tag',       // user    | item  | tag
            'ownerId'           => null,

            'controlMembers'    => 'owner',     // owner   | group
            'controlItems'      => 'owner',     // owner   | group
            'visibility'        => 'private',   // private | group | public
            'canTransfer'       => false,
    );

    protected   $_owner     = null;
    protected   $_members   = null;
    protected   $_items     = null;

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
        return ( $this->groupId );
    }

    /*************************************************************************
     * Connexions_Model overrides
     *
     */

    /** @brief  Set the value of the given field.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  @return $this for a fluent interface.
     */
    public function __set($name, $value)
    {
        /* Allow fields that reference an external model to be set to either
         * the instance or the identifier needed to instantiate the model
         * later.
         */
        switch ($name)
        {
        case 'owner':
            if ( (  $value !== null )             &&
                 (! $value instanceof Model_User) )
            {
                throw new Exception('Owner must be a Model_User instance '
                                    . '('. (is_object($value)
                                                ? get_class($value)
                                                : gettype($value))
                                    . ')');
            }

            // Direct set, no further filtering or validation
            $this->_owner = $value;
            break;

        case 'members':
            if ( (  $value !== null )             &&
                 (! $value instanceof Model_Set_User) )
            {
                throw new Exception('Members must be a Model_Set_User or null '
                                    . '('. (is_object($value)
                                                ? get_class($value)
                                                : gettype($value))
                                    . ')');
            }

            // Direct set, no further filtering or validation
            $this->_members = $value;
            break;

        case 'items':
            // (! $value instanceof Model_Set_User) )
            if ( (  $value !== null )             &&
                 //(! $value instanceof Connexions_Model_Set) )
                 (get_class($value) !==
                                'Model_Set_'. ucfirst($this->groupType)) )
            {
                throw new Exception('Items must be a Connexions_Model_Set '
                                    . 'or null '
                                    . '('. (is_object($value)
                                                ? get_class($value)
                                                : gettype($value))
                                    . ')');
            }

            /* :XXX: Ensure that $value is the proper TYPE of Model_Set
             *       based upon 'groupType' (user, item, tag)
             */

            // Direct set, no further filtering or validation
            $this->_items = $value;
            break;

        default:
            parent::__set($name, $value);
            break;
        }

        return $this;
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
        case 'owner':
            $val = $this->_owner;
            if ( $val === null )
            {
                // Load the Model_User instance now.
                $val = $this->getMapper()->getOwner( $this->_data['ownerId'] );
                $this->_owner = $val; //$this->_data['owner'] = $val;
            }
            break;

        case 'members':
            $val = $this->_members;
            if ( $val === null )
            {
                // Load the Model_Set_User now.
                $val = $this->getMembers( );
                $this->_members = $val; //$this->_data['members'] = $val;
            }
            break;

        case 'items':
            $val = $this->_items;
            if ( $val === null )
            {
                // Load the Connexions_Model_Set now.
                $val = $this->getItems( );
                $this->_items = $val; //$this->_data['items'] = $val;
            }
            break;

        default:
            $val = parent::__get($name);
            break;
        }

        return $val;
    }

    /** @brief  Retrieve the set of members for this group.
     *  @param  order   Optional ORDER clause (string, array);
     *  @param  count   Optional LIMIT count;
     *  @param  offset  Optional LIMIT offset;
     *
     *  @return A Model_Set_User instance.
     */
    public function getMembers($order   = null,
                               $count   = null,
                               $offset  = null)
    {
        return $this->_getMembers( $order, $count, $offset );
    }

    /** @brief  Retrieve the set of items for this group.
     *  @param  order   Optional ORDER clause (string, array);
     *  @param  count   Optional LIMIT count;
     *  @param  offset  Optional LIMIT offset;
     *
     *  @return A Model_Set_(User|Tag|Item|Bookmark) instance.
     */
    public function getItems($order     = null,
                             $count     = null,
                             $offset    = null)
    {
        return $this->getMapper()->getItems( $this, $order, $count, $offset );
    }

    /**********************************************
     * Visibility and Control checks
     *
     */

    /** @brief  Is the given user a member of this group?
     *  @param  user    The user to check.
     *
     *  @return true | false
     */
    public function isMember(Model_User $user)
    {
        $members = $this->_getMembers(null,     // order
                                      null,     // count
                                      null,     // offset
                                      true);    // noAuth

        return ( ($members !== null)                  &&
                 //($members instanceof Model_User_Set) &&
                 $members->in_array($user) );
    }

    /** @brief  Is the given Model instance an item in this group?
     *  @param  item    The item to check.
     *
     *  @return true | false
     */
    public function isItem(Connexions_Model $item)
    {
        if (get_class($item) !== 'Model_'. ucfirst($this->groupType))
        {
            return false;
        }

        $items = $this->_getItems(null,     // order
                                  null,     // count
                                  null,     // offset
                                  true);    // noAuth

        return ( ($items !== null)                                       &&
                 // /*
                 (get_class($items) ===
                                'Model_Set_'. ucfirst($this->groupType)) &&
                 // */
                 $items->in_array($item) );
    }

    /** @brief  Is the provided user permitted to view this group?
     *  @param  user    The Model_User instance to check.
     *
     *  @return true | false
     */
    public function canView(Model_User $user)
    {
        $res = false;

        switch ($this->visibility)
        {
        case 'group':
            // 'user' MUST be 'owner' OR in the set of members
            if ($this->ownerId == $user->getId())
            {
                $res = true;
            }
            else
            {
                $members = $this->_getMembers(null,     // order
                                              null,     // count
                                              null,     // offset
                                              true);    // noAuth
                if ($members !== null)
                {
                    $res = $members->in_array( $user );
                }
            }
            break;

        case 'public':
            // All users.
            $res = true;
            break;

        case 'private':
        default:
            // 'user' MUST be the owner
            $res = ($this->ownerId == $user->getId());
            break;
        }

        return $res;
    }

    /** @brief  Is the provided user permitted to add/remove items to this
     *          group?
     *  @param  user    The Model_User instance to check.
     *
     *  @return true | false
     */
    public function canControlItems(Model_User $user)
    {
        $res = false;
        switch ($this->controlItems)
        {
        case 'group':
            // 'user' MUST be in the set of members
            if ($this->ownerId == $user->getId())
            {
                $res = true;
            }
            else
            {
                $members = $this->_getMembers(null,     // order
                                              null,     // count
                                              null,     // offset
                                              true);    // noAuth
                if ($members !== null)
                {
                    $res = $members->in_array( $user );
                }
            }
            break;

        case 'owner':
        default:
            // 'user' MUST be the owner
            $res = ($this->ownerId == $user->getId());
            break;
        }

        return $res;
    }

    /** @brief  Is the provided user permitted to add/remove items to this
     *          group?
     *  @param  user    The Model_User instance to check.
     *
     *  @return true | false
     */
    public function canControlMembers(Model_User $user)
    {
        $res = false;
        switch ($this->controlMembers)
        {
        case 'group':
            // 'user' MUST be in the set of members
            if ($this->ownerId == $user->getId())
            {
                $res = true;
            }
            else
            {
                $members = $this->_getMembers(null,     // order
                                              null,     // count
                                              null,     // offset
                                              true);    // noAuth
                if ($members !== null)
                {
                    $res = $members->in_array( $user );
                }
            }
            break;

        case 'owner':
        default:
            // 'user' MUST be the owner
            $res = ($this->ownerId == $user->getId());
            break;
        }

        return $res;
    }

    /**********************************************
     * Member management
     *
     */

    /** @brief  Add the given user to this group.
     *  @param  user    The Model_User instance to add.
     *
     *  @return $this for a fluent interface.
     */
    public function addMember(Model_User    $user)
    {
        $res = $this->getMapper()->addMember( $this, $user );

        // Force the re-caching of the members
        $this->_members = null;

        return $res;
    }

    /** @brief  Remove the given user from this group.
     *  @param  user    The Model_User instance to remove.
     *
     *  @return $this for a fluent interface.
     */
    public function removeMember(Model_User $user)
    {
        $res = $this->getMapper()->removeMember( $this, $user );

        // Force the re-caching of the members
        $this->_members = null;

        return $res;
    }

    /**********************************************
     * Item management
     *
     */

    /** @brief  Add the given item to this group.
     *  @param  item    The item to add.
     *
     *  @return $this for a fluent interface.
     */
    public function addItem(Connexions_Model $item)
    {
        $res = $this->getMapper()->addItem( $this, $item );

        // Force the re-caching of the items
        $this->_items = null;

        return $res;
    }

    /** @brief  Remove the given item from this group.
     *  @param  item    The item to remove.
     *
     *  @return $this for a fluent interface.
     */
    public function removeItem(Connexions_Model $item)
    {
        $res = $this->getMapper()->removeItem( $this, $item );

        // Force the re-caching of the items
        $this->_items = null;

        return $res;
    }

    /**********************************************
     * Conversions and caching
     *
     */

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

        if ($props['deep'] !== false)
        {
            // Owner: Force resolution via '->owner' vs '->_owner'
            if ($this->owner !== null)
                $data['owner'] = $this->owner->toArray( $props );

            // Members: Force resolution via '->members' vs '->_members'
            if ($this->members !== null)
            {
                // Reduce the members...
                $reducedMembers = array();
                foreach ($this->members as $idex => $member)
                {
                    array_push($reducedMembers,
                               $member->toArray(  $props ));
                }

                $data['members'] = $reducedMembers;
            }

            // Items: Force resolution via '->items' vs '->_items'
            if ($this->items !== null)
            {
                // Reduce the items...
                $reducedItems = array();
                foreach ($this->items as $idex => $item)
                {
                    array_push($reducedItems,
                               $item->toArray(  $props ));
                }

                $data['items'] = $reducedItems;
            }
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

        return parent::invalidate();
    }

    /** @brief  Invalidate our internal cache of sub-instances.
     *
     *  @return $this for a fluent interface
     */
    public function invalidateCache()
    {
        $this->_owner   = null;
        $this->_members = null;
        $this->_items   = null;

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

        // Include owner, member, and items information
        $owner   = $this->owner;
        $members = $this->members;
        $items   = $this->items;

        $str .= sprintf ("%s%-15s == %-15s %s [\n%s%s]\n",
                         str_repeat(' ', $indent + 1),
                         'owner',
                         (is_object($owner)
                            ? get_class($owner)
                            : gettype($owner)),
                         ' ',
                         (is_object($owner)
                            ? $owner->debugDump($indent + 2, true)
                            : ''),
                         str_repeat(' ', $indent + 1));

        $str .= sprintf ("%s%-15s == %-15s %s [ %s ]\n",
                         str_repeat(' ', $indent + 1),
                         'members',
                         (is_object($members)
                            ? get_class($members)
                            : gettype($members)),
                         ' ',
                         (is_object($members)
                            ? $members->debugDump($indent + 2, true)
                            : ''));

        $str .= sprintf ("%s%-15s == %-15s %s [ %s ]\n",
                         str_repeat(' ', $indent + 1),
                         'items',
                         (is_object($items)
                            ? get_class($items)
                            : gettype($items)),
                         ' ',
                         (is_object($items)
                            ? $items->debugDump($indent + 2, true)
                            : ''));

        if ($leaveOpen !== true)
            $str .= str_repeat(' ', $indent) .'];';

        return $str;
    }

    /*********************************************************************
     * Protected helpers
     *
     */

    /** @brief  Retrieve the set of members for this group.
     *  @param  order   Optional ORDER clause (string, array);
     *  @param  count   Optional LIMIT count;
     *  @param  offset  Optional LIMIT offset;
     *  @param  noAuth  If 'true', do NOT perform an visibility/authentication
     *                  checks.
     *
     *  @return A Model_Set_User instance.
     */
    protected function _getMembers($order   = null,
                                   $count   = null,
                                   $offset  = null,
                                   $noAuth  = false)
    {
        return $this->getMapper()->getMembers( $this, $order, $count, $offset,
                                               $noAuth );
    }

    /** @brief  Retrieve the set of items for this group.
     *  @param  order   Optional ORDER clause (string, array);
     *  @param  count   Optional LIMIT count;
     *  @param  offset  Optional LIMIT offset;
     *  @param  noAuth  If 'true', do NOT perform an visibility/authentication
     *                  checks.
     *
     *  @return A Model_Set_(User|Tag|Item|Bookmark) instance.
     */
    protected function _getItems($order   = null,
                                 $count   = null,
                                 $offset  = null,
                                 $noAuth  = false)
    {
        return $this->getMapper()->getItems( $this, $order, $count, $offset,
                                             $noAuth );
    }
}
