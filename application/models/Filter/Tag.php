<?php
/** @file
 *
 *  This is the input filter & validator for the Tag Domain Model.
 *
 */
class Model_Filter_Tag extends Connexions_Model_Filter
{
    const INVALID_TAG_REGEX = '/[,"\\\'`\\\\]/';

    protected $_filterRules     = array(
        'tagId'         => array('int'),
        'tag'           => array('stripTags', 'stringTrim', 'stringToLower',
                                 // Collapse white-space
                                 array('pregReplace',
                                       'match'  => '/\s+/',
                                       'replace'=> ' '),
                                 // Remove invalid characters
                                 array('pregReplace',
                                       'match'  => self::INVALID_TAG_REGEX,
                                       'replace'=> ''),
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
                                       'min'    => 2,
                                       'max'    => 30),
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
}

