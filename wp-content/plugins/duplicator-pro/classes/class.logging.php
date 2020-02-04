<?php
defined("ABSPATH") or die("");
if (!defined('DUPLICATOR_PRO_VERSION')) exit; // Exit if accessed directly

/**
 * Used to create package and application trace logs
 *
 * Package logs: Consist of a separate log file for each package created
 * Trace logs:   Created only when tracing is enabled see Settings > General
 *               One trace log is created and when it hits a threshold a
 *               second one is made
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2
 *
 * @package DUP_PRO
 * @subpackage classes
 * @copyright (c) 2017, Snapcreek LLC
 * @license	https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 3.0.0
 *
 */

//require_once(dirname(__FILE__) . '/../lib/snaplib/class.snaplib.u.string.php');

class DUP_PRO_Profile_Call_Info
{
    public $latestStartTS = -1;
    public $latestStopTS = -1;

    public $numCalls = 0;
    public $culmulativeTime = 0;
    
    public $eventName = '';

    public function __construct($eventName)
    {
        $this->eventName = $eventName;
    }
}

class DUP_PRO_Log
{
    /**
     * The file handle used to write to the package log file
     */
    private static $logFileHandle;

    /**
     * Get the setting which indicates if tracing is enabled
     */
    private static $traceEnabled;

    public static $profileLogs = null;

    /**
     * Init this static object
     */
    public static function init()
    {
        self::$traceEnabled = (bool) get_option('duplicator_pro_trace_log_enabled', false);
    }

	/**
     * Is tracing enabled
     */
    public static function isTraceLogEnabled()
    {
        return self::$traceEnabled;
    }

    public static function setProfileLogs($profileLogs)
    {
        if($profileLogs == null)
        {
            self::$profileLogs = new stdClass();
        }
        else
        {
            self::$profileLogs = $profileLogs;
        }

    }

    /**
     * Open a log file connection for writing to the package log file
     *
     * @param string $nameHas The Name of the log file to create
     *
     * @return nul
     */
    public static function open($nameHash)
    {
        if (!isset($nameHash)) throw new Exception("A name value is required to open a file log.");
        self::$logFileHandle = @fopen(DUPLICATOR_PRO_SSDIR_PATH."/{$nameHash}.log", "a+");
    }

    /**
     * Close the package log file connection
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public static function close()
    {
        return @fclose(self::$logFileHandle);
    }

    /**
     *  General information send to the package log
     *
     *  @param string $msg	The message to log
     * 
     *  @return null
     */
    public static function info($msg)
    {
        @fwrite(self::$logFileHandle, "{$msg} \n");
    }

	   /**
     *  General information send to the package log and trace log
     *
     *  @param string $msg	The message to log
     *
     *  @return null
     */
    public static function infoTrace($msg)
    {
        self::info($msg);
		self::trace($msg);
    }

    /**
     *  Called for the package log when an error is detected and no further processing should occur
     *
     * @param string $msg       The message to log
     * @param string $details   Additional details to help resolve the issue if possible
     * @param bool   $die       Issue a die command when finished logging
     *
     * @return null
     */
    public static function error($msg, $detail = '', $die = true)
    {
        if ($detail == '') {
            $detail = '(no detail)';
        }

        DUP_PRO_LOG::traceError("Forced Error Generated: ".$msg."-$detail");
        $source = self::getStack(debug_backtrace());

        $err_msg = "\n====================================================================\n";
        $err_msg .= "!RUNTIME ERROR!\n";
        $err_msg .= "---------------------------------------------------------------------\n";
        $err_msg .= "MESSAGE:\n{$msg}\n";
        if (strlen($detail)) {
            $err_msg .= "DETAILS:\n{$detail}\n";
        }
        $err_msg .= "---------------------------------------------------------------------\n";
        $err_msg .= "TRACE:\n{$source}";
        $err_msg .= "====================================================================\n\n";
        @fwrite(self::$logFileHandle, "\n{$err_msg}");

        if ($die) {
            //Output to browser
            $browser_msg = "RUNTIME ERROR:<br/>An error has occured. Please try again!<br/>";
            $browser_msg .= "See the duplicator log file for full details: Duplicator Pro &gt; Tools &gt; Logging<br/><br/>";
            $browser_msg .= "MESSAGE:<br/> {$msg} <br/><br/>";
            if (strlen($detail)) {
                $browser_msg .= "DETAILS: {$detail} <br/>";
            }
            die($browser_msg);
        }
    }

    /**
     * The current stack trace of a PHP call
     *
     * @param $stacktrace   The current debug stack
     *
     * @return string       A log friend stack-trace view of info
     */
    public static function getStack($stacktrace)
    {
        $output = "";
        $i      = 1;

        foreach ($stacktrace as $node) {
            $file_output     = isset($node['file']) ? basename($node['file']) : '';
            $function_output = isset($node['function']) ? basename($node['function']) : '';
            $line_output     = isset($node['line']) ? basename($node['line']) : '';

            $output .= "$i. ".$file_output." : ".$function_output." (".$line_output.")\n";
            $i++;
        }

        return $output;
    }



