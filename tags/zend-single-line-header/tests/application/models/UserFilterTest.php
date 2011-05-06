<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/User.php';

class UserFilterTest extends BaseTestCase
{
    protected function _getParsed($filter)
    {
        $data = array(
            'userId'        => $filter->getUnescaped('userId'),
            'name'          => $filter->getUnescaped('name'),
            'fullName'      => $filter->getUnescaped('fullName'),
            'email'         => $filter->getUnescaped('email'),
            'apiKey'        => $filter->getUnescaped('apiKey'),
            'pictureUrl'    => $filter->getUnescaped('pictureUrl'),
            'profile'       => $filter->getUnescaped('profile'),
            'lastVisit'     => $filter->getUnescaped('lastVisit'),
            'totalTags'     => $filter->getUnescaped('totalTags'),
            'totalItems'    => $filter->getUnescaped('totalItems'),
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

    public function testUserFilter1()
    {
        $data       = array(
            'userId'        => 1,
            'name'          => 'test_user',
            'fullName'      => 'Test User',
            'email'         => 'test.user@gmail.com',
            'apiKey'        => '1234567890',
            'pictureUrl'    => 'http://gravatar.com/avatar/12345.jpg',
            'profile'       => 'https://google.com/profile/test.user@gmail.com',
            'lastVisit'     => date('Y.m.d H:i:s'),

            'totalTags'     => 0,
            'totalItems'    => 0,
        );
        $expected = $data;

        $filter  = new Model_Filter_User($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertTrue ( $filter->isValid()  );

        $this->assertEquals($expected, $this->_getParsed($filter));

        //$this->_outputInfo($filter, $data);
    }

    public function testUserFilter2()
    {
        $data       = array(
            'userId'        => 1,
            'name'          => 'test_user',
            'fullName'      => '<b>Test</b>   <i>User<i>',
            'email'         => 'test.user@gmail.com',
            'apiKey'        => '1234567890',
            'pictureUrl'    => 'http://gravatar.com/avatar/12345.jpg',
            'profile'       => 'https://google.com/profile/test.user@gmail.com',
            'lastVisit'     => date('Y.m.d H:i:s'),

            'totalTags'     => 0,
            'totalItems'    => 0,
        );
        $expected             = $data;
        $expected['fullName'] = trim(
                                    preg_replace('/\s+/', ' ',
                                        strip_tags($expected['fullName'])));

        $filter  = new Model_Filter_User($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertTrue ( $filter->isValid()  );

        $this->assertEquals($expected, $this->_getParsed($filter));

        //$this->_outputInfo($filter, $data);
    }

    public function testUserFilterMissingUserId()
    {
        $data       = array(
            //'userId'        => 1,
            'name'          => 'test_user',
            'fullName'      => '<b>Test</b>   <i>User<i>',
            'email'         => 'test.user@gmail.com',
            'apiKey'        => '1234567890',
            'pictureUrl'    => 'http://gravatar.com/avatar/12345.jpg',
            'profile'       => 'https://google.com/profile/test.user@gmail.com',
            'lastVisit'     => date('Y.m.d H:i:s'),

            'totalTags'     => 0,
            'totalItems'    => 0,
        );

        $filter  = new Model_Filter_User($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertTrue ( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        $this->assertArrayHasKey('userId', $filter->getMissing());

        //$this->_outputInfo($filter, $data);
    }

    public function testUserFilterTooShortName()
    {
        $data       = array(
            'userId'        => 1,
            'name'          => 'T',
            'fullName'      => '<b>Test</b>   <i>User<i>',
            'email'         => 'test.user@gmail.com',
            'apiKey'        => '1234567890',
            'pictureUrl'    => 'http://gravatar.com/avatar/12345.jpg',
            'profile'       => 'https://google.com/profile/test.user@gmail.com',
            'lastVisit'     => date('Y.m.d H:i:s'),

            'totalTags'     => 0,
            'totalItems'    => 0,
        );

        $filter  = new Model_Filter_User($data);

        $this->assertTrue ( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        $this->assertArrayHasKey('name', $filter->getInvalid());

        //$this->_outputInfo($filter, $data);
    }

    public function testUserFilterTooLongName()
    {
        $data       = array(
            'userId'        => 1,
            'name'          => 'test_user'. str_repeat('_', 30),
            'fullName'      => '<b>Test</b>   <i>User<i>',
            'email'         => 'test.user@gmail.com',
            'apiKey'        => '1234567890',
            'pictureUrl'    => 'http://gravatar.com/avatar/12345.jpg',
            'profile'       => 'https://google.com/profile/test.user@gmail.com',
            'lastVisit'     => date('Y.m.d H:i:s'),

            'totalTags'     => 0,
            'totalItems'    => 0,
        );

        $filter  = new Model_Filter_User($data);

        $this->assertTrue ( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        $this->assertArrayHasKey('name', $filter->getInvalid());

        //$this->_outputInfo($filter, $data);
    }

    public function testUserFilterEmptyFullName()
    {
        $data       = array(
            'userId'        => 1,
            'name'          => 'test_user',
            'fullName'      => null,
            'email'         => 'test.user@gmail.com',
            'apiKey'        => '1234567890',
            'pictureUrl'    => 'http://gravatar.com/avatar/12345.jpg',
            'profile'       => 'https://google.com/profile/test.user@gmail.com',
            'lastVisit'     => date('Y.m.d H:i:s'),

            'totalTags'     => 0,
            'totalItems'    => 0,
        );

        $filter  = new Model_Filter_User($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertTrue ( $filter->isValid()  );

        //$this->_outputInfo($filter, $data);
    }

    public function testUserFilterTooLongFullName()
    {
        $data       = array(
            'userId'        => 1,
            'name'          => 'test_user',
            'fullName'      => '<b>Test</b>   <i>User<i>'. str_repeat('.', 255),
            'email'         => 'test.user@gmail.com',
            'apiKey'        => '1234567890',
            'pictureUrl'    => 'http://gravatar.com/avatar/12345.jpg',
            'profile'       => 'https://google.com/profile/test.user@gmail.com',
            'lastVisit'     => date('Y.m.d H:i:s'),

            'totalTags'     => 0,
            'totalItems'    => 0,
        );

        $filter  = new Model_Filter_User($data);

        $this->assertTrue ( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        $this->assertArrayHasKey('fullName', $filter->getInvalid());

        //$this->_outputInfo($filter, $data);
    }

    public function testUserFilterEmptyEmail()
    {
        $data       = array(
            'userId'        => 1,
            'name'          => 'test_user',
            'fullName'      => '<b>Test</b>   <i>User<i>',
            'email'         => null,
            'apiKey'        => '1234567890',
            'pictureUrl'    => 'http://gravatar.com/avatar/12345.jpg',
            'profile'       => 'https://google.com/profile/test.user@gmail.com',
            'lastVisit'     => date('Y.m.d H:i:s'),

            'totalTags'     => 0,
            'totalItems'    => 0,
        );

        $filter  = new Model_Filter_User($data);

        $this->assertFalse( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertTrue ( $filter->isValid()  );

        //$this->_outputInfo($filter, $data);
    }

    public function testUserFilterInvalidEmail()
    {
        $data       = array(
            'userId'        => 1,
            'name'          => 'test_user',
            'fullName'      => '<b>Test</b>   <i>User<i>',
            'email'         => 'test.user@gmail.com-1234+52:http://abc.com/',
            'apiKey'        => '1234567890',
            'pictureUrl'    => 'http://gravatar.com/avatar/12345.jpg',
            'profile'       => 'https://google.com/profile/test.user@gmail.com',
            'lastVisit'     => date('Y.m.d H:i:s'),

            'totalTags'     => 0,
            'totalItems'    => 0,
        );

        $filter  = new Model_Filter_User($data);

        $this->assertTrue ( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        $this->assertArrayHasKey('email', $filter->getInvalid());

        //$this->_outputInfo($filter, $data);
    }

    public function testUserFilterTooShortApiKey()
    {
        $data       = array(
            'userId'        => 1,
            'name'          => 'test_user',
            'fullName'      => '<b>Test</b>   <i>User<i>',
            'email'         => 'test.user@gmail.com',
            'apiKey'        => '12345',
            'pictureUrl'    => 'http://gravatar.com/avatar/12345.jpg',
            'profile'       => 'https://google.com/profile/test.user@gmail.com',
            'lastVisit'     => date('Y.m.d H:i:s'),

            'totalTags'     => 0,
            'totalItems'    => 0,
        );

        $filter  = new Model_Filter_User($data);

        $this->assertTrue ( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        $this->assertArrayHasKey('apiKey', $filter->getInvalid());

        //$this->_outputInfo($filter, $data);
    }

    public function testUserFilterTooLongApiKey()
    {
        $data       = array(
            'userId'        => 1,
            'name'          => 'test_user',
            'fullName'      => '<b>Test</b>   <i>User<i>',
            'email'         => 'test.user@gmail.com',
            'apiKey'        => '1234567890bcdef',
            'pictureUrl'    => 'http://gravatar.com/avatar/12345.jpg',
            'profile'       => 'https://google.com/profile/test.user@gmail.com',
            'lastVisit'     => date('Y.m.d H:i:s'),

            'totalTags'     => 0,
            'totalItems'    => 0,
        );

        $filter  = new Model_Filter_User($data);

        $this->assertTrue ( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        $this->assertArrayHasKey('apiKey', $filter->getInvalid());

        //$this->_outputInfo($filter, $data);
    }

    public function testUserFilterInvalidApiKey()
    {
        $data       = array(
            'userId'        => 1,
            'name'          => 'test_user',
            'fullName'      => '<b>Test</b>   <i>User<i>',
            'email'         => 'test.user@gmail.com',
            'apiKey'        => 'abc[efg]ij',
            'pictureUrl'    => 'http://gravatar.com/avatar/12345.jpg',
            'profile'       => 'https://google.com/profile/test.user@gmail.com',
            'lastVisit'     => date('Y.m.d H:i:s'),

            'totalTags'     => 0,
            'totalItems'    => 0,
        );

        $filter  = new Model_Filter_User($data);

        $this->assertTrue ( $filter->hasInvalid() );
        $this->assertFalse( $filter->hasMissing() );
        $this->assertFalse( $filter->hasUnknown() );

        $this->assertTrue ( $filter->hasValid() );
        $this->assertFalse( $filter->isValid()  );

        $this->assertArrayHasKey('apiKey', $filter->getInvalid());

        //$this->_outputInfo($filter, $data);
    }
}
