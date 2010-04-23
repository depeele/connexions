<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/Bookmark.php';

class BookmarkTest extends BaseTestCase
{
    protected   $_bookmark1 = array(
            'user'          => null,
            'item'          => null,
            'tags'          => null,

            'name'          => 'New Bookmark',
            'description'   => 'This is a new bookmark',
            'rating'        => null,
            'isFavorite'    => null,
            'isPrivate'     => null,
            'taggedOn'      => null,
            'updatedOn'     => null,
    );

    public function testBookmarkConstructorInjectionOfProperties()
    {
        $expected   = $this->_bookmark1;
        $expected['rating']     = 4;
        $expected['isFavorite'] = true;
        $expected['isPrivate']  = false;
        $expected['taggedOn']   = '2010.04.15 12:37:00';

        $data       = array(
            'name'        => $expected['name'],
            'description' => $expected['description']
        );

        $bookmark = new Model_Bookmark( $data );

        // Make sure we can change properties
        $bookmark->rating     = $expected['rating'];
        $bookmark->isFavorite = $expected['isFavorite'];
        $bookmark->isPrivate  = $expected['isPrivate'];
        $bookmark->taggedOn   = $expected['taggedOn'];

        $this->assertFalse( $bookmark->isBacked() );
        $this->assertFalse( $bookmark->isValid() );

        $this->assertEquals($expected,
                            $bookmark->toArray( Connexions_Model::DEPTH_SHALLOW,
                                                Connexions_Model::FIELDS_ALL ));
    }

    public function testBookmarkToArray()
    {
        $expected  = $this->_bookmark1;
        $expected2 = $expected;

        $data     = array(
            'name'        => $expected['name'],
            'description' => $expected['description']
        );

        $bookmark = new Model_Bookmark( $data );

        $this->assertEquals($expected,
                            $bookmark->toArray(Connexions_Model::DEPTH_SHALLOW,
                                               Connexions_Model::FIELDS_ALL ));
        $this->assertEquals($expected2,
                            $bookmark->toArray(Connexions_Model::DEPTH_SHALLOW,
                                               Connexions_Model::FIELDS_PUBLIC ));
    }

    public function testBookmarkGetId()
    {
        $expected = null;

        $bookmark = new Model_Bookmark( );

        $this->assertEquals($expected, $bookmark->getId());
    }

    public function testBookmarkGetMapper()
    {
        $bookmark = new Model_Bookmark( );

        $mapper = $bookmark->getMapper();

        $this->assertType('Model_Mapper_Bookmark', $mapper);
    }

    public function testBookmarkGetFilter()
    {
        $bookmark = new Model_Bookmark( );

        $filter = $bookmark->getFilter();

        //$this->assertType('Model_Filter_Bookmark', $filter);
        $this->assertEquals(Connexions_Model::NO_INSTANCE, $filter);
    }
}
