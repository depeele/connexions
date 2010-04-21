<?php
class ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase
{
    public $application;

    public function setUp()
    {
        $config = new Zend_Config_Ini(
                            APPLICATION_PATH . '/configs/application.ini',
                            APPLICATION_ENV);
        Zend_Registry::set('config', $config);

        $this->application = new Zend_Application(APPLICATION_ENV, $config);
        $this->bootstrap   = array($this, 'testBootstrap');

        parent::setUp();
    }

    public function testBootstrap()
    {
        $this->application->bootstrap();
    }

    /* If we want the Front Controller to throw all the exceptions, we have no
     * other choice than to overwrite the dispatch method and pass a boolean
     * TRUE to the throwExceptions() method.
     */
    public function dispatch($url = null)
    {
        // redirector should not exit
        $redirector = 
            Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $redirector->setExit(false);

        // json helper should not exit
        $json = 
            Zend_Controller_Action_HelperBroker::getStaticHelper('json');
        $json->suppressExit = true;

        $request = $this->getRequest();
        if ($url !== null)
            $request->setRequestUri($url);
        $request->setPathInfo(null);

        $this->getFrontController()
             ->setRequest($request)
             ->setResponse($this->getResponse())
             ->throwExceptions(true)
             ->returnResponse(false);

        $this->getFrontController()->dispatch();
    }

    public function tearDown()
    {
        Zend_Controller_Front::getInstance()->resetInstance();
        $this->resetRequest();
        $this->resetResponse();

        $this->request->setPost(array());
        $this->request->setQuery(array());
    }

}
