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

                        'totalTags'     => 24,
                        'totalItems'    => 5,
                        'userItemCount' => 0,
                        'itemCount'     => 0,
                        'tagCount'      => 0,
        ),

        // Expected userAuth model data (by authType)
        'password'  => array(
            'userId'        => 1,
            'authType'      => 'password',
            'credential'    => '77c3d13750c0a0a59b0a2cf1bc189f61',
        ),
        'openid'    => array(
            'userId'        => 1,
            'authType'      => 'openid',
            'credential'    => 'https://google.com/profile/User.1',
        ),
        'pki'       => array(
            'userId'        => 1,
            'authType'      => 'pki',
            'credential'    => 'C=US, ST=Maryland, L=Baltimore, O=City Government, OU=Public Works, CN=User 1/emailAddress=User1@home.com',
        ),
    );

    protected function getDataSet()
    {
        return $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/5users.xml');
                        //dirname(__FILE__) .'/_files/userAuthSeed.xml');
    }

    public function testUserAuthRetrieveByUnknownId()
    {
        $mapper   = new Model_Mapper_UserAuth( );
        $userAuth = $mapper->find( 5 );

        $this->assertEquals(null, $userAuth);
    }

    public function testUserAuthRetrieveById1()
    {
        $expected = $this->_user1['password'];

        $mapper   = new Model_Mapper_UserAuth( );
        $userAuth = $mapper->find( array($expected['userId'],
                                         $expected['authType']) );
        $this->assertEquals($expected, $userAuth->toArray());
    }

    public function testUserAuthGetId()
    {
        $expected = array($this->_user1['password']['userId'],
                          $this->_user1['password']['authType']);

        $mapper   = new Model_Mapper_UserAuth( );
        $userAuth = $mapper->find( $expected );

        $this->assertEquals($expected, $userAuth->getId());
    }


    public function testUserAuthUser()
    {
        $authTarget = $this->_user1['password'];
        $expected   = $this->_user1['model'];

        $mapper   = new Model_Mapper_UserAuth( );
        $userAuth = $mapper->find( array($authTarget['userId'],
                                         $authTarget['authType']) );

        $user = $userAuth->user;

        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserAuthRetrieveById2()
    {
        $expected = $this->_user1['pki'];

        $mapper   = new Model_Mapper_UserAuth( );
        $userAuth = $mapper->find( array($expected['userId'],
                                         $expected['authType']) );
        $this->assertEquals($expected, $userAuth->toArray());
    }

    public function testUserAuthRetrieveByCredential1()
    {
        $expected = $this->_user1['password'];

        $mapper   = new Model_Mapper_UserAuth( );

        $userAuth = $mapper->find( $expected['credential'] );

        $this->assertNotEquals(null, $userAuth);
        $this->assertEquals($expected, $userAuth->toArray());
    }

    public function testUserAuthRetrieveByCredential2()
    {
        $expected = $this->_user1['openid'];

        $mapper   = new Model_Mapper_UserAuth( );

        $userAuth = $mapper->find( $expected['credential'] );

        $this->assertNotEquals(null, $userAuth);
        $this->assertEquals($expected, $userAuth->toArray());
    }

    public function testUserAuthRetrieveByCredential3()
    {
        $expected = $this->_user1['pki'];

        $mapper   = new Model_Mapper_UserAuth( );

        $userAuth = $mapper->find( $expected['credential'] );

        $this->assertNotEquals(null, $userAuth);
        $this->assertEquals($expected, $userAuth->toArray());
    }

    public function testUserAuthSet()
    {
        $mapper   = new Model_Mapper_UserAuth( );

        // Fetch all entries for user 1
        $userAuths = $mapper->fetch( array('userId' => 1) );

        $ds = $this->createFlatXmlDataSet(
              dirname(__FILE__) .'/_files/userAuthFetchAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('userAuth'), $userAuths );
    }

    public function testUserAuthDefaultTypeInsertedIntoDatabase()
    {
        $expected = array(
            'userId'        => 2,
            'authType'      => 'password',
                               // md5( 'User441:' )
            'credential'    => '60766ed79ea8ac6e58c88683a62c2b9d',
        );

        $data = array('userId' => $expected['userId']);

        $userAuth = new Model_UserAuth( array(
                            'userId'     => $expected['userId'],
                        ));
        $userAuth = $userAuth->save();

        $this->assertNotEquals(null, $userAuth);
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
    }

    public function testUserAuthOpenIdInsertedIntoDatabase()
    {
        $userAuth = new Model_UserAuth( array(
                            'userId'     => 2,
                            'authType'   => 'openid',
                            'credential' => 'https://google.com/profile/me',
                        ));

        $userAuth = $userAuth->save();

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('userAuth', 'SELECT * FROM userAuth');

        $this->assertDataSetsEqual(
            $this->createFlatXmlDataSet(
                dirname(__FILE__) .'/_files/userAuthInsertOpenIdAssertion.xml'),
            $ds);
    }

    public function testUserAuthPkiInsertedIntoDatabase()
    {
        $userAuth = new Model_UserAuth( array(
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
    }


    public function testUserAuthenticationInvalidUser()
    {
        $expected   = new Zend_Auth_Result(
                                Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS,
                                null);

        $user       = new Model_User( array(
                                'isValid'  => true,
                                'isBacked' => false,
                                'data'     => array(
                                    'userId'     => 32,
                                    'name'       => 'User 32',
                                    'credential' => 'abc',
                                )
                          ) );

        $this->assertEquals( $expected, $user->authenticate() );
    }

    public function testUserAuthenticationInvalidAuthType()
    {
        $expected   = new Zend_Auth_Result(
                                Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
                                null);
        $eUser      = $this->_user1['model'];

        $user       = new Model_User( array(
                                'isValid'  => true,
                                'isBacked' => true,
                                'data'     => $eUser,
                          ) );

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

    public function testUserAuthenticationInvalidCredential()
    {
        $expected   = new Zend_Auth_Result(
                                Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
                                null);
        $eUser      = $this->_user1['model'];
        $user       = new Model_User( array(
                                'isValid'  => true,
                                'isBacked' => true,
                                'data'     => $eUser,
                          ) );
        $user->setCredential('abc', 'pki');

        //printf ("User[ %s ]\n", $user->debugDump());

        $this->assertEquals( $expected, $user->authenticate() );
    }

    public function testUserAuthenticationSuccess()
    {
        $eUser      = $this->_user1['model'];
        $expected   = new Zend_Auth_Result(
                                Zend_Auth_Result::SUCCESS,
                                $eUser);
        $user       = new Model_User( array(
                                'isValid'  => true,
                                'isBacked' => true,
                                'data'     => $eUser,
                          ) );
        $user->setCredential('abcdefg');

        $this->assertEquals( $expected, $user->authenticate() );
        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertTrue  ( $user->isBacked() );
        $this->assertTrue  ( $user->isValid() );
    }

    public function testUserAuthenticationPreHashedSuccess()
    {
        $eUser      = $this->_user1['model'];
        $expected   = new Zend_Auth_Result(
                                Zend_Auth_Result::SUCCESS,
                                $eUser);
        $user       = new Model_User( array(
                                'isValid'  => true,
                                'isBacked' => true,
                                'data'     => $eUser,
                          ) );
        $user->setCredential('77c3d13750c0a0a59b0a2cf1bc189f61');

        $this->assertEquals( $expected, $user->authenticate() );
        $this->assertTrue  ( $user->isAuthenticated() );
        $this->assertTrue  ( $user->isBacked() );
        $this->assertTrue  ( $user->isValid() );
    }
}
