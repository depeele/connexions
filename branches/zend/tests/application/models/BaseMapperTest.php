<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/models/Mapper/Base.php';

class BaseMapperDbTest extends DbTestCase
{
    protected function getDataSet()
    {
        //Connexions::log("BookmarkDbTest::getDataSet()");

        return $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/5users.xml');
    }

    protected function tearDown()
    {
        /* Since these tests setup and teardown the database for each new test,
         * we need to clean-up any Identity Maps that are used in order to 
         * maintain test validity.
         */
        $uMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $uMapper->flushIdentityMap();

        $iMapper = Connexions_Model_Mapper::factory('Model_Mapper_Item');
        $iMapper->flushIdentityMap();

        $tMapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tMapper->flushIdentityMap();

        $bMapper = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bMapper->flushIdentityMap();


        parent::tearDown();
    }


    public function testBaseMapperRelated1()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');

        $set = $mapper->fetchRelated(array(//'users' => array('1'),
                                           'order' => 'ratingAvg'));

        //echo $set->debugDump(), "\n";
    }
}
