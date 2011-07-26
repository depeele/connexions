<?php
/** @file
 *
 *  This controller controls access to Search and is accessed via POST to the
 *  url/routes:
 *      /search
 *          POST parameters:
 *              owner                   The owner user name;
 *              tags                    Tags to limit the search;
 *              searchContext           The search context from the top-level
 *                                      search area;
 *              directSearchContext     The search context specified in the
 *                                      search form;
 *              terms                   The search terms/query.
 */


class SearchController extends Connexions_Controller_Action
{
    // Tell Connexions_Controller_Action_Helper_ResourceInjector which
    // Bootstrap resources to make directly available
    public  $dependencies   = array('db','layout');
    public  $contexts       = array(
                                'index' => array(
                                    'partial', 'json', 'rss',     'atom'),
                              );
    protected $_noSidebar   = true;


    protected $_referer = null; // connexions URL from which a search was
                                // performed.
    protected $_context = null; // The requested search context.
    protected $_terms   = null; // The terms to search for.
    protected $_results = null; // The generated search results.

    public function indexAction()
    {
        $request =& $this->_request;

        $this->_referer  =  $request->getParam('referer',             null);
        $this->_context  =  $request->getParam('directSearchContext',
                             $request->getParam('searchContext',
                              $request->getParam('context',           null)));
        $this->_terms    =  $request->getParam('terms',               null);
        $this->_context  =  strtolower($this->_context);

        $this->_baseUrl .= 'search/';
        $this->_url      = $this->_baseUrl;

        // Set initial view variables
        $this->view->referer = $this->_referer;
        $this->view->context = $this->_context;
        $this->view->terms   = $this->_terms;
    }

    /*************************************************************************
     * Protected Helpers
     *
     */

    /** @brief  Prepare and render a partial view.
     *
     *  Override Connexions_Controller_Action in order to adjust the rendering
     *  based upon the search context.
     *
     *  For this controller, all preparation is post poned until here.
     *
     *  The individual search methods will also make use of _prepare_main() to
     *  adjust '_namespace' in order to retrieve display parameters associated
     *  with the section, which is why we don't just over-ride _prepare_main().
     */
    protected function _renderPartial()
    {
        // /*
        Connexions::log("SearchController::_renderPartial: "
                        . "referer[ %s ], context[ %s ], terms[ %s ]",
                        $this->_referer,
                        $this->_context,
                        $this->_terms);
        // */

        if (! empty($this->_terms))
        {
            $this->_results = array();

            switch ($this->_context)
            {
            case 'mybookmarks':
            case 'mynetwork':
                $this->_authSearch();
                break;

            case 'same':
                $this->_refererSearch();
                break;

            default:
                $this->_search();
                break;
            }

            $this->view->results = $this->_results;
        }
        else
        {
            // No results - revert to the simple search form.
            $this->_partials = array('form');
        }

        $script = implode('-', $this->_partials);
        $this->render($script);
    }

