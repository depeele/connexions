<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/models/Group.php';

class GroupDbTest extends DbTestCase
{
    private $_group1 = array(
                        'groupId'        => 1,
                        'name'           => 'Group1',
                        'groupType'      => 'tag',
                        'ownerId'        => 1,

                        'controlMembers' => 'owner',
                        'controlItems'   => 'owner',
                        'visibility'     => 'private',
                        'canTransfer'    => 0,
    );
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

    public function testGroupMembers()
    {
        $userMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $members    = $userMapper->fetch( array('userId' => array(1,4)));
        $this->assertEquals(2, $members->count() );
        $memberMin = array();
        foreach ($members as $member)
        {
            //$min = $userMapper->reduceModel( $member );

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

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Group');
        $group  = $mapper->find( array('name' => $expected['name'] ));

        $this->assertNotEquals(null,   $group );

        $memberCount = $group->members;
        $this->assertNotEquals(null,   $group->members );

        /*
        echo "\nGroup Members:\n";
        print_r($group->members->toArray(self::$toArray_shallow_all));
        // */

        $this->assertEquals($expected,
                            $group->toArray(self::$toArray_shallow_all));

        $expected['members'] = $memberMin;
    }

    public function testGroupInsertedIntoDatabase()
    {
        $expected = array(
            'groupId'        => 2,
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
}

