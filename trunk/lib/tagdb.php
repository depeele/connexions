<?php
/** @file
 *
 *  This is the main class for accessing the tagging database.  It provides
 *  unrestricted access to the database tables associated with tagging.
 *
 *  Access restrictions must be implemented by any routines that use this
 *  class.
 *
 *
 *  Following the tagschema suggestions by Nitin Barwankar at:
 *      - http://tagschema.com/blogs/tagschema/
 *          - 2005/05/slicing-and-dicing-data-20-part-2.html
 *          - 2005/10/many-dimensions-of-relatedness-in.html
 * 
 *  This solution uses three data tables, three association tables and one fact
 *  table as opposed to the single data table and single fact table used by
 *  freetag.
 *
 *  If it is true that SQL joins provide quick data access, then the additional
 *  association tables should remove any bottlenecks and improve performance.
 *  Particularly when one or more dimensions (user, item, tag) grow large.
 *
 *  There are three basic entities, each with its own data table:
 *      - user        userid, name, ...
 *      - tag         tagid,  name, ...
 *      - item        itemid, url,  ...
 *
 *  Each pair has an association table that may contain additional information
 *  about the association:
 *   - useritem    useritem    * -> 1 user     |   userid/itemid, name,
 *                 useritem    1 -> 1 item     |   description, rating,
 *                                             |   is_favorite, is_private
 *
 *   - usertag     usertag     * -> 1 user     |   userid/tagid,
 *                 usertag     1 -> 1 tag      |   is_favorite
 *
 *   - itemtag     itemtag     1 -> 1 tag      |   itemid/tagid
 *                 itemtag     * -> 1 item     |
 *
 *  The whole combination has a fact table that is a 3-way association:
 *   - useritemtag useritemtag * -> 1 user     |   userid/itemid/tagid
 *                 useritemtag * -> 1 item     |
 *                 useritemtag 1 -> 1 tag      |
 *
 *
 *  Given these, Nitin suggests the following (paraphrased):
 *    Let the letter i, t, and u represent a specific 'item', 'tag', and 'user'
 *    respectively.
 *    Let the upper case I, T, and U represent mappings as follows:
 *      Let U(i) be all users of item i (with id == x):
 *              SELECT u.* FROM user u, useritem ui
 *                     WHERE u.id = ui.userid AND ui.itemid = x
 *      Let U(t) be all users of tag t (with id == x):
 *              SELECT u.* FROM user u, usertag ut
 *                     WHERE u.id = ut.userid AND ut.tagid = x
 *  Similarly:
 *      Let I(u) be all items of   user u
 *      Let I(t) be all items with tag t
 *      Let T(u) be all tags  of   user u
 *      Let T(i) be all tags  of   item i
 *
 *  Now, we can combine these like T(U(t)) to be the set of all tags of all
 *  users of a single tag t (with id == x):
 *              SELECT t.* FROM tag t, usertag ut
 *                     WHERE ut.userid in
 *                      (SELECT userid from usertag where tagid = x)
 *
 *  Further, U, T, and I are idempotent.  That is, U(U()) == U()
 *
 *  Relatedness:
 *    items:
 *      I(T(i)) tag related items                   (i.e. all items of all tags
 *                                                   of item i).
 *      I(U(i)) user related items                  (i.e. all items of all
 *                                                   users of item i).
 *      U(T(i)) tag related users for item i        (i.e. users who tagged this
 *                                                   item in a similar manner -
 *                                                   the T-cluster of users for
 *                                                   item i.  All users of all
 *                                                   tags of item i).
 *      T(U(i)) user related tags for item i        (i.e. all tags of all users
 *                                                   who have tagged this item.
 *                                                   the collective tag-wisdom
 *                                                   about this item).
 *    tags:
 *      T(U(t)) user related tags                   (i.e. all tags of all users
 *                                                   that have a tag t).
 *      T(I(t)) item related tags                   (i.e. all tags of all items
 *                                                   having tag t).
 *      U(I(t)) item related user for tag t         (i.e. users who have items
 *                                                   with this tag - I-cluster
 *                                                   of users for tag t).
 *      I(U(t)) user related items for tag t        (i.e. all items of all
 *                                                   users with this tag -
 *                                                   items you might find
 *                                                   interesting if you have
 *                                                   tag t).
 *
 *    users:
 *      U(T(u)) tag related users of user u         (i.e. all users of all tags
 *                                                   of user u).
 *      U(I(u)) item related users of user u        (i.e. all users of all
 *                                                   items of user u).
 *      I(T(u)) all items with all tags of user u   (i.e. all items of all tags
 *                                                   of user u - the T-cluster
 *                                                   of items for user u).
 *      T(I(u)) all tags of all items of user u     (i.e. the I-cluster of tags
 *                                                   for user u).
 *
 */
require_once('lib/paging.php');

/** @brief  Taggable Item Database API */ 
class TagDB
{
    /** @brief  Constructor for the taggable item database instance.
     *  @param  array options   An associative array of options used (by
     *                          $this->init()) to initialize the instance.
     */ 
    function TagDB($options = NULL)
    {
        $this->init($options);
    }

    /***********************************************************************
     * Database interface
     *
     */

    /** @brief  Execute SQL and return an array of records.
     *  @param  sql     The SQL to execute.
     *
     *  @return If 'sql' is a SELECT, an array of records.  Each record will be
     *            an associative array of column values (false on error).
     *          Otherwise, a boolean true on success, false on error.
     */
    function    exec($sql)
    {
        $funcId = 'TagDB::exec';
        $this->profile_start($funcId, "sql[%s]", $sql);

        if ($this->_noexec)
        {
            echo "$sql;\n";
            $info = true;
        }
        else if (preg_match('/^\s*SELECT /i', $sql))
        {
            $info = $this->db->GetAll($sql);
            $this->profile_checkpoint($funcId, "sql SELECT DONE [%s]",
                                      ($info === false ? "failure" :
                                       sprintf ("%u records",
                                                count($info)) ));
        }
        else
        {
            // This is something simple (e.g. INSERT, UPDATE).
            $info = $this->db->_query($sql, null);

            $this->profile_checkpoint($funcId, "sql DONE [%s]",
                                      ($info === false ? "failure":"success"));
        }

        $this->profile_stop($funcId);
        return $info;
    }

    /** @brief  Given an identification, generate SQL to match it.
     *  @param  table   The database table (e.g. user, item, tag).
     *  @param  id      Item identification as an integer unique id or
     *                  an array of key/value pairs where the key
     *                  may be prefixed to indicate logical combinations:
     *                      - '|'   = OR
     *                      - '+'   = AND
     *
     *  @return An SQL string representing the given 'id' (false on error).
     */
    function id_sql($table, $id)
    {
        $sql = '';
        if (is_array($id))
        {
            // Array = key/value pairs, possibly with prefixed keys
            foreach ($id as $key => $value)
            {
                $prefix = substr($key, 0, 1);
                switch ($prefix)
                {
                case '|':   $combine = 'OR';  $key = substr($key, 1);   break;
                case '+':   $combine = 'AND'; $key = substr($key, 1);   break;
                default:    $combine = 'AND';
                }

                if (! empty($sql))
                    $sql  .= " {$combine} ";

                $sql .= "{$key} = " . $this->quote($value);
            }
        }
        else if (is_numeric($id))
        {
            // Integer = unique identifier (only valid for 'user', 'item', and
            // 'tag' tables)
            switch ($table)
            {
            case 'user': $sql .= sprintf("userid = %u",$id);    break;
            case 'item': $sql .= sprintf("itemid = %u",$id);    break;
            case 'tag':  $sql .= sprintf("tagid  = %u",$id);    break;
            default:
                return false;
            }
        }
        else
        {
            // String - Normalize and quote (only valid for 'user', 'item',
            // and 'tag' tables)
            $id = $this->quote(strtolower($id));

            switch ($table)
            {
            case 'user': $sql .= "name = $id";          break;
            case 'item': $sql .= "(url = $id) OR ".
                                 "(md5(url) = $id)";    break;
            case 'tag':  $sql .= "tag = $id";           break;
            default:
                return false;
            }
        }

        return ($sql);
    }

    /** @brief  Retrieve basic data about the specified item.
     *  @param  table   The database table (e.g. user, item, tag).
     *  @param  id      Item identification as an integer unique id or
     *                  an array of key/value pairs where the key
     *                  may be prefixed to indicate logical combinations:
     *                      - '|'   = OR
     *                      - '+'   = AND
     *  @param  order   The SQL sort order.
     *  @param  limit   An associative array of ('offset' => offset,
     *                                           'count'  => count)
     *
     *  @return An associative array of information about the data
     *          (false on error).
     */
    function    get($table, $id, $order = null, $limit = null)
    {
        $funcId = 'TagDB::get';
        /*$this->profile_start($funcId,
                             "table[%s], id[%s], order[%s], limit[%s]",
                             $table, var_export($id, true),
                             $order, var_export($limit,true));*/
        $prefix = $this->_table_prefix;

        $sql = "SELECT * FROM {$prefix}{$table} WHERE";

        $idSql = $this->id_sql($table, $id);
        if ($idSql === false)
            return false;
        $sql .= " $idSql";

        if (is_string($order))
        {
            $sql .= " ORDER BY " . $order;
        }

        if (is_array($limit))
        {
            /* :NOTE: If count is undefined (or < 1), should that mean
             *        all records from 'offset' on, or just 1 record?
             */
            $offset = isset($limit['offset']) ? $limit['offset'] : $limit[0];
            $count  = isset($limit['count'])  ? $limit['count']  : $limit[1];

            if (is_int($offset) && ($count > 0))
                $sql .= sprintf(" LIMIT %u,%u", $offset, $count);
            else if ($count > 0)
                $sql .= sprintf(" LIMIT %u", $count);
        }

        //printf ("%s: sql[%s]<br />\n", $funcId, $sql);
        $info = $this->exec($sql);

        //$this->profile_stop($funcId, "info[%s]", var_export($info, true));
        return $info;
    }

    /** @brief  Given SQL that generates a 'count' column, return the value
     *          of the 'count' for the first row.
     *  @param  sql     The SQL.
     *
     *  NOTE: If the provided SQL does NOT define a 'count' column, return
     *        the count of records retrieved.
     *
     *  :TODO: Take ANY SQL query and convert it to one that retrieves
     *         COUNT(*) [as is done in Pager()).
     *
     *  @return The count (false on error).
     */
    function get_count($sql)
    {
        $funcId = 'TagDB::get_count';
        //$this->profile_start($funcId, "sql[%s]", $sql);

        /********************************************************************
         * Since the SQL may return more than one row (e.g. if it includes a
         * GROUP BY), retrieve all returned rows and sum them.
         */
        //$countRecs = $this->db->GetOne($sql);
        $countRows = $this->db->GetCol($sql);
        $countRecs = 0;
        foreach ($countRows as $idex => $count)
        {
            $countRecs += (int)$count;
        }
        return ($countRecs);
    }

