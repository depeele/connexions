<?php
/** @file
 *
 *  An abstract service class to include shared methods that don't really
 *  belong in the main Connexions_Server class.
 *
 */
abstract class Service_Base extends Connexions_Service
{
    /** @brief  Retrieve a set of Domain Model instance related by the provided 
     *          set of Users, Tags, and/or Items.
     *  @param  to      An array containing the User(s), Tag(s) and/or Item(s) 
     *                  retrieved bookmarks should be related to:
     *                      users       A Model_Set_User instance, array, or 
     *                                  comma-separated string of users to 
     *                                  match -- ANY user in the list.
     *                      items       A Model_Set_Item instance, array, or 
     *                                  comma-separated string of items to 
     *                                  match -- ANY item in the list..
     *                      tags        A Model_Set_Tag instance, array, or 
     *                                  comma-separated string of tags to
     *                                  match -- ANY tag in the list.
     *                      exactUsers  If 'users' is provided, the retrieved
     *                                  models MUST match them all;
     *                      exactItems  If 'items' is provided, the retrieved
     *                                  models MUST match them all;
     *                      exactTags   If 'tags' is provided, the retrieved
     *                                  models MUST match them all;
     *  @param  order   Optional ORDER clause (string, array)
     *                      [ [ 'taggedOn      DESC',
     *                          'name          ASC',
     *                          'userCount     DESC',
     *                          'tagCount      DESC' ] ]
     *  @param  count   Optional LIMIT count
     *  @param  offset  Optional LIMIT offset
     *
     *  @return A new Connexions_Model_Set instance.
     */
    public function fetchRelated(array  $to,
                                        $order  = null,
                                        $count  = null,
                                        $offset = null)
    {
        /*
        Connexions::log("Service_Base(%s)::fetchRelated(): "
                        .   "to[ %s ], ",
                        get_class($this),
                        Connexions::varExport($to));
        // */

        /* Request the inclusion of a privacy filter based upon the current
         * user wherever necessary.
         */
        $config = array('order'   => $this->_extraOrder($order),
                        'count'   => $count,
                        'offset'  => $offset,
                        'privacy' => $this->_curUser(),
                  );

        $config = $this->_normalizeParams($to, $config);

        /*
        Connexions::log("Service_Base(%s)::fetchRelated(): "
                        .   "config[ %s ]",
                        get_class($this),
                        Connexions::varExport($config));
        // */

        return $this->_mapper->fetchRelated( $config );
    }

    /** @brief  Retrieve statistics.
     *  @param  params  An array of optional retrieval criteria:
     *                      - users     A set of users to use in generating
     *                                  statistics.  A Model_Set_User instance
     *                                  or an array of userIds;
     *                      - items     A set of items to use in generating
     *                                  statistics.  A Model_Set_Item instance
     *                                  or an array of itemIds;
     *                      - tags      A set of tags to use in generating
     *                                  statistics.  A Model_Set_Tag instance
     *                                  or an array of tagIds;
     *                      - aggregate Generate aggregate statistics (true)
     *                                  or per item (based upon the table)
     *                                  [ false ];
     *                      - order     If ! aggregate, an ORDER clause
     *                                  (string, array) [ 'taggedOn DESC' ];
     *                      - count     If ! aggregate, a  LIMIT count
     *                                  [ all ];
     *                      - offset    If ! aggregate, a  LIMIT offset
     *                                  [ 0 ];
     *
     *  @return An array of statistics.
     */
    public function getStatistics(array $params = array())
    {
        $privacy   = ( isset($params['privacy'])
                        ? $params['privacy']
                        : null);
        $aggregate = ( isset($params['aggregate'])
                        ? Connexions::to_bool($params['aggregate'])
                        : false);

        $config = $this->_normalizeParams($params);

        // Check for additional restrictions that might require privacy
        if ( ($privacy !== true) &&
             ( (isset($config['users']) && (! empty($config['users']))) ||
               (isset($config['items']) && (! empty($config['items']))) ||
               (isset($config['tags'])  && (! empty($config['tags'])))  ||
               (isset($config['from'])  && (! empty($config['from'])))  ||
               (isset($config['until']) && (! empty($config['until']))) ||
               (isset($config['count']) && (! empty($config['count']))) ) )
        {
            $privacy         = true;
        }


        /* The choice of privacy is based upon:
         *  - keep any specifically requested privacy restrictions;
         *  - if the request includes specific query restrictions
         *    (e.g. users, items, tags), then 'privacy' will be true, forcing
         *    the default privacy checks;
         *  - otherwise:
         *      - if generating aggregate statistics, these are non-specific
         *        enough to disable privacy restrictions;
         *      - else use the default privacy;
         */
        $config['privacy']   = ($privacy !== null
                                    ? $privacy
                                    : ($aggregate === true
                                        ? false // none for non-specific
                                                // aggregate
                                        : null  // required for non-aggregates
                                    )
                               );

        /*
        Connexions::log("Service_Base(%s)::getStatistics(): "
                        . "config[ %s ]",
                        get_class($this),
                        Connexions::varExport($config));
        // */

        $stats = $this->_mapper->getStatistics( $config );
        return $stats;
    }

    /***********************************************
     * Protected helpers
     *
     */

    /** @brief  Given an array of parameters, convert any bookmarks, users,
     *          tags, or items entries to be suitable for the mappers.
     *  @param  params  An array of configuration data;
     *  @param  norm    An array to add normalized data to;
     *
     *  @return The normalize array ('norm');
     */
    protected function _normalizeParams(array $params,
                                        array $norm   = array())
    {
        foreach ($params as $key => $val)
        {
            // Rely on Services to properly interpret users/items/tags
            switch ($key)
            {
            case 'bookmarks':
                $bookmarks = $this->factory('Service_Bookmark')
                                                ->csList2set($val);
                $val       = ( (! empty($bookmarks)) &&
                                ($bookmarks instanceof Model_Set_Bookmark)
                                    ? $bookmarks->getIds()
                                    : $bookmarks);
                break;

            case 'users':
                $val = $this->factory('Service_User')
                                                ->csList2set($val);
                break;

            case 'items':
                $val = $this->factory('Service_Item')
                                                ->csList2set($val);
                break;

            case 'tags':
                $val = $this->factory('Service_Tag')
                                                ->csList2set($val);
                break;

            case 'order':
                $val = $this->_csOrder2array($val, true /* noExtras */);
                break;
            }

            $norm[$key] = $val;
        }

        /*
        Connexions::log("Service_Base(%s)::_normalizeParams(): "
                        .   "params[ %s ], "
                        .   "norm[ %s ]",
                        get_class($this),
                        Connexions::varExport($params),
                        Connexions::varExport($norm));
        // */

        return $norm;
    }
}
