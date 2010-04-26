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
            'groupType'         => 'tag',
            'controlMembers'    => 'owner',
            'controlItems'      => 'owner',
            'visibility'        => 'private',
            'canTransfer'       => false,

            // Other Domain Models (not directly persisted).
            'owner'             => null, // Model_User instance or userId
            'members'           => null, // Model_Set_User instance
            'items'             => null, // Model_Set_(User|Item|Tag|Bookmark)
                                         //  instance
    );

    /*************************************************************************
     * Connexions_Model abstract method implementations
     *
     */

    /** @brief  Retrieve the unique identifier for this instance.  This MAY 
     *          return an array of identifiers as key/value pairs.
     *
     *  This MUST return null if the model is not currently backed.
     *
     *  @return The unique identifier.
     */
    public function getId()
    {
        return ( $this->isBacked()
                    ? $this->groupId
                    : null );
    }

    /*************************************************************************
     * Connexions_Model overrides
     *
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
                 (! $value instanceof Model_User) &&
                 (! is_int($value)) )
            {
                throw new Exception('Owner must be a Model_User instance '
                                    . '('. (is_object($value)
                                                ? get_class($value)
                                                : gettype($value))
                                    . ')');
            }

            // Direct set, no further filtering or validation
            $this->_data[$name] = $value;
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
            $this->_data[$name] = $value;
            break;

        case 'items':
            if ( (  $value !== null )             &&
                 (! $value instanceof Connexions_Model_Set) )
            {
                throw new Exception('Items must be a Connexions_Model_Set '
                                    . 'or null '
                                    . '('. (is_object($value)
                                                ? get_class($value)
                                                : gettype($value))
                                    . ')');
            }

            // Direct set, no further filtering or validation
            $this->_data[$name] = $value;
            break;

        default:
            parent::__set($name, $value);
            break;
        }

        return $this;
    }

    public function __get($name)
    {
        switch ($name)
        {
        case 'owner':
            $val = $this->_data['owner'];
            if ( is_int($val) )
            {
                // Load the Model_User instance now.
                $val = $this->getMapper()->getOwner( $val );
                $this->owner = $val; //$this->_data['owner'] = $val;
            }
            break;

        case 'members':
            $val = $this->_data['members'];
            if ( $val === null )
            {
                // Load the Model_Set_User now.
                $val = $this->getMapper()->getMembers( $this );
                $this->members = $val; //$this->_data['members'] = $val;
            }
            break;

        case 'items':
            $val = $this->_data['items'];
            if ( $val === null )
            {
                // Load the Connexions_Model_Set now.
                $val = $this->getMapper()->getItems( $this );
                $this->items = $val; //$this->_data['items'] = $val;
            }
            break;

        default:
            $val = parent::__get($name);
            break;
        }

        return $val;
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

        // Owner
        $owner = ($deep === self::DEPTH_DEEP
                    ? $this->owner
                    : $data['owner']);
        if ($owner instanceof Model_Owner)
        {
            if ($deep === self::DEPTH_DEEP)
                $data['owner'] = $owner->toArray( $deep, $public );
            else
                $data['owner'] = $owner->userId;
        }

        // Members
        $members = ($deep === self::DEPTH_DEEP
                        ? $this->members
                        : $data['members']);
        if ( ($members !== null) && ($members instanceof Model_Set_User) )
        {
            // Reduce the members...
            $reducedMembers = array();
            foreach ($members as $idex => $tag)
            {
                array_push($reducedMembers, $tag->toArray(  $deep, $public ));
            }

            $data['members'] = $reducedMembers;
        }

        // Items
        $items = ($deep === self::DEPTH_DEEP
                    ? $this->items
                    : $data['items']);
        if ( ($items !== null) && ($items instanceof Connexions_Model_Set) )
        {
            // Reduce the items...
            $reducedItems = array();
            foreach ($items as $idex => $tag)
            {
                array_push($reducedItems, $tag->toArray(  $deep, $public ));
            }

            $data['items'] = $reducedItems;
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
        if ($this->_data['owner'] instanceof Model_User)
            $this->user = $this->_data['owner']->getId();

        $this->members = null;
        $this->items   = null;

        return $this;
    }
}
