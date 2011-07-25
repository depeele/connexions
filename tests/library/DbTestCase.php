<?php
/** @file
 *
 *  Base class for a database-related test case
 */
abstract class DbTestCase extends Zend_Test_PHPUnit_DatabaseTestCase
{
    public  $application        = null;
    private $_connectionMock    = null;

    public function __construct(       $name     = NULL,
                                 array $data     = array(),
                                       $dataName = '')
    {
        global  $application;
        $this->application = $application;

        parent::__construct($name, $data, $dataName);
    }

    protected function getConnection()
    {
        if ($this->_connectionMock === null)
        {
            $db = Zend_Registry::get('db');
            $this->_connectionMock = $this->createZendDbConnection(
                                        $db, 'zfunittests');
            Zend_Db_Table_Abstract::setDefaultAdapter($db);
        }

        return $this->_connectionMock;
    }

    //protected abstract function getDataSet();
}
