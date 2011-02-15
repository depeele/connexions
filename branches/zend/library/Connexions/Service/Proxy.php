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
            /* The associated service was NOT directly provided.
             *
             * Use the name of this class to construct the associated Service
             * class name:
             *      Service_Proxy_<Class> => Service_<Class>
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

    /*************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Retrieve the currently authenticated user and validate the
     *          provided API key.
     *  @param  apiKey      The API key that is supposed to be associated with
     *                      the currently authenticated user;
     *
     *  @throw  Exceptioni('Invalid apiKey')
     *
     *  @return The currently authenticated user.
     */
    protected function _authenticate($apiKey)
    {
        $user = Connexions::getUser();
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }

        /* For GET requests, REQUIRE an 'apiKey' that matches the authenticated
         * user's apiKey.
         *
         * Allow other request methods (e.g. POST, PUT, DELETE) to exclude the
         * apiKey since client-side cross-domain protection will be enabled for
         * those.
         */
        $req = Connexions::getRequest();
        if ( $req->isGet() && ($user->apiKey !== $apiKey) )
        {
            throw new Exception('Invalid apiKey.');
        }

        return $user;
    }
}
