<?php
/** @file
 *
 *  This controller controls access to Help and is accessed via the url/routes:
 *      /help[<topic>]
 */

class HelpController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public      $dependencies   = array('db','layout');
    public      $contexts       = array('index' => array('partial'));
    protected   $_noSidebar     = true;

    public static   $tabs       = array(
        'general'   => array(
            'title'     => 'General',
            'script'    => 'main-general',
        ),
        'faq'       => array(
            'title'     => 'FAQ',
            'script'    => 'main-faq',
        ),
        'developers'=> array(
            'title'     => 'Developers',
            'script'    => 'main-developers',
        ),
        'about'     => array(
            'title'     => 'About',
            'script'    => 'main-about',
        ),
    );

    public function init()
    {
        $this->_baseUrl    = $this->_helper->url(null, 'help');
        $this->_cookiePath = $this->_baseUrl;

        parent::init();
    }

    public function indexAction()
    {
        $request =& $this->_request;

        $this->view->tabs    = self::$tabs;
        $this->view->topic   = $request->getParam('topic',   null);
        $this->view->section = $request->getParam('section', null);
        $this->view->rest    = $request->getParam('rest',    null);

        /*
        Connexions::log("HelpController::indexAction(): "
                        .   "topic[ %s ], section[ %s ], rest[ %s ]",
                        Connexions::varExport($this->view->topic),
                        Connexions::varExport($this->view->section),
                        Connexions::varExport($this->view->rest));
        // */

        // HTML form/cookie namespace
        $this->_namespace = 'help';

        return;

        // The specific view requested will be contained in 'topic'
        $request = $this->getRequest();
        $topic   = $request->getParam('topic', null);
        if (! @empty($topic))
        {
            // Render the Topic view (if it exists)
            try
            {
                $this->render($topic);
            }
            catch (Zend_Exception $e)
            {
                // Just show the top-level help
                $this->render('index');
            }
        }
    }
}
