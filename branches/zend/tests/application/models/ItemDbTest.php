<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/models/Item.php';

/**
 *  @group Mappers
 */
class ItemDbTest extends DbTestCase
{
    protected   $_item1 = array(
            'itemId'        => 1,
            'url'           => 'http://www.clipperz.com/',
            'urlHash'       => '383cb614a2cc9247b86cad9a315d02e3',

            'userCount'     => 1,
            'ratingCount'   => 1,
            'ratingSum'     => 1,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );
    protected   $_item3 = array(
            'itemId'        => 3,
            'url'           => 'http://demo.openlinksw.com/weblog/demo/?id=1',
            'urlHash'       => '052973b1ac311978abdc0413daa1d5db',

            'userCount'     => 1,
            'ratingCount'   => 0,
            'ratingSum'     => 0,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );
    protected   $_item4 = array(
            'itemId'        => 4,
            'url'           => 'http://demo.openlinksw.com/DAV/JS/demo/index.html',
            'urlHash'       => '52cda3e66df5938103c48725357c59ab',

            'userCount'     => 2,
            'ratingCount'   => 0,
            'ratingSum'     => 0,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );

    protected function getDataSet()
    {
        //Connexions::log("ItemDbTest::getDataSet()");

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

    public function testItemRetrieveById1()
    {
        $expected  = $this->_item1;

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Item');
        $item     = $mapper->find( array('itemId' => $expected['itemId']) );

        $this->assertEquals($expected,
                            $item->toArray(self::$toArray_deep_all));
    }

    public function testItemIdentityMap()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Item');
        $id     = array( 'itemId' => $this->_item1['itemId'] );

        $item  = $mapper->find( $id );
        $item2 = $mapper->find( $id );

        $this->assertSame( $item, $item2 );
    }

    public function testItemInvalidate()
    {
        $expected  = $this->_item1;

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Item');
        $item     = $mapper->find( array( 'itemId' => $expected['itemId']) );

        $this->assertEquals($expected,
                            $item->toArray(self::$toArray_shallow_all));
    }

    public function testItemSimilar()
    {
        $expected = '52cda3e66df5938103c48725357c59ab';

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Item');
        $items    = $mapper->fetchSimilar( array(
                                'itemId' => $this->_item3['itemId']
                    ));

        $this->assertEquals($expected,
                            $items->__toString());
    }
}
