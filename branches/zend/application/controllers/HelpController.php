<?php
/** @file
 *
 *  This controller controls access to Help and is accessed via the url/routes:
 *      /help[<topic>]
 */

class HelpController extends Connexions_Controller_Action
{
    protected   $_noSidebar = true;

    public function indexAction()
    {
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

