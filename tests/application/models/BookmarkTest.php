<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/Bookmark.php';

class BookmarkTest extends BaseTestCase
{
    protected   $_bookmark1 = array(
            'userId'        => 0,
            'itemId'        => 0,

            'name'          => 'New Bookmark',
            'description'   => 'This is a new bookmark',
            'rating'        => null,
            'isFavorite'    => true,
            'isPrivate'     => true,
            'taggedOn'      => null,
            'updatedOn'     => null,
    );

    public function testBookmarkConstructorInjectionOfProperties()
    {
        $expected   = $this->_bookmark1;
        $expected['rating']     = 4;
        $expected['isFavorite'] = ($expected['isFavorite'] ? 1 : 0);
        $expected['isPrivate']  = ($expected['isPrivate']  ? 1 : 0);
        $expected['taggedOn']   = '2010.04.15 12:37:00';

        $bookmark = new Model_Bookmark( array(
            'name'        => $expected['name'],
            'description' => $expected['description'],
        ));


        // Make sure we can change properties
        $bookmark->rating     = $expected['rating'];
        $bookmark->isFavorite = ($expected['isFavorite'] ? 'yes'  : 'no');
        $bookmark->isPrivate  = ($expected['isPrivate']  ? 'true' : 'false');
        $bookmark->taggedOn   = $expected['taggedOn'];

        //printf ("Bookmark: [ %s ]\n", $bookmark->debugDump());

        $this->assertFalse( $bookmark->isBacked() );
        $this->assertTrue ( $bookmark->isValid() );

        // updatedOn is dynamic
        $expected['updatedOn'] = $bookmark->updatedOn;

        $this->assertEquals($expected,
                            $bookmark->toArray( Connexions_Model::DEPTH_SHALLOW,
                                                Connexions_Model::FIELDS_ALL ));
    }

    public function testBookmarkToArray()
    {
        $expected  = $this->_bookmark1;
        $expected['rating']     = (int)$expected['rating'];
        $expected['isFavorite'] = ($expected['isFavorite'] ? 1 : 0);
        $expected['isPrivate']  = ($expected['isPrivate']  ? 1 : 0);

        $expected2 = $expected;

        $data     = array(
            'name'        => $expected['name'],
            'description' => $expected['description'],
            'rating'      => $expected['rating'],
            'isFavorite'  => $expected['isFavorite'],
            'isPrivate'   => $expected['isPrivate'],
        );

        $bookmark = new Model_Bookmark( $data );

        // 'updatedOn' is dynamic (as is 'taggedOn' if not provided).
        $expected['updatedOn'] = $expected2['updatedOn'] = $bookmark->updatedOn;
        $expected['taggedOn']  = $expected2['taggedOn']  = $bookmark->taggedOn;

        $this->assertEquals($expected,
                            $bookmark->toArray(Connexions_Model::DEPTH_SHALLOW,
                                               Connexions_Model::FIELDS_ALL ));
        $this->assertEquals($expected2,
                            $bookmark->toArray());
    }

    public function testBookmarkGetId()
    {
        $expected = array(0, 0);

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

        $this->assertType('Model_Filter_Bookmark', $filter);
    }
}
