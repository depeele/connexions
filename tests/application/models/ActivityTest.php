<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/Activity.php';

class ActivityTest extends BaseTestCase
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
            'activityId'    => null,
            'userId'        => 1,
            'objectType'    => 'bookmark',
            'objectId'      => '1:1',
            'operation'     => 'save',
            'time'          => null,
            'properties'    => '{"userId":1,"itemId":1,"name":"New Bookmark","description":"This is a new bookmark","rating":0,"isFavorite":1,"isPrivate":1,"taggedOn":"2010-07-07 12:00:00","updatedOn":"2011-04-12 15:21:08"}'
    );

    public function testActivityConstructorInjectionOfProperties()
    {
        $expected                = $this->_activity1;

        $item = new Model_Activity( array(
            'userId'     => $expected['userId'],
            'objectType' => $expected['objectType'],
            'objectId'   => $expected['objectId'],
            'operation'  => $expected['operation'],
        ));

        // Make sure we can change properties
        $item->properties  = $expected['properties'];

        $this->assertTrue( ! $item->isBacked() );
        $this->assertTrue(   $item->isValid() );

        $this->assertEquals($expected,
                            $item->toArray(self::$toArray_shallow_all));
    }

    public function testActivityGetId()
    {
        $data     = array('activityId' => 1);
        $expected = $data['activityId'];

        $activity = new Model_Activity( $data );

        $this->assertEquals($expected, $activity->getId());
    }

    public function testActivityGetMapper()
    {
        $item = new Model_Activity( );

        $mapper = $item->getMapper();

        $this->assertType('Model_Mapper_Activity', $mapper);
    }
}
