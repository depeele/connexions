<?php
/** @file
 *
 *  This is an input filter to convert HTML entities to non-entities.
 *
 *  Note: stripTags should probably be called AFTER this filter is applied.
 */

class Connexions_Filter_DecodeEntities implements Zend_Filter_Interface
{
    public function filter($value)
    {
        /*
        Connexions::log("DeocdeEntities::filter( %s )", $value);
        // */

        $res = html_entity_decode($value, ENT_QUOTES);

        /*
        Connexions::log("DeocdeEntities::filter( %s ) == [ %s ]",
                        $value, $res);
        // */

        return $res;
    }
}
