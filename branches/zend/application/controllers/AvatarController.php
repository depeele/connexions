<?php
/** @file
 *
 *  This controller controls access to the "avatar" of a specific user.
 *  It may be accessed via the url/routes:
 *      /avatar/<user>
 */
class AvatarController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public  $dependencies = array('db');

    protected   $_owner         = null;

    public function init()
    {
        //Connexions::log("AvatarController::init");

        $this->_baseUrl = $this->_helper->url(null, 'avatar');

        $this->_noNav            = true;
        $this->_noSidebar        = true;
        $this->_noFormatHandling = true;

        parent::init();
    }

    /** @brief  Index/Get/Read/View action.
     *
     *  Retrieve the avatar of the provided user/owner.
     */
    public function indexAction()
    {
        $request =& $this->_request;

        /***************************************************************
         * Process the requested 'owner' and 'tags'
         *
         */
        $reqOwner = $request->getParam('owner', null);

        // See if the requested user is one of the special 'self' indicators.
        if ( ($reqOwner === null)    ||
             ($reqOwner === '@mine') ||
             ($reqOwner === '@self') ||
             ($reqOwner === 'mine')  ||
             ($reqOwner === 'me')    ||
             ($reqOwner === 'self') )
        {
            // 'mine' == the currently authenticated user (viewer)
            if ( ( ! $this->_viewer instanceof Model_User) ||
                 (! $this->_viewer->isAuthenticated()) )
            {
                // Unauthenticated user -- Redirect to signIn
                return $this->_redirectToSignIn();
            }

            // Redirect to the viewer's network
            $url = $this->_viewer->name;
            return $this->_helper->redirector( $url );
        }

        // Does the name match an existing user?
        if ($reqOwner === $this->_viewer->name)
        {
            // 'name' matches the current viewer...
            $this->_owner =& $this->_viewer;
        }
        else
        {
            //$ownerInst = Model_User::find(array('name' => $name));
            $this->_owner = $this->service('User')
                                ->find(array('name' => $reqOwner));
        }

        $this->_render();
    }

    /*************************************************************************
     * Protected Helpers
     *
     */

    /** @brief  Directly render.
     */
    protected function _render()
    {
        $this->_disableRendering();

        // Retrieve and validate the avatar URL.
        $avatar = ($this->_owner ? $this->_owner->pictureUrl : null);
        if (empty($avatar))
        {
            $avatar = "images/user.gif";
        }
        else if (! @stat($avatar))
        {
            // Directly redirect to the "URL"
            header('Location: '. $avatar, true, 307);
            return;
        }

        // Retrieve information about the avatar image
        $size = getimagesize($avatar);

        /*
        Connexions::log("AvatarController::_render(): avatar[ %s ]: size[ %s ]",
                        $avatar, Connexions::varExport($size));
        // */

        if ($size)
        {
            $fp = fopen($avatar, 'rb');
            if ($fp)
            {
                /*
                Connexions::log("AvatarController::_render(): "
                                . "return the '%s' image",
                                $size['mime']);
                // */

                header('Content-Type: '. $size['mime']);
                fpassthru($fp);
                fclose($fp);
            }
        }

        //Connexions::log("AvatarController::_render(): complete");
    }

    /** @brief  Disable view rendering.
     */
    protected function _disableRendering()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->setParam('disableOutputBuffering', true);
        $front->getDispatcher()
                ->setParam('disableOutputBuffering', true);

        $viewRenderer =
          Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');

        $view = $viewRenderer->view;
        if ($view instanceof Zend_View_Interface)
        {
            $viewRenderer->setNoRender(true);
        }

        $layout = Zend_Layout::getMvcInstance();
        if ($layout instanceof Zend_Layout)
        {
            $layout->disableLayout();
        }
    }
}
