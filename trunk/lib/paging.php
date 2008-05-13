<?php

/** @brief  Provide paging control for a set of database records returned using
 *          the provided SQL query.
 */
class Pager
{
    var $mDB            = null;
    var $mId            = '';
    var $mSql           = null;
    var $mPerPage       = null;
    var $mCurPage       = 1;
    var $mPageCount     = 0;
    var $mRecordCount   = 0;
    var $EOF            = false;
    var $mCurPageId     = 'Pager:curPage';
    var $mPerPageId     = 'Pager:perPage';
    var $mSqlId         = 'Pager:sql';
    var $mRendererId    = 'Pager:renderer';
    var $mFormId        = 'Pager:form:';
    var $mRenderer      = null;

    /** @brief  Create a new pager.
     *  @param  db      The database connection.
     *  @param  id      The unique id of this pager
     *  @param  sql     The SQL for this pager.
     *  @param  renderer  The render callback for this pager
     *                      [ renderer($pager, $items) returns HTML ]
     *  @param  perPage The number of items per page.
     *  @param  curPage The current page.
     */
    function Pager(&$db,
                   $id,
                   $sql         = null,
                   $renderer    = null,
                   $perPage     = 20,
                   $curPage     = 1)
    {
        $funcId = "Pager";

        global  $gTagging;
        global  $_SESSION;

        $gTagging->profile_start($funcId,
                     "id[%s], sql[%s], rednerer[%s], perPage[%u], curPage[%u]",
                     $id, $sql, $renderer, $perPage, $curPage);

        if (! empty($id))
        {
            $this->mId            = $id;
            $this->mCurPageId     = $id . ':' . $this->mCurPageId;
            $this->mPerPageId     = $id . ':' . $this->mPerPageId;
            $this->mSqlId         = $id . ':' . $this->mSqlId;
            $this->mRendererId    = $id . ':' . $this->mRendererId;
            $this->mFormId        = $id . ':' . $this->mFormId;
        }

        if (($sql === null) || empty($sql))
        {
            // See if we can fill from the session variable
            $sql      = $_SESSION[$this->mSqlId];
            $perPage  = $_SESSION[$this->mPerPageId];

            if ($curPage === 1)
                $curPage  = $_SESSION[$this->mCurPageId];
            else
                $_SESSION[$this->mCurPageId] = $curPage;

            $renderer = $_SESSION[$this->mRendererId];

            /*printf ("Pager: Reinstate from session:<br />\n".
                    "     : sql     :'%s'<br />\n".
                    "     : perPage : %u<br />\n".
                    "     : curPage : %u<br />\n".
                    "     : renderer:'%s'<br />\n",
                    $sql, $perPage, $curPage, $renderer);*/
        }
        else
        {
            // Save our current paging information in the session variable.
            $_SESSION[$this->mSqlId]      = $sql;
            $_SESSION[$this->mPerPageId]  = $perPage;
            $_SESSION[$this->mCurPageId]  = $curPage;
            $_SESSION[$this->mRendererId] = $renderer;

            /*printf ("Pager: Initiate session:<br />\n".
                    "     : sql     :'%s'<br />\n".
                    "     : perPage : %u<br />\n".
                    "     : curPage : %u<br />\n".
                    "     : renderer:'%s'<br />\n",
                    $sql, $perPage, $curPage, $renderer);*/
        }

        $gTagging->profile_checkpoint($funcId,
                     "id[%s], sql[%s], rednerer[%s], perPage[%u], curPage[%u]",
                     $this->mId, $sql, $renderer, $perPage, $curPage);

        // Convert the incoming SQL to one that will return a COUNT.
        // First, grab any 'GROUP BY' clause to use as COUNT(DISTINCT ...)
        if (preg_match('/GROUP BY (\S+(,\s*)?)+\b/i', $sql, $matches))
        {
            $countLimit = "DISTINCT ". $matches[1];
        }
        else
        {
            $countLimit = '*';
        }

        $gTagging->profile_checkpoint($funcId,
                             "Count limit[%s]", $countLimit);

        $countSql = preg_replace('/SELECT .*? FROM/i',
                                    "SELECT COUNT($countLimit) FROM", $sql);
        $countSql = preg_replace('/ORDER BY (.*?[, ])+/i', '', $countSql);
        $countSql = preg_replace('/ (ASC|DESC)/i',         '', $countSql);

        $gTagging->profile_checkpoint($funcId, "countSql[%s]", $countSql);

        if (get_class($db) == 'tagdb')
            $db =& $db->db;

        $this->mDB          =& $db;
        $this->mSql         =  $sql;
        $this->mPerPage     =  $perPage;
        $this->mCurPage     =  $curPage;

        // Count all matching records
        $countRows = $this->mDB->GetCol($countSql);
        $gTagging->profile_checkpoint($funcId, "%u counting rows",
                                      count($countRows));
        $countRecs = 0;
        foreach ($countRows as $idex => $count)
        {
            $countRecs += (int)$count;
        }

        $this->mRecordCount = $countRecs;
        $this->mPageCount   = (int)ceil($this->mRecordCount / $this->mPerPage);

        if ($this->mCurPage > $this->mPageCount)
            $this->mCurPage = $this->mPageCount;

        if (function_exists("{$renderer}"))
            $this->mRenderer = $renderer;

        $gTagging->profile_stop($funcId, "RecordCount[%u], PageCount[%u]",
                            $this->mRecordCount, $this->mPageCount);
    }

