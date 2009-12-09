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
     *              type may be:
     *                  ('numeric', 'string', 'integer', 'auto')
     */
    abstract public static function getTable();
    abstract public static function getKeys();
    abstract public static function getModel();

    // The following are per-instance
    protected   $_id        = null;     // The record id

    protected   $_isValid   = false;
    protected   $_isDirty   = false;
    protected   $_error     = null;

    protected   $_db        = null;
    protected   $_record    = null;

    /** @brief  Create a new instance.
     *  @param  id      The record identifier.
     *  @param  db      An optional database instance (Zend_Db_Abstract).
     *
     */
    public function __construct($id, $db = null)
    {
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

        $this->_isValid   = false;
        $this->_isDirty   = false;
        $this->_error     = null;
        $this->_record    = null;

        if ($db !== null)
            $this->_db    = $db;

        if ($id === null)
            return $this;

        // (Re)retrieve our data
        if (@is_array($id))
        {
            /* An incoming record
             *  :TODO: validate the incoming record
             */
            $this->_record  = $id;
            $this->_isValid = true;
        }
        else
        {
            // Does the incoming 'id' match any of the table keys?
            $rec      =  null;
            $firstKey =  null;
            $select   =  'SELECT * FROM '. $this->getTable() .' WHERE ';

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
                    $rec = $this->db()->fetchRow($select . $where, array($id));

                    // We have a matching record!
                    $this->_id = $id;
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
                $this->_record  = $rec;
                $this->_isValid = true;
            }
            else
            {
                $this->_error   = 'No record matching "'. $id .'"';
                $this->_isValid = false;
            }
        }

        return $this;
    }

    public function isValid()
    {
        return $this->_isValid;
    }

    public function isDirty()
    {
        return $this->_isDirty;
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

        if (! @is_array($this->_record))
            $this->_record = array();

        if ($this->_record[$name] != $value)
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

        // store the new data for this record
       $res = false;
        if ($this->_isValid === true)
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

            if ($this->db()->update($this->getTable(),
                                    $this->_record,
                                    $where) === 1)
            {
                $res = true;
            }
        }
        else
        {
            // This is a new record that we need to insert

            /* Ensure that we have at least one key in our current record data.
             *
             * If we hit an unset key in the process, and have '_id', and the
             * type of the key matches the type of '_id', use _id's value for
             * that key.
             */
            $included = false;
            $keysSet  = 0;
            $model    =& $this->getModel();
            foreach ($this->getKeys() as $key)
            {
                if (@isset($this->_record[$key]))
                {
                    $keysSet++;
                    continue;
                }

                switch ($model[$key])
                {
                case 'auto':
                case 'integer':
                case 'numeric':
                    if (! @is_numeric($this->_id))
                        continue;

                    $this->_record[$key] = $this->_id;
                    $included = true;
                    $keysSet++;
                    break;

                case 'string':
                default:
                    if (! @is_string($this->_id))
                        continue;

                    $this->_record[$key] = $this->_id;
                    $included = true;
                    $keysSet++;
                    break;
                }

                if ($included)
                    break;
            }

            if ( ($keysSet > 0) &&
                 ($this->db()->insert($this->getTable(),
                                      $this->_record) === 1) )
            {
                $res = true;

                // Now, retrieve the full record that we've just inserted.
                $id = $this->db()->lastInsertId($this->getTable());

                $this->setId($id);
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

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Retrieve a valid database handle.
     *
     *  @return The database handle.
     */
    protected function db()
    {
        if ($this->_db === null)
            $this->_db = Connexions::getDb();

        return $this->_db;
    }

    /*************************************************************************
     * Static methods
     *
     */

    /** @brief  Locate the record for the identified user and return a new User
     *          instance.
     *  @param  id      The user identifier (integrer userId or string name).
     *
     *  @return A new instance (false if no matching user).
     */
    public static function find($id)
    {
        $user = new self($id);
        if ($user->isValid())
            return $user;

        return false;
    }

    /** @brief  Retrieve all records an return an array of instances.
     *
     *  @return An array of instances.
     */
    public static function fetchAll()
    {
        $db   = Connexions::getDb();

        $sql  = 'SELECT * FROM '. self::getTable();
        $recs = $db->fetchAll($sql);

        $set = array();
        foreach ($recs as $row)
        {
            array_push($set, new self($row, $db));
        }

        return $set;
    }


}