    /** @brief  Create a new basic data item.
     *  @param  table   The database table (user, item, tag).
     *  @param  info    An associative array of item data.
     *
     *  @return The unique identifier of the new (or existing) item
     *          (false on error).
     */
    function    add($table, $info)
    {
        $funcId = 'TagDB::add';
        //$this->profile_start($funcId, "table[%s]",$table);
                             //, info[%s]", $table, var_export($info, true));

        unset($id);
        // String - Normalize and quote
        if (is_numeric($info['id']))
        {
            // Integer = unique identifier
            $id    = (int)$info['id'];
            $idKey = 'id';
        }
        else
        {
            // Table-specific unique identifer OR a
            // string that should be normalized and quoted
            switch ($table)
            {
            case 'user':
                $idKey = 'userid';
                if (is_numeric($info['userid']))
                    $id = (int)$info['userid'];
                else
                    $id = strtolower($info['name']);
                break;
            case 'item':
                $idKey = 'itemid';
                if (is_numeric($info['itemid']))
                    $id = (int)$info['itemid'];
                else
                    $id = strtolower($info['url']);
                break;
            case 'tag':
                $idKey = 'tagid';
                if (is_numeric($info['tagid']))
                    $id = (int)$info['tagid'];
                else
                    $id = strtolower($info['tag']);
                break;
            }
        }

        if (false)  //isset($id))
        {
            /*
             * Should we automatically do this check every time??
             *
             * See if a matching record already exists...
             */
            if ($this->_noexec)
            {
                if ($this->_uid[$table][$id] > 0)
                {
                    $id = $this->_uid[$table][$id];

                    //$this->profile_stop($funcId, "id[%u]", $id);
                    return ($id);
                }
            }
            else
            {
                // See if a record with this id already exists.
                $curInfo = $this->get($table, $id, null, array('count'=>1));
                if (is_array($curInfo) && (count($curInfo) == 1))
                {
                    $id = $curInfo[0][$idKey];

                    //$this->profile_stop($funcId, "%s[%u]", $idKey, $id);
                    return ($id);
                }
            }
        }

        /*
         * Add a record for this new item.
         */
        $prefix = $this->_table_prefix;

        // Parse the key/value pairs in 'info'
        $keys   = '';
        $values = '';
        foreach ($info as $key => $value)
        {
            if (! empty($keys))
            {
                $keys   .= ', ';
                $values .= ', ';
            }

            $keys   .= $key;
            $values .= $this->quote($value);
        }

        $sql = "INSERT INTO {$prefix}{$table} ({$keys}) VALUES({$values})";

        $id = false;
        //if ($this->exec($sql))
        if ($this->db->_query($sql, null))
        {
            if ($this->_noexec)
            {
                $insertId = $this->_uid[$table]['##_id_##']++;

                $this->_uid[$table][$id] = $insertId;
                $id                      = $insertId;
            }
            else
            {
                $id = (int)$this->db->Insert_ID();
            }
        }

        //$this->profile_stop($funcId, "id[%u]", $id);
        return ($id);
    }

    /** @brief  Update an existing basic data item.
     *  @param  table   The database table (user, item, tag).
     *  @param  id      The item identfier.
     *  @param  info    An associative array of item data.
     *
     *  @return  boolean true on success, false on failure.
     */
    function    update($table, $id, $info)
    {
        $funcId = 'TagDB::update';
        /*$this->profile_start($funcId, "table[%s], id[%s], info[%s]",
                             $table,
                             var_export($id, true), var_export($info, true));*/

        // This information does not exist.  Add it now.
        $prefix = $this->_table_prefix;

        $keys = $this->id_sql($table, $id);
        if ($idSql === false)
            return false;

        $values = '';
        foreach ($info as $key => $value)
        {
            if (! empty($values))
                $values .= ', ';

            $values .= "$key = " . $this->quote($value);
        }

        $sql = "UPDATE {$prefix}{$table} SET {$values} WHERE {$keys}";
        $res = $this->db->_query($sql, null);

        //$this->profile_stop($funcId);
        return ($res);
    }

    /** @brief  Delete a basic data item.
     *  @param  table   The database table (user, item, tag).
     *  @param  id      The item identfier.
     *
     *  @return  boolean true on success, false on failure.
     */
    function    delete($table, $id)
    {
        $funcId = 'TagDB::delete';
        /*$this->profile_start($funcId, "table[%s], id[%s]",
                      $table, var_export($id, true));*/

        $prefix = $this->_table_prefix;

        $sql = "DELETE FROM {$prefix}{$table} WHERE";

        $idSql = $this->id_sql($table, $id);
        if ($idSql === false)
            return false;
        $sql .= " $idSql";

        $res = $this->db->_query($sql, null);
        
        //$this->profile_stop($funcId);
        return ($res);
    }

    /**************************************************************************
     * Tag Schema, cross-table queries
     *
     */
    
    /** @brief  All users of the given item(s) -- U(i)
     *  @param  items   An array of item ids (or a single itemid).
     *
     *  @return An array of user ids.
     */
    function usersOfItems($items)
    {
        $funcId = 'TagDB::usersOfItems';
        $prefix = $this->_table_prefix;

        if ( (! is_array($items)) && is_numeric($items) )
        {
            // Normalize this single number into an array of containing a
            // single itemid
            $items = array((int)$items);
        }
        if ( (! is_array($items)) || (count($items) < 1) )
            return array();

        $sql = "SELECT u.userid FROM {$prefix}user u, {$prefix}useritem ui"
                . " WHERE (ui.itemid IN (". implode(',',$items) ."))"
                . "   AND (u.userid = ui.userid)"
                . "     GROUP BY u.userid";

        //printf ("%s: sql[%s]\n", $funcId, $sql);
        $ids = $this->db->GetCol($sql);
        return ($ids);
    }

    /** @brief  All users of the given tag(s) -- U(t)
     *  @param  tags    An array of tag ids.
     *
     *  @return An array of user ids.
     */
    function usersOfTags($tags)
    {
        $funcId = 'TagDB::usersOfTags';
        $prefix = $this->_table_prefix;

        if (! is_array($tags))
            return array();

        $sql = "SELECT u.userid FROM {$prefix}user u, {$prefix}usertag ut"
                . " WHERE (ut.tagid IN (". implode(',',$tags) ."))"
                . "   AND (u.userid = ut.userid)"
                . "     GROUP BY u.userid";

        //printf ("%s: sql[%s]\n", $funcId, $sql);
        $ids = $this->db->GetCol($sql);
        return ($ids);
    }

    /** @brief  All items of the given user(s) -- I(u)
     *  @param  users   An array of user ids (or a single userid).
     *
     *  @return An array of item ids.
     */
    function itemsOfUsers($users)
    {
        $funcId = 'TagDB::itemsOfUsers';
        $prefix = $this->_table_prefix;

        if (! is_array($users))
        {
            if (is_numeric($users))
            {
                // Normalize this single number into an array of containing a
                // single userid
                $users = array((int)$users);
            }
            else
                return array();
        }

        $sql = "SELECT i.itemid FROM {$prefix}item i, {$prefix}useritem ui"
                . " WHERE (ui.userid IN (". implode(',',$users) ."))"
                . "   AND (i.itemid = ui.itemid)"
                . "     GROUP BY i.itemid";

        //printf ("%s: sql[%s]\n", $funcId, $sql);
        $ids = $this->db->GetCol($sql);
        return ($ids);
    }

    /** @brief  All items with the given tag(s) -- I(t)
     *  @param  tags    An array of tag ids.
     *
     *  @return An array of item ids.
     */
    function itemsOfTags($tags)
    {
        $funcId = 'TagDB::itemsOfTags';
        $prefix = $this->_table_prefix;

        if (! is_array($tags))
            return array();

        $sql = "SELECT i.itemid FROM {$prefix}item i, {$prefix}itemtag it"
                . " WHERE (it.tagid IN (". implode(',',$tags) ."))"
                . "   AND (i.itemid = it.itemid)"
                . "     GROUP BY i.itemid";

        //printf ("%s: sql[%s]\n", $funcId, $sql);
        $ids = $this->db->GetCol($sql);
        return ($ids);
    }

    /** @brief  All tags of the given user(s) -- T(u)
     *  @param  users   An array of user ids (or a single userid).
     *
     *  @return An array of tag ids.
     */
    function tagsOfUsers($users)
    {
        $funcId = 'TagDB::tagsOfUsers';
        $prefix = $this->_table_prefix;

        if (! is_array($users))
        {
            if (is_numeric($users))
            {
                // Normalize this single number into an array of containing a
                // single userid
                $users = array((int)$users);
            }
            else
                return array();
        }

        $sql = "SELECT t.tagid FROM {$prefix}tag t, {$prefix}usertag ut"
                . " WHERE (ut.userid IN (". implode(',',$users) ."))"
                . "   AND (t.tagid = ut.tagid)"
                . "     GROUP BY t.tagid";

        //printf ("%s: sql[%s]\n", $funcId, $sql);
        $ids = $this->db->GetCol($sql);
        return ($ids);
    }

    /** @brief  All tags of the given item(s) -- T(i)
     *  @param  items   An array of item ids (or a single itemid).
     *
     *  @return An array of tag ids.
     */
    function tagsOfItems($items)
    {
        $funcId = 'TagDB::tagsOfItems';
        $prefix = $this->_table_prefix;

        if ( (! is_array($items)) && is_numeric($items) )
        {
            // Normalize this single number into an array of containing a
            // single userid
            $items = array((int)$items);
        }
        if ( (! is_array($items)) || (count($items) < 1) )
            return array();

        $sql = "SELECT t.tagid FROM {$prefix}tag t, {$prefix}itemtag it"
                . " WHERE (it.itemid IN (". implode(',',$items) ."))"
                . "   AND (t.tagid = it.tagid)"
                . "     GROUP BY t.tagid";

        //printf ("%s: sql[%s]\n", $funcId, $sql);
        $ids = $this->db->GetCol($sql);
        return ($ids);
    }

    /** @brief  All items of user(s) with tag(s)
     *  @param  users       An array of user ids (or a single userid,
     *                      null == all users).
     *  @param  tags        An array of tag ids (null == no tag limits).
     *
     *  @return An array of item ids.
     */
    function items($users, $tags)
    {
        $funcId = 'TagDB::items';
        $prefix = $this->_table_prefix;

        if ( (! is_array($users)) && is_numeric($users) )
        {
            // Normalize this single number into an array of containing a
            // single userid
            $users = array((int)$users);
        }
        $haveUsers = (is_array($users) && (count($users) > 0));
        $haveTags  = (is_array($tags)  && (count($tags)  > 0));

        $sql = "SELECT i.itemid FROM {$prefix}item i";
        
        if ($haveUsers || $haveTags)
            $sql .= ", {$prefix}usertagitem uti"
                    . " WHERE (i.itemid = uti.itemid)";

        if ($haveUses)
            $sql .= " AND (uti.userid IN (". implode(',',$users) ."))";

        if ($haveTags)
            $sql .= " AND (uti.tagid  IN (". implode(',',$tags) ."))";

        if ($haveUsers || $haveTags)
            $sql .= " GROUP BY uti.itemid";

        if ($haveTags)
            $sql .= " HAVING COUNT(DISTINCT uti.tagid) = ". count($tags);

        //printf ("%s: sql[%s]\n", $funcId, $sql);
        $ids = $this->db->GetCol($sql);
        return ($ids);
    }

    /** @brief  All tags of user(s) with item(s)
     *  @param  users   An array of user ids (or a single userid,
     *                  null == all users).
     *  @param  items   An array of item ids (or a single itemid,
     *                  null == all items).
     *
     *  @return An array of tag ids
     */
    function tags($users, $items)
    {
        $funcId = 'TagDB::tags';
        $prefix = $this->_table_prefix;

        if ( (! is_array($users)) && is_numeric($users) )
        {
            // Normalize this single number into an array of containing a
            // single userid
            $users = array((int)$users);
        }
        if ( (! is_array($items)) && is_numeric($items) )
        {
            // Normalize this single number into an array of containing a
            // single itemid
            $items = array((int)$items);
        }

        $sql = "SELECT uti.tagid FROM {$prefix}item i, {$prefix}usertagitem uti"
                . " WHERE (i.itemid = uti.itemid)";
        if (is_array($users) && (count($users) > 0))
            $sql .= " AND (uti.userid IN (". implode(',',$users) ."))";

        if (is_array($items) && (count($items) > 0))
            $sql .= " AND (uti.itemid  IN (". implode(',',$items) ."))";

        $sql .= " GROUP BY uti.tagid";

        //printf ("%s: sql[%s]\n", $funcId, $sql);
        //$ids = $this->exec($sql);
        $ids = $this->db->GetCol($sql);
        return ($ids);
    }

    /**************************************************************************
     * User Item, Tag, User, and Watchlist retrievals
     *
     */

    /** @brief  Given a user name, return the matching userid.
     *  @param  name    The user name.
     *
     *  @return The userid (false on failure).
     */
    function userId($name)
    {
        $funcId = 'TagDB::userId';

        $info = $this->get('user', $name, null, array('count'=>1));
        if (is_array($info) && (count($info) == 1))
        {
            $id = (int)$info[0]['userid'];
        }
        else
        {
            $id = false;
        }

        return ($id);
    }

