<?php
/** @file
 *
 *  The form/javascript action entry point to the tagging class.
 */

// Set-up a different profile for the action script.
$gProfileFile = 'profile-action.txt';

require_once('lib/tagging.php');

perform_action($_REQUEST);

/** @brief  Attempt to perform a discrete action.
 *  @param  params  The parameters we were called with.
 */
function    perform_action(&$params)
{
    global  $gTagging;

    $funcId = 'perform_action';

    //printf ("%s: params{%s}<br />\n", $funcId, var_export($params,true));

    $gTagging->profile_start($funcId, "params{%s}", var_export($params,true));
    if (! empty($params['Tags']))
        $tagStr = $params['Tags'];
    else if (! empty($params['tags']))
        $tagStr = $params['tags'];

    if (! empty($tagStr))
        $tags = preg_split('#[\/,\s\+]+#', $tagStr);

    if (! empty($params['Tagger']))
    {
        /*
         * Establish the tagger that we're displaying information about.
         * MAY be different than the current user.
         */
        $gTagging->setTagger($params['Tagger']);
    }
    else
    {
        $gTagging->setTagger($gTagging->mCurUser['name']);
    }

    $userid = $gTagging->authenticatedUserId(); // $gTagging->getCurUserId();
    if ($userid < 1)
    {
        // Ignore request from unauthenticated users.
        return;
    }

    $params['Action'] = strtolower($params['Action']);
    switch ($params['Action'])
    {
    case 'page':
        /****************************************************************
         * Generic paging using Pager() and session variables.
         */
        /*echo "page:<pre>\n";
        print_r($params);
        echo "</pre>\n";*/
    
        $pager = new Pager($gTagging->mTagDB->db,
                           $params['PagerId'],
                           null,    // SQL      from session
                           null,    // renderer from session
                           null,    // perPage  from session
                           $params['Page']);
        echo $pager->pageHtml();
        break;

    case 'change_params':
        /****************************************************************
         * Change generic paging parameters.
         */
        /*echo "change_params:<pre>\n";
        print_r($params);
        print_r($_SESSION);
        echo "</pre>\n";*/
    
        switch ($params['type'])
        {
        case 'Items':
        case 'userItems':
            $order    = $params['order'];

            echo $gTagging->itemsArea(null, // users from session
                                      null, // tags  from session
                                      $order);
            break;

        case 'Tags':
        case 'itemTags':
            $display  = $params['display'];
            $order    = $params['order'];
            $limit    = $params['limit'];

            echo $gTagging->tagsArea(null,  // users from session
                                     null,  // tags  from session
                                     $display,
                                     $order,
                                     $limit);
            break;

        case 'Users':
        case 'users':
            $order    = $params['order'];

            echo $gTagging->peopleArea($order);
            break;
        }

        break;
    
    case 'create':
        /****************************************************************
         * Create a new item.
         */
        /*echo "create:<pre>\n";
        print_r($params);
        echo "</pre>\n";*/
    
        // Fall through to 'edit' since it can handle both create and modify
    
    case 'edit':
        /****************************************************************
         * Modify an item.
         */
        /*echo "edit:<pre>\n";
        print_r($params);
        echo "</pre>\n";*/
        $itemid = (int)$params['Id'];
    
        $success = true;
        if ($itemid == 0)
        {
            // This is actually the creation of a new item.
            $itemid = $gTagging->itemCreate($params['Url'],
                                            $params['Name'],
                                            $params['Description'],
                                            $params['Tags'],
                                            (int)$params['Rating'],
                                            ($params['Favorite'] == 'On'
                                                            ? true : false),
                                            ($params['Privacy']  == 'private'
                                                            ? true : false));
            if ($itemid < 1)
            {
                $success = false;
                echo "FAILURE";
            }
        }
        else
        {
            // This is a modification of an existing item.
            if (! $gTagging->itemModify($itemid,
                                        $params['Url'],
                                        $params['Name'],
                                        $params['Description'],
                                        $params['Tags'],
                                        (int)$params['Rating'],
                                        ($params['Favorite'] == 'On'
                                                            ? true : false),
                                        ($params['Privacy']  == 'private'
                                                            ? true : false)) )
            {
                $success = false;
                echo "FAILURE";
            }
        }
    
        if ($success)
        {
            $info   = $gTagging->mTagDB->userItem($userid, $itemid);
            echo $gTagging->itemHtml($info);
        }
        break;
    
    case 'delete':
        /****************************************************************
         * Delete an item or tag.
         */
        echo "delete:<pre>\n";
        print_r($params);
        echo "</pre>\n";

        switch ($params['Type'])
        {
        case 'Item':
            // Delete the item with the given identifier (for the current user)
            echo ($gTagging->itemDelete($params['Id']) ? "SUCCESS" : "ERROR");
            break;
    
        case 'Tag':
            /*
             * Delete the specified tag(s) for the item with the provided
             * identifier (0 == all items) for the current user.
             */
            break;
        }
        break;
    
    case 'favorite':
        /****************************************************************
         * Change the favorite status of an item.
         */
        /*echo "favorite:<pre>\n";
        print_r($params);
        echo "</pre>\n";*/
        $itemid = (int)$params['Id'];
        if (strtolower($params['State']) == 'off')
            $is_favorite = false;
        else
            $is_favorite = true;

        if ($params['Type'] == 'Item')
        {
            if (! $gTagging->itemChangeIndicators($itemid,
                                                  null,         // rating
                                                  $is_favorite, // favorite
                                                  null          // private
                                                  ))
            {
                echo "FAILURE";
            }
            else
            {
                $info = $gTagging->mTagDB->userItem($userid, $itemid);
                echo $gTagging->itemHtml($info);
            }
        }
        else
        {
            echo "Unknown Type '{$params['Type']}'";
        }
        break;
    
    case 'private':
        /****************************************************************
         * Change the privacy status of an item.
         */
        /*echo "private:<pre>\n";
        print_r($params);
        echo "</pre>\n";*/
        $itemid = (int)$params['Id'];
        if (strtolower($params['State']) == 'private')
            $is_private = true;
        else
            $is_private = false;

        if ($params['Type'] == 'Item')
        {
            if (! $gTagging->itemChangeIndicators($itemid,
                                                  null,         // rating
                                                  null,         // favorite
                                                  $is_private   // private
                                                  ))
            {
                echo "FAILURE";
            }
            else
            {
                $info = $gTagging->mTagDB->userItem($userid, $itemid);
                echo $gTagging->itemHtml($info);
            }
        }
        else
        {
            echo "Unknown Type '{$params['Type']}'";
        }
        break;
    
    case 'vote':
        /****************************************************************
         * Register a vote for an item.
         */
        /*
        echo "<!-- vote:";
        print_r($params);
        echo " -->\n";
        */
        $itemid = (int)$params['Id'];
        $rating = (int)$params['State'];

        /*
        echo "<!-- curUser:";
        print_r($gTagging->mCurUser);
        printf (" ... userid %u -->\n", $userid);
        */

        switch (strtolower($params['Type']))
        {
        case 'item':
            if (! $gTagging->itemChangeIndicators($itemid,
                                                  $rating,      // rating
                                                  null,         // favorite
                                                  null          // private
                                                  ))
            {
                echo "FAILURE";
            }
            else
            {
                $info = $gTagging->mTagDB->userItem($userid, $itemid);
                echo $gTagging->itemHtml($info);
            }
            break;

        case 'user':
            if (! $gTagging->mTagDB->watchlistChangeRating($userid, $itemid,
                                                           $rating))
            {
                echo "FAILURE";
            }
            else
            {
                $info = $gTagging->mTagDB->watchlistEntry($userid, $itemid);
                echo $gTagging->userHtml($info,
                                         array('icon',
                                               'iconLink',
                                               'userName',
                                               'rating',
                                               'relation'));
            }
            break;

        default:
            echo "Unknown Type '{$params['Type']}'";
            break;
        }
        break;
    
    case 'watchlist_changestatus':
    case 'watchlist':
        /****************************************************************
         * Add/Remove a user from the watchlist.
         */
        /*
        echo "<!-- watchlist:";
        print_r($params);
        echo " -->\n";
        // */

        $watchingid = $params['watchingid'];
        $status     = strtolower($params['status']);
        $type       = strtolower($params['type']);
        if (! is_numeric($watchingid))
        {
            $id = $gTagging->mTagDB->userId($watchingid);
            if ($id < 1)
            {
                //echo "FAILURE: Unknown user '$watchingid'";
                echo "FAILURE: Unknown user";
                return;
            }
            else if ($id == $userid)
            {
                echo "FAILURE: Cannot add yourself!";
                return;
            }

            $watchingid = $id;
        }

        switch ($status)
        {
        case 'add':
            $res = $gTagging->mTagDB->watchlistAdd($userid, $watchingid);
            break;
        case 'delete':
            $res = $gTagging->mTagDB->watchlistDelete($userid, $watchingid);
            break;
        }

        if ($res === false)
            echo "FAILURE on $status($userid, $watchingid)";
        else
        {
            switch ($type)
            {
            case 'user':
                $userInfo = $gTagging->mTagDB->watchlistEntry($userid,
                                                              $watchingid);
                echo $gTagging->userHtml($userInfo, 'user');
                break;

            case 'watchlist':
                $dispUserId = (int)$params['dispUserId'];
                if ($dispUserId < 1)
                    $dispUserId = $userid;
                else
                    $gTagging->setTagger($dispUserId);

                //printf ("watchlist: dispUserId[%u]<br />\n", $dispUserId);
                $watchList = $gTagging->mTagDB->watchlist($dispUserId);
                echo $gTagging->watchListArea($watchList);
                break;
            }
        }
        break;

    default:
        /*echo "UNKNOWN:<pre>\n";
        print_r($params);
        echo "</pre>\n";*/
        break;
    }

    $gTagging->profile_stop($funcId);
}

?>
