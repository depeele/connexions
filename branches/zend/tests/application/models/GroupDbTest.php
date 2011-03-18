<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/models/Group.php';

class GroupDbTest extends DbTestCase
{
    private $_group1 = array(
                        'groupId'        => 1,
                        'name'           => 'Tags',
                        'groupType'      => 'tag',
                        'ownerId'        => 1,

                        'controlMembers' => 'owner',
                        'controlItems'   => 'owner',
                        'visibility'     => 'private',
                        'canTransfer'    => 0,
    );
    private $_group1_items      = array(6,10,12);

    private $_group1u= array(
                        'groupId'        => 2,
                        'name'           => 'System:Network',
                        'groupType'      => 'user',
                        'ownerId'        => 1,

                        'controlMembers' => 'owner',
                        'controlItems'   => 'owner',
                        'visibility'     => 'private',
                        'canTransfer'    => 0,
    );
    private $_group1u_items     = array(1,3,4);

    private $_group1i= array(
                        'groupId'        => 3,
                        'name'           => 'Urls',
                        'groupType'      => 'item',
                        'ownerId'        => 1,

                        'controlMembers' => 'owner',
                        'controlItems'   => 'owner',
                        'visibility'     => 'group',
                        'canTransfer'    => 0,
    );
    private $_group1i_items     = array(2,3,4);

    /* In the test dataset, all groups name 'Group1' have the same list of
     * members
     */
    private $_group1_members    = array(1,4);

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

