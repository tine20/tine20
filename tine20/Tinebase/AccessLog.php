<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */ 

/**
 * this class provides functions to get, add and remove entries from/to the access log
 * 
 * @package     Tinebase
 */
class Tinebase_AccessLog
{
    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_AccessLog
     */
    private static $instance = NULL;
    
    /**
     * the table object for the SQL_TABLE_PREFIX . applications table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $accessLogTable;

    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->accessLogTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'access_log'));
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_AccessLog
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_AccessLog;
        }
        
        return self::$instance;
    }
    
    /**
     * add login entry to the access log
     *
     * @param string $_sessionId the session id
     * @param string $_loginId the loginname as provided by the user
     * @param string $_ipAddress the ip address the user connects from
     * @param int $_result the result of the login
     * @param int $_accountId OPTIONAL the accountId of the user, if the login was successfull
     */
    public function addLoginEntry($_sessionId, $_loginId, $_ipAddress, $_result, $_accountId = NULL)
    {
        $data = array(
            'sessionid'     => $_sessionId,
            'login_name'    => $_loginId,
            'ip'            => $_ipAddress,
            'li'            => time(),
            'result'        => $_result
        );
        if($_accountId !== NULL) {
            $data['account_id'] = $_accountId;
        }
        
        if(Zend_Registry::get('configFile')->database->get('egw14compat') == 1) {
            unset($data['result']);
        }
        
        $this->accessLogTable->insert($data);
    }

    /**
     * add logout entry to the access log
     *
     * @param string $_sessionId the session id
     * @param string $_ipAddress the ip address the user connects from
     *      
     */
    public function addLogoutEntry($_sessionId, $_ipAddress)
    {
        $data = array(
            'lo' => time()
        );
        
        $where = array(
            $this->accessLogTable->getAdapter()->quoteInto('sessionid = ?', $_sessionId),
            $this->accessLogTable->getAdapter()->quoteInto('ip = ?', $_ipAddress)
        );
        
        $this->accessLogTable->update($data, $where);
    }
    
    /**
     * delete entries from the access log
     *
     * @param array $_logIds the id of the rows which should get deleted
     * 
     * @return int the number of deleted rows
     */
    public function deleteEntries(array $_logIds)
    {
        $where  = array(
            $this->accessLogTable->getAdapter()->quoteInto('log_id IN (?)', $_logIds, 'INTEGER')
        );
         
        $result = $this->accessLogTable->delete($where);

        return $result;
    }
    
    /**
     * Enter description here...
     *
     * @param Zend_Date $_from the date from which to fetch the access log entries from
     * @param Zend_Date $_to the date to which to fetch the access log entries to
     * @param string $_sort OPTIONAL the column name to sort by
     * @param string $_dir OPTIONAL the sort direction can be ASC or DESC only
     * @param string $_filter OPTIONAL search parameter
     * @param int $_limit OPTIONAL how many applications to return
     * @param int $_start OPTIONAL offset for applications
     * 
     * @return Tinebase_RecordSet_AccessLog set of matching access log entries
     */
    public function getEntries($_filter = NULL, $_sort = 'li', $_dir = 'ASC', $_start = NULL, $_limit = NULL, $_from = NULL, $_to = NULL)
    {
        if($_from instanceof Zend_Date && $_to instanceof Zend_Date) {
            $where = array(
                'li BETWEEN ' . $_from->getTimestamp() . ' AND ' . $_to->getTimestamp()
            );
        } elseif ($_from instanceof Zend_Date) {
            $where = array(
                $this->accessLogTable->getAdapter()->quoteInto('li > ?', $_from->getTimestamp())
            );
        }
        
        if(!empty($_filter)) {
            $where[] = $this->accessLogTable->getAdapter()->quoteInto('login_name LIKE ?', '%' . $_filter . '%');
        }

        $rowSet = $this->accessLogTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);
        
        $arrayRowSet = $rowSet->toArray();
        
        foreach($arrayRowSet as $rowId => $row) {
            if($row['lo'] >= $row['li']) {
                $row['lo'] = new Zend_Date($row['lo'], Zend_Date::TIMESTAMP);
            } else {
                $row['lo'] = NULL;
            }
            $row['li'] = new Zend_Date($row['li'], Zend_Date::TIMESTAMP);
            $arrayRowSet[$rowId] = $row;
        }

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_AccessLog', $arrayRowSet);

        return $result;
    }

    /**
     * get the total number of accesslog entries
     *
     * @return int
     */
    public function getTotalCount(Zend_Date $_from, Zend_Date $_to, $_filter = NULL)
    {
        $where = array(
            'li BETWEEN ' . $_from->getTimestamp() . ' AND ' . $_to->getTimestamp()
        );
        if(!empty($_filter)) {
            $where[] = $this->accessLogTable->getAdapter()->quoteInto('login_name LIKE ?', '%' . $_filter . '%');
        }

        $count = $this->accessLogTable->getTotalCount($where);

        return $count;
    }
}