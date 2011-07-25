<?php
/** @file
 *
 *  This is the input filter & validator for the Item Domain Model.
 *
 */
class Model_Filter_Item extends Connexions_Model_Filter
{
    protected $_filterRules     = array(
        'itemId'        => array('int'),
        'url'           => array('stripTags', 'stringTrim'),

        // The following SHOULD NOT be set from outside the Model Layers
        'userCount'     => array('int'),
        'ratingCount'   => array('int'),
        'ratingSum'     => array('int'),
    );
    protected $_validatorRules  = array(
        'itemId'        => array('int',
                                 'presence' => 'required',
        ),
        'url'           => array('presence' => 'required'),
        'urlHash'       => array(array('regex',
                                       'pattern' => '/^([a-z0-9]{32,64})?$/i'),
        ),

        // The following SHOULD NOT be set from outside the Model Layers
        'userCount'     => array('int'),
        'ratingCount'   => array('int'),
        'ratingSum'     => array('int'),
    );
}
