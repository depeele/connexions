<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/Tag.php';

class TagTest extends BaseTestCase
{
    protected $_tag1    = array(
            'tagId'         => null,
            'tag'           => 'tagname',

            'userItemCount' => null,
            'userCount'     => null,
            'itemCount'     => null,
            'tagCount'      => null,
    );

    public function testConstructorInjectionOfProperties()
    {
        $expected = $this->_tag1;
        $expected['userItemCount'] = 100;
        $expected['userCount']     = 5;
        $expected['itemCount']     = 25;
        $expected['tagCount']      = 32;

        $data     = array(
            'tag'         => strtoupper($expected['tag']),
        );

        $tag = new Model_Tag( $data );

        // Make sure we can change properties
        $tag->userItemCount = $expected['userItemCount'];
        $tag->userCount     = $expected['userCount'];
        $tag->itemCount     = $expected['itemCount'];
        $tag->tagCount      = $expected['tagCount'];

        $this->assertTrue( ! $tag->isBacked() );
        $this->assertTrue( ! $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                           Connexions_Model::FIELDS_ALL ));
    }

    public function testToArray()
    {
        $expected  = $this->_tag1;
        $expected2 = $this->_tag1;

        unset($expected2['tagId']);

        $data     = array(
            'tag'         => strtoupper($expected['tag']),
        );

        $tag = new Model_Tag( $data );

        $this->assertEquals($expected,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                           Connexions_Model::FIELDS_ALL ));
        $this->assertEquals($expected2,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                           Connexions_Model::FIELDS_PUBLIC ));
    }

    public function testGetId()
    {
        $data     = array('tagId'  => 5);
        $expected = null;

        $tag = new Model_Tag( $data );

        $this->assertEquals($expected, $tag->getId());
    }

    public function testGetMapper()
    {
        $tag = new Model_Tag( );

        $mapper = $tag->getMapper();

        $this->assertType('Model_Mapper_Tag', $mapper);
    }

    public function testGetFilter()
    {
        $tag = new Model_Tag( );

        $filter = $tag->getFilter();

        //$this->assertType('Model_Filter_Tag', $filter);
        $this->assertEquals(Connexions_Model::NO_INSTANCE, $filter);
    }
}
