<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/Bookmark.php';

/**
 *  @group Filters
 */
class BookmarkFilterTest extends BaseTestCase
{
    protected function _getParsed($filter)
    {
        $data = array(
            'userId'      => $filter->getUnescaped('userId'),
            'itemId'      => $filter->getUnescaped('itemId'),
            'name'        => $filter->getUnescaped('name'),
            'description' => $filter->getUnescaped('description'),
            'rating'      => $filter->getUnescaped('rating'),
            'isFavorite'  => $filter->getUnescaped('isFavorite'),
            'isPrivate'   => $filter->getUnescaped('isPrivate'),
            'taggedOn'    => $filter->getUnescaped('taggedOn'),
            'updatedOn'   => $filter->getUnescaped('updatedOn'),
        );

        return $data;
    }

    protected function _outputInfo($filter, $data)
    {
        $errors  = $filter->getErrors();
        echo "Errors:\n";
        print_r($errors);

        $invalid = $filter->getInvalid();
        echo "Invalids:\n";
        print_r($invalid);

        $missing = $filter->getMissing();
        echo "Missing:\n";
        print_r($missing);

        $unknown = $filter->getUnknown();
        echo "Unknowns:\n";
        print_r($unknown);

        $parsed = $this->_getParsed($filter);
        echo "Parsed Data:\n";
        print_r($parsed);

        echo "------------------------------------------------------\n";
    }

    public function testBookmarkFilter1()
    {
        $data       = array(
            'userId'        => 1,
            'itemId'        => 2,

            'name'          => 'Test Bookmark Name',
            'description'   => 'Test Bookmark Description',
            'rating'        => 3,
            'isFavorite'    => true,
            'isPrivate'     => false,
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );
        $expected               = $data;
        $expected['isFavorite'] = ($expected['isFavorite'] ? 1 : 0);
        $expected['isPrivate']  = ($expected['isPrivate']  ? 1 : 0);

        $filter  = new Model_Filter_Bookmark($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertTrue ( $filter->isValid()  );

        $this->assertEquals($expected, $this->_getParsed($filter));

        //$this->_outputInfo($filter, $data);
    }

    public function testBookmarkFilter2()
    {
        $data   = array(
            'userId'        => 1,
            'itemId'        => 2,

            'name'          => 'Test Bookmark Name',
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            'rating'        => 3,
            'isFavorite'    => 'yes',
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );
        $expected                = $data;
        $expected['description'] = trim(
                                    strip_tags($expected['description']));
        $expected['isFavorite']  = 1;
        $expected['isPrivate']   = 0;

        $filter  = new Model_Filter_Bookmark($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertTrue ( $filter->isValid()  );

        $this->assertEquals($expected, $this->_getParsed($filter));

        //$this->_outputInfo($filter, $data);
    }

    public function testBookmarkFilterMissingUserId()
    {
        $data   = array(
            // Missing 'userId'
            'itemId'        => 2,

            'name'          => 'Test Bookmark Name',
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            'rating'        => 3,
            'isFavorite'    => 'yes',
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );

        $filter  = new Model_Filter_Bookmark($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertTrue ( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        //$this->_outputInfo($filter, $data);
    }

    public function testBookmarkFilterMissingItemId()
    {
        $data   = array(
            'userId'        => 1,
            // Missing itemId

            'name'          => 'Test Bookmark Name',
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            'rating'        => 3,
            'isFavorite'    => 'yes',
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );

        $filter  = new Model_Filter_Bookmark($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertTrue ( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        //$this->_outputInfo($filter, $data);
    }

    public function testBookmarkFilterNameTooShort()
    {
        $data   = array(
            'userId'        => 1,
            'itemId'        => 2,

            // Name too short
            'name'          => 'T',
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            'rating'        => 3,
            'isFavorite'    => 'yes',
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );

        $filter  = new Model_Filter_Bookmark($data);

        $this->assertTrue ( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        //$this->_outputInfo($filter, $data);
    }

    public function testBookmarkFilterNameTooLong()
    {
        $data   = array(
            'userId'        => 1,
            'itemId'        => 2,

            // Name too long
            'name'          => 'Test Bookmark Name-'. str_repeat('.', 255),
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            'rating'        => 3,
            'isFavorite'    => 'yes',
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );

        $filter  = new Model_Filter_Bookmark($data);

        $this->assertTrue ( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        //$this->_outputInfo($filter, $data);
    }

    public function testBookmarkFilterRatingTooLow()
    {
        $data   = array(
            'userId'        => 1,
            'itemId'        => 2,

            'name'          => 'Test Bookmark Name',
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            // Invalid rating - not between 0 and 5
            'rating'        => -1,
            'isFavorite'    => 'yes',
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );

        $filter  = new Model_Filter_Bookmark($data);

        $this->assertTrue ( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        //$this->_outputInfo($filter, $data);
    }

    public function testBookmarkFilterRatingTooHigh()
    {
        $data   = array(
            'userId'        => 1,
            'itemId'        => 2,

            'name'          => 'Test Bookmark Name',
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            // Invalid rating - not between 0 and 5
            'rating'        => 10,
            'isFavorite'    => 'yes',
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );

        $filter  = new Model_Filter_Bookmark($data);

        $this->assertTrue ( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        //$this->_outputInfo($filter, $data);
    }

    public function testBookmarkFilterisFavorite2()
    {
        $data   = array(
            'userId'        => 1,
            'itemId'        => 2,

            'name'          => 'Test Bookmark Name',
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            'rating'        => 3,
            'isFavorite'    => 2,
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );
        $expected                = $data;
        $expected['description'] = trim(
                                    strip_tags($expected['description']));
        $expected['isFavorite']  = 1;
        $expected['isPrivate']   = 0;

        $filter  = new Model_Filter_Bookmark($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertTrue ( $filter->isValid()  );

        $this->assertEquals($expected, $this->_getParsed($filter));
        //$this->_outputInfo($filter, $data);
    }
}
