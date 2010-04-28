<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/Item.php';

class ItemTest extends BaseTestCase
{
    protected   $_item1 = array(
            'itemId'        => 0,
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
        $expected['ratingSum']   = 3;

        $item = new Model_Item( array(
                        // vvv should be trimmed by filter
            'url'       => '   '. $expected['url']. '   ',
                        // vvv should be filtered to an integer 3
            'ratingSum' => $expected['ratingSum'] + 0.2,
        ));


        // Make sure we can change properties
        $item->userCount   = $expected['userCount'];
        $item->ratingCount = $expected['ratingCount'];
        $item->ratingSum   = $expected['ratingSum'];

        $this->assertTrue( ! $item->isBacked() );
        $this->assertTrue(   $item->isValid() );

        $this->assertEquals($expected,
                            $item->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testItemToArray()
    {
        $expected            = $this->_item1;
        $expected['urlHash'] = Connexions::md5Url($expected['url']);
        $expected2           = $expected;

        $item = new Model_Item( array(
            'url'         => $expected['url'],
        ));

        $this->assertEquals($expected,
                            $item->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
        $this->assertEquals($expected2,
                            $item->toArray( ));
    }

    public function testItemGetId()
    {
        $data     = array('itemId'  => 5);
        $expected = $data['itemId'];

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

        $this->assertType('Model_Filter_Item', $filter);
    }
}
