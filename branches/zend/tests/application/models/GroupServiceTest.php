<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/services/Group.php';

class GroupServiceTest extends DbTestCase
{
    private     $_groups    = array(
        // New item
        array(
            'groupId'           => false,
            'name'              => 'Group5',
            'groupType'         => 'tag',
            'ownerId'           => 1,

            'controlMembers'    => 'owner',
            'controlItems'      => 'owner',
            'visibility'        => 'private',
            'canTransfer'       => false,
        ),
        // Duplicate of data from the source DataSet
        array(
            'groupId'           => 1,
            'name'              => 'Tags',
            'groupType'         => 'tag',
            'ownerId'           => 1,

            'controlMembers'    => 'owner',
            'controlItems'      => 'owner',
            'visibility'        => 'private',
            'canTransfer'       => 0,
        ),
        array(
            'groupId'           => 2,
            'name'              => 'System:Network',
            'groupType'         => 'user',
            'ownerId'           => 1,

            'controlMembers'    => 'owner',
            'controlItems'      => 'owner',
            'visibility'        => 'private',
            'canTransfer'       => 0,
        ),
        array(
            'groupId'           => 3,
            'name'              => 'Urls',
            'groupType'         => 'item',
            'ownerId'           => 1,

            'controlMembers'    => 'owner',
            'controlItems'      => 'owner',
            'visibility'        => 'group',
            'canTransfer'       => 0,
        ),
        array(
            'groupId'           => 4,
            'name'              => 'System:Network',
            'groupType'         => 'user',
            'ownerId'           => 2,

            'controlMembers'    => 'owner',
            'controlItems'      => 'owner',
            'visibility'        => 'public',
            'canTransfer'       => 0,
        ),
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
        $uMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $uMapper->flushIdentityMap();

        $gMapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $gMapper->flushIdentityMap();

        $iMapper = Connexions_Model_Mapper::factory('Model_Mapper_Item');
        $iMapper->flushIdentityMap();

        $tMapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tMapper->flushIdentityMap();

        $bMapper = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bMapper->flushIdentityMap();


        parent::tearDown();
    }

    public function testGroupServiceFactory()
    {
        $service1 = Connexions_Service::factory('Model_Group');
        $this->assertTrue( $service1 instanceof Connexions_Service );
        $this->assertTrue( $service1 instanceof Service_Group );

        $service2 = Connexions_Service::factory('Service_Group');
        $this->assertTrue( $service2 instanceof Connexions_Service );
        $this->assertTrue( $service2 instanceof Service_Group );
        $this->assertSame( $service1, $service2 );
    }

    public function testGroupServiceCreateNew()
    {
        $expected = $this->_groups[0];
        $service  = Connexions_Service::factory('Model_Group');

        $group    = $service->get( $data = array(
            'name'      => $expected['name'],
            'groupType' => $expected['groupType'],
            'ownerId'   => $expected['ownerId'],
        ));

        $this->assertTrue( $group instanceof Model_Group );

        $this->assertFalse(  $group->isBacked() );
        $this->assertTrue(   $group->isValid() );

        $this->assertEquals($group->getValidationMessages(), array() );
        $this->assertEquals($expected,
                            $group->toArray(self::$toArray_shallow_all));
    }

    public function testGroupServiceCreateExistingReturnsBackedInstance()
    {
        $expected = $this->_groups[1];
        $service  = Connexions_Service::factory('Model_Group');

        $group    = $service->get( $data = array(
            'name'      => $expected['name'],
            'groupType' => $expected['groupType'],
            'ownerId'   => $expected['ownerId'],
        ));

        $this->assertTrue(  $group instanceof Model_Group );
        $this->assertTrue(  $group->isBacked() );
        $this->assertTrue(  $group->isValid() );

        $this->assertEquals($expected,
                            $group->toArray(self::$toArray_shallow_all));
    }

    /*************************************************************************
     * Single Instance retrieval tests
     *
     */
    public function testGroupServiceFind1()
    {
        $expected        = $this->_groups[1];
        $expectedMembers = array(1,4);
        $expectedItems   = array(6,10,12);
        $service         = Connexions_Service::factory('Model_Group');
        $id              = array( 'groupId' => $expected['groupId']);

        $group           = $service->find( $id );

        $this->assertTrue(  $group instanceof Model_Group );
        $this->assertTrue(  $group->isBacked() );
        $this->assertTrue(  $group->isValid() );

        /*
        printf ("group[ %s ]\n", $group->debugDump());
        // */

        $this->assertEquals($expected,
                            $group->toArray(self::$toArray_shallow_all));

        $this->assertEquals($expectedMembers, $group->members->getIds());
        $this->assertEquals($expectedItems,   $group->items->getIds());
    }

    public function testGroupServiceFind2()
    {
        $expected = $this->_groups[2];
        $service  = Connexions_Service::factory('Model_Group');
        $id       = array( 'name'       => $expected['name'],
                           'ownerId'    => $expected['ownerId'],
                           'groupType'  => $expected['groupType'] );

        $group    = $service->find( $id );

        $this->assertTrue(  $group instanceof Model_Group );
        $this->assertTrue(  $group->isBacked() );
        $this->assertTrue(  $group->isValid() );

        $this->assertEquals($expected,
                            $group->toArray(self::$toArray_shallow_all));
    }

