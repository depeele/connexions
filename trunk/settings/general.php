<?php
/** @file
 *
 *  This is a connexions plug-in that implements the settings/general
 *  namespace.
 *      general_main          - present the top-level bookmark options.
 *      general_import        - if $_SERVER['REQUEST_METHOD'] is POST,
 *                                  invoke _general_importPost()
 *                                otherwise, present a form that describes
 *                                and enables importing.
 */
require_once('settings/index.php');
require_once('lib/ua.php');

/** @brief  Present the top-level bookmark settings.
 *  @param  params  An array of incoming parameters.
 */
function general_main($params)
{
    settings_nav($params);
    if ($params['SettingAction'] == 'Update')
    {
        _general_update($params);
    }

    _general_form($params);
}

/** @brief  Present a form to allow modification of user information.
 *  @param  params  An array of incoming parameters.
 */
function _general_form($params)
{
    global  $gTagging;
    global  $gBaseUrl;
    global  $gUserPhotos;

    $user  = $gTagging->mCurUser;

    /*$html  = "user:<pre>";
    $html .= htmlspecialchars(var_export($user,true));
    $html .= "</pre>";*/

    $html .= "
<style type='text/css'>
.gen_form, .gen_form td
{
   margin:      1em;
   font-size:   0.85em;
   line-height: 1.2em;
}
.gen_form .grey
{   color: #666; }
.gen_form .area
{   margin-top: 2em; }
.gen_form .small
{   font-size: 0.7em; }
.gen_form .userImg
{
    float:  left;
    width:  50px;
    height: 50px;
    margin: 0 10px;
    border: 1px solid #999;
}
</style>
<form name='userInfo' class='gen_form' enctype='multipart/form-data' method='post'>
 <input type='hidden' name='userId' value='{$user['userid']}' />
 <input type='hidden' name='SettingAction' value='Update' />
 <div class='area'>
   <label for='User.fullName'>Full Name</label><br />
   <input type='text' class='textinput' name='fullName' value='{$user['fullName']}' id='User.fullName' />
 </div>
 <div class='area'>
   <label for='User.email'>Email</label><br />
   <input type='text' class='textinput' name='email' value='{$user['email']}' id='User.email' />
 </div>";

    if ($gUserPhotos)
    {
        $html .= "
 <div class='area'>
   <div style='float:left;'>
     <label for='User.picture'>Choose a Picture</label>
     <table border='0' style='margin-left:1em;'>
      <tr>
       <td>upload:</td>
       <td><input type='file' class='textinput' name='picture_file' id='User.picture_file' /></td>
      </tr>
      <tr>
       <td>url:</td>
       <td><input type='text' class='textinput' name='picture_url' id='User.picture_url' size='30' value='{$user['pictureUrl']}' /></td>
      </tr>
      <tr>
       <td>&nbsp;</td>
       <td class='small' style='padding:0 8px;'>
         Your picture will be resized to 50x50 pixels.<br />
         If it is not already in a square format, this will<br />
         cause it to look stretched.
       </td>
      </tr>
     </table>
   </div>
   <img class='userImg' src='{$user['pictureUrl']}'/>
   <br style='clear:both;' />
 </div>";
    }

    $html .= "
 <div class='area'>
   <label for='User.profile'>Profile</label><br />
   <textarea class='textinput' name='profile' id='User.profile' rows='10' cols='40'>{$user['profile']}</textarea>
 </div>
 <div class='area'>
   <input type='submit' value='Update' />
 </div>
</form>
";

    echo $html;
}

/** @brief  Present a form to allow modification of user information.
 *  @param  params  An array of incoming parameters.
 */
function _general_update($params)
{
    global  $_FILES;
    global  $gTagging;

    $userId   = $params['userId'];
    $userInfo = $gTagging->mTagDB->user($userId);

    $newInfo['fullName']     = $params['fullName'];
    $newInfo['email']        = $params['email'];
    $newInfo['profile']      = $params['profile'];

    $file = $_FILES['picture_file'];
    if (! empty($file['name']))
    {
        global  $gBaseUrl;

        //$file = $_FILES['picture_file'];
        $ext  = substr(strrchr($file['name'], '.'), 1);

        $baseFile  = "/images/users/{$userInfo['name']}.$ext";
        $localFile = dirname($_SERVER['SCRIPT_FILENAME']) . $baseFile;
        $localUrl  = $gBaseUrl . $baseFile;

        /* Copy the file from it's temporary location. */
        /*echo "file:<pre>";
        print_r($file);
        echo "</pre>";
        echo "ext [$ext]<br />\n";
        echo "baseFile [$baseFile]<br />\n";
        echo "localFile [$localFile]<br />\n";
        echo "localUrl [$localUrl]<br />\n";*/

        if (! copy($file['tmp_name'], $localFile))
        {
            echo "<div class='error'>*** Cannot copy temporary file into place</div>\n";
            return;
        }

        $newInfo['pictureUrl'] = $localUrl;
    }
    else
    {
        if (empty($userInfo['pictureUrl']))
            $newInfo['pictureUrl']  = $params['picture_url'];
        else
            $newInfo['pictureUrl']  = $userInfo['pictureUrl'];
    }

    echo "<div class='success'>Your user information has been successfully updated.</div>";

    /*
    echo "newInfo:<pre>";
    print_r($newInfo);
    echo "</pre>";
    */

    /* Attempt to update the user record. */
    if (! $gTagging->mTagDB->userModify($userId, $newInfo))
    {
        echo "<div class='error'>*** Cannot update the record</div>\n";
    }
    else
    {
        // Update gTagging->mCurUser
        $userInfo = $gTagging->mTagDB->user($userId);
        if (is_array($userInfo))
            $gTagging->mCurUser = $userInfo;
    }
}

?>
