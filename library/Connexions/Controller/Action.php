<?php
/** @file
 *
 *  The base Connexions Controller.
 *
 *  Provides easy access to Services as well as the Model_User instance
 *  representing the current viewer.
 */
class Connexions_Controller_Action extends Zend_Controller_Action
{
    protected   $_request   = null;
    protected   $_viewer    = null;

    public function init()
    {
        /* Initialize action controller here */
        $this->_viewer  =& Zend_Registry::get('user');
        $this->_request =& $this->getRequest();
    }

    /** @brief  Retrieve a Connexions_Service instance.
     *  @param  name    The name of the desired service.
     *
     *  @return The Connexions_Service instance (null on failure).
     */
    protected function service($name)
    {
        if (strpos($name, 'Service_') === false)
            $name = 'Service_'. $name;

        return Connexions_Service::factory($name);
    }
}