    /** @brief  Return the page count for the current pager.
     *
     *  @return The number of pages.
     */
    function PageCount()
    {
        return $this->mPageCount;
    }

    /** @brief  Return the page number of the records that were returned
     *          by the previous call to GetPage().
     *
     *  @return The page number.
     */
    function PageNum()
    {
        return $this->mCurPage - 1;
    }

    /** @brief  Return the number of records for the current pager.
     *
     *  @return The number of records for the current pager.
     */
    function RecordCount()
    {
        return $this->mRecordCount;
    }

    /** @brief  Return the records for the current page.
     *
     *  @return An array of associative arrays one for each record on this
     *          page.
     */
    function GetPage()
    {
        $funcId = "Pager:GetPage";

        global  $gTagging;
        if ($this->mCurPage > $this->mPageCount)
        {
            return null;
        }

        // Grab the records for the current page.
        $startRec = ($this->mCurPage - 1) * $this->mPerPage;

        $sql = sprintf ("%s LIMIT %u,%u",
                        $this->mSql, $startRec, $this->mPerPage);

        $gTagging->profile_start($funcId, "sql[%s]", $sql);

        /*printf ("%s: sql[%s]<br />\n", $funcId, $sql);
        flush();*/
        $recs = $this->mDB->GetAll($sql);

        // Move on to the next page.
        $this->mCurPage++;

        // Save our current paging information in the session variable.
        global  $_SESSION;
        $_SESSION[$mCurPageId] = $curPage;

        $this->EOF = ($this->mCurPage > $this->mPageCount);

        $gTagging->profile_stop($funcId);
        return ($recs);
    }

    function Close()
    {
    }

    /** @brief  Retrieve the current page of records and generate the HTML to
     *          represent those records.
     *  @param  renderer    A rendering callback of the form:
     *                          renderer($pager, $items) returns HTML
     *
     *  @return The HTML representing the current page.
     */
    function pageHtml($renderer = null)
    {
        if (function_exists("{$renderer}"))
            $this->mRenderer = $renderer;

        $items = $this->GetPage();

        if ($this->mRenderer == null)
        {
            $html  = sprintf ("Page %u / %u:<br />\n",
                              $this->PageNum(), $this->mPageCount);

            $html .= "<pre>" . print_r($items, true) . "</pre>\n";
        }
        else
        {
            $renderer = $this->mRenderer;

            $html = $renderer($this, $items);
        }

        return $html;
    }

