<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/Item.php';

class ItemTest extends BaseTestCase
{
    protected   $_item1 = array(
            'itemId'        => null,
            'url'           => 'http://www.clipperz.com/',
            'urlHash'       => null,

            'userCount'     => 0,
            'ratingCount'   => 0,
            'ratingSum'     => 0,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );

    public function testItemConstructorInjectionOfProperties()
    {
        $expected                = $this->_item1;
        $expected['urlHash']     = Connexions::md5Url($expected['url']);
        $expected['userCount']   = 1;
        $expected['ratingCount'] = 5;
        $expected['ratingSum']   = 3.2;

        $data = array(
            'url'         => $expected['url'],
        );

        $item = new Model_Item( $data );

        // Make sure we can change properties
        $item->userCount   = $expected['userCount'];
        $item->ratingCount = $expected['ratingCount'];
        $item->ratingSum   = $expected['ratingSum'];

        $this->assertTrue( ! $item->isBacked() );
        $this->assertTrue( ! $item->isValid() );

        $this->assertEquals($expected,
                            $item->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testItemToArray()
    {
        $expected            = $this->_item1;
        $expected['urlHash'] = Connexions::md5Url($expected['url']);
        $expected2           = $expected;

        unset($expected2['itemId']);

        $data     = array(
            'url'         => $expected['url'],
        );

        $item = new Model_Item( $data );

        $this->assertEquals($expected,
                            $item->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
        $this->assertEquals($expected2,
                            $item->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_PUBLIC ));
    }

    public function testItemGetId()
    {
        $data     = array('itemId'  => 5);
        $expected = null;

        $item = new Model_Item( $data );

        $this->assertEquals($expected, $item->getId());
    }

    public function testItemGetMapper()
    {
        $item = new Model_Item( );

        $mapper = $item->getMapper();

        $this->assertType('Model_Mapper_Item', $mapper);
    }

    public function testItemGetFilter()
    {
        $item = new Model_Item( );

        $filter = $item->getFilter();

        //$this->assertType('Model_Filter_Item', $filter);
        $this->assertEquals(Connexions_Model::NO_INSTANCE, $filter);
    }
}
