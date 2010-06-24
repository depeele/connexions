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
            'ownerId'           => null,

            'controlMembers'    => 'owner',
            'controlItems'      => 'owner',
            'visibility'        => 'private',
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
            $val = $this->_data['members'];
            if ( $val === null )
            {
                // Load the Model_Set_User now.
                $val = $this->getMapper()->getMembers( $this );
                $this->_members = $val; //$this->_data['members'] = $val;
            }
            break;

        case 'items':
            $val = $this->_data['items'];
            if ( $val === null )
            {
                // Load the Connexions_Model_Set now.
                $val = $this->getMapper()->getItems( $this );
                $this->_items = $val; //$this->_data['items'] = $val;
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
    public function toArray($deep   = self::DEPTH_SHALLOW,
                            $public = self::FIELDS_PUBLIC)
    {
        $data = $this->_data;

        if ($deep === self::DEPTH_DEEP)
        {
            // Owner: Force resolution via '->owner' vs '->_owner'
            if ($this->owner !== null)
                $data['owner'] = $this->owner->toArray( $deep, $public );

            // Members: Force resolution via '->members' vs '->_members'
            if ($this->members !== null)
            {
                // Reduce the members...
                $reducedMembers = array();
                foreach ($this->members as $idex => $member)
                {
                    array_push($reducedMembers,
                               $member->toArray(  $deep, $public ));
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
                               $item->toArray(  $deep, $public ));
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
}