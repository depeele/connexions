<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/models/UserAuth.php';

class UserAuthDbTest extends DbTestCase
{
    private $_user1 = array(
        // Expected user model data for User 1
        'model'     => array(
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
        ),

        // Expected userAuth model data (by authType)
        'openid'    => array(
            'userAuthId'    => 1,
            'userId'        => 1,
            'authType'      => 'openid',
            'credential'    => 'https://google.com/profile/User.1',
            'name'          => '',
        ),
        'password'  => array(
            'userAuthId'    => 2,
            'userId'        => 1,
            'authType'      => 'password',
            'credential'    => '77c3d13750c0a0a59b0a2cf1bc189f61',
            'name'          => '',
        ),
        'pki'       => array(
            'userAuthId'    => 3,
            'userId'        => 1,
            'authType'      => 'pki',
            'credential'    => 'C=US, ST=Maryland, L=Baltimore, O=City Government, OU=Public Works, CN=User 1/emailAddress=User1@home.com',
            'name'          => '',
        ),
    );

    protected function getDataSet()
    {
        return $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/5users.xml');
                        //dirname(__FILE__) .'/_files/userAuthSeed.xml');
    }

    protected function tearDown()
    {
        /* Since these tests setup and teardown the database for each new test,
         * we need to clean-up any Identity Maps that are used in order to 
         * maintain test validity.
         */
        $uMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $uMapper->flushIdentityMap();

        $uaMapper = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $uaMapper->flushIdentityMap();


        parent::tearDown();
    }

    public function testUserAuthRetrieveByUnknownId()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->find( array('userId' => 5) );

