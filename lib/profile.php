<?php
/*****************************************************************************
 * Profiling
 *
 */

$gProfile   = null;

/** @brief  Initialize the use of profiling.
 *  @param  file    The file to output profiling results to
 */
function profile_init   ($file)
{
    global  $gProfile;

    if ( ($gProfile == null) || (! is_a($gProfile, 'Profile')) )
    {
        $gProfile = new Profile($file);
    }

    return;
}


/** @brief  Profiling class */
class   Profile
{
    var $mFH        = null;
    var $mProfiles  = array();
    var $mUid       = 1;

    /** @brief  Initialize all required global information. */
    function    Profile($fileName   = null)
    {
        if (is_null($fileName))
            $this->mFH = fopen("php://output", 'w');
        else
            @$this->mFH = fopen($fileName, 'w');

        if (! $this->mFH)
        {
            die ("*** Profile: Cannot open file '$fileName' for write");
            return null;
        }
    }

    /** @brief  Destructore (MUST be called directly prior to unset) */
    function    destroy()
    {
        if (! is_null($this->mFH))
        {
            // Stop all current profiles
            foreach($this->mProfiles as $uid => $ts)
            {
                $this->stop($uid, "destroy");
            }

            fclose($this->mFH);

            $this->mFH       = null;
            $this->mProfiles = array();
            $this->mUid      = 1;
        }
    }

    /** @brief  Begin profiling.
     *  @param  uid     The unique identifier to associate with this profile.
     *  @param  ...     variable arguments that will be passed to sprintf
     *                  to include additional information in the profile.
     *
     *  @return The unique identifier for this profile information.
     */
    function    start($uid)
    {
        return ( $this->vstart(func_get_args()) );
    }

    /** @brief  Checkpoint our profiling.
     *  @param  uid     The unique identifier of the desired profile.
     *  @param  ...     variable arguments that will be passed to sprintf
     *                  to include additional information in the profile.
     */
    function    checkpoint($uid)
    {
        return ( $this->vcheckpoint(func_get_args()) );
    }

    /** @brief  Finish our profiling.
     *  @param  uid     The unique identifier of the desired profile.
     *  @param  ...     variable arguments that will be passed to sprintf
     *                  to include additional information in the profile.
     */
    function    stop($uid)
    {
        return ( $this->vstop(func_get_args()) );
    }

    /************************************************************************
     * Argument array versions.
     *
     */

    /** @brief  Begin profiling.
     *  @param  argv    An array of arguments.
     *
     *  Parameters:
     *      uid
     *      sprintf format
     *      sprintf arguments
     *
     *  @return The unique identifier for this profile information.
     */
    function    vstart($argv)
    {
        if (is_null($this->mFH))    return 0;

        $argc = count($argv);
        $uid  = 0;
        if ($argc > 0)
        {
            // The first argument should be the uid
            $uid = array_shift($argv);
            $argc--;
        }

        if ($uid === 0)
        {
            // Assign a unique identifier
            $uid = $this->mUid++;
        }

        $tsNow = time() + microtime();
        $argc  = count($argv);

        if ($argc > 0)
        {
            $fmt       = array_shift($argv);
            $callerStr = vsprintf($fmt, $argv);
        }
        else
        {
            $callerStr = '';
        }

        $this->mProfiles[$uid] = $tsNow;

        fwrite($this->mFH,
                sprintf("+%s @%8.4f:   start  : %s\n",
                          $uid, $tsNow, $callerStr) );
        fflush($this->mFH);

        return ($uid);
    }

    /** @brief  Checkpoint our profiling.
     *  @param  argv    An array of arguments.
     *
     *  Parameters:
     *      uid
     *      sprintf format
     *      sprintf arguments
     */
    function    vcheckpoint($argv)
    {
        if (is_null($this->mFH))    return false;

        $argc = count($argv);
        $uid  = 0;
        if ($argc > 0)
        {
            // The first argument should be the uid
            $uid = array_shift($argv);
            $argc--;
        }

        if ( (uid === 0) || (! isset($this->mProfiles[$uid])) )
        {
            // Don't know about a profile with this uid.
            printf ("*** Profile::checkpoint: unknown uid[%s]\n", $uid);
            return (false);
        }

        $tsNow  = time() + microtime();
        $tsBeg  = $this->mProfiles[$uid];

        if ($argc > 0)
        {
            $fmt       = array_shift($argv);
            $callerStr = vsprintf($fmt, $argv);
        }
        else
        {
            $callerStr = '';
        }

        fwrite($this->mFH,
                sprintf(".%s @%8.4f: %8.4fs: %s\n",
                          $uid, $tsNow, ($tsNow - $tsBeg), $callerStr) );
        fflush($this->mFH);

        return (true);
    }

    /** @brief  Finish our profiling.
     *  @param  argv    An array of arguments.
     *
     *  Parameters:
     *      uid
     *      sprintf format
     *      sprintf arguments
     */
    function    vstop($argv)
    {
        if (is_null($this->mFH))    return false;

        $argc = count($argv);
        $uid  = 0;
        if ($argc > 0)
        {
            // The first argument should be the uid
            $uid = array_shift($argv);
            $argc--;
        }

        if ( (uid === 0) || (! isset($this->mProfiles[$uid])) )
        {
            // Don't know about a profile with this uid.
            printf ("*** Profile::stop: unknown uid[%s]\n", $uid);
            return (false);
        }

        $tsNow  = time() + microtime();
        $tsBeg  = $this->mProfiles[$uid];

        if ($argc > 0)
        {
            $fmt       = array_shift($argv);
            $callerStr = vsprintf($fmt, $argv);
        }
        else
        {
            $callerStr = '';
        }

        fwrite($this->mFH,
                sprintf("-%s @%8.4f: %8.4fs: %s\n",
                          $uid, $tsNow, ($tsNow - $tsBeg), $callerStr) );
        fflush($this->mFH);

        unset($this->mProfiles[$uid]);

        return (true);
    }
}

?>
