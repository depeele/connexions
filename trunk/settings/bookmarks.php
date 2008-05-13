<?php
/** @file
 *
 *  This is a connexions plug-in that implements the settings/bookmarks
 *  namespace.
 *      bookmarks_main          - present the top-level bookmark options.
 *      bookmarks_import        - if $_SERVER['REQUEST_METHOD'] is POST,
 *                                  invoke _bookmarks_importPost()
 *                                otherwise, present a form that describes
 *                                and enables importing.
 *      bookmarks_export        - if $_SERVER['REQUEST_METHOD'] is POST,
 *                                  invoke _bookmarks_exportPost()
 *                                otherwise, present a form that describes
 *                                and enables importing.
 *
 *      _bookmarks_importPost   - a private routine that validates inputs,
 *                                attempts to open the uploaded bookmark file
 *                                and, if everything looks OK, invokes
 *                                _bookmarks_importFile to
 *                                perform the import.
 *      _bookmarks_importFile   - a private routine that, when invoked from
 *                                _bookmarks_importPost() will parse the
 *                                open bookmarks file and perform the import.
 *      _bookmarks_exportPost   - a private routine that generates the export
 *                                version of bookmarks.
 */
require_once('settings/index.php');
require_once('lib/ua.php');

/** @brief  Output the page header
 *  @param  params  An array of incoming parameters.
 */
function bookmarks_header($area, $title, $params)
{
    global  $gTagging;

    /*
     * Generate the HTML and page headers
     */
    echo $gTagging->htmlHead($title);
    echo $gTagging->pageHeader(false, null,
                               $params['crumb']);
    flush();
}

/** @brief  Output the page footer */
function bookmarks_footer()
{
    global  $gTagging;

    echo $gTagging->pageFooter();
    flush();
}

/** @brief  Present the top-level bookmark settings.
 *  @param  params  An array of incoming parameters.
 */
function bookmarks_main($params)
{
    global  $gTagging;

    settings_nav($params);

    ?>
<div class='helpQuestion'>Bookmarks: management</div>
<div class='helpAnswer'>
 This section allows you to <a href='bookmarks/import'>import</a> or <a
 href='bookmarks/export'>export</a> your bookmarks.  For more information about
 bookmarks, please take a look at the <a href='<?= $gTagging->mBaseUrl?>/help/saving'>help section on
 bookmarks</a>.
</div>
<?php
}

/** @brief  Present a bookmark import form.
 *  @param  params  An array of incoming parameters.
 */
