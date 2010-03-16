<?php
/** @file
 *
 *  A set of Model_UserAuth instances.
 *
 */

class Model_UserAuthSet extends Connexions_Set
{
    const       MEMBER_CLASS    = 'Model_UserAuth';

    protected   $_userId        = null;

    /** @brief  Create a new instance.
     *  @param  userId      The identifier of the target user.
     *
     */
    public function __construct($userId)
    {
        $memberClass  = self::MEMBER_CLASS;

        // Generate a Zend_Db_Select instance
        $db     = Connexions::getDb();
        $table  = Connexions_Model::metaData('table', $memberClass);

        $select = $db->select()
                     ->from(array('ua' => $table))
                     ->where('ua.userId = ?', $userId)
                     ->order('authType DESC');

        // Include '_memberClass' in $select so we can use 'Connexions_Set'
        $select->_memberClass = $memberClass;

        $this->_userId  = $userId;

        /*
        Connexions::log(
                sprintf("Model_UserAuthSet: select[ %s ]<br />\n",
                        $select->assemble()) );
        // */

        return parent::__construct($select, $memberClass);
    }
}
