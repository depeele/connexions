<?php
$config = new Zend_Config_Ini(
                    APPLICATION_PATH . '/configs/application.ini',
                    APPLICATION_ENV);
Zend_Registry::set('config', $config);

$application = new Zend_Application(APPLICATION_ENV, $config);
$application->bootstrap('common');

abstract class ModelTestCase extends Zend_Test_PHPUnit_DatabaseTestCase
{
    private $_connectionMock    = null;

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