function bookmarks_import($params)
{
    global  $gTagging;
    global  $gUaKnown;
    global  $_SERVER;

    settings_nav($params);


    /***********************************************************************
     * First, if this is a POST then we are actually performing an import.
     *
     */
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
        return _bookmarks_importPost();


    $ua = strtolower($params['ua']);
    if ($ua != 'other')
    {
        $uaInfo = ua_get($ua);
        $ua     = $uaInfo['key'];
    }

    ?>
<style type='text/css'>
.bm_step
{
    margin:     0;
    font-size:  0.85em;
}
ul.bm_info
{
    margin:     0.5em 1.75em;
    font-size:  0.85em;
}
.bm_form
{
   font-size:  0.85em;
   line-height:1.2em;
}
.bm_form .grey
{   color: #666; }
.bm_form .area
{   margin-top: 2em; }
</style>
<div class='helpQuestion'>Bookmarks: import /upload</div>
<div class='helpAnswer'>
 If you have bookmarks stored in your browser, you can use this tool to
 transfer a copy of them into connexions.
</div>

<table border='0' cellpadding='0' cellspacing='0' style='margin:2em;'>
 <tr>
  <td width='40%' style='border-right:1px solid #ccc; padding-right:1em;' valign='top'>

  <h4 class='bm_step'>1. Save your browser bookmarks to a file</h4>
  <ul class='bm_info'><?php

  switch ($ua)
  {
  case 'safari':
      ?>
    <li>Choose "Export Bookmarks..." from the File menu</li>
    <li>Choose a location for your file and click: Save<br />
        &nbsp;<br />
        <b>Note:</b> for versions of Safari released before Mac OSX Tiger, you must use a third party tool to export your bookmarks to a file.
    </li>
<?php
      break;
  case 'opera':
      ?>
    <li>Choose "Import and Export..." from the File menu</li>
    <li>Choose "Export Opera bookmarks"<br />
        <span style='color:#f00;'>Important:</span> don't choose the similar looking "Export bookmarks as HTML" option, which is incompatible!</li>
    <li>Choose a location for your file and click: Save</li>
<?php
      break;
  case 'ie':
      ?>
    <li>Choose "Import and Export..." from the File menu</li>
    <li>Click Next, choose "Export Favorites" and click Next</li>
    <li>Choose which folder you want to export from and click Next</li>
    <li>Choose "Export to a File or Address", choose a location for your file and click Next</li>
    <li>Click Finish, then OK when it says "Successfully exported favorites"</li>
<?php
      break;
  case 'firefox':
      ?>
    <li>Choose "Manage Bookmarks" from the Bookmarks menu</li>
    <li>Choose "Export..." from the File menu</li>
    <li>Choose a location for your file and click: Save</li>
<?php
      break;
        
  default:
      ?>
    <li>Consult your browser's documentation to learn how to export your bookmarks to an HTML file</li>
    <li>Save the file to a known locationi, and proceed to step 2<br />
        &nbsp;<br />
        <b>Note:</b> The import may not be successful if you are using an unsupported browser.
<?php
      break;
  }
  ?>
  </ul>
<div class='helpAnswer' style='margin:1em 2em;font-size:.75em;'>
 (Show instructions for: <?php

    reset($gUaKnown);
    $first = true;
    foreach ($gUaKnown as $key => $info)
    {
        if ( ! is_array($info) )
            continue;

        if ($first) $first = false;
        else        echo ", ";

        if ( $uaInfo['name'] == $info['name'] )
            printf ("<b>%s</b>", $info['name']);
        else
            printf("<a href='?ua=%s'>%s</a>",
                   $key, $info['name']);
    }

    if ( $ua == 'other')
        printf (", <b>other</b>");
    else
        printf(", <a href='?ua=other'>other</a>");
 ?>)
</div>
  </td>

  <td width='60%' style='padding-left:1em;' valign='top'>

  <h4 class='bm_step'>2. Import the file to connexions</h4>
  <form enctype='multipart/form-data' method='post'>
   <div class='bm_form'>
    <!-- ********************************************************** -->
    <p style='margin-top:1em;'>
     <label for='bookmark_file'>Click browse and select the file you exported in step 1:</label><br />
    </p><?php

    /*************************************************************************
     * What's the largest file we can upload?
     * (This will be the minimum of 'upload_max_filesize' and 'post_max_size')
     */
    $maxFilesize = ini_get('upload_max_filesize');
    if (preg_match("/([0-9]+)\s*([KMGB])?/", $maxFilesize, $parts))
    {
        //printf ("maxFilesize[%s]:%u.%s, ",$maxFilesize, $parts[1], $parts[2]);
        switch (strtolower($parts[2]))
        {
        case 'k':   // kilo-bytes
            $maxFilesize = (int)$parts[1] * 1024;
            break;

        case 'm':   // mega-bytes
            $maxFilesize = (int)$parts[1] * 1024 * 1024;
            break;

        case 'g':   // giga-bytes
            $maxFilesize = (int)$parts[1] * 1024 * 1024 * 1024;
            break;
        case 'b':   // bytes
        default:
            $maxFilesize = (int)$parts[1];
            break;
        }
    }

    $maxPostsize = ini_get('post_max_size');
    if (preg_match("/([0-9]+)\s*([KMGB])?/", $maxPostsize, $parts))
    {
        //printf ("maxPostsize[%s]:%u.%s, ",$maxPostsize, $parts[1], $parts[2]);

        switch (strtolower($parts[2]))
        {
        case 'k':   // kilo-bytes
            $maxPostsize = (int)$parts[1] * 1024;
            break;

        case 'm':   // mega-bytes
            $maxPostsize = (int)$parts[1] * 1024 * 1024;
            break;

        case 'g':   // giga-bytes
            $maxPostsize = (int)$parts[1] * 1024 * 1024 * 1024;
            break;
        case 'b':   // bytes
        default:
            $maxPostsize = (int)$parts[1];
            break;
        }
    }

    $maxSize = ($maxFilesize < $maxPostsize
                    ? $maxFilesize
                    : $maxPostsize);

    ?>
    <p>
     <input type='hidden' name='MAX_FILE_SIZE' value='<? echo $maxSize;?>' />
     <input id='bookmark_file' name='bookmark_file' type='file' size='30' />
    </p>
    <p class='grey'>(Maximum file size of <?php

    if ($maxSize > (1024 * 1024 * 1024))
        printf ("%3.2f GB", $maxSize / (1024 * 1024 * 1024));
    else if ($maxSize > (1024 * 1024))
        printf ("%3.2f MB", $maxSize / (1024 * 1024));
    else if ($maxSize > 1024)
        printf ("%3.2f KB", $maxSize / 1024);
    else if ($maxSize)
        printf ("%u Bytes", $maxSize);

    ?>)</p>

    <!-- ********************************************************** -->
    <p class='area'>
     <label for='tags'>Tags will be added to your bookmarks based on the folder structure of your bookmarks.  For example, a bookmark in the &ldquo;recipes&rdquo; folder within the &ldquo;household&rdquo; folder will be tagged with both &ldquo;household&rdquo; and &ldquo;recipes&rdquo;.  You can also choose additional tags here that will be applied to all the imported bookmarks.</label><br />
    </p>
    <p>
     <input id='tags' name='tags' type='text' value='imported' size='40' />
    </p>
    <p class='grey'>(This is a comma separated list of tags.  It is recommended
            that you use the default tag &ldquo;imported&rdquo; to make it
            easier to view all imported items in one list by visiting
            <?php
                printf ("http%s://%s%s/%s/imported",
                        ($_SERVER['HTTPS'] == 'on' ? "s" : ""),
                        $_SERVER['HTTP_HOST'],
                        $gTagging->mBaseUrl,
                        $gTagging->mCurUser['name']);
            ?>)
    </p>
    <!-- ********************************************************** -->
    <p class='area'>
     Should imported bookmarks be marked private or shared?
    </p>
    <p>
     <input id='accessible_private' name='accessible' type='radio' value='private' checked />
     <label for='accessible_private'>private</label><br />
     <input id='accessible_shared' name='accessible' type='radio' value='shared'/>
     <label for='accessible_shared'>shared</label><br />
    </p>
    <p style='margin:1em 2em;'>
     <b>Note:</b> If privacy is set within the incoming bookmarks (e.g. the
             items have 'PRIVATE="yes/no"'), that will override this setting.
     Otherwise, mark all items either private or shared.  No matter what the
     case may be, you may later change the privacy setting for any item
     individually.  You are encouraged to share as many as possible after
     importing so everyone can benefit.
    </p>

    <!-- ********************************************************** -->
    <p class='area'>
     What should happen if a bookmark you are importing already exists
     in your connextions bookmarks?
    </p>
    <p>
     <input id='conflict_replace' name='conflict' type='radio' value='replace' />
     <label for='conflict_replace'>Replace my connexions bookmark with the imported one</label><br />
     <input id='conflict_ignore' name='conflict' type='radio' value='ignore' checked />
     <label for='conflict_ignore'>Only import items I don't already have</label><br />
    </p>

    <!-- ********************************************************** -->
    <p class='area'>
     Would you like to test the import before actually importing?
    </p>
    <p>
     <input id='test_1' name='test' type='radio' value='yes'/>
     <label for='test_1'>yes, please</label><br />
     <input id='test_0' name='test' type='radio' value='no' checked/>
     <label for='test_0'>no, just do it</label><br />
    </p>

    <p style='margin:1em 2em;'>
     <b>Note:</b> If you choose <i>yes</i> you will see the output of a test import, but no bookmarks will actually be imported.  If you are pleased with the results, go back one page, uncheck 'test' and submit again.
    </p>

    <!-- ********************************************************** -->
    <p class='area'>
     <input type='submit' value='import' />
    </p>
   </div>
  </form>
  </td>
 </tr>
</table>

<?php
}

/** @brief  Import bookmarks based upon the incoming post information.
 *
 *  _POST should include:
 *      - tags          a comma separated list of tags to apply to incoming
 *                      bookmarks
 *      - conflict      how should conflicts be handled:
 *                          - ignore  (ignore the new bookmark)
 *                          - replace (replace the existing bookmark)
 *      - accessible    how should bookmarks be listed:
 *                          - shared
 *                          - private
 *      - test          Should we test first?
 *                          - yes
 *                          - no
 *
 *
 *  _FILES should contain a file that contains the bookmark data:
 *      - name
 *      - type
 *      - tmp_name
 *      - error
 *      - size
 */
function _bookmarks_importPost()
{
    global  $gTagging;
    global  $_SERVER;
    global  $_POST;
    global  $_FILES;

    if ($_SERVER['REQUEST_METHOD'] != 'POST')
    {
        // Only valid if posted -- redirect to bookmarks_import()
        return bookmarks_import();
    }

    $curUserId = $gTagging->authenticatedUserId();

    /*printf("_POST %u, %u<pre>",isset($_POST['tags']),empty($_POST['conflict']));
        var_dump($_POST); echo "</pre>";
    printf("_FILES (%u, %u, %s, %u)<pre>",
            count($_FILES),
            $_FILES['bookmark_file']['size'],
            $_FILES['bookmark_file']['tmp_name'],
            file_exists($_FILES['bookmark_file']['tmp_name']));

        var_dump($_FILES); echo "</pre>";*/

    // Start by validating the input
    if ( ($curUserId === false)                   ||
         (! isset($_POST['tags']))                ||
         (  empty($_POST['conflict']))            ||
         (  empty($_POST['accessible']))          ||
         (count($_FILES) != 1)                    ||
         (! isset($_FILES['bookmark_file']))      ||
         ($_FILES['bookmark_file']['size'] < 1)   ||
         (! file_exists($_FILES['bookmark_file']['tmp_name'])) )
    {
        ?>
<p>
 <span style='color:#f00;font-weight:bold;'>Import error</span>:
 there are one or more missing variables...
</p><?php
        return;
    }

    if ($_POST['test'] == 'no')
        $test = 'no';
    else
        $test = 'yes';

    /************************************
     * Parse an import the bookmarks.
     *
     */
    $fileName = $_FILES['bookmark_file']['tmp_name'];

    $fh       = fopen($fileName, "r");
    if ($fh === false)
    {
        ?>
<p>
 <span style='color:#f00;font-weight:bold;'>Import error</span>:
 cannot access the uploaded file...
</p><?php
        return;
    }

    list($numImported, $numIgnored, $numErrors, $numBookmarks, $numFolders) =
            _bookmarks_importFile($fh,
                                    $_POST['tags'],
                                    $_POST['conflict'],
                                    $_POST['accessible'],
                                    $test);

    fclose($fh);

    printf ("<p style='margin:2em 0 0 2em;font-size:1.1em;'><b>Success%s:</b> imported %u bookmarks, ignored %u, and encountered %u errors (%u total in %u folders).</p>\n",
                                ($test == 'yes' ? "ful Test " : ""),
                                $numImported, $numIgnored, $numErrors,
                                $numBookmarks, $numFolders);

    if ($test == 'yes')
    {
        printf ("<p style='margin:0 0 0 4em;'>If you are pleased with these test results, go <a href='javascript:history.back()'>back to the previous page</a>, click 'no, just do it', and resubmit.</p>\n");
    }
}

/** @brief  Given new item information, handle it.
 *  @param  item    Item information:
 *                      id, url, name, description, tagStr, rating,
 *                      isFavorite, isPrivate
 *  @param  state   Processing state:
 *                      addIt, test, numImported, numErrors, numIgnored
 */
function _bookmarks_add($item, $state)
{
    global  $gTagging;

    $rc = false;

    // We have all item information now so act upon it.
    if ($state['addIt'])
    {
        // Attempt to add/modify
        if ($state['test'] == 'yes')
        {
            $rc = true;
        }
        else
        {
            $rc = $gTagging->itemModify($item['id'],
                                        $item['url'],
                                        $item['name'],
                                        $item['description'],
                                        $item['tagStr'],
                                        $item['rating'],
                                        $item['isFavorite'],
                                        $item['isPrivate']);
        }

        if ($rc === true)
        {
            printf ("<a class='bm_name' href='%s'>%s</a> &mdash; <span class='bm_tag'>%s</span>",
                    $item['url'], $item['name'], $item['tagStr']);
            if ($item['rating']> 0)
                printf (", rating %u", $item['rating']);
            //if ($item['isPrivate'] != $state['defPrivacy'])
            {
                echo ", ";
                if ($item['isPrivate'])
                    echo "<span style='color:#f00;'>private</span>";
                else
                    echo "<span style='color:#0f0;'>shared</span>";
            }
            if ($item['isFavorite'])
                printf (", favorite");

            echo "<br />\n";
            if (! empty($item['description']))
            {
                printf("<blockquote style='margin:0 2em;'>%s</blockquote>",
                        htmlentities($item['description']));
            }

            $state['numImported']++;
        }
        else
        {
            printf ("<span style='color:#f00;font-weight:bold;'>*** Error importing</span> <a class='bm_name' href='%s'>%s</a> &mdash; <span class='bm_tag'>%s</span><br />\n",
                    $item['url'], $item['name'], $item['tagStr']);
            $state['numErrors']++;
        }
    }
    else
    {
        // Do NOT add/modify this item - something is amiss...
        if ($item['id'] > 0)
        {
            // Duplicate
            printf ("<span style='color:#00f;font-weight:bold;'>Ignore duplicate</span> <a class='bm_name' href='%s'>%s</a> &mdash; <span class='bm_tag'>%s</span><br />\n",
                    $item['url'], $item['name'], $item['tagStr']);
        }
        else
        {
            // Ignored because of the protocol
            preg_match('/^([^:]+):/', $item['url'], $protocol);

            printf ("<span style='color:#f0f;font-weight:bold;'>Ignore protocol '%s'</span> <a class='bm_name' href='%s'>%s</a> &mdash; <span class='bm_tag'>%s</span><br />\n",
                    $protocol[1], $item['url'], $item['name'], $item['tagStr']);
        }

        $state['numIgnored']++;
    }

    return ($state);
}

/** @brief  Import bookmarks from the provided file handle.
 *  @param  fh          An open file handle to the file to import.
 *  @param  tags        A comma separated list of tags to apply to incoming
 *                      bookmarks
 *  @param  conflicts   How should conflicts be handled:
 *                          - ignore  (ignore the new bookmark)
 *                          - replace (replace the existing bookmark)
 *  @param  accessible  How should bookmarks be listed:
 *                          - shared
 *                          - private
 *  @param  test        Don't actually import, just test?
 *                          - yes
 *                          - no
 *
 *  @return An array of (numImported,
 *                       numIgnored,
 *                       numErrors,
 *                       numBookmarks,
 *                       numFolders)
 */
function _bookmarks_importFile($fh, $tags, $conflicts, $accessible, $test)
{
    global  $gTagging;

    $state = array(
                    'numBookmarks'  => 0,
                    'numFolders'    => 0,
                    'numImported'   => 0,
                    'numIgnored'    => 0,
                    'numErrors'     => 0,
                    'userId'        => $gTagging->getCurUserId(),
                    'defPrivacy'    => ($accessible == 'private'),
                    'level'         => 0,
                    'test'          => $test,
                    'addIt'         => false,
                  );

    /* Limit the length of tags to the maximum tag size. */
    $tagStack = preg_split('#\s*[/,+]\s*#', $tags);
    foreach ($tagStack as $idex=>$tag)
    {
        if (strlen($tag) > $gTagging->mTagSize)
        {
            $tagStack[$idex] = substr($tag, 0, $gTagging->mTagSize);
        }
    }
    $tags = implode(',', $tagStack);
    $state['tagStack'] = $tagStack;

    ?>
<style type='text/css'>
.bm_list
{
    margin:0 2.0em;
    font-size: 0.8em;
}
a.bm_name
{
    font-size: 1.10em;
    margin-right: 1ex;
}
.bm_tag
{
    color:      #999;
    font-size:  0.85em;
    font-style: italic;
}
</style>
<?php

    printf ("<h4 style='margin:1em 0 0 1em;'>%sImporting bookmarks using default tag%s '<span class='bm_tag'>%s</span>', default privacy of <span style='color:%s'>%s</span>, %s</h4>\n",

              ($test == 'yes' ? "Test " : ""),
              (count($state['tagStack']) == 1 ? "" : "s"), $tags,
              ($state['defPrivacy'] ? '#f00' : '#0f0'), $accessible,
              ($conflicts == 'replace'
                ? 'replacing existing bookmarks'
                : 'keeping existing bookmarks'));
    echo "<div class='bm_list'>\n";

    $item = null;
    while (!feof($fh))
    {
        $line = trim(fgets($fh));
        if ($line === false)
        {
            printf ("*** Read error<br />\n");
            continue;
        }

        //printf("line[%s]<br />\n", htmlentities($line));

        if (preg_match('/^<DT><A HREF="([^"]+)([^>]*?)>([^<]+)<\/A>/i',
                                                            $line, $markInfo))
        {
            if (is_array($item))
            {
                // We have delayed item information that needs to be processed.
                $state = _bookmarks_add($item, $state);
                $item  = null;
            }

            /* New bookmark:
             *  - 1 == url
             *  - 2 == rest of <a params>
             *  - 3 == bookmark name
             */
            $state['numBookmarks']++;
            $item['url']        = $markInfo[1];
            $params             = $markInfo[2];
            $item['name']       = $markInfo[3];
            $item['description']= '';
            $item['tagStr']     = implode(',', $state['tagStack']);
            $item['isFavorite'] = false;
            $item['isPrivate']  = $state['defPrivacy'];
            $item['haveInfo']   = true;

            /* See if additional information is available in the parameters:
             *  - TAGS      comma-separated list of tags for this item
             *  - RATING    item rating
             *  - FAVORITE  if this item a favorite (yes/no)
             *  - PRIVATE   if this item private (yes/no)
             */
            if (! empty($params))
            {
                $keyvals = preg_split('/\s+/', $params);
                for ($idex = 1; $idex < count($keyvals); $idex++)
                {
                    list($key, $val) = split('=', $keyvals[$idex]);
                    $val = preg_replace('/"/', '', $val);
                    //printf ("key[%s], val[%s]<br />\n", $key, $val);

                    switch ($key)
                    {
                    case 'TAGS':
                        if (! empty($item['tagStr']))
                            $item['tagStr'] .= ',';
                        $item['tagStr'] .= $val;
                        break;

                    case 'RATING':
                        $item['rating'] = (int)$val;
                        break;
                    case 'FAVORITE':
                        if (($val === 'yes') || ($val === 'true'))
                            $item['isFavorite'] = true;
                        break;
                    case 'PRIVATE':
                        if (($val === 'yes') || ($val === 'true'))
                            $item['isPrivate'] = true;
                        else
                            $item['isPrivate'] = false;
                        break;
                    default:
                        // Ignore all others (e.g. LAST_VISIT, ADD_DATE)
                        break;
                    }
                }
            }

            /*printf ("%u%s '%s', url[%s], tags{%s}, rating[%u], favorite[%s], private[%s]<br />\n",
                    $state['level'], str_repeat('|&nbsp;',$state['level']),
                    $item['name'], $item['url'],
                    $item['tagStr'], $item['rating'],
                    ($item['isFavorite']? 'yes' : 'no'),
                    ($item['isPrivate']? 'yes' : 'no'));*/

            /*
             * If 'conflicts' == 'replace', simply add (which will modify the
             *                              item with the current information
             *                              if it exists) otherwise we need to
             *
             * First, retrieve the top-level item (if it exists).
             */
            $state['addIt'] = true;

            if (! preg_match('/^(http|ftp|gopher|docid)/i', $item['url']))
            {
                // Ignore this url/protocol
                $state['addIt'] = false;
            }
            else
            {
                $item['id'] = $gTagging->mTagDB->itemId($url);

                if (($item['id'] > 0) && ($conflicts != 'replace'))
                {
                    // See if this user already has a bookmark for this item.
                    $detailId = $gTagging->mTagDB->userItem($state['userId'],
                                                            $item['id']);
                    $state['addIt'] = is_array($detailId);
                }
            }

            // Postpone the actual add until we see if there is a description.
            continue;
        }

        if (preg_match('/^<DD>(.*)/', $line, $markInfo))
        {
            if (is_array($item))
            {
                $item['description'] = $markInfo[1];
                // We have delayed item information that needs to be processed.
                $state = _bookmarks_add($item, $state);
                $item  = null;
            }
        }

        if (preg_match('/^<\/DL>/', $line))
        {
            if ($state['level'] > 0)
            {
                array_pop($state['tagStack']);
                $state['level']--;
            }
        }
        if (preg_match('/^<DT><H3([^>]+)>([^<]+)<\/H3>/i', $line, $folderInfo))
        {
            /* New folder
             *  - 1 == reset of <h3 params>
             *  - 2 == folder name
             */
            $state['numFolders']++;
            $params = $folderInfo[1];
            $name   = $folderInfo[2];

            if (strlen($name) > $gTagging->mTagSize)
            {
                $name = substr($name, 0, $gTagging->mTagSize);
            }

            /*printf ("%u%s+ '%s'<br />\n",
                    $state['level'], str_repeat('|&nbsp;',$state['level']),
                    $name);*/
            $state['level']++;
            array_push($state['tagStack'], $name);
        }
    }
    echo "</div>\n";

    return array($state['numImported'], $state['numIgnored'],
                 $state['$numErrors'],
                 $state['numBookmarks'], $state['numFolders']);
}

/** @brief  Export bookmarks based upon the incoming post information.
 *
 *  _POST should include:
 *      - includetags   Should we include tags?
 *                          - yes
 *                          - no
 *      - includenotes  Should we include notes (along with privacy, favorite
 *                      and rating information)?
 *                          - yes
 *                          - no
 */
function _bookmarks_exportPost()
{
    global  $gTagging;
    global  $_POST;

    if ($_SERVER['REQUEST_METHOD'] != 'POST')
    {
        // Only valid if posted -- redirect to bookmarks_export()
        // and fake the params...
        $params = array('Action'    => 'main:settings',
                        'params'    => null,
                        'crumb'     =>
                            "<a href='/connexions-test/settings'>settings</a>".
                                " / ".
                            "<a href='/connexions-test/settings/bookmarks'>".
                                "bookmarks</a>".
                                " / export"
                        );
        return bookmarks_export($params);
    }

    $curUserId = $gTagging->authenticatedUserId();

    /*echo "<pre>_POST";
        var_dump($_POST); echo "</pre>";*/

    // Start by validating the input
    if ($curUserId === false)
    {
        ?>
<p>
 <span style='color:#f00;font-weight:bold;'>Export error</span>:
 there are one or more missing variables...
</p><?php
        return;
    }

    /************************************
     * Generate an export file from the
     * bookmarks.
     *
     */
    header('Content-Type: text/html');
    header('Content-Encoding: utf-8');

    echo '<!DOCTYPE NETSCAPE-Bookmark-file-1>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
<!-- This is an automatically generated file.
It will be read and overwritten.
Do Not Edit! -->
<TITLE>Bookmarks</TITLE>
<H1>Bookmarks</H1>

<DL><p>
';

    $userId = $gTagging->getCurUserId();
    $posts  = $gTagging->mTagDB->userItems(array($userId),
                                           null,            // tags
                                           $userId,         // curUser
                                           'tagged_on ASC', // orderBy
                                           -1);             // non-paged
    //printf ("%u posts for user #%u<br />\n", count($posts), $userId);
    foreach ($posts as $key => $post)
    {
        //$_POST['includetags'], $_POST['includenotes'];
        $post['timestamp'] = strtotime($post['tagged_on']);

        printf ('<DT><A HREF="%s" LAST_VISIT="%u" ADD_DATE="%u"',
                $post['url'],
                $post['timestamp'],
                $post['timestamp']);

        if (($_POST['includetags'] == 'on')   ||
            ($_POST['includetags'] == 'yes')  ||
            ($_POST['includetags'] == 'true') ||
            ($_POST['includetags'] == 1))
        {
            $tags = $gTagging->mTagDB->itemTags(array($userId),
                                                array((int)$post['itemid']));

            $tagStr = '';
            foreach ($tags as $idex => $tagInfo)
            {
                if (! empty($tagStr))   $tagStr .= ',';
                $tagStr .= $tagInfo['tag'];
            }

            printf (' TAGS="%s"', $tagStr);
        }

        if (($_POST['includenotes'] == 'on')   ||
            ($_POST['includenotes'] == 'yes')  ||
            ($_POST['includenotes'] == 'true') ||
            ($_POST['includenotes'] == 1))
        {
            printf (' RATING="%u" FAVORITE="%s" PRIVATE="%s"',
                    $post['rating'],
                    ($post['is_favorite'] ? "yes" : "no"),
                    ($post['is_private']  ? "yes" : "no"));
        }

        printf (">%s</A>\n", htmlentities($post['name']));

        if (! empty($post['description']))
        {
            printf ("<DD>%s\n", htmlentities($post['description']));
        }
    }

    echo '
</DL><p>
';
}

/** @brief  Export bookmarks.
 *  @param  params  An array of incoming parameters.
 */
function bookmarks_export($params)
{
    global  $gTagging;
    global  $_SERVER;

    /***********************************************************************
     * First, if this is a POST then we are actually performing an export.
     *
     */
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
        return _bookmarks_exportPost();

    //echo "<pre>bookmarks_export:params"; print_r($params); echo "</pre>\n";
    bookmarks_header('bookmarks', 'User Settings', $params);

    settings_nav($params);

    ?>
<style type='text/css'>
.bm_step
{
    margin:     0;
    font-size:  0.85em;
}
ul.bm_info
{
    margin:     0.5em 1.75em;
    font-size:  0.85em;
}
.bm_form
{
   font-size:  0.85em;
   line-height:1.2em;
}
.bm_form .grey
{   color: #666; }
.bm_form .area
{   margin-top: 2em; }
</style>
<div class='helpQuestion'>Bookmarks: export / backup</div>
<div class='helpAnswer'>
 This tool creates a list of all your bookmarks in a format understandable by
 most browsers.  You can save the generate page (as HTML) and import it into
 your browser (or anything that accepts bookmarks in a standard format).

  <form enctype='multipart/form-data' method='post'>
   <div class='bm_form'>
    <!-- ********************************************************** -->
    <p style='margin-top:1em;'>
     <input type='checkbox' name='includetags' id='includetags' checked='checked' />
     <label for='includetags'>include tags?<br />

     <input type='checkbox' name='includenotes' id='includenotes' checked='checked' />
     <label for='includenotes'>include notes, privacy, favorite and rating information?<br />
    </p>

    <p style='margin:1em 2em;'>
     <b>Note:</b> If you choose to include tags and/or notes, these <b>will</b>
     be included though you many applications will ignore them.</p>

    <!-- ********************************************************** -->
    <p class='area'>
     <input type='submit' value='export' />
    </p>
   </div>
  </form>
</div>
<?php

    bookmarks_footer();
}
?>