    public function testGroupServiceFind3()
    {
        $expected = $this->_groups[3];
        $service  = Connexions_Service::factory('Model_Group');
        $id       = $expected['groupId'];

        $group    = $service->find( $id );

        $this->assertTrue(  $group instanceof Model_Group );
        $this->assertTrue(  $group->isBacked() );
        $this->assertTrue(  $group->isValid() );

        $this->assertEquals($expected,
                            $group->toArray(self::$toArray_shallow_all));
    }

    /*************************************************************************
     * Set retrieval tests
     *
     */

    public function testGroupServiceFetchSet()
    {
        $service  = Connexions_Service::factory('Model_Group');
        $groups   = $service->fetch();

        //printf ("Groups [ %s ]\n", $groups->debugDump());

        // Fetch the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/groupSetAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('memberGroup'), $groups );
    }

    public function testGroupServiceFetchPaginated()
    {
        $service  = Connexions_Service::factory('Model_Group');
        $groups   = $service->fetchPaginated();

        /*
        printf ("%d groups of %d, %d pages with %d per page, current page %d\n",
                $groups->getTotalItemCount(),
                $groups->getCurrentItemCount(),
                $groups->count(),
                $groups->getItemCountPerPage(),
                $groups->getCurrentPageNumber());
        // */

        $this->assertEquals(4, $groups->getTotalItemCount());
        $this->assertEquals(4, $groups->getCurrentItemCount());
        $this->assertEquals(1,  count($groups));

        /*
        foreach ($groups as $idex => $group)
        {
            printf ("Row %2d: [ %s ]\n",
                    $idex,
                    Connexions::varExport( (is_object($group)
                                                ? $group->debugDump()
                                                : $group)));
        }
        // */

        // Fetch the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/groupSetAssertion.xml');

        $this->assertPaginatedSetEquals( $ds->getTable('memberGroup'), $groups);
    }

    public function testGroupServiceFetchByOwners1()
    {
                    // vv ordered by 'name ASC, visibility DESC, groupType DESC'
        $expected   = array(2, 1, 3);
        $users      = 1;
        $service    = Connexions_Service::factory('Model_Group');
        $groups     = $service->fetchByOwners( $users,
                                               false );  // !exact
        $this->assertNotEquals(null, $groups);

        /*
        printf ("Groups: [ %s ]\n", $groups->debugDump());
        printf ("Groups: [ %s ]\n",
                print_r($groups->getIds(), true));
        printf ("Groups: [ %s ]\n", $groups);
        // */

        $this->assertEquals($expected, $groups->getIds());
    }

    public function testGroupServiceFetchByOwners2()
    {
                    // vv ordered by 'name ASC, visibility DESC, groupType DESC'
        $expected   = array(2, 1, 3);
        $users      = 'User1';
        $service    = Connexions_Service::factory('Model_Group');
        $groups     = $service->fetchByOwners( $users,
                                               false );  // !exact
        $this->assertNotEquals(null, $groups);

        /*
        printf ("Groups: [ %s ]\n", $groups->debugDump());
        printf ("Groups: [ %s ]\n",
                print_r($groups->getIds(), true));
        printf ("Groups: [ %s ]\n", $groups);
        // */

        $this->assertEquals($expected, $groups->getIds());
    }

    public function testGroupServiceFetchByOwners3()
    {
                    // vv ordered by 'name ASC, visibility DESC, groupType DESC'
        $expected   = array(4, 2, 1, 3);
        $users      = array(1, 2, 3);
        $service    = Connexions_Service::factory('Model_Group');
        $groups     = $service->fetchByOwners( $users,
                                               false );  // !exact
        $this->assertNotEquals(null, $groups);

        /*
        printf ("Groups: [ %s ]\n", $groups->debugDump());
        printf ("Groups: [ %s ]\n",
                print_r($groups->getIds(), true));
        printf ("Groups: [ %s ]\n", $groups);
        // */

        $this->assertEquals($expected, $groups->getIds());
    }

    public function testGroupServiceFetchByOwners4()
    {
                    // vv ordered by 'name ASC, visibility DESC, groupType DESC'
        $expected   = array(4, 2, 1, 3);
        $users      = array('User1', 'User441', 'User83');
        $service    = Connexions_Service::factory('Model_Group');
        $groups     = $service->fetchByOwners( $users,
                                               false );  // !exact
        $this->assertNotEquals(null, $groups);

        /*
        printf ("Groups: [ %s ]\n", $groups->debugDump());
        printf ("Groups: [ %s ]\n",
                print_r($groups->getIds(), true));
        printf ("Groups: [ %s ]\n", $groups);
        // */

        $this->assertEquals($expected, $groups->getIds());
    }

    public function testGroupServiceFetchByOwners5()
    {
                    // vv ordered by 'userCount DESC'
        $expected   = array(4, 2, 1, 3);
        $users      = 'User1,User441, User83';
        $service    = Connexions_Service::factory('Model_Group');
        $groups     = $service->fetchByOwners( $users,
                                               false );  // !exact
        $this->assertNotEquals(null, $groups);

        /*
        printf ("Groups: [ %s ]\n", $groups->debugDump());
        printf ("Groups: [ %s ]\n",
                print_r($groups->getIds(), true));
        printf ("Groups: [ %s ]\n", $groups);
        // */

        $this->assertEquals($expected, $groups->getIds());
    }
}
