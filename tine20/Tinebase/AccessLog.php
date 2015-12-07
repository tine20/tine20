<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */ 

/**
 * this class provides functions to get, add and remove entries from/to the access log
 * 
 * @package     Tinebase
 */
class Tinebase_AccessLog extends Tinebase_Controller_Record_Abstract
{
    /**
     * @var Tinebase_Backend_Sql
     */
    protected $_backend;
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_AccessLog
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->_modelName = 'Tinebase_Model_AccessLog';
        $this->_omitModLog = TRUE;
        $this->_doContainerACLChecks = FALSE;
        
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName, 
            'tableName' => 'access_log',
        ));
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_AccessLog
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_AccessLog;
        }
        
        return self::$_instance;
    }
    
    /**
     * get previous access log entry
     * 
     * @param Tinebase_Model_AccessLog $accessLog
     * @throws Tinebase_Exception_NotFound
     * @return Tinebase_Model_AccessLog
     */
    public function getPreviousAccessLog(Tinebase_Model_AccessLog $accessLog)
    {
        $previousAccessLog = $this->search(
            new Tinebase_Model_AccessLogFilter(array(
                array(
                    'field'    => 'ip',
                    'operator' => 'equals',
                    'value'    => $accessLog->ip
                ),
                array(
                    'field'    => 'account_id',
                    'operator' => 'equals',
                    'value'    => $accessLog->account_id
                ),
                array(
                    'field'    => 'result',
                    'operator' => 'equals',
                    'value'    => Tinebase_Auth::SUCCESS
                ),
                array(
                    'field'    => 'clienttype',
                    'operator' => 'equals',
                    'value'    => $accessLog->clienttype
                ),
                array(
                    'field'    => 'user_agent',
                    'operator' => 'equals',
                    'value'    => $accessLog->user_agent
                ),
                array(
                    'field'    => 'lo',
                    'operator' => 'after',
                    'value'    => Tinebase_DateTime::now()->subHour(2) // @todo use session timeout from config
                ),
            )),
            new Tinebase_Model_Pagination(array(
                'sort'  => 'li',
                'dir'   => 'DESC',
                'limit' => 1
            ))
        )->getFirstRecord();
        
        if (!$previousAccessLog) {
            throw new Tinebase_Exception_NotFound('previous access log entry not found');
        }
        
        return $previousAccessLog;
    }
    
    /**
     * add logout entry to the access log
     *
     * @param string $_sessionId the session id
     * @param string $_ipAddress the ip address the user connects from
     * @return void|Tinebase_Model_AccessLog
     */
    public function setLogout()
    {
        $sessionId = Tinebase_Core::getSessionId();

        try {
            $loginRecord = $this->_backend->getByProperty($sessionId, 'sessionid');
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not find access log login record for session id ' . $_sessionId);
            return;
        }
        
        $loginRecord->lo = Tinebase_DateTime::now();
        
        // call update of backend direct to save overhead of $this->update()
        return $this->_backend->update($loginRecord);
    }

    /**
     * clear access log table
     * - if $date param is ommitted, the last 60 days of access log are kept, the rest will be removed
     * 
     * @param Tinebase_DateTime $date
     * @return integer deleted rows
     * 
     * @todo use $this->deleteByFilter($_filter)? might be slow for huge access_logs
     */
    public function clearTable($date = NULL)
    {
        $date = ($date instanceof Tinebase_DateTime) ? $date : Tinebase_DateTime::now()->subDay(60);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removing all access log entries before ' . $date->toString());
        
        $db = $this->_backend->getAdapter();
        $where = array(
            $db->quoteInto($db->quoteIdentifier('li') . ' < ?', $date->toString())
        );
        $deletedRows = $db->delete($this->_backend->getTablePrefix() . $this->_backend->getTableName(), $where);
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed ' . $deletedRows . ' rows.');
        
        return $deletedRows;
    }


    /**
     * return accessLog instance
     *
     * @param string $loginName
     * @param Zend_Auth_Result $authResult
     * @param Zend_Controller_Request_Abstract $request
     * @param string $clientIdString
     * @return Tinebase_Model_AccessLog
     */
    public function getAccessLogEntry($loginName, Zend_Auth_Result $authResult, \Zend\Http\Request $request, $clientIdString)
    {
        if ($header = $request->getHeaders('USER-AGENT')) {
            $userAgent = substr($header->getFieldValue(), 0, 255);
        } else {
            $userAgent = 'unknown';
        }

        $accessLog = new Tinebase_Model_AccessLog(array(
            'ip'         => $request->getServer('REMOTE_ADDR'),
            'li'         => Tinebase_DateTime::now(),
            'result'     => $authResult->getCode(),
            'clienttype' => $clientIdString,
            'login_name' => $loginName ? $loginName : $authResult->getIdentity(),
            'user_agent' => $userAgent
        ), true);

        return $accessLog;
    }

    /**
     * set session for current request
     *
     * @param Tinebase_Model_AccessLog $accessLog
     */
    public function setSessionId(Tinebase_Model_AccessLog $accessLog)
    {
        if (in_array($accessLog->clienttype, array(Tinebase_Server_WebDAV::REQUEST_TYPE, ActiveSync_Server_Http::REQUEST_TYPE))) {
            try {
                $accessLog = Tinebase_AccessLog::getInstance()->getPreviousAccessLog($accessLog);
                // $accessLog->sessionid is set now
            } catch (Tinebase_Exception_NotFound $tenf) {
                // ignore
            }
        }

        if (! $accessLog->sessionid) {
            $accessLog->sessionid = Tinebase_Core::getSessionId();
        }

        Tinebase_Core::set(Tinebase_Core::SESSIONID, $accessLog->sessionid);
    }
}
