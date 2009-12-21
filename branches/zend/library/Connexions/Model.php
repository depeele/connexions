<?php
/** @file
 *
 *  The base class for Connexions Database Table Models.
 */

abstract class Connexions_Model
{
    /* The following MUST be made available via the following, abstract static
     * methods:
     *  table       The name of the database table;
     *  keys        An array of key names (each MUST be represented in 'model');
     *  model       An array of key/type pairs defining the table model where
     *              type MUST be lower-case and may be and of the following
     *              values:
     *                  ('numeric', 'string', 'integer', 'auto')
     */
    abstract public static function getTable();
    abstract public static function getKeys();
    abstract public static function getModel();

    /*************************************************************************/
    protected   $_id        = null;     // The record id

    protected   $_isBacked  = false;    /* Is there a record backing this (i.e.
                                         * was it pulled from the database or
                                         * constructed without a matching
                                         * record)
                                         */
    protected   $_isDirty   = false;    /* Have any field values been changed
                                         * since retrieval?  Not, if _isBacked
                                         * is false, this should ALWAYS be
                                         * true.
                                         */

    protected   $_isValid   = false;    // Are all fields valid?

    protected   $_error     = null;     /* If there has been an error, this
                                         * will contain the error message
                                         * string.
                                         */

    protected   $_db        = null;     // Our database handle
    protected   $_record    = null;     /* The raw data of this item -- if
                                         * _isBacked is true, this will be a
                                         * copy of the database record.
                                         */


    /** @brief  Create a new instance.
     *  @param  id      The record identifier.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     */
    public function __construct($id, $db = null)
    {
        /* Normalize the field types to lower-case
        $model =& $this->getModel();
        foreach ($model as $name => &$type)
        {
            $type = strtolower($type);
        }
         */
        $this->setId($id, $db);
    }

    /** @brief  Set the id for this model/record.  This will cause an overall
     *          reset of this instance, (re)retrieving the data.
     *  @param  id      The record identifier.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     * @return  Connexions_Model to provide a fluent interface.
     */
    public function setId($id, $db = null)
    {
        // (Re)set our state
        $this->_id        = null;

        $this->_isBacked  = false;
        $this->_isDirty   = false;
        $this->_isValid   = false;
        $this->_error     = null;
        $this->_record    = null;

        if ($db !== null)
            $this->_db    = $db;

        if ($id === null)
            return $this;

        // (Re)retrieve our data
        if (@is_array($id))
        {
            // An incoming record
            $this->_record  = $id;

            /* Validate this incoming data.
             *
             * We consider this record valid iff all keys (except for those
             * marked as 'auto') have values consistent with their field type.
             */
            $this->_isValid = $this->_validate();
        }
        else
        {
            // Does the incoming 'id' match any of the table keys?
            $this->_id = $id;
            $rec       =  null;
            $firstKey  =  null;
            $select    =  'SELECT * FROM '. $this->getTable() .' WHERE ';

            $model    =& $this->getModel();
            foreach ($this->getKeys() as $key)
            {
                $type  = $model[$key];
                $where = null;
                switch ($type)
                {
                case 'auto':
                case 'integer':
                case 'numeric':
                    if (! @is_numeric($id))
                        continue;

                    if ($firstKey === null)
                        $firstKey = $key;

                    $where = $key .'=?';
                    break;

                case 'string':
                default:
                    if (! @is_string($id))
                        continue;

                    if ($firstKey === null)
                        $firstKey = $key;

                    $where = $key .'=?';
                    break;
                }

                if (@empty($where))
                    continue;

                // Attempt to retrieve a matching record.
                try
                {
                    $rec = $this->_db()->fetchRow($select . $where, array($id));
                }
                catch (Exception $e)
                {
                    $rec = null;
                }

                if (! @empty($rec))
                    break;
            }

            if (@is_array($rec))
            {
                // We have successfully (re)retrieved the record data.
                $this->_record   = $rec;
                $this->_isBacked = true;
                $this->_isValid  = true;
            }
            else
            {
                $this->_error   = 'No record matching "'. $id .'"';
            }
        }

        return $this;
    }

