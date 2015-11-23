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
        $db = $this->_backend->getAdapter();
        $dbCommand = Tinebase_Backend_Sql_Command::factory($db);
        $select = $db->select()
            ->distinct(true)
            ->from($this->_backend->getTablePrefix() . $this->_backend->getTableName(), 'user_agent')
            ->where( $db->quoteIdentifier('account_id') . ' = ?', $_user->getId() )
            ->where( $db->quoteIdentifier('li') . ' > NOW() - ' . $dbCommand->getInterval('HOUR', '1'))
            ->where( $db->quoteIdentifier('result') . ' <> ?', Tinebase_Auth::SUCCESS, Zend_Db::PARAM_INT)
            ->limit(10);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $select);

        $stmt = $db->query($select);

        if ($stmt->columnCount() > $numberOfAllowedUserAgents) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' More than ' . $numberOfAllowedUserAgents . ' different UserAgents? we don\'t trust you!');
            $result = true;
        }
        $stmt->closeCursor();
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
     * @param string $_sessionId the session id
     * @param string $_ipAddress the ip address the user connects from
     * @return null|Tinebase_Model_AccessLog
     */
    public function setLogout($_sessionId)
    {
        try {
            $loginRecord = $this->_backend->getByProperty($_sessionId, 'sessionid');
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not find access log login record for session id ' . $_sessionId);
            return null;
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
}