    /** @brief  Perform a search regardless of authentication.
     *
     */
    protected function _search()
    {
        /*
        Connexions::log("SearchController::_search(): "
                        . "context[ %s ], terms[ %s ]",
                        $this->_context, $this->_terms);
        // */

        switch ($this->_context)
        {
        case 'mybookmarks':
            /* Fetch bookmarks belonging to the current viewer that also have
             * a match to 'term(s)' in name and/or description.
             */
            $this->_searchBookmarks($this->_viewer);

            break;

        case 'mynetwork':
            /* Fetch bookmarks belonging to the any user in the network
             * of the current viewer that also have a match to 'term(s)' in 
             * name and/or description.
             */
            break;

        case 'bookmarks':
            /* Fetch bookmarks that have a match to 'term(s)' in name and/or 
             * description.
             */
            $this->_searchBookmarks();
            break;

        case 'all':
            /* Fetch bookmarks, tags, items, and people that have a match to 
             * 'term(s)' in:
             *  - bookmarks - name and/or description
             *  - tags      - tag
             *  - people    - name, fullName, email, pictureUrl, profile
             *  - items     - url
             */
            $partial = ( count($this->_partials) > 1
                            ? implode('-', $this->_partials)
                            : null );

            /*
            Connexions::log("SearchController::_search(): all, partial[ %s ]",
                            $partial);
            // */

            if ( ($partial === null) || ($partial === 'main-bookmarks') )
            {
                $this->_searchBookmarks();
            }

            if ( ($partial === null) || ($partial === 'main-tags') )
            {
                /*****************************************
                 * Use _prepare_main to retrieve display
                 * parameters for the 'tags' section
                 * and retrieve the tags.
                 *
                 */
                $this->_namespace = 'tags';
                $this->_prepare_main();
                $tags = $this->view->main;
                $tags['namespace']   = $this->_namespace;   //'tags';
                $tags['panePartial'] = 'main-tags';
                $tags['paneVars']    = array(
                    'referer'   => $this->_referer,
                    'context'   => $this->_context,
                    'terms'     => $this->_terms
                );
                $tags['itemType']    =
                                View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
                $tags['itemBaseUrl'] = $this->_helper->url(null, 'bookmarks');

                $tags['weightName']  = 'userItemCount';
                $tags['weightTitle'] = 'Bookmarks with this tag';
                $tags['titleTitle']  = 'Tag';

                $where = $this->_parseTerms($this->_terms,
                                            array('tag'));

                /*
                Connexions::log("SearchController::_search(): "
                                . "tags config[ %s ]",
                                Connexions::varExport($tags));
                // */

                $fetchOrder = array('userItemCount DESC',
                                    'userCount     DESC',
                                    'itemCount     DESC',
                                    'tag           ASC');
                $tags = $this->_prepare_cloud($tags, 'Model_Tag',
                                              $where, $fetchOrder);

                $this->_results['tags'] = $tags;
            }

            if ( ($partial === null) || ($partial === 'main-people') )
            {
                /*****************************************
                 * Use _prepare_main to retrieve display
                 * parameters for the 'people' section
                 * and retrieve the people.
                 *
                 */
                $this->_namespace = 'people';
                $this->_prepare_main();
                $people = $this->view->main;
                $people['namespace']   = $this->_namespace; //'people';
                $people['panePartial'] = 'main-people';
                $people['paneVars']    = array(
                    'referer'   => $this->_referer,
                    'context'   => $this->_context,
                    'terms'     => $this->_terms
                );

                $people['where'] = $this->_parseTerms($this->_terms,
                                                      array('name',
                                                            'fullName',
                                                            'email',
                                                            'pictureUrl',
                                                            'profile'));

                $this->_results['people'] = $people;
            }

            if ( ($partial === null) || ($partial === 'main-items') )
            {
                /*****************************************
                 * Use _prepare_main to retrieve display
                 * parameters for the 'items' section
                 * and retrieve the items.
                 *
                 */
                $this->_namespace = 'items';
                $this->_prepare_main();
                $items = $this->view->main;
                $items['namespace']   = $this->_namespace;  //'items';
                $items['panePartial'] = 'main-items';
                $items['paneVars']    = array(
                    'referer'   => $this->_referer,
                    'context'   => $this->_context,
                    'terms'     => $this->_terms
                );
                $items['itemType']    =
                                View_Helper_HtmlItemCloud::ITEM_TYPE_ITEM;
                $items['itemBaseUrl'] = $this->_helper->url(null, 'url');

                $items['weightName']  = 'userItemCount';
                $items['weightTitle'] = 'Bookmarks';
                $items['titleTitle']  = 'Url';

                if ($items['displayStyle'] === null)
                {
                    $items['displayStyle'] =
                        View_Helper_HtmlItemCloud::STYLE_LIST;
                }


                $where = $this->_parseTerms($this->_terms, array('url'));

                /*
                Connexions::log("SearchController::_search(): "
                                . "items config[ %s ]",
                                Connexions::varExport($items));
                // */

                $fetchOrder = array('ratingAvg DESC',
                                    'url       ASC');

                $items = $this->_prepare_cloud($items, 'Model_Item',
                                               $where, $fetchOrder);

                $this->_results['items'] = $items;
            }

            break;
        }
    }

