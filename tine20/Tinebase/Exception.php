<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * only send exceptions to sentry if this is true - don't send exceptions that are important to the code flow / domain logic
     *
     * @var bool
     */
    protected $_logToSentry = true;

    /**
     * default loglevel method for Tinebase_Exception::log()
     *
     * @var string
     */
    protected $_logLevelMethod = 'err';

    /**
     * the constructor
     * 
     * @param string $message
     * @param int $code
     * @param Throwable $previous
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
    public static function getTraceAsArray(Throwable $exception)
    {
        $trace = $exception->getTrace();
        $traceArray = array();
        
        foreach($trace as $part) {
            if ((isset($part['file']) || array_key_exists('file', $part))) {
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
     * @param Throwable $exception
     * @param boolean $suppressTrace
     * @param mixed $additionalData
     */
    public static function log(Throwable $exception, $suppressTrace = null, $additionalData = null)
    {
        if (! is_object(Tinebase_Core::getLogger())) {
            // no logger -> exception happened very early
            error_log($exception);
        } else {
            self::logExceptionToLogger($exception, $suppressTrace, $additionalData);
            self::sendExceptionToSentry($exception);
        }
    }

    /**
     * @param Throwable $exception
     * @param null $suppressTrace
     * @param null $additionalData
     */
    public static function logExceptionToLogger(Throwable $exception, $suppressTrace = null, $additionalData = null)
    {
        $logMethod = $exception instanceof Tinebase_Exception ? $exception->getLogLevelMethod() : 'err';
        $logLevel = strtoupper($logMethod);

        Tinebase_Core::getLogger()->$logMethod(__METHOD__ . '::' . __LINE__ . ' ' . get_class($exception)
            . ' -> ' . $exception->getMessage());

        if ($additionalData) {
            Tinebase_Core::getLogger()->$logMethod(__METHOD__ . '::' . __LINE__ . ' Data: ' . print_r($additionalData, true));
        }

        // log trace?
        if ($suppressTrace === null) {
            try {
                $config = Tinebase_Core::getConfig();
                $suppressTrace = (isset($config->suppressExceptionTraces)) ? $config->suppressExceptionTraces : false;
            } catch (Exception $e) {
                // catch all config exceptions here
                $suppressTrace = false;
            }
        }

        if (Tinebase_Core::isLogLevel(constant("Zend_Log::$logLevel")) && ! $suppressTrace) {
            $traceString = $exception->getTraceAsString();
            $traceString = self::_replaceBasePath($traceString);
            $traceString = self::_removeCredentials($traceString);
            Tinebase_Core::getLogger()->$logMethod(__METHOD__ . '::' . __LINE__ . ' ' . $traceString);
        }
    }

    /**
     * @param Throwable $exception
     */
    public static function sendExceptionToSentry(Throwable $exception)
    {
        $sentryClient = Tinebase_Core::getSentry();
        if (! $sentryClient) {
            return;
        }

        if ($exception instanceof Tinebase_Exception && ! $exception->logToSentry()) {
            return;
        }

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Sending exception to Sentry');
        $sentryClient->captureException($exception, array(
            // TODO add more information? add it here or in \Tinebase_Core::setupSentry?
            'extra' => array(
                'tinebaseId' => Tinebase_Core::getTinebaseId(),
            ),
        ));
    }

    /**
     * @param bool $bool
     */
    public function setLogToSentry($bool)
    {
        $this->_logToSentry = (bool)$bool;
    }

    /**
     * @return bool
     */
    public function logToSentry()
    {
        return $this->_logToSentry;
    }

    /**
     * @param string $logLvl
     */
    public function setLogLevelMethod($logLvl)
    {
        $this->_logLevelMethod = $logLvl;
    }

    /**
     * @return string
     */
    public function getLogLevelMethod()
    {
        return $this->_logLevelMethod;
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
            "/->updateCredentialCache\('[^']*'/",
        );
        $replacements = array(
            "->login('$1', '********'",
            "->loginFromPost('$1', '********'",
            "->validate('$1', '********'",
            "->$1authenticate('$2', '********'",
            "->updateCredentialCache('********'",
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

    /**
     * @param Zend_Db_Statement_Exception $zdse
     * @return bool
     */
    public static function isDbDuplicate(Zend_Db_Statement_Exception $zdse)
    {
        if (preg_match('/Duplicate entry/', $zdse->getMessage())) {
            return true;
        }
        return false;
    }
}
