<?php
require_once TESTS_PATH .'/application/ControllerTestCase.php';
require_once APPLICATION_PATH .'/controllers/IndexController.php';

class IndexControllerTest extends ControllerTestCase
{
    public function testDefaultShouldInvokeIndexAction()
    {
        $this->dispatch('/');
        $this->assertController('index');
        $this->assertAction('index');
    }

    public function testViewObjectContainsStringProperty()
    {
        Connexions::log("IndexControllerTest:2");

        $this->dispatch('/');

        $controller = new IndexController(
                            $this->request,
                            $this->response,
                            $this->request->getParams());
        $controller->indexAction();

        $this->assertTrue(isset($controller->view->string));
    }
}