   /** ========================================================
	* TRACE SPECIFIC CALLS
    * =====================================================  */

    /**
     * Writes a message to the trace log
     *
     * @param $message   The message to write
     *
     * @return null
     */
    public static function ddebug($message)
    {
        self::trace($message, true);
    }

    /**
     * Deletes the trace log and backup trace log files
     *
     * @return null
     */
    public static function deleteTraceLog()
    {
        $file_path   = self::getTraceFilepath();
        $backup_path = self::getBackupTraceFilepath();

        self::trace("deleting $file_path");
        @unlink($file_path);
        self::trace("deleting $backup_path");
        @unlink($backup_path);
    }

    /**
     * Gets the backup trace file path
     *
     * @return string   Returns the full path to the backup trace file (i.e. dup-pro_hash.log1)
     */
    public static function getBackupTraceFilepath()
    {
        $default_key = DUP_PRO_Crypt_Blowfish::getDefaultKey();
        $backup_log_filename = "dup_pro_$default_key.log1";
        $backup_path = DUPLICATOR_PRO_SSDIR_PATH."/".$backup_log_filename;

        return $backup_path;
    }

    /**
     * Gets the active trace file path
     *
     * @return string   Returns the full path to the active trace file (i.e. dup-pro_hash.log)
     */
    public static function getTraceFilepath()
    {
        $default_key  = DUP_PRO_Crypt_Blowfish::getDefaultKey();
        $log_filename = "dup_pro_$default_key.log";
        $file_path    = DUPLICATOR_PRO_SSDIR_PATH."/".$log_filename;

        return $file_path;
    }

    /**
     * Gets the current file size of the active trace file
     *
     * @return string   Returns a human readable file size of the active trace file
     */
    public static function getTraceStatus()
    {
        $file_path   = DUP_PRO_LOG::getTraceFilepath();
        $backup_path = DUP_PRO_LOG::getBackupTraceFilepath();

        if (file_exists($file_path)) {
            $filesize = filesize($file_path);

            if (file_exists($backup_path)) {
                $filesize += filesize($backup_path);
            }

            $message = sprintf(DUP_PRO_U::__('%1$s'), DUP_PRO_U::byteSize($filesize));
        } else {
            $message = DUP_PRO_U::__('No Log');
        }

        return $message;
    }

    /**
     * Gets the active trace file URL path
     *
     * @return string   Returns the URL to the active trace file
     */
    public static function getTraceURL()
    {
        $default_key  = DUP_PRO_Crypt_Blowfish::getDefaultKey();
        $log_filename = "dup_pro_$default_key.log";
        $url          = DUPLICATOR_PRO_SSDIR_URL."/".$log_filename;

        return $url;
    }

    /**
     * Adds a message to the active trace log
     *
     * @param string $message The message to add to the active trace
     * @param bool $audit Add the trace message to the PHP error log
     *                    additional constraints are required
     *
     * @return null
     */
    public static function trace($message, $audit = true, $calling_function_override = null, $force_trace = false)
    {
        if (self::$traceEnabled || $force_trace) {
            $send_trace_to_error_log = (bool) get_option('duplicator_pro_send_trace_to_error_log', false);
            $unique_id               = sprintf("%08x", abs(crc32($_SERVER['REMOTE_ADDR'].$_SERVER['REQUEST_TIME'].$_SERVER['REMOTE_PORT'])));

            if ($calling_function_override == null) {
				$calling_function = DUP_PRO_U::getCallingFunctionName();
			} else {
				$calling_function = $calling_function_override;
			}

			if (is_object($message)) {
				$ov = get_object_vars($message);
				$message = print_r($ov, true);
			} else if (is_array($message)) {
				$message = print_r($message, true);
			}

			$logging_message           = "{$unique_id}|{$calling_function} | {$message}";
            $ticks                     = time() + ((int) get_option('gmt_offset') * 3600);
            $formatted_time            = date('d-m-H:i:s', $ticks);
			$formatted_logging_message = "{$formatted_time}|DPRO|{$logging_message} \r\n";

            // Write to error log if warranted - if either it's a non audit(error) or tracing has been piped to the error log
            if (($audit == false) || ($send_trace_to_error_log) || ($force_trace) && WP_DEBUG && WP_DEBUG_LOG) {
                DUP_PRO_Low_U::errLog($logging_message);
            }

            // Everything goes to the plugin log, whether it's part of package generation or not.
            self::writeToTrace($formatted_logging_message);
        }
    }

    /**
     * Adds a message to the active trace log with ***ERROR*** prepended
     *
     * @param string $message The error message to add to the active trace
     *
     * @return null
     */
    public static function traceError($message)
    {
        self::trace("***ERROR*** $message", false);
    }

    /**
     * Adds a message followed by an object dump to the message trace
     *
     * @param string $message The message to add to the active trace
     * @param object $object  A valid object types such as a class or array
     *
     * @return null
     */
    public static function traceObject($message, $object)
    {
		
			self::trace($message.'<br\>', true, DUP_PRO_U::getCallingFunctionName());
			self::trace($object, true, DUP_PRO_U::getCallingFunctionName());
		
    }

