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
    public static function log(Exception $exception, $suppressTrace = NULL)
    {
        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . get_class($exception) . ' -> ' . $exception->getMessage());
        
        $suppressTrace = ($suppressTrace !== NULL) ? $suppressTrace : Tinebase_Core::getConfig()->suppressExceptionTraces === TRUE;
        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE) && ! $suppressTrace) {
            $traceString = $exception->getTraceAsString();
            $traceString = self::_replaceBasePath($traceString);
            $traceString = self::_removeCredentials($traceString);
             
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $traceString);
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
            "/->validate\('[^']*', '[^']*'/",
            "/->authenticate\('[^']*', '[^']*'/",
        );
        $replacements = array(
            "->login('$1', '********'",
            "->loginFromPost('$1', '********'",
            "->validate('$1', '********'",
            "->authenticate('$1', '********'",
        );
        
        return preg_replace($passwordPatterns, $replacements, $_traceString);
    }
    
}