    /** @brief  The user item for the given user and itemid.
     *  @param  userid  The user identifier.
     *  @param  id      The id of the desired item:
     *                      - if this is a string, retrieve by matching the
     *                          URL or an MD5 of the URL,
     *                      - if this is numeric, retrieve by itemid.
     *
     *  @return An associative array of user item information (false on error):
     *              - itemid            item/useritem
     *              - url               item
     *              - userCount         item
     *              - ratingCount       item
     *              - ratingSum         item
     *              - userid            user/useritem
     *              - userName          user [name]
     *              - fullname          user
     *              - email             user
     *              - pictureUrl        user
     *              - profile           user
     *              - lastVisit         user
     *              - totalTags         user
     *              - totalItems        user
     *              - name              useritem
     *              - description       useritem
     *              - rating            useritem
     *              - is_favorite       useritem
     *              - is_private        useritem
     *              - tagged_on         useritem
     */
    function userItem($userid, $id)
    {
        $funcId = 'TagDB::userItem';
        $prefix = $this->_table_prefix;

        if ($userid < 1)
            return false;

        $this->profile_start($funcId, "userid[%u], itemid[%s]",
                             $userid, $id);

        $item = $this->item($id);
        if (! is_array($item))
        {
            // Unknown item
            $this->profile_stop($funcId, "Unknown item id[%s]", $id);
            return false;
        }
        $itemid = (int)$item['itemid'];


        $sql = "SELECT i.*, u.*, u.name as userName, ui.*"
                . " FROM {$prefix}item i,{$prefix}user u,{$prefix}useritem ui"
                . " WHERE (i.itemid = $itemid)"
                .   " AND (u.userid = $userid)"
                .   " AND (i.itemid = ui.itemid)"
                .   " AND (u.userid = ui.userid)";

        $this->profile_checkpoint($funcId, "sql[%s]", $sql);
        /*printf ("%s: sql[%s]\n", $funcId, $sql);
        flush();*/
        $res = $this->db->GetRow($sql);

        $this->profile_stop($funcId, "res[%s]", var_export($res,true));
        return ($res);
    }

    /** @brief  All user items for user(s) and having all tag(s)
     *  @param  users       An array of user ids (or a single userid,
     *                      null == all users).
     *  @param  tags        An array of tag ids  (null == all tags).
     *  @param  curUser     The id of the current, authenticated user (-1 if
     *                      none).
     *  @param  orderBy     The desired sort order.
     *                          (e.g. 'itemid ASC',  [DEFAULT]
     *                                'rating ASC',
     *                                'tagged_on DESC')
     *  @param  perPage     The number of records per page.
     *  @param  renderer    A rendering callback of the form:
     *                          renderer($pager, $items) returns HTML
     *
     *  @return - If 'perPage' < 1, an array of associative arrays representing
     *            all matching items:
     *              - itemid            item/useritem
     *              - url               item
     *              - userCount         item
     *              - ratingCount       item
     *              - ratingSum         item
     *              - userid            user/useritem
     *              - userName          user [name]
     *              - fullname          user
     *              - email             user
     *              - pictureUrl        user
     *              - profile           user
     *              - lastVisit         user
     *              - totalTags         user
     *              - totalItems        user
     *              - name              useritem
     *              - description       useritem
     *              - rating            useritem
     *              - is_favorite       useritem
     *              - is_private        useritem
     *              - tagged_on         useritem
     *          - If 'perPage' > 0, a paginated set of items:
     *              $pageCount = $pager->PageCount();
     *              $itemCount = $pager->RecordCount();
     *              do
     *              {
     *                  $items   = $pager->GetPage();
     *                  $pageNum = $pager->PageNum();
     *
     *              } while ($items !== null);
     */
    function userItems($users, $tags,
                       $curUser     = -1,
                       $orderBy     = 'itemid ASC',
                       $perPage     = 20,
                       $renderer    = null)
    {
        $funcId = 'TagDB::userItems';
        $prefix = $this->_table_prefix;

        if ( (! is_array($users)) && is_numeric($users) )
        {
            // Normalize this single number into an array of containing a
            // single userid
            $users = array((int)$users);
        }

        $sql = "SELECT i.*, u.*, u.name as userName, ui.*"
                . " FROM {$prefix}item i,{$prefix}user u,{$prefix}useritem ui";
        
        if (is_array($tags) && (count ($tags) > 0) )
            $sql .= ", usertagitem uti";

        $sql .= " WHERE (i.itemid = ui.itemid)"
                . " AND (u.userid = ui.userid)";

        if (is_array($users) && (count($users) > 0) )
            $sql .= " AND (ui.userid IN (". implode(',',$users) ."))";

        if (is_array($tags) && (count ($tags) > 0) )
            $sql .= " AND (i.itemid  = uti.itemid)"
                  . " AND (ui.userid = uti.userid)"
                  . " AND (uti.tagid IN (". implode(',',$tags) ."))";

        // Filter out any private items that don't belong to the current user.
        $sql .= " AND ((ui.is_private = false)";
        if ($curUser > 0)
            $sql .= " OR (ui.userid = {$curUser})";
        $sql .= ")";

        $sql .= " GROUP BY ui.userid,ui.itemid";

        if (is_array($tags) && (count ($tags) > 0) )
            $sql .= " HAVING (COUNT(DISTINCT uti.tagid) = ". count($tags) .")";

        if ($orderBy !== null)
            $sql .= " ORDER BY {$orderBy}";

        /*printf ("%s: sql[%s]\n", $funcId, $sql);
        flush();*/
        if ($perPage > 0)
            $res = new Pager($this->db, 'Items', $sql, $renderer, $perPage);
        else
            $res = $this->db->GetAll($sql);

        return ($res);
    }

    /** @brief  All user items for the given itemid.
     *  @param  users       An array of user ids (or a single userid,
     *                      null == all users).
     *  @param  id          The item identifier:
     *                          - if this is a string, retrieve by matching the
     *                              URL or an MD5 of the URL,
     *                          - if this is numeric, retrieve by itemid.
     *  @param  curUser     The id of the current, authenticated user (-1 if
     *                      none).
     *  @param  orderBy     The desired sort order.
     *                          (e.g. 'itemid ASC',  [DEFAULT]
     *                                'rating ASC',
     *                                'tagged_on DESC')
     *  @param  perPage     The number of records per page.
     *  @param  renderer    A rendering callback of the form:
     *                          renderer($pager, $items) returns HTML
     *
     *  @return - If 'perPage' < 1, an array of associative arrays representing
     *            all matching items:
     *              - itemid            item/useritem
     *              - url               item
     *              - userCount         item
     *              - ratingCount       item
     *              - ratingSum         item
     *              - userid            user/useritem
     *              - userName          user [name]
     *              - fullname          user
     *              - email             user
     *              - pictureUrl        user
     *              - profile           user
     *              - lastVisit         user
     *              - totalTags         user
     *              - totalItems        user
     *              - name              useritem
     *              - description       useritem
     *              - rating            useritem
     *              - is_favorite       useritem
     *              - is_private        useritem
     *              - tagged_on         useritem
     *          - If 'perPage' > 0, a paginated set of items:
     *              $pageCount = $pager->PageCount();
     *              $itemCount = $pager->RecordCount();
     *              do
     *              {
     *                  $items   = $pager->GetPage();
     *                  $pageNum = $pager->PageNum();
     *
     *              } while ($items !== null);
     */
    function userItemsForId($users,
                            $id,
                            $curUser     = -1,
                            $orderBy     = 'itemid ASC',
                            $perPage     = 20,
                            $renderer    = null)
    {
        $funcId = 'TagDB::userItemsForId';
        $prefix = $this->_table_prefix;

        if ( (! is_array($users)) && is_numeric($users) )
        {
            // Normalize this single number into an array of containing a
            // single userid
            $users = array((int)$users);
        }

        $item = $this->item($id);
        if (! is_array($item))
        {
            // Unknown item
            /*printf ("<p><b>%s</b>: Unknown item for id[%s]</p>\n",
                    $funcId, $id);*/
            return false;
        }
        $itemid = (int)$item['itemid'];


        $sql = "SELECT i.*, u.*, u.name as userName, ui.*"
                . " FROM {$prefix}item i,{$prefix}user u,{$prefix}useritem ui";
        
        $sql .= " WHERE (i.itemid = $itemid)"
                . " AND (i.itemid = ui.itemid)"
                . " AND (u.userid = ui.userid)";

        if (is_array($users) && (count($users) > 0) )
            $sql .= " AND (ui.userid IN (". implode(',',$users) ."))";

        // Filter out any private items that don't belong to the current user.
        $sql .= " AND ((ui.is_private = false)";
        if ($curUser > 0)
            $sql .= " OR (ui.userid = {$curUser})";
        $sql .= ")";

        $sql .= " GROUP BY ui.userid,ui.itemid";

        if ($orderBy !== null)
            $sql .= " ORDER BY {$orderBy}";

        /*printf ("%s: sql[%s]\n", $funcId, $sql);
        flush();*/
        if ($perPage > 0)
            $res = new Pager($this->db, 'Items', $sql, $renderer, $perPage);
        else
            $res = $this->db->GetAll($sql);

        return ($res);
    }

    /** @brief  Retrieve a count of the number of users.
     *
     *  @return A count of the number of users.
     */
    function userCount()
    {
        $funcId = 'TagDB::userCount';
        $prefix = $this->_table_prefix;

        $sql = "SELECT COUNT(*) AS count FROM {$prefix}user";
        $res = $this->get_count($sql);

        return $res;
    }

    /** @brief  Retrieve a count of the number of users that have posted at
     *          least one item.
     *
     *  @return A count of the number of contributing users.
     */
    function userCountContributors()
    {
        $funcId = 'TagDB::userCountContributors';
        $prefix = $this->_table_prefix;

        $sql = "SELECT COUNT(DISTINCT userid) AS count FROM {$prefix}useritem";
        $res = $this->get_count($sql);

        return $res;
    }

    /** @brief  Fill in any missing information from a user record.
     *  @param  info    The current user information.
     *
     *  @return An associative array of user information.
     */
    function userNormalize(&$info)
    {
        if ( (! is_array($info)) || (count($info) < 1) )
            return false;

        if (empty($info['email']))
        {
            /* Construct an email address assuming that the name is
             * 'uid'.'email domain'
             */
            $info['email']  = preg_replace('/\./', '@', $info['name'], 1);
            $info['update'] = true; // Tell everyone that this record needs
                                    // to be updated...
        }
        if (empty($info['uid']))
        {
            /* Retrieve the uid assuming that the name is
             * 'uid'.'email domain'
             */
            $info['uid'] = preg_replace('/\..*/', '', $info['name']);
        }

        global  $gBaseUrl;
        if (empty($info['pictureUrl']))
        {
            // Use the default user picture
            $info['pictureUrl'] = "{$gBaseUrl}/images/user.png";
        }
        else
        {
            // If this is a local URL, make sure it's absolute.
            if (! preg_match('#^(http|/)#', $info['pictureUrl']))
            {
                $info['pictureUrl'] = $gBaseUrl ."/". $info['pictureUrl'];
            }
        }

        return $info;
    }

    /** @brief  Retrieve information about user(s)
     *  @param  users       The user(s) to retrieve:
     *                          - array of userids;
     *                          - array of user names;
     *                          - single userid;
     *                          - single user name;
     *                          - null == all users.
     *  @param  orderBy     How the results should be ordered:
     *                          (e.g. 'name ASC',   [DEFAULT]
     *                                'email DESC')
     *  @param  renderer    A rendering callback of the form:
     *                          renderer($pager, $items) returns HTML
     *  @param  perPage     The number of records per page.
     *
     *  @return - If 'perPage' < 1, an array of associative arrays representing
     *            all matching users:
     *              - userid
     *              - name
     *              - fullName
     *              - email
     *              - pictureUrl
     *              - profile
     *              - lastVisit
     *              - totalTags
     *              - totalItems
     *          - If 'perPage > 0, a paginated set of users:
     *              $pageCount = $pager->PageCount();
     *              $userCount = $pager->RecordCount();
     *              do
     *              {
     *                  $users   = $pager->GetPage();
     *                  $pageNum = $pager->PageNum();
     *
     *              } while (Rusers !== null);
     */
    function users($users       = null,
                   $orderBy     = 'name ASC',
                   $renderer    = null,
                   $perPage     = 20)
    {
        $prefix = $this->_table_prefix;

        $by = 'userid';
        if ( ($users !== null) && (! is_array($users)) )
        {
            // Either a single numeric userid or a user name.
            if (is_numeric($users))
            {
                $users = array((int)$users);
            }
            else
            {
                $users = array($users);
                $by    = 'name';
            }
        }
        else if (count($users) > 0)
        {
            // Array of userids or user names.
            if (! is_numeric($users[0]))
                // Treat this as an array of user names (else userids)
                $by = 'name';
        }
        else
            $users = null;

        if ( ($users != null) && ($by === 'name') )
        {
            // Quote all user names
            foreach ($users as $idex => $name)
            {
                $users[$idex] = $this->quote($name);
            }
        }

        $sql = "SELECT * FROM {$prefix}user";

        if (is_array($users))
            $sql .= " WHERE $by IN (". implode(',', $users) .")";

        if (! empty($orderBy))
            $sql .= " ORDER BY $orderBy";

        if ($perPage > 0)
            $res = new Pager($this->db, 'Users', $sql, $renderer, $perPage);
        else
            $res = $this->db->GetAll($sql);

        return ($res);
    }

