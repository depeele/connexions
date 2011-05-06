<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';
require_once APPLICATION_PATH .'/models/UserAuth.php';

class UserAuthTest extends BaseTestCase
{
    public function testUserAuthConstructorInjectionOfProperties()
    {
        $expected = array(
            'userAuthId'    => null,
            'userId'        => null,
            'authType'      => 'password',
            'credential'    => '',
            'name'          => '',
        );

        $userAuth = new Model_UserAuth( );

        $this->assertTrue( ! $userAuth->isBacked() );
        $this->assertTrue(   $userAuth->isValid() );

        $this->assertEquals($expected, $userAuth->toArray());
    }

    public function testUserAuthGetId()
    {
        $data     = array('userId'   => 5,
                          'authType' => 'password');
        $expected = null;

        $userAuth = new Model_UserAuth( $data );

        $this->assertEquals($expected, $userAuth->getId());
    }

    public function testUserAuthGetMapper()
    {
        $userAuth = new Model_UserAuth( );

        $mapper = $userAuth->getMapper();

        $this->assertType('Model_Mapper_UserAuth', $mapper);
    }

    public function testUserAuthGetFilter()
    {
        $userAuth = new Model_UserAuth( );

        $filter = $userAuth->getFilter();

        //$this->assertType('Model_Filter_UserAuth', $filter);
        $this->assertEquals(Connexions_Model::NO_INSTANCE, $filter);
    }

    public function testUserAuthSetInvalidUser()
    {
        $expected = array(
            'userId'        => null,
            'authType'      => 'password',
            'credential'    => '',
        );

        $userAuth = new Model_UserAuth( );

        try
        {
            $userAuth->user = 'user1';

            $this->fail("should only accept Model_User for member 'user'");
        }
        catch (Exception $e)
        {
            /*
            Connexions::log("testUserAuthSetInvalidUser: Exception: %s",
                            $e->getMessage());
            // */
        }
    }

}
