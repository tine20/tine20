<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 * @todo        refactor this -> use abstract sql backend / tine filter logic
 * @todo        remove legacy code
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
    
    // legacy
    // @todo remove it!
	/**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
	/**
     * the table object for the SQL_TABLE_PREFIX . applications table
     *
     * @var Tinebase_Db_Table
     */
    protected $_accessLogTable;
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
	    $this->_accessLogTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'access_log'));
        $this->_db = Tinebase_Core::getDb();
        
        $this->_modelName = 'Tinebase_Model_AccessLog';
        $this->_omitModLog = TRUE;
        $this->_doContainerACLChecks = FALSE;
        
        $this->_backend = new Tinebase_Backend_Sql($this->_modelName, 'access_log');
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
     * add logout entry to the access log
     *
     * @param string $_sessionId the session id
     * @param string $_ipAddress the ip address the user connects from
     * @return Tinebase_Model_AccessLog
     */
    public function setLogout($_sessionId, $_ipAddress = NULL)
    {
        $loginRecord = $this->_backend->getByProperty($_sessionId, 'sessionid');
        
        $loginRecord->lo = Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
        if ($_ipAddress !== NULL) {
            $loginRecord->ip = $_ipAddress;
        }
        
        return $this->update($loginRecord);
    }
    
    /**
     * Search for acceslog entries
     *
     * @param Zend_Date $_from the date from which to fetch the access log entries from
     * @param Zend_Date $_to the date to which to fetch the access log entries to
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param string $_sort OPTIONAL the column name to sort by
     * @param string $_dir OPTIONAL the sort direction can be ASC or DESC only
     * @param string $_filter OPTIONAL search parameter
     * @param int $_limit OPTIONAL how many applications to return
     * @param int $_start OPTIONAL offset for applications
     * 
     * @return Tinebase_RecordSet_AccessLog set of matching access log entries
     * 
     * @todo remove legacy code
     */
    public function getEntries($_filter = NULL, $_pagination = NULL, $_from = NULL, $_to = NULL)
    {
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'access_log');
            
        if($_pagination instanceof Tinebase_Model_Pagination) {
            $_pagination->appendPaginationSql($select);
        }
        
        if ($_from instanceof Zend_Date && $_to instanceof Zend_Date) {
            $select->where(
                $this->_db->quoteInto($this->_accessLogTable->getAdapter()->quoteIdentifier('li') . ' BETWEEN ? ', $_from->get(Tinebase_Record_Abstract::ISO8601LONG)) .
                $this->_db->quoteInto('AND ?', $_to->get(Tinebase_Record_Abstract::ISO8601LONG))
            );
        } elseif ($_from instanceof Zend_Date) {
            $select->where(
                $this->_db->quoteInto($this->_accessLogTable->getAdapter()->quoteIdentifier('li') . ' > ?', $_from->get(Tinebase_Record_Abstract::ISO8601LONG))
            );
        }
                
        if(!empty($_filter)) {
            $select->where(
                $this->_db->quoteInto($this->_accessLogTable->getAdapter()->quoteIdentifier('login_name') . ' LIKE ?', '%' . $_filter . '%')
            );
        }
        
        $stmt = $select->query();

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        foreach ($rows as $rowId => &$row) {
            if ($row['lo'] >= $row['li']) {
                $row['lo'] = new Zend_Date($row['lo'], Tinebase_Record_Abstract::ISO8601LONG);
            } else {
                $row['lo'] = NULL;
            }
            $row['li'] = new Zend_Date($row['li'], Tinebase_Record_Abstract::ISO8601LONG);
        }

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_AccessLog', $rows);

        return $result;
    }

    /**
     * get the total number of accesslog entries
     * 
     * @param Zend_Date $_from the date from which to fetch the access log entries from
     * @param Zend_Date $_to the date to which to fetch the access log entries to
     * @param string $_filter OPTIONAL search parameter
     * 
     * @return int
     * 
     * @todo remove legacy code
     */
    public function getTotalCount(Zend_Date $_from, Zend_Date $_to, $_filter = NULL)
    {
        $where = array(
           $this->_accessLogTable->getAdapter()->quoteIdentifier('li') .  ' BETWEEN ' .$this->_accessLogTable->getAdapter()->quote($_from->get(Tinebase_Record_Abstract::ISO8601LONG)) . ' AND ' . $this->_accessLogTable->getAdapter()->quote($_to->get(Tinebase_Record_Abstract::ISO8601LONG))
        );
        if( !empty($_filter) ) {
            $where[] = $this->_accessLogTable->getAdapter()->quoteInto($this->_accessLogTable->getAdapter()->quoteIdentifier('login_name') . ' LIKE ?', '%' . $_filter . '%');
        }

        $count = $this->_accessLogTable->getTotalCount($where);

        return $count;
    }
}
