<?php
/** @file
 *
 *  This controller controls access to Search and is accessed via POST to the
 *  url/routes:
 *      /search
 *          POST parameters:
 *              owner           The owner user name;
 *              tags            Tags to limit the search;
 *              searchContext   The search context;
 *              q               The search query.
 */


class SearchController extends Connexions_Controller_Action
{
    protected $_referer = null; // connexions URL from which a search was
                                // performed.
    protected $_context = null; // The requested search context.
    protected $_terms   = null; // The terms to search for.
    protected $_results = null; // The generated search results.


    public function indexAction()
    {
        $request =& $this->_request;

        $this->_referer =  $request->getParam('referer',       null);
        $this->_context =  $request->getParam('searchContext', null);
        $this->_terms   =  $request->getParam('terms',         null);

        $this->_context =  strtolower($this->_context);

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

        $this->view->referer = $this->_referer;
        $this->view->context = $this->_context;
        $this->view->terms   = $this->_terms;
        $this->view->results = $this->_results;
    }

    /** @brief  Perform a search regardless of authentication.
     *
     */
    protected function _search()
    {
        Connexions::log("SearchController::_search(): "
                        . "context[ %s ], terms[ %s ]",
                        $this->_context, $this->_terms);

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
            $this->_searchBookmarks();

            $tService = $this->service('Tag');
            $iService = $this->service('Item');
            $uService = $this->service('User');

            $tTo = array(
                'where' => $this->_parseTerms($this->_terms,
                                              array('tag')),
            );
            $iTo = array(
                'where' => $this->_parseTerms($this->_terms,
                                              array('url')),
            );
            $uTo = array(
                'where' => $this->_parseTerms($this->_terms,
                                              array(
                                                'name',
                                                'fullName',
                                                'email',
                                                'pictureUrl',
                                                'profile',
                                              )),
            );

            $this->_results['tags']   = $tService->fetchRelated($tTo);
            $this->_results['people'] = $uService->fetchRelated($uTo);
            $this->_results['items']  = $iService->fetchRelated($iTo);

            break;
        }
    }

    /** @brief  Restrict this search to authenticated users.
     *
     */
    protected function _authSearch()
    {
        if ( (! $this->_viewer instanceof Model_User) ||
             (! $this->_viewer->isAuthenticated()) )
        {
            Connexions::log("SearchController::_authSearch(): "
                            . "NOT authenticated");

            $this->view->error =
                    "You must be logged in to perform that search.";
            return;
        }

        Connexions::log("SearchController::_authSearch(): "
                        . "authenticated as '%s'",
                        $this->_viewer);

        return $this->_search();
    }

    /** @brief  Perform a search based upon what the referer was presenting.
     *
     */
    protected function _refererSearch()
    {
        Connexions::log("SearchController::_refererSearch(): "
                        . "referer[ %s ], context[ %s ], terms[ %s ]",
                        $this->_referer, $this->_context, $this->_terms);

        $referer = preg_replace('#^'. $this->_baseUrl .'#', '',
                                rtrim($this->_referer, '/'));

        $rest       = split('/', $referer);
        $controller = array_shift($rest);

        Connexions::log("SearchController::_refererSearch(): "
                        . "controller[ %s ], rest[ %s ]",
                        $controller,
                        Connexions::varExport($rest));

        switch ($controller)
        {
        case 'people':
            $tags = $this->_getNext($rest);

            Connexions::log("SearchController::_refererSearch(): "
                            . "people, tags[ %s ]",
                            Connexions::varExport($tags));

            /*****************************************************************
             * Perform a search for 'people' with the given 'tags' AND match
             * 'terms' in name, fullName, email, pictureUrl, or profile.
             */
            break;

        case 'tags':
            $people = $this->_getNext($rest);

            Connexions::log("SearchController::_refererSearch(): "
                            . "tags, people[ %s ]",
                            Connexions::varExport($people));

            /*****************************************************************
             * Perform a search for 'tags' used by the given 'tags' AND match
             * 'terms'.
             */
            break;

        case 'help':
            $topic = $this->_getNext($rest);

            Connexions::log("SearchController::_refererSearch(): "
                            . "help, topic[ %s ]",
                            Connexions::varExport($topic));
            break;

        case 'search':
            $this->view->error = "Sorry.  "
                               . "Contextual search within a search "
                               . "is not currently supported.";
            break;

        case 'url':
            $hash = $this->_getNext($rest);
            $tags = $this->_getNext($rest);

            Connexions::log("SearchController::_refererSearch(): "
                            . "url, hash[ %s ], tags[ %s ]",
                            Connexions::varExport($hash),
                            Connexions::varExport($tags));

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

            Connexions::log("SearchController::_refererSearch(): "
                            . "inbox, owner[ %s ], tags[ %s ]",
                            Connexions::varExport($owner),
                            Connexions::varExport($tags));

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

            Connexions::log("SearchController::_refererSearch(): "
                            . "bookmarks, owner[ %s ], tags[ %s ]",
                            Connexions::varExport($owner),
                            Connexions::varExport($tags));

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
        $to = array('where' => $this->_parseTerms($this->_terms,
                                                   array(
                                                    'name',
                                                    'description',
                                                ))
        );

        if ($users !== null)
        {
            $to['users'] = $users;
        }
        if ($tags !== null)
        {
            $to['tags']      = $tags;
            $to['exactTags'] = true;
        }
        if ($items !== null)
        {
            $to['items'] = $items;
        }

        $this->_results = array(
            'bookmarks' => $this->service('Bookmark')->fetchRelated($to),
        );
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