        $this->assertEquals(null, $userAuth);
    }

    public function testUserAuthRetrieveById1()
    {
        $expected = $this->_user1['password'];
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->find( array('userId'   => $expected['userId'],
                                         'authType' => $expected['authType']) );
        $this->assertTrue  ($userAuth instanceof Model_UserAuth);
        $this->assertEquals($expected, $userAuth->toArray());
    }

    public function testUserAuthGetId1()
    {
        $expected = $this->_user1['password'];

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->find( array('userAuthId' =>
                                            $expected['userAuthId']) );

        $this->assertTrue  ($userAuth instanceof Model_UserAuth);
        $this->assertEquals($expected,
                            $userAuth->toArray(self::$toArray_shallow_all));
    }


    public function testUserAuthUser()
    {
        $authTarget = $this->_user1['password'];
        $expected   = $this->_user1['model'];

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->find( array('userId'   => $authTarget['userId'],
                                         'authType' => $authTarget['authType']) );
        $this->assertTrue  ($userAuth instanceof Model_UserAuth);

        $user = $userAuth->user;

        $this->assertEquals($expected,
                            $user->toArray(self::$toArray_shallow_all));
    }

    public function testUserAuthRetrieveById2()
    {
        $expected = $this->_user1['pki'];

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->find( array('userId'   => $expected['userId'],
                                         'authType' =>
                                                $expected['authType']) );
        $this->assertTrue  ($userAuth instanceof Model_UserAuth);
        $this->assertEquals($expected, $userAuth->toArray());
    }

    public function testUserAuthRetrieveByCredential1()
    {
        $expected = $this->_user1['password'];

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');

        $userAuth = $mapper->find( array('credential' =>
                                                $expected['credential']) );

        $this->assertNotEquals(null, $userAuth);
        $this->assertTrue  ($userAuth instanceof Model_UserAuth);
        $this->assertEquals($expected, $userAuth->toArray());
    }

    public function testUserAuthRetrieveByCredential2()
    {
        $expected = $this->_user1['openid'];

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');

        $userAuth = $mapper->find( array('credential' =>
                                                $expected['credential'] ));

        $this->assertNotEquals(null, $userAuth);
        $this->assertTrue  ($userAuth instanceof Model_UserAuth);
        $this->assertEquals($expected, $userAuth->toArray());
    }

    public function testUserAuthRetrieveByCredential3()
    {
        $expected = $this->_user1['pki'];

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');

        $userAuth = $mapper->find( array('credential' =>
                                                $expected['credential'] ));

        $this->assertNotEquals(null, $userAuth);
        $this->assertTrue  ($userAuth instanceof Model_UserAuth);
        $this->assertEquals($expected, $userAuth->toArray());
    }

    public function testUserAuthSet()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');

        // Fetch all entries for user 1
        $userAuths = $mapper->fetch( array('userId' => 1) );

        $ds = $this->createFlatXmlDataSet(
              dirname(__FILE__) .'/_files/userAuthFetchAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('userAuth'), $userAuths );
    }

    public function testUserAuthDefaultTypeInsertedIntoDatabase()
    {
        $expected = array(
            'userAuthId'    => 4,
            'userId'        => 2,
            'authType'      => 'password',
                               // md5( 'User441:' )
            'credential'    => '60766ed79ea8ac6e58c88683a62c2b9d',
            'name'          => '',
        );
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->getModel( array(
                            'userId'     => $expected['userId'],
                        ));
        $this->assertNotEquals(null, $userAuth);
        $this->assertTrue  ($userAuth instanceof Model_UserAuth);
        $this->assertFalse($userAuth->isBacked());
        $this->assertTrue ($userAuth->isValid());

        $userAuth = $userAuth->save();

        $this->assertNotEquals(null, $userAuth);
        $this->assertTrue  ($userAuth instanceof Model_UserAuth);
        $this->assertTrue ($userAuth->isBacked());
        $this->assertTrue ($userAuth->isValid());
        $this->assertEquals($expected, $userAuth->toArray());

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('userAuth', 'SELECT * FROM userAuth');

        $this->assertDataSetsEqual(
            $this->createFlatXmlDataSet(
                dirname(__FILE__) .'/_files/userAuthInsertAssertion.xml'),
            $ds);

        //$userAuth->delete();
    }

    public function testUserAuthOpenIdInsertedIntoDatabase()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->getModel( array(
                            'userId'     => 2,
                            'authType'   => 'openid',
                            'credential' => 'https://google.com/profile/me',
                        ));
        $this->assertTrue  ($userAuth instanceof Model_UserAuth);

        $userAuth = $userAuth->save();
        $this->assertTrue  ($userAuth instanceof Model_UserAuth);

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('userAuth', 'SELECT * FROM userAuth');

        $this->assertDataSetsEqual(
            $this->createFlatXmlDataSet(
                dirname(__FILE__) .'/_files/userAuthInsertOpenIdAssertion.xml'),
            $ds);

        //$userAuth->delete();
    }

    public function testUserAuthPkiInsertedIntoDatabase()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->getModel( array(
                            'userId'     => 2,
                            'authType'   => 'pki',
                            'credential' => 'CN=me',
                        ));

        $userAuth = $userAuth->save();

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('userAuth', 'SELECT * FROM userAuth');

        $this->assertDataSetsEqual(
            $this->createFlatXmlDataSet(
                dirname(__FILE__) .'/_files/userAuthInsertPkiAssertion.xml'),
            $ds);

        //$userAuth->delete();
    }

    public function testUserAuthenticationInvalidAuthType()
    {
        $expected = new Zend_Auth_Result(
                                Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
                                null);
        $eUser    = $this->_user1['model'];
        $uMapper  = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user     = $uMapper->getModel( array('userId' => $eUser['userId']) );

        try
        {
            $user->authType = 'invalid_auth_type';
            $this->fail("Invalid Auth Type was permitted");
        }
        catch (Exception $e)
        {
            $this->assertEquals("Invalid authType", $e->getMessage());
        }
    }

    public function testUserAuthenticationComparePasswordInvalidCredential()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->getModel( array(
                            'userId'     => $this->_user1['model']['userId'],
                            'authType'   => 'password',
                        ));
        $cred     = $this->_user1['password']['credential'] .'?bad';

        $this->assertFalse( $userAuth->compare( $cred ) );
    }

    public function testUserAuthenticationComparePasswordSuccessRaw()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->getModel( array(
                            'userId'     => $this->_user1['model']['userId'],
                            'authType'   => 'password',
                        ));
        $cred     = 'abcdefg';

        $this->assertTrue( $userAuth->compare( $cred ) );
    }

    public function testUserAuthenticationComparePasswordSuccess()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->getModel( array(
                            'userId'     => $this->_user1['model']['userId'],
                            'authType'   => 'password',
                        ));
        $cred     = $this->_user1['password']['credential'];

        $this->assertTrue( $userAuth->compare( $cred ) );
    }

    public function testUserAuthenticationComparePkiInvalidCredential()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->getModel( array(
                            'userId'     => $this->_user1['model']['userId'],
                            'authType'   => 'pki',
                        ));
        $cred     = $this->_user1['pki']['credential'] .'?bad';

        $this->assertFalse( $userAuth->compare( $cred ) );
    }

    public function testUserAuthenticationComparePkiSuccess()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->getModel( array(
                            'userId'     => $this->_user1['model']['userId'],
                            'authType'   => 'pki',
                        ));
        $cred     = $this->_user1['pki']['credential'];

        $this->assertTrue( $userAuth->compare( $cred ) );
    }

    public function testUserAuthenticationCompareOpenIdInvalidCredential()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->getModel( array(
                            'userId'     => $this->_user1['model']['userId'],
                            'authType'   => 'openid',
                        ));
        $cred     = $this->_user1['openid']['credential']. '?bad';

        $this->assertFalse( $userAuth->compare( $cred ) );
    }

    public function testUserAuthenticationCompareOpenIdSuccess()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_UserAuth');
        $userAuth = $mapper->getModel( array(
                            'userId'     => $this->_user1['model']['userId'],
                            'authType'   => 'openid',
                        ));
        $cred     = $this->_user1['openid']['credential'];

        $this->assertTrue( $userAuth->compare( $cred ) );
    }

    /*
    public function testUserAuthenticationInvalidUser()
    {
        $expected = new Zend_Auth_Result(
                                Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS,
                                null);
        $uMapper  = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user     = $uMapper->getModel( array(
                                'userId'     => 32,
                                'name'       => 'User 32',
                          ) );
        $user->credential = 'abc';

        $this->assertEquals( $expected, $user->authenticate() );
    }

    public function testUserAuthenticationInvalidCredential()
    {
        $expected = new Zend_Auth_Result(
                                Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
                                null);
        $eUser    = $this->_user1['model'];
        $uMapper  = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user     = $uMapper->getModel( $eUser['userId'] );
        $user->setCredential('abc', 'pki');

        //printf ("User[ %s ]\n", $user->debugDump());

        $this->assertEquals( $expected, $user->authenticate() );
    }

    public function testUserAuthenticationSuccess()
    {
        $eUser    = $this->_user1['model'];
        $expected = new Zend_Auth_Result(
                                Zend_Auth_Result::SUCCESS,
                                $eUser);
        $uMapper  = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user     = $uMapper->getModel( $eUser['userId'] );
        $user->setCredential('abcdefg', 'password');

        $this->assertEquals( $expected, $user->authenticate() );
        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertTrue  ( $user->isBacked() );
        $this->assertTrue  ( $user->isValid() );
    }

    public function testUserAuthenticationPreHashedSuccess()
    {
        $eUser    = $this->_user1['model'];
        $expected = new Zend_Auth_Result(
                                Zend_Auth_Result::SUCCESS,
                                $eUser);
        $uMapper  = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user     = $uMapper->getModel( $eUser['userId'] );
        $user->setCredential('77c3d13750c0a0a59b0a2cf1bc189f61');

        $this->assertEquals( $expected, $user->authenticate() );
        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertTrue  ( $user->isBacked() );
        $this->assertTrue  ( $user->isValid() );
    }
    */
}
