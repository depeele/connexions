<?php
/** @file
 *
 *  Implement the original connexions API for backwards compatability.
 *
 *  .htaccess will redirect requests here with 'cmd' set to the target API:
 *      posts/get           apikey, tags
 *      posts/add           apikey, url, name, tags, description,
 *                          is_private, is_favorite, rating, update
 *      posts/update        apikey, url, name, tags, description,
 *                          is_private, is_favorite, rating
 *      posts/delete        apikey, url
 *
 *      tags/get            apikey
 *      tags/delete         apikey, tags
 *      tags/rename         apikey, old, new
 *
 *  Parameters may be directly in the URL OR as a JSON-encoded string passed
 *  via 'jsonRpc'.  In this case, 'cmd' will be unset and is instead found in
 *  the 'method' property of the JSON-decoded RPC object.
 *
 */
define('RPC_DIR', realpath(dirname(__FILE__)));
require_once(RPC_DIR .'/bootstrap.php');

// Create an initial request and response
$req = new Connexions_Json_Server_Request_Http();
$rsp = new Zend_Json_Server_Response();

// See if the JSON RPC information is in a parameter
$jsonRpc = $req->getParam('jsonRpc');
if (! empty($jsonRpc))
{
    // Attempt to set the request from 'jsonRpc'
    try
    {
        $req->setRawJson( cleanup_json($_REQUEST['jsonRpc']) );
    }
    catch (Exception $e)
    {
        // Invalid JSON
        $err = new Zend_Json_Server_Error(
                        "Invalid JSON: {$e->getMessage()}",
                        Zend_Json_Server_Error::ERROR_PARSE);
        $rsp->setError($err);
    }
}
else
{
    // Build the JSON RPC from _REQUEST
    $req->setOptions(array(
        'version'   => '2.0',
        'id'        => 1,
        'method'    => preg_replace('#/#', '_', $_REQUEST['cmd']),
    ));

    foreach ($_REQUEST as $key => $val)
    {
        if ($key === 'cmd') continue;

        $req->addParam($val, $key);
    }
}

if (! $rsp->isError())
{
    // Establish the JSON RPC server
    $server = new Zend_Json_Server();
    $server->setAutoEmitResponse(false);
    $server->setClass('API_v1');

    try
    {
        $rsp = $server->handle( $req );
    }
    catch (Exception $e)
    {
        $msg  = $e->getMessage();
        $code = $e->getCode();
        if (preg_match('/Missing required parameter/', $msg))
        {
            $code = Zend_Json_Server_Error::ERROR_INVALID_PARAMS;
        }
        $err = new Zend_Json_Server_Error($msg, $code);
        $rsp->setError($err);

        if (($id = $req->getId()) !== null)
        {
            $rsp->setid($id);
        }
        if (($version = $req->getVersion()) !== null)
        {
            $rsp->setVersion($version);
        }
        $server->setResponse($rsp);
    }
}

//header('Content-Type: application/json');
echo $rsp;

/******************************************************************************
 * Implementation
 *
 */

/** @brief  A class for handling JSON-RPC requests for the original connexions
 *          API.
 */
class API_v1
{
    /** @brief  Retrieve a set of items for the current user.
     *  @param  string apikey   Your unique api key.
     *  @param  string tags     A comma-separated list of tags to filter by.
     *
     *  @return array of bookmarks
     */
    public function posts_get($apikey,
                              $tags = null)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('Bookmark');

        $bookmarks  = $service->fetchByUsersAndTags($user, $tags,
                                                    true,     // exactUsers
                                                    true);    // exactTags

