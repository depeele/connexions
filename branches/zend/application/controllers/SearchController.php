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
        $this->_terms   =  $request->getParam('q',             null);

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

        case 'all':
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
            break;

        case 'mynetwork':
            break;

        case 'same':
            break;

        case 'all':
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

            $this->_searchBookmarks($owner,
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
    protected function _searchBookmarks($users,
                                        $tags,
                                        $items = null)
    {
        $to = array('where' => $this->_terms);

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

        $this->_results = $this->service('Bookmark')->fetchRelated($to);
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

    /** @brief  Given search term(s), parse them according to our search
     *          syntax.
     *  @param  terms       The term(s) to parse.
     *  @param  fields      A set of field(s) to apply the term(s) against.
     *
     *  Syntax:
     *      - Quoted phrase;
     *      - Term exclusion by prefixing the term with '-';
     *      - Wildcard '*';
     *      - Exact match '+';
     *      - The OR operator: '|' 'OR';
     *      - By default, all terms are combined with AND;
     *
     *  @return An array of conditions suitable for the 'where' parameter to
     *          Connexions_Service::fetch() / fetchRelated().
     */
    protected function _parseTerm($terms, array $fields)
    {
        $parts = preg_split('/(".*?[^\\]"|\s+(?:\|OR)\s+|\s+)/', $terms);
    }
}
