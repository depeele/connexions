<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/models/Activity.php';

class ActivityDbTest extends DbTestCase
{
    protected   $_activity1 = array(
        'activityId'    => "1",
        'userId'        => "1",
        'objectType'    => "bookmark",
        'objectId'      => "1:1",
        'operation'     => "save",
        'time'          => "2010-04-05 17:25:19",
        'properties'    => '{"userId":1,"itemId":1,"name":"More than a password manager | CLipperz","description":"Testing 1,2...","rating":0,"isFavorite":0,"isPrivate":0,"taggedOn":"2010-04-05 17:25:19","updatedOn":"2010-04-05 17:25:19"}',
    );
    protected   $_activity2 = array(
        'activityId'    => "2",
        'userId'        => "1",
        'objectType'    => "bookmark",
        'objectId'      => "1:1",
        'operation'     => "update",
        'time'          => "2010-07-22 10:00:00",
        'properties'    => '{"description":"Testing 1,2 3, 4...","rating":1,"isPrivate":1,"updatedOn":"2010-04-05 17:25:19"}',
    );
    protected   $_activity3 = array(
        'activityId'    => "3",
        'userId'        => "1",
        'objectType'    => "bookmark",
        'objectId'      => "1:1",
        'operation'     => "update",
        'time'          => null,
        'properties'    => '{"description":"Testing...","rating":0,"isPrivate":0,"updatedOn":"2011-04-12 17:25:19"}',
    );


    protected function getDataSet()
    {
        return $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/5users+groups.xml');
    }

    protected function tearDown()
    {
        /* Since these tests setup and teardown the database for each new test,
         * we need to clean-up any Identity Maps that are used in order to 
         * maintain test validity.
         */
        $uMapper = Connexions_Model_Mapper::factory('Model_Mapper_Activity');
        $uMapper->flushIdentityMap();

        parent::tearDown();
    }

    protected function _authAs($userId)
    {
        $service  = Connexions_Service::factory('Model_User');
        $user     = $service->find( $userId  );

        $this->assertTrue(  $user instanceof Model_User );
        $this->assertTrue(  $user->isBacked() );
        $this->assertTrue(  $user->isValid() );
        $this->assertFalse( $user->isAuthenticated() );

        // Mark the user as 'authenticated'
        $this->_setAuthenticatedUser($user);
        $this->assertTrue ($user->isAuthenticated());

        return $user;
    }

    public function testActivityInsertedIntoDatabase()
    {
        $expected = $this->_activity3;
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Activity');
        $activity = $mapper->getModel( array(
                                'userId'     => $expected['userId'],
                                'objectType' => $expected['objectType'],
                                'objectId'   => $expected['objectId'],
                                'operation'  => $expected['operation'],
                                'properties' => $expected['properties'],
        ));

        $this->assertFalse ( $activity->isBacked() );
        $this->assertTrue  ( $activity->isValid() );

        //printf ("Activity [ %s ]\n", $activity->debugDump());

        $activity = $activity->save();

        $this->assertNotEquals(null, $activity);
        $this->assertTrue  ( $activity->isBacked() );
        $this->assertTrue  ( $activity->isValid() );

        // apiKey and lastVisit are dynamically generated
        $expected['time'] = $activity->time;

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('activity', 'SELECT * FROM activity');

        /*********
         * Modify 'apiKey' and 'lastVisit' in our expected set for the target
         * row since it's dynamic...
         */
        $es = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/activityInsertAssertion.xml');
        $et = $es->getTable('activity');
        $et->setValue(2, 'time', $expected['time']);

        $this->assertDataSetsEqual( $es, $ds );
    }

    public function testActivityRetrieveByUnknownId()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Activity');
        $activity = $mapper->find( array('activityId' => 5 ));

        $this->assertEquals(null, $user);
    }

    public function testActivityRetrieveById1()
    {
        $expected = $this->_activity1;

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Activity');
        $activity = $mapper->find( array(
                            'activityId' => $this->_activity1['activityId']
        ));

        /*
        printf ("Activity by id %d:\n", $this->_activity1['activityid']);
        echo $activity->debugDump();
        // */

        $this->assertNotEquals(null, $activity);
        $this->assertTrue  ( $activity->isBacked() );
        $this->assertTrue  ( $activity->isValid() );

        $this->assertEquals($expected,
                            $activity->toArray(self::$toArray_shallow_all));
    }

    public function testActivityIdentityMap()
    {
        $mapper    = Connexions_Model_Mapper::factory('Model_Mapper_Activity');
        $activity  = $mapper->find( array('activityId' =>
                                            $this->_activity1['activityId'] ));
        $activity2 = $mapper->find( array('activityId' =>
                                            $this->_activity1['activityId'] ));

        $this->assertSame  ( $activity, $activity2 );
    }

    public function testActivityIdentityMap2()
    {
        $mapper    = Connexions_Model_Mapper::factory('Model_Mapper_Activity');
        $activity  = $mapper->find( array('activityId' =>
                                            $this->_activity1['activityId'] ));
        $activity2 = $mapper->find( array('activityId' =>
                                            $this->_activity1['activityId'] ));

        $this->assertSame  ( $activity, $activity2 );
    }

    public function testActivityGetId()
    {
        $expected = $this->_activity1['activityId'];

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Activity');
        $activity = $mapper->find( array('activityId' => $expected ));

        $this->assertNotEquals(null, $activity);
        $this->assertEquals($expected, $activity->getId());
    }

    public function testActivityGetUser1()
    {
        $expected   = 'User1';

        // Retrieve the target user
        $mapper     = Connexions_Model_Mapper::factory('Model_Mapper_Activity');
        $activity   = $mapper->find( array('activityId' => 1 ));
        $this->assertNotEquals(null, $activity);

        $res    = $activity->getUser();
        $this->assertEquals($expected, $res->__toString());
    }

    public function testActivityGetObject1()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Activity');
        $activity = $mapper->find( array('activityId' => 1 ));

        $this->assertTrue(  $activity instanceof Model_Activity );
        $this->assertTrue(  $activity->isBacked() );
        $this->assertTrue(  $activity->isValid() );

        /* Retrieve the referenced object -- the bookmark is private so, unless
         * authenticated as user1, this should return null
         */
        $object = $activity->getObject();
        $this->assertEquals(null, $object);
    }

    public function testActivityGetObject2()
    {
        $expected = array(1,1);
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Activity');
        $activity = $mapper->find( array('activityId' => 1 ));

        $this->assertTrue(  $activity instanceof Model_Activity );
        $this->assertTrue(  $activity->isBacked() );
        $this->assertTrue(  $activity->isValid() );

        // Authenticate as the owner
        $user = $this->_authAs($activity->userId);

        /* Retrieve the referenced object -- the bookmark is private so, unless
         * authenticated as user1, this should return null
         */
        $object = $activity->getObject();
        $this->assertNotEquals(null, $object);

        /*
        printf ("Activity[ %s ] object[ %s ], id[ %s ]",
                $activity,
                ($object === null ? 'null' : $object->debugDump()),
                ($object === null
                    ? ''
                    : Connexions::varExport($object->getId()))
                );
        // */

        $this->assertEquals($expected, $object->getId());

        // De-authenticate $user
        $this->_unsetAuthenticatedUser($user);
    }
}
