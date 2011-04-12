<?php
/** @file
 *
 *  Model for the Activity table.
 */

class Model_Activity extends Model_Base
{
    const   ACTIVITY_SAVE       = 'save';
    const   ACTIVITY_UPDATE     = 'update';
    const   ACTIVITY_DELETE     = 'delete';

    const   ACTIVITY_DEFAULT    = self::ACTIVITY_SAVE;

    /* inferred via classname
    protected   $_mapper    = 'Model_Mapper_Activity'; */

    // The data for this Model
    protected   $_data      = array(
            'activityId'    => null,
            'userId'        => null,
            'objectType'    => '',
            'objectId'      => '',
            'operation'     => self::ACTIVITY_DEFAULT,
            'time'          => '',
            'properties'    => '',
    );

    // Properties not directly backed by our Mapper/DAO
    protected   $_user      = null;
    protected   $_object    = null;

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
        return ( $this->activityId );
    }

    /*************************************************************************
     * Connexions_Model - abstract method implementations
     *
     */

    /** @brief  Get a value of the given field.
     *  @param  name    The field name.
     *
     *  @return The field value (null if invalid).
     */
    public function __get($name)
    {
        switch ($name)
        {
        case 'actor':
        case 'user':        $val = $this->getUser();        break;

        case 'object':      $val = $this->getObject();      break;
        default:            $val = parent::__get($name);    break;
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
        /*
        Connexions::log("Model_Activity::__set(%s, %s) from '%s'",
                        $name,
                        Connexions::varExport($value),
                        Connexions::varExport($this->__get($name)) );
        // */

        switch ($name)
        {
        case 'actor':
        case 'user':
            if (! $value instanceof Model_User)
            {
                throw new Exception("user MUST be a Model_User instance");
            }
            $this->_user = $value;
            $this->userId = $this->_user->getId();
            return;

            break;

        case 'object':
            if (! $value instanceof Connexions_Model)
            {
                throw new Exception("object MUST be a "
                                    .   "Connexions_Model instance");
            }
            $this->_object    = $value;

            // Set 'objectType' based upon this new object
            $this->objectType = $this->objectType($value);

            // Also retrieve the object identifier
            $objectId   = $this->_object->getId();
            if (is_array($objectId))    $objectId = implode(':', $objectId);
            else                        $objectId = (String)$objectId;

            $this->objectId = $objectId;
            return;

            break;

        case 'operation':
            if (! self::validateOperation($value))
            {
                throw new Exception("Model_Activity::__set({$name}, {$value}): "
                                    . "Invalid operation");
            }
            break;
        }

        return parent::__set($name, $value);
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
        $this->_user   = null;
        $this->_object = null;

        return $this;
    }

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        $str = '@'. $this->time  .': '
             . $this->user       .' '
             . $this->operation  .'d '
             . $this->objectType .' '
             . $this->objectId
             . ' ('. $this->getObject() .')';

        return $str;
    }

    /** @brief  Return the referenced object.
     *
     *  @return A Connexions_Model instance (or null if not found).
     */
    public function getObject()
    {
        if ($this->_object === null)
        {
            $this->_object = $this->getMapper()->getObject( $this );
        }

        return $this->_object;
    }

    /** @brief  Return the referenced user.
     *
     *  @return A Model_User instance (or null if not found).
     */
    public function getUser()
    {
        if ($this->_user === null)
        {
            $this->_user = $this->getMapper()->getUser( $this );
        }

        return $this->_user;
    }

    /*************************************************************************
     * Static methods
     *
     */

    /** @brief  Return the object type for the provided object.
     *  @param  object  The desired object.
     *
     *  @return The object type (string).
     */
    public static function objectType($object)
    {
        if (is_object($object))
        {
            $objectType = strtolower( str_replace('Model_', '',
                                                  get_class($object)) );
        }
        else
        {
            $objectType = gettype($object);
        }

        return $objectType;
    }

    /** @brief  Given an authentication type string, check if it's valid.
     *  @param  type    The type string to check.
     *
     *  @return true (valid) or false (invalid)
     */
    public static function validateOperation($type)
    {
        $validity = false;
        switch ($type)
        {
        case self::ACTIVITY_SAVE:
        case self::ACTIVITY_UPDATE:
        case self::ACTIVITY_DELETE:
            $validity = true;
            break;
        }

        /*
        Connexions::log("Model_Activity::validateOperation( %s ): %svalid",
                        $type, ($validity ? '' : 'NOT '));
        // */

        return $validity;
    }
}