    /** @brief  Retrieve information about a single user
     *  @param  id  The identifier of the desired user:
     *                  - numeric userid;
     *                  - user name;
     *
     *  @return An associative array of user information (false if no match):
     *              - userid
     *              - name
     *              - fullName
     *              - email
     *              - pictureUrl
     *              - profile
     *              - lastVisit
     *              - totalTags
     *              - totalItems
     */
    function user($id)
    {
        $funcId = 'TagDB::user';

        $prefix = $this->_table_prefix;

        $by = 'userid';
        if (! is_numeric($id))
        {
            $by = 'name';
            $id = $this->quote($id);
        }

        $sql = "SELECT * FROM {$prefix}user"
              ." WHERE $by = $id"
              ." ORDER BY $by ASC";

        $res = $this->userNormalize($this->db->GetRow($sql));
        return ($res);
    }

    /** @brief  All tags for item(s) tagged by user(s)
     *  @param  users       An array of user ids (or a single userid,
     *                      null == all users).
     *  @param  items       An array of item ids (or a single itemid,
     *                      null == all items).
     *  @param  orderby     The desired sort order.
     *                          (e.g. 'tag ASC',  [DEFAULT]
     *                                'userCount ASC')
     *  @param  limit       The number of tags to display (0 == all)
     *
     *  @return An associative arrays representing the tags (false on failure):
     *              - tagid             tag
     *              - tag               tag
     *              - itemCount         usertagitem
     *              - uniqueItemCount   usertagitem
     *              - userCount         usertagitem
     */
    function itemTags($users, $items,
                      $orderBy      = 'tag ASC',
                      $limit        = 0)
    {
        $funcId = 'TagDB::itemTags';
        $prefix = $this->_table_prefix;

        if ( (! is_array($users)) && is_numeric($users) )
        {
            // Normalize this single number into an array of containing a
            // single userid
            $users = array((int)$users);
        }
        if ( (! is_array($items)) && is_numeric($items) )
        {
            // Normalize this single number into an array of containing a
            // single itemid
            $items = array((int)$items);
        }

        $sql = "SELECT t.*,COUNT(DISTINCT uti.itemid,uti.userid) AS itemCount, "
                .         "COUNT(DISTINCT uti.itemid) AS uniqueItemCount, "
                .         "COUNT(DISTINCT uti.userid) AS userCount"
                . " FROM {$prefix}tag t, {$prefix}usertagitem uti"
                . " WHERE (t.tagid = uti.tagid)";

        if (is_array($users) && (count($users) > 0) )
            $sql .= " AND (uti.userid IN (". implode(',',$users) ."))";

        if (is_array($items) && (count ($items) > 0) )
            $sql .= " AND (uti.itemid IN (". implode(',',$items) ."))";

        $sql .= " GROUP BY t.tagid";

        if ($orderBy !== null)
            $sql .= " ORDER BY {$orderBy}";

        if ($limit > 0)
            $sql .= sprintf(" LIMIT %u", $limit);

        //printf ("%s: sql[%s]\n", $funcId, $sql);
        $itemTags = $this->db->GetAll($sql);
        return ($itemTags);
    }

    /** @brief  All tags for user(s)
     *  @param  users       An array of user ids (or a single userid,
     *                      null == all users).
     *  @param  orderby     The desired sort order.
     *                          (e.g. 'tag ASC',  [DEFAULT]
     *                                'userCount ASC')
     *  @param  limit       The number of tags to display (0 == all)
     *
     *  @return An associative arrays representing the tags (false on failure):
     *              - tagid             tag
     *              - tag               tag
     *              - itemCount         usertagitem
     *              - uniqueItemCount   usertagitem
     *              - userCount         usertagitem
     */
    function userTags($users,
                      $orderBy      = 'tag ASC',
                      $limit        = 0)
    {
        $funcId = 'TagDB::userTags';
        $prefix = $this->_table_prefix;

        if ( (! is_array($users)) && is_numeric($users) )
        {
            // Normalize this single number into an array of containing a
            // single userid
            $users = array((int)$users);
        }

        $sql = "SELECT t.*,COUNT(DISTINCT uti.itemid,uti.userid) AS itemCount, "
                .         "COUNT(DISTINCT uti.itemid) AS uniqueItemCount, "
                .         "COUNT(DISTINCT uti.userid) AS userCount"
                . " FROM {$prefix}tag t, {$prefix}usertagitem uti"
                . " WHERE (t.tagid = uti.tagid)";

        if (is_array($users) && (count($users) > 0) )
            $sql .= " AND (uti.userid IN (". implode(',',$users) ."))";

        $sql .= " GROUP BY t.tagid";

        if ($orderBy !== null)
            $sql .= " ORDER BY {$orderBy}";

        if ($limit > 0)
            $sql .= sprintf(" LIMIT %u", $limit);

        //printf ("%s: sql[%s]\n", $funcId, $sql);
        $userTags = $this->db->GetAll($sql);
        return ($userTags);
    }

    /** @brief  Given a set of tags, retrieve their ids.
     *  @param  tags    The set of tags.  Either a [/+,]-separated string
     *                  or an array of strings.
     *
     *  @return An array of tag ids.
     */
    function tagIds($tags)
    {
        $funcId = 'TagDB::tagIds';
        $prefix = $this->_table_prefix;

        if (is_string($tags))
            $tags = $this->tagStr2Array($tags);
        else if (! is_array($tags))
            return array();

        $sql = "SELECT tagid FROM {$prefix}tag WHERE tag IN (";

        // Construct the list of normalized, quoted tags
        for ($idex = 0; $idex < count($tags); $idex++)
        {
            if ($idex > 0)  $sql .= ",";
            $sql .= $this->quote(strtolower($tags[$idex]));
        }
        $sql .= ")";

        $ids = $this->db->GetCol($sql);
        return ($ids);
    }

    /** @brief  Given a set of tag ids, retrieve their names.
     *  @param  tags    The set of tag ids.
     *
     *  @return An array of tag names.
     */
    function tagNames($tags)
    {
        $funcId = 'TagDB::tagNames';
        $prefix = $this->_table_prefix;

        if (count($tags) < 1)
            return array();
        $prefix = $this->_table_prefix;

        $sql = "SELECT tag FROM {$prefix}tag WHERE tagid IN (" .
                    implode(',', $tags) . ')'
               . " ORDER BY tag";

        $names = $this->db->GetCol($sql);
        if (! is_array($names))
            $names = array();

        return ($names);
    }

    /** @brief  Tag details for all provided tag identifiers.
     *  @param  tags        An array of tag ids.
     *  @param  orderBy     The desired sort order.
     *                          (e.g. 'tag ASC',  [DEFAULT]
     *                                'userCount ASC')
     *  @param  limit       The number of tags to display (0 == all)
     *
     *  @return An associative arrays representing the tags (false on failure):
     *              - tagid             tag
     *              - tag               tag
     *              - itemCount         usertagitem
     *              - uniqueItemCount   usertagitem
     *              - userCount         usertagitem
     */
    function tagDetails($tags,
                        $orderBy    = 'tag ASC',
                        $limit      = 0)
    {
        $funcId = 'TagDB::tagDetails';
        $prefix = $this->_table_prefix;

        $sql = "SELECT t.*,COUNT(DISTINCT uti.itemid,uti.userid) AS itemCount, "
                .         "COUNT(DISTINCT uti.itemid) AS uniqueItemCount, "
                .         "COUNT(DISTINCT uti.userid) AS userCount"
                . " FROM {$prefix}tag t, {$prefix}usertagitem uti"
                . " WHERE (t.tagid = uti.tagid)";

        if (is_array($tags) && (count($tags) > 0) )
            $sql .= " AND (t.tagid IN (". implode(',',$tags) ."))";

        $sql .= " GROUP BY t.tagid";

        if ($orderBy !== null)
            $sql .= " ORDER BY {$orderBy}";

        if ($limit > 0)
            $sql .= sprintf(" LIMIT %u", $limit);

        //printf ("%s: sql[%s]\n", $funcId, $sql);
        $itemTags = $this->db->GetAll($sql);
        return ($itemTags);
    }

    /** @brief  Count the tags for the specific user(s) and item(s).
     *  @param  users       An array of user ids (or a single userid,
     *                      null == all users).
     *  @param  items       An array of item ids (or a single itemid,
     *                      null == all items).
     *
     *  @return The count (false on failure)
     */
    function tagsCount($users   = null,
                       $items   = null)
    {
        $funcId = 'TagDB::tagsCount';
        $prefix = $this->_table_prefix;

        if ( (! is_array($users)) && is_numeric($users) )
        {
            // Normalize this single number into an array of containing a
            // single userid
            $users = array((int)$users);
        }
        if ( (! is_array($items)) && is_numeric($items) )
        {
            // Normalize this single number into an array of containing a
            // single itemid
            $items = array((int)$items);
        }

        $sql = "SELECT COUNT(DISTINCT tagid) AS count"
                ." FROM {$prefix}usertag"
                ." WHERE 1";

        if (is_array($users) && (count($users) > 0) )
            $sql .= " AND (userid IN (". implode(',',$users) ."))";

        if (is_array($items) && (count($items) > 0) )
            $sql .= " AND (itemid IN (" . implode(',',$items) ."))";

        $res = $this->get_count($sql);
        return ($res);
    }

    /** @brief  Given an item id, retrieve the matching item.
     *  @param  id      The id of the desired item:
     *                      - if this is a string, retrieve by matching the
     *                          URL or an MD5 of the URL,
     *                      - if this is numeric, retrieve by itemid.
     *  @return An associative array of item information:
     *              - itemid
     *              - url
     *              - userCount
     *              - ratingCount
     *              - ratingSum
     */
    function item($id)
    {
        $funcId = "TagDB::item";

        $item = $this->get('item', $id, null, array('count'=>1));
        if (is_array($item) && (count($item) == 1))
            $item = $item[0];
        else
            $item = false;

        return ($item);
    }

    /** @brief  Given a URL, retrieve the id of the matching item.
     *  @param  url     The url of the desired item.
     *
     *  @return The itemid (0 if not found)
     *              - itemid
     *              - url
     *              - userCount
     *              - ratingCount
     *              - ratingSum
     */
    function itemId($url)
    {
        $funcId = "TagDB::itemId";

        $itemId = 0;
        $item   = $this->get('item', $url, null, array('count'=>1));
        if (is_array($item) && (count($item) == 1))
        {
            $item   = $item[0];
            $itemId = (int)$item['itemid'];
        }

        return ($itemId);
    }

