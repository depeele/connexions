<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/User.php';

class UserTest extends BaseTestCase
{
    protected   $_user1 = array(
            'userId'        => null,
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


        // apiKey is dynamically generated if not set
        if ( empty($expected['apiKey']))
        {
            $expected['apiKey'] = $user->apiKey;
        }

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
        $this->assertTrue( ! $user->isValid() );
        $this->assertTrue( ! $user->isAuthenticated() );

        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
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

        // apiKey is dynamically generated if not set
        if ( empty($expected['apiKey']))
        {
            $expected['apiKey'] = $user->apiKey;
        }


        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
        $this->assertEquals($expected2,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_PUBLIC ));
    }

    public function testUserGetId()
    {
        $data     = array('userId'  => 1);
        $expected = null;   //$data['userId'];

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
}
