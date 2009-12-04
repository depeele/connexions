<?php
/** @file
 *
 *  The base class for Connexions Database Table Models.
 */

abstract class Connexions_Model
{
    /* The following MUST be made available via the following, abstract static
     * methods:
     *  table       The name of the database table
     *  keys        An array of key/type pairs defining available selection
     *              keys and their generic types
     *              ('numeric', 'string', 'integer');
     *  model       An array of key/type pairs defining the table model.
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

        $this->_db        = ($db === null
                                ? ($this->_db === null
                                    ? Connexions::getDb()
                                    : $this->_db)
                                : $db);
        $this->_record    = null;

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
            $rec    = null;
            $select = 'SELECT * FROM '. $this->getTable() .' WHERE ';
            foreach ($this->getKeys() as $key => $type)
            {
                $where = null;
                switch ($type)
                {
                case 'integer':
                case 'numeric':
                    if (! @is_numeric($id))
                        continue;

                    $where = $key .'=?';
                    break;

                case 'string':
                default:
                    if (! @is_string($id))
                        continue;

                    $where = $key .'=?';
                    break;
                }

                if (@empty($where))
                    continue;

                // Attempt to retrieve a matching record.
                try
                {
                    $rec = $this->_db->fetchRow($select . $where, array($id));

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
        if (! @in_array($name, $this->model))
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
             (! @in_array($name, $this->_record)) )
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

        // :TODO: store the new data for this record
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