    /** @brief  Perform preparation for using the HtmlItemCloud helper.
     *  @param  config      Current configuration;
     *  @param  modelName   The name of the Model to be presented;
     *  @param  where       The where condition to restrict item retrieval;
     *  @param  fetchOrder  The retrieval ordering
     *                      (that will return items sorted by weight);
     *
     *  @return New configuration.
     */
    protected function _prepare_cloud(array &$config,
                                            $modelName,
                                            $where,
                                            $fetchOrder)
    {
        /*
        Connexions::log("SearchController::_prepare_cloud(): "
                        . "config[ %s ], modelName[ %s ], "
                        . "where[ %s ], fetchOrder[ %s ]",
                        Connexions::varExport($config),
                        $modelName,
                        Connexions::varExport($where),
                        Connexions::varExport($fetchOrder));
        // */

        $extra = array(
            'showRelation'  => false,
        );
        $config = array_merge($config, $extra);

        // Defaults
        if ( ($config['perPage'] = (int)$config['perPage']) < 1)
            $config['perPage'] = 250;

        if ( ($config['page'] = (int)$config['page']) < 1)
            $config['page'] = 1;

        if ((empty($config['sortBy'])) || ($config['sortBy'] === 'title'))
            $config['sortBy'] = 'tag';

        if (empty($config['sortOrder']))
            $config['sortOrder'] = Connexions_Service::SORT_DIR_ASC;

        if (empty($config['displayStyle']))
            $config['displayStyle'] = View_Helper_HtmlItemCloud::STYLE_CLOUD;

        if (empty($config['highlightCount']))
            $config['highlightCount'] = 0;

        // Retrieve the set of tags to be presented.
        $count      = $config['perPage'];
        $offset     = ($config['page'] - 1) * $count;

        /*
        Connexions::log("SearchController::_prepare_cloud(): "
                        . "page[ %d ], perPage[ %d ], "
                        . "offset[ %d ], count[ %d ], order[ %s ]",
                        $config['page'], $config['perPage'],
                        $offset, $count,
                        Connexions::varExport($fetchOrder));
        // */

        $to = array('where' => $where);

        $config['items'] = Connexions_Service::factory($modelName)
                                    ->fetchRelated($to,
                                                   $fetchOrder,
                                                   $count,
                                                   $offset);


        $paginator       =  new Zend_Paginator($config['items']
                                                ->getPaginatorAdapter());
        $paginator->setItemCountPerPage( $config['perPage'] );
        $paginator->setCurrentPageNumber($config['page'] );

        $config['paginator']        = $paginator;

        $config['currentSortBy']    =
                                 View_Helper_HtmlItemCloud::SORT_BY_WEIGHT;
        $config['currentSortOrder'] =
                                 Connexions_Service::SORT_DIR_DESC;

        return $config;
    }

    /** @brief  Restrict this search to authenticated users.
     *
     */
    protected function _authSearch()
    {
        if ( (! $this->_viewer instanceof Model_User) ||
             (! $this->_viewer->isAuthenticated()) )
        {
            /*
            Connexions::log("SearchController::_authSearch(): "
                            . "NOT authenticated");
            // */

            $this->view->error =
                    "You must be logged in to perform that search.";
            return;
        }

        /*
        Connexions::log("SearchController::_authSearch(): "
                        . "authenticated as '%s'",
                        $this->_viewer);
        // */

        return $this->_search();
    }

