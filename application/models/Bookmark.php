<?php
/** @file
 *
 *  Bookmark / UserItem Domain Model.
 *
 */

class Model_Bookmark extends Model_Base
{
    //protected   $_mapper    = 'Model_BookmarkMapper';

    // The data for this Model
    protected   $_data      = array(
            'name'          => null,
            'description'   => null,
            'rating'        => null,
            'isFavorite'    => null,
            'isPrivate'     => null,
            'taggedOn'      => null,
            'updatedOn'     => null,

            // Other Domain Models (not directly persisted).
            'user'          => null,    // Model_User instance or userId
            'item'          => null,    // Model_Item instance or itemId
            'tags'          => null,    // Model_Tag  array
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
        $id = null;
        if ($this->isBacked())
        {
            $userId = ($this->_data['user'] instanceof Model_User
                        ? $this->user->userId
                        : $this->_data['user']);
            $itemId = ($this->_data['item'] instanceof Model_Item
                        ? $this->item->itemId
                        : $this->_data['item']);

            $id = array( $userId, $itemId );
        }
        
        return ( $id );
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
        case 'user':
            if ( (  $value !== null )             &&
                 (! $value instanceof Model_User) &&
                 (! is_int($value)) )
            {
                throw new Exception('User must be a Model_User instance '
                                    . '('. (is_object($value)
                                                ? get_class($value)
                                                : gettype($value))
                                    . ')');
            }
            break;

        case 'item':
            if ( (  $value !== null )             &&
                 (! $value instanceof Model_Item) &&
                 (! is_int($value)) )
            {
                throw new Exception('Item must be a Model_Item instance '
                                    . '('. (is_object($value)
                                                ? get_class($value)
                                                : gettype($value))
                                    . ')');
            }
            break;

        case 'tags':
            if ( (  $value !== null )             &&
                 (! $value instanceof Connexions_Model_Set) )
            {
                throw new Exception('Tags must be a Connexons_Model_Set or null '
                                    . '('. (is_object($value)
                                                ? get_class($value)
                                                : gettype($value))
                                    . ')');
            }
            break;
        }

        parent::__set($name, $value);
    }

    public function __get($name)
    {
        switch ($name)
        {
        case 'user':
            $val = $this->_data['user'];
            if ( is_int($val) )
            {
                // Load the Model_User instance now.
                $val = $this->getMapper()->getUser( $val );
                $this->user = $val; //$this->_data['user'] = $val;
            }
            break;

        case 'item':
            $val = $this->_data['item'];
            if ( is_int($val) )
            {
                // Load the Model_Item instance now.
                $val = $this->getMapper()->getItem( $val );
                $this->item = $val; //$this->_data['item'] = $val;
            }
            break;

        case 'tags':
            $val = $this->_data['tags'];
            if ( $val === null )
            {
                // Load the Model_Tag array now.
                $val = $this->getMapper()->getTags( $this );
                $this->tags = $val; //$this->_data['tags'] = $val;
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

        // User
        $user = ($deep === self::DEPTH_DEEP
                    ? $this->user
                    : $data['user']);
        if ($user instanceof Model_User)
        {
            if ($deep === self::DEPTH_DEEP)
                $data['user'] = $user->toArray( $deep, $public );
            else
                $data['user'] = $user->userId;
        }

        // Item
        $item = ($deep === self::DEPTH_DEEP
                    ? $this->item
                    : $data['item']);
        if ($item instanceof Model_Item)
        {
            if ($deep === self::DEPTH_DEEP)
                $data['item'] = $item->toArray( $deep, $public );
            else
                $data['item'] = $item->itemId;
        }

        // Tags
        $tags = ($deep === self::DEPTH_DEEP
                    ? $this->tags
                    : $data['tags']);
        if ( ($tags !== null) && ($tags instanceof Model_Set_Tag) )
        {
            // Reduce the tags...
            $reducedTags = array();
            foreach ($tags as $idex => $tag)
            {
                array_push($reducedTags, $tag->toArray(  $deep, $public ));
            }

            $data['tags'] = $reducedTags;
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
        if ($this->_data['user'] instanceof Model_User)
            $this->user = $this->_data['user']->getId();

        if ($thie->_data['item'] instanceof Model_Item)
            $this->item = $this->_data['item']->getId();

        $this->tags = null;

        return $this;
    }
}
