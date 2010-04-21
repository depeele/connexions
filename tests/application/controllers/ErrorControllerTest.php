<?php
require_once TESTS_PATH .'/application/ControllerTestCase.php';
require_once APPLICATION_PATH .'/controllers/ErrorController.php';

class ErrorControllerTest extends ControllerTestCase
{
    public function testExceptionIsAnInstanceOfZendControllerException()
    {
        $e            = new stdClass();
        $e->type      =
            Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER;
        $e->exception = new Zend_Controller_Exception('Invalid controller');
        $e->request   = $this->request;

        $this->request->setParam('error_handler', $e);
        $this->request->setParam('controller',    'error');
        $this->request->setParam('action',        'error');

        $controller = new ErrorController(
                            $this->request,
                            $this->response,
                            $this->request->getParams());

        $controller->errorAction();

        $isInstanceOf =
            $controller->view->exception instanceof Zend_Controller_Exception;

        $this->assertTrue($isInstanceOf);
    }
}

