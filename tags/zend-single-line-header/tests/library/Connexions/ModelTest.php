<?php
require_once TESTS_PATH .'/library/LibraryTestCase.php';
require_once LIBRARY_PATH .'/Connexions/Model.php';

// Simple Test Model
class Model_UnitTest extends Connexions_Model
{
    protected   $_data  = array(
        'unitId'    => null,
        'name'      => ''
    );

    public function getId()
    {
        return ($this->unitId);
    }
}

class ModelTest extends LibraryTestCase
{
    public function testCannotSetInitialPropertiesUnlessDefined()
    {
        $data = array('name'        => 'test',
                      'notDefined'  => 1);

        try
        {
            $unit = new Model_UnitTest( $data );

            $this->fail('Initializing with a property not defined in the '
                        . 'Domain Model should raise an Exception');
        }
        catch (Exception $e)
        {
        }
    }

    public function testIssetStatusOfProperties()
    {
        $unit = new Model_UnitTest();
        $unit->name = 'My Name';
        $this->assertTrue(isset($unit->name));
    }

    public function testCanUnsetProperties()
    {
        $unit = new Model_UnitTest();
        $unit->name = 'My Name';
        unset($unit->name);

        $this->assertFalse(isset($unit->name));
    }

    public function testCannotSetNewPropertiesUnlessDefined()
    {
        $unit = new Model_UnitTest();

        try
        {
            $unit->notDefined = 1;
            $this->fail('Setting new property not defined in the '
                        . 'Domain Model should raise an Exception');
        }
        catch (Exception $e)
        {
        }
    }

    public function testInitialState()
    {
        $expected = array(
            'unitId'    => null,
            'name'      => null,
        );

        $unit = new Model_UnitTest( );  //$expected );

        $this->assertEquals($expected, $unit->toArray());
        $this->assertFalse( $unit->isBacked() );
        $this->assertFalse( $unit->isValid() );
    }

    public function testBasicSetters()
    {
        $unit = new Model_UnitTest( );

        $this->assertFalse( $unit->isBacked() );
        $unit->setIsBacked();
        $this->assertTrue( $unit->isBacked() );
        $unit->setIsBacked(false);
        $this->assertFalse( $unit->isBacked() );

        $this->assertFalse( $unit->isValid() );
        $unit->setIsValid();
        $this->assertTrue( $unit->isValid() );
        $unit->setIsValid(false);
        $this->assertFalse( $unit->isValid() );
    }

    public function testGetOfUnsetProperty()
    {
        $expected = null;

        $unit     = new Model_UnitTest();

        $this->assertEquals($expected, $unit->notDefined);
    }

    public function testGetId()
    {
        $expected = 5;

        $unit = new Model_UnitTest();
        $unit->unitId = $expected;

        $this->assertEquals($expected, $unit->getId());
        $this->assertEquals($expected, $unit->unitId);
    }

    public function testToString()
    {
        $id       = 5;
        $expected = "{$id}";

        $unit = new Model_UnitTest();
        $unit->unitId = $id;

        //$this->assertEquals($expected, ''. $unit);
        $this->assertSame($expected, (string)$unit);
    }

    public function testToArray()
    {
        $expected = array(
            'unitId'    => null,
            'name'      => 'Unit Name',
        );

        $unit = new Model_UnitTest( $expected );

        $this->assertEquals($expected, $unit->toArray());
    }

    public function testInvalidate()
    {
        $expected = array(
            'unitId'    => null,
            'name'      => null,
        );

        $unit = new Model_UnitTest( array('unitId' => 1, 'name' => 'Name') );

        $unit->invalidate();

        $this->assertEquals($expected, $unit->toArray());
    }

    public function testGetMapperWithNoMapperClass()
    {
        $unit = new Model_UnitTest( );

        $mapper = $unit->getMapper();

        $this->assertEquals(Connexions_Model_Mapper::NO_INSTANCE, $mapper);
    }

    public function testGetFilterWithNoFilterClass()
    {
        $unit = new Model_UnitTest( );

        $filter = $unit->getFilter();

        $this->assertEquals(Connexions_Model::NO_INSTANCE, $filter);
    }
}
