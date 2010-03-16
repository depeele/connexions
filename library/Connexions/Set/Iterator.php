<?php
/** @file
 *
 *  
 *  A lazy iterator.  Allows us to postpone the actual instantiation of a Model
 *  instance until it is actually retrieved.
 *
 *  Note: ArrayIterator (and thus Connexions_Set_Iterator) implements
 *          Iterator, Traversable, ArrayAccess, SeekableIterator, Countable
 */
class Connexions_Set_Iterator extends ArrayIterator
{
    /** @brief  The Connexions_Set that is the source of these items. */
    protected   $_parentSet     = null;

    public function __construct(Connexions_Set $parentSet, $array)
    {
        $this->_parentSet =  $parentSet;

        parent::__construct($array);
    }

    public function current()
    {
        $memberClass  = $this->_parentSet->memberClass();
        $offset = $this->key();
        $row    = parent::current();

        if ($row instanceof $memberClass)
            return $row;

        // Create Model instance for each retrieved record
        $db    = $this->_parentSet->select()->getAdapter();
        $row['@isBacked'] = true;

        /* PHP < 5.3:
         *  return call_user_func(array($memberClass, 'find'), $row, $db);
         */
        return $memberClass::find($row, $db);
    }
}