    /** @brief  Generate and return the HTML for paging controls
     *  @param  area        'Top' or 'Bottom'
     *  @param  content     The id of the div containing the paged content.
     *  @param  retName     If not null, return the name of the generated
     *                      paging form
     *
     *  @return The HTML
     */
    function controlHtml($area, $content, $retName = null)
    {
        global  $gBaseUrl;

        $pageNum = $this->PageNum();
        if ($area == 'Top')
            $pageNum++;

        $thisForm  = $this->mFormId . $area;

        if ($retName != null)
            $retName = $thisForm;

        $html = "
    <form id='{$thisForm}'
        onsubmit='Pagination($(\"{$thisForm}\"),\"-\",0); return false; '>
     <input type='hidden' name='ContentId' value='{$content}' />
     <input type='hidden' name='PagerId'   value='{$this->mId}' />
     <img class='PagerCtl' src='{$gBaseUrl}/images/Rewind";
    
        if($pageNum <= 1) $html .= "-off";
    
        $html .= ".png'
          alt='Previous Page'
          title='Previous Page'
          onclick='Pagination($(\"{$thisForm}\"),\"-\",1)'
        />
     <img src='{$gBaseUrl}/images/Progress.gif'
          alt='Progress'
          title='Progress'
          class='Progress'
          style='display:none;'/>
     <input
        type='text'
        name='PageNum'
        size='3' maxlength='6'
        value='{$pageNum}'
        onkeypress='return Blocknon_numeric(event);'
        class='PageNum'
        onchange='Pagination($(\"{$thisForm}\"),\"-\",0)' />
     <!-- a href='#'
        class='PageNum'";

        if ($this->mPageCount > 0)
        {
            $html .= "
        onmouseover='helpTip(this, \"Page Count\", \"This is the number of pages of links that you have. Each page has {$this->mPerPage} links. Did you know that you can select the page number that you are currently at and key in the page number that you want to go to? Try it.\");'
        >/{$this->mPageCount}";
        }

        $html .= "</a --><span name='PageMax' class='PageNum'>/ {$this->mPageCount}</span>
     <img class='PagerCtl'
          src='{$gBaseUrl}/images/Forward";
        if (($this->mPageCount > 0) && ($pageNum >= $this->mPageCount))
            $html .= "-off";
            
        $html .= ".png'
          alt='Next Page'
          title='Next Page'
          onclick='Pagination($(\"{$thisForm}\"),\"+\",1)'
        />
    </form>";

        return ($html);
    }
}

/** @brief  Generate and return the HTML for paging controls
 *  @param  area        'Top' or 'Bottom'
 *  @param  taggerId    The unique identifier of the tagger of the displayed
 *                      items.
 *  @param  order       The type of data being paged
 *                          (Recent, Popular, TopRated, ByDate)
 *  @param  dataType    The data type.
 *  @param  curPage     The current page number
 *  @param  maxPage     The maximum number of pages (<=0 means unknown)
 *  @param  perPage     The number of items per page
 *  @param  retName     If not null, return the name of the generated
 *                      paging form
 *
 *  @note The content area that contains the paged information MUST
 *        have an ID of:
 *          $dataType
 *
 *  @return The HTML
 */
function    pagingHtml($taggerId, $area,    $order,   $dataType,
                       $curPage, $maxPage, $perPage = 7, $retName = null)
{
    global  $gBaseUrl;

    $thisForm  = "Paging" . $dataType . $area;

    if ($retName != null)
        $retName = $thisForm;

    $html = "
    <form id='{$thisForm}'
        onsubmit='Pagination($(\"{$thisForm}\"),\"-\",0); return false; '>
     <input type='hidden' name='taggerId' value='{$taggerId}' />
     <input type='hidden' name='order'    value='{$order}' />
     <input type='hidden' name='dataType' value='{$dataType}' />
     <input type='hidden' name='perPage'  value='{$perPage}' />
     <img src='{$gBaseUrl}/images/Rewind";
    
    if($curPage <= 1) $html .= "-off";
    
    $html .= ".png'
          alt='Previous Page'
          onclick='Pagination($(\"{$thisForm}\"),\"-\",1)'
        />
     <img src='{$gBaseUrl}/images/Progress.gif' alt='Progress' style='padding-left:10px;padding-right:10px;display:none;' />
     <input
        type='text'
        name='PageNum'
        size='2' maxlength='3'
        value='{$curPage}'
        onkeypress='return Blocknon_numeric(event);'
        class='PageNum'
        onchange='Pagination($(\"{$thisForm}\"),\"-\",0)'
        title='This is the number of pages. Each page has {$perPage} items. You can select the page number that you are currently at and enter the page number that you want to go to? Try it.' />
     <span name='PageMax' class='PageNum'>/ $maxPage</span>
     <img style='margin-left:20px'
          src='{$gBaseUrl}/images/Forward";
            if (($maxPage > 0) && ($curPage >= $maxPage))
                $html .= "-off";
            
            $html .= ".png'
          alt='Next Page'
          onclick='Pagination($(\"{$thisForm}\"),\"+\",1)'
        />
    </form>";

    return ($html);
}

?>