    /** @brief  Is this instance backed by an existing, matching database
     *          record?
     *  
     *  @return true | false
     */
    public function isBacked()
    {
        return $this->_isBacked;
    }

    /** @brief  Have field values been changed since retrieval (or is this
     *          instance not backed by a database record?
     *
     *  @return true | false
     */
    public function isDirty()
    {
        return ($this->_isBacked ? $this->_isDirty : true);
    }

    /** @brief  Are all fields of this instance valid?
     *  
     *  @return true | false
     */
    public function isValid()
    {
        return $this->_isValid;
    }

    public function getError()
    {
        return $this->_error;
    }

    /** @brief  Set a value in this record and mark it dirty.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  @return true | false
     */
    public function __set($name, $value)
    {
        $model =& $this->getModel();
        if (! @isset($model[$name]))
        {
            // Invalid field
            return false;
        }

        if ($model[$name] === 'auto')
        {
            // Modification of an 'auto' generated key field is NOT permitted
            return false;
        }

        if (! @is_array($this->_record))
            $this->_record = array();

        if ( (! @isset($this->_record[$name])) ||
             ($this->_record[$name] != $value) )
        {
            $this->_isDirty       = true;
            $this->_record[$name] = $value;
        }

        return true;
    }

    /** @brief  Get a value of the given field.
     *  @param  name    The field name.
     *
     *  @return The field value (or null if invalid field).
     */
    public function __get($name)
    {
        if ( (! @is_array($this->_record)) ||
             (! @isset($this->_record[$name])) )
        {
            // Invalid or unset field
            return null;
        }

        return $this->_record[$name];
    }

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        return (String)($this->_id);
    }

    /** @brief  If this record is dirty, save it to the database.
     *
     *  @return true | false
     */
    public function save()
    {
        if ($this->_isDirty !== true)
            return true;

        // Before we actually save, do a final validation
        $this->_isValid = $this->_validate();
        if ($this->_isValid !== true)
            return false;

        // store the new data for this record
       $res = false;
        if ($this->_isBacked === true)
        {
            // This is an existing record that we need to update

            /* Generate a where clause comprised of the primary/first key
             * included in this record
             *
             * Note: We only use the primary/first key to allow any other key
             *       to be updates.
             */
            $where  =  array();
            foreach ($this->getKeys() as $field)
            {
                if (@isset($this->_record[$field]))
                {
                    array_push($where, $field.'='.$this->_record[$field]);
                    break;
                }
            }

            if ($this->_db()->update($this->getTable(),
                                     $this->_record,
                                     $where) === 1)
            {
                $res = true;
            }
        }
        else
        {
            // This is a new record that we need to insert
            if ( $this->_db()->insert($this->getTable(),
                                      $this->_record) === 1)
            {
                $res = true;

                // Now, retrieve the full record that we've just inserted.
                $id = $this->_db()->lastInsertId($this->getTable());

                $this->setId($id);
            }
        }

        return $res;
    }

    /** @brief  If this record is backed, delete it from the database.
     *
     *  @return true | false
     */
    public function delete()
    {
       $res = false;
        if ($this->_isBacked === true)
        {
            /* Generate a where clause comprised of ALL the keys of this
             * record.
             *
             * Note: We COULD just pass the entire record as the where clause,
             *       but then this would negate any advantage gained by having
             *       indexed keys.
             */
            $where  =  array();
            foreach ($this->getKeys() as $field)
            {
                if (! @isset($this->_record[$field]))
                {
                    throw(new Exception("*** Cannot delete record: ".
                                            "Missing key [ ". $field ." ]"));
                }

                $where[$field] = $this->_record[$field];
            }

            if (empty($where))
            {
                throw(new Exception("*** Don't delete all records!!"));
            }

            if ($this->_db()->delete($this->getTable(), $where) )
            {
                $res = true;
            }
        }

        return $res;
    }

    /** @brief  Invalidate the current instance.
     *  @param  error   If provided, set an error message.
     *
     *  Mark this record as invalid.
     */
    public function invalidate($error = null)
    {
        if (! @empty($error))
            $this->_error = $error;

        $this->_isValid = false;
    }

    public function toArray()
    {
        return $this->_record;
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Retrieve a valid database handle.
     *
     *  @return The database handle.
     */
    protected function _db()
    {
        if ($this->_db === null)
            $this->_db = Connexions::getDb();

        return $this->_db;
    }

    /** @brief  Ensure that all keys, save those marked as 'auto', have a value
     *          consistent with their field type.
     *  @param  field   The field to validate (null == all).
     *
     *  This helper is used to set _isValid.
     *
     *  @return true | false
     */
    protected function _validate($field = null)
    {
        $keys  =& $this->getKeys();
        $model =& $this->getModel();

        if ($field !== null)
        {
            // Validate the given field
            $isValid = $this->_validateField($field, $keys, $model);
        }
        else
        {
            // Validate all fields -- assume it will be valid
            $isValid = true;
            foreach ($model as $name => $type)
            {
                if (! $this->_validateField($name, $keys, $model))
                {
                    $isValid = false;
                    break;
                }
            }
        }

        return $isValid;
    }

    /** @brief  Validate the given field using the provided keys and model.
     *  @param  field   The field to validate.
     *  @param  keys    The keys  of the underlying database.
     *  @param  model   The model of the underlying database.
     *
     *  @return true | false
     */
    protected function _validateField($field, &$keys, &$model)
    {
        $isValid = true;

        if (! @isset($this->_record[$field]))
        {
            /* This field has no value.  If it is key field that is NOT 'auto',
             * consider it invalid.  Otherwise, allow it.
             */
            if ( (in_array($field, $keys)) &&
                 ($model[$field] !== 'auto') )
            {
                // Key field that is NOT 'auto' -- INVALID
                $isValid = false;
            }

            return $isValid;
        }

        /*********************************************************************
         * This field has a value.  Is it consistent with the field type?
         *
         */
        switch ($model[$field])
        {
        case 'auto':
            /* ALWAYS call 'auto' key fields valid since we don't allow them to
             * be changed (see __set()).
             */
            break;

        case 'integer':
        case 'numeric':
            if ( ! @is_numeric($this->_record[$field]) )
            {
                // NOT valid
                $isValid = false;
                break;
            }
            break;

        case 'string':
        default:
            if (! @is_string($this->_record[$field]))
            {
                // NOT valid
                $isValid = false;
                break;
            }
            break;
        }

        return $isValid;
    }

    /*************************************************************************
     * Abstract Static methods
     *
     */

    /** @brief  Locate the record for the identified user and return a new User
     *          instance.
     *  @param  className   The name of the concrete sub-class.
     *  @param  id          The user identifier
     *                      (integrer userId or string name).
     *
     *  @return A new instance (false if no matching user).
     */
    public static function find($className, $id)
    {
        $user = new $className($id);
        if ($user->isBacked())
            return $user;

        return false;
    }

    /** @brief  Retrieve all records an return an array of instances.
     *  @param  className   The name of the concrete sub-class.
     *  @param  where       A string or associative array of restrictions.
     *
     *  @return An array of instances.
     */
    public static function fetchAll($className, $where = null)
    {
        // Figure out the table of the given class
        $ev    = "\$table = $className::\$table;";
        eval($ev);

        $db   = Connexions::getDb();

        $sql  = 'SELECT * FROM '. $table;
        $bind = array();
        if (@is_array($where))
        {
            $parts = array();
            foreach ($where as $key => $val)
            {
                array_push($bind,  $val);
                array_push($parts, '('.$key.'=?)');
            }

            $sql .= ' WHERE '. implode('AND ', $parts);
        }
        else if (@is_string($where))
        {
            $sql .= ' WHERE '. $where;
        }

        $recs = $db->fetchAll($sql, $bind);

        $set = array();
        foreach ($recs as $row)
        {
            array_push($set, new $className($row, $db));
        }

        return $set;
    }
}
