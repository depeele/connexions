<?php
/** @file
 *
 *  The abstract base class for Connexions Database Table Models.
 *
 */
abstract class Connexions_Model
{
    /*************************************************************************
     * The following static, identity members MUST be overridden by concrete
     * classes.
     *
     *   table          is the name of the underlying database table
     *   keys           is an array of database keys, increasing order by most
     *                  used
     *
     *   model          an associative array of 'field' => 'type' defining all
     *                  fields of the table.  Valid types are:
     *                      auto        - an auto-incrementing integer value
     *                      integer
     *                      float
     *                      boolean
     *                      datetime
     *                      string      - default type
     */
    public static   $table  = null;
    public static   $keys   = null;
    public static   $model  = null;

    /* Primarily for PHP < 5.3, these are established during instantiation via
     * _bind() and should be references to the static, identity members of
     * the concrete sub-class.
     */
    protected   $_table     = null;
    protected   $_keys      = null;
    protected   $_model     = null;
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
        $this->_bind();
        $this->_init($id, $db);
    }

    /** @brief  Map a field name.
     *  @param  name    The provided name.
     *
     *  @return The new, mapped name (null if the name is NOT a valid field).
     */
    public function mapField($name)
    {
        if (( ! (is_array($this->_record) &&
                 isset($this->_record[$name])) ) &&
            (! isset($this->_model[$name])) )
        {
            $name = null;
        }

        return $name;
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
        $vName = $this->mapField($name);
        if ($vName === null)
        {
            // Invalid field
            $this->_error = "Invalid field '{$name}' on set";
            return false;
        }

        if ($this->_model[$vName] === 'auto')
        {
            // Modification of an 'auto' generated key field is NOT permitted
            $this->_error = "Cannot modify auto field '{$name}' == '{$vName}'";
            return false;
        }

        $tmpRec  = array($vName => $value);

        $isValid = $this->_validateField($tmpRec, $vName);
        if ($isValid)
        {
            if (! @is_array($this->_record))
                $this->_record = array();

            $this->_record[$vName]    = $tmpRec[$vName];
            $this->_validated[$vName] = true;
            $this->_dirty[$vName]     = true;
            $this->_isValid           = true;
            $this->_error             = null;
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
        $vName = $this->mapField($name);
        if ($vName === null)
        {
            // Invalid field
            $this->_error = "Invalid field '{$name}' on get";
            return null;
        }

        // If this field has not yet been validated, validate it now.
        if ( (! @isset($this->_validated[$vName])) ||
            ($this->_validated[$vName] !== true) )
            $this->_validate($vName);

        /*
        Connexions::log("Model::__get(%s / %s): [ %s ]",
                        $name, $vName,
                        print_r($this->_record[$vName], true));
        // */

        return $this->_record[$vName];
    }

    /** @brief  Is the given field set?
     *  @param  name    The field name.
     *
     *  @return true | false
     */
    public function __isset($name)
    {
        $vName = $this->mapField($name);
        if ($vName === null)
        {
            // Invalid field
            $this->_error = "Invalid field '{$name}' on isset";
            return false;
        }

        //Connexions::log("Connexions_Model::__isset({$name}): true");
        return true;
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
        /*
        Connexions::log("Connexions_Model::save: record[ %s ]",
                        $this->debugDump(true));
        // */

        if ($this->isDirty() !== true)
            return true;

        // Before we actually save, do a final validation
        $this->_isValid = $this->_validate();
        if ($this->_isValid !== true)
            return false;

        // store the new data for this record
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
            Connexions::log("Connexions_Model: Update table '%s'; "
                            . "fields[%s], values[%s], "
                            . "where( clause[%s], binding[%s] )\n",
                            $this->_table,
                            implode(', ', array_keys($dirty)),
                            implode(', ', array_values($dirty)),
                            implode(' AND ', array_keys($where)),
                            implode(', ', array_values($where)) );
            // */

            if ( (count($dirty) < 1) ||
                 ($this->_db->update($this->_table,
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
            Connexions::log("Connexions_Model: Insert table '%s'; "
                            . "fields[%s], values[%s]\n",
                            $this->_table,
                            implode(', ', array_keys($this->_record)),
                            implode(', ', array_values($this->_record)) );
            // */

            // Catch exceptions like -- duplicate primary key...
            try
            {
                $res = $this->_db->insert($this->_table, $this->_record);
            }
            catch (Zend_Db_Statement_Exception $e)
            {
                $this->_error = $e->getMessage();
                $res          = false;
            }
               
            if ($res === 1)
            {
                $res = true;

                /* Now, retrieve the full record that we've just inserted,
                 * including any fields that were not included in the insert.
                 */
                $id = $this->_db->lastInsertId($this->_table);
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
        if ($this->_isBacked !== true)
        {
            $this->_error = "Record is not backed";
            return false;
        }

        /* Generate a where clause comprised of ALL the keys of this
         * record.
         *
         * Note: We COULD just pass the entire record as the where clause,
         *       but then this would negate any advantage gained by having
         *       indexed keys.
         */
        $res   = false;
        $where = $this->_record2where();
        if (empty($where))
        {
            throw(new Exception('Connexions_Model::delete:'
                                .  ' ('. get_class($this) .') '
                                .  'There are no keys with associated values '
                                .  'set for this record.  '
                                .  'ABORT, otherwise ALL records woudl be '
                                .  'deleted!'));
        }

        /*
        Connexions::log("Connexions_Model: Delete from '%s'; "
                        . "where( clause[%s], binding[%s] )\n",
                        $this->_table,
                        implode(' AND ', array_keys($where)),
                        implode(', ', array_values($where)) );
        // */
        if ($this->_db->delete($this->_table, $where) )
        {
            $res = true;
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

        $this->_isValid   = false;
        $this->_validated = array();
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
            foreach ($this->_record as $key => $val)
            {
                if ($skipValidation !== true)
                {
                    // Use the getter to force validation if not done already
                    $val     = $this->{$key};   
                }

                $isKey   = in_array($key, $this->_keys);
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
        $where = array();
        foreach ($this->_keys as $key)
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
        if (@is_scalar($data))
            $inData = array($data);
        else
            $inData =& $data;

        /*
        Connexions::log("Connexions_Model:_data2where:: "
                        . "table '{$this->_table}', "
                        .   "data[ "
                        .       print_r($data, true) . " ]");
        // */

        $isKey       = true;
        $keysMatched = array();
        $where       = array();
        foreach ($inData as $field => &$val)
        {
            /* Attempt to match the current field against the keys for this
             * model.
             */
            $dbKey = false;
            foreach ($this->_keys as $key)
            {
                if (isset($keysMatches[$key]))
                    // We've already matched this 'key', skip it...
                    continue;

                if ( (! is_string($field)) || ($field == $key) )
                {
                    // See if the value matches the type of this key.
                    try
                    {
                        $val   = $this->_coherse($val, $this->_model[$key]);
                        $dbKey = $key;

                        // We have a match by field name and value.
                    }
                    catch(Exception $e)
                    {
                        // No match on value, therefore no match on key field
                    }
                }

                if ($dbKey !== false)
                    break;
            }

            if ($dbKey !== false)
            {
                $isKey = true;
                $where["({$dbKey}=?)"] = $val;
            }
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
        // (Re)set our state
        $this->_id        = null;
        $this->_isBacked  = false;
        $this->_isValid   = false;
        $this->_error     = null;
        $this->_record    = null;
        $this->_validated = array();
        $this->_dirty     = array();

        if ($db !== null)
            $this->_db = $db;

        // Make sure we have a database conneciont in $this->_db
        if ( ! $this->_db instanceof Zend_Db_Adapter_Abstract)
            $this->_db = $this->getDb();

        $isBacked = false;
        $isRecord = false;

        if (is_array($id))
        {
            if (isset($id['@isBacked']))
            {
                $isBacked = ($id['@isBacked'] ? true : false);
                unset($id['@isBacked']);

                if ($isBacked)
                {
                    $isRecord = true;
                    unset($id['@isRecord']);
                }
            }

            if (isset($id['@isRecord']))
            {
                $isRecord = ($id['@isRecord'] ? true : false);
                unset($id['@isRecord']);
            }
        }

        if ((! $isBacked) && @is_array($id) && @isset($id['@isRecord']))
        {
            $isRecord = ($id['@isRecord'] ? true : false);
            unset($id['@isRecord']);
        }

        /* Attempt to figure out what 'id' represents:
         *  - a scaler value representing a database key;
         *  - an array of key/value pair(s) representing a set of database
         *    field/value pairs to locate;
         *  - an array of key/value pairs representing record data
         *    (iff isBacked is true).
         */
        if ( $isRecord )
        {
            /***************************************************************
             * $id is incoming record data, possibly backed.
             *
             */
            $this->_record = $id;

            if ($isBacked === true)
            {
                /* The incomding record initialization data is backed
                 * (i.e. directly from the database), so call it valid.
                 */
                $this->_isValid  = true;
                $this->_isBacked = true;
            }
            else
            {
                /* The incoming record initialization data is NOT backed, so
                 * perform validation.
                 */
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
        else if ( ($where = $this->_data2where($id)) !== false )
        {
            /***************************************************************
             * 'id' was NOT marked as a backed record AND we have
             * successfully generated an SQL where clause using it.
             *
             * Attempt to retrieve a matching record.
             *
             */
            $select =  'SELECT * FROM '. $this->_table
                    .  ' WHERE '. implode(' AND ', array_keys($where));

            /*
            Connexions::log("Connexions_Model:_init:: "
                            . "table '{$this->_table}', "
                            . "where( "
                            .   "clause[ "
                            .       implode(' AND ', array_keys($where))
                            .                 " ], "
                            .   "binding[ "
                            .       implode(', ', array_values($where))
                            .                 " ]");
            // */

            try
            {
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
                $rec['@isBacked'] = true;   // implies '@isRecord'
                $this->_init($rec, $this->_db);
            }
            else
            {
                /*************************************
                 * No matching record
                 *
                 * Attempt to parse the data into
                 * record initialization data.
                 */
                if (@is_array($id))
                {
                    /* Attempt to parse this array into data that
                     * can be used to initialize this record.
                     *
                     * It will either invoke this method again with valid
                     * initialization data, or will set an error.  Either way,
                     * we're finished.
                     */
                    $this->_array2record($id);
                }
                else
                {
                    $this->_error = "No record matching '{$id}'";
                }
            }
        }
        else if (is_array($id))
        {
            // Try marking this array data as '@isRecord'...
            $id['@isRecord'] = true;
            return $this->_init($id, $db);
        }
        else
        {
            // Not a key nor valid record data...
            $this->_error = "Invalid record initialization data: '{$id}'";
        }

        return $this;
    }

    /** @brief  Given an array, see if it contains valid data for the
     *          initialization of a new, unbacked record.
     *  @param  id      The initialization array.
     *
     *  @return Connexions_Model to provide a fluent interface.
     */
    protected function _array2record(array $id)
    {
        /* The incoming 'id' is an array, perhaps it is the data
         * for a new record.
         */
        $idParts = array();
        $idKeys  = array_keys($id);
        if (is_string($idKeys[0]))
        {
            /* 'id' is an associative array.  Use our 'model' to try and build
             * a valid record initialization array.
             *
             * Start by removing any field that has a type of 'auto'.  If there
             * are fields remaining, call it a non-backed record.
             */
            foreach ($this->_keys as $key)
            {
                if ($this->_model[$key] !== 'auto')
                    continue;

                if (isset($id[$key]) )
                {
                    /* Remember the fields that we remove in case
                     * we end up removing them all.
                     */
                    array_push($idParts, $key.'=='.$id[$key]);

                    unset($id[$key]);
                }
            }

            if (! @empty($id))
            {
                /* We have data that includes one or more non-auto fields.
                 *
                 * The fields MAY NOT be valid for a record, but that will be
                 * taken care of down the line.  Invoke the _init() method
                 * again, noting that the data seems to represent a non-backed
                 * record.
                 */
                $id['@isRecord'] = true;
                return $this->_init($id, $this->_db);
            }

            /* The only fields were all 'auto' keys.
             *
             * Fall through to error...
             */
        }

        /***********************************************************
         * Error -- assemble and record an error message.
         *
         */
        foreach ($id as $key => $val)
        {
            if (@is_string($key))
            {
                array_push($idParts, $key.'=='.$val);
            }
            else
                array_push($idParts, $val);
        }

        $this->_error = 'No record matching "'
                      .         implode(', ', $idParts) . '"';

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
        $isValid = false;
        if ($field === null)
        {
            $fields = $this->_model;
        }
        else
            $fields = array( $field => null );

        // Validate all fields, assuming they will be valid.
        $isValid = true;
        foreach ($this->_model as $field => $type)
        {
            $field = $this->mapField($field);
            if ($field === null)
                return false;

            $this->_validated[$field] =
                ( (@isset($this->_validated[$field]) &&
                          $this->_validated[$field]) ||
                 $this->_validateField($this->_record, $field));

            if ($this->_validated[$field] !== true)
                $isValid = false;
        }

        return $isValid;
    }

    /** @brief  Validate the given field.
     *  @param  record  The record to validate within.
     *  @param  field   The pre-mapped field to validate.
     *
     *  @return true | false
     */
    protected function _validateField(&$record, $field)
    {
        $isValid = true;
        if (! @isset($record[$field]))
        {
            /* This field has no value.
             *
             * See if it is a 'key' field.  If so, we only allow 'auto' keys to 
             * be empty.
             */
            if ($this->_model[$field] !== 'auto')
            {
                // The field is NOT marked 'auto'.  See if it matches any key.
                if (in_array($field, $this->_keys))
                {
                    /* This field matches a non-auto key.
                     *
                     * We don't allow empty key fields so mark this field
                     * invalid.
                     */
                    $isValid = false;
                }
            }

            return $isValid;
        }

        /*********************************************************************
         * This field has a value.  Is it consistent with the field type?
         *
         */

        $value =& $record[$field];

        if (@isset($this->_model[$field]))
        {
            try
            {
                $record[$field] =
                        $this->_coherse($record[$field], $this->_model[$field]);
            }
            catch (Exception $e)
            {
                $this->_error = sprintf ("Invalid Cohersion: ".
                                            "field[%s], type[%s], value[%s]: ".
                                            "%s<br />\n",
                                         $field,
                                         $this->_model[$field],
                                         $record[$field],
                                         $e->getMessage());
                $isValid = false;
            }
        }

        return $isValid;
    }

    /** @brief  Coherse the given value to the specified type.
     *  @param  field   The field to validate.
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
            {
                throw (new Exception(implode('\n', $date['errors'])));
            }

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

    /** @brief  Connect this instance with the concrete sub-class */
    protected function _bind()
    {
        if ($this->_table === null)
        {
            $className = get_class($this);

            $this->_table =& self::metaData('table', $className);
            $this->_keys  =& self::metaData('keys',  $className);
            $this->_model =& self::metaData('model', $className);
        }
    }

    /*************************************************************************
     * Static methods
     *
     */

    /** @brief  Retrieve a database adapter.
     *
     *  @return A Zend_Db_Adapter_Abstract instance
     */
    public static function getDb()
    {
        $db = null;
        if (isset($this))
        {
            $db = $this->_db;
        }

        if ( ! $db instanceof Zend_Db_Adapter_Abstract)
            $db = Connexions::getDb();

        return $db;
    }

    /** @brief  Locate the identified record.
     *  @param  id          The record identifier.
     *  @param  db          An optional database instance (Zend_Db_Abstract).
     *  @param  className   The name of the concrete sub-class.
     *
     *  @return A new instance (check isBacked(), isValid(), getError()).
     */
    public static function find($id, $db = null, $className = null)
    {
        // PHP < 5.3, comment out this test, requiring callers to supply ALL
        //            parameters.
        if ($className === null)
            $className = get_called_class();

        return new $className($id, $db);
    }

    /** @brief  Return a Zend_Db_Select instance for all records matching the
     *          given 'where' clause.
     *  @param  config      Configuration settings for the desired
     *                      Zend_Db_Select:
     *                          db          An optional Zend_Db_Abstract
     *                                      database adapter [ self::getDb() ];
     *                          modelClass  The name of the concrete
     *                                      Connexions_Model sub-class
     *                                      [ get_called_class() ];
     *                          as          The SQL alias to use for the target
     *                                      table [ 't' ];
     *                          where       A string or associative array of
     *                                      restrictions [ none ];
     *
     *  @return A Zend_Db_Select instance that can retrieve the desired
     *          records.  The target table will be aliased as 't'.
     */
    public static function select(array $config = array())
    {
        if ( ! @empty($config['as']))
            $as = $config['as'];
        else
            $as = 't';

        if ( isset($config['db']) && ($db instanceof Zend_Db_Abstract))
            $db = $config['db'];
        else
            $db = self::getDb();

        if ( isset($config['modelClass']))
            $modelClass = $config['modelClass'];
        else
            // PHP < 5.3, comment out this else, requiring callers to supply
            //            the 'modelClass' (i.e. throw an exception here).
            $modelClass = get_called_class();

        // Construct the Zend_Db_Select instance.
        $select = $db->select()
                     ->from( array($as => self::metaData('table',
                                                         $modelClass)));

        if (! @empty($config['where']))
            $select->where( $config['where'] );

        return $select;
    }

    /*************************************************************************
     * Static methods supporting late static binding
     *
     */

    /** @brief  Retrieve the value of a static meta-data member of this Model.
     *  @param  name        The name of the desired meta-data.
     *  @param  className   The calling classes name -- needed only when we
     *                      call this method from another static method of the
     *                      Connexions_Model class.
     *
     *  @return The value.
     */
    public static function metaData($name, $className = null)
    {
        /* PHP < 5.3 -- requires reflection to access late static bindings AND
         *              requires that the class name ALWAYS be passed in.
         *
         *  $reflect = new ReflectionClass($className);
         *  return $reflect->getStaticPropertyValue($name);
         */
        if ($className === null)
            $className = get_called_class();

        return $className::$$name;
    }
}