    /** @brief  Perform a search based upon what the referer was presenting.
     *
     */
    protected function _refererSearch()
    {
        /*
        Connexions::log("SearchController::_refererSearch(): "
                        . "referer[ %s ], context[ %s ], terms[ %s ], "
                        . "baseUrl[ %s ]",
                        $this->_referer, $this->_context, $this->_terms,
                        $this->_rootUrl);
        // */

        $referer = preg_replace('#^'. $this->_rootUrl .'#', '',
                                rtrim($this->_referer, '/'));

        $rest       = explode('/', $referer);
        $controller = array_shift($rest);

        /*
        Connexions::log("SearchController::_refererSearch(): "
                        . "controller[ %s ], rest[ %s ]",
                        $controller,
                        Connexions::varExport($rest));
        // */

        switch ($controller)
        {
        case 'people':
            $tags = $this->_getNext($rest);

            // /*
            Connexions::log("SearchController::_refererSearch(): "
                            . "people, tags[ %s ]",
                            Connexions::varExport($tags));
            // */

            /*****************************************************************
             * Perform a search for 'people' with the given 'tags' AND match
             * 'terms' in name, fullName, email, pictureUrl, or profile.
             */
            $this->view->error = "Sorry.  "
                               . "Contextual 'people' search "
                               . "is not yet implemented.";
            break;

        case 'tags':
            $people = $this->_getNext($rest);

            // /*
            Connexions::log("SearchController::_refererSearch(): "
                            . "tags, people[ %s ]",
                            Connexions::varExport($people));
            // */

            /*****************************************************************
             * Perform a search for 'tags' used by the given 'tags' AND match
             * 'terms'.
             */
            $this->view->error = "Sorry.  "
                               . "Contextual 'tag' search "
                               . "is not yet implemented.";
            break;

        case 'help':
            $topic = $this->_getNext($rest);

            // /*
            Connexions::log("SearchController::_refererSearch(): "
                            . "help, topic[ %s ]",
                            Connexions::varExport($topic));
            // */

            $this->view->error = "Sorry.  "
                               . "Contextual 'help' search "
                               . "is not yet implemented.";
            break;

        case 'search':
            $this->view->error = "Sorry.  "
                               . "Contextual search within a search "
                               . "is not currently supported.";
            break;

        case 'url':
            $hash = $this->_getNext($rest);
            $tags = $this->_getNext($rest);

            /*
            Connexions::log("SearchController::_refererSearch(): "
                            . "url, hash[ %s ], tags[ %s ]",
                            Connexions::varExport($hash),
                            Connexions::varExport($tags));
            // */

            $this->_searchBookmarks(null,   // No 'owner'
                                    $tags,
                                    $hash);
            break;

        case 'inbox':
            $owner = $this->_getNext($rest);
            $tags  = $this->_getNext($rest);

            if (! is_string($tags))
                $tags = '';
            else if (! empty($tags))
                $tags .= ',';

            $tags .= 'for:'. $owner;

            /*
            Connexions::log("SearchController::_refererSearch(): "
                            . "inbox, owner[ %s ], tags[ %s ]",
                            Connexions::varExport($owner),
                            Connexions::varExport($tags));
            // */

            $this->_searchBookmarks(null,   // ALL users $owner,
                                    $tags);
            break;

        default:        // bookmarks
            $owner = ($controller !== 'bookmarks'
                        ? (empty($controller)
                            ? null
                            : $controller)
                        : null);
            $tags  = $this->_getNext($rest);

            /*
            Connexions::log("SearchController::_refererSearch(): "
                            . "bookmarks, owner[ %s ], tags[ %s ]",
                            Connexions::varExport($owner),
                            Connexions::varExport($tags));
            // */

            $this->_searchBookmarks($owner,
                                    $tags);
        }
    }

    /** @brief  Perform a search for 'bookmarks' that have the specified owner,
     *          tags, and possibly item URL hash.
     *  @param  users   The required user(s) (null == no user restrictions);
     *  @param  tags    The required tag(s)  (null == no tag  restrictions);
     *  @param  items   The required item(s) (null == no item restrictions);
     *
     */
    protected function _searchBookmarks($users  = null,
                                        $tags   = null,
                                        $items  = null)
    {
        /*
        Connexions::log("SearchController::_searchBookmarks(): "
                        . "users[ %s ], tags[ %s ], items[ %s ]",
                        $users, $tags, $items);
        // */

        /*****************************************
         * Use _prepare_main to retrieve display
         * parameters for the 'bookmarks' section
         * and retrieve the bookmarks.
         *
         */
        $this->_namespace = 'bookmarks';
        $this->_prepare_main();
        $bookmarks = $this->view->main;
        $bookmarks['namespace']   = 'bookmarks';
        $bookmarks['panePartial'] = 'main-bookmarks';
        $bookmarks['paneVars']    = array(
            'referer'   => $this->_referer,
            'context'   => $this->_context,
            'terms'     => $this->_terms
        );

        /*
        Connexions::log("SearchController::_searchBookmarks: "
                        . "bookmarks config[ %s ]",
                        Connexions::varExport($bookmarks));
        // */

        $bookmarks['where'] = $this->_parseTerms($this->_terms,
                                                 array('name',
                                                       'description',
                                                 ));
        if ($users !== null)
        {
            $bookmarks['users'] = $users;
        }
        if ($tags !== null)
        {
            $bookmarks['tags']  = $tags;
        }
        if ($items !== null)
        {
            $bookmarks['items'] = $items;
        }

        /*
        Connexions::log("SearchController::_searchBookmarks: "
                        . "bookmarks config[ %s ]",
                        Connexions::varExport($bookmarks));
        // */


        $this->_results['bookmarks'] = $bookmarks;
    }

