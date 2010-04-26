<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/Bookmark.php';

class BookmarkTest extends BaseTestCase
{
    protected function _doTest($data)
    {
        $filter  = new Model_Filter_Bookmark($data);
        $errors  = $filter->getErrors();
        echo "Errors:\n";
        print_r($errors);

        $invalid = $filter->getInvalid();
        echo "Invalids:\n";
        print_r($invalid);

        $missing = $filter->getMissing();
        echo "Mmissing:\n";
        print_r($missing);

        $unknown = $filter->getUnknown();
        echo "Unknowns:\n";
        print_r($unknown);

        $parsed = array(
            'userId'        => $filter->getUnescaped('userId'),
            'itemId'        => $filter->getUnescaped('itemId'),

            'name'          => $filter->getUnescaped('name'),
            'description'   => $filter->getUnescaped('description'),
            'rating'        => $filter->getUnescaped('rating'),
            'isFavorite'    => $filter->getUnescaped('isFavorite'),
            'isPrivate'     => $filter->getUnescaped('isPrivate'),
            'taggedOn'      => $filter->getUnescaped('taggedOn'),
            'updatedOn'     => $filter->getUnescaped('updatedOn'),
        );
        echo "Parsed Data:\n";
        print_r($parsed);

        echo "------------------------------------------------------\n";
    }

    public function testBookmarkFilter1()
    {
        $data   = array(
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

        $this->_doTest($data);
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

        $this->_doTest($data);
    }

    public function testBookmarkFilterMissingUserId()
    {
        $data   = array(
            'itemId'        => 2,

            'name'          => 'Test Bookmark Name',
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            'rating'        => 3,
            'isFavorite'    => 'yes',
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );

        $this->_doTest($data);
    }

    public function testBookmarkFilterMissingItemId()
    {
        $data   = array(
            'userId'        => 1,

            'name'          => 'Test Bookmark Name',
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            'rating'        => 3,
            'isFavorite'    => 'yes',
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );

        $this->_doTest($data);
    }

    public function testBookmarkFilterNameTooShort()
    {
        $data   = array(
            'userId'        => 1,
            'itemId'        => 2,

            'name'          => 'T',
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            'rating'        => 3,
            'isFavorite'    => 'yes',
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );

        $this->_doTest($data);
    }

    public function testBookmarkFilterNameTooLong()
    {
        $data   = array(
            'userId'        => 1,
            'itemId'        => 2,

            'name'          => 'Test Bookmark Name-'. str_repeat('.', 255),
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            'rating'        => 3,
            'isFavorite'    => 'yes',
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );

        $this->_doTest($data);
    }

    public function testBookmarkFilterRatingTooLow()
    {
        $data   = array(
            'userId'        => 1,
            'itemId'        => 2,

            'name'          => 'Test Bookmark Name',
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            'rating'        => -1,
            'isFavorite'    => 'yes',
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );

        $this->_doTest($data);
    }

    public function testBookmarkFilterRatingTooHigh()
    {
        $data   = array(
            'userId'        => 1,
            'itemId'        => 2,

            'name'          => 'Test Bookmark Name',
            'description'   => '<p>Test Bookmark <i>Description</i></p>   ',
            'rating'        => 10,
            'isFavorite'    => 'yes',
            'isPrivate'     => 'no',
            'taggedOn'      => date('Y.m.d H:i:s'),
            'updatedOn'     => date('Y.m.d H:i:s'),
        );

        $this->_doTest($data);
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

        $this->_doTest($data);
    }
}