    /** @brief  Retrieve statistics for the given item.
     *  @param  itemid      The unique identifier of the item.
     *
     *  @return An associative array of statistics:
     *              - 'userCount'   (users,taggers) => # unique users.
     *              - 'ratingCount' (votes)         => # non-zero ratings.
     *              - 'ratingSum'                   => Sum of all ratings.
     *              - 'avgRating'                   => Average rating.
     */
    function itemStats($itemid)
    {
        $funcId = 'TagDB::itemStats';
        $prefix = $this->_table_prefix;

        $stats  = array('userCount'     => 0,
                        'ratingCount'   => 0,
                        'ratingSum'     => 0,
                        'users'         => 0,
                        'taggers'       => 0,
                        'votes'         => 0,
                        'avgRating'     => 0.0);

        if ( $itemid < 1)
        {
            //$this->profile_stop($funcId, "Missing param(s)");
            return $stats;
        }

        $sql = "SELECT userCount,ratingCount,ratingSum FROM {$prefix}item".
                    " WHERE itemid = {$itemid}";
        $res = $this->db->GetRow($sql);
        if (is_array($res))
        {
            /*
            printf ("<pre>%s: results:\n", $funcId);
            print_r($res);
            echo "</pre>\n";
            // */

            $stats['userCount']   = (int)$res['userCount'];
            $stats['ratingCount'] = (int)$res['ratingCount'];
            $stats['ratingSum']   = (int)$res['ratingSum'];

            // Alias values
            $stats['users']       = $stats['userCount'];
            $stats['taggers']     = $stats['userCount'];
            $stats['votes']       = $stats['ratingCount'];

            // Computed values
            $stats['avgRating']   = $stats['ratingCount'] > 0
                                        ? $stats['ratingSum'] /
                                            $stats['ratingCount']
                                        : 0.0;
        }

        //$this->profile_stop($funcId, "stats[%s]", var_export($stats,true));
        return ($stats);
    }

    /** @brief  Count the items for the specific userid and tags(s).
     *  @param  users       An array of user ids (or a single userid,
     *                      null == all users).
     *  @param  tags        The specific tag(s):
     *                        - null == no specific tag
     *                        - array of tagids,
     *                        - a single integer tagid,
     *                        - array of tag names,
     *                        - a [/+,]-separated string of tag names.
     *  @param  curUser The id of the current, authenticated user (-1 if none).
     *  @param  since   Only count items tagged since the given date/time.
     *
     *  @return The count (false on failure)
     */
    function userItemsCount($users      = null,
                            $tags       = null,
                            $curUser    = -1,
                            $since      = null)
    {
        $funcId = 'TagDB::userItemsCount';
        $prefix = $this->_table_prefix;

        if ( (! is_array($users)) && is_numeric($users) )
        {
            // Normalize this single number into an array of containing a
            // single userid
            $users = array((int)$users);
        }

        if ( (is_array($tags) && (count($tags) > 0) && (is_string($tags[0]))) ||
             (is_string($tags)) )
        {
            // Convert names to ids
            $tags = $this->tagIds($tags);
        }
        else if (is_numeric($tags))
        {
            // Single tagid (normalize to an array)
            $tags = array($tags);
        }

        $sql = "SELECT COUNT(DISTINCT ui.itemid) AS count"
                ." FROM {$prefix}useritem ui";

        if (is_array($tags) && (count($tags) > 0))
        {
            $sql .= ", {$prefix}usertagitem uti"
                    . " WHERE (ui.itemid = uti.itemid)"
                    .   " AND (ui.userid = uti.userid)"
                    .   " AND (uti.tagid IN (". implode(',',$tags) ."))";
        }
        else
        {
            $sql .= " WHERE 1";
        }

        if (is_array($users) && (count($users) > 0) )
            $sql .= " AND (ui.userid IN (". implode(',',$users) ."))";

        // Filter out any private items that don't belong to the current user.
        $sql .= " AND ((ui.is_private = false)";
        if ($curUser > 0)
            $sql .= " OR (ui.userid = {$curUser})";
        $sql .= ")";

        if (! empty($since))
            $sql .= ' AND (ui.tagged_on > '.$since.')';

        $sql .= " GROUP BY ui.userid,ui.itemid";

        if (is_array($tags) && (count ($tags) > 0) )
            $sql .= " HAVING (COUNT(DISTINCT uti.tagid) = ". count($tags) .")";

        //printf ("%s: sql[%s]<br />\n", $funcId, $sql);
        $res = $this->get_count($sql);
        return ($res);
    }

    /** @brief  Return user information for all users that are in the watchlist
     *          of the provided user(s)
     *  @param  users       The users(s) of interest:
     *                          - array of userid,
     *                          - a single integer userid,
     *                          - a single user name.
     *  @param  watchers    Also retrieve watchers and generate a combined
     *                      list that full defines relationships.
     *
     *  @return An array of associative arrays of user information (false if no
     *          match):
     *              - userid        user
     *              - name          user
     *              - fullName      user
     *              - email         user
     *              - pictureUrl    user
     *              - profile       user
     *              - lastVisit     user
     *              - totalTags     user
     *              - totalItems    user
     *              - rating        watchlist
     *
     *          if (watchers == true), the user information will also contain:
     *              - relation      watchlist meta-data
     *                                  (watched, watcher, mutual)
     *              - watcherRating watchlist meta-data
     *                                  (if 'relation' is 'watcher' or 'mutual')
     */
    function watchlist($users, $watchers = true)
    {
        $funcId = 'TagDB::watchlist';
        $prefix = $this->_table_prefix;

        if (! is_array($users))
        {
            if (is_numeric($users))
                $users = array($users);
            else
                $users = array($this->userId($users));
        }

        if (count($users) < 1)
            return false;

        // First, who 'users' are watching
        $sql = "SELECT ";
        if ($watchers)
            $sql .= "u.userid, ";

        $sql .= " u.*,w.rating,'watched' AS relation"
              . " FROM {$prefix}user u, {$prefix}watchlist w"
              . " WHERE (w.userid IN (". implode(',',$users) . "))"
              .   " AND (u.userid = w.watchingid)"
              . " ORDER BY u.name ASC";
        if ($watchers)
            $watchedList = $this->db->GetAssoc($sql);
        else
            $watchlist = $this->db->GetAll($sql);

        if ($watchers)
        {
            // Second, who is watching 'users'.
            $sql = "SELECT u.userid,u.*,w.rating AS watcherRating,"
                  . "'watcher' AS relation"
                  . " FROM {$prefix}user u, {$prefix}watchlist w"
                  . " WHERE (w.watchingid IN (". implode(',',$users) . "))"
                  .   " AND (u.userid = w.userid)"
                  . " ORDER BY u.name ASC";
            $watcherList = $this->db->GetAssoc($sql);

            // Finally, merge the information from the watched and watcher
            // lists, defining the relation of each entry.
            $watchlist = array();
            foreach ($watchedList as $userid => $userInfo)
            {
                $userInfo['userid'] = $userid;
                if (is_array($watcherList[$userid]))
                {
                    $userInfo['relation'] = 'mutual';
                    unset($watcherList[$userid]);
                }

                $watchlist[] = $userInfo;
            }

            foreach ($watcherList as $userid => $userInfo)
            {
                $userInfo['userid']   = $userid;
                $watchlist[] = $userInfo;
            }
        }

        return ($watchlist);
    }

    /** @brief  Return a single watchlist entry for the given userid and
     *          watchingid.
     *  @param  userid      The users.
     *  @param  watchingid  The watchingid to modify.
     *
     *  @return An associative array of user information (false if no match):
     *              - userid        user
     *              - name          user
     *              - fullName      user
     *              - email         user
     *              - pictureUrl    user
     *              - profile       user
     *              - lastVisit     user
     *              - totalTags     user
     *              - totalItems    user
     *              - rating        watchlist
     *              - relation      watchlist meta-data
     *                                  (watched, watcher, mutual)
     *              - watcherRating watchlist meta-data
     *                                  (if 'relation' is 'watcher' or 'mutual')
     */
    function watchlistEntry($userid, $watchingid)
    {
        $funcId = 'TagDB::watchlist';
        $prefix = $this->_table_prefix;

        $userid     = (int)$userid;
        $watchingid = (int)$watchingid;
        if (($userid == $watchingid) || ($watchingid < 1))
        {
            // Self
            $userInfo = $this->user($userid);
            $userInfo['relation'] = 'self';
            return ($userInfo);
        }

        // First, userid->watchingid relation
        $sql .= "SELECT u.*,w.rating,'watched' AS relation"
              . " FROM {$prefix}user u, {$prefix}watchlist w"
              . " WHERE (w.userid     = {$userid})"
              .   " AND (w.watchingid = {$watchingid})"
              .   " AND (u.userid = w.watchingid)";
        $userInfo = $this->db->GetRow($sql);

        // Second, watchingid->userid relation.
        $sql = "SELECT w.rating"
              . " FROM {$prefix}watchlist w"
              . " WHERE (w.watchingid = {$userid})"
              . "   AND (w.userid     = {$watchingid})";
        $watcherList = $this->db->GetRow($sql);
        if (is_array($watcherList) && (count($watcherList) > 0))
        {
            // Mutual
            $userInfo['relation']      = 'mutual';
            $userInfo['watcherRating'] = $watcherList['rating'];
        }

        if ((count($userInfo) < 1) || ($userInfo['userid'] < 1))
        {
            // No watch information -- unrelated
            $userInfo = $this->user($watchingid);
            $userInfo['relation'] = 'none';
        }
        else
        {
            $userInfo = $this->userNormalize($userInfo);
        }
        return ($userInfo);
    }

    /** @brief  Return all users that are in the watchlist of the provided
     *          user(s)
     *  @param  users   The users(s) of interest:
     *                      - array of userid,
     *                      - a single integer userid,
     *                      - a single user name
     *
     *  @return An array of userid (false if no match).
     */
    function watchlistIds($users)
    {
        $funcId = 'TagDB::watchlistIds';
        $prefix = $this->_table_prefix;

        if (! is_array($users))
        {
            if (is_numeric($users))
                $users = array($users);
            else
                $users = array($this->userId($users));
        }

        //printf ("%s: users{%s}<br />\n", $funcId, implode(', ', $users));
        if (count($users) < 1)
            return false;

        $sql = "SELECT w.watchingid FROM {$prefix} watchlist w"
              . " WHERE (w.userid IN (". implode(',',$users) . "))"
              . " ORDER BY rating DESC";

        //printf ("%s: sql[%s]<br />\n", $funcId, $sql);
        $userids = $this->db->GetCol($sql);
        return ($userids);
    }

    /** @brief  Change the watchlist rating of the provided userid/watchingid.
     *  @param  userid      The users.
     *  @param  watchingid  The watchingid to modify.
     *  @param  rating      The new rating.
     *
     *  @return true (SUCCESS), false (FAILURE)
     */
    function watchlistChangeRating($userid, $watchingid, $rating)
    {
        $funcId = 'TagDB::watchlistChangeRating';
        $prefix = $this->_table_prefix;

        if (($userid < 1) || ($watchingid < 1))
            return false;

        $userid     = (int)$userid;
        $watchingid = (int)$watchingid;
        $rating     = (int)$rating;

        if ($rating > 5)
            $rating = 5;
        else if ($rating < 0)
            $rating = 0;

        $sql = "UPDATE {$prefix}watchlist SET rating={$rating} "
              ." WHERE (userid = {$userid}) AND (watchingid = {$watchingid})";
        //printf ("%s: sql[%s]<br />\n", $funcId, $sql);

        $res = $this->db->_query($sql, null);

        return ($res);
    }

    /** @brief  Return information about the watchlist relation between
     *          the provided current user and another.
     *  @param  userid      The userid of the current user.
     *  @param  watchingid  The userid of the other user.
     *
     *  @return An associative array of relation information (false if no
     *          match):
     *              - watched       if 'watchingid' is watched by 'userid'.
     *              - rating        if 'watchingid' is watched by 'userid'.
     *              - watcher       if 'userid'     is watched by 'watchingid'.
     */
    function watchlistRelation($userid, $watchingid)
    {
        $funcId = 'TagDB::watchlistRelation';

        if ($userid == $watchingid)
        {
            return array('relation' => 'self');
        }

        $relation = array();

        // First, userid->watchingid relation
        $sql = "SELECT rating,'true' AS watched"
              . " FROM watchlist"
              . " WHERE (userid     = {$userid})"
              .    "AND (watchingid = {$watchingid})";

        //printf ("%s: sql[%s]<br />\n", $funcId, $sql);
        $relation = $this->db->GetRow($sql);

        // Now, watchingid->userid relation
        $sql = "SELECT rating AS watcherRating,'true' AS watcher"
              . " FROM watchlist"
              . " WHERE (watchingid = {$userid})"
              .    "AND (userid     = {$watchingid})";

        //printf ("%s: sql[%s]<br />\n", $funcId, $sql);
        $relation = array_merge($relation, $this->db->GetRow($sql));

        if ($relation['watched'] && $relation['watcher'])
            $relation['relation'] = 'mutual';
        else if ($relation['watched'])
            $relation['relation'] = 'watched';
        else if ($relation['watcher'])
            $relation['relation'] = 'watcher';
        else
            $relation['relation'] = '';

        return ($relation);
    }

