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

    /** @brief  For an action that requires authentication and the current user
     *          is unauthenticated, this method will redirect to signIn with a
     *          flash indicating that it should return to this same action upon
     *          succssful authentication.
     *  @param  urlParams       An array or string of additional URL parameters
     *                          to be added to the end of the redirection URL;
     */
    protected function _redirectToSignIn($urlParams = null)
    {
        $flash = $this->_helper->getHelper('FlashMessenger');

        /* Note: Since the redirection back to here will be via
         *       Redirector->gotoUrl(), which pre-pends the base URL,
         *       we need to remove the base URL from the return string.
         */
        $url = str_replace($this->_request->getBaseUrl(), '',
                           $this->_request->getRequestUri());

        if (! empty($urlParams))
        {
            $params = $urlParams;
            if (is_array($urlParams))
            {
                $params = array();
                foreach ($urlParams as $key => $val)
                {
                    if (is_int($key))
                        array_push($params, $val);
                    else
                        $params[$key] = $val;
                }

                $params = implode('&', $params);
            }

            if (strpos($url, '?') === false)
                $url .= '?';

            $url .= $params;
        }

        $this->_flashMessenger->addMessage('onSuccess:'. $url);

        // /*
        Connexions::log("Connexions_Controller_Action::_redirectToSignIn() "
                        . "Redirect to signIn with a flash to return to "
                        . "url [ %s ].",
                        $url);
        // */

        return $this->_helper->Redirector('signIn','auth');
    }
}
