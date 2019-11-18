<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * returns false if not blocked and number of failed logins if
     *
     * @param Tinebase_Model_FullUser  $_user
     * @param Tinebase_Model_AccessLog $_accessLog
     * @return bool|integer
     */
    public function isUserAgentBlocked(Tinebase_Model_FullUser $_user, Tinebase_Model_AccessLog $_accessLog)
    {
        if ($this->_tooManyUserAgents($_user)) {
            return true;
        }

        $db = $this->_backend->getAdapter();
        $dbCommand = Tinebase_Backend_Sql_Command::factory($db);
        $select = $db->select()
            ->from($this->_backend->getTablePrefix() . $this->_backend->getTableName(), new Zend_Db_Expr('count(*)'))
            ->where( $db->quoteIdentifier('account_id') . ' = ?', $_user->getId() )
            ->where( $db->quoteIdentifier('li') . ' > NOW() - ' . $dbCommand->getInterval('MINUTE', '1'))
            ->where( $db->quoteIdentifier('result') . ' <> ?', Tinebase_Auth::SUCCESS, Zend_Db::PARAM_INT)
            ->where( $db->quoteIdentifier('user_agent') . ' = ?', $_accessLog->user_agent);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $select);

        $stmt = $db->query($select);
        $count = $stmt->fetchColumn();
        $stmt->closeCursor();

        if ($count > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' UserAgent blocked. Login failures: ' . $count);
            return true;
        }
        return false;
    }

    /**
     * check if user connected with too many user agent during the last hour
     *
     * @param Tinebase_Model_FullUser  $_user
     * @param int $numberOfAllowedUserAgents
     * @return bool
     */
    protected function _tooManyUserAgents($_user, $numberOfAllowedUserAgents = 3)
    {
        $result = false;

        $userAgents = $this->_getUserAgentsByInterval($_user);
        if (count($userAgents) > $numberOfAllowedUserAgents) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' More than ' . $numberOfAllowedUserAgents . ' different UserAgents? we don\'t trust you!');
            $result = true;
        }
        return $result;
    }

    /**
     * @param        $_user
     * @param string $_unit
     * @param int    $_interval
     * @return array
     * @throws Tinebase_Exception_Backend_Database
     *
     * TODO move to backend
     */
    protected function _getUserAgentsByInterval($_user, $_unit = 'HOUR', $_interval = 1, $_onlyInvalidLogins = true)
    {
        $db = $this->_backend->getAdapter();
        $dbCommand = Tinebase_Backend_Sql_Command::factory($db);
        $select = $db->select()
            ->distinct(true)
            ->from($this->_backend->getTablePrefix() . $this->_backend->getTableName(), 'user_agent')
            ->where( $db->quoteIdentifier('account_id') . ' = ?', $_user->getId() )
            ->where( $db->quoteIdentifier('li') . ' > NOW() - ' . $dbCommand->getInterval($_unit, $_interval))
            ->limit(20);

        if ($_onlyInvalidLogins) {
            $select->where( $db->quoteIdentifier('result') . ' <> ?', Tinebase_Auth::SUCCESS, Zend_Db::PARAM_INT);
        }

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        $stmt->closeCursor();

        return $result;
    }

    /**
     * @param     $user
     * @param int $lastMonths
     * @return array of user clients
     */
    public function getUserClients($user, $lastMonths = 1)
    {
        $userAgents = $this->_getUserAgentsByInterval($user, 'MONTH', $lastMonths, /* $_onlyInvalidLogins */ false);
        $result = array();
        foreach ($userAgents as $row) {
            // TODO maybe _getUserAgentsByInterval could already do this ...
            if (isset($row['user_agent']) && $row['user_agent']) {
                $result[] = $row['user_agent'];
            }
        }
        return $result;
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
     * @return null|Tinebase_Model_AccessLog
     */
    public function setLogout()
    {
        $sessionId = Tinebase_Core::getSessionId();

        try {
            if (Tinebase_Core::isRegistered(Tinebase_Core::USERACCESSLOG)) {
                $loginRecord = Tinebase_Core::get(Tinebase_Core::USERACCESSLOG);
            } else {
                $loginRecord = $this->_backend->getByProperty($sessionId, 'sessionid');
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' Could not find access log login record for session id ' . $sessionId);
            return null;
        }
        
        $loginRecord->lo = Tinebase_DateTime::now();
        
        // call update of backend direct to save overhead of $this->update()
        try {
            return $this->_backend->update($loginRecord);
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' ' . $tenf->getMessage());
            return null;
        }
    }

    /**
     * clear access log table
     * - if $date param is omitted, the last 60 days of access log are kept, the rest will be removed
     * 
     * @param Tinebase_DateTime $date
     * @return bool
     * 
     * @todo use $this->deleteByFilter($_filter)? might be slow for huge access_logs
     */
    public function clearTable($date = NULL)
    {
        $date = ($date instanceof Tinebase_DateTime) ? $date : Tinebase_DateTime::now()->subDay(
            Tinebase_Config::getInstance()->{Tinebase_Config::ACCESS_LOG_ROTATION_DAYS});
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removing all access log entries before ' . $date->toString());
        
        $db = $this->_backend->getAdapter();
        $where = array(
            $db->quoteInto($db->quoteIdentifier('li') . ' < ?', $date->toString())
        );
        $deletedRows = $db->delete($this->_backend->getTablePrefix() . $this->_backend->getTableName(), $where);
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed ' . $deletedRows . ' rows.');
        
        return true;
    }


    /**
     * return accessLog instance
     *
     * @param string $loginName
     * @param Zend_Auth_Result $authResult
     * @param Tinebase_Http_Request $request
     * @param string $clientIdString
     * @return Tinebase_Model_AccessLog
     */
    public function getAccessLogEntry($loginName, Zend_Auth_Result $authResult, Tinebase_Http_Request $request, $clientIdString)
    {
        if ($header = $request->getHeaders('USER-AGENT')) {
            $userAgent = substr($header->getFieldValue(), 0, 255);
        } else {
            $userAgent = 'unknown';
        }

        $accessLog = new Tinebase_Model_AccessLog(array(
            'ip'         => $request->getRemoteAddress(),
            'li'         => Tinebase_DateTime::now(),
            'result'     => $authResult->getCode(),
            'clienttype' => $clientIdString,
            'login_name' => $authResult->getIdentity(),
            'user_agent' => $userAgent
        ), true);

        return $accessLog;
    }

    /**
     * set session id for current request in accesslog
     *
     * @param Tinebase_Model_AccessLog $accessLog
     */
    public function setSessionId(Tinebase_Model_AccessLog $accessLog)
    {
        if (in_array($accessLog->clienttype, array(Tinebase_Server_WebDAV::REQUEST_TYPE, ActiveSync_Server_Http::REQUEST_TYPE))) {
            try {
                $previousAccessLog = Tinebase_AccessLog::getInstance()->getPreviousAccessLog($accessLog);
                $accessLog->merge($previousAccessLog);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // ignore
            }
        }

        if (empty($accessLog->sessionid)) {
            $accessLog->sessionid = Tinebase_Core::getSessionId();
        } else {
            Tinebase_Core::set(Tinebase_Core::SESSIONID, $accessLog->sessionid);
        }
    }
}
