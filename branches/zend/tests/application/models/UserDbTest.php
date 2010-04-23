<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/models/User.php';

class UserDbTest extends DbTestCase
{
    private $_user1 = array(
                        'userId'        => 1,
                        'name'          => 'User1',
                        'fullName'      => 'Random User 1',
                        'email'         => 'User1@home.com',
                        'apiKey'        => null,
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
                        //dirname(__FILE__) .'/_files/userSeed.xml');
    }

    public function testUserInsertedIntoDatabase()
    {
        $expected = array(
            'userId'        => 5,
            'name'          => 'test_user',
            'fullName'      => 'Test User',
            'email'         => null,
            'apiKey'        => null,
            'pictureUrl'    => null,
            'profile'       => null,
            'lastVisit'     => '0000-00-00 00:00:00',

            'totalTags'     => 0,
            'totalItems'    => 0,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
        );

        $user = new Model_User( array(
                        'name'      => $expected['name'],
                        'fullName'  => $expected['fullName']));


        /*
        echo "New User:\n";
        echo $user->debugDump();
        // */

        $user = $user->save();

        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('user', 'SELECT * FROM user');

        $this->assertDataSetsEqual(
            $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/userInsertAssertion.xml'),
            $ds);
    }

    public function testUserRetrieveByUnknownId()
    {
        $mapper = new Model_Mapper_User( );
        $user   = $mapper->find( 5 );

        $this->assertEquals(null, $user);
    }

    public function testUserRetrieveById1()
    {
        $expected = $this->_user1;

        $mapper = new Model_Mapper_User( );
        $user   = $mapper->find( $this->_user1['userId'] );

        /*
        printf ("User by id %d:\n", $this->_user1['userid']);
        echo $user->debugDump();
        // */

        $this->assertTrue  ( $user->isBacked() );
        $this->assertTrue  ( $user->isValid() );
        $this->assertFalse ( $user->isAuthenticated() );

        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserIdentityMap()
    {
        $mapper = new Model_Mapper_User( );
        $user   = $mapper->find( $this->_user1['userId'] );
        $user2  = $mapper->find( $this->_user1['userId'] );

        $this->assertSame  ( $user, $user2 );
    }

    public function testUserIdentityMap2()
    {
        $mapper = new Model_Mapper_User( );
        $user   = $mapper->find( $this->_user1['userId'] );
        $user2  = $mapper->find( $this->_user1['name'] );

        $this->assertSame  ( $user, $user2 );
    }

    public function testUserInsertUpdatedIdentityMap()
    {
        $expected = array(
            'userId'        => 5,
            'name'          => 'test_user',
            'fullName'      => 'Test User',
            'email'         => null,
            'apiKey'        => null,
            'pictureUrl'    => null,
            'profile'       => null,
            'lastVisit'     => '0000-00-00 00:00:00',

            'totalTags'     => 0,
            'totalItems'    => 0,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
        );

        $data = array('name'        => 'test_user',
                      'fullName'    => 'Test User');

        $user = new Model_User( $data );
        $user = $user->save();

        $user2 = $user->getMapper()->find( $user->userId );

        $this->assertSame  ( $user, $user2 );
    }

    public function testUserGetId()
    {
        $expected = $this->_user1['userId'];

        $mapper = new Model_Mapper_User( );
        $user   = $mapper->find( $expected );

        $this->assertEquals($expected, $user->getId());
    }

    public function testUserRetrieveById2()
    {
        $expected = $this->_user1;

        $mapper = new Model_Mapper_User( );
        $user   = $mapper->find( array('userId' => $expected['userId']) );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserRetrieveByName1()
    {
        $expected = $this->_user1;

        $mapper = new Model_Mapper_User( );
        $user   = $mapper->find( $expected['name'] );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserRetrieveByName2()
    {
        $expected = $this->_user1;

        $mapper = new Model_Mapper_User( );
        $user   = $mapper->find( array('name' => $expected['name']) );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserDeletedFromDatabase()
    {
        $mapper = new Model_Mapper_User( );
        $user   = $mapper->find( 1 );

        $user->delete();

        /*
        echo "Deleted User:\n";
        echo $user->debugDump();
        // */

        // Make sure the user instance has been invalidated
        $this->assertTrue( ! $user->isBacked() );
        $this->assertTrue( ! $user->isValid() );
        $this->assertTrue( ! $user->isAuthenticated() );

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('user', 'SELECT * FROM user');

        $this->assertDataSetsEqual(
            $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/userDeleteAssertion.xml'),
            $ds);
    }

    public function testUserFullyDeletedFromDatabase()
    {
        $expected   = array(
            'userId'        => null,
            'name'          => null,
            'fullName'      => null,
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

        $mapper = new Model_Mapper_User( );
        $user   = $mapper->find( 1 );

        $user->delete();

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('user',        'SELECT * FROM user'
                                     .  ' ORDER BY userId ASC');
        $ds->addTable('item',        'SELECT * FROM item'
                                     .  ' ORDER BY itemId ASC');
        $ds->addTable('tag',         'SELECT * FROM tag'
                                     .  ' ORDER BY tagId ASC');

        $ds->addTable('userAuth',    'SELECT * FROM userAuth'
                                     .  ' ORDER BY userId,authType ASC');
        $ds->addTable('userItem',    'SELECT * FROM userItem'
                                     .  ' ORDER BY userId,itemId ASC');
        $ds->addTable('userTagItem', 'SELECT userId,itemId,tagId FROM userTagItem'
                                     .  ' ORDER BY userId,itemId,tagId ASC');

        $this->assertDataSetsEqual(
            $this->createFlatXmlDataSet(
                      dirname(__FILE__) .'/_files/userDeleteFullAssertion.xml'),
            $ds);
    }

    public function testUserSet()
    {
        $mapper = new Model_Mapper_User( );
        $users  = $mapper->fetch();

        // Retrieve the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/userSetAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('user'), $users );
    }

    public function testUserSetCount()
    {
        $expectedCount = 4; // Remember, 1 was deleted in a test above...
        $expectedTotal = 4; // Remember, 1 was deleted in a test above...

        $mapper = new Model_Mapper_User( );
        $users  = $mapper->fetch();

        $this->assertEquals($expectedCount, $users->count());
        $this->assertEquals($expectedTotal, $users->getTotalCount());
    }

    public function testUserSetLimitOrder()
    {
        $mapper = new Model_Mapper_User( );
        $users  = $mapper->fetch(null,
                                 array('name ASC'), // order
                                 2,                 // count
                                 1);                // offset

        // Retrieve the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/userSetLimitOrderAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('user'), $users );
    }

    public function testUserSetLimitCount()
    {
        $expectedCount = 2;
        $expectedTotal = 4; // Remember, 1 was deleted in a test above...

        $mapper = new Model_Mapper_User( );
        $users  = $mapper->fetch(null,
                                 array('name ASC'), // order
                                 $expectedCount,    // count
                                 1);                // offset

        $this->assertEquals($expectedCount, $users->count());
        $this->assertEquals($expectedTotal, $users->getTotalCount());
    }
}