        return $bookmarks;
    }

    /** @brief  Add a new item or update an existing bookmark.
     *  @param  string apikey       Your unique api key.
     *  @param  string url          The url of the bookmark.
     *  @param  string name         The user selected name for this bookmark.
     *  @param  string tags         A comma-separated list of tags.
     *  @param  string description  The user's description for this bookmark.
     *  @param  bool   is_private   Is this item 'private' [ false ].
     *  @param  bool   is_favorite  Is this item 'private' [ false ].
     *  @param  int    rating       The user rating (0-5)  [ 0 ].
     *  @param  bool   update       Update the item if it exists [ true ].
     *
     *  @return array representing the (updated) bookmark
     */
    public function posts_add($apikey,
                              $url,
                              $name,
                              $tags,
                              $description  = '',
                              $is_private   = false,
                              $is_favorite  = false,
                              $rating       = -1,
                              $update       = true)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('Bookmark');

        $id         = array('user'  => $user,
                            'url'   => $url);

        /*
        if ($update !== true)
        {
            // FIRST perform a lookup to see if the bookmark already exists.
            // If it does, do nothing.
            $bookmark   = $service->get($id);

            printf ("posts_add: bookmark[ %s ]<br />\n",
                    $bookmark->debugDump());

            if ($bookmark->isBacked())
            {
                return;
            }
        }
        // */

        // Simply call update which will either create OR update
        $bookmark = $service->update($id,
                                     $name,
                                     $description,
                                     $rating,
                                     $is_favorite,
                                     $is_private,
                                     $tags,
                                     $url);

        return $bookmark;
    }

    /** @brief  Update an existing bookmark.
     *  @param  string apikey       Your unique api key.
     *  @param  string url          The url of the bookmark.
     *  @param  string name         The user selected name for this bookmark.
     *  @param  string tags         A comma-separated list of tags.
     *  @param  string description  The user's description for this bookmark.
     *  @param  bool   is_private   Is this item 'private' [ false ].
     *  @param  bool   is_favorite  Is this item 'private' [ false ].
     *  @param  int    rating       The user rating (0-5)  [ 0 ].
     *
     *  @return array representing the (updated) bookmark
     */
    public function posts_update($apikey,
                                 $url,
                                 $name          = null,
                                 $tags          = null,
                                 $description   = '',
                                 $is_private    = false,
                                 $is_favorite   = false,
                                 $rating        = -1)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('Bookmark');

        $id         = array('userId' => $user->userId,
                            'itemId' => $url);

        // Simply call update which will either create OR update
        $bookmark = $service->update($id,
                                     $name,
                                     $description,
                                     $rating,
                                     $is_favorite,
                                     $is_private,
                                     $tags,
                                     $url);

        return $bookmark;
    }

    /** @brief  Delete an existing bookmark.
     *  @param  string apikey   Your unique api key.
     *  @param  string url      The url of the bookmark to delete.
     *
     *  @return null
     */
    public function posts_delete($apikey, $url)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('Bookmark');

        $id         = array('userId' => $user->userId,
                            'itemId' => $url);

        // Simply call update which will either create OR update
        $service->delete($id);
    }

    /** @brief  Retrieve the list of tags and counts for the current user.
     *  @param  string apikey   Your unique api key.
     *
     *  @return array of tags
     */
    public function tags_get($apikey)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('Tag');

        $tags       = $service->fetchByUsers($user);

        return $tags;
    }

    /** @brief  Delete the given tag(s) for all bookmarks of the current user.
     *  @param  string apikey   Your unique api key.
     *  @param  string tags     A comma-separated list of tags.
     *
     *  @return array of items of the form:
     *              '%tag%': status
     */
    public function tags_delete($apikey, $tags)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('User');

        $res        = $service->deleteTags($user, $tags);

        return $res;
    }

    /** @brief  Rename the given tag(s).
     *  @param  string apikey   Your unique api key.
     *  @param  string old      A colon-separated list of tags.
     *  @param  string new      A parallel colon-separated list of new tags.
     *
     *  @return array of items of the form:
     *              'old':      %old%,
     *              'new':      %new%,
     *              'items':    count of successfully changed item
     *              'renames':  An associative array of old-name and a
     *                          status string indicating the rename results
     */
    public function tags_rename($apikey, $old, $new)
    {
        $user       = $this->_authenticate($apikey);
        $service    = $this->_service('User');

        $tagsOld    = preg_split('/\s*[:,]\s*/', $old);
        $tagsNew    = preg_split('/\s*[:,]\s*/', $new);

        if (count($tagsOld) !== count($tagsNew))
        {
            throw new Exception("Mismatch old/new tag counts");
        }

        $renames    = array_combine($tagsOld, $tagsNew);
        $res        = $service->renameTags($user, $renames);

        $res2 = array(
            'old'       => $old,
            'new'       => $new,
            'items'     => count($res),
            'renames'   => $res,
        );

        return $res2;
    }

    /**************************************************************************
     * Protected helpers
     *
     */

    /** @brief  Retrieve a Connexions_Service instance.
     *  @param  name    The name of the desired service.
     *
     *  @return The Connexions_Service instance (null on failure).
     */
    protected function _service($name)
    {
        if (strpos($name, 'Service_') === false)
            $name = 'Service_'. ucfirst($name);

        return Connexions_Service::factory($name);
    }

    /** @brief  Retrieve the currently authenticated user and validate the
     *          provided API key.
     *  @param  apikey  The API key that should be associated with the
     *                  currently authenticated user.
     *
     *  @throw  Exception('Invalid apikey')
     *
     *  @return The currently authenticated user.
     */
    protected function _authenticate($apikey)
    {
        $user = Connexions::getUser();
        if (! $user->isAuthenticated())
        {
            throw new Exception('Operation prohibited for an '
                                .   'unauthenticated user.');
        }

        if ($user->apiKey !== $apikey)
        {
            throw new Exception('Invalid apikey.');
        }

        return $user;
    }
}

/** @brief  Given a string that is SUPPOSED to be JSON-encoded, clean it up in
 *          an attempt to ensure that it is valid JSON.
 *  @param  str     The string to clean.
 *
 *  Strictly valid JSON MUST have all keys and any string values quoted with
 *  " (not ').
 *
 *  @return The processed string.
 */
function cleanup_json($str)
{
    $str = urldecode($str);

    // First, extract all strings that are quoted with "
    if (preg_match_all('/("[^"]+")/', $str, $matches))
    {
        $quoted  = $matches[1];
        $nQuoted = count($quoted);
        $str     = preg_replace('/"[^"]+"/', '%x%', $str);
    }
    else
    {
        $nQuoted = 0;
    }

    // Remove all white-space around ',:{}'
    //$str = preg_replace("/\s*([,:\\{\\}])\s*/", '$1', $str);

    // Replace all unescaped single quotes with double.
    $str = preg_replace("/([^\\\\%])'/", '$1"', $str);

    // Locate all keys that are not quoted, and quote them.
    preg_match_all('/\s*[{,]\s*([^"\'%:]+):/', $str, $matches);
    foreach($matches[1] as $match)
    {
        $with = preg_replace('/\s+/', '', $match);
        $str  = preg_replace('/'. $match .':/', "\"{$with}\":", $str);
    }

    // Finally, re-insert the initial quoted values.
    $quotesUsed = 0;
    while (($quotesUsed < $nQuoted) && preg_match('/%x%/', $str))
    {
        $str = preg_replace('/%x%/', $quoted[$quotesUsed++], $str, 1);
    }

    return $str;
}
