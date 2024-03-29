<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/Tag.php';

/**
 *  @group Models
 */
class TagTest extends BaseTestCase
{
    protected $_tag1    = array(
            'tagId'         => 0,
            'tag'           => 'tagname',

            'userItemCount' => 0,
            'userCount'     => 0,
            'itemCount'     => 0,
    );

    public function testTagConstructorInjectionOfProperties()
    {
        $expected = $this->_tag1;
        $expected['userItemCount'] = 100;
        $expected['userCount']     = 5;
        $expected['itemCount']     = 25;
        $expected['tagCount']      = 32;

        $tag = new Model_Tag( array(
                             // Also test tag normalization
            'tag'         => strtoupper($expected['tag']),
        ));


        // Make sure we can change properties
        $tag->userItemCount = $expected['userItemCount'];
        $tag->userCount     = $expected['userCount'];
        $tag->itemCount     = $expected['itemCount'];
        $tag->tagCount      = $expected['tagCount'];

        $this->assertTrue( ! $tag->isBacked() );
        $this->assertTrue(   $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray(self::$toArray_shallow_all));
    }

    public function testTagToArray()
    {
        $expected  = $this->_tag1;
        $expected2 = $this->_tag1;

        //unset($expected2['tagId']);

        $tag = new Model_Tag( array(
            'tag'         => strtoupper($expected['tag']),
        ));


        $this->assertEquals($expected,
                            $tag->toArray(self::$toArray_shallow_all));
        $this->assertEquals($expected2,
                            $tag->toArray( ));
    }

    public function testTagGetId()
    {
        $data     = array('tagId'  => 5);
        $expected = $data['tagId'];

        $tag = new Model_Tag( $data );

        $this->assertEquals($expected, $tag->getId());
    }

    public function testTagGetMapper()
    {
        $tag = new Model_Tag( );

        $mapper = $tag->getMapper();

        $this->assertInstanceOf('Model_Mapper_Tag', $mapper);
    }

    public function testTagGetFilter()
    {
        $tag = new Model_Tag( );

        $filter = $tag->getFilter();

        $this->assertInstanceOf('Model_Filter_Tag', $filter);
    }
}
