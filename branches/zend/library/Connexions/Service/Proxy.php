<?php
/** @file
 *
 *  Abstract base class for service proxies that expose only the publically
 *  callable methods of a service.
 */
class Connexions_Service_Proxy
{
    protected   $_service   = null;

    public function __construct()
    {
        $serviceName = $this->_service;
        if (empty($serviceName))
        {
            /* Use the model name to construct a Model Mapper
             * class name:
             *      Model_<Class> => Model_Mapper_<Class>
             */
            $serviceName = str_replace('_Proxy', '', get_class($this));
        }

        $service        = Connexions_Service::factory($serviceName);
        $this->_service = $service;
    }

    /** @brief  Retrieve a single, existing Domain Model instance.
     *  @param  id          The identifier of the desired Domain Model.
     *
     *  @return The matching Connexions_Model instance.
     */
    public function find($id)
    {
        return $this->_service->find($id);
    }

    /** @brief  Retrieve a set of Domain Model instances.
     *  @param  ids         A comma-separated list of identifiers appropriate
     *                      for the Domain Model.
     *  @param  order       An comma-separated list of name/direction pairs
     *                      representing the desired sorting order.
     *  @param  count       The maximum number of items from the full set of
     *                      matching items that should be returned
     *                      [ null == all ];
     *  @param  offset      The starting offset in the full set of matching
     *                      items [ null == 0 ].
     *
     *  @return A new Connexions_Model_Set.
     */
    public function fetch($ids       = null,
                          $order     = null,
                          $count     = null,
                          $offset    = null)
    {
        return $this->_service->fetch($ids, $order, $count, $offset);
    }
}
