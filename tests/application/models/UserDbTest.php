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
    private $_user5 = array(
                        'userId'        => 5,
                        'name'          => 'test_user',
                        'fullName'      => 'Test User',
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

    protected function getDataSet()
    {
        return $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/5users.xml');
                        //dirname(__FILE__) .'/_files/userSeed.xml');
    }

    protected function tearDown()
    {
        /* Since these tests setup and teardown the database for each new test,
         * we need to clean-up any Identity Maps that are used in order to 
         * maintain test validity.
         */
        $uMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $uMapper->flushIdentityMap();

        parent::tearDown();
    }


    public function testUserInsertedIntoDatabase()
    {
        $expected = $this->_user5;
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user     = $mapper->getModel( array(
                                'name'      => $expected['name'],
                                'fullName'  => $expected['fullName']));

        $this->assertFalse ( $user->isBacked() );
        $this->assertTrue  ( $user->isValid() );

        //printf ("User [ %s ]\n", $user->debugDump());

        $user = $user->save();

        $this->assertNotEquals(null, $user);
        $this->assertTrue  ( $user->isBacked() );
        $this->assertTrue  ( $user->isValid() );

        // apiKey and lastVisit are dynamically generated
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

        $this->assertEquals($user->getValidationMessages(), array() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('user', 'SELECT * FROM user');

        /*********
         * Modify 'apiKey' and 'lastVisit' in our expected set for the target
         * row since it's dynamic...
         */
        $es = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/userInsertAssertion.xml');
        $et = $es->getTable('user');
        $et->setValue(4, 'apiKey',    $expected['apiKey']);
        $et->setValue(4, 'lastVisit', $expected['lastVisit']);

        $this->assertDataSetsEqual( $es, $ds );
    }

    public function testUserRetrieveByUnknownId()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( 5 );

        $this->assertEquals(null, $user);
    }

    public function testUserRetrieveById1()
    {
        $expected = $this->_user1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( $this->_user1['userId'] );

        /*
        printf ("User by id %d:\n", $this->_user1['userid']);
        echo $user->debugDump();
        // */

        $this->assertNotEquals(null, $user);
        $this->assertTrue  ( $user->isBacked() );
        $this->assertTrue  ( $user->isValid() );
        $this->assertFalse ( $user->isAuthenticated() );

        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserIdentityMap()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( $this->_user1['userId'] );
        $user2  = $mapper->find( $this->_user1['userId'] );

        $this->assertSame  ( $user, $user2 );
    }

    public function testUserIdentityMap2()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( $this->_user1['userId'] );
        $user2  = $mapper->find( $this->_user1['name'] );

        $this->assertSame  ( $user, $user2 );
    }

    public function testUserUpdate()
    {
        $expected = $this->_user5;
        $mapper   = new Model_Mapper_User( );
        $user     = $mapper->getModel( array(
                                'name'      => $expected['name'],
                                'fullName'  => $expected['fullName']));

        $this->assertNotEquals(null, $user);

        //printf ("User [ %s ]\n", $user->debugDump());

        $this->assertFalse ( $user->isBacked() );
        $this->assertTrue  ( $user->isValid() );

        $user = $user->save();
        $this->assertNotEquals(null, $user);

        $this->assertTrue  ( $user->isBacked() );
        $this->assertTrue  ( $user->isValid() );

        // apiKey and lastVisit are dynamically generated
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

        $this->assertEquals($user->getValidationMessages(), array() );
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // Update email
        $expected['email'] = 'user5@gmail.com';
        $user->email = $expected['email'];
        $user2 = $user->save();
        $this->assertNotEquals(null, $user2);

        // The lastVisit time MAY have changed
        $expected['lastVisit'] = $user2->lastVisit;

        //printf ("User [ %s ]\n", $user2->debugDump());

        $this->assertEquals($expected,
                            $user2->toArray( Connexions_Model::DEPTH_SHALLOW,
                                             Connexions_Model::FIELDS_ALL ));

        // Verify that the identity map is updated
        $user3 = $user->getMapper()->find( $user->userId );
        $this->assertNotEquals(null, $user3);
        $this->assertSame($user2, $user3);
    }

    public function testUserGetId()
    {
        $expected = $this->_user1['userId'];

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( $expected );

        $this->assertNotEquals(null, $user);
        $this->assertEquals($expected, $user->getId());
    }

    public function testUserRetrieveById2()
    {
        $expected = $this->_user1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( array('userId' => $expected['userId']) );
        $this->assertNotEquals(null, $user);
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserRetrieveByName1()
    {
        $expected = $this->_user1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( $expected['name'] );
        $this->assertNotEquals(null, $user);
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserRetrieveByName2()
    {
        $expected = $this->_user1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( array('name' => $expected['name']) );
        $this->assertNotEquals(null, $user);
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testUserDeletedFromDatabase()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( 1 );

        $user->delete();

        /*
        echo "Deleted User:\n";
        echo $user->debugDump();
        // */

        // Make sure the user instance has been deleted
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

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
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
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
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

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $users  = $mapper->fetch();

        $this->assertNotEquals(null, $users);
        $this->assertEquals($expectedCount, $users->count());
        $this->assertEquals($expectedTotal, $users->getTotalCount());
    }

    public function testUserSetLimitOrder()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
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

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $users  = $mapper->fetch(null,
                                 array('name ASC'), // order
                                 $expectedCount,    // count
                                 1);                // offset

        $this->assertNotEquals(null, $users);
        $this->assertEquals($expectedCount, $users->count());
        $this->assertEquals($expectedTotal, $users->getTotalCount());
    }

    public function testUserTagRename()
    {
        //$rename = array(5, 10, 15);
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

        // Retrieve the target user
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( 1 );
        $this->assertNotEquals(null, $user);

        $res    = $user->renameTags($renames);
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
    }

    public function testUserTagDelete()
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
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( 1 );
        $this->assertNotEquals(null, $user);

        $res    = $user->deleteTags($tags);
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
    }
}
