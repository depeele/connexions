<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/services/Activity.php';

/**
 *  @group Services
 */
class ActivityServiceTest extends DbTestCase
{
    protected static    $toArray_deep_all       = array(
        'deep'      => true,
        'public'    => false,
        'dirty'     => false,
        'raw'       => true,
    );
    protected static    $toArray_shallow_all    = array(
        'deep'      => false,
        'public'    => false,
        'dirty'     => false,
        'raw'       => true,
    );

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

        $iMapper = Connexions_Model_Mapper::factory('Model_Mapper_Item');
        $iMapper->flushIdentityMap();

        $tMapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tMapper->flushIdentityMap();

        $bMapper = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bMapper->flushIdentityMap();

        parent::tearDown();
    }

    public function testActivityServiceFactory()
    {
        $service1 = Connexions_Service::factory('Model_Activity');
        $this->assertTrue( $service1 instanceof Connexions_Service );
        $this->assertTrue( $service1 instanceof Service_Activity );

        $service2 = Connexions_Service::factory('Service_Activity');
        $this->assertTrue( $service2 instanceof Connexions_Service );
        $this->assertTrue( $service2 instanceof Service_Activity );
        $this->assertSame( $service1, $service2 );
    }

    public function testActivityServiceGet()
    {
        $expected = $this->_activity1;
        $service  = Connexions_Service::factory('Model_Activity');

        $activity = $service->get( $data = array(
            'activityId'  => $expected['activityId'],
        ));

        $this->assertTrue( $activity instanceof Model_Activity );

        $this->assertEquals($expected,
                            $activity->toArray(self::$toArray_shallow_all));
    }

    /*************************************************************************
     * Single Instance retrieval tests
     *
     */
    public function testActivityServiceFindByActivityId1()
    {
        $expected = $this->_activity1;
        $service  = Connexions_Service::factory('Model_Activity');
        $id       = array( 'activityId' => $expected['activityId']);

        $activity     = $service->find( $id );

        $this->assertTrue(  $activity instanceof Model_Activity );
        $this->assertTrue(  $activity->isBacked() );
        $this->assertTrue(  $activity->isValid() );

        $this->assertEquals($expected,
                            $activity->toArray(self::$toArray_shallow_all));
    }

    public function testActivityServiceFindByActivityId2()
    {
        $expected = $this->_activity1;
        $service  = Connexions_Service::factory('Model_Activity');
        $id       = $expected['activityId'];

        $activity     = $service->find( $id );

        $this->assertTrue(  $activity instanceof Model_Activity );
        $this->assertTrue(  $activity->isBacked() );
        $this->assertTrue(  $activity->isValid() );

        $this->assertEquals($expected,
                            $activity->toArray(self::$toArray_shallow_all));
    }

    /*************************************************************************
     * Set retrieval tests
     *
     */
    public function testActivityServiceFetch1()
    {
        $expectedAr = array( 1, 2 );
        $fetchAr    = $expectedAr;

        $service    = Connexions_Service::factory('Model_Activity');
        $activities = $service->fetch( $fetchAr );

        $this->assertNotEquals(null, $activities);

        /*
        printf ("Activities: [ %s ]\n",
                print_r($activities->toArray(), true));
        // */

        $ids        = $activities->getIds();

        $this->assertEquals($expectedAr, $ids);
    }

    public function testActivityServiceFetchByUsers1()
    {
        $expectedAr = array( 1, 2 );
        $fetchAr    = $expectedAr;

        $service    = Connexions_Service::factory('Model_Activity');
        $activities = $service->fetchByUsers( array( 1 ) );

        $this->assertNotEquals(null, $activities);

        /*
        printf ("Activities: [ %s ]\n",
                print_r($activities->toArray(), true));
        // */

        $ids        = $activities->getIds();

        $this->assertEquals($expectedAr, $ids);
    }

    public function testActivityServiceFetchByUsers2()
    {
        $expectedAr = array( 1, 2 );
        $fetchAr    = $expectedAr;

        $service    = Connexions_Service::factory('Model_Activity');
        $activities = $service->fetchByUsers( '1' );

        $this->assertNotEquals(null, $activities);

        /*
        printf ("Activities: [ %s ]\n",
                print_r($activities->toArray(), true));
        // */

        $ids        = $activities->getIds();

        $this->assertEquals($expectedAr, $ids);
    }

    public function testActivityServiceFetchByUsers3()
    {
        $uService = Connexions_Service::factory('Service_User');
        $user     = $uService->find( $this->_activity1['userId'] );

        $expectedAr = array( 1, 2 );
        $fetchAr    = $expectedAr;

        $service    = Connexions_Service::factory('Model_Activity');
        $activities = $service->fetchByUsers( $user );

        $this->assertNotEquals(null, $activities);

        /*
        printf ("Activities: [ %s ]\n",
                print_r($activities->toArray(), true));
        // */

        $ids        = $activities->getIds();

        $this->assertEquals($expectedAr, $ids);
    }

    public function testActivityServiceFetchByUsers4()
    {
        $expectedAr = array( 1, 2 );
        $fetchAr    = $expectedAr;

        $service    = Connexions_Service::factory('Model_Activity');
        $activities = $service->fetchByUsers( '1',
                                              null, // order
                                              null, // count
                                              null, // offset
                                              "2010-04-05 17:25:18" );

        $this->assertNotEquals(null, $activities);

        /*
        printf ("Activities: [ %s ]\n",
                print_r($activities->toArray(), true));
        // */

        $ids        = $activities->getIds();

        $this->assertEquals($expectedAr, $ids);
    }

    public function testActivityServiceFetchByUsers5()
    {
        $expectedAr = array( 2 );
        $fetchAr    = $expectedAr;

        $service    = Connexions_Service::factory('Model_Activity');
        $activities = $service->fetchByUsers( '1',
                                              null, // objectType
                                              null, // operation
                                              null, // order
                                              null, // count
                                              null, // offset
                                              "2010-04-05 17:25:30" );

        $this->assertNotEquals(null, $activities);

        /*
        printf ("Activities: [ %s ]\n",
                $activities->debugDump());
        // */

        $ids        = $activities->getIds();

        $this->assertEquals($expectedAr, $ids);
    }

    public function testActivityServiceFetchByUsers6()
    {
        $expectedAr = array( );
        $fetchAr    = $expectedAr;

        $service    = Connexions_Service::factory('Model_Activity');
        $activities = $service->fetchByUsers( '2' );

        $this->assertNotEquals(null, $activities);

        /*
        printf ("Activities: [ %s ]\n",
                print_r($activities->toArray(), true));
        // */

        $ids        = $activities->getIds();

        $this->assertEquals($expectedAr, $ids);
    }
}
