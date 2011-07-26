<?php
require_once TESTS_PATH .'/application/BaseTestCase.php';

/**
 *  @group Models
 */
class AuthTest extends BaseTestCase
{
    public function testAuthUserPasswordType()
    {
        $auth = new Connexions_Auth_UserPassword();

        $this->assertEquals(Model_UserAuth::AUTH_PASSWORD, $auth->getAuthType());
    }

    public function testAuthOpenIdType()
    {
        $auth = new Connexions_Auth_OpenId();

        $this->assertEquals(Model_UserAuth::AUTH_OPENID, $auth->getAuthType());
    }

    public function testAuthApacheSslType()
    {
        $auth = new Connexions_Auth_ApacheSsl();

        $this->assertEquals(Model_UserAuth::AUTH_PKI, $auth->getAuthType());
    }
}
