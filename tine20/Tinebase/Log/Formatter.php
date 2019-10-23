<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 */

/**
 * Log Formatter for Tine 2.0
 * - prefixes log statements
 * - replaces passwords
 * - adds user name
 * 
 * @package     Tinebase
 * @subpackage  Log
 *
 * @todo remove static vars to allow to configure multiple log writers/formatters individually
 */
class Tinebase_Log_Formatter extends Zend_Log_Formatter_Simple
{
    /**
     * last time a log message was processed
     *
     * @var boolean
     */
    protected static $_lastlogtime = NULL;
    
    /**
     * should difftime be logged
     *
     * @var boolean
     */
    protected static $_logdifftime = NULL;
    
    /**
     * should runtime be logged
     *
     * @var boolean
     */
    protected static $_logruntime = NULL;

    /**
     * should log output be colorized (depending on loglevel)
     *
     * @var boolean
     */
    protected static $_colorize = false;

    /**
     * session/request id
     * 
     * @var string
     */
    protected static $_requestId = '-';

    /**
     * transaction id
     *
     * @var string
     */
    protected static $_transactionId = '-';

    /**
     * application start time
     *
     * @var float
     */
    protected static $_starttime = NULL;
    
    /**
     * username
     * 
     * @var string
     */
    protected static $_username = NULL;
    
    /**
     * search strings
     * 
     * @var array
     */
    protected $_search = array();
    
    /**
     * replacement strings
     * 
     * @var array
     */
    protected $_replace = array();
    
    /**
     * overwritten parent constructor to load configuration, calls parent constructor
     * 
     * supported configuration keys:
     * logruntime    => prepend time passed since request started
     * logdifftime   => prepend time passed since last log message
     *
     * @param string $format
     */
    public function __construct($format = null)
    {
        parent::__construct($format);
        
        if (!self::$_requestId || self::$_requestId === '-') {
            self::$_requestId = Tinebase_Record_Abstract::generateUID(5);
        }
        
        if (self::$_starttime === NULL) {
            self::$_starttime = Tinebase_Core::get(Tinebase_Core::STARTTIME);
            if (self::$_starttime === NULL) {
                self::$_starttime = microtime(true);
            }
        }
        
        if (self::$_logruntime === NULL || self::$_logdifftime === NULL) {
            $config = Tinebase_Core::getConfig();
            if ($config->logger->logruntime) {
                self::$_logruntime = true;
            } else {
                self::$_logruntime = false;
            }
            if ($config->logger->logdifftime) {
                self::$_logdifftime = true;
            } else {
                self::$_logdifftime = false;
            }
            if ($config->logger->colorize) {
                self::$_colorize = $config->logger->colorize;
            }
        }
    }
    
    /**
     * add strings to replace in log output (passwords for example)
     * 
     * @param string $search
     * @param string $replace
     */
    public function addReplacement($search, $replace = '********')
    {
        if (! in_array($search, $this->_search)) {
            $this->_search[] = $search;
            $this->_replace[] = $replace;
        }
    }
    
    /**
     * Add session id in front of log line
     *
     * @param  array    $event    event data
     * @return string             formatted line to write to the log
     */
    public function format($event)
    {
        $output = parent::format($event);
        $output = str_replace($this->_search, $this->_replace, $output);

        $logruntime = $this->_getLogRunTime();
        $logdifftime = $this->_getLogDiffTime();
        $timelog = trim(implode(' ', [$logruntime, $logdifftime]));
        if (! empty($timelog)) {
            $timelog .= ' ';
        }

        return self::$_requestId . ' ' . self::$_transactionId . ' ' . self::getUsername() . ' ' . $timelog . '- '
            . $this->_getFormattedOutput($output, $event);
    }

    /**
     * @param bool $format
     * @return float|mixed|string
     *
     * TODO move calculation to Tinebase_Log
     */
    protected function _getLogRunTime($format = true)
    {
        $result = '';
        if (self::$_logruntime) {
            $currenttime = microtime(true);
            $result = $currenttime - self::$_starttime;
            if ($format) {
                $result = Tinebase_Helper::formatMicrotimeDiff($result);
            }
        }
        return $result;
    }

    /**
     * @param bool $format
     * @return bool|mixed|string
     *
     * TODO move calculation to Tinebase_Log
     */
    protected function _getLogDiffTime($format = true)
    {
        $result = '';
        if (self::$_logdifftime) {
            $currenttime = microtime(true);
            $result = $currenttime - (self::$_lastlogtime ? self::$_lastlogtime : $currenttime);
            self::$_lastlogtime = $currenttime;
            if ($format) {
                $result = Tinebase_Helper::formatMicrotimeDiff($result);
            }
        }
        return $result;
    }

    /**
     * @param $output
     * @param array $event
     * @return string
     */
    protected function _getFormattedOutput($output, array $event)
    {
        if (self::$_colorize) {
            $color = $this->_getColorByPrio($event['priority']);
            $output = "\e[1m\e[$color" . $output . "\e[0m";
        }

        return $output;
    }

    /**
     * @param $logPrio
     * @return string
     */
    protected function _getColorByPrio($logPrio)
    {
        switch ($logPrio) {
            case 0:
            case 1:
            case 2:
                $color = "31m"; // red
                break;
            case 3:
                $color = "35m"; // magenta
                break;
            case 4:
                $color = "33m"; // yellow
                break;
            case 5:
                $color = "36m"; // cyan
                break;
            case 6:
                $color = "32m"; // green
                break;
            case 8:
                $color = "34m"; // blue
                break;
            default:
                $color = "37m"; // white
        }

        return $color;
    }

    /**
     * get current username
     * 
     * @return string
     */
    public static function getUsername()
    {
        if (self::$_username === NULL) {
            $user = Tinebase_Core::getUser();
            self::$_username = ($user && is_object($user))
                ? (isset($user->accountLoginName)
                    ? $user->accountLoginName
                    : (isset($user->accountDisplayName) ? $user->accountDisplayName : NULL)) 
                : NULL;
        }

        $result = (self::$_username) ? self::$_username : '-- none --';

        if (self::$_colorize) {
            // TODO use different background here?
            $result = "\e[1m\e[32m" . $result . "\e[0m";
        }

        return $result;
    }

    /**
     * reset current username
     *
     * @return string
     */
    public static function resetUsername()
    {
        self::$_username = NULL;
    }

    /**
     * reset username and options
     *
     * @return string
     */
    public static function reset()
    {
        self::$_username = NULL;
        self::$_colorize = false;
    }

    public static function setTransactionId($transactionId)
    {
        self::$_transactionId = $transactionId;
    }

    public static function getTransactionId()
    {
        return self::$_transactionId;
    }

    public static function getRequestId()
    {
        return self::$_requestId;
    }

    /**
     * @param array $options
     *
     * TODO allow to set more options
     */
    public function setOptions(array $options)
    {
        if (isset($options['colorize'])) {
            self::$_colorize = $options['colorize'];
        }
    }
}
