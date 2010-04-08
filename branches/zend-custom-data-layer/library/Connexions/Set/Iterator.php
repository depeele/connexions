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
    protected   $_parentOffset  = 0;

    public function __construct(Connexions_Set  $parentSet,
                                                $array,
                                                $parentOffset)
    {
        $this->_parentSet    =  $parentSet;
        $this->_parentOffset =  $parentOffset;

        /*
        Connexions::log("Connexions_Set_Iterator:: "
                        . "parent(". get_class($parentSet) ."), "
                        . "offset[ {$offset} ], "
                        . "count[ ". count($array) ." ]");
        // */

        parent::__construct($array);
    }

    public function current()
    {
        /*
        Connexions::log("Connexions_Set_Iterator::current(): "
                        . "parent(". get_class($this->_parentSet) ."), "
                        . "offset[ {$this->key()} ], "
                        . "parentOffset[ {$this->_parentOffset} ]");
        // */

        return $this->_parentSet->getItem($this->_parentOffset + $this->key());
    }
}
