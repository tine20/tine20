<?php
/**
 * the class provides functions to handle the accesslog
 *
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

class Egwbase_AccessLog
{
    /**
     * the table object for the egw_applications table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $accessLogTable;

    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->accessLogTable = new Egwbase_Db_Table(array('name' => 'egw_access_log'));
    }

    public function addLoginEntry($_sessionId, $_loginId, $_ipAddress, $_result, $_accountId = NULL)
    {
        $data = array(
            'sessionid'  => $_sessionId,
            'loginid'    => $_loginId,
            'ip'         => $_ipAddress,
            'li'         => time(),
            'result'     => $_result
        );
        if($_accountId !== NULL) {
            $data['account_id'] = $_accountId;
        }
        
        if(Zend_Registry::get('dbConfig')->get('egw14compat') == 1) {
            unset($data['result']);
        }
        
        $this->accessLogTable->insert($data);
    }

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
    
    public function deleteEntries(array $_logIds)
    {
        $where  = array(
            $this->accessLogTable->getAdapter()->quoteInto('log_id IN (?)', $_logIds, 'INTEGER')
        );
         
        $result = $this->accessLogTable->delete($where);

        return $result;
    }
    
    /**
     * get list of installed applications
     *
     * @param string $_sort optional the column name to sort by
     * @param string $_dir optional the sort direction can be ASC or DESC only
     * @param string $_filter optional search parameter
     * @param int $_limit optional how many applications to return
     * @param int $_start optional offset for applications
     * @return Egwbase_RecordSet_Application
     */
    public function getEntries(Zend_Date $_from, Zend_Date $_to, $_sort = 'li', $_dir = 'ASC', $_filter = NULL, $_limit = NULL, $_start = NULL)
    {
        $where = array(
            'li BETWEEN ' . $_from->getTimestamp() . ' AND ' . $_to->getTimestamp()
        );
        if(!empty($_filter)) {
            $where[] = $this->accessLogTable->getAdapter()->quoteInto('loginid LIKE ?', '%' . $_filter . '%');
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

        $result = new Egwbase_RecordSet_AccessLog($arrayRowSet, 'Egwbase_Record_AccessLog');

        return $result;
    }

    /**
     * return the total number of accesslog entries
     *
     * @return int
     */
    public function getTotalCount()
    {
        $count = $this->accessLogTable->getTotalCount();

        return $count;
    }
}