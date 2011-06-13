<?php
/** @file
 *
 *  This is the input filter & validator for the Bookmark Domain Model.
 *
 */

class Model_Filter_Bookmark extends Connexions_Model_Filter
{
    const VALID_DATE    =
    // YYYY.mm.dd                            HH:ii:ss
    '/^[0-9]{4,}[.\-][0-9]{2,}[.\-][0-9]{2,} [0-9]{2,}:[0-9]{2,}:[0-9]{2,}$/';

    protected $_filterRules     = array(
        'userId'        => array('int'),
        'itemId'        => array('int'),

        /* 'decodeEntities' requires the addFilterPrefixPath() from the
         * __construct() of our parent class (Connexions_Model_Filter).
         */
        'name'          => array('decodeEntities', 'stripTags', 'stringTrim'),
        'description'   => array('decodeEntities', 'stripTags', 'stringTrim'),

        'rating'        => array('int'),

        'isFavorite'    => array(array('boolean',
                                       'type' => 'all'),
                                 'int'),
        'isPrivate'     => array(array('boolean',
                                       'type' => 'all'),
                                 'int'),
    );

    protected $_validatorRules  = array(
        'userId'        => array('int',
                                 'presence' => 'required' ,
        ),
        'itemId'        => array('int',
                                 'presence' => 'required' ,
        ),

        'name'          => array(array('stringLength',
                                       'min'    => 2,
                                       'max'    => 255),
                                 'presence' => 'required',
        ),
        'description'   => array('default'      => '',
                                 'allowEmpty'   => true,
        ),
        'rating'        => array('int',
                                 array('between',
                                       'min'    => 0,
                                       'max'    => 5),
                                 'allowEmpty'   => true,
        ),
        'isFavorite'    => array('int',
                                 array('between',
                                       'min'    => 0,
                                       'max'    => 1),
                                 'allowEmpty'   => true,
        ),
        'isPrivate'     => array('int',
                                 array('between',
                                       'min'    => 0,
                                       'max'    => 1),
                                 'allowEmpty'   => true,
        ),
        'taggedOn'      => array(array('regex',
                                       'pattern' => self::VALID_DATE),
                                 'default'      => '0000-00-00 00:00:00',
                                 'allowEmpty'   => true,
        ),
        'updatedOn'     => array(array('regex',
                                       'pattern' => self::VALID_DATE),
                                 'default'      => '0000-00-00 00:00:00',
                                 'allowEmpty'   => true,
        ),
    );
}

