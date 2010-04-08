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
        // Generate a Zend_Db_Select instance
        $db     = Connexions::getDb();
        $table  = Connexions_Model::metaData('table', self::MEMBER_CLASS);

        $select = $db->select()
                     ->from(array('ua' => $table))
                     ->where('ua.userId = ?', $userId)
                     ->order('authType DESC');

        $this->_userId  = $userId;

        /*
        Connexions::log(
                sprintf("Model_UserAuthSet: select[ %s ]<br />\n",
                        $select->assemble()) );
        // */

        return parent::__construct($select, self::MEMBER_CLASS);
    }
}
