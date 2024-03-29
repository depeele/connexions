<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/services/Bookmark.php';

/**
 *  @group Services
 */
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
            'lastVisitFor'  => '0000-00-00 00:00:00',

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
            'updatedOn'     => '2010-07-22 10:00:00',
    );

    protected   $_bookmark2 = array(
            'userId'        => 1,
            'itemId'        => 2,

            'name'          => 'OAT Framework',
            'description'   => '',
            'rating'        => 2,
            'isFavorite'    => 1,
            'isPrivate'     => 0,
            'taggedOn'      => "2007-03-30 14:39:52",
            'updatedOn'     => "2007-03-30 14:39:52",
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
        $expected['isFavorite'] = ($expected['isFavorite'] ? 1 : 0);
        $expected['isPrivate']  = ($expected['isPrivate']  ? 1 : 0);
        $id                     = array( $expected['userId'],
                                         $expected['itemId']);

        /* Bookmark 1 is private -- retrieval should FAIL unless we're
         * authenticated as the owner (user1)
         */
        $service  = Connexions_Service::factory('Model_Bookmark');
        $bookmark = $service->find( $id );

        $this->assertEquals(null, $bookmark);
    }

    public function testBookmarkRetrieveById2()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

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

        /* Bookmark 1 is private -- retrieval should FAIL unless we're
         * authenticated as the owner (user1)
         */
        $service  = Connexions_Service::factory('Model_Bookmark');
        $bookmark = $service->find( $id );

        $this->assertNotEquals(null, $bookmark);
        $this->assertEquals($expected,
                            $bookmark->toArray(self::$toArray_deep_all));

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkRetrieveById3()
    {
        $expected               = $this->_bookmark2;
        $expected['isFavorite'] = ($expected['isFavorite'] ? 1 : 0);
        $expected['isPrivate']  = ($expected['isPrivate']  ? 1 : 0);
        $id                     = array( $expected['userId'],
                                         $expected['itemId']);

        $service  = Connexions_Service::factory('Model_Bookmark');
        $bookmark = $service->find( $id );

        $this->assertNotEquals(null, $bookmark);
        $this->assertEquals($expected,
                            $bookmark->toArray(self::$toArray_shallow_all));
    }

    public function testBookmarkRetrieveById4()
    {
        $expected               = $this->_bookmark2;
        $expected['isFavorite'] = ($expected['isFavorite'] ? 1 : 0);
        $expected['isPrivate']  = ($expected['isPrivate']  ? 1 : 0);
        $id                     = array( 'userId' => $expected['userId'],
                                         'itemId' => $expected['itemId']);

        $service  = Connexions_Service::factory('Model_Bookmark');
        $bookmark = $service->find( $id );

        $this->assertNotEquals(null, $bookmark);
        $this->assertEquals($expected,
                            $bookmark->toArray(self::$toArray_shallow_all));
    }

    public function testBookmarkRetrieveById5()
    {
        $expected               = $this->_bookmark2;
        $expected['isFavorite'] = ($expected['isFavorite'] ? 1 : 0);
        $expected['isPrivate']  = ($expected['isPrivate']  ? 1 : 0);
        $id                     = $expected['userId']
                                .  ':'
                                . $expected['itemId'];

        $service  = Connexions_Service::factory('Model_Bookmark');
        $bookmark = $service->find( $id );

        $this->assertNotEquals(null, $bookmark);
        $this->assertEquals($expected,
                            $bookmark->toArray(self::$toArray_shallow_all));
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
        /*
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user     = $mapper->find( array('userId' => 1) );
        printf ("User1[ %s ]\n", $user->debugDump());
        // */

        $expected   = '1:2,1:4,1:5,3:4,4:15';
        $expectedAr = array( array(1,2),
                             array(1,4),
                             array(1,5),
                             array(3,4),
                             array(4,15),
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
        $expected   = '1:2,1:4,1:5,3:4,4:15';
        $expectedAr = array( array(1,2),
                             array(1,4),
                             array(1,5),
                             array(3,4),
                             array(4,15),
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
        $expected   = '1:2,1:4,1:5,3:4,4:15';
        $expectedAr = array( array(1,2),
                             array(1,4),
                             array(1,5),
                             array(3,4),
                             array(4,15),
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

        $expected   = '1:1,1:2,1:3,1:4,1:5,3:4,4:15';
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
        $expected   = '1:2,1:4,1:5,3:9,3:4,3:6,3:8,3:10';
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

        $expected   = '1:1,1:2,1:3,1:4,1:5,3:9,3:4,3:6,3:8,3:10';
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
        $expected   = '4:6,2:6,4:12,3:6';
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

        $expected   = '4:6,2:6,4:12,3:6';
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
                                                     array( 12, 13 ),  // tags
                                                     false,     // exactUsers
                                                     true);     // exactTags
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
                                                     array( 12, 13 ),  // tags
                                                     false,     // exactUsers
                                                     true);     // exactTags
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
        $expected   = '1:2,1:4,1:5,3:4,4:15';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByUsersAndTags( array( 1, 3, 4 ), // users
                                                     array( 12, 13 ),  // tags
                                                     false,     // !exactUsers
                                                     false);    // !exactTags
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

        $expected   = '1:2,1:3,1:4,1:5,3:4,4:15';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchByUsersAndTags( array( 1, 3, 4 ), // users
                                                     array( 12, 13 ),  // tags
                                                     false,     // !exactUsers
                                                     false);    // !exactTags
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
        $expected   = '3:4,4:15';
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

        $expected   = '3:4,4:15';
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
        $expected   = '1:4,3:9,2:6,2:7,3:4,2:13,4:15,3:8,2:11,3:10';
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

        $expected   = '1:3,1:4,3:9,2:6,2:7,3:4,2:13,4:15,3:8,2:11,3:10';
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

    public function testBookmarkServiceFetchRelated1()
    {
        $expected   = '1:2,1:4,1:5,3:4,4:15';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array('tags'      => array( 6, 12 ),
                            'exactTags' => false);

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $ids        = $bookmarks->getIds();
        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected,   $bookmarks);
    }

    public function testBookmarkServiceFetchRelated2()
    {
        $expected   = '1:2,1:4,1:5,3:4,4:15';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array('tags'      => array( 'web2.0', 'javascript' ),
                            'exactTags' => false);

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $ids        = $bookmarks->getIds();
        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected,   $bookmarks);
    }

    public function testBookmarkServiceFetchRelated3()
    {
        $expected   = '1:2,1:4,1:5,3:4,4:15';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array('tags'      => 'web2.0,javascript',
                            'exactTags' => false,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $ids        = $bookmarks->getIds();
        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected,   $bookmarks);
    }

    public function testBookmarkServiceFetchRelatedAuthenticated3()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '1:1,1:2,1:3,1:4,1:5,3:4,4:15';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array('tags'      => array( 6, 12 ),
                            'exactTags' => false,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchRelated4()
    {
        $expected   = '1:2,1:4,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array('tags'      => array( 6, 12 ),
                            'exactTags' => true,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchRelatedAuthenticated4()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '1:2,1:3,1:4,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array('tags'      => array( 6, 12 ),
                            'exactTags' => true,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchRelated5()
    {
        $expected   = '1:2,1:4,1:5,3:9,3:4,3:6,3:8,3:10';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array('users'      => array( 1, 3 ),
                            'exactUsers' => false,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchRelatedAuthenticated5()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '1:1,1:2,1:3,1:4,1:5,3:9,3:4,3:6,3:8,3:10';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array('users'      => array( 1, 3 ),
                            'exactUsers' => false,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchRelated6()
    {
        $expected   = '4:6,2:6,4:12,3:6';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array( 'items' => array( 6, 12 ) );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchRelatedAuthenticated6()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '4:6,2:6,4:12,3:6';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array( 'items' => array( 6, 12 ) );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchRelated7()
    {
        $expected   = '1:2,1:4,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array('users'      => array( 1, 3, 4 ),
                            'tags'       => array(12,13 ),
                            'exactTags'  => true,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchRelatedAuthenticated17()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '1:2,1:3,1:4,3:4';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array('users'     => array( 1, 3, 4 ),
                            'tags'      => array(12,13 ),
                            'exactTags' => true,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchRelated8()
    {
        $expected   = '1:2,1:4,1:5,3:4,4:15';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array( 'users'      => array( 1, 3, 4 ),
                             'tags'       => array(12,13 ),
                             'exactTags'  => false,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchRelatedAuthenticated8()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '1:2,1:3,1:4,1:5,3:4,4:15';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array( 'users'     => array( 1, 3, 4 ),
                             'tags'      => array(12,13 ),
                             'exactTags' => false,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchRelated9()
    {
        $expected   = '3:4,4:15';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array(
                        'items'     => array( 3, 4, 6, 7, 8, 9, 10, 11, 13, 15),
                        'tags'      => array(12,73 ),
                        'exactTags' => true,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchRelatedAuthenticated9()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '3:4,4:15';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array(
                        'items'     => array( 3, 4, 6, 7, 8, 9, 10, 11, 13, 15),
                        'tags'      => array(12,73 ),
                        'exactTags' => true,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchRelated10()
    {
        $expected   = '1:4,3:9,2:6,2:7,3:4,2:13,4:15,3:8,2:11,3:10';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array(
                        'items'     => array( 3, 4, 6, 7, 8, 9, 10, 11, 13, 15),
                        'tags'      => array(12,73 ),
                        'exactTags' => false,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchRelatedAuthenticated10()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected   = '1:3,1:4,3:9,2:6,2:7,3:4,2:13,4:15,3:8,2:11,3:10';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $config     = array(
                        'items'     => array( 3, 4, 6, 7, 8, 9, 10, 11, 13, 15),
                        'tags'      => array(12,73 ),
                        'exactTags' => false,
                      );

        $bookmarks  = $service->fetchRelated( $config );
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceFetchInbox1()
    {
        $expected   = '3:9,2:6,2:7,2:14,3:4,2:13,4:15,3:8,2:11,3:10';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchInbox( 'User1',
                                            null,   // No tags
                                            null);  // forever
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchInbox2()
    {
        $expected   = '3:9,2:7,2:14,3:4,2:13';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchInbox( 'User1',
                                            null,   // No tags
                                            '2006-06-26');
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    public function testBookmarkServiceFetchInbox3()
    {
        $expected   = '2:7,2:14';
        $service    = Connexions_Service::factory('Model_Bookmark');
        $bookmarks  = $service->fetchInbox( 'User1',
                                            null,   // No tags
                                            '2007-01-01');
        $this->assertNotEquals(null, $bookmarks);

        //printf ("Bookmarks: [ %s ]\n", print_r($bookmarks->toArray(), true));

        $bookmarks  = $bookmarks->__toString();

        //printf ("Bookmarks: [ %s ]\n", $bookmarks);

        $this->assertEquals($expected, $bookmarks);
    }

    /** @outputBuffering disabled */
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
            'updatedOn'     => '2010-07-22 10:00:00',
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
        //echo "Bookmark (get):\n", $bookmark->debugDump(), "\n";

        $bookmark = $bookmark->save();
        //echo "Bookmark (save):\n", $bookmark->debugDump(), "\n";

        // Adjust the dynamic values in our expected results
        $expected['taggedOn']          = $bookmark->taggedOn;
        $expected['updatedOn']         = $bookmark->updatedOn;
        $expected['user']['lastVisit'] = $bookmark->user->lastVisit;

        $actual = $bookmark->toArray(self::$toArray_deep_all);

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
            'updatedOn'     => '2010-07-22 10:00:00',
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

        $actual = $bookmark->toArray(self::$toArray_deep_all);

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
            'updatedOn'     => '2010-07-22 10:00:00',
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

        $actual = $bookmark->toArray(self::$toArray_deep_all);

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
            'updatedOn'     => '2010-07-22 10:00:00',
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

        $actual = $bookmark->toArray(self::$toArray_deep_all);

        //echo "Expected:\n", print_r($expected, true), "\n\n";
        //echo "Actual:\n",   print_r($actual,   true), "\n\n";

        $this->assertEquals($expected, $actual );
    }

    public function testBookmarkServiceAutocompleteTag1()
    {
        /* If Service_Bookmark::autocompleteTag() uses
         *  'tag=*', this list should be:
         *      'tiddlywiki,collection,decoration,documentation,identity,tiddlywikiplugin'
         *
         *  'tag=^', the list should be:
         *      'tiddlywiki,tiddlywikiplugin'
         */
        $expected = 'tiddlywiki,collection,decoration,documentation,identity,tiddlywikiplugin';
        $service  = Connexions_Service::factory('Model_Bookmark');
        $tags     = $service->autocompleteTag('ti');

        $this->assertEquals($expected, $tags->__toString() );
    }

    public function testBookmarkServiceAutocompleteTag2()
    {
        $expected = 'manual,mediawiki';
        $curTags  = 'tiddlywiki,for:user1';    // 26*,27,28,29,55,57,58,59,60,73*
        //$curItems = array(8,13);

        $service  = Connexions_Service::factory('Model_Bookmark');
        $tags     = $service->autocompleteTag('m',
                                              $curTags);

        $this->assertEquals($expected, $tags->__toString() );
    }

    public function testBookmarkServiceAutocompleteTag3()
    {
        $expected = 'java,java3d,javascript';
        $curTags  = 'for:user1';       // 26*,27,28,29,55,57,58,59,60,73*
        $curUsers = '2,3';
        //$curItems = array(8,13);

        $service  = Connexions_Service::factory('Model_Bookmark');
        $tags     = $service->autocompleteTag('java',
                                              $curTags,
                                              $curUsers);

        $this->assertEquals($expected, $tags->__toString() );
    }

    public function testBookmarkServiceTimeline1()
    {
        // Private bookmarks aren't included
        $expected = array(
            'activity'  => array(
                "2007033014"   => 2,
                "2007033013"   => 1,
            ),
        );
        $params = array(
            'users' => "1",
            'items' => null,
            'tags'  => null,
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);
    }

    public function testBookmarkServiceTimeline2()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            'activity'  => array(
                "2010040517"   => 1,
                "2007033014"   => 3,
                "2007033013"   => 1,
            ),
        );
        $params = array(
            'users' => "1",
            'items' => null,
            'tags'  => null,
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimeline3()
    {
        $expected = array(
            'activity'  => array(
                "2006063018"   => 1,
                "2006040923"   => 1,
                "0000000000"   => 1,
            ),
        );
        $params = array(
            'users' => null,
            'items' => '6',
            'tags'  => null,
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        //echo Connexions::varExport($timeline) ."\n";

        $this->assertEquals($expected, $timeline);
    }

    public function testBookmarkServiceTimeline4()
    {
        // Private bookmarks aren't included
        $expected = array(
            'activity'  => array(
                "2007033014"   => 1,
            ),
        );
        $params = array(
            'users' => "1,2",
            'items' => "1,3,4",
            'tags'  => null,
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);
    }

    public function testBookmarkServiceTimeline5()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            'activity'  => array(
                "2010040517"   => 1,
                "2007033014"   => 2,
            ),
        );
        $params = array(
            'users' => "1,2",
            'items' => "1,3,4",
            'tags'  => null,
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimeline6()
    {
        // Private bookmarks aren't included
        $expected = array(
            'activity'  => array(
            ),
        );
        $params = array(
            'users' => "1,2",
            'items' => "1,3,4",
            'tags'  => null,
            'from'  => '2007-03-30 14:35:00',
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);
    }

    public function testBookmarkServiceTimeline7()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            'activity'  => array(
                "2010040517"   => 1,
                "2007033014"   => 1,
            ),
        );
        $params = array(
            'users' => "1,2",
            'items' => "1,3,4",
            'tags'  => null,
            'from'  => '2007-03-30 14:35:00',
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimeline8()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            'activity'  => array(
                "2007033014"   => 1,
            ),
        );
        $params = array(
            'users' => "1,2",
            'items' => "1,3,4",
            'tags'  => null,
            'from'  => '2007-03-30 14:35:00',
            'until' => '2010-04-05 17:25:00',
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupHour1()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "17" => 1,
                "14" => 3,
                "13" => 1,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'H',     // hour
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupHour2()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            '30' => array( '14' => 3, '13' => 1 ),
            '05' => array( '17' => 1 ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'D:H',   // hour / day
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupHour3()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            '04' => array( '17' => 1 ),
            '03' => array( '14' => 3, '13' => 1 ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'M:H',   // hour / month
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupHour4()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            "2010" => array( "17" => 1 ),
            "2007" => array( "14" => 3, "13" => 1 ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'Y:H',   // hour / year
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupHour5()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "14" => 2,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'H',     // hour
            'from'      => '2007-03-30 14:35:00',
            'until'     => '2010-04-05 17:25:00',
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupDay1()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "05" => 1,
                "30" => 4,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'D',     // day
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupDay2()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            '04' => array( '05' => 1 ),
            '03' => array( '30' => 4 ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'M:D',   // day / month
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupDay3()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            "2010" => array( "05" => 1 ),
            "2007" => array( "30" => 4 ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'Y:D',   // day / year
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupDay4()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "30" => 2,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'D',     // day
            'from'      => '2007-03-30 14:35:00',
            'until'     => '2010-04-05 17:25:00',
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupDayOfWeek1()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "5" => 4,
                "1" => 1,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'd',     // day-of-week
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupDayOfWeek2()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            '04' => array( '1' => 1 ),
            '03' => array( '5' => 4 ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'M:d'    // day-of-week / month
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupDayOfWeek3()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            "2010" => array( "1" => 1 ),
            "2007" => array( "5" => 4 ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'Y:d'    // day-of-week / year
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupDayOfWeek4()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "5" => 2,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'd',     // day-of-week
            'from'      => '2007-03-30 14:35:00',
            'until'     => '2010-04-05 17:25:00',
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupWeekM1()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "14"    => 1,
                "13"    => 4,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'W',     // week
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupWeekM2()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            "04" => array( "14" => 1 ),
            "03" => array( "13" => 4 ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'M:W'    // week / month
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupWeekM3()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            "2010" => array( "14" => 1 ),
            "2007" => array( "13" => 4 ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'Y:W',   // week / year
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupWeekM4()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "13"    => 2,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'W',     // week
            'from'      => '2007-03-30 14:35:00',
            'until'     => '2010-04-05 17:25:00',
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupWeekS1()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "14"    => 1,
                "12"    => 4,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'w',     // week
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupWeekS2()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            "04" => array( "14" => 1 ),
            "03" => array( "12" => 4 ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'M:w',   // week / month
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupWeekS3()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            "2010" => array( "14" => 1 ),
            "2007" => array( "12" => 4 ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'Y:w',   // week / year
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupWeekS4()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "12"    => 2,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'w',     // week
            'from'      => '2007-03-30 14:35:00',
            'until'     => '2010-04-05 17:25:00',
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupMonth1()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "04" => 1,
                "03" => 4,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'M',     // month
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupMonth2()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            "2010" => array( "04" => 1 ),
            "2007" => array( "03" => 4 ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'Y:M'    // month / year
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupMonth3()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "03" => 2,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'M',     // month
            'from'      => '2007-03-30 14:35:00',
            'until'     => '2010-04-05 17:25:00',
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupYear1()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "2010" => 1,
                "2007" => 4,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'Y',     // year
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }

    public function testBookmarkServiceTimelineGroupYear2()
    {
        // Establish User1 as the authenticated, visiting user.
        $this->_setAuthenticatedUser(1);

        $expected = array(
            /*
            "2010-04-05 17:25:19"   => 1,
            "2007-03-30 14:39:52"   => 1,
            "2007-03-30 14:35:51"   => 1,
            "2007-03-30 14:33:27"   => 1,
            "2007-03-30 13:11:57"   => 1,
            // */
            'activity'  => array(
                "2007" => 2,
            ),
        );
        $params = array(
            'users'     => "1",
            'items'     => null,
            'tags'      => null,
            'grouping'  => 'Y',     // year
            'from'      => '2007-03-30 14:35:00',
            'until'     => '2010-04-05 17:25:00',
        );
        $service  = Connexions_Service::factory('Model_Bookmark');
        $timeline = $service->getTimeline( $params );

        $this->assertEquals($expected, $timeline);

        // De-Establish User1 as the authenticated, visiting user.
        $this->_unsetAuthenticatedUser();
    }
}