    /** @brief  Retrieve the next item from 'rest'
     *  @param  rest    The result of a split, this may be an array or a single
     *                  primative.
     *
     *  @return The next item (null if none).
     */
    protected function _getNext(&$rest)
    {
        $ret = null;
        if (is_array($rest))
        {
            $ret = array_shift($rest);
        }
        else if (! empty($rest))
        {
            $ret  = $rest;
            $rest = null;
        }
        else
        {
            $ret = $rest;
        }

        return $ret;
    }

    /** @brief  Given a search term (string) and array of fields to apply the 
     *          term against, construct a representative array of 
     *          'condition/value' pairs.
     *  @param  terms       The search term string;
     *  @param  fields      The array of fields to apply the term against;
     *
     *  The search term syntax:
     *      - Quoted phrase;
     *      - Term exclusion by prefixing the term with '-';
     *      - Wildcard '*';
     *      - Exact match '+';
     *      - The OR operator: '|' 'OR';
     *      - By default, all terms are combined with AND;
     *
     *  @return An array of 'condition/value' pairs.
     */
    protected function _parseTerms($terms, array $fields)
    {
        $re = '/(?:'
            .    '(\(|\))'              // ( or )   => ( or )
            .'|'.'"(.*?[^\\\])"'        // "term"   => term
            .'|'.'\'(.*?[^\\\])\''      // 'term'   => term
            .'|'.'\s*([^\s\)]+)\s*'     // \sterm\s => term
            . ')/';
        preg_match_all($re, $terms, $matches);
    
        
        $splitTerms = array();
        $nParts = count($matches[0]);
        for ($idex = 0; $idex < $nParts; $idex++)
        {
            if (! empty($matches[2][$idex]))
                array_push($splitTerms, str_replace('\\','',
                           $matches[2][$idex]));
            else if (! empty($matches[3][$idex]))
                array_push($splitTerms, str_replace('\\','',
                           $matches[3][$idex]));
            else if (! empty($matches[4][$idex]))
                array_push($splitTerms, str_replace('\\','',
                           $matches[4][$idex]));
            else if (! empty($matches[1][$idex]))
                array_push($splitTerms, str_replace('\\','',
                           $matches[1][$idex]));
        }
    
        // Now, combine the splitTerms and fields
        $search = array();
        $nTerms = count($splitTerms) - 1;
        $op     = '';
        foreach ($splitTerms as $term)
        {
            $combiner = '+|';
            if (($term === '|') || ($term === 'OR'))
            {
                $op = '|';
                continue;
            }
            else if ($term[0] === '+')
            {
                // Exactly
                $comp = '=';
                $term = substr($term,1);
            }
            else if ($term[0] === '-')
            {
                // Does NOT contain
                $comp     = '!=*';
                $combiner = '+';
                $term     = substr($term,1);
            }
            else if ($term[0] === '*')
            {
                // Ends with
                $comp = '=$';
                $term = substr($term,1);
            }
            else if ($term[strlen($term)-1] === '*')
            {
                // Begins with
                $comp = '=^';
                $term = substr($term,0,-1);
            }
            else
            {
                // Contains
                $comp = '=*';
            }
    
            foreach ($fields as $idex => $field)
            {
                $condition = ($idex === 0
                                ? $op      . $field . $comp
                                : $combiner. $field . $comp);
    
                array_push($search, array('condition' => $condition,
                                          'value'     => $term));
            }
    
            $op   = '';
        }
    
        return $search;
    }
}
