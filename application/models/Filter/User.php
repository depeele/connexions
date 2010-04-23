<?php
/** @file
 *
 *  This is the input filter & validator for the User Domain Model.
 *
 */

class Model_Filter_User extends Connexions_Model_Filter
{
    const VALID_DATE    =
    // YYYY.mm.dd                            HH:ii:ss
    '/^[0-9]{4,}[.\-][0-9]{2,}[.\-][0-9]{2,} [0-9]{2,}:[0-9]{2,}:[0-9]{2,}$/';

    protected $_filterRules     = array(
        'userId'        => array('int'),
        'name'          => array('stripTags', 'stringTrim',
                                 array('pregReplace',
                                       'match'  => '/[^a-zA-Z0-9\._\-]/',
                                       'replace'=> ''),
        ),
        'fullName'      => array('stripTags', 'stringTrim'),
        'email'         => array('stripTags', 'stringTrim'),
        'pictureUrl'    => array('stripTags', 'stringTrim'),
        'profile'       => array('stripTags', 'stringTrim'),

        // The following SHOULD NOT be set from outside the Model Layers
        'totalTags'     => array('int'),
        'totalItems'    => array('int'),
        'userItemCount' => array('int'),
        'itemCount'     => array('int'),
        'tagCount'      => array('int'),
    );
    protected $_validatorRules  = array(
        'userId'        => array('int',
                                 'presence' => 'required' ,
        ),
        'name'          => array(array('stringLength',
                                       'min'    => 2,
                                       'max'    => 30),
                                 'presence' => 'required',
        ),
        'fullName'      => array(array('stringLength',
                                       'min'    => 0,
                                       'max'    => 255),
                                 'default'      => '',
                                 'allowEmpty'   => true,
        ),
        'email'         => array('EmailAddress',
                                 'default'      => '',
                                 'allowEmpty'   => true,
        ),
        'pictureUrl'    => array(array('regex',
                                       'pattern' => '/^((https?:\/\/)?.+)?$/'),
                                 'default'      => '',
                                 'allowEmpty'   => true,
        ),
        'profile'       => array(array('regex',
                                       'pattern' => '/^((https?:\/\/)?.+)?$/'),
                                 'default'      => '',
                                 'allowEmpty'   => true,
        ),

        // The following SHOULD NOT be set from outside the Model Layers
        'apiKey'        => array(array('regex',
                                       'pattern' => '/^([a-z0-9]{10,})?$/i'),
                                 'allowEmpty'   => true,
        ),
        'lastVisit'     => array(array('regex',
                                       'pattern' => self::VALID_DATE),
                                 'default'  => '0000-00-00 00:00:00',
        ),
        'totalTags'     => array('int',
                                 'default'  => 0,
        ),
        'totalItems'    => array('int',
                                 'default'  => 0,
        ),
        'userItemCount' => array('int',
                                 'default'  => 0,
        ),
        'itemCount'     => array('int',
                                 'default'  => 0,
        ),
        'tagCount'      => array('int',
                                 'default'  => 0,
        ),
    );
}
