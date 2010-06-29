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
    protected   $_item12 = array(
            'itemId'        => 12,
            'url'           => 'http://www.textpattern.com/',
            'urlHash'       => '24032aec5555f2361ebf683f9214fb0f',

            'userCount'     => 1,
            'ratingCount'   => 0,
            'ratingSum'     => 0,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );
    protected   $_tags1 = array(
            array('tagId' =>       10,
                  'tag'   => 'ajax',
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
            array('tagId' =>        5,
                  'tag'   => 'identity',
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
            array('tagId' =>        1,
                  'tag'   => 'security',
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
            array('tagId' =>       71,
                  'tag'   => 'test',
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
            array('tagId' =>        6,
                  'tag'   => 'web2.0',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
    );
    protected   $_tagsNew = array(
            array('tagId' =>       74,
                  'tag'   => 'test tag 1',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>       75,
                  'tag'   => 'test tag 2',
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
        $id                     = array( $expected['userId'],
                                         $expected['itemId']);

        $service  = Connexions_Service::factory('Model_Bookmark');
        $bookmark = $service->find( $id );

        $this->assertNotEquals(null, $bookmark);
        $this->assertEquals($expected,
                            $bookmark->toArray( Connexions_Model::DEPTH_DEEP,
                                                Connexions_Model::FIELDS_ALL ));
    }

    public function testBookmarkRetrieveById2()
    {
        $expected               = $this->_bookmark1;
        $expected['userId']     = $this->_user1['userId'];
        $expected['itemId']     = $this->_item1['itemId'];
        $expected['user']       = $this->_user1;
        $expected['item']       = $this->_item1;
        $expected['tags']       = $this->_tags1;
        $expected['isFavorite'] = ($expected['isFavorite'] ? 1 : 0);
        $expected['isPrivate']  = ($expected['isPrivate']  ? 1 : 0);
        $id                     = array( 'userId' => $expected['userId'],
                                         'itemId' => $expected['itemId']);

        $service  = Connexions_Service::factory('Model_Bookmark');
        $bookmark = $service->find( $id );

        $this->assertNotEquals(null, $bookmark);
        $this->assertEquals($expected,
                            $bookmark->toArray( Connexions_Model::DEPTH_DEEP,
                                                Connexions_Model::FIELDS_ALL ));
    }

    public function testBookmarkRetrieveById3()
    {
        $expected               = $this->_bookmark1;
        $expected['userId']     = $this->_user1['userId'];
        $expected['itemId']     = $this->_item1['itemId'];
        $expected['user']       = $this->_user1;
        $expected['item']       = $this->_item1;
        $expected['tags']       = $this->_tags1;
        $expected['isFavorite'] = ($expected['isFavorite'] ? 1 : 0);
        $expected['isPrivate']  = ($expected['isPrivate']  ? 1 : 0);
        $id                     = $expected['userId']
                                .  ':'
                                . $expected['itemId'];

        $service  = Connexions_Service::factory('Model_Bookmark');
        $bookmark = $service->find( $id );

        $this->assertNotEquals(null, $bookmark);
        $this->assertEquals($expected,
                            $bookmark->toArray( Connexions_Model::DEPTH_DEEP,
                                                Connexions_Model::FIELDS_ALL ));
    }

    public function testBookmarkServiceFetch1()
    {
        /* Note:
         *  For this test to succeed, Zend_Db_Adapter_Abstract::quote()
         *  MUST be patched.  In the 'if (is_array($value))' body,
         *  change:
         *      return implode(', ', $value);
         *
         *  to:
         *      return '('. implode(', ', $value) .')';
         *
         * and ensure that Connexions_Model_Mapper_DbTable::_where() has, for
         * the body of its 'if (is_array($value))':
         *      $condition .= ' IN ?';
         *
         * and NOT:
         *      $condition .= ' IN (?)';
         *
         * and ensure that Model_Mapper_Base::_includeSecondarySelect() also
         * uses ' IN ?' instead of ' IN (?)';
         *
         *
         */
        $expected   = '1:2,1:4,1:5,3:4,4:15';
        $expectedAr = array(array(1,2),
                            array(1,4),
                            array(1,5),
                            array(3,4),
                            array(4,15),
                      );
        $fetchAr    = $expectedAr;

        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetch( $fetchAr, 'updatedOn DESC' );

        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $ids        = $bookmarks->getIds();
        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected,   $bookmarks);
        $this->assertEquals($expectedAr, $ids);
    }

    public function testBookmarkServiceFetch2()
    {
        $expected   = '1:2,1:4,1:5,3:4,4:15';
        $expectedAr = array(array(1,2),
                            array(1,4),
                            array(1,5),
                            array(3,4),
                            array(4,15),
                      );
        $fetchAr    = array( 'userId' => array(1,3,4),
                             'itemId' => array(2,4,5,15) );

        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetch( $fetchAr,
                                       array('updatedOn' => 'DESC' ));

        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $ids        = $bookmarks->getIds();
        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected,   $bookmarks);
        $this->assertEquals($expectedAr, $ids);
    }


    public function testBookmarkServiceFetchByTagsAny1()
    {
        $expected   = '1:2,1:4,3:4,1:5,4:15';
        $expectedAr = array( array(1,2),
                             array(1,4),
                             array(3,4),
                             array(1,5),
                             array(4,15)
                      );
        $service    = Connexions_Service::factory('Model_Bookmark');
        $ids        = array( 6, 12 );

        $bookmarks  = $service->fetchByTags( $ids, false );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $ids        = $bookmarks->getIds();
        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected,   $bookmarks);
        $this->assertEquals($expectedAr, $ids);
    }

    public function testBookmarkServiceFetchByTagsAny2()
    {
        $expected   = '1:2,1:4,3:4,1:5,4:15';
        $expectedAr = array( array(1,2),
                             array(1,4),
                             array(3,4),
                             array(1,5),
                             array(4,15)
                      );
        $service    = Connexions_Service::factory('Model_Bookmark');
        $ids        = array( 'web2.0', 'javascript' );

        $bookmarks  = $service->fetchByTags( $ids, false );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $ids        = $bookmarks->getIds();
        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected,   $bookmarks);
        $this->assertEquals($expectedAr, $ids);
    }

    public function testBookmarkServiceFetchByTagsAny3()
    {
        $expected   = '1:2,1:4,3:4,1:5,4:15';
        $expectedAr = array( array(1,2),
                             array(1,4),
                             array(3,4),
                             array(1,5),
                             array(4,15)
                      );
        $service    = Connexions_Service::factory('Model_Bookmark');
        $ids        = 'web2.0,javascript';

        $bookmarks  = $service->fetchByTags( $ids, false );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $ids        = $bookmarks->getIds();
        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected,   $bookmarks);
        $this->assertEquals($expectedAr, $ids);
    }

    public function testBookmarkServiceFetchByTagsAnyAuthenticated()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '1:2,1:3,1:4,3:4,1:1,1:5,4:15';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByTags( array( 6, 12 ), false );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchByTagsExact()
    {
        $expected   = '1:2,1:4,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByTags( array( 6, 12 ) );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchByTagsExactAuthenticated()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '1:2,1:3,1:4,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByTags( array( 6, 12 ) );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchByUsers1()
    {
        $expected   = '1:2,1:4,1:5,3:9,3:10,3:6,3:8,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByUsers( array( 1, 3 ) );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchByUsersAuthenticated()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '1:1,1:2,1:3,1:4,1:5,3:9,3:10,3:6,3:8,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByUsers( array( 1, 3 ) );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchByItems()
    {
        $expected   = '4:6,2:6,3:6,4:12';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByItems( array( 6, 12 ) );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchByItemsAuthenticated()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '4:6,2:6,3:6,4:12';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByItems( array( 6, 12 ) );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchByUsersAndTagsExact()
    {
        $expected   = '1:2,1:4,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByUsersAndTags( array( 1, 3, 4 ), // users
                                                     array( 12, 13 ) );// tags
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchByUsersAndTagsExactAuthenticated()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '1:2,1:3,1:4,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByUsersAndTags( array( 1, 3, 4 ), // users
                                                     array( 12, 13 ) );// tags
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchByUsersAndTagsAny()
    {
        $expected   = '1:2,1:4,1:5,4:15,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByUsersAndTags( array( 1, 3, 4 ), // users
                                                     array( 12, 13 ),  // tags
                                                     false);           //!exact
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchByUsersAndTagsAnyAuthenticated()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '1:2,1:3,1:4,1:5,4:15,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByUsersAndTags( array( 1, 3, 4 ), // users
                                                     array( 12, 13 ),  // tags
                                                     false);           //!exact
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchByItemsAndTagsExact()
    {
        $expected   = '4:15,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByItemsAndTags(
                                    // items
                                    array( 3, 4, 6, 7, 8, 9, 10, 11, 13, 15),
                                    // tags
                                    array( 12, 73 ) );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchByItemsAndTagsExactAuthenticated()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '4:15,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByItemsAndTags(
                                    // items
                                    array( 3, 4, 6, 7, 8, 9, 10, 11, 13, 15),
                                    // tags
                                    array( 12, 73 ) );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchByItemsAndTagsAny()
    {
        $expected   = '1:4,3:9,2:6,3:10,4:15,3:8,2:7,3:4,2:11,2:13';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByItemsAndTags(
                                    // items
                                    array( 3, 4, 6, 7, 8, 9, 10, 11, 13, 15),
                                    // tags
                                    array( 10, 73 ),
                                    false );         //!exact
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchByItemsAndTagsAnyAuthenticated()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '1:3,1:4,3:9,2:6,3:10,4:15,3:8,2:7,3:4,2:11,2:13';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByItemsAndTags(
                                    // items
                                    array( 3, 4, 6, 7, 8, 9, 10, 11, 13, 15),
                                    // tags
                                    array( 10, 73 ),
                                    false );         //!exact
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceCreateBookmark1()
    {
        $user = $this->_user1;
        $item = $this->_item12;
        $tags = $this->_tags1;
        $tagNames = array();
        foreach ($tags as $tag)
        {
            array_push($tagNames, $tag['tag']);
        }

        $user['totalTags']    =  24;
        $user['totalItems']   +=  1;
        $item['userCount']    +=  1;
        $item['ratingCount']  +=  1;
        $item['ratingSum']    +=  1;    // $expected['rating']

        $expected   = array(
            'name'          => 'TextPattern -- ideas??',
            'description'   => 'Information about text patterns.',
            'rating'        => 1,
            'isFavorite'    => 0,
            'isPrivate'     => 0,
            'taggedOn'      => '2010-04-05 17:25:19',
            'updatedOn'     => '2010-02-22 10:00:00',
            'userId'        => $user['userId'],
            'itemId'        => $item['itemId'],

            'user'          => $user,
            'item'          => $item,
            'tags'          => $tags,
        );

        // Create a template data array for the new Bookmark
        $template = $expected;
        $template['tags'] = implode(', ', $tagNames);
        unset($template['user']);
        unset($template['item']);
        unset($template['taggedOn']);
        unset($template['updatedOn']);

        /* Normal userId, itemId, tags settings:
         *  userId  == userId
         *  itemId  == itemId
         *  tags    == comma-separated string of tags.
         *
         * Create the new bookmark and save it.
         */
        $service  = Connexions_Service::factory('Model_Bookmark');
        $bookmark = $service->get( $template );
        $bookmark = $bookmark->save();

        //echo "Bookmark:\n", $bookmark->debugDump(), "\n";

        // Adjust the dynamic values in our expected results
        $expected['taggedOn']          = $bookmark->taggedOn;
        $expected['updatedOn']         = $bookmark->updatedOn;
        $expected['user']['lastVisit'] = $bookmark->user->lastVisit;

        $actual = $bookmark->toArray( Connexions_Model::DEPTH_DEEP,
                                      Connexions_Model::FIELDS_ALL );

        //echo "Expected:\n", print_r($expected, true), "\n\n";
        //echo "Actual:\n",   print_r($actual,   true), "\n\n";

        $this->assertEquals($expected, $actual );
    }

    public function testBookmarkServiceCreateBookmark2()
    {
        $user = $this->_user1;
        $item = $this->_item12;
        $tags = $this->_tags1;
        $tagNames = array();
        foreach ($tags as $tag)
        {
            array_push($tagNames, $tag['tag']);
        }

        $user['totalTags']     = 24;
        $user['totalItems']   +=  1;
        $item['userCount']    +=  1;
        $item['ratingCount']  +=  1;
        $item['ratingSum']    +=  1;    // $expected['rating']

        $expected   = array(
            'name'          => 'TextPattern -- ideas??',
            'description'   => 'Information about text patterns.',
            'rating'        => 1,
            'isFavorite'    => 0,
            'isPrivate'     => 0,
            'taggedOn'      => '2010-04-05 17:25:19',
            'updatedOn'     => '2010-02-22 10:00:00',
            'userId'        => $user['userId'],
            'itemId'        => $item['itemId'],

            'user'          => $user,
            'item'          => $item,
            'tags'          => $tags,
        );

        // Create a template data array for the new Bookmark
        $template = $expected;
        $template['userId'] = $user['name'];
        $template['itemId'] = $item['urlHash'];
        $template['tags']   = implode(', ', $tagNames);
        unset($template['user']);
        unset($template['item']);
        unset($template['taggedOn']);
        unset($template['updatedOn']);

        /*  userId  == user-name
         *  itemId  == urlHash
         *  tags    == comma-separated string of tags.
         *
         * Create the new bookmark and save it.
         */
        $service  = Connexions_Service::factory('Model_Bookmark');
        $bookmark = $service->get( $template );
        $bookmark = $bookmark->save();

        //echo "Bookmark:\n", $bookmark->debugDump(), "\n";

        // Adjust the dynamic values in our expected results
        $expected['taggedOn']          = $bookmark->taggedOn;
        $expected['updatedOn']         = $bookmark->updatedOn;
        $expected['user']['lastVisit'] = $bookmark->user->lastVisit;

        $actual = $bookmark->toArray( Connexions_Model::DEPTH_DEEP,
                                      Connexions_Model::FIELDS_ALL );

        //echo "Expected:\n", print_r($expected, true), "\n\n";
        //echo "Actual:\n",   print_r($actual,   true), "\n\n";

        $this->assertEquals($expected, $actual );
    }

    public function testBookmarkServiceCreateBookmark3()
    {
        $user = $this->_user1;
        $item = $this->_item12;
        $tags = $this->_tags1;
        $tagNames = array();
        foreach ($tags as $tag)
        {
            array_push($tagNames, $tag['tag']);
        }

        $user['totalTags']    =  24;
        $user['totalItems']   +=  1;
        $item['userCount']    +=  1;
        $item['ratingCount']  +=  1;
        $item['ratingSum']    +=  1;    // $expected['rating']

        $expected   = array(
            'name'          => 'TextPattern -- ideas??',
            'description'   => 'Information about text patterns.',
            'rating'        => 1,
            'isFavorite'    => 0,
            'isPrivate'     => 0,
            'taggedOn'      => '2010-04-05 17:25:19',
            'updatedOn'     => '2010-02-22 10:00:00',
            'userId'        => $user['userId'],
            'itemId'        => $item['itemId'],

            'user'          => $user,
            'item'          => $item,
            'tags'          => $tags,
        );

        // Create a template data array for the new Bookmark
        $template = $expected;
        $template['userId']  = $user['name'];
        $template['itemId']  = $item['url'];
        $template['tags']    = implode(', ', $tagNames);
        //unset($template['itemId']);
        unset($template['user']);
        unset($template['item']);
        unset($template['taggedOn']);
        unset($template['updatedOn']);

        /*  userId  == user-name
         *  itemId  == <unset>
         *  itemUrl == item-url
         *  tags    == comma-separated string of tags.
         *
         * Create the new bookmark and save it.
         */
        $service  = Connexions_Service::factory('Model_Bookmark');
        $bookmark = $service->get( $template );
        $bookmark = $bookmark->save();

        //echo "Bookmark:\n", $bookmark->debugDump(), "\n";

        // Adjust the dynamic values in our expected results
        $expected['taggedOn']          = $bookmark->taggedOn;
        $expected['updatedOn']         = $bookmark->updatedOn;
        $expected['user']['lastVisit'] = $bookmark->user->lastVisit;

        $actual = $bookmark->toArray( Connexions_Model::DEPTH_DEEP,
                                      Connexions_Model::FIELDS_ALL );

        //echo "Expected:\n", print_r($expected, true), "\n\n";
        //echo "Actual:\n",   print_r($actual,   true), "\n\n";

        $this->assertEquals($expected, $actual );
    }

    public function testBookmarkServiceCreateBookmark4()
    {
        // Sort 'tags' by tag name
        function sort_tags_by_name($a, $b)
        {
            return strcasecmp($a['tag'], $b['tag']);
        }

        $user = $this->_user1;
        $item = $this->_item12;
        $tags = array_merge($this->_tags1, $this->_tagsNew);
        usort($tags, 'sort_tags_by_name');

        $tagNames = array();
        foreach ($tags as $tag)
        {
            array_push($tagNames, $tag['tag']);
        }

        $user['totalTags']    =  26;
        $user['totalItems']   +=  1;
        $item['userCount']    +=  1;
        $item['ratingCount']  +=  1;
        $item['ratingSum']    +=  1;    // $expected['rating']

        $expected   = array(
            'name'          => 'TextPattern -- ideas??',
            'description'   => 'Information about text patterns.',
            'rating'        => 1,
            'isFavorite'    => 0,
            'isPrivate'     => 0,
            'taggedOn'      => '2010-04-05 17:25:19',
            'updatedOn'     => '2010-02-22 10:00:00',
            'userId'        => $user['userId'],
            'itemId'        => $item['itemId'],

            'user'          => $user,
            'item'          => $item,
            'tags'          => $tags,
        );

        // Create a template data array for the new Bookmark
        $template = $expected;
        $template['tags'] = implode(', ', $tagNames);
        unset($template['user']);
        unset($template['item']);
        unset($template['taggedOn']);
        unset($template['updatedOn']);

        /*
        printf ("%d incoming tags[ %s ]\n",
                count($tags), $template['tags']);
        // */

        /* Normal userId, itemId, tags settings:
         *  userId  == userId
         *  itemId  == itemId
         *  tags    == comma-separated string of tags.
         *
         * Create the new bookmark and save it.
         */
        $service  = Connexions_Service::factory('Model_Bookmark');
        $bookmark = $service->get( $template );
        $bookmark = $bookmark->save();

        //echo "Bookmark:\n", $bookmark->debugDump(), "\n";

        // Adjust the dynamic values in our expected results
        $expected['taggedOn']          = $bookmark->taggedOn;
        $expected['updatedOn']         = $bookmark->updatedOn;
        $expected['user']['lastVisit'] = $bookmark->user->lastVisit;

        $actual = $bookmark->toArray( Connexions_Model::DEPTH_DEEP,
                                      Connexions_Model::FIELDS_ALL );

        //echo "Expected:\n", print_r($expected, true), "\n\n";
        //echo "Actual:\n",   print_r($actual,   true), "\n\n";

        $this->assertEquals($expected, $actual );
    }
}
