<?php
/** @file
 *
 *  This is the input filter & validator for the Tag Domain Model.
 *
 */
class Model_Filter_Tag extends Connexions_Model_Filter
{
    const   ENCODING    = 'UTF-8';
    const   MIN_LENGTH  = 1;
    const   MAX_LENGTH  = 30;

    protected $_filterRules     = array(
        'tagId'         => array('int'),
        'tag'           => array(array('callback',
                                       'callback' =>
                                            'Model_Filter_Tag::filterTag'),
        ),

        // The following SHOULD NOT be set from outside the Model Layers
        'userItemCount' => array('int'),
        'userCount'     => array('int'),
        'itemCount'     => array('int'),
    );
    protected $_validatorRules  = array(
        'tagId'         => array('int',
                                 'presence' => 'required' ,
        ),
        'tag'           => array(array('stringLength',
                                       'min'    => self::MIN_LENGTH,
                                       'max'    => self::MAX_LENGTH),
                                 'presence' => 'required',
        ),

        // The following SHOULD NOT be set from outside the Model Layers
        'userItemCount' => array('int',
                                 'default'  => 0,
        ),
        'userCount'     => array('int',
                                 'default'  => 0,
        ),
        'itemCount'     => array('int',
                                 'default'  => 0,
        ),
    );

    static public function filterTag($value)
    {
        $orig = $value;

        /* Decode any HTML entities, handling '&nbsp;' and '&shy;' specially
         * since html_entity_decode() generates some uniicode character that
         * isn't matched by \s, \pZ, \pM, \pP, nor \pC
         */
        $value = preg_replace('/(&nbsp;?|&shy;?)/', ' ', $value);
        $value = html_entity_decode($value, ENT_COMPAT, self::ENCODING);

        // Strip tags
        $value = strip_tags($value);

        // Convert new-lines to white-space.
        $value = str_replace(array("\n","\r"), ' ', $value);

        // Trim
        $value = trim($value);

        // Collapse white-space
        $value = preg_replace('/\s+/', ' ', $value);

        // Remove invalid characters
        $value = preg_replace('/[,"\'`\\\\]/', '', $value);

        // Lower-case
        //$value = mb_strtolower($value, self::ENCODING);
        $value = strtolower($value);

        // Do NOT allow a fully numeric tag.
        if (is_numeric( $value ))
        {
            $value = '_'. $value;
        }

        // Filter down to the proper length
        $value = preg_replace('/(.{'. self::MIN_LENGTH .','
                                    . self::MAX_LENGTH .'}).*'.'/',
                              '$1', $value);

        if (empty($value))
        {
            // Replace an empty value with '_empty_'
            // /*
            Connexions::log("Model_Filter_Tag::filterTag(): value[ %s ] "
                            .   "filters to an empty tag",
                            $orig);
            // */

            $value = '_empty_';
        }

        return $value;
    }
}