    /**************************************************************************
     * Tag managment
     *
     */

    /** @brief  Return the maximum size for a single tag.
     *
     *  @return The maximum size for a single tag.
     */ 
    function tagsMaxSize()
    {
        $size   = 30;   // Default value

        $prefix  = $this->_table_prefix;
        $columns = $this->db->MetaColumns("{$prefix}tag");

        if (is_array($columns) && isset($columns['TAG']))
        {
            /* The column object has (at least for MySQL):
             *  - name              'tag'
             *  - max_length        30
             *  - type              varchar
             *  - scale             NULL
             *  - not_null          true
             *  - primary_key       false
             *  - auto_increment    false
             *  - binary            false
             *  - unsigned          false
             *  - has_default       false
             */
            $tagCol =& $columns['TAG'];
            $size   =  $tagCol->max_length;
        }

        return $size;
    }

    /** @brief  Split a tag string into an array.
     *  @param  tagStr  The tag string.
     *
     *  @return An array of tags.
     */
    function tagStr2Array($tagStr)
    {
        $tagArr = preg_split('#\s*[/+,]\s*#', strtolower($tagStr));

        return ($tagArr);
    }

    /** @brief  If the tag doesn't exist, create it.
     *  @param  tag     The tag name.
     *
     *  @return The tagid (false on failure).
     */ 
    function tagCreate($tag)
    {
        $funcId = 'TagDB::tagCreate';

        $this->profile_start($funcId, "tag[%s]", $tag);

        // 1) Does the tag already exist?
        $res = $this->get('tag', array('tag' => strtolower($tag)));
        if (is_array($res) && (count($res) < 0))
        {
            $this->profile_checkpoint($funcId,
                                   "FAILURE checking existence of '%s'",
                                      $tag);
            // Proceed as if it doesn't exist
            $tagid = 0;
        }
        else
        {
            $rec =& $res[0];
            $tagid = (int)$rec['tagid'];
        }

        if ($tagid < 1)
        {
            // 1a) the tag does not yet exist so add it
            $tagid = $this->add('tag',array('tag' => strtolower($tag)));
            if ($tagid === false)
            {
                $this->profile_stop($funcId,
                                    "FAILURE adding tag '%s'",
                                    $tag);
                return (false);
            }
        }

        $this->profile_stop($funcId, "tag[%s] id[%u]", $tag, $tagid);
        return ($tagid);
    }

    /** @brief  Add tags to a specific item / user.
     *  @param  userid      The unique identifier of the user.
     *  @param  itemid      The unique identifier of the item.
     *  @param  tags        An array of tags (names) to add.
     *
     *  @return true (SUCCESS) or false (FAILURE).
     */ 
    function tagsAdd($userid,
                     $itemid,
                     $tags)
    {
        $funcId = 'TagDB::tagsAdd';
        $this->profile_start($funcId, "u%u/i%u/t%s",
                             $userid, $itemid,
                             "{".implode(',',$tags)."}");

        $itemid = (int)$itemid;
        $userid = (int)$userid;

        if ( ($userid < 1) || ($itemid < 1) || (! is_array($tags)) )
        {
            $this->profile_stop($funcId, "Missing parameter(s)");
            return false;
        }

        if (count($tags) < 1)
        {
            // Nothing to do
            $this->profile_stop($funcId, "No tags to add");
            return true;
        }

        /*
         * To add a new set of tags for userid/itemid, we need to process
         * the tags one at a time performing the following:
         *  - see if the tag exists
         *      - if not, add it
         *  - ensure that there are tag-related entries in
         *    'usertag', 'itemtag', and 'usertagitem'.
         */
        $this->profile_checkpoint($funcId, "add new tags{%s}",
                                  implode(',',$tags));
        foreach ($tags as $tag)
        {
            // 1) Does the tag already exist?
            $tagid = $this->tagCreate($tag);

            /* 2) ensure that there are tag-related entries in
             *    'usertag', 'itemtag', and 'usertagitem'.
             *
             *     NOTE: ASSUME that any failure on 'add' is because the
             *           record already exists...
             */
            $this->add('usertag',     array('userid' => $userid,
                                            'tagid'  => $tagid));
            $this->add('itemtag',     array('itemid' => $itemid,
                                            'tagid'  => $tagid));
            $this->add('usertagitem', array('userid' => $userid,
                                            'itemid' => $itemid,
                                            'tagid'  => $tagid));
        }

        $this->profile_stop($funcId);
        return true;
    }

    /** @brief  Delete the given tags from the identified item.
     *  @param  userid  The unique ID of the user.
     *  @param  itemid  The unique ID of the item.
     *  @param  tags    The set of tags to delete. Either a [/+,]-separated
     *                  string or an array of strings (NULL == delete all
     *                  tags).
     *
     *  @return true (SUCCESS) or false (FAILURE).
     */
    function tagsDelete($userid,
                        $itemid,
                        $tags  = array())
    {
        $funcId = 'TagDB::tagsDelete';
        $prefix = $this->_table_prefix;

        $itemid = (int)$itemid;
        $userid = (int)$userid;

        if ( ($itemid < 1) || ($userid < 1) )
            return false;

        /*
         * To delete tags from an item, we need to:
         *  - retrieve the 'tagid' for each tag we are to delete.
         *
         *  - delete from 'usertagitem' where 'itemid' = $itemid AND 'tagid' is
         *    in our set of tag identifiers AND 'userid' = $userid.
         *    
         *  - for each tag, if there are no more 'usertagitem' records matching
         *    'itemid/tagid', delete the 'itemtag' record matching
         *    'itemid/tagid'.
         *
         *  - for each tag, if there are no more 'usertagitem' records matching
         *    'userid/tagid', delete the 'usertag' record matching
         *    'userid/tagid'.
         *
         * 1) retrieve the 'tagid' for each tag we are to delete.
         */
        if ($tags === null)
        {
            // Deleting all tags for the given item/user, so retrieve them.
            $tagList = $this->itemTags(array($userid), array($itemid));
            $tagids  = array();
            foreach ($tagList as $idex => $tagInfo)
            {
                $tagids[] = $tagInfo['tagid'];
            }
        }
        else
        {
            /*
             * The incoming 'tags' array should be an array of tag names.
             *
             * Map tag names to tagids
             */
            $tagids = $this->tagIds($tags);
        }

        if (! is_array($tagids))
            return false;

        if (count($tagids) < 1)
            // Nothing to delete
            return true;


        // 2) delete from 'usertagitem' where 'itemid' = $itemid AND 'tagid' is
        //    in our set of tag identifiers AND 'userid' = $userid.
        $sql = "DELETE FROM {$prefix}usertagitem"
                   ." WHERE (itemid = $itemid)"
                     ." AND (userid = $userid)"
                     .' AND (tagid IN (' . implode(',',$tagids) . '))';

        $res = $this->db->_query($sql, null);
        if ($res === false)
        {
            printf("*** %s: FAILURE deleting tags for %u/%u {%s}<br />\n",
                   $funcId, $userid, $itemid, implode(',',$tagids) );
            printf("*** %s: Error Msg: %s<br />\n",
                   $funcId, $this->db->ErrorMsg());
            return false;
        }

        // 3) for each tag, if there are no more 'usertagitem' records matching
        //    'itemid/tagid', delete the 'itemtag' record matching
        //    'itemid/tagid'.
        for ($idex = 0; $idex < count($tagids); $idex++)
        {
            $tagid = $tagids[$idex];
            $sql   = "SELECT COUNT(DISTINCT itemid) AS count".
                     " FROM {$prefix}usertagitem".
                        " WHERE (itemid = $itemid) AND (tagid = $tagid)";
            $count = $this->get_count($sql);
            if ($count === false)
            {
                printf("*** %s: FAILURE counting 'usertagitem' records for item[%u], tag[%u]<br />\n",
                   $funcId, $itemid, $tagid);
            }
            else if ($count < 1)
            {
                // Delete from 'itemtag' where itemid = $itemid AND
                //                              tagid = $tagid
                if ($this->delete('itemtag',
                                  array('itemid' => $itemid,
                                        'tagid'  => $tagid)) === false)
                {
                    printf("*** %s: FAILURE deleting 'itemtag' record item[%u], tag[%u]<br />\n",
                           $funcId, $itemid, $tagid);
                    return false;
                }
            }


            // 4) for each tag, if there are no more 'usertagitem' records
            //    matching 'userid/tagid', delete the 'usertag' record matching
            //    'userid/tagid'.
            //
            $sql   = "SELECT COUNT(DISTINCT itemid) AS count".
                        " FROM {$prefix}usertagitem".
                        " WHERE (userid = $userid) AND (tagid = $tagid)";
            $count = $this->get_count($sql);
            if ($count === false)
            {
                printf("*** %s: FAILURE counting 'usertagitem' records for user[%u], tag[%u]<br />\n",
                   $funcId, $userid, $tagid);
            }
            else if ($count < 1)
            {
                // Delete from 'usertag' where userid = $userid AND
                //                              tagid = $tagid
                if ($this->delete('usertag',
                                  array('userid' => $userid,
                                        'tagid'  => $tagid)) === false)
                {
                    printf("*** %s: FAILURE deleting 'usertag' record user[%u], tag[%u]<br />\n",
                           $funcId, $userid, $tagid);
                    return false;
                }
            }
        }

        return true;
    }

    /** @brief  Modify tags to a specific item / user.
     *  @param  userid      The unique identifier of the user.
     *  @param  itemid      The unique identifier of the item.
     *  @param  tags        A comma separated string of tags that constitute
     *                      the new, full set of tags for the item.
     *
     *  @return true (SUCCESS) or false (FAILURE).
     */ 
    function tagsChange($userid,
                        $itemid,
                        $tags)
    {
        $funcId = 'Tag::tagsChange';
        $this->profile_start($funcId, "u%u/i%u/t%s",
                             $userid, $itemid, $tags);

        $itemid = (int)$itemid;
        $userid = (int)$userid;

        if (($userid < 1) || ($itemid < 1))
        {
            $this->profile_stop($funcId, "Missing parameter(s)");
            return false;
        }

        if (! is_array($tags))
        {
            // Convert this tag string into an array
            $tagArr = $this->tagStr2Array($tags);
            $tags   = $tagArr;
            $this->profile_checkpoint($funcId, "tags {%s}",implode(',',$tags));
        }

        /*
         * To change a set of tags for userid/itemid, we need to:
         *  - retrieve all current tags for this item/user
         *  - figure out which of the old tags should be deleted and
         *    which of the new tags are being added.
         *  - add any tags that are being added
         *  - delete any tags that are no longer needed
         *
         * 1) retrieve all current tags for this item/user
         */
        $oldTags = $this->itemTags(array($userid), array($itemid));
        if ($oldTags === false)
        {
            $this->profile_stop($funcId, "FAILURE locating tags for u%u/i%u\n",
                                $userid, $itemid);
            return false;
        }
        $this->profile_checkpoint($funcId, "oldTags{%s}",
                                  var_export($oldTags, true));

        /*
         * 2) figure out which of the old tags should be deleted and
         *    which of the new tags are being added.
         *      e.g.    new = (a,    c, d,    f, g, h, i)
         *              old = (a, b, c, d, e, f)
         *              del = (   b,       e)
         *              sav = (a,    c, d,    f)
         *
         *              add = (                  g, h, i)
         *                  = array_diff(new, sav)
         */
        $delTags = array();
        $savTags = array();
        foreach ($oldTags as $idex => $tagInfo)
        {
            $tag = $tagInfo['tag'];

            if (! in_array($tag, $tags))
            {
                /*
                 * This tag does not exist in the new list so mark it for
                 * deletion.
                 */
                $delTags[] = $tag;
            }
            else
            {
                // This is a tag that we will keep
                $savTags[] = $tag;
            }
        }

        // The tags that we need to add will be the difference between 
        $addTags = array_diff($tags, $savTags);
        $this->profile_checkpoint($funcId, "delTags{%s}, addTags{%s}",
                                  implode(',',$delTags), implode(',',$addTags));


        /*
         * 3) add any tags that are being added
         */
        if (count($addTags) > 0)
        {
            $this->profile_checkpoint($funcId, "add new tags{%s}",
                                      implode(',',$addTags));
            $res = $this->tagsAdd($userid, $itemid, $addTags);
            if ($res === false)
            {
                $this->profile_checkpoint($funcId,
                                          "Cannot add new tags{%s}",
                                          implode(',',$addTags));
            }
        }

        /*
         * 4) delete any tags that are no longer needed
         */
        if (count($delTags) > 0)
        {
            $this->profile_checkpoint($funcId, "delete unneeded tags{%s}",
                                      implode(',',$delTags));
            $res = $this->tagsDelete($userid, $itemid, $delTags);
            if ($res === false)
            {
                $this->profile_checkpoint($funcId,
                                          "Cannot delete unneeded tags{%s}",
                                          implode(',',$delTags));
            }
        }

        $this->profile_stop($funcId);
        return true;
    }

