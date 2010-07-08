<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/User.php';

class UserTest extends BaseTestCase
{
    protected   $_user1 = array(
            'userId'        => 0,
            'name'          => 'test_user',
            'fullName'      => 'Test User',
            'email'         => 'test.user@gmail.com',
            'apiKey'        => null,
            'pictureUrl'    => 'http://gravatar.com/avatar/%md5%.jpg',
            'profile'       => 'https://google.com/profile/test.user@gmail.com',
            'lastVisit'     => null,

            'totalTags'     => 0,
            'totalItems'    => 0,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );

    public function testUserConstructorInjectionOfProperties()
    {
        $expected   = $this->_user1;
        $expected['pictureUrl'] = preg_replace('/%md5%/',
                                               md5($expected['email']),
                                               $expected['pictureUrl']);

        $user = new Model_User( array(
                            // vvv test stripTags and trim filters
            'name'        => '<a href="test.com">'. $expected['name'] .'  </a>',
            'fullName'    => '<b>  '. $expected['fullName'],
        ));


        // Make sure we can change properties
        $user->email      = $expected['email'];
        $user->pictureUrl = $expected['pictureUrl'];
        $user->profile    = $expected['profile'];

        /*
        Connexions::log("UserTest:testNewUser(): is%s authenticated; "
                        .   "user[ %s ]",
                        ($user->isAuthenticated() ? '' : ' NOT'),
                        $user->debugDump());
        // */

        $this->assertTrue( ! $user->isBacked() );
        $this->assertTrue(   $user->isValid() );
        $this->assertTrue( ! $user->isAuthenticated() );

        // apiKey and lastVisit are dynamically generated
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $user->lastVisit;

        $this->assertEquals($user->getValidationMessages(), array() );
        $this->assertEquals($expected,
                            $user->toArray(self::$toArray_shallow_all));
    }

    public function testUserToArray()
    {
        $expected  = $this->_user1;
        $expected2 = $this->_user1;

        unset($expected2['apiKey']);

        $user = new Model_User( array(
            'name'        => $expected['name'],
            'fullName'    => $expected['fullName'],
            'email'       => $expected['email'],
            'pictureUrl'  => $expected['pictureUrl'],
            'profile'     => $expected['profile'],
        ));

        // apiKey and lastVisit are dynamically generated
        $expected['apiKey']    = $user->apiKey;
        $expected['lastVisit'] = $expected2['lastVisit'] = $user->lastVisit;

        $this->assertEquals($user->getValidationMessages(), array() );
        $this->assertEquals($expected,
                            $user->toArray(self::$toArray_shallow_all));
        $this->assertEquals($expected2,
                            $user->toArray( ));
    }

    public function testUserGetId()
    {
        $data     = array('userId'  => 1);
        $expected = $data['userId'];

        $user = new Model_User( $data );

        $this->assertEquals($expected, $user->getId());
    }

    public function testUserGetMapper()
    {
        $user = new Model_User( );

        $mapper = $user->getMapper();

        $this->assertType('Model_Mapper_User', $mapper);
    }

    public function testUserGetFilter()
    {
        $user = new Model_User( );

        $filter = $user->getFilter();

        $this->assertType('Model_Filter_User', $filter);
    }

    /*************************************************************************
     * Data validation tests
     *
     */
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

        $user  = new Model_User($data);

        $this->assertFalse( $user->isValid()  );

        /*
        echo "Validation Messages:\n";
        print_r($user->getValidationMessages());
        printf ("User: [ %s ]\n", $user->debugDump());
        // */

        $this->assertArrayHasKey('name', $user->getValidationMessages());
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

        $user  = new Model_User($data);

        $this->assertFalse( $user->isValid()  );

        /*
        echo "Validation Messages:\n";
        print_r($user->getValidationMessages());
        printf ("User: [ %s ]\n", $user->debugDump());
        // */

        $this->assertArrayHasKey('name', $user->getValidationMessages());
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
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
        );
        $expected                  = $data;
        $expected['fullName']      = trim(preg_replace('/\s+/', ' ',
                                            strip_tags($expected['fullName'])));

        $user  = new Model_User($data);

        $this->assertTrue ( $user->isValid()  );

        $this->assertEquals($user->getValidationMessages(), array());
        $this->assertEquals($expected,
                            $user->toArray(self::$toArray_shallow_all));
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
        $expected                  = $data;
        $expected['fullName']      = trim(preg_replace('/\s+/', ' ',
                                            strip_tags($expected['fullName'])));
        $expected['userItemCount'] = 0;
        $expected['itemCount']     = 0;
        $expected['tagCount']      = 0;

        $user  = new Model_User($data);

        $this->assertFalse( $user->isValid()  );

        /*
        echo "Validation Messages:\n";
        print_r($user->getValidationMessages());
        printf ("User: [ %s ]\n", $user->debugDump());
        // */

        $this->assertArrayHasKey('fullName', $user->getValidationMessages());
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

        $user  = new Model_User($data);

        $this->assertTrue ( $user->isValid()  );
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

        $user  = new Model_User($data);

        $this->assertFalse( $user->isValid()  );

        /*
        echo "Validation Messages:\n";
        print_r($user->getValidationMessages());
        printf ("User: [ %s ]\n", $user->debugDump());
        // */

        $this->assertArrayHasKey('email', $user->getValidationMessages());
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

        $user  = new Model_User($data);

        /*
        echo "Validation Messages:\n";
        print_r($user->getValidationMessages());
        printf ("User: [ %s ]\n", $user->debugDump());
        // */

        $this->assertFalse( $user->isValid()  );

        /*
        echo "Validation Messages:\n";
        print_r($user->getValidationMessages());
        printf ("User: [ %s ]\n", $user->debugDump());
        // */

        $this->assertArrayHasKey('apiKey', $user->getValidationMessages());
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

        $user  = new Model_User($data);

        $this->assertFalse( $user->isValid()  );

        /*
        echo "Validation Messages:\n";
        print_r($user->getValidationMessages());
        printf ("User: [ %s ]\n", $user->debugDump());
        // */

        $this->assertArrayHasKey('apiKey', $user->getValidationMessages());
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

        $user  = new Model_User($data);

        $this->assertFalse( $user->isValid()  );

        /*
        echo "Validation Messages:\n";
        print_r($user->getValidationMessages());
        printf ("User: [ %s ]\n", $user->debugDump());
        // */

        $this->assertArrayHasKey('apiKey', $user->getValidationMessages());
    }
}
