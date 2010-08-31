<?php
/** @file
 *
 *  This controller controls access to an authenticated Users settings and is
 *  accessed via the url/routes:
 *      /settings       [/:type     [/:cmd]]    Viewer settings
 */


class SettingsController extends Connexions_Controller_Action
{

    public function indexAction()
    {
        $viewer =& $this->_viewer;

        // Use the currently authenticated user
        if ( ( ! $viewer instanceof Model_User) ||
             (! $viewer->isAuthenticated()) )
        {
            // Unauthenticated user -- Redirect to signIn
            return $this->_helper->redirector('signIn','auth');
        }

        $request =& $this->_request;
        $type    =  $request->getParam('type', null);
        $cmd     =  $request->getParam('cmd',  null);

        $this->view->type   = $type;
        $this->view->cmd    = $cmd;
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