    /**************************************************************************
     * Item/User Item managment
     *
     */

    /** @brief  Add a new item if it doesn't already exist.
     *  @param  url     The url of the new item.
     *
     *  @return The unique id of the (new) item.
     */
    function itemAdd($url)
    {
        $funcId = 'TagDB::itemAdd';

        // First, does this item already exist?
        $id = $this->itemId($url);
        if ($id > 0)
            // This item already exists: return the existing itemid.
            return ($id);

        // A matching item does not yet exist.  Create it now.
        return $this->add('item', array('url' => $url));
    }

    /** @brief  Delete an item and all related item details.
     *  @param  itemid  The unique ID of the item.
     *
     *  @return true (SUCCESS) or false (FAILURE).
     */ 
    function itemDelete($itemid)
    {
        $funcId = 'TagDB::itemDelete';

        $itemid = (int)$itemid;

        if($itemid < 1)
            return false;

        /*
         * To delete an item, we need to:
         *  - delete from 'useritem'    where itemid = $itemid
         *  - delete from 'itemtag'     where itemid = $itemid
         *  - delete from 'usertagitem' where itemid = $itemid
         *  - delete from 'item'        where itemid = $itemid
         *
         *
         * 1) delete from 'useritem'    where itemid = $itemid
         */
        if (! $this->delete('useritem',    array('itemid' => $itemid)) )
            return false;

        // 2) delete from 'itemtag'     where itemid = $itemid
        if (! $this->delete('itemtag',     array('itemid' => $itemid)) )
            return false;

        // 3) delete from 'usertagitem' where itemid = $itemid
        if (! $this->delete('usertagitem', array('itemid' => $itemid)) )
            return false;

        // 4) delete from 'item'        where itemid = $itemid
        if (! $this->delete('item',        array('itemid' => $itemid)) )
            return false;

        return true;
    }

    /** @brief  Add/Modify a user item.
     *  @param  userid  The unique identifier of the user.
     *  @param  itemid  The unique identifier of the item.
     *  @param  name    The item name.
     *  @param  details An associative array of additional details to use in
     *                  creating the item:
     *                      - description   The description [DEFAULT: none]
     *                      - rating        User's rating   [DEFAULT: 0]
     *                      - is_favorite   Favorite item?  [DEFAULT: false]
     *                      - is_private    Private item?   [DEFAULT: false]
     *                      - tagged_on     Timestamp       [DEFAULT: now]
     *
     *  @return true (SUCCESS) or false (FAILURE).
     */
    function userItemModify($userid,
                            $itemid,
                            $name,
                            $details    = null)
    {
        $funcId = 'TagDB::userItemModify';

        $this->profile_start($funcId,
                             "userid[%u], itemid[%u], name[%s], details{%s}",
                             $userid, $itemid, $name,var_export($details,true));

        if ( ($itemid < 1) || ($userid < 1) )
        {
            $this->profile_checkpoint($funcId, "Invalid parameter(s)");
            return false;
        }

        $isModification = false;

        // See if an item already exists.
        $curInfo = $this->userItem($userid, $itemid);
        if (is_array($curInfo) && (count($curInfo) > 0))
        {
            $this->profile_checkpoint($funcId, "Have existing userItem");

            // Pull out existing information as the default
            $defaults = array('name'        => $curInfo['name'],
                              'description' => $curInfo['description'],
                              'rating'      => (int)$curInfo['rating'],
                              'is_favorite' => (int)$curInfo['is_favorite'],
                              'is_private'  => (int)$curInfo['is_private'],
                              'tagged_on'   => $curInfo['tagged_on']);
            $isModification = true;

            if (empty($name))
                $name = $defaults['name'];
        }
        else
        {
            $this->profile_checkpoint($funcId, "No current userItem");

            // Default details.
            $defaults = array('description' => '',
                              'rating'      => 0,
                              'is_favorite' => 0,   //false,
                              'is_private'  => 0,   //false,
                              'tagged_on'   => time());
        }

        if (is_array($details))
        {
            // Merge the incoming information with the defaults
            $details = array_merge($defaults, $details);
            $details['rating']      = (int)$details['rating'];
            $details['is_favorite'] = (int)$details['is_favorite'];
            $details['is_private']  = (int)$details['is_private'];

            // Ensure the incoming details don't include the ids.
            unset($details['userid']);
            unset($details['itemid']);
        }
        else
        {
            $details = $defaults;
        }

        // Add in the other incoming parameters.
        if (! empty($name))
            $details['name'] = $name;

        if (is_numeric($details['tagged_on']) )
        {
            // Assume this number value is a UNIX timestamp.
            $details['tagged_on'] = strftime("%Y-%m-%d %H:%M:%S",
                                             $details['tagged_on']);
        }

        if ($isModification)
        {
            // Update this item.
            $oldRating = (int)$curInfo['rating'];

            $this->profile_checkpoint($funcId,
                               "Update userItem: userid[%u], itemid[%u],"
                               ." details{%s}",
                               $userid, $itemid,
                               var_export($details,true));

            $res = $this->update('useritem',
                                 array('userid'    => (int)$userid,
                                       'itemid'    => (int)$itemid),
                                 $details);
        }
        else
        {
            $oldRating   =  0;
            $countChange = +1;

            $details['userid'] = (int)$userid;
            $details['itemid'] = (int)$itemid;

            $this->profile_checkpoint($funcId,
                               "Add userItem: details{%s}",
                               var_export($details,true));

            $res = $this->add('useritem', $details);
        }

        if ($res !== false)
        {
            $this->profile_checkpoint($funcId, "Update stats");

            // Update the item statistics
            $this->itemStatsUpdate($itemid, $countChange,
                                    $oldRating, $details['rating']);
        }
        else
        {
            $this->profile_checkpoint($funcId,
                                      "FAILED: %s",
                                      $this->db->ErrorMsg());
        }

        $this->profile_stop($funcId);
        return ($res);
    }

    /** @brief  Delete item details for the specific item and user.
     *  @param  userid  The unique ID of the user.
     *  @param  itemid  The unique ID of the item.
     *
     *  @return true (SUCCESS) or false (FAILURE).
     */ 
    function userItemDelete($userid, $itemid)
    {
        $funcId = 'TagDB::userItemDelete';

        $itemid  = (int)$itemid;
        $userid = (int)$userid;

        $this->profile_start($funcId, "userid[%u], itemid[%u]",
                             $userid, $itemid);
        if(($itemid < 1) || ($userid < 1))
        {
            $this->profile_stop($funcId, "Invalid parameter(s)");
            return false;
        }

        $this->profile_checkpoint($funcId, "get_details(%u, %u)",
                                  $itemid, $userid);

        // In deleting details, we will need to update the statistics for this
        // item so retrieve our current detail informaiton.
        $info = $this->userItem($userid, $itemid);

        $this->profile_checkpoint($funcId, "info{%s}",
                                    var_export($info, true));
        if ($info['itemid'] > 0)
        {
            $oldRating = $info['rating'];
            $newRating = 0;

            // Delete the useritem record.
            if (! $this->delete('useritem', array('itemid'  => $itemid,
                                                  'userid'  => $userid)) )
            {
                $this->profile_stop($funcId, "useritem delete failed: %s",
                                     $this->db->ErrorMsg());
                return false;
            }

            // Delete the usertagitem record(s).
            if (! $this->delete('usertagitem', array('itemid'  => $itemid,
                                                     'userid'  => $userid)) )
            {
                $this->profile_stop($funcId, "usertagitem delete failed: %s",
                                     $this->db->ErrorMsg());
                return false;
            }

            // Update item statistics to remove one user and possibly modify
            // the rating count and sum.
            $this->itemStatsUpdate($itemid, -1, $oldRating, $newRating);
        }

        $this->profile_stop($funcId, "DONE");
        return true;
    }

    /** @brief  Change the statistics for a specific item.
     *  @param  itemid      The unique identifier of the item.
     *  @param  countChange How should the count be changed (e.g. 0, +1, -1)
     *  @param  oldRating   The old rating.
     *  @param  newRating   The new rating.
     *
     *  @return true (SUCCESS) or false (FAILURE).
     */
    function itemStatsUpdate($itemid,
                             $countChange,
                             $oldRating,
                             $newRating)
    {
        $funcId = 'Item::itemStatsUpdate';

        $this->profile_start($funcId,
                              "itemid[%u], countChange[%u], "
                             ."oldRating[%u], newRating[%u]",
                             $itemid, $countChange, $oldRating, $newRating);

        // Retrieve the current statistics
        $stats = $this->itemStats($itemid);
        if (! is_array($stats))
        {
            $this->profile_stop($funcId, "Missing stats");
            return false;
        }

        // Update the statistics
        if ($countChange !== 0)
            $stats['userCount'] += $countChange;

        /*
         * If the rating changed to/from 0, we'll need to update the
         * rating counts.
         */
        if ($newRating != $oldRating)
        {
            $stats['ratingSum'] += ($newRating - $oldRating);

            if (($newRating > 0) && ($oldRating < 1))
            {
                $stats['ratingCount']++;  // Gaining a vote
            }
            else if (($newRating < 1) && ($oldRating > 0))
            {
                $stats['ratingCount']--;  // Losing a vote
            }
        }

        // Limit the update information to JUST the stats
        $newStats = array('userCount'  => $stats['userCount'],
                          'ratingCount'=> $stats['ratingCount'],
                          'ratingSum'  => $stats['ratingSum']);

        $this->profile_checkpoint($funcId, "newStats{%s}",
                                    var_export($newStats, true));
        $res = $this->update('item', $itemid, $newStats);
        if ($res === false)
        {
            $this->profile_stop($funcId, "Update failed");
            return false;
        }

        $this->profile_stop($funcId);
        return true;
    }

    /**************************************************************************
     * Watchlist managment
     *
     */

    /** @brief  Add a new userid/watchingid/rating to the watchlist.
     *  @param  userid      The userid to add.
     *  @param  watchingid  The watchingid to add:
     *                          - numeric userid;
     *                          - user name;
     *  @param  rating      The rating [default: 0]
     *
     *  @return true (SUCCESS) or false (FAILURE).
     */
    function watchlistAdd($userid,
                          $watchingid,
                          $rating       = 0)
    {
        $funcId = 'TagDB::watchlistAdd';
        $prefix = $this->_table_prefix;

        $userid     = (int)$userid;

        if (is_numeric($watchingid))
            $watchingid = (int)$watchingid;
        else
            $watchingid = $this->userId($watchingid);

        $rating     = (int)$rating;

        if (($userid < 1) || ($watchingid < 1) || ($userid == $watchingid))
            return false;

        $res = $this->add('watchlist', array('userid'     => $userid,
                                             'watchingid' => $watchingid,
                                             'rating'     => $rating));
        return ($res !== false);
    }