        parent::tearDown();
    }


    public function testGroupRetrieveByUnknownId()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('groupId' => 5) );

        $this->assertEquals(null, $group);
    }

    public function testGroupRetrieveById1()
    {
        $expected = $this->_group1;

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group    = $mapper->find( array('groupId' =>
                                            $this->_group1['groupId'] ));

        $this->assertNotEquals(null,  $group );
        $this->assertTrue  ( $group->isBacked() );
        $this->assertTrue  ( $group->isValid() );

        $this->assertEquals($expected,
                            $group->toArray(self::$toArray_shallow_all));
    }

    public function testGroupIdentityMap()
    {
        $mapper  = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group   = $mapper->find( array('groupId' =>
                                            $this->_group1['groupId'] ));
        $group2  = $mapper->find( array('groupId' =>
                                            $this->_group1['groupId'] ));

        $this->assertSame  ( $group, $group2 );
    }

    public function testGroupGetId()
    {
        $expected = $this->_group1['groupId'];

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group    = $mapper->find( array('groupId' => $expected ));

        $this->assertNotEquals(null,   $group );
        $this->assertEquals($expected, $group->getId());
    }

    public function testGroupRetrieveByName1()
    {
        $expected = $this->_group1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name' => $expected['name'] ));

        $this->assertNotEquals(null,   $group );
        $this->assertEquals($expected,
                            $group->toArray(self::$toArray_shallow_all));
    }

    public function testGroupOwner()
    {
        $expected = $this->_user1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name' => $this->_group1['name'] ));

        $this->assertNotEquals(null, $group );
        $this->assertNotEquals(null, $group->owner );

        /*
        printf ("\nGroup Owner: [ %s ]\n",
                Connexions::varExport(
                   $group->owner->toArray(self::$toArray_shallow_all));
        // */

        $this->assertEquals($expected,
                            $group->owner->toArray(self::$toArray_shallow_all));
    }

    public function testGroupMembers1()
    {
        /* Retrieve the members that SHOULD be part of the group identified by
         * _group1 (i.e. Users 1 and 4)
         */
        $mapper  = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $members = $mapper->fetch( array('userId' => $this->_group1_members));
        $this->assertEquals(2, $members->count() );
        $memberMin = array();
        foreach ($members as $member)
        {
            //$min = $mapper->reduceModel( $member );

            $min = $member->toArray(self::$toArray_shallow_all);
            $min['userItemCount'] = 0;
            $min['itemCount']     = 0;
            $min['tagCount']      = 0;
            
            array_push($memberMin, $min);
        }
        //$members->toArray(self::$toArray_shallow_all);


        /*
        echo "\nMinimized Members:\n";
        print_r($memberMin);
        echo "\n\n";
        // */

        $expected = $this->_group1;

        // Retrieve the target group by name
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name' => $expected['name'] ));

        $this->assertNotEquals(null,   $group );

        // Retrieve the group members
        $this->assertNotEquals(null,   $group->members );

        /*
        printf ("\nGroup Members:\n%s\n", $group->members->debugDump());
        // */

        $this->assertEquals($expected,
                            $group->toArray(self::$toArray_shallow_all));

        $expected['members'] = $memberMin;
    }

    public function testGroupMembers2()
    {
        $expected = array(1,4);

        // Retrieve the target group by name
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name' => $this->_group1['name'] ));
        $this->assertNotEquals(null,   $group );

        // Retrieve the group members
        $members = $group->getMembers( array('name ASC'));

        $this->assertNotEquals(null,   $members );

        /*
        printf ("\nGroup Members:\n%s\n", $members->debugDump());
        // */

        $this->assertEquals($expected, $members->getIds());
    }

    public function testTagGroupItems1()
    {
        /* Retrieve the items that SHOULD be part of the group identified by
         * _group1
         */
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $items  = $mapper->fetch( array('tagId' => $this->_group1_items));
        $this->assertEquals(count($this->_group1_items), $items->count() );
        $itemMin = array();
        foreach ($items as $item)
        {
            //$min = $mapper->reduceModel( $item );

            $min = $item->toArray(self::$toArray_shallow_all);
            $min['userItemCount'] = 0;
            $min['itemCount']     = 0;
            $min['tagCount']      = 0;
            
            array_push($itemMin, $min);
        }
        //$items->toArray(self::$toArray_shallow_all);


        /*
        echo "\nMinimized items:\n";
        print_r($itemMin);
        echo "\n\n";
        // */

        // Retrieve the target group by name
        $expected = $this->_group1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'      => $expected['name'],
                                       'groupType' => $expected['groupType']));

        $this->assertNotEquals(null,   $group );

        /*
        printf("\n%s Group named '%s':\n%s\n\n",
               $expected['groupType'], $expected['name'], $group->debugDump());
        // */


        // Retrieve the group items
        $this->assertNotEquals(null, $group->items );

        /*
        printf ("\nGroup items:\n%s\n", $group->items->debugDump());
        // */

        $this->assertEquals($expected,
                            $group->toArray(self::$toArray_shallow_all));

        $expected['items'] = $itemMin;
    }

    public function testTagGroupItems2()
    {
        $expected = array(10,12,6);

        // Retrieve the target group by name
        $group  = $this->_group1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'     => $group['name'],
                                      'groupType' => $group['groupType']));
        $this->assertNotEquals(null,   $group );

        // Retrieve the group items
        $items = $group->getItems( array('tag ASC'));

        $this->assertNotEquals(null,            $items );
        $this->assertEquals(   'Model_Set_Tag', get_class($items));

        /*
        printf ("\nGroup Items:\n%s\n", $items->debugDump());
        // */

        $this->assertEquals($expected, $items->getIds());
    }

    public function testTagGroupItems3()
    {
        $expected = array(6,12,10);

        // Retrieve the target group by name
        $group  = $this->_group1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'     => $group['name'],
                                      'groupType' => $group['groupType']));
        $this->assertNotEquals(null,   $group );

        // Retrieve the group items
        $items = $group->getItems( array('tag DESC'));

        $this->assertNotEquals(null,            $items );
        $this->assertEquals(   'Model_Set_Tag', get_class($items));

        /*
        printf ("\nGroup Items:\n%s\n", $items->debugDump());
        // */

        $this->assertEquals($expected, $items->getIds());
    }

    public function testUserGroupItems1()
    {
        /* Retrieve the items that SHOULD be part of the group identified by
         * _group1u
         */
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $items  = $mapper->fetch( array('userId' => $this->_group1u_items));
        $this->assertEquals(count($this->_group1u_items), $items->count() );
        $itemMin = array();
        foreach ($items as $item)
        {
            //$min = $mapper->reduceModel( $item );

            $min = $item->toArray(self::$toArray_shallow_all);
            $min['userItemCount'] = 0;
            $min['itemCount']     = 0;
            $min['tagCount']      = 0;
            
            array_push($itemMin, $min);
        }
        //$items->toArray(self::$toArray_shallow_all);


        /*
        echo "\nMinimized items:\n";
        print_r($itemMin);
        echo "\n\n";
        // */

        // Retrieve the target group by name
        $expected = $this->_group1u;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'      => $expected['name'],
                                       'groupType' => $expected['groupType']));

        $this->assertNotEquals(null,   $group );

        /*
        printf("\n%s Group named '%s':\n%s\n\n",
               $expected['groupType'], $expected['name'], $group->debugDump());
        // */


        // Retrieve the group items
        $this->assertNotEquals(null, $group->items );

        /*
        printf ("\nGroup items:\n%s\n", $group->items->debugDump());
        // */

        $this->assertEquals($expected,
                            $group->toArray(self::$toArray_shallow_all));

        $expected['items'] = $itemMin;
    }

    public function testUserGroupItems2()
    {
        $expected = array(1,4,3);

        // Retrieve the target group by name
        $group  = $this->_group1u;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'     => $group['name'],
                                      'groupType' => $group['groupType']));
        $this->assertNotEquals(null,   $group );

        // Retrieve the group items
        $items = $group->getItems( array('name ASC'));

        $this->assertNotEquals(null,             $items );
        $this->assertEquals(   'Model_Set_User', get_class($items));

        /*
        printf ("\nGroup Items:\n%s\n", $items->debugDump());
        // */

        $this->assertEquals($expected, $items->getIds());
    }

    public function testUserGroupItems3()
    {
        $expected = array(4,1,3);

        // Retrieve the target group by name
        $group  = $this->_group1u;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'     => $group['name'],
                                      'groupType' => $group['groupType']));
        $this->assertNotEquals(null,   $group );

        // Retrieve the group items
        $items = $group->getItems( array('totalTags DESC'));

        $this->assertNotEquals(null,             $items );
        $this->assertEquals(   'Model_Set_User', get_class($items));

        /*
        printf ("\nGroup Items:\n%s\n", $items->debugDump());
        // */

        $this->assertEquals($expected, $items->getIds());
    }

    public function testItemGroupItems1()
    {
        /* Retrieve the items that SHOULD be part of the group identified by
         * _group1i
         */
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Item');
        $items  = $mapper->fetch( array('itemId' => $this->_group1i_items));
        $this->assertEquals(count($this->_group1i_items), $items->count() );
        $itemMin = array();
        foreach ($items as $item)
        {
            //$min = $mapper->reduceModel( $item );

            $min = $item->toArray(self::$toArray_shallow_all);
            $min['userItemCount'] = 0;
            $min['itemCount']     = 0;
            $min['tagCount']      = 0;
            
            array_push($itemMin, $min);
        }
        //$items->toArray(self::$toArray_shallow_all);


        /*
        echo "\nMinimized items:\n";
        print_r($itemMin);
        echo "\n\n";
        // */

        // Retrieve the target group by name
        $expected = $this->_group1i;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'      => $expected['name'],
                                       'groupType' => $expected['groupType']));

        $this->assertNotEquals(null,   $group );

        /*
        printf("\n%s Group named '%s':\n%s\n\n",
               $expected['groupType'], $expected['name'], $group->debugDump());
        // */


        // Retrieve the group items
        $this->assertNotEquals(null, $group->items );

        /*
        printf ("\nGroup items:\n%s\n", $group->items->debugDump());
        // */

        $this->assertEquals($expected,
                            $group->toArray(self::$toArray_shallow_all));

        $expected['items'] = $itemMin;
    }

    public function testItemGroupItems2()
    {
        $expected = array(4,3,2);

        // Retrieve the target group by name
        $group  = $this->_group1i;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'     => $group['name'],
                                      'groupType' => $group['groupType']));
        $this->assertNotEquals(null,   $group );

        // Retrieve the group items
        $items = $group->getItems( array('url ASC'));

        $this->assertNotEquals(null,             $items );
        $this->assertEquals(   'Model_Set_Item', get_class($items));

        /*
        printf ("\nGroup Items:\n%s\n", $items->debugDump());
        // */

        $this->assertEquals($expected, $items->getIds());
    }

    public function testItemGroupItems3()
    {
        $expected = array(2,3,4);

        // Retrieve the target group by name
        $group  = $this->_group1i;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'     => $group['name'],
                                      'groupType' => $group['groupType']));
        $this->assertNotEquals(null,   $group );

        // Retrieve the group items
        $items = $group->getItems( array('userCount ASC'));

        $this->assertNotEquals(null,             $items );
        $this->assertEquals(   'Model_Set_Item', get_class($items));

        /*
        printf ("\nGroup Items:\n%s\n", $items->debugDump());
        // */

        $this->assertEquals($expected, $items->getIds());
    }

    public function testGroupInsertedIntoDatabase()
    {
        $expected = array(
            'groupId'        => 5,
            'name'           => 'Group2',
            'groupType'      => 'tag',
            'ownerId'        => 1,
            'controlMembers' => 'owner',
            'controlItems'   => 'owner',
            'visibility'     => 'private',
            'canTransfer'    => 0,
        );

        $group = new Model_Group( array(
                        'name'    => $expected['name'],
                        'ownerId' => $expected['ownerId'],
                     ));
        $group = $group->save();

        $this->assertEquals($expected,
                            $group->toArray(self::$toArray_shallow_all));
    }

    public function testTagGroupAdd1()
    {
        $expected = 'web2.0,ajax,javascript,newtag';

        // Retrieve the target group by name
        $group  = $this->_group1;
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'      => $group['name'],
                                       'groupType' => $group['groupType']));

        $this->assertNotEquals(null, $group );
        $this->assertNotEquals(null, $group->items );

        /*
        printf ("\nGroup items:\n%s\n", $group->items->debugDump());
        // */

        $tService = Connexions_Service::factory('Service_Tag');
        $tag      = $tService->get(array('tag' => 'NewTag'));


        $group->addItem($tag);
        $this->assertEquals($expected, $group->items->__toString());
    }

    public function testTagGroupAdd2()
    {
        $expected = '';

        // Retrieve the target group by name
        $group  = $this->_group1;
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'      => $group['name'],
                                       'groupType' => $group['groupType']));

        $this->assertNotEquals(null, $group );
        $this->assertNotEquals(null, $group->items );

        /*
        printf ("\nGroup items:\n%s\n", $group->items->debugDump());
        // */

        $uService = Connexions_Service::factory('Service_User');
        $user     = $uService->get(array('userId' => 1));
        $this->assertNotEquals(null, $user );


        try
        {
            $group->addItem($user);
        }
        catch (Exception $e)
        {
            $this->assertEquals("Unexpected model instance for 'tag' group",
                                $e->getMessage());
        }
    }

    public function testTagGroupRemove1()
    {
        $expected = 'web2.0,javascript';

        // Retrieve the target group by name
        $group  = $this->_group1;
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'      => $group['name'],
                                       'groupType' => $group['groupType']));

        $this->assertNotEquals(null, $group );
        $this->assertNotEquals(null, $group->items );

        /*
        printf ("\nGroup items:\n%s\n", $group->items->debugDump());
        // */

        $tService = Connexions_Service::factory('Service_Tag');
        $tag      = $tService->get(array('tag' => 'ajax'));

        $group->removeItem($tag);
        $this->assertEquals($expected, $group->items->__toString());
    }

    public function testTagGroupRemove2()
    {
        $expected = '';

        // Retrieve the target group by name
        $group  = $this->_group1;
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'      => $group['name'],
                                       'groupType' => $group['groupType']));

        $this->assertNotEquals(null, $group );
        $this->assertNotEquals(null, $group->items );

        /*
        printf ("\nGroup items:\n%s\n", $group->items->debugDump());
        // */

        $uService = Connexions_Service::factory('Service_User');
        $user     = $uService->get(array('userId' => 1));
        $this->assertNotEquals(null, $user );

        try
        {
            $group->removeItem($user);
        }
        catch (Exception $e)
        {
            $this->assertEquals("Unexpected model instance for 'tag' group",
                                $e->getMessage());
        }
    }

    public function testTagGroupRemove3()
    {
        // Retrieve the target group by name
        $group  = $this->_group1;
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name'      => $group['name'],
                                       'groupType' => $group['groupType']));

        $this->assertNotEquals(null, $group );
        $this->assertNotEquals(null, $group->items );

        /*
        printf ("\nGroup items:\n%s\n", $group->items->debugDump());
        // */

        $tService = Connexions_Service::factory('Service_Tag');
        $tag      = $tService->get(array('tag' => 'no matching tag'));

        try
        {
            $group->removeItem($tag);
        }
        catch (Exception $e)
        {
            $this->assertEquals("Non-backed item cannot be removed",
                                $e->getMessage());
        }
    }
}
