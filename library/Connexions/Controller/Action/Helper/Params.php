<?php
/** @file
 *
 *  Normalize access to incoming parameters, particularly from non-standard
 *  Content-Types.
 *
 *  Range usage in a view script (assuming 'sprints' is a Paginator object:
 *      $request = $this->request;
 *      if ($request->range_start || $request->range_end)
 *      {
 *          $start = (int)$request->range_start;
 *          $end   = (int)$request->range_end;
 *          $count = $this->sprints->getTotalItemCount();
 *          $limit = (! $end) ? $count : $end - $start;
 *          $end   = (! $end) ? $count : $end;
 *
 *          $items = $this->sprints->getAdapter()->getItems($start, $limit);
 *
 *          $this->response->setHeader('Content-Range',
 *                                     'items '. $start .'-'. $end
 *                                                      .'/'. $count);
 *      }
 */

class Connexions_Controller_Action_Helper_Params
            extends Zend_Controller_Action_Helper_Abstract
{
    protected $_bodyParams  = array();

    public function init()
    {
        Connexions::log("Connexions_Controller_Action_Helper_Params::init");

        $request = $this->getRequest();
        $rawBody = $request->getRawBody();
        if (empty($rawBody))
        {
            return;
        }

        $contentType = $request->getHeader('Content-Type');
        switch (true)
        {
        case (strstr($contentType, 'application/json')):
            $this->setBodyParams(Zend_Json::decode($rawBody));
            break;

        case (strstr($contentType, 'application/xml')):
            $config = new Zend_Config_Xml($rawBody);
            $this->setBodyParams($config->toArray());
            break;

        default:
            if ($request->isPut())
            {
                parse_str($rawBody, $params);
                $this->setBodyParams($params);
            }
        }

        Connexions::log("Connexions_Controller_Action_Helper_Params::init: "
                        .   "params[ %s ]",
                        print_r($this->direct(), true));
    }

    public function dispatchLoopStartup(
                        Zend_Controller_Request_Abstract    $request)
    {
        Connexions::log("Connexions_Controller_Action_Helper_Params::"
                        .   "dispatchLoopStartup...");

        $range = $request->getHeader('Range');
        if (! $range)
            return;

        // Format is: Range: items=0-9
        $range             = explode('=', $range);
        list($start, $end) = explode('-', $range[1]);

        $request->setParams(array('range_start'   => $start,
                                  'range_end'     => $end,
                            ));
    }

    public function getBodyParam($name, $default = null)
    {
        return (isset($this->_bodyParams[$name])
                    ? $this->_bodyParams[$name]
                    : $default);
    }

    public function hasBodyParam($name)
    {
        return isset($this->_bodyParams[$name]);
    }

    public function hasBodyParams()
    {
        return (! empty($this->_bodyParams));
    }

    public function getSubmitParams()
    {
        return ($this->hasBodyParams()
                    ? $this->_bodyParams
                    : $this->getRequest()->getPost());

    }

    /** @brief  Allow calling this helper as a broker method.
     *
     *  @return Parameters
     */
    public function direct()
    {
        return $this->getSubmitParams();
    }
}


