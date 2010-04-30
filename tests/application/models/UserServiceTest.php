<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/services/User.php';

class UserServiceTest extends DbTestCase
{
    private     $_user0 = array(
            'userId'        => 0,
            'name'          => 'User0',
            'fullName'      => 'Visitor',
            'email'         => null,
            'apiKey'        => null,
            'pictureUrl'    => null,
            'profile'       => null,
            'lastVisit'     => null,

            'totalTags'     => 0,
            'totalItems'    => 0,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );
    private     $_user1 = array(
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
    private     $_user2 = array(
            'userId'        => 2,
            'name'          => 'User441',
            'fullName'      => 'Random User 441',
            'email'         => 'User441@home.com',
            'apiKey'        => 'xvkz0j5OwR',
            'pictureUrl'    => null,
            'profile'       => null,
            'lastVisit'     => '0000-00-00 00:00:00',

            'totalTags'     => 24,
            'totalItems'    => 5,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
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

    public function testUserServiceFactory()
    {
        $service1 = Connexions_Service::factory('Model_User');
        $this->assertTrue( $service1 instanceof Connexions_Service );
        $this->assertTrue( $service1 instanceof Service_User );

        $service2 = Connexions_Service::factory('Service_User');
        $this->assertTrue( $service2 instanceof Connexions_Service );
        $this->assertTrue( $service2 instanceof Service_User );
        $this->assertSame( $service1, $service2 );
    }

    public function testUserServiceCreate()
    {
        $expected = $this->_user0;
        $service  = Connexions_Service::factory('Model_User');

        $user     = $service->create( $data = array(
            'name'        => $expected['name'],
            'fullName'    => $expected['fullName'],
        ));

        $this->assertTrue( $user instanceof Model_User );

        // Make sure we can change properties
        $user->email      = $expected['email'];
        $user->pictureUrl = $expected['pictureUrl'];
        $user->profile    = $expected['profile'];

        $this->assertFalse(  $user->isBacked() );
        $this->assertTrue(   $user->isValid() );
        $this->assertFalse(  $user->isAuthenticated() );

        // apiKey and lastVisit are dynamically generated
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

        $this->assertEquals($user->getValidationMessages(), array() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceCreateExistingReturnsBackedInstance()
    {
        $expected = $this->_user1;
        $service  = Connexions_Service::factory('Model_User');
        $user     = $service->create( $data = array(
            'name'        => $expected['name'],
            'fullName'    => $expected['fullName'],
        ));

        $this->assertTrue(  $user instanceof Model_User );
        $this->assertTrue(  $user->isBacked() );
        $this->assertTrue(  $user->isValid() );
        $this->assertFalse( $user->isAuthenticated() );

        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    /*************************************************************************
     * Single Instance retrieval tests
     *
     */
    public function testUserServiceFindByUserId1()
    {
        $expected = $this->_user1;
        $service  = Connexions_Service::factory('Model_User');
        $user     = $service->find( array(
                                        'userId'=> $expected['userId'],
                    ));

        $this->assertTrue(  $user instanceof Model_User );
        $this->assertTrue(  $user->isBacked() );
        $this->assertTrue(  $user->isValid() );
        $this->assertFalse( $user->isAuthenticated() );

        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceFindByUserId2()
    {
        $expected = $this->_user1;
        $service  = Connexions_Service::factory('Model_User');
        $user     = $service->find( array(
                                        'name' => $expected['name'],
                    ));

        $this->assertTrue(  $user instanceof Model_User );
        $this->assertTrue(  $user->isBacked() );
        $this->assertTrue(  $user->isValid() );
        $this->assertFalse( $user->isAuthenticated() );

        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceFindByUserId3()
    {
        $expected = $this->_user1;
        $service  = Connexions_Service::factory('Model_User');
        $user     = $service->find( $expected['userId'] );

        $this->assertTrue(  $user instanceof Model_User );
        $this->assertTrue(  $user->isBacked() );
        $this->assertTrue(  $user->isValid() );
        $this->assertFalse( $user->isAuthenticated() );

        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceFindByUserId4()
    {
        $expected = $this->_user1;
        $service  = Connexions_Service::factory('Model_User');
        $user     = $service->find( $expected['name'] );

        $this->assertTrue(  $user instanceof Model_User );
        $this->assertTrue(  $user->isBacked() );
        $this->assertTrue(  $user->isValid() );
        $this->assertFalse( $user->isAuthenticated() );

        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    /*************************************************************************
     * Set retrieval tests
     *
     */

    public function testUserServiceFetchSet()
    {
        $service  = Connexions_Service::factory('Model_User');
        $users    = $service->fetch();

        // Fetch the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/userSetAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('user'), $users );
    }

    public function testUserServiceFetchPaginated()
    {
        $service  = Connexions_Service::factory('Model_User');
        $users    = $service->fetchPaginated();

        /*
        printf ("%d users of %d, %d pages with %d per page, current page %d\n",
                $users->getTotalItemCount(),
                $users->getCurrentItemCount(),
                $users->count(),
                $users->getItemCountPerPage(),
                $users->getCurrentPageNumber());
        // */

        $this->assertEquals(4,  $users->getTotalItemCount());
        $this->assertEquals(10, $users->getCurrentItemCount());
        $this->assertEquals(1,  count($users));

        /*
        foreach ($users as $idex => $item)
        {
            printf ("Row %2d: [ %s ]\n",
                    $idex,
                    Connexions::varExport( (is_object($item)
                                                ? $item->toArray()
                                                : $item)));
        }
        // */

        // Fetch the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/userSetAssertion.xml');

        $this->assertPaginatedSetEquals( $ds->getTable('user'), $users );
    }

    /*************************************************************************
     * Authentication tests
     *
     */
    public function testUserServiceAuthenticationInvalidUser()
    {
        $expected   = $this->_user0;
        $credential = 'abcdefg';
        $service    = Connexions_Service::factory('Model_User');
        $user       = $service->authenticate( $expected['name'], $credential );

        // apiKey and lastVisit are dynamically generated
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

        $this->assertFalse ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceAuthenticationInvalidCredential()
    {
        $expected   = $this->_user1;
        $credential = 'abc';
        $service    = Connexions_Service::factory('Model_User');
        $user       = $service->authenticate( $expected['name'], $credential );

        $this->assertFalse ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceAuthenticationSuccess()
    {
        $expected   = $this->_user1;
        $credential = 'abcdefg';
        $service    = Connexions_Service::factory('Model_User');
        $user       = $service->authenticate( $expected['name'], $credential );

        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // De-authenticate this user for the next test
        $user->logout();
        $this->assertFalse ( $user->isAuthenticated() );
    }

    public function testUserServiceAuthenticationPreHashedSuccess()
    {
        $expected   = $this->_user1;
                    // md5($expected['name'] .':abcdefg');
        $credential = '77c3d13750c0a0a59b0a2cf1bc189f61';
        $service    = Connexions_Service::factory('Model_User');
        $user       = $service->authenticate( $expected['name'], $credential );

        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // De-authenticate this user for the next test
        $user->logout();
        $this->assertFalse ( $user->isAuthenticated() );
    }

    public function testUserServiceAuthenticationPkiMismatch()
    {
        $expected   = $this->_user1;
        $credential = 'C=US, ST=Maryland, L=Baltimore, O=City Government, OU=Public Works, CN=User 1/emailAddress=User1@home.com';
        $service    = Connexions_Service::factory('Model_User');
        $user       = $service->authenticate( $expected['name'], $credential );

        $this->assertFalse ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // De-authenticate this user for the next test
        $user->logout();
        $this->assertFalse ( $user->isAuthenticated() );
    }

    public function testUserServiceAuthenticationPkiSuccess()
    {
        $expected   = $this->_user1;
        $credential = 'C=US, ST=Maryland, L=Baltimore, O=City Government, OU=Public Works, CN=User 1/emailAddress=User1@home.com';
        $service    = Connexions_Service::factory('Model_User');
        $user       = $service->authenticate( $expected['name'], $credential,
                                                     Model_UserAuth::AUTH_PKI);

        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // De-authenticate this user for the next test
        $user->logout();
        $this->assertFalse ( $user->isAuthenticated() );
    }

    public function testUserServiceAuthenticationOpenIdMismatch()
    {
        $expected   = $this->_user1;
        $credential = 'https://google.com/profile/User.1';
        $service    = Connexions_Service::factory('Model_User');
        $user       = $service->authenticate( $expected['name'], $credential );

        $this->assertFalse ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceAuthenticationOpenIdSuccess()
    {
        $expected   = $this->_user1;
        $credential = 'https://google.com/profile/User.1';
        $service    = Connexions_Service::factory('Model_User');
        $user       = $service->authenticate( $expected['name'],
                                              $credential,
                                              Model_UserAuth::AUTH_OPENID);

        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // De-authenticate this user for the next test
        $user->logout();
        $this->assertFalse ( $user->isAuthenticated() );
    }

    public function testUserServiceInvalidAddAuth1()
    {
        $service    = Connexions_Service::factory('Model_User');
        $user       = $service->find( $this->_user2['userId'] );

        $this->assertNotEquals(null, $user);

        $credential = '1234567';
        $expected   = array('userId'     => $user->userId,
                            'authType'   => 'invalid_auth_type',
                            'credential' => $credential);

        try
        {
            $auth = $user->addAuthenticator($expected['credential'],
                                            $expected['authType']);
            $this->fail("Invalid Auth Type was permitted");
        }
        catch (Exception $e)
        {
            $this->assertEquals('Invalid authType', $e->getMessage());
            //$this->assertEquals(null, $auth);
        }
    }

    public function testUserServiceAddAuth1()
    {
        $service    = Connexions_Service::factory('Model_User');
        $user       = $service->find( $this->_user2['userId'] );
        $this->assertNotEquals(null, $user);

        $credential = '1234567';
        $expected   = array('userId'     => $user->userId,
                            'authType'   => Model_UserAuth::AUTH_DEFAULT,
                            'credential' => $credential);

        $auth = $user->addAuthenticator($expected['credential']);
        $this->assertNotEquals(null, $auth);

        // A password is stored as md5( user->name .':'. credential)
        $seed = $user->name .':'. $credential;
        $expected['credential'] = md5($seed);

        $this->assertEquals($expected,
                            $auth->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // Make sure we can retrieve.
        $authSet = $user->getAuthenticator();  //$expected['authType']);
        $this->assertEquals(array($expected),
                            $authSet->toArray(Connexions_Model::DEPTH_SHALLOW,
                                              Connexions_Model::FIELDS_ALL ));

        // Make sure the retrieved item is exacly the same instance.
        $this->assertSame($auth, $authSet[0]);

        // Make sure we can retrieve by credential.
        $authSet = $user->getAuthenticator(null, $expected['credential']);
        $this->assertEquals(array($expected),
                            $authSet->toArray(Connexions_Model::DEPTH_SHALLOW,
                                              Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceRemoveAuth1()
    {
        $service    = Connexions_Service::factory('Model_User');
        $user       = $service->find( $this->_user1['name'] );
        $this->assertNotEquals(null, $user);

        $user->removeAuthenticator( null, Model_UserAuth::AUTH_DEFAULT );

        $authSet = $user->getAuthenticator( Model_UserAuth::AUTH_DEFAULT );
        $this->assertEquals(array(),
                            $authSet->toArray(Connexions_Model::DEPTH_SHALLOW,
                                              Connexions_Model::FIELDS_ALL ));

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('userAuth', 'SELECT * FROM userAuth');

        $this->assertDataSetsEqual(
            $this->createFlatXmlDataSet(
              dirname(__FILE__) .'/_files/userServiceAuthDeleteAssertion.xml'),
            $ds);
    }

    public function testUserServiceFetchByTags()
    {
        //            vv ordered by 'tagCount DESC'
        $expected   = 'User1,User83';
        $service    = Connexions_Service::factory('Model_User');
        $users      = $service->fetchByTags( array( 6, 12 ) );
        $this->assertNotEquals(null, $users);

        //printf ("Users: [ %s ]\n", print_r($users->toArray(), true));

        $users      = $users->__toString();

        //printf ("Users: [ %s ]\n", $users);

        $this->assertEquals($expected, $users);
    }

    public function testUserServicecsList2set()
    {
        $expected   = array(1, 4, 3);
        $names      = "user1, user478,  user83, user12345, user91828";
        $service    = Connexions_Service::factory('Model_User');
        $users      = $service->csList2set( $names );
        $this->assertNotEquals(null, $users);

        $ids        = $users->idArray();
        $this->assertEquals($expected, $ids);

        /*
        printf ("Users [ %s ]: [ %s ]\n",
                $names, print_r($users->toArray(), true) );

        printf ("User Ids: [ %s ]\n", implode(', ', $ids));
        // */
    }

    public function testUserServiceSetString()
    {
        $expected   = "User1,User478,User83";
        $service    = Connexions_Service::factory('Model_User');
        $users      = $service->csList2set( $expected );
        $this->assertNotEquals(null, $users);

        $names      = $users->__toString();
        $this->assertEquals($expected, $names);

        //printf ("User Names: [ %s ]\n", $names);
    }
}
