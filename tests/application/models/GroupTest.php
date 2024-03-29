<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/Group.php';

/**
 *  @group Models
 */
class GroupTest extends BaseTestCase
{
    public function testGroupConstructorInjectionOfProperties()
    {
        $expected   = array(
            'groupId'           => 0,
            'name'              => 'Group1',
            'groupType'         => 'tag',
            'ownerId'           => 0,

            'controlMembers'    => 'owner',
            'controlItems'      => 'group',
            'visibility'        => 'public',
            'canTransfer'       => 0,
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
                            $group->toArray(self::$toArray_shallow_all));
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

        $this->assertInstanceOf('Model_Mapper_Group', $mapper);
    }

    public function testGroupGetFilter()
    {
        $group = new Model_Group( );

        $filter = $group->getFilter();

        $this->assertInstanceOf('Model_Filter_Group', $filter);
    }
}

