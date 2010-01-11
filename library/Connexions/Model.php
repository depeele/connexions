<?php
/** @file
 *
 *  The base class for Connexions Database Table Models.
 */
abstract class Connexions_Model
{
    /*************************************************************************
     * The following static, identity members MUST be overridden by concrete
     * classes.
     *
     */
    public static   $table  = null;
    public static   $keys   = null;
    public static   $model  = null;
    /*************************************************************************/

    protected   $_id        = null;     // The record id

    protected   $_isBacked  = false;    /* Is there a record backing this (i.e.
                                         * was it pulled from the database or
                                         * constructed without a matching
                                         * record)
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
    protected   $_validated = null;     /* An array of booleans that parallels
                                         * _record to track which fields have
                                         * been validated.
                                         */
    protected   $_dirty     = null;     /* An array of booleans that parallels
                                         * _record to track which fields have
                                         * been modified since
                                         * retrieval/creation.
                                         */


    /** @brief  Create a new instance.
     *  @param  id      The record identifier.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     */
    public function __construct($id, $db = null)
    {
        $this->_init($id, $db);
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
        return ($this->_isBacked ? (! @empty($this->_dirty)) : true);
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

    public function hasError()
    {
        return ($this->_error !== null);;
    }

    /** @brief  Set a value in this record and mark it dirty.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  @return true | false
     */
    public function __set($name, $value)
    {
        $class =  get_class($this);

        $model =& $class::$model;   //$this->getModel();
        if (! @isset($model[$name]))
        {
            // Invalid field
            $this->_error = 'Invalid field "'.  $name .'"';
            return false;
        }

        if ($model[$name] === 'auto')
        {
            // Modification of an 'auto' generated key field is NOT permitted
            $this->_error = 'Cannot modify auto field "'.  $name .'"';
            return false;
        }

        $keys    =& $class::$keys;  //$this->getKeys();
        $tmpRec  = array($name => $value);

        $isValid = $this->_validateField($tmpRec, $name, $keys, $model);
        if ($isValid)
        {
            if (! @is_array($this->_record))
                $this->_record = array();

            $this->_record[$name]    = $tmpRec[$name];
            $this->_validated[$name] = true;
            $this->_dirty[$name]     = true;
            $this->_isValid          = true;
            $this->_error            = null;
        }

        return $isValid;
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

        // If this field has not yet been validated, validate it now.
        if ($this->_validated[$name] !== true)
            $this->_validate($name);

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
        if ($this->isDirty() !== true)
            return true;

        // Before we actually save, do a final validation
        $this->_isValid = $this->_validate();
        if ($this->_isValid !== true)
            return false;

        // store the new data for this record
        $class = get_class($this);
        $res   = false;
        if ($this->_isBacked === true)
        {
            /* This is an existing record that we need to update
             *
             * Generate a where clause comprised of all non-dirty keys.
             */
            $where = $this->_record2where(false);

            // Only update fields that have been modified.
            $dirty = array_intersect_key($this->_record, $this->_dirty);

            /*
            printf ("Connexions_Model: Update table '%s'; ".
                        "fields[%s], values[%s], ".
                        "where( clause[%s], binding[%s] )\n",
                     $class::$table,    //$this->getTable(),
                     implode(', ', array_keys($dirty)),
                     implode(', ', array_values($dirty)),
                     implode(' AND ', array_keys($where)),
                     implode(', ', array_values($where)) );
            // */

            if ( (count($dirty) < 1) ||
                 ($this->_db->update($class::$table,    //$this->getTable(),
                                     $dirty,
                                     $where) === 1) )
            {
                $res          = true;
                $this->_dirty = array();
            }
        }
        else
        {
            // This is a new record that we need to insert
            /*
            printf ("Connexions_Model: Insert table '%s'; ".
                        "fields[%s], values[%s]\n",
                     $class::$table,    //$this->getTable(),
                     implode(', ', array_keys($this->_record)),
                     implode(', ', array_values($this->_record)) );
            // */

            if ( $this->_db->insert($class::$table, //$this->getTable(),
                                    $this->_record) === 1)
            {
                $res = true;

                /* Now, retrieve the full record that we've just inserted,
                 * including any fields that were not included in the insert.
                 */
                //$id = $this->_db->lastInsertId($this->getTable());
                $id = $this->_db->lastInsertId($class::$table);
                $this->_init($id, $this->_db);
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
            $class  = get_class($this);
            $where  = array();
            //foreach ($this->getKeys() as $field)
            foreach ($class::$keys as $field)
            {
                if (! @isset($this->_record[$field]))
                {
                    throw(new Exception("*** Cannot delete record: ".
                                            "Missing key [ ". $field ." ]"));
                }

                $where['('. $field .'=?)'] = $this->_record[$field];
            }

            if (empty($where))
            {
                throw(new Exception("*** Don't delete all records!!"));
            }

            /*
            printf ("Connexions_Model: Delete from '%s'; ".
                            "where( clause[%s], binding[%s] )\n",
                     $class::$table,    //$this->getTable(),
                     implode(' AND ', array_keys($where)),
                     implode(', ', array_values($where)) );
            // */
            //if ($this->_db->delete($this->getTable(), $where) )
            if ($this->_db->delete($class::$table, $where) )
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

    /** @brief  Generate a string representation of this record.
     *  @param  skipValidation  Skip validation of each field [false]?
     *
     *  @return A string.
     */
    public function debugDump($skipValidation = false)
    {
        $str = sprintf("[%svalid, %sbacked, %sdirty, error[ %s ]:\n",
                        ($this->isValid()  ? "" : "NOT "),
                        ($this->isBacked() ? "" : "NOT "),
                        ($this->isDirty()  ? "" : "NOT "),
                        $this->_error);

        if (@is_array($this->_record))
        {
            $class =  get_class($this);
            $keys  =& $class::$keys;    //$this->getKeys();
            foreach ($this->_record as $key => $val)
            {
                if ($skipValidation !== true)
                {
                    // Use the getter to force validation if not done already
                    $val     = $this->{$key};   
                }

                $isKey   = in_array($key, $keys);
                $isValid = $this->_validated[$key];
                $isDirty = $this->_dirty[$key];

                $type = gettype($val);
                if ($type === 'object')
                    $type = get_class($val);
                else if ($type === 'boolean')
                    $val = ($val ? 'true' : 'false');

                $str .= sprintf (" %s%-15s == %-15s%s%s[ %s ]\n",
                                 ($isKey   ? "@" : " "),
                                 $key, $type,
                                 ($isValid ? ' ' : '!'),
                                 ($isDirty ? '*' : ' '),
                                 $val);
            }
        }
        else
        {
            $str .= " *** EMPTY ***";
        }

        $str .= "\n];";

        return $str;
    }

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Generate an array of SQL where clauses comprised of all
     *          (possibly non-dirty) keys of this instance.
     *  @param  useDirty    Should we include keys marked dirty?
     *
     *  @return An associative array with keys like 'field=?' that reference
     *          the desired value.
     */
    protected function _record2where($includeDirty = true)
    {
        $class = get_class($this);
        $where = array();
        //foreach ($this->getKeys() as $key)
        foreach ($class::$keys as $key)
        {
            if ( (($includeDirty === true) ||
                  (! @isset($this->_dirty[$key]))) &&
                 (@isset($this->_record[$key])) )
            {
                $where['('.$key.'=?)'] = $this->_record[$key];
                //array_push($where, $key.'='.$this->_record[$key]);
            }
        }

        return $where;
    }

    /** @brief  Given a potential database key or array of key/value pairs, do
     *          they match a key or keys for this model?
     *  @param  data    The array of key/value pairs.
     *
     *  @return An associative array with keys like 'field=?' that reference
     *          the desired value; false if not a valid key.
     */
    protected function _data2where(&$data)
    {
        $class =  get_class($this);
        $keys  =& $class::$keys;    //$this->getKeys();
        $model =& $class::$model;   //$this->getModel();
        if (@is_scalar($data))
            $data = array($data);

        $isKey       = true;
        $keysMatched = array();
        $where       = array();
        foreach ($data as $key => &$val)
        {
            /* If 'key' is a string, see if it matches a database key field
             * name.
             */
            $dbKey = false;
            if (@is_string($key))
            {
                /* See if this 'key' matches a database key field
                 * that we haven't already seen...
                 */
                if (@in_array($key, $keys) && (! @isset($keysMatched[$key])) )
                {
                    // See if the value matches the type of this key.
                    try
                    {
                        $val   = $this->_coherse($val, $model[$key]);
                        $dbKey = $key;

                        // We have a match by field name and value.
                    }
                    catch(Exception $e)
                    {
                        // No match on value, therefore no match on key field
                    }
                }
            }
            else
            {
                /* This 'key' seems to be the index of an array.  See if the
                 * provided value matches any of the keys from our database.
                 */
                foreach ($keys as $checkKey)
                {
                    if (@isset($keysMatched[$checkKey]))
                    {
                        // We've already matched this 'key', skip it....
                        continue;
                    }

                    try
                    {
                        $val   = $this->_coherse($val, $model[$checkKey]);
                        $dbKey = $checkKey;

                        // Match!
                        break;
                    }
                    catch(Exception $e)
                    {
                        // Ignore and continue
                    }
                }
            }

            if ($dbKey === false)
            {
                /* The 'key' from this key/value pair doesn't match a
                 * database key field name or value/type.
                 */
                $isKey = false;
                break;
            }

            $where['('.$dbKey.'=?)'] = $val;
        }

        return ($isKey && (! empty($where))
                    ? $where
                    : false);
    }

    /** @brief  Initialize this model/record.  This will cause an overall
     *          reset of this instance, possibly (re)retrieving the data.
     *  @param  id      The record identifier.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     *  Note: 'id' may include the following special fields:
     *      '@isRecord' => true/false - Treat 'id' data as a raw record?
     *      '@isBacked' => true/false - Is this a pre-fetched, database-backed
     *                                  record? true implies
     *                                              '@isRecord == true'.
     *
     *  @return Connexions_Model to provide a fluent interface.
     */
    protected function _init($id, $db = null)
    {
        $class    = get_class($this);
        $isBacked = false;
        $isRecord = false;

        // (Re)set our state
        $this->_id        = null;
        $this->_isBacked  = false;
        $this->_isValid   = false;
        $this->_error     = null;
        $this->_record    = null;
        $this->_validated = array();
        $this->_dirty     = array();

        if (@isset($id['@isBacked']))
        {
            /* Note: Use '(unset) var;' vs 'unset(var);' to eliminate
             *          'Fatal error: Cannot unset string offsets'
             */
            $isBacked = ($id['@isBacked'] ? true : false);
            (unset) $id['@isBacked'];

            if ($isBacked)
            {
                $isRecord = true;
                (unset) $id['@isRecord'];
            }
        }

        if ((! $isBacked) && @isset($id['@isRecord']))
        {
            $isRecord = ($id['@isRecord'] ? true : false);
            (unset) $id['@isRecord'];
        }

        if ($db !== null)
            $this->_db = $db;

        // Make sure we have a database conneciont in $this->_db
        if ( ! $this->_db instanceof Zend_Db_Adapter_Abstract)
            $this->_db = Connexions::getDb();

        if ($id === null)
            return $this;

        $keys  =& $class::$keys;    //$this->getKeys();
        $model =& $class::$model;   //$this->getModel();

        /* Attempt to figure out what 'id' represents:
         *  - a scaler value representing a database key;
         *  - an array of key/value pair(s) representing a set of database
         *    field/value pairs to locate;
         *  - an array of key/value pairs representing record data
         *    (iff isBacked is true).
         */
        if ( ($isRecord !== true) && ($isBacked !== true) &&
             (($where = $this->_data2where($id)) !== false) )
        {
            /***************************************************************
             * 'id' was NOT marked as a backed record AND we have
             * successfully generated an SQL where clause using it.
             *
             * Attempt to retrieve a matching record.
             *
             */
            $select =  'SELECT * FROM '. $class::$table //$this->getTable()
                    .  ' WHERE '. implode(' AND ', array_keys($where));

            try
            {
                /*
                printf ("Connexions_Model: _init table '%s'; ".
                            "where( clause[%s], binding[%s] )\n",
                         $class::$table,    //$this->getTable(),
                         implode(' AND ', array_keys($where)),
                         implode(', ', array_values($where)) );
                // */

                $rec       = $this->_db->fetchRow($select,
                                                  array_values($where));
                $this->_id = $id;
            }
            catch (Exception $e)
            {
                $rec = null;
            }

            if (! @empty($rec))
            {
                /* We have successfully (re)retrieved the record data.
                 *
                 * Invoke this method again noting that the data is a backed
                 * record.
                 */
                $rec['@isBacked'] = true;
                $this->_init($rec, $this->_db);
            }
            else
            {
                // No matching record
                $idParts = array();
                if (@is_array($id))
                {
                    /* The incoming 'id' is an array, perhaps it is the data
                     * for a new record.
                     */
                    $idKeys = array_keys($id);
                    if (is_string($idKeys[0]))
                    {
                        /* 'id' is an associative array.  Remove any field that
                         * is an 'auto' key and, if there are fields remaining,
                         * call it a non-backed record.
                         */
                        foreach ($keys as $dbKey)
                        {
                            if (@isset($id[$dbKey]) &&
                                ($model[$dbKey] === 'auto') )
                            {
                                /* Remember the fields that we remove in case
                                 * we end up removing them all.
                                 */
                                array_push($idParts, $dbKey.'=='.$id[$dbKey]);

                                (unset) $id[$dbKey];
                            }
                        }

                        if (! @empty($id))
                        {
                            /* We have data for a new record that includes one
                             * or more non-auto fields.
                             *
                             * Invoke this method again noting that the data
                             * represents a non-backed record.
                             */
                            $id['@isRecord'] = true;
                            return $this->_init($id, $this->_db);
                        }

                        // Oops.  The only fields were all 'auto' keys
                    }

                    // Assemble an error message
                    foreach ($id as $key => $val)
                    {
                        if (@is_string($key))
                        {
                            array_push($idParts, $key.'=='.$val);
                        }
                        else
                            array_push($idParts, $val);
                    }
                }
                else
                {
                    array_push($idParts, $id);
                }

                $this->_error = 'No record matching "'
                              .         implode(', ', $idParts) . '"';
            }
        }
        else if (@is_array($id))
        {
            /***************************************************************
             * $id is incoming record data, possibly backed.
             *
             */
            $this->_record = $id;

            if ($isBacked === true)
            {
                $this->_isValid  = true;
                $this->_isBacked = true;
            }
            else
            {
                // Validate this incoming data.
                $this->_isValid = $this->_validate();
                if ($this->_isValid !== true)
                {
                    $invalid = array_diff(array_keys($this->_record),
                                          array_keys($this->_validated));

                    $this->_error = 'Invalid fields and/or values ['
                                  .     implode(', ', $invalid) .']';

                    $this->_record    = null;
                    $this->_validated = array();
                    $this->_dirty     = array();
                }
            }
        }
        else
        {
            // Not a key nor valid record data...
        }

        return $this;
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
        $class =  get_class($this);
        $keys  =& $class::$keys;    //$this->getKeys();
        $model =& $class::$model;   //$this->getModel();

        if ($field !== null)
        {
            // Validate the given field
            $this->_validated[$field] =
                    ($this->_validated[$field] ||
                     $this->_validateField($this->_record, $field,
                                           $keys, $model));
        }
        else
        {
            // Validate all fields -- assume it will be valid
            $isValid = true;
            foreach ($model as $field => $type)
            {
                $this->_validated[$field] =
                    ($this->_validated[$field] ||
                     $this->_validateField($this->_record, $field,
                                           $keys, $model));

                if ($this->_validated[$field] !== true)
                    $isValid = false;
            }
        }

        return $isValid;
    }

    /** @brief  Validate the given field using the provided keys and model.
     *  @param  record  The record to validate within.
     *  @param  field   The field to validate.
     *  @param  keys    The keys  of the underlying database.
     *  @param  model   The model of the underlying database.
     *
     *  @return true | false
     */
    protected function _validateField(&$record, $field, &$keys, &$model)
    {
        $isValid = true;

        if (! @isset($record[$field]))
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

        $value =& $record[$field];

        try
        {
            $record[$field] =
                    $this->_coherse($record[$field], $model[$field]);
        }
        catch (Exception $e)
        {
            $this->_error = sprintf ("Invalid Cohersion: ".
                                        "field[%s], type[%s], value[%s]: ".
                                        "%s<br />\n",
                                     $field, $model[$field], $record[$field],
                                     $e->getMessage());
            $isValid = false;
        }

        return $isValid;
    }

    /** @brief  Validate the given field using the provided keys and model.
     *  @param  field   The field to validate.
     *  @param  keys    The keys  of the underlying database.
     *  @param  model   The model of the underlying database.
     *
     *  @throw  Exception if cohersion is not possible.
     *
     *  @return The cohersed value
     */
    protected function _coherse(&$value, $type)
    {
        switch ($type)
        {
        case 'auto':
        case 'integer':
            if ( ! @is_int($value))
            {
                if (! @is_numeric($value))
                    throw(new Exception('Not numeric'));

                //if (@is_scalar($value))
                {
                    // Force this field to an integer
                    $value = intval($value);
                }
            }
            break;

        case 'float':
            if ( ! @is_float($value))
            {
                if (! @is_numeric($value))
                    throw(new Exception('Not numeric'));

                //if (! @is_scalar($value))
                {
                    // Force this field to a float
                    $value = floatval($value);
                }
            }
            break;

        case 'boolean':
            if (! @is_bool($value))
            {
                // Force this field to a boolean
                switch (@strtolower($value))
                {
                case 'false':
                case 'no':
                case 'off':
                    // A few "special" boolean values
                    $value = false;
                    break;

                default:
                    // Otherwise, rely on PHP's casting
                    $value = (boolean)$value;
                    break;
                }
            }
            break;

        case 'datetime':
            $date = date_parse($value);
            if (! @empty($date['errors']))
                throw (new Exception(implode('\n', $date['errors'])));

            // Format the date/time for MySQL 'YYYY-MM-DD HH:mm:ss'
            $value = sprintf("%04d-%02d-%02d %02d:%02d:%02d",
                             $date['year'], $date['month'],  $date['day'],
                             $date['hour'], $date['minute'], $date['second']);
            break;

        case 'string':
        default:
            $value = strval($value);
            break;
        }

        return $value;
    }

    /*************************************************************************
     * Static methods
     *
     */

    /** @brief  Locate the identified record.
     *  @param  className   The name of the concrete sub-class.
     *  @param  id          The record identifier.
     *  @param  db          An optional database instance (Zend_Db_Abstract).
     *
     *  @return A new instance (check isBacked(), isValid(), getError()).
     */
    public static function find($className, $id, $db = null)
    {
        /* For php >= 5.3, we could do away with the incoming $className along
         * with the need for a find() in the concrete classes and simply use:
         *      $className = get_called_class();
         */
        return new $className($id, $db);
    }

    /** @brief  Return a Zend_Db_Select instance for all records matching the
     *          given 'where' clause.
     *  @param  className   The name of the concrete sub-class.
     *  @param  where       A string or associative array of restrictions.
     *  @param  db          An optional database instance (Zend_Db_Abstract).
     *
     *  @return A Zend_Db_Select instance that can retrieve the desired
     *          records.
     */
    public static function select($className, $where = null, $db = null)
    {
        /* For php >= 5.3, we could do away with the incoming $className along
         * with the need for a fetchAll() in the concrete classes and simply
         * use:
         *  $className = get_called_class();
         */
        if ($db === null)
            $db = Connexions::getDb();

        $select = $db->select()
                     ->from($className::$table);
        if (! @empty($where))
            $select->where($where);

        return $select;
    }
}
