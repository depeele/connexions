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
                        'lastVisitFor'  => '0000-00-00 00:00:00',

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
                        'lastVisitFor'  => '0000-00-00 00:00:00',

                        'totalTags'     => 0,
                        'totalItems'    => 0,
                        'userItemCount' => 0,
                        'itemCount'     => 0,
                        'tagCount'      => 0,
    );

    protected function getDataSet()
    {
        return $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/5users+groups.xml');
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
                            $user->toArray(self::$toArray_shallow_all));

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
        $user   = $mapper->find( array('userId' => 5 ));

        $this->assertEquals(null, $user);
    }

    public function testUserRetrieveById1()
    {
        $expected = $this->_user1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( array('userId' => $this->_user1['userId'] ));

        /*
        printf ("User by id %d:\n", $this->_user1['userid']);
        echo $user->debugDump();
        // */

        $this->assertNotEquals(null, $user);
        $this->assertTrue  ( $user->isBacked() );
        $this->assertTrue  ( $user->isValid() );
        $this->assertFalse ( $user->isAuthenticated() );

        $this->assertEquals($expected,
                            $user->toArray(self::$toArray_shallow_all));
    }

    public function testUserIdentityMap()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( array('userId' => $this->_user1['userId'] ));
        $user2  = $mapper->find( array('userId' => $this->_user1['userId'] ));

        $this->assertSame  ( $user, $user2 );
    }

    public function testUserIdentityMap2()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( array('userId' => $this->_user1['userId'] ));
        $user2  = $mapper->find( array('name'   => $this->_user1['name'] ));

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

        //printf ("User new:     [ %s ]\n", $user->debugDump());

        $this->assertFalse ( $user->isBacked() );
        $this->assertTrue  ( $user->isValid() );

        $user = $user->save();
        $this->assertNotEquals(null, $user);

        //printf ("User saved:   [ %s ]\n", $user->debugDump());

        $this->assertTrue  ( $user->isBacked() );
        $this->assertTrue  ( $user->isValid() );

        // apiKey and lastVisit are dynamically generated
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

        $this->assertEquals($user->getValidationMessages(), array() );
        $this->assertEquals($expected,
                            $user->toArray(self::$toArray_shallow_all));

        //Connexions::log('--------------------------------------------------');
        // Update email
        $expected['email'] = 'user5@gmail.com';
        $user->email       = $expected['email'];

        //printf ("User updated: [ %s ]\n", $user->debugDump());
        //printf ("User [ %s ]\n", $user->debugDump());
        $user2 = $user->save();

        //printf ("User saved2:  [ %s ]\n", $user->debugDump());
        $this->assertNotEquals(null, $user2);

        // The lastVisit time MAY have changed
        $expected['lastVisit'] = $user2->lastVisit;

        //printf ("User [ %s ]\n", $user2->debugDump());

        $this->assertEquals($expected,
                            $user2->toArray(self::$toArray_shallow_all));

        // Verify that the identity map is updated
        $user3 = $user->getMapper()->find( array('userId' => $user->userId ));
        $this->assertNotEquals(null, $user3);
        $this->assertSame($user2, $user3);
    }

    public function testUserGetId()
    {
        $expected = $this->_user1['userId'];

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( array('userId' => $expected ));

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
                            $user->toArray(self::$toArray_shallow_all));
    }

    public function testUserRetrieveByName1()
    {
        $expected = $this->_user1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( array('name' => $expected['name'] ));
        $this->assertNotEquals(null, $user);
        $this->assertEquals($expected,
                            $user->toArray(self::$toArray_shallow_all));
    }

    public function testUserDeletedFromDatabase()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( array('userId' => 1 ));

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
        $user   = $mapper->find( array('userId' => 1 ));

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
        $user   = $mapper->find( array('userId' => 1 ));
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
        $user   = $mapper->find( array('userId' => 1 ));
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

    public function testUserGetTags1()
    {
        $expected   = 'security,passwords,privacy,identity,web2.0,online,password,storage,ajax,tools,javascript,framework,library,oat,widgets,demo,graph,chart,diagram,graphics,generator,php,test,cryptography';

        // Retrieve the target user
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( array('userId' => 1 ));
        $this->assertNotEquals(null, $user);

        $res    = $user->getTags(null,  // order
                                 null,  // count
                                 null,  // offset
                                 null); // term
        $this->assertEquals($expected, $res->__toString());
    }

    public function testUserGetTags2()
    {
        $expected   = 'passwords,password,storage,framework,generator';

        // Retrieve the target user
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $user   = $mapper->find( array('userId' => 1 ));
        $this->assertNotEquals(null, $user);

        $res    = $user->getTags(null,  // order
                                 null,  // count
                                 null,  // offset
                                 'or'); // term
        $this->assertEquals($expected, $res->__toString());
    }

    public function testUserGetNetwork1()
    {
        $expected = 2;
        $service  = Connexions_Service::factory('Model_User');
        $id       = array( 'userId' => 1 );

        $user     = $service->find( $id );

        $this->assertTrue(  $user instanceof Model_User );
        $this->assertTrue(  $user->isBacked() );
        $this->assertTrue(  $user->isValid() );
        $this->assertFalse( $user->isAuthenticated() );

        // User1's Network is private
        $network = $user->getNetwork();
        $this->assertNotEquals(null, $network);

        /*
        printf ("User[ %s ] network[ %s ]",
                $user, ($network === null ? 'null' : $network->debugDump()) );
        // */

        $this->assertEquals($expected, $network->getId());
    }

    public function testUserGetNetwork2()
    {
        $expected = 4;
        $service  = Connexions_Service::factory('Model_User');

        $user     = $service->find(array( 'userId' => 2 ));

        $this->assertTrue(  $user instanceof Model_User );
        $this->assertTrue(  $user->isBacked() );
        $this->assertTrue(  $user->isValid() );
        $this->assertFalse( $user->isAuthenticated() );

        /*
        // Mark the user as 'authenticated'
        $this->_setAuthenticatedUser($user);
        $this->assertTrue($user->isAuthenticated());
        // */

        $network = $user->getNetwork();
        $this->assertNotEquals(null, $network);

        /*
        printf ("User[ %s ] network[ %s ]",
                $user, ($network === null ? 'null' : $network->debugDump()) );
        // */

        $this->assertEquals($expected, $network->getId());

        // De-authenticate $user
        //$this->_unsetAuthenticatedUser($user);
    }

    public function testUserGetNetwork3()
    {
        $expected = 2;
        $service  = Connexions_Service::factory('Model_User');

        $user1     = $service->find(array( 'userId' => 1 ));
        $this->assertTrue(  $user1 instanceof Model_User );
        $this->assertTrue(  $user1->isBacked() );
        $this->assertTrue(  $user1->isValid() );
        $this->assertFalse( $user1->isAuthenticated() );

        $user2    = $service->find(array( 'userId' => 2 ));
        $this->assertTrue(  $user2 instanceof Model_User );
        $this->assertTrue(  $user2->isBacked() );
        $this->assertTrue(  $user2->isValid() );
        $this->assertFalse( $user2->isAuthenticated() );

        $user4    = $service->find(array( 'userId' => 4 ));
        $this->assertTrue(  $user4 instanceof Model_User );
        $this->assertTrue(  $user4->isBacked() );
        $this->assertTrue(  $user4->isValid() );
        $this->assertFalse( $user4->isAuthenticated() );


        // Mark the user as 'authenticated'
        $this->_setAuthenticatedUser($user1);
        $this->assertTrue($user1->isAuthenticated());

        // User1's Network is private
        $network = $user1->getNetwork();
        $this->assertNotEquals(null, $network);

        /*
        printf ("User[ %s ] network[ %s ]",
                $user1, ($network === null ? 'null' : $network->debugDump()) );
        // */

        $this->assertEquals($expected, $network->getId());

        /*
        printf ("Group %d: items [ %s ]\n",
                $network->getId(), $network->items);
        printf ("-- %s in network[ %s ]\n",
                $user2,
                ($network->items->contains( $user2 )
                    ? 'yes' : 'no'));
        printf ("-- %s in network[ %s ]\n",
                $user4,
                ($network->items->contains( $user4 )
                    ? 'yes' : 'no'));
        // */

        $this->assertFalse($network->items->contains( $user2 ));
        $this->assertTrue( $network->items->contains( $user4 ));

        // De-authenticate $user1
        $this->_unsetAuthenticatedUser($user1);
    }

    public function testUserCanNetwork1()
    {
        $service  = Connexions_Service::factory('Model_User');

        $user1    = $service->find( array('userId' => 1) );
        $this->assertTrue(  $user1 instanceof Model_User );
        $this->assertTrue(  $user1->isBacked() );
        $this->assertTrue(  $user1->isValid() );
        $this->assertFalse( $user1->isAuthenticated() );

        $user2    = $service->find( array('userId' => 2) );
        $this->assertTrue(  $user2 instanceof Model_User );
        $this->assertTrue(  $user2->isBacked() );
        $this->assertTrue(  $user2->isValid() );
        $this->assertFalse( $user2->isAuthenticated() );

        $user3    = $service->find( array('userId' => 3) );
        $this->assertTrue(  $user2 instanceof Model_User );
        $this->assertTrue(  $user2->isBacked() );
        $this->assertTrue(  $user2->isValid() );
        $this->assertFalse( $user2->isAuthenticated() );


        /* User1's Network is 'visibility     = private',
         *                    'controlMembers = owner',
         *                    'controlItems   = owner'
         */
        $network = $user1->getNetwork();
        $this->assertNotEquals(null, $network);

        // User1's Network is private: viewable only by user1.
        $this->assertTrue( $network->canView( $user1 ));
        $this->assertFalse($network->canView( $user2 ));
        $this->assertFalse($network->canView( $user3 ));

        // User1's Network has 'controlMembers' of 'owner'
        $this->assertTrue( $network->canControlMembers( $user1 ));
        $this->assertFalse($network->canControlMembers( $user2 ));
        $this->assertFalse($network->canControlMembers( $user3 ));

        // User1's Network has 'controlItems' of 'owner'
        $this->assertTrue( $network->canControlItems( $user1 ));
        $this->assertFalse($network->canControlItems( $user2 ));
        $this->assertFalse($network->canControlItems( $user3 ));
    }

    public function testUserCanNetwork2()
    {
        $service  = Connexions_Service::factory('Model_User');

        $user1    = $service->find( array('userId' => 1) );
        $user2    = $service->find( array('userId' => 2) );
        $user3    = $service->find( array('userId' => 3) );


        /* User2's Network is 'visibility     = public',
         *                    'controlMembers = group',
         *                    'controlItems   = group'
         *
         *  Members: User 1, 2
         */
        $network = $user2->getNetwork();
        $this->assertNotEquals(null, $network);


        // User2's Network is public.
        $this->assertTrue( $network->canView( $user1 ));
        $this->assertTrue( $network->canView( $user2 ));
        $this->assertTrue( $network->canView( $user3 ));

        // User2's Network has 'controlMembers' of 'group'
        $this->assertTrue( $network->canControlMembers( $user1 ));
        $this->assertTrue( $network->canControlMembers( $user2 ));
        $this->assertFalse($network->canControlMembers( $user3 ));

        // User2's Network has 'controlItems' of 'group'
        $this->assertTrue( $network->canControlItems( $user1 ));
        $this->assertTrue( $network->canControlItems( $user2 ));
        $this->assertFalse($network->canControlItems( $user3 ));
    }

    public function testUserCanNetwork3()
    {
        $service  = Connexions_Service::factory('Model_User');

        $user1    = $service->find( array('userId' => 1) );
        $user2    = $service->find( array('userId' => 2) );
        $user3    = $service->find( array('userId' => 3) );


        /* User3's Network is 'visibility     = group',
         *                    'controlMembers = group',
         *                    'controlItems   = group'
         *
         *  Members: User 2, 3, 4
         */
        $network = $user3->getNetwork();
        $this->assertNotEquals(null, $network);


        // User2's Network is group.
        $this->assertFalse($network->canView( $user1 ));
        $this->assertTrue( $network->canView( $user2 ));
        $this->assertTrue( $network->canView( $user3 ));

        // User2's Network has 'controlMembers' of 'group'
        $this->assertFalse($network->canControlMembers( $user1 ));
        $this->assertTrue( $network->canControlMembers( $user2 ));
        $this->assertTrue( $network->canControlMembers( $user3 ));

        // User2's Network has 'controlItems' of 'group'
        $this->assertFalse($network->canControlItems( $user1 ));
        $this->assertTrue( $network->canControlItems( $user2 ));
        $this->assertTrue( $network->canControlItems( $user3 ));
    }

    public function testUserNetworkAddMember1()
    {
        $expected = array(1,2,3,4);
        $service  = Connexions_Service::factory('Model_User');

        $user1    = $service->find( array('userId' => 1) );
        $user2    = $service->find( array('userId' => 2) );
        $user3    = $service->find( array('userId' => 3) );


        /* User3's Network is 'visibility     = group',
         *                    'controlMembers = group',
         *                    'controlItems   = group'
         *
         *  Members: User 2, 3, 4
         */
        $network = $user3->getNetwork();
        $this->assertNotEquals(null, $network);

        $this->_setAuthenticatedUser($user3);
        $this->assertTrue($user3->isAuthenticated());

        $network->addMember($user1);
        $this->assertEquals($expected, $network->members->getIds());

        // De-authenticate $user
        $this->_unsetAuthenticatedUser($user3);
    }

    public function testUserNetworkAddItem1()
    {
        $expected = array(1,2,3);
        $uService = Connexions_Service::factory('Model_User');
        $tService = Connexions_Service::factory('Model_Tag');

        $user2    = $uService->find( 2 );
        $user1    = $uService->find( 1 );
        $user3    = $uService->find( 3 );
        $tag1     = $tService->find( 1 );


        $network = $user2->getNetwork();
        $this->assertNotEquals(null, $network);

        $this->_setAuthenticatedUser($user2);
        $this->assertTrue($user2->isAuthenticated());

        // Already added item...
        $network->addItem($user1);

        // Adding new item...
        $network->addItem($user3);

        // Adding an item of the wrong type...
        try
        {
            $network->addItem($tag1);
        }
        catch (Exception $e)
        {
            $this->assertEquals("Unexpected model instance for 'user' group",
                                $e->getMessage());
        }

        $this->assertEquals($expected, $network->items->getIds());

        // De-authenticate $user
        $this->_unsetAuthenticatedUser($user2);
    }

    public function testUserNetworkRelation1()
    {
        $expected = array('self');
        $service  = Connexions_Service::factory('Model_User');

        $user     = $service->find(array( 'userId' => 2 ));

        $this->assertTrue(  $user instanceof Model_User );
        $this->assertTrue(  $user->isBacked() );
        $this->assertTrue(  $user->isValid() );
        $this->assertFalse( $user->isAuthenticated() );

        $relation = $user->networkRelation( $user );

        $this->assertEquals($expected, $relation);
    }

    public function testUserNetworkRelation2()
    {
        $expected = array('amIn');
        $service  = Connexions_Service::factory('Model_User');

        $user1    = $service->find( 1 );
        $user2    = $service->find( 2 );

        $relation = $user1->networkRelation( $user2 );

        $this->assertEquals($expected, $relation);
    }

    public function testUserNetworkRelation3()
    {
        $expected = array('isIn');
        $service  = Connexions_Service::factory('Model_User');

        $user1    = $service->find( 1 );
        $user2    = $service->find( 2 );

        $relation = $user2->networkRelation( $user1 );

        $this->assertEquals($expected, $relation);
    }

    public function testUserNetworkRelation4()
    {
        $expected = array('isIn', 'amIn', 'mutual');
        $service  = Connexions_Service::factory('Model_User');

        $user1    = $service->find( 1 );
        $user3    = $service->find( 3 );

        $relation = $user1->networkRelation( $user3 );

        $this->assertEquals($expected, $relation);
    }

    public function testUserNetworkRelation5()
    {
        $expected = array('none');
        $service  = Connexions_Service::factory('Model_User');

        $user2    = $service->find( 2 );
        $user4    = $service->find( 4 );

        $relation = $user2->networkRelation( $user4 );

        $this->assertEquals($expected, $relation);
    }

    public function testUserNetworkRelation6()
    {
        $expected = array();
        $service  = Connexions_Service::factory('Model_User');

        $user1    = $service->find( 1 );

        $relation = $user1->networkRelation( null );

        $this->assertEquals($expected, $relation);
    }
}
