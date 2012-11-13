<?php
/** @file
 *
 *  Bookmark / UserItem Domain Model.
 *
 */

class Model_Bookmark extends Model_Base
{
    /* inferred via classname
    protected   $_mapper    = 'Model_Mapper_Bookmark'; */

    // The data for this Model
    protected   $_data      = array(
            'userId'        => null,
            'itemId'        => null,

            'name'          => null,
            'description'   => null,
            'rating'        => null,
            'isFavorite'    => null,
            'isPrivate'     => null,
            'worldModify'   => null,
            'taggedOn'      => null,
            'updatedOn'     => null,
    );

    // Associated Domain Model instances
    protected   $_user      = null;
    protected   $_item      = null;
    protected   $_tags      = null;

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
        return (array( $this->_data['userId'], $this->_data['itemId'] ));
    }

    /*************************************************************************
     * Connexions_Model overrides
     *
     */

    /** @brief  Save this instancne.
     *  @param  noLog   Should the activity log be bypassed?
     *
     *  Override to update 'updatedOn'
     *
     *  @return The (updated) instance.
     */
    public function save($noLog = false)
    {
        // On save, modify 'updatedOn' to NOW.
        $this->updatedOn = date('Y-m-d H:i:s');

        if ($this->_data['taggedOn'] === null)
        {
            // If 'taggedOn' is not yet set, set it now.
            $this->taggedOn = $this->updatedOn;
        }

        $tags = $this->_tags;
        if ($this->_tags !== null)
        {
            // Also save tags -- this will only save un-backed instances.
            $this->_tags = $this->_tags->save();
        }

        if ($this->isPrivate)
        {
            /* Since activity logs are not privacy protected, and this bookmark
             * is NOW private, do NOT log this activity.
             */
            $noLog = true;
        }

        $bookmark = parent::save($noLog);

        /*
        if ($tags !== null)
        {
            // Invalidate the tags so they will be re-retrieved to reflect
            // updated statistics.
            $tags->invalidate();
        }
        // */

        /*
        Connexions::log("Model_Bookmark::save(): bookmark[ %s ]",
                        $bookmark->debugDump());
        // */

        return $bookmark;
    }

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
        case 'user':
            if ( (  $value !== null )             &&
                 (! $value instanceof Model_User) )
            {
                throw new Exception('User must be a Model_User instance '
                                    . '('. (is_object($value)
                                                ? get_class($value)
                                                : gettype($value))
                                    . ')');
            }

            // Direct set, no further filtering nor validation
            $this->_user = $value;

            if ($this->_user !== null)
            {
                // Ensure that the userId matches
                $this->_data['userId'] = $this->_user->userId;
            }
            break;

        case 'item':
            if ( (  $value !== null )             &&
                 (! $value instanceof Model_Item) )
            {
                throw new Exception('Item must be a Model_Item instance '
                                    . '('. (is_object($value)
                                                ? get_class($value)
                                                : gettype($value))
                                    . ')');
            }

            // Direct set, no further filtering nor validation
            $this->_item = $value;

            if ($this->_item !== null)
            {
                // Ensure that the itemId matches
                $this->_data['itemId'] = $this->_item->itemId;
            }
            break;

        case 'tags':
            if ( (  $value !== null )             &&
                 (! $value instanceof Connexions_Model_Set) )
            {
                throw new Exception('Tags must be a Connexons_Model_Set '
                                    . 'or null '
                                    . '('. (is_object($value)
                                                ? get_class($value)
                                                : gettype($value))
                                    . ')');
            }

            // Direct set, no further filtering nor validation
            $this->_tags = $value;
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
        case 'user':
            $val = $this->_user;
            if ( $val === null )
            {
                // Load the Model_User instance now.
                $val = $this->getMapper()->getUser( $this->_data['userId'] );
                $this->_user = $val; //$this->_data['user'] = $val;
            }
            break;

        case 'item':
            $val = $this->_item;
            if ( $val === null )
            {
                // Load the Model_Item instance now.
                $val = $this->getMapper()->getItem( $this->_data['itemId'] );
                $this->_item = $val; //$this->_data['item'] = $val;
            }
            break;

        case 'tags':
            $val = $this->_tags;
            if ( $val === null )
            {
                // Load the Model_Tag array now.
                $val = $this->getMapper()->getTags( $this );
                $this->_tags = $val; //$this->_data['tags'] = $val;
            }
            break;

        /*
        case 'taggedOn':
        case 'updatedOn':
            // Convert dates to a user-specified locale
            $val = parent::__get($name);
            try
            {
                $date = new DateTime( $val );
                $date->setTimezone(new DateTimeZone('US/Eastern'));

                //$val  = $date->format('Y-m-d H:i:s');
                $val  = $date->format('Y-m-d g:i a');
            }
            catch (Exception $e)
            {
                // Simply use the database date/time string...
            }
            break;
        // */

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

        if ( (! isset($props['deep'])) || ($props['deep'] !== false) )
        {
            // User: Force resolution via '->user' vs '->_user'
            if ($this->user !== null)
                $data['user'] = $this->user->toArray( $props );

            // Item: Force resolution via '->item' vs '->_item'
            if ($this->item !== null)
                $data['item'] = $this->item->toArray( $props );

            // Tags: Force resolution via '->tags' vs '->_tags'
            if ($this->tags !== null)
            {
                // Reduce the tags...
                $reducedTags = array();
                foreach ($this->tags as $idex => $tag)
                {
                    array_push($reducedTags, $tag->toArray(  $props ));
                }

                $data['tags'] = $reducedTags;
            }
        }
        else if ( (! isset($props['public'])) || ($props['public'] !== false) )
        {
            /* Convert "private" data to a public representation
             *  -- don't throw away userId/itemId since the caller may need
             *     them (e.g. Bookmark Update client-side scripts).
             */
            if ($this->user !== null)
            {
                $data['user']   = strval($this->user);
                $data['userId'] = $this->user->userId;
            }
            if ($this->item !== null)
            {
                $data['url']     = $this->item->url;
                $data['urlHash'] = $this->itemHash;
                $data['itemId']  = $this->item->itemId;
            }

            if ($this->tags !== null)
            {
                $reducedTags = array();
                foreach ($this->tags as $idex => $tag)
                {
                    array_push($reducedTags, strval($tag));
                }

                $data['tags'] = $reducedTags;
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
        $this->_user = null;
        $this->_item = null;
        $this->_tags = null;

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

        //if ($this->_credential !== null)
        {
            // Include user and tag information
            $user = $this->user;
            $item = $this->item;
            $tags = $this->tags;

            $str .= sprintf ("%s%-15s == %-15s %s [\n%s%s]\n",
                             str_repeat(' ', $indent + 1),
                             'user',
                             (is_object($user)
                                ? get_class($user)
                                : gettype($user)),
                             ' ',
                             (is_object($user)
                                ? $user->debugDump($indent + 2, true)
                                : ''),
                             str_repeat(' ', $indent + 1));

            $str .= sprintf ("%s%-15s == %-15s %s [ %s ]\n",
                             str_repeat(' ', $indent + 1),
                             'item',
                             (is_object($item)
                                ? get_class($item)
                                : gettype($item)),
                             ' ',
                             (is_object($item)
                                ?  $item->debugDump($indent + 2, true)
                                : ''));

            $str .= sprintf ("%s%-15s == %-15s %s [ %s ]\n",
                             str_repeat(' ', $indent + 1),
                             'tags',
                             (is_object($tags)
                                ? get_class($tags)
                                : gettype($tags)),
                             ' ',
                             (is_object($tags)
                                ? $tags->debugDump($indent + 2, true)
                                : ''));

            if ($leaveOpen !== true)
                $str .= str_repeat(' ', $indent) .'];';
        }

        return $str;
    }

    /**********************************************
     * Access controls
     *
     */

    /** @brief  Is the given Model_User the owner of this bookmark?
     *  @param  user                    The target user;
     *  @param  requireAuthenticated    Require that 'user' is authenticated?
     *                                  [ true ];
     *
     *  @return true | false
     */
    public function isOwner($user, $requireAuthenticated = true)
    {
        return ($user                                                       &&
                ($user instanceof Model_User)                               &&
                $this->user                                                 &&
                ($this->user instanceof Model_User)                         &&
                $user->isSame( $this->user )                                &&
                ( (! $requireAuthenticated) || $user->isAuthenticated() ) );
    }

    /** @brief  Can the identified user perform the specified action on this
     *          bookmark?
     *  @param  action      The desired action (e.g. view, edit, modify);
     *  @param  user        The target user;
     *
     *  @return true | false
     */
    public function allow($action, Model_User $user)
    {
        $isOwner   = $this->isOwner( $user );
        $permitted = $isOwner;

        // The owner can do anything.  All others need further examination
        if ($isOwner === false)
        {
            switch ($action)
            {
            case 'view':
                // Viewable iff 'public'
                if (! $this->isPrivate)
                {
                    $permitted = true;
                }
                break;

            case 'modify':
                /* Modify is the ability to change 'name', 'description',
                 * and/or 'tags' but NOTHING ELSE.
                 *
                 * Modifiable iff 'worldModify'
                 */
                if ($this->worldModify)
                {
                    $permitted = true;
                }
                break;

            case 'edit':
            case 'delete':
                // Any other action is only permitted only for the owner
                break;
            }
        }

        return $permitted;
    }

    /**********************************************
     * Statistics related methods
     *
     */

    /** @brief  Update external-table statistics related to this Bookmark 
     *          instance:
     *              user - totalTags, totalItems
     *              item - userCount, ratingCount, ratingSum
     *
     *  @return $this for a fluent interface
     */
    public function updateStatistics()
    {
        if (! $this->user instanceof Model_User)
            throw new Exception("Missing Model_User");
        if (! $this->item instanceof Model_Item)
            throw new Exception("Missing Model_Item");

        $this->user->updateStatistics( );
        $this->item->updateStatistics( );

        return $this;
    }

}
