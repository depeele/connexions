<?php
/** @file
 *
 *  Performance profiling singleton
 *
 */

class   Connexions_Profile
{
    private static  $_log       = null;
    private static  $_profiles  = array();
    private static  $_uid       = 1;

    /** @brief  Initialize all required global state.
     *  @param  logger  A Zend_Log instance -- null will cause a registry
     *                  lookup for 'log', if that fails, logging will be
     *                  performed on standard out.
     *
     */
    public static function  init(Zend_Log $logger   = null)
    {
        if ($logger === null)
        {
            $logger = Zend_Registry::get('log');
        }

        self::$_log = $logger;
    }

    /** @brief  Begin profiling.
     *  @param  uid     A unique identifier to associate with this profile.
     *  @param  ...     Variable arguments that will be passed to sprintf to
     *                  include additional information in the profile.
     *
     *  @return The unique identifier for this profile information.
     */
    public static function  start($uid  = null)
    {
        $argv = func_get_args();
        $uid  = array_shift($argv);     // exlucde $uid from $argv
        return ( self::vaction( 'start', $uid, $argv) );
    }

    /** @brief  Profiling checkpoint -- take measurements at this point.
     *  @param  uid     The unique identifier of the desired profile.
     *  @param  ...     Variable arguments that will be passed to sprintf to
     *                  include additional information in the profile.
     *
     *  @return The unique identifier for this profile information.
     */
    public static function  checkpoint($uid)
    {
        $argv = func_get_args();
        $uid  = array_shift($argv);     // exlucde $uid from $argv
        return ( self::vaction( 'checkpoint', $uid, $argv) );
    }

    /** @brief  Finish/Stop profiling.
     *  @param  uid     The unique identifier of the desired profile.
     *  @param  ...     Variable arguments that will be passed to sprintf to
     *                  include additional information in the profile.
     *
     *  @return The unique identifier for this profile information.
     */
    public static function  stop($uid)
    {
        $argv = func_get_args();
        $uid  = array_shift($argv);     // exlucde $uid from $argv
        return ( self::vaction( 'stop', $uid, $argv) );
    }

    /**********************
     * Varadic helpers
     *
     */

    /** @brief  Begin profiling
     *  @param  action  The profiling action ('start', 'checkpoint', end');
     *  @param  uid     The uid of the desired profile;
     *  @param  argv    sprintf arguments (fmt, ...);
     *
     *  @return The unique identifier for this profile.
     */
    public static function  vaction($action, $uid, $argv)
    {
        $measure = new Connexions_ProfileMeasure();

        // Generate / validate the uid based upon the current action.
        if ($action === 'start')
        {
            if (($uid === 0) || ($uid === null))
            {
                // Assign a unique identifier
                $uid = self::$_uid++;
            }
        }
        else
        {
            if (($uid === 0) || ($uid === null))
                $uid = self::$_uid;

            if ( ! isset(self::$_profiles[$uid]) )
            {
                printf ("*** Profile::%s: unknown uid[ %s ]\n",
                        $action, $uid);
                return false;
            }
        }

        // Generate the output string.
        $argc    = count($argv);
        if ($argc > 0)
        {
            $fmt       = array_shift($argv);
            $callerStr = vsprintf($fmt, $argv);
        }
        else
        {
            $callerStr = $action;
        }

        // Locate the profile to compare against.
        switch ($action)
        {
        case 'start':
            $indicator = '+';
            $prev      =& $measure;
            break;

        case 'stop':
            $indicator = '-';
            $prev      = self::$_profiles[$uid]['start'];
            break;

        case 'checkpoint':
        default:
            $action    = 'checkpoint';
            $indicator = '.';
            $prev      = (isset(self::$_profiles[$uid]['checkpoint'])
                            ? self::$_profiles[$uid]['checkpoint']
                            : self::$_profiles[$uid]['start']);
            break;
        }

        self::_log(sprintf("{$indicator}{$uid} @%8.4f:%10s: %s",
                           $measure->time($prev),
                           number_format($measure->memory($prev)),
                           $callerStr) );
        self::$_profiles[$uid][$action] = $measure;

        return $uid;
    }

    /***********************************************************************
     * Protected helpers
     *
     */

    /** @brief  Log a message.
     *  @param  msg     The message string.
     *  @param  level   The level (Zend_Log::EMERG, ALERT, CRIT, ERR, WARN,
     *                                       NOTICE, INFO, DEBUG)
     *
     *  @return void
     */
    protected static function   _log($msg, $level = Zend_Log::INFO)
    {
        if (self::$_log instanceof Zend_Log)
        {
            self::$_log->log($msg, $level);
        }
        else
        {
            echo $msg ."\n";
        }
    }

}

/** @brief  A container for a single profile measurement.
 */
class Connexions_ProfileMeasure
{
    protected   $_timestamp = null;
    protected   $_memory    = null;

    public function __construct()
    {
        $this->_timestamp = time() + microtime(true);
        $this->_memory    = memory_get_usage();
    }

    /** @brief  Given a second profile measure, compute and return the time
     *          difference.
     *  @param  measure     The second profile measure.
     *
     *  @return The absolute value of the time difference (micro-seconds).
     */
    public function time(Connexions_ProfileMeasure  $measure    = null)
    {
        if ($measure === null)
            return $this->_timestamp;

        return abs($measure->_timestamp - $this->_timestamp);
    }

    /** @brief  Given a second profile measure, compute and return the memory
     *          usages difference.
     *  @param  measure     The second profile measure.
     *
     *  @return The memory usage difference based upon the timestamps, this
     *          will indicate whether usage dropped (negative) or increased
     *          (positive).
     */
    public function memory(Connexions_ProfileMeasure    $measure    = null)
    {
        if ($measure === null)
            return $this->_memory;

        if ($measure->_timestamp > $this->_timestamp)
            $diff = $measure->_memory - $this->_memory;
        else
            $diff = $this->_memory - $measure->_memory;

        return $diff;
    }
}
