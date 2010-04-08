<?php
/** @file
 *
 *  This controller controls access to the Welcome area and is accessed via the
 *  url/routes:
 *      /welcome
 */


class WelcomeController extends Zend_Controller_Action
{
    public function indexAction()
    {
        // action body
    }

    /** @brief Redirect all other actions to 'index'
     *  @param  method      The target method.
     *  @param  args        Incoming arguments.
     *
     */
    public function __call($method, $args)
    {
        if (substr($method, -6) == 'Action')
        {
            // Redirect
            return $this->_forward('index');
        }

        throw new Exception('Invalid method "'. $method .'" called', 500);
    }
}