	 /**
	  * Profiles an event for performance analysis
	  * 
	  * @param string $eventName A descriptive name of an event to profile
	  * @param bool $start Start or stop the profiler event
	  *
	  * @example:
	  *		DUP_PRO_LOG::profile('MyUniqueString-StartLoop', true);
	  *		foreach {...}
	  *		DUP_PRO_LOG::profile('MyUniqueString-EndLoop', false);
	  *
	  * @return null
	  */
	public static function profile($eventName, $start)
	{
		if (self::$profileLogs !== null) {
			
			if (isset(self::$profileLogs->$eventName)) {

				$profileCallInfo = &self::$profileLogs->$eventName;

				if ($start) {
					if (($profileCallInfo->latestStartTS != -1) && ($profileCallInfo->latestStopTS == -1)) {
						throw new Exception("Overwriting a start for {$eventName} when stop hasn't occurred yet");
					}

					$profileCallInfo->latestStartTS	 = microtime(true);
					$profileCallInfo->latestStopTS	 = -1;
				} else {
					$profileCallInfo->latestStopTS = microtime(true);

					if ($profileCallInfo->latestStartTS == -1) {
						throw new Exception("Attempting to stop event $eventName when start didn't occur yet");
					}

					$deltaTime = ($profileCallInfo->latestStopTS - $profileCallInfo->latestStartTS);
					$profileCallInfo->numCalls++;
					$profileCallInfo->culmulativeTime += $deltaTime;
				}
			} else {
				if (!$start) {
                    throw new Exception("Trying to stop an event that never started ({$eventName})");
				}

				$profileCallInfo				 = new DUP_PRO_Profile_Call_Info($eventName);
				$profileCallInfo->latestStartTS	 = microtime(true);
				$profileCallInfo->latestStopTS	 = -1;
				self::$profileLogs->$eventName	 = $profileCallInfo;
			}
		}
	}

	/**
	  * Logs the cumulative aggregation of all profiled events
	  *
	  * @return null
	  */
	public static function profileReport()
	{

		function DUP_PRO_Profile_Call_Info_profileReport_CustomSort($a, $b) {
			return ($a->culmulativeTime < $b->culmulativeTime ? 1 : -1);
		}

        $profileLogArray = get_object_vars(self::$profileLogs);
        usort($profileLogArray, "DUP_PRO_Profile_Call_Info_profileReport_CustomSort");

        $eventWidth = 30;
        foreach ($profileLogArray as $profileLog) {
            if(strlen($profileLog->eventName) > $eventWidth) {
                $eventWidth = strlen($profileLog->eventName);
            }
        }

        $eventWidth += 4;
        if($eventWidth > 70) {
            $eventWidth = 70;
        }

        $txt = ("\n\n====START PROFILE REPORT====\n");
        $txt .= sprintf("%-{$eventWidth}s | %-7s | %-6s | %9s", 'EVENT NAME', '# CALLS', 'AVG(T)', "TOTAL T\n");
       
        foreach ($profileLogArray as $profileLog) {
			$avgTime	= ($profileLog->numCalls != 0)	? $profileLog->culmulativeTime / $profileLog->numCalls	: -1;
            $name		= SnapLibStringU::truncateString($profileLog->eventName, $eventWidth);
            $entry		= sprintf("%-{$eventWidth}s | %-7d | %-6.3f | %9.3f \n", $name, $profileLog->numCalls, $avgTime, $profileLog->culmulativeTime);
            $txt		.= $entry;
        }
        $txt .= ("====END PROFILE REPORT====\n");
        self::trace($txt, true, null, true);
	}

	/**
     * Does the trace file exists
     *
     * @return bool Returns true if an active trace file exists
     */
    public static function traceFileExists()
    {
        $file_path = DUP_PRO_LOG::getTraceFilepath();

        return file_exists($file_path);
    }

    /**
     * Manages writing the active or backup log based on the size setting
     *
     * @return null
     */
    private static function writeToTrace($formatted_logging_message)
    {
        $log_filepath = DUP_PRO_LOG::getTraceFilepath();

        if (@filesize($log_filepath) > DUP_PRO_Constants::MAX_LOG_SIZE) {
            $backup_log_filepath = DUP_PRO_LOG::getBackupTraceFilepath();

            if (file_exists($backup_log_filepath)) {
                if (@unlink($backup_log_filepath) === false) {
                    DUP_PRO_Low_U::errLog("Couldn't delete backup log $backup_log_filepath");
                }
            }

            if (@rename($log_filepath, $backup_log_filepath) === false) {
                DUP_PRO_Low_U::errLog("Couldn't rename log $log_filepath to $backup_log_filepath");
            }
        }

        if (@file_put_contents($log_filepath, $formatted_logging_message, FILE_APPEND) === false) {
            // Not en error worth reporting
        }
    }
}

DUP_PRO_LOG::init();