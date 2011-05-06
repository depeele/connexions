<?php
/** @file
 *
 *  This is an action helper that takes a controllers 'dependencies' member and
 *  uses it to make the listed Bootstrap resources directly available to the
 *  controller.
 *
 *  This is based upon a blog by Matthew Weier O’Phinney:
 *      http://weierophinney.net/matthew/archives/
 *              235-A-Simple-Resource-Injector-for-ZF-Action-Controllers.html
 */
class Connexions_Controller_Action_Helper_ResourceInjector
                            extends Zend_Controller_Action_Helper_Abstract
{
    protected $_resources;

    public function preDispatch()
    {
        $mid = 'Connexions_Controller_Action_Helper_ResourceInjector';

        $bootstrap  = $this->getBootstrap();
        if (! is_object($bootstrap))
            return;

        $controller = $this->getActionController();

        if (!isset($controller->dependencies) ||
            !is_array($controller->dependencies))
        {
            return;
        }

        foreach ($controller->dependencies as $name)
        {
            if (!$bootstrap->hasResource($name))
            {
                throw new Zend_Controller_Action_Exception(
                                "Unable to find dependency by name '$name'");
            }

            /*
            Connexions::log("%s: Inject resource '%s'",
                            $mid, $name);
            // */

            $controller->$name = $bootstrap->getResource($name);
        }
    }
 
    public function getBootstrap()
    {
        return $this->getFrontController()->getParam('bootstrap');
    }
}
