<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/services/Bookmark.php';

class BookmarkServiceTest extends DbTestCase
{
    protected   $_user1 = array(
            'userId'        => 1,
            'name'          => 'User1',
            'fullName'      => 'Random User 1',
            'email'         => 'User1@home.com',
            'apiKey'        => 'edOEMfwY6d',
            'pictureUrl'    => '/connexions/images/User1.png',
            'profile'       => null,
            'lastVisit'     => '2007-04-12 12:38:02',

            'totalTags'     => 24,
            'totalItems'    => 5,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );
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
    protected   $_tags1 = array(
            array('tagId' =>        1,
                  'tag'   => 'security',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        2,
                  'tag'   => 'passwords',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        4,
                  'tag'   => 'privacy',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        5,
                  'tag'   => 'identity',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        6,
                  'tag'   => 'web2.0',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        7,
                  'tag'   => 'online',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        8,
                  'tag'   => 'password',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        9,
                  'tag'   => 'storage',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>       10,
                  'tag'   => 'ajax',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>       11,
                  'tag'   => 'tools',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>       71,
                  'tag'   => 'test',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>       72,
                  'tag'   => 'cryptography',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
    );
    protected   $_bookmark1 = array(
            'userId'        => null,    // $this->_user1,
            'itemId'        => null,    // $this->_item1,

            'name'          => 'More than a password manager | Clipperz',
            'description'   => 'Testing 1,2 3, 4...',
            'rating'        => 1,
            'isFavorite'    => 0,
            'isPrivate'     => 1,
            'taggedOn'      => '2010-04-05 17:25:19',
            'updatedOn'     => '2010-02-22 10:00:00',
    );

    protected function getDataSet()
    {
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

    public function testBookmarkServiceFactory()
    {
        $service1 = Connexions_Service::factory('Model_Bookmark');
        $this->assertTrue( $service1 instanceof Connexions_Service );
        $this->assertTrue( $service1 instanceof Service_Bookmark );

        $service2 = Connexions_Service::factory('Service_Bookmark');
        $this->assertTrue( $service2 instanceof Connexions_Service );
        $this->assertTrue( $service2 instanceof Service_Bookmark );
        $this->assertSame( $service1, $service2 );
    }

    public function testBookmarkRetrieveById1()
    {
        $expected               = $this->_bookmark1;
        $expected['userId']     = $this->_user1['userId'];
        $expected['itemId']     = $this->_item1['itemId'];
        $expected['user']       = $this->_user1;
        $expected['item']       = $this->_item1;
        $expected['tags']       = $this->_tags1;
        $expected['isFavorite'] = ($expected['isFavorite'] ? 1 : 0);
        $expected['isPrivate']  = ($expected['isPrivate']  ? 1 : 0);

        $service  = Connexions_Service::factory('Model_Bookmark');
        $bookmark = $service->find( array( $expected['userId'],
                                           $expected['itemId']) );

        $this->assertEquals($expected,
                            $bookmark->toArray( Connexions_Model::DEPTH_DEEP,
                                                Connexions_Model::FIELDS_ALL ));
    }

    public function testBookmarkServiceFetchByTags()
    {
        //            vv ordered by 'tagCount DESC'
        $expected   = '1:2,1:3,1:4,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByTags( array( 6, 12 ) );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchByUsers()
    {
        //            vv ordered by 'userCount DESC'
        $expected   = '1:1,1:5,3:4,3:6,1:4,1:2,1:3,3:8,3:10,3:9';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByUsers( array( 1, 3 ) );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchByItems()
    {
        //            vv ordered by 'itemCount DESC'
        $expected   = '2:6,3:6,4:6,4:12';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByItems( array( 6, 12 ) );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchByUsersAndTags()
    {
        //            vv ordered by 'userCount,tagCount DESC'
        $expected   = '3:4,1:1,1:2,1:3,1:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByUsersAndTags( array( 1, 3 ),
                                                     array( 6, 10 ) );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchByItemsAndTags()
    {
        //            vv ordered by 'itemCount,tagCount DESC'
        $expected   = '1:2,1:4,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByItemsAndTags( array( 2,  4 ),
                                                     array( 6, 12 ) );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }
}
