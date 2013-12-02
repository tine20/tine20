<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Addressbook exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception extends Exception
{
    /**
     * the name of the application, this exception belongs to
     * 
     * @var string
     */
    protected $_appName = NULL;
    
    /**
     * the title of the Exception (may be shown in a dialog)
     * 
     * @var string
     */
    protected $_title = NULL;
    
    /**
     * the constructor
     * 
     * @param message[optional]
     * @param code[optional]
     * @param previous[optional]
     */
    public function __construct($message = null, $code = null, $previous = null)
    {
        if (! $this->_appName) {
            $c = explode('_', get_class($this));
            $this->_appName = $c[0];
        }
        
        if (! $this->_title) {
            $this->_title = 'Exception ({0})'; // _('Exception ({0})')
        }
        
        parent::__construct(($message ? $message : $this->message), $code, $previous);
    }
    
    /**
     * get exception trace as array (remove confidential information)
     * 
     * @param Exception $exception
     * @return array
     */
    public static function getTraceAsArray(Exception $exception)
    {
        $trace = $exception->getTrace();
        $traceArray = array();
        
        foreach($trace as $part) {
            if (array_key_exists('file', $part)) {
                // don't send full paths to the client
                $part['file'] = self::_replaceBasePath($part['file']);
            }
            // unset args to make sure no passwords are shown
            unset($part['args']);
            $traceArray[] = $part;
        }
        
        return $traceArray;
    }
    
    /**
     * replace base path in string
     * 
     * @param string|array $_string
     * @return string
     */
    protected static function _replaceBasePath($_string)
    {
        $basePath = dirname(dirname(__FILE__));
        return str_replace($basePath, '...', $_string);
    }
    
    /**
     * log exception (remove confidential information from trace)
     * 
     * @param Exception $exception
     * @param boolean $suppressTrace
     */
    public static function log(Exception $exception, $suppressTrace = null)
    {
        if (! is_object(Tinebase_Core::getLogger())) {
            // no logger -> exception happened very early
            error_log($exception);
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . get_class($exception) . ' -> ' . $exception->getMessage());
            
            if ($suppressTrace === null) {
                try {
                    $suppressTrace = Tinebase_Core::getConfig()->suppressExceptionTraces;
                } catch (Exception $e) {
                    // catch all config exceptions here
                    $suppressTrace = true;
                }
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE) && ! $suppressTrace) {
                $traceString = $exception->getTraceAsString();
                $traceString = self::_replaceBasePath($traceString);
                $traceString = self::_removeCredentials($traceString);
                 
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $traceString);
            }
        }
    }
    
    /**
     * remove credentials/passwords from trace 
     * 
     * @param string $_traceString
     * @return string
     */
    protected static function _removeCredentials($_traceString)
    {
        $passwordPatterns = array(
            "/->login\('([^']*)', '[^']*'/",
            "/->loginFromPost\('([^']*)', '[^']*'/",
            "/->validate\('([^']*)', '[^']*'/",
            "/->(_{0,1})authenticate\('([^']*)', '[^']*'/",
        );
        $replacements = array(
            "->login('$1', '********'",
            "->loginFromPost('$1', '********'",
            "->validate('$1', '********'",
            "->$1authenticate('$2', '********'",
        );
        
        return preg_replace($passwordPatterns, $replacements, $_traceString);
    }
    
    /**
     * returns the name of the application, this exception belongs to
     * 
     * @return string
     */
    public function getAppName()
    {
        return $this->_appName;
    }
    
    /**
     * returns the title of this exception
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }
}
