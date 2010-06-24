<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/services/User.php';

class UserServiceTest extends DbTestCase
{
    private     $_user0 = array(
            'userId'        => 0,
            'name'          => 'anonymous',
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

    public function testUserServiceGet()
    {
        $expected = $this->_user0;
        $service  = Connexions_Service::factory('Model_User');

        $user     = $service->get( $data = array(
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
        $user     = $service->get( $data = array(
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
        $id       = array( 'userId' => $expected['userId']);

        $user     = $service->find( $id );

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
        $id       = array( 'name'   => $expected['name']);

        $user     = $service->find( $id );

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
        $id       = $expected['userId'];

        $user     = $service->find( $id );

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
        $id       = $expected['name'];

        $user     = $service->find( $id );

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
        $this->assertEquals(4,  $users->getCurrentItemCount());
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

        // Password authentication
        $authType          = Model_UserAuth::AUTH_PASSWORD;
        $_POST['username'] = $expected['name'];
        $_POST['password'] = 'abcdefg';

        $service           = Connexions_Service::factory('Model_User');
        $user              = $service->authenticate( $authType );

        // Dynamic values
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

        $this->assertFalse ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceAuthenticationInvalidCredential()
    {
        $expected   = $this->_user0;

        // Password authentication
        $authType          = Model_UserAuth::AUTH_PASSWORD;
        $_POST['username'] = $this->_user1['name'];
        $_POST['password'] = 'abc';

        $service           = Connexions_Service::factory('Model_User');
        $user              = $service->authenticate( $authType );

        // Dynamic values
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

        $this->assertFalse ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceAuthenticationSuccess()
    {
        $expected   = $this->_user1;

        // Password authentication
        $authType          = Model_UserAuth::AUTH_PASSWORD;

        // Establish the request parameters
        $request = new Zend_Controller_Request_Simple();
        $request->setParam('username', $expected['name']);
        $request->setParam('password', 'abcdefg');
        Connexions::setRequest($request);

        $service           = Connexions_Service::factory('Model_User');
        $user              = $service->authenticate( $authType );

        // Dynamic values
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // De-authenticate this user for the next test
        $user->logout();
        $this->assertFalse ( $user->isAuthenticated() );

        Connexions::setRequest(null);
    }

    public function testUserServiceAuthenticationPreHashedSuccess()
    {
        $expected   = $this->_user1;

        // Password authentication
        $authType          = Model_UserAuth::AUTH_PASSWORD;

        // Establish the request parameters
        $request = new Zend_Controller_Request_Simple();
        $request->setParam('username', $expected['name']);
                                       // md5($expected['name'] .':abcdefg');
        $request->setParam('password', '77c3d13750c0a0a59b0a2cf1bc189f61');
        Connexions::setRequest($request);

        $service           = Connexions_Service::factory('Model_User');
        $user              = $service->authenticate( $authType );

        // Dynamic values
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // De-authenticate this user for the next test
        $user->logout();
        $this->assertFalse ( $user->isAuthenticated() );
        Connexions::setRequest(null);
    }

    public function testUserServiceAuthenticationPkiMismatch()
    {
        $expected   = $this->_user0;

        // PKI authentication
        $authType                     = Model_UserAuth::AUTH_PKI;
        $_SERVER['SSL_CLIENT_VERIFY'] = 'SUCCESS';
        $_SERVER['SSL_CLIENT_I_DN']   = 'not.empty';
        $_SERVER['SSL_CLIENT_S_DN']   = 'C=US, ST=Maryland, L=Baltimore, O=City Government, OU=Public Works, CN=User 52/emailAddress=User52@home.com';

        $service           = Connexions_Service::factory('Model_User');
        $user              = $service->authenticate( $authType );

        // Dynamic values
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

        $this->assertTrue  (! $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceAuthenticationPkiSuccess()
    {
        $expected   = $this->_user1;

        // PKI authentication
        $authType                     = Model_UserAuth::AUTH_PKI;
        $_SERVER['SSL_CLIENT_VERIFY'] = 'SUCCESS';
        $_SERVER['SSL_CLIENT_I_DN']   = 'not.empty';
        $_SERVER['SSL_CLIENT_S_DN']   = 'C=US, ST=Maryland, L=Baltimore, O=City Government, OU=Public Works, CN=User 1/emailAddress=User1@home.com';

        $service           = Connexions_Service::factory('Model_User');
        $user              = $service->authenticate( $authType );

        // Dynamic values
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

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
        $expected   = $this->_user0;

        // OpenId authentication
        $authType   = Model_UserAuth::AUTH_OPENID;
        $credential = 'https://google.com/profile/User.52';

        $service    = Connexions_Service::factory('Model_User');
        $user       = $service->authenticate( $authType, $credential );

        // Dynamic values
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

        $this->assertFalse ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    /* Can't really test an OpenId success since it requires a multi-way
     * conversation with the OpenId server.
     *
    public function testUserServiceAuthenticationOpenIdSuccess()
    {
        $expected   = $this->_user0;

        // OpenId authentication
        $authType   = Model_UserAuth::AUTH_OPENID;
        $credential = 'https://google.com/profile/User.1';

        $service    = Connexions_Service::factory('Model_User');
        $user       = $service->authenticate( $authType, $credential );

        // Dynamic values
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // De-authenticate this user for the next test
        $user->logout();
        $this->assertFalse ( $user->isAuthenticated() );
    }
    */ 

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

    public function testUserServiceFetchByTagsAny()
    {
        //            vv ordered by 'tagCount DESC'
        $expected   = 'User1,User83,User478';
        $service    = Connexions_Service::factory('Model_User');
        $users      = $service->fetchByTags( array( 6, 12 ), false );
        $this->assertNotEquals(null, $users);

        //printf ("Users: [ %s ]\n", print_r($users->toArray(), true));

        $users      = $users->__toString();

        //printf ("Users: [ %s ]\n", $users);

        $this->assertEquals($expected, $users);
    }

    public function testUserServiceFetchByTagsExact()
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
        $expected   = array(1, 3, 4);
        $names      = "user1, user478,  user83, user12345, user91828";
        $service    = Connexions_Service::factory('Model_User');
        $users      = $service->csList2set( $names );
        $this->assertNotEquals(null, $users);

        $ids        = $users->getIds();
        $this->assertEquals($expected, $ids);

        /*
        printf ("Users [ %s ]: [ %s ]\n",
                $names, print_r($users->toArray(), true) );

        printf ("User Ids: [ %s ]\n", implode(', ', $ids));
        // */
    }

    public function testUserServiceSetString()
    {
        $expected   = "User1,User83,User478";
        $service    = Connexions_Service::factory('Model_User');
        $users      = $service->csList2set( $expected );
        $this->assertNotEquals(null, $users);

        $names      = $users->__toString();
        $this->assertEquals($expected, $names);

        //printf ("User Names: [ %s ]\n", $names);
    }

    public function testUserServiceTagRenameUnauthenticatedFailure()
    {
        $renames  = array('identity' => 'personal.identity', // new
                          'ajax'     => 'ajaj',              // new
                          'oat'      => 'widgets',           // existing
                          'cooling'  => 'heating',           // no userTagItems
                          'invalid'  => 'what',              // no userTagItems
                    );
        // Retrieve the target user.
        $service  = Connexions_Service::factory('Model_User');
        $user     = $service->find( 1 );
        $this->assertNotEquals(null, $user);

        try
        {
            $res = $service->renameTags($user, $renames);

            $this->fail("Should throw an authentication exception");
        }
        catch (Exception $e)
        {
            // SUCCESS
        }

        // Make sure nothing was changed in the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('user',        'SELECT * FROM user '
                                     .  'ORDER BY userId ASC');
        $ds->addTable('item',        'SELECT * FROM item '
                                     .  'ORDER BY itemId ASC');
        $ds->addTable('tag',         'SELECT * FROM tag '
                                     .  'ORDER BY tagId ASC');

        $ds->addTable('userAuth',    'SELECT * FROM userAuth '
                                     .  'ORDER BY userId,authType ASC');
        $ds->addTable('userItem',    'SELECT * FROM userItem '
                                     .  'ORDER BY userId,itemId ASC');
        $ds->addTable('userTagItem', 'SELECT userId,itemId,tagId '
                                     .  'FROM userTagItem '
                                     .  'ORDER BY userId,itemId,tagId ASC');

        $es = $this->createFlatXmlDataSet(
               dirname(__FILE__) .'/_files/5users.xml');

        $this->assertDataSetsEqual( $es, $ds );
    }

    public function testUserServiceTagRenameSuccess()
    {
        $expected = array('identity' => true,       // 5
                          'ajax'     => true,       // 10
                          'oat'      => true,       // 15
                          'cooling'  => 'unused',   // 31
                          'invalid'  => 'unused',   // 31
                    );
        $renames  = array('identity' => 'personal.identity', // new
                          'ajax'     => 'ajaj',              // new
                          'oat'      => 'widgets',           // existing
                          'cooling'  => 'heating',           // no userTagItems
                          'invalid'  => 'what',              // no userTagItems
                    );

        // Retrieve the target user.
        $service  = Connexions_Service::factory('Model_User');
        $user     = $service->find( 1 );
        $this->assertNotEquals(null, $user);

        // Mark the user as 'authenticated'
        $this->_setAuthenticatedUser($user);
        $this->assertTrue ($user->isAuthenticated());

        // Rename tags
        $res      = $service->renameTags($user, $renames);
        $this->assertEquals($expected, $res);

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('user',        'SELECT * FROM user '
                                     .  'ORDER BY userId ASC');
        $ds->addTable('item',        'SELECT * FROM item '
                                     .  'ORDER BY itemId ASC');
        $ds->addTable('tag',         'SELECT * FROM tag '
                                     .  'ORDER BY tagId ASC');

        $ds->addTable('userAuth',    'SELECT * FROM userAuth '
                                     .  'ORDER BY userId,authType ASC');
        $ds->addTable('userItem',    'SELECT * FROM userItem '
                                     .  'ORDER BY userId,itemId ASC');
        $ds->addTable('userTagItem', 'SELECT userId,itemId,tagId '
                                     .  'FROM userTagItem '
                                     .  'ORDER BY userId,itemId,tagId ASC');

        /*********
         * Modify 'lastVisit' in the expected set for the user row since it's
         * dynamic...
         */
        $es = $this->createFlatXmlDataSet(
               dirname(__FILE__) .'/_files/userTagRenameAssertion.xml');
        $et = $es->getTable('user');
        $et->setValue(0, 'lastVisit', $user->lastVisit);

        $this->assertDataSetsEqual( $es, $ds );

        // De-authenticate $user
        $this->_unsetAuthenticatedUser($user);
    }

    public function testUserServiceTagDeleteUnauthenticatedFailure()
    {
        //$rename = array(5, 10, 15);
        // 6, 10, 12, 13, 15, 16, 17   -- will orphan Bookmark 1,4
        $expected = array('ajax'        => true,    // 10
                          'demo'        => true,    // 17
                          'framework'   => true,    // 13
                          'javascript'  => true,    // 12
                          'library'     => true,    // 14
                          'oat'         => true,    // 15
                          'web2.0'      =>          // 6
                            'Deleting this tag will orphan 1 bookmark',
                          'widgets'     => true,    // 16
                    );
        $tags     = array_keys($expected);

        // Retrieve the target user
        $service  = Connexions_Service::factory('Model_User');
        $user     = $service->find( array('userId'=> $this->_user1['userId']));
        $this->assertNotEquals(null, $user);

        try
        {
            $res  = $service->deleteTags($user, $tags);

            $this->fail("Should throw an authentication exception");
        }
        catch (Exception $e)
        {
            // SUCCESS
        }

        // Make sure nothing was changed in the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('user',        'SELECT * FROM user '
                                     .  'ORDER BY userId ASC');
        $ds->addTable('item',        'SELECT * FROM item '
                                     .  'ORDER BY itemId ASC');
        $ds->addTable('tag',         'SELECT * FROM tag '
                                     .  'ORDER BY tagId ASC');

        $ds->addTable('userAuth',    'SELECT * FROM userAuth '
                                     .  'ORDER BY userId,authType ASC');
        $ds->addTable('userItem',    'SELECT * FROM userItem '
                                     .  'ORDER BY userId,itemId ASC');
        $ds->addTable('userTagItem', 'SELECT userId,itemId,tagId '
                                     .  'FROM userTagItem '
                                     .  'ORDER BY userId,itemId,tagId ASC');

        $es = $this->createFlatXmlDataSet(
               dirname(__FILE__) .'/_files/5users.xml');

        $this->assertDataSetsEqual( $es, $ds );
    }

    public function testUserTagDeleteSuccess()
    {
        //$rename = array(5, 10, 15);
        // 6, 10, 12, 13, 15, 16, 17   -- will orphan Bookmark 1,4
        $expected = array('ajax'        => true,    // 10
                          'demo'        => true,    // 17
                          'framework'   => true,    // 13
                          'javascript'  => true,    // 12
                          'library'     => true,    // 14
                          'oat'         => true,    // 15
                          'web2.0'      =>          // 6
                            'Deleting this tag will orphan 1 bookmark',
                          'widgets'     => true,    // 16
                    );
        $tags     = array_keys($expected);

        // Retrieve the target user
        $service  = Connexions_Service::factory('Model_User');
        $user     = $service->find( array('userId'=> $this->_user1['userId']));
        $this->assertNotEquals(null, $user);

        // Mark the user as 'authenticated'
        $this->_setAuthenticatedUser($user);
        $this->assertTrue ($user->isAuthenticated());

        // Delete the tags.
        $res      = $service->deleteTags($user, $tags);
        $this->assertEquals($expected, $res);

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('user',        'SELECT * FROM user '
                                     .  'ORDER BY userId ASC');
        $ds->addTable('item',        'SELECT * FROM item '
                                     .  'ORDER BY itemId ASC');
        $ds->addTable('tag',         'SELECT * FROM tag '
                                     .  'ORDER BY tagId ASC');

        $ds->addTable('userAuth',    'SELECT * FROM userAuth '
                                     .  'ORDER BY userId,authType ASC');
        $ds->addTable('userItem',    'SELECT * FROM userItem '
                                     .  'ORDER BY userId,itemId ASC');
        $ds->addTable('userTagItem', 'SELECT userId,itemId,tagId '
                                     .  'FROM userTagItem '
                                     .  'ORDER BY userId,itemId,tagId ASC');

        // *******************************************************************
        // Modify 'lastVisit' in the expected set for the user row since it's
        // dynamic...
        $es = $this->createFlatXmlDataSet(
               dirname(__FILE__) .'/_files/userTagDeleteAssertion.xml');
        $et = $es->getTable('user');
        $et->setValue(0, 'lastVisit', $user->lastVisit);

        $this->assertDataSetsEqual( $es, $ds );

        // De-authenticate $user
        $this->_unsetAuthenticatedUser($user);
    }
}
