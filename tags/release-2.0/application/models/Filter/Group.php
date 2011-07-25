<?php
/** @file
 *
 *  This is the input filter & validator for the Group Domain Model.
 *
 */

class Model_Filter_Group extends Connexions_Model_Filter
{
    const VALID_DATE    =
    // YYYY.mm.dd                            HH:ii:ss
    '/^[0-9]{4,}[.\-][0-9]{2,}[.\-][0-9]{2,} [0-9]{2,}:[0-9]{2,}:[0-9]{2,}$/';

    protected $_filterRules     = array(
        'groupId'       => array('int'),
        'ownerId'       => array('int'),

        /* 'decodeEntities' requires the addFilterPrefixPath() from the
         * __construct() of our parent class (Connexions_Model_Filter).
         */
        'name'          => array('decodeEntities', 'stripTags', 'stringTrim'),
        'groupType'     => array('decodeEntities', 'stripTags', 'stringTrim'),
        'controlMembers'=> array('decodeEntities', 'stripTags', 'stringTrim'),
        'controlItems'  => array('decodeEntities', 'stripTags', 'stringTrim'),
        'visibility'    => array('decodeEntities', 'stripTags', 'stringTrim'),

        'canTransfer'   => array(array('boolean',
                                       'type' => 'all'),
                                 'int'),
    );

    protected $_validatorRules  = array(
        'groupId'       => array('int',
                                 'presence' => 'required' ,
        ),
        'ownerId'       => array('int',
                                 'presence' => 'required' ,
        ),

        'name'          => array(array('stringLength',
                                       'min'    => 2,
                                       'max'    => 127),
                                 'presence' => 'required',
        ),
        'groupType'     => array('default'          => 'tag',
                                 array('inArray',
                                       'haystack'   => array(
                                           'user',
                                           'item',
                                           'tag',
                                       ),
                                 ),
        ),
        'controlMembers'=> array('default'          => 'owner',
                                 array('inArray',
                                       'haystack'   => array(
                                           'owner',
                                           'group',
                                       ),
                                 ),
        ),
        'controlItems'  => array('default'          => 'owner',
                                 array('inArray',
                                       'haystack'   => array(
                                           'owner',
                                           'group',
                                       ),
                                 ),
        ),
        'visibility'    => array('default'          => 'private',
                                 array('inArray',
                                       'haystack'   => array(
                                           'private',
                                           'group',
                                           'public',
                                       ),
                                 ),
        ),
        'canTransfer'   => array('default'      => 0,
                                 'int',
                                 array('between',
                                       'min'    => 0,
                                       'max'    => 1),
                                 'allowEmpty'   => true,
        ),
    );
}
