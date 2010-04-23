<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/models/Service/User.php';

class UserServiceTest extends DbTestCase
{
    protected   $_service   = null;
    private     $_user0 = array(
            'userId'        => null,
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

    protected function getDataSet()
    {
        return $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/5users.xml');
    }

    public function setUp()
    {
        // PHPUnit_Extensions_Database_TestCase
        parent::setUp();

        $this->_service = new Model_Service_User();
    }

    public function testUserServiceConstructorInjectionOfProperties()
    {
        $expected = $this->_user0;

        $user     = $this->_service->create( $data = array(
            'name'        => $expected['name'],
            'fullName'    => $expected['fullName'],
        ));

        // apiKey is dynamically generated
        $expected['apiKey'] = $user->apiKey;

        $this->assertTrue( $user instanceof Model_User );

        // Make sure we can change properties
        $user->email      = $expected['email'];
        $user->pictureUrl = $expected['pictureUrl'];
        $user->profile    = $expected['profile'];

        $this->assertTrue( ! $user->isBacked() );
        $this->assertTrue( ! $user->isValid() );
        $this->assertTrue( ! $user->isAuthenticated() );

        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    /*************************************************************************
     * Single Instance retrieval tests
     *
     */
    public function testUserServiceRetrieveByUserId1()
    {
        $expected = $this->_user1;
        $user     = $this->_service->retrieve( array(
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

    public function testUserServiceRetrieveByUserId2()
    {
        $expected = $this->_user1;
        $user     = $this->_service->retrieve( array(
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

    public function testUserServiceRetrieveByUserId3()
    {
        $expected = $this->_user1;
        $user     = $this->_service->retrieve( $expected['userId'] );

        $this->assertTrue(  $user instanceof Model_User );
        $this->assertTrue(  $user->isBacked() );
        $this->assertTrue(  $user->isValid() );
        $this->assertFalse( $user->isAuthenticated() );

        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceRetrieveByUserId4()
    {
        $expected = $this->_user1;
        $user     = $this->_service->retrieve( $expected['name'] );

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

    public function testUserServiceRetrieveSet()
    {
        $users  = $this->_service->retrieveSet();

        // Retrieve the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/userSetAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('user'), $users );
    }

    public function testUserServiceRetrievePaginated()
    {
        $users  = $this->_service->retrievePaginated();

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

        // Retrieve the expected set
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
        $user       = $this->_service->authenticate( $expected['name'],
                                                     $credential );

        // apiKey is dynamically generated
        $expected['apiKey'] = $user->apiKey;

        $this->assertFalse ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceAuthenticationInvalidCredential()
    {
        $expected   = $this->_user1;
        $credential = 'abc';
        $user       = $this->_service->authenticate( $expected['name'],
                                                     $credential );

        $this->assertFalse ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserServiceAuthenticationSuccess()
    {
        $expected   = $this->_user1;
        $credential = 'abcdefg';
        $user       = $this->_service->authenticate( $expected['name'],
                                                     $credential );

        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // De-authenticate this user for the next test
        $user->logout();
        $this->assertFalse ( $user->isAuthenticated() );

        // Clear out identity maps so future tests have a clean slate
        //$user->invalidate();  // or just ->unsetIdentity();
    }

    public function testUserServiceAuthenticationPreHashedSuccess()
    {
        $expected   = $this->_user1;
                    // md5($expected['name'] .':abcdefg');
        $credential = '77c3d13750c0a0a59b0a2cf1bc189f61';
        $user       = $this->_service->authenticate( $expected['name'],
                                                     $credential );

        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // De-authenticate this user for the next test
        $user->logout();
        $this->assertFalse ( $user->isAuthenticated() );

        // Clear out identity maps so future tests have a clean slate
        //$user->invalidate();  // or just ->unsetIdentity();
    }

    public function testUserServiceAuthenticationPkiMismatch()
    {
        $expected   = $this->_user1;
        $credential = 'C=US, ST=Maryland, L=Baltimore, O=City Government, OU=Public Works, CN=User 1/emailAddress=User1@home.com';
        $user       = $this->_service->authenticate( $expected['name'],
                                                     $credential );

        $this->assertFalse ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // De-authenticate this user for the next test
        $user->logout();
        $this->assertFalse ( $user->isAuthenticated() );

        // Clear out identity maps so future tests have a clean slate
        //$user->invalidate();  // or just ->unsetIdentity();
    }

    public function testUserServiceAuthenticationPkiSuccess()
    {
        $expected   = $this->_user1;
        $credential = 'C=US, ST=Maryland, L=Baltimore, O=City Government, OU=Public Works, CN=User 1/emailAddress=User1@home.com';
        $user       = $this->_service->authenticate( $expected['name'],
                                                     $credential,
                                                     Model_UserAuth::AUTH_PKI);

        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // De-authenticate this user for the next test
        $user->logout();
        $this->assertFalse ( $user->isAuthenticated() );

        // Clear out identity maps so future tests have a clean slate
        //$user->invalidate();  // or just ->unsetIdentity();
    }

    public function testUserServiceAuthenticationOpenIdMismatch()
    {
        $expected   = $this->_user1;
        $credential = 'https://google.com/profile/User.1';
        $user       = $this->_service->authenticate( $expected['name'],
                                                     $credential );

        $this->assertFalse ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // Clear out identity maps so future tests have a clean slate
        //$user->invalidate();  // or just ->unsetIdentity();
    }

    public function testUserServiceAuthenticationOpenIdSuccess()
    {
        $expected   = $this->_user1;
        $credential = 'https://google.com/profile/User.1';
        $user       = $this->_service->authenticate(
                                        $expected['name'],
                                        $credential,
                                        Model_UserAuth::AUTH_OPENID);

        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // De-authenticate this user for the next test
        $user->logout();
        $this->assertFalse ( $user->isAuthenticated() );

        // Clear out identity maps so future tests have a clean slate
        //$user->invalidate();  // or just ->unsetIdentity();
    }
}
