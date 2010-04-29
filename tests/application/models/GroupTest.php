<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/Group.php';

class GroupTest extends BaseTestCase
{
    public function testGroupConstructorInjectionOfProperties()
    {
        $expected   = array(
            'groupId'           => null,
            'name'              => 'Group1',
            'groupType'         => 'tag',
            'ownerId'           => null,

            'controlMembers'    => 'owner',
            'controlItems'      => 'group',
            'visibility'        => 'public',
            'canTransfer'       => null,
        );
        $data = array(
            'name'        => $expected['name'],
        );

        $group = new Model_Group( $data );

        // Make sure we can change properties
        $group->controlItems = $expected['controlItems'];
        $group->visibility   = $expected['visibility'];

        $this->assertTrue( ! $group->isBacked() );
        $this->assertTrue(   $group->isValid() );

        $this->assertEquals($expected,
                            $group->toArray(Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testGroupGetId()
    {
        $data     = array('groupId' => 1);
        $expected = $data['groupId'];

        $group = new Model_Group( $data );

        $this->assertEquals($expected, $group->getId());
    }

    public function testGroupGetMapper()
    {
        $group = new Model_Group( );

        $mapper = $group->getMapper();

        $this->assertType('Model_Mapper_Group', $mapper);
    }

    public function testGroupGetFilter()
    {
        $group = new Model_Group( );

        $filter = $group->getFilter();

        //$this->assertType('Model_Filter_Group', $filter);
        $this->assertEquals(Connexions_Model::NO_INSTANCE, $filter);
    }
}

