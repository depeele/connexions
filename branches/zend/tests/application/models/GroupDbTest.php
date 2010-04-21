<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/models/Group.php';

class GroupDbTest extends DbTestCase
{
    private $_group1 = array(
                        'groupId'        => 1,
                        'name'           => 'Group1',
                        'groupType'      => 'tag',
                        'controlMembers' => 'owner',
                        'controlItems'   => 'owner',
                        'visibility'     => 'private',
                        'canTransfer'    => 0,
                        'owner'          => 1,
                        'items'          => null,
                        'members'        => null,
    );
    private $_user1 = array(
                        'userId'        => 1,
                        'name'          => 'User1',
                        'fullName'      => 'Random User 1',
                        'email'         => 'User1@home',
                        'apiKey'        => null,
                        'pictureUrl'    => '/connexions/images/User1.png',
                        'profile'       => null,
                        'lastVisit'     => '2007-04-12 12:38:02',
                        'totalTags'     => 24,
                        'totalItems'    => 5,

                        'userItemCount' => null,
                        'userCount'     => null,
                        'itemCount'     => null,
                        'tagCount'      => null,
    );

    protected function getDataSet()
    {
        return $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/5users+groups.xml');
    }

    public function testGroupRetrieveByUnknownId()
    {
        $mapper = new Model_Mapper_Group( );
        $group  = $mapper->find( 5 );

        $this->assertEquals(null, $group);
    }

    public function testGroupRetrieveById1()
    {
        $expected = $this->_group1;

        $mapper   = new Model_Mapper_Group( );
        $group    = $mapper->find( $this->_group1['groupId'] );

        $this->assertNotEquals(null,  $group );
        $this->assertTrue  ( $group->isBacked() );
        $this->assertTrue  ( $group->isValid() );

        $this->assertEquals($expected,
                            $group->toArray( Connexions_Model::DEPTH_SHALLOW,
                                             Connexions_Model::FIELDS_ALL ));
    }

    public function testGroupIdentityMap()
    {
        $mapper = new Model_Mapper_Group( );
        $group   = $mapper->find( $this->_group1['groupId'] );
        $group2  = $mapper->find( $this->_group1['groupId'] );

        $this->assertSame  ( $group, $group2 );
    }

    public function testGetId()
    {
        $expected = $this->_group1['groupId'];

        $mapper   = new Model_Mapper_Group( );
        $group    = $mapper->find( $expected );

        $this->assertNotEquals(null,   $group );
        $this->assertEquals($expected, $group->getId());
    }

    public function testGroupRetrieveByName1()
    {
        $expected = $this->_group1;

        $mapper = new Model_Mapper_Group( );
        $group  = $mapper->find( $expected['name'] );

        $this->assertNotEquals(null,   $group );
        $this->assertEquals($expected,
                            $group->toArray( Connexions_Model::DEPTH_SHALLOW,
                                             Connexions_Model::FIELDS_ALL ));
    }

    public function testGroupOwner()
    {
        $expected = $this->_user1;

        $mapper = new Model_Mapper_Group( );
        $group  = $mapper->find( $this->_group1['name'] );

        $this->assertNotEquals(null, $group );
        $this->assertNotEquals(null, $group->owner );

        /*
        printf ("\nGroup Owner: [ %s ]\n",
                Connexions::varExport(
                   $group->owner->toArray( Connexions_Model::DEPTH_SHALLOW,
                                           Connexions_Model::FIELDS_ALL )) );
        // */

        $this->assertEquals($expected,
                            $group->owner->toArray(
                                        Connexions_Model::DEPTH_SHALLOW,
                                        Connexions_Model::FIELDS_ALL ));
    }

    public function testGroupMembers()
    {
        $members =
            Connexions_Model_Mapper::factory('Model_Mapper_User')
                                    ->fetch( array('userId' => array(1,4)));
        $this->assertEquals(2, $members->count() );
        $memberMin = array();
        foreach ($members as $member)
        {
            $min = $member->toArray(Connexions_Model::DEPTH_SHALLOW,
                                    Connexions_Model::FIELDS_ALL );
            $min['userItemCount'] = null;
            $min['userCount']     = null;
            $min['itemCount']     = null;
            $min['tagCount']      = null;

            array_push($memberMin, $min);
        }
        //$members->toArray( Connexions_Model::DEPTH_SHALLOW,
        //                                Connexions_Model::FIELDS_ALL );

        $expected = $this->_group1;
        $expected['members'] = $memberMin;

        $mapper = new Model_Mapper_Group( );
        $group  = $mapper->find( $expected['name'] );

        $this->assertNotEquals(null,   $group );

        $memberCount = $group->members;
        $this->assertNotEquals(null,   $group->members );

        // /*
        echo "\nGroup Members:\n";
        print_r($group->members->toArray( Connexions_Model::DEPTH_SHALLOW,
                                          Connexions_Model::FIELDS_ALL ) );
        // */

        // /*
        $this->assertEquals($expected,
                            $group->toArray( Connexions_Model::DEPTH_SHALLOW,
                                             Connexions_Model::FIELDS_ALL ));
        // */
    }


    public function testGroupInsertedIntoDatabase()
    {
        $expected = array(
            'groupId'        => 2,
            'name'           => 'Group2',
            'groupType'      => 'tag',
            'controlMembers' => 'owner',
            'controlItems'   => 'owner',
            'visibility'     => 'private',
            'canTransfer'    => 0,
            'owner'          => 1,
            'items'          => null,
            'members'        => null,
        );

        $group = new Model_Group( array(
                        'name'  => $expected['name'],
                        'owner' => $expected['owner'],
                     ));
        $group = $group->save();

        $this->assertEquals($expected,
                            $group->toArray( Connexions_Model::DEPTH_SHALLOW,
                                             Connexions_Model::FIELDS_ALL ));
    }
}