    /** @brief  Delete an exising userid/watchingid from the watchlist.
     *  @param  userid      The userid to add.
     *  @param  watchingid  The watchingid to add.
     *                          - numeric userid;
     *                          - user name;
     *
     *  @return true (SUCCESS) or false (FAILURE).
     */
    function watchlistDelete($userid,
                             $watchingid)
    {
        $funcId = 'TagDB::watchlistDelete';

        $userid     = (int)$userid;

        if (is_numeric($watchingid))
            $watchingid = (int)$watchingid;
        else
            $watchingid = $this->userId($watchingId);

        if (($userid < 1) || ($watchingid < 1))
            return false;

        return ($this->delete('watchlist', array('userid'     => $userid,
                                                 'watchingid' => $watchingid)));
    }

    /**************************************************************************
     * User managment
     *
     */

    /** @brief  Add a new user with the given information.
     *  @param  name        The user's unique name.
     *  @param  details An associative array of additional details to use in
     *                  creating the user:
     *                      - fullName      User's full name [DEFAULT: name]
     *                      - email         User's email     [DEFAULT: '']
     *                      - pictureUrl    User's photo     [DEFAULT: '']
     *                      - profile       User's profile   [DEFAULT: '']
     *                      - lastVisit     Timestamp        [DEFAULT: now]
     *
     *  @return Returns the unique id of the new user (false on failure).
     */ 
    function userAdd($name, $details = null)
    {
        if (empty($name))
            return false;

        // Does a user with this name already exist?
        $info = $this->get('user', $name, null, array('count'=>1));
        if (is_array($info) && (count($info) == 1))
        {
            // A user with this name already exists.
            return false;
        }

        if ($details === null)
        {
            $details = array('fullName' => $name,
                             'email'    => '');
        }
        else
        {
            // Don't allow userid to be explicitly set.
            unset($details['userid']);
        }

        if (empty($details['email']))
        {
            /* Construct an email address assuming that 'name' has the format:
             *  'uid'.'email domain'
             */
            $details['email']  = preg_replace('/\./', '@', $name, 1);
        }

        $id = $this->add('user', $details);
        return $id;
    }

    /** @brief  Delete a user from the database.
     *  @param  userid  The unique ID of the user.
     *
     *  @return true on success, false on failure.
     */ 
    function userDelete($userid)
    {
        $userid = (int)$userid;

        if($userid < 1)
            return false;

        // :TODO: Should we also delete all related useritem, usertag,
        //        usertagitem records.

        return ($this->delete('user', $userid));
    }

    /** @brief  Update user information.
     *  @param  userid  The unique ID of the user.
     *  @param  details An associative array of specific details to modify:
     *                      - fullName      User's full name [DEFAULT: name]
     *                      - email         User's email     [DEFAULT: '']
     *                      - pictureUrl    User's photo     [DEFAULT: '']
     *                      - profile       User's profile   [DEFAULT: '']
     *                      - lastVisit     Timestamp        [DEFAULT: now]
     *
     *  @return true on success, false on failure.
     */ 
    function userModify($userid, $details)
    {
        $funcId = 'TagDB::userModify';

        $userid = (int)$userid;

        if ( ($userid < 1) || (! is_array($details)) )
            return false;

        // Does this user exist?
        $userInfo = $this->user($userid);
        if ($userInfo === false)
        {
            // There is no user with the given userid.
            return false;
        }

        // Don't allow userid or name to be changed
        unset($details['userid']);
        unset($details['name']);

        return ($this->update('user', $userid, $details));
    }

    /** @brief  Update user statistics.
     *  @param  userid      The unique id of the user.
     *  @param  lastVisit   The last visit date (if >= 0):
     *                          - numeric = unix timestamp;
     *                          - string  = formatted date/time (Y-m-d H:M:S)
     *                          - null    = set to now.
     *  @param  totalTags   The total tags for this user:
     *                          - null = recompute
     *  @param  totalItems  The total items for this user:
     *                          - null = recompute
     *
     *  @return true (SUCCESS), false (FAILURE).
     */
    function userStatsUpdate($userid,
                             $lastVisit     = null,
                             $totalTags     = null,
                             $totalItems    = null)
    {
        $userid = (int)$userid;
        if ($userid < 1)
            return false;

        if ($lastVisit === null)
        {
            // Set lastVisit to now
            $lastVisit  = strftime("%Y-%m-%d %H:%M:%S", time());
        }
        else if (is_numeric($lastVisit))
        {
            if ($lastVisit > 0)
                // Assume this number value is a UNIX timestamp.
                $lastVisit = strftime("%Y-%m-%d %H:%M:%S", $lastVisit);
            else
                $lastVisit = '';    // Empty / do NOT set
        }

        if ($totalTags === null)
            $totalTags  = $this->tagsCount($userid);
        if ($totalItems === null)
            $totalItems = $this->userItemsCount($userid, null, $userid);

        if ( empty($lastVisit) && ($totalTags < 0) && ($totalItems < 0) )
            return false;

        // Assemble the array of new/updated information
        $newInfo = array();
        if (! empty($lastVisit))
            $newInfo['lastVisit'] = $lastVisit;
        if ($totalTags >= 0)
            $newInfo['totalTags'] = $totalTags;
        if ($totalItems >= 0)
            $newInfo['totalItems'] = $totalItems;

        /*printf("%s: user:%u, [%s]\n",
                $funcId, $userid, var_export($newInfo, true));*/
        return ($this->update('user', $userid, $newInfo));
    }

    /**************************************************************************
     * Debug and profile support
     *
     */

    /** @brief  Prints debug text if debug is enabled.
     *  @param  ...     Variable arguments passed to sprintf for formatting.
     */
    function debug_text()
    {
        if ($this->_debug)
        {
            $argv = func_get_args();
            $argc = count($argv);
            if ($argc > 0)
            {
                $fmt = array_shift($argv);
                $out = vsprintf($fmt, $argv);
            }
            else
            {
                $out = '';
            }

            echo "$out<br />\n";
        }
        return true;
    }

    /** @brief  Start profiling
     *  @param  args    Variable arguments comprised of:
     *                      unique identifier, sprintf format, sprintf args
     */
    function profile_start()
    {
        return ($this->profile
                    ? $this->profile->vstart(func_get_args()) : 0);
    }

    /** @brief  Checkpoint profiling
     *  @param  args    Variable arguments comprised of:
     *                      unique identifier, sprintf format, sprintf args
     */
    function profile_checkpoint()
    {
        return ($this->profile
                    ? $this->profile->vcheckpoint(func_get_args()) : 0);
    }

    /** @brief  Stop profiling
     *  @param  args    Variable arguments comprised of:
     *                      unique identifier, sprintf format, sprintf args
     */
    function profile_stop()
    {
        return ($this->profile
                    ? $this->profile->vstop(func_get_args()) : 0);
    }

    /**************************************************************************
     * Private member variables
     *
     */

    /**#@+
     *  @access private
     *  @param string
     */ 
    var $_db_user = 'user';
    var $_db_pass = 'pass';
    var $_db_host = 'localhost';
    var $_db_name = 'freetag'; 
    /**#@-*/

    /**
     * @access private
     * @param ADOConnection The ADODB Database connection instance.
     */
    var $_db;

    /**
     * @access private
     * @param string The db driver string to pass to ADOdb.
     */
    var $_db_driver = 'mysql';

    /**
     * @access private
     * @param bool  Prints out limited debugging information if true, not fully
     *              implemented yet.
     */
    var $_debug = FALSE;

    /**
     * @access private
     * @param object    The profiling object (if any).
     */
    var $profile = null;

    /**
     * @access private
     * @param string The prefix of user database tables.
     */
    var $_table_prefix = '';

    /**
     * @access private
     * @param boolean   The (cached) value of get_magic_quotes_runtime().
     */
    var $_quotes = true;

    /**
     * @access private
     * @param boolean   Should SQL be executed (false) or just displayed (true).
     */
    var $_noexec    = false;

    /**
     * @access private
     * @param array     An array of unique identifiers for use with _noexec to
     *                  simulate table insertions.
     *
     * One entry for each table (user, item, tag).
     */
    var $_uid       = array();

    /**
     * @access private
     * @param bool  Whether to use persistent ADODB connections. False by
     *              default.
     */
    var $_PCONNECT = FALSE;

    /**
     * @access private
     * @param string    The file path to the installation of ADOdb used.
     */ 
    var $_ADODB_DIR = 'adodb/';

    /** @brief  Initialize a new instance with a set of options.
     *  @param  array options   An associative array of options.
     *
     *  The following options are valid:
     *  - debug: Set to TRUE for debugging information. [default:FALSE]
     *  - noexec: Set to TRUE to display SQL vice execute it [default:FALSE].
     *  - db: If you've already got an ADODB ADOConnection, you can pass it
     *        directly and Freetag will use that. [default:NULL]
     *  - db_user: Database username
     *  - db_pass: Database password
     *  - db_host: Database hostname [default: localhost]
     *  - db_name: Database name
     *  - table_prefix: If you wish to create multiple Freetag databases on the
     *              same database, you can put a prefix in front of the table
     *              names and pass separate prefixes to the constructor.
     *              [default: '']
     *  - ADODB_DIR: directory in which adodb is installed. Change if you don't
     *              want to use the bundled version. [default: adodb/]
     *  - PCONNECT: Whether to use ADODB persistent connections.
     *              [default: FALSE]
     *
     *  @return An array of unprocessed options.
     */ 
    function init($options)
    {
        // Initialize some values
        $this->_quotes   = get_magic_quotes_runtime();

        $unprocessed        = array();
        $available_options  = array('debug', 'noexec',
                                    'db', 'db_driver', 'db_user',
                                    'db_pass', 'db_host', 'db_name',
                                    'table_prefix', 'ADODB_DIR', 'PCONNECT');
        if (is_array($options))
        {
            foreach ($options as $key => $value)
            {
                $this->debug_text("Option: $key");

                if (in_array($key, $available_options) )
                {
                    $this->debug_text("Valid Config options: $key");
                    $property = '_'.$key;
                    $this->$property = $value;
                    $this->debug_text("Setting $property to $value");
                }
                else
                {
                    $unprocessed[$key] = $value;
                    //$this->debug_text("ERROR: Config option: $key is not a valid option");
                }
            }
        }

        // If the profile class has been included, turn profiling on.
        global  $gProfile;
        if (is_a($gProfile, 'Profile'))
        {
            $this->profile =& $gProfile;
        }

        require_once($this->_ADODB_DIR . "/adodb.inc.php");
        if (is_object($this->_db))
        {
            $this->db = &$this->_db;
            $this->debug_text("DB Instance already exists, using this one.");
        }
        else
        {
            $this->db = ADONewConnection($this->_db_driver);
            $this->debug_text("Connecting to db with:" .
                              $this->_db_host . " " . $this->_db_user . " " .
                              $this->_db_pass . " " . $this->_db_name);
            if ($this->_PCONNECT)
            {
                $this->db->PConnect($this->_db_host, $this->_db_user,
                                    $this->_db_pass, $this->_db_name);
            }
            else
            {
                $this->db->Connect($this->_db_host, $this->_db_user,
                                   $this->_db_pass, $this->_db_name);
            }
        }

        if ($this->_noexec)
        {
            $this->_uid['user']['##_id_##'] = 1;
            $this->_uid['item']['##_id_##'] = 1;
            $this->_uid['tag']['##_id_##']  = 1;
        }

        global  $gTagging;
        $this->mTagging  =& $gTagging;

        $this->db->debug =  $this->_debug;

        // User uses ASSOC for ease of maintenance and compatibility with
        // people who choose to modify the schema.  Feel free to convert to NUM
        // if performance is the highest concern.
        $this->db->SetFetchMode(ADODB_FETCH_ASSOC);

        return ($unprocessed);
    }

    /** @brief  Quote the provided value for proper use in the database.
     *  @param  value   The value to quote.
     *
     *  If the incoming value is an integer, leave it alone, otherwise quote
     *  it.
     *
     *  @return A properly quoted value.
     */
    function quote($value)
    {
        if ((string)$value === (string)(int)$value)
            // Integer value
            $value = (int)$value;
        else if (! is_float($value))
            $value = $this->db->qstr($value, $this->_quotes);

        return ($value);
    }

}
