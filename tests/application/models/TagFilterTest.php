<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/Tag.php';

class TagFilterTest extends BaseTestCase
{
    protected function _getParsed($filter)
    {
        $data = array(
            'tagId'       => $filter->getUnescaped('tagId'),
            'tag'         => $filter->getUnescaped('tag'),
        );

        return $data;
    }

    protected function _outputInfo($filter, $data)
    {
        $errors  = $filter->getErrors();
        echo "Errors:\n";
        print_r($errors);

        $invalid = $filter->getInvalid();
        echo "Invalids:\n";
        print_r($invalid);

        $missing = $filter->getMissing();
        echo "Missing:\n";
        print_r($missing);

        $unknown = $filter->getUnknown();
        echo "Unknowns:\n";
        print_r($unknown);

        $parsed = $this->_getParsed($filter);
        echo "Parsed Data:\n";
        print_r($parsed);

        echo "------------------------------------------------------\n";
    }

    public function testTagFilter1()
    {
        $data       = array(
            'tagId'         => 1,
            'tag'           => 'Test Tag #1',
        );
        $expected        = $data;
        $expected['tag'] = strtolower(
                                preg_replace('/\s+/', ' ',
                                    trim(strip_tags($expected['tag'])) ));

        $filter  = new Model_Filter_Tag($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertTrue ( $filter->isValid()  );

        $this->assertEquals($expected, $this->_getParsed($filter));

        //$this->_outputInfo($filter, $data);
    }

    public function testTagFilter2()
    {
        $data       = array(
            'tagId'         => 1,
            'tag'           => '<b>Test   Tag</b>	   #1',
        );
        $expected        = $data;
        $expected['tag'] = strtolower(
                                preg_replace('/\s+/', ' ',
                                    trim(strip_tags($expected['tag'])) ));

        $filter  = new Model_Filter_Tag($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertTrue ( $filter->isValid()  );

        $this->assertEquals($expected, $this->_getParsed($filter));

        //$this->_outputInfo($filter, $data);
    }

    public function testTagFilter3()
    {
        $data       = array(
            'tagId'         => 1,
            'tag'           => '<b>Test   Tag</b>	   #1,2,3"\'`\\',
        );
        $expected        = $data;
        $expected['tag'] = strtolower(
                                preg_replace('/[,"\'`\\\\]/', '',
                                    preg_replace('/\s+/', ' ',
                                        trim(strip_tags($expected['tag'])) )));

        $filter  = new Model_Filter_Tag($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertTrue ( $filter->isValid()  );

        $this->assertEquals($expected, $this->_getParsed($filter));

        //$this->_outputInfo($filter, $data);
    }


    public function testTagFilterMissingTagId()
    {
        $data       = array(
            // Missing tagId
            'tag'           => 'Test Tag #1',
        );
        $filter  = new Model_Filter_Tag($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertTrue ( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        //$this->_outputInfo($filter, $data);
    }

    public function testTagFilterNameTooShort()
    {
        $data       = array(
            'tagId'         => 1,
            'tag'           => 'T',
        );
        $filter  = new Model_Filter_Tag($data);

        $this->assertTrue ( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        //$this->_outputInfo($filter, $data);
    }

    public function testTagFilterNameTooLong()
    {
        $data       = array(
            'tagId'         => 1,
            'tag'           => 'Test Tag-'. str_repeat('.', 30),
        );
        $expected        = $data;
        $expected['tag'] = strtolower( substr($expected['tag'],0,30) );

        $filter  = new Model_Filter_Tag($data);

        /*
        $this->assertTrue ( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        //$this->_outputInfo($filter, $data);
        // */

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertTrue ( $filter->isValid()  );

        $this->assertEquals($expected, $this->_getParsed($filter));

        //$this->_outputInfo($filter, $data);
    }

    public function testTagFilterNormalize1()
    {
        $data       = array(
            'tagId'         => 1,
            'tag'           => "Tag\nToo\r&nbsp;Long123456789012345678 &nbsp; with newlines and entities",
        );
        $expected        = $data;
        $expected['tag'] = 'tag too long123456789012345678';

        $filter  = new Model_Filter_Tag($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertTrue ( $filter->isValid()  );

        $this->assertEquals($expected, $this->_getParsed($filter));

        //$this->_outputInfo($filter, $data);
    }

    public function testTagFilterNormalize2()
    {
        $data       = array(
            'tagId'         => 1,
            'tag'           => 'Tag1&nbsp;&shy;',
        );
        $expected        = $data;
        $expected['tag'] = 'tag1';

        $filter  = new Model_Filter_Tag($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertTrue ( $filter->isValid()  );

        $this->assertEquals($expected, $this->_getParsed($filter));

        //$this->_outputInfo($filter, $data);
    }
}
