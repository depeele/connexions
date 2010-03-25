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
        return $this->_parentSet->getItem($this->key());
    }
}
